<?php

namespace App\Livewire\Storefront;

use App\Livewire\Storefront\Concerns\InteractsWithStore;
use App\Models\Review;
use Illuminate\Support\Facades\Schema;
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

        $reviews = match ($locale) {
            'uz' => [
                [
                    'id' => 1,
                    'author' => 'Dilnoza Karimova',
                    'city' => 'Toshkent (Mirobod tumani)',
                    'date' => '28-sentabr 2026',
                    'title' => 'Nihoyat Toshkentda original Rhode!',
                    'text' => "Singlimga sovg'a uchun Rhode Peptide Lip Tint va Rituals of Sakura to'plamini buyurtma qildim. Kuryer shu kunning o'zida eshik oldigacha yetkazib berdi. Batch-kodlarni CheckFresh orqali tekshirdim — 100% AQSH originali!",
                ],
                [
                    'id' => 2,
                    'author' => 'Madina Umarova',
                    'city' => 'Samarqand',
                    'date' => '24-sentabr 2026',
                    'title' => 'Tezkor yetkazib berish va chiroyli qadoqlash',
                    'text' => "Samarqandga yetkazib berishdan xavotir olgandim, lekin buyurtma BTS orqali 2 kunda yetib keldi. Hammasi ipak lentali firma qutisiga juda chiroyli qadoqlangan. Sovg'a sifatida qo'shilgan probniklar uchun alohida rahmat!",
                ],
                [
                    'id' => 3,
                    'author' => 'Zarina Aliyeva',
                    'city' => 'Toshkent (Yunusobod)',
                    'date' => '19-sentabr 2026',
                    'title' => "Hourglass ikki tomonlama cho'tkasi — ajoyib",
                    'text' => "Hourglass Ambient Lighting Edit Brush cho'tkasini uzoq qidirdim. Telegram orqali administratorga yozdim, to'lovdan so'ng 2 soat ichida yetkazib berishdi. Tuklari juda yumshoq va mukammal surtadi.",
                ],
                [
                    'id' => 4,
                    'author' => 'Sevara Xamidova',
                    'city' => 'Buxoro',
                    'date' => '12-sentabr 2026',
                    'title' => 'Sephora AQSHdan individual bayer-buyurtma',
                    'text' => "Toshkent omborida yo'q bo'lgan limitlangan paletkani buyurtma qildim. Nyu-Yorkdagi Sephora do'konidan xarid qilib, hech qanday bojxona muammolarisiz 15 kunda yetkazib berishdi. Mally xizmatidan juda mamnunman!",
                ],
            ],
            'en' => [
                [
                    'id' => 1,
                    'author' => 'Dilnoza Karimova',
                    'city' => 'Tashkent (Mirabad)',
                    'date' => 'September 28, 2026',
                    'title' => 'Finally authentic Rhode in Tashkent!',
                    'text' => 'Ordered the Rhode Peptide Lip Tint and Rituals of Sakura gift set for my sister. Same-day courier delivery right to my door. Verified the batch codes on CheckFresh — 100% authentic from the USA!',
                ],
                [
                    'id' => 2,
                    'author' => 'Madina Umarova',
                    'city' => 'Samarkand',
                    'date' => 'September 24, 2026',
                    'title' => 'Fast express shipping & signature gift box',
                    'text' => 'My parcel arrived in Samarkand in just 2 days via BTS Express. Everything was carefully wrapped in a craft gift box with a satin ribbon and free samples inside!',
                ],
                [
                    'id' => 3,
                    'author' => 'Zarina Aliyeva',
                    'city' => 'Tashkent (Yunusabad)',
                    'date' => 'September 19, 2026',
                    'title' => 'Hourglass double-ended brush is perfection',
                    'text' => 'Searched everywhere for the authentic Hourglass Ambient Lighting Edit Brush. Ordered from Mally and received it in 2 hours across Tashkent. Super soft bristles and blends like a dream.',
                ],
                [
                    'id' => 4,
                    'author' => 'Sevara Khamidova',
                    'city' => 'Bukhara',
                    'date' => 'September 12, 2026',
                    'title' => 'Custom Personal Shopper order from Sephora USA',
                    'text' => 'Ordered a limited-edition palette that was out of stock locally. The Mally team bought it at Sephora New York and delivered it straight to me without any customs hassle!',
                ],
            ],
            default => [
                [
                    'id' => 1,
                    'author' => 'Дильноза Каримова',
                    'city' => 'Ташкент (Мирабадский р-н)',
                    'date' => '28 сентября 2026',
                    'title' => 'Наконец-то оригинальный Rhode в Ташкенте!',
                    'text' => 'Заказывала тинт Rhode Peptide Lip Tint и спа-набор Rituals of Sakura на подарок сестре. Курьер доставил в день заказа прямо до дверей. Батч-коды проверила через CheckFresh — всё 100% оригинал из США! Теперь не нужно просить знакомых привозить из Дубая или Америки.',
                ],
                [
                    'id' => 2,
                    'author' => 'Мадина Умарова',
                    'city' => 'Самарканд',
                    'date' => '24 сентября 2026',
                    'title' => 'Быстрая экспресс-доставка и фирменная упаковка',
                    'text' => 'Очень переживала за доставку в Самарканд, но посылка пришла за 2 дня через BTS. Всё упаковано в двойную пупырку и фирменную крафтовую коробку с шелковой лентой. Ничего не помялось! Отдельное спасибо за пробники в подарок.',
                ],
                [
                    'id' => 3,
                    'author' => 'Зарина Алиева',
                    'city' => 'Ташкент (Юнусабад)',
                    'date' => '19 сентября 2026',
                    'title' => 'Двусторонняя кисть Hourglass — восторг',
                    'text' => 'Долго искала кисть Hourglass Ambient Lighting Edit Brush, везде были подделки или не было в наличии. Написала администратору в Telegram, уточнила детали, оплатила и через 2 часа кисть была у меня. Очень мягкий ворс, растушевывает идеально.',
                ],
                [
                    'id' => 4,
                    'author' => 'Севара Хамидова',
                    'city' => 'Бухара',
                    'date' => '12 сентября 2026',
                    'title' => 'Индивидуальный байер-заказ из Sephora США',
                    'text' => 'Заказывала лимитированную палетку, которой не было на складе в Ташкенте. Ребята выкупили в Sephora в Нью-Йорке и привезли без всяких таможенных заморочек с моей стороны. Очень довольна сервисом Mally!',
                ],
            ],
        };

        if (Schema::hasTable('reviews')) {
            $dbReviews = Review::where('is_approved', true)
                ->latest('id')
                ->limit(4)
                ->get()
                ->map(function (Review $rev) {
                    return [
                        'id' => 'db_'.$rev->id,
                        'author' => $rev->author_name,
                        'city' => $rev->city ?: 'Ташкент',
                        'date' => $rev->created_at ? $rev->created_at->format('d.m.Y') : now()->format('d.m.Y'),
                        'title' => $rev->product_title ?: 'Отзыв о заказе Mally',
                        'text' => $rev->comment,
                        'rating' => (int) $rev->rating,
                    ];
                })
                ->all();

            if (! empty($dbReviews)) {
                $reviews = array_slice(array_merge($dbReviews, $reviews), 0, 8);
            }
        }

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
