@props(['product', 'store' => null])

@php
    $store = $store ?? app(\App\Services\StorefrontService::class);
    $prodKey = (string) ($product['sku'] ?? $product['id'] ?? 'prod');
    $qtyInCart = $store->getItemQuantityInCart($prodKey);
    $favorited = $store->isFavorite($prodKey);
    $displayTitle = $product['title'] ?? $product['name'] ?? 'Косметика Mally';
    $quantity = (int) ($product['quantity'] ?? 10);
    $isOutOfStock = ($product['status'] ?? '') === 'OUT_OF_STOCK' || ($product['status'] ?? '') === 'out_of_stock' || $quantity <= 0 || ($product['inStock'] ?? true) === false;
    $mainImage = $product['image'] ?? ($product['images'][0] ?? 'https://findy.uz/cdn/shop/files/sample.jpg');
    $price = (float) ($product['price'] ?? $product['selling_price'] ?? 35);
    $oldPrice = isset($product['oldPrice']) && $product['oldPrice'] > $price ? (float) $product['oldPrice'] : null;
    $discountPercent = $oldPrice ? (int) round((($oldPrice - $price) / $oldPrice) * 100) : null;
    $productSku = trim((string) ($product['sku'] ?? ('ML-P-'.($product['id'] ?? 'ITEM'))));
    $productUrl = '/products/'.rawurlencode($productSku);
    $brandName = is_array($product['brand'] ?? null) ? ($product['brand']['name'] ?? 'Mally Collection') : ($product['brand'] ?? 'Mally Collection');
@endphp

<div class="group relative flex flex-col bg-white rounded-3xl p-3.5 sm:p-4 border border-[#EAE3DE] hover:border-[#C4B7AF] hover:shadow-xl transition-all duration-300 select-none">
    <!-- 1. Блок фото с зумом при наведении -->
    <div class="relative aspect-square w-full rounded-2xl overflow-hidden bg-[#FAF7F5]">
        <a href="{{ $productUrl }}" wire:navigate class="block w-full h-full">
            <img
                src="{{ $mainImage }}"
                alt="{{ $displayTitle }}"
                class="w-full h-full object-cover object-center group-hover:scale-105 transition-transform duration-500 ease-out"
                loading="lazy"
            />
        </a>

        <!-- Бейджи статуса -->
        <div class="absolute top-2.5 left-2.5 flex flex-col gap-1 z-10 pointer-events-none">
            @if($isOutOfStock)
                <span class="px-2.5 py-1 bg-stone-900/85 backdrop-blur-sm text-stone-200 text-[10px] uppercase tracking-wider font-bold rounded-full shadow-sm">
                    {{ $store->t('product.out_of_stock', 'Распродано') }}
                </span>
            @else
                @if(!empty($product['isNew']))
                    <span class="px-2.5 py-1 bg-[#1C1917] text-white text-[10px] uppercase tracking-wider font-bold rounded-full shadow-sm">
                        NEW
                    </span>
                @endif
                @if($discountPercent)
                    <span class="px-2.5 py-1 bg-[#E11D48] text-white text-[10px] uppercase tracking-wider font-bold rounded-full shadow-sm">
                        -{{ $discountPercent }}%
                    </span>
                @endif
            @endif
        </div>

        <!-- Кнопка "В избранное" (сохраняется на сервере в сессии) -->
        <button
            type="button"
            wire:click="toggleFavorite('{{ addslashes($prodKey) }}')"
            class="absolute top-2.5 right-2.5 z-20 w-8 h-8 rounded-full flex items-center justify-center transition shadow-sm cursor-pointer {{ $favorited ? 'bg-rose-50 text-rose-600 border border-rose-200 shadow-rose-100' : 'bg-white/90 backdrop-blur-sm text-neutral-500 hover:text-rose-600 hover:bg-white border border-neutral-200/60' }}"
            title="{{ $favorited ? 'В избранном' : 'Добавить в избранное' }}"
        >
            <i class="{{ $favorited ? 'fa-solid text-rose-500' : 'fa-regular' }} fa-heart text-xs"></i>
        </button>
    </div>

    <!-- 2. Информация о товаре -->
    <div class="flex flex-col flex-1 mt-3.5">
        <div class="flex items-center justify-between gap-1.5 text-[11px] text-[#8C827A]">
            <span class="font-semibold uppercase tracking-wider truncate">
                {{ $brandName }}
            </span>
            @if(!empty($product['sku']))
                <span class="font-mono text-[10px] text-stone-400 flex-shrink-0">
                    {{ $product['sku'] }}
                </span>
            @endif
        </div>

        <a href="{{ $productUrl }}" wire:navigate class="block mt-1">
            <h3 class="text-xs sm:text-sm font-medium text-[#1C1917] group-hover:text-[#685F58] transition-colors line-clamp-2 min-h-[38px] leading-snug">
                {{ $displayTitle }}
            </h3>
        </a>

        <!-- Звезды рейтинга и остаток на складе -->
        <div class="mt-2 flex items-center justify-between gap-2 flex-nowrap">
            <div class="flex items-center gap-0.5 text-amber-400 text-[10px] shrink-0 whitespace-nowrap">
                <i class="fa-solid fa-star"></i>
                <i class="fa-solid fa-star"></i>
                <i class="fa-solid fa-star"></i>
                <i class="fa-solid fa-star"></i>
                <i class="fa-solid fa-star-half-stroke"></i>
                <span class="text-stone-600 font-mono font-bold ml-1 text-[11px]">4.9</span>
            </div>

            @if(!$isOutOfStock)
                <span class="inline-flex items-center gap-1.5 text-[10px] font-medium px-2.5 py-0.5 rounded-full whitespace-nowrap shrink-0 {{ $quantity <= 5 ? 'bg-amber-50 text-amber-700 border border-amber-200' : 'bg-emerald-50 text-emerald-700 border border-emerald-200' }}">
                    <span class="w-1.5 h-1.5 rounded-full shrink-0 {{ $quantity <= 5 ? 'bg-amber-500 animate-pulse' : 'bg-emerald-500' }}"></span>
                    <span class="whitespace-nowrap">{{ $quantity <= 5 ? $store->t('product.remaining', 'Осталось').' '.$quantity.' '.$store->t('product.pcs', 'шт.') : $quantity.' '.$store->t('product.pcs', 'шт.') }}</span>
                </span>
            @endif
        </div>

        <!-- Цены в выбранной валюте (USD $ / UZS сум) -->
        <div class="mt-2 flex items-baseline gap-2">
            <span class="text-sm sm:text-base font-bold {{ $discountPercent ? 'text-[#E11D48]' : 'text-[#1C1917]' }}">
                {{ $store->formatPrice($price) }}
            </span>
            @if($oldPrice)
                <span class="text-[11px] text-[#A8A29E] line-through">
                    {{ $store->formatPrice($oldPrice) }}
                </span>
            @endif
        </div>

        <!-- 3. Кнопка или счетчик на карточке -->
        <div class="mt-3 pt-1">
            @if($isOutOfStock)
                <a
                    href="https://t.me/mallyuz?text={{ rawurlencode('Здравствуйте! Хочу оформить заказ из США на товар: '.$displayTitle.' (Артикул: '.$productSku.'). Сориентируйте по срокам доставки и деталям.') }}"
                    target="_blank"
                    rel="noreferrer"
                    class="group/oos relative w-full h-10 rounded-2xl bg-stone-100 hover:bg-amber-50 text-stone-600 hover:text-amber-900 border border-stone-200 hover:border-amber-300 text-xs font-semibold transition-all duration-300 flex items-center justify-center overflow-hidden cursor-pointer"
                    title="Товар распродан. Наведите для заказа из США через Telegram"
                >
                    <div class="absolute inset-0 flex items-center justify-center gap-1.5 transition-transform duration-300 ease-out group-hover/oos:-translate-y-full">
                        <i class="fa-solid fa-box-open text-xs text-stone-400"></i>
                        <span>{{ $store->t('product.out_of_stock', 'Распродано') }}</span>
                    </div>
                    <div class="absolute inset-0 flex items-center justify-center gap-1.5 text-amber-900 font-bold transition-transform duration-300 ease-out translate-y-full group-hover/oos:translate-y-0">
                        <i class="fa-solid fa-plane-departure text-xs text-amber-600"></i>
                        <span>{{ $store->t('product.order_us', 'Заказать из США') }}</span>
                    </div>
                </a>
            @elseif($qtyInCart > 0)
                <div class="flex items-center gap-1.5 w-full animate-fade-in">
                    <div class="flex-1 py-1 px-1.5 rounded-2xl bg-[#1C1917] text-white flex items-center justify-between shadow-sm">
                        <button
                            type="button"
                            wire:click="updateCartQuantity('{{ addslashes($prodKey) }}', {{ $qtyInCart - 1 }})"
                            class="w-7 h-7 rounded-xl bg-white/10 hover:bg-white/20 flex items-center justify-center text-sm font-bold transition active:scale-95 cursor-pointer"
                            title="Уменьшить"
                        >
                            −
                        </button>
                        <div class="flex items-center gap-1 px-1">
                            <span class="text-xs font-bold leading-none">{{ $qtyInCart }}</span>
                            <span class="text-[10px] text-[#D5CCC7] leading-none">{{ $store->t('cart.qty', 'шт.') }}</span>
                        </div>
                        <button
                            type="button"
                            wire:click="updateCartQuantity('{{ addslashes($prodKey) }}', {{ $qtyInCart + 1 }})"
                            @disabled($qtyInCart >= $quantity)
                            class="w-7 h-7 rounded-xl flex items-center justify-center text-sm font-bold transition {{ $qtyInCart >= $quantity ? 'bg-white/5 text-stone-500 opacity-40 cursor-not-allowed' : 'bg-white/10 hover:bg-white/20 text-white active:scale-95 cursor-pointer' }}"
                            title="{{ $qtyInCart >= $quantity ? 'Максимум на складе ('.$quantity.' шт.)' : 'Увеличить' }}"
                        >
                            +
                        </button>
                    </div>

                    <a
                        href="/cart"
                        wire:navigate
                        class="py-2 px-3 rounded-2xl bg-[#FAF7F5] hover:bg-[#EAE3DE] text-[#1C1917] border border-[#EAE3DE] hover:border-[#1C1917] text-xs font-bold transition flex items-center gap-1.5 shadow-2xs whitespace-nowrap cursor-pointer"
                        title="Перейти в корзину"
                    >
                        <i class="fa-solid fa-bag-shopping text-xs"></i>
                        <span>{{ $store->t('nav.cart', 'В корзину') }}</span>
                    </a>
                </div>
            @else
                <button
                    type="button"
                    wire:click="addToCart('{{ addslashes($prodKey) }}')"
                    class="w-full py-2.5 px-4 rounded-2xl bg-[#1C1917] hover:bg-black text-white text-xs font-semibold transition-all duration-200 flex items-center justify-center gap-1.5 shadow-sm active:scale-[0.98] cursor-pointer"
                >
                    <i class="fa-solid fa-plus text-xs"></i>
                    <span>{{ $store->t('product.add_to_cart', 'В корзину') }}</span>
                </button>
            @endif
        </div>
    </div>
</div>
