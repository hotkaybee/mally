<?php

namespace App\Livewire\Storefront;

use App\Livewire\Storefront\Concerns\InteractsWithStore;
use App\Models\Review;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('components.layouts.app')]
class HomePage extends Component
{
    use InteractsWithStore;

    public function render()
    {
        $store = $this->store();
        $locale = $store->getLocale();

        $brandsList = match ($locale) {
            'uz' => [
                [
                    'name' => 'Rhode',
                    'origin' => 'AQSH',
                    'desc' => 'Xeyli Biberning mashhur parvarish liniyasi: peptidli flyuidlar, tiklovchi kremlar va lab uchun virusli tintlar.',
                    'badge' => 'Sotuv xiti',
                ],
                [
                    'name' => 'Hourglass',
                    'origin' => 'AQSH',
                    'desc' => "Lyuks dekorativ kosmetika, fotoshop effektli Ambient Lighting upalari va ikki tomonlama ipakdek cho'tkalar.",
                    'badge' => 'Premium',
                ],
                [
                    'name' => 'Rituals',
                    'origin' => 'Niderlandiya / YI',
                    'desc' => "Tana va uy uchun aromatik spa-to'plamlar: The Ritual of Sakura, Ayurveda va Karma sovg'a qutilarida.",
                    'badge' => "Sovg'a uchun ideal",
                ],
                [
                    'name' => 'Rare Beauty',
                    'origin' => 'AQSH',
                    'desc' => 'Selena Gomesdan innovatsion Soft Pinch suyuq rumyanalari, vaznsiz lab yog‘lari va xaylayterlar.',
                    'badge' => 'Bestseller',
                ],
                [
                    'name' => 'Sol de Janeiro',
                    'origin' => 'AQSH',
                    'desc' => 'Mashhur Brazilian Bum Bum kremi va abadiy yoz iforiga ega Cheirosa 68 va 59 parfyumlangan mistlari.',
                    'badge' => 'TikTok trendi',
                ],
                [
                    'name' => 'Dior',
                    'origin' => 'Fransiya',
                    'desc' => 'Mukammal makiyaj uchun afsonaviy Dior Lip Glow Oil lab yog‘lari va Backstage Rosy Glow rumyanalari.',
                    'badge' => 'Kult',
                ],
            ],
            'en' => [
                [
                    'name' => 'Rhode',
                    'origin' => 'USA',
                    'desc' => 'Iconic skincare by Hailey Bieber: peptide glazing fluids, barrier restore creams, and viral peptide lip tints.',
                    'badge' => 'Best Seller',
                ],
                [
                    'name' => 'Hourglass',
                    'origin' => 'USA',
                    'desc' => 'Luxury makeup, airbrush-finish Ambient Lighting powders, and ultra-soft double-ended brushes.',
                    'badge' => 'Luxury',
                ],
                [
                    'name' => 'Rituals',
                    'origin' => 'Netherlands / EU',
                    'desc' => 'Aromatic body & home spa gift sets: The Ritual of Sakura, Ayurveda, and Karma in signature gift boxes.',
                    'badge' => 'Perfect Gift',
                ],
                [
                    'name' => 'Rare Beauty',
                    'origin' => 'USA',
                    'desc' => 'Weightless Soft Pinch liquid blushes, tinted lip oils, and silky highlighters by Selena Gomez.',
                    'badge' => 'Bestseller',
                ],
                [
                    'name' => 'Sol de Janeiro',
                    'origin' => 'USA',
                    'desc' => 'World-famous Brazilian Bum Bum Cream and Cheirosa 68 & 59 perfume mists with an endless summer scent.',
                    'badge' => 'TikTok Viral',
                ],
                [
                    'name' => 'Dior',
                    'origin' => 'France',
                    'desc' => 'Legendary Dior Addict Lip Glow Oil and Backstage Rosy Glow blush for a radiant everyday look.',
                    'badge' => 'Iconic',
                ],
            ],
            default => [
                [
                    'name' => 'Rhode',
                    'origin' => 'США',
                    'desc' => 'Культовый уход от Хейли Бибер: пептидные флюиды, восстанавливающие кремы и вирусные тинты для губ.',
                    'badge' => 'Хит продаж',
                ],
                [
                    'name' => 'Hourglass',
                    'origin' => 'США',
                    'desc' => 'Люксовая декоративная косметика, пудры Ambient Lighting с эффектом фотошопа и шелковистые двусторонние кисти.',
                    'badge' => 'Премиум',
                ],
                [
                    'name' => 'Rituals',
                    'origin' => 'Нидерланды / ЕС',
                    'desc' => 'Ароматические спа-наборы для тела и дома: The Ritual of Sakura, Ayurveda и Karma в подарочных боксах.',
                    'badge' => 'Идеально на подарок',
                ],
                [
                    'name' => 'Rare Beauty',
                    'origin' => 'США',
                    'desc' => 'Инновационные жидкие румяна Soft Pinch, невесомые масла для губ и хайлайтеры от Селены Гомес.',
                    'badge' => 'Бестселлер',
                ],
                [
                    'name' => 'Sol de Janeiro',
                    'origin' => 'США',
                    'desc' => 'Знаменитый крем Brazilian Bum Bum и парфюмированные мисты Cheirosa 68 и 59 с ароматом вечного лета.',
                    'badge' => 'Тренды TikTok',
                ],
                [
                    'name' => 'Dior',
                    'origin' => 'Франция',
                    'desc' => 'Легендарные масла для губ Dior Lip Glow Oil и румяна Backstage Rosy Glow для безупречного макияжа.',
                    'badge' => 'Культ',
                ],
            ],
        };

        // Only real reviews approved in the admin panel.
        $reviews = Review::with(['product', 'order.customer'])
            ->where('is_approved', true)
            ->latest('id')
            ->limit(4)
            ->get()
            ->map(fn (Review $rev) => [
                'id' => 'db_'.$rev->id,
                'author' => $rev->author_name,
                'city' => $rev->author_city,
                'date' => $rev->created_at?->timezone('Asia/Tashkent')->format('d.m.Y'),
                'title' => $rev->product?->name ?: 'Mally',
                'text' => $rev->comment,
                'rating' => max(1, min(5, (int) $rev->rating)),
            ])
            ->all();

        $faqs = match ($locale) {
            'uz' => [
                [
                    'q' => 'Kosmetika 100% original ekanligiga qanday ishonch hosil qilishim mumkin?',
                    'a' => "Biz Dubay replikalari yoki Xitoy nusxalari bilan umuman ishlamaymiz. Barcha mahsulotlar to'g'ridan-to'g'ri brendlarning rasmiy do'konlaridan (Rhode, Hourglass) va AQSHning Sephora tarmoqlaridan xarid qilinadi. Har bir qutida original shtrix-kod va batch-kod mavjud bo'lib, ularni CheckFresh orqali tekshirishingiz mumkin.",
                ],
                [
                    'q' => "Qanday to'lov usullari mavjud?",
                    'a' => "To'lov do'kon rekvizitlari bo'yicha bank kartasiga (Humo / Uzcard) to'g'ridan-to'g'ri o'tkazma orqali amalga oshiriladi. Shuningdek, saytda narxlarni USD ($) va UZS (so'm) da ko'rish imkoniyati mavjud.",
                ],
                [
                    'q' => 'Yetkazib berish narxi qancha va qancha vaqt oladi?',
                    'a' => "Toshkent shahri bo'ylab buyurtmalar Yandex Go orqali shu kunning o'zida jo'natiladi (yetkazib berish xaridor tomonidan tarif bo'yicha to'lanadi). O'zbekistonning barcha viloyatlariga BTS ekspress-pochtasi orqali 1–3 kun ichida yetkaziladi.",
                ],
                [
                    'q' => "Agar menga kerakli mahsulot yoki rang sotuvda bo'lmasa nima qilish kerak?",
                    'a' => 'Bizning «AQSHdan buyurtma» bayer-xizmatimizdan foydalaning! Telegram orqali @mallyuz menejerimizga yozing — biz kerakli mahsulotni AQSHdan xarid qilib, 15–20 kun ichida Toshkentga yetkazib beramiz.',
                ],
                [
                    'q' => "Issiq havoda tashish paytida kosmetika sifatini yo'qotmaydimi?",
                    'a' => "Barcha kremlar, zardoblar va lab bo'yoqlari harorat rejimiga rioya qilingan holda maxsus termoqadoqlarda avia-pochta orqali keltiriladi va Toshkentdagi salqin omborimizda saqlanadi.",
                ],
            ],
            'en' => [
                [
                    'q' => 'How can I be sure the cosmetics are 100% authentic?',
                    'a' => 'We never deal with replicas or unauthorized resellers. Every product is purchased directly from official brand stores (Rhode, Hourglass) and Sephora USA. Original factory barcodes and batch codes on every package can be verified on CheckFresh upon delivery.',
                ],
                [
                    'q' => 'What payment methods do you accept?',
                    'a' => 'Payments are accepted via direct card transfer (Humo / Uzcard) using the store card details shown at checkout. You can also browse prices in both USD ($) and UZS on our website.',
                ],
                [
                    'q' => 'How fast is delivery and how much does it cost?',
                    'a' => 'Within Tashkent, orders are dispatched the same day via Yandex Go (delivery fee is paid by the recipient according to the courier tariff). Across all regions of Uzbekistan, we ship via BTS Express within 1–3 business days.',
                ],
                [
                    'q' => 'What if the product or shade I want is out of stock?',
                    'a' => 'Use our Personal Shopper service from the USA! Message our administrator @mallyuz on Telegram — we will purchase the item directly from Sephora USA and deliver it to Uzbekistan within 15–20 days at a fixed price.',
                ],
                [
                    'q' => 'Are products protected from heat during shipping?',
                    'a' => 'Yes! All skincare and makeup items are shipped exclusively by air in insulated thermal packaging and stored in a climate-controlled facility in Tashkent.',
                ],
            ],
            default => [
                [
                    'q' => 'Как я могу быть уверена, что косметика на 100% оригинальная?',
                    'a' => 'Мы категорически не работаем с дубайскими репликами, китайскими копиями или сомнительными перекупщиками. Все позиции выкупаются напрямую в официальных магазинах брендов (Rhode, Hourglass) и в авторизованных сетях Sephora (США). На каждой коробке сохранены оригинальные заводские штрих-коды и батч-коды производителя, которые вы можете проверить на любом независимом сервисе (CheckFresh, Cosmetic Calculator) прямо в момент получения.',
                ],
                [
                    'q' => 'Какие способы оплаты поддерживаются?',
                    'a' => 'Оплата производится прямым переводом на банковскую карту (Humo / Uzcard) по реквизитам магазина. Комиссию банка за перевод покрывает отправитель. Также для удобства на сайте доступно отображение цен в USD ($).',
                ],
                [
                    'q' => 'Сколько стоит и как быстро осуществляется доставка?',
                    'a' => 'По Ташкенту отправка осуществляется оперативно через сервис Яндекс Go (доставка оплачивается покупателем по фактическому тарифу такси при получении заказа). В Самарканд, Бухару, Фергану, Андижан, Наманган и все регионы Узбекистана отправляем экспресс-почтой BTS (срок 1–3 дня по тарифу службы).',
                ],
                [
                    'q' => 'Что делать, если нужного мне товара или оттенка нет в наличии?',
                    'a' => 'Воспользуйтесь нашим байер-сервисом «Под заказ из США»! Нажмите кнопку на карточке товара или напишите в Telegram нашему менеджеру @mallyuz. Мы выкупим нужную позицию в США и доставим авиапочтой в Ташкент за 15–20 дней по фиксированной цене.',
                ],
                [
                    'q' => 'Косметика не испортится при транспортировке в жару?',
                    'a' => 'Все кремы, сыворотки, помады и флюиды перевозятся исключительно авиасообщением в герметичных термоупаковках с соблюдением температурного режима, а на складе в Ташкенте хранятся в прохладном климатическом помещении без воздействия солнечных лучей.',
                ],
            ],
        };

        return view('livewire.storefront.home-page', array_merge($this->sharedViewData(), [
            'brandsList' => $brandsList,
            'reviews' => $reviews,
            'faqs' => $faqs,
        ]));
    }
}
