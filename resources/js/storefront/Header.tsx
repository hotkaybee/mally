import React, { useState } from 'react';
import { useCart } from '../context/CartContext';
import { useFavorites } from '../context/FavoritesContext';
import { formatUSD } from '../shared/formatters';
import { ShoppingBag, Heart, Search, X, Menu, Sparkles, ShieldCheck } from 'lucide-react';

interface HeaderProps {
  searchQuery: string;
  onSearchChange: (query: string) => void;
  selectedCategory: string;
  onSelectCategory: (category: string) => void;
  categories: string[];
}

export const Header: React.FC<HeaderProps> = ({
  searchQuery,
  onSearchChange,
  selectedCategory,
  onSelectCategory,
  categories,
}) => {
  const { totalItems, subtotal, setIsCartOpen } = useCart();
  const { favoritesCount } = useFavorites();
  const [isMobileMenuOpen, setIsMobileMenuOpen] = useState(false);
  const [isSearchOpenMobile, setIsSearchOpenMobile] = useState(false);

  return (
    <header className="sticky top-0 z-40 bg-[#FAF7F5]/95 backdrop-blur-md border-b border-stone-200/80 transition-all">
      {/* Top USP Banner */}
      <div className="bg-[#1C1917] text-[#FAF7F5] text-xs py-2 px-4 tracking-wider text-center font-medium flex items-center justify-center gap-4">
        <span className="flex items-center gap-1.5 opacity-90">
          <Sparkles className="w-3.5 h-3.5 text-[#E5D3B3]" />
          100% оригинальная косметика из США и Европы
        </span>
        <span className="hidden md:inline text-stone-500">•</span>
        <span className="hidden md:flex items-center gap-1.5 opacity-90">
          <ShieldCheck className="w-3.5 h-3.5 text-[#E5D3B3]" />
          Проверка батч-кодов перед отправкой
        </span>
        <span className="hidden lg:inline text-stone-500">•</span>
        <span className="hidden lg:inline opacity-90">
          Все цены зафиксированы в USD ($)
        </span>
      </div>

      {/* Main Navbar */}
      <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div className="flex items-center justify-between h-20 gap-4">
          {/* Mobile menu trigger */}
          <div className="flex items-center lg:hidden">
            <button
              onClick={() => setIsMobileMenuOpen(!isMobileMenuOpen)}
              className="p-2 -ml-2 text-stone-700 hover:text-stone-900 rounded-lg hover:bg-stone-100 transition-colors"
              aria-label="Меню"
            >
              {isMobileMenuOpen ? <X className="w-6 h-6" /> : <Menu className="w-6 h-6" />}
            </button>
            <button
              onClick={() => setIsSearchOpenMobile(!isSearchOpenMobile)}
              className="p-2 ml-1 text-stone-700 hover:text-stone-900 rounded-lg hover:bg-stone-100 transition-colors"
              aria-label="Поиск"
            >
              <Search className="w-5 h-5" />
            </button>
          </div>

          {/* Logo */}
          <div className="flex flex-col items-center lg:items-start select-none cursor-pointer" onClick={() => onSelectCategory('Все')}>
            <span className="text-2xl sm:text-3xl font-serif tracking-[0.25em] text-[#1C1917] font-semibold hover:opacity-90 transition-opacity">
              MALLY
            </span>
            <span className="text-[10px] tracking-[0.35em] uppercase text-stone-500 font-sans -mt-1 font-medium">
              Beauty & Care
            </span>
          </div>

          {/* Search bar (Desktop) */}
          <div className="hidden lg:flex flex-1 max-w-md mx-8 relative">
            <div className="relative w-full">
              <input
                type="text"
                placeholder="Поиск по товарам, брендам или артикулу..."
                value={searchQuery}
                onChange={(e) => onSearchChange(e.target.value)}
                className="w-full pl-10 pr-10 py-2.5 bg-white border border-stone-200 rounded-full text-sm placeholder:text-stone-400 focus:outline-none focus:ring-2 focus:ring-[#1C1917]/20 focus:border-[#1C1917] transition-all shadow-sm"
              />
              <Search className="w-4 h-4 text-stone-400 absolute left-3.5 top-3" />
              {searchQuery && (
                <button
                  onClick={() => onSearchChange('')}
                  className="absolute right-3 top-3 text-stone-400 hover:text-stone-600 p-0.5"
                >
                  <X className="w-3.5 h-3.5" />
                </button>
              )}
            </div>
          </div>

          {/* Actions (Favorites & Cart) */}
          <div className="flex items-center gap-3">
            {/* Wishlist Button */}
            <button
              onClick={() => onSelectCategory('Избранное')}
              className={`relative p-2.5 rounded-full border transition-all ${
                selectedCategory === 'Избранное'
                  ? 'border-[#1C1917] bg-[#1C1917] text-white shadow-sm'
                  : 'border-stone-200 bg-white text-stone-700 hover:border-stone-300 hover:bg-stone-50'
              }`}
              title="Избранное"
            >
              <Heart className="w-5 h-5" />
              {favoritesCount > 0 && (
                <span className="absolute -top-1.5 -right-1.5 bg-[#D4AF37] text-[#1C1917] text-[11px] font-bold w-5 h-5 rounded-full flex items-center justify-center border-2 border-white shadow-sm">
                  {favoritesCount}
                </span>
              )}
            </button>

            {/* Cart Button */}
            <button
              onClick={() => setIsCartOpen(true)}
              className="flex items-center gap-3 px-4 py-2.5 bg-[#1C1917] hover:bg-[#2C2724] text-white rounded-full transition-all shadow-md hover:shadow-lg active:scale-95 group"
            >
              <div className="relative">
                <ShoppingBag className="w-5 h-5 text-white transition-transform group-hover:scale-105" />
                {totalItems > 0 && (
                  <span className="absolute -top-2 -right-2 bg-[#D4AF37] text-[#1C1917] text-[10px] font-extrabold w-4 h-4 rounded-full flex items-center justify-center shadow">
                    {totalItems}
                  </span>
                )}
              </div>
              <div className="hidden sm:flex flex-col text-left leading-none">
                <span className="text-[10px] text-stone-300 font-medium tracking-wider uppercase">Корзина</span>
                <span className="text-xs font-semibold mt-0.5">{formatUSD(subtotal)}</span>
              </div>
            </button>
          </div>
        </div>

        {/* Mobile Search Bar Expansion */}
        {isSearchOpenMobile && (
          <div className="pb-4 pt-1 lg:hidden">
            <div className="relative w-full">
              <input
                type="text"
                placeholder="Поиск по названию или бренду..."
                value={searchQuery}
                onChange={(e) => onSearchChange(e.target.value)}
                autoFocus
                className="w-full pl-10 pr-10 py-2.5 bg-white border border-stone-300 rounded-full text-sm placeholder:text-stone-400 focus:outline-none focus:ring-2 focus:ring-[#1C1917]"
              />
              <Search className="w-4 h-4 text-stone-400 absolute left-3.5 top-3" />
              {searchQuery && (
                <button
                  onClick={() => onSearchChange('')}
                  className="absolute right-3.5 top-3 text-stone-400"
                >
                  <X className="w-4 h-4" />
                </button>
              )}
            </div>
          </div>
        )}

        {/* Horizontal Category Nav (Desktop) */}
        <nav className="hidden lg:flex items-center gap-1 py-2.5 border-t border-stone-200/60 overflow-x-auto no-scrollbar">
          {categories.map((cat) => {
            const isActive = selectedCategory === cat;
            return (
              <button
                key={cat}
                onClick={() => onSelectCategory(cat)}
                className={`px-4 py-1.5 rounded-full text-xs tracking-wider uppercase font-medium whitespace-nowrap transition-all ${
                  isActive
                    ? 'bg-[#1C1917] text-white shadow-sm'
                    : 'text-stone-600 hover:text-stone-900 hover:bg-stone-100/80'
                }`}
              >
                {cat}
              </button>
            );
          })}
        </nav>
      </div>

      {/* Mobile Drawer Menu */}
      {isMobileMenuOpen && (
        <div className="lg:hidden fixed inset-0 z-50 bg-black/40 backdrop-blur-sm" onClick={() => setIsMobileMenuOpen(false)}>
          <div
            className="w-4/5 max-w-sm h-full bg-[#FAF7F5] shadow-2xl p-6 flex flex-col justify-between overflow-y-auto"
            onClick={(e) => e.stopPropagation()}
          >
            <div>
              <div className="flex items-center justify-between pb-6 border-b border-stone-200">
                <span className="text-2xl font-serif tracking-[0.2em] font-semibold text-[#1C1917]">
                  MALLY
                </span>
                <button
                  onClick={() => setIsMobileMenuOpen(false)}
                  className="p-2 text-stone-500 hover:text-stone-800"
                >
                  <X className="w-6 h-6" />
                </button>
              </div>

              <div className="mt-6 flex flex-col gap-1.5">
                <span className="text-[11px] font-bold tracking-widest uppercase text-stone-400 mb-2">
                  Категории
                </span>
                {categories.map((cat) => (
                  <button
                    key={cat}
                    onClick={() => {
                      onSelectCategory(cat);
                      setIsMobileMenuOpen(false);
                    }}
                    className={`text-left px-4 py-2.5 rounded-xl text-sm font-medium transition-colors ${
                      selectedCategory === cat
                        ? 'bg-[#1C1917] text-white'
                        : 'text-stone-700 hover:bg-stone-100'
                    }`}
                  >
                    {cat}
                  </button>
                ))}
              </div>
            </div>

            <div className="pt-6 border-t border-stone-200 text-xs text-stone-500 space-y-2">
              <p className="font-medium text-stone-700">Официальный магазин косметики</p>
              <p>Оригинал из США и Европы. Проверка батч-кодов.</p>
              <p className="text-[11px] text-stone-400 pt-2">© {new Date().getFullYear()} Mally Store</p>
            </div>
          </div>
        </div>
      )}
    </header>
  );
};
