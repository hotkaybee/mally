@props(['store' => null])
@php
    $store = $store ?? app(\App\Services\StorefrontService::class);
    $locale = $store->getLocale();

    $ft = match ($locale) {
        'uz' => [
            'care_title' => 'Mijozlarga xizmat ko‘rsatish',
            'care_hours' => 'Har kuni, soat 10:00 dan 20:00 gacha',
            'calls_title' => "Qo'ng'iroqlar",
            'email_title' => 'Taklif va murojaatlar',
            'about_text' => "Yetakchi jahon brendlaridan original kosmetika va premium parvarish. Butun O'zbekiston bo'ylab tezkor yetkazib berish va har bir mijoz uchun g'amxo'r xizmat.",
            'badge_orig' => '100% Original mahsulot',
            'badge_fast' => 'Tezkor yetkazib berish',
            'badge_chat' => "Qo'llab-quvvatlash chati",
            'menu_title' => 'Menyu',
            'menu_home' => 'Bosh sahifa',
            'menu_catalog' => 'Mahsulotlar katalogi',
            'menu_contact' => 'Aloqa formasi',
            'policy_title' => 'Siyosat',
            'policy_return' => 'Qaytarish siyosati',
            'policy_quality' => 'Sifat standartlari',
            'policy_shipping' => 'Yetkazib berish shartlari',
        ],
        'en' => [
            'care_title' => 'Customer Care',
            'care_hours' => 'Every day, from 10:00 to 20:00',
            'calls_title' => 'Phone Support',
            'email_title' => 'Suggestions & Inquiries',
            'about_text' => '100% authentic cosmetics and luxury skincare from leading global brands. Fast delivery across Uzbekistan and dedicated care for every customer.',
            'badge_orig' => '100% Authentic Products',
            'badge_fast' => 'Fast Delivery',
            'badge_chat' => 'Support Chat',
            'menu_title' => 'Menu',
            'menu_home' => 'Home Page',
            'menu_catalog' => 'Product Catalog',
            'menu_contact' => 'Contact Form',
            'policy_title' => 'Policies',
            'policy_return' => 'Return Policy',
            'policy_quality' => 'Quality Standards',
            'policy_shipping' => 'Shipping Terms',
        ],
        default => [
            'care_title' => 'Обслуживание клиентов',
            'care_hours' => 'Каждый день, с 10:00 до 20:00',
            'calls_title' => 'Звонки',
            'email_title' => 'Предложения и жалобы',
            'about_text' => 'Оригинальная косметика и премиальный уход от ведущих мировых брендов. Быстрая доставка по всему Узбекистану и заботливый сервис для каждого клиента.',
            'badge_orig' => '100% Оригинальная продукция',
            'badge_fast' => 'Быстрая доставка',
            'badge_chat' => 'Чат с поддержкой',
            'menu_title' => 'Меню',
            'menu_home' => 'Главная страница',
            'menu_catalog' => 'Каталог товаров',
            'menu_contact' => 'Контакты и обратная связь',
            'policy_title' => 'Политика',
            'policy_return' => 'Политика возврата',
            'policy_quality' => 'Стандарты качества',
            'policy_shipping' => 'Условия доставки',
        ],
    };
@endphp

<footer class="w-full bg-[#FBF3F1] border-t border-[#F0E4E1] pt-12 pb-8 px-4 sm:px-6 lg:px-12 text-[#1C1917] font-sans transition-colors">
    <div class="max-w-7xl mx-auto">
        <!-- 1. Верхние 3 карточки контактов -->
        <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 mb-12">
            <div class="bg-white rounded-2xl p-5 shadow-sm border border-[#EFE5E2] flex items-start gap-3.5 hover:shadow-md transition">
                <div class="w-10 h-10 rounded-xl bg-[#F0FAF7] text-[#0D9488] flex items-center justify-center text-lg flex-shrink-0">
                    <i class="fa-regular fa-comment-dots"></i>
                </div>
                <div>
                    <h4 class="font-bold text-sm text-[#1C1917] leading-snug">{{ $ft['care_title'] }}</h4>
                    <p class="text-xs text-[#78716C] mt-1">{{ $ft['care_hours'] }}</p>
                </div>
            </div>

            <div class="bg-white rounded-2xl p-5 shadow-sm border border-[#EFE5E2] flex items-start gap-3.5 hover:shadow-md transition">
                <div class="w-10 h-10 rounded-xl bg-[#F0FAF7] text-[#0D9488] flex items-center justify-center text-lg flex-shrink-0">
                    <i class="fa-solid fa-phone"></i>
                </div>
                <div>
                    <h4 class="font-bold text-sm text-[#1C1917] leading-snug">{{ $ft['calls_title'] }}</h4>
                    <a
                        href="tel:+998998222880"
                        class="text-xs text-[#78716C] hover:text-[#0D9488] mt-1 block font-mono font-medium transition"
                    >
                        +998 99 822 28 80
                    </a>
                </div>
            </div>

            <div class="bg-white rounded-2xl p-5 shadow-sm border border-[#EFE5E2] flex items-start gap-3.5 hover:shadow-md transition">
                <div class="w-10 h-10 rounded-xl bg-[#F0FAF7] text-[#0D9488] flex items-center justify-center text-lg flex-shrink-0">
                    <i class="fa-regular fa-paper-plane"></i>
                </div>
                <div>
                    <h4 class="font-bold text-sm text-[#1C1917] leading-snug">{{ $ft['email_title'] }}</h4>
                    <a
                        href="mailto:info@mally.uz"
                        class="text-xs text-[#78716C] hover:text-[#0D9488] mt-1 block font-mono transition"
                    >
                        info@mally.uz
                    </a>
                </div>
            </div>
        </div>

        <!-- 2. Средняя часть: О бренде и меню -->
        <div class="grid grid-cols-1 lg:grid-cols-12 gap-10 lg:gap-14 pb-12 border-b border-[#EAE0DC]">
            <div class="lg:col-span-6 space-y-4">
                <a href="/" wire:navigate class="inline-flex flex-col items-start group">
                    <span class="font-serif text-3xl font-bold tracking-wider text-[#1C1917] leading-none group-hover:opacity-80 transition">
                        mally
                    </span>
                    <span class="text-[9px] uppercase tracking-[0.3em] text-[#8C827A] font-medium mt-1">
                        Beauty without limits
                    </span>
                </a>
                <p class="text-xs sm:text-sm text-[#57534E] leading-relaxed max-w-md">
                    {{ $ft['about_text'] }}
                </p>

                <div class="flex flex-wrap items-center gap-2 pt-2">
                    <span class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-full bg-white border border-[#E2D6D2] text-[11px] font-medium text-[#57534E]">
                        <i class="fa-solid fa-certificate text-emerald-600 text-xs"></i>
                        {{ $ft['badge_orig'] }}
                    </span>
                    <span class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-full bg-white border border-[#E2D6D2] text-[11px] font-medium text-[#57534E]">
                        <i class="fa-solid fa-truck-fast text-[#0D9488] text-xs"></i>
                        {{ $ft['badge_fast'] }}
                    </span>
                    <a
                        href="https://t.me/mallyuz"
                        target="_blank"
                        rel="noreferrer"
                        class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-full bg-[#0088cc]/10 hover:bg-[#0088cc]/20 border border-[#0088cc]/20 text-[11px] font-medium text-[#0088cc] transition"
                    >
                        <i class="fa-brands fa-telegram text-xs"></i>
                        {{ $ft['badge_chat'] }}
                    </a>
                </div>
            </div>

            <div class="lg:col-span-6 grid grid-cols-2 sm:grid-cols-2 gap-8 lg:pl-10">
                <div class="space-y-3.5">
                    <h4 class="font-serif text-sm font-bold text-[#0F2636] tracking-wide">{{ $ft['menu_title'] }}</h4>
                    <ul class="space-y-2 text-xs text-[#57534E]">
                        <li>
                            <a href="/" wire:navigate class="hover:text-[#0F2636] transition inline-block">
                                {{ $ft['menu_home'] }}
                            </a>
                        </li>
                        <li>
                            <a href="/catalog" wire:navigate class="hover:text-[#0F2636] transition inline-block">
                                {{ $ft['menu_catalog'] }}
                            </a>
                        </li>
                        <li>
                            <a href="/contact" wire:navigate class="hover:text-[#0F2636] transition inline-block">
                                {{ $ft['menu_contact'] }}
                            </a>
                        </li>
                    </ul>
                </div>

                <div class="space-y-3.5">
                    <h4 class="font-serif text-sm font-bold text-[#0F2636] tracking-wide">{{ $ft['policy_title'] }}</h4>
                    <ul class="space-y-2 text-xs text-[#57534E]">
                        <li>
                            <a href="/returns" wire:navigate class="hover:text-[#0F2636] transition inline-block">
                                {{ $ft['policy_return'] }}
                            </a>
                        </li>
                        <li>
                            <a href="/returns" wire:navigate class="hover:text-[#0F2636] transition inline-block">
                                {{ $ft['policy_quality'] }}
                            </a>
                        </li>
                        <li>
                            <a href="/returns" wire:navigate class="hover:text-[#0F2636] transition inline-block">
                                {{ $ft['policy_shipping'] }}
                            </a>
                        </li>
                    </ul>
                </div>
            </div>
        </div>

        <!-- 3. Нижняя панель -->
        <div class="pt-8 flex flex-col sm:flex-row items-center justify-between gap-4 text-xs text-[#78716C]">
            <p>© 2026 Mally Uzbekistan. {{ $store->t('footer.rights', 'Все права защищены.') }}</p>

            <div class="flex items-center gap-4 text-base">
                <a
                    href="https://www.instagram.com/mally_uz"
                    target="_blank"
                    rel="noreferrer"
                    aria-label="Instagram @mally_uz"
                    class="text-[#1C1917] hover:text-[#0D9488] transition"
                >
                    <i class="fa-brands fa-instagram"></i>
                </a>
                <a
                    href="https://t.me/mallyuz"
                    target="_blank"
                    rel="noreferrer"
                    aria-label="Telegram"
                    class="text-[#1C1917] hover:text-[#0D9488] transition"
                >
                    <i class="fa-brands fa-telegram"></i>
                </a>
            </div>
        </div>
    </div>
</footer>
