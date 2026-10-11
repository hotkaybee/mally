@php
    $fvt = match ($locale) {
        'uz' => [
            'home' => 'Bosh sahifa',
            'breadcrumb' => 'Sevimli mahsulotlarim',
            'title' => 'Sevimlilar',
            'subtitle' => "Siz saqlab qo'ygan mahsulotlar. Ularni istalgan vaqtda tezda buyurtma qilishingiz mumkin.",
            'to_catalog_link' => 'Mahsulotlar katalogiga',
            'empty_title' => "Sevimlilarda hozircha bo'sh",
            'empty_desc' => "Katalogdagi yoqqan mahsulotlar yonidagi yurakcha belgisini bosing — ularni yo'qotib qo'ymaysiz va mavjudligini kuzatib borasiz!",
            'empty_btn' => "Xarid qilishga o'tish",
            'remove_fav' => "Sevimlilardan o'chirish",
            'add_to_cart' => "Savatga qo'shish",
        ],
        'en' => [
            'home' => 'Home',
            'breadcrumb' => 'My Favorite Products',
            'title' => 'Favorites',
            'subtitle' => 'Products you have saved. Available for quick ordering anytime.',
            'to_catalog_link' => 'Browse Catalog',
            'empty_title' => 'Your favorites list is empty',
            'empty_desc' => 'Tap the heart icon on any product in the catalog to save your favorite items and track their availability!',
            'empty_btn' => 'Start Shopping',
            'remove_fav' => 'Remove from favorites',
            'add_to_cart' => 'Add to Cart',
        ],
        default => [
            'home' => 'Главная',
            'breadcrumb' => 'Мои любимые товары',
            'title' => 'Избранное',
            'subtitle' => 'Товары, которые вы сохранили. Они доступны для быстрого заказа в любое время.',
            'to_catalog_link' => 'В каталог товаров',
            'empty_title' => 'В избранном пока пусто',
            'empty_desc' => 'Нажимайте на сердечко рядом с понравившимися товарами в каталоге, чтобы не потерять их и следить за наличием!',
            'empty_btn' => 'Перейти к покупкам',
            'remove_fav' => 'Удалить из избранного',
            'add_to_cart' => 'В корзину',
        ],
    };
@endphp

<div class="min-h-screen bg-[#FCECEC] flex flex-col font-sans text-[#1C1917]">
    <x-storefront.header />

    <main class="max-w-7xl mx-auto px-4 py-8 w-full flex-1">
        <!-- Хлебные крошки -->
        <div class="text-xs text-[#8C827A] flex items-center gap-2 mb-6">
            <a href="{{ route('home') }}" wire:navigate class="hover:text-black transition">
                {{ $fvt['home'] }}
            </a>
            <span>/</span>
            <span class="text-[#1C1917] font-medium">{{ $fvt['breadcrumb'] }}</span>
        </div>

        <!-- Заголовок -->
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 mb-8 pb-4 border-b border-[#EAE3DE]">
            <div>
                <h1 class="font-serif text-2xl sm:text-3xl font-bold text-[#1C1917] flex items-center gap-3">
                    <span>{{ $fvt['title'] }}</span>
                    <span class="text-xs px-3 py-1 bg-white border border-[#EAE3DE] rounded-full font-sans font-semibold text-[#8C827A]">
                        {{ count($favoriteProducts) }}
                    </span>
                </h1>
                <p class="text-xs text-[#78716C] mt-1">
                    {{ $fvt['subtitle'] }}
                </p>
            </div>

            <a
                href="{{ route('catalog') }}"
                wire:navigate
                class="inline-flex items-center gap-2 text-xs font-semibold text-[#1C1917] hover:underline"
            >
                <span>{{ $fvt['to_catalog_link'] }}</span>
                <i class="fa-solid fa-arrow-right text-[10px]"></i>
            </a>
        </div>

        <!-- Список избранных товаров -->
        @if(count($favoriteProducts) === 0)
            <div class="bg-white rounded-3xl p-12 text-center max-w-lg mx-auto border border-[#EAE3DE] shadow-xs my-8 space-y-4">
                <div class="w-16 h-16 mx-auto rounded-full bg-rose-50 text-rose-500 flex items-center justify-center text-2xl">
                    <i class="fa-regular fa-heart"></i>
                </div>
                <h2 class="font-serif text-xl font-bold text-[#1C1917]">{{ $fvt['empty_title'] }}</h2>
                <p class="text-xs text-[#78716C] leading-relaxed">
                    {{ $fvt['empty_desc'] }}
                </p>
                <div class="pt-2">
                    <a
                        href="{{ route('catalog') }}"
                        wire:navigate
                        class="inline-block px-6 py-3.5 bg-[#1C1917] text-white rounded-2xl text-xs font-semibold uppercase tracking-wider hover:bg-black transition shadow-sm"
                    >
                        {{ $fvt['empty_btn'] }}
                    </a>
                </div>
            </div>
        @else
            <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-4 gap-4 sm:gap-6">
                @foreach($favoriteProducts as $item)
                    @php
                        $prodKey = (string) ($item['sku'] ?? $item['id']);
                        $productUrl = '/products/'.rawurlencode($prodKey);
                    @endphp
                    <div
                        wire:key="fav-item-{{ $prodKey }}"
                        class="group relative flex flex-col bg-white rounded-2xl p-3 border border-[#EAE3DE] hover:border-[#D5CCC7] hover:shadow-lg transition-all duration-300"
                    >
                        <!-- Изображение -->
                        <div class="relative aspect-square w-full rounded-xl overflow-hidden bg-[#FAF7F5]">
                            <a href="{{ $productUrl }}" wire:navigate class="block w-full h-full">
                                <img
                                    src="{{ $item['image'] ?? 'https://findy.uz/cdn/shop/files/sample.jpg' }}"
                                    alt="{{ $item['title'] }}"
                                    class="w-full h-full object-cover object-center group-hover:scale-105 transition-transform duration-500"
                                />
                            </a>

                            <!-- Кнопка удаления из избранного -->
                            <button
                                type="button"
                                wire:click="toggleFavorite('{{ addslashes($prodKey) }}')"
                                class="absolute top-2.5 right-2.5 z-10 w-8 h-8 rounded-full bg-white/90 backdrop-blur-sm text-rose-500 hover:bg-rose-50 flex items-center justify-center transition shadow-sm border border-rose-100 cursor-pointer"
                                title="{{ $fvt['remove_fav'] }}"
                            >
                                <i class="fa-solid fa-heart text-xs"></i>
                            </button>
                        </div>

                        <!-- Инфо -->
                        <div class="flex flex-col flex-1 mt-3">
                            <div class="text-[10px] font-medium uppercase tracking-wider text-[#8C827A]">
                                {{ $item['brand'] ?: 'Mally Collection' }}
                            </div>

                            <a href="{{ $productUrl }}" wire:navigate>
                                <h3 class="text-xs sm:text-sm font-medium text-[#1C1917] group-hover:text-[#57534E] transition-colors mt-1 line-clamp-2 min-h-[38px] leading-snug">
                                    {{ $item['title'] }}
                                </h3>
                            </a>

                            <div class="mt-2 flex items-baseline gap-2">
                                <span class="text-sm sm:text-base font-bold text-[#1C1917]">
                                    {{ $this->formatPrice($item['price']) }}
                                </span>
                                @if(!empty($item['oldPrice']) && $item['oldPrice'] > $item['price'])
                                    <span class="text-[11px] text-[#A8A29E] line-through">
                                        {{ $this->formatPrice($item['oldPrice']) }}
                                    </span>
                                @endif
                            </div>

                            <!-- Добавить в корзину -->
                            <div class="mt-4 pt-1">
                                <button
                                    type="button"
                                    wire:click="addToCart('{{ addslashes($prodKey) }}')"
                                    class="w-full py-2.5 px-3 bg-[#1C1917] hover:bg-black text-white text-xs font-semibold rounded-xl transition shadow-xs flex items-center justify-center gap-1.5 cursor-pointer"
                                >
                                    <i class="fa-solid fa-bag-shopping text-xs"></i>
                                    <span>{{ $fvt['add_to_cart'] }}</span>
                                </button>
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
        @endif
    </main>

    <x-storefront.footer />
    <x-storefront.auth-modal />
</div>
