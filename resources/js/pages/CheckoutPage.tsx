import React, { useState, useEffect } from 'react';
import { useNavigate } from 'react-router-dom';
import Link from '../components/Link';
import { useCart } from '../context/CartContext';
import { useToast } from '../context/ToastContext';
import { useLanguage } from '../context/LanguageContext';

export default function CheckoutPage() {
  const navigate = useNavigate();
  const { cart, totalAmount, formatPrice, clearCart, user, isAuthenticated, openAuthModal, deviceId, exchangeRate } = useCart();
  const { showToast } = useToast();
  const { t, language } = useLanguage();

  const [firstName, setFirstName] = useState('');
  const [lastName, setLastName] = useState('');
  const [address, setAddress] = useState('');
  const [apartment, setApartment] = useState('');
  const [city, setCity] = useState('Ташкент');
  const [postalCode, setPostalCode] = useState('');
  const [phoneBilling, setPhoneBilling] = useState('+998 ');
  const [comment, setComment] = useState('');

  useEffect(() => {
    if (user) {
      if (user.firstName) setFirstName(user.firstName);
      if (user.lastName) setLastName(user.lastName);
      if (!user.firstName && !user.lastName && user.fullName) {
        const parts = user.fullName.trim().split(' ');
        setFirstName(parts[0] || '');
        setLastName(parts.slice(1).join(' ') || '');
      }
      if (user.phone) setPhoneBilling(user.phone);
      if (user.city) setCity(user.city);
      if (user.address) setAddress(user.address);
    }
  }, [user]);

  const [promoCode, setPromoCode] = useState('');
  const [discountAmount, setDiscountAmount] = useState(0);
  const [appliedPromo, setAppliedPromo] = useState<string | null>(null);
  const [promoLoading, setPromoLoading] = useState(false);

  const [loading, setLoading] = useState(false);

  const applyPromo = async () => {
    if (!promoCode.trim()) {
      showToast(language === 'uz' ? 'Promokodni kiriting' : 'Введите промокод', 'warning');
      return;
    }

    setPromoLoading(true);
    try {
      const res = await fetch('/api/promocodes/apply', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({
          code: promoCode.trim(),
          totalAmount,
          userId: user?.id,
          customerPhone: phoneBilling || user?.phone || '',
        }),
      });
      const data = await res.json();
      if (res.ok && data.success) {
        setDiscountAmount(data.discountAmount);
        setAppliedPromo(data.code);
        showToast(data.message || 'Промокод применён!', 'success');
      } else {
        setDiscountAmount(0);
        setAppliedPromo(null);
        showToast(data.error || 'Промокод не найден', 'error');
      }
    } catch (e: any) {
      showToast('Ошибка проверки промокода: ' + e.message, 'error');
    } finally {
      setPromoLoading(false);
    }
  };

  const finalTotal = Math.max(0, totalAmount - discountAmount);

  const handleCreateOrder = async (e: React.FormEvent) => {
    e.preventDefault();

    if (!isAuthenticated || !user) {
      showToast('Для оформления заказа необходимо войти', 'warning');
      openAuthModal('Для оформления заказа необходимо войти');
      return;
    }

    if (cart.length === 0) {
      showToast('Ваша корзина пуста', 'warning');
      return;
    }

    // Защита от оформления больше фактического наличия на складе
    for (const item of cart) {
      const maxStock = (item as any).maxQuantity ?? (item as any).stockCount ?? 999;
      if (item.quantity > maxStock) {
        showToast(`Нельзя оформить больше наличия для «${item.title}». На складе доступно всего: ${maxStock} шт.`, 'warning');
        return;
      }
    }

    setLoading(true);

    try {
      const fullAddress = `${address}${apartment ? ', кв. ' + apartment : ''}${postalCode ? ' (' + postalCode + ')' : ''}`;
      const customerFullName = `${firstName} ${lastName}`.trim() || user.fullName || 'Покупатель';
      const payload = {
        customerName: customerFullName,
        customerPhone: phoneBilling || user.phone || '',
        userId: user.id,
        deviceId,
        promoCode: appliedPromo || undefined,
        city: city || 'Ташкент',
        address: fullAddress,
        comment: comment.trim() ? comment.trim() : null,
        items: cart.map((i) => ({
          id: i.id,
          sku: i.sku,
          title: i.title,
          quantity: i.quantity,
          price: i.price,
        })),
      };

      const res = await fetch('/api/orders', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify(payload),
      });

      const data = await res.json();
      if (res.ok && data.success) {
        clearCart();
        showToast(`Заказ №${data.orderNumber} успешно оформлен!`, 'success');
        navigate(`/checkout/${data.orderNumber}/${data.orderHash || ''}`);
        return;
      } else {
        showToast(data.error || 'Ошибка оформления заказа', 'error');
      }
    } catch (err: any) {
      showToast(`Ошибка связи: ${err.message}`, 'error');
    } finally {
      setLoading(false);
    }
  };

  return (
    <div className="min-h-screen bg-[#FAF7F5] text-[#2C2A29] font-sans flex flex-col">
      {/* Шапка Checkout */}
      <header className="border-b border-[#EAE3DE] py-4 px-6 bg-white/90 backdrop-blur-md sticky top-0 z-30 shadow-2xs">
        <div className="max-w-6xl mx-auto flex justify-between items-center">
          <Link href="/" className="flex flex-col">
            <span className="font-serif text-2xl font-bold tracking-tight text-[#1C1917]">mally</span>
            <span className="text-[9px] uppercase tracking-[0.25em] text-[#8C827A]">Beauty without limits</span>
          </Link>
          <Link href="/cart" className="text-xs font-semibold text-[#78716C] hover:text-black flex items-center gap-1.5 transition">
            <i className="fa-solid fa-arrow-left text-xs" />
            <span>Вернуться в корзину</span>
          </Link>
        </div>
      </header>

      <main className="max-w-6xl mx-auto w-full flex-1 grid grid-cols-1 lg:grid-cols-12 py-4 sm:py-8">
        {/* Левая колонка: поля */}
        <div className="lg:col-span-7 px-4 sm:px-8 py-4 space-y-8">
              <form onSubmit={handleCreateOrder} className="space-y-6">
                <div>
                  <h2 className="text-base font-bold text-[#1C1917] mb-3">1. Получатель заказа</h2>
                  <div className="grid grid-cols-1 sm:grid-cols-2 gap-3">
                    <div>
                      <label className="block text-[11px] font-bold text-[#8C827A] mb-1">Имя</label>
                      <input
                        type="text"
                        required
                        value={firstName}
                        onChange={(e) => setFirstName(e.target.value)}
                        placeholder="Имя"
                        className="w-full px-4 py-2.5 bg-white border border-[#E3DBD5] rounded-xl text-xs focus:outline-none focus:border-[#1C1917]"
                      />
                    </div>
                    <div>
                      <label className="block text-[11px] font-bold text-[#8C827A] mb-1">Фамилия</label>
                      <input
                        type="text"
                        value={lastName}
                        onChange={(e) => setLastName(e.target.value)}
                        placeholder="Фамилия"
                        className="w-full px-4 py-2.5 bg-white border border-[#E3DBD5] rounded-xl text-xs focus:outline-none focus:border-[#1C1917]"
                      />
                    </div>
                  </div>
                  <div className="mt-3">
                    <label className="block text-[11px] font-bold text-[#8C827A] mb-1">Телефон для связи</label>
                    <input
                      type="tel"
                      required
                      value={phoneBilling}
                      onChange={(e) => setPhoneBilling(e.target.value)}
                      placeholder="+998 90 123 45 67"
                      className="w-full px-4 py-2.5 bg-white border border-[#E3DBD5] rounded-xl text-xs font-mono focus:outline-none focus:border-[#1C1917]"
                    />
                  </div>
                </div>

                <div>
                  <h2 className="text-base font-bold text-[#1C1917] mb-3">2. Адрес доставки</h2>
                  <div className="space-y-3">
                    <div>
                      <label className="block text-[11px] font-bold text-[#8C827A] mb-1">Город</label>
                      <input
                        type="text"
                        required
                        value={city}
                        onChange={(e) => setCity(e.target.value)}
                        placeholder="Ташкент"
                        className="w-full px-4 py-2.5 bg-white border border-[#E3DBD5] rounded-xl text-xs focus:outline-none focus:border-[#1C1917]"
                      />
                    </div>
                    <div>
                      <label className="block text-[11px] font-bold text-[#8C827A] mb-1">Улица и дом</label>
                      <input
                        type="text"
                        required
                        value={address}
                        onChange={(e) => setAddress(e.target.value)}
                        placeholder="ул. Амира Темура, д. 10"
                        className="w-full px-4 py-2.5 bg-white border border-[#E3DBD5] rounded-xl text-xs focus:outline-none focus:border-[#1C1917]"
                      />
                    </div>
                    <div className="grid grid-cols-2 gap-3">
                      <div>
                        <label className="block text-[11px] font-bold text-[#8C827A] mb-1">Квартира / Офис</label>
                        <input
                          type="text"
                          value={apartment}
                          onChange={(e) => setApartment(e.target.value)}
                          placeholder="кв. 42"
                          className="w-full px-4 py-2.5 bg-white border border-[#E3DBD5] rounded-xl text-xs focus:outline-none focus:border-[#1C1917]"
                        />
                      </div>
                      <div>
                        <label className="block text-[11px] font-bold text-[#8C827A] mb-1">Почтовый индекс</label>
                        <input
                          type="text"
                          value={postalCode}
                          onChange={(e) => setPostalCode(e.target.value)}
                          placeholder="100000"
                          className="w-full px-4 py-2.5 bg-white border border-[#E3DBD5] rounded-xl text-xs focus:outline-none focus:border-[#1C1917]"
                        />
                      </div>
                    </div>
                  </div>
                </div>

                <div>
                  <h2 className="text-base font-bold text-[#1C1917] mb-3">3. Комментарий к заказу</h2>
                  <textarea
                    rows={2}
                    value={comment}
                    onChange={(e) => setComment(e.target.value)}
                    placeholder="Пожелания курьеру..."
                    className="w-full px-4 py-2.5 bg-white border border-[#E3DBD5] rounded-xl text-xs focus:outline-none focus:border-[#1C1917]"
                  />
                </div>

                <div className="p-4 bg-white rounded-2xl border border-[#EAE3DE] space-y-2">
                  <div className="flex items-center gap-2 text-xs font-bold text-[#1C1917]">
                    <i className="fa-solid fa-credit-card text-amber-600" />
                    <span>Оплата переводом на карту Humo / Uzcard</span>
                  </div>
                  <p className="text-[11px] text-[#78716C] leading-relaxed">
                    После подтверждения заказа на экране отобразится защищенная банковская карта для перевода через Payme, Click или Uzum Bank.
                  </p>
                </div>

                <button
                  type="submit"
                  disabled={loading}
                  className="w-full py-4 bg-[#1C1917] hover:bg-black text-white text-xs font-bold uppercase tracking-wider rounded-2xl shadow-xl transition active:scale-[0.99] flex items-center justify-center gap-2 cursor-pointer disabled:opacity-50"
                >
                  {loading ? 'Создание заказа...' : `Подтвердить заказ на ${formatPrice(finalTotal)}`}
                </button>
              </form>
            </div>

            {/* Правая колонка: Состав заказа */}
            <div className="lg:col-span-5 px-4 sm:px-6 py-4">
              <div className="bg-white rounded-3xl p-6 border border-[#EAE3DE] shadow-sm space-y-6 sticky top-20">
                <h3 className="font-serif text-lg font-bold text-[#1C1917]">
                  Ваш заказ ({cart.length})
                </h3>

                <div className="divide-y divide-[#FAF7F5] max-h-64 overflow-y-auto pr-1">
                  {cart.map((item) => (
                    <div key={item.id} className="py-3 first:pt-0 last:pb-0 flex items-center gap-3">
                      <div className="w-12 h-12 rounded-xl bg-[#FAF7F5] border border-[#EAE3DE] overflow-hidden flex-shrink-0">
                        <img src={item.image} alt={item.title} className="w-full h-full object-cover" />
                      </div>
                      <div className="flex-1 min-w-0">
                        <h4 className="text-xs font-semibold text-[#1C1917] truncate">{item.title}</h4>
                        <span className="text-[10px] text-[#8C827A] font-mono">
                          {item.quantity} шт. × {formatPrice(item.price)}
                        </span>
                      </div>
                      <div className="font-mono text-xs font-bold text-[#1C1917]">
                        {formatPrice(item.price * item.quantity)}
                      </div>
                    </div>
                  ))}
                </div>

                {/* Промокод */}
                <div className="pt-2 border-t border-[#F2ECE8]">
                  <div className="flex gap-2">
                    <input
                      type="text"
                      value={promoCode}
                      onChange={(e) => setPromoCode(e.target.value)}
                      placeholder="Промокод (MALLY10)"
                      className="flex-1 px-3 py-2 bg-[#FAF7F5] border border-[#E3DBD5] rounded-xl text-xs uppercase font-mono font-bold focus:outline-none"
                    />
                    <button
                      type="button"
                      disabled={promoLoading}
                      onClick={applyPromo}
                      className="px-4 py-2 bg-[#1C1917] text-white rounded-xl text-xs font-semibold hover:bg-black transition cursor-pointer disabled:opacity-50"
                    >
                      {promoLoading ? '...' : 'Применить'}
                    </button>
                  </div>
                  {appliedPromo && (
                    <span className="text-[11px] text-emerald-600 block mt-1 font-semibold">
                      ✓ Промокод {appliedPromo} активен (-{formatPrice(discountAmount)})
                    </span>
                  )}
                </div>

                {/* Итоги */}
                <div className="pt-2 border-t border-[#F2ECE8] space-y-2 text-xs">
                  <div className="flex justify-between text-[#78716C]">
                    <span>Товары:</span>
                    <span className="font-mono">{formatPrice(totalAmount)}</span>
                  </div>
                  {discountAmount > 0 && (
                    <div className="flex justify-between text-emerald-600 font-semibold">
                      <span>Скидка:</span>
                      <span className="font-mono">-{formatPrice(discountAmount)}</span>
                    </div>
                  )}
                  <div className="flex justify-between text-[#78716C]">
                    <span>Доставка:</span>
                    <span className="text-[11px]">По тарифу Яндекс Go</span>
                  </div>
                  <div className="flex justify-between items-baseline pt-2 border-t border-[#F2ECE8] font-bold text-base text-[#1C1917]">
                    <span>Итого к оплате:</span>
                    <span className="font-serif text-xl">{formatPrice(finalTotal)}</span>
                  </div>
                </div>
              </div>
            </div>
      </main>
    </div>
  );
}
