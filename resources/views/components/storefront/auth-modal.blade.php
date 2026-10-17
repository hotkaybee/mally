@props([
    'store' => null,
    'isOpen' => null,
    'step' => null,
    'phone' => null,
    'message' => null,
])

@php
    $store = $store ?? app(\App\Services\StorefrontService::class);
    $locale = $store->getLocale();
    $isOpen = $isOpen ?? ($this->isAuthModalOpen ?? false);
    $step = $step ?? ($this->authStep ?? 'phone');
    $phone = $phone ?? ($this->authPhone ?? '+998 ');
    $message = $message ?? ($this->authModalMessage ?? '');

    $texts = match ($locale) {
        'uz' => [
            'title_auth' => "Kirish va Ro'yxatdan o'tish",
            'title_complete' => "Ma'lumotlarni to'ldirish",
            'phone_label' => 'Telefon raqami',
            'phone_hint' => "Parolsiz: kirish uchun kodni tasdiqlash kifoya (+998 va 9 ta raqam)",
            'get_code' => 'Kirish kodini olish',
            'or' => 'yoki',
            'tg_login' => 'Telegram-bot orqali kirish',
            'code_sent_to' => 'Kod ushbu raqamga yuborildi:',
            'enter_code' => 'Tasdiqlash kodini kiriting',
            'sign_in_btn' => 'Akkauntga kirish',
            'change_phone' => "Raqamni o'zgartirish",
            'resend_code' => 'Kodni qayta yuborish',
            'phone_verified' => 'Raqam tasdiqlandi:',
            'first_name' => 'Ism',
            'last_name' => 'Familiya',
            'city' => 'Shahar',
            'city_placeholder' => 'Toshkent',
            'address' => 'Yetkazib berish manzili',
            'address_placeholder' => "Ko'cha, uy, xonadon",
            'finish_reg' => "Ro'yxatdan o'tishni yakunlash",
            'tg_desc' => 'Raqamingizni SMSsiz Telegram-botimiz orqali bir bosishda tasdiqlang.',
            'open_tg_bot' => 'Telegram-botni ochish',
            'tg_step1' => 'Botga kiring va <b>Start</b> tugmasini bosing',
            'tg_step2' => '<b>«📱 Kontaktni ulashish»</b> tugmasini bosing',
            'tg_step3' => 'Sayt avtomatik ravishda tizimga kiradi!',
            'tg_waiting' => 'Botda tasdiqlash kutilmoqda...',
            'back_to_phone' => '← Telefon raqami orqali kirishga qaytish',
        ],
        'en' => [
            'title_auth' => 'Sign In & Register',
            'title_complete' => 'Complete Your Profile',
            'phone_label' => 'Phone Number',
            'phone_hint' => 'Passwordless login: just verify your code (+998 and 9 digits)',
            'get_code' => 'Get Verification Code',
            'or' => 'or',
            'tg_login' => 'Sign in via Telegram Bot',
            'code_sent_to' => 'Verification code sent to:',
            'enter_code' => 'Enter verification code',
            'sign_in_btn' => 'Sign In',
            'change_phone' => 'Change phone number',
            'resend_code' => 'Resend code',
            'phone_verified' => 'Phone number verified:',
            'first_name' => 'First Name',
            'last_name' => 'Last Name',
            'city' => 'City',
            'city_placeholder' => 'Tashkent',
            'address' => 'Delivery Address',
            'address_placeholder' => 'Street, building, apartment',
            'finish_reg' => 'Complete Registration',
            'tg_desc' => 'Verify your phone number in one click via our official Telegram bot without SMS.',
            'open_tg_bot' => 'Open Telegram Bot',
            'tg_step1' => 'Open the bot and press <b>Start</b>',
            'tg_step2' => 'Tap <b>"📱 Share Contact"</b>',
            'tg_step3' => 'The website will sign you in automatically!',
            'tg_waiting' => 'Waiting for confirmation in Telegram...',
            'back_to_phone' => '← Back to phone number login',
        ],
        default => [
            'title_auth' => 'Вход и Регистрация',
            'title_complete' => 'Завершение профиля',
            'phone_label' => 'Номер телефона',
            'phone_hint' => 'Без паролей: введите номер в формате +998 и 9 цифр',
            'get_code' => 'Получить код для входа',
            'or' => 'или',
            'tg_login' => 'Войти через Telegram-бота',
            'code_sent_to' => 'Код отправлен на номер:',
            'enter_code' => 'Введите проверочный код',
            'sign_in_btn' => 'Войти в аккаунт',
            'change_phone' => 'Изменить номер',
            'resend_code' => 'Отправить код повторно',
            'phone_verified' => 'Номер подтвержден:',
            'first_name' => 'Имя',
            'last_name' => 'Фамилия',
            'city' => 'Город',
            'city_placeholder' => 'Ташкент',
            'address' => 'Адрес доставки',
            'address_placeholder' => 'Улица, дом, квартира',
            'finish_reg' => 'Завершить регистрацию',
            'tg_desc' => 'Подтвердите ваш номер в один клик через нашего Telegram-бота без SMS.',
            'open_tg_bot' => 'Открыть Telegram-бота',
            'tg_step1' => 'Перейдите в бота и нажмите <b>Start</b>',
            'tg_step2' => 'Нажмите кнопку <b>«📱 Поделиться контактом»</b>',
            'tg_step3' => 'Сайт автоматически завершит вход!',
            'tg_waiting' => 'Ожидаем подтверждения в боте...',
            'back_to_phone' => '← Вернуться ко входу по номеру телефона',
        ],
    };
@endphp

@if($isOpen)
    <div
        wire:click="closeAuthModal"
        class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/40 backdrop-blur-sm animate-fade-in"
    >
        <div
            wire:click.stop
            class="relative w-full max-w-md bg-white rounded-3xl border border-[#EAE3DE] shadow-2xl overflow-hidden p-6 sm:p-8"
        >
            <!-- Кнопка закрытия -->
            <button
                wire:click="closeAuthModal"
                type="button"
                class="absolute top-5 right-5 w-8 h-8 rounded-full bg-[#FAF7F5] hover:bg-[#EAE3DE] text-[#78716C] hover:text-[#1C1917] flex items-center justify-center text-sm transition cursor-pointer"
                aria-label="Close"
            >
                <i class="fa-solid fa-xmark"></i>
            </button>

            <!-- Заголовок -->
            <div class="text-center mb-6">
                <span class="text-[10px] tracking-[0.25em] uppercase font-bold text-[#A8A29E] block mb-1">
                    Mally Store Uzbekistan
                </span>
                <h2 class="font-serif text-2xl font-bold text-[#1C1917]">
                    {{ $step === 'complete-profile' ? $texts['title_complete'] : $texts['title_auth'] }}
                </h2>
                @if($message && ($step === 'phone' || $step === 'telegram'))
                    <div class="mt-2.5 text-xs text-[#57534E] bg-[#FAF7F5] border border-[#EAE3DE] rounded-xl py-2 px-3">
                        {{ $message }}
                    </div>
                @endif
            </div>

            <!-- ШАГ 1: ВВОД НОМЕРА ТЕЛЕФОНА -->
            @if($step === 'phone')
                <form wire:submit.prevent="sendAuthCode" class="space-y-4">
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
                        <label class="block text-[11px] font-semibold uppercase tracking-wider text-[#78716C] mb-1.5">
                            {{ $texts['phone_label'] }}
                        </label>
                        <div class="relative">
                            <input
                                type="tel"
                                required
                                autofocus
                                maxlength="17"
                                placeholder="+998 99 822 28 80"
                                wire:model="authPhone"
                                @input="formatUzPhone($event)"
                                @focus="if (!$event.target.value || !$event.target.value.startsWith('+998')) { $event.target.value = '+998 '; $wire.set('authPhone', '+998 ', false); }"
                                class="w-full px-4 py-3 text-base font-mono font-medium bg-[#FAF8F6] border border-[#E3DBD5] rounded-2xl focus:outline-none focus:border-[#1C1917] focus:bg-white transition"
                            />
                            <span class="absolute right-3.5 top-1/2 -translate-y-1/2 text-stone-400">
                                <i class="fa-solid fa-phone text-xs"></i>
                            </span>
                        </div>
                        <p class="text-[11px] text-[#A8A29E] mt-1.5">
                            {{ $texts['phone_hint'] }}
                        </p>
                    </div>

                    <button
                        type="submit"
                        class="w-full py-3.5 px-4 bg-[#1C1917] hover:bg-[#2C2724] active:scale-[0.98] text-[#FAF7F5] rounded-2xl text-xs font-bold tracking-wider uppercase transition shadow-lg hover:shadow-xl flex items-center justify-center gap-2 cursor-pointer"
                    >
                        <span>{{ $texts['get_code'] }}</span>
                        <i class="fa-solid fa-arrow-right text-xs"></i>
                    </button>

                    <!-- Разделитель -->
                    <div class="relative my-4">
                        <div class="absolute inset-0 flex items-center">
                            <div class="w-full border-t border-[#EAE3DE]"></div>
                        </div>
                        <div class="relative flex justify-center text-[10px] uppercase tracking-wider">
                            <span class="bg-white px-2 text-[#A8A29E]">{{ $texts['or'] }}</span>
                        </div>
                    </div>

                    <!-- Кнопка входа через Telegram-бота -->
                    <button
                        type="button"
                        wire:click="setAuthStep('telegram')"
                        class="w-full py-3 px-4 bg-[#EBF6FC] hover:bg-[#DDF0FA] active:scale-[0.98] text-[#24A1DE] border border-[#BCE1F5] rounded-2xl text-xs font-bold tracking-wide transition flex items-center justify-center gap-2.5 cursor-pointer"
                    >
                        <i class="fa-brands fa-telegram text-base"></i>
                        <span>{{ $texts['tg_login'] }}</span>
                    </button>
                </form>
            @endif

            <!-- ШАГ 2: ВВОД КОДА ПОДТВЕРЖДЕНИЯ -->
            @if($step === 'code')
                <form wire:submit.prevent="verifyAuthCode" class="space-y-4">
                    <div class="text-center">
                        <span class="text-xs text-[#78716C]">{{ $texts['code_sent_to'] }}</span>
                        <div class="font-mono font-bold text-sm text-[#1C1917] mt-0.5">
                            {{ $phone }}
                        </div>
                    </div>

                    <!-- Поле ввода проверочного кода -->
                    <div>
                        <label class="block text-[11px] font-semibold uppercase tracking-wider text-[#78716C] mb-1.5 text-center">
                            {{ $texts['enter_code'] }}
                        </label>
                        <input
                            type="text"
                            required
                            autofocus
                            maxlength="6"
                            placeholder="• • • • • •"
                            inputmode="numeric"
                            autocomplete="one-time-code"
                            wire:model="authCode"
                            class="w-full text-center tracking-[0.5em] text-2xl font-mono font-bold py-3 bg-[#FAF8F6] border border-[#E3DBD5] rounded-2xl focus:outline-none focus:border-[#1C1917] focus:bg-white transition"
                        />
                    </div>

                    <button
                        type="submit"
                        class="w-full py-3.5 px-4 bg-[#1C1917] hover:bg-[#2C2724] active:scale-[0.98] text-[#FAF7F5] rounded-2xl text-xs font-bold tracking-wider uppercase transition shadow-lg hover:shadow-xl flex items-center justify-center gap-2 cursor-pointer"
                    >
                        <span>{{ $texts['sign_in_btn'] }}</span>
                        <i class="fa-solid fa-arrow-right text-xs"></i>
                    </button>

                    <div class="flex justify-between items-center text-xs text-[#78716C] pt-1">
                        <button
                            type="button"
                            wire:click="setAuthStep('phone')"
                            class="hover:text-[#1C1917] underline transition cursor-pointer"
                        >
                            {{ $texts['change_phone'] }}
                        </button>
                        <button
                            type="button"
                            wire:click="sendAuthCode"
                            class="hover:text-[#1C1917] underline transition cursor-pointer"
                        >
                            {{ $texts['resend_code'] }}
                        </button>
                    </div>
                </form>
            @endif

            <!-- ШАГ 3: ЗАВЕРШЕНИЕ РЕГИСТРАЦИИ НОВОГО ПОКУПАТЕЛЯ (без username) -->
            @if($step === 'complete-profile')
                <form wire:submit.prevent="completeAuthRegister" class="space-y-3.5">
                    <div class="bg-emerald-50 border border-emerald-200 text-emerald-800 p-3 rounded-2xl text-center">
                        <div class="flex items-center justify-center gap-2 text-xs font-semibold">
                            <i class="fa-solid fa-circle-check text-emerald-600"></i>
                            <span>{{ $texts['phone_verified'] }}</span>
                        </div>
                        <p class="font-mono font-bold text-sm text-emerald-900 mt-0.5">
                            {{ $phone }}
                        </p>
                    </div>

                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label class="block text-[11px] font-semibold uppercase tracking-wider text-[#78716C] mb-1">
                                {{ $texts['first_name'] }} <span class="text-rose-500">*</span>
                            </label>
                            <input
                                type="text"
                                required
                                placeholder="{{ $texts['first_name'] }}"
                                wire:model="authFirstName"
                                class="w-full px-3.5 py-2.5 text-sm bg-[#FAF8F6] border border-[#E3DBD5] rounded-xl focus:outline-none focus:border-[#1C1917] focus:bg-white transition"
                            />
                        </div>

                        <div>
                            <label class="block text-[11px] font-semibold uppercase tracking-wider text-[#78716C] mb-1">
                                {{ $texts['last_name'] }} <span class="text-rose-500">*</span>
                            </label>
                            <input
                                type="text"
                                required
                                placeholder="{{ $texts['last_name'] }}"
                                wire:model="authLastName"
                                class="w-full px-3.5 py-2.5 text-sm bg-[#FAF8F6] border border-[#E3DBD5] rounded-xl focus:outline-none focus:border-[#1C1917] focus:bg-white transition"
                            />
                        </div>
                    </div>

                    <div>
                        <label class="block text-[11px] font-semibold uppercase tracking-wider text-[#78716C] mb-1">
                            {{ $texts['address'] }} <span class="text-rose-500">*</span>
                        </label>
                        <input
                            type="text"
                            required
                            placeholder="{{ $texts['address_placeholder'] }}"
                            wire:model="authAddress"
                            class="w-full px-3.5 py-2.5 text-sm bg-[#FAF8F6] border border-[#E3DBD5] rounded-xl focus:outline-none focus:border-[#1C1917] focus:bg-white transition"
                        />
                    </div>

                    <button
                        type="submit"
                        class="w-full mt-2 py-3.5 px-4 bg-[#1C1917] hover:bg-[#2C2724] text-[#FAF7F5] rounded-xl text-xs font-bold tracking-wider uppercase transition shadow-lg hover:shadow-xl flex items-center justify-center gap-2 cursor-pointer"
                    >
                        <span>{{ $texts['finish_reg'] }}</span>
                        <i class="fa-solid fa-arrow-right text-xs"></i>
                    </button>
                </form>
            @endif

            <!-- ШАГ 4: ВХОД ЧЕРЕЗ TELEGRAM-БОТА -->
            @if($step === 'telegram')
                <div class="space-y-4">
                    <div class="text-center">
                        <div class="w-16 h-16 mx-auto mb-3 bg-[#EBF6FC] border border-[#BCE1F5] rounded-2xl flex items-center justify-center text-[#24A1DE] text-3xl shadow-inner">
                            <i class="fa-brands fa-telegram"></i>
                        </div>
                        <p class="text-xs text-[#57534E] leading-relaxed">
                            {{ $texts['tg_desc'] }}
                        </p>
                    </div>

                    <a
                        href="https://t.me/mallyuz"
                        target="_blank"
                        rel="noreferrer"
                        class="w-full py-3.5 px-4 bg-[#24A1DE] hover:bg-[#1E8EC6] active:scale-[0.98] text-white rounded-2xl text-xs font-bold tracking-wide uppercase transition shadow-md hover:shadow-lg flex items-center justify-center gap-2.5 cursor-pointer"
                    >
                        <i class="fa-solid fa-paper-plane text-sm"></i>
                        <span>{{ $texts['open_tg_bot'] }}</span>
                    </a>

                    <div class="bg-[#FAF7F5] border border-[#EAE3DE] rounded-2xl p-4 text-xs space-y-2">
                        <div class="flex items-start gap-2.5">
                            <span class="w-5 h-5 rounded-full bg-[#1C1917] text-[#FAF7F5] flex items-center justify-center text-[10px] font-bold shrink-0 mt-0.5">1</span>
                            <span class="text-[#57534E]">{!! $texts['tg_step1'] !!}</span>
                        </div>
                        <div class="flex items-start gap-2.5">
                            <span class="w-5 h-5 rounded-full bg-[#1C1917] text-[#FAF7F5] flex items-center justify-center text-[10px] font-bold shrink-0 mt-0.5">2</span>
                            <span class="text-[#57534E]">{!! $texts['tg_step2'] !!}</span>
                        </div>
                        <div class="flex items-start gap-2.5">
                            <span class="w-5 h-5 rounded-full bg-[#1C1917] text-[#FAF7F5] flex items-center justify-center text-[10px] font-bold shrink-0 mt-0.5">3</span>
                            <span class="text-[#57534E]">{!! $texts['tg_step3'] !!}</span>
                        </div>
                    </div>

                    <div class="flex items-center justify-center gap-2.5 py-2 text-xs text-[#78716C]">
                        <span class="relative flex h-2.5 w-2.5">
                            <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-sky-400 opacity-75"></span>
                            <span class="relative inline-flex rounded-full h-2.5 w-2.5 bg-sky-500"></span>
                        </span>
                        <span>{{ $texts['tg_waiting'] }}</span>
                    </div>

                    <div class="text-center pt-1">
                        <button
                            type="button"
                            wire:click="setAuthStep('phone')"
                            class="text-xs text-[#78716C] hover:text-[#1C1917] underline transition cursor-pointer"
                        >
                            {{ $texts['back_to_phone'] }}
                        </button>
                    </div>
                </div>
            @endif
        </div>
    </div>
@endif
