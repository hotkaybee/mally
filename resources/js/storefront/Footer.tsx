import React from 'react';
import { Sparkles, ShieldCheck, Send } from 'lucide-react';

export const Footer: React.FC = () => {
  return (
    <footer className="bg-[#1C1917] text-stone-300 pt-16 pb-12 border-t border-stone-800">
      <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div className="grid grid-cols-1 md:grid-cols-4 gap-10 pb-12 border-b border-stone-800">
          {/* Col 1: Brand Info */}
          <div className="md:col-span-1 space-y-4">
            <div>
              <span className="text-2xl font-serif tracking-[0.25em] text-white font-semibold">
                MALLY
              </span>
              <p className="text-[10px] tracking-[0.3em] uppercase text-[#E5D3B3] font-medium mt-0.5">
                Beauty & Care
              </p>
            </div>
            <p className="text-xs text-stone-400 leading-relaxed font-light">
              Премиальная оригинальная косметика и наборы из США и Европы. Мы гарантируем подлинность каждого продукта и проверяем батч-коды.
            </p>
            <div className="flex items-center gap-3 pt-2">
              <a
                href="https://t.me/"
                target="_blank"
                rel="noreferrer"
                className="w-9 h-9 rounded-full bg-stone-800 hover:bg-[#229ed9] text-white flex items-center justify-center transition-colors shadow-xs"
                title="Telegram"
              >
                <Send className="w-4 h-4" />
              </a>
              <a
                href="https://instagram.com/"
                target="_blank"
                rel="noreferrer"
                className="w-9 h-9 rounded-full bg-stone-800 hover:bg-[#e1306c] text-white flex items-center justify-center transition-colors shadow-xs"
                title="Instagram"
              >
                <svg className="w-4 h-4 fill-current" viewBox="0 0 24 24">
                  <path d="M12 2.163c3.204 0 3.584.012 4.85.07 3.252.148 4.771 1.691 4.919 4.919.058 1.265.069 1.645.069 4.849 0 3.205-.012 3.584-.069 4.849-.149 3.225-1.664 4.771-4.919 4.919-1.266.058-1.644.07-4.85.07-3.204 0-3.584-.012-4.849-.07-3.26-.149-4.771-1.699-4.919-4.92-.058-1.265-.07-1.644-.07-4.849 0-3.204.013-3.583.07-4.849.149-3.227 1.664-4.771 4.919-4.919 1.266-.057 1.645-.069 4.849-.069zm0-2.163c-3.259 0-3.667.014-4.947.072-4.358.2-6.78 2.618-6.98 6.98-.059 1.281-.073 1.689-.073 4.948 0 3.259.014 3.668.072 4.948.2 4.358 2.618 6.78 6.98 6.98 1.281.058 1.689.072 4.948.072 3.259 0 3.668-.014 4.948-.072 4.354-.2 6.782-2.618 6.979-6.98.059-1.28.073-1.689.073-4.948 0-3.259-.014-3.667-.072-4.947-.196-4.354-2.617-6.78-6.979-6.98-1.281-.059-1.69-.073-4.949-.073zm0 5.838c-3.403 0-6.162 2.759-6.162 6.162s2.759 6.163 6.162 6.163 6.162-2.759 6.162-6.163c0-3.403-2.759-6.162-6.162-6.162zm0 10.162c-2.209 0-4-1.79-4-4 0-2.209 1.791-4 4-4s4 1.791 4 4c0 2.21-1.791 4-4 4zm6.406-11.845c-.796 0-1.441.645-1.441 1.44s.645 1.44 1.441 1.44c.795 0 1.439-.645 1.439-1.44s-.644-1.44-1.439-1.44z"/>
                </svg>
              </a>
            </div>
          </div>

          {/* Col 2: Categories */}
          <div>
            <h4 className="text-xs font-bold tracking-widest uppercase text-white mb-4">
              Категории
            </h4>
            <ul className="space-y-2.5 text-xs text-stone-400">
              <li><span className="hover:text-white transition-colors cursor-pointer">Макияж</span></li>
              <li><span className="hover:text-white transition-colors cursor-pointer">Уход за кожей</span></li>
              <li><span className="hover:text-white transition-colors cursor-pointer">Парфюмерия</span></li>
              <li><span className="hover:text-white transition-colors cursor-pointer">Подарочные наборы</span></li>
              <li><span className="hover:text-white transition-colors cursor-pointer">Блески и помады</span></li>
              <li><span className="hover:text-white transition-colors cursor-pointer">Кисти и аксессуары</span></li>
            </ul>
          </div>

          {/* Col 3: Brands */}
          <div>
            <h4 className="text-xs font-bold tracking-widest uppercase text-white mb-4">
              Бренды
            </h4>
            <ul className="space-y-2.5 text-xs text-stone-400">
              <li><span className="hover:text-white transition-colors cursor-pointer">Rhode by Hailey Bieber</span></li>
              <li><span className="hover:text-white transition-colors cursor-pointer">Hourglass Cosmetics</span></li>
              <li><span className="hover:text-white transition-colors cursor-pointer">Rituals Cosmetics</span></li>
              <li><span className="hover:text-white transition-colors cursor-pointer">Rare Beauty by Selena Gomez</span></li>
              <li><span className="hover:text-white transition-colors cursor-pointer">Sol de Janeiro</span></li>
              <li><span className="hover:text-white transition-colors cursor-pointer">Dior Beauty</span></li>
            </ul>
          </div>

          {/* Col 4: Quality & Trust */}
          <div className="space-y-4">
            <h4 className="text-xs font-bold tracking-widest uppercase text-white mb-4">
              Гарантии качества
            </h4>
            <div className="flex items-start gap-3 text-xs text-stone-400">
              <ShieldCheck className="w-5 h-5 text-emerald-400 shrink-0 mt-0.5" />
              <span>Только оригинальная продукция с подтвержденными батч-кодами.</span>
            </div>
            <div className="flex items-start gap-3 text-xs text-stone-400">
              <Sparkles className="w-5 h-5 text-[#E5D3B3] shrink-0 mt-0.5" />
              <span>Фирменная подарочная упаковка для каждого заказа.</span>
            </div>
            <p className="text-[11px] text-stone-500 pt-2 border-t border-stone-800">
              Все цены в магазине зафиксированы в USD ($). Оплата осуществляется по прозрачному тарифу.
            </p>
          </div>
        </div>

        {/* Bottom Credits */}
        <div className="pt-8 flex flex-col sm:flex-row items-center justify-between gap-4 text-xs text-stone-500">
          <p>© {new Date().getFullYear()} Mally Store. Все права защищены.</p>
          <p className="text-[11px] text-stone-600">
            Официальный бутик оригинальной косметики из США и Европы в Узбекистане.
          </p>
        </div>
      </div>
    </footer>
  );
};
