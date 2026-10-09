import React, { useState, useEffect } from 'react';
import Link from '../components/Link';
import Header from '../components/Header';
import Footer from '../components/Footer';
import ProductCard from '../components/ProductCard';
import { INITIAL_PRODUCTS } from '../data/initialProducts';

export default function HomePage() {
  const [openFaq, setOpenFaq] = useState<number | null>(null);
  const [featuredProducts, setFeaturedProducts] = useState<any[]>(INITIAL_PRODUCTS.slice(0, 8));

  const toggleFaq = (index: number) => {
    setOpenFaq(openFaq === index ? null : index);
  };

  useEffect(() => {
    fetch('/api/products')
      .then((res) => res.json())
      .then((data) => {
        if (data.products && Array.isArray(data.products) && data.products.length > 0) {
          setFeaturedProducts(data.products.slice(0, 8));
        }
      })
      .catch(() => {});
  }, []);

  const brandsList = [
    {
      name: 'Rhode',
      origin: 'США',
      desc: 'Культовый уход от Хейли Бибер: пептидные флюиды, восстанавливающие кремы и вирусные тинты для губ.',
      badge: 'Хит продаж',
    },
    {
      name: 'Hourglass',
      origin: 'США',
      desc: 'Люксовая декоративная косметика, пудры Ambient Lighting с эффектом фотошопа и шелковистые двусторонние кисти.',
      badge: 'Премиум',
    },
    {
      name: 'Rituals',
      origin: 'Нидерланды / ЕС',
      desc: 'Ароматические спа-наборы для тела и дома: The Ritual of Sakura, Ayurveda и Karma в подарочных боксах.',
      badge: 'Идеально на подарок',
    },
    {
      name: 'Rare Beauty',
      origin: 'США',
      desc: 'Инновационные жидкие румяна Soft Pinch, невесомые масла для губ и хайлайтеры от Селены Гомес.',
      badge: 'Бестселлер',
    },
    {
      name: 'Sol de Janeiro',
      origin: 'США',
      desc: 'Знаменитый крем Brazilian Bum Bum и парфюмированные мисты Cheirosa 68 и 59 с ароматом вечного лета.',
      badge: 'Тренды TikTok',
    },
    {
      name: 'Dior Beauty',
      origin: 'Франция',
      desc: 'Легендарные масла для губ Dior Lip Glow Oil и румяна Backstage Rosy Glow для безупречного макияжа.',
      badge: 'Культ',
    },
  ];

  const reviews = [
    {
      id: 1,
      author: 'Дильноза Каримова',
      city: 'Ташкент (Мирабадский р-н)',
      rating: 5,
      date: '28 сентября 2026',
      title: 'Наконец-то оригинальный Rhode в Ташкенте!',
      text: 'Заказывала тинт Rhode Peptide Lip Tint и спа-набор Rituals of Sakura на подарок сестре. Курьер доставил в день заказа прямо до дверей. Батч-коды проверила через CheckFresh — всё 100% оригинал из США! Теперь не нужно просить знакомых привозить из Дубая или Америки.',
    },
    {
      id: 2,
      author: 'Мадина Умарова',
      city: 'Самарканд',
      rating: 5,
      date: '24 сентября 2026',
      title: 'Быстрая экспресс-доставка и фирменная упаковка',
      text: 'Очень переживала за доставку в Самарканд, но посылка пришла за 2 дня через BTS. Всё упаковано в двойную пупырку и фирменную крафтовую коробку с шелковой лентой. Ничего не помялось! Отдельное спасибо за пробники в подарок.',
    },
    {
      id: 3,
      author: 'Зарина Алиева',
      city: 'Ташкент (Юнусабад)',
      rating: 5,
      date: '19 сентября 2026',
      title: 'Двусторонняя кисть Hourglass — восторг',
      text: 'Долго искала кисть Hourglass Ambient Lighting Edit Brush, везде были подделки или не было в наличии. Написала администратору в Telegram, уточнила детали, оплатила через Click и через 2 часа кисть была у меня. Очень мягкий ворс, растушевывает идеально.',
    },
    {
      id: 4,
      author: 'Севара Хамидова',
      city: 'Бухара',
      rating: 5,
      date: '12 сентября 2026',
      title: 'Индивидуальный байер-заказ из Sephora США',
      text: 'Заказывала лимитированную палетку, которой не было на складе в Ташкенте. Ребята выкупили в Sephora в Нью-Йорке и привезли за 10 дней без всяких таможенных заморочек с моей стороны. Очень довольна сервисом Mally!',
    },
  ];

  const faqs = [
    {
      q: 'Как я могу быть уверена, что косметика на 100% оригинальная?',
      a: 'Мы категорически не работаем с дубайскими репликами, китайскими копиями или сомнительными перекупщиками. Все позиции выкупаются напрямую в официальных магазинах брендов (Rhode, Hourglass) и в авторизованных сетях Sephora (США). На каждой коробке сохранены оригинальные заводские штрих-коды и батч-коды производителя, которые вы можете проверить на любом независимом сервисе (CheckFresh, Cosmetic Calculator) прямо в момент получения.',
    },
    {
      q: 'Какие способы оплаты поддерживаются?',
      a: 'Оплата производится прямым переводом на банковскую карту (Humo / Uzcard) по реквизитам магазина. Комиссию банка за перевод покрывает отправитель. Также для удобства на сайте доступно отображение цен в USD ($).',
    },
    {
      q: 'Сколько стоит и как быстро осуществляется доставка?',
      a: 'По Ташкенту отправка осуществляется оперативно через сервис Яндекс Go (доставка оплачивается покупателем по фактическому тарифу такси при получении заказа). В Самарканд, Бухару, Фергану, Андижан, Наманган и все регионы Узбекистана отправляем экспресс-почтой BTS (срок 1–3 дня по тарифу службы).',
    },
    {
      q: 'Что делать, если нужного мне товара или оттенка нет в наличии?',
      a: 'Воспользуйтесь нашим байер-сервисом «Под заказ из США»! Нажмите кнопку на карточке товара или напишите в Telegram нашему менеджеру @mallyuz. Мы выкупим нужную позицию в США и доставим авиапочтой в Ташкент за 15–20 рабочих дней по фиксированной цене.',
    },
    {
      q: 'Косметика не испортится при транспортировке в жару?',
      a: 'Все кремы, сыворотки, помады и флюиды перевозятся исключительно авиасообщением в герметичных термоупаковках с соблюдением температурного режима, а на складе в Ташкенте хранятся в прохладном климатическом помещении без воздействия солнечных лучей.',
    },
  ];

  return (
    <div className="min-h-screen bg-[#FCECEC] text-[#2C2A29] flex flex-col font-sans">
      <Header />

      {/* 1. Главный Hero-блок */}
      <section className="relative bg-gradient-to-b from-[#FFF5F2] via-[#FCECEC] to-[#FCECEC] border-b border-[#F2D8D0] py-20 sm:py-28 px-4 overflow-hidden">
        <div className="absolute top-10 left-1/2 -translate-x-1/2 w-[700px] h-[700px] bg-rose-200/40 rounded-full blur-3xl -z-0 pointer-events-none" />

        <div className="max-w-5xl mx-auto text-center relative z-10 space-y-6">
          <div className="inline-flex items-center gap-2 px-4 py-1.5 bg-white border border-[#F2D8D0] rounded-full shadow-sm">
            <span className="w-2 h-2 rounded-full bg-emerald-500 animate-pulse" />
            <span className="text-[11px] uppercase tracking-widest text-[#8C827A] font-semibold">
              Прямой импорт оригинальной косметики из США в Узбекистан
            </span>
          </div>

          <h1 className="font-serif text-4xl sm:text-6xl font-bold text-[#1C1917] leading-[1.12] tracking-tight">
            Красота без границ с <span className="italic font-normal">mally</span>
          </h1>

          <p className="text-sm sm:text-base text-[#57534E] max-w-2xl mx-auto leading-relaxed">
            Mally — ваш надежный проводник к мировым брендам красоты. Мы привозим оригинальную косметику Rhode, Hourglass, Rare Beauty, Rituals и Sol de Janeiro напрямую из США. Забудьте о сложностях с иностранными картами, посредниками и таможней — культовые бестселлеры уже в Ташкенте.
          </p>

          <div className="pt-4 flex flex-col sm:flex-row items-center justify-center gap-4">
            <Link
              href="/catalog"
              className="px-9 py-4 bg-[#1C1917] hover:bg-black text-white text-xs uppercase tracking-widest font-semibold rounded-full shadow-xl transition active:scale-95 flex items-center gap-2"
            >
              <span>Перейти в каталог</span>
              <span>→</span>
            </Link>

            <a
              href="https://t.me/mallyuz?text=Здравствуйте!%20Хочу%20узнать%20про%20индивидуальный%20заказ%20косметики%20из%20США"
              target="_blank"
              rel="noreferrer"
              className="px-7 py-4 bg-white border border-[#D5CCC7] hover:bg-[#FAF8F6] text-[#1C1917] text-xs font-semibold rounded-full transition shadow-sm flex items-center gap-2"
            >
              <span>✈️ Заказ из США под ключ (15-20 дн.)</span>
            </a>
          </div>

          {/* Статистика доверия */}
          <div className="pt-10 grid grid-cols-2 sm:grid-cols-4 gap-4 max-w-3xl mx-auto border-t border-[#EAE3DE] mt-12 text-left">
            <div>
              <div className="font-serif text-2xl font-bold text-[#1C1917]">100%</div>
              <div className="text-[11px] text-[#8C827A]">Оригинал из США и Европы</div>
            </div>
            <div>
              <div className="font-serif text-2xl font-bold text-[#1C1917]">&gt; 1 500</div>
              <div className="text-[11px] text-[#8C827A]">Доставленных заказов</div>
            </div>
            <div>
              <div className="font-serif text-2xl font-bold text-[#1C1917]">В день заказа</div>
              <div className="text-[11px] text-[#8C827A]">Доставка по Ташкенту</div>
            </div>
            <div>
              <div className="font-serif text-2xl font-bold text-[#1C1917]">1–3 дня</div>
              <div className="text-[11px] text-[#8C827A]">Экспресс-доставка по РУз</div>
            </div>
          </div>
        </div>
      </section>

      {/* 2. Сравнение: Почему Mally */}
      <section className="py-16 px-4 bg-white border-b border-[#EAE3DE]">
        <div className="max-w-5xl mx-auto">
          <div className="text-center max-w-2xl mx-auto mb-12">
            <span className="text-xs uppercase tracking-widest text-[#8C827A] font-bold">Стандарты качества</span>
            <h2 className="font-serif text-2xl sm:text-3xl font-bold text-[#1C1917] mt-1">
              Почему девушки Узбекистана выбирают Mally
            </h2>
            <p className="text-xs text-[#78716C] mt-2">
              Мы гарантируем подлинность каждого флакона и обеспечиваем первоклассный сервис.
            </p>
          </div>

          <div className="grid grid-cols-1 md:grid-cols-3 gap-6">
            <div className="p-6 bg-[#FAF7F5] rounded-3xl border border-[#EAE3DE] space-y-3">
              <div className="w-12 h-12 rounded-2xl bg-white flex items-center justify-center text-xl text-amber-600 shadow-sm">
                <i className="fa-solid fa-gem" />
              </div>
              <h3 className="font-serif text-base font-bold text-[#1C1917]">Официальный выкуп в США</h3>
              <p className="text-xs text-[#57534E] leading-relaxed">
                Выкупаем только на официальных американских сайтах брендов и в Sephora USA. Предоставляем инвойсы и помогаем проверить батч-коды.
              </p>
            </div>

            <div className="p-6 bg-[#FAF7F5] rounded-3xl border border-[#EAE3DE] space-y-3">
              <div className="w-12 h-12 rounded-2xl bg-white flex items-center justify-center text-xl text-amber-500 shadow-sm">
                <i className="fa-solid fa-bolt" />
              </div>
              <h3 className="font-serif text-base font-bold text-[#1C1917]">В наличии на складе</h3>
              <p className="text-xs text-[#57534E] leading-relaxed">
                Вам не нужно ждать посылку неделями: топовые позиции Rhode, Hourglass и Rituals уже находятся в Ташкенте и готовы к отправке за пару часов.
              </p>
            </div>

            <div className="p-6 bg-[#FAF7F5] rounded-3xl border border-[#EAE3DE] space-y-3">
              <div className="w-12 h-12 rounded-2xl bg-white flex items-center justify-center text-xl text-rose-500 shadow-sm">
                <i className="fa-solid fa-gift" />
              </div>
              <h3 className="font-serif text-base font-bold text-[#1C1917]">Подарочная эстетика</h3>
              <p className="text-xs text-[#57534E] leading-relaxed">
                Каждый заказ бережно упаковывается в крафтовую коробку с атласной лентой и пробниками в подарок — идеально для подарка себе или близким.
              </p>
            </div>
          </div>
        </div>
      </section>

      {/* 3. Как работает Индивидуальный заказ из США */}
      <section className="py-16 px-4 bg-[#FAF7F5] border-b border-[#EAE3DE]">
        <div className="max-w-5xl mx-auto">
          <div className="text-center max-w-2xl mx-auto mb-12">
            <span className="text-xs uppercase tracking-widest text-[#8C827A] font-bold">Байер-сервис Mally</span>
            <h2 className="font-serif text-2xl sm:text-3xl font-bold text-[#1C1917] mt-1">
              Не нашли нужный товар? Привезем из США за 7–14 дней
            </h2>
            <p className="text-xs text-[#78716C] mt-2">
              Если нужного оттенка помады или лимитированного набора нет на складе в Ташкенте, мы закажем его специально для вас.
            </p>
          </div>

          <div className="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 text-xs">
            <div className="p-5 bg-white rounded-2xl border border-[#EAE3DE] space-y-2">
              <span className="w-7 h-7 rounded-full bg-[#1C1917] text-white flex items-center justify-center font-bold text-xs">1</span>
              <h4 className="font-bold text-sm text-[#1C1917]">Отправьте запрос</h4>
              <p className="text-[#57534E]">
                Пришлите фото или ссылку на товар администратору в Telegram @mallyuz.
              </p>
            </div>

            <div className="p-5 bg-white rounded-2xl border border-[#EAE3DE] space-y-2">
              <span className="w-7 h-7 rounded-full bg-[#1C1917] text-white flex items-center justify-center font-bold text-xs">2</span>
              <h4 className="font-bold text-sm text-[#1C1917]">Расчет под ключ</h4>
              <p className="text-[#57534E]">
                Фиксируем итоговую стоимость в сумах или долларах без скрытых доплат за вес.
              </p>
            </div>

            <div className="p-5 bg-white rounded-2xl border border-[#EAE3DE] space-y-2">
              <span className="w-7 h-7 rounded-full bg-[#1C1917] text-white flex items-center justify-center font-bold text-xs">3</span>
              <h4 className="font-bold text-sm text-[#1C1917]">Выкуп и страховка</h4>
              <p className="text-[#57534E]">
                Выкупаем в Sephora USA и отправляем ускоренной авиапочтой с климат-контролем.
              </p>
            </div>

            <div className="p-5 bg-white rounded-2xl border border-[#EAE3DE] space-y-2">
              <span className="w-7 h-7 rounded-full bg-[#1C1917] text-white flex items-center justify-center font-bold text-xs">4</span>
              <h4 className="font-bold text-sm text-[#1C1917]">Получение в руки</h4>
              <p className="text-[#57534E]">
                Через 7–14 рабочих дней курьер доставляет посылку прямо вам в руки в Узбекистане.
              </p>
            </div>
          </div>

          <div className="mt-8 text-center">
            <a
              href="https://t.me/mallyuz?text=Здравствуйте!%20Хочу%20оформить%20индивидуальный%20заказ%20из%20США"
              target="_blank"
              rel="noreferrer"
              className="inline-flex items-center gap-2 px-8 py-3.5 bg-[#0088cc] hover:bg-[#0077b3] text-white font-semibold text-xs rounded-full shadow-md transition"
            >
              <i className="fa-brands fa-telegram text-base" />
              <span>Оставить заявку на байер-заказ в Telegram</span>
            </a>
          </div>
        </div>
      </section>

      {/* 4. Популярные товары в наличии с адаптивным масштабированием */}
      <section className="py-16 px-4 sm:px-6 lg:px-8 bg-white border-b border-[#EAE3DE]">
        <div className="max-w-[1720px] mx-auto">
          <div className="flex flex-col sm:flex-row justify-between items-start sm:items-end mb-8 gap-4">
            <div>
              <span className="text-xs uppercase tracking-widest text-[#8C827A] font-bold">Хиты продаж</span>
              <h2 className="font-serif text-2xl sm:text-3xl font-bold text-[#1C1917] mt-1">
                Бестселлеры в наличии в Ташкенте
              </h2>
            </div>
            <Link
              href="/catalog"
              className="text-xs font-semibold text-[#1C1917] hover:underline flex items-center gap-1"
            >
              Смотреть весь каталог →
            </Link>
          </div>

          <div className="grid grid-cols-2 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-4 xl:grid-cols-5 2xl:grid-cols-6 gap-3 sm:gap-4 lg:gap-5">
            {featuredProducts.map((p) => (
              <ProductCard
                key={p.sku || p.id}
                product={p}
              />
            ))}
          </div>
        </div>
      </section>

      {/* 5. Культовые мировые бренды */}
      <section className="py-16 px-4 bg-[#FAF7F5] border-b border-[#EAE3DE]">
        <div className="max-w-6xl mx-auto">
          <div className="text-center max-w-2xl mx-auto mb-12">
            <span className="text-xs uppercase tracking-widest text-[#8C827A] font-bold">Бренды Mally</span>
            <h2 className="font-serif text-2xl sm:text-3xl font-bold text-[#1C1917] mt-1">
              Мировые бренды, доступные для вас
            </h2>
          </div>

          <div className="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6">
            {brandsList.map((b) => (
              <div key={b.name} className="p-6 bg-white rounded-3xl border border-[#EAE3DE] shadow-sm space-y-2">
                <div className="flex justify-between items-start">
                  <h3 className="font-serif text-lg font-bold text-[#1C1917]">{b.name}</h3>
                  <span className="text-[10px] px-2 py-0.5 rounded-full bg-rose-50 text-rose-700 font-bold border border-rose-100">
                    {b.badge}
                  </span>
                </div>
                <span className="text-[11px] text-[#8C827A] block font-medium">Страна: {b.origin}</span>
                <p className="text-xs text-[#57534E] leading-relaxed pt-1">
                  {b.desc}
                </p>
                <Link
                  href={`/catalog?brand=${encodeURIComponent(b.name)}`}
                  className="inline-block pt-2 text-xs font-semibold text-[#1C1917] hover:underline"
                >
                  Смотреть продукцию {b.name} →
                </Link>
              </div>
            ))}
          </div>
        </div>
      </section>

      {/* 6. Отзывы покупателей */}
      <section className="py-16 px-4 bg-white border-b border-[#EAE3DE]">
        <div className="max-w-5xl mx-auto">
          <div className="text-center max-w-xl mx-auto mb-12">
            <span className="text-xs uppercase tracking-widest text-[#8C827A] font-bold">Отзывы клиентов</span>
            <h2 className="font-serif text-2xl sm:text-3xl font-bold text-[#1C1917] mt-1">
              Что говорят о нас наши покупательницы
            </h2>
            <div className="flex items-center justify-center gap-2 mt-2 text-amber-500 text-sm">
              <span>★★★★★</span>
              <span className="text-xs font-semibold text-black">4.9 из 5.0 (более 140 отзывов)</span>
            </div>
          </div>

          <div className="grid grid-cols-1 md:grid-cols-2 gap-6">
            {reviews.map((rev) => (
              <div key={rev.id} className="bg-[#FAF7F5] p-6 rounded-3xl border border-[#EAE3DE] shadow-sm space-y-3">
                <div className="flex justify-between items-start">
                  <div>
                    <h4 className="font-serif font-bold text-sm text-[#1C1917]">{rev.title}</h4>
                    <span className="text-xs text-[#8C827A]">{rev.author}, {rev.city}</span>
                  </div>
                  <span className="text-amber-500 text-xs">★★★★★</span>
                </div>
                <p className="text-xs text-[#57534E] leading-relaxed italic">
                  «{rev.text}»
                </p>
                <div className="pt-2 text-[10px] text-[#A8A29E] border-t border-[#EAE3DE]">
                  Проверенный отзыв • {rev.date}
                </div>
              </div>
            ))}
          </div>
        </div>
      </section>

      {/* 7. FAQ */}
      <section className="py-16 px-4 bg-[#FAF7F5] border-b border-[#EAE3DE]">
        <div className="max-w-3xl mx-auto">
          <div className="text-center mb-10">
            <span className="text-xs uppercase tracking-widest text-[#8C827A] font-bold">Вопросы и ответы</span>
            <h2 className="font-serif text-2xl sm:text-3xl font-bold text-[#1C1917] mt-1">
              Часто задаваемые вопросы
            </h2>
          </div>

          <div className="space-y-3">
            {faqs.map((faq, index) => (
              <div
                key={index}
                className="bg-white rounded-2xl border border-[#EAE3DE] overflow-hidden shadow-sm transition"
              >
                <button
                  type="button"
                  onClick={() => toggleFaq(index)}
                  className="w-full p-4 sm:p-5 text-left flex justify-between items-center text-xs sm:text-sm font-semibold text-[#1C1917] hover:bg-[#FAF8F6] cursor-pointer"
                >
                  <span className="pr-4">{faq.q}</span>
                  <span className="text-base text-[#8C827A] font-mono">
                    {openFaq === index ? '−' : '+'}
                  </span>
                </button>
                {openFaq === index && (
                  <div className="px-5 pb-5 pt-1 text-xs text-[#57534E] leading-relaxed border-t border-[#F2ECE8]">
                    {faq.a}
                  </div>
                )}
              </div>
            ))}
          </div>
        </div>
      </section>

      <Footer />
    </div>
  );
}
