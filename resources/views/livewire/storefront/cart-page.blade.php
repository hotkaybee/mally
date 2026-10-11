@php
    $ct = match ($locale) {
        'uz' => [
            'home' => 'Bosh sahifa',
            'title' => 'Savatingiz',
            'empty_title' => "Savatingiz hozircha bo'sh",
            'empty_desc' => "Savatingizda hozircha mahsulotlar yo'q. AQSHdan original kosmetika katalogiga o'ting.",
            'to_catalog' => "Xarid qilishga o'tish",
            'remove_title' => "Mahsulotni o'chirish",
            'note_title' => 'Buyurtmaga izoh',
            'note_placeholder' => "Yetkazib berish bo'yicha maxsus istaklar...",
            'shipping_title' => 'Taxminiy yetkazib berish narxi',
            'shipping_tashkent' => "• <b>Toshkent bo'ylab:</b> <b>Yandex Go</b> orqali tezkor jo'natish (yetkazib berish xaridor tomonidan tarif bo'yicha alohida to'lanadi)",
            'shipping_regions' => "• <b>O'zbekiston viloyatlari:</b> BTS kuryerlik xizmati / pochta orqali tashuvchi tarifi bo'yicha",
            'subtotal' => 'Taxminiy umumiy summa',
            'tax_shipping_note' => 'Yetkazib berish tafsilotlari buyurtmani rasmiylashtirishda kelishiladi.',
            'checkout_btn' => 'Buyurtma berish',
        ],
        'en' => [
            'home' => 'Home',
            'title' => 'Your Cart',
            'empty_title' => 'Your cart is currently empty',
            'empty_desc' => 'There are no items in your cart yet. Explore our catalog of 100% authentic cosmetics from the USA.',
            'to_catalog' => 'Start Shopping',
            'remove_title' => 'Remove item',
            'note_title' => 'Order Note',
            'note_placeholder' => 'Special delivery requests or instructions...',
            'shipping_title' => 'Estimated Shipping Information',
            'shipping_tashkent' => '• <b>Tashkent:</b> fast dispatch via <b>Yandex Go</b> (delivery fee paid by the customer upon receipt at courier tariff)',
            'shipping_regions' => '• <b>Regions of Uzbekistan:</b> shipped via BTS Express / postal service at carrier rates',
            'subtotal' => 'Estimated Subtotal',
            'tax_shipping_note' => 'Delivery details are finalized during checkout.',
            'checkout_btn' => 'Proceed to Checkout',
        ],
        default => [
            'home' => 'Главная',
            'title' => 'Ваша корзина',
            'empty_title' => 'Ваша корзина пуста',
            'empty_desc' => 'В вашей корзине пока нет товаров. Перейдите в каталог оригинальной косметики из США.',
            'to_catalog' => 'Перейти к покупкам',
            'remove_title' => 'Удалить товар',
            'note_title' => 'Примечание к заказу',
            'note_placeholder' => 'Особые пожелания к доставке...',
            'shipping_title' => 'Ориентировочная стоимость доставки',
            'shipping_tashkent' => '• <b>По Ташкенту:</b> быстрая отправка через <b>Яндекс Go</b> (доставка оплачивается покупателем отдельно по тарифу при получении)',
            'shipping_regions' => '• <b>Регионы Узбекистана:</b> отправка через курьерскую службу BTS / почту по тарифу перевозчика',
            'subtotal' => 'Расчетная общая сумма',
            'tax_shipping_note' => 'Детали доставки уточняются при оформлении заказа.',
            'checkout_btn' => 'Оформить заказ',
        ],
    };
@endphp

<div class="min-h-screen bg-[#FCECEC] text-[#2C2A29] flex flex-col font-sans">
    <x-storefront.header />

    <!-- Хлебные крошки -->
    <div class="max-w-6xl mx-auto px-4 py-4 w-full text-xs text-[#8C827A] flex items-center gap-2">
        <a href="{{ route('home') }}" wire:navigate class="hover:text-black">
            {{ $ct['home'] }}
        </a>
        <span>/</span>
        <span class="text-[#1C1917]">{{ $ct['title'] }}</span>
    </div>

    <main class="max-w-6xl mx-auto px-4 py-6 w-full flex-1">
        <h1 class="font-serif text-3xl font-bold text-[#1C1917] mb-8">
            {{ $ct['title'] }} ({{ $totalCount }})
        </h1>

        @if(count($cartItems) === 0)
            <div class="bg-white rounded-3xl p-16 text-center border border-[#EAE3DE] space-y-4 shadow-sm">
                <div class="w-16 h-16 mx-auto rounded-full bg-[#FAF7F5] flex items-center justify-center text-[#A8A29E] text-2xl">
                    <i class="fa-solid fa-bag-shopping"></i>
                </div>
                <h2 class="font-serif text-xl font-bold">{{ $ct['empty_title'] }}</h2>
                <p class="text-xs text-[#8C827A] max-w-sm mx-auto">
                    {{ $ct['empty_desc'] }}
                </p>
                <a
                    href="{{ route('catalog') }}"
                    wire:navigate
                    class="inline-flex items-center gap-2 px-8 py-3.5 bg-[#1C1917] hover:bg-black text-white text-xs font-semibold rounded-full uppercase tracking-widest transition"
                >
                    <span>{{ $ct['to_catalog'] }}</span>
                    <i class="fa-solid fa-arrow-right text-[10px]"></i>
                </a>
            </div>
        @else
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-10">
                <!-- Левая колонка: Список товаров -->
                <div class="lg:col-span-2 space-y-4 divide-y divide-[#EAE3DE]">
                    @foreach($cartItems as $item)
                        @php
                            $p = $item['product'];
                            $prodKey = (string) ($p['sku'] ?? $p['id']);
                        @endphp
                        <div wire:key="cart-item-{{ $prodKey }}" class="pt-4 first:pt-0 flex items-center justify-between gap-4">
                            <!-- Кнопка удаления -->
                            <button
                                type="button"
                                wire:click="removeFromCart('{{ addslashes($prodKey) }}')"
                                class="text-[#8C827A] hover:text-rose-600 transition p-2 rounded-lg hover:bg-rose-50 cursor-pointer"
                                title="{{ $ct['remove_title'] }}"
                            >
                                <i class="fa-regular fa-trash-can text-sm"></i>
                            </button>

                            <!-- Фото -->
                            <a href="/products/{{ rawurlencode($prodKey) }}" wire:navigate class="w-20 h-20 rounded-2xl bg-[#FAF7F5] overflow-hidden border border-[#EAE3DE] flex-shrink-0 block">
                                <img src="{{ $p['image'] }}" alt="{{ $p['title'] }}" class="w-full h-full object-cover" />
                            </a>

                            <!-- Описание -->
                            <div class="flex-1 min-w-0">
                                <span class="text-[10px] uppercase tracking-wider text-[#8C827A] font-semibold block">
                                    {{ $p['brand'] }}
                                </span>
                                <a href="/products/{{ rawurlencode($prodKey) }}" wire:navigate class="text-xs sm:text-sm font-semibold text-[#1C1917] truncate block hover:underline">
                                    {{ $p['title'] }}
                                </a>
                                <div class="text-xs text-[#78716C] mt-1 font-mono">
                                    {{ $this->formatPrice($p['price']) }}
                                </div>
                            </div>

                            <!-- Счетчик [- 1 +] -->
                            <div class="inline-flex items-center border border-[#E3DBD5] rounded-xl bg-white px-2 py-1 shadow-sm">
                                <button
                                    type="button"
                                    wire:click="updateCartQuantity('{{ addslashes($prodKey) }}', {{ $item['quantity'] - 1 }})"
                                    class="w-7 h-7 flex items-center justify-center text-sm font-bold text-[#57534E] hover:text-black transition cursor-pointer"
                                >
                                    −
                                </button>
                                <span class="w-8 text-center text-xs font-mono font-bold">
                                    {{ $item['quantity'] }}
                                </span>
                                <button
                                    type="button"
                                    wire:click="updateCartQuantity('{{ addslashes($prodKey) }}', {{ $item['quantity'] + 1 }})"
                                    class="w-7 h-7 flex items-center justify-center text-sm font-bold text-[#57534E] hover:text-black transition cursor-pointer"
                                >
                                    +
                                </button>
                            </div>

                            <!-- Итог по позиции -->
                            <div class="text-right font-serif font-bold text-sm text-[#1C1917] whitespace-nowrap min-w-[100px]">
                                {{ $this->formatPrice($item['line_total_usd']) }}
                            </div>
                        </div>
                    @endforeach
                </div>

                <!-- Правая колонка: Итог и оформление -->
                <div class="space-y-4" x-data="{ isNoteOpen: false, isShippingOpen: false }">
                    <!-- Примечание к заказу -->
                    <div class="bg-white rounded-2xl border border-[#EAE3DE] overflow-hidden shadow-sm">
                        <button
                            type="button"
                            @click="isNoteOpen = !isNoteOpen"
                            class="w-full p-4 flex justify-between items-center text-xs font-semibold text-[#1C1917] hover:bg-[#FAF8F6] cursor-pointer"
                        >
                            <span>{{ $ct['note_title'] }}</span>
                            <i class="fa-solid text-[10px] text-[#8C827A]" :class="isNoteOpen ? 'fa-chevron-up' : 'fa-chevron-down'"></i>
                        </button>
                        <div x-show="isNoteOpen" x-cloak class="p-4 pt-0 border-t border-[#F2ECE8]">
                            <textarea
                                rows="3"
                                placeholder="{{ $ct['note_placeholder'] }}"
                                wire:model="orderNote"
                                class="w-full p-2.5 text-xs bg-[#FAF8F6] border border-[#E3DBD5] rounded-xl focus:outline-none mt-3"
                            ></textarea>
                        </div>
                    </div>

                    <!-- Стоимость доставки -->
                    <div class="bg-white rounded-2xl border border-[#EAE3DE] overflow-hidden shadow-sm">
                        <button
                            type="button"
                            @click="isShippingOpen = !isShippingOpen"
                            class="w-full p-4 flex justify-between items-center text-xs font-semibold text-[#1C1917] hover:bg-[#FAF8F6] cursor-pointer"
                        >
                            <span>{{ $ct['shipping_title'] }}</span>
                            <i class="fa-solid text-[10px] text-[#8C827A]" :class="isShippingOpen ? 'fa-chevron-up' : 'fa-chevron-down'"></i>
                        </button>
                        <div x-show="isShippingOpen" x-cloak class="p-4 pt-3 border-t border-[#F2ECE8] text-xs text-[#57534E] space-y-1.5">
                            <p>{!! $ct['shipping_tashkent'] !!}</p>
                            <p>{!! $ct['shipping_regions'] !!}</p>
                        </div>
                    </div>

                    <!-- Итоговый блок -->
                    <div class="bg-white rounded-2xl border border-[#EAE3DE] p-6 shadow-sm space-y-4">
                        <div class="flex justify-between items-baseline">
                            <span class="text-xs text-[#78716C]">{{ $ct['subtotal'] }}</span>
                            <span class="font-serif text-xl font-bold text-[#1C1917]">
                                {{ $this->formatPrice($subtotalUsd) }}
                            </span>
                        </div>

                        <p class="text-[11px] text-[#8C827A] leading-relaxed">
                            {{ $ct['tax_shipping_note'] }}
                        </p>

                        <!-- Кнопка оформления -->
                        <button
                            type="button"
                            wire:click="proceedToCheckout"
                            class="w-full py-4 bg-[#1C1917] hover:bg-black text-white text-xs font-semibold uppercase tracking-widest rounded-xl transition shadow-md flex items-center justify-center gap-2 cursor-pointer"
                        >
                            <span>{{ $ct['checkout_btn'] }}</span>
                            <i class="fa-solid fa-arrow-right text-[10px]"></i>
                        </button>
                    </div>
                </div>
            </div>
        @endif
    </main>

    <x-storefront.footer :store="$store" />
    <x-storefront.auth-modal />
</div>
