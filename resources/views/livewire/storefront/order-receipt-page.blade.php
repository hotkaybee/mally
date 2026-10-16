@php
    $orderNum = $order->order_number ?: $order->id;
    // Order amounts are stored in USD. So'm values use the CBU rate saved
    // with the order, never today's rate.
    $rate = (float) ($order->exchange_rate ?? 0);
    $hasUzs = $rate > 0 && $order->amount_uzs !== null;
    $amountUsd = (float) $order->amount;
    $sumUnit = match ($locale) { 'uz' => "so'm", 'en' => 'UZS', default => 'сум' };
    $money = fn (float $usd) => $hasUzs
        ? number_format(round($usd * $rate), 0, '.', ' ').' '.$sumUnit
        : '$'.number_format($usd, 2, '.', ',');
    $totalDisplay = $hasUzs ? number_format((int) $order->amount_uzs, 0, '.', ' ').' '.$sumUnit : $money($amountUsd);
    $totalCopyValue = $hasUzs ? (string) (int) $order->amount_uzs : number_format($amountUsd, 2, '.', '');

    $statusNormalized = match ($order->status) {
        'processing', 'new', 'NEW' => 'NEW',
        'paid', 'PAYMENT_RECEIVED' => 'PAYMENT_RECEIVED',
        'awaiting_delivery', 'PACKING' => 'PACKING',
        'on_the_way', 'SHIPPED' => 'SHIPPED',
        'delivered', 'DELIVERED' => 'DELIVERED',
        'cancelled', 'CANCELLED' => 'CANCELLED',
        'ARCHIVED' => 'ARCHIVED',
        default => 'NEW',
    };

    $rt = match ($locale) {
        'uz' => [
            'home' => 'Bosh sahifa',
            'profile' => 'Shaxsiy kabinet',
            'receipt_title' => 'Buyurtma cheki №',
            'refresh_status' => 'Holatni yangilash',
            'order_label' => 'Buyurtma:',
            'copy_order_hint' => 'Buyurtma raqamini nusxalash',
            'step_new' => 'Rasmiylashtirildi',
            'step_paid' => "To'landi",
            'step_packing' => "Yig'ilmoqda",
            'step_shipped' => "Yo'lda",
            'step_delivered' => 'Topshirildi',
            'badge_orig' => 'Mally USA • 100% Original',
            'finance_label' => 'Moliyaviy hisob-kitob',
            'finance_paid_title' => "Buyurtma to'lovi qayd etildi",
            'finance_pending_title' => "Buyurtmani to'lash uchun rekvizitlar",
            'amount_due_label' => "To'lov summasi:",
            'sum_unit' => "so'm",
            'copy_amount_hint' => 'Summani nusxalash',
            'usd_rate_note' => 'MB kursi bo‘yicha',
            'direct_transfer' => "To'g'ridan-to'g'ri o'tkazma",
            'card_number_label' => 'Qabul qiluvchi karta raqami:',
            'copied' => 'Nusxalandi!',
            'copy' => 'Nusxalash',
            'recipient_card_label' => 'Qabul qiluvchi:',
            'purpose_label' => 'Maqsad:',
            'order_no' => 'Buyurtma №',
            'how_to_pay_title' => "Buyurtmani qanday to'lash mumkin:",
            'pay_step_1' => 'Yuqoridagi karta raqamini nusxalab oling.',
            'pay_step_2' => "Istalgan ilova (Payme, Click, Uzum) orqali <b>".$totalDisplay."</b> o'tkazing.",
            'pay_step_3' => 'Quyidagi tugmani bosing va chek yoki skrinshotni Telegramda menejerga yuboring.',
            'send_receipt_btn' => 'Chekni administratorga yuborish',
            'manager_confirm_note' => "Menejer to'lovni darhol tasdiqlaydi va buyurtmani yig'ishga topshiradi",
            'paid_box_title' => "To'lov do'kon tomonidan muvaffaqiyatli tasdiqlandi",
            'paid_box_desc' => number_format($totalAmountUzs, 0, '.', ' ')." so'm qabul qilindi. Qayta to'lov talab qilinmaydi.",
            'write_manager_btn' => 'Menejerga yozish',
            'items_title' => 'Buyurtmadagi mahsulotlar',
            'pcs_unit' => 'dona',
            'sku_prefix' => 'Art:',
            'promo_label' => 'Promokod',
            'total_label' => "Jami to'lov:",
            'delivery_details_title' => "Yetkazib berish ma'lumotlari",
            'customer_label' => 'Qabul qiluvchi:',
            'customer_default' => 'Xaridor',
            'phone_label' => 'Telefon:',
            'address_label' => 'Manzil:',
            'city_prefix' => 'sh. ',
            'tashkent' => 'Toshkent',
            'clarifying' => 'Aniqlashtirilmoqda',
            'comment_label' => 'Izoh:',
            'my_orders_btn' => 'Shaxsiy kabinet (Buyurtmalarim)',
            'back_catalog_btn' => 'Katalogga qaytish',
            'st_paid_title' => "To'lov muvaffaqiyatli tasdiqlandi!",
            'st_paid_sub' => "Administrator chekni tekshirdi va to'lovni tasdiqladi. Buyurtmangiz omborda yig'ishga tayyorlanmoqda.",
            'st_packing_title' => 'Buyurtmangiz ehtiyotkorlik bilan qadoqlanmoqda',
            'st_packing_sub' => "Ombor bo'limi original kosmetikangizni maxsus himoya qutisiga joylashtirmoqda.",
            'st_shipped_title' => "Buyurtma kuryerga topshirildi va yo'lda!",
            'st_shipped_sub' => 'Kuryer buyurtmani manzilingizga yetkazmoqda. Yetkazishdan oldin kuryer siz bilan bog‘lanadi.',
            'st_delivered_title' => 'Buyurtma muvaffaqiyatli yetkazildi!',
            'st_delivered_sub' => 'Mally ni tanlaganingiz uchun tashakkur! Original kosmetikamiz sizga mamnuniyat bag‘ishlashiga tilakdoshmiz.',
            'st_cancelled_title' => 'Buyurtma bekor qilindi',
            'st_cancelled_sub' => 'Ushbu buyurtma bekor qilingan. Savollaringiz bo‘lsa, Telegram orqali menejerimizga yozing.',
            'st_new_title' => "Buyurtma qabul qilindi — To'lov kutilmoqda",
            'st_new_sub' => "Mahsulotlar band qilindi. Iltimos, quyidagi do'kon kartasiga o'tkazmani amalga oshiring va chekni Telegramga yuboring.",
        ],
        'en' => [
            'home' => 'Home',
            'profile' => 'My Account',
            'receipt_title' => 'Order Receipt #',
            'refresh_status' => 'Refresh Status',
            'order_label' => 'Order:',
            'copy_order_hint' => 'Copy order number',
            'step_new' => 'Placed',
            'step_paid' => 'Paid',
            'step_packing' => 'Packing',
            'step_shipped' => 'On the Way',
            'step_delivered' => 'Delivered',
            'badge_orig' => 'Mally USA • 100% Authentic',
            'finance_label' => 'Financial Summary',
            'finance_paid_title' => 'Order Payment Confirmed',
            'finance_pending_title' => 'Payment Details for Your Order',
            'amount_due_label' => 'Amount Due:',
            'sum_unit' => 'UZS',
            'copy_amount_hint' => 'Copy amount',
            'usd_rate_note' => 'at CBU rate',
            'direct_transfer' => 'Direct Transfer',
            'card_number_label' => 'Recipient Card Number:',
            'copied' => 'Copied!',
            'copy' => 'Copy',
            'recipient_card_label' => 'Recipient:',
            'purpose_label' => 'Reference:',
            'order_no' => 'Order #',
            'how_to_pay_title' => 'How to pay for your order:',
            'pay_step_1' => 'Copy the card number above.',
            'pay_step_2' => 'Transfer <b>'.$totalDisplay.'</b> via any banking app (Payme, Click, Uzum).',
            'pay_step_3' => 'Click the button below and send the payment receipt or screenshot to our manager on Telegram.',
            'send_receipt_btn' => 'Send Receipt to Administrator',
            'manager_confirm_note' => 'Our manager will promptly confirm your payment and forward the order for packing',
            'paid_box_title' => 'Payment confirmed by store',
            'paid_box_desc' => 'Amount of '.$totalDisplay.' has been received. No further payment is required.',
            'write_manager_btn' => 'Contact Manager',
            'items_title' => 'Items in Order',
            'pcs_unit' => 'pcs',
            'sku_prefix' => 'SKU:',
            'promo_label' => 'Promo Code',
            'total_label' => 'Total Due:',
            'delivery_details_title' => 'Delivery Details',
            'customer_label' => 'Recipient:',
            'customer_default' => 'Customer',
            'phone_label' => 'Phone:',
            'address_label' => 'Address:',
            'city_prefix' => '',
            'tashkent' => 'Tashkent',
            'clarifying' => 'To be confirmed',
            'comment_label' => 'Comment:',
            'my_orders_btn' => 'My Account (My Orders)',
            'back_catalog_btn' => 'Back to Catalog',
            'st_paid_title' => 'Payment Successfully Confirmed!',
            'st_paid_sub' => 'The administrator verified your receipt and confirmed payment. Your order is being prepared for packing.',
            'st_packing_title' => 'Your Order is Being Carefully Packed',
            'st_packing_sub' => 'Our warehouse team is packing your authentic cosmetics into a protective Mally box.',
            'st_shipped_title' => 'Order Handed Over to Courier & On the Way!',
            'st_shipped_sub' => 'Courier delivery is heading to your address. The courier will call you prior to arrival.',
            'st_delivered_title' => 'Order Successfully Delivered!',
            'st_delivered_sub' => 'Thank you for choosing Mally! We hope our authentic beauty products bring you joy.',
            'st_cancelled_title' => 'Order Cancelled',
            'st_cancelled_sub' => 'This order was cancelled. If you have any questions, please message our manager on Telegram.',
            'st_new_title' => 'Order Received — Awaiting Payment',
            'st_new_sub' => 'We have reserved your items. Please transfer the amount to the store card below and send the receipt via Telegram.',
        ],
        default => [
            'home' => 'Главная',
            'profile' => 'Личный кабинет',
            'receipt_title' => 'Чек заказа №',
            'refresh_status' => 'Обновить статус',
            'order_label' => 'Заказ:',
            'copy_order_hint' => 'Скопировать номер заказа',
            'step_new' => 'Оформлен',
            'step_paid' => 'Оплачен',
            'step_packing' => 'Сборка',
            'step_shipped' => 'В пути',
            'step_delivered' => 'Вручен',
            'badge_orig' => 'Mally USA • 100% Оригинал',
            'finance_label' => 'Финансовый расчет',
            'finance_paid_title' => 'Оплата заказа зафиксирована',
            'finance_pending_title' => 'Реквизиты для оплаты заказа',
            'amount_due_label' => 'Сумма к оплате:',
            'sum_unit' => 'сум',
            'copy_amount_hint' => 'Скопировать сумму',
            'usd_rate_note' => 'по курсу ЦБ',
            'direct_transfer' => 'Прямой перевод',
            'card_number_label' => 'Номер карты получателя:',
            'copied' => 'Скопировано!',
            'copy' => 'Скопировать',
            'recipient_card_label' => 'Получатель:',
            'purpose_label' => 'Назначение:',
            'order_no' => 'Заказ №',
            'how_to_pay_title' => 'Как оплатить заказ:',
            'pay_step_1' => 'Скопируйте номер карты выше.',
            'pay_step_2' => 'Переведите сумму <b>'.$totalDisplay.'</b> через любое приложение (Payme, Click, Uzum).',
            'pay_step_3' => 'Нажмите кнопку ниже и отправьте чек или скриншот менеджеру в Telegram.',
            'send_receipt_btn' => 'Отправить чек администратору',
            'manager_confirm_note' => 'Менеджер мгновенно подтвердит оплату и передаст заказ на сборку',
            'paid_box_title' => 'Оплата успешно подтверждена магазином',
            'paid_box_desc' => 'Сумма '.$totalDisplay.' зачислена. Повторная оплата не требуется.',
            'write_manager_btn' => 'Написать менеджеру',
            'items_title' => 'Товары в заказе',
            'pcs_unit' => 'шт.',
            'sku_prefix' => 'Арт:',
            'promo_label' => 'Промокод',
            'total_label' => 'Итого к оплате:',
            'delivery_details_title' => 'Данные доставки',
            'customer_label' => 'Получатель:',
            'customer_default' => 'Покупатель',
            'phone_label' => 'Телефон:',
            'address_label' => 'Адрес:',
            'city_prefix' => 'г. ',
            'tashkent' => 'Ташкент',
            'clarifying' => 'Уточняется',
            'comment_label' => 'Комментарий:',
            'my_orders_btn' => 'Личный кабинет (Мои заказы)',
            'back_catalog_btn' => 'Вернуться в каталог',
            'st_paid_title' => 'Оплата успешно подтверждена!',
            'st_paid_sub' => 'Администратор проверил чек и подтвердил поступление средств. Ваш заказ готовится к комплектации на складе.',
            'st_packing_title' => 'Заказ бережно упаковывается',
            'st_packing_sub' => 'Складской отдел комплектует вашу оригинальную косметику в фирменную защитную коробку Mally с наполнителем.',
            'st_shipped_title' => 'Заказ передан курьеру и уже в пути!',
            'st_shipped_sub' => 'Курьерская доставка направляется по вашему адресу. Перед доставкой курьер свяжется с вами по указанному телефону.',
            'st_delivered_title' => 'Заказ успешно доставлен!',
            'st_delivered_sub' => 'Благодарим вас за выбор Mally! Надеемся, оригинальная косметика подарит вам истинное эстетическое удовольствие.',
            'st_cancelled_title' => 'Заказ отменён',
            'st_cancelled_sub' => 'Этот заказ был отменён. Если у вас возникли вопросы или произошла ошибка, напишите нашему менеджеру в Telegram.',
            'st_new_title' => 'Заказ принят — Ожидает оплаты',
            'st_new_sub' => 'Мы зарезервировали товары. Пожалуйста, совершите перевод на карту магазина ниже и отправьте чек в Telegram для оперативной отправки.',
        ],
    };

    $stepDefinitions = [
        ['key' => 'NEW', 'label' => $rt['step_new'], 'icon' => 'fa-file-invoice'],
        ['key' => 'PAYMENT_RECEIVED', 'label' => $rt['step_paid'], 'icon' => 'fa-credit-card'],
        ['key' => 'PACKING', 'label' => $rt['step_packing'], 'icon' => 'fa-box-open'],
        ['key' => 'SHIPPED', 'label' => $rt['step_shipped'], 'icon' => 'fa-truck-fast'],
        ['key' => 'DELIVERED', 'label' => $rt['step_delivered'], 'icon' => 'fa-circle-check'],
    ];

    $currentStepIdx = match ($statusNormalized) {
        'NEW' => 0,
        'PAYMENT_RECEIVED' => 1,
        'PACKING' => 2,
        'SHIPPED' => 3,
        'DELIVERED' => 4,
        default => -1,
    };

    $isPaidOrHigher = in_array($statusNormalized, ['PAYMENT_RECEIVED', 'PACKING', 'SHIPPED', 'DELIVERED'], true) || $order->payment_status === 'PAID';

    $statusTheme = match ($statusNormalized) {
        'PAYMENT_RECEIVED' => [
            'title' => $rt['st_paid_title'],
            'subtitle' => $rt['st_paid_sub'],
            'cardGradient' => 'from-emerald-500/15 via-emerald-500/5 to-white',
            'border' => 'border-emerald-200',
            'icon' => 'fa-circle-check',
            'iconColor' => 'bg-emerald-500 text-white shadow-emerald-500/30',
            'pulse' => false,
        ],
        'PACKING' => [
            'title' => $rt['st_packing_title'],
            'subtitle' => $rt['st_packing_sub'],
            'cardGradient' => 'from-orange-500/15 via-orange-500/5 to-white',
            'border' => 'border-orange-200',
            'icon' => 'fa-box-open',
            'iconColor' => 'bg-orange-500 text-white shadow-orange-500/30',
            'pulse' => true,
        ],
        'SHIPPED' => [
            'title' => $rt['st_shipped_title'],
            'subtitle' => $rt['st_shipped_sub'],
            'cardGradient' => 'from-indigo-500/15 via-indigo-500/5 to-white',
            'border' => 'border-indigo-200',
            'icon' => 'fa-truck-fast',
            'iconColor' => 'bg-indigo-600 text-white shadow-indigo-500/30',
            'pulse' => true,
        ],
        'DELIVERED' => [
            'title' => $rt['st_delivered_title'],
            'subtitle' => $rt['st_delivered_sub'],
            'cardGradient' => 'from-emerald-600/15 via-amber-500/5 to-white',
            'border' => 'border-emerald-300',
            'icon' => 'fa-gift',
            'iconColor' => 'bg-emerald-600 text-white shadow-emerald-500/30',
            'pulse' => false,
        ],
        'CANCELLED' => [
            'title' => $rt['st_cancelled_title'],
            'subtitle' => $rt['st_cancelled_sub'],
            'cardGradient' => 'from-rose-500/15 via-rose-500/5 to-white',
            'border' => 'border-rose-200',
            'icon' => 'fa-ban',
            'iconColor' => 'bg-rose-500 text-white shadow-rose-500/30',
            'pulse' => false,
        ],
        default => [
            'title' => $rt['st_new_title'],
            'subtitle' => $rt['st_new_sub'],
            'cardGradient' => 'from-amber-500/15 via-amber-500/5 to-white',
            'border' => 'border-amber-200',
            'icon' => 'fa-credit-card',
            'iconColor' => 'bg-amber-500 text-white shadow-amber-500/30',
            'pulse' => true,
        ],
    };

    $tgMessage = rawurlencode(
        "Здравствуйте! Я по поводу заказа №{$orderNum} на сумму {$totalDisplay}."
    );
    $tgLink = "https://t.me/{$adminTelegram}?text={$tgMessage}";
@endphp

<div class="min-h-screen bg-[#FCECEC] text-[#1C1917] flex flex-col font-sans print:bg-white print:p-0" x-data="{ copiedCard: false, copiedAmount: false, copiedOrder: false }">
    <div class="print:hidden">
        <x-storefront.header />
    </div>

    <main class="max-w-4xl mx-auto px-4 py-8 sm:py-12 w-full flex-1 space-y-6">
        <!-- Верхняя навигация и статусная плашка -->
        <div class="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-3 print:hidden">
            <div class="flex items-center gap-2 text-xs text-[#78716C]">
                <a href="{{ route('home') }}" wire:navigate class="hover:text-[#1C1917] transition">{{ $rt['home'] }}</a>
                <span>/</span>
                <a href="{{ route('profile') }}" wire:navigate class="hover:text-[#1C1917] transition">{{ $rt['profile'] }}</a>
                <span>/</span>
                <span class="font-semibold text-[#1C1917]">{{ $rt['receipt_title'] }}{{ $orderNum }}</span>
            </div>

            <div class="flex items-center gap-2 self-end sm:self-auto">
                <button
                    type="button"
                    wire:click="refreshOrderStatus"
                    class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-xl bg-white border border-[#EAE3DE] hover:border-[#1C1917] text-xs font-medium text-[#57534E] hover:text-[#1C1917] transition shadow-2xs cursor-pointer active:scale-95"
                >
                    <i class="fa-solid fa-arrows-rotate text-[11px] text-stone-400" wire:loading.class="animate-spin text-amber-600" wire:target="refreshOrderStatus"></i>
                    <span>{{ $rt['refresh_status'] }}</span>
                </button>
            </div>
        </div>

        <!-- ГЛАВНАЯ КАРТОЧКА СТАТУСА ЗАКАЗА -->
        <div class="bg-gradient-to-b {{ $statusTheme['cardGradient'] }} rounded-3xl p-6 sm:p-8 border-2 {{ $statusTheme['border'] }} shadow-lg relative overflow-hidden transition-all duration-300">
            <div class="absolute top-0 right-0 translate-x-10 -translate-y-10 w-48 h-48 rounded-full bg-white/40 blur-2xl pointer-events-none"></div>

            <div class="relative flex flex-col items-center text-center space-y-4">
                <div class="w-16 h-16 sm:w-20 sm:h-20 rounded-3xl {{ $statusTheme['iconColor'] }} flex items-center justify-center text-2xl sm:text-3xl shadow-xl">
                    <i class="fa-solid {{ $statusTheme['icon'] }} {{ $statusTheme['pulse'] ? 'animate-pulse' : '' }}"></i>
                </div>

                <div class="space-y-1">
                    <div class="inline-flex items-center gap-2 px-3 py-1 bg-white/90 backdrop-blur-sm rounded-full border border-stone-200/80 shadow-2xs">
                        <span class="text-[10px] uppercase font-bold tracking-widest text-[#8C827A]">{{ $rt['order_label'] }}</span>
                        <button
                            type="button"
                            @click="navigator.clipboard.writeText('{{ $orderNum }}'); copiedOrder = true; setTimeout(() => copiedOrder = false, 2000)"
                            :class="copiedOrder ? 'bg-emerald-600 text-white border-emerald-600' : 'bg-stone-100 hover:bg-[#1C1917] text-[#1C1917] hover:text-white border-stone-200'"
                            class="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-lg font-mono text-sm font-extrabold transition cursor-pointer border"
                            title="{{ $rt['copy_order_hint'] }}"
                        >
                            <span>№{{ $orderNum }}</span>
                            <i class="fa-solid text-xs" :class="copiedOrder ? 'fa-check text-white' : 'fa-copy opacity-60'"></i>
                        </button>
                    </div>

                    <h1 class="font-serif text-2xl sm:text-3xl font-bold tracking-tight text-[#1C1917] pt-1">
                        {{ $statusTheme['title'] }}
                    </h1>

                    <p class="text-xs sm:text-sm text-[#57534E] max-w-xl mx-auto leading-relaxed pt-1">
                        {{ $statusTheme['subtitle'] }}
                    </p>
                </div>

                <!-- Шкала выполнения заказа (Stepper) -->
                @if($statusNormalized !== 'CANCELLED' && $statusNormalized !== 'ARCHIVED')
                    <div class="w-full max-w-2xl pt-4 pb-2">
                        <div class="grid grid-cols-5 gap-1 sm:gap-2 relative">
                            @foreach($stepDefinitions as $idx => $step)
                                @php
                                    $isDone = $idx <= $currentStepIdx;
                                    $isCurrent = $idx === $currentStepIdx;
                                @endphp
                                <div class="flex flex-col items-center text-center space-y-2 relative">
                                    <div class="w-9 h-9 sm:w-11 sm:h-11 rounded-2xl flex items-center justify-center text-xs sm:text-sm transition-all duration-300 z-10 {{ $isCurrent ? 'bg-[#1C1917] text-white ring-4 ring-black/10 scale-110 shadow-md' : ($isDone ? 'bg-emerald-500 text-white shadow-xs' : 'bg-white/80 text-stone-400 border border-stone-200') }}">
                                        <i class="fa-solid {{ $step['icon'] }}"></i>
                                    </div>
                                    <span class="text-[10px] sm:text-xs font-semibold leading-tight {{ $isCurrent ? 'text-[#1C1917] font-bold' : ($isDone ? 'text-emerald-700' : 'text-stone-400') }}">
                                        {{ $step['label'] }}
                                    </span>
                                </div>
                            @endforeach
                        </div>
                    </div>
                @endif

                <div class="pt-2 flex flex-wrap justify-center items-center gap-2 text-xs">
                    <span class="px-3 py-1 bg-white/85 rounded-full text-[#78716C] border border-[#EAE3DE] flex items-center gap-1.5">
                        <i class="fa-regular fa-calendar text-[11px]"></i>
                        <span>{{ $order->created_at?->timezone('Asia/Tashkent')->format('d.m.Y, H:i') }}</span>
                    </span>
                    <span class="px-3 py-1 bg-white/85 rounded-full text-emerald-700 border border-emerald-200 font-medium flex items-center gap-1.5">
                        <i class="fa-solid fa-shield-halved text-[11px]"></i>
                        <span>{{ $rt['badge_orig'] }}</span>
                    </span>
                </div>
            </div>
        </div>

        <!-- АКЦЕНТНЫЙ БЛОК ОПЛАТЫ И РЕКВИЗИТОВ КАРТЫ -->
        <div class="bg-white rounded-3xl p-6 sm:p-8 border border-[#EAE3DE] shadow-sm space-y-6">
            <div class="flex flex-col sm:flex-row sm:items-center justify-between pb-4 border-b border-[#F2ECE8] gap-3">
                <div>
                    <span class="text-[10px] uppercase font-bold tracking-widest text-[#8C827A] block">
                        {{ $rt['finance_label'] }}
                    </span>
                    <h2 class="font-serif text-xl font-bold text-[#1C1917]">
                        {{ $isPaidOrHigher ? $rt['finance_paid_title'] : $rt['finance_pending_title'] }}
                    </h2>
                </div>

                <div class="text-left sm:text-right">
                    <span class="text-[11px] text-[#78716C] block">{{ $rt['amount_due_label'] }}</span>
                    <div class="flex items-center sm:justify-end gap-2">
                        <span class="font-mono text-2xl sm:text-3xl font-extrabold text-[#1C1917]">
                            {{ $totalDisplay }}
                        </span>
                        <button
                            type="button"
                            @click="navigator.clipboard.writeText('{{ $totalCopyValue }}'); copiedAmount = true; setTimeout(() => copiedAmount = false, 2000)"
                            class="p-1 text-stone-400 hover:text-black transition cursor-pointer"
                            title="{{ $rt['copy_amount_hint'] }}"
                        >
                            <i class="fa-solid text-sm" :class="copiedAmount ? 'fa-check text-emerald-600' : 'fa-copy'"></i>
                        </button>
                    </div>
                    @if($hasUzs)
                        <span class="text-xs text-emerald-600 font-medium font-mono">
                            = ${{ number_format($amountUsd, 2, '.', ',') }} USD · 1 USD = {{ number_format($rate, 2, '.', ' ') }} {{ $sumUnit }} ({{ $rt['usd_rate_note'] }})
                        </span>
                    @endif
                </div>
            </div>

            @if(!$isPaidOrHigher)
                <!-- РЕЖИМ 1: ОЖИДАЕТСЯ ОПЛАТА — ЯРКИЙ АКЦЕНТ НА КАРТУ -->
                <div class="grid grid-cols-1 md:grid-cols-12 gap-6 items-stretch">
                    <!-- Премиальная карточка банка Humo / Uzcard -->
                    <div class="md:col-span-7 bg-gradient-to-br from-[#1C1917] via-[#2A2624] to-[#141211] text-white rounded-3xl p-6 shadow-xl flex flex-col justify-between relative overflow-hidden min-h-[220px]">
                        <div class="absolute top-0 right-0 w-44 h-44 rounded-full bg-white/5 blur-xl pointer-events-none"></div>
                        <div class="absolute bottom-0 right-10 w-28 h-28 rounded-full bg-amber-500/10 blur-xl pointer-events-none"></div>

                        <div class="flex items-center justify-between relative z-10">
                            <div class="flex items-center gap-2">
                                <span class="font-serif text-lg font-bold tracking-tight text-white">mally</span>
                                <span class="text-[9px] uppercase tracking-widest text-[#A8A29E]">• Store USA</span>
                            </div>
                            <div class="flex items-center gap-1.5 text-xs font-bold text-amber-400">
                                <i class="fa-solid fa-credit-card"></i>
                                <span>Humo / Uzcard</span>
                            </div>
                        </div>

                        <div class="my-3 flex items-center justify-between relative z-10">
                            <div class="w-10 h-7 rounded-md bg-gradient-to-r from-amber-300 via-yellow-200 to-amber-400 border border-amber-400/50 shadow-inner flex items-center justify-center">
                                <span class="w-5 h-4 border-t border-b border-amber-600/40 rounded-xs"></span>
                            </div>
                            <span class="text-[10px] text-stone-400 uppercase tracking-widest font-mono">{{ $rt['direct_transfer'] }}</span>
                        </div>

                        <div class="space-y-1 relative z-10">
                            <span class="text-[10px] text-[#A8A29E] uppercase tracking-wider block">{{ $rt['card_number_label'] }}</span>
                            <div class="flex items-center justify-between gap-3 bg-white/10 backdrop-blur-md px-3.5 py-2 rounded-xl border border-white/15">
                                <span class="font-mono text-base sm:text-lg font-bold tracking-wider text-white">
                                    {{ $cardNumber }}
                                </span>
                                <button
                                    type="button"
                                    @click="navigator.clipboard.writeText('{{ preg_replace('/\D/', '', $cardNumber) }}'); copiedCard = true; setTimeout(() => copiedCard = false, 2000)"
                                    class="px-3 py-1 rounded-lg text-xs font-semibold transition flex items-center gap-1.5 cursor-pointer active:scale-95"
                                    :class="copiedCard ? 'bg-emerald-500 text-white' : 'bg-white hover:bg-stone-100 text-black shadow-xs'"
                                >
                                    <i class="fa-solid text-[10px]" :class="copiedCard ? 'fa-check' : 'fa-copy'"></i>
                                    <span x-text="copiedCard ? '{{ $rt['copied'] }}' : '{{ $rt['copy'] }}'">{{ $rt['copy'] }}</span>
                                </button>
                            </div>
                        </div>

                        <div class="mt-3 pt-2 border-t border-white/10 flex justify-between items-center text-xs relative z-10">
                            <div>
                                <span class="text-[9px] uppercase text-[#A8A29E] block">{{ $rt['recipient_card_label'] }}</span>
                                <span class="font-semibold text-stone-200">{{ $cardHolder }}</span>
                            </div>
                            <div class="text-right">
                                <span class="text-[9px] uppercase text-[#A8A29E] block">{{ $rt['purpose_label'] }}</span>
                                <span class="font-mono text-amber-300 font-bold">{{ $rt['order_no'] }}{{ $orderNum }}</span>
                            </div>
                        </div>
                    </div>

                    <!-- Инструкция и прямая кнопка отправки чека в Telegram -->
                    <div class="md:col-span-5 flex flex-col justify-between bg-[#FAF7F5] rounded-3xl p-6 border border-[#EAE3DE] space-y-4">
                        <div class="space-y-2.5">
                            <h3 class="font-serif text-base font-bold text-[#1C1917] flex items-center gap-2">
                                <i class="fa-solid fa-circle-info text-amber-600 text-sm"></i>
                                <span>{{ $rt['how_to_pay_title'] }}</span>
                            </h3>
                            <ol class="text-xs text-[#57534E] space-y-2 list-decimal list-inside leading-relaxed">
                                <li>{{ $rt['pay_step_1'] }}</li>
                                <li>{!! $rt['pay_step_2'] !!}</li>
                                <li>{{ $rt['pay_step_3'] }}</li>
                            </ol>
                        </div>

                        <div class="space-y-2 pt-2">
                            <a
                                href="{{ $tgLink }}"
                                target="_blank"
                                rel="noreferrer"
                                class="w-full py-3.5 px-4 bg-[#229ED9] hover:bg-[#1C8CC4] text-white rounded-2xl text-xs font-bold transition flex items-center justify-center gap-2 shadow-md cursor-pointer active:scale-95"
                            >
                                <i class="fa-brands fa-telegram text-base"></i>
                                <span>{{ $rt['send_receipt_btn'] }}</span>
                            </a>

                            <p class="text-[11px] text-stone-400 text-center">
                                {{ $rt['manager_confirm_note'] }}
                            </p>
                        </div>
                    </div>
                </div>
            @else
                <!-- РЕЖИМ 2: ОПЛАТА УЖЕ ПОЛУЧЕНА -->
                <div class="bg-emerald-50/70 border border-emerald-200 rounded-3xl p-6 flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4">
                    <div class="flex items-center gap-3.5">
                        <div class="w-12 h-12 rounded-2xl bg-emerald-500 text-white flex items-center justify-center text-xl flex-shrink-0 shadow-md">
                            <i class="fa-solid fa-circle-check"></i>
                        </div>
                        <div>
                            <h4 class="font-serif font-bold text-base text-emerald-950">
                                {{ $rt['paid_box_title'] }}
                            </h4>
                            <p class="text-xs text-emerald-700 mt-0.5">
                                {{ $rt['paid_box_desc'] }}
                            </p>
                        </div>
                    </div>

                    <a
                        href="{{ $tgLink }}"
                        target="_blank"
                        rel="noreferrer"
                        class="px-4 py-2.5 bg-emerald-600 hover:bg-emerald-700 text-white rounded-xl text-xs font-semibold transition flex items-center gap-2 whitespace-nowrap shadow-xs"
                    >
                        <i class="fa-brands fa-telegram"></i>
                        <span>{{ $rt['write_manager_btn'] }}</span>
                    </a>
                </div>
            @endif
        </div>

        <!-- СОСТАВ ЗАКАЗА И ДЕТАЛИ ДОСТАВКИ -->
        <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
            <!-- Состав заказа -->
            <div class="md:col-span-2 bg-white rounded-3xl p-6 border border-[#EAE3DE] shadow-sm space-y-4">
                <div class="flex items-center justify-between pb-3 border-b border-[#F2ECE8]">
                    <h3 class="font-serif text-base font-bold text-[#1C1917] flex items-center gap-2">
                        <i class="fa-solid fa-bag-shopping text-sm text-[#8C827A]"></i>
                        <span>{{ $rt['items_title'] }} ({{ $order->orderProducts->count() }})</span>
                    </h3>
                    <span class="text-xs text-stone-400">
                        {{ $order->orderProducts->sum('quantity') }} {{ $rt['pcs_unit'] }}
                    </span>
                </div>

                <div class="divide-y divide-[#F2ECE8]">
                    @foreach($order->orderProducts as $item)
                        @php
                            $prod = $item->product;
                            $img = $prod?->image
                                ? (str_starts_with($prod->image, 'http') ? $prod->image : asset($prod->image))
                                : \App\Models\Product::placeholderUrl();
                        @endphp
                        <div class="py-3.5 first:pt-0 flex items-center gap-4">
                            <div class="w-16 h-16 rounded-2xl bg-[#FAF7F5] border border-[#EAE3DE] overflow-hidden flex-shrink-0">
                                <img src="{{ $img }}" alt="{{ $prod?->name ?? 'Product' }}" class="w-full h-full object-cover" />
                            </div>
                            <div class="flex-1 min-w-0">
                                <h4 class="text-xs sm:text-sm font-semibold text-[#1C1917] truncate">
                                    {{ $prod?->name ?: 'Mally Beauty' }}
                                </h4>
                                <div class="text-[11px] text-[#8C827A] flex items-center gap-2 mt-0.5">
                                    @if($prod?->sku)
                                        <span>{{ $rt['sku_prefix'] }} <code class="font-mono">{{ $prod->sku }}</code></span>
                                        <span>·</span>
                                    @endif
                                    <span>{{ $item->quantity }} {{ $rt['pcs_unit'] }}</span>
                                </div>
                            </div>
                            <div class="text-right font-mono font-bold text-xs sm:text-sm text-[#1C1917]">
                                {{ $money((float) $item->price * $item->quantity) }}
                            </div>
                        </div>
                    @endforeach
                </div>

                <!-- Расчет скидки и промокода -->
                <div class="pt-3 border-t border-[#F2ECE8] space-y-1.5 text-xs">
                    @if($order->promo_code)
                        <div class="flex justify-between items-center text-pink-600 font-medium">
                            <span class="flex items-center gap-1.5">
                                <i class="fa-solid fa-ticket text-[11px]"></i>
                                <span>{{ $rt['promo_label'] }} ({{ $order->promo_code }}):</span>
                            </span>
                            <span>-{{ $money((float) $order->discount_amount) }}</span>
                        </div>
                    @endif

                    <div class="flex justify-between items-center pt-2 border-t border-[#F2ECE8] font-serif">
                        <span class="text-sm font-bold text-[#78716C]">{{ $rt['total_label'] }}</span>
                        <span class="text-xl font-bold text-[#1C1917] font-mono">
                            {{ $totalDisplay }}
                        </span>
                    </div>
                </div>
            </div>

            <!-- Доставка и Получатель -->
            <div class="space-y-6">
                <div class="bg-white rounded-3xl p-6 border border-[#EAE3DE] shadow-sm space-y-3">
                    <h3 class="font-serif text-base font-bold text-[#1C1917] flex items-center gap-2 pb-2 border-b border-[#F2ECE8]">
                        <i class="fa-solid fa-location-dot text-sm text-[#8C827A]"></i>
                        <span>{{ $rt['delivery_details_title'] }}</span>
                    </h3>
                    <div class="text-xs text-[#57534E] space-y-2 leading-relaxed">
                        <div>
                            <span class="text-[10px] uppercase font-bold text-[#A8A29E] block">{{ $rt['customer_label'] }}</span>
                            <span class="font-semibold text-[#1C1917]">{{ $order->customer_name ?: ($order->customer ? $order->customer->first_name.' '.$order->customer->last_name : $rt['customer_default']) }}</span>
                        </div>
                        <div>
                            <span class="text-[10px] uppercase font-bold text-[#A8A29E] block">{{ $rt['phone_label'] }}</span>
                            <span class="font-mono font-semibold text-[#1C1917]">{{ $order->customer_phone ?: $order->customer?->phone_number }}</span>
                        </div>
                        <div>
                            <span class="text-[10px] uppercase font-bold text-[#A8A29E] block">{{ $rt['address_label'] }}</span>
                            <span>{{ $rt['city_prefix'] }}{{ $order->city ?: $rt['tashkent'] }}, {{ $order->address ?: $rt['clarifying'] }}</span>
                        </div>
                        <div>
                            <span class="text-[10px] uppercase font-bold text-[#A8A29E] block">{{ $rt['comment_label'] }}</span>
                            @if($order->comment)
                                <div class="p-2.5 rounded-xl bg-[#FAF7F5] border border-[#EAE3DE] italic mt-0.5">
                                    «{{ $order->comment }}»
                                </div>
                            @else
                                <span class="text-stone-400 font-mono">—</span>
                            @endif
                        </div>
                    </div>
                </div>

                <!-- Быстрые действия -->
                <div class="space-y-2 print:hidden">
                    <a
                        href="{{ route('profile') }}"
                        wire:navigate
                        class="w-full py-3 bg-white hover:bg-[#FAF8F6] text-[#1C1917] border border-[#EAE3DE] text-xs font-semibold rounded-2xl transition flex items-center justify-center gap-2 shadow-2xs"
                    >
                        <i class="fa-regular fa-user text-xs"></i>
                        <span>{{ $rt['my_orders_btn'] }}</span>
                    </a>
                    <a
                        href="{{ route('catalog') }}"
                        wire:navigate
                        class="w-full py-3 bg-white hover:bg-[#FAF8F6] text-[#78716C] hover:text-[#1C1917] border border-[#EAE3DE] text-xs font-semibold rounded-2xl transition flex items-center justify-center gap-2 shadow-2xs"
                    >
                        <i class="fa-solid fa-arrow-left text-xs"></i>
                        <span>{{ $rt['back_catalog_btn'] }}</span>
                    </a>
                </div>
            </div>
        </div>
    </main>

    <x-storefront.auth-modal />
</div>
