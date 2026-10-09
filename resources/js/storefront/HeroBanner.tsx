import React from 'react';
import { Sparkles, CheckCircle2, Truck, DollarSign } from 'lucide-react';

interface HeroBannerProps {
  onExploreClick: () => void;
  onBrandClick?: (brand: string) => void;
}

export const HeroBanner: React.FC<HeroBannerProps> = ({ onExploreClick }) => {
  return (
    <section className="relative overflow-hidden bg-gradient-to-b from-[#FCECEC]/70 via-[#FAF7F5] to-[#FAF7F5] pt-10 pb-16 px-4 sm:px-6 lg:px-8 border-b border-stone-200/60">
      <div className="max-w-7xl mx-auto">
        <div className="max-w-3xl mx-auto text-center">
          <div className="inline-flex items-center gap-2 px-3.5 py-1.5 rounded-full bg-white/90 border border-stone-200/80 shadow-xs mb-6">
            <Sparkles className="w-4 h-4 text-[#C5A880]" />
            <span className="text-xs font-semibold tracking-widest uppercase text-stone-700">
              Original Beauty Collection • 2026
            </span>
          </div>

          <h1 className="text-4xl sm:text-5xl lg:text-6xl font-serif text-[#1C1917] tracking-tight leading-[1.15] font-normal">
            Премиальная косметика <br className="hidden sm:inline" />
            <span className="italic font-normal text-[#96674c]">из США и Европы</span>
          </h1>

          <p className="mt-5 text-base sm:text-lg text-stone-600 max-w-2xl mx-auto leading-relaxed font-sans font-light">
            Официальные поставки Rhode, Hourglass, Rituals, Rare Beauty, Dior и Sol de Janeiro. Все товары имеют оригинальные батч-коды и бережно доставляются в фирменной упаковке.
          </p>

          <div className="mt-8 flex flex-col sm:flex-row items-center justify-center gap-3">
            <button
              onClick={onExploreClick}
              className="w-full sm:w-auto px-8 py-3.5 rounded-full bg-[#1C1917] text-white font-medium text-sm tracking-wider uppercase hover:bg-[#322c28] transition-all shadow-md hover:shadow-lg active:scale-95"
            >
              Смотреть каталог
            </button>
            <a
              href="#brands"
              className="w-full sm:w-auto px-7 py-3.5 rounded-full bg-white text-[#1C1917] border border-stone-300 font-medium text-sm tracking-wider uppercase hover:bg-stone-50 transition-all shadow-xs"
            >
              Популярные бренды
            </a>
          </div>
        </div>

        {/* USP Grid */}
        <div className="mt-14 grid grid-cols-2 lg:grid-cols-4 gap-4 sm:gap-6 pt-10 border-t border-stone-200/70">
          <div className="flex items-start gap-3.5 p-3 rounded-2xl bg-white/60 border border-stone-200/50 shadow-2xs">
            <div className="p-2.5 rounded-xl bg-[#FCECEC] text-[#96674c]">
              <CheckCircle2 className="w-5 h-5" />
            </div>
            <div>
              <h4 className="text-sm font-semibold text-[#1C1917]">100% Оригинал</h4>
              <p className="text-xs text-stone-500 mt-0.5 leading-snug">
                Прямые закупки из США, Sephora и официальных бутиков.
              </p>
            </div>
          </div>

          <div className="flex items-start gap-3.5 p-3 rounded-2xl bg-white/60 border border-stone-200/50 shadow-2xs">
            <div className="p-2.5 rounded-xl bg-[#FCECEC] text-[#96674c]">
              <Sparkles className="w-5 h-5" />
            </div>
            <div>
              <h4 className="text-sm font-semibold text-[#1C1917]">Батч-коды</h4>
              <p className="text-xs text-stone-500 mt-0.5 leading-snug">
                Каждый флакон проверяется через CheckFresh перед отправкой.
              </p>
            </div>
          </div>

          <div className="flex items-start gap-3.5 p-3 rounded-2xl bg-white/60 border border-stone-200/50 shadow-2xs">
            <div className="p-2.5 rounded-xl bg-[#FCECEC] text-[#96674c]">
              <Truck className="w-5 h-5" />
            </div>
            <div>
              <h4 className="text-sm font-semibold text-[#1C1917]">Экспресс доставка</h4>
              <p className="text-xs text-stone-500 mt-0.5 leading-snug">
                По Ташкенту в день заказа, по регионам за 1-2 дня.
              </p>
            </div>
          </div>

          <div className="flex items-start gap-3.5 p-3 rounded-2xl bg-white/60 border border-stone-200/50 shadow-2xs">
            <div className="p-2.5 rounded-xl bg-[#FCECEC] text-[#96674c]">
              <DollarSign className="w-5 h-5" />
            </div>
            <div>
              <h4 className="text-sm font-semibold text-[#1C1917]">Цены в USD ($)</h4>
              <p className="text-xs text-stone-500 mt-0.5 leading-snug">
                Честные прозрачные цены без скрытых комиссий и наценок.
              </p>
            </div>
          </div>
        </div>
      </div>
    </section>
  );
};
