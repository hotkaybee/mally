<?php

namespace App\Services;

use App\Models\TelegramLoginRequest;
use App\Support\PhoneNumber;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\RateLimiter;
use Throwable;

/**
 * "Continue with Telegram" for the storefront.
 *
 * 1. The site creates a one-time request bound to the browser session and
 *    shows t.me/<bot>?start=login_<token> (only the token hash is stored).
 * 2. In the bot, /start login_<token> attaches the Telegram user to the
 *    request and asks them to share their own contact (Telegram verifies the
 *    number; a contact whose user_id differs from the sender is refused).
 * 3. The bot sends a 6-digit code to that chat. The person must type it on
 *    the site, which proves the browser and the Telegram account belong to
 *    the same person — a link forwarded to someone else is useless.
 * 4. verifyCode() consumes the request exactly once and returns the verified
 *    Telegram identity; the caller decides login / register / password reset.
 *
 * Updates arrive through the webhook (TELEGRAM_AUTH_WEBHOOK_SECRET) or the
 * `telegram:auth-poll` worker; in local development without either, status
 * checks poll getUpdates once.
 */
class TelegramAuthService
{
    public const REQUEST_TTL_SECONDS = 600;

    public const CODE_TTL_SECONDS = 300;

    public const MAX_CODE_ATTEMPTS = 5;

    public const MAX_STARTS_PER_HOUR = 10;

    private const SESSION_KEY = 'mally_tg_login_request';

    public const VERIFIED = 'verified';

    public const INVALID = 'invalid';

    public const EXPIRED = 'expired';

    public const THROTTLED = 'throttled';

    public function botToken(): string
    {
        return trim((string) config('services.telegram.auth_bot_token'));
    }

    public function botUsername(): string
    {
        return ltrim(trim((string) config('services.telegram.auth_bot_username')), '@');
    }

    public function isConfigured(): bool
    {
        return $this->botToken() !== '' && $this->botUsername() !== '';
    }

    // ------------------------------------------------------------------ site

    /**
     * @return array{bot_url: string, bot_username: string}|null null when rate-limited
     */
    public function start(string $purpose): ?array
    {
        $limiterKey = 'tg-login-start:'.request()->ip();
        if (RateLimiter::tooManyAttempts($limiterKey, self::MAX_STARTS_PER_HOUR)) {
            return null;
        }
        RateLimiter::hit($limiterKey, 3600);

        $this->forgetSessionRequest();

        $token = bin2hex(random_bytes(20));
        $request = TelegramLoginRequest::create([
            'token_hash' => hash('sha256', $token),
            'session_hash' => $this->sessionHash(),
            'purpose' => $purpose,
            'status' => TelegramLoginRequest::STATUS_PENDING,
            'expires_at' => now()->addSeconds(self::REQUEST_TTL_SECONDS),
        ]);

        session([self::SESSION_KEY => $request->id]);

        return [
            'bot_username' => $this->botUsername(),
            'bot_url' => 'https://t.me/'.$this->botUsername().'?start=login_'.$token,
        ];
    }

    /** The request started in this browser session, if still usable. */
    public function currentRequest(): ?TelegramLoginRequest
    {
        $id = (int) session(self::SESSION_KEY, 0);
        if ($id <= 0) {
            return null;
        }

        $request = TelegramLoginRequest::find($id);

        if (! $request
            || ! hash_equals($request->session_hash, $this->sessionHash())
            || $request->status === TelegramLoginRequest::STATUS_USED
            || $request->isExpired()) {
            return null;
        }

        return $request;
    }

    /** pending | contact_requested | code_sent | expired */
    public function status(): string
    {
        if (! $this->currentRequest()) {
            return self::EXPIRED;
        }

        $this->pollIfNeeded();

        return $this->currentRequest()?->status ?? self::EXPIRED;
    }

    /**
     * Check the code the bot sent. On success the request is consumed and the
     * verified identity is returned.
     *
     * @return array{result: string, identity?: array{telegram_user_id: string, telegram_username: string, phone: string, first_name: string, last_name: string, purpose: string}}
     */
    public function verifyCode(string $code): array
    {
        $limiterKey = 'tg-login-verify:'.request()->ip();
        if (RateLimiter::tooManyAttempts($limiterKey, 30)) {
            return ['result' => self::THROTTLED];
        }
        RateLimiter::hit($limiterKey, 3600);

        $request = $this->currentRequest();
        if (! $request || $request->status !== TelegramLoginRequest::STATUS_CODE_SENT || ! $request->code_hash) {
            return ['result' => self::EXPIRED];
        }

        // Count the attempt atomically before checking, so parallel requests
        // can't all read a stale counter and test more than MAX_CODE_ATTEMPTS codes.
        $attemptsKey = $this->codeAttemptsKey($request);
        $attempts = RateLimiter::hit($attemptsKey, self::CODE_TTL_SECONDS);
        $request->update(['attempts' => $attempts]);
        if ($attempts > self::MAX_CODE_ATTEMPTS) {
            $this->burn($request);

            return ['result' => self::EXPIRED];
        }

        $code = preg_replace('/\D/', '', $code);

        if ($code === '' || ! Hash::check($code, $request->code_hash)) {
            if ($attempts >= self::MAX_CODE_ATTEMPTS) {
                $this->burn($request);

                return ['result' => self::EXPIRED];
            }

            return ['result' => self::INVALID];
        }

        RateLimiter::clear($attemptsKey);

        // Consume atomically: only one request can flip code_sent -> used.
        $consumed = TelegramLoginRequest::whereKey($request->id)
            ->where('status', TelegramLoginRequest::STATUS_CODE_SENT)
            ->update(['status' => TelegramLoginRequest::STATUS_USED, 'used_at' => now(), 'code_hash' => null]);

        $this->forgetSessionRequest();

        if ($consumed !== 1) {
            return ['result' => self::EXPIRED];
        }

        return [
            'result' => self::VERIFIED,
            'identity' => [
                'telegram_user_id' => (string) $request->telegram_user_id,
                'telegram_username' => (string) $request->telegram_username,
                'phone' => (string) $request->phone,
                'first_name' => (string) $request->first_name,
                'last_name' => (string) $request->last_name,
                'purpose' => $request->purpose,
            ],
        ];
    }

    public function forgetSessionRequest(): void
    {
        session()->forget(self::SESSION_KEY);
    }

    // ------------------------------------------------------------------- bot

    /**
     * Handle one Telegram update (from the webhook or getUpdates).
     */
    public function handleUpdate(array $update): void
    {
        $message = $update['message'] ?? null;
        if (! is_array($message) || ($message['chat']['type'] ?? 'private') !== 'private') {
            return;
        }

        $chatId = (string) ($message['chat']['id'] ?? '');
        $from = is_array($message['from'] ?? null) ? $message['from'] : [];
        $fromId = (string) ($from['id'] ?? '');
        if ($chatId === '' || $fromId === '' || ! empty($from['is_bot'])) {
            return;
        }

        if (is_array($message['contact'] ?? null)) {
            $this->handleContact($chatId, $fromId, $from, $message['contact']);

            return;
        }

        $text = trim((string) ($message['text'] ?? ''));
        if (str_starts_with($text, '/start')) {
            $payload = trim((string) (preg_split('/\s+/', $text, 2)[1] ?? ''));
            $this->handleStart($chatId, $fromId, $from, $payload);
        }
    }

    private function handleStart(string $chatId, string $fromId, array $from, string $payload): void
    {
        $token = str_starts_with($payload, 'login_') ? substr($payload, 6) : '';
        $request = $token !== '' && ctype_xdigit($token)
            ? TelegramLoginRequest::where('token_hash', hash('sha256', $token))->first()
            : null;

        $usable = $request
            && ! $request->isExpired()
            && in_array($request->status, [TelegramLoginRequest::STATUS_PENDING, TelegramLoginRequest::STATUS_CONTACT_REQUESTED], true)
            && ($request->telegram_user_id === null || $request->telegram_user_id === $fromId);

        if (! $usable) {
            $this->sendMessage($chatId, __('This sign-in link has expired or was already used. Open the Mally website and press “Continue with Telegram” again.'));

            return;
        }

        $request->update([
            'status' => TelegramLoginRequest::STATUS_CONTACT_REQUESTED,
            'chat_id' => $chatId,
            'telegram_user_id' => $fromId,
            'telegram_username' => trim((string) ($from['username'] ?? '')) ?: null,
            'first_name' => mb_substr(trim((string) ($from['first_name'] ?? '')), 0, 100) ?: null,
            'last_name' => mb_substr(trim((string) ($from['last_name'] ?? '')), 0, 100) ?: null,
        ]);

        $this->sendMessage(
            $chatId,
            __('To continue on the Mally website, share your phone number with the “📱 Share contact” button below. If it wasn’t you, just ignore this message.'),
            [
                'keyboard' => [[['text' => __('📱 Share contact'), 'request_contact' => true]]],
                'resize_keyboard' => true,
                'one_time_keyboard' => true,
            ]
        );
    }

    private function handleContact(string $chatId, string $fromId, array $from, array $contact): void
    {
        // Only the sender's own contact proves the phone number.
        if ((string) ($contact['user_id'] ?? '') !== $fromId) {
            $this->sendMessage($chatId, __('Please send your own contact with the “📱 Share contact” button.'));

            return;
        }

        $request = TelegramLoginRequest::where('telegram_user_id', $fromId)
            ->where('chat_id', $chatId)
            ->where('status', TelegramLoginRequest::STATUS_CONTACT_REQUESTED)
            ->where('expires_at', '>', now())
            ->latest('id')
            ->first();

        if (! $request) {
            $this->sendMessage($chatId, __('The sign-in session was not found or has expired. Press “Continue with Telegram” on the website again.'), ['remove_keyboard' => true]);

            return;
        }

        $phone = PhoneNumber::normalize((string) ($contact['phone_number'] ?? ''));
        if ($phone === null) {
            $this->sendMessage($chatId, __('For now we only accept Uzbekistan phone numbers (+998).'), ['remove_keyboard' => true]);

            return;
        }

        $code = (string) random_int(100000, 999999);

        // Reset the counter before the new code becomes checkable.
        RateLimiter::clear($this->codeAttemptsKey($request));
        $request->update([
            'phone' => $phone,
            'code_hash' => Hash::make($code),
            'attempts' => 0,
            'status' => TelegramLoginRequest::STATUS_CODE_SENT,
            'expires_at' => now()->addSeconds(self::CODE_TTL_SECONDS),
        ]);

        $text = $request->purpose === TelegramLoginRequest::PURPOSE_RESET
            ? __('Mally password reset code: :code', ['code' => $code])
            : __('Mally sign-in code: :code', ['code' => $code]);

        $this->sendMessage(
            $chatId,
            $text."\n\n".__('Enter it in the open window on the website. The code is valid for 5 minutes. Never share it with anyone — Mally staff will never ask for it.'),
            ['remove_keyboard' => true]
        );
    }

    // ------------------------------------------------------------- transport

    /**
     * Fetch pending updates with getUpdates (worker or local fallback).
     * Not used when a webhook secret is configured: Telegram rejects
     * getUpdates while a webhook is set.
     */
    public function pollUpdates(int $longPollTimeout = 0): void
    {
        $token = $this->botToken();
        if ($token === '') {
            return;
        }

        $offsetKey = 'tg_auth_update_offset:'.substr(hash('sha256', $token), 0, 12);

        try {
            $response = Http::timeout(max(3, $longPollTimeout + 2))
                ->connectTimeout(3)
                ->get("https://api.telegram.org/bot{$token}/getUpdates", [
                    'offset' => (int) Cache::get($offsetKey, 0) ?: null,
                    'limit' => 30,
                    'timeout' => $longPollTimeout,
                    'allowed_updates' => json_encode(['message']),
                ]);
        } catch (Throwable $e) {
            Log::warning('Telegram getUpdates failed', ['error' => str_replace($token, '***', $e->getMessage())]);

            return;
        }

        foreach ((array) $response->json('result', []) as $update) {
            if (! is_array($update)) {
                continue;
            }
            Cache::put($offsetKey, (int) ($update['update_id'] ?? 0) + 1, 86400 * 7);
            $this->handleUpdate($update);
        }
    }

    private function pollIfNeeded(): void
    {
        if (trim((string) config('services.telegram.auth_webhook_secret')) !== '' || Cache::has('mally_tg_poller_alive')) {
            return;
        }

        // Without a webhook or worker (local development) poll once, but not
        // more often than every 2 seconds across all visitors.
        if (Cache::add('mally_tg_inline_poll', true, 2)) {
            $this->pollUpdates(0);
        }
    }

    protected function sendMessage(string $chatId, string $text, ?array $replyMarkup = null): void
    {
        $token = $this->botToken();
        if ($token === '') {
            return;
        }

        $payload = ['chat_id' => $chatId, 'text' => $text];
        if ($replyMarkup !== null) {
            $payload['reply_markup'] = json_encode($replyMarkup, JSON_UNESCAPED_UNICODE);
        }

        try {
            Http::timeout(5)->connectTimeout(3)->post("https://api.telegram.org/bot{$token}/sendMessage", $payload);
        } catch (Throwable $e) {
            Log::warning('Telegram sendMessage failed', ['error' => str_replace($token, '***', $e->getMessage())]);
        }
    }

    private function codeAttemptsKey(TelegramLoginRequest $request): string
    {
        return 'tg-login-code:'.$request->id;
    }

    private function burn(TelegramLoginRequest $request): void
    {
        DB::table('telegram_login_requests')->where('id', $request->id)->update([
            'status' => TelegramLoginRequest::STATUS_USED,
            'used_at' => now(),
            'code_hash' => null,
        ]);
        $this->forgetSessionRequest();
    }

    private function sessionHash(): string
    {
        return hash('sha256', session()->getId());
    }
}
