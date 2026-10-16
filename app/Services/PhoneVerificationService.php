<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\RateLimiter;

/**
 * One-time login codes for storefront phone numbers.
 *
 * The code is stored only as a hash in the cache, expires after
 * CODE_TTL_SECONDS, allows MAX_ATTEMPTS guesses and is deleted after a
 * successful check. A verified phone is remembered in the session (never in
 * a Livewire property) so registration can trust it.
 */
class PhoneVerificationService
{
    public const CODE_TTL_SECONDS = 300;

    public const MAX_ATTEMPTS = 5;

    public const RESEND_SECONDS = 60;

    public const MAX_SENDS_PER_HOUR = 5;

    public const MAX_SENDS_PER_IP_PER_HOUR = 10;

    public const VERIFIED_TTL_SECONDS = 900;

    private const SESSION_PENDING = 'mally_otp_phone';

    private const SESSION_VERIFIED = 'mally_verified_phone';

    public const SENT = 'sent';

    public const THROTTLED = 'throttled';

    public const UNAVAILABLE = 'unavailable';

    public const VERIFIED = 'verified';

    public const INVALID = 'invalid';

    public const EXPIRED = 'expired';

    /**
     * @param  string  $phone  normalized phone (+998 XX XXX XX XX)
     */
    public function send(string $phone, string $ip): string
    {
        $digits = $this->digits($phone);

        foreach ([
            ['otp-resend:'.$digits, 1, self::RESEND_SECONDS],
            ['otp-hour:'.$digits, self::MAX_SENDS_PER_HOUR, 3600],
            ['otp-ip:'.$ip, self::MAX_SENDS_PER_IP_PER_HOUR, 3600],
        ] as [$key, $max, $decay]) {
            if (RateLimiter::tooManyAttempts($key, $max)) {
                return self::THROTTLED;
            }
        }

        $code = (string) random_int(100000, 999999);

        if (! $this->deliver($phone, $code)) {
            return self::UNAVAILABLE;
        }

        RateLimiter::hit('otp-resend:'.$digits, self::RESEND_SECONDS);
        RateLimiter::hit('otp-hour:'.$digits, 3600);
        RateLimiter::hit('otp-ip:'.$ip, 3600);

        // Reset the counter before the new code becomes checkable.
        RateLimiter::clear($this->attemptsKey($digits));
        Cache::put($this->cacheKey($digits), [
            'hash' => Hash::make($code),
        ], self::CODE_TTL_SECONDS);

        session([self::SESSION_PENDING => $phone]);
        session()->forget(self::SESSION_VERIFIED);

        return self::SENT;
    }

    /** The phone the last code was sent to in this session. */
    public function pendingPhone(): ?string
    {
        $phone = session(self::SESSION_PENDING);

        return is_string($phone) ? $phone : null;
    }

    public function verify(string $code): string
    {
        $phone = $this->pendingPhone();
        if (! $phone) {
            return self::EXPIRED;
        }

        $digits = $this->digits($phone);
        $key = $this->cacheKey($digits);
        $entry = Cache::get($key);
        if (! is_array($entry)) {
            return self::EXPIRED;
        }

        // Count the attempt atomically before checking, so parallel requests
        // can't all read a stale counter and test more than MAX_ATTEMPTS codes.
        $attempts = RateLimiter::hit($this->attemptsKey($digits), self::CODE_TTL_SECONDS);
        if ($attempts > self::MAX_ATTEMPTS) {
            Cache::forget($key);

            return self::EXPIRED;
        }

        $code = preg_replace('/\D/', '', $code);
        if ($code !== '' && Hash::check($code, $entry['hash'])) {
            Cache::forget($key);
            RateLimiter::clear($this->attemptsKey($digits));
            session()->forget(self::SESSION_PENDING);
            session([self::SESSION_VERIFIED => ['phone' => $phone, 'until' => now()->addSeconds(self::VERIFIED_TTL_SECONDS)->timestamp]]);

            return self::VERIFIED;
        }

        if ($attempts >= self::MAX_ATTEMPTS) {
            Cache::forget($key);

            return self::EXPIRED;
        }

        return self::INVALID;
    }

    /** A phone verified in this session within VERIFIED_TTL_SECONDS. */
    public function verifiedPhone(): ?string
    {
        $state = session(self::SESSION_VERIFIED);
        if (! is_array($state) || ($state['until'] ?? 0) < now()->timestamp) {
            return null;
        }

        return $state['phone'] ?? null;
    }

    public function forgetVerified(): void
    {
        session()->forget([self::SESSION_PENDING, self::SESSION_VERIFIED]);
    }

    /**
     * No SMS provider is connected yet. Outside local/testing the code can't
     * be delivered, so login by phone is reported as unavailable instead of
     * falling back to a shared code.
     */
    protected function deliver(string $phone, string $code): bool
    {
        if (app()->environment(['local', 'testing'])) {
            Log::info("Storefront login code for {$phone}: {$code}");

            return true;
        }

        return false;
    }

    private function digits(string $phone): string
    {
        return preg_replace('/\D/', '', $phone);
    }

    private function cacheKey(string $digits): string
    {
        return 'otp:'.$digits;
    }

    private function attemptsKey(string $digits): string
    {
        return 'otp-verify:'.$digits;
    }
}
