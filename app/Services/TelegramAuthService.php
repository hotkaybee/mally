<?php

namespace App\Services;

use App\Models\Customer;
use App\Models\Order;
use App\Models\User;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use Throwable;

class TelegramAuthService
{
    public const TOKEN_TTL_SECONDS = 900; // 15 minutes

    /**
     * Dedicated token for the Auth/Verification Telegram Bot (separate from the Admin bot).
     */
    public function getBotToken(): string
    {
        if (app()->environment('local') && ! app()->runningUnitTests()) {
            $envVal = $this->readDotEnvValue('TELEGRAM_AUTH_BOT_TOKEN') ?: $this->readDotEnvValue('TELEGRAM_BOT_TOKEN');
            if ($envVal !== '') {
                return $envVal;
            }
        }

        return trim((string) (config('services.telegram.auth_bot_token') ?: config('services.telegram.bot_token')));
    }

    /**
     * Dedicated username for the Auth/Verification Telegram Bot.
     */
    public function getBotUsername(): string
    {
        if (app()->environment('local') && ! app()->runningUnitTests()) {
            $envVal = $this->readDotEnvValue('TELEGRAM_AUTH_BOT_USERNAME') ?: $this->readDotEnvValue('TELEGRAM_BOT_USERNAME');
            if ($envVal !== '') {
                return ltrim($envVal, '@');
            }
        }

        $username = trim((string) (config('services.telegram.auth_bot_username') ?: config('services.telegram.bot_username', 'MallyAdmin_bot')));

        return ltrim($username, '@');
    }

    protected function readDotEnvValue(string $key): string
    {
        $envPath = base_path('.env');
        if (! is_file($envPath)) {
            return '';
        }

        $lines = @file($envPath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        if (! is_array($lines)) {
            return '';
        }

        foreach ($lines as $line) {
            $trimmed = trim($line);
            if ($trimmed === '' || str_starts_with($trimmed, '#')) {
                continue;
            }
            if (str_starts_with($trimmed, $key.'=')) {
                $val = trim(substr($trimmed, strlen($key) + 1));

                return trim($val, "\"'");
            }
        }

        return '';
    }

    /**
     * Create a new Telegram verification token and return the deep link URL.
     *
     * @return array{token: string, bot_url: string, bot_username: string}
     */
    public function startAuthSession(): array
    {
        $token = bin2hex(random_bytes(16));
        $botUsername = $this->getBotUsername();

        Cache::put($this->tokenCacheKey($token), [
            'status' => 'pending',
            'created_at' => time(),
        ], self::TOKEN_TTL_SECONDS);

        return [
            'token' => $token,
            'bot_username' => $botUsername,
            'bot_url' => "https://t.me/{$botUsername}?start=auth_{$token}",
        ];
    }

    /**
     * Poll Telegram Bot API `getUpdates`.
     */
    public function pollUpdates(int $longPollTimeout = 0): void
    {
        $botToken = $this->getBotToken();
        if ($botToken === '') {
            return;
        }

        $offsetKey = 'tg_auth_update_offset:'.substr(sha1($botToken), 0, 12);
        $offset = (int) Cache::get($offsetKey, 0);

        try {
            $httpTimeout = max(3, $longPollTimeout + 2);
            $response = Http::timeout($httpTimeout)
                ->connectTimeout(2)
                ->get("https://api.telegram.org/bot{$botToken}/getUpdates", [
                    'offset' => $offset > 0 ? $offset : null,
                    'limit' => 30,
                    'timeout' => $longPollTimeout,
                    'allowed_updates' => json_encode(['message']),
                ]);

            if (! $response->successful()) {
                return;
            }

            $updates = $response->json('result', []);
            if (! is_array($updates)) {
                return;
            }

            foreach ($updates as $update) {
                if (! is_array($update)) {
                    continue;
                }
                $updateId = (int) ($update['update_id'] ?? 0);
                if ($updateId > 0) {
                    Cache::put($offsetKey, $updateId + 1, 86400 * 7);
                }

                $this->handleTelegramUpdate($update);
            }
        } catch (Throwable) {
            // Ignore network timeouts during background polling
        }
    }

    /**
     * Handle a single Telegram update (from `getUpdates` or webhook).
     */
    public function handleTelegramUpdate(array $update): void
    {
        $message = $update['message'] ?? null;
        if (! is_array($message)) {
            return;
        }

        $chatId = $message['chat']['id'] ?? null;
        if (! $chatId) {
            return;
        }

        $from = is_array($message['from'] ?? null) ? $message['from'] : [];
        $text = trim((string) ($message['text'] ?? ''));

        // 1. Handle `/start auth_{token}`
        if (str_starts_with($text, '/start')) {
            $parts = preg_split('/\s+/', $text, 2);
            $payload = trim((string) ($parts[1] ?? ''));
            if (str_starts_with($payload, 'auth_')) {
                $payload = substr($payload, 5);
            }

            if ($payload !== '' && Cache::has($this->tokenCacheKey($payload))) {
                Cache::put($this->chatCacheKey((string) $chatId), $payload, self::TOKEN_TTL_SECONDS);

                $existing = (array) Cache::get($this->tokenCacheKey($payload), []);
                $existing['status'] = 'awaiting_contact';
                $existing['chat_id'] = (string) $chatId;
                $existing['telegram_id'] = (string) ($from['id'] ?? $chatId);
                $existing['telegram_username'] = trim((string) ($from['username'] ?? ''));
                $existing['first_name'] = trim((string) ($from['first_name'] ?? ''));
                $existing['last_name'] = trim((string) ($from['last_name'] ?? ''));

                Cache::put($this->tokenCacheKey($payload), $existing, self::TOKEN_TTL_SECONDS);

                $this->sendContactRequest((string) $chatId);

                return;
            }

            $this->sendMessage(
                (string) $chatId,
                '👋 Здравствуйте! Чтобы войти или зарегистрироваться на сайте Mally Store, нажмите кнопку «Продолжить через Telegram» в окне входа на сайте.'
            );

            return;
        }

        // 2. Handle shared contact (`message.contact`)
        if (isset($message['contact']) && is_array($message['contact'])) {
            $contact = $message['contact'];
            $fromId = (string) ($from['id'] ?? '');
            $contactUserId = (string) ($contact['user_id'] ?? $fromId);

            if ($fromId !== '' && $contactUserId !== '' && $fromId !== $contactUserId) {
                $this->sendMessage(
                    (string) $chatId,
                    '⚠️ Пожалуйста, поделитесь своим собственным контактом с помощью кнопки «📱 Поделиться контактом» внизу экрана.'
                );

                return;
            }

            $token = (string) Cache::get($this->chatCacheKey((string) $chatId), '');
            if ($token === '' || ! Cache::has($this->tokenCacheKey($token))) {
                $this->sendMessage(
                    (string) $chatId,
                    '⏳ Сессия подтверждения истекла или не найдена. Пожалуйста, нажмите «Продолжить через Telegram» на сайте ещё раз.',
                    ['remove_keyboard' => true]
                );

                return;
            }

            $rawPhone = (string) ($contact['phone_number'] ?? '');
            $normalizedPhone = $this->normalizeTelegramPhone($rawPhone);

            if ($normalizedPhone === '') {
                $this->sendMessage(
                    (string) $chatId,
                    '⚠️ Не удалось распознать номер телефона. Попробуйте ещё раз.'
                );

                return;
            }

            $firstName = trim((string) ($contact['first_name'] ?? $from['first_name'] ?? ''));
            $lastName = trim((string) ($contact['last_name'] ?? $from['last_name'] ?? ''));
            $username = trim((string) ($from['username'] ?? ''));

            Cache::put($this->tokenCacheKey($token), [
                'status' => 'verified',
                'phone' => $normalizedPhone,
                'telegram_id' => $fromId ?: (string) $chatId,
                'telegram_username' => $username,
                'first_name' => $firstName,
                'last_name' => $lastName,
                'verified_at' => time(),
            ], self::TOKEN_TTL_SECONDS);

            Cache::forget($this->chatCacheKey((string) $chatId));

            $this->sendMessage(
                (string) $chatId,
                "✅ Номер {$normalizedPhone} успешно подтверждён!\n\nВернитесь на сайт Mally Store — вход или регистрация выполнится автоматически.",
                ['remove_keyboard' => true]
            );
        }
    }

    /**
     * Check current status of a website Telegram auth token.
     *
     * @return array{
     *   status: string,
     *   user?: User,
     *   customer?: Customer,
     *   phone?: string,
     *   name?: string,
     *   telegram_username?: string,
     *   merge_user?: array{id: int, name: string, email: string}
     * }
     */
    public function checkTokenStatus(string $token, bool $poll = true): array
    {
        $cleanToken = trim($token);
        if ($cleanToken === '') {
            return ['status' => 'expired'];
        }

        // Only poll directly from web request if background daemon is not actively polling
        if ($poll && ! Cache::has('mally_tg_poller_alive')) {
            $this->pollUpdates(0);
        }

        $data = Cache::get($this->tokenCacheKey($cleanToken));
        if (! is_array($data)) {
            return ['status' => 'expired'];
        }

        $status = (string) ($data['status'] ?? 'pending');
        if ($status !== 'verified') {
            return ['status' => $status];
        }

        $phone = trim((string) ($data['phone'] ?? ''));
        if ($phone === '') {
            return ['status' => 'expired'];
        }

        $firstName = trim((string) ($data['first_name'] ?? ''));
        $lastName = trim((string) ($data['last_name'] ?? ''));
        $fullName = trim($firstName.' '.$lastName);
        $telegramUsername = trim((string) ($data['telegram_username'] ?? ''));

        $matchingUsers = $this->findAllUsersByPhone($phone);

        if ($matchingUsers->isNotEmpty()) {
            // Prefer user with a real email (Gmail etc.) as primary candidate
            $realEmailUser = $matchingUsers->first(fn (User $u) => ! $this->isSyntheticTelegramEmail((string) $u->email));
            $primaryUser = $realEmailUser ?? $matchingUsers->first();

            // Check if we should ask the user to confirm merging with their existing Gmail/Email account:
            // Ask when:
            // 1) There is a real email account (e.g. Gmail) that hasn't linked Telegram yet, OR
            // 2) There are multiple User records with this phone number (e.g. one Gmail + one Telegram)
            $customer = $this->findCustomerForUser($primaryUser, $phone);
            $alreadyLinkedToTelegram = $customer
                && ! empty($customer->telegram)
                && $matchingUsers->count() === 1;

            if ($realEmailUser && ! $alreadyLinkedToTelegram) {
                Cache::forget($this->tokenCacheKey($cleanToken));

                return [
                    'status' => 'merge_confirm',
                    'phone' => $phone,
                    'name' => $fullName !== '' ? $fullName : $realEmailUser->name,
                    'telegram_username' => $telegramUsername,
                    'merge_user' => [
                        'id' => (int) $realEmailUser->id,
                        'name' => (string) $realEmailUser->name,
                        'email' => (string) $realEmailUser->email,
                    ],
                ];
            }

            // Already linked Telegram account -> merge any stray duplicates and log in immediately
            Cache::forget($this->tokenCacheKey($cleanToken));
            $merged = $this->mergePhoneAccountsIntoPrimary($primaryUser, $phone, $telegramUsername, $fullName, true);

            return [
                'status' => 'login',
                'user' => $merged['user'],
                'customer' => $merged['customer'],
                'phone' => $phone,
            ];
        }

        // Also check if a Customer record exists by phone even without a User record
        $existingCustomer = $this->findCustomerByPhone($phone);
        if ($existingCustomer && ! empty($existingCustomer->email) && ! $this->isSyntheticTelegramEmail($existingCustomer->email)) {
            $userByCustomerEmail = User::whereRaw('LOWER(email) = ?', [mb_strtolower(trim($existingCustomer->email))])->first();
            if ($userByCustomerEmail) {
                Cache::forget($this->tokenCacheKey($cleanToken));

                return [
                    'status' => 'merge_confirm',
                    'phone' => $phone,
                    'name' => $fullName !== '' ? $fullName : $userByCustomerEmail->name,
                    'telegram_username' => $telegramUsername,
                    'merge_user' => [
                        'id' => (int) $userByCustomerEmail->id,
                        'name' => (string) $userByCustomerEmail->name,
                        'email' => (string) $userByCustomerEmail->email,
                    ],
                ];
            }
        }

        Cache::forget($this->tokenCacheKey($cleanToken));

        return [
            'status' => 'register',
            'phone' => $phone,
            'name' => $fullName !== '' ? $fullName : 'Покупатель',
            'telegram_username' => $telegramUsername,
        ];
    }

    /**
     * Merge all User and Customer accounts sharing `$verifiedPhone` (or `$primaryUser->email`)
     * into `$primaryUser`, transferring 100% of orders (`Order::whereIn('customer_id', ...)`)
     * inside a DB transaction.
     *
     * @return array{user: User, customer: Customer}
     */
    public function mergePhoneAccountsIntoPrimary(
        User $primaryUser,
        string $verifiedPhone,
        string $telegramUsername = '',
        string $fallbackName = '',
        bool $markTelegramVerified = true
    ): array {
        $normalizedPhone = $this->normalizeTelegramPhone($verifiedPhone);

        return DB::transaction(function () use ($primaryUser, $normalizedPhone, $telegramUsername, $fallbackName, $markTelegramVerified) {
            $primaryUser = User::whereKey($primaryUser->id)->lockForUpdate()->first() ?? $primaryUser;

            $allPhoneUsers = $this->findAllUsersByPhone($normalizedPhone);
            $realEmailUser = $allPhoneUsers->first(fn (User $u) => ! $this->isSyntheticTelegramEmail((string) $u->email));
            if ($realEmailUser && $realEmailUser->id !== $primaryUser->id && $this->isSyntheticTelegramEmail((string) $primaryUser->email)) {
                $primaryUser = $realEmailUser;
            }

            // Collect all related Customer records by phone or email
            $relatedCustomerIds = collect();

            $primaryEmail = mb_strtolower(trim((string) $primaryUser->email));
            if ($primaryEmail !== '') {
                $byEmailIds = Customer::whereRaw('LOWER(email) = ?', [$primaryEmail])->pluck('id');
                $relatedCustomerIds = $relatedCustomerIds->merge($byEmailIds);
            }

            foreach ($allPhoneUsers as $u) {
                $uEmail = mb_strtolower(trim((string) $u->email));
                if ($uEmail !== '') {
                    $ids = Customer::whereRaw('LOWER(email) = ?', [$uEmail])->pluck('id');
                    $relatedCustomerIds = $relatedCustomerIds->merge($ids);
                }
            }

            $byPhoneCustomers = $this->findAllCustomersByPhone($normalizedPhone);
            $relatedCustomerIds = $relatedCustomerIds->merge($byPhoneCustomers->pluck('id'))->unique()->values();

            $primaryCustomer = null;
            $preservedTelegramHandle = '';
            if ($relatedCustomerIds->isNotEmpty()) {
                $customers = Customer::whereIn('id', $relatedCustomerIds)->orderBy('id')->get();
                foreach ($customers as $c) {
                    if (! empty($c->telegram) && $preservedTelegramHandle === '') {
                        $preservedTelegramHandle = (string) $c->telegram;
                    }
                }
                // Prefer customer with a real email, or the oldest customer record
                $primaryCustomer = $customers->first(fn (Customer $c) => ! empty($c->email) && ! $this->isSyntheticTelegramEmail((string) $c->email))
                    ?? $customers->first();

                $otherCustomerIds = $customers->pluck('id')
                    ->reject(fn ($id) => (int) $id === (int) $primaryCustomer->id)
                    ->values();

                if ($otherCustomerIds->isNotEmpty()) {
                    // Reassign all orders from secondary customers to the primary customer!
                    Order::whereIn('customer_id', $otherCustomerIds)->update([
                        'customer_id' => $primaryCustomer->id,
                    ]);

                    Customer::whereIn('id', $otherCustomerIds)->delete();
                }
            }

            $wasMerged = $allPhoneUsers->count() > 1
                || $relatedCustomerIds->count() > 1
                || ($preservedTelegramHandle !== '' && ! $markTelegramVerified);

            // Delete any duplicate synthetic Telegram User records now merged into $primaryUser
            foreach ($allPhoneUsers as $otherUser) {
                if ((int) $otherUser->id !== (int) $primaryUser->id && $this->isSyntheticTelegramEmail((string) $otherUser->email)) {
                    DB::table('sessions')->where('user_id', $otherUser->id)->update(['user_id' => $primaryUser->id]);
                    $otherUser->delete();
                }
            }

            $userUpdates = ['phone' => $normalizedPhone];
            if (trim((string) $primaryUser->name) === '' && $fallbackName !== '') {
                $userUpdates['name'] = $fallbackName;
            }
            $primaryUser->update($userUpdates);

            $effectiveTg = $telegramUsername !== '' ? $telegramUsername : $preservedTelegramHandle;
            $customer = $this->syncCustomerForTelegramUser($primaryUser, $effectiveTg, $primaryCustomer, $markTelegramVerified || $preservedTelegramHandle !== '');

            return [
                'user' => $primaryUser->fresh() ?? $primaryUser,
                'customer' => $customer,
                'merged' => $wasMerged,
            ];
        });
    }

    /**
     * If user declines merging with the old unverified-phone account, detach the phone
     * from the old account so the verified Telegram user can register a separate account.
     */
    public function detachPhoneFromOtherAccounts(string $verifiedPhone): void
    {
        $normalizedPhone = $this->normalizeTelegramPhone($verifiedPhone);
        if ($normalizedPhone === '') {
            return;
        }

        DB::transaction(function () use ($normalizedPhone) {
            $users = $this->findAllUsersByPhone($normalizedPhone);
            foreach ($users as $u) {
                if (! $this->isSyntheticTelegramEmail((string) $u->email)) {
                    $u->update(['phone' => null]);
                }
            }
        });
    }

    /**
     * Complete registration for a new user whose phone was verified via Telegram bot.
     * Password is REQUIRED (min 8 chars). Email (Gmail) is OPTIONAL.
     *
     * @return array{
     *   success: bool,
     *   error?: string,
     *   field?: string,
     *   message: string,
     *   user?: User,
     *   customer?: Customer,
     *   merge_user?: array{id: int, name: string, email: string}
     * }
     */
    public function registerTelegramUser(
        string $verifiedPhone,
        string $name,
        ?string $optionalEmail = null,
        ?string $plainPassword = null,
        ?string $telegramUsername = null
    ): array {
        $cleanName = trim($name);
        if ($cleanName === '') {
            return [
                'success' => false,
                'field' => 'authName',
                'message' => 'Пожалуйста, укажите ваше имя.',
            ];
        }

        $normalizedPhone = $this->normalizeTelegramPhone($verifiedPhone);
        if ($normalizedPhone === '') {
            return [
                'success' => false,
                'field' => 'authPhone',
                'message' => 'Сначала подтвердите номер телефона через Telegram-бота.',
            ];
        }

        $rawEmail = mb_strtolower(trim((string) $optionalEmail));
        if ($rawEmail !== '' && filter_var($rawEmail, FILTER_VALIDATE_EMAIL) === false) {
            return [
                'success' => false,
                'field' => 'authEmail',
                'message' => 'Введите корректный email или оставьте поле пустым.',
            ];
        }

        $rawPassword = (string) $plainPassword;
        if (mb_strlen($rawPassword) < 8) {
            return [
                'success' => false,
                'field' => 'authPassword',
                'message' => 'Пароль обязателен и должен содержать минимум 8 символов.',
            ];
        }

        return DB::transaction(function () use ($cleanName, $normalizedPhone, $rawEmail, $rawPassword, $telegramUsername) {
            // If user entered an existing Gmail/Email on the Telegram registration screen:
            if ($rawEmail !== '') {
                $existingEmailUser = User::whereRaw('LOWER(email) = ?', [$rawEmail])->lockForUpdate()->first();
                if ($existingEmailUser) {
                    return [
                        'success' => false,
                        'error' => 'merge_needed',
                        'field' => 'authEmail',
                        'message' => 'Аккаунт с этим email уже существует. Подтвердите объединение аккаунтов.',
                        'merge_user' => [
                            'id' => (int) $existingEmailUser->id,
                            'name' => (string) $existingEmailUser->name,
                            'email' => (string) $existingEmailUser->email,
                        ],
                    ];
                }
                $userEmail = $rawEmail;
            } else {
                $digits = preg_replace('/\D/', '', $normalizedPhone);
                $userEmail = "tg_{$digits}@telegram.mally.local";
                if (User::whereRaw('LOWER(email) = ?', [$userEmail])->exists()) {
                    $userEmail = "tg_{$digits}_".strtolower(Str::random(4)).'@telegram.mally.local';
                }
            }

            $existingByPhone = $this->findUserByPhone($normalizedPhone);
            if ($existingByPhone) {
                $existingByPhone->update([
                    'name' => $cleanName,
                    'email' => $rawEmail !== '' ? $rawEmail : $existingByPhone->email,
                    'phone' => $normalizedPhone,
                    'password' => Hash::make($rawPassword),
                ]);

                $merged = $this->mergePhoneAccountsIntoPrimary($existingByPhone, $normalizedPhone, (string) $telegramUsername, $cleanName);

                return [
                    'success' => true,
                    'message' => 'Регистрация через Telegram успешно завершена.',
                    'user' => $merged['user'],
                    'customer' => $merged['customer'],
                ];
            }

            $user = User::create([
                'name' => $cleanName,
                'email' => $userEmail,
                'phone' => $normalizedPhone,
                'password' => Hash::make($rawPassword),
            ]);

            $merged = $this->mergePhoneAccountsIntoPrimary($user, $normalizedPhone, (string) $telegramUsername, $cleanName);

            return [
                'success' => true,
                'message' => 'Регистрация через Telegram успешно завершена.',
                'user' => $merged['user'],
                'customer' => $merged['customer'],
            ];
        });
    }

    public function isSyntheticTelegramEmail(?string $email): bool
    {
        $clean = mb_strtolower(trim((string) $email));

        return $clean !== '' && str_ends_with($clean, '@telegram.mally.local');
    }

    public function normalizeTelegramPhone(string $rawPhone): string
    {
        $digits = preg_replace('/\D/', '', $rawPhone);
        if ($digits === '') {
            return '';
        }

        if (strlen($digits) === 9) {
            $digits = '998'.$digits;
        }

        if (strlen($digits) === 12 && str_starts_with($digits, '998')) {
            return sprintf(
                '+998 %s %s %s %s',
                substr($digits, 3, 2),
                substr($digits, 5, 3),
                substr($digits, 8, 2),
                substr($digits, 10, 2)
            );
        }

        return '+'.$digits;
    }

    public function findUserByPhone(string $phone): ?User
    {
        return $this->findAllUsersByPhone($phone)->first();
    }

    /**
     * @return \Illuminate\Support\Collection<int, User>
     */
    public function findAllUsersByPhone(string $phone): \Illuminate\Support\Collection
    {
        $targetDigits = preg_replace('/\D/', '', $phone);
        if ($targetDigits === '') {
            return collect();
        }

        $shortDigits = strlen($targetDigits) > 9 ? substr($targetDigits, -9) : $targetDigits;

        $candidates = User::whereNotNull('phone')
            ->orderBy('id')
            ->get();

        return $candidates->filter(function (User $candidate) use ($targetDigits, $shortDigits) {
            $candDigits = preg_replace('/\D/', '', (string) $candidate->phone);

            return $candDigits !== '' && (
                $candDigits === $targetDigits
                || (strlen($candDigits) >= 9 && substr($candDigits, -9) === $shortDigits)
            );
        })->values();
    }

    /**
     * @return \Illuminate\Support\Collection<int, Customer>
     */
    public function findAllCustomersByPhone(string $phone): \Illuminate\Support\Collection
    {
        $targetDigits = preg_replace('/\D/', '', $phone);
        if ($targetDigits === '') {
            return collect();
        }

        $shortDigits = strlen($targetDigits) > 9 ? substr($targetDigits, -9) : $targetDigits;

        $candidates = Customer::whereNotNull('phone_number')
            ->orderBy('id')
            ->get();

        return $candidates->filter(function (Customer $cand) use ($targetDigits, $shortDigits) {
            $candDigits = preg_replace('/\D/', '', (string) $cand->phone_number);

            return $candDigits !== '' && (
                $candDigits === $targetDigits
                || (strlen($candDigits) >= 9 && substr($candDigits, -9) === $shortDigits)
            );
        })->values();
    }

    public function findCustomerByPhone(string $phone): ?Customer
    {
        return $this->findAllCustomersByPhone($phone)->first();
    }

    protected function findCustomerForUser(User $user, string $phone): ?Customer
    {
        $email = mb_strtolower(trim((string) $user->email));
        if ($email !== '') {
            $byEmail = Customer::whereRaw('LOWER(email) = ?', [$email])->first();
            if ($byEmail) {
                return $byEmail;
            }
        }

        return $this->findCustomerByPhone($phone);
    }

    /**
     * Synchronize Customer record for a Telegram-verified User.
     * Note: `customers.email` has a NOT NULL constraint in SQLite, and `customers.source` has a CHECK constraint (`instagram`),
     * so we always store a non-null email string and keep `source = 'instagram'`, tracking Telegram verification via `customers.telegram`.
     */
    public function syncCustomerForTelegramUser(
        User $user,
        string $telegramUsername = '',
        ?Customer $preferredCustomer = null,
        bool $markTelegramVerified = true
    ): Customer {
        $emailForCustomer = mb_strtolower(trim((string) $user->email));
        if ($emailForCustomer === '') {
            $digits = preg_replace('/\D/', '', (string) $user->phone) ?: 'user'.$user->id;
            $emailForCustomer = "tg_{$digits}@telegram.mally.local";
        }

        $parts = explode(' ', trim(preg_replace('/\s+/', ' ', (string) $user->name)), 2);
        $firstName = $parts[0] ?? 'Покупатель';
        $lastName = $parts[1] ?? '';

        $customer = $preferredCustomer
            ?? Customer::whereRaw('LOWER(email) = ?', [$emailForCustomer])->first()
            ?? (! empty($user->phone) ? $this->findCustomerByPhone((string) $user->phone) : null);

        $cleanTg = ltrim(trim($telegramUsername), '@');
        $tgHandle = $cleanTg !== '' ? '@'.$cleanTg : ($markTelegramVerified ? '@tg_verified' : null);

        if ($customer) {
            $updates = [];
            if (empty($customer->email) || ($this->isSyntheticTelegramEmail((string) $customer->email) && ! $this->isSyntheticTelegramEmail($emailForCustomer))) {
                $updates['email'] = $emailForCustomer;
            }
            if (! empty($user->phone) && $customer->phone_number !== $user->phone) {
                $updates['phone_number'] = $user->phone;
            }
            if ($cleanTg !== '') {
                $updates['telegram'] = '@'.$cleanTg;
            } elseif ($markTelegramVerified && empty($customer->telegram)) {
                $updates['telegram'] = '@tg_verified';
            }

            if (! empty($updates)) {
                $customer->update($updates);
            }

            return $customer;
        }

        return Customer::create([
            'first_name' => $firstName ?: 'Покупатель',
            'last_name' => $lastName,
            'email' => $emailForCustomer,
            'phone_number' => $user->phone,
            'telegram' => $tgHandle,
            'instagram' => null,
            'source' => 'instagram',
        ]);
    }

    protected function sendContactRequest(string $chatId): void
    {
        $text = "👋 Здравствуйте!\n\nДля входа или регистрации в Mally Store без кода нажмите кнопку «📱 Поделиться контактом» внизу экрана:";

        $replyMarkup = [
            'keyboard' => [
                [
                    [
                        'text' => '📱 Поделиться контактом',
                        'request_contact' => true,
                    ],
                ],
            ],
            'resize_keyboard' => true,
            'one_time_keyboard' => true,
        ];

        $this->sendMessage($chatId, $text, $replyMarkup);
    }

    protected function sendMessage(string $chatId, string $text, ?array $replyMarkup = null): void
    {
        $botToken = $this->getBotToken();
        if ($botToken === '') {
            return;
        }

        $payload = [
            'chat_id' => $chatId,
            'text' => $text,
        ];

        if ($replyMarkup !== null) {
            $payload['reply_markup'] = json_encode($replyMarkup, JSON_UNESCAPED_UNICODE);
        }

        try {
            Http::timeout(4)
                ->connectTimeout(2)
                ->post("https://api.telegram.org/bot{$botToken}/sendMessage", $payload);
        } catch (Throwable) {
            // Ignore Telegram HTTP send errors
        }
    }

    public function tokenCacheKey(string $token): string
    {
        return 'mally_tg_auth_token:'.trim($token);
    }

    public function chatCacheKey(string $chatId): string
    {
        return 'mally_tg_auth_chat:'.trim($chatId);
    }
}
