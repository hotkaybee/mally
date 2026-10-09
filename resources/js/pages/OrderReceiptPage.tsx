import React, { useState, useEffect, useCallback } from 'react';
import { useParams } from 'react-router-dom';
import Link from '../components/Link';
import Header from '../components/Header';
import Footer from '../components/Footer';
import { useLanguage } from '../context/LanguageContext';

export interface OrderReceiptItem {
  id: string;
  quantity: number;
  price: number;
  product?: {
    id: string;
    title: string;
    sku: string;
    images?: string[] | string;
    price?: number;
  };
}

export interface OrderReceiptData {
  id: string;
  orderNumber: number;
  secureHash: string;
  customerName: string;
  customerPhone: string;
  customerEmail?: string | null;
  city: string;
  address: string;
  comment?: string | null;
  totalAmount: number;
  discountAmount?: number;
  promoCode?: string | null;
  status: string;
  paymentStatus?: string;
  paymentMethod?: string;
  createdAt: string;
  updatedAt?: string;
  items: OrderReceiptItem[];
}

export interface StoreSettingsData {
  cardNumber: string;
  cardHolder: string;
  adminTelegram: string;
  usdRate: number;
}

const STEP_DEFINITIONS = [
  { key: 'NEW', label: 'Оформлен', icon: 'fa-file-invoice' },
  { key: 'PAYMENT_RECEIVED', label: 'Оплачен', icon: 'fa-credit-card' },
  { key: 'PACKING', label: 'Сборка', icon: 'fa-box-open' },
  { key: 'SHIPPED', label: 'В пути', icon: 'fa-truck-fast' },
  { key: 'DELIVERED', label: 'Вручен', icon: 'fa-circle-check' },
];

export default function OrderReceiptPage() {
  const { orderNumber, orderHash } = useParams();
  const { t, language } = useLanguage();

  const [order, setOrder] = useState<OrderReceiptData | null>(null);
  const [settings, setSettings] = useState<StoreSettingsData>({
    cardNumber: '9860 1901 0114 9374',
    cardHolder: 'MALLY BEAUTY',
    adminTelegram: 'mallyuz',
    usdRate: 12850,
  });
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState<string | null>(null);

  const [copiedCard, setCopiedCard] = useState(false);
  const [copiedAmount, setCopiedAmount] = useState(false);
  const [copiedOrderNumber, setCopiedOrderNumber] = useState(false);
  const [isRefreshing, setIsRefreshing] = useState(false);

  const fetchStatus = useCallback(async (isManual = false) => {
    if (isManual) setIsRefreshing(true);
    try {
      const url = `/api/orders/status?orderNumber=${orderNumber || ''}&orderHash=${orderHash || ''}`;
      const res = await fetch(url);
      const data = await res.json();
      if (res.ok && data.success && data.order) {
        setOrder(data.order);
        if (data.storeSettings) setSettings(data.storeSettings);
      } else {
        setError(data.error || 'Заказ не найден');
      }
    } catch (e: any) {
      setError('Ошибка загрузки данных заказа');
    } finally {
      setLoading(false);
      if (isManual) {
        setTimeout(() => setIsRefreshing(false), 500);
      }
    }
  }, [orderNumber, orderHash]);

  useEffect(() => {
    fetchStatus();
  }, [fetchStatus]);

  const copyToClipboard = (text: string, type: 'card' | 'amount' | 'order') => {
    navigator.clipboard.writeText(text);
    if (type === 'card') {
      setCopiedCard(true);
      setTimeout(() => setCopiedCard(false), 2200);
    } else if (type === 'amount') {
      setCopiedAmount(true);
      setTimeout(() => setCopiedAmount(false), 2200);
    } else if (type === 'order') {
      setCopiedOrderNumber(true);
      setTimeout(() => setCopiedOrderNumber(false), 2200);
    }
  };

  const getStepIndex = (status: string) => {
    switch (status) {
      case 'NEW': return 0;
      case 'PAYMENT_RECEIVED': return 1;
      case 'PACKING': return 2;
      case 'SHIPPED': return 3;
      case 'DELIVERED': return 4;
      case 'CANCELLED': return -1;
      default: return 0;
    }
  };

  const getStatusTheme = (status: string) => {
    switch (status) {
      case 'NEW':
        return {
          title: 'Заказ принят — Ожидает оплаты',
          subtitle: 'Мы зарезервировали товары. Пожалуйста, совершите перевод на карту магазина ниже и отправьте чек в Telegram для быстрой отправки.',
          badgeBg: 'bg-amber-100 text-amber-800 border-amber-300',
          cardGradient: 'from-amber-500/15 via-amber-500/5 to-white',
          border: 'border-amber-200',
          icon: 'fa-credit-card',
          iconColor: 'bg-amber-500 text-white shadow-amber-500/30',
          pulse: true,
        };
      case 'PAYMENT_RECEIVED':
        return {
          title: 'Оплата успешно подтверждена!',
          subtitle: 'Администратор проверил оплату и подтвердил поступление средств. Ваш заказ готовится к комплектации на складе.',
          badgeBg: 'bg-emerald-100 text-emerald-800 border-emerald-300',
          cardGradient: 'from-emerald-500/15 via-emerald-500/5 to-white',
          border: 'border-emerald-200',
          icon: 'fa-circle-check',
          iconColor: 'bg-emerald-500 text-white shadow-emerald-500/30',
          pulse: false,
        };
      case 'PACKING':
        return {
          title: 'Заказ бережно упаковывается',
          subtitle: 'Складской отдел комплектует вашу оригинальную косметику в фирменную защитную коробку Mally с наполнителем.',
          badgeBg: 'bg-orange-100 text-orange-800 border-orange-300',
          cardGradient: 'from-orange-500/15 via-orange-500/5 to-white',
          border: 'border-orange-200',
          icon: 'fa-box-open',
          iconColor: 'bg-orange-500 text-white shadow-orange-500/30',
          pulse: true,
        };
      case 'SHIPPED':
        return {
          title: 'Заказ передан курьеру и уже в пути!',
          subtitle: `Курьерская доставка направляется по вашему адресу. Перед доставкой курьер свяжется с вами по указанному телефону.`,
          badgeBg: 'bg-indigo-100 text-indigo-800 border-indigo-300',
          cardGradient: 'from-indigo-500/15 via-indigo-500/5 to-white',
          border: 'border-indigo-200',
          icon: 'fa-truck-fast',
          iconColor: 'bg-indigo-600 text-white shadow-indigo-500/30',
          pulse: true,
        };
      case 'DELIVERED':
        return {
          title: 'Заказ успешно доставлен!',
          subtitle: 'Благодарим вас за выбор Mally! Надеемся, оригинальная косметика подарит вам истинное удовольствие.',
          badgeBg: 'bg-emerald-100 text-emerald-800 border-emerald-300',
          cardGradient: 'from-emerald-600/15 via-amber-500/5 to-white',
          border: 'border-emerald-300',
          icon: 'fa-gift',
          iconColor: 'bg-emerald-600 text-white shadow-emerald-500/30',
          pulse: false,
        };
      case 'CANCELLED':
        return {
          title: 'Заказ отменён',
          subtitle: 'Этот заказ был отменён. Если у вас возникли вопросы, напишите менеджеру в Telegram.',
          badgeBg: 'bg-rose-100 text-rose-800 border-rose-300',
          cardGradient: 'from-rose-500/15 via-rose-500/5 to-white',
          border: 'border-rose-200',
          icon: 'fa-ban',
          iconColor: 'bg-rose-500 text-white shadow-rose-500/30',
          pulse: false,
        };
      default:
        return {
          title: 'Статус заказа обновляется',
          subtitle: 'Заказ обрабатывается системой.',
          badgeBg: 'bg-stone-100 text-stone-800 border-stone-300',
          cardGradient: 'from-stone-500/10 to-white',
          border: 'border-stone-200',
          icon: 'fa-clock',
          iconColor: 'bg-stone-600 text-white',
          pulse: false,
        };
    }
  };

  if (loading) {
    return (
      <div className="min-h-screen bg-[#FCECEC] flex flex-col font-sans">
        <Header />
        <main className="flex-1 max-w-4xl mx-auto px-4 py-20 w-full flex flex-col items-center justify-center">
          <div className="w-12 h-12 rounded-full border-3 border-[#1C1917] border-t-transparent animate-spin mb-4" />
          <p className="text-sm font-medium text-[#78716C]">Загрузка информации о чеке заказа...</p>
        </main>
        <Footer />
      </div>
    );
  }

  if (error || !order) {
    return (
      <div className="min-h-screen bg-[#FCECEC] flex flex-col font-sans">
        <Header />
        <main className="flex-1 max-w-xl mx-auto px-4 py-20 w-full text-center space-y-4">
          <div className="w-16 h-16 rounded-2xl bg-white border border-[#EAE3DE] flex items-center justify-center text-rose-500 text-2xl mx-auto shadow-sm">
            <i className="fa-solid fa-triangle-exclamation" />
          </div>
          <h1 className="font-serif text-2xl font-bold text-[#1C1917]">Заказ не найден</h1>
          <p className="text-xs text-[#78716C] leading-relaxed">
            {error || 'Возможно, указан неверный номер заказа или ссылка устарела.'}
          </p>
          <div className="pt-2">
            <Link
              href="/"
              className="inline-flex items-center gap-2 px-6 py-3 bg-[#1C1917] text-white text-xs font-bold uppercase tracking-wider rounded-xl hover:bg-black transition"
            >
              Вернуться на главную
            </Link>
          </div>
        </main>
        <Footer />
      </div>
    );
  }

  const currentStepIdx = getStepIndex(order.status);
  const statusTheme = getStatusTheme(order.status);
  const isPaidOrHigher = ['PAYMENT_RECEIVED', 'PACKING', 'SHIPPED', 'DELIVERED'].includes(order.status) || order.paymentStatus === 'PAID';

  const formattedDate = new Date(order.createdAt).toLocaleDateString(language === 'en' ? 'en-US' : (language === 'uz' ? 'uz-UZ' : 'ru-RU'), {
    day: 'numeric',
    month: 'long',
    year: 'numeric',
  });
  const formattedTime = new Date(order.createdAt).toLocaleTimeString(language === 'en' ? 'en-US' : (language === 'uz' ? 'uz-UZ' : 'ru-RU'), {
    hour: '2-digit',
    minute: '2-digit',
  });

  const actualRate = (settings.usdRate && settings.usdRate > 1000) ? settings.usdRate : 12850;
  // Если сумма <= 1000 — значит сохранена в USD ($), иначе в UZS (сум)
  const isStoredInUsd = order.totalAmount <= 1000;
  const amountInUsd = isStoredInUsd ? order.totalAmount : (order.totalAmount / actualRate);
  const amountInUzs = isStoredInUsd ? Math.round(order.totalAmount * actualRate) : order.totalAmount;

  const discountInUzs = (order.discountAmount && isStoredInUsd)
    ? Math.round(order.discountAmount * actualRate)
    : (order.discountAmount || 0);

  const usdEquiv = amountInUsd.toFixed(2);
  const uzsEquiv = amountInUzs.toLocaleString('ru-RU');

  const tgMessage = encodeURIComponent(
    `Здравствуйте! Я по поводу заказа №${order.orderNumber} на сумму ${uzsEquiv} сум ($${usdEquiv} USD). ${
      !isPaidOrHigher ? 'Прикрепляю чек об оплате.' : 'Хочу уточнить статус.'
    }`
  );
  const tgLink = `https://t.me/${(settings.adminTelegram || 'mallyuz').replace('@', '')}?text=${tgMessage}`;

  return (
    <div className="min-h-screen bg-[#FCECEC] text-[#1C1917] flex flex-col font-sans print:bg-white print:p-0">
      <div className="print:hidden">
        <Header />
      </div>

      <main className="max-w-4xl mx-auto px-4 py-8 sm:py-12 w-full flex-1 space-y-6">
        {/* Верхняя навигация и кнопка Обновить статус */}
        <div className="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-3 print:hidden">
          <div className="flex items-center gap-2 text-xs text-[#78716C]">
            <Link href="/" className="hover:text-[#1C1917] transition">{t('nav.home', 'Главная')}</Link>
            <span>/</span>
            <Link href="/profile" className="hover:text-[#1C1917] transition">{t('profile.title', 'Личный кабинет')}</Link>
            <span>/</span>
            <span className="font-semibold text-[#1C1917]">Чек заказа №{order.orderNumber}</span>
          </div>

          <div className="flex items-center gap-2 self-end sm:self-auto">
            <button
              type="button"
              onClick={() => fetchStatus(true)}
              disabled={isRefreshing}
              className="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-xl bg-white border border-[#EAE3DE] hover:border-[#1C1917] text-xs font-medium text-[#57534E] hover:text-[#1C1917] transition shadow-2xs cursor-pointer active:scale-95"
              title="Проверить актуальный статус заказа на сервере"
            >
              <i className={`fa-solid fa-arrows-rotate text-[11px] ${isRefreshing ? 'animate-spin text-amber-600' : 'text-stone-400'}`} />
              <span>{isRefreshing ? 'Проверка...' : 'Обновить статус'}</span>
            </button>
          </div>
        </div>

        {/* ГЛАВНАЯ КАРТОЧКА СТАТУСА ЗАКАЗА */}
        <div className={`bg-gradient-to-b ${statusTheme.cardGradient} rounded-3xl p-6 sm:p-8 border-2 ${statusTheme.border} shadow-lg relative overflow-hidden transition-all duration-300`}>
          <div className="absolute top-0 right-0 translate-x-10 -translate-y-10 w-48 h-48 rounded-full bg-white/40 blur-2xl pointer-events-none" />

          <div className="relative flex flex-col items-center text-center space-y-4">
            {/* Иконка статуса */}
            <div className={`w-16 h-16 sm:w-20 sm:h-20 rounded-3xl ${statusTheme.iconColor} flex items-center justify-center text-2xl sm:text-3xl shadow-xl transition-transform duration-300 hover:scale-105`}>
              <i className={`fa-solid ${statusTheme.icon} ${statusTheme.pulse ? 'animate-pulse' : ''}`} />
            </div>

            {/* Номер заказа */}
            <div className="space-y-1">
              <div className="inline-flex items-center gap-2 px-3.5 py-1 bg-white/90 backdrop-blur-sm rounded-full border border-stone-200/80 shadow-2xs">
                <span className="text-[10px] uppercase font-bold tracking-widest text-[#8C827A]">Заказ:</span>
                <span className="font-mono text-sm font-extrabold text-[#1C1917]">№{order.orderNumber}</span>
                <button
                  type="button"
                  onClick={() => copyToClipboard(String(order.orderNumber), 'order')}
                  className="text-stone-400 hover:text-black transition text-xs p-0.5 ml-0.5 cursor-pointer"
                  title="Скопировать номер заказа"
                >
                  <i className={`fa-solid ${copiedOrderNumber ? 'fa-check text-emerald-600' : 'fa-copy'}`} />
                </button>
              </div>

              <h1 className="font-serif text-2xl sm:text-3xl font-bold tracking-tight text-[#1C1917] pt-1">
                {statusTheme.title}
              </h1>

              <p className="text-xs sm:text-sm text-[#57534E] max-w-xl mx-auto leading-relaxed pt-1">
                {statusTheme.subtitle}
              </p>
            </div>

            {/* Шкала выполнения заказа (Stepper) */}
            {order.status !== 'CANCELLED' && (
              <div className="w-full max-w-2xl pt-4 pb-2">
                <div className="grid grid-cols-5 gap-1 sm:gap-2 relative">
                  {STEP_DEFINITIONS.map((step, idx) => {
                    const isDone = idx <= currentStepIdx;
                    const isCurrent = idx === currentStepIdx;

                    return (
                      <div key={step.key} className="flex flex-col items-center text-center space-y-2 relative">
                        <div
                          className={`w-9 h-9 sm:w-11 sm:h-11 rounded-2xl flex items-center justify-center text-xs sm:text-sm transition-all duration-300 z-10 ${
                            isCurrent
                              ? 'bg-[#1C1917] text-white ring-4 ring-black/10 scale-110 shadow-md'
                              : isDone
                              ? 'bg-emerald-500 text-white shadow-xs'
                              : 'bg-white/80 text-stone-400 border border-stone-200'
                          }`}
                        >
                          <i className={`fa-solid ${step.icon}`} />
                        </div>

                        <span
                          className={`text-[10px] sm:text-xs font-semibold leading-tight ${
                            isCurrent
                              ? 'text-[#1C1917] font-bold'
                              : isDone
                              ? 'text-emerald-700'
                              : 'text-stone-400'
                          }`}
                        >
                          {step.label}
                        </span>
                      </div>
                    );
                  })}
                </div>
              </div>
            )}

            {/* Мета-информация */}
            <div className="pt-2 flex flex-wrap justify-center items-center gap-2 text-xs">
              <span className="px-3 py-1 bg-white/85 rounded-full text-[#78716C] border border-[#EAE3DE] flex items-center gap-1.5">
                <i className="fa-regular fa-calendar text-[11px]" />
                <span>{formattedDate}, {formattedTime}</span>
              </span>
              <span className="px-3 py-1 bg-white/85 rounded-full text-emerald-700 border border-emerald-200 font-medium flex items-center gap-1.5">
                <i className="fa-solid fa-shield-halved text-[11px]" />
                <span>Mally USA • 100% Оригинал</span>
              </span>
            </div>
          </div>
        </div>

        {/* АКЦЕНТНЫЙ БЛОК ОПЛАТЫ ИЛИ УВЕДОМЛЕНИЕ ОБ ОТМЕНЕ */}
        {order.status === 'CANCELLED' ? (
          <div className="bg-white rounded-3xl p-6 sm:p-8 border border-rose-200 shadow-sm space-y-6">
            <div className="flex flex-col sm:flex-row sm:items-center justify-between pb-4 border-b border-rose-100 gap-3">
              <div className="flex items-center gap-3.5">
                <div className="w-12 h-12 rounded-2xl bg-rose-500 text-white flex items-center justify-center text-xl flex-shrink-0 shadow-md">
                  <i className="fa-solid fa-ban" />
                </div>
                <div>
                  <span className="text-[10px] uppercase font-bold tracking-widest text-rose-500 block">
                    Уведомление о статусе
                  </span>
                  <h2 className="font-serif text-xl font-bold text-[#1C1917]">
                    Ваш заказ отменён
                  </h2>
                </div>
              </div>

              <div className="text-left sm:text-right">
                <span className="text-[11px] text-[#78716C] block">Сумма заказа:</span>
                <span className="font-mono text-xl sm:text-2xl font-extrabold text-stone-400 line-through">
                  {uzsEquiv} сум
                </span>
              </div>
            </div>

            <div className="bg-amber-50/80 rounded-2xl p-4 sm:p-5 border border-amber-200/80 flex items-start gap-3.5">
              <div className="w-8 h-8 rounded-xl bg-amber-500 text-white flex items-center justify-center text-sm flex-shrink-0 mt-0.5 shadow-xs">
                <i className="fa-solid fa-triangle-exclamation" />
              </div>
              <div className="space-y-1 text-xs text-amber-950">
                <h4 className="font-bold text-amber-900">
                  Регламент магазина и политика возвратов
                </h4>
                <p className="leading-relaxed text-amber-900/90">
                  Обратите внимание: в соответствии с правилами магазина и условиями прямых поставок из США, возврат косметики не осуществляется. Все продажи носят окончательный характер.
                </p>
              </div>
            </div>

            <div className="flex flex-col sm:flex-row items-center justify-between gap-4 pt-2">
              <p className="text-xs text-stone-500 text-center sm:text-left">
                Если у вас возникли вопросы по данному заказу:
              </p>
              <a
                href={tgLink}
                target="_blank"
                rel="noreferrer"
                className="w-full sm:w-auto px-6 py-3.5 bg-[#229ED9] hover:bg-[#1C8CC4] text-white rounded-2xl text-xs font-bold transition flex items-center justify-center gap-2 shadow-md cursor-pointer active:scale-95 whitespace-nowrap"
              >
                <i className="fa-brands fa-telegram text-base" />
                <span>Связаться с менеджером</span>
              </a>
            </div>
          </div>
        ) : (
          /* АКЦЕНТНЫЙ БЛОК ОПЛАТЫ И РЕКВИЗИТОВ КАРТЫ */
          <div className="bg-white rounded-3xl p-6 sm:p-8 border border-[#EAE3DE] shadow-sm space-y-6">
            <div className="flex flex-col sm:flex-row sm:items-center justify-between pb-4 border-b border-[#F2ECE8] gap-3">
              <div>
                <span className="text-[10px] uppercase font-bold tracking-widest text-[#8C827A] block">
                  Финансовый расчет
                </span>
                <h2 className="font-serif text-xl font-bold text-[#1C1917]">
                  {isPaidOrHigher ? 'Оплата заказа зафиксирована' : 'Реквизиты для оплаты заказа'}
                </h2>
              </div>

              <div className="text-left sm:text-right">
                <span className="text-[11px] text-[#78716C] block">Сумма к оплате:</span>
                <div className="flex items-center sm:justify-end gap-2">
                  <span className="font-mono text-2xl sm:text-3xl font-extrabold text-[#1C1917]">
                    {uzsEquiv} сум
                  </span>
                  <button
                    type="button"
                    onClick={() => copyToClipboard(String(amountInUzs), 'amount')}
                    className="p-1 text-stone-400 hover:text-black transition cursor-pointer"
                    title="Скопировать сумму"
                  >
                    <i className={`fa-solid ${copiedAmount ? 'fa-check text-emerald-600' : 'fa-copy'} text-sm`} />
                  </button>
                </div>
                <span className="text-xs text-emerald-600 font-medium font-mono">
                  ≈ ${usdEquiv} USD (по курсу ЦБ)
                </span>
              </div>
            </div>

            {!isPaidOrHigher ? (
              /* РЕЖИМ: ОЖИДАЕТСЯ ОПЛАТА */
              <div className="grid grid-cols-1 md:grid-cols-12 gap-6 items-stretch">
                {/* Карточка банка Humo / Uzcard */}
                <div className="md:col-span-7 bg-gradient-to-br from-[#1C1917] via-[#2A2624] to-[#141211] text-white rounded-3xl p-6 shadow-xl flex flex-col justify-between relative overflow-hidden min-h-[220px]">
                  <div className="absolute top-0 right-0 w-44 h-44 rounded-full bg-white/5 blur-xl pointer-events-none" />
                  <div className="absolute bottom-0 right-10 w-28 h-28 rounded-full bg-amber-500/10 blur-xl pointer-events-none" />

                  <div className="flex items-center justify-between relative z-10">
                    <div className="flex items-center gap-2">
                      <span className="font-serif text-lg font-bold tracking-tight text-white">mally</span>
                      <span className="text-[9px] uppercase tracking-widest text-[#A8A29E]">• Store USA</span>
                    </div>
                    <div className="flex items-center gap-1.5 text-xs font-bold text-amber-400">
                      <i className="fa-solid fa-credit-card" />
                      <span>Humo / Uzcard</span>
                    </div>
                  </div>

                  {/* Чип */}
                  <div className="my-3 flex items-center justify-between relative z-10">
                    <div className="w-10 h-7 rounded-md bg-gradient-to-r from-amber-300 via-yellow-200 to-amber-400 border border-amber-400/50 shadow-inner flex items-center justify-center">
                      <span className="w-5 h-4 border-t border-b border-amber-600/40 rounded-xs" />
                    </div>
                    <span className="text-[10px] text-stone-400 uppercase tracking-widest font-mono">Прямой перевод</span>
                  </div>

                  {/* Номер карты с 1-клик копированием */}
                  <div className="space-y-1 relative z-10">
                    <span className="text-[10px] text-[#A8A29E] uppercase tracking-wider block">Номер карты получателя:</span>
                    <div className="flex items-center justify-between gap-3 bg-white/10 backdrop-blur-md px-3.5 py-2 rounded-xl border border-white/15">
                      <span className="font-mono text-base sm:text-lg font-bold tracking-wider text-white">
                        {settings.cardNumber}
                      </span>
                      <button
                        type="button"
                        onClick={() => copyToClipboard(settings.cardNumber.replace(/\s+/g, ''), 'card')}
                        className={`px-3 py-1 rounded-lg text-xs font-semibold transition flex items-center gap-1.5 cursor-pointer active:scale-95 ${
                          copiedCard
                            ? 'bg-emerald-500 text-white'
                            : 'bg-white hover:bg-stone-100 text-black shadow-xs'
                        }`}
                      >
                        <i className={`fa-solid ${copiedCard ? 'fa-check' : 'fa-copy'} text-[10px]`} />
                        <span>{copiedCard ? 'Скопировано!' : 'Скопировать'}</span>
                      </button>
                    </div>
                  </div>

                  {/* Держатель карты */}
                  <div className="mt-3 pt-2 border-t border-white/10 flex justify-between items-center text-xs relative z-10">
                    <div>
                      <span className="text-[9px] uppercase text-[#A8A29E] block">Получатель:</span>
                      <span className="font-semibold text-stone-200">{settings.cardHolder}</span>
                    </div>
                    <div className="text-right">
                      <span className="text-[9px] uppercase text-[#A8A29E] block">Назначение:</span>
                      <span className="font-mono text-amber-300 font-bold">Заказ №{order.orderNumber}</span>
                    </div>
                  </div>
                </div>

                {/* Инструкция и прямая кнопка отправки чека в Telegram (БЕЗ загрузки на сайте) */}
                <div className="md:col-span-5 flex flex-col justify-between bg-[#FAF7F5] rounded-3xl p-6 border border-[#EAE3DE] space-y-4">
                  <div className="space-y-2.5">
                    <h3 className="font-serif text-base font-bold text-[#1C1917] flex items-center gap-2">
                      <i className="fa-solid fa-circle-info text-amber-600 text-sm" />
                      <span>Как оплатить заказ:</span>
                    </h3>
                    <ol className="text-xs text-[#57534E] space-y-2 list-decimal list-inside leading-relaxed">
                      <li>Скопируйте номер карты слева.</li>
                      <li>Переведите сумму <b>{uzsEquiv} сум</b> (≈ ${usdEquiv} USD) через банковское приложение (Payme, Click, Uzum).</li>
                      <li>Нажмите кнопку ниже и отправьте чек или скриншот менеджеру в Telegram.</li>
                    </ol>
                  </div>

                  <div className="space-y-2 pt-2">
                    <a
                      href={tgLink}
                      target="_blank"
                      rel="noreferrer"
                      className="w-full py-3.5 px-4 bg-[#229ED9] hover:bg-[#1C8CC4] text-white rounded-2xl text-xs font-bold transition flex items-center justify-center gap-2 shadow-md cursor-pointer active:scale-95 text-center"
                    >
                      <i className="fa-brands fa-telegram text-base" />
                      <span>Отправить чек администратору</span>
                    </a>

                    <p className="text-[11px] text-stone-400 text-center">
                      Менеджер оперативно подтвердит оплату и передаст заказ на сборку
                    </p>
                  </div>
                </div>
              </div>
            ) : (
              /* РЕЖИМ: ОПЛАТА ПОЛУЧЕНА */
              <div className="bg-emerald-50/70 border border-emerald-200 rounded-3xl p-6 flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4">
                <div className="flex items-center gap-3.5">
                  <div className="w-12 h-12 rounded-2xl bg-emerald-500 text-white flex items-center justify-center text-xl flex-shrink-0 shadow-md">
                    <i className="fa-solid fa-circle-check" />
                  </div>
                  <div>
                    <h4 className="font-serif font-bold text-base text-emerald-950">
                      Оплата успешно подтверждена магазином
                    </h4>
                    <p className="text-xs text-emerald-700 mt-0.5">
                      Сумма {uzsEquiv} сум (≈ ${usdEquiv} USD) зачислена. Повторная оплата не требуется.
                    </p>
                  </div>
                </div>

                <a
                  href={tgLink}
                  target="_blank"
                  rel="noreferrer"
                  className="px-4 py-2.5 bg-emerald-600 hover:bg-emerald-700 text-white rounded-xl text-xs font-semibold transition flex items-center gap-2 whitespace-nowrap shadow-xs cursor-pointer"
                >
                  <i className="fa-brands fa-telegram" />
                  <span>Написать менеджеру</span>
                </a>
              </div>
            )}
          </div>
        )}

        {/* СОСТАВ ЗАКАЗА И ДЕТАЛИ ДОСТАВКИ */}
        <div className="grid grid-cols-1 md:grid-cols-3 gap-6 items-start">
          {/* Состав заказа */}
          <div className="md:col-span-2 bg-white rounded-3xl p-6 border border-[#EAE3DE] shadow-sm">
            <div className="flex items-center justify-between pb-3 border-b border-[#F2ECE8]">
              <h3 className="font-serif text-base font-bold text-[#1C1917] flex items-center gap-2">
                <i className="fa-solid fa-bag-shopping text-sm text-[#8C827A]" />
                <span>Товары в заказе ({order.items.length})</span>
              </h3>
              <span className="text-xs text-stone-400">
                {order.items.reduce((acc, it) => acc + (it.quantity || 1), 0)} шт.
              </span>
            </div>

            <div className="divide-y divide-[#F2ECE8]">
              {order.items.map((item) => {
                let img = 'https://images.unsplash.com/photo-1522335789203-aabd1fc54bc9?auto=format&fit=crop&w=400&q=80';
                if (Array.isArray(item.product?.images) && item.product.images.length > 0) {
                  img = item.product.images[0];
                } else if (typeof item.product?.images === 'string') {
                  try {
                    const parsed = JSON.parse(item.product.images);
                    if (Array.isArray(parsed) && parsed.length > 0) img = parsed[0];
                  } catch {}
                }

                return (
                  <div key={item.id} className="py-3.5 first:pt-0 flex items-center gap-4">
                    <div className="w-16 h-16 rounded-2xl bg-[#FAF7F5] border border-[#EAE3DE] overflow-hidden flex-shrink-0">
                      <img
                        src={img}
                        alt={item.product?.title || 'Товар'}
                        className="w-full h-full object-cover"
                        onError={(e) => {
                          (e.target as HTMLImageElement).src = 'https://images.unsplash.com/photo-1522335789203-aabd1fc54bc9?auto=format&fit=crop&w=400&q=80';
                        }}
                      />
                    </div>
                    <div className="flex-1 min-w-0">
                      <h4 className="text-xs sm:text-sm font-semibold text-[#1C1917] truncate">
                        {item.product?.title || 'Товар Mally'}
                      </h4>
                      <div className="text-[11px] text-[#8C827A] flex items-center gap-2 mt-0.5">
                        {item.product?.sku && <span>Арт: <code className="font-mono">{item.product.sku}</code></span>}
                        <span>·</span>
                        <span>{item.quantity} шт.</span>
                      </div>
                    </div>
                    <div className="text-right font-mono font-bold text-xs sm:text-sm text-[#1C1917]">
                      {(() => {
                        const linePrice = item.price;
                        const isLineUsd = linePrice <= 1000;
                        const lineUsd = isLineUsd ? (linePrice * item.quantity) : ((linePrice * item.quantity) / actualRate);
                        const lineUzs = isLineUsd ? Math.round(linePrice * item.quantity * actualRate) : (linePrice * item.quantity);
                        return (
                          <div>
                            <div>{lineUzs.toLocaleString('ru-RU')} сум</div>
                            <div className="text-[10px] text-stone-400 font-normal font-sans">≈ ${lineUsd.toFixed(2)}</div>
                          </div>
                        );
                      })()}
                    </div>
                  </div>
                );
              })}
            </div>

            {/* Расчет суммы и промокода */}
            <div className="pt-3.5 border-t border-[#F2ECE8] space-y-2 text-xs mt-3">
              {order.promoCode && (
                <div className="flex justify-between items-center text-pink-600 font-medium">
                  <span className="flex items-center gap-1.5">
                    <i className="fa-solid fa-ticket text-[11px]" />
                    <span>Промокод ({order.promoCode}):</span>
                  </span>
                  <span>-{discountInUzs.toLocaleString('ru-RU')} сум</span>
                </div>
              )}

              <div className="flex justify-between items-baseline pt-2 border-t border-[#F2ECE8]">
                <span className="text-sm font-bold text-[#78716C] font-serif">Итого к оплате:</span>
                <div className="text-right">
                  <div className="text-xl sm:text-2xl font-black text-[#1C1917] font-mono leading-none">
                    {uzsEquiv} сум
                  </div>
                  <div className="text-xs text-stone-400 font-sans mt-1">
                    ≈ ${usdEquiv} USD
                  </div>
                </div>
              </div>
            </div>
          </div>

          {/* Доставка и Получатель */}
          <div className="space-y-6">
            <div className="bg-white rounded-3xl p-6 border border-[#EAE3DE] shadow-sm space-y-3">
              <h3 className="font-serif text-base font-bold text-[#1C1917] flex items-center gap-2 pb-2 border-b border-[#F2ECE8]">
                <i className="fa-solid fa-location-dot text-sm text-[#8C827A]" />
                <span>Данные доставки</span>
              </h3>
              <div className="text-xs text-[#57534E] space-y-2 leading-relaxed">
                <div>
                  <span className="text-[10px] uppercase font-bold text-[#A8A29E] block">Получатель:</span>
                  <span className="font-semibold text-[#1C1917]">{order.customerName}</span>
                </div>
                <div>
                  <span className="text-[10px] uppercase font-bold text-[#A8A29E] block">Телефон:</span>
                  <span className="font-mono font-semibold text-[#1C1917]">{order.customerPhone}</span>
                </div>
                <div>
                  <span className="text-[10px] uppercase font-bold text-[#A8A29E] block">Город и адрес:</span>
                  <span className="font-medium text-[#1C1917]">{order.city}, {order.address}</span>
                </div>
                {order.comment && (
                  <div>
                    <span className="text-[10px] uppercase font-bold text-[#A8A29E] block">Примечание:</span>
                    <span className="text-stone-600 italic">{order.comment}</span>
                  </div>
                )}
              </div>
            </div>

            <div className="bg-[#FAF7F5] rounded-3xl p-5 border border-[#EAE3DE] space-y-3">
              <h4 className="font-serif font-bold text-xs uppercase tracking-wider text-[#1C1917]">
                Служба заботы Mally
              </h4>
              <p className="text-xs text-[#78716C] leading-relaxed">
                По любым вопросам комплектации, времени доставки или состава косметики напишите менеджеру в Telegram.
              </p>
              <a
                href={tgLink}
                target="_blank"
                rel="noreferrer"
                className="w-full py-2.5 px-3 rounded-xl bg-white hover:bg-stone-50 border border-[#EAE3DE] text-[#1C1917] text-xs font-semibold flex items-center justify-center gap-2 transition shadow-2xs"
              >
                <i className="fa-brands fa-telegram text-[#0088cc]" />
                <span>Написать в @mallyuz</span>
              </a>
            </div>
          </div>
        </div>
      </main>

      <Footer />
    </div>
  );
}
