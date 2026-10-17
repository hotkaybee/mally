@props([
    'store' => null,
    'locale' => null,
    'currency' => null,
    'exchangeRate' => null,
    'rateSource' => null,
    'cartCount' => null,
    'favoritesCount' => null,
    'currentCustomer' => null,
    'headerCategories' => null,
    'selectedCategory' => 'Все товары',
    'liveCatalogMode' => false,
])

@php
    $store = $store ?? app(\App\Services\StorefrontService::class);
    $rateData = $store->getExchangeRateData();
    $locale = $locale ?? $store->getLocale();
    $currency = $currency ?? $store->getCurrency();
    $exchangeRate = $exchangeRate ?? $rateData['rate'];
    $rateSource = $rateSource ?? $rateData['source'];
    $cartCount = $cartCount ?? $store->getCartCount();
    $favoritesCount = $favoritesCount ?? $store->getFavoritesCount();
    $currentCustomer = $currentCustomer ?? $store->getCurrentCustomer();
    $headerCategories = $headerCategories ?? $store->getHeaderCategories();

    $allCategoriesList = array_values(array_filter(
        $headerCategories,
        fn ($c) => ! in_array($c, ['Каталог', 'Katalog', 'Catalog', 'Все товары', 'Barcha mahsulotlar', 'All Products'], true)
    ));
    $halfCount = max(1, (int) ceil(count($allCategoriesList) / 2));
    $categoryColumns = array_chunk($allCategoriesList, $halfCount);
    $isContactPage = request()->is('contact*');
    $isCatalogPage = request()->is('catalog*');
    $isAllProductsActive = $isCatalogPage && in_array($selectedCategory, ['Каталог', 'Katalog', 'Catalog', 'Все товары', 'Barcha mahsulotlar', 'All Products'], true);
    $isCategorySelected = $isCatalogPage && ! $isAllProductsActive && $selectedCategory !== '';

    $sumUnit = match ($locale) {
        'uz' => "so'm",
        'en' => 'UZS',
        default => 'сум',
    };
    $authenticTagline = match ($locale) {
        'uz' => 'AQSHdan 100% original kosmetika',
        'en' => '100% Authentic USA Beauty',
        default => '100% Оригинальная косметика из США',
    };
@endphp

<header
    x-data="{
        categoriesOpen: false
    }"
    class="w-full bg-white border-b border-[#F2D8D0] relative z-40"
>
    <!-- 1. Верхняя полоса -->
    <div class="bg-[#1C1917] text-[#FAF7F5] py-2 px-3 sm:px-4 text-xs font-medium">
        <div class="max-w-7xl mx-auto flex justify-between items-center gap-2">
            <!-- В мобильной версии вместо инфы о доставке показываем курс доллара -->
            <div class="flex md:hidden items-center gap-1.5 text-[10px] text-[#D5CCC7] truncate">
                <span class="w-1.5 h-1.5 rounded-full flex-shrink-0 {{ ($exchangeRate ?? 0) > 0 ? 'bg-emerald-400 animate-pulse' : 'bg-amber-400' }}"></span>
                <span class="font-mono font-semibold text-white">
                    1 USD = {{ ($exchangeRate ?? 0) > 0 ? number_format(round($exchangeRate), 0, '.', ' ').' '.$sumUnit : '-' }}
                </span>
            </div>

            <!-- На десктопе слева информация о доставке -->
            <div class="hidden md:flex items-center gap-2 truncate pr-2">
                <span class="inline-block text-amber-400">
                    <i class="fa-solid fa-truck-fast text-xs"></i>
                </span>
                <span class="truncate">{{ $store->t('nav.shipping_banner', 'Отправка через Яндекс Go · Доставка оплачивается покупателем по тарифу') }}</span>
            </div>

            <div class="flex items-center gap-1.5 sm:gap-3 flex-shrink-0">
                <!-- Переключатель языка RU / UZ / EN -->
                <div class="flex items-center bg-white/10 rounded-full p-0.5 text-[10px] font-semibold">
                    <button
                        type="button"
                        wire:click="switchLanguage('ru')"
                        class="px-2 py-0.5 rounded-full transition cursor-pointer {{ $locale === 'ru' ? 'bg-white text-black' : 'text-[#D5CCC7] hover:text-white' }}"
                        title="Русский язык"
                    >
                        RU
                    </button>
                    <button
                        type="button"
                        wire:click="switchLanguage('uz')"
                        class="px-2 py-0.5 rounded-full transition cursor-pointer {{ $locale === 'uz' ? 'bg-white text-black' : 'text-[#D5CCC7] hover:text-white' }}"
                        title="O'zbek tili"
                    >
                        UZ
                    </button>
                    <button
                        type="button"
                        wire:click="switchLanguage('en')"
                        class="px-2 py-0.5 rounded-full transition cursor-pointer {{ $locale === 'en' ? 'bg-white text-black' : 'text-[#D5CCC7] hover:text-white' }}"
                        title="English"
                    >
                        EN
                    </button>
                </div>

                <!-- Живой курс доллара ЦБ РУз на десктопе -->
                <span class="hidden md:inline text-white/20">|</span>
                <div class="hidden md:flex items-center gap-1.5 text-[10px] text-[#D5CCC7]" title="{{ $rateSource }}">
                    <span class="w-1.5 h-1.5 rounded-full flex-shrink-0 {{ ($exchangeRate ?? 0) > 0 ? 'bg-emerald-400 animate-pulse' : 'bg-amber-400' }}"></span>
                    <span>1 USD = {{ ($exchangeRate ?? 0) > 0 ? number_format(round($exchangeRate), 0, '.', ' ').' '.$sumUnit : '-' }}</span>
                </div>
                <span class="text-white/20">|</span>

                <!-- Переключатель валют UZS / USD -->
                <div class="flex items-center bg-white/10 rounded-full p-0.5 text-[10px] font-semibold">
                    <button
                        type="button"
                        wire:click="switchCurrency('UZS')"
                        class="px-2 py-0.5 rounded-full transition cursor-pointer {{ $currency === 'UZS' ? 'bg-white text-black' : 'text-[#D5CCC7] hover:text-white' }}"
                    >
                        UZS
                    </button>
                    <button
                        type="button"
                        wire:click="switchCurrency('USD')"
                        class="px-2 py-0.5 rounded-full transition cursor-pointer {{ $currency === 'USD' ? 'bg-white text-black' : 'text-[#D5CCC7] hover:text-white' }}"
                    >
                        USD
                    </button>
                </div>

                <!-- Иконка Telegram -->
                <a
                    href="https://t.me/mallyuz"
                    target="_blank"
                    rel="noreferrer"
                    class="w-6 h-6 rounded-full bg-[#0088cc] hover:bg-[#0077b3] flex items-center justify-center text-white transition shadow-sm"
                    title="{{ $store->t('nav.support', 'Написать администратору @mallyuz') }}"
                >
                    <i class="fa-brands fa-telegram text-xs"></i>
                </a>
            </div>
        </div>
    </div>

    <!-- 2. Основная шапка: Логотип строго по центру в десктопной и мобильной версии -->
    <div class="max-w-7xl mx-auto px-4 py-3.5 sm:py-5 grid grid-cols-3 items-center">
        <!-- Левая колонка (для идеальной центровки логотипа) -->
        <div class="flex items-center">
            <div class="hidden md:inline-flex items-center gap-2 px-3 py-1.5 rounded-full bg-[#FAF8F6] border border-[#EAE3DE] text-[11px] font-medium text-[#57534E]">
                <i class="fa-solid fa-certificate text-emerald-600 text-xs"></i>
                <span>{{ $authenticTagline }}</span>
            </div>
        </div>

        <!-- Логотип строго по центру -->
        <div class="flex justify-center">
            <a href="/" wire:navigate class="flex flex-col items-center text-center group">
                <div class="font-serif text-3xl sm:text-4xl font-bold tracking-wider text-[#1C1917] leading-none group-hover:opacity-85 transition">
                    mally
                </div>
                <span class="text-[8px] sm:text-[9px] uppercase tracking-[0.22em] sm:tracking-[0.3em] text-[#8C827A] font-medium mt-1 whitespace-nowrap">
                    Beauty without limits
                </span>
            </a>
        </div>

        <!-- Правый блок: Профиль, Избранное и Корзина -->
        <div class="flex items-center justify-end gap-1 sm:gap-3">
            @if($currentCustomer)
                <a
                    href="/profile"
                    wire:navigate
                    class="flex items-center gap-2 p-1.5 sm:px-3 sm:py-1.5 rounded-full hover:bg-[#FAF8F6] text-[#1C1917] border border-transparent hover:border-[#EAE3DE] transition cursor-pointer"
                    title="{{ $store->t('nav.profile', 'Личный кабинет') }}"
                >
                    <div class="w-7 h-7 rounded-full bg-[#1C1917] text-white flex items-center justify-center text-xs font-serif font-bold">
                        {{ mb_strtoupper(mb_substr($currentCustomer->first_name ?: 'M', 0, 1)) }}
                    </div>
                    <span class="hidden sm:inline text-xs font-semibold max-w-[100px] truncate">
                        {{ $currentCustomer->first_name ?: $store->t('nav.profile', 'Профиль') }}
                    </span>
                </a>
            @else
                <button
                    type="button"
                    wire:click="openAuthModal('{{ $store->t('profile.need_login', 'Войдите для доступа к профилю и оформлению заказов') }}')"
                    class="flex items-center gap-1.5 p-2 rounded-full hover:bg-[#FAF8F6] text-[#1C1917] transition cursor-pointer"
                    title="{{ $store->t('nav.login', 'Войти в личный кабинет') }}"
                >
                    <i class="fa-regular fa-user text-base"></i>
                    <span class="hidden sm:inline text-xs font-medium">{{ $store->t('nav.login', 'Войти') }}</span>
                </button>
            @endif

            <!-- Избранное (на сервере) -->
            <a
                href="/favorites"
                wire:navigate
                class="relative flex items-center justify-center p-2 rounded-full hover:bg-[#FAF8F6] text-[#1C1917] transition cursor-pointer"
                title="{{ $store->t('nav.favorites', 'Избранные товары') }}"
            >
                <i class="fa-regular fa-heart text-lg"></i>
                @if($favoritesCount > 0)
                    <span class="absolute top-0 right-0 bg-[#E11D48] text-white text-[10px] w-4 h-4 rounded-full flex items-center justify-center font-bold">
                        {{ $favoritesCount }}
                    </span>
                @endif
            </a>

            <!-- Корзина (на сервере) -->
            <a
                href="/cart"
                wire:navigate
                class="relative flex items-center justify-center p-2 rounded-full hover:bg-[#FAF8F6] text-[#1C1917] transition cursor-pointer"
                title="{{ $store->t('nav.cart', 'Перейти в корзину') }}"
            >
                <i class="fa-solid fa-bag-shopping text-lg"></i>
                @if($cartCount > 0)
                    <span class="absolute top-0 right-0 bg-[#E11D48] text-white text-[10px] w-4 h-4 rounded-full flex items-center justify-center font-bold">
                        {{ $cartCount }}
                    </span>
                @endif
            </a>
        </div>
    </div>

    <!-- 3. Горизонтальная навигация: 3 пункта (Все товары | Категории в 2 колонки | Контактная форма) -->
    <nav class="bg-[#1C1917] text-[#FAF7F5] relative z-30">
        <div class="max-w-7xl mx-auto px-2 sm:px-4 flex items-center justify-center gap-2 sm:gap-12 py-2.5 text-[11px] sm:text-sm font-medium">
            <!-- Пункт 1: Все товары -->
            @if($liveCatalogMode)
                <button
                    type="button"
                    wire:click="selectCategory('Каталог')"
                    @click="categoriesOpen = false"
                    class="inline-flex items-center gap-1.5 sm:gap-2 py-1 px-2.5 sm:px-3 rounded-full transition cursor-pointer whitespace-nowrap {{ $isAllProductsActive ? 'bg-white/15 text-white font-bold' : 'text-[#D5CCC7] hover:text-white' }}"
                >
                    <i class="fa-solid fa-border-all text-[10px] sm:text-[11px] text-amber-400"></i>
                    <span>{{ $store->t('nav.all_products', 'Все товары') }}</span>
                </button>
            @else
                <a
                    href="/catalog"
                    wire:navigate
                    class="inline-flex items-center gap-1.5 sm:gap-2 py-1 px-2.5 sm:px-3 rounded-full transition cursor-pointer whitespace-nowrap {{ $isAllProductsActive ? 'bg-white/15 text-white font-bold' : 'text-[#D5CCC7] hover:text-white' }}"
                >
                    <i class="fa-solid fa-border-all text-[10px] sm:text-[11px] text-amber-400"></i>
                    <span>{{ $store->t('nav.all_products', 'Все товары') }}</span>
                </a>
            @endif

            <!-- Пункт 2: Категории (выпадающее меню в 2 списка / 2 колонки при наведении или клике) -->
            <div
                class="relative"
                @mouseenter="categoriesOpen = true"
                @mouseleave="categoriesOpen = false"
                @click.outside="categoriesOpen = false"
            >
                <button
                    type="button"
                    @click="categoriesOpen = !categoriesOpen"
                    class="inline-flex items-center gap-1.5 sm:gap-2 py-1 px-2.5 sm:px-3.5 rounded-full transition cursor-pointer whitespace-nowrap {{ ($isCategorySelected || false) ? 'bg-white/15 text-white font-bold' : 'text-[#D5CCC7] hover:text-white' }}"
                    :class="categoriesOpen ? 'bg-white/15 text-white' : ''"
                >
                    <i class="fa-solid fa-layer-group text-[10px] sm:text-[11px] text-rose-400"></i>
                    <span>{{ $store->t('nav.categories', 'Категории') }}</span>
                    @if($isCategorySelected)
                        <span class="hidden sm:inline-block px-2 py-0.5 rounded-full bg-rose-500/20 border border-rose-400/30 text-rose-200 text-[10px] font-semibold">
                            {{ $selectedCategory }}
                        </span>
                    @endif
                    <i
                        class="fa-solid fa-chevron-down text-[9px] sm:text-[10px] transition-transform duration-200"
                        :class="categoriesOpen ? 'rotate-180 text-white' : 'text-[#A8A29E]'"
                    ></i>
                </button>

                <!-- Выпадающее окно с 2 списками (2 колонки) всех категорий -->
                <div
                    x-show="categoriesOpen"
                    x-cloak
                    x-transition:enter="transition ease-out duration-150"
                    x-transition:enter-start="opacity-0 translate-y-1"
                    x-transition:enter-end="opacity-100 translate-y-0"
                    x-transition:leave="transition ease-in duration-100"
                    x-transition:leave-start="opacity-100 translate-y-0"
                    x-transition:leave-end="opacity-0 translate-y-1"
                    class="fixed sm:absolute left-1/2 -translate-x-1/2 top-[132px] sm:top-full pt-2.5 w-[94vw] max-w-xl z-50"
                >
                    <div class="bg-white text-[#1C1917] rounded-3xl shadow-2xl border border-[#EAE3DE] overflow-hidden">
                        <!-- Шапка выпадающего меню -->
                        <div class="px-5 py-3.5 bg-[#FAF8F6] border-b border-[#EAE3DE] flex items-center justify-between">
                            <div class="flex items-center gap-2">
                                <span class="w-2 h-2 rounded-full bg-[#E11D48]"></span>
                                <span class="text-xs font-serif font-bold uppercase tracking-wider text-[#1C1917]">
                                    {{ $store->t('nav.categories', 'Категории') }}
                                </span>
                                <span class="text-[10px] px-2 py-0.5 rounded-full bg-[#F2ECE8] text-[#78716C] font-bold">
                                    {{ count($allCategoriesList) }}
                                </span>
                            </div>

                            @if($liveCatalogMode)
                                <button
                                    type="button"
                                    wire:click="selectCategory('Каталог')"
                                    @click="categoriesOpen = false"
                                    class="text-[11px] font-bold text-[#0D9488] hover:underline cursor-pointer"
                                >
                                    {{ $store->t('nav.all_products', 'Все товары') }} →
                                </button>
                            @else
                                <a
                                    href="/catalog"
                                    wire:navigate
                                    @click="categoriesOpen = false"
                                    class="text-[11px] font-bold text-[#0D9488] hover:underline cursor-pointer"
                                >
                                    {{ $store->t('nav.all_products', 'Все товары') }} →
                                </a>
                            @endif
                        </div>

                        <!-- 2 списка (2 колонки) со всеми категориями -->
                        <div class="p-3.5 sm:p-5 grid grid-cols-2 gap-2 sm:gap-6 sm:divide-x sm:divide-[#F2ECE8] max-h-[65vh] overflow-y-auto">
                            @foreach($categoryColumns as $colIndex => $colCategories)
                                <div class="space-y-1 {{ $colIndex === 1 ? 'sm:pl-4' : '' }}">
                                    @foreach($colCategories as $cat)
                                        @php
                                            $isSelected = $selectedCategory === $cat && $isCatalogPage;
                                            $isDiscount = in_array($cat, ['Скидки', 'Chegirmalar', 'Discounts'], true);
                                        @endphp
                                        @if($liveCatalogMode)
                                            <button
                                                type="button"
                                                wire:click="selectCategory('{{ addslashes($cat) }}')"
                                                @click="categoriesOpen = false"
                                                class="w-full text-left px-2.5 sm:px-3 py-2 rounded-xl text-[11px] sm:text-xs font-medium flex items-center justify-between gap-2 transition cursor-pointer {{ $isSelected ? 'bg-[#1C1917] text-white font-bold shadow-xs' : ($isDiscount ? 'text-[#E11D48] font-bold hover:bg-rose-50' : 'text-[#1C1917] hover:bg-[#FAF8F6]') }}"
                                            >
                                                <span class="truncate">{{ $cat }}</span>
                                                <i class="fa-solid fa-angle-right text-[10px] {{ $isSelected ? 'text-white' : 'text-[#C7BEB8]' }}"></i>
                                            </button>
                                        @else
                                            <a
                                                href="/catalog?category={{ urlencode($cat) }}"
                                                wire:navigate
                                                @click="categoriesOpen = false"
                                                class="w-full text-left px-2.5 sm:px-3 py-2 rounded-xl text-[11px] sm:text-xs font-medium flex items-center justify-between gap-2 transition cursor-pointer {{ $isSelected ? 'bg-[#1C1917] text-white font-bold shadow-xs' : ($isDiscount ? 'text-[#E11D48] font-bold hover:bg-rose-50' : 'text-[#1C1917] hover:bg-[#FAF8F6]') }}"
                                            >
                                                <span class="truncate">{{ $cat }}</span>
                                                <i class="fa-solid fa-angle-right text-[10px] {{ $isSelected ? 'text-white' : 'text-[#C7BEB8]' }}"></i>
                                            </a>
                                        @endif
                                    @endforeach
                                </div>
                            @endforeach
                        </div>
                    </div>
                </div>
            </div>

            <!-- Пункт 3: Контактная форма -->
            <a
                href="/contact"
                wire:navigate
                class="inline-flex items-center gap-1.5 sm:gap-2 py-1 px-2.5 sm:px-3 rounded-full transition cursor-pointer whitespace-nowrap {{ $isContactPage ? 'bg-white/15 text-white font-bold' : 'text-[#D5CCC7] hover:text-white' }}"
            >
                <i class="fa-regular fa-paper-plane text-[10px] sm:text-[11px] text-emerald-400"></i>
                <span>{{ $store->t('nav.contact', 'Контактная форма') }}</span>
            </a>
        </div>
    </nav>
</header>
