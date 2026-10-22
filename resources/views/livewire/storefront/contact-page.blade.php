@php
    $ct = match ($locale) {
        'uz' => [
            'badge_online' => "Aloqadamiz · Mally mijozlarni qo'llab-quvvatlash xizmati",
            'page_title' => "Biz bilan bog'lanish va aloqa formasi",
            'page_desc' => "Savolingiz, taklifingiz yoki AQSHdan individual buyurtmangiz bormi? Formani to'ldiring yoki administrator bilan to'g'ridan-to'g'ri bog'laning.",
            'admin_title' => 'Mally Administratori',
            'online_status' => 'Aloqada',
            'admin_sub' => "Maslahat, buyurtmada yordam va AQSHdan xarid",
            'phone_label' => 'Administrator telefoni',
            'phone_sub' => "Qo'ng'iroqlar va maslahat uchun to'g'ridan-to'g'ri liniya",
            'hours_label' => 'Ish tartibi',
            'hours_val' => 'Har kuni, soat 10:00 dan 20:00 gacha',
            'hours_sub' => 'Dam olish kunlarisiz · Saytda arizalar 24/7 qabul qilinadi',
            'email_label' => 'Taklif va hamkorlik uchun pochta',
            'email_sub' => 'Ish kuni davomida javob beramiz',
            'social_badge' => 'Messenjerlar va ijtimoiy tarmoqlar',
            'social_title' => 'Biz Telegram va Instagramda',
            'social_desc' => "Kanallarimizga obuna bo'ling yoki qulay messenjer orqali to'g'ridan-to'g'ri yozing:",
            'tg_admin_title' => 'Administrator Telegrami',
            'tg_admin_sub' => '@mallyuz · Chatda tezkor javob',
            'tg_write' => 'Yozish →',
            'ig_title' => 'Rasmiy Instagram',
            'ig_sub' => '@mally_uz · Yangiliklar, obzorlar va Direct',
            'ig_open' => 'Ochish →',
            'form_badge' => 'Qayta aloqa',
            'form_title' => 'Xabar yozish',
            'first_name' => 'Ism',
            'first_name_ph' => 'Aziza',
            'last_name' => 'Familiya',
            'last_name_ph' => 'Karimova',
            'phone_input_label' => 'Telefon raqami (+998)',
            'msg_label' => 'Xabaringiz / Yozuv maydoni',
            'msg_ph' => "Savolingiz, tilagingiz, buyurtma raqami yoki AQSHdan buyurtma qilinadigan kosmetika nomini yozing...",
            'submit_btn' => "Yozish va jo'natish",
            'sending_btn' => "Jo'natilmoqda...",
            'sent_badge' => 'Muvaffaqiyatli yuborildi',
            'sent_title' => 'Xabaringiz muvaffaqiyatli yuborildi!',
            'sent_desc' => 'Administrator murojaatingizni qabul qildi va tez orada siz bilan bog‘lanadi.',
            'send_another' => 'Yana xabar yozish',
            'continue_tg' => 'Telegramda muloqotni davom ettirish',
        ],
        'en' => [
            'badge_online' => 'Online · Mally Customer Care',
            'page_title' => 'Contact Form & Administrator Support',
            'page_desc' => 'Have a question about stock, delivery, or want to place a custom cosmetics order from the USA? Fill out the form below or contact our administrator directly.',
            'admin_title' => 'Mally Administrator',
            'online_status' => 'Online',
            'admin_sub' => 'Consultations, order assistance & US personal shopping',
            'phone_label' => 'Administrator Phone',
            'phone_sub' => 'Direct line for calls and consultations',
            'hours_label' => 'Working Hours',
            'hours_val' => 'Every day, from 10:00 to 20:00',
            'hours_sub' => '7 days a week · Online requests accepted 24/7',
            'email_label' => 'Email for Inquiries & Partnerships',
            'email_sub' => 'We reply within one business day',
            'social_badge' => 'Messengers & Social Media',
            'social_title' => 'Find Us on Telegram & Instagram',
            'social_desc' => 'Follow our channels or message us directly in your preferred messenger:',
            'tg_admin_title' => 'Administrator Telegram',
            'tg_admin_sub' => '@mallyuz · Fast reply in chat',
            'tg_write' => 'Message →',
            'ig_title' => 'Official Instagram',
            'ig_sub' => '@mally_uz · Reviews, new arrivals & Direct',
            'ig_open' => 'Open →',
            'form_badge' => 'Feedback Form',
            'form_title' => 'Send a Message',
            'first_name' => 'First Name',
            'first_name_ph' => 'Aziza',
            'last_name' => 'Last Name',
            'last_name_ph' => 'Karimova',
            'phone_input_label' => 'Phone Number (+998)',
            'msg_label' => 'Your Message / Notes',
            'msg_ph' => 'Write your question, feedback, order number, or the name of the beauty product you want to order from the USA...',
            'submit_btn' => 'Write & Send',
            'sending_btn' => 'Sending...',
            'sent_badge' => 'Sent Successfully',
            'sent_title' => 'Your message has been sent!',
            'sent_desc' => 'Our administrator has received your request and will get back to you shortly.',
            'send_another' => 'Send Another Message',
            'continue_tg' => 'Continue Chat in Telegram',
        ],
        default => [
            'badge_online' => 'На связи · Служба заботы и обратной связи Mally',
            'page_title' => 'Контактная форма и связь с администратором',
            'page_desc' => 'Остались вопросы по наличию, доставке или хотите оформить индивидуальный заказ косметики из США? Напишите нам через форму или свяжитесь напрямую.',
            'admin_title' => 'Администратор Mally',
            'online_status' => 'На связи',
            'admin_sub' => 'Консультация, помощь с заказом и выкуп из США',
            'phone_label' => 'Телефон администратора',
            'phone_sub' => 'Прямая линия для звонков и консультаций',
            'hours_label' => 'Режим работы',
            'hours_val' => 'Каждый день, с 10:00 до 20:00',
            'hours_sub' => 'Без выходных · Прием заявок на сайте 24/7',
            'email_label' => 'Почта для предложений и сотрудничества',
            'email_sub' => 'Отвечаем в течение рабочего дня',
            'social_badge' => 'Мессенджеры и соцсети',
            'social_title' => 'Мы в Telegram и Instagram',
            'social_desc' => 'Подписывайтесь на наши каналы или пишите напрямую в удобный мессенджер:',
            'tg_admin_title' => 'Telegram администратора',
            'tg_admin_sub' => '@mallyuz · Быстрый ответ в чате',
            'tg_write' => 'Написать →',
            'ig_title' => 'Официальный Instagram',
            'ig_sub' => '@mally_uz · Обзоры, новинки и Direct',
            'ig_open' => 'Открыть →',
            'form_badge' => 'Обратная связь',
            'form_title' => 'Написать сообщение',
            'first_name' => 'Имя',
            'first_name_ph' => 'Азиза',
            'last_name' => 'Фамилия',
            'last_name_ph' => 'Каримова',
            'phone_input_label' => 'Номер телефона (+998)',
            'msg_label' => 'Ваше сообщение / Поле для записей',
            'msg_ph' => 'Напишите ваш вопрос, пожелание, номер заказа или название косметики для индивидуального заказа...',
            'submit_btn' => 'Написать и отправить',
            'sending_btn' => 'Отправка...',
            'sent_badge' => 'Успешно отправлено',
            'sent_title' => 'Ваше сообщение успешно отправлено!',
            'sent_desc' => 'Администратор уже получил ваше обращение и свяжется с вами в ближайшее время.',
            'send_another' => 'Написать ещё одно сообщение',
            'continue_tg' => 'Продолжить диалог в Telegram',
        ],
    };
@endphp

<div class="min-h-screen bg-[#FCECEC] text-[#1C1917] flex flex-col font-sans">
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

    <main class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8 sm:py-12 w-full flex-1">
        <!-- Хлебные крошки -->
        <nav class="text-xs text-[#78716C] mb-6 flex items-center gap-2">
            <a href="/" wire:navigate class="hover:text-[#1C1917] transition">{{ $store->t('nav.home', 'Главная') }}</a>
            <span>/</span>
            <span class="text-[#1C1917] font-semibold">{{ $store->t('nav.contact', 'Контактная форма') }}</span>
        </nav>

        <!-- Заголовок страницы -->
        <div class="mb-8 sm:mb-10">
            <div class="inline-flex items-center gap-2 px-3.5 py-1.5 bg-white border border-[#EFE5E2] rounded-full shadow-2xs mb-3">
                <span class="w-2 h-2 rounded-full bg-emerald-500 animate-pulse"></span>
                <span class="text-[11px] uppercase tracking-wider font-bold text-[#0D9488]">
                    {{ $ct['badge_online'] }}
                </span>
            </div>

            <h1 class="font-serif text-3xl sm:text-4xl font-bold text-[#1C1917] tracking-tight">
                {{ $ct['page_title'] }}
            </h1>
            <p class="text-xs sm:text-sm text-[#57534E] mt-2 max-w-2xl leading-relaxed">
                {{ $ct['page_desc'] }}
            </p>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-12 gap-6 sm:gap-8 items-start">
            <!-- ЛЕВАЯ КОЛОНКА: Информация об администраторе, режим работы, телефон и соцсети -->
            <div class="lg:col-span-5 space-y-5">
                <!-- Карточка 1: Статус «На связи» и Режим работы -->
                <div class="bg-white rounded-3xl p-6 shadow-sm border border-[#EFE5E2] space-y-5">
                    <div class="flex items-start justify-between gap-4 pb-4 border-b border-[#F2ECE8]">
                        <div class="flex items-center gap-3.5">
                            <div class="w-12 h-12 rounded-2xl bg-[#1C1917] text-white flex items-center justify-center text-lg font-serif font-bold shadow-sm relative">
                                M
                                <span class="absolute -bottom-0.5 -right-0.5 w-3.5 h-3.5 rounded-full bg-emerald-500 border-2 border-white"></span>
                            </div>
                            <div>
                                <div class="flex items-center gap-2">
                                    <h3 class="font-serif text-base font-bold text-[#1C1917]">{{ $ct['admin_title'] }}</h3>
                                    <span class="px-2 py-0.5 rounded-full bg-emerald-50 text-emerald-700 border border-emerald-200 text-[10px] font-bold">
                                        {{ $ct['online_status'] }}
                                    </span>
                                </div>
                                <p class="text-xs text-[#78716C] mt-0.5">{{ $ct['admin_sub'] }}</p>
                            </div>
                        </div>
                    </div>

                    <!-- Режим работы и телефон администратора -->
                    <div class="space-y-3.5">
                        <div class="flex items-start gap-3.5 p-3.5 rounded-2xl bg-[#FAF8F6] border border-[#EAE3DE]">
                            <div class="w-10 h-10 rounded-xl bg-white text-[#0D9488] border border-[#EAE3DE] flex items-center justify-center text-base flex-shrink-0 shadow-2xs">
                                <i class="fa-solid fa-phone-volume"></i>
                            </div>
                            <div class="flex-1 min-w-0">
                                <span class="text-[10px] uppercase tracking-wider font-bold text-[#8C827A] block">
                                    {{ $ct['phone_label'] }}
                                </span>
                                <a
                                    href="tel:+998998222880"
                                    class="text-base font-mono font-bold text-[#1C1917] hover:text-[#0D9488] transition block mt-0.5"
                                >
                                    +998 99 822 28 80
                                </a>
                                <span class="text-[11px] text-[#78716C]">{{ $ct['phone_sub'] }}</span>
                            </div>
                        </div>

                        <div class="flex items-start gap-3.5 p-3.5 rounded-2xl bg-[#FAF8F6] border border-[#EAE3DE]">
                            <div class="w-10 h-10 rounded-xl bg-white text-amber-600 border border-[#EAE3DE] flex items-center justify-center text-base flex-shrink-0 shadow-2xs">
                                <i class="fa-regular fa-clock"></i>
                            </div>
                            <div class="flex-1 min-w-0">
                                <span class="text-[10px] uppercase tracking-wider font-bold text-[#8C827A] block">
                                    {{ $ct['hours_label'] }}
                                </span>
                                <p class="text-sm font-bold text-[#1C1917] mt-0.5">
                                    {{ $ct['hours_val'] }}
                                </p>
                                <span class="text-[11px] text-[#78716C]">{{ $ct['hours_sub'] }}</span>
                            </div>
                        </div>

                        <div class="flex items-start gap-3.5 p-3.5 rounded-2xl bg-[#FAF8F6] border border-[#EAE3DE]">
                            <div class="w-10 h-10 rounded-xl bg-white text-rose-600 border border-[#EAE3DE] flex items-center justify-center text-base flex-shrink-0 shadow-2xs">
                                <i class="fa-regular fa-envelope"></i>
                            </div>
                            <div class="flex-1 min-w-0">
                                <span class="text-[10px] uppercase tracking-wider font-bold text-[#8C827A] block">
                                    {{ $ct['email_label'] }}
                                </span>
                                <a
                                    href="mailto:info@mally.uz"
                                    class="text-sm font-mono font-bold text-[#1C1917] hover:text-rose-600 transition block mt-0.5"
                                >
                                    info@mally.uz
                                </a>
                                <span class="text-[11px] text-[#78716C]">{{ $ct['email_sub'] }}</span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Карточка 2: Наши соцсети и прямая обратная связь -->
                <div class="bg-white rounded-3xl p-6 shadow-sm border border-[#EFE5E2] space-y-4">
                    <div>
                        <span class="text-[10px] uppercase tracking-widest font-bold text-[#8C827A]">{{ $ct['social_badge'] }}</span>
                        <h3 class="font-serif text-lg font-bold text-[#1C1917] mt-0.5">
                            {{ $ct['social_title'] }}
                        </h3>
                        <p class="text-xs text-[#78716C] mt-1">
                            {{ $ct['social_desc'] }}
                        </p>
                    </div>

                    <div class="space-y-2.5">
                        <!-- Telegram Администратора -->
                        <a
                            href="https://t.me/mallyuz"
                            target="_blank"
                            rel="noreferrer"
                            class="flex items-center justify-between p-3.5 rounded-2xl bg-[#0088cc]/8 hover:bg-[#0088cc]/15 border border-[#0088cc]/20 transition group"
                        >
                            <div class="flex items-center gap-3">
                                <div class="w-10 h-10 rounded-xl bg-[#0088cc] text-white flex items-center justify-center text-lg shadow-xs">
                                    <i class="fa-brands fa-telegram"></i>
                                </div>
                                <div>
                                    <div class="text-xs font-bold text-[#1C1917] group-hover:text-[#0088cc] transition">
                                        {{ $ct['tg_admin_title'] }}
                                    </div>
                                    <div class="text-[11px] text-[#57534E] font-mono">{{ $ct['tg_admin_sub'] }}</div>
                                </div>
                            </div>
                            <span class="text-xs font-bold text-[#0088cc] group-hover:translate-x-0.5 transition">
                                {{ $ct['tg_write'] }}
                            </span>
                        </a>

                        <!-- Instagram -->
                        <a
                            href="https://www.instagram.com/mally_uz"
                            target="_blank"
                            rel="noreferrer"
                            class="flex items-center justify-between p-3.5 rounded-2xl bg-rose-50/70 hover:bg-rose-100/70 border border-rose-200/70 transition group"
                        >
                            <div class="flex items-center gap-3">
                                <div class="w-10 h-10 rounded-xl bg-gradient-to-tr from-amber-500 via-rose-500 to-purple-600 text-white flex items-center justify-center text-lg shadow-xs">
                                    <i class="fa-brands fa-instagram"></i>
                                </div>
                                <div>
                                    <div class="text-xs font-bold text-[#1C1917] group-hover:text-rose-600 transition">
                                        {{ $ct['ig_title'] }}
                                    </div>
                                    <div class="text-[11px] text-[#57534E] font-mono">{{ $ct['ig_sub'] }}</div>
                                </div>
                            </div>
                            <span class="text-xs font-bold text-rose-600 group-hover:translate-x-0.5 transition">
                                {{ $ct['ig_open'] }}
                            </span>
                        </a>
                    </div>
                </div>
            </div>

            <!-- ПРАВАЯ КОЛОНКА: Контактная форма -->
            <div class="lg:col-span-7">
                <div class="bg-white rounded-3xl p-6 sm:p-8 shadow-sm border border-[#EFE5E2]">
                    @if($submitted)
                        <div class="py-10 px-4 text-center space-y-5">
                            <div class="w-16 h-16 rounded-full bg-emerald-100 text-emerald-700 flex items-center justify-center text-3xl mx-auto shadow-xs">
                                <i class="fa-solid fa-check"></i>
                            </div>
                            <div class="space-y-2 max-w-md mx-auto">
                                <span class="inline-block px-3 py-1 rounded-full bg-emerald-50 text-emerald-700 text-[11px] font-bold uppercase tracking-wider">
                                    {{ $ct['sent_badge'] }}
                                </span>
                                <h2 class="font-serif text-2xl font-bold text-[#1C1917]">
                                    {{ $ct['sent_title'] }}
                                </h2>
                                <p class="text-xs sm:text-sm text-[#57534E] leading-relaxed">
                                    {{ $ct['sent_desc'] }}
                                </p>
                            </div>

                            <div class="pt-3 flex flex-col sm:flex-row items-center justify-center gap-3">
                                <button
                                    type="button"
                                    wire:click="resetContactForm"
                                    class="w-full sm:w-auto px-6 py-3 rounded-2xl bg-[#1C1917] hover:bg-black text-white text-xs font-bold transition cursor-pointer"
                                >
                                    {{ $ct['send_another'] }}
                                </button>

                                @if($lastTelegramText)
                                    <a
                                        href="https://t.me/mallyuz?text={{ rawurlencode($lastTelegramText) }}"
                                        target="_blank"
                                        rel="noreferrer"
                                        class="w-full sm:w-auto px-6 py-3 rounded-2xl bg-[#0088cc] hover:bg-[#0077b3] text-white text-xs font-bold transition flex items-center justify-center gap-2"
                                    >
                                        <i class="fa-brands fa-telegram text-sm"></i>
                                        <span>{{ $ct['continue_tg'] }}</span>
                                    </a>
                                @endif
                            </div>
                        </div>
                    @else
                        <div class="flex items-center justify-between gap-4 pb-5 mb-6 border-b border-[#F2ECE8]">
                            <div>
                                <span class="text-[10px] uppercase tracking-widest font-bold text-[#0D9488]">
                                    {{ $ct['form_badge'] }}
                                </span>
                                <h2 class="font-serif text-xl sm:text-2xl font-bold text-[#1C1917] mt-0.5">
                                    {{ $ct['form_title'] }}
                                </h2>
                            </div>
                            <div class="w-11 h-11 rounded-2xl bg-[#F0FAF7] text-[#0D9488] flex items-center justify-center text-lg flex-shrink-0">
                                <i class="fa-regular fa-paper-plane"></i>
                            </div>
                        </div>

                        <form wire:submit="submitForm" class="space-y-4">
                            <!-- Имя и Фамилия в 2 колонки -->
                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                <div>
                                    <label class="block text-xs font-bold text-[#1C1917] mb-1.5">
                                        {{ $ct['first_name'] }} <span class="text-rose-500">*</span>
                                    </label>
                                    <input
                                        type="text"
                                        wire:model="firstName"
                                        placeholder="{{ $ct['first_name_ph'] }}"
                                        required
                                        class="w-full px-4 py-3 text-xs sm:text-sm bg-[#FAF8F6] border border-[#E3DBD5] rounded-2xl focus:outline-none focus:border-[#1C1917] focus:bg-white transition"
                                    />
                                </div>

                                <div>
                                    <label class="block text-xs font-bold text-[#1C1917] mb-1.5">
                                        {{ $ct['last_name'] }} <span class="text-rose-500">*</span>
                                    </label>
                                    <input
                                        type="text"
                                        wire:model="lastName"
                                        placeholder="{{ $ct['last_name_ph'] }}"
                                        required
                                        class="w-full px-4 py-3 text-xs sm:text-sm bg-[#FAF8F6] border border-[#E3DBD5] rounded-2xl focus:outline-none focus:border-[#1C1917] focus:bg-white transition"
                                    />
                                </div>
                            </div>

                            <!-- Номер телефона: закреплен из профиля если авторизован, иначе доступен для ввода -->
                            @if($currentCustomer && !empty($currentCustomer->phone_number))
                                <div>
                                    <div class="flex items-center justify-between mb-1.5">
                                        <label class="block text-xs font-bold text-[#1C1917]">
                                            {{ $ct['phone_input_label'] }} <span class="text-rose-500">*</span>
                                        </label>
                                        <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full bg-emerald-50 text-emerald-700 border border-emerald-200 text-[10px] font-bold">
                                            <i class="fa-solid fa-lock text-[9px]"></i>
                                            <span>{{ $locale === 'uz' ? 'Profildan' : ($locale === 'en' ? 'From profile' : 'Из профиля') }}</span>
                                        </span>
                                    </div>
                                    <input
                                        type="tel"
                                        disabled
                                        readonly
                                        value="{{ $phone }}"
                                        class="w-full px-4 py-3 text-xs sm:text-sm font-mono font-bold bg-emerald-50/40 border border-emerald-200/70 rounded-2xl text-emerald-950 cursor-not-allowed select-none"
                                    />
                                </div>
                            @else
                                <div
                                    x-data="{
                                        formatUzPhone(e) {
                                            let digits = (e.target.value || '').replace(/\D/g, '');
                                            if (digits.startsWith('998')) {
                                                digits = digits.slice(3);
                                            }
                                            digits = digits.slice(0, 9);
                                            let formatted = '+998';
                                            if (digits.length > 0) formatted += ' ' + digits.slice(0, 2);
                                            if (digits.length > 2) formatted += ' ' + digits.slice(2, 5);
                                            if (digits.length > 5) formatted += ' ' + digits.slice(5, 7);
                                            if (digits.length > 7) formatted += ' ' + digits.slice(7, 9);
                                            e.target.value = formatted;
                                            $wire.set('phone', formatted, false);
                                        }
                                    }"
                                >
                                    <label class="block text-xs font-bold text-[#1C1917] mb-1.5">
                                        {{ $ct['phone_input_label'] }} <span class="text-rose-500">*</span>
                                    </label>
                                    <input
                                        type="tel"
                                        wire:model="phone"
                                        maxlength="17"
                                        placeholder="+998 99 822 28 80"
                                        required
                                        @input="formatUzPhone($event)"
                                        @focus="if (!$event.target.value || !$event.target.value.startsWith('+998')) { $event.target.value = '+998 '; $wire.set('phone', '+998 ', false); }"
                                        class="w-full px-4 py-3 text-xs sm:text-sm font-mono font-medium bg-[#FAF8F6] border border-[#E3DBD5] rounded-2xl focus:outline-none focus:border-[#1C1917] focus:bg-white transition"
                                    />
                                </div>
                            @endif

                            <!-- Поле для надписей / Сообщение -->
                            <div>
                                <label class="block text-xs font-bold text-[#1C1917] mb-1.5">
                                    {{ $ct['msg_label'] }} <span class="text-rose-500">*</span>
                                </label>
                                <textarea
                                    wire:model="message"
                                    rows="5"
                                    placeholder="{{ $ct['msg_ph'] }}"
                                    required
                                    class="w-full px-4 py-3 text-xs sm:text-sm bg-[#FAF8F6] border border-[#E3DBD5] rounded-2xl focus:outline-none focus:border-[#1C1917] focus:bg-white transition resize-y"
                                ></textarea>
                            </div>

                            <!-- Кнопка Отправить -->
                            <div class="pt-2">
                                <button
                                    type="submit"
                                    wire:loading.attr="disabled"
                                    class="w-full py-4 px-6 rounded-2xl bg-[#1C1917] hover:bg-black text-white text-xs sm:text-sm font-bold uppercase tracking-wider shadow-lg transition flex items-center justify-center gap-2.5 cursor-pointer disabled:opacity-60"
                                >
                                    <span wire:loading.remove wire:target="submitForm" class="flex items-center gap-2">
                                        <i class="fa-regular fa-paper-plane"></i>
                                        <span>{{ $ct['submit_btn'] }}</span>
                                    </span>
                                    <span wire:loading wire:target="submitForm" class="flex items-center gap-2">
                                        <i class="fa-solid fa-spinner animate-spin"></i>
                                        <span>{{ $ct['sending_btn'] }}</span>
                                    </span>
                                </button>
                            </div>
                        </form>
                    @endif
                </div>
            </div>
        </div>
    </main>

    <x-storefront.footer :store="$store" />
    <x-storefront.auth-modal :store="$store" />
</div>
