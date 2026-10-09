import React, { useState } from 'react';
import { useNavigate } from 'react-router-dom';
import Link from '../components/Link';
import Header from '../components/Header';
import Footer from '../components/Footer';
import { useCart } from '../context/CartContext';
import { useLanguage } from '../context/LanguageContext';

export default function CartPage() {
  const navigate = useNavigate();
  const {
    cart,
    removeFromCart,
    updateQuantity,
    totalCount,
    totalAmount,
    formatPrice,
    isAuthenticated,
    openAuthModal,
  } = useCart();
  const { t } = useLanguage();

  const [orderNote, setOrderNote] = useState('');
  const [isNoteOpen, setIsNoteOpen] = useState(false);
  const [isShippingOpen, setIsShippingOpen] = useState(false);

  const handleCheckoutClick = (e: React.MouseEvent) => {
    e.preventDefault();
    if (!isAuthenticated) {
      openAuthModal('Войдите или зарегистрируйтесь для оформления заказа');
      return;
    }
    navigate('/checkout');
  };

  return (
    <div className="min-h-screen bg-[#FCECEC] text-[#2C2A29] flex flex-col font-sans">
      <Header />

      {/* Хлебные крошки */}
      <div className="max-w-6xl mx-auto px-4 py-4 w-full text-xs text-[#8C827A] flex items-center gap-2">
        <Link href="/" className="hover:text-black">
          Главная
        </Link>
        <span>/</span>
        <span className="text-[#1C1917]">Ваша корзина</span>
      </div>

      <main className="max-w-6xl mx-auto px-4 py-6 w-full flex-1">
        <h1 className="font-serif text-3xl font-bold text-[#1C1917] mb-8">
          {t('cart.title', 'Ваша корзина')} ({totalCount})
        </h1>

        {cart.length === 0 ? (
          <div className="bg-white rounded-3xl p-16 text-center border border-[#EAE3DE] space-y-4 shadow-sm">
            <div className="w-16 h-16 mx-auto rounded-full bg-[#FAF7F5] flex items-center justify-center text-[#A8A29E] text-2xl">
              <i className="fa-solid fa-bag-shopping" />
            </div>
            <h2 className="font-serif text-xl font-bold">{t('cart.empty_title', 'Ваша корзина пуста')}</h2>
            <p className="text-xs text-[#8C827A] max-w-sm mx-auto">
              {t('cart.empty_desc', 'В вашей корзине пока нет товаров. Перейдите в каталог оригинальной косметики из США.')}
            </p>
            <Link
              href="/catalog"
              className="inline-flex items-center gap-2 px-8 py-3.5 bg-[#1C1917] hover:bg-black text-white text-xs font-semibold rounded-full uppercase tracking-widest transition"
            >
              <span>{t('cart.to_catalog', 'Перейти к покупкам')}</span>
              <i className="fa-solid fa-arrow-right text-[10px]" />
            </Link>
          </div>
        ) : (
          <div className="grid grid-cols-1 lg:grid-cols-3 gap-10">
            {/* Левая колонка: Список товаров */}
            <div className="lg:col-span-2 space-y-4 divide-y divide-[#EAE3DE]">
              {cart.map((item) => (
                <div key={item.id} className="pt-4 first:pt-0 flex items-center justify-between gap-4">
                  {/* Кнопка удаления */}
                  <button
                    type="button"
                    onClick={() => removeFromCart(item.id)}
                    className="text-[#8C827A] hover:text-rose-600 transition p-2 rounded-lg hover:bg-rose-50 cursor-pointer"
                    title="Удалить товар"
                  >
                    <i className="fa-regular fa-trash-can text-sm" />
                  </button>

                  {/* Фото */}
                  <Link
                    href={`/products/${encodeURIComponent(item.sku || item.id)}`}
                    className="w-20 h-20 rounded-2xl bg-[#FAF7F5] overflow-hidden border border-[#EAE3DE] flex-shrink-0 block hover:opacity-90 transition-opacity"
                  >
                    <img src={item.image} alt={item.title} className="w-full h-full object-cover" />
                  </Link>

                  {/* Описание */}
                  <div className="flex-1 min-w-0">
                    <span className="text-[10px] uppercase tracking-wider text-[#8C827A] font-semibold block">
                      {item.brand}
                    </span>
                    <Link
                      href={`/products/${encodeURIComponent(item.sku || item.id)}`}
                      className="text-xs sm:text-sm font-semibold text-[#1C1917] truncate hover:text-[#96674c] transition-colors block"
                    >
                      {item.title}
                    </Link>
                    <div className="text-xs text-[#78716C] mt-1 font-mono">
                      {formatPrice(item.price)}
                    </div>
                  </div>

                  {/* Счетчик [- 1 +] */}
                  <div className="inline-flex items-center border border-[#E3DBD5] rounded-xl bg-white px-2 py-1 shadow-sm">
                    <button
                      type="button"
                      onClick={() => updateQuantity(item.id, item.quantity - 1)}
                      className="w-7 h-7 flex items-center justify-center text-sm font-bold text-[#57534E] hover:text-black transition cursor-pointer"
                    >
                      −
                    </button>
                    <span className="w-8 text-center text-xs font-mono font-bold">
                      {item.quantity}
                    </span>
                    <button
                      type="button"
                      onClick={() => updateQuantity(item.id, item.quantity + 1)}
                      disabled={item.quantity >= (item.maxQuantity || 12)}
                      className={`w-7 h-7 flex items-center justify-center text-sm font-bold transition ${
                        item.quantity >= (item.maxQuantity || 12)
                          ? 'text-stone-300 opacity-40 cursor-not-allowed'
                          : 'text-[#57534E] hover:text-black cursor-pointer'
                      }`}
                      title={item.quantity >= (item.maxQuantity || 12) ? `Максимум на складе (${item.maxQuantity || 12} шт.)` : "Увеличить"}
                    >
                      +
                    </button>
                  </div>

                  {/* Итог по позиции */}
                  <div className="text-right font-serif font-bold text-sm text-[#1C1917] whitespace-nowrap min-w-[100px]">
                    {formatPrice(item.price * item.quantity)}
                  </div>
                </div>
              ))}
            </div>

            {/* Правая колонка: Итог и оформление */}
            <div className="space-y-4">
              {/* Примечание к заказу */}
              <div className="bg-white rounded-2xl border border-[#EAE3DE] overflow-hidden shadow-sm">
                <button
                  type="button"
                  onClick={() => setIsNoteOpen(!isNoteOpen)}
                  className="w-full p-4 flex justify-between items-center text-xs font-semibold text-[#1C1917] hover:bg-[#FAF8F6] cursor-pointer"
                >
                  <span>Примечание к заказу</span>
                  <i className={`fa-solid fa-chevron-${isNoteOpen ? 'up' : 'down'} text-[10px] text-[#8C827A]`} />
                </button>
                {isNoteOpen && (
                  <div className="p-4 pt-0 border-t border-[#F2ECE8]">
                    <textarea
                      rows={3}
                      placeholder="Особые пожелания к доставке..."
                      value={orderNote}
                      onChange={(e) => setOrderNote(e.target.value)}
                      className="w-full p-2.5 text-xs bg-[#FAF8F6] border border-[#E3DBD5] rounded-xl focus:outline-none"
                    />
                  </div>
                )}
              </div>

              {/* Стоимость доставки */}
              <div className="bg-white rounded-2xl border border-[#EAE3DE] overflow-hidden shadow-sm">
                <button
                  type="button"
                  onClick={() => setIsShippingOpen(!isShippingOpen)}
                  className="w-full p-4 flex justify-between items-center text-xs font-semibold text-[#1C1917] hover:bg-[#FAF8F6] cursor-pointer"
                >
                  <span>Ориентировочная стоимость доставки</span>
                  <i className={`fa-solid fa-chevron-${isShippingOpen ? 'up' : 'down'} text-[10px] text-[#8C827A]`} />
                </button>
                {isShippingOpen && (
                  <div className="p-4 pt-0 border-t border-[#F2ECE8] text-xs text-[#57534E] space-y-1.5">
                    <p>• <b>По Ташкенту:</b> быстрая отправка через <b>Яндекс Go</b> (доставка оплачивается покупателем по фактическому тарифу при получении)</p>
                    <p>• <b>Регионы Узбекистана:</b> отправка через курьерскую службу BTS / почту по тарифу перевозчика</p>
                  </div>
                )}
              </div>

              {/* Итоговый блок */}
              <div className="bg-white rounded-2xl border border-[#EAE3DE] p-6 shadow-sm space-y-4">
                <div className="flex justify-between items-baseline">
                  <span className="text-xs text-[#78716C]">Расчетная общая сумма</span>
                  <span className="font-serif text-xl font-bold text-[#1C1917]">
                    {formatPrice(totalAmount)}
                  </span>
                </div>

                <p className="text-[11px] text-[#8C827A] leading-relaxed">
                  Налоги и стоимость доставки рассчитываются при оформлении заказа.
                </p>

                <button
                  type="button"
                  onClick={handleCheckoutClick}
                  className="w-full py-4 bg-[#1C1917] hover:bg-black text-white text-xs font-bold uppercase tracking-wider rounded-2xl shadow-lg transition active:scale-[0.99] flex items-center justify-center gap-2 cursor-pointer"
                >
                  <span>{t('cart.checkout', 'Оформить заказ')}</span>
                  <i className="fa-solid fa-arrow-right text-[10px]" />
                </button>

                <div className="pt-2 text-center">
                  <Link href="/catalog" className="text-xs text-[#8C827A] hover:text-black underline underline-offset-4">
                    Продолжить покупки
                  </Link>
                </div>
              </div>
            </div>
          </div>
        )}
      </main>

      <Footer />
    </div>
  );
}
