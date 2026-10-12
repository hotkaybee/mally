<?php

namespace App\Livewire\Storefront\Concerns;

use App\Models\AuthCode;
use App\Services\EmailAuthService;
use App\Services\StorefrontService;
use App\Services\TelegramAuthService;

trait InteractsWithStore
{
    public bool $isAuthModalOpen = false;

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

    public string $authTelegramToken = '';

    public string $authTelegramBotUrl = '';

    public string $authTelegramBotUsername = '';

    public string $authTelegramStatus = 'pending';

    public string $authMergeTargetName = '';

    public string $authMergeTargetEmail = '';

    protected function store(): StorefrontService
    {
        return app(StorefrontService::class);
    }

    protected function emailAuth(): EmailAuthService
    {
        return app(EmailAuthService::class);
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

    public function updateCartQty(string|int $productKey, int $qty): void
    {
        $this->updateCartQuantity((string) $productKey, $qty);
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

    public function backToEmailStep(): void
    {
        $this->backToLogin(true);
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
            $normalizedInput = $this->telegramAuth()->normalizeTelegramPhone($rawInput);
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

        $user = $res['user'];
        $customer = $res['customer'];

        session()->regenerate();
        session(['mally_user_id' => $user->id]);
        $this->store()->loginCustomer($customer);

        $this->authPassword = '';
        $this->authOfferRegistration = false;
        $this->isAuthModalOpen = false;

        if (method_exists($this, 'onCustomerAuthenticated')) {
            $this->onCustomerAuthenticated($customer);
        }

        if ($this->authRedirectTo !== null) {
            $safeTarget = $this->emailAuth()->sanitizeRedirectPath($this->authRedirectTo);
            $this->redirect($safeTarget, navigate: true);
        }
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

    public function submitAuthEmail(): void
    {
        $this->sendRegisterCode();
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
            if (($res['error'] ?? '') === 'email_taken') {
                $this->authEmail = $verifiedEmail;
                $this->authStep = 'login';
            }
            $this->failAuth('authEmail', $res['message']);

            return;
        }

        session()->forget('mally_verified_register_email');

        $user = $res['user'];
        $customer = $res['customer'];

        session()->regenerate();
        session(['mally_user_id' => $user->id]);
        $this->store()->loginCustomer($customer);

        $this->authPassword = '';
        $this->authPasswordConfirmation = '';
        $this->isAuthModalOpen = false;

        if (method_exists($this, 'onCustomerAuthenticated')) {
            $this->onCustomerAuthenticated($customer);
        }

        $toastMsg = ! empty($res['merged'])
            ? 'Ваш Telegram и почта успешно объединены в один профиль! Все заказы сохранены.'
            : 'Регистрация успешно завершена!';

        $this->dispatch('toast', message: $toastMsg, type: 'success');

        if ($this->authRedirectTo !== null) {
            session()->flash('mally_toast', ['message' => $toastMsg, 'type' => 'success']);
            $safeTarget = $this->emailAuth()->sanitizeRedirectPath($this->authRedirectTo);
            $this->redirect($safeTarget, navigate: true);
        }
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

        $user = $res['user'];
        $customer = $res['customer'];

        session()->regenerate();
        session(['mally_user_id' => $user->id]);
        $this->store()->loginCustomer($customer);

        $this->authOfferRegistration = false;
        $this->isAuthModalOpen = false;

        if (method_exists($this, 'onCustomerAuthenticated')) {
            $this->onCustomerAuthenticated($customer);
        }

        if ($this->authRedirectTo !== null) {
            $safeTarget = $this->emailAuth()->sanitizeRedirectPath($this->authRedirectTo);
            $this->redirect($safeTarget, navigate: true);
        }
    }

    /**
     * Start Telegram Bot verification flow: generate a unique token, open the waiting step,
     * and give the user the `t.me/{bot}?start=auth_{token}` link.
     */
    public function startTelegramAuth(): void
    {
        $this->clearAuthFeedback();
        $this->authOfferRegistration = false;

        $session = $this->telegramAuth()->startAuthSession();

        $this->authTelegramToken = $session['token'];
        $this->authTelegramBotUrl = $session['bot_url'];
        $this->authTelegramBotUsername = $session['bot_username'];
        $this->authTelegramStatus = 'pending';

        session([
            'mally_tg_auth_token' => $session['token'],
        ]);

        $this->authStep = 'telegram-wait';
    }

    /**
     * Poll Telegram bot status every 2 seconds while the user is on the `telegram-wait` modal screen.
     * - If the user already exists in the DB by phone -> logs them in immediately and closes the modal.
     * - If the user is new -> opens the `telegram-register` screen with verified phone pre-filled and optional email.
     */
    public function checkTelegramAuthStatus(): void
    {
        if ($this->authStep !== 'telegram-wait') {
            return;
        }

        $token = (string) session('mally_tg_auth_token', $this->authTelegramToken);
        if ($token === '') {
            return;
        }

        $res = $this->telegramAuth()->checkTokenStatus($token, true);
        $status = (string) ($res['status'] ?? 'pending');
        $this->authTelegramStatus = $status;

        if ($status === 'login' && isset($res['user'], $res['customer'])) {
            session()->forget('mally_tg_auth_token');

            $user = $res['user'];
            $customer = $res['customer'];

            session()->regenerate();
            session(['mally_user_id' => $user->id]);
            $this->store()->loginCustomer($customer);

            $this->authOfferRegistration = false;
            $this->isAuthModalOpen = false;

            if (method_exists($this, 'onCustomerAuthenticated')) {
                $this->onCustomerAuthenticated($customer);
            }

            if ($this->authRedirectTo !== null) {
                $safeTarget = $this->emailAuth()->sanitizeRedirectPath($this->authRedirectTo);
                $this->redirect($safeTarget, navigate: true);
            }

            return;
        }

        if ($status === 'merge_confirm' && isset($res['merge_user'])) {
            $verifiedPhone = (string) ($res['phone'] ?? '');
            $tgName = (string) ($res['name'] ?? '');
            $tgUsername = (string) ($res['telegram_username'] ?? '');
            $mergeUser = $res['merge_user'];

            session([
                'mally_verified_tg_phone' => $verifiedPhone,
                'mally_verified_tg_username' => $tgUsername,
                'mally_tg_merge_user_id' => (int) ($mergeUser['id'] ?? 0),
                'mally_tg_name' => $tgName,
            ]);
            session()->forget('mally_tg_auth_token');

            $this->authPhone = $verifiedPhone;
            $this->authName = $tgName;
            $this->authMergeTargetName = (string) ($mergeUser['name'] ?? '');
            $this->authMergeTargetEmail = (string) ($mergeUser['email'] ?? '');
            $this->clearAuthFeedback();
            $this->authStep = 'telegram-merge-confirm';

            return;
        }

        if ($status === 'register') {
            $verifiedPhone = (string) ($res['phone'] ?? '');
            $tgName = (string) ($res['name'] ?? '');
            $tgUsername = (string) ($res['telegram_username'] ?? '');

            session([
                'mally_verified_tg_phone' => $verifiedPhone,
                'mally_verified_tg_username' => $tgUsername,
            ]);
            session()->forget('mally_tg_auth_token');

            $this->authPhone = $verifiedPhone;
            $this->authName = $tgName;
            $this->authEmail = '';
            $this->authPassword = '';
            $this->authPasswordConfirmation = '';
            $this->clearAuthFeedback();
            $this->authStep = 'telegram-register';
        }
    }

    /**
     * User clicked "Да, объединить аккаунты и войти" when Telegram phone matched an existing Gmail/Email account.
     * Merges accounts and 100% of order history inside a DB transaction and logs in immediately.
     */
    public function confirmTelegramMerge(): void
    {
        $this->clearAuthFeedback();

        $verifiedPhone = (string) session('mally_verified_tg_phone', '');
        $tgUsername = (string) session('mally_verified_tg_username', '');
        $mergeUserId = (int) session('mally_tg_merge_user_id', 0);
        $fallbackName = (string) session('mally_tg_name', $this->authName);

        if ($verifiedPhone === '' || $mergeUserId <= 0) {
            $this->startTelegramAuth();

            return;
        }

        $primaryUser = \App\Models\User::find($mergeUserId);
        if (! $primaryUser) {
            $this->authStep = 'telegram-register';

            return;
        }

        $merged = $this->telegramAuth()->mergePhoneAccountsIntoPrimary(
            $primaryUser,
            $verifiedPhone,
            $tgUsername,
            $fallbackName
        );

        session()->forget([
            'mally_verified_tg_phone',
            'mally_verified_tg_username',
            'mally_tg_merge_user_id',
            'mally_tg_name',
            'mally_tg_auth_token',
        ]);

        $user = $merged['user'];
        $customer = $merged['customer'];

        session()->regenerate();
        session(['mally_user_id' => $user->id]);
        $this->store()->loginCustomer($customer);

        $this->authPassword = '';
        $this->authPasswordConfirmation = '';
        $this->isAuthModalOpen = false;

        if (method_exists($this, 'onCustomerAuthenticated')) {
            $this->onCustomerAuthenticated($customer);
        }

        $mergeToast = 'Ваш Telegram и аккаунт с почтой успешно объединены! Все заказы сохранены.';
        $this->dispatch('toast', message: $mergeToast, type: 'success');

        if ($this->authRedirectTo !== null) {
            session()->flash('mally_toast', ['message' => $mergeToast, 'type' => 'success']);
            $safeTarget = $this->emailAuth()->sanitizeRedirectPath($this->authRedirectTo);
            $this->redirect($safeTarget, navigate: true);
        }
    }

    /**
     * User clicked "Нет, создать отдельный аккаунт" on the merge confirmation step.
     */
    public function declineTelegramMerge(): void
    {
        $this->clearAuthFeedback();

        $verifiedPhone = (string) session('mally_verified_tg_phone', '');
        if ($verifiedPhone === '') {
            $this->startTelegramAuth();

            return;
        }

        $this->telegramAuth()->detachPhoneFromOtherAccounts($verifiedPhone);

        session()->forget('mally_tg_merge_user_id');

        $this->authPhone = $verifiedPhone;
        $this->authEmail = '';
        $this->authPassword = '';
        $this->authPasswordConfirmation = '';
        $this->authStep = 'telegram-register';
    }

    /**
     * Complete registration for a new user whose phone was verified via Telegram bot.
     * Password is REQUIRED (min 8 chars). Gmail/Email is OPTIONAL!
     */
    public function completeTelegramRegistration(): void
    {
        $this->clearAuthFeedback();

        $verifiedPhone = (string) session('mally_verified_tg_phone', '');
        $tgUsername = (string) session('mally_verified_tg_username', '');

        if ($verifiedPhone === '') {
            $this->startTelegramAuth();

            return;
        }

        if (trim($this->authName) === '') {
            $this->failAuth('authName', 'Пожалуйста, укажите ваше имя.');

            return;
        }

        if (mb_strlen($this->authPassword) < 8) {
            $this->failAuth('authPassword', 'Пароль обязателен и должен содержать минимум 8 символов.');

            return;
        }

        if ($this->authPassword !== $this->authPasswordConfirmation) {
            $this->failAuth('authPasswordConfirmation', 'Пароли не совпадают.');

            return;
        }

        $res = $this->telegramAuth()->registerTelegramUser(
            $verifiedPhone,
            $this->authName,
            $this->authEmail,
            $this->authPassword,
            $tgUsername
        );

        if (! $res['success']) {
            if (($res['error'] ?? '') === 'merge_needed' && isset($res['merge_user'])) {
                $mergeUser = $res['merge_user'];
                session([
                    'mally_tg_merge_user_id' => (int) ($mergeUser['id'] ?? 0),
                    'mally_tg_name' => $this->authName,
                ]);
                $this->authMergeTargetName = (string) ($mergeUser['name'] ?? '');
                $this->authMergeTargetEmail = (string) ($mergeUser['email'] ?? '');
                $this->authStep = 'telegram-merge-confirm';

                return;
            }

            $field = (string) ($res['field'] ?? 'authName');
            $this->failAuth($field, $res['message']);

            return;
        }

        session()->forget([
            'mally_verified_tg_phone',
            'mally_verified_tg_username',
            'mally_tg_merge_user_id',
            'mally_tg_name',
            'mally_tg_auth_token',
        ]);

        $user = $res['user'];
        $customer = $res['customer'];

        session()->regenerate();
        session(['mally_user_id' => $user->id]);
        $this->store()->loginCustomer($customer);

        $this->authPassword = '';
        $this->authPasswordConfirmation = '';
        $this->isAuthModalOpen = false;

        if (method_exists($this, 'onCustomerAuthenticated')) {
            $this->onCustomerAuthenticated($customer);
        }

        $tgToast = 'Регистрация через Telegram успешно завершена!';
        $this->dispatch('toast', message: $tgToast, type: 'success');

        if ($this->authRedirectTo !== null) {
            session()->flash('mally_toast', ['message' => $tgToast, 'type' => 'success']);
            $safeTarget = $this->emailAuth()->sanitizeRedirectPath($this->authRedirectTo);
            $this->redirect($safeTarget, navigate: true);
        }
    }

    public function logoutCustomer(): void
    {
        session()->forget('mally_user_id');
        $this->store()->logoutCustomer();
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
