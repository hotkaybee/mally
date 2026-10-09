import React, { useState, useEffect } from 'react';
import { useSearchParams } from 'react-router-dom';
import Link from '../components/Link';
import Header from '../components/Header';
import Footer from '../components/Footer';

export default function TrackPage() {
  const [searchParams] = useSearchParams();
  const [query, setQuery] = useState(searchParams.get('q') || '');
  const [order, setOrder] = useState<any>(null);
  const [loading, setLoading] = useState(false);
  const [error, setError] = useState<string | null>(null);

  const handleSearch = async (targetQuery?: string) => {
    const q = (targetQuery !== undefined ? targetQuery : query).trim();
    if (!q) return;

    setLoading(true);
    setError(null);
    setOrder(null);

    try {
      const res = await fetch(`/api/orders/track?q=${encodeURIComponent(q)}`);
      const data = await res.json();
      if (res.ok && data.order) {
        setOrder(data.order);
      } else {
        setError(data.error || 'Заказ не найден');
      }
    } catch (err: any) {
      setError('Ошибка подключения к серверу');
    } finally {
      setLoading(false);
    }
  };

  useEffect(() => {
    const q = searchParams.get('q');
    if (q) {
      setQuery(q);
      handleSearch(q);
    }
  }, [searchParams]);

  const steps = [
    { key: 'NEW', label: 'Заявка принята', desc: 'Ожидает перевода на карту' },
    { key: 'PAYMENT_RECEIVED', label: 'Оплата подтверждена', desc: 'Чек проверен администратором' },
    { key: 'PACKING', label: 'Сборка заказа', desc: 'Комплектуется на складе' },
    { key: 'SHIPPED', label: 'Передан в доставку', desc: 'Курьер везет заказ' },
    { key: 'DELIVERED', label: 'Доставлен', desc: 'Успешно вручен клиенту' },
  ];

  const getStepIndex = (status: string) => {
    switch (status) {
      case 'NEW': return 0;
      case 'PAYMENT_RECEIVED': return 1;
      case 'PACKING': return 2;
      case 'SHIPPED': return 3;
      case 'DELIVERED': return 4;
      default: return 0;
    }
  };

  const currentStep = order ? getStepIndex(order.status) : 0;

  return (
    <div className="min-h-screen bg-[#FCECEC] text-[#2C2A29] font-sans flex flex-col">
      <Header />

      <main className="max-w-2xl mx-auto px-4 py-12 flex-1 w-full">
        <div className="text-center mb-8">
          <span className="text-[10px] uppercase tracking-widest text-[#8C827A] font-bold">
            Отслеживание доставки
          </span>
          <h1 className="font-serif text-3xl font-bold text-[#1C1917] mt-1">
            Где мой заказ?
          </h1>
          <p className="text-xs text-[#78716C] mt-2">
            Введите номер вашего заказа (например, 1001) или номер телефона, указанный при покупке
          </p>
        </div>

        {/* Форма поиска */}
        <form
          onSubmit={(e) => {
            e.preventDefault();
            handleSearch();
          }}
          className="flex gap-2"
        >
          <input
            type="text"
            required
            placeholder="Номер заказа (1001) или телефон (+998 ...)"
            value={query}
            onChange={(e) => setQuery(e.target.value)}
            className="flex-1 px-4 py-3 bg-white border border-[#E3DBD5] rounded-2xl text-xs focus:outline-none focus:border-[#8C827A] shadow-sm"
          />
          <button
            type="submit"
            disabled={loading}
            className="px-6 py-3 bg-[#1C1917] text-white text-xs font-semibold rounded-2xl hover:bg-black transition shadow-sm disabled:opacity-50 cursor-pointer"
          >
            {loading ? 'Поиск...' : 'Проверить'}
          </button>
        </form>

        {error && (
          <div className="mt-4 p-4 bg-rose-50 border border-rose-200 text-rose-700 rounded-2xl text-xs text-center">
            {error}
          </div>
        )}

        {/* Результат поиска */}
        {order && (
          <div className="mt-8 bg-white rounded-3xl p-6 sm:p-8 border border-[#EAE3DE] shadow-md animate-fade-in space-y-6">
            <div className="flex flex-col sm:flex-row justify-between items-start sm:items-center pb-6 border-b border-[#F2ECE8] gap-2">
              <div>
                <span className="text-[10px] uppercase tracking-wider text-[#8C827A] font-medium">
                  Информация о заказе
                </span>
                <h2 className="font-serif text-xl font-bold text-[#1C1917]">
                  Заказ №{order.orderNumber}
                </h2>
              </div>
              <div className="text-right">
                <span className="text-xs font-serif font-bold text-black block">
                  {order.totalAmount.toLocaleString('ru-RU')} сум
                </span>
                <span className="text-[10px] text-[#8C827A]">
                  {new Date(order.createdAt).toLocaleDateString('ru-RU')}
                </span>
              </div>
            </div>

            {/* Таймлайн статуса заказа */}
            <div className="py-2">
              {order.status === 'CANCELLED' ? (
                <div className="p-4 bg-rose-50 border border-rose-200 text-rose-700 rounded-2xl text-xs text-center font-medium">
                  Данный заказ был отменен. Если у вас есть вопросы, напишите администратору @mallyuz в Telegram.
                </div>
              ) : (
                <div className="relative pl-6 space-y-6 before:absolute before:left-2.5 before:top-2 before:bottom-2 before:w-0.5 before:bg-[#EAE3DE]">
                  {steps.map((s, idx) => {
                    const isDone = idx <= currentStep;
                    const isCurrent = idx === currentStep;

                    return (
                      <div key={s.key} className="relative flex items-start gap-4">
                        <div
                          className={`absolute -left-6 w-5 h-5 rounded-full flex items-center justify-center text-[10px] font-bold border-2 ${
                            isDone
                              ? 'bg-[#1C1917] text-white border-[#1C1917]'
                              : 'bg-white text-gray-300 border-[#EAE3DE]'
                          }`}
                        >
                          {isDone ? '✓' : idx + 1}
                        </div>
                        <div>
                          <h4
                            className={`text-xs font-semibold ${
                              isCurrent
                                ? 'text-black font-bold'
                                : isDone
                                ? 'text-[#44403C]'
                                : 'text-gray-400'
                            }`}
                          >
                            {s.label}
                          </h4>
                          <p className="text-[11px] text-[#8C827A] mt-0.5">{s.desc}</p>
                        </div>
                      </div>
                    );
                  })}
                </div>
              )}
            </div>

            {/* Детали заказа */}
            <div className="pt-4 border-t border-[#F2ECE8] space-y-2 text-xs">
              <div className="flex justify-between text-[#78716C]">
                <span>Получатель:</span>
                <span className="font-semibold text-[#1C1917]">{order.customerName}</span>
              </div>
              <div className="flex justify-between text-[#78716C]">
                <span>Телефон:</span>
                <span className="font-mono text-[#1C1917]">{order.customerPhone}</span>
              </div>
              <div className="flex justify-between text-[#78716C]">
                <span>Адрес:</span>
                <span className="text-[#1C1917]">{order.city}, {order.address}</span>
              </div>
            </div>

            <div className="pt-2 text-center">
              <Link
                href={`/checkout/${order.orderNumber}/${order.secureHash || ''}`}
                className="inline-block py-2.5 px-6 rounded-xl bg-[#1C1917] text-white text-xs font-semibold hover:bg-black transition"
              >
                Открыть электронный чек
              </Link>
            </div>
          </div>
        )}
      </main>

      <Footer />
    </div>
  );
}
