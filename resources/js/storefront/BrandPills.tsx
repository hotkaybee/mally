import React from 'react';

interface BrandPillsProps {
  selectedBrand: string;
  onSelectBrand: (brand: string) => void;
  brands: string[];
}

export const BrandPills: React.FC<BrandPillsProps> = ({
  selectedBrand,
  onSelectBrand,
  brands,
}) => {
  return (
    <div id="brands" className="py-6 border-b border-stone-200/60 bg-white/40">
      <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div className="flex items-center justify-between mb-3">
          <span className="text-xs font-bold tracking-widest uppercase text-stone-400">
            Популярные бренды
          </span>
          {selectedBrand && selectedBrand !== 'Все бренды' && (
            <button
              onClick={() => onSelectBrand('Все бренды')}
              className="text-xs text-stone-500 hover:text-stone-900 underline underline-offset-4"
            >
              Сбросить фильтр бренда
            </button>
          )}
        </div>

        <div className="flex items-center gap-2 overflow-x-auto pb-2 no-scrollbar">
          <button
            onClick={() => onSelectBrand('Все бренды')}
            className={`px-4 py-2 rounded-xl text-xs font-semibold whitespace-nowrap transition-all border ${
              selectedBrand === 'Все бренды' || !selectedBrand
                ? 'bg-[#1C1917] text-white border-[#1C1917] shadow-sm'
                : 'bg-white text-stone-700 border-stone-200 hover:border-stone-300 hover:bg-stone-50'
            }`}
          >
            Все бренды
          </button>

          {brands.map((brand) => {
            const isSelected = selectedBrand === brand;
            return (
              <button
                key={brand}
                onClick={() => onSelectBrand(brand)}
                className={`px-4 py-2 rounded-xl text-xs font-semibold whitespace-nowrap transition-all border ${
                  isSelected
                    ? 'bg-[#1C1917] text-white border-[#1C1917] shadow-sm'
                    : 'bg-white text-stone-700 border-stone-200 hover:border-stone-300 hover:bg-stone-50'
                }`}
              >
                {brand}
              </button>
            );
          })}
        </div>
      </div>
    </div>
  );
};
