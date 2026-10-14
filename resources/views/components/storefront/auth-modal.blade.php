@props([
    'store' => null,
    'isOpen' => null,
    'step' => null,
    'phone' => null,
    'message' => null,
])

@php
    $store = $store ?? app(\App\Services\StorefrontService::class);
    $isOpen = $isOpen ?? ($this->isAuthModalOpen ?? false);
    $step = $step ?? ($this->authStep ?? 'login');
    $offerRegistration = (bool) ($this->authOfferRegistration ?? false);
    $verifiedRegisterEmail = (string) session('mally_verified_register_email', $this->authEmail ?? '');
    $verifiedResetEmail = (string) session('mally_verified_reset_email', $this->authEmail ?? '');

    $txt = [
        'login_title' => 'Вход в аккаунт',
        'email_label' => 'Email',
        'email_placeholder' => 'name@example.com',
        'password_label' => 'Пароль',
        'password_placeholder' => 'Введите пароль',
        'forgot_password' => 'Забыли пароль?',
        'login_btn' => 'Войти',
        'register_btn' => 'Зарегистрироваться',
        'register_with_email_btn' => 'Зарегистрироваться с этим email',
        'reg_email_title' => 'Регистрация',
        'reg_email_sub' => 'Введите ваш email, чтобы получить 6-значный код подтверждения.',
        'get_code_btn' => 'Получить код',
        'reg_code_title' => 'Подтверждение почты',
        'reg_code_sub' => 'Мы отправили 6-значный код подтверждения на адрес:',
        'code_placeholder' => '000000',
        'verify_code_btn' => 'Подтвердить',
        'resend_wait' => 'Отправить повторно через',
        'resend_sec' => 'сек.',
        'resend_btn' => 'Отправить повторно',
        'reg_profile_title' => 'Завершение регистрации',
        'reg_profile_sub' => 'Заполните контактные данные и задайте пароль для входа',
        'name_label' => 'Имя *',
        'name_placeholder' => 'Например, Малика',
        'phone_label' => 'Телефон *',
        'phone_placeholder' => '+998 90 123 45 67',
        'new_pass_label' => 'Пароль (минимум 8 символов) *',
        'confirm_pass_label' => 'Подтверждение пароля *',
        'complete_reg_btn' => 'Зарегистрироваться',
        'reset_email_title' => 'Восстановление пароля',
        'reset_email_sub' => 'Укажите ваш email — мы отправим 6-значный код для сброса пароля.',
        'send_reset_code_btn' => 'Отправить код',
        'reset_code_title' => 'Код восстановления',
        'reset_code_sub' => 'Введите 6-значный код, отправленный на адрес:',
        'reset_pass_title' => 'Новый пароль',
        'reset_pass_sub' => 'Задайте новый пароль длиной не менее 8 символов.',
        'reset_new_pass_label' => 'Новый пароль (минимум 8 символов) *',
        'save_and_login_btn' => 'Сохранить и войти',
        'back_btn' => 'Назад',
        'show_pass' => 'Показать пароль',
        'hide_pass' => 'Скрыть пароль',
        'code_ttl_hint' => 'Код действует 10 минут',
    ];
@endphp

@if($isOpen)
    <div
        class="fixed inset-0 z-[100] flex items-center justify-center bg-black/60 backdrop-blur-sm p-4 animate-fadeIn"
        wire:click.self="closeAuthModal"
    >
        <div
            class="relative w-full max-w-md bg-white rounded-3xl shadow-2xl border border-[#EAE3DE] p-6 sm:p-8 overflow-hidden"
            @click.stop
        >
            <!-- Кнопка закрытия -->
            <button
                type="button"
                wire:click="closeAuthModal"
                aria-label="Close"
                class="absolute top-5 right-5 w-9 h-9 rounded-full bg-[#FAF7F5] hover:bg-[#EAE3DE] flex items-center justify-center text-[#78716C] hover:text-[#1C1917] transition cursor-pointer"
            >
                <i class="fa-solid fa-xmark text-sm"></i>
            </button>

            <!-- Логотип (тот же markup, что в header.blade.php) -->
            <div class="flex justify-center mb-5">
                <div class="flex flex-col items-center text-center">
                    <div class="font-serif text-3xl sm:text-4xl font-bold tracking-wider text-[#1C1917] leading-none">
                        mally
                    </div>
                    <span class="text-[8px] sm:text-[9px] uppercase tracking-[0.22em] sm:tracking-[0.3em] text-[#8C827A] font-medium mt-1 whitespace-nowrap">
                        Beauty without limits
                    </span>
                </div>
            </div>

            @if($step === 'login' || $step === 'email' || $step === 'password')
                <!-- ЭКРАН 1: ВХОД В АККАУНТ -->
                <div x-data="{ showPassword: false }">
                    <h2 class="font-serif text-2xl font-bold text-[#1C1917] text-center mb-5">
                        {{ $txt['login_title'] }}
                    </h2>

                    @if($this->authNotice)
                        <p class="mb-4 p-3 rounded-xl bg-sky-50 border border-sky-200 text-xs text-sky-800 font-medium text-center">{{ $this->authNotice }}</p>
                    @endif
                    @error('authTelegram')
                        <p role="alert" class="mb-4 p-3 rounded-xl bg-rose-50 border border-rose-200 text-xs text-rose-700 font-medium text-center">{{ $message }}</p>
                    @enderror

                    <form wire:submit="loginWithPassword" class="space-y-4" novalidate>
                        <!-- Поле Email или Телефон -->
                        <div>
                            <label for="mally-login-email" class="block text-[11px] font-semibold uppercase tracking-wider text-[#78716C] mb-1.5">
                                Email или телефон
                            </label>
                            <div class="relative">
                                <span class="absolute inset-y-0 left-0 pl-4 flex items-center text-stone-400 pointer-events-none">
                                    <i class="fa-regular fa-user text-xs"></i>
                                </span>
                                <input
                                    id="mally-login-email"
                                    type="text"
                                    wire:model="authEmail"
                                    placeholder="name@example.com или +998 90 123 45 67"
                                    autocomplete="username"
                                    class="w-full pl-10 pr-4 py-3.5 rounded-xl bg-[#FAF8F6] border border-[#E3DBD5] text-sm font-medium text-[#1C1917] focus:outline-none focus:border-[#1C1917] focus:bg-white transition"
                                />
                            </div>
                            @error('authEmail')
                                <p role="alert" class="mt-1.5 text-xs text-rose-600 font-medium">{{ $message }}</p>
                            @enderror

                            @if($offerRegistration)
                                <div class="mt-2.5">
                                    <button
                                        type="button"
                                        wire:click="startRegistrationWithEmail"
                                        wire:loading.attr="disabled"
                                        class="w-full py-2.5 px-4 rounded-xl bg-amber-50 hover:bg-amber-100 border border-amber-300 text-[#1C1917] text-xs font-bold transition flex items-center justify-center gap-2 cursor-pointer"
                                    >
                                        <span>{{ $txt['register_with_email_btn'] }}</span>
                                        <i class="fa-solid fa-arrow-right text-[10px]"></i>
                                    </button>
                                </div>
                            @endif
                        </div>

                        <!-- Поле Пароль -->
                        <div>
                            <label for="mally-login-password" class="block text-[11px] font-semibold uppercase tracking-wider text-[#78716C] mb-1.5">
                                {{ $txt['password_label'] }}
                            </label>
                            <div class="relative">
                                <input
                                    id="mally-login-password"
                                    :type="showPassword ? 'text' : 'password'"
                                    type="password"
                                    wire:model="authPassword"
                                    placeholder="{{ $txt['password_placeholder'] }}"
                                    autocomplete="current-password"
                                    class="w-full pl-4 pr-11 py-3.5 rounded-xl bg-[#FAF8F6] border border-[#E3DBD5] text-sm font-medium text-[#1C1917] focus:outline-none focus:border-[#1C1917] focus:bg-white transition"
                                />
                                <button
                                    type="button"
                                    @click="showPassword = !showPassword"
                                    :aria-label="showPassword ? '{{ $txt['hide_pass'] }}' : '{{ $txt['show_pass'] }}'"
                                    class="absolute inset-y-0 right-0 pr-3.5 flex items-center text-stone-500 hover:text-[#1C1917] transition cursor-pointer"
                                >
                                    <i class="fa-regular text-sm" :class="showPassword ? 'fa-eye-slash' : 'fa-eye'"></i>
                                </button>
                            </div>
                            @error('authPassword')
                                <p role="alert" class="mt-1.5 text-xs text-rose-600 font-medium">{{ $message }}</p>
                            @enderror

                            <!-- Ссылка «Забыли пароль?» под полем пароля -->
                            <div class="mt-2 flex justify-end">
                                <button
                                    type="button"
                                    wire:click="openForgotPassword"
                                    class="text-xs font-medium text-[#78716C] hover:text-[#1C1917] underline underline-offset-4 transition cursor-pointer"
                                >
                                    {{ $txt['forgot_password'] }}
                                </button>
                            </div>
                        </div>

                        <!-- Кнопка «Войти» -->
                        <button
                            type="submit"
                            wire:loading.attr="disabled"
                            class="w-full py-3.5 px-5 rounded-xl bg-[#1C1917] hover:bg-[#2C2724] disabled:opacity-60 text-[#FAF7F5] text-xs font-bold uppercase tracking-wider transition shadow-md flex items-center justify-center gap-2 cursor-pointer"
                        >
                            <span>{{ $txt['login_btn'] }}</span>
                        </button>
                    </form>

                    <!-- Крупная заметная кнопка «Зарегистрироваться» и «Продолжить через Telegram» внизу окна -->
                    <div class="mt-5 pt-5 border-t border-[#EAE3DE] space-y-2.5">
                        <button
                            type="button"
                            wire:click="openRegister"
                            wire:loading.attr="disabled"
                            class="w-full py-3.5 px-5 rounded-xl bg-[#FAF7F5] hover:bg-[#F2EAE4] border-2 border-[#1C1917] text-[#1C1917] text-xs font-bold uppercase tracking-wider transition flex items-center justify-center gap-2 cursor-pointer"
                        >
                            <span>{{ $txt['register_btn'] }}</span>
                        </button>

                        <button
                            type="button"
                            wire:click="startTelegramAuth"
                            wire:loading.attr="disabled"
                            class="w-full py-3.5 px-5 rounded-xl bg-[#0088CC] hover:bg-[#0077B5] disabled:opacity-60 text-white text-xs font-bold uppercase tracking-wider transition shadow-sm flex items-center justify-center gap-2 cursor-pointer"
                        >
                            <i class="fa-brands fa-telegram text-base"></i>
                            <span>Продолжить через Telegram</span>
                        </button>
                    </div>
                </div>

            @elseif($step === 'register-email')
                <!-- РЕГИСТРАЦИЯ: ШАГ 1 — ВВОД EMAIL -->
                <div>
                    <h2 class="font-serif text-2xl font-bold text-[#1C1917] text-center mb-1.5">
                        {{ $txt['reg_email_title'] }}
                    </h2>
                    <p class="text-xs text-[#78716C] text-center mb-5 leading-relaxed">
                        {{ $txt['reg_email_sub'] }}
                    </p>

                    <form wire:submit="sendRegisterCode" class="space-y-4" novalidate>
                        <div>
                            <label for="mally-register-email" class="block text-[11px] font-semibold uppercase tracking-wider text-[#78716C] mb-1.5">
                                {{ $txt['email_label'] }}
                            </label>
                            <div class="relative">
                                <span class="absolute inset-y-0 left-0 pl-4 flex items-center text-stone-400 pointer-events-none">
                                    <i class="fa-regular fa-envelope text-xs"></i>
                                </span>
                                <input
                                    id="mally-register-email"
                                    type="email"
                                    wire:model="authEmail"
                                    placeholder="{{ $txt['email_placeholder'] }}"
                                    autocomplete="email"
                                    class="w-full pl-10 pr-4 py-3.5 rounded-xl bg-[#FAF8F6] border border-[#E3DBD5] text-sm font-medium text-[#1C1917] focus:outline-none focus:border-[#1C1917] focus:bg-white transition"
                                />
                            </div>
                            @error('authEmail')
                                <p role="alert" class="mt-1.5 text-xs text-rose-600 font-medium">{{ $message }}</p>
                            @enderror
                        </div>

                        <button
                            type="submit"
                            wire:loading.attr="disabled"
                            class="w-full py-3.5 px-5 rounded-xl bg-[#1C1917] hover:bg-[#2C2724] disabled:opacity-60 text-[#FAF7F5] text-xs font-bold uppercase tracking-wider transition shadow-md flex items-center justify-center gap-2 cursor-pointer"
                        >
                            <span>{{ $txt['get_code_btn'] }}</span>
                        </button>

                        <button
                            type="button"
                            wire:click="startTelegramAuth"
                            wire:loading.attr="disabled"
                            class="w-full py-3 px-4 rounded-xl bg-[#0088CC]/10 hover:bg-[#0088CC]/15 border border-[#0088CC]/30 text-[#0077B5] text-xs font-bold transition flex items-center justify-center gap-2 cursor-pointer"
                        >
                            <i class="fa-brands fa-telegram text-base"></i>
                            <span>Без почты — продолжить через Telegram</span>
                        </button>

                        <button
                            type="button"
                            wire:click="backToLogin"
                            class="w-full py-2.5 text-xs text-[#78716C] hover:text-[#1C1917] font-medium transition flex items-center justify-center gap-1.5 cursor-pointer"
                        >
                            <i class="fa-solid fa-arrow-left text-[10px]"></i>
                            <span>{{ $txt['back_btn'] }}</span>
                        </button>
                    </form>
                </div>

            @elseif($step === 'register-code')
                <!-- РЕГИСТРАЦИЯ: ШАГ 2 — ВВОД 6-ЗНАЧНОГО КОДА -->
                <div
                    x-data="{
                        seconds: @entangle('authCooldownSeconds'),
                        timer: null,
                        init() {
                            this.timer = setInterval(() => {
                                if (this.seconds > 0) {
                                    this.seconds--;
                                }
                            }, 1000);
                        }
                    }"
                >
                    <h2 class="font-serif text-2xl font-bold text-[#1C1917] text-center mb-1.5">
                        {{ $txt['reg_code_title'] }}
                    </h2>
                    <p class="text-xs text-[#78716C] text-center mb-1.5 leading-relaxed">
                        {{ $txt['reg_code_sub'] }}
                        <span class="font-semibold text-[#1C1917] break-all">{{ $this->authEmail }}</span>
                    </p>
                    <p class="text-[11px] text-[#9E8B89] text-center mb-5">
                        {{ $txt['code_ttl_hint'] }}
                    </p>

                    <form wire:submit="verifyRegisterCode" class="space-y-4" novalidate>
                        <div>
                            <input
                                type="text"
                                inputmode="numeric"
                                pattern="[0-9]*"
                                maxlength="6"
                                wire:model="authCode"
                                placeholder="{{ $txt['code_placeholder'] }}"
                                autocomplete="one-time-code"
                                class="w-full px-4 py-3.5 rounded-xl bg-[#FCECEC]/50 border border-[#E3DBD5] text-center text-2xl font-mono tracking-[0.45em] font-bold text-[#1C1917] focus:outline-none focus:border-[#1C1917] focus:bg-white transition"
                            />
                            @error('authCode')
                                <p role="alert" class="mt-1.5 text-xs text-rose-600 font-medium text-center">{{ $message }}</p>
                            @enderror
                        </div>

                        <button
                            type="submit"
                            wire:loading.attr="disabled"
                            class="w-full py-3.5 px-5 rounded-xl bg-[#1C1917] hover:bg-[#2C2724] disabled:opacity-60 text-[#FAF7F5] text-xs font-bold uppercase tracking-wider transition shadow-md cursor-pointer"
                        >
                            {{ $txt['verify_code_btn'] }}
                        </button>
                    </form>

                    <div class="mt-4 text-center space-y-2">
                        <template x-if="seconds > 0">
                            <p class="text-xs text-[#78716C]">
                                {{ $txt['resend_wait'] }} <span class="font-mono font-semibold text-[#1C1917]" x-text="seconds"></span> {{ $txt['resend_sec'] }}
                            </p>
                        </template>
                        <template x-if="seconds <= 0">
                            <button
                                type="button"
                                wire:click="resendRegisterCode"
                                wire:loading.attr="disabled"
                                class="text-xs font-semibold text-[#1C1917] hover:underline underline-offset-4 transition cursor-pointer"
                            >
                                {{ $txt['resend_btn'] }}
                            </button>
                        </template>

                        <div>
                            <button
                                type="button"
                                wire:click="setAuthStep('register-email')"
                                class="w-full py-2 text-xs text-[#78716C] hover:text-[#1C1917] font-medium transition flex items-center justify-center gap-1.5 cursor-pointer"
                            >
                                <i class="fa-solid fa-arrow-left text-[10px]"></i>
                                <span>{{ $txt['back_btn'] }}</span>
                            </button>
                        </div>
                    </div>
                </div>

            @elseif($step === 'register-profile')
                <!-- РЕГИСТРАЦИЯ: ШАГ 3 — ИМЯ, ТЕЛЕФОН С МАСКОЙ +998, ПАРОЛЬ И ПОДТВЕРЖДЕНИЕ -->
                <div x-data="{ showPassword: false, showPasswordConfirm: false }">
                    <h2 class="font-serif text-2xl font-bold text-[#1C1917] text-center mb-1.5">
                        {{ $txt['reg_profile_title'] }}
                    </h2>
                    <p class="text-xs text-[#78716C] text-center mb-4 leading-relaxed">
                        {{ $txt['reg_profile_sub'] }}
                    </p>

                    <div class="mb-4 px-3.5 py-2.5 rounded-xl bg-[#FAF7F5] border border-[#EAE3DE] flex items-center justify-between gap-2">
                        <div class="flex items-center gap-2 min-w-0">
                            <i class="fa-solid fa-circle-check text-emerald-600 text-xs flex-shrink-0"></i>
                            <span class="text-xs font-medium text-[#1C1917] truncate">{{ $verifiedRegisterEmail }}</span>
                        </div>
                    </div>

                    <form wire:submit="completeEmailRegistration" class="space-y-3.5" novalidate>
                        <!-- Имя -->
                        <div>
                            <label for="mally-reg-name" class="block text-[11px] font-semibold uppercase tracking-wider text-[#78716C] mb-1">
                                {{ $txt['name_label'] }}
                            </label>
                            <input
                                id="mally-reg-name"
                                type="text"
                                wire:model="authName"
                                placeholder="{{ $txt['name_placeholder'] }}"
                                autocomplete="name"
                                class="w-full px-3.5 py-2.5 rounded-xl bg-[#FAF8F6] border border-[#E3DBD5] text-sm text-[#1C1917] focus:outline-none focus:border-[#1C1917]"
                            />
                            @error('authName')
                                <p role="alert" class="mt-1 text-xs text-rose-600 font-medium">{{ $message }}</p>
                            @enderror
                        </div>

                        <!-- Телефон с фиксированным префиксом +998 и маской +998 XX XXX XX XX -->
                        <div
                            x-data="{
                                formatUzPhone(e) {
                                    let digits = (e.target.value || '').replace(/\D/g, '');
                                    if (digits.startsWith('998')) {
                                        digits = digits.slice(3);
                                    }
                                    digits = digits.slice(0, 9);
                                    let formatted = '+998';
                                    if (digits.length > 0) formatted += ' ' + digits.slice(0, 2);
                                    if (digits.length > 2) formatted += ' ' + digits.slice(2, 5);
                                    if (digits.length > 5) formatted += ' ' + digits.slice(5, 7);
                                    if (digits.length > 7) formatted += ' ' + digits.slice(7, 9);
                                    e.target.value = formatted;
                                    $wire.set('authPhone', formatted, false);
                                }
                            }"
                        >
                            <label for="mally-reg-phone" class="block text-[11px] font-semibold uppercase tracking-wider text-[#78716C] mb-1">
                                {{ $txt['phone_label'] }}
                            </label>
                            <input
                                id="mally-reg-phone"
                                type="tel"
                                maxlength="17"
                                wire:model="authPhone"
                                placeholder="{{ $txt['phone_placeholder'] }}"
                                autocomplete="tel"
                                @input="formatUzPhone($event)"
                                @focus="if (!$event.target.value || !$event.target.value.startsWith('+998')) { $event.target.value = '+998 '; $wire.set('authPhone', '+998 ', false); }"
                                class="w-full px-3.5 py-2.5 rounded-xl bg-[#FAF8F6] border border-[#E3DBD5] text-sm font-mono text-[#1C1917] focus:outline-none focus:border-[#1C1917]"
                            />
                            @error('authPhone')
                                <p role="alert" class="mt-1 text-xs text-rose-600 font-medium">{{ $message }}</p>
                            @enderror
                        </div>

                        <!-- Пароль -->
                        <div>
                            <label for="mally-reg-password" class="block text-[11px] font-semibold uppercase tracking-wider text-[#78716C] mb-1">
                                {{ $txt['new_pass_label'] }}
                            </label>
                            <div class="relative">
                                <input
                                    id="mally-reg-password"
                                    :type="showPassword ? 'text' : 'password'"
                                    type="password"
                                    wire:model="authPassword"
                                    minlength="8"
                                    autocomplete="new-password"
                                    class="w-full pl-3.5 pr-10 py-2.5 rounded-xl bg-[#FAF8F6] border border-[#E3DBD5] text-sm text-[#1C1917] focus:outline-none focus:border-[#1C1917]"
                                />
                                <button
                                    type="button"
                                    @click="showPassword = !showPassword"
                                    :aria-label="showPassword ? '{{ $txt['hide_pass'] }}' : '{{ $txt['show_pass'] }}'"
                                    class="absolute inset-y-0 right-0 pr-3 flex items-center text-stone-500 hover:text-[#1C1917] cursor-pointer"
                                >
                                    <i class="fa-regular text-xs" :class="showPassword ? 'fa-eye-slash' : 'fa-eye'"></i>
                                </button>
                            </div>
                            @error('authPassword')
                                <p role="alert" class="mt-1 text-xs text-rose-600 font-medium">{{ $message }}</p>
                            @enderror
                        </div>

                        <!-- Подтверждение пароля -->
                        <div>
                            <label for="mally-reg-password-confirm" class="block text-[11px] font-semibold uppercase tracking-wider text-[#78716C] mb-1">
                                {{ $txt['confirm_pass_label'] }}
                            </label>
                            <div class="relative">
                                <input
                                    id="mally-reg-password-confirm"
                                    :type="showPasswordConfirm ? 'text' : 'password'"
                                    type="password"
                                    wire:model="authPasswordConfirmation"
                                    minlength="8"
                                    autocomplete="new-password"
                                    class="w-full pl-3.5 pr-10 py-2.5 rounded-xl bg-[#FAF8F6] border border-[#E3DBD5] text-sm text-[#1C1917] focus:outline-none focus:border-[#1C1917]"
                                />
                                <button
                                    type="button"
                                    @click="showPasswordConfirm = !showPasswordConfirm"
                                    :aria-label="showPasswordConfirm ? '{{ $txt['hide_pass'] }}' : '{{ $txt['show_pass'] }}'"
                                    class="absolute inset-y-0 right-0 pr-3 flex items-center text-stone-500 hover:text-[#1C1917] cursor-pointer"
                                >
                                    <i class="fa-regular text-xs" :class="showPasswordConfirm ? 'fa-eye-slash' : 'fa-eye'"></i>
                                </button>
                            </div>
                            @error('authPasswordConfirmation')
                                <p role="alert" class="mt-1 text-xs text-rose-600 font-medium">{{ $message }}</p>
                            @enderror
                        </div>

                        <button
                            type="submit"
                            wire:loading.attr="disabled"
                            class="w-full py-3.5 px-5 rounded-xl bg-[#1C1917] hover:bg-[#2C2724] disabled:opacity-60 text-[#FAF7F5] text-xs font-bold uppercase tracking-wider transition shadow-md mt-2 cursor-pointer"
                        >
                            {{ $txt['complete_reg_btn'] }}
                        </button>

                        <button
                            type="button"
                            wire:click="openRegister"
                            class="w-full py-2 text-xs text-[#78716C] hover:text-[#1C1917] font-medium transition flex items-center justify-center gap-1.5 cursor-pointer"
                        >
                            <i class="fa-solid fa-arrow-left text-[10px]"></i>
                            <span>{{ $txt['back_btn'] }}</span>
                        </button>
                    </form>
                </div>

            @elseif($step === 'reset-email')
                <!-- ВОССТАНОВЛЕНИЕ ПАРОЛЯ: ШАГ A — ВВОД EMAIL -->
                <div>
                    <h2 class="font-serif text-2xl font-bold text-[#1C1917] text-center mb-1.5">
                        {{ $txt['reset_email_title'] }}
                    </h2>
                    <p class="text-xs text-[#78716C] text-center mb-5 leading-relaxed">
                        {{ $txt['reset_email_sub'] }}
                    </p>

                    <form wire:submit="sendPasswordResetCode" class="space-y-4" novalidate>
                        <div>
                            <label for="mally-reset-email" class="block text-[11px] font-semibold uppercase tracking-wider text-[#78716C] mb-1.5">
                                {{ $txt['email_label'] }}
                            </label>
                            <input
                                id="mally-reset-email"
                                type="email"
                                wire:model="authEmail"
                                placeholder="{{ $txt['email_placeholder'] }}"
                                autocomplete="email"
                                class="w-full px-4 py-3.5 rounded-xl bg-[#FAF8F6] border border-[#E3DBD5] text-sm font-medium text-[#1C1917] focus:outline-none focus:border-[#1C1917] focus:bg-white transition"
                            />
                            @error('authEmail')
                                <p role="alert" class="mt-1.5 text-xs text-rose-600 font-medium">{{ $message }}</p>
                            @enderror
                        </div>

                        <button
                            type="submit"
                            wire:loading.attr="disabled"
                            class="w-full py-3.5 px-5 rounded-xl bg-[#1C1917] hover:bg-[#2C2724] disabled:opacity-60 text-[#FAF7F5] text-xs font-bold uppercase tracking-wider transition shadow-md cursor-pointer"
                        >
                            {{ $txt['send_reset_code_btn'] }}
                        </button>

                        <button
                            type="button"
                            wire:click="backToLogin"
                            class="w-full py-2.5 text-xs text-[#78716C] hover:text-[#1C1917] font-medium transition flex items-center justify-center gap-1.5 cursor-pointer"
                        >
                            <i class="fa-solid fa-arrow-left text-[10px]"></i>
                            <span>{{ $txt['back_btn'] }}</span>
                        </button>
                    </form>
                </div>

            @elseif($step === 'reset-code')
                <!-- ВОССТАНОВЛЕНИЕ ПАРОЛЯ: ШАГ B — ВВОД 6-ЗНАЧНОГО КОДА -->
                <div
                    x-data="{
                        seconds: @entangle('authCooldownSeconds'),
                        timer: null,
                        init() {
                            this.timer = setInterval(() => {
                                if (this.seconds > 0) {
                                    this.seconds--;
                                }
                            }, 1000);
                        }
                    }"
                >
                    <h2 class="font-serif text-2xl font-bold text-[#1C1917] text-center mb-1.5">
                        {{ $txt['reset_code_title'] }}
                    </h2>
                    <p class="text-xs text-[#78716C] text-center mb-1.5 leading-relaxed">
                        {{ $txt['reset_code_sub'] }}
                        <span class="font-semibold text-[#1C1917] break-all">{{ $this->authEmail }}</span>
                    </p>
                    <p class="text-[11px] text-[#9E8B89] text-center mb-5">
                        {{ $txt['code_ttl_hint'] }}
                    </p>

                    <form wire:submit="verifyPasswordResetCode" class="space-y-4" novalidate>
                        <div>
                            <input
                                type="text"
                                inputmode="numeric"
                                pattern="[0-9]*"
                                maxlength="6"
                                wire:model="authCode"
                                placeholder="{{ $txt['code_placeholder'] }}"
                                autocomplete="one-time-code"
                                class="w-full px-4 py-3.5 rounded-xl bg-[#FCECEC]/50 border border-[#E3DBD5] text-center text-2xl font-mono tracking-[0.45em] font-bold text-[#1C1917] focus:outline-none focus:border-[#1C1917] focus:bg-white transition"
                            />
                            @error('authCode')
                                <p role="alert" class="mt-1.5 text-xs text-rose-600 font-medium text-center">{{ $message }}</p>
                            @enderror
                        </div>

                        <button
                            type="submit"
                            wire:loading.attr="disabled"
                            class="w-full py-3.5 px-5 rounded-xl bg-[#1C1917] hover:bg-[#2C2724] disabled:opacity-60 text-[#FAF7F5] text-xs font-bold uppercase tracking-wider transition shadow-md cursor-pointer"
                        >
                            {{ $txt['verify_code_btn'] }}
                        </button>
                    </form>

                    <div class="mt-4 text-center space-y-2">
                        <template x-if="seconds > 0">
                            <p class="text-xs text-[#78716C]">
                                {{ $txt['resend_wait'] }} <span class="font-mono font-semibold text-[#1C1917]" x-text="seconds"></span> {{ $txt['resend_sec'] }}
                            </p>
                        </template>
                        <template x-if="seconds <= 0">
                            <button
                                type="button"
                                wire:click="resendPasswordResetCode"
                                wire:loading.attr="disabled"
                                class="text-xs font-semibold text-[#1C1917] hover:underline underline-offset-4 transition cursor-pointer"
                            >
                                {{ $txt['resend_btn'] }}
                            </button>
                        </template>

                        <div>
                            <button
                                type="button"
                                wire:click="openForgotPassword"
                                class="w-full py-2 text-xs text-[#78716C] hover:text-[#1C1917] font-medium transition flex items-center justify-center gap-1.5 cursor-pointer"
                            >
                                <i class="fa-solid fa-arrow-left text-[10px]"></i>
                                <span>{{ $txt['back_btn'] }}</span>
                            </button>
                        </div>
                    </div>
                </div>

            @elseif($step === 'reset-password')
                <!-- ВОССТАНОВЛЕНИЕ ПАРОЛЯ: ШАГ C — НОВЫЙ ПАРОЛЬ И ПОДТВЕРЖДЕНИЕ -->
                <div x-data="{ showPassword: false, showPasswordConfirm: false }">
                    <h2 class="font-serif text-2xl font-bold text-[#1C1917] text-center mb-1.5">
                        {{ $txt['reset_pass_title'] }}
                    </h2>
                    <p class="text-xs text-[#78716C] text-center mb-4 leading-relaxed">
                        {{ $txt['reset_pass_sub'] }}
                    </p>

                    <div class="mb-4 px-3.5 py-2.5 rounded-xl bg-[#FAF7F5] border border-[#EAE3DE] flex items-center gap-2">
                        <i class="fa-solid fa-key text-stone-600 text-xs flex-shrink-0"></i>
                        <span class="text-xs font-medium text-[#1C1917] truncate">{{ $verifiedResetEmail }}</span>
                    </div>

                    <form wire:submit="completePasswordReset" class="space-y-3.5" novalidate>
                        <div>
                            <label for="mally-reset-new-password" class="block text-[11px] font-semibold uppercase tracking-wider text-[#78716C] mb-1">
                                {{ $txt['reset_new_pass_label'] }}
                            </label>
                            <div class="relative">
                                <input
                                    id="mally-reset-new-password"
                                    :type="showPassword ? 'text' : 'password'"
                                    type="password"
                                    wire:model="authPassword"
                                    minlength="8"
                                    autocomplete="new-password"
                                    class="w-full pl-3.5 pr-10 py-2.5 rounded-xl bg-[#FAF8F6] border border-[#E3DBD5] text-sm text-[#1C1917] focus:outline-none focus:border-[#1C1917]"
                                />
                                <button
                                    type="button"
                                    @click="showPassword = !showPassword"
                                    :aria-label="showPassword ? '{{ $txt['hide_pass'] }}' : '{{ $txt['show_pass'] }}'"
                                    class="absolute inset-y-0 right-0 pr-3 flex items-center text-stone-500 hover:text-[#1C1917] cursor-pointer"
                                >
                                    <i class="fa-regular text-xs" :class="showPassword ? 'fa-eye-slash' : 'fa-eye'"></i>
                                </button>
                            </div>
                            @error('authPassword')
                                <p role="alert" class="mt-1 text-xs text-rose-600 font-medium">{{ $message }}</p>
                            @enderror
                        </div>

                        <div>
                            <label for="mally-reset-confirm-password" class="block text-[11px] font-semibold uppercase tracking-wider text-[#78716C] mb-1">
                                {{ $txt['confirm_pass_label'] }}
                            </label>
                            <div class="relative">
                                <input
                                    id="mally-reset-confirm-password"
                                    :type="showPasswordConfirm ? 'text' : 'password'"
                                    type="password"
                                    wire:model="authPasswordConfirmation"
                                    minlength="8"
                                    autocomplete="new-password"
                                    class="w-full pl-3.5 pr-10 py-2.5 rounded-xl bg-[#FAF8F6] border border-[#E3DBD5] text-sm text-[#1C1917] focus:outline-none focus:border-[#1C1917]"
                                />
                                <button
                                    type="button"
                                    @click="showPasswordConfirm = !showPasswordConfirm"
                                    :aria-label="showPasswordConfirm ? '{{ $txt['hide_pass'] }}' : '{{ $txt['show_pass'] }}'"
                                    class="absolute inset-y-0 right-0 pr-3 flex items-center text-stone-500 hover:text-[#1C1917] cursor-pointer"
                                >
                                    <i class="fa-regular text-xs" :class="showPasswordConfirm ? 'fa-eye-slash' : 'fa-eye'"></i>
                                </button>
                            </div>
                            @error('authPasswordConfirmation')
                                <p role="alert" class="mt-1 text-xs text-rose-600 font-medium">{{ $message }}</p>
                            @enderror
                        </div>

                        <button
                            type="submit"
                            wire:loading.attr="disabled"
                            class="w-full py-3.5 px-5 rounded-xl bg-[#1C1917] hover:bg-[#2C2724] disabled:opacity-60 text-[#FAF7F5] text-xs font-bold uppercase tracking-wider transition shadow-md mt-2 cursor-pointer"
                        >
                            {{ $txt['save_and_login_btn'] }}
                        </button>

                        <button
                            type="button"
                            wire:click="openForgotPassword"
                            class="w-full py-2 text-xs text-[#78716C] hover:text-[#1C1917] font-medium transition flex items-center justify-center gap-1.5 cursor-pointer"
                        >
                            <i class="fa-solid fa-arrow-left text-[10px]"></i>
                            <span>{{ $txt['back_btn'] }}</span>
                        </button>
                    </form>
                </div>

            @elseif($step === 'telegram-wait')
                <!-- TELEGRAM: ШАГ 1 — ОТКРЫТЬ БОТА И ПОДЕЛИТЬСЯ КОНТАКТОМ -->
                <div @if($this->authTelegramBotUrl) wire:poll.2s="checkTelegramAuthStatus" @endif class="space-y-4">
                    <h2 class="font-serif text-2xl font-bold text-[#1C1917] text-center">
                        {{ __('Sign in with Telegram') }}
                    </h2>

                    @error('authTelegram')
                        <p role="alert" class="p-3 rounded-xl bg-rose-50 border border-rose-200 text-xs text-rose-700 font-medium text-center">{{ $message }}</p>
                    @enderror

                    @if($this->authTelegramBotUrl)
                        <div class="p-4 rounded-2xl bg-[#F0F9FF] border border-[#BAE6FD] space-y-2.5 text-xs text-[#0C4A6E]">
                            <ol class="list-decimal list-inside space-y-1.5 text-[11px] leading-relaxed text-[#0369A1]">
                                <li>{{ __('Open the bot with the button below and press Start.') }}</li>
                                <li>{{ __('Share your contact with the "📱 Share contact" button.') }}</li>
                                <li>{{ __('The bot will send you a 6-digit code — enter it here.') }}</li>
                            </ol>
                        </div>

                        <a
                            href="{{ $this->authTelegramBotUrl }}"
                            target="_blank"
                            rel="noopener noreferrer"
                            class="w-full py-3.5 px-5 rounded-xl bg-[#0088CC] hover:bg-[#0077B5] text-white text-xs font-bold uppercase tracking-wider transition shadow-md flex items-center justify-center gap-2 cursor-pointer"
                        >
                            <i class="fa-brands fa-telegram text-base"></i>
                            <span>{{ __('Open Telegram bot') }}</span>
                            <i class="fa-solid fa-up-right-from-square text-[10px]"></i>
                        </a>

                        <div class="flex items-center justify-center gap-2 py-1 text-[11px] text-[#78716C]">
                            <i class="fa-solid fa-spinner fa-spin text-[#0088CC]"></i>
                            <span>{{ ($this->authTelegramStatus ?? 'pending') === 'contact_requested' ? __('Waiting for your contact in the bot…') : __('Waiting for the bot…') }}</span>
                        </div>
                    @else
                        <button
                            type="button"
                            wire:click="startTelegramAuth"
                            class="w-full py-3.5 px-5 rounded-xl bg-[#0088CC] hover:bg-[#0077B5] text-white text-xs font-bold uppercase tracking-wider transition shadow-md flex items-center justify-center gap-2 cursor-pointer"
                        >
                            <i class="fa-brands fa-telegram text-base"></i>
                            <span>{{ __('Start again') }}</span>
                        </button>
                    @endif

                    <button
                        type="button"
                        wire:click="backToLogin"
                        class="w-full py-2 text-xs text-[#78716C] hover:text-[#1C1917] font-medium transition flex items-center justify-center gap-1.5 cursor-pointer"
                    >
                        <i class="fa-solid fa-arrow-left text-[10px]"></i>
                        <span>{{ $txt['back_btn'] }}</span>
                    </button>
                </div>

            @elseif($step === 'telegram-code')
                <!-- TELEGRAM: ШАГ 2 — КОД ИЗ БОТА -->
                <div class="space-y-4">
                    <h2 class="font-serif text-2xl font-bold text-[#1C1917] text-center">
                        {{ __('Code from Telegram') }}
                    </h2>
                    <p class="text-xs text-[#78716C] text-center leading-relaxed">
                        {{ __('Enter the 6-digit code the bot has just sent you. Never share it with anyone.') }}
                    </p>

                    <form wire:submit="verifyTelegramCode" class="space-y-3" novalidate>
                        <input
                            type="text"
                            wire:model="authTelegramCode"
                            inputmode="numeric"
                            autocomplete="one-time-code"
                            maxlength="6"
                            placeholder="• • • • • •"
                            autofocus
                            class="w-full text-center tracking-[0.5em] text-2xl font-mono font-bold py-3 bg-[#FAF8F6] border border-[#E3DBD5] rounded-2xl focus:outline-none focus:border-[#1C1917]"
                        />
                        @error('authTelegramCode')
                            <p role="alert" class="text-xs text-rose-600 font-medium text-center">{{ $message }}</p>
                        @enderror

                        <button
                            type="submit"
                            wire:loading.attr="disabled"
                            class="w-full py-3.5 px-5 rounded-xl bg-[#1C1917] hover:bg-[#2C2724] disabled:opacity-60 text-white text-xs font-bold uppercase tracking-wider transition shadow-md cursor-pointer"
                        >
                            {{ __('Confirm') }}
                        </button>
                    </form>

                    <button
                        type="button"
                        wire:click="backToLogin"
                        class="w-full py-2 text-xs text-[#78716C] hover:text-[#1C1917] font-medium transition flex items-center justify-center gap-1.5 cursor-pointer"
                    >
                        <i class="fa-solid fa-arrow-left text-[10px]"></i>
                        <span>{{ $txt['back_btn'] }}</span>
                    </button>
                </div>

            @elseif($step === 'telegram-register')
                <!-- ЗАВЕРШЕНИЕ РЕГИСТРАЦИИ ЧЕРЕЗ TELEGRAM (ТЕЛЕФОН УЖЕ ПОДТВЕРЖДЁН, ПАРОЛЬ ОБЯЗАТЕЛЕН, GMAIL НЕ ОБЯЗАТЕЛЕН) -->
                <div x-data="{ showPassword: false, showPasswordConfirm: false }">
                    <h2 class="font-serif text-2xl font-bold text-[#1C1917] text-center mb-1.5">
                        Регистрация через Telegram
                    </h2>
                    <p class="text-xs text-[#78716C] text-center mb-4 leading-relaxed">
                        Ваш номер подтверждён через Telegram. Задайте пароль, чтобы входить и по номеру телефона.
                    </p>

                    <div class="mb-4 px-3.5 py-2.5 rounded-xl bg-emerald-50 border border-emerald-200 flex items-center justify-between gap-2">
                        <div class="flex items-center gap-2 min-w-0">
                            <i class="fa-brands fa-telegram text-[#0088CC] text-base flex-shrink-0"></i>
                            <div class="min-w-0">
                                <span class="block text-[10px] uppercase tracking-wider text-emerald-700 font-bold">Подтверждённый номер</span>
                                <span class="text-xs font-mono font-bold text-[#1C1917]">{{ $this->authPhone }}</span>
                            </div>
                        </div>
                        <i class="fa-solid fa-circle-check text-emerald-600 text-sm flex-shrink-0"></i>
                    </div>

                    <form wire:submit="completeTelegramRegistration" class="space-y-3.5" novalidate>
                        <!-- Имя (обязательно) -->
                        <div>
                            <label for="mally-tg-reg-name" class="block text-[11px] font-semibold uppercase tracking-wider text-[#78716C] mb-1">
                                {{ $txt['name_label'] }}
                            </label>
                            <input
                                id="mally-tg-reg-name"
                                type="text"
                                wire:model="authName"
                                placeholder="{{ $txt['name_placeholder'] }}"
                                autocomplete="name"
                                class="w-full px-3.5 py-2.5 rounded-xl bg-[#FAF8F6] border border-[#E3DBD5] text-sm text-[#1C1917] focus:outline-none focus:border-[#1C1917]"
                            />
                            @error('authName')
                                <p role="alert" class="mt-1 text-xs text-rose-600 font-medium">{{ $message }}</p>
                            @enderror
                        </div>

                        <!-- Пароль (ОБЯЗАТЕЛЬНО) -->
                        <div>
                            <label for="mally-tg-reg-password" class="block text-[11px] font-semibold uppercase tracking-wider text-[#78716C] mb-1">
                                {{ $txt['new_pass_label'] }}
                            </label>
                            <div class="relative">
                                <input
                                    id="mally-tg-reg-password"
                                    :type="showPassword ? 'text' : 'password'"
                                    type="password"
                                    wire:model="authPassword"
                                    minlength="8"
                                    placeholder="Минимум 8 символов"
                                    autocomplete="new-password"
                                    class="w-full pl-3.5 pr-10 py-2.5 rounded-xl bg-[#FAF8F6] border border-[#E3DBD5] text-sm text-[#1C1917] focus:outline-none focus:border-[#1C1917]"
                                />
                                <button
                                    type="button"
                                    @click="showPassword = !showPassword"
                                    :aria-label="showPassword ? '{{ $txt['hide_pass'] }}' : '{{ $txt['show_pass'] }}'"
                                    class="absolute inset-y-0 right-0 pr-3 flex items-center text-stone-500 hover:text-[#1C1917] cursor-pointer"
                                >
                                    <i class="fa-regular text-xs" :class="showPassword ? 'fa-eye-slash' : 'fa-eye'"></i>
                                </button>
                            </div>
                            @error('authPassword')
                                <p role="alert" class="mt-1 text-xs text-rose-600 font-medium">{{ $message }}</p>
                            @enderror
                        </div>

                        <!-- Подтверждение пароля (ОБЯЗАТЕЛЬНО) -->
                        <div>
                            <label for="mally-tg-reg-password-confirm" class="block text-[11px] font-semibold uppercase tracking-wider text-[#78716C] mb-1">
                                {{ $txt['confirm_pass_label'] }}
                            </label>
                            <div class="relative">
                                <input
                                    id="mally-tg-reg-password-confirm"
                                    :type="showPasswordConfirm ? 'text' : 'password'"
                                    type="password"
                                    wire:model="authPasswordConfirmation"
                                    minlength="8"
                                    placeholder="Повторите пароль"
                                    autocomplete="new-password"
                                    class="w-full pl-3.5 pr-10 py-2.5 rounded-xl bg-[#FAF8F6] border border-[#E3DBD5] text-sm text-[#1C1917] focus:outline-none focus:border-[#1C1917]"
                                />
                                <button
                                    type="button"
                                    @click="showPasswordConfirm = !showPasswordConfirm"
                                    :aria-label="showPasswordConfirm ? '{{ $txt['hide_pass'] }}' : '{{ $txt['show_pass'] }}'"
                                    class="absolute inset-y-0 right-0 pr-3 flex items-center text-stone-500 hover:text-[#1C1917] cursor-pointer"
                                >
                                    <i class="fa-regular text-xs" :class="showPasswordConfirm ? 'fa-eye-slash' : 'fa-eye'"></i>
                                </button>
                            </div>
                            @error('authPasswordConfirmation')
                                <p role="alert" class="mt-1 text-xs text-rose-600 font-medium">{{ $message }}</p>
                            @enderror
                        </div>

                        <button
                            type="submit"
                            wire:loading.attr="disabled"
                            class="w-full py-3.5 px-5 rounded-xl bg-[#1C1917] hover:bg-[#2C2724] disabled:opacity-60 text-[#FAF7F5] text-xs font-bold uppercase tracking-wider transition shadow-md mt-2 cursor-pointer"
                        >
                            {{ $txt['complete_reg_btn'] }}
                        </button>

                        <button
                            type="button"
                            wire:click="backToLogin"
                            class="w-full py-2 text-xs text-[#78716C] hover:text-[#1C1917] font-medium transition flex items-center justify-center gap-1.5 cursor-pointer"
                        >
                            <i class="fa-solid fa-arrow-left text-[10px]"></i>
                            <span>{{ $txt['back_btn'] }}</span>
                        </button>
                    </form>
                </div>
            @endif
        </div>
    </div>
@endif
