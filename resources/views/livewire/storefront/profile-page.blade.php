@php
    $pt = match ($locale) {
        'uz' => [
            'home' => 'Bosh sahifa',
            'title' => 'Shaxsiy kabinet',
            'login_title' => 'Shaxsiy kabinetga kirish',
            'login_desc' => "Buyurtmalar tarixi, yetkazib berish holati va qurilmaga biriktirish uchun telefon raqamingiz orqali tizimga kiring.",
            'login_btn' => "Kirish yoki ro'yxatdan o'tish",
            'default_name' => 'Mally xaridori',
            'device_bound' => 'Qurilma biriktirilgan',
            'to_catalog' => 'Katalogga',
            'logout' => 'Chiqish',
            'orders_tab' => 'Buyurtmalarim',
            'details_tab' => "Shaxsiy ma'lumotlar",
            'no_orders' => "Sizda hozircha buyurtmalar yo'q",
            'no_orders_desc' => "Katalogga o'ting, AQSHdan original kosmetikani tanlang va birinchi buyurtmangizni bering.",
            'go_catalog_btn' => "Katalogga o'tish",
            'order_word' => 'Buyurtma',
            'copy_hint' => 'Buyurtma raqamini nusxalash uchun bosing',
            'receipt_and_status' => 'Chek va holat',
            'date_from' => 'Sana:',
            'sum_unit' => "so'm",
            'pcs_unit' => 'dona',
            'sku_prefix' => 'Art:',
            'order_items' => 'Buyurtmadagi mahsulotlar',
            'forming_items' => 'Buyurtma tarkibi shakllantirilmoqda',
            'leave_review' => 'Fikr qoldirish',
            'delivery_label' => 'Yetkazib berish:',
            'city_prefix' => 'sh.',
            'tashkent' => 'Toshkent',
            'clarifying' => 'Aniqlashtirilmoqda',
            'tg_support' => "Telegramda Mally qo'llab-quvvatlash",
            'tg_hello' => "Assalomu alaykum! Men buyurtma bo'yicha murojaat qilyapman №",
            'personal_details_title' => "Xaridorning shaxsiy ma'lumotlari",
            'first_name' => 'Ism',
            'last_name' => 'Familiya',
            'phone' => 'Telefon raqami',
            'verified' => 'Tasdiqlangan',
            'city' => 'Yetkazib berish shahri',
            'address' => "Manzil (ko'cha, uy, xonadon)",
            'save_btn' => "O'zgarishlarni saqlash",
            'review_header' => 'Mahsulot haqida fikr · Buyurtma №',
            'your_rating' => 'Bahoyingiz:',
            'review_text_label' => 'Fikr matni',
            'review_placeholder' => "Sifati, ifori va ta'siri haqidagi taassurotlaringizni yozing...",
            'review_photos_label' => 'Fikr uchun rasmlar (havola orqali)',
            'review_photo_placeholder' => 'Havolani kiriting https://...',
            'add_link_btn' => '+ Havola',
            'publish_review_btn' => "Fikrni e'lon qilish",
            'st_new' => 'Yangi buyurtma',
            'st_paid' => "To'langan",
            'st_packing' => "Yig'ilmoqda",
            'st_shipped' => 'Yetkazilmoqda',
            'st_delivered' => 'Topshirildi',
            'st_cancelled' => 'Bekor qilingan',
            'st_archived' => 'Arxivda',
        ],
        'en' => [
            'home' => 'Home',
            'title' => 'My Account',
            'login_title' => 'Sign In to Your Account',
            'login_desc' => 'Sign in with your phone number to view your order history, delivery status, and linked device.',
            'login_btn' => 'Sign In or Register',
            'default_name' => 'Mally Customer',
            'device_bound' => 'Device Linked',
            'to_catalog' => 'Catalog',
            'logout' => 'Sign Out',
            'orders_tab' => 'My Orders',
            'details_tab' => 'Personal Details',
            'no_orders' => 'You have no orders yet',
            'no_orders_desc' => 'Explore our catalog, choose authentic beauty products from the USA, and place your first order.',
            'go_catalog_btn' => 'Browse Catalog',
            'order_word' => 'Order',
            'copy_hint' => 'Click to copy order number',
            'receipt_and_status' => 'Receipt & Status',
            'date_from' => 'Placed on',
            'sum_unit' => 'UZS',
            'pcs_unit' => 'pcs',
            'sku_prefix' => 'SKU:',
            'order_items' => 'Items in Order',
            'forming_items' => 'Order items are being prepared',
            'leave_review' => 'Leave a Review',
            'delivery_label' => 'Delivery:',
            'city_prefix' => '',
            'tashkent' => 'Tashkent',
            'clarifying' => 'To be confirmed',
            'tg_support' => 'Mally Support on Telegram',
            'tg_hello' => 'Hello! I am writing regarding order #',
            'personal_details_title' => 'Customer Personal Details',
            'first_name' => 'First Name',
            'last_name' => 'Last Name',
            'phone' => 'Phone Number',
            'verified' => 'Verified',
            'city' => 'Delivery City',
            'address' => 'Address (street, building, apt)',
            'save_btn' => 'Save Changes',
            'review_header' => 'Product Review · Order #',
            'your_rating' => 'Your Rating:',
            'review_text_label' => 'Review Text',
            'review_placeholder' => 'Share your impressions of the quality, scent, and results...',
            'review_photos_label' => 'Review Photos (by URL)',
            'review_photo_placeholder' => 'Paste image URL https://...',
            'add_link_btn' => '+ Add URL',
            'publish_review_btn' => 'Publish Review',
            'st_new' => 'New Order',
            'st_paid' => 'Paid',
            'st_packing' => 'Packing',
            'st_shipped' => 'In Delivery',
            'st_delivered' => 'Delivered',
            'st_cancelled' => 'Cancelled',
            'st_archived' => 'Archived',
        ],
        default => [
            'home' => 'Главная',
            'title' => 'Личный кабинет',
            'login_title' => 'Вход в личный кабинет',
            'login_desc' => 'Авторизуйтесь по номеру телефона для просмотра истории заказов, статуса доставки и привязки к устройству.',
            'login_btn' => 'Войти или зарегистрироваться',
            'default_name' => 'Покупатель Mally',
            'device_bound' => 'Устройство привязано',
            'to_catalog' => 'В каталог',
            'logout' => 'Выйти',
            'orders_tab' => 'Мои заказы',
            'details_tab' => 'Личные данные',
            'no_orders' => 'У вас пока нет заказов',
            'no_orders_desc' => 'Перейдите в каталог, выберите оригинальную косметику из США и оформите первый заказ.',
            'go_catalog_btn' => 'Перейти в каталог',
            'order_word' => 'Заказ',
            'copy_hint' => 'Нажмите, чтобы скопировать номер заказа',
            'receipt_and_status' => 'Чек и статус',
            'date_from' => 'От',
            'sum_unit' => 'сум',
            'pcs_unit' => 'шт.',
            'sku_prefix' => 'Арт:',
            'order_items' => 'Товары в заказе',
            'forming_items' => 'Состав заказа формируется',
            'leave_review' => 'Оставить отзыв',
            'delivery_label' => 'Доставка:',
            'city_prefix' => 'г. ',
            'tashkent' => 'Ташкент',
            'clarifying' => 'Уточняется',
            'tg_support' => 'Поддержка Mally в Telegram',
            'tg_hello' => 'Здравствуйте! Я по поводу заказа №',
            'personal_details_title' => 'Личные данные покупателя',
            'first_name' => 'Имя',
            'last_name' => 'Фамилия',
            'phone' => 'Номер телефона',
            'verified' => 'Подтверждён',
            'city' => 'Город доставки',
            'address' => 'Адрес (улица, дом, квартира)',
            'save_btn' => 'Сохранить изменения',
            'review_header' => 'Отзыв на товар · Заказ №',
            'your_rating' => 'Ваша оценка:',
            'review_text_label' => 'Текст отзыва',
            'review_placeholder' => 'Опишите ваши впечатления от качества, аромата, эффекта...',
            'review_photos_label' => 'Фотографии к отзыву (ссылкой)',
            'review_photo_placeholder' => 'Вставьте ссылку https://...',
            'add_link_btn' => '+ Ссылка',
            'publish_review_btn' => 'Опубликовать отзыв',
            'st_new' => 'Новый заказ',
            'st_paid' => 'Оплачен',
            'st_packing' => 'Комплектуется',
            'st_shipped' => 'В доставке',
            'st_delivered' => 'Вручен',
            'st_cancelled' => 'Отменён',
            'st_archived' => 'В архиве',
        ],
    };
@endphp

<div class="min-h-screen bg-[#FCECEC] flex flex-col font-sans">
    <x-storefront.header />

    <main class="flex-1 max-w-5xl w-full mx-auto px-4 py-8">
        <!-- Navigation Breadcrumb -->
        <div class="flex items-center gap-2 text-xs text-[#78716C] mb-6">
            <a href="{{ route('home') }}" wire:navigate class="hover:text-[#1C1917] transition">
                {{ $pt['home'] }}
            </a>
            <span>/</span>
            <span class="text-[#1C1917] font-medium">{{ $pt['title'] }}</span>
        </div>

        @if(!$customer)
            <!-- NOT LOGGED IN STATE -->
            <div class="bg-white rounded-3xl p-10 border border-[#EAE3DE] shadow-xl text-center max-w-md mx-auto my-12">
                <div class="w-16 h-16 rounded-2xl bg-[#FAF7F5] border border-[#EAE3DE] flex items-center justify-center text-2xl text-[#1C1917] mx-auto mb-4">
                    <i class="fa-regular fa-user"></i>
                </div>
                <h1 class="font-serif text-2xl font-bold text-[#1C1917] mb-2">
                    {{ $pt['login_title'] }}
                </h1>
                <p class="text-xs text-[#78716C] mb-6 leading-relaxed">
                    {{ $pt['login_desc'] }}
                </p>
                <button
                    type="button"
                    wire:click="openAuthModal('{{ $pt['login_title'] }}')"
                    class="w-full py-3.5 px-6 rounded-2xl bg-[#1C1917] hover:bg-[#2C2724] text-[#FAF7F5] text-xs font-bold uppercase tracking-wider transition shadow-lg flex items-center justify-center gap-2 cursor-pointer"
                >
                    <span>{{ $pt['login_btn'] }}</span>
                    <i class="fa-solid fa-arrow-right text-[10px]"></i>
                </button>
            </div>
        @else
            @php
                $displayName = trim(($customer->first_name ?? '').' '.($customer->last_name ?? '')) ?: $pt['default_name'];
                $avatarLetter = mb_strtoupper(mb_substr($displayName, 0, 1)) ?: 'M';
            @endphp
            <!-- LOGGED IN USER PROFILE -->
            <div class="space-y-6">
                <!-- Top User Card -->
                <div class="bg-white rounded-3xl p-6 sm:p-8 border border-[#EAE3DE] shadow-sm flex flex-col sm:flex-row items-start sm:items-center justify-between gap-6">
                    <div class="flex items-center gap-4">
                        <div class="w-16 h-16 rounded-2xl bg-[#1C1917] text-white flex items-center justify-center font-serif text-2xl font-bold flex-shrink-0 shadow-md">
                            {{ $avatarLetter }}
                        </div>
                        <div>
                            <div class="flex items-center gap-2 flex-wrap">
                                <h1 class="font-serif text-2xl font-bold text-[#1C1917] leading-tight">
                                    {{ $displayName }}
                                </h1>
                            </div>
                            <div class="flex items-center gap-2 mt-1.5 flex-wrap">
                                <span class="text-xs text-[#78716C] font-mono">{{ $customer->phone_number }}</span>
                                <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full bg-emerald-50 text-emerald-700 border border-emerald-200 text-[10px] font-semibold">
                                    <i class="fa-solid fa-shield-halved text-[9px]"></i>
                                    <span>{{ $pt['device_bound'] }}</span>
                                </span>
                            </div>
                        </div>
                    </div>

                    <div class="flex items-center gap-3 w-full sm:w-auto">
                        <a
                            href="{{ route('catalog') }}"
                            wire:navigate
                            class="flex-1 sm:flex-initial py-2.5 px-4 rounded-xl border border-[#EAE3DE] hover:border-[#1C1917] text-xs font-semibold text-[#1C1917] transition text-center"
                        >
                            {{ $pt['to_catalog'] }}
                        </a>
                        <button
                            type="button"
                            wire:click="logoutCustomer"
                            class="py-2.5 px-4 rounded-xl bg-rose-50 hover:bg-rose-100 text-rose-700 text-xs font-semibold transition cursor-pointer"
                        >
                            {{ $pt['logout'] }}
                        </button>
                    </div>
                </div>

                <!-- Profile Tabs -->
                <div class="flex border-b border-[#EAE3DE] gap-8 text-sm font-semibold">
                    <button
                        type="button"
                        wire:click="$set('activeTab', 'orders')"
                        class="pb-3 relative transition cursor-pointer {{ $activeTab === 'orders' ? 'text-[#1C1917]' : 'text-[#A8A29E] hover:text-[#1C1917]' }}"
                    >
                        {{ $pt['orders_tab'] }} ({{ $orders->count() }})
                        @if($activeTab === 'orders')
                            <span class="absolute bottom-0 left-0 right-0 h-0.5 bg-[#1C1917]"></span>
                        @endif
                    </button>
                    <button
                        type="button"
                        wire:click="$set('activeTab', 'details')"
                        class="pb-3 relative transition cursor-pointer {{ $activeTab === 'details' ? 'text-[#1C1917]' : 'text-[#A8A29E] hover:text-[#1C1917]' }}"
                    >
                        {{ $pt['details_tab'] }}
                        @if($activeTab === 'details')
                            <span class="absolute bottom-0 left-0 right-0 h-0.5 bg-[#1C1917]"></span>
                        @endif
                    </button>
                </div>

                <!-- TAB 1: Orders History -->
                @if($activeTab === 'orders')
                    <div class="space-y-4">
                        @if($orders->isEmpty())
                            <div class="bg-white rounded-3xl p-12 text-center border border-[#EAE3DE] shadow-sm space-y-3">
                                <div class="w-14 h-14 mx-auto rounded-full bg-[#FAF7F5] flex items-center justify-center text-xl text-[#A8A29E]">
                                    <i class="fa-solid fa-bag-shopping"></i>
                                </div>
                                <h3 class="font-serif text-lg font-bold text-[#1C1917]">
                                    {{ $pt['no_orders'] }}
                                </h3>
                                <p class="text-xs text-[#78716C] max-w-sm mx-auto">
                                    {{ $pt['no_orders_desc'] }}
                                </p>
                                <a
                                    href="{{ route('catalog') }}"
                                    wire:navigate
                                    class="inline-flex items-center gap-2 py-3 px-6 rounded-xl bg-[#1C1917] hover:bg-[#2C2724] text-white text-xs font-bold uppercase tracking-wider transition"
                                >
                                    <span>{{ $pt['go_catalog_btn'] }}</span>
                                    <i class="fa-solid fa-arrow-right text-[10px]"></i>
                                </a>
                            </div>
                        @else
                            @php
                                $statusMap = [
                                    'processing' => ['label' => $pt['st_new'], 'bg' => '#EFF6FF', 'color' => '#1D4ED8', 'border' => '#BFDBFE'],
                                    'NEW' => ['label' => $pt['st_new'], 'bg' => '#EFF6FF', 'color' => '#1D4ED8', 'border' => '#BFDBFE'],
                                    'PAYMENT_RECEIVED' => ['label' => $pt['st_paid'], 'bg' => '#ECFDF5', 'color' => '#047857', 'border' => '#A7F3D0'],
                                    'awaiting_delivery' => ['label' => $pt['st_packing'], 'bg' => '#FFFBEB', 'color' => '#B45309', 'border' => '#FDE68A'],
                                    'PACKING' => ['label' => $pt['st_packing'], 'bg' => '#FFFBEB', 'color' => '#B45309', 'border' => '#FDE68A'],
                                    'SHIPPED' => ['label' => $pt['st_shipped'], 'bg' => '#F5F3FF', 'color' => '#6D28D9', 'border' => '#DDD6FE'],
                                    'delivered' => ['label' => $pt['st_delivered'], 'bg' => '#F0FDF4', 'color' => '#15803D', 'border' => '#BBF7D0'],
                                    'DELIVERED' => ['label' => $pt['st_delivered'], 'bg' => '#F0FDF4', 'color' => '#15803D', 'border' => '#BBF7D0'],
                                    'CANCELLED' => ['label' => $pt['st_cancelled'], 'bg' => '#FEF2F2', 'color' => '#B91C1C', 'border' => '#FECACA'],
                                    'ARCHIVED' => ['label' => $pt['st_archived'], 'bg' => '#F5F5F4', 'color' => '#57534E', 'border' => '#D6D3D1'],
                                ];
                            @endphp

                            @foreach($orders as $order)
                                @php
                                    $orderNum = $order->order_number;
                                    $receiptUrl = $order->secure_hash
                                        ? route('checkout.receipt', ['orderNumber' => $orderNum, 'orderHash' => $order->secure_hash])
                                        : route('checkout.receipt', ['orderNumber' => $orderNum]);
                                    $st = $statusMap[$order->status] ?? ['label' => $order->status, 'bg' => '#F3F4F6', 'color' => '#374151', 'border' => '#E5E7EB'];
                                    // Stored in USD; so'm only with the rate saved on the order.
                                    $rate = (float) ($order->exchange_rate ?? 0);
                                    $hasUzs = $rate > 0 && $order->amount_uzs !== null;
                                    $amountUsd = (float) $order->amount;
                                    $money = fn (float $usd) => $hasUzs
                                        ? number_format(round($usd * $rate), 0, '.', ' ').' '.$pt['sum_unit']
                                        : '$'.number_format($usd, 2, '.', ',');
                                    $isDelivered = in_array($order->status, ['delivered', 'DELIVERED', 'ARCHIVED'], true);
                                @endphp
                                <div class="bg-white rounded-3xl p-6 border border-[#EAE3DE] shadow-sm space-y-4 hover:border-[#D5CCC7] transition">
                                    <!-- Order Header -->
                                    <div class="flex flex-col sm:flex-row sm:items-center justify-between pb-4 border-b border-[#FAF7F5] gap-3">
                                        <div>
                                            <div class="flex items-center gap-2.5 flex-wrap">
                                                <div
                                                    x-data="{ copied: false }"
                                                    class="flex items-center gap-1.5 font-mono text-base font-bold text-[#1C1917]"
                                                >
                                                    <span>{{ $pt['order_word'] }}</span>
                                                    <button
                                                        type="button"
                                                        @click="navigator.clipboard.writeText('{{ $orderNum }}'); copied = true; setTimeout(() => copied = false, 2000)"
                                                        :class="copied ? 'bg-emerald-600 text-white border-emerald-600' : 'bg-stone-100 hover:bg-[#1C1917] text-[#1C1917] hover:text-white border-stone-200'"
                                                        class="group inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-lg transition font-mono font-extrabold text-sm cursor-pointer border"
                                                        title="{{ $pt['copy_hint'] }}"
                                                    >
                                                        <span>№{{ $orderNum }}</span>
                                                        <i x-show="!copied" class="fa-regular fa-copy text-[11px] text-stone-400 group-hover:text-amber-300 transition"></i>
                                                        <i x-show="copied" x-cloak class="fa-solid fa-check text-[11px] text-white"></i>
                                                    </button>
                                                </div>

                                                <span style="display:inline-flex;align-items:center;gap:6px;padding:4px 10px;border-radius:999px;font-size:11px;font-weight:700;background:{{ $st['bg'] }};color:{{ $st['color'] }};border:1px solid {{ $st['border'] }}">
                                                    <span style="width:6px;height:6px;border-radius:50%;background:{{ $st['color'] }}"></span>
                                                    {{ $st['label'] }}
                                                </span>

                                                <a
                                                    href="{{ $receiptUrl }}"
                                                    wire:navigate
                                                    class="inline-flex items-center gap-1.5 px-3 py-1 rounded-xl bg-[#1C1917] hover:bg-[#2C2724] text-white text-[11px] font-semibold transition shadow-xs active:scale-95 cursor-pointer ml-1"
                                                >
                                                    <i class="fa-solid fa-receipt text-amber-300 text-[10px]"></i>
                                                    <span>{{ $pt['receipt_and_status'] }}</span>
                                                </a>
                                            </div>
                                            <span class="text-[11px] text-[#A8A29E] mt-1 block">
                                                {{ $pt['date_from'] }} {{ $order->created_at?->timezone('Asia/Tashkent')->format('d.m.Y H:i') }}
                                            </span>
                                        </div>

                                        <div class="text-left sm:text-right">
                                            <div class="font-bold text-base text-[#1C1917]">
                                                {{ $hasUzs ? number_format((int) $order->amount_uzs, 0, '.', ' ').' '.$pt['sum_unit'] : $money($amountUsd) }}
                                            </div>
                                            @if($hasUzs)
                                                <div class="text-[11px] text-emerald-600 font-medium">
                                                    = ${{ number_format($amountUsd, 2, '.', ',') }} USD
                                                </div>
                                            @endif
                                        </div>
                                    </div>

                                    <!-- Заголовок блока товаров в профиле -->
                                    <div class="pt-1">
                                        <div class="text-[11px] font-bold uppercase tracking-wider text-[#8C827A] mb-2 flex items-center justify-between">
                                            <span>{{ $pt['order_items'] }} ({{ $order->orderProducts->count() }})</span>
                                            <span class="text-[10px] text-[#A8A29E] font-normal">
                                                {{ $order->orderProducts->sum('quantity') }} {{ $pt['pcs_unit'] }}
                                            </span>
                                        </div>

                                        <div class="divide-y divide-[#FAF7F5] bg-[#FAF7F5]/50 rounded-2xl p-3 border border-[#F2ECE8]">
                                            @if($order->orderProducts->isEmpty())
                                                <p class="text-xs text-[#A8A29E] italic py-2 text-center">
                                                    {{ $pt['forming_items'] }}
                                                </p>
                                            @else
                                                @foreach($order->orderProducts as $item)
                                                    @php
                                                        $prod = $item->product;
                                                        $img = $prod?->image
                                                            ? (str_starts_with($prod->image, 'http') ? $prod->image : asset($prod->image))
                                                            : \App\Models\Product::placeholderUrl();
                                                    @endphp
                                                    <div class="py-2.5 first:pt-1 last:pb-1 flex items-center justify-between gap-4">
                                                        <div class="flex items-center gap-3 flex-1 min-w-0">
                                                            <div class="w-14 h-14 rounded-xl bg-white overflow-hidden flex-shrink-0 border border-[#EAE3DE] shadow-2xs">
                                                                <img src="{{ $img }}" alt="{{ $prod?->name ?? 'Product' }}" class="w-full h-full object-cover" />
                                                            </div>
                                                            <div class="min-w-0 flex-1">
                                                                <h4 class="text-xs font-bold text-[#1C1917] truncate">
                                                                    {{ $prod?->name ?: 'Mally Beauty' }}
                                                                </h4>
                                                                <div class="text-[11px] text-[#8C827A] flex items-center gap-1.5 mt-0.5 font-mono">
                                                                    @if($prod?->sku)
                                                                        <span>{{ $pt['sku_prefix'] }} {{ $prod->sku }} ·</span>
                                                                    @endif
                                                                    <span>{{ $item->quantity }} {{ $pt['pcs_unit'] }} × {{ $money((float) $item->price) }}</span>
                                                                </div>
                                                            </div>
                                                        </div>

                                                        <div class="text-right flex flex-col items-end gap-1.5 flex-shrink-0">
                                                            <span class="font-mono font-bold text-xs text-[#1C1917]">
                                                                {{ $money((float) $item->price * $item->quantity) }}
                                                            </span>

                                                            @if($isDelivered && $prod)
                                                                @php
                                                                    $ordKeyInt = (int) $order->id;
                                                                    $hasReview = !empty($reviewedKeys[$ordKeyInt.':'.$prod->id])
                                                                        || ($prod->sku && !empty($reviewedKeys[$ordKeyInt.':'.$prod->sku]));
                                                                    $reviewedLabel = match ($locale) {
                                                                        'uz' => 'Sharh qoldirildi',
                                                                        'en' => 'Reviewed',
                                                                        default => 'Отзыв оставлен',
                                                                    };
                                                                @endphp
                                                                @if($hasReview)
                                                                    <span class="px-2.5 py-1 bg-emerald-50 text-emerald-700 border border-emerald-200 rounded-lg text-[10px] font-bold flex items-center gap-1">
                                                                        <i class="fa-solid fa-check text-[9px]"></i>
                                                                        <span>{{ $reviewedLabel }}</span>
                                                                    </span>
                                                                @else
                                                                    <button
                                                                        type="button"
                                                                        wire:click="openProductReview('{{ $prod->id }}', '{{ addslashes($prod->name) }}', '{{ addslashes($img) }}', {{ (int) $orderNum }})"
                                                                        class="px-2.5 py-1 bg-amber-500 hover:bg-amber-600 text-white rounded-lg text-[10px] font-bold transition flex items-center gap-1 shadow-2xs hover:shadow-xs cursor-pointer"
                                                                    >
                                                                        <i class="fa-solid fa-star text-[9px]"></i>
                                                                        <span>{{ $pt['leave_review'] }}</span>
                                                                    </button>
                                                                @endif
                                                            @endif
                                                        </div>
                                                    </div>
                                                @endforeach
                                            @endif
                                        </div>
                                    </div>

                                    <!-- Delivery address info & Telegram contact -->
                                    <div class="pt-2 text-xs text-[#78716C] flex flex-col sm:flex-row sm:items-center justify-between gap-2 border-t border-[#FAF7F5]">
                                        <div class="flex items-center gap-2">
                                            <i class="fa-solid fa-location-dot text-[11px] text-amber-600"></i>
                                            <span>{{ $pt['delivery_label'] }} {{ $pt['city_prefix'] }}{{ $order->city ?: $pt['tashkent'] }}, {{ $order->address ?: $pt['clarifying'] }}</span>
                                        </div>
                                        <div class="flex items-center gap-3">
                                            <a
                                                href="https://t.me/mallyuz?text={{ rawurlencode($pt['tg_hello'].$orderNum) }}"
                                                target="_blank"
                                                rel="noreferrer"
                                                class="inline-flex items-center gap-1 text-[11px] text-[#0088cc] hover:underline font-medium"
                                            >
                                                <i class="fa-brands fa-telegram text-xs"></i>
                                                <span>{{ $pt['tg_support'] }}</span>
                                            </a>
                                        </div>
                                    </div>
                                </div>
                            @endforeach
                        @endif
                    </div>
                @endif

                <!-- TAB 2: Personal Details -->
                @if($activeTab === 'details')
                    <div class="bg-white rounded-3xl p-6 sm:p-8 border border-[#EAE3DE] shadow-sm max-w-2xl">
                        <h3 class="font-serif text-lg font-bold text-[#1C1917] mb-4">
                            {{ $pt['personal_details_title'] }}
                        </h3>
                        <form wire:submit.prevent="saveSettings" class="space-y-4">
                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                <div>
                                    <label class="block text-xs font-semibold text-[#78716C] mb-1">
                                        {{ $pt['first_name'] }} *
                                    </label>
                                    <input
                                        type="text"
                                        required
                                        wire:model="firstName"
                                        placeholder="{{ $pt['first_name'] }}"
                                        class="w-full px-4 py-2.5 bg-[#FAF8F6] border border-[#E3DBD5] rounded-xl text-xs focus:outline-none focus:border-[#1C1917]"
                                    />
                                </div>
                                <div>
                                    <label class="block text-xs font-semibold text-[#78716C] mb-1">
                                        {{ $pt['last_name'] }}
                                    </label>
                                    <input
                                        type="text"
                                        wire:model="lastName"
                                        placeholder="{{ $pt['last_name'] }}"
                                        class="w-full px-4 py-2.5 bg-[#FAF8F6] border border-[#E3DBD5] rounded-xl text-xs focus:outline-none focus:border-[#1C1917]"
                                    />
                                </div>
                            </div>

                            <div>
                                <div class="flex items-center justify-between mb-1">
                                    <label class="block text-xs font-semibold text-[#78716C]">
                                        {{ $pt['phone'] }}
                                    </label>
                                    <span class="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-full bg-emerald-50 text-emerald-700 border border-emerald-200 text-[11px] font-semibold">
                                        <i class="fa-solid fa-check text-[10px]"></i>
                                        <span>{{ $pt['verified'] }}</span>
                                    </span>
                                </div>
                                <input
                                    type="tel"
                                    disabled
                                    value="{{ $phone }}"
                                    class="w-full px-4 py-2.5 bg-emerald-50/40 border border-emerald-200/70 rounded-xl text-xs font-mono font-bold text-emerald-950"
                                />
                            </div>

                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                <div>
                                    <label class="block text-xs font-semibold text-[#78716C] mb-1">
                                        {{ $pt['city'] }}
                                    </label>
                                    <input
                                        type="text"
                                        wire:model="city"
                                        placeholder="{{ $pt['tashkent'] }}"
                                        class="w-full px-4 py-2.5 bg-[#FAF8F6] border border-[#E3DBD5] rounded-xl text-xs focus:outline-none focus:border-[#1C1917]"
                                    />
                                </div>
                                <div>
                                    <label class="block text-xs font-semibold text-[#78716C] mb-1">
                                        {{ $pt['address'] }}
                                    </label>
                                    <input
                                        type="text"
                                        wire:model="address"
                                        placeholder="{{ $pt['address'] }}"
                                        class="w-full px-4 py-2.5 bg-[#FAF8F6] border border-[#E3DBD5] rounded-xl text-xs focus:outline-none focus:border-[#1C1917]"
                                    />
                                </div>
                            </div>

                            <button
                                type="submit"
                                class="w-full py-3 bg-[#1C1917] hover:bg-black text-white text-xs font-bold uppercase tracking-wider rounded-xl transition shadow-md mt-2 cursor-pointer"
                            >
                                {{ $pt['save_btn'] }}
                            </button>
                        </form>
                    </div>
                @endif
            </div>
        @endif
    </main>

    <!-- Модальное окно отзыва для доставленного товара -->
    @if($reviewTarget)
        <div
            wire:click="closeReviewModal"
            class="fixed inset-0 z-50 bg-black/35 backdrop-blur-md flex items-center justify-center p-4 animate-fade-in"
        >
            <div
                wire:click.stop
                class="bg-white w-full max-w-lg rounded-3xl p-6 sm:p-8 border border-[#EAE3DE] shadow-2xl space-y-4"
            >
                <div class="flex justify-between items-center">
                    <div class="flex items-center gap-3">
                        @if(!empty($reviewTarget['image']))
                            <div class="w-14 h-14 rounded-2xl bg-stone-100 overflow-hidden border border-stone-200 flex-shrink-0 shadow-2xs">
                                <img src="{{ $reviewTarget['image'] }}" alt="" class="w-full h-full object-cover" />
                            </div>
                        @endif
                        <div class="min-w-0 flex-1">
                            <span class="text-[10px] uppercase font-bold text-amber-600 block">
                                {{ $pt['review_header'] }}{{ $reviewTarget['orderNumber'] ?? '' }}
                            </span>
                            <h3 class="font-serif text-base font-bold text-[#1C1917] truncate">
                                {{ $reviewTarget['title'] ?? '' }}
                            </h3>
                        </div>
                    </div>
                    <button
                        type="button"
                        wire:click="closeReviewModal"
                        class="w-8 h-8 rounded-full bg-[#FAF7F5] text-[#78716C] hover:text-black flex items-center justify-center transition cursor-pointer"
                    >
                        <i class="fa-solid fa-xmark text-sm"></i>
                    </button>
                </div>

                <form wire:submit.prevent="submitReview" class="space-y-4">
                    <div>
                        <label class="block text-xs font-semibold text-[#78716C] mb-1">
                            {{ $pt['your_rating'] }}
                        </label>
                        <div class="flex gap-2 text-2xl text-amber-400">
                            @foreach([1, 2, 3, 4, 5] as $star)
                                <button
                                    type="button"
                                    wire:click="$set('reviewRating', {{ $star }})"
                                    class="hover:scale-110 transition cursor-pointer"
                                >
                                    <i class="fa-{{ $star <= $reviewRating ? 'solid' : 'regular' }} fa-star"></i>
                                </button>
                            @endforeach
                        </div>
                    </div>

                    <div>
                        <label class="block text-xs font-semibold text-[#78716C] mb-1">
                            {{ $pt['review_text_label'] }} <span class="text-rose-500">*</span>
                        </label>
                        <textarea
                            required
                            rows="3"
                            wire:model="reviewComment"
                            placeholder="{{ $pt['review_placeholder'] }}"
                            class="w-full p-3 text-xs bg-[#FAF8F6] border border-[#E3DBD5] rounded-xl focus:outline-none focus:border-[#1C1917]"
                        ></textarea>
                    </div>

                    <div>
                        <label class="block text-xs font-semibold text-[#78716C] mb-1.5">
                            {{ $pt['review_photos_label'] }}
                        </label>
                        <div class="flex gap-2">
                            <input
                                type="url"
                                wire:model="reviewPhotoUrl"
                                placeholder="{{ $pt['review_photo_placeholder'] }}"
                                class="flex-1 px-3 py-2 text-xs bg-[#FAF8F6] border border-[#E3DBD5] rounded-xl focus:outline-none"
                            />
                            <button
                                type="button"
                                wire:click="addReviewPhoto"
                                class="px-3 py-2 bg-stone-200 hover:bg-stone-300 text-stone-800 text-xs font-semibold rounded-xl cursor-pointer"
                            >
                                {{ $pt['add_link_btn'] }}
                            </button>
                        </div>

                        @if(count($reviewPhotos) > 0)
                            <div class="flex gap-2 mt-2.5 flex-wrap">
                                @foreach($reviewPhotos as $idx => $photo)
                                    <div class="relative w-14 h-14 rounded-xl overflow-hidden border border-[#EAE3DE] group shadow-2xs">
                                        <img src="{{ $photo }}" alt="" class="w-full h-full object-cover" />
                                        <button
                                            type="button"
                                            wire:click="removeReviewPhoto({{ $idx }})"
                                            class="absolute inset-0 bg-black/60 text-white flex items-center justify-center opacity-0 group-hover:opacity-100 transition text-xs cursor-pointer"
                                        >
                                            <i class="fa-solid fa-trash-can"></i>
                                        </button>
                                    </div>
                                @endforeach
                            </div>
                        @endif
                    </div>

                    <button
                        type="submit"
                        class="w-full py-3.5 bg-[#1C1917] hover:bg-black text-white text-xs font-bold uppercase tracking-wider rounded-xl transition shadow-md cursor-pointer"
                    >
                        {{ $pt['publish_review_btn'] }}
                    </button>
                </form>
            </div>
        </div>
    @endif

    <x-storefront.footer />
    <x-storefront.auth-modal />
</div>
