<?php

namespace App\Services;

use App\Mail\AuthCodeMail;
use App\Models\AuthCode;
use App\Models\Customer;
use App\Models\User;
use App\Support\PhoneNumber;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\RateLimiter;
use InvalidArgumentException;
use RuntimeException;
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

    /** Generous, because mobile carriers in UZ put many people behind one IP. */
    public const MAX_LOGIN_FAILURES_PER_IP = 30;

    public const LOGIN_IP_DECAY_SECONDS = 900;

    /**
     * Failed logins for one account from one IP within 15 minutes. Keyed per
     * IP so strangers' failures elsewhere can't lock the owner out.
     */
    public const MAX_LOGIN_FAILURES_PER_ACCOUNT = 10;

    public const GENERIC_LOGIN_ERROR = 'Wrong email, phone number or password.';

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
            $normalizedPhone = PhoneNumber::normalize($rawEmail) ?? trim($rawEmail);

            if ($password === '') {
                return [
                    'success' => false,
                    'error' => 'empty_password',
                    'is_phone' => true,
                    'message' => __('Enter your password.'),
                ];
            }

            $loginKey = $this->loginRateKey($normalizedPhone, $ipAddress);
            if ($this->loginThrottled($loginKey, $ipAddress, $normalizedPhone)) {
                return [
                    'success' => false,
                    'error' => 'rate_limit',
                    'is_phone' => true,
                    'message' => __('Too many sign-in attempts. Please wait a minute and try again.'),
                ];
            }

            $user = User::wherePhone($normalizedPhone)->first();
            if (! $user) {
                $this->recordFailedLogin($loginKey, $ipAddress, $normalizedPhone);

                return [
                    'success' => false,
                    'error' => 'user_not_found',
                    'is_phone' => true,
                    'message' => __('There is no account with this phone number.'),
                ];
            }

            if (! Hash::check($password, (string) $user->password)) {
                $this->recordFailedLogin($loginKey, $ipAddress, $normalizedPhone);

                return [
                    'success' => false,
                    'error' => 'invalid_credentials',
                    'is_phone' => true,
                    'message' => __(self::GENERIC_LOGIN_ERROR),
                ];
            }

            RateLimiter::clear($loginKey);
            $customer = $this->accounts()->customerFor($user);

            return [
                'success' => true,
                'is_phone' => true,
                'message' => __('You have signed in.'),
                'user' => $user,
                'customer' => $customer,
            ];
        }

        $email = $this->normalizeEmail($rawEmail);

        if (! $this->isValidEmail($email)) {
            return [
                'success' => false,
                'error' => 'invalid_email',
                'message' => __('Please enter a valid email address.'),
            ];
        }

        if ($password === '') {
            return [
                'success' => false,
                'error' => 'empty_password',
                'message' => __('Enter your password.'),
            ];
        }

        $loginKey = $this->loginRateKey($email, $ipAddress);
        if ($this->loginThrottled($loginKey, $ipAddress, $email)) {
            return [
                'success' => false,
                'error' => 'rate_limit',
                'message' => __('Too many sign-in attempts. Please wait a minute and try again.'),
            ];
        }

        $user = $this->findUserByEmail($email);

        if (! $user) {
            $this->recordFailedLogin($loginKey, $ipAddress, $email);

            return [
                'success' => false,
                'error' => 'user_not_found',
                'message' => __('There is no account with this email.'),
            ];
        }

        if (! Hash::check($password, (string) $user->password)) {
            $this->recordFailedLogin($loginKey, $ipAddress, $email);

            return [
                'success' => false,
                'error' => 'invalid_credentials',
                'message' => __(self::GENERIC_LOGIN_ERROR),
            ];
        }

        RateLimiter::clear($loginKey);

        $customer = $this->accounts()->customerFor($user);

        return [
            'success' => true,
            'message' => __('You have signed in.'),
            'user' => $user,
            'customer' => $customer,
        ];
    }

    protected function accounts(): CustomerAccountService
    {
        return app(CustomerAccountService::class);
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
                'message' => __('Please enter a valid email address.'),
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
                'message' => __('You can request a new code in :seconds sec.', ['seconds' => $remaining]),
            ];
        }

        if (
            RateLimiter::tooManyAttempts($emailRateKey, self::HOURLY_LIMIT_PER_EMAIL)
            || RateLimiter::tooManyAttempts($ipRateKey, self::HOURLY_LIMIT_PER_IP)
        ) {
            return [
                'success' => false,
                'error' => 'rate_limit',
                'message' => __('Too many codes requested (at most 5 per hour). Please try again later.'),
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
                'message' => __('If this email is registered, we have sent a confirmation code to it.'),
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
            // The "log" mailer would write the code into laravel.log.
            if (app()->isProduction() && in_array(config('mail.default'), ['log', 'array'], true)) {
                throw new RuntimeException('MAIL_MAILER is not set up for production; login codes are not sent.');
            }

            Mail::to($email)->send(new AuthCodeMail($plainCode, $purpose));
        } catch (Throwable $e) {
            report($e);
            $authCode->delete();

            return [
                'success' => false,
                'error' => 'mail_failed',
                'message' => __('Could not send the email with the code. Please try again later.'),
            ];
        }

        Cache::put($cooldownKey, time() + self::COOLDOWN_SECONDS, self::COOLDOWN_SECONDS);
        RateLimiter::hit($emailRateKey, 3600);
        RateLimiter::hit($ipRateKey, 3600);

        $msg = $purpose === AuthCode::PURPOSE_RESET
            ? __('If this email is registered, we have sent a confirmation code to it.')
            : __('A confirmation code has been sent to your email.');

        return [
            'success' => true,
            'cooldown' => self::COOLDOWN_SECONDS,
            'message' => $msg,
        ];
    }

    /**
     * Verify a 6-digit code for (email, purpose).
     * Checks: not used, not expired, at most 5 attempts, Hash::check.
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
                'message' => __('Enter the 6-digit code from the email.'),
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
                'message' => __('The code is invalid or has already been used.'),
            ];
        }

        if ($authCode->expires_at === null || $authCode->expires_at->isPast()) {
            return [
                'success' => false,
                'error' => 'expired',
                'message' => __('The code has expired. Please request a new one.'),
            ];
        }

        // Count the attempt atomically before checking, so parallel requests
        // can't all read a stale counter and test more than 5 codes.
        // Each code has its own budget, so a new code starts from zero.
        $attemptsKey = $this->codeAttemptsKey($authCode);
        $attempts = RateLimiter::hit($attemptsKey, self::CODE_TTL_MINUTES * 60);
        $authCode->update(['attempts' => $attempts]);

        if ($attempts > self::MAX_CODE_ATTEMPTS) {
            $authCode->update(['used_at' => now()]);

            return [
                'success' => false,
                'error' => 'max_attempts',
                'message' => __('Too many wrong codes. Request a new code.'),
            ];
        }

        if (! Hash::check($code, (string) $authCode->code_hash)) {
            if ($attempts >= self::MAX_CODE_ATTEMPTS) {
                $authCode->update(['used_at' => now()]);

                return [
                    'success' => false,
                    'error' => 'max_attempts',
                    'message' => __('The code was cancelled after 5 wrong attempts. Request a new code.'),
                ];
            }

            return [
                'success' => false,
                'error' => 'wrong_code',
                'message' => __('Invalid verification code'),
            ];
        }

        $authCode->update(['used_at' => now()]);
        RateLimiter::clear($attemptsKey);

        return [
            'success' => true,
            'message' => __('The code is confirmed.'),
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
                    'message' => __('This email is already registered. Sign in with your password.'),
                ];
            }

            // The phone isn't verified here, so it must not point at someone
            // else's account. Accounts are never merged.
            if ($this->accounts()->phoneTaken($normalizedPhone)) {
                return [
                    'success' => false,
                    'error' => 'phone_taken',
                    'message' => __('This phone number already belongs to another account. Sign in to it or use another number.'),
                ];
            }

            $user = User::create([
                'name' => $cleanName,
                'email' => $email,
                'phone' => $normalizedPhone,
                'password' => Hash::make($plainPassword),
            ]);

            $this->invalidateActiveCodes($email, AuthCode::PURPOSE_REGISTER);

            return [
                'success' => true,
                'message' => __('Registration completed.'),
                'user' => $user,
                'customer' => $this->accounts()->customerFor($user),
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
                    'message' => __('There is no account with this email.'),
                ];
            }

            // A new password hash also signs out every other session of this
            // account (StorefrontService checks the fingerprint).
            $user->update([
                'password' => Hash::make($newPlainPassword),
            ]);

            $this->invalidateActiveCodes($email, AuthCode::PURPOSE_RESET);

            $customer = $this->accounts()->customerFor($user);

            return [
                'success' => true,
                'message' => __('Password changed.'),
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

    public function codeAttemptsKey(AuthCode $authCode): string
    {
        return 'auth_code_attempts:'.$authCode->id;
    }

    public function loginRateKey(string $rawEmail, string $ip): string
    {
        return 'auth_login_attempts:'.sha1($this->normalizeEmail($rawEmail).'|'.trim($ip));
    }

    /** Failed logins from one IP across all emails/phones (stops password spraying). */
    public function loginIpRateKey(string $ip): string
    {
        return 'auth_login_failures_ip:'.sha1(trim($ip));
    }

    /** Failed logins for one email/phone from one IP (15-minute window). */
    public function loginAccountRateKey(string $identifier, string $ip): string
    {
        return 'auth_login_failures_account:'.sha1(mb_strtolower(trim($identifier)).'|'.trim($ip));
    }

    private function loginThrottled(string $loginKey, string $ip, string $identifier): bool
    {
        return RateLimiter::tooManyAttempts($loginKey, self::MAX_LOGIN_ATTEMPTS)
            || RateLimiter::tooManyAttempts($this->loginIpRateKey($ip), self::MAX_LOGIN_FAILURES_PER_IP)
            || RateLimiter::tooManyAttempts($this->loginAccountRateKey($identifier, $ip), self::MAX_LOGIN_FAILURES_PER_ACCOUNT);
    }

    private function recordFailedLogin(string $loginKey, string $ip, string $identifier): void
    {
        RateLimiter::hit($loginKey, self::LOGIN_DECAY_SECONDS);
        RateLimiter::hit($this->loginIpRateKey($ip), self::LOGIN_IP_DECAY_SECONDS);
        RateLimiter::hit($this->loginAccountRateKey($identifier, $ip), self::LOGIN_IP_DECAY_SECONDS);
    }
}
