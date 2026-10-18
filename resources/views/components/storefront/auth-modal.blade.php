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
                        {{ __('Sign in to your account') }}
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
                                {{ __('Email or phone') }}
                            </label>
                            <div class="relative">
                                <span class="absolute inset-y-0 left-0 pl-4 flex items-center text-stone-400 pointer-events-none">
                                    <i class="fa-regular fa-user text-xs"></i>
                                </span>
                                <input
                                    id="mally-login-email"
                                    type="text"
                                    wire:model="authEmail"
                                    placeholder="{{ __('name@example.com or +998 90 123 45 67') }}"
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
                                        <span>{{ __('Sign up with this email') }}</span>
                                        <i class="fa-solid fa-arrow-right text-[10px]"></i>
                                    </button>
                                </div>
                            @endif
                        </div>

                        <!-- Поле Пароль -->
                        <div>
                            <label for="mally-login-password" class="block text-[11px] font-semibold uppercase tracking-wider text-[#78716C] mb-1.5">
                                {{ __('Password') }}
                            </label>
                            <div class="relative">
                                <input
                                    id="mally-login-password"
                                    :type="showPassword ? 'text' : 'password'"
                                    type="password"
                                    wire:model="authPassword"
                                    placeholder="{{ __('Enter your password') }}"
                                    autocomplete="current-password"
                                    class="w-full pl-4 pr-11 py-3.5 rounded-xl bg-[#FAF8F6] border border-[#E3DBD5] text-sm font-medium text-[#1C1917] focus:outline-none focus:border-[#1C1917] focus:bg-white transition"
                                />
                                <button
                                    type="button"
                                    @click="showPassword = !showPassword"
                                    :aria-label="showPassword ? '{{ __('Hide password') }}' : '{{ __('Show password') }}'"
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
                                    {{ __('Forgot password?') }}
                                </button>
                            </div>
                        </div>

                        <!-- Кнопка «Войти» -->
                        <button
                            type="submit"
                            wire:loading.attr="disabled"
                            class="w-full py-3.5 px-5 rounded-xl bg-[#1C1917] hover:bg-[#2C2724] disabled:opacity-60 text-[#FAF7F5] text-xs font-bold uppercase tracking-wider transition shadow-md flex items-center justify-center gap-2 cursor-pointer"
                        >
                            <span>{{ __('Sign in') }}</span>
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
                            <span>{{ __('Sign up') }}</span>
                        </button>

                        <button
                            type="button"
                            wire:click="startTelegramAuth"
                            wire:loading.attr="disabled"
                            class="w-full py-3.5 px-5 rounded-xl bg-[#0088CC] hover:bg-[#0077B5] disabled:opacity-60 text-white text-xs font-bold uppercase tracking-wider transition shadow-sm flex items-center justify-center gap-2 cursor-pointer"
                        >
                            <i class="fa-brands fa-telegram text-base"></i>
                            <span>{{ __('Continue with Telegram') }}</span>
                        </button>
                    </div>
                </div>

            @elseif($step === 'register-email')
                <!-- РЕГИСТРАЦИЯ: ШАГ 1 — ВВОД EMAIL -->
                <div>
                    <h2 class="font-serif text-2xl font-bold text-[#1C1917] text-center mb-1.5">
                        {{ __('Sign up') }}
                    </h2>
                    <p class="text-xs text-[#78716C] text-center mb-5 leading-relaxed">
                        {{ __('Enter your email to receive a 6-digit confirmation code.') }}
                    </p>

                    <form wire:submit="sendRegisterCode" class="space-y-4" novalidate>
                        <div>
                            <label for="mally-register-email" class="block text-[11px] font-semibold uppercase tracking-wider text-[#78716C] mb-1.5">
                                {{ __('Email') }}
                            </label>
                            <div class="relative">
                                <span class="absolute inset-y-0 left-0 pl-4 flex items-center text-stone-400 pointer-events-none">
                                    <i class="fa-regular fa-envelope text-xs"></i>
                                </span>
                                <input
                                    id="mally-register-email"
                                    type="email"
                                    wire:model="authEmail"
                                    placeholder="name@example.com"
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
                            <span>{{ __('Get code') }}</span>
                        </button>

                        <button
                            type="button"
                            wire:click="startTelegramAuth"
                            wire:loading.attr="disabled"
                            class="w-full py-3 px-4 rounded-xl bg-[#0088CC]/10 hover:bg-[#0088CC]/15 border border-[#0088CC]/30 text-[#0077B5] text-xs font-bold transition flex items-center justify-center gap-2 cursor-pointer"
                        >
                            <i class="fa-brands fa-telegram text-base"></i>
                            <span>{{ __('No email? Continue with Telegram') }}</span>
                        </button>

                        <button
                            type="button"
                            wire:click="backToLogin"
                            class="w-full py-2.5 text-xs text-[#78716C] hover:text-[#1C1917] font-medium transition flex items-center justify-center gap-1.5 cursor-pointer"
                        >
                            <i class="fa-solid fa-arrow-left text-[10px]"></i>
                            <span>{{ __('Back') }}</span>
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
                        {{ __('Email confirmation') }}
                    </h2>
                    <p class="text-xs text-[#78716C] text-center mb-1.5 leading-relaxed">
                        {{ __('We sent a 6-digit confirmation code to:') }}
                        <span class="font-semibold text-[#1C1917] break-all">{{ $this->authEmail }}</span>
                    </p>
                    <p class="text-[11px] text-[#9E8B89] text-center mb-5">
                        {{ __('The code is valid for 10 minutes') }}
                    </p>

                    <form wire:submit="verifyRegisterCode" class="space-y-4" novalidate>
                        <div>
                            <input
                                type="text"
                                inputmode="numeric"
                                pattern="[0-9]*"
                                maxlength="6"
                                wire:model="authCode"
                                placeholder="000000"
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
                            {{ __('Confirm') }}
                        </button>
                    </form>

                    <div class="mt-4 text-center space-y-2">
                        <template x-if="seconds > 0">
                            <p class="text-xs text-[#78716C]">
                                {{ __('Resend in') }} <span class="font-mono font-semibold text-[#1C1917]" x-text="seconds"></span> {{ __('sec.') }}
                            </p>
                        </template>
                        <template x-if="seconds <= 0">
                            <button
                                type="button"
                                wire:click="resendRegisterCode"
                                wire:loading.attr="disabled"
                                class="text-xs font-semibold text-[#1C1917] hover:underline underline-offset-4 transition cursor-pointer"
                            >
                                {{ __('Resend') }}
                            </button>
                        </template>

                        <div>
                            <button
                                type="button"
                                wire:click="setAuthStep('register-email')"
                                class="w-full py-2 text-xs text-[#78716C] hover:text-[#1C1917] font-medium transition flex items-center justify-center gap-1.5 cursor-pointer"
                            >
                                <i class="fa-solid fa-arrow-left text-[10px]"></i>
                                <span>{{ __('Back') }}</span>
                            </button>
                        </div>
                    </div>
                </div>

            @elseif($step === 'register-profile')
                <!-- РЕГИСТРАЦИЯ: ШАГ 3 — ИМЯ, ТЕЛЕФОН С МАСКОЙ +998, ПАРОЛЬ И ПОДТВЕРЖДЕНИЕ -->
                <div x-data="{ showPassword: false, showPasswordConfirm: false }">
                    <h2 class="font-serif text-2xl font-bold text-[#1C1917] text-center mb-1.5">
                        {{ __('Complete sign-up') }}
                    </h2>
                    <p class="text-xs text-[#78716C] text-center mb-4 leading-relaxed">
                        {{ __('Fill in your contact details and set a password') }}
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
                                {{ __('Name *') }}
                            </label>
                            <input
                                id="mally-reg-name"
                                type="text"
                                wire:model="authName"
                                placeholder="{{ __('For example, Malika') }}"
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
                                {{ __('Phone *') }}
                            </label>
                            <input
                                id="mally-reg-phone"
                                type="tel"
                                maxlength="17"
                                wire:model="authPhone"
                                placeholder="+998 90 123 45 67"
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
                                {{ __('Password (at least 8 characters) *') }}
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
                                    :aria-label="showPassword ? '{{ __('Hide password') }}' : '{{ __('Show password') }}'"
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
                                {{ __('Confirm password *') }}
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
                                    :aria-label="showPasswordConfirm ? '{{ __('Hide password') }}' : '{{ __('Show password') }}'"
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
                            {{ __('Sign up') }}
                        </button>

                        <button
                            type="button"
                            wire:click="openRegister"
                            class="w-full py-2 text-xs text-[#78716C] hover:text-[#1C1917] font-medium transition flex items-center justify-center gap-1.5 cursor-pointer"
                        >
                            <i class="fa-solid fa-arrow-left text-[10px]"></i>
                            <span>{{ __('Back') }}</span>
                        </button>
                    </form>
                </div>

            @elseif($step === 'reset-email')
                <!-- ВОССТАНОВЛЕНИЕ ПАРОЛЯ: ШАГ A — ВВОД EMAIL -->
                <div>
                    <h2 class="font-serif text-2xl font-bold text-[#1C1917] text-center mb-1.5">
                        {{ __('Password reset') }}
                    </h2>
                    <p class="text-xs text-[#78716C] text-center mb-5 leading-relaxed">
                        {{ __('Enter your email and we will send a 6-digit code to reset your password.') }}
                    </p>

                    <form wire:submit="sendPasswordResetCode" class="space-y-4" novalidate>
                        <div>
                            <label for="mally-reset-email" class="block text-[11px] font-semibold uppercase tracking-wider text-[#78716C] mb-1.5">
                                {{ __('Email') }}
                            </label>
                            <input
                                id="mally-reset-email"
                                type="email"
                                wire:model="authEmail"
                                placeholder="name@example.com"
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
                            {{ __('Send code') }}
                        </button>

                        @error('authTelegram')
                            <p role="alert" class="p-3 rounded-xl bg-rose-50 border border-rose-200 text-xs text-rose-700 font-medium text-center">{{ $message }}</p>
                        @enderror

                        <!-- Сброс через Telegram — для аккаунтов, зарегистрированных через бота -->
                        <button
                            type="button"
                            wire:click="startTelegramReset"
                            wire:loading.attr="disabled"
                            class="w-full py-3 px-5 rounded-xl bg-[#0088CC] hover:bg-[#0077B5] disabled:opacity-60 text-white text-xs font-bold uppercase tracking-wider transition flex items-center justify-center gap-2 cursor-pointer"
                        >
                            <i class="fa-brands fa-telegram text-base"></i>
                            <span>{{ __('Reset via Telegram') }}</span>
                        </button>

                        <button
                            type="button"
                            wire:click="backToLogin"
                            class="w-full py-2.5 text-xs text-[#78716C] hover:text-[#1C1917] font-medium transition flex items-center justify-center gap-1.5 cursor-pointer"
                        >
                            <i class="fa-solid fa-arrow-left text-[10px]"></i>
                            <span>{{ __('Back') }}</span>
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
                        {{ __('Reset code') }}
                    </h2>
                    <p class="text-xs text-[#78716C] text-center mb-1.5 leading-relaxed">
                        {{ __('Enter the 6-digit code sent to:') }}
                        <span class="font-semibold text-[#1C1917] break-all">{{ $this->authEmail }}</span>
                    </p>
                    <p class="text-[11px] text-[#9E8B89] text-center mb-5">
                        {{ __('The code is valid for 10 minutes') }}
                    </p>

                    <form wire:submit="verifyPasswordResetCode" class="space-y-4" novalidate>
                        <div>
                            <input
                                type="text"
                                inputmode="numeric"
                                pattern="[0-9]*"
                                maxlength="6"
                                wire:model="authCode"
                                placeholder="000000"
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
                            {{ __('Confirm') }}
                        </button>
                    </form>

                    <div class="mt-4 text-center space-y-2">
                        <template x-if="seconds > 0">
                            <p class="text-xs text-[#78716C]">
                                {{ __('Resend in') }} <span class="font-mono font-semibold text-[#1C1917]" x-text="seconds"></span> {{ __('sec.') }}
                            </p>
                        </template>
                        <template x-if="seconds <= 0">
                            <button
                                type="button"
                                wire:click="resendPasswordResetCode"
                                wire:loading.attr="disabled"
                                class="text-xs font-semibold text-[#1C1917] hover:underline underline-offset-4 transition cursor-pointer"
                            >
                                {{ __('Resend') }}
                            </button>
                        </template>

                        <div>
                            <button
                                type="button"
                                wire:click="openForgotPassword"
                                class="w-full py-2 text-xs text-[#78716C] hover:text-[#1C1917] font-medium transition flex items-center justify-center gap-1.5 cursor-pointer"
                            >
                                <i class="fa-solid fa-arrow-left text-[10px]"></i>
                                <span>{{ __('Back') }}</span>
                            </button>
                        </div>
                    </div>
                </div>

            @elseif($step === 'reset-password')
                <!-- ВОССТАНОВЛЕНИЕ ПАРОЛЯ: ШАГ C — НОВЫЙ ПАРОЛЬ И ПОДТВЕРЖДЕНИЕ -->
                <div x-data="{ showPassword: false, showPasswordConfirm: false }">
                    <h2 class="font-serif text-2xl font-bold text-[#1C1917] text-center mb-1.5">
                        {{ __('New password') }}
                    </h2>
                    <p class="text-xs text-[#78716C] text-center mb-4 leading-relaxed">
                        {{ __('Set a new password of at least 8 characters.') }}
                    </p>

                    <div class="mb-4 px-3.5 py-2.5 rounded-xl bg-[#FAF7F5] border border-[#EAE3DE] flex items-center gap-2">
                        <i class="fa-solid fa-key text-stone-600 text-xs flex-shrink-0"></i>
                        <span class="text-xs font-medium text-[#1C1917] truncate">{{ $verifiedResetEmail }}</span>
                    </div>

                    <form wire:submit="completePasswordReset" class="space-y-3.5" novalidate>
                        <div>
                            <label for="mally-reset-new-password" class="block text-[11px] font-semibold uppercase tracking-wider text-[#78716C] mb-1">
                                {{ __('New password (at least 8 characters) *') }}
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
                                    :aria-label="showPassword ? '{{ __('Hide password') }}' : '{{ __('Show password') }}'"
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
                                {{ __('Confirm password *') }}
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
                                    :aria-label="showPasswordConfirm ? '{{ __('Hide password') }}' : '{{ __('Show password') }}'"
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
                            {{ __('Save and sign in') }}
                        </button>

                        <button
                            type="button"
                            wire:click="openForgotPassword"
                            class="w-full py-2 text-xs text-[#78716C] hover:text-[#1C1917] font-medium transition flex items-center justify-center gap-1.5 cursor-pointer"
                        >
                            <i class="fa-solid fa-arrow-left text-[10px]"></i>
                            <span>{{ __('Back') }}</span>
                        </button>
                    </form>
                </div>

            @elseif($step === 'telegram-wait')
                <!-- TELEGRAM: ШАГ 1 — ОТКРЫТЬ БОТА И ПОДЕЛИТЬСЯ КОНТАКТОМ -->
                <div @if($this->authTelegramBotUrl) wire:poll.2s.keep-alive="checkTelegramAuthStatus" @endif class="space-y-4">
                    <h2 class="font-serif text-2xl font-bold text-[#1C1917] text-center">
                        {{ ($this->authTelegramPurpose ?? 'login') === 'reset' ? __('Password reset via Telegram') : __('Sign in with Telegram') }}
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
                            <span>{{ ($this->authTelegramStatus ?? 'pending') === 'contact_requested' ? __('Waiting for your contact in the bot…') : __('Please wait, the bot will reply in a few seconds…') }}</span>
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
                        <span>{{ __('Back') }}</span>
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
                        <span>{{ __('Back') }}</span>
                    </button>
                </div>

            @elseif($step === 'telegram-register')
                <!-- ЗАВЕРШЕНИЕ РЕГИСТРАЦИИ ЧЕРЕЗ TELEGRAM: номер уже подтверждён ботом; пароль — для входа по номеру телефона -->
                <div x-data="{ showPassword: false, showPasswordConfirm: false }">
                    <h2 class="font-serif text-2xl font-bold text-[#1C1917] text-center mb-1.5">
                        {{ __('Sign up with Telegram') }}
                    </h2>
                    <p class="text-xs text-[#78716C] text-center mb-4 leading-relaxed">
                        {{ __('Your number is confirmed by Telegram. Choose a password: next time sign in with your phone and password or through the bot again.') }}
                    </p>

                    <div class="mb-4 px-3.5 py-2.5 rounded-xl bg-emerald-50 border border-emerald-200 flex items-center justify-between gap-2">
                        <div class="flex items-center gap-2 min-w-0">
                            <i class="fa-brands fa-telegram text-[#0088CC] text-base flex-shrink-0"></i>
                            <div class="min-w-0">
                                <span class="block text-[10px] uppercase tracking-wider text-emerald-700 font-bold">{{ __('Confirmed number') }}</span>
                                <span class="text-xs font-mono font-bold text-[#1C1917]">{{ $this->authPhone }}</span>
                            </div>
                        </div>
                        <i class="fa-solid fa-circle-check text-emerald-600 text-sm flex-shrink-0"></i>
                    </div>

                    <form wire:submit="completeTelegramRegistration" class="space-y-3.5" novalidate>
                        <!-- Имя (обязательно) -->
                        <div>
                            <label for="mally-tg-reg-name" class="block text-[11px] font-semibold uppercase tracking-wider text-[#78716C] mb-1">
                                {{ __('Name *') }}
                            </label>
                            <input
                                id="mally-tg-reg-name"
                                type="text"
                                wire:model="authName"
                                placeholder="{{ __('For example, Malika') }}"
                                autocomplete="name"
                                class="w-full px-3.5 py-2.5 rounded-xl bg-[#FAF8F6] border border-[#E3DBD5] text-sm text-[#1C1917] focus:outline-none focus:border-[#1C1917]"
                            />
                            @error('authName')
                                <p role="alert" class="mt-1 text-xs text-rose-600 font-medium">{{ $message }}</p>
                            @enderror
                        </div>

                        <!-- Пароль -->
                        <div>
                            <label for="mally-tg-reg-password" class="block text-[11px] font-semibold uppercase tracking-wider text-[#78716C] mb-1">
                                {{ __('Password (at least 8 characters) *') }}
                            </label>
                            <div class="relative">
                                <input
                                    id="mally-tg-reg-password"
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
                                    :aria-label="showPassword ? '{{ __('Hide password') }}' : '{{ __('Show password') }}'"
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
                            <label for="mally-tg-reg-password-confirm" class="block text-[11px] font-semibold uppercase tracking-wider text-[#78716C] mb-1">
                                {{ __('Confirm password *') }}
                            </label>
                            <div class="relative">
                                <input
                                    id="mally-tg-reg-password-confirm"
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
                                    :aria-label="showPasswordConfirm ? '{{ __('Hide password') }}' : '{{ __('Show password') }}'"
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
                            {{ __('Sign up') }}
                        </button>

                        <button
                            type="button"
                            wire:click="backToLogin"
                            class="w-full py-2 text-xs text-[#78716C] hover:text-[#1C1917] font-medium transition flex items-center justify-center gap-1.5 cursor-pointer"
                        >
                            <i class="fa-solid fa-arrow-left text-[10px]"></i>
                            <span>{{ __('Back') }}</span>
                        </button>
                    </form>
                </div>
            @endif
        </div>
    </div>
@endif
