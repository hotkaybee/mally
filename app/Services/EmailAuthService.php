<?php

namespace App\Services;

use App\Mail\AuthCodeMail;
use App\Models\AuthCode;
use App\Models\Customer;
use App\Models\User;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\RateLimiter;
use InvalidArgumentException;
use Throwable;

class EmailAuthService
{
    public const CODE_TTL_MINUTES = 10;

    public const COOLDOWN_SECONDS = 60;

    public const HOURLY_LIMIT_PER_EMAIL = 5;

    public const HOURLY_LIMIT_PER_IP = 5;

    public const MAX_CODE_ATTEMPTS = 5;

    public const MAX_LOGIN_ATTEMPTS = 5;

    public const LOGIN_DECAY_SECONDS = 60;

    public const GENERIC_LOGIN_ERROR = 'Неверный email или пароль.';

    public function normalizeEmail(string $rawEmail): string
    {
        return mb_strtolower(trim($rawEmail));
    }

    public function isValidEmail(string $email): bool
    {
        $normalized = $this->normalizeEmail($email);

        return $normalized !== '' && filter_var($normalized, FILTER_VALIDATE_EMAIL) !== false;
    }

    public function userExists(string $rawEmail): bool
    {
        $email = $this->normalizeEmail($rawEmail);
        if (! $this->isValidEmail($email)) {
            return false;
        }

        return User::whereRaw('LOWER(email) = ?', [$email])->exists();
    }

    public function findUserByEmail(string $rawEmail): ?User
    {
        $email = $this->normalizeEmail($rawEmail);
        if (! $this->isValidEmail($email)) {
            return null;
        }

        return User::whereRaw('LOWER(email) = ?', [$email])->first();
    }

    /**
     * Authenticate a storefront user by email and password with rate limiting.
     *
     * @return array{success: bool, error?: string, message: string, user?: User, customer?: Customer}
     */
    public function isPhoneIdentifier(string $rawInput): bool
    {
        $trimmed = trim($rawInput);
        if ($trimmed === '' || str_contains($trimmed, '@')) {
            return false;
        }

        $digits = preg_replace('/\D/', '', $trimmed);

        return strlen($digits) >= 9 && strlen($digits) <= 15;
    }

    /**
     * Authenticate a storefront user by email OR phone number and password with rate limiting.
     *
     * @return array{success: bool, error?: string, is_phone?: bool, message: string, user?: User, customer?: Customer}
     */
    public function attemptLogin(string $rawEmail, string $password, ?string $ip = null): array
    {
        $ipAddress = trim((string) ($ip ?? request()->ip() ?? '127.0.0.1'));
        $isPhone = $this->isPhoneIdentifier($rawEmail);

        if ($isPhone) {
            $tgService = app(TelegramAuthService::class);
            $normalizedPhone = $tgService->normalizeTelegramPhone($rawEmail);

            if ($password === '') {
                return [
                    'success' => false,
                    'error' => 'empty_password',
                    'is_phone' => true,
                    'message' => 'Введите пароль.',
                ];
            }

            $loginKey = $this->loginRateKey($normalizedPhone, $ipAddress);
            if (RateLimiter::tooManyAttempts($loginKey, self::MAX_LOGIN_ATTEMPTS)) {
                return [
                    'success' => false,
                    'error' => 'rate_limit',
                    'is_phone' => true,
                    'message' => 'Слишком много попыток входа. Пожалуйста, подождите минуту и попробуйте снова.',
                ];
            }

            $user = $tgService->findUserByPhone($normalizedPhone);
            if (! $user) {
                RateLimiter::hit($loginKey, self::LOGIN_DECAY_SECONDS);

                return [
                    'success' => false,
                    'error' => 'user_not_found',
                    'is_phone' => true,
                    'message' => 'Аккаунта с таким номером телефона нет.',
                ];
            }

            if (! Hash::check($password, (string) $user->password)) {
                RateLimiter::hit($loginKey, self::LOGIN_DECAY_SECONDS);

                return [
                    'success' => false,
                    'error' => 'invalid_credentials',
                    'is_phone' => true,
                    'message' => self::GENERIC_LOGIN_ERROR,
                ];
            }

            RateLimiter::clear($loginKey);
            $customer = $this->syncCustomerForUser($user);

            return [
                'success' => true,
                'is_phone' => true,
                'message' => 'Вы успешно вошли в аккаунт.',
                'user' => $user,
                'customer' => $customer,
            ];
        }

        $email = $this->normalizeEmail($rawEmail);

        if (! $this->isValidEmail($email)) {
            return [
                'success' => false,
                'error' => 'invalid_email',
                'message' => 'Введите корректный адрес электронной почты.',
            ];
        }

        if ($password === '') {
            return [
                'success' => false,
                'error' => 'empty_password',
                'message' => 'Введите пароль.',
            ];
        }

        $loginKey = $this->loginRateKey($email, $ipAddress);
        if (RateLimiter::tooManyAttempts($loginKey, self::MAX_LOGIN_ATTEMPTS)) {
            return [
                'success' => false,
                'error' => 'rate_limit',
                'message' => 'Слишком много попыток входа. Пожалуйста, подождите минуту и попробуйте снова.',
            ];
        }

        $user = $this->findUserByEmail($email);

        if (! $user) {
            RateLimiter::hit($loginKey, self::LOGIN_DECAY_SECONDS);

            return [
                'success' => false,
                'error' => 'user_not_found',
                'message' => 'Аккаунта с таким email нет.',
            ];
        }

        if (! Hash::check($password, (string) $user->password)) {
            RateLimiter::hit($loginKey, self::LOGIN_DECAY_SECONDS);

            return [
                'success' => false,
                'error' => 'invalid_credentials',
                'message' => self::GENERIC_LOGIN_ERROR,
            ];
        }

        RateLimiter::clear($loginKey);

        $customer = $this->syncCustomerForUser($user);

        return [
            'success' => true,
            'message' => 'Вы успешно вошли в аккаунт.',
            'user' => $user,
            'customer' => $customer,
        ];
    }

    /**
     * Synchronize a storefront Customer record for a given User by email.
     */
    public function syncCustomerForUser(User $user): Customer
    {
        $email = $this->normalizeEmail((string) $user->email);
        [$firstName, $lastName] = $this->splitFullName((string) $user->name);

        $customer = Customer::whereRaw('LOWER(email) = ?', [$email])->first();

        if (! $customer && ! empty($user->phone)) {
            $customer = Customer::where('phone_number', $user->phone)->first();
        }

        if ($customer) {
            $updates = [];
            if (strtolower((string) $customer->email) !== $email) {
                $updates['email'] = $email;
            }
            if (! empty($user->phone) && empty($customer->phone_number)) {
                $updates['phone_number'] = $user->phone;
            }
            if (empty($customer->first_name) && $firstName !== '') {
                $updates['first_name'] = $firstName;
            }
            if (! empty($updates)) {
                $customer->update($updates);
            }

            return $customer;
        }

        return Customer::create([
            'first_name' => $firstName ?: 'Покупатель',
            'last_name' => $lastName,
            'email' => $email,
            'phone_number' => $user->phone,
            'telegram' => null,
            'instagram' => null,
            'source' => 'instagram',
        ]);
    }

    /**
     * Sanitize redirect path so only internal paths starting with `/` (and not `//` or ` /\`) are allowed.
     */
    public function sanitizeRedirectPath(?string $redirectTo): string
    {
        $path = trim((string) $redirectTo);

        if (
            $path === ''
            || ! str_starts_with($path, '/')
            || str_starts_with($path, '//')
            || str_starts_with($path, '/\\')
            || preg_match('/^[a-zA-Z][a-zA-Z0-9+.-]*:/', $path) === 1
        ) {
            return '/';
        }

        return $path;
    }

    /**
     * Generate a 6-digit verification code, hash and store it in `auth_codes`,
     * invalidate older active codes for the same (email, purpose), and send the
     * email while enforcing a 60s cooldown and 5/hour limits per email and IP.
     *
     * @return array{success: bool, error?: string, message: string, cooldown?: int}
     */
    public function sendCode(string $rawEmail, string $purpose, ?string $ip = null, bool $deliverMail = true): array
    {
        $email = $this->normalizeEmail($rawEmail);

        if (! $this->isValidEmail($email)) {
            return [
                'success' => false,
                'error' => 'invalid_email',
                'message' => 'Введите корректный адрес электронной почты.',
            ];
        }

        if (! in_array($purpose, [AuthCode::PURPOSE_REGISTER, AuthCode::PURPOSE_RESET], true)) {
            throw new InvalidArgumentException('Invalid auth code purpose.');
        }

        $ipAddress = trim((string) ($ip ?? request()->ip() ?? '127.0.0.1'));
        $cooldownKey = $this->cooldownCacheKey($email);
        $emailRateKey = $this->emailHourlyRateKey($email);
        $ipRateKey = $this->ipHourlyRateKey($ipAddress);

        if (Cache::has($cooldownKey)) {
            $expiresAt = (int) Cache::get($cooldownKey, time() + self::COOLDOWN_SECONDS);
            $remaining = max(1, min(self::COOLDOWN_SECONDS, $expiresAt - time()));

            return [
                'success' => false,
                'error' => 'cooldown',
                'cooldown' => $remaining,
                'message' => "Повторная отправка кода возможна через {$remaining} сек.",
            ];
        }

        if (
            RateLimiter::tooManyAttempts($emailRateKey, self::HOURLY_LIMIT_PER_EMAIL)
            || RateLimiter::tooManyAttempts($ipRateKey, self::HOURLY_LIMIT_PER_IP)
        ) {
            return [
                'success' => false,
                'error' => 'rate_limit',
                'message' => 'Превышен лимит отправки кодов (не более 5 запросов в час). Попробуйте позже.',
            ];
        }

        if ($purpose === AuthCode::PURPOSE_RESET && ! $this->userExists($email)) {
            $deliverMail = false;
        }

        if (! $deliverMail) {
            Cache::put($cooldownKey, time() + self::COOLDOWN_SECONDS, self::COOLDOWN_SECONDS);
            RateLimiter::hit($emailRateKey, 3600);
            RateLimiter::hit($ipRateKey, 3600);

            return [
                'success' => true,
                'cooldown' => self::COOLDOWN_SECONDS,
                'message' => 'Если указанный email зарегистрирован, мы отправили на него код подтверждения.',
            ];
        }

        $plainCode = (string) random_int(100000, 999999);

        // Invalidate all previous unused codes for this (email, purpose)
        $this->invalidateActiveCodes($email, $purpose);

        $authCode = AuthCode::create([
            'email' => $email,
            'purpose' => $purpose,
            'code_hash' => Hash::make($plainCode),
            'expires_at' => now()->addMinutes(self::CODE_TTL_MINUTES),
            'attempts' => 0,
            'used_at' => null,
        ]);

        try {
            $this->syncLocalDotEnvMailConfig();
            Mail::to($email)->send(new AuthCodeMail($plainCode, $purpose));
        } catch (Throwable) {
            $authCode->delete();

            return [
                'success' => false,
                'error' => 'mail_failed',
                'message' => 'Не удалось отправить письмо с кодом подтверждения. Пожалуйста, попробуйте позже.',
            ];
        }

        Cache::put($cooldownKey, time() + self::COOLDOWN_SECONDS, self::COOLDOWN_SECONDS);
        RateLimiter::hit($emailRateKey, 3600);
        RateLimiter::hit($ipRateKey, 3600);

        $msg = $purpose === AuthCode::PURPOSE_RESET
            ? 'Если указанный email зарегистрирован, мы отправили на него код подтверждения.'
            : 'Код подтверждения отправлен на вашу электронную почту.';

        return [
            'success' => true,
            'cooldown' => self::COOLDOWN_SECONDS,
            'message' => $msg,
        ];
    }

    /**
     * Verify a 6-digit code for (email, purpose).
     * Checks: not used, not expired, attempts < 5, Hash::check.
     *
     * @return array{success: bool, error?: string, message: string, authCode?: AuthCode}
     */
    public function verifyCode(string $rawEmail, string $purpose, string $rawCode): array
    {
        $email = $this->normalizeEmail($rawEmail);
        $code = preg_replace('/\D/', '', trim($rawCode));

        if ($code === '' || strlen($code) !== 6) {
            return [
                'success' => false,
                'error' => 'invalid_format',
                'message' => 'Введите 6-значный код подтверждения из письма.',
            ];
        }

        $authCode = AuthCode::where('email', $email)
            ->where('purpose', $purpose)
            ->latest('id')
            ->first();

        if (! $authCode || $authCode->used_at !== null) {
            return [
                'success' => false,
                'error' => 'already_used',
                'message' => 'Код недействителен или уже был использован.',
            ];
        }

        if ($authCode->expires_at === null || $authCode->expires_at->isPast()) {
            return [
                'success' => false,
                'error' => 'expired',
                'message' => 'Срок действия кода истёк. Запросите новый код.',
            ];
        }

        if ((int) $authCode->attempts >= self::MAX_CODE_ATTEMPTS) {
            $authCode->update(['used_at' => now()]);

            return [
                'success' => false,
                'error' => 'max_attempts',
                'message' => 'Превышено количество попыток ввода кода. Запросите новый код.',
            ];
        }

        if (! Hash::check($code, (string) $authCode->code_hash)) {
            $newAttempts = (int) $authCode->attempts + 1;
            $updates = ['attempts' => $newAttempts];
            if ($newAttempts >= self::MAX_CODE_ATTEMPTS) {
                $updates['used_at'] = now();
            }
            $authCode->update($updates);

            if ($newAttempts >= self::MAX_CODE_ATTEMPTS) {
                return [
                    'success' => false,
                    'error' => 'max_attempts',
                    'message' => 'Код аннулирован после 5 неверных попыток. Запросите новый код.',
                ];
            }

            return [
                'success' => false,
                'error' => 'wrong_code',
                'message' => 'Неверный код подтверждения.',
            ];
        }

        $authCode->update(['used_at' => now()]);

        return [
            'success' => true,
            'message' => 'Код успешно подтверждён.',
            'authCode' => $authCode,
        ];
    }

    /**
     * Create a new shopper account in `users` (without adding a `role` column)
     * and synchronize the storefront `Customer` inside a database transaction.
     *
     * @return array{success: bool, error?: string, message: string, user?: User, customer?: Customer}
     */
    public function registerCustomerUser(string $verifiedEmail, string $name, string $normalizedPhone, string $plainPassword): array
    {
        $email = $this->normalizeEmail($verifiedEmail);
        $cleanName = trim($name);

        return DB::transaction(function () use ($email, $cleanName, $normalizedPhone, $plainPassword) {
            $existing = User::whereRaw('LOWER(email) = ?', [$email])->lockForUpdate()->first();
            if ($existing) {
                return [
                    'success' => false,
                    'error' => 'email_taken',
                    'message' => 'Этот email уже зарегистрирован. Войдите с паролем.',
                ];
            }

            $user = User::create([
                'name' => $cleanName,
                'email' => $email,
                'phone' => $normalizedPhone,
                'password' => Hash::make($plainPassword),
            ]);

            $this->invalidateActiveCodes($email, AuthCode::PURPOSE_REGISTER);

            // If there was already a Telegram account with the same phone number, merge it and all its order history into this Gmail user!
            $merged = app(TelegramAuthService::class)->mergePhoneAccountsIntoPrimary($user, $normalizedPhone, '', $cleanName, false);
            $wasMerged = (bool) ($merged['merged'] ?? false);

            return [
                'success' => true,
                'merged' => $wasMerged,
                'message' => $wasMerged
                    ? 'Ваш Telegram и почта успешно объединены в один профиль! Все заказы сохранены.'
                    : 'Регистрация успешно завершена.',
                'user' => $merged['user'],
                'customer' => $merged['customer'],
            ];
        });
    }

    /**
     * Reset password for a user whose email was verified via `purpose = reset` code,
     * and synchronize the storefront Customer so the user can be logged in immediately.
     *
     * @return array{success: bool, error?: string, message: string, user?: User, customer?: Customer}
     */
    public function resetPassword(string $verifiedEmail, string $newPlainPassword): array
    {
        $email = $this->normalizeEmail($verifiedEmail);

        return DB::transaction(function () use ($email, $newPlainPassword) {
            $user = User::whereRaw('LOWER(email) = ?', [$email])->lockForUpdate()->first();
            if (! $user) {
                return [
                    'success' => false,
                    'error' => 'user_not_found',
                    'message' => 'Аккаунта с таким email нет.',
                ];
            }

            $user->update([
                'password' => Hash::make($newPlainPassword),
            ]);

            $this->invalidateActiveCodes($email, AuthCode::PURPOSE_RESET);

            $customer = $this->syncCustomerForUser($user);

            return [
                'success' => true,
                'message' => 'Пароль успешно изменён.',
                'user' => $user,
                'customer' => $customer,
            ];
        });
    }

    public function invalidateActiveCodes(string $rawEmail, string $purpose): void
    {
        $email = $this->normalizeEmail($rawEmail);

        AuthCode::where('email', $email)
            ->where('purpose', $purpose)
            ->whereNull('used_at')
            ->update([
                'used_at' => now(),
                'expires_at' => now()->subSecond(),
            ]);
    }

    public function cooldownCacheKey(string $rawEmail): string
    {
        return 'auth_code_cooldown:'.sha1($this->normalizeEmail($rawEmail));
    }

    public function emailHourlyRateKey(string $rawEmail): string
    {
        return 'auth_code_hourly_email:'.sha1($this->normalizeEmail($rawEmail));
    }

    public function ipHourlyRateKey(string $ip): string
    {
        return 'auth_code_hourly_ip:'.sha1(trim($ip));
    }

    public function loginRateKey(string $rawEmail, string $ip): string
    {
        return 'auth_login_attempts:'.sha1($this->normalizeEmail($rawEmail).'|'.trim($ip));
    }

    /**
     * @return array{0: string, 1: string}
     */
    protected function splitFullName(string $fullName): array
    {
        $trimmed = trim(preg_replace('/\s+/', ' ', $fullName));
        if ($trimmed === '') {
            return ['Покупатель', ''];
        }

        $parts = explode(' ', $trimmed, 2);

        return [
            $parts[0] ?? 'Покупатель',
            $parts[1] ?? '',
        ];
    }

    /**
     * When running under `php artisan serve --no-reload` in local development,
     * sync live `MAIL_*` settings from `.env` so changes take effect without restarting the server.
     */
    protected function syncLocalDotEnvMailConfig(): void
    {
        if (! app()->environment('local') || app()->runningUnitTests()) {
            return;
        }

        $envPath = base_path('.env');
        if (! is_file($envPath)) {
            return;
        }

        $lines = @file($envPath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        if (! is_array($lines)) {
            return;
        }

        $env = [];
        foreach ($lines as $line) {
            $trimmed = trim($line);
            if ($trimmed === '' || str_starts_with($trimmed, '#') || ! str_contains($trimmed, '=')) {
                continue;
            }
            [$k, $v] = explode('=', $trimmed, 2);
            $k = trim($k);
            $v = trim(trim($v), "\"'");
            if (str_starts_with($k, 'MAIL_')) {
                $env[$k] = $v;
            }
        }

        if (! empty($env['MAIL_MAILER'])) {
            config(['mail.default' => $env['MAIL_MAILER']]);
        }
        if (isset($env['MAIL_SCHEME'])) {
            config(['mail.mailers.smtp.scheme' => $env['MAIL_SCHEME'] === 'null' ? null : $env['MAIL_SCHEME']]);
        }
        if (! empty($env['MAIL_HOST'])) {
            config(['mail.mailers.smtp.host' => $env['MAIL_HOST']]);
        }
        if (! empty($env['MAIL_PORT'])) {
            config(['mail.mailers.smtp.port' => (int) $env['MAIL_PORT']]);
        }
        if (isset($env['MAIL_USERNAME'])) {
            config(['mail.mailers.smtp.username' => $env['MAIL_USERNAME'] === 'null' ? null : $env['MAIL_USERNAME']]);
        }
        if (isset($env['MAIL_PASSWORD'])) {
            config(['mail.mailers.smtp.password' => $env['MAIL_PASSWORD'] === 'null' ? null : $env['MAIL_PASSWORD']]);
        }
        if (! empty($env['MAIL_FROM_ADDRESS'])) {
            config(['mail.from.address' => $env['MAIL_FROM_ADDRESS']]);
        }
        if (! empty($env['MAIL_FROM_NAME']) && ! str_contains($env['MAIL_FROM_NAME'], '${')) {
            config(['mail.from.name' => $env['MAIL_FROM_NAME']]);
        }

        app('mail.manager')->forgetMailers();
    }
}
