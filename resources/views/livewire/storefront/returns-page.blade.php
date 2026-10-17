@php
    $rt = match ($locale ?? 'ru') {
        'uz' => [
            'badge' => "Do'kon reglamenti",
            'title' => 'Qaytarish siyosati va sifat standartlari',
            'updated' => 'Yangilangan sana: 2026 yil · Mally Uzbekistan',
            'main_rule_title' => 'Asosiy qoida: Mahsulot qaytarilmaydi va almashtirilmaydi',
            'main_rule_desc' => "O'zbekiston Respublikasi qonunchiligi va xalqaro gigiyena standartlariga muvofiq, sifatli parfyumeriya va kosmetika vositalari xaridorga topshirilgandan so'ng <strong>qaytarilmaydi yoki almashtirilmaydi</strong>.",
            's1_title' => '1. Nuqsonlarni istisno qilish va sotuv oldidan nazorat',
            's1_desc' => "Mally do'konida <strong>nuqsonli mahsulotlarni yuborish to'liq istisno qilingan</strong>. Har bir buyurtmani kuryerlik xizmatiga topshirishdan oldin:",
            's1_li1' => "Har bir pozitsiya flakon, dozator va zavod qadog'ining butunligi bo'yicha vizual tekshiruvdan o'tadi.",
            's1_li2' => 'Original zavod himoya plombalari va plyonkalarining mavjudligi hamda butunligi tekshiriladi.',
            's1_li3' => "AQSh va Yevropadagi rasmiy distribyutorlardan yaroqlilik muddatlari va haqiqiylik batch-kodlari solishtiriladi.",
            's1_li4' => 'Xavfsiz tashish uchun buyurtma mustahkam amortizatsiyali qutiga ishonchli tarzda qadoqlanadi.',
            's2_title' => '2. Yandex Go orqali yetkazib berish',
            's2_desc' => "Barcha buyurtmalar <strong>Yandex Go</strong> xizmatiga topshiriladi. Yetkazib berish narxi xaridor tomonidan buyurtmani qabul qilish vaqtida kuryerlik xizmatining amaldagi tarifi bo'yicha to'lanadi.",
            's3_title' => "3. To'lov usullari va komissiyalar",
            's3_desc' => "Buyurtmalar uchun to'lov Mally Beauty rasmiy rekvizitlari bo'yicha Humo / Uzcard kartasiga to'g'ridan-to'g'ri o'tkazma orqali amalga oshiriladi. Agar bankingiz banklararo o'tkazma uchun komissiya olsa, ushbu komissiya to'lovni yuboruvchi tomonidan qoplanadi.",
            's4_title' => "4. Mijozlarga g'amxo'rlik xizmati",
            's4_desc' => "Agar sizda buyurtma tarkibi, kosmetikadan foydalanish yoki rang tanlash bo'yicha savollar tug'ilsa, bizning g'amxo'rlik bo'limimiz har kuni soat 10:00 dan 20:00 gacha Telegram orqali ishlaydi:",
            'tg_btn' => 'Telegram orqali yozish @mallyuz',
            'back_home' => 'Bosh sahifaga qaytish',
            'go_catalog' => 'Kosmetika katalogi →',
        ],
        'en' => [
            'badge' => 'Store Regulations',
            'title' => 'Return Policy & Quality Standards',
            'updated' => 'Last updated: 2026 · Mally Uzbekistan',
            'main_rule_title' => 'Key Policy: Products are non-refundable and non-exchangeable',
            'main_rule_desc' => 'In accordance with the legislation of the Republic of Uzbekistan and international hygiene standards, perfumery and cosmetic products of proper quality <strong>are not subject to return or exchange</strong> once handed over to the buyer.',
            's1_title' => '1. Zero-Defect Guarantee & Pre-Shipment Inspection',
            's1_desc' => 'At Mally, <strong>shipping defective products is strictly ruled out</strong>. Before handing over each order to the courier service:',
            's1_li1' => 'Every item undergoes a visual inspection for the integrity of the bottle, dispenser, and factory packaging.',
            's1_li2' => 'Original factory security seals and shrink-wrap are verified to be intact.',
            's1_li3' => 'Expiration dates and authenticity batch codes from official distributors in the USA and Europe are verified.',
            's1_li4' => 'The order is securely packed in a sturdy cushioned box for safe transit.',
            's2_title' => '2. Delivery via Yandex Go',
            's2_desc' => 'All orders are dispatched via <strong>Yandex Go</strong>. The delivery cost is paid directly by the customer upon receipt according to the courier service’s current rate.',
            's3_title' => '3. Payment Methods & Fees',
            's3_desc' => 'Orders are paid via direct transfer to our official Mally Beauty Humo / Uzcard card. If your bank charges an interbank transfer fee, this fee is covered by the sender.',
            's4_title' => '4. Customer Care Service',
            's4_desc' => 'If you have questions about your order contents, product application, or shade selection, our customer care team is available daily from 10:00 to 20:00 on Telegram:',
            'tg_btn' => 'Message on Telegram @mallyuz',
            'back_home' => 'Back to Home',
            'go_catalog' => 'Cosmetics Catalog →',
        ],
        default => [
            'badge' => 'Регламент магазина',
            'title' => 'Политика возврата и стандарты качества',
            'updated' => 'Дата обновления: 2026 год · Mally Uzbekistan',
            'main_rule_title' => 'Главный регламент: Товар возврату и обмену не подлежит',
            'main_rule_desc' => 'В соответствии с законодательством Республики Узбекистан и международными стандартами гигиены, парфюмерно-косметические средства надлежащего качества <strong>не подлежат возврату или обмену</strong> после передачи покупателю.',
            's1_title' => '1. Исключение брака и предпродажный контроль',
            's1_desc' => 'В магазине Mally <strong>полностью исключена отправка бракованной продукции</strong>. Перед передачей каждого заказа курьерской службе:',
            's1_li1' => 'Каждая позиция проходит визуальную проверку на целостность флакона, дозатора и заводской упаковки.',
            's1_li2' => 'Проверяется наличие и сохранность оригинальных заводских защитных пломб и слюды.',
            's1_li3' => 'Сверяются сроки годности и батч-коды подлинности от официальных дистрибьюторов в США и Европе.',
            's1_li4' => 'Заказ надежно упаковывается в плотную амортизирующую коробку для безопасной транспортировки.',
            's2_title' => '2. Доставка через Яндекс Go',
            's2_desc' => 'Все заказы передаются в службу <strong>Яндекс Go</strong>. Стоимость доставки оплачивается покупателем непосредственно при получении по актуальному тарифу курьерской службы.',
            's3_title' => '3. Способы оплаты и комиссии',
            's3_desc' => 'Оплата заказов осуществляется прямым переводом на карту Humo / Uzcard по официальным реквизитам Mally Beauty. Если ваш банк взимает комиссию за межбанковский перевод, данная комиссия покрывается отправителем платежа.',
            's4_title' => '4. Служба заботы о клиентах',
            's4_desc' => 'Если у вас возникли вопросы по составу заказа, применению косметики или подбору оттенка, наш отдел заботы работает ежедневно с 10:00 до 20:00 в Telegram:',
            'tg_btn' => 'Написать в Telegram @mallyuz',
            'back_home' => 'Вернуться на главную',
            'go_catalog' => 'Каталог косметики →',
        ],
    };
@endphp
<div class="min-h-screen bg-[#FAF6F3] text-[#1C1917] flex flex-col font-sans">
    <x-storefront.header />

    <main class="flex-1 max-w-4xl mx-auto w-full px-4 sm:px-6 py-12 sm:py-16">
        <div class="bg-white rounded-3xl p-6 sm:p-12 border border-[#EAE0DC] shadow-sm space-y-8">
            <div class="border-b border-[#F0EAE6] pb-6">
                <span class="text-xs uppercase tracking-[0.25em] text-[#8C827A] font-bold block mb-1">
                    {{ $rt['badge'] }}
                </span>
                <h1 class="font-serif text-3xl sm:text-4xl font-bold text-[#1C1917]">
                    {{ $rt['title'] }}
                </h1>
                <p class="text-xs text-[#78716C] mt-2">
                    {{ $rt['updated'] }}
                </p>
            </div>

            <!-- Важная плашка -->
            <div class="p-5 rounded-2xl bg-amber-50 border border-amber-200 text-amber-950 space-y-2">
                <div class="flex items-center gap-2.5 font-bold text-sm">
                    <i class="fa-solid fa-shield-halved text-amber-600 text-base"></i>
                    <span>{{ $rt['main_rule_title'] }}</span>
                </div>
                <p class="text-xs leading-relaxed text-amber-900">
                    {!! $rt['main_rule_desc'] !!}
                </p>
            </div>

            <div class="space-y-6 text-xs sm:text-sm text-[#57534E] leading-relaxed">
                <section class="space-y-2.5">
                    <h2 class="font-serif text-lg font-bold text-[#1C1917] flex items-center gap-2">
                        <i class="fa-solid fa-circle-check text-emerald-600 text-sm"></i>
                        {{ $rt['s1_title'] }}
                    </h2>
                    <p>
                        {!! $rt['s1_desc'] !!}
                    </p>
                    <ul class="list-disc pl-5 space-y-1.5 text-xs text-[#78716C]">
                        <li>{{ $rt['s1_li1'] }}</li>
                        <li>{{ $rt['s1_li2'] }}</li>
                        <li>{{ $rt['s1_li3'] }}</li>
                        <li>{{ $rt['s1_li4'] }}</li>
                    </ul>
                </section>

                <section class="space-y-2.5 pt-4 border-t border-[#F0EAE6]">
                    <h2 class="font-serif text-lg font-bold text-[#1C1917] flex items-center gap-2">
                        <i class="fa-solid fa-truck-fast text-[#0D9488] text-sm"></i>
                        {{ $rt['s2_title'] }}
                    </h2>
                    <p>
                        {!! $rt['s2_desc'] !!}
                    </p>
                </section>

                <section class="space-y-2.5 pt-4 border-t border-[#F0EAE6]">
                    <h2 class="font-serif text-lg font-bold text-[#1C1917] flex items-center gap-2">
                        <i class="fa-solid fa-credit-card text-sky-600 text-sm"></i>
                        {{ $rt['s3_title'] }}
                    </h2>
                    <p>
                        {{ $rt['s3_desc'] }}
                    </p>
                </section>

                <section class="space-y-2.5 pt-4 border-t border-[#F0EAE6]">
                    <h2 class="font-serif text-lg font-bold text-[#1C1917] flex items-center gap-2">
                        <i class="fa-brands fa-telegram text-[#0088cc] text-sm"></i>
                        {{ $rt['s4_title'] }}
                    </h2>
                    <p>
                        {{ $rt['s4_desc'] }}
                    </p>
                    <div class="pt-2">
                        <a
                            href="https://t.me/mallyuz"
                            target="_blank"
                            rel="noreferrer"
                            class="inline-flex items-center gap-2 px-5 py-2.5 rounded-xl bg-[#0088cc] hover:bg-[#0077b5] text-white font-semibold text-xs transition shadow-xs"
                        >
                            <i class="fa-brands fa-telegram text-sm"></i>
                            <span>{{ $rt['tg_btn'] }}</span>
                        </a>
                    </div>
                </section>
            </div>

            <div class="pt-6 border-t border-[#F0EAE6] flex justify-between items-center text-xs">
                <a href="{{ route('home') }}" wire:navigate class="text-[#8C827A] hover:text-black font-semibold flex items-center gap-1.5 transition">
                    <i class="fa-solid fa-arrow-left text-[10px]"></i>
                    <span>{{ $rt['back_home'] }}</span>
                </a>
                <a href="{{ route('catalog') }}" wire:navigate class="text-black font-bold hover:underline">
                    {{ $rt['go_catalog'] }}
                </a>
            </div>
        </div>
    </main>

    <x-storefront.footer />
    <x-storefront.auth-modal />
</div>
