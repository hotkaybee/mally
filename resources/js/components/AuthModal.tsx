import React, { useState, useEffect } from 'react';
import { useCart } from '../context/CartContext';
import { useToast } from '../context/ToastContext';
import { useLanguage } from '../context/LanguageContext';

interface AuthModalProps {
  isOpen: boolean;
  onClose: () => void;
  initialMode?: 'login' | 'register';
  message?: string;
  onSuccess?: () => void;
}

export default function AuthModal({
  isOpen,
  onClose,
  message,
  onSuccess,
}: AuthModalProps) {
  const { login } = useCart();
  const { showToast } = useToast();
  const { t } = useLanguage();

  const [step, setStep] = useState<'phone' | 'code' | 'complete-profile'>('phone');
  const [phone, setPhone] = useState('+998 ');
  const [code, setCode] = useState('');
  const [devCode, setDevCode] = useState<string | null>(null);
  const [verificationToken, setVerificationToken] = useState('');
  const [verifiedPhone, setVerifiedPhone] = useState('');
  const [loading, setLoading] = useState(false);

  const [firstName, setFirstName] = useState('');
  const [lastName, setLastName] = useState('');

  const handlePhoneChange = (val: string) => {
    let raw = val.replace(/[^\d+]/g, '');
    if (!raw.startsWith('+998')) {
      raw = '+998' + raw.replace(/^\+?998?/, '');
    }
    if (raw.length > 13) raw = raw.slice(0, 13);
    setPhone(raw);
  };

  useEffect(() => {
    if (isOpen) {
      setStep('phone');
      setPhone('+998 ');
      setCode('');
      setDevCode(null);
      setVerificationToken('');
      setVerifiedPhone('');
      setFirstName('');
      setLastName('');
    }
  }, [isOpen]);

  const handleSendCode = async (e?: React.FormEvent) => {
    if (e) e.preventDefault();
    const cleanDigits = phone.replace(/\D/g, '');
    if (cleanDigits.length < 9) {
      showToast('Введите корректный номер телефона', 'warning');
      return;
    }

    setLoading(true);
    try {
      const res = await fetch('/api/auth/phone-verify/send-code', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ phone }),
      });
      const data = await res.json();

      if (!res.ok || !data.success) {
        throw new Error(data.error || 'Не удалось отправить код');
      }

      setVerificationToken(data.token);
      setVerifiedPhone(data.phone);
      if (data.devCode) {
        setDevCode(data.devCode);
        setCode(data.devCode);
      }
      setStep('code');
      showToast('Код подтверждения отправлен!', 'info');
    } catch (err: any) {
      showToast(err.message, 'error');
    } finally {
      setLoading(false);
    }
  };

  const handleVerifyCode = async (e?: React.FormEvent, customCode?: string) => {
    if (e) e.preventDefault();
    const codeToVerify = (customCode || code).trim();
    if (!codeToVerify) {
      showToast('Введите проверочный код', 'warning');
      return;
    }

    setLoading(true);
    try {
      const res = await fetch('/api/auth/phone-verify/verify-code', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({
          token: verificationToken,
          code: codeToVerify,
          phone: verifiedPhone || phone,
        }),
      });
      const data = await res.json();

      if (!res.ok || !data.success) {
        throw new Error(data.error || 'Неверный код подтверждения');
      }

      // If user already exists in DB -> log in immediately
      if (!data.isNewUser && data.user) {
        login(data.user);
        showToast('Вы успешно вошли в аккаунт!', 'success');
        if (onSuccess) onSuccess();
        onClose();
      } else {
        // New user -> ask for First Name & Last Name (no username)
        setStep('complete-profile');
        showToast('Номер подтверждён. Введите имя и фамилию для регистрации', 'info');
      }
    } catch (err: any) {
      showToast(err.message, 'error');
    } finally {
      setLoading(false);
    }
  };

  const handleCompleteRegister = async (e: React.FormEvent) => {
    e.preventDefault();
    if (!firstName.trim()) {
      showToast('Пожалуйста, введите ваше имя', 'warning');
      return;
    }

    setLoading(true);
    try {
      const res = await fetch('/api/auth/phone-verify/complete-register', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({
          phone: verifiedPhone || phone,
          firstName: firstName.trim(),
          lastName: lastName.trim(),
        }),
      });
      const data = await res.json();

      if (!res.ok || !data.success) {
        throw new Error(data.error || 'Ошибка при завершении регистрации');
      }

      login(data.user);
      showToast('Регистрация успешно завершена! Добро пожаловать!', 'success');
      if (onSuccess) onSuccess();
      onClose();
    } catch (err: any) {
      showToast(err.message, 'error');
    } finally {
      setLoading(false);
    }
  };

  if (!isOpen) return null;

  return (
    <div className="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/40 backdrop-blur-sm animate-fade-in">
      <div className="relative w-full max-w-md bg-white rounded-3xl p-6 sm:p-8 shadow-2xl border border-[#EAE3DE] text-[#1C1917] space-y-6">
        <button
          onClick={onClose}
          type="button"
          className="absolute top-5 right-5 w-8 h-8 rounded-full bg-[#FAF7F5] hover:bg-[#EAE3DE] text-[#8C827A] flex items-center justify-center text-sm transition cursor-pointer"
        >
          <i className="fa-solid fa-xmark" />
        </button>

        <div className="text-center space-y-1.5">
          <div className="font-serif text-2xl font-bold tracking-tight">mally</div>
          <h2 className="text-base font-bold text-[#1C1917]">
            {step === 'phone'
              ? 'Вход или регистрация'
              : step === 'code'
              ? 'Подтверждение номера'
              : 'Завершение регистрации'}
          </h2>
          <p className="text-xs text-[#78716C]">
            {step === 'complete-profile'
              ? 'Укажите ваше имя для оформления заказов'
              : (message || 'Доступ к вашим заказам и профилю')}
          </p>
        </div>

        {/* ШАГ 1: Ввод телефона */}
        {step === 'phone' && (
          <form onSubmit={handleSendCode} className="space-y-4">
            <div>
              <label className="block text-[11px] uppercase tracking-wider font-bold text-[#8C827A] mb-1.5">
                Номер телефона
              </label>
              <input
                type="tel"
                required
                value={phone}
                onChange={(e) => handlePhoneChange(e.target.value)}
                placeholder="+998 90 123 45 67"
                className="w-full px-4 py-3 bg-[#FAF7F5] border border-[#E3DBD5] rounded-2xl text-sm font-mono focus:outline-none focus:border-[#1C1917] transition"
              />
            </div>

            <button
              type="submit"
              disabled={loading}
              className="w-full py-3.5 bg-[#1C1917] hover:bg-black text-white text-xs font-bold uppercase tracking-wider rounded-2xl shadow-md transition disabled:opacity-50 cursor-pointer"
            >
              {loading ? 'Отправка...' : 'Получить код'}
            </button>
          </form>
        )}

        {/* ШАГ 2: Ввод кода подтверждения */}
        {step === 'code' && (
          <form onSubmit={handleVerifyCode} className="space-y-4">
            <div className="text-center">
              <span className="text-xs text-[#78716C]">Код отправлен на номер</span>
              <div className="font-mono font-bold text-sm text-[#1C1917] mt-0.5">{phone}</div>
            </div>

            <div>
              <label className="block text-[11px] uppercase tracking-wider font-bold text-[#8C827A] mb-1.5 text-center">
                4-значный код
              </label>
              <input
                type="text"
                maxLength={6}
                required
                value={code}
                onChange={(e) => setCode(e.target.value)}
                placeholder="7788"
                className="w-full px-4 py-3 bg-[#FAF7F5] border border-[#E3DBD5] rounded-2xl text-center text-xl font-mono tracking-widest font-bold focus:outline-none focus:border-[#1C1917] transition"
              />
              {devCode && (
                <p className="text-[11px] text-emerald-600 text-center mt-1">
                  Тестовый код: <span className="font-mono font-bold">{devCode}</span>
                </p>
              )}
            </div>

            <button
              type="submit"
              disabled={loading}
              className="w-full py-3.5 bg-[#1C1917] hover:bg-black text-white text-xs font-bold uppercase tracking-wider rounded-2xl shadow-md transition disabled:opacity-50 cursor-pointer"
            >
              {loading ? 'Проверка...' : 'Подтвердить код'}
            </button>

            <button
              type="button"
              onClick={() => setStep('phone')}
              className="w-full text-center text-xs text-[#8C827A] hover:text-black transition cursor-pointer"
            >
              Изменить номер
            </button>
          </form>
        )}

        {/* ШАГ 3: Заполнение данных нового клиента (ТОЛЬКО имя и фамилия, без username) */}
        {step === 'complete-profile' && (
          <form onSubmit={handleCompleteRegister} className="space-y-4">
            <div>
              <label className="block text-[11px] uppercase tracking-wider font-bold text-[#8C827A] mb-1.5">
                Ваше Имя *
              </label>
              <input
                type="text"
                required
                value={firstName}
                onChange={(e) => setFirstName(e.target.value)}
                placeholder="Имя"
                className="w-full px-4 py-3 bg-[#FAF7F5] border border-[#E3DBD5] rounded-2xl text-sm focus:outline-none focus:border-[#1C1917] transition"
              />
            </div>

            <div>
              <label className="block text-[11px] uppercase tracking-wider font-bold text-[#8C827A] mb-1.5">
                Фамилия
              </label>
              <input
                type="text"
                value={lastName}
                onChange={(e) => setLastName(e.target.value)}
                placeholder="Фамилия"
                className="w-full px-4 py-3 bg-[#FAF7F5] border border-[#E3DBD5] rounded-2xl text-sm focus:outline-none focus:border-[#1C1917] transition"
              />
            </div>

            <button
              type="submit"
              disabled={loading}
              className="w-full py-3.5 bg-[#1C1917] hover:bg-black text-white text-xs font-bold uppercase tracking-wider rounded-2xl shadow-md transition disabled:opacity-50 cursor-pointer"
            >
              {loading ? 'Сохранение...' : 'Завершить регистрацию'}
            </button>
          </form>
        )}
      </div>
    </div>
  );
}
