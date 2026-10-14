@php
    $cht = match ($locale) {
        'uz' => [
            'back_to_cart' => 'Savatga qaytish',
            'customer_default' => 'Xaridor',
            'my_profile' => 'Mening profilim',
            'need_login_title' => 'Buyurtma berish faqat ro‘yxatdan o‘tgan mijozlar uchun mavjud',
            'need_login_desc' => 'Iltimos, telefon raqamingiz orqali tizimga kiring.',
            'login_btn' => "Kirish yoki ro'yxatdan o'tish",
            'recipient_title' => 'Qabul qiluvchi va yetkazib berish',
            'first_name' => 'Ism',
            'last_name' => 'Familiya',
            'phone_label' => "Yetkazib berish va Telegram orqali bog'lanish uchun telefon raqami (+998)",
            'address_label' => 'Yetkazib berish manzili',
            'address_placeholder' => "Yetkazib berish manzili (ko'cha, uy, mo'ljal)",
            'comment_label' => 'Buyurtmaga izoh',
            'comment_placeholder' => 'Yetkazib berish istaklari, domofon kodi...',
            'payment_title' => "To'lov usuli",
            'card_transfer' => "Kartaga to'g'ridan-to'g'ri o'tkazma (UZCARD / HUMO)",
            'main_method' => 'Asosiy usul',
            'card_note' => "Karta rekvizitlari va to'lov summasi buyurtma tasdiqlangandan so'ng ko'rsatiladi.",
            'confirm_btn' => 'Buyurtmani tasdiqlash',
            'sending_btn' => 'Yuborilmoqda...',
            'promo_placeholder' => "Promokod yoki sovg'a kartasi",
            'promo_apply' => "Qo'llash",
            'subtotal' => 'Oraliq jami',
            'discount' => 'Promokod chegirmasi',
            'shipping' => 'Yetkazib berish',
            'shipping_value' => "Yandex Go tarifi bo'yicha (yetkazilganda)",
            'total' => "Jami to'lov:",
        ],
        'en' => [
            'back_to_cart' => 'Back to Cart',
            'customer_default' => 'Customer',
            'my_profile' => 'My Profile',
            'need_login_title' => 'Checkout is available for registered customers',
            'need_login_desc' => 'Please sign in with your phone number to complete your order.',
            'login_btn' => 'Sign In or Register',
            'recipient_title' => 'Recipient & Delivery',
            'first_name' => 'First Name',
            'last_name' => 'Last Name',
            'phone_label' => 'Phone number for delivery & Telegram (+998)',
            'address_label' => 'Delivery Address',
            'address_placeholder' => 'Delivery address (street, building, landmark)',
            'comment_label' => 'Order Comment',
            'comment_placeholder' => 'Delivery notes, intercom code, preferred time...',
            'payment_title' => 'Payment Method',
            'card_transfer' => 'Direct Card Transfer (UZCARD / HUMO)',
            'main_method' => 'Primary Method',
            'card_note' => 'Card details and exact payment amount will be displayed right after confirming your order.',
            'confirm_btn' => 'Confirm Order',
            'sending_btn' => 'Submitting...',
            'promo_placeholder' => 'Promo code or gift card',
            'promo_apply' => 'Apply',
            'subtotal' => 'Subtotal',
            'discount' => 'Promo Discount',
            'shipping' => 'Shipping',
            'shipping_value' => 'At Yandex Go tariff (paid upon delivery)',
            'total' => 'Total Due:',
        ],
        default => [
            'back_to_cart' => 'Вернуться в корзину',
            'customer_default' => 'Покупатель',
            'my_profile' => 'Мой профиль',
            'need_login_title' => 'Оформление доступно только для зарегистрированных клиентов',
            'need_login_desc' => 'Пожалуйста, войдите в аккаунт по номеру телефона.',
            'login_btn' => 'Войти или зарегистрироваться',
            'recipient_title' => 'Получатель и доставка',
            'first_name' => 'Имя',
            'last_name' => 'Фамилия',
            'phone_label' => 'Номер телефона (+998)',
            'address_label' => 'Адрес доставки',
            'address_placeholder' => 'Адрес доставки (улица, дом, ориентир)',
            'comment_label' => 'Комментарий к заказу',
            'comment_placeholder' => 'Пожелания по доставке, код домофона, время вручения...',
            'payment_title' => 'Способ оплаты',
            'card_transfer' => 'Прямой перевод на карту (UZCARD / HUMO)',
            'main_method' => 'Единственный способ',
            'card_note' => 'Реквизиты карты и точная сумма к оплате будут показаны сразу после подтверждения заказа.',
            'confirm_btn' => 'Подтвердить заказ',
            'sending_btn' => 'Отправка заявки...',
            'promo_placeholder' => 'Промокод или подарочная карта',
            'promo_apply' => 'Применить',
            'subtotal' => 'Промежуточный итог',
            'discount' => 'Скидка по промокоду',
            'shipping' => 'Доставка',
            'shipping_value' => 'По тарифу Яндекс Go (при получении)',
            'total' => 'Всего к оплате:',
        ],
    };
@endphp

<div class="min-h-screen bg-[#FAF7F5] text-[#2C2A29] font-sans flex flex-col">
    <!-- Шапка Checkout -->
    <header class="border-b border-[#EAE3DE] py-4 px-6 bg-white/90 backdrop-blur-md sticky top-0 z-30 shadow-2xs">
        <div class="max-w-6xl mx-auto flex justify-between items-center">
            <a href="{{ route('home') }}" wire:navigate class="flex flex-col">
                <span class="font-serif text-2xl font-bold tracking-tight text-[#1C1917]">mally</span>
                <span class="text-[9px] uppercase tracking-[0.25em] text-[#8C827A]">Beauty without limits</span>
            </a>
            <a href="{{ route('cart') }}" wire:navigate class="text-xs font-semibold text-[#78716C] hover:text-black flex items-center gap-1.5 transition">
                <i class="fa-solid fa-arrow-left text-xs"></i>
                <span>{{ $cht['back_to_cart'] }}</span>
            </a>
        </div>
    </header>

    <main class="max-w-6xl mx-auto w-full flex-1 grid grid-cols-1 lg:grid-cols-12 py-4 sm:py-8">
        <!-- Левая колонка: Форма данных -->
        <div class="lg:col-span-7 py-8 px-4 sm:px-8 border-r border-[#EAE3DE]">
            <!-- Статус авторизации -->
            @if($currentCustomer)
                @php
                    $custName = trim(($currentCustomer->first_name ?? '').' '.($currentCustomer->last_name ?? '')) ?: $cht['customer_default'];
                    $custLetter = mb_strtoupper(mb_substr($custName, 0, 1)) ?: 'M';
                @endphp
                <div class="bg-emerald-50 border border-emerald-200/80 p-4 rounded-2xl flex items-center justify-between gap-3 mb-6">
                    <div class="flex items-center gap-3">
                        <div class="w-9 h-9 rounded-xl bg-emerald-700 text-white flex items-center justify-center text-xs font-serif font-bold shadow-sm">
                            {{ $custLetter }}
                        </div>
                        <div>
                            <div class="flex items-center gap-2 flex-wrap">
                                <span class="font-semibold text-xs text-[#1C1917] block">
                                    {{ $custName }}
                                </span>
                            </div>
                            <span class="text-[11px] text-[#78716C] font-mono">{{ $currentCustomer->phone_number }}</span>
                        </div>
                    </div>
                    <a href="{{ route('profile') }}" wire:navigate class="text-xs font-semibold text-emerald-800 hover:underline">
                        {{ $cht['my_profile'] }} →
                    </a>
                </div>
            @else
                <div class="bg-amber-50/80 border border-amber-200/80 p-5 rounded-2xl mb-6">
                    <div class="flex items-start gap-3">
                        <span class="text-2xl">🔒</span>
                        <div class="flex-1">
                            <h3 class="font-serif font-bold text-sm text-[#1C1917] mb-1">
                                {{ $cht['need_login_title'] }}
                            </h3>
                            <p class="text-[11px] text-[#78716C] mb-3 leading-relaxed">
                                {{ $cht['need_login_desc'] }}
                            </p>
                            <button
                                type="button"
                                wire:click="openAuthModal('{{ $cht['need_login_title'] }}')"
                                class="py-2.5 px-5 bg-[#1C1917] hover:bg-[#2C2724] text-white rounded-xl text-xs font-bold uppercase tracking-wider transition shadow-md cursor-pointer"
                            >
                                {{ $cht['login_btn'] }} →
                            </button>
                        </div>
                    </div>
                </div>
            @endif

            <form wire:submit.prevent="placeOrder" class="space-y-6 text-xs text-left max-w-xl">
                <!-- Доставка и получатель: только Имя, Фамилия, Телефон (+998), Адрес доставки и Комментарий -->
                <div class="space-y-3 pt-2">
                    <h2 class="text-base font-semibold text-[#1C1917]">
                        {{ $cht['recipient_title'] }}
                    </h2>

                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label class="text-[11px] text-[#78716C] mb-1 block">
                                {{ $cht['first_name'] }} *
                            </label>
                            <input
                                type="text"
                                required
                                placeholder="{{ $cht['first_name'] }}"
                                wire:model="firstName"
                                class="w-full p-3 bg-[#FAF8F6] border border-[#E3DBD5] rounded-xl focus:outline-none focus:border-[#1C1917]"
                            />
                        </div>
                        <div>
                            <label class="text-[11px] text-[#78716C] mb-1 block">
                                {{ $cht['last_name'] }}
                            </label>
                            <input
                                type="text"
                                placeholder="{{ $cht['last_name'] }}"
                                wire:model="lastName"
                                class="w-full p-3 bg-[#FAF8F6] border border-[#E3DBD5] rounded-xl focus:outline-none focus:border-[#1C1917]"
                            />
                        </div>
                    </div>

                    <!-- Номер телефона из профиля (заблокирован для изменения) -->
                    <div>
                        <div class="flex items-center justify-between mb-1">
                            <label class="text-[11px] text-[#78716C] block">
                                {{ $cht['phone_label'] }} *
                            </label>
                            <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full bg-emerald-50 text-emerald-700 border border-emerald-200 text-[10px] font-semibold">
                                <i class="fa-solid fa-lock text-[9px]"></i>
                                <span>{{ $locale === 'uz' ? 'Profildan' : ($locale === 'en' ? 'From profile' : 'Из профиля') }}</span>
                            </span>
                        </div>
                        <input
                            type="tel"
                            disabled
                            readonly
                            value="{{ $currentCustomer?->phone_number ?: $phoneBilling }}"
                            class="w-full p-3 bg-emerald-50/40 border border-emerald-200/70 rounded-xl font-mono font-bold text-sm text-emerald-950 cursor-not-allowed select-none"
                        />
                    </div>

                    <div>
                        <label class="text-[11px] text-[#78716C] mb-1 block">
                            {{ $cht['address_label'] }} *
                        </label>
                        <input
                            type="text"
                            required
                            placeholder="{{ $cht['address_placeholder'] }}"
                            wire:model="address"
                            class="w-full p-3 bg-[#FAF8F6] border border-[#E3DBD5] rounded-xl focus:outline-none focus:border-[#1C1917]"
                        />
                    </div>

                    <!-- Комментарий к заказу -->
                    <div>
                        <label class="text-[11px] text-[#78716C] mb-1 block">
                            {{ $cht['comment_label'] }}
                        </label>
                        <textarea
                            rows="2"
                            placeholder="{{ $cht['comment_placeholder'] }}"
                            wire:model="comment"
                            class="w-full p-3 bg-[#FAF8F6] border border-[#E3DBD5] rounded-xl focus:outline-none focus:border-[#1C1917] text-xs"
                        ></textarea>
                    </div>
                </div>

                <!-- Способ оплаты -->
                <div class="pt-4 border-t border-[#EAE3DE] space-y-3">
                    <h2 class="text-base font-semibold text-[#1C1917]">
                        {{ $cht['payment_title'] }}
                    </h2>
                    <div class="p-4 rounded-2xl border-2 border-[#1C1917] bg-[#FAF8F6] shadow-sm flex items-center justify-between gap-3">
                        <div class="flex items-center gap-3.5">
                            <div class="w-10 h-10 rounded-xl bg-amber-100 text-amber-800 flex items-center justify-center text-lg flex-shrink-0">
                                <i class="fa-solid fa-credit-card"></i>
                            </div>
                            <div>
                                <div class="flex items-center gap-2 flex-wrap">
                                    <span class="font-bold text-xs text-[#1C1917]">
                                        {{ $cht['card_transfer'] }}
                                    </span>
                                    <span class="text-[10px] bg-emerald-100 text-emerald-800 px-2 py-0.5 rounded-full font-bold">
                                        {{ $cht['main_method'] }}
                                    </span>
                                </div>
                                <p class="text-[11px] text-[#78716C] mt-0.5">
                                    {{ $cht['card_note'] }}
                                </p>
                            </div>
                        </div>
                        <i class="fa-solid fa-circle-check text-emerald-600 text-lg flex-shrink-0"></i>
                    </div>
                </div>

                <button
                    type="submit"
                    wire:loading.attr="disabled"
                    class="w-full py-4 bg-[#0F2027] hover:bg-black text-white text-xs font-semibold uppercase tracking-widest rounded-xl transition shadow-md disabled:opacity-50 cursor-pointer"
                >
                    <span wire:loading.remove wire:target="placeOrder">{{ $cht['confirm_btn'] }}</span>
                    <span wire:loading wire:target="placeOrder">{{ $cht['sending_btn'] }}</span>
                </button>
            </form>
        </div>

        <!-- Правая колонка: Состав заказа -->
        <div class="lg:col-span-5 bg-[#FAF8F6] py-8 px-4 sm:px-8 space-y-6">
            <div class="space-y-3 divide-y divide-[#EAE3DE]">
                @foreach($cartItems as $item)
                    @php $p = $item['product']; @endphp
                    <div class="pt-3 first:pt-0 flex items-center justify-between gap-3">
                        <div class="relative w-14 h-14 rounded-xl bg-white border border-[#EAE3DE] overflow-hidden flex-shrink-0">
                            <img src="{{ $p['image'] }}" alt="" class="w-full h-full object-cover" />
                            <span class="absolute top-0 right-0 bg-[#57534E] text-white text-[10px] w-4 h-4 rounded-full flex items-center justify-center font-bold">
                                {{ $item['quantity'] }}
                            </span>
                        </div>

                        <div class="flex-1 min-w-0 pr-2">
                            <span class="text-xs font-medium text-[#1C1917] block line-clamp-2">
                                {{ $p['title'] }}
                            </span>
                            <span class="text-[10px] uppercase text-[#8C827A] font-semibold">
                                {{ $p['brand'] }}
                            </span>
                        </div>

                        <span class="font-medium text-xs whitespace-nowrap">
                            {{ $this->formatPrice($item['line_total_usd']) }}
                        </span>
                    </div>
                @endforeach
            </div>

            <!-- Промокод -->
            <div class="flex gap-2">
                <input
                    type="text"
                    placeholder="{{ $cht['promo_placeholder'] }}"
                    wire:model="promoCode"
                    class="flex-1 p-3 bg-white border border-[#E3DBD5] rounded-xl text-xs focus:outline-none uppercase"
                />
                <button
                    type="button"
                    wire:click="applyPromo"
                    class="px-4 py-3 bg-[#EAE3DE] hover:bg-[#D5CCC7] text-xs font-semibold rounded-xl transition cursor-pointer"
                >
                    {{ $cht['promo_apply'] }}
                </button>
            </div>

            <!-- Суммы -->
            <div class="space-y-2 text-xs pt-3 border-t border-[#EAE3DE]">
                <div class="flex justify-between">
                    <span class="text-[#78716C]">{{ $cht['subtotal'] }}</span>
                    <span class="font-medium">{{ $this->formatPrice($subtotalUsd) }}</span>
                </div>
                @if($discountUsd > 0)
                    <div class="flex justify-between text-emerald-700">
                        <span>{{ $cht['discount'] }} ({{ $appliedPromo }})</span>
                        <span>-{{ $this->formatPrice($discountUsd) }}</span>
                    </div>
                @endif
                <div class="flex justify-between items-center text-xs">
                    <span class="text-[#78716C]">{{ $cht['shipping'] }}</span>
                    <span class="font-medium text-[#1C1917] text-right text-[11px]">
                        {{ $cht['shipping_value'] }}
                    </span>
                </div>
                <div class="flex justify-between items-baseline text-base font-bold pt-3 border-t border-[#EAE3DE]">
                    <span>{{ $cht['total'] }}</span>
                    <div class="text-right">
                        <span class="font-serif block text-lg">{{ $this->formatPrice($finalTotalUsd) }}</span>
                        <span class="text-xs font-mono text-[#78716C] block font-normal mt-0.5">
                            ≈ ${{ number_format($finalTotalUsd, 2, '.', '') }} USD
                        </span>
                    </div>
                </div>
            </div>
        </div>
    </main>

    <x-storefront.auth-modal />
</div>
