import React from 'react';
import Link from '../components/Link';
import Header from '../components/Header';
import Footer from '../components/Footer';

export default function ReturnsPage() {
  return (
    <div className="min-h-screen bg-[#FCECEC] text-[#1C1917] flex flex-col font-sans">
      <Header />

      <main className="flex-1 max-w-4xl mx-auto w-full px-4 sm:px-6 py-12 sm:py-16">
        <div className="bg-white rounded-3xl p-6 sm:p-12 border border-[#EAE0DC] shadow-sm space-y-8">
          <div className="border-b border-[#F0EAE6] pb-6">
            <span className="text-xs uppercase tracking-[0.25em] text-[#8C827A] font-bold block mb-1">
              Регламент магазина
            </span>
            <h1 className="font-serif text-3xl sm:text-4xl font-bold text-[#1C1917]">
              Политика возврата и стандарты качества
            </h1>
            <p className="text-xs text-[#78716C] mt-2">
              Дата обновления: 2026 год · Mally Uzbekistan
            </p>
          </div>

          <div className="p-5 rounded-2xl bg-amber-50 border border-amber-200 text-amber-950 space-y-2">
            <div className="flex items-center gap-2.5 font-bold text-sm">
              <i className="fa-solid fa-shield-halved text-amber-600 text-base" />
              <span>Главный регламент: Товар возврату и обмену не подлежит</span>
            </div>
            <p className="text-xs leading-relaxed text-amber-900">
              В соответствии с законодательством Республики Узбекистан и международными стандартами гигиены, парфюмерно-косметические средства надлежащего качества <strong>не подлежат возврату или обмену</strong> после передачи покупателю.
            </p>
          </div>

          <div className="space-y-6 text-xs sm:text-sm text-[#57534E] leading-relaxed">
            <section className="space-y-2.5">
              <h2 className="font-serif text-lg font-bold text-[#1C1917] flex items-center gap-2">
                <i className="fa-solid fa-circle-check text-emerald-600 text-sm" />
                1. Исключение брака и предпродажный контроль
              </h2>
              <p>
                В магазине Mally <strong>полностью исключена отправка бракованной продукции</strong>. Перед передачей каждого заказа курьерской службе:
              </p>
              <ul className="list-disc pl-5 space-y-1.5 text-xs text-[#78716C]">
                <li>Каждая позиция проходит визуальную проверку на целостность флакона, дозатора и заводской упаковки.</li>
                <li>Проверяется наличие и сохранность оригинальных заводских защитных пломб и слюды.</li>
                <li>Сверяются сроки годности и батч-коды подлинности от официальных дистрибьюторов в США и Европе.</li>
                <li>Заказ надежно упаковывается в плотную амортизирующую коробку для безопасной транспортировки.</li>
              </ul>
            </section>

            <section className="space-y-2.5 pt-4 border-t border-[#F0EAE6]">
              <h2 className="font-serif text-lg font-bold text-[#1C1917] flex items-center gap-2">
                <i className="fa-solid fa-truck-fast text-[#0D9488] text-sm" />
                2. Доставка через Яндекс Go
              </h2>
              <p>
                Все заказы передаются в службу <strong>Яндекс Go</strong>. Стоимость доставки оплачивается покупателем непосредственно при получении по актуальному тарифу курьерской службы.
              </p>
            </section>

            <section className="space-y-2.5 pt-4 border-t border-[#F0EAE6]">
              <h2 className="font-serif text-lg font-bold text-[#1C1917] flex items-center gap-2">
                <i className="fa-solid fa-credit-card text-sky-600 text-sm" />
                3. Способы оплаты и комиссии
              </h2>
              <p>
                Оплата заказов осуществляется прямым переводом на карту Humo / Uzcard по официальным реквизитам Mally Beauty. Если ваш банк взимает комиссию за межбанковский перевод, данная комиссия покрывается отправителем платежа.
              </p>
            </section>

            <section className="space-y-2.5 pt-4 border-t border-[#F0EAE6]">
              <h2 className="font-serif text-lg font-bold text-[#1C1917] flex items-center gap-2">
                <i className="fa-brands fa-telegram text-[#0088cc] text-sm" />
                4. Служба заботы о клиентах
              </h2>
              <p>
                Если у вас возникли вопросы по составу заказа, применению косметики или подбору оттенка, наш отдел заботы работает ежедневно с 10:00 до 20:00 в Telegram:
              </p>
              <div className="pt-2">
                <a
                  href="https://t.me/mallyuz"
                  target="_blank"
                  rel="noreferrer"
                  className="inline-flex items-center gap-2 px-5 py-2.5 rounded-xl bg-[#0088cc] hover:bg-[#0077b5] text-white font-semibold text-xs transition shadow-xs"
                >
                  <i className="fa-brands fa-telegram text-sm" />
                  <span>Написать в Telegram @mallyuz</span>
                </a>
              </div>
            </section>
          </div>
        </div>
      </main>

      <Footer />
    </div>
  );
}
