@php
    $perPageUnit = match ($locale) {
        'uz' => 'sah.',
        'en' => 'page',
        default => 'стр.',
    };
    $notFoundHint = match ($locale) {
        'uz' => "Qidiruv so'rovini o'zgartirib ko'ring yoki filtrlarni bekor qiling",
        'en' => 'Try changing your search query or resetting the filters',
        default => 'Попробуйте изменить запрос или сбросить фильтры',
    };
    $searchPlaceholder = match ($locale) {
        'uz' => "Mahsulot nomi, brend yoki artikul (SKU) bo'yicha qidirish...",
        'en' => 'Search by product title, brand, or SKU...',
        default => 'Поиск товара по названию, бренду или артикулу (SKU)...',
    };
    $searchBadgeText = match ($locale) {
        'uz' => 'Nomi + Artikul (SKU)',
        'en' => 'Title + SKU',
        default => 'По названию + артикулу (SKU)',
    };
@endphp

<div class="min-h-screen bg-[#FCECEC] text-[#2C2A29] flex flex-col font-sans">
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
        :selectedCategory="$selectedCategory"
        :liveCatalogMode="true"
    />

    <main class="max-w-[1720px] mx-auto px-4 sm:px-6 lg:px-8 py-8 w-full flex-1">
        <!-- Поиск на странице каталога: всегда ищет по названию + артикулу (SKU) -->
        <div class="bg-white rounded-3xl p-4 sm:p-5 border border-[#EAE3DE] shadow-2xs mb-6">
            <div class="flex flex-col sm:flex-row items-stretch sm:items-center justify-between gap-3">
                <div class="relative flex-1">
                    <i class="fa-solid fa-magnifying-glass absolute left-4 top-1/2 -translate-y-1/2 text-xs text-[#8C827A]"></i>
                    <input
                        type="text"
                        wire:model.live.debounce.250ms="searchQuery"
                        placeholder="{{ $searchPlaceholder }}"
                        class="w-full pl-10 pr-10 py-3 text-xs sm:text-sm bg-[#FAF8F6] border border-[#E3DBD5] rounded-2xl focus:outline-none focus:border-[#1C1917] focus:bg-white transition"
                    />
                    @if(trim($searchQuery) !== '')
                        <button
                            type="button"
                            wire:click="$set('searchQuery', '')"
                            class="absolute right-3.5 top-1/2 -translate-y-1/2 text-[#8C827A] hover:text-[#1C1917] p-1 cursor-pointer"
                            title="Очистить"
                        >
                            <i class="fa-solid fa-xmark text-sm"></i>
                        </button>
                    @endif
                </div>

                <div class="inline-flex items-center gap-2 px-3.5 py-2.5 rounded-2xl bg-[#FAF8F6] border border-[#EAE3DE] text-xs font-semibold text-[#57534E] self-start sm:self-auto whitespace-nowrap">
                    <i class="fa-solid fa-barcode text-[#0D9488] text-xs"></i>
                    <span>{{ $searchBadgeText }}</span>
                </div>
            </div>
        </div>

        <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center pb-6 border-b border-[#EAE3DE] gap-4">
            <div>
                <h1 class="font-serif text-3xl font-bold text-[#1C1917]">
                    @if(in_array($selectedCategory, ['Каталог', 'Katalog', 'Catalog'], true))
                        {{ $store->t('catalog.title', 'Все товары и наборы') }}
                    @elseif(in_array($selectedCategory, ['Все товары', 'Barcha mahsulotlar', 'All Products'], true))
                        {{ $store->t('catalog.all', 'Все товары') }}
                    @else
                        {{ $selectedCategory }}
                    @endif
                </h1>
                <p class="text-xs text-[#78716C] mt-1">
                    @if($locale === 'uz')
                        AQSH va Yevropadan to'g'ridan-to'g'ri original kosmetika, butun O'zbekiston bo'ylab yetkazib berish
                    @elseif($locale === 'en')
                        Direct original beauty products from USA and Europe with delivery across Uzbekistan
                    @else
                        Оригинальная косметика напрямую из США и Европы с доставкой по Узбекистану
                    @endif
                    <span class="ml-2 text-[#a8a29e]">· {{ $totalProductsCount }} {{ $store->t('catalog.products', 'товаров') }}</span>
                </p>
            </div>

            <div class="flex flex-wrap items-center gap-3 text-xs">
                <select
                    wire:model.live="selectedBrand"
                    class="px-3 py-2 bg-white border border-[#E3DBD5] rounded-xl focus:outline-none cursor-pointer"
                >
                    <option value="ALL">{{ $store->t('catalog.all_brands', 'Все бренды') }}</option>
                    @foreach($brands as $b)
                        <option value="{{ $b }}">{{ $b }}</option>
                    @endforeach
                </select>

                <select
                    wire:model.live="sortBy"
                    class="px-3 py-2 bg-white border border-[#E3DBD5] rounded-xl focus:outline-none font-medium cursor-pointer"
                >
                    <option value="recommended">{{ $store->t('catalog.sort_recommended', 'Рекомендуемые') }}</option>
                    <option value="newest">{{ $store->t('catalog.sort_newest', 'Сначала новинки') }}</option>
                    <option value="price_asc">{{ $store->t('catalog.sort_price_asc', 'По возрастанию цены') }}</option>
                    <option value="price_desc">{{ $store->t('catalog.sort_price_desc', 'По убыванию цены') }}</option>
                </select>

                <select
                    wire:model.live="pageSize"
                    class="px-2.5 py-2 bg-white border border-[#E3DBD5] rounded-xl focus:outline-none text-[11px] cursor-pointer"
                >
                    <option value="18">18 / {{ $perPageUnit }}</option>
                    <option value="36">36 / {{ $perPageUnit }}</option>
                    <option value="72">72 / {{ $perPageUnit }}</option>
                </select>
            </div>
        </div>

        @if(count($paginatedProducts) === 0)
            <div class="bg-white rounded-3xl p-16 text-center max-w-md mx-auto my-12 border border-[#EAE3DE] space-y-4">
                <div class="w-16 h-16 mx-auto rounded-full bg-[#FAF7F5] flex items-center justify-center text-2xl text-[#8C827A]">
                    <i class="fa-solid fa-magnifying-glass"></i>
                </div>
                <h3 class="font-serif text-xl font-bold">{{ $store->t('catalog.not_found', 'Товары не найдены') }}</h3>
                <p class="text-xs text-[#78716C]">
                    {{ $notFoundHint }}
                </p>
                <button
                    type="button"
                    wire:click="resetFilters"
                    class="px-6 py-2.5 bg-[#1C1917] text-white rounded-xl text-xs font-semibold hover:bg-black transition cursor-pointer"
                >
                    {{ $store->t('catalog.reset_filters', 'Сбросить фильтры') }}
                </button>
            </div>
        @else
            <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-3 xl:grid-cols-4 2xl:grid-cols-5 gap-4 sm:gap-5 lg:gap-6 mt-6">
                @foreach($paginatedProducts as $p)
                    <x-storefront.product-card :product="$p" :store="$store" />
                @endforeach
            </div>

            <!-- Пагинация -->
            @if($totalPages > 1)
                <div class="flex flex-wrap items-center justify-center gap-2 mt-12">
                    <button
                        type="button"
                        wire:click="setPage({{ max(1, $safePage - 1) }})"
                        @disabled($safePage === 1)
                        class="px-3 py-2 rounded-xl border border-[#EAE3DE] bg-white text-xs font-medium disabled:opacity-40 cursor-pointer"
                    >
                        ←
                    </button>
                    @for($pNum = 1; $pNum <= $totalPages; $pNum++)
                        <button
                            type="button"
                            wire:click="setPage({{ $pNum }})"
                            class="w-9 h-9 rounded-xl text-xs font-semibold transition cursor-pointer {{ $safePage === $pNum ? 'bg-[#1C1917] text-white shadow-sm' : 'bg-white border border-[#EAE3DE] text-[#1C1917] hover:border-[#1C1917]' }}"
                        >
                            {{ $pNum }}
                        </button>
                    @endfor
                    <button
                        type="button"
                        wire:click="setPage({{ min($totalPages, $safePage + 1) }})"
                        @disabled($safePage === $totalPages)
                        class="px-3 py-2 rounded-xl border border-[#EAE3DE] bg-white text-xs font-medium disabled:opacity-40 cursor-pointer"
                    >
                        →
                    </button>
                </div>
            @endif
        @endif
    </main>

    <x-storefront.footer :store="$store" />

    <x-storefront.auth-modal
        :isOpen="$isAuthModalOpen"
        :step="$authStep"
        :phone="$authPhone"
        :message="$authModalMessage"
    />
</div>
