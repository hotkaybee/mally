@php
    $homeTexts = match ($locale) {
        'uz' => [
            'hero_badge' => "AQSHdan O'zbekistonga original kosmetikaning to'g'ridan-to'g'ri importi",
            'hero_title_1' => 'Chegarasiz go‘zallik',
            'hero_title_2' => 'bilan',
            'hero_desc' => "Mally — jahon go'zallik brendlariga ishonchli yo'lboshchingiz. Biz Rhode, Hourglass, Rare Beauty, Rituals va Sol de Janeiro original kosmetikasini AQSHdan to'g'ridan-to'g'ri olib kelamiz. Xorijiy kartalar, vositachilar va bojxona qiyinchiliklarini unuting — eng mashhur bestsellerlar allaqachon Toshkentda.",
            'btn_catalog' => "Katalogga o'tish",
            'btn_us_order' => '✈️ AQSHdan buyurtma (15–20 kun)',
            'stat_1_val' => '100%',
            'stat_1_lbl' => 'AQSH va Yevropadan original',
            'stat_3_val' => 'Buyurtma kunida',
            'stat_3_lbl' => "Toshkent bo'ylab yetkazish",
            'stat_4_val' => '1–3 kun',
            'stat_4_lbl' => "O'zbekiston bo'ylab ekspress-pochta",
            'why_badge' => 'Sifat standartlari',
            'why_title' => "Nega O'zbekiston qizlari Mally ni tanlaydilar",
            'why_subtitle' => "Biz har bir flakonning haqiqiyligini kafolatlaymiz va yuqori darajadagi xizmatni taqdim etamiz.",
            'why_1_title' => 'AQSHdan rasmiy xarid',
            'why_1_desc' => "Faqat brendlarning rasmiy Amerika saytlaridan va Sephora USA do'konlaridan xarid qilamiz. Invoyslarni taqdim etamiz va batch-kodlarni tekshirishga yordam beramiz.",
            'why_2_title' => 'Omborda mavjud',
            'why_2_desc' => "Haftalab posilka kutishingiz shart emas: Rhode, Hourglass va Rituals top pozitsiyalari allaqachon Toshkentda va bir necha soat ichida jo'natishga tayyor.",
            'why_3_title' => "Sovg'a estetikasi",
            'why_3_desc' => "Har bir buyurtma atlas lentali kraft qutiga ehtiyotkorlik bilan qadoqlanadi va sovg'a sifatida probniklar qo'shiladi.",
            'buyer_badge' => 'Mally Bayer-xizmati',
            'buyer_title' => 'Kerakli mahsulotni topmadingizmi? AQSHdan 15–20 kunda olib kelamiz',
            'buyer_subtitle' => "Agar kerakli lab bo'yog'i rangi yoki limitlangan to'plam Toshkentdagi omborda bo'lmasa, uni siz uchun maxsus buyurtma qilamiz.",
            'step_1_title' => "So'rov yuboring",
            'step_1_desc' => "Mahsulot rasmini yoki havolasini Telegram orqali @mallyuz administratoriga yuboring.",
            'step_2_title' => 'Yakuniy hisob-kitob',
            'step_2_desc' => "Yashirin qo'shimcha to'lovlarsiz yakuniy narxni so'm yoki dollarda belgilaymiz.",
            'step_3_title' => "Xarid va sug'urta",
            'step_3_desc' => "Sephora USA do'konidan xarid qilamiz va iqlim-nazoratli tezkor aviapochta orqali jo'natamiz.",
            'step_4_title' => "Qo'lga qabul qilish",
            'step_4_desc' => "15–20 kun ichida kuryer buyurtmani O'zbekistonda to'g'ridan-to'g'ri qo'lingizga yetkazib beradi.",
            'buyer_btn' => 'Telegram orqali bayer-buyurtma qoldirish',
            'brands_badge' => 'Mally brendlari',
            'brands_title' => 'Siz uchun mavjud jahon brendlari',
            'brand_country' => 'Mamlakat:',
            'brand_view' => 'mahsulotlarini ko‘rish →',
            'reviews_badge' => 'Mijozlar fikri',
            'reviews_title' => 'Xaridorlarimiz biz haqimizda nima deyishadi',
            'reviews_verified' => 'Tasdiqlangan sharh',
            'faq_badge' => 'Savollar va javoblar',
            'faq_title' => "Ko'p beriladigan savollar",
        ],
        'en' => [
            'hero_badge' => 'Direct Import of Authentic Cosmetics from the USA to Uzbekistan',
            'hero_title_1' => 'Beauty without limits with',
            'hero_title_2' => '',
            'hero_desc' => 'Mally is your trusted gateway to global beauty brands. We bring 100% authentic Rhode, Hourglass, Rare Beauty, Rituals, and Sol de Janeiro directly from the USA. Forget about foreign card issues, middlemen, and customs — iconic bestsellers are already in stock in Tashkent.',
            'btn_catalog' => 'Browse Catalog',
            'btn_us_order' => '✈️ Custom US Order (15–20 days)',
            'stat_1_val' => '100%',
            'stat_1_lbl' => 'Authentic from USA & Europe',
            'stat_3_val' => 'Same Day',
            'stat_3_lbl' => 'Delivery in Tashkent',
            'stat_4_val' => '1–3 Days',
            'stat_4_lbl' => 'Express Shipping Across UZ',
            'why_badge' => 'Quality Standards',
            'why_title' => 'Why Women in Uzbekistan Choose Mally',
            'why_subtitle' => 'We guarantee the authenticity of every bottle and provide first-class customer care.',
            'why_1_title' => 'Official US Purchasing',
            'why_1_desc' => 'We purchase exclusively from official US brand websites and Sephora USA. We provide invoices and help verify batch codes.',
            'why_2_title' => 'In Stock in Tashkent',
            'why_2_desc' => 'No need to wait weeks for a parcel: top products from Rhode, Hourglass, and Rituals are already in Tashkent and ready to ship within hours.',
            'why_3_title' => 'Gift-Ready Aesthetics',
            'why_3_desc' => 'Every order is carefully packed in a signature craft box with a satin ribbon and complimentary samples — perfect for gifting.',
            'buyer_badge' => 'Mally Personal Shopper',
            'buyer_title' => "Didn't find the item you need? We'll bring it from the USA in 15–20 days",
            'buyer_subtitle' => 'If a specific lipstick shade or limited-edition set is not in stock in Tashkent, we will order it specially for you.',
            'step_1_title' => 'Send a Request',
            'step_1_desc' => 'Send a photo or product link to our administrator on Telegram @mallyuz.',
            'step_2_title' => 'All-Inclusive Quote',
            'step_2_desc' => 'We lock in the final price in UZS or USD with zero hidden weight fees.',
            'step_3_title' => 'Purchase & Insurance',
            'step_3_desc' => 'We buy it at Sephora USA and ship via climate-controlled express airmail.',
            'step_4_title' => 'Doorstep Delivery',
            'step_4_desc' => 'Within 15–20 days, our courier delivers the parcel straight to your hands in Uzbekistan.',
            'buyer_btn' => 'Request a Custom US Order on Telegram',
            'brands_badge' => 'Mally Brands',
            'brands_title' => 'World-Renowned Brands Available for You',
            'brand_country' => 'Origin:',
            'brand_view' => 'Explore products →',
            'reviews_badge' => 'Customer Reviews',
            'reviews_title' => 'What Our Customers Say About Us',
            'reviews_verified' => 'Verified Review',
            'faq_badge' => 'Questions & Answers',
            'faq_title' => 'Frequently Asked Questions',
        ],
        default => [
            'hero_badge' => 'Прямой импорт оригинальной косметики из США в Узбекистан',
            'hero_title_1' => 'Красота без границ с',
            'hero_title_2' => '',
            'hero_desc' => 'Mally — ваш надежный проводник к мировым брендам красоты. Мы привозим оригинальную косметику Rhode, Hourglass, Rare Beauty, Rituals и Sol de Janeiro напрямую из США. Забудьте о сложностях с иностранными картами, посредниками и таможней — культовые бестселлеры уже в Ташкенте.',
            'btn_catalog' => 'Перейти в каталог',
            'btn_us_order' => '✈️ Заказ из США под ключ (15–20 дн.)',
            'stat_1_val' => '100%',
            'stat_1_lbl' => 'Оригинал из США и Европы',
            'stat_3_val' => 'В день заказа',
            'stat_3_lbl' => 'Доставка по Ташкенту',
            'stat_4_val' => '1–3 дня',
            'stat_4_lbl' => 'Экспресс-доставка по РУз',
            'why_badge' => 'Стандарты качества',
            'why_title' => 'Почему девушки Узбекистана выбирают Mally',
            'why_subtitle' => 'Мы гарантируем подлинность каждого флакона и обеспечиваем первоклассный сервис.',
            'why_1_title' => 'Официальный выкуп в США',
            'why_1_desc' => 'Выкупаем только на официальных американских сайтах брендов и в Sephora USA. Предоставляем инвойсы и помогаем проверить батч-коды.',
            'why_2_title' => 'В наличии на складе',
            'why_2_desc' => 'Вам не нужно ждать посылку неделями: топовые позиции Rhode, Hourglass и Rituals уже находятся в Ташкенте и готовы к отправке за пару часов.',
            'why_3_title' => 'Подарочная эстетика',
            'why_3_desc' => 'Каждый заказ бережно упаковывается в крафтовую коробку с атласной лентой и пробниками в подарок — идеально для подарка себе или близким.',
            'buyer_badge' => 'Байер-сервис Mally',
            'buyer_title' => 'Не нашли нужный товар? Привезем из США за 15–20 дней',
            'buyer_subtitle' => 'Если нужного оттенка помады или лимитированного набора нет на складе в Ташкенте, мы закажем его специально для вас.',
            'step_1_title' => 'Отправьте запрос',
            'step_1_desc' => 'Пришлите фото или ссылку на товар администратору в Telegram @mallyuz.',
            'step_2_title' => 'Расчет под ключ',
            'step_2_desc' => 'Фиксируем итоговую стоимость в сумах или долларах без скрытых доплат за вес.',
            'step_3_title' => 'Выкуп и страховка',
            'step_3_desc' => 'Выкупаем в Sephora USA и отправляем ускоренной авиапочтой с климат-контролем.',
            'step_4_title' => 'Получение в руки',
            'step_4_desc' => 'Через 15–20 дней курьер доставляет посылку прямо вам в руки в Узбекистане.',
            'buyer_btn' => 'Оставить заявку на байер-заказ в Telegram',
            'brands_badge' => 'Бренды Mally',
            'brands_title' => 'Мировые бренды, доступные для вас',
            'brand_country' => 'Страна:',
            'brand_view' => 'Смотреть продукцию →',
            'reviews_badge' => 'Отзывы клиентов',
            'reviews_title' => 'Что говорят о нас наши покупательницы',
            'reviews_verified' => 'Проверенный отзыв',
            'faq_badge' => 'Вопросы и ответы',
            'faq_title' => 'Часто задаваемые вопросы',
        ],
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
    />

    <!-- 1. Главный Hero-блок -->
    <section class="relative bg-gradient-to-b from-[#FFF5F2] via-[#FCECEC] to-[#FCECEC] border-b border-[#F2D8D0] py-20 sm:py-28 px-4 overflow-hidden">
        <div class="absolute top-10 left-1/2 -translate-x-1/2 w-[700px] h-[700px] bg-rose-200/40 rounded-full blur-3xl -z-0 pointer-events-none"></div>

        <div class="max-w-5xl mx-auto text-center relative z-10 space-y-6">
            <div class="inline-flex items-center gap-2 px-4 py-1.5 bg-white border border-[#F2D8D0] rounded-full shadow-sm">
                <span class="w-2 h-2 rounded-full bg-emerald-500 animate-pulse"></span>
                <span class="text-[11px] uppercase tracking-widest text-[#8C827A] font-semibold">
                    {{ $homeTexts['hero_badge'] }}
                </span>
            </div>

            <h1 class="font-serif text-4xl sm:text-6xl font-bold text-[#1C1917] leading-[1.12] tracking-tight">
                {{ $homeTexts['hero_title_1'] }} <span class="italic font-normal">mally</span> {{ $homeTexts['hero_title_2'] }}
            </h1>

            <p class="text-sm sm:text-base text-[#57534E] max-w-2xl mx-auto leading-relaxed">
                {{ $homeTexts['hero_desc'] }}
            </p>

            <div class="pt-4 flex flex-col sm:flex-row items-center justify-center gap-4">
                <a
                    href="/catalog"
                    wire:navigate
                    class="px-9 py-4 bg-[#1C1917] hover:bg-black text-white text-xs uppercase tracking-widest font-semibold rounded-full shadow-xl transition active:scale-95 flex items-center gap-2"
                >
                    <span>{{ $homeTexts['btn_catalog'] }}</span>
                    <span>→</span>
                </a>

                <a
                    href="https://t.me/mallyuz?text={{ rawurlencode('Здравствуйте! Хочу узнать про индивидуальный заказ косметики из США') }}"
                    target="_blank"
                    rel="noreferrer"
                    class="px-7 py-4 bg-white border border-[#D5CCC7] hover:bg-[#FAF8F6] text-[#1C1917] text-xs font-semibold rounded-full transition shadow-sm flex items-center gap-2"
                >
                    <span>{{ $homeTexts['btn_us_order'] }}</span>
                </a>
            </div>

            <!-- Статистика доверия -->
            <div class="pt-10 grid grid-cols-1 sm:grid-cols-3 gap-4 max-w-3xl mx-auto border-t border-[#EAE3DE] mt-12 text-left">
                <div>
                    <div class="font-serif text-2xl font-bold text-[#1C1917]">{{ $homeTexts['stat_1_val'] }}</div>
                    <div class="text-[11px] text-[#8C827A]">{{ $homeTexts['stat_1_lbl'] }}</div>
                </div>
                <div>
                    <div class="font-serif text-2xl font-bold text-[#1C1917]">{{ $homeTexts['stat_3_val'] }}</div>
                    <div class="text-[11px] text-[#8C827A]">{{ $homeTexts['stat_3_lbl'] }}</div>
                </div>
                <div>
                    <div class="font-serif text-2xl font-bold text-[#1C1917]">{{ $homeTexts['stat_4_val'] }}</div>
                    <div class="text-[11px] text-[#8C827A]">{{ $homeTexts['stat_4_lbl'] }}</div>
                </div>
            </div>
        </div>
    </section>

    <!-- 2. Сравнение: Почему Mally -->
    <section class="py-16 px-4 bg-white border-b border-[#EAE3DE]">
        <div class="max-w-5xl mx-auto">
            <div class="text-center max-w-2xl mx-auto mb-12">
                <span class="text-xs uppercase tracking-widest text-[#8C827A] font-bold">{{ $homeTexts['why_badge'] }}</span>
                <h2 class="font-serif text-2xl sm:text-3xl font-bold text-[#1C1917] mt-1">
                    {{ $homeTexts['why_title'] }}
                </h2>
                <p class="text-xs text-[#78716C] mt-2">
                    {{ $homeTexts['why_subtitle'] }}
                </p>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                <div class="p-6 bg-[#FAF7F5] rounded-3xl border border-[#EAE3DE] space-y-3">
                    <div class="w-12 h-12 rounded-2xl bg-white flex items-center justify-center text-xl text-amber-600 shadow-sm">
                        <i class="fa-solid fa-gem"></i>
                    </div>
                    <h3 class="font-serif text-base font-bold text-[#1C1917]">{{ $homeTexts['why_1_title'] }}</h3>
                    <p class="text-xs text-[#57534E] leading-relaxed">
                        {{ $homeTexts['why_1_desc'] }}
                    </p>
                </div>

                <div class="p-6 bg-[#FAF7F5] rounded-3xl border border-[#EAE3DE] space-y-3">
                    <div class="w-12 h-12 rounded-2xl bg-white flex items-center justify-center text-xl text-amber-500 shadow-sm">
                        <i class="fa-solid fa-bolt"></i>
                    </div>
                    <h3 class="font-serif text-base font-bold text-[#1C1917]">{{ $homeTexts['why_2_title'] }}</h3>
                    <p class="text-xs text-[#57534E] leading-relaxed">
                        {{ $homeTexts['why_2_desc'] }}
                    </p>
                </div>

                <div class="p-6 bg-[#FAF7F5] rounded-3xl border border-[#EAE3DE] space-y-3">
                    <div class="w-12 h-12 rounded-2xl bg-white flex items-center justify-center text-xl text-rose-500 shadow-sm">
                        <i class="fa-solid fa-gift"></i>
                    </div>
                    <h3 class="font-serif text-base font-bold text-[#1C1917]">{{ $homeTexts['why_3_title'] }}</h3>
                    <p class="text-xs text-[#57534E] leading-relaxed">
                        {{ $homeTexts['why_3_desc'] }}
                    </p>
                </div>
            </div>
        </div>
    </section>

    <!-- 3. Как работает Индивидуальный заказ из США (15-20 дней) -->
    <section class="py-16 px-4 bg-[#FAF7F5] border-b border-[#EAE3DE]">
        <div class="max-w-5xl mx-auto">
            <div class="text-center max-w-2xl mx-auto mb-12">
                <span class="text-xs uppercase tracking-widest text-[#8C827A] font-bold">{{ $homeTexts['buyer_badge'] }}</span>
                <h2 class="font-serif text-2xl sm:text-3xl font-bold text-[#1C1917] mt-1">
                    {{ $homeTexts['buyer_title'] }}
                </h2>
                <p class="text-xs text-[#78716C] mt-2">
                    {{ $homeTexts['buyer_subtitle'] }}
                </p>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 text-xs">
                <div class="p-5 bg-white rounded-2xl border border-[#EAE3DE] space-y-2">
                    <span class="w-7 h-7 rounded-full bg-[#1C1917] text-white flex items-center justify-center font-bold text-xs">1</span>
                    <h4 class="font-bold text-sm text-[#1C1917]">{{ $homeTexts['step_1_title'] }}</h4>
                    <p class="text-[#57534E]">
                        {{ $homeTexts['step_1_desc'] }}
                    </p>
                </div>

                <div class="p-5 bg-white rounded-2xl border border-[#EAE3DE] space-y-2">
                    <span class="w-7 h-7 rounded-full bg-[#1C1917] text-white flex items-center justify-center font-bold text-xs">2</span>
                    <h4 class="font-bold text-sm text-[#1C1917]">{{ $homeTexts['step_2_title'] }}</h4>
                    <p class="text-[#57534E]">
                        {{ $homeTexts['step_2_desc'] }}
                    </p>
                </div>

                <div class="p-5 bg-white rounded-2xl border border-[#EAE3DE] space-y-2">
                    <span class="w-7 h-7 rounded-full bg-[#1C1917] text-white flex items-center justify-center font-bold text-xs">3</span>
                    <h4 class="font-bold text-sm text-[#1C1917]">{{ $homeTexts['step_3_title'] }}</h4>
                    <p class="text-[#57534E]">
                        {{ $homeTexts['step_3_desc'] }}
                    </p>
                </div>

                <div class="p-5 bg-white rounded-2xl border border-[#EAE3DE] space-y-2">
                    <span class="w-7 h-7 rounded-full bg-[#1C1917] text-white flex items-center justify-center font-bold text-xs">4</span>
                    <h4 class="font-bold text-sm text-[#1C1917]">{{ $homeTexts['step_4_title'] }}</h4>
                    <p class="text-[#57534E]">
                        {{ $homeTexts['step_4_desc'] }}
                    </p>
                </div>
            </div>

            <div class="mt-8 text-center">
                <a
                    href="https://t.me/mallyuz?text={{ rawurlencode('Здравствуйте! Хочу оформить индивидуальный заказ из США') }}"
                    target="_blank"
                    rel="noreferrer"
                    class="inline-flex items-center gap-2 px-8 py-3.5 bg-[#0088cc] hover:bg-[#0077b3] text-white font-semibold text-xs rounded-full shadow-md transition"
                >
                    <i class="fa-brands fa-telegram text-base"></i>
                    <span>{{ $homeTexts['buyer_btn'] }}</span>
                </a>
            </div>
        </div>
    </section>

    <!-- 4. Культовые мировые бренды -->
    <section class="py-16 px-4 bg-white border-b border-[#EAE3DE]">
        <div class="max-w-6xl mx-auto">
            <div class="text-center max-w-2xl mx-auto mb-12">
                <span class="text-xs uppercase tracking-widest text-[#8C827A] font-bold">{{ $homeTexts['brands_badge'] }}</span>
                <h2 class="font-serif text-2xl sm:text-3xl font-bold text-[#1C1917] mt-1">
                    {{ $homeTexts['brands_title'] }}
                </h2>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6">
                @foreach($brandsList as $b)
                    <div class="p-6 bg-[#FAF7F5] rounded-3xl border border-[#EAE3DE] shadow-sm space-y-2">
                        <div class="flex justify-between items-start">
                            <h3 class="font-serif text-lg font-bold text-[#1C1917]">{{ $b['name'] }}</h3>
                            <span class="text-[10px] px-2 py-0.5 rounded-full bg-rose-50 text-rose-700 font-bold border border-rose-100">
                                {{ $b['badge'] }}
                            </span>
                        </div>
                        <span class="text-[11px] text-[#8C827A] block font-medium">{{ $homeTexts['brand_country'] }} {{ $b['origin'] }}</span>
                        <p class="text-xs text-[#57534E] leading-relaxed pt-1">
                            {{ $b['desc'] }}
                        </p>
                        <a
                            href="/catalog?brand={{ urlencode($b['name']) }}"
                            wire:navigate
                            class="inline-block pt-2 text-xs font-semibold text-[#1C1917] hover:underline"
                        >
                            {{ $b['name'] }} — {{ $homeTexts['brand_view'] }}
                        </a>
                    </div>
                @endforeach
            </div>
        </div>
    </section>

    <!-- 5. Отзывы покупателей (только реальные, одобренные в админке) -->
    @if(count($reviews) > 0)
    <section class="py-16 px-4 bg-[#FAF7F5] border-b border-[#EAE3DE]">
        <div class="max-w-5xl mx-auto">
            <div class="text-center max-w-xl mx-auto mb-12">
                <span class="text-xs uppercase tracking-widest text-[#8C827A] font-bold">{{ $homeTexts['reviews_badge'] }}</span>
                <h2 class="font-serif text-2xl sm:text-3xl font-bold text-[#1C1917] mt-1">
                    {{ $homeTexts['reviews_title'] }}
                </h2>

            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                @foreach($reviews as $rev)
                    <div class="bg-white p-6 rounded-3xl border border-[#EAE3DE] shadow-sm space-y-3">
                        <div class="flex justify-between items-start">
                            <div>
                                <h4 class="font-serif font-bold text-sm text-[#1C1917]">{{ $rev['title'] }}</h4>
                                <span class="text-xs text-[#8C827A]">{{ $rev['author'] }}@if($rev['city']), {{ $rev['city'] }}@endif</span>
                            </div>
                            <span class="text-amber-500 text-xs">{{ str_repeat('★', $rev['rating']) }}{{ str_repeat('☆', 5 - $rev['rating']) }}</span>
                        </div>
                        <p class="text-xs text-[#57534E] leading-relaxed italic">
                            «{{ $rev['text'] }}»
                        </p>
                        <div class="pt-2 text-[10px] text-[#A8A29E] border-t border-[#EAE3DE]">
                            {{ $homeTexts['reviews_verified'] }} • {{ $rev['date'] }}
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    </section>
    @endif

    <!-- 6. FAQ на Alpine.js -->
    <section x-data="{ openFaq: null }" class="py-16 px-4 bg-white border-b border-[#EAE3DE]">
        <div class="max-w-3xl mx-auto">
            <div class="text-center mb-10">
                <span class="text-xs uppercase tracking-widest text-[#8C827A] font-bold">{{ $homeTexts['faq_badge'] }}</span>
                <h2 class="font-serif text-2xl sm:text-3xl font-bold text-[#1C1917] mt-1">
                    {{ $homeTexts['faq_title'] }}
                </h2>
            </div>

            <div class="space-y-3">
                @foreach($faqs as $index => $faq)
                    <div class="bg-[#FAF7F5] rounded-2xl border border-[#EAE3DE] overflow-hidden shadow-xs transition">
                        <button
                            type="button"
                            @click="openFaq = openFaq === {{ $index }} ? null : {{ $index }}"
                            class="w-full p-4 sm:p-5 text-left flex justify-between items-center text-xs sm:text-sm font-semibold text-[#1C1917] hover:bg-[#F2ECE8]/60 cursor-pointer"
                        >
                            <span class="pr-4">{{ $faq['q'] }}</span>
                            <span class="text-base text-[#8C827A] font-mono" x-text="openFaq === {{ $index }} ? '−' : '+'">+</span>
                        </button>
                        <div
                            x-show="openFaq === {{ $index }}"
                            x-cloak
                            class="px-5 pb-5 pt-2 text-xs text-[#57534E] leading-relaxed border-t border-[#EAE3DE] bg-white"
                        >
                            {{ $faq['a'] }}
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    </section>

    <x-storefront.footer :store="$store" />

    <x-storefront.auth-modal
        :store="$store"
        :isOpen="$isAuthModalOpen"
        :step="$authStep"
        :phone="$authPhone"
        :message="$authModalMessage"
    />
</div>
