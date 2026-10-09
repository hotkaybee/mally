import React from 'react';
import Link from './Link';
import { useLanguage } from '../context/LanguageContext';

export function Footer() {
  const { t } = useLanguage();

  return (
    <footer className="w-full bg-[#FBF3F1] border-t border-[#F0E4E1] pt-12 pb-8 px-4 sm:px-6 lg:px-12 text-[#1C1917] font-sans transition-colors">
      <div className="max-w-7xl mx-auto">
        {/* 1. Верхние 3 карточки контактов */}
        <div className="grid grid-cols-1 sm:grid-cols-3 gap-4 mb-12">
          {/* Обслуживание клиентов */}
          <div className="bg-white rounded-2xl p-5 shadow-sm border border-[#EFE5E2] flex items-start gap-3.5 hover:shadow-md transition">
            <div className="w-10 h-10 rounded-xl bg-[#F0FAF7] text-[#0D9488] flex items-center justify-center text-lg flex-shrink-0">
              <i className="fa-regular fa-comment-dots" />
            </div>
            <div>
              <h4 className="font-bold text-sm text-[#1C1917] leading-snug">Обслуживание клиентов</h4>
              <p className="text-xs text-[#78716C] mt-1">Каждый день, с 10:00 до 20:00</p>
            </div>
          </div>

          {/* Звонки */}
          <div className="bg-white rounded-2xl p-5 shadow-sm border border-[#EFE5E2] flex items-start gap-3.5 hover:shadow-md transition">
            <div className="w-10 h-10 rounded-xl bg-[#F0FAF7] text-[#0D9488] flex items-center justify-center text-lg flex-shrink-0">
              <i className="fa-solid fa-phone" />
            </div>
            <div>
              <h4 className="font-bold text-sm text-[#1C1917] leading-snug">Звонки</h4>
              <a
                href="tel:+998998222880"
                className="text-xs text-[#78716C] hover:text-[#0D9488] mt-1 block font-mono font-medium transition"
              >
                +998 99 822 2880
              </a>
            </div>
          </div>

          {/* Предложения и жалобы */}
          <div className="bg-white rounded-2xl p-5 shadow-sm border border-[#EFE5E2] flex items-start gap-3.5 hover:shadow-md transition">
            <div className="w-10 h-10 rounded-xl bg-[#F0FAF7] text-[#0D9488] flex items-center justify-center text-lg flex-shrink-0">
              <i className="fa-regular fa-paper-plane" />
            </div>
            <div>
              <h4 className="font-bold text-sm text-[#1C1917] leading-snug">Предложения и жалобы</h4>
              <a
                href="mailto:info@mally.uz"
                className="text-xs text-[#78716C] hover:text-[#0D9488] mt-1 block font-mono transition"
              >
                info@mally.uz
              </a>
            </div>
          </div>
        </div>

        {/* 2. Средняя часть: О бренде и меню */}
        <div className="grid grid-cols-1 lg:grid-cols-12 gap-10 lg:gap-14 pb-12 border-b border-[#EAE0DC]">
          <div className="lg:col-span-6 space-y-4">
            <Link href="/" className="inline-flex flex-col items-start group">
              <span className="font-serif text-3xl font-bold tracking-wider text-[#1C1917] leading-none group-hover:opacity-80 transition">
                mally
              </span>
              <span className="text-[9px] uppercase tracking-[0.3em] text-[#8C827A] font-medium mt-1">
                Beauty without limits
              </span>
            </Link>
            <p className="text-xs sm:text-sm text-[#57534E] leading-relaxed max-w-md">
              Оригинальная косметика и премиальный уход от ведущих мировых брендов.
              Быстрая доставка по всему Узбекистану и заботливый сервис для каждого клиента.
            </p>

            <div className="flex flex-wrap items-center gap-2 pt-2">
              <span className="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-full bg-white border border-[#E2D6D2] text-[11px] font-medium text-[#57534E]">
                <i className="fa-solid fa-certificate text-emerald-600 text-xs" />
                100% Оригинальная продукция
              </span>
              <span className="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-full bg-white border border-[#E2D6D2] text-[11px] font-medium text-[#57534E]">
                <i className="fa-solid fa-truck-fast text-[#0D9488] text-xs" />
                Быстрая доставка
              </span>
              <a
                href="https://t.me/mallyuz"
                target="_blank"
                rel="noreferrer"
                className="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-full bg-[#0088cc]/10 hover:bg-[#0088cc]/20 border border-[#0088cc]/20 text-[11px] font-medium text-[#0088cc] transition"
              >
                <i className="fa-brands fa-telegram text-xs" />
                Чат с поддержкой
              </a>
            </div>
          </div>

          <div className="lg:col-span-6 grid grid-cols-2 sm:grid-cols-2 gap-8 lg:pl-10">
            <div className="space-y-3.5">
              <h4 className="font-serif text-sm font-bold text-[#0F2636] tracking-wide">Меню</h4>
              <ul className="space-y-2 text-xs text-[#57534E]">
                <li>
                  <Link href="/" className="hover:text-[#0F2636] transition inline-block">
                    Главная страница
                  </Link>
                </li>
                <li>
                  <Link href="/catalog" className="hover:text-[#0F2636] transition inline-block">
                    Каталог товаров
                  </Link>
                </li>
                <li>
                  <Link href="/track" className="hover:text-[#0F2636] transition inline-block">
                    Отследить доставку
                  </Link>
                </li>
                <li>
                  <a href="https://t.me/mallyuz" target="_blank" rel="noreferrer" className="hover:text-[#0F2636] transition inline-block">
                    Контакты
                  </a>
                </li>
                <li>
                  <a href="/admin" className="hover:text-[#0F2636] transition inline-block font-semibold">
                    Админ-панель
                  </a>
                </li>
              </ul>
            </div>

            <div className="space-y-3.5">
              <h4 className="font-serif text-sm font-bold text-[#0F2636] tracking-wide">Политика</h4>
              <ul className="space-y-2 text-xs text-[#57534E]">
                <li>
                  <Link href="/returns" className="hover:text-[#0F2636] transition inline-block">
                    Политика возврата
                  </Link>
                </li>
                <li>
                  <Link href="/returns" className="hover:text-[#0F2636] transition inline-block">
                    Стандарты качества
                  </Link>
                </li>
                <li>
                  <Link href="/returns" className="hover:text-[#0F2636] transition inline-block">
                    Условия доставки
                  </Link>
                </li>
              </ul>
            </div>
          </div>
        </div>

        {/* 3. Нижняя панель */}
        <div className="pt-8 flex flex-col sm:flex-row items-center justify-between gap-4 text-xs text-[#78716C]">
          <p>© 2026 Mally Uzbekistan. {t('footer.rights', 'Все права защищены.')}</p>

          <div className="flex items-center gap-4 text-base">
            <a
              href="https://www.instagram.com/mally_uz"
              target="_blank"
              rel="noreferrer"
              aria-label="Instagram @mally_uz"
              className="text-[#1C1917] hover:text-[#0D9488] transition"
            >
              <i className="fa-brands fa-instagram" />
            </a>
            <a
              href="https://t.me/mallyuz"
              target="_blank"
              rel="noreferrer"
              aria-label="Telegram"
              className="text-[#1C1917] hover:text-[#0D9488] transition"
            >
              <i className="fa-brands fa-telegram" />
            </a>
          </div>
        </div>
      </div>
    </footer>
  );
}

export default Footer;
