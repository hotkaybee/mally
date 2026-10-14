@php
    $currentProduct = $product ?? [];
    $prodKey = (string) ($currentProduct['sku'] ?? $currentProduct['id'] ?? 'ML-ITEM');
    $title = $currentProduct['title'] ?? $currentProduct['name'] ?? 'Косметика Mally';
    $qtyInCart = $store->getItemQuantityInCart($prodKey);
    $images = !empty($currentProduct['images']) && is_array($currentProduct['images'])
        ? array_values(array_filter($currentProduct['images']))
        : [$currentProduct['image'] ?? 'https://findy.uz/cdn/shop/files/sample.jpg'];
    if (empty($images)) {
        $images = ['https://findy.uz/cdn/shop/files/sample.jpg'];
    }
    $isOutOfStock = empty($currentProduct['inStock']);
    $canIncrease = $qtyInCart > 0 && $store->canIncrease($prodKey);
    $price = (float) ($currentProduct['price'] ?? $currentProduct['selling_price'] ?? 35);
    $oldPrice = isset($currentProduct['oldPrice']) && $currentProduct['oldPrice'] > $price ? (float) $currentProduct['oldPrice'] : null;
    $discountPercent = $oldPrice ? (int) round((($oldPrice - $price) / $oldPrice) * 100) : null;
    $brandName = is_array($currentProduct['brand'] ?? null) ? ($currentProduct['brand']['name'] ?? 'Mally Beauty') : ($currentProduct['brand'] ?? 'Mally Beauty');
    $categoryName = is_array($currentProduct['category'] ?? null) ? ($currentProduct['category']['name_ru'] ?? $currentProduct['category']['name'] ?? 'Каталог') : ($currentProduct['category'] ?? 'Каталог');
    $skuCode = $currentProduct['sku'] ?? $prodKey;
    $favorited = $store->isFavoriteProduct($currentProduct);
@endphp

<div
    x-data="{
        activeImageIndex: 0,
        isZoomOpen: false,
        copiedSku: false,
        images: @js($images),
        prevImage() {
            if (this.images.length <= 1) return;
            this.activeImageIndex = this.activeImageIndex === 0 ? this.images.length - 1 : this.activeImageIndex - 1;
        },
        nextImage() {
            if (this.images.length <= 1) return;
            this.activeImageIndex = this.activeImageIndex === this.images.length - 1 ? 0 : this.activeImageIndex + 1;
        },
        copySku(code) {
            navigator.clipboard.writeText(code);
            this.copiedSku = true;
            setTimeout(() => this.copiedSku = false, 2000);
        }
    }"
    @keydown.window.arrow-left="prevImage()"
    @keydown.window.arrow-right="nextImage()"
    @keydown.window.escape="isZoomOpen = false"
    class="min-h-screen bg-[#FCECEC] text-[#2C2A29] flex flex-col font-sans"
>
    <x-storefront.header
        :store="$store"
        :locale="$locale"
        :currency="$currency"
        :exchangeRate="$exchangeRate"
        :rateSource="$rateSource"
        :cartCount="$cartCount"
        :favoritesCount="$favoritesCount"
        :currentCustomer="$currentCustomer"
        :headerCategories="$headerCategories"
    />

    <!-- Хлебные крошки со строгим Артикулом (SKU) -->
    <div class="max-w-7xl mx-auto px-4 py-4 w-full text-xs text-[#8C827A] flex items-center gap-2">
        <a href="/" wire:navigate class="hover:text-black transition">{{ $store->t('nav.home', 'Главная') }}</a>
        <span>/</span>
        <a href="/catalog" wire:navigate class="hover:text-black transition">{{ $categoryName ?: $store->t('nav.catalog', 'Каталог') }}</a>
        <span>/</span>
        <span class="font-mono text-[#1C1917] font-semibold">{{ $skuCode }}</span>
        <span>•</span>
        <span class="text-[#57534E] truncate max-w-xs">{{ $title }}</span>
    </div>

    <main class="max-w-7xl mx-auto px-4 py-6 w-full flex-1 space-y-12">
        <!-- ВЕРХНЯЯ ЧАСТЬ: Фотогалерея и Панель Покупки -->
        <div class="grid grid-cols-1 lg:grid-cols-12 gap-8 lg:gap-12 items-start">
            <!-- Левая колонка: Большая интерактивная галерея со стрелками фото -->
            <div class="lg:col-span-6 space-y-4">
                <div class="relative aspect-square w-full rounded-3xl overflow-hidden bg-white border border-[#EAE3DE] shadow-sm flex items-center justify-center select-none group">
                    <img
                        :src="images[activeImageIndex] || images[0]"
                        alt="{{ $title }}"
                        class="w-full h-full object-cover cursor-zoom-in transition-transform duration-500 group-hover:scale-[1.03]"
                        @click="isZoomOpen = true"
                    />

                    @if($discountPercent)
                        <span class="absolute top-4 left-4 px-3 py-1 bg-[#E11D48] text-white text-xs font-bold rounded-full shadow-md z-10">
                            -{{ $discountPercent }}%
                        </span>
                    @endif

                    @if(count($images) > 1)
                        <div class="absolute top-4 left-1/2 -translate-x-1/2 px-3 py-1 bg-black/60 backdrop-blur-md text-white text-[11px] font-mono font-medium rounded-full z-10 pointer-events-none">
                            <span x-text="activeImageIndex + 1">1</span> / {{ count($images) }}
                        </div>
                    @endif

                    <!-- Кнопка "В избранное" -->
                    <button
                        type="button"
                        wire:click="toggleFavorite('{{ addslashes($prodKey) }}')"
                        class="absolute top-4 right-4 z-10 w-11 h-11 rounded-full flex items-center justify-center transition shadow-md cursor-pointer {{ $favorited ? 'bg-rose-50 text-rose-600 border border-rose-200' : 'bg-white/90 backdrop-blur-sm text-neutral-600 hover:text-rose-600 hover:bg-white border border-neutral-200' }}"
                        title="{{ $favorited ? 'В избранном' : 'Добавить в избранное' }}"
                    >
                        <i class="{{ $favorited ? 'fa-solid text-rose-500' : 'fa-regular' }} fa-heart text-base"></i>
                    </button>

                    @if(count($images) > 1)
                        <button
                            type="button"
                            @click.stop="prevImage()"
                            class="absolute left-3 top-1/2 -translate-y-1/2 z-20 w-12 h-12 rounded-full bg-white/90 hover:bg-white text-[#1C1917] hover:scale-110 shadow-lg border border-stone-200/80 backdrop-blur-md flex items-center justify-center transition cursor-pointer"
                            title="Предыдущее фото"
                        >
                            <i class="fa-solid fa-chevron-left text-base"></i>
                        </button>

                        <button
                            type="button"
                            @click.stop="nextImage()"
                            class="absolute right-3 top-1/2 -translate-y-1/2 z-20 w-12 h-12 rounded-full bg-white/90 hover:bg-white text-[#1C1917] hover:scale-110 shadow-lg border border-stone-200/80 backdrop-blur-md flex items-center justify-center transition cursor-pointer"
                            title="Следующее фото"
                        >
                            <i class="fa-solid fa-chevron-right text-base"></i>
                        </button>
                    @endif
                </div>

                @if(count($images) > 1)
                    <div class="flex gap-3 overflow-x-auto pb-2 scrollbar-none">
                        @foreach($images as $idx => $img)
                            <button
                                type="button"
                                @click="activeImageIndex = {{ $idx }}"
                                :class="activeImageIndex === {{ $idx }} ? 'border-[#1C1917] shadow-md ring-2 ring-[#1C1917]/20 scale-105' : 'border-[#EAE3DE] opacity-70 hover:opacity-100 hover:border-stone-400'"
                                class="relative w-20 h-20 rounded-2xl overflow-hidden border-2 flex-shrink-0 transition-all cursor-pointer"
                            >
                                <img src="{{ $img }}" alt="{{ $title }}" class="w-full h-full object-cover" />
                            </button>
                        @endforeach
                    </div>
                @endif
            </div>

            <!-- Правая колонка: Основные детали, наличие на складе с полосой партии, цена и покупка -->
            <div class="lg:col-span-6 space-y-6">
                <div>
                    <div class="flex items-center justify-between gap-3">
                        <span class="text-xs uppercase tracking-widest text-[#8C827A] font-bold">
                            {{ $brandName }}
                        </span>
                        <span class="text-xs text-[#8C827A] bg-stone-100 px-2.5 py-0.5 rounded-full font-medium">
                            {{ $categoryName }}
                        </span>
                    </div>

                    <h1 class="font-serif text-2xl sm:text-3xl lg:text-4xl font-bold text-[#1C1917] mt-2 leading-snug">
                        {{ $title }}
                    </h1>

                    <div class="flex items-center gap-2 mt-3 pt-2 border-t border-[#EAE3DE]">
                        <span class="text-xs text-[#8C827A]">{{ $store->t('product.sku_label', 'Артикул товара') }}:</span>
                        <button
                            type="button"
                            @click="copySku('{{ addslashes($skuCode) }}')"
                            class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-lg bg-[#FAF7F5] hover:bg-[#EAE3DE] border border-[#EAE3DE] font-mono text-xs font-bold text-[#1C1917] transition cursor-pointer"
                        >
                            <span>{{ $skuCode }}</span>
                            <i class="fa-solid text-[10px]" :class="copiedSku ? 'fa-check text-emerald-600' : 'fa-copy text-[#8C827A]'"></i>
                        </button>
                    </div>
                </div>

                <!-- НАЛИЧИЕ: только «В наличии» / «Распродано» -->
                <div class="p-5 bg-white rounded-3xl border border-[#EAE3DE] shadow-xs">
                    <div class="flex items-center gap-2 text-xs sm:text-sm">
                        <span class="w-2.5 h-2.5 rounded-full {{ $isOutOfStock ? 'bg-stone-400' : 'bg-emerald-500' }}"></span>
                        <span class="font-bold text-[#1C1917]">
                            {{ $isOutOfStock ? $store->t('product.out_of_stock', 'Распродано') : $store->t('product.in_stock', 'В наличии') }}
                        </span>
                    </div>
                </div>

                <!-- ЦЕНЫ -->
                <div class="p-5 bg-white rounded-3xl border border-[#EAE3DE] flex items-baseline justify-between shadow-xs">
                    <div>
                        <span class="text-[11px] text-[#8C827A] uppercase tracking-wider block font-semibold mb-1">
                            {{ $currency === 'UZS' ? $store->t('product.price_uzs', 'Стоимость в сумах (UZS)') : $store->t('product.price_usd', 'Стоимость в долларах США ($)') }}
                        </span>
                        <div class="flex items-baseline gap-3">
                            <span class="text-3xl sm:text-4xl font-bold font-serif {{ $discountPercent ? 'text-[#E11D48]' : 'text-[#1C1917]' }}">
                                {{ $store->formatPrice($price) }}
                            </span>
                            @if($oldPrice)
                                <span class="text-base text-[#A8A29E] line-through font-serif">
                                    {{ $store->formatPrice($oldPrice) }}
                                </span>
                            @endif
                        </div>
                    </div>

                    @if($discountPercent && $oldPrice)
                        <div class="text-right">
                            <span class="inline-block px-3 py-1 bg-rose-50 text-[#E11D48] text-xs font-bold rounded-full border border-rose-200">
                                {{ $store->t('product.savings', 'Экономия') }} {{ $store->formatPrice($oldPrice - $price) }}
                            </span>
                        </div>
                    @endif
                </div>

                <!-- Кнопка добавления в корзину и Telegram-заказ -->
                <div class="space-y-3">
                    @if($isOutOfStock)
                        <div class="space-y-3">
                            <a
                                href="https://t.me/mallyuz?text={{ rawurlencode('Здравствуйте! Хочу оформить индивидуальный заказ из США (Sephora) на товар: '.$title.' (Артикул: '.$skuCode.', Цена: '.$store->formatPrice($price).').') }}"
                                target="_blank"
                                rel="noreferrer"
                                class="w-full py-4 px-6 bg-gradient-to-r from-amber-600 to-amber-700 hover:from-amber-700 hover:to-amber-800 text-white text-xs font-bold uppercase tracking-wider rounded-2xl shadow-md hover:shadow-lg transition flex items-center justify-center gap-2.5 cursor-pointer"
                            >
                                <i class="fa-solid fa-plane-departure text-sm"></i>
                                <span>{{ $store->t('product.order_tg', 'Заказать байером из США в Telegram') }}</span>
                            </a>
                            <p class="text-[11px] text-[#78716C] text-center">
                                {{ $store->t('product.tg_note', 'Выкупаем напрямую в Sephora / Ulta USA по фиксированной стоимости') }}
                            </p>
                        </div>
                    @elseif($qtyInCart > 0)
                        <div class="space-y-2 w-full">
                            <div class="flex flex-col sm:flex-row items-stretch sm:items-center gap-3 w-full">
                                <div class="py-3 px-5 rounded-2xl bg-[#1C1917] text-white flex items-center justify-between shadow-md flex-1">
                                    <button
                                        type="button"
                                        wire:click="updateCartQuantity('{{ addslashes($prodKey) }}', {{ $qtyInCart - 1 }})"
                                        class="w-9 h-9 rounded-xl bg-white/10 hover:bg-white/20 flex items-center justify-center text-lg font-bold transition cursor-pointer"
                                    >
                                        −
                                    </button>
                                    <div class="flex items-center gap-2">
                                        <span class="text-base font-bold font-mono">{{ $qtyInCart }}</span>
                                        <span class="text-xs text-[#D5CCC7]">{{ $store->t('product.in_cart', 'в корзине') }}</span>
                                    </div>
                                    <button
                                        type="button"
                                        wire:click="updateCartQuantity('{{ addslashes($prodKey) }}', {{ $qtyInCart + 1 }})"
                                        @disabled(! $canIncrease)
                                        class="w-9 h-9 rounded-xl flex items-center justify-center text-lg font-bold transition {{ ! $canIncrease ? 'bg-white/5 text-stone-500 opacity-40 cursor-not-allowed' : 'bg-white/10 hover:bg-white/20 text-white cursor-pointer' }}"
                                    >
                                        +
                                    </button>
                                </div>

                                <a
                                    href="/cart"
                                    wire:navigate
                                    class="py-4 px-6 rounded-2xl bg-[#FAF7F5] hover:bg-[#EAE3DE] text-[#1C1917] border border-[#EAE3DE] hover:border-[#1C1917] text-xs font-bold transition flex items-center justify-center gap-2 shadow-sm whitespace-nowrap cursor-pointer"
                                >
                                    <i class="fa-solid fa-bag-shopping text-sm"></i>
                                    <span>{{ $store->t('cart.checkout', 'Перейти к оформлению') }}</span>
                                </a>
                            </div>

                            @if(! $canIncrease)
                                <div class="text-[11px] text-amber-800 bg-amber-50 px-3 py-1.5 rounded-xl border border-amber-200/60 flex items-center gap-1.5">
                                    <i class="fa-solid fa-triangle-exclamation text-amber-600"></i>
                                    <span>{{ __('No more units of this product are available.') }}</span>
                                </div>
                            @endif
                        </div>
                    @else
                        <div class="flex flex-col sm:flex-row gap-3">
                            <button
                                type="button"
                                wire:click="addToCart('{{ addslashes($prodKey) }}')"
                                class="flex-1 py-4 px-6 bg-[#1C1917] hover:bg-black text-white text-xs font-bold uppercase tracking-wider rounded-2xl shadow-lg transition active:scale-[0.99] flex items-center justify-center gap-2.5 cursor-pointer"
                            >
                                <i class="fa-solid fa-bag-shopping text-sm"></i>
                                <span>{{ $store->t('product.add_to_cart', 'Добавить в корзину') }} ({{ $store->formatPrice($price) }})</span>
                            </button>

                            <a
                                href="https://t.me/mallyuz?text={{ rawurlencode('Здравствуйте! Хочу заказать товар: '.$title.' (Артикул: '.$skuCode.', Цена: '.$store->formatPrice($price).').') }}"
                                target="_blank"
                                rel="noreferrer"
                                class="py-4 px-5 bg-white hover:bg-stone-50 text-[#1C1917] border border-[#EAE3DE] text-xs font-bold rounded-2xl shadow-sm transition flex items-center justify-center gap-2 cursor-pointer"
                            >
                                <i class="fa-brands fa-telegram text-[#229ED9] text-base"></i>
                                <span>{{ $store->t('product.buy_tg', 'Купить в Telegram') }}</span>
                            </a>
                        </div>
                    @endif
                </div>

                <!-- Краткие маркеры гарантий -->
                <div class="pt-2 grid grid-cols-2 gap-3 text-xs text-[#57534E]">
                    <div class="flex items-center gap-2 p-3 bg-white/60 rounded-2xl border border-stone-200/60">
                        <i class="fa-solid fa-shield-halved text-emerald-600 text-sm"></i>
                        <span class="font-medium text-[#1C1917]">{{ $store->t('product.usa_origin', '100% Оригинал из США') }}</span>
                    </div>
                    <div class="flex items-center gap-2 p-3 bg-white/60 rounded-2xl border border-stone-200/60">
                        <i class="fa-solid fa-wand-magic-sparkles text-[#96674c] text-sm"></i>
                        <span class="font-medium text-[#1C1917]">{{ $store->t('product.batch_verified', 'Батч-код проверяется') }}</span>
                    </div>
                </div>
            </div>
        </div>

        <!-- НИЖНЯЯ ПОЛНОРАЗМЕРНАЯ ЛЕНТА ВСЕХ БЛОКОВ ОПИСАНИЯ -->
        <div class="space-y-8 pt-6">
            <!-- БЛОК 1: Подробное описание и текстура -->
            <section class="bg-white rounded-3xl p-6 sm:p-8 border border-[#EAE3DE] shadow-xs space-y-5">
                <div class="flex items-center gap-3 pb-3 border-b border-[#EAE3DE]">
                    <div class="w-9 h-9 rounded-xl bg-[#FAF7F5] text-[#1C1917] flex items-center justify-center font-bold">
                        <i class="fa-solid fa-feather text-base"></i>
                    </div>
                    <div>
                        <h2 class="font-serif text-xl sm:text-2xl font-bold text-[#1C1917]">
                            {{ $store->t('product.desc_texture', 'Описание и текстура') }}
                        </h2>
                        <p class="text-xs text-[#8C827A]">
                            {{ $store->t('product.desc_subtitle', 'Особенности текстуры, формула и эффект от применения') }}
                        </p>
                    </div>
                </div>

                <div class="text-sm sm:text-base text-[#2C2A29] font-serif leading-relaxed">
                    {{ $currentProduct['description'] ?: '100% оригинальная косметика от официальных ритейлеров США (Sephora, Ulta). Проверено экспертами Mally.' }}
                </div>

                @if(!empty($currentProduct['features']) && is_array($currentProduct['features']))
                    <div class="pt-2 space-y-3">
                        <h3 class="text-xs font-bold uppercase tracking-wider text-[#1C1917]">
                            {{ $store->t('product.key_benefits', 'Ключевые преимущества формулы:') }}
                        </h3>
                        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-3">
                            @foreach($currentProduct['features'] as $f)
                                <div class="flex items-start gap-2.5 bg-[#FAF7F5] p-3 rounded-2xl border border-stone-200/60 text-xs text-[#57534E]">
                                    <i class="fa-solid fa-circle-check text-emerald-600 mt-0.5"></i>
                                    <span class="font-medium">{{ $f }}</span>
                                </div>
                            @endforeach
                        </div>
                    </div>
                @endif
            </section>

            <!-- БЛОК 2: Состав и активные компоненты -->
            <section class="bg-white rounded-3xl p-6 sm:p-8 border border-[#EAE3DE] shadow-xs space-y-5">
                <div class="flex items-center gap-3 pb-3 border-b border-[#EAE3DE]">
                    <div class="w-9 h-9 rounded-xl bg-emerald-50 text-emerald-700 flex items-center justify-center font-bold">
                        <i class="fa-solid fa-leaf text-base"></i>
                    </div>
                    <div>
                        <h2 class="font-serif text-xl sm:text-2xl font-bold text-[#1C1917]">
                            {{ $store->t('product.ingredients_title', 'Состав и компоненты') }}
                        </h2>
                        <p class="text-xs text-[#8C827A]">
                            {{ $store->t('product.ingredients_subtitle', 'Стандарты чистоты Clean Beauty, безопасность и активные вещества') }}
                        </p>
                    </div>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-3 gap-3 text-xs">
                    <div class="p-3.5 bg-emerald-50/50 rounded-2xl border border-emerald-100 flex items-center gap-3">
                        <i class="fa-solid fa-check text-emerald-600 text-base"></i>
                        <div>
                            <div class="font-bold text-emerald-900">Cruelty-Free</div>
                            <div class="text-[10px] text-emerald-700">{{ $locale === 'uz' ? 'Hayvonlarda sinovdan o‘tkazilmagan' : ($locale === 'en' ? 'Never tested on animals' : 'Не тестируется на животных') }}</div>
                        </div>
                    </div>
                    <div class="p-3.5 bg-emerald-50/50 rounded-2xl border border-emerald-100 flex items-center gap-3">
                        <i class="fa-solid fa-check text-emerald-600 text-base"></i>
                        <div>
                            <div class="font-bold text-emerald-900">Paraben-Free</div>
                            <div class="text-[10px] text-emerald-700">{{ $locale === 'uz' ? 'Parabenlar va SLSsiz' : ($locale === 'en' ? 'Free of parabens & SLS' : 'Без парабенов и SLS') }}</div>
                        </div>
                    </div>
                    <div class="p-3.5 bg-emerald-50/50 rounded-2xl border border-emerald-100 flex items-center gap-3">
                        <i class="fa-solid fa-check text-emerald-600 text-base"></i>
                        <div>
                            <div class="font-bold text-emerald-900">Clean Formula</div>
                            <div class="text-[10px] text-emerald-700">{{ $locale === 'uz' ? 'Gipoallergen tarkib' : ($locale === 'en' ? 'Hypoallergenic formula' : 'Гипоаллергенный состав') }}</div>
                        </div>
                    </div>
                </div>
            </section>

            <!-- БЛОК 3: Полные характеристики товара -->
            <section class="bg-white rounded-3xl p-6 sm:p-8 border border-[#EAE3DE] shadow-xs space-y-5">
                <div class="flex items-center gap-3 pb-3 border-b border-[#EAE3DE]">
                    <div class="w-9 h-9 rounded-xl bg-amber-50 text-amber-800 flex items-center justify-center font-bold">
                        <i class="fa-solid fa-list-check text-base"></i>
                    </div>
                    <div>
                        <h2 class="font-serif text-xl sm:text-2xl font-bold text-[#1C1917]">
                            {{ $store->t('product.specs_title', 'Характеристики товара') }}
                        </h2>
                        <p class="text-xs text-[#8C827A]">
                            {{ $locale === 'uz' ? "Original mahsulot pasporti va partiya ma'lumotlari" : ($locale === 'en' ? 'Authentic product passport & batch details' : 'Паспорт оригинального продукта и данные партии') }}
                        </p>
                    </div>
                </div>

                <div class="overflow-x-auto">
                    <table class="w-full text-xs">
                        <tbody class="divide-y divide-stone-100">
                            <tr class="hover:bg-stone-50/60 transition-colors">
                                <td class="py-3 px-2 text-[#8C827A] font-medium w-1/3 sm:w-1/4">{{ $store->t('product.sku_label', 'Артикул (SKU)') }}</td>
                                <td class="py-3 px-2 text-[#1C1917] font-mono font-bold">{{ $skuCode }}</td>
                            </tr>
                            <tr class="hover:bg-stone-50/60 transition-colors">
                                <td class="py-3 px-2 text-[#8C827A] font-medium">{{ $store->t('product.brand', 'Бренд') }}</td>
                                <td class="py-3 px-2 text-[#1C1917] font-bold">{{ $brandName }}</td>
                            </tr>
                            <tr class="hover:bg-stone-50/60 transition-colors">
                                <td class="py-3 px-2 text-[#8C827A] font-medium">{{ $store->t('product.category', 'Категория') }}</td>
                                <td class="py-3 px-2 text-[#1C1917]">{{ $categoryName }}</td>
                            </tr>
                            <tr class="hover:bg-stone-50/60 transition-colors">
                                <td class="py-3 px-2 text-[#8C827A] font-medium">{{ $store->t('product.origin_country', 'Страна закупки') }}</td>
                                <td class="py-3 px-2 text-[#1C1917]">{{ $store->t('product.origin_usa', 'США (Официальные дистрибьюторы Sephora USA / Ulta)') }}</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </section>

            <!-- БЛОК 4: Способ применения -->
            <section class="bg-white rounded-3xl p-6 sm:p-8 border border-[#EAE3DE] shadow-xs space-y-5">
                <div class="flex items-center gap-3 pb-3 border-b border-[#EAE3DE]">
                    <div class="w-9 h-9 rounded-xl bg-purple-50 text-purple-800 flex items-center justify-center font-bold">
                        <i class="fa-solid fa-wand-magic-sparkles text-base"></i>
                    </div>
                    <div>
                        <h2 class="font-serif text-xl sm:text-2xl font-bold text-[#1C1917]">
                            {{ $store->t('product.how_to_use_title', 'Способ применения') }}
                        </h2>
                        <p class="text-xs text-[#8C827A]">
                            {{ $store->t('product.how_to_use_subtitle', 'Пошаговая инструкция и профессиональные советы') }}
                        </p>
                    </div>
                </div>

                <div class="text-sm font-medium text-[#1C1917] leading-relaxed">
                    {{ $currentProduct['howToUse'] ?: 'Наносите по мере необходимости в соответствии с инструкциями бренда на очищенную кожу.' }}
                </div>
            </section>

            <!-- БЛОК 5: Отзывы покупателей -->
            @php
                $productReviews = is_array($currentProduct['reviews'] ?? null) ? $currentProduct['reviews'] : [];
                $reviewsTitle = match ($locale) {
                    'uz' => 'Xaridorlar sharhlari',
                    'en' => 'Customer Reviews',
                    default => 'Отзывы покупателей',
                };
                $reviewsSubtitle = match ($locale) {
                    'uz' => 'Tasdiqlangan buyurtmalar bo‘yicha haqiqiy fikrlar',
                    'en' => 'Verified reviews from Mally customers',
                    default => 'Реальные впечатления клиентов по доставленным заказам',
                };
                $noReviewsText = match ($locale) {
                    'uz' => "Ushbu mahsulot bo'yicha hozircha sharhlar yo'q. Xarid qilganingizdan so'ng shaxsiy kabinet orqali birinchi bo'lib fikr qoldirishingiz mumkin!",
                    'en' => 'No reviews for this product yet. You can leave the first review from your profile after your order is delivered!',
                    default => 'Отзывов на этот товар пока нет. Вы сможете оставить первый отзыв в личном кабинете после получения заказа!',
                };
                $verifiedBadgeText = match ($locale) {
                    'uz' => 'Tasdiqlangan xarid',
                    'en' => 'Verified Purchase',
                    default => 'Проверенная покупка',
                };
            @endphp
            <section class="bg-white rounded-3xl p-6 sm:p-8 border border-[#EAE3DE] shadow-xs space-y-5">
                <div class="flex items-center justify-between gap-3 pb-3 border-b border-[#EAE3DE] flex-wrap">
                    <div class="flex items-center gap-3">
                        <div class="w-9 h-9 rounded-xl bg-amber-50 text-amber-600 flex items-center justify-center font-bold">
                            <i class="fa-solid fa-star text-base"></i>
                        </div>
                        <div>
                            <h2 class="font-serif text-xl sm:text-2xl font-bold text-[#1C1917]">
                                {{ $reviewsTitle }} ({{ count($productReviews) }})
                            </h2>
                            <p class="text-xs text-[#8C827A]">
                                {{ $reviewsSubtitle }}
                            </p>
                        </div>
                    </div>
                </div>

                @if(count($productReviews) === 0)
                    <p class="text-xs sm:text-sm text-[#78716C] py-2">
                        {{ $noReviewsText }}
                    </p>
                @else
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        @foreach($productReviews as $rev)
                            @php
                                $revAuthor = (string) ($rev['author'] ?? 'Покупатель Mally');
                                $revCity = (string) ($rev['city'] ?? '');
                                $revDate = (string) ($rev['date'] ?? '');
                                $revRating = max(1, min(5, (int) ($rev['rating'] ?? 5)));
                                $revComment = (string) ($rev['comment'] ?? $rev['text'] ?? '');
                                $revPhotos = is_array($rev['photos'] ?? null) ? $rev['photos'] : [];
                            @endphp
                            <div class="p-4 sm:p-5 rounded-2xl bg-[#FAF8F6] border border-[#EAE3DE] space-y-3 flex flex-col justify-between">
                                <div class="space-y-2.5">
                                    <div class="flex items-center justify-between gap-2">
                                        <div class="flex items-center gap-1 text-amber-400 text-xs">
                                            @for($i = 1; $i <= 5; $i++)
                                                <i class="fa-{{ $i <= $revRating ? 'solid' : 'regular' }} fa-star"></i>
                                            @endfor
                                        </div>
                                        <span class="inline-flex items-center gap-1 text-[10px] font-semibold text-emerald-700 bg-emerald-50 border border-emerald-200/80 px-2 py-0.5 rounded-full">
                                            <i class="fa-solid fa-circle-check text-[9px]"></i>
                                            <span>{{ $verifiedBadgeText }}</span>
                                        </span>
                                    </div>

                                    <p class="text-xs sm:text-sm text-[#1C1917] leading-relaxed">
                                        {{ $revComment }}
                                    </p>

                                    @if(count($revPhotos) > 0)
                                        <div class="flex gap-2 flex-wrap pt-1">
                                            @foreach($revPhotos as $pUrl)
                                                <a href="{{ $pUrl }}" target="_blank" rel="noreferrer" class="block w-14 h-14 rounded-xl overflow-hidden border border-[#EAE3DE] hover:opacity-90 transition">
                                                    <img src="{{ $pUrl }}" alt="Review photo" class="w-full h-full object-cover" />
                                                </a>
                                            @endforeach
                                        </div>
                                    @endif
                                </div>

                                <div class="pt-2 border-t border-[#EAE3DE]/70 flex items-center justify-between text-[11px] text-[#78716C]">
                                    <span class="font-bold text-[#1C1917]">
                                        {{ $revAuthor }}@if($revCity !== '') · <span class="font-normal text-[#8C827A]">{{ $revCity }}</span>@endif
                                    </span>
                                    @if($revDate !== '')
                                        <span>{{ $revDate }}</span>
                                    @endif
                                </div>
                            </div>
                        @endforeach
                    </div>
                @endif
            </section>
        </div>

        <!-- БЛОК УМНЫХ РЕКОМЕНДАЦИЙ -->
        @if(count($recommendedProducts) > 0)
            <div class="pt-8 border-t border-[#EAE3DE]">
                <div class="flex items-center justify-between mb-6">
                    <div>
                        <h2 class="font-serif text-2xl sm:text-3xl font-bold text-[#1C1917]">
                            {{ $store->t('product.recommended', 'Вам также может понравиться') }}
                        </h2>
                        <p class="text-xs text-[#8C827A] mt-1">
                            {{ $store->t('product.recommended_subtitle', 'Средства из этой коллекции и похожие бестселлеры') }}
                        </p>
                    </div>
                    <a href="/catalog" wire:navigate class="text-xs font-bold text-[#96674c] hover:underline">
                        {{ $store->t('catalog.all', 'Весь каталог') }} →
                    </a>
                </div>
                <div class="grid grid-cols-2 sm:grid-cols-4 gap-4 sm:gap-6">
                    @foreach($recommendedProducts as $p)
                        <x-storefront.product-card :product="$p" :store="$store" />
                    @endforeach
                </div>
            </div>
        @endif

        <!-- ПОЛНОЭКРАННЫЙ ЗУМ -->
        <div
            x-show="isZoomOpen"
            x-cloak
            class="fixed inset-0 z-50 bg-black/90 backdrop-blur-md flex items-center justify-center p-4 select-none animate-fade-in"
            @click="isZoomOpen = false"
        >
            <button
                type="button"
                @click="isZoomOpen = false"
                class="absolute top-5 right-5 z-50 w-12 h-12 rounded-full bg-white/20 hover:bg-white/40 text-white flex items-center justify-center transition cursor-pointer"
            >
                <i class="fa-solid fa-xmark text-lg"></i>
            </button>

            <div class="relative max-w-4xl max-h-[88vh] flex items-center justify-center" @click.stop>
                <img
                    :src="images[activeImageIndex] || images[0]"
                    alt="{{ $title }}"
                    class="max-h-[85vh] max-w-full object-contain rounded-2xl shadow-2xl"
                />
            </div>
        </div>
    </main>

    <x-storefront.footer :store="$store" />

    <x-storefront.auth-modal
        :isOpen="$isAuthModalOpen"
        :step="$authStep"
        :phone="$authPhone"
        :message="$authModalMessage"
    />
</div>
