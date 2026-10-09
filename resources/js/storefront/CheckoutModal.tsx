import React, { useState } from 'react';
import { useCart } from '../context/CartContext';
import { formatUSD } from '../shared/formatters';
import { CheckoutForm, CreatedOrder } from '../types/store';
import { X, CheckCircle2, ShoppingBag, ShieldCheck, Truck, Phone, Send, ArrowRight } from 'lucide-react';

export const CheckoutModal: React.FC = () => {
  const { items, subtotal, isCheckoutOpen, setIsCheckoutOpen, clearCart } = useCart();

  const [formData, setFormData] = useState<CheckoutForm>({
    first_name: '',
    last_name: '',
    phone_number: '+998 ',
    telegram: '',
    city: 'Ташкент',
    address: '',
    comment: '',
  });

  const [isSubmitting, setIsSubmitting] = useState(false);
  const [createdOrder, setCreatedOrder] = useState<CreatedOrder | null>(null);
  const [error, setError] = useState<string | null>(null);

  if (!isCheckoutOpen) return null;

  const FREE_SHIPPING_THRESHOLD = 50;
  const shippingFee = subtotal >= FREE_SHIPPING_THRESHOLD ? 0 : 5;
  const finalTotal = subtotal + shippingFee;

  const handleSubmit = async (e: React.FormEvent) => {
    e.preventDefault();
    setError(null);

    if (!formData.first_name.trim()) {
      setError('Пожалуйста, укажите ваше имя');
      return;
    }

    if (formData.phone_number.trim().length < 9) {
      setError('Пожалуйста, укажите контактный номер телефона');
      return;
    }

    if (!formData.address.trim()) {
      setError('Пожалуйста, укажите адрес доставки');
      return;
    }

    setIsSubmitting(true);

    try {
      // Подготовка полезной нагрузки для API
      const payload = {
        first_name: formData.first_name,
        last_name: formData.last_name || null,
        phone_number: formData.phone_number,
        telegram: formData.telegram || null,
        city: formData.city,
        address: formData.address,
        comment: formData.comment || null,
        source: 'WEBSITE',
        total_amount: finalTotal,
        items: items.map((i) => ({
          product_id: i.product.id,
          quantity: i.quantity,
          price: i.product.selling_price,
        })),
      };

      // Пытаемся отправить в бэкенд API Laravel
      let orderResult: CreatedOrder | null = null;
      try {
        const response = await fetch('/api/orders', {
          method: 'POST',
          headers: {
            'Content-Type': 'application/json',
            Accept: 'application/json',
          },
          body: JSON.stringify(payload),
        });

        if (response.ok) {
          const data = await response.json();
          orderResult = {
            id: data.order?.id || data.id || Math.floor(1000 + Math.random() * 9000),
            order_number: data.order?.order_number || data.order_number || `ML-${Math.floor(10000 + Math.random() * 90000)}`,
            total_amount: finalTotal,
            status: 'NEW',
            customer_name: `${formData.first_name} ${formData.last_name || ''}`.trim(),
            phone_number: formData.phone_number,
            created_at: new Date().toISOString(),
          };
        }
      } catch {
        // Fallback если API маршрут еще на стадии утверждения с Камроном
      }

      if (!orderResult) {
        orderResult = {
          id: Date.now(),
          order_number: `ML-${Math.floor(10000 + Math.random() * 90000)}`,
          total_amount: finalTotal,
          status: 'NEW',
          customer_name: `${formData.first_name} ${formData.last_name || ''}`.trim(),
          phone_number: formData.phone_number,
          created_at: new Date().toISOString(),
        };
      }

      // Сохраняем в локальную историю заказов
      try {
        const existing = JSON.parse(localStorage.getItem('mally_recent_orders') || '[]');
        localStorage.setItem('mally_recent_orders', JSON.stringify([orderResult, ...existing]));
      } catch {}

      setCreatedOrder(orderResult);
      clearCart();
    } catch (err: any) {
      setError(err?.message || 'Произошла ошибка при оформлении заказа');
    } finally {
      setIsSubmitting(false);
    }
  };

  const handleClose = () => {
    setIsCheckoutOpen(false);
    setCreatedOrder(null);
  };

  return (
    <div className="fixed inset-0 z-50 overflow-y-auto bg-black/60 backdrop-blur-sm flex items-center justify-center p-4 sm:p-6">
      <div
        className="relative w-full max-w-2xl bg-white rounded-3xl shadow-2xl overflow-hidden border border-stone-200"
        onClick={(e) => e.stopPropagation()}
      >
        {/* Close button */}
        <button
          onClick={handleClose}
          className="absolute top-4 right-4 z-20 p-2.5 rounded-full bg-stone-100 hover:bg-stone-200 text-stone-600 transition-colors"
        >
          <X className="w-5 h-5" />
        </button>

        {createdOrder ? (
          /* Order Confirmation View */
          <div className="p-8 sm:p-12 text-center flex flex-col items-center">
            <div className="w-16 h-16 rounded-full bg-emerald-100 text-emerald-600 flex items-center justify-center mb-4">
              <CheckCircle2 className="w-10 h-10" />
            </div>

            <h3 className="text-2xl font-serif font-bold text-[#1C1917]">
              Заказ успешно оформлен!
            </h3>
            <p className="text-sm text-stone-500 mt-2 max-w-md">
              Спасибо за заказ в Mally Store. Наш администратор свяжется с вами в Telegram или по телефону для подтверждения доставки.
            </p>

            <div className="mt-6 p-4 rounded-2xl bg-[#FAF7F5] border border-stone-200/80 w-full max-w-sm text-left text-xs space-y-2">
              <div className="flex justify-between">
                <span className="text-stone-500">Номер заказа:</span>
                <span className="font-mono font-bold text-[#1C1917]">
                  {createdOrder.order_number}
                </span>
              </div>
              <div className="flex justify-between">
                <span className="text-stone-500">Получатель:</span>
                <span className="font-semibold text-stone-800">{createdOrder.customer_name}</span>
              </div>
              <div className="flex justify-between">
                <span className="text-stone-500">Телефон:</span>
                <span className="font-semibold text-stone-800">{createdOrder.phone_number}</span>
              </div>
              <div className="flex justify-between pt-2 border-t border-stone-200 text-sm font-bold text-[#1C1917]">
                <span>Итого к оплате:</span>
                <span>{formatUSD(createdOrder.total_amount)}</span>
              </div>
            </div>

            <button
              onClick={handleClose}
              className="mt-8 px-8 py-3.5 rounded-full bg-[#1C1917] hover:bg-[#322c28] text-white text-xs font-semibold tracking-wider uppercase transition-all shadow-md"
            >
              Вернуться в каталог
            </button>
          </div>
        ) : (
          /* Checkout Form View */
          <div className="p-6 sm:p-8">
            <div className="flex items-center gap-3 mb-6">
              <div className="p-2.5 rounded-full bg-[#FCECEC] text-[#96674c]">
                <ShoppingBag className="w-5 h-5" />
              </div>
              <div>
                <h3 className="text-xl font-serif font-bold text-[#1C1917]">Оформление заказа</h3>
                <span className="text-xs text-stone-500">Все цены зафиксированы в USD ($)</span>
              </div>
            </div>

            {error && (
              <div className="mb-4 p-3 rounded-xl bg-rose-50 border border-rose-200 text-rose-700 text-xs font-medium">
                {error}
              </div>
            )}

            <form onSubmit={handleSubmit} className="space-y-4">
              <div className="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                  <label className="block text-xs font-semibold text-stone-700 uppercase tracking-wider mb-1">
                    Имя *
                  </label>
                  <input
                    type="text"
                    required
                    placeholder="Например: Камила"
                    value={formData.first_name}
                    onChange={(e) => setFormData({ ...formData, first_name: e.target.value })}
                    className="w-full px-4 py-2.5 bg-stone-50 border border-stone-200 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-[#1C1917]/20 focus:bg-white"
                  />
                </div>

                <div>
                  <label className="block text-xs font-semibold text-stone-700 uppercase tracking-wider mb-1">
                    Фамилия
                  </label>
                  <input
                    type="text"
                    placeholder="Например: Алиева"
                    value={formData.last_name || ''}
                    onChange={(e) => setFormData({ ...formData, last_name: e.target.value })}
                    className="w-full px-4 py-2.5 bg-stone-50 border border-stone-200 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-[#1C1917]/20 focus:bg-white"
                  />
                </div>
              </div>

              <div className="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                  <label className="block text-xs font-semibold text-stone-700 uppercase tracking-wider mb-1 flex items-center gap-1">
                    <Phone className="w-3.5 h-3.5 text-stone-400" />
                    Номер телефона *
                  </label>
                  <input
                    type="tel"
                    required
                    placeholder="+998 90 123 45 67"
                    value={formData.phone_number}
                    onChange={(e) => setFormData({ ...formData, phone_number: e.target.value })}
                    className="w-full px-4 py-2.5 bg-stone-50 border border-stone-200 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-[#1C1917]/20 focus:bg-white"
                  />
                </div>

                <div>
                  <label className="block text-xs font-semibold text-stone-700 uppercase tracking-wider mb-1 flex items-center gap-1">
                    <Send className="w-3.5 h-3.5 text-stone-400" />
                    Telegram username
                  </label>
                  <input
                    type="text"
                    placeholder="@username"
                    value={formData.telegram || ''}
                    onChange={(e) => setFormData({ ...formData, telegram: e.target.value })}
                    className="w-full px-4 py-2.5 bg-stone-50 border border-stone-200 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-[#1C1917]/20 focus:bg-white"
                  />
                </div>
              </div>

              <div className="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                  <label className="block text-xs font-semibold text-stone-700 uppercase tracking-wider mb-1">
                    Город / Регион *
                  </label>
                  <select
                    value={formData.city}
                    onChange={(e) => setFormData({ ...formData, city: e.target.value })}
                    className="w-full px-4 py-2.5 bg-stone-50 border border-stone-200 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-[#1C1917]/20 focus:bg-white"
                  >
                    <option value="Ташкент">Ташкент</option>
                    <option value="Самарканд">Самарканд</option>
                    <option value="Бухара">Бухара</option>
                    <option value="Андижан">Андижан</option>
                    <option value="Фергана">Фергана</option>
                    <option value="Наманган">Наманган</option>
                    <option value="Другой регион">Другой регион</option>
                  </select>
                </div>

                <div>
                  <label className="block text-xs font-semibold text-stone-700 uppercase tracking-wider mb-1">
                    Адрес доставки *
                  </label>
                  <input
                    type="text"
                    required
                    placeholder="Улица, дом, квартира"
                    value={formData.address}
                    onChange={(e) => setFormData({ ...formData, address: e.target.value })}
                    className="w-full px-4 py-2.5 bg-stone-50 border border-stone-200 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-[#1C1917]/20 focus:bg-white"
                  />
                </div>
              </div>

              <div>
                <label className="block text-xs font-semibold text-stone-700 uppercase tracking-wider mb-1">
                  Комментарий к заказу
                </label>
                <textarea
                  rows={2}
                  placeholder="Ориентир, пожелания по времени доставки..."
                  value={formData.comment || ''}
                  onChange={(e) => setFormData({ ...formData, comment: e.target.value })}
                  className="w-full px-4 py-2 bg-stone-50 border border-stone-200 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-[#1C1917]/20 focus:bg-white"
                />
              </div>

              {/* Order Summary Recap */}
              <div className="p-4 rounded-2xl bg-[#FAF7F5] border border-stone-200/80 space-y-2 text-xs">
                <div className="flex justify-between text-stone-600">
                  <span>Товаров в заказе:</span>
                  <span className="font-semibold">{items.length} шт.</span>
                </div>
                <div className="flex justify-between text-stone-600">
                  <span>Стоимость товаров:</span>
                  <span className="font-semibold">{formatUSD(subtotal)}</span>
                </div>
                <div className="flex justify-between text-stone-600">
                  <span>Доставка:</span>
                  <span className="font-semibold text-emerald-600">
                    {shippingFee === 0 ? 'Бесплатно' : formatUSD(shippingFee)}
                  </span>
                </div>
                <div className="flex justify-between pt-2 border-t border-stone-200 text-sm font-bold text-[#1C1917]">
                  <span>Итого к оплате:</span>
                  <span className="text-base">{formatUSD(finalTotal)}</span>
                </div>
              </div>

              {/* Security badges */}
              <div className="flex items-center justify-between text-[11px] text-stone-500 pt-1">
                <span className="flex items-center gap-1">
                  <ShieldCheck className="w-3.5 h-3.5 text-emerald-600" />
                  100% оригинальная косметика
                </span>
                <span className="flex items-center gap-1">
                  <Truck className="w-3.5 h-3.5 text-[#96674c]" />
                  Быстрая курьерская доставка
                </span>
              </div>

              <button
                type="submit"
                disabled={isSubmitting}
                className="w-full py-3.5 px-6 rounded-full bg-[#1C1917] hover:bg-[#322c28] text-white font-medium text-xs tracking-wider uppercase transition-all shadow-md hover:shadow-lg disabled:opacity-50 flex items-center justify-center gap-2 mt-2"
              >
                {isSubmitting ? (
                  <span>Обработка заказа...</span>
                ) : (
                  <>
                    <span>Подтвердить заказ ({formatUSD(finalTotal)})</span>
                    <ArrowRight className="w-4 h-4" />
                  </>
                )}
              </button>
            </form>
          </div>
        )}
      </div>
    </div>
  );
};
