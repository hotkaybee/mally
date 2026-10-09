import React, { useState, useEffect } from 'react';
import Link from '../components/Link';
import Header from '../components/Header';
import Footer from '../components/Footer';
import { useCart } from '../context/CartContext';
import { useToast } from '../context/ToastContext';
import { useLanguage } from '../context/LanguageContext';
import CopyableBadge from '../components/ui/CopyableBadge';

export default function ProfilePage() {
  const { user, isAuthenticated, logout, login, openAuthModal, formatPrice } = useCart();
  const { showToast } = useToast();
  const { t, language } = useLanguage();

  const [activeTab, setActiveTab] = useState<'orders' | 'details'>('orders');
  const [loadingOrders, setLoadingOrders] = useState(false);
  const [orders, setOrders] = useState<any[]>([]);

  const [firstName, setFirstName] = useState('');
  const [lastName, setLastName] = useState('');
  const [phone, setPhone] = useState('');
  const [city, setCity] = useState('');
  const [address, setAddress] = useState('');
  const [saving, setSaving] = useState(false);

  useEffect(() => {
    if (user) {
      setFirstName(user.firstName || '');
      setLastName(user.lastName || '');
      setPhone(user.phone || '');
      setCity(user.city || '');
      setAddress(user.address || '');
      fetchUserProfile();
    }
  }, [user?.id, user?.phone]);

  const fetchUserProfile = async () => {
    if (!user) return;
    setLoadingOrders(true);
    try {
      const res = await fetch(`/api/user/profile?userId=${user.id || ''}`);
      const data = await res.json();
      if (data.success) {
        if (data.user) {
          setFirstName(data.user.firstName || '');
          setLastName(data.user.lastName || '');
          setPhone(data.user.phone || '');
        }
        if (data.orders) {
          setOrders(data.orders);
        }
      }
    } catch (e) {
      console.error('Error fetching profile:', e);
    } finally {
      setLoadingOrders(false);
    }
  };

  const handleSaveDetails = async (e: React.FormEvent) => {
    e.preventDefault();
    if (!user) return;

    if (!firstName.trim()) {
      showToast(t('profile.name_required', 'Имя обязательно для заполнения'), 'warning');
      return;
    }

    setSaving(true);
    try {
      const res = await fetch('/api/user/profile', {
        method: 'PUT',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({
          userId: user.id,
          firstName: firstName.trim(),
          lastName: lastName.trim(),
          phone: phone.trim(),
          city: city.trim(),
          address: address.trim(),
        }),
      });

      const data = await res.json();
      if (!res.ok) throw new Error(data.error || 'Ошибка сохранения');

      login(data.user);
      showToast(t('profile.saved_success', 'Данные профиля успешно сохранены'), 'success');
    } catch (err: any) {
      showToast(err.message, 'error');
    } finally {
      setSaving(false);
    }
  };

  const getStatusBadge = (status: string) => {
    const map: Record<string, { label: string; bg: string; color: string; border: string }> = {
      NEW: { label: t('status.new', 'Новый заказ'), bg: '#EFF6FF', color: '#1D4ED8', border: '#BFDBFE' },
      PAYMENT_RECEIVED: { label: t('status.payment_received', 'Оплачен'), bg: '#ECFDF5', color: '#047857', border: '#A7F3D0' },
      PACKING: { label: t('status.packing', 'Комплектуется'), bg: '#FFFBEB', color: '#B45309', border: '#FDE68A' },
      SHIPPED: { label: t('status.shipped', 'В доставке'), bg: '#F5F3FF', color: '#6D28D9', border: '#DDD6FE' },
      DELIVERED: { label: t('status.delivered', 'Вручен'), bg: '#F0FDF4', color: '#15803D', border: '#BBF7D0' },
      CANCELLED: { label: t('status.cancelled', 'Отменён'), bg: '#FEF2F2', color: '#B91C1C', border: '#FECACA' },
    };
    const s = map[status] || { label: status, bg: '#F3F4F6', color: '#374151', border: '#E5E7EB' };
    return (
      <span
        style={{
          display: 'inline-flex',
          alignItems: 'center',
          gap: 6,
          padding: '4px 10px',
          borderRadius: 999,
          fontSize: 11,
          fontWeight: 700,
          background: s.bg,
          color: s.color,
          border: `1px solid ${s.border}`,
        }}
      >
        <span style={{ width: 6, height: 6, borderRadius: '50%', background: s.color }} />
        {s.label}
      </span>
    );
  };

  return (
    <div className="min-h-screen bg-[#FCECEC] flex flex-col font-sans">
      <Header />

      <main className="flex-1 max-w-5xl w-full mx-auto px-4 py-8">
        <div className="flex items-center gap-2 text-xs text-[#78716C] mb-6">
          <Link href="/" className="hover:text-[#1C1917] transition">
            {t('nav.home', 'Главная')}
          </Link>
          <span>/</span>
          <span className="text-[#1C1917] font-medium">{t('profile.title', 'Личный кабинет')}</span>
        </div>

        {!isAuthenticated || !user ? (
          <div className="bg-white rounded-3xl p-10 border border-[#EAE3DE] shadow-xl text-center max-w-md mx-auto my-12 space-y-4">
            <div className="w-16 h-16 rounded-2xl bg-[#FAF7F5] border border-[#EAE3DE] flex items-center justify-center text-2xl text-[#1C1917] mx-auto">
              <i className="fa-regular fa-user" />
            </div>
            <h1 className="font-serif text-2xl font-bold text-[#1C1917]">
              {t('profile.title', 'Вход в личный кабинет')}
            </h1>
            <p className="text-xs text-[#78716C] leading-relaxed">
              {t('profile.login_desc', 'Авторизуйтесь через телефон для просмотра истории заказов, статуса доставки и привязки к устройству.')}
            </p>
            <button
              type="button"
              onClick={() => openAuthModal(t('profile.need_login', 'Войдите для просмотра профиля и заказов'))}
              className="w-full py-3.5 px-6 rounded-2xl bg-[#1C1917] hover:bg-[#2C2724] text-[#FAF7F5] text-xs font-bold uppercase tracking-wider transition shadow-lg flex items-center justify-center gap-2 cursor-pointer"
            >
              <span>{t('profile.login_btn', 'Войти или зарегистрироваться')}</span>
              <i className="fa-solid fa-arrow-right text-[10px]" />
            </button>
          </div>
        ) : (
          <div className="space-y-6">
            {/* Top User Card */}
            <div className="bg-white rounded-3xl p-6 sm:p-8 border border-[#EAE3DE] shadow-sm flex flex-col sm:flex-row items-start sm:items-center justify-between gap-6">
              <div className="flex items-center gap-4">
                <div className="w-16 h-16 rounded-2xl bg-[#1C1917] text-white flex items-center justify-center font-serif text-2xl font-bold flex-shrink-0 shadow-md">
                  {firstName ? firstName[0].toUpperCase() : (user.fullName ? user.fullName[0].toUpperCase() : 'M')}
                </div>
                <div>
                  <div className="flex items-center gap-2 flex-wrap">
                    <h1 className="font-serif text-2xl font-bold text-[#1C1917] leading-tight">
                      {firstName ? `${firstName} ${lastName || ''}`.trim() : (user.fullName || 'Покупатель Mally')}
                    </h1>
                  </div>
                  <div className="flex items-center gap-2 mt-1.5 flex-wrap">
                    <span className="text-xs text-[#78716C] font-mono">{user.phone}</span>
                    <span className="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full bg-emerald-50 text-emerald-700 border border-emerald-200 text-[10px] font-semibold">
                      <i className="fa-solid fa-shield-halved text-[9px]" />
                      <span>{t('profile.device_bound', 'Устройство привязано')}</span>
                    </span>
                  </div>
                </div>
              </div>

              <div className="flex items-center gap-3 w-full sm:w-auto">
                <Link
                  href="/catalog"
                  className="flex-1 sm:flex-initial py-2.5 px-4 rounded-xl border border-[#EAE3DE] hover:border-[#1C1917] text-xs font-semibold text-[#1C1917] transition text-center"
                >
                  {t('cart.to_catalog', 'В каталог')}
                </Link>
                <button
                  type="button"
                  onClick={() => {
                    logout();
                    showToast(t('auth.logout_msg', 'Вы вышли из аккаунта'), 'info');
                  }}
                  className="py-2.5 px-4 rounded-xl bg-rose-50 hover:bg-rose-100 text-rose-700 text-xs font-semibold transition cursor-pointer"
                >
                  {t('profile.logout', 'Выйти')}
                </button>
              </div>
            </div>

            {/* Profile Tabs */}
            <div className="flex border-b border-[#EAE3DE] gap-8 text-sm font-semibold">
              <button
                type="button"
                onClick={() => setActiveTab('orders')}
                className={`pb-3 relative transition cursor-pointer ${
                  activeTab === 'orders'
                    ? 'text-[#1C1917]'
                    : 'text-[#A8A29E] hover:text-[#1C1917]'
                }`}
              >
                {t('profile.orders_tab', 'Мои заказы')} ({orders.length})
                {activeTab === 'orders' && (
                  <span className="absolute bottom-0 left-0 right-0 h-0.5 bg-[#1C1917]" />
                )}
              </button>
              <button
                type="button"
                onClick={() => setActiveTab('details')}
                className={`pb-3 relative transition cursor-pointer ${
                  activeTab === 'details'
                    ? 'text-[#1C1917]'
                    : 'text-[#A8A29E] hover:text-[#1C1917]'
                }`}
              >
                {t('profile.details_tab', 'Личные данные')}
                {activeTab === 'details' && (
                  <span className="absolute bottom-0 left-0 right-0 h-0.5 bg-[#1C1917]" />
                )}
              </button>
            </div>

            {/* TAB 1: Orders History */}
            {activeTab === 'orders' && (
              <div className="space-y-4">
                {loadingOrders ? (
                  <div className="bg-white rounded-3xl p-12 text-center text-[#78716C] border border-[#EAE3DE]">
                    <i className="fa-solid fa-spinner fa-spin text-2xl text-[#1C1917] mb-2" />
                    <p className="text-xs">Загрузка заказов...</p>
                  </div>
                ) : orders.length === 0 ? (
                  <div className="bg-white rounded-3xl p-12 text-center border border-[#EAE3DE] shadow-sm space-y-3">
                    <div className="w-14 h-14 mx-auto rounded-full bg-[#FAF7F5] flex items-center justify-center text-xl text-[#A8A29E]">
                      <i className="fa-solid fa-bag-shopping" />
                    </div>
                    <h3 className="font-serif text-lg font-bold text-[#1C1917]">
                      {t('profile.no_orders', 'У вас пока нет заказов')}
                    </h3>
                    <p className="text-xs text-[#78716C] max-w-sm mx-auto">
                      {t('cart.empty_desc', 'Перейдите в каталог, выберите оригинальную косметику из США и оформите первый заказ.')}
                    </p>
                    <Link
                      href="/catalog"
                      className="inline-flex items-center gap-2 py-3 px-6 rounded-xl bg-[#1C1917] hover:bg-[#2C2724] text-white text-xs font-bold uppercase tracking-wider transition"
                    >
                      <span>{t('cart.to_catalog', 'Перейти в каталог')}</span>
                      <i className="fa-solid fa-arrow-right text-[10px]" />
                    </Link>
                  </div>
                ) : (
                  orders.map((order) => {
                    const receiptUrl = `/checkout/${order.orderNumber}/${order.secureHash || ''}`;

                    return (
                      <div
                        key={order.id}
                        className="bg-white rounded-3xl p-6 border border-[#EAE3DE] shadow-sm space-y-4 hover:border-[#D5CCC7] transition"
                      >
                        <div className="flex flex-col sm:flex-row sm:items-center justify-between pb-4 border-b border-[#FAF7F5] gap-3">
                          <div>
                            <div className="flex items-center gap-2.5 flex-wrap">
                              <span className="font-mono text-base font-bold text-[#1C1917]">
                                {t('profile.order_num', 'Заказ №')}{order.orderNumber}
                              </span>
                              <CopyableBadge text={order.orderNumber} prefix="№" size="sm" />
                              {getStatusBadge(order.status)}

                              <Link
                                href={receiptUrl}
                                className="inline-flex items-center gap-1.5 px-3 py-1 rounded-xl bg-[#1C1917] hover:bg-[#2C2724] text-white text-[11px] font-semibold transition ml-1"
                              >
                                <i className="fa-solid fa-receipt text-amber-300 text-[10px]" />
                                <span>{t('profile.receipt_and_status', 'Чек и статус')}</span>
                              </Link>
                            </div>
                            <span className="text-[11px] text-[#A8A29E] mt-1 block">
                              {new Date(order.createdAt).toLocaleDateString(language === 'en' ? 'en-US' : (language === 'uz' ? 'uz-UZ' : 'ru-RU'))}
                            </span>
                          </div>

                          <div className="text-left sm:text-right">
                            <div className="font-bold text-base text-[#1C1917]">
                              {formatPrice(order.totalAmount)}
                            </div>
                          </div>
                        </div>

                        {/* Order Items */}
                        <div className="divide-y divide-[#FAF7F5] bg-[#FAF7F5]/50 rounded-2xl p-3 border border-[#F2ECE8]">
                          {order.items?.map((item: any) => (
                            <div key={item.id} className="py-2.5 first:pt-1 last:pb-1 flex items-center justify-between gap-4">
                              <div className="flex items-center gap-3 flex-1 min-w-0">
                                <div className="w-12 h-12 rounded-xl bg-white overflow-hidden flex-shrink-0 border border-[#EAE3DE]">
                                  <img
                                    src={item.product?.images?.[0] || 'https://findy.uz/cdn/shop/files/sample.jpg'}
                                    alt={item.product?.title || 'Товар'}
                                    className="w-full h-full object-cover"
                                  />
                                </div>
                                <div className="min-w-0 flex-1 truncate">
                                  <h4 className="text-xs font-bold text-[#1C1917] truncate">
                                    {item.product?.title || 'Оригинальная косметика Mally'}
                                  </h4>
                                  <div className="text-[11px] text-[#8C827A] font-mono">
                                    {item.quantity} {t('cart.qty', 'шт.')} × {formatPrice(item.price)}
                                  </div>
                                </div>
                              </div>
                              <div className="font-mono font-bold text-xs text-[#1C1917]">
                                {formatPrice(item.price * item.quantity)}
                              </div>
                            </div>
                          ))}
                        </div>
                      </div>
                    );
                  })
                )}
              </div>
            )}

            {/* TAB 2: Details */}
            {activeTab === 'details' && (
              <div className="bg-white rounded-3xl p-6 sm:p-8 border border-[#EAE3DE] shadow-sm max-w-xl">
                <form onSubmit={handleSaveDetails} className="space-y-4">
                  <div className="grid grid-cols-2 gap-3">
                    <div>
                      <label className="block text-[11px] font-bold text-[#8C827A] mb-1">{t('profile.first_name', 'Имя')}</label>
                      <input
                        type="text"
                        required
                        value={firstName}
                        onChange={(e) => setFirstName(e.target.value)}
                        className="w-full px-4 py-2.5 bg-[#FAF7F5] border border-[#E3DBD5] rounded-xl text-xs focus:outline-none focus:border-[#1C1917]"
                      />
                    </div>
                    <div>
                      <label className="block text-[11px] font-bold text-[#8C827A] mb-1">{t('profile.last_name', 'Фамилия')}</label>
                      <input
                        type="text"
                        value={lastName}
                        onChange={(e) => setLastName(e.target.value)}
                        className="w-full px-4 py-2.5 bg-[#FAF7F5] border border-[#E3DBD5] rounded-xl text-xs focus:outline-none focus:border-[#1C1917]"
                      />
                    </div>
                  </div>

                  <div>
                    <label className="block text-[11px] font-bold text-[#8C827A] mb-1">{t('profile.phone', 'Телефон')}</label>
                    <input
                      type="tel"
                      value={phone}
                      onChange={(e) => setPhone(e.target.value)}
                      className="w-full px-4 py-2.5 bg-[#FAF7F5] border border-[#E3DBD5] rounded-xl text-xs font-mono focus:outline-none focus:border-[#1C1917]"
                    />
                  </div>

                  <div>
                    <label className="block text-[11px] font-bold text-[#8C827A] mb-1">{t('profile.city', 'Город')}</label>
                    <input
                      type="text"
                      value={city}
                      onChange={(e) => setCity(e.target.value)}
                      placeholder="Ташкент"
                      className="w-full px-4 py-2.5 bg-[#FAF7F5] border border-[#E3DBD5] rounded-xl text-xs focus:outline-none focus:border-[#1C1917]"
                    />
                  </div>

                  <div>
                    <label className="block text-[11px] font-bold text-[#8C827A] mb-1">{t('profile.address', 'Адрес доставки')}</label>
                    <input
                      type="text"
                      value={address}
                      onChange={(e) => setAddress(e.target.value)}
                      placeholder="ул. Амира Темура, д. 10"
                      className="w-full px-4 py-2.5 bg-[#FAF7F5] border border-[#E3DBD5] rounded-xl text-xs focus:outline-none focus:border-[#1C1917]"
                    />
                  </div>

                  <button
                    type="submit"
                    disabled={saving}
                    className="w-full py-3.5 bg-[#1C1917] hover:bg-black text-white text-xs font-bold uppercase tracking-wider rounded-xl transition cursor-pointer disabled:opacity-50"
                  >
                    {saving ? t('profile.saving', 'Сохранение...') : t('profile.save', 'Сохранить изменения')}
                  </button>
                </form>
              </div>
            )}
          </div>
        )}
      </main>

      <Footer />
    </div>
  );
}
