<?php

namespace App\Livewire\Storefront\Concerns;

use App\Models\AuthCode;
use App\Models\Customer;
use App\Models\TelegramLoginRequest;
use App\Models\User;
use App\Services\CustomerAccountService;
use App\Services\EmailAuthService;
use App\Services\StorefrontService;
use App\Services\TelegramAuthService;
use App\Support\PhoneNumber;
use Livewire\Attributes\Locked;

trait InteractsWithStore
{
    private const VERIFIED_TELEGRAM = 'mally_verified_telegram';

    private const PENDING_TELEGRAM_LINK = 'mally_pending_telegram_link';

    public bool $isAuthModalOpen = false;

    /** Server-controlled; the browser can only open the entry steps via setAuthStep(). */
    #[Locked]
    public string $authStep = 'login';

    public string $authEmail = '';

    public string $authPassword = '';

    public string $authPasswordConfirmation = '';

    public string $authCode = '';

    public string $authName = '';

    public string $authPhone = '+998 ';

    public string $authFirstName = '';

    public string $authLastName = '';

    public string $authCity = 'Ташкент';

    public string $authAddress = '';

    public string $authModalMessage = '';

    public string $authError = '';

    public string $authNotice = '';

    public int $authCooldownSeconds = 0;

    public ?string $authRedirectTo = null;

    public bool $authOfferRegistration = false;

    #[Locked]
    public string $authTelegramBotUrl = '';

    #[Locked]
    public string $authTelegramBotUsername = '';

    #[Locked]
    public string $authTelegramStatus = 'pending';

    public string $authTelegramCode = '';

    protected function store(): StorefrontService
    {
        return app(StorefrontService::class);
    }

    protected function emailAuth(): EmailAuthService
    {
        return app(EmailAuthService::class);
    }

    protected function accounts(): CustomerAccountService
    {
        return app(CustomerAccountService::class);
    }

    protected function telegramAuth(): TelegramAuthService
    {
        return app(TelegramAuthService::class);
    }

    public function t(string $key, ?string $fallback = null): string
    {
        return $this->store()->t($key, $fallback);
    }

    public function formatPrice(float|int|string|null $priceVal, ?string $forceCurrency = null): string
    {
        return $this->store()->formatPrice($priceVal, $forceCurrency);
    }

    public function switchLanguage(string $lang): void
    {
        $this->store()->setLocale($lang);
    }

    public function switchCurrency(string $currency): void
    {
        $this->store()->setCurrency($currency);
    }

    public function addToCart(string $productKey, bool $redirectToCart = false): void
    {
        $res = $this->store()->addToCart($productKey);
        if (! ($res['success'] ?? false) && ! empty($res['message'])) {
            $this->dispatch('toast', message: $res['message'], type: 'error');

            return;
        }

        if ($redirectToCart) {
            $this->redirect('/cart', navigate: true);
        }
    }

    public function updateCartQuantity(string $productKey, int $qty): void
    {
        $res = $this->store()->updateCartQuantity($productKey, $qty);
        if (! ($res['success'] ?? true) && ! empty($res['message'])) {
            $this->dispatch('toast', message: $res['message'], type: 'error');
        }
    }

    public function removeFromCart(string|int $productKey): void
    {
        $this->store()->removeFromCart((string) $productKey);
    }

    public function clearCart(): void
    {
        $this->store()->clearCart();
    }

    public function toggleFavorite(string $productKey): void
    {
        $this->store()->toggleFavorite($productKey);
    }

    public function openAuthModal(string $message = '', ?string $redirectTo = null): void
    {
        $this->authModalMessage = '';
        $this->authStep = 'login';
        $this->authEmail = '';
        $this->authPassword = '';
        $this->authPasswordConfirmation = '';
        $this->authCode = '';
        $this->authName = '';
        $this->authPhone = '+998 ';
        $this->authError = '';
        $this->authNotice = '';
        $this->authCooldownSeconds = 0;
        $this->authOfferRegistration = false;
        $this->authRedirectTo = $redirectTo !== null
            ? $this->emailAuth()->sanitizeRedirectPath($redirectTo)
            : null;
        $this->resetValidation();
        session()->forget([
            'mally_verified_register_email',
            'mally_verified_reset_email',
        ]);
        $this->isAuthModalOpen = true;
    }

    public function closeAuthModal(): void
    {
        $this->isAuthModalOpen = false;
        $this->authPassword = '';
        $this->authPasswordConfirmation = '';
        $this->authCode = '';
        $this->authError = '';
        $this->authNotice = '';
        $this->authOfferRegistration = false;
        $this->resetValidation();
    }

    public function setAuthStep(string $step): void
    {
        // Every other step is reached only after a server-side check.
        if (! in_array($step, ['login', 'register-email', 'reset-email'], true)) {
            return;
        }

        $this->clearAuthFeedback();
        $this->authOfferRegistration = false;
        $this->authStep = $step;
    }

    public function backToLogin(bool $preserveEmail = true): void
    {
        $this->authStep = 'login';
        if (! $preserveEmail) {
            $this->authEmail = '';
        }
        $this->authPassword = '';
        $this->authPasswordConfirmation = '';
        $this->authCode = '';
        $this->authError = '';
        $this->authNotice = '';
        $this->authOfferRegistration = false;
        $this->resetValidation();
        session()->forget([
            'mally_verified_register_email',
            'mally_verified_reset_email',
        ]);
    }

    /**
     * Open registration Step 1 (preserving already entered email if any).
     */
    public function openRegister(): void
    {
        $this->clearAuthFeedback();
        $this->authOfferRegistration = false;
        if (trim($this->authEmail) !== '') {
            $this->authEmail = $this->emailAuth()->normalizeEmail($this->authEmail);
        }
        $this->authPassword = '';
        $this->authPasswordConfirmation = '';
        $this->authCode = '';
        $this->authStep = 'register-email';
    }

    /**
     * Triggered by "Зарегистрироваться с этим email" when login reports that the email does not exist.
     * Opens registration with the already entered email pre-filled.
     */
    public function startRegistrationWithEmail(): void
    {
        $this->openRegister();
    }

    /**
     * Screen 1 ("Вход в аккаунт"): Login with email OR phone number and password directly.
     */
    public function loginWithPassword(): void
    {
        $this->clearAuthFeedback();
        $this->authOfferRegistration = false;

        $rawInput = trim($this->authEmail);
        $isPhone = $this->emailAuth()->isPhoneIdentifier($rawInput);

        if ($isPhone) {
            $normalizedInput = PhoneNumber::normalize($rawInput) ?? $rawInput;
            $this->authEmail = $normalizedInput;
        } else {
            $normalizedInput = $this->emailAuth()->normalizeEmail($rawInput);
            $this->authEmail = $normalizedInput;
        }

        $hasValidationError = false;

        if (! $isPhone && ($normalizedInput === '' || ! $this->emailAuth()->isValidEmail($normalizedInput))) {
            $this->failAuth('authEmail', 'Введите корректный адрес электронной почты.');
            $hasValidationError = true;
        }

        if ($this->authPassword === '') {
            $this->failAuth('authPassword', 'Введите пароль.');
            $hasValidationError = true;
        }

        if ($hasValidationError) {
            return;
        }

        $res = $this->emailAuth()->attemptLogin(
            $normalizedInput,
            $this->authPassword,
            (string) request()->ip()
        );

        if (! $res['success']) {
            $this->authPassword = '';

            if (($res['error'] ?? '') === 'user_not_found') {
                $this->authOfferRegistration = ! ($res['is_phone'] ?? false);
                $this->failAuth('authEmail', $res['message']);

                return;
            }

            $this->failAuth('authPassword', $res['message']);

            return;
        }

        $this->linkPendingTelegram($res['user']);
        $this->finishLogin($res['user']);
    }

    /**
     * Registration Step 1: User enters email and clicks "Получить код".
     * If email already exists in `users` -> message "Этот email уже зарегистрирован. Войдите с паролем."
     * and return to the login screen with the email pre-filled.
     * Otherwise -> send 6-digit registration code and go to Step 2 (`register-code`).
     */
    public function sendRegisterCode(): void
    {
        $this->clearAuthFeedback();
        $this->authOfferRegistration = false;

        $normalizedEmail = $this->emailAuth()->normalizeEmail($this->authEmail);
        $this->authEmail = $normalizedEmail;

        if (! $this->emailAuth()->isValidEmail($normalizedEmail)) {
            $this->failAuth('authEmail', 'Введите корректный адрес электронной почты.');

            return;
        }

        if ($this->emailAuth()->userExists($normalizedEmail)) {
            $this->authPassword = '';
            $this->authStep = 'login';
            $this->failAuth('authEmail', 'Этот email уже зарегистрирован. Войдите с паролем.');

            return;
        }

        $res = $this->emailAuth()->sendCode(
            $normalizedEmail,
            AuthCode::PURPOSE_REGISTER,
            (string) request()->ip()
        );

        if (! $res['success']) {
            if (isset($res['cooldown'])) {
                $this->authCooldownSeconds = (int) $res['cooldown'];
            }
            $this->failAuth('authEmail', $res['message']);

            return;
        }

        $this->authCode = '';
        $this->authCooldownSeconds = (int) ($res['cooldown'] ?? EmailAuthService::COOLDOWN_SECONDS);
        $this->authStep = 'register-code';
    }

    /**
     * Resend registration verification code (respecting 60s cooldown and 5/hr limits).
     */
    public function resendRegisterCode(): void
    {
        $this->clearAuthFeedback();

        $normalizedEmail = $this->emailAuth()->normalizeEmail($this->authEmail);
        if (! $this->emailAuth()->isValidEmail($normalizedEmail)) {
            $this->openRegister();

            return;
        }

        $res = $this->emailAuth()->sendCode(
            $normalizedEmail,
            AuthCode::PURPOSE_REGISTER,
            (string) request()->ip()
        );

        if (! $res['success']) {
            if (isset($res['cooldown'])) {
                $this->authCooldownSeconds = (int) $res['cooldown'];
            }
            $this->failAuth('authCode', $res['message']);

            return;
        }

        $this->authCode = '';
        $this->authCooldownSeconds = (int) ($res['cooldown'] ?? EmailAuthService::COOLDOWN_SECONDS);
    }

    /**
     * Registration Step 2: Verify 6-digit registration code and store verified email in session.
     */
    public function verifyRegisterCode(): void
    {
        $this->clearAuthFeedback();

        $normalizedEmail = $this->emailAuth()->normalizeEmail($this->authEmail);
        if (! $this->emailAuth()->isValidEmail($normalizedEmail)) {
            $this->openRegister();

            return;
        }

        $res = $this->emailAuth()->verifyCode(
            $normalizedEmail,
            AuthCode::PURPOSE_REGISTER,
            $this->authCode
        );

        if (! $res['success']) {
            $this->authStep = 'register-code';
            $this->failAuth('authCode', $res['message']);

            return;
        }

        session(['mally_verified_register_email' => $normalizedEmail]);
        $this->authCode = '';
        $this->authPassword = '';
        $this->authPasswordConfirmation = '';
        if (trim($this->authPhone) === '') {
            $this->authPhone = '+998 ';
        }
        $this->authStep = 'register-profile';
    }

    /**
     * Registration Step 3: Complete registration using email strictly from server session.
     */
    public function completeEmailRegistration(): void
    {
        $this->clearAuthFeedback();

        $verifiedEmail = (string) session('mally_verified_register_email', '');
        if (! $this->emailAuth()->isValidEmail($verifiedEmail)) {
            $this->authStep = 'register-email';
            $this->failAuth('authEmail', 'Сначала подтвердите ваш email одноразовым кодом из письма.');

            return;
        }

        $cleanName = trim($this->authName);
        if ($cleanName === '') {
            $this->failAuth('authName', 'Пожалуйста, укажите ваше имя.');

            return;
        }

        $rawPhone = trim($this->authPhone);
        if ($rawPhone === '' || $rawPhone === '+998') {
            $this->failAuth('authPhone', 'Введите корректный номер телефона (+998 и 9 цифр).');

            return;
        }

        $normalizedPhone = $this->store()->normalizeAndValidateUzPhone($rawPhone);
        if (! $normalizedPhone) {
            $this->failAuth('authPhone', 'Введите корректный номер телефона (+998 и 9 цифр).');

            return;
        }

        if (mb_strlen($this->authPassword) < 8) {
            $this->failAuth('authPassword', 'Пароль должен содержать минимум 8 символов.');

            return;
        }

        if ($this->authPassword !== $this->authPasswordConfirmation) {
            $this->failAuth('authPasswordConfirmation', 'Пароли не совпадают.');

            return;
        }

        $res = $this->emailAuth()->registerCustomerUser(
            $verifiedEmail,
            $cleanName,
            $normalizedPhone,
            $this->authPassword
        );

        if (! $res['success']) {
            if (($res['error'] ?? '') === 'phone_taken') {
                $this->failAuth('authPhone', $res['message']);

                return;
            }
            if (($res['error'] ?? '') === 'email_taken') {
                $this->authEmail = $verifiedEmail;
                $this->authStep = 'login';
            }
            $this->failAuth('authEmail', $res['message']);

            return;
        }

        session()->forget('mally_verified_register_email');
        $this->finishLogin($res['user'], 'Регистрация успешно завершена!');
    }

    /**
     * Password recovery: switch to Step A (`reset-email`).
     */
    public function openForgotPassword(): void
    {
        $this->clearAuthFeedback();
        $this->authOfferRegistration = false;
        if (trim($this->authEmail) !== '') {
            $this->authEmail = $this->emailAuth()->normalizeEmail($this->authEmail);
        }
        $this->authPassword = '';
        $this->authPasswordConfirmation = '';
        $this->authCode = '';
        $this->authStep = 'reset-email';
    }

    /**
     * Step A of password recovery: request a 6-digit reset code (`purpose = reset`).
     */
    public function sendPasswordResetCode(): void
    {
        $this->clearAuthFeedback();

        $normalizedEmail = $this->emailAuth()->normalizeEmail($this->authEmail);
        $this->authEmail = $normalizedEmail;

        if (! $this->emailAuth()->isValidEmail($normalizedEmail)) {
            $this->failAuth('authEmail', 'Введите корректный адрес электронной почты.');

            return;
        }

        $res = $this->emailAuth()->sendCode(
            $normalizedEmail,
            AuthCode::PURPOSE_RESET,
            (string) request()->ip()
        );

        if (! $res['success']) {
            if (isset($res['cooldown'])) {
                $this->authCooldownSeconds = (int) $res['cooldown'];
            }
            $this->failAuth('authEmail', $res['message']);

            return;
        }

        $this->authCode = '';
        $this->authCooldownSeconds = (int) ($res['cooldown'] ?? EmailAuthService::COOLDOWN_SECONDS);
        $this->authStep = 'reset-code';
    }

    /**
     * Resend password reset code (respecting 60s cooldown and 5/hr limits).
     */
    public function resendPasswordResetCode(): void
    {
        $this->clearAuthFeedback();

        $normalizedEmail = $this->emailAuth()->normalizeEmail($this->authEmail);
        if (! $this->emailAuth()->isValidEmail($normalizedEmail)) {
            $this->authStep = 'reset-email';

            return;
        }

        $res = $this->emailAuth()->sendCode(
            $normalizedEmail,
            AuthCode::PURPOSE_RESET,
            (string) request()->ip()
        );

        if (! $res['success']) {
            if (isset($res['cooldown'])) {
                $this->authCooldownSeconds = (int) $res['cooldown'];
            }
            $this->failAuth('authCode', $res['message']);

            return;
        }

        $this->authCode = '';
        $this->authCooldownSeconds = (int) ($res['cooldown'] ?? EmailAuthService::COOLDOWN_SECONDS);
    }

    /**
     * Step B of password recovery: verify the 6-digit reset code.
     */
    public function verifyPasswordResetCode(): void
    {
        $this->clearAuthFeedback();

        $normalizedEmail = $this->emailAuth()->normalizeEmail($this->authEmail);
        if (! $this->emailAuth()->isValidEmail($normalizedEmail)) {
            $this->authStep = 'reset-email';

            return;
        }

        $res = $this->emailAuth()->verifyCode(
            $normalizedEmail,
            AuthCode::PURPOSE_RESET,
            $this->authCode
        );

        if (! $res['success']) {
            $this->authStep = 'reset-code';
            $this->failAuth('authCode', $res['message']);

            return;
        }

        session(['mally_verified_reset_email' => $normalizedEmail]);
        $this->authCode = '';
        $this->authPassword = '';
        $this->authPasswordConfirmation = '';
        $this->authStep = 'reset-password';
    }

    /**
     * Step C of password recovery: save new password using email strictly from server session,
     * immediately log the user in (`session()->regenerate()`), and close the modal.
     */
    public function completePasswordReset(): void
    {
        $this->clearAuthFeedback();

        $verifiedEmail = (string) session('mally_verified_reset_email', '');
        if (! $this->emailAuth()->isValidEmail($verifiedEmail)) {
            $this->authStep = 'reset-email';
            $this->failAuth('authEmail', 'Сначала подтвердите код восстановления пароля.');

            return;
        }

        if (mb_strlen($this->authPassword) < 8) {
            $this->failAuth('authPassword', 'Новый пароль должен содержать минимум 8 символов.');

            return;
        }

        if ($this->authPassword !== $this->authPasswordConfirmation) {
            $this->failAuth('authPasswordConfirmation', 'Пароли не совпадают.');

            return;
        }

        $res = $this->emailAuth()->resetPassword($verifiedEmail, $this->authPassword);

        session()->forget('mally_verified_reset_email');
        $this->authPassword = '';
        $this->authPasswordConfirmation = '';

        if (! $res['success']) {
            $this->authStep = 'reset-email';
            $this->failAuth('authEmail', $res['message']);

            return;
        }

        $this->finishLogin($res['user']);
    }

    // =========================================================================
    // TELEGRAM LOGIN (see TelegramAuthService for the full flow)
    // =========================================================================

    /**
     * Step 1: create a one-time request bound to this browser session and
     * show the bot link. Also used from the profile to link Telegram.
     */
    public function startTelegramAuth(): void
    {
        $this->clearAuthFeedback();
        $this->authOfferRegistration = false;
        $this->authTelegramCode = '';

        if (! $this->telegramAuth()->isConfigured()) {
            $this->failAuth('authTelegram', __('Telegram login is temporarily unavailable.'));

            return;
        }

        $user = $this->store()->getCurrentUser();
        $started = $this->telegramAuth()->start(
            $user ? TelegramLoginRequest::PURPOSE_LINK : TelegramLoginRequest::PURPOSE_LOGIN,
            $user
        );

        if ($started === null) {
            $this->failAuth('authTelegram', __('Too many attempts. Please try again later.'));

            return;
        }

        $this->authTelegramBotUrl = $started['bot_url'];
        $this->authTelegramBotUsername = $started['bot_username'];
        $this->authTelegramStatus = TelegramLoginRequest::STATUS_PENDING;
        $this->authStep = 'telegram-wait';
    }

    /**
     * Polled by the modal while waiting for the bot: switches to the code
     * input as soon as the bot has sent the code.
     */
    public function checkTelegramAuthStatus(): void
    {
        if (! in_array($this->authStep, ['telegram-wait', 'telegram-code'], true)) {
            return;
        }

        $status = $this->telegramAuth()->status();
        $this->authTelegramStatus = $status;

        if ($status === TelegramAuthService::EXPIRED) {
            $this->authStep = 'telegram-wait';
            $this->failAuth('authTelegram', __('The Telegram login link has expired. Please start again.'));

            return;
        }

        if ($status === TelegramLoginRequest::STATUS_CODE_SENT && $this->authStep === 'telegram-wait') {
            $this->clearAuthFeedback();
            $this->authStep = 'telegram-code';
        }
    }

    /**
     * Step 2: the code the bot sent to the user's Telegram.
     */
    public function verifyTelegramCode(): void
    {
        $this->clearAuthFeedback();

        $res = $this->telegramAuth()->verifyCode($this->authTelegramCode);
        $this->authTelegramCode = '';

        if ($res['result'] === TelegramAuthService::INVALID) {
            $this->failAuth('authTelegramCode', __('Invalid verification code'));

            return;
        }

        if ($res['result'] !== TelegramAuthService::VERIFIED) {
            $this->authStep = 'telegram-wait';
            $this->authTelegramBotUrl = '';
            $this->failAuth('authTelegram', $res['result'] === TelegramAuthService::THROTTLED
                ? __('Too many attempts. Please try again later.')
                : __('The code has expired. Please request a new one.'));

            return;
        }

        $identity = $res['identity'];

        if ($identity['purpose'] === TelegramLoginRequest::PURPOSE_LINK) {
            $this->finishTelegramLink($identity);

            return;
        }

        // 1. This Telegram account is already linked -> sign in.
        $user = $this->accounts()->findByTelegram($identity['telegram_user_id']);
        if ($user) {
            $this->finishLogin($user);

            return;
        }

        // 2. The phone already belongs to an account that isn't linked to this
        //    Telegram: don't sign in and don't merge. The owner may link it
        //    after signing in with the account's password.
        if ($this->accounts()->phoneTaken($identity['phone'])) {
            session([self::PENDING_TELEGRAM_LINK => $identity + ['until' => now()->addMinutes(10)->timestamp]]);
            $this->authEmail = $identity['phone'];
            $this->authStep = 'login';
            $this->notifyAuth(__('An account with this phone number already exists. Sign in with its password to link Telegram.'));

            return;
        }

        // 3. New customer -> finish registration with name and password.
        session([self::VERIFIED_TELEGRAM => $identity + ['until' => now()->addMinutes(15)->timestamp]]);
        $this->authPhone = $identity['phone'];
        $this->authName = trim($identity['first_name'].' '.$identity['last_name']);
        $this->authPassword = '';
        $this->authPasswordConfirmation = '';
        $this->authStep = 'telegram-register';
    }

    /**
     * Step 3 (new customers): just a name. The phone is verified by Telegram
     * and these accounts always sign in through the bot, so no password.
     */
    public function completeTelegramRegistration(): void
    {
        $this->clearAuthFeedback();

        $identity = $this->sessionIdentity(self::VERIFIED_TELEGRAM);
        if (! $identity) {
            $this->authStep = 'login';
            $this->failAuth('authTelegram', __('The code has expired. Please request a new one.'));

            return;
        }

        $name = trim($this->authName);
        if ($name === '' || mb_strlen($name) > 100) {
            $this->failAuth('authName', 'Пожалуйста, укажите ваше имя.');

            return;
        }

        $res = $this->accounts()->registerFromTelegram($identity, $name);
        session()->forget(self::VERIFIED_TELEGRAM);

        if ($res['result'] !== CustomerAccountService::OK) {
            $this->authStep = 'login';
            $this->authEmail = $identity['phone'];
            $this->failAuth('authEmail', __('An account with this phone number already exists. Please sign in.'));

            return;
        }

        $this->finishLogin($res['user'], __('Registration via Telegram completed.'));
    }

    /** Link flow from the profile (the owner is already signed in). */
    protected function finishTelegramLink(array $identity): void
    {
        $user = $this->store()->getCurrentUser();
        if (! $user || (int) $identity['user_id'] !== (int) $user->id) {
            $this->authStep = 'login';
            $this->failAuth('authTelegram', __('Please sign in again.'));

            return;
        }

        $result = $this->accounts()->linkTelegram($user, $identity);
        $this->isAuthModalOpen = false;

        if ($result !== CustomerAccountService::OK) {
            $this->dispatch('toast', message: __('This Telegram account or phone number is already used by another account.'), type: 'error');

            return;
        }

        $customer = $this->accounts()->customerFor($user->fresh());
        if (method_exists($this, 'onCustomerAuthenticated')) {
            $this->onCustomerAuthenticated($customer);
        }
        $this->dispatch('toast', message: __('Telegram is linked and your phone number is confirmed.'), type: 'success');
    }

    /**
     * After a password login: link the Telegram identity that was verified a
     * moment ago, but only to the account that owns that phone number.
     */
    protected function linkPendingTelegram(User $user): void
    {
        $identity = $this->sessionIdentity(self::PENDING_TELEGRAM_LINK);
        session()->forget(self::PENDING_TELEGRAM_LINK);

        if ($identity && User::wherePhone($identity['phone'])->whereKey($user->id)->exists()) {
            $this->accounts()->linkTelegram($user, $identity);
        }
    }

    protected function sessionIdentity(string $key): ?array
    {
        $identity = session($key);

        return is_array($identity) && ($identity['until'] ?? 0) >= now()->timestamp ? $identity : null;
    }

    /**
     * Common end of every successful login: new session id, customer in the
     * session, modal closed, optional safe redirect.
     */
    protected function finishLogin(User $user, ?string $toast = null): Customer
    {
        $this->telegramAuth()->forgetSessionRequest();
        $customer = $this->accounts()->login($user);

        $this->authPassword = '';
        $this->authPasswordConfirmation = '';
        $this->authOfferRegistration = false;
        $this->authStep = 'login';
        $this->isAuthModalOpen = false;

        if (method_exists($this, 'onCustomerAuthenticated')) {
            $this->onCustomerAuthenticated($customer);
        }

        if ($toast !== null) {
            $this->dispatch('toast', message: $toast, type: 'success');
        }

        if ($this->authRedirectTo !== null) {
            if ($toast !== null) {
                session()->flash('mally_toast', ['message' => $toast, 'type' => 'success']);
            }
            $this->redirect($this->emailAuth()->sanitizeRedirectPath($this->authRedirectTo), navigate: true);
        }

        return $customer;
    }

    public function logoutCustomer(): void
    {
        $this->store()->logoutCustomer();
        $this->redirect(route('home'));
    }

    protected function clearAuthFeedback(): void
    {
        $this->authError = '';
        $this->authNotice = '';
        $this->resetValidation();
    }

    protected function failAuth(string $field, string $message): void
    {
        $this->authError = $message;
        $this->addError($field, $message);
    }

    protected function notifyAuth(string $message): void
    {
        $this->authNotice = $message;
    }

    protected function sharedViewData(): array
    {
        $store = $this->store();
        $rateData = $store->getExchangeRateData();

        return [
            'store' => $store,
            'locale' => $store->getLocale(),
            'currency' => $store->getCurrency(),
            'exchangeRate' => $rateData['rate'],
            'rateSource' => $rateData['source'],
            'cartCount' => $store->getCartCount(),
            'favoritesCount' => $store->getFavoritesCount(),
            'currentCustomer' => $store->getCurrentCustomer(),
            'headerCategories' => $store->getHeaderCategories(),
        ];
    }
}
