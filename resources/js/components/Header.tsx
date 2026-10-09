import React, { useState, useEffect, useRef } from 'react';
import Link from './Link';
import { useCart } from '../context/CartContext';
import { useFavorites } from '../context/FavoritesContext';
import { useLanguage } from '../context/LanguageContext';

interface HeaderProps {
  selectedCategory?: string;
  onSelectCategory?: (cat: string) => void;
  searchQuery?: string;
  onSearchChange?: (q: string) => void;
}

export default function Header({
  selectedCategory = 'Все товары',
  onSelectCategory,
  searchQuery = '',
  onSearchChange,
}: HeaderProps) {
  const { totalCount, currency, setCurrency, exchangeRate, rateSource, user, isAuthenticated, openAuthModal, formatPrice } = useCart();
  const { favoritesCount } = useFavorites();
  const { language, setLanguage, t } = useLanguage();

  const [isMobileMenuOpen, setIsMobileMenuOpen] = useState(false);
  const [categories, setCategories] = useState<string[]>([
    'Каталог',
    'Наборы',
    'Бренды',
    'Макияж',
    'Уход',
    'Парфюмерия',
    'Аксессуары',
    'Скидки',
  ]);

  // Live beautiful search state
  const [searchTerm, setSearchTerm] = useState(searchQuery);
  const [allProducts, setAllProducts] = useState<any[]>([]);
  const [isSearchOpen, setIsSearchOpen] = useState(false);
  const searchContainerRef = useRef<HTMLDivElement>(null);
  const mobileSearchRef = useRef<HTMLDivElement>(null);

  useEffect(() => {
    setSearchTerm(searchQuery);
  }, [searchQuery]);

  // Fetch products once for live search autocomplete
  useEffect(() => {
    fetch('/api/products')
      .then((res) => res.json())
      .then((data) => {
        if (data.products && Array.isArray(data.products)) {
          setAllProducts(data.products);
        }
      })
      .catch(() => {});
  }, []);

  // Close search dropdown on click outside
  useEffect(() => {
    const handleClickOutside = (event: MouseEvent) => {
      if (
        searchContainerRef.current &&
        !searchContainerRef.current.contains(event.target as Node) &&
        mobileSearchRef.current &&
        !mobileSearchRef.current.contains(event.target as Node)
      ) {
        setIsSearchOpen(false);
      }
    };

    document.addEventListener('mousedown', handleClickOutside);
    return () => {
      document.removeEventListener('mousedown', handleClickOutside);
    };
  }, []);

  // Filter matching products
  const matchingProducts = React.useMemo(() => {
    const q = searchTerm.trim().toLowerCase();
    if (!q) return [];
    return allProducts.filter((p) => {
      const bName = (typeof p.brand === 'object' ? p.brand?.name : p.brand) || '';
      return (
        p.title.toLowerCase().includes(q) ||
        bName.toLowerCase().includes(q) ||
        (p.sku || '').toLowerCase().includes(q)
      );
    }).slice(0, 6);
  }, [searchTerm, allProducts]);

  const handleInputChange = (val: string) => {
    const clean = val.replace(/[<>]/g, '');
    setSearchTerm(clean);
    if (onSearchChange) onSearchChange(clean);
    setIsSearchOpen(clean.trim().length > 0);
  };

  const handleSearchKeyDown = (e: React.KeyboardEvent<HTMLInputElement>) => {
    if (e.key === 'Enter' && searchTerm.trim()) {
      setIsSearchOpen(false);
      window.location.href = `/catalog?q=${encodeURIComponent(searchTerm.trim())}`;
    } else if (e.key === 'Escape') {
      setIsSearchOpen(false);
    }
  };

  const handleClearSearch = () => {
    setSearchTerm('');
    if (onSearchChange) onSearchChange('');
    setIsSearchOpen(false);
  };

  useEffect(() => {
    fetch(`/api/admin/categories?lang=${language}`)
      .then((res) => res.json())
      .then((data) => {
        if (data.categories && Array.isArray(data.categories) && data.categories.length > 0) {
          const names = data.categories.map((c: any) => c.name);
          setCategories(names);
        }
      })
      .catch(() => {});
  }, [language]);

  useEffect(() => {
    if (isMobileMenuOpen) {
      document.body.style.overflow = 'hidden';
    } else {
      document.body.style.overflow = '';
    }
    return () => {
      document.body.style.overflow = '';
    };
  }, [isMobileMenuOpen]);

  return (
    <header className="w-full bg-white border-b border-[#F2D8D0] relative z-40">
      {/* 1. Верхняя полоса */}
      <div className="bg-[#1C1917] text-[#FAF7F5] py-2 px-4 text-xs font-medium">
        <div className="max-w-7xl mx-auto flex justify-between items-center">
          <div className="flex items-center gap-2 truncate pr-2">
            <span className="inline-block text-amber-400">
              <i className="fa-solid fa-truck-fast text-xs" />
            </span>
            <span className="truncate">{t('nav.shipping_banner', 'Отправка через Яндекс Go · Доставка оплачивается покупателем по тарифу')}</span>
          </div>

          <div className="flex items-center gap-2 sm:gap-3 flex-shrink-0">
            {/* Переключатель языка RU / UZ / EN */}
            <div className="flex items-center bg-white/10 rounded-full p-0.5 text-[10px] font-semibold">
              <button
                type="button"
                onClick={() => setLanguage('ru')}
                className={`px-2 py-0.5 rounded-full transition cursor-pointer ${language === 'ru' ? 'bg-white text-black' : 'text-[#D5CCC7] hover:text-white'}`}
                title="Русский язык"
              >
                RU
              </button>
              <button
                type="button"
                onClick={() => setLanguage('uz')}
                className={`px-2 py-0.5 rounded-full transition cursor-pointer ${language === 'uz' ? 'bg-white text-black' : 'text-[#D5CCC7] hover:text-white'}`}
                title="O'zbek tili"
              >
                UZ
              </button>
              <button
                type="button"
                onClick={() => setLanguage('en')}
                className={`px-2 py-0.5 rounded-full transition cursor-pointer ${language === 'en' ? 'bg-white text-black' : 'text-[#D5CCC7] hover:text-white'}`}
                title="English"
              >
                EN
              </button>
            </div>
            <span className="text-white/20">|</span>

            {/* Живой курс доллара без запятой */}
            <div className="hidden sm:flex items-center gap-1.5 text-[10px] text-[#D5CCC7]" title={`Источник: ${rateSource} · ЦБ Узбекистана`}>
              <span className={`w-1.5 h-1.5 rounded-full flex-shrink-0 ${rateSource === 'loading' ? 'bg-gray-400 animate-pulse' : 'bg-emerald-400 animate-pulse'}`} />
              <span>1 USD = {rateSource === 'loading' ? '—' : `${Math.round(exchangeRate).toLocaleString('ru-RU')} сум`}</span>
            </div>
            <span className="hidden sm:inline text-white/20">|</span>

            {/* Переключатель валют UZS / USD */}
            <div className="flex items-center bg-white/10 rounded-full p-0.5 text-[10px] font-semibold">
              <button
                type="button"
                onClick={() => setCurrency('UZS')}
                className={`px-2 py-0.5 rounded-full transition cursor-pointer ${currency === 'UZS' ? 'bg-white text-black' : 'text-[#D5CCC7] hover:text-white'}`}
              >
                UZS
              </button>
              <button
                type="button"
                onClick={() => setCurrency('USD')}
                className={`px-2 py-0.5 rounded-full transition cursor-pointer ${currency === 'USD' ? 'bg-white text-black' : 'text-[#D5CCC7] hover:text-white'}`}
              >
                USD
              </button>
            </div>

            {/* Иконка Telegram */}
            <a
              href="https://t.me/mallyuz"
              target="_blank"
              rel="noreferrer"
              className="w-6 h-6 rounded-full bg-[#0088cc] hover:bg-[#0077b3] flex items-center justify-center text-white transition shadow-sm"
              title={t('nav.support', 'Написать администратору @mallyuz')}
            >
              <i className="fa-brands fa-telegram text-xs" />
            </a>
          </div>
        </div>
      </div>

      {/* 2. Основная шапка */}
      <div className="max-w-7xl mx-auto px-4 py-3 sm:py-4 flex items-center justify-between gap-3 sm:gap-4">
        {/* Кнопка Меню (3 полоски) для мобильных */}
        <div className="flex items-center gap-2">
          <button
            type="button"
            onClick={() => setIsMobileMenuOpen(true)}
            className="md:hidden w-9 h-9 flex flex-col items-center justify-center gap-1 rounded-xl text-[#1C1917] hover:bg-[#FAF8F6] active:bg-[#F0EAE5] transition border border-[#EAE3DE] cursor-pointer"
            aria-label="Открыть мобильное меню"
          >
            <span className="w-4 h-0.5 bg-[#1C1917] rounded-full"></span>
            <span className="w-4 h-0.5 bg-[#1C1917] rounded-full"></span>
            <span className="w-4 h-0.5 bg-[#1C1917] rounded-full"></span>
          </button>

          {/* Живой красивый поиск на десктопе */}
          <div ref={searchContainerRef} className="flex-1 max-w-xs relative hidden md:block">
            <div className="relative">
              <input
                type="text"
                placeholder={t('nav.search', 'Поиск косметики...')}
                value={searchTerm}
                onChange={(e) => handleInputChange(e.target.value)}
                onFocus={() => {
                  if (searchTerm.trim().length > 0) setIsSearchOpen(true);
                }}
                onKeyDown={handleSearchKeyDown}
                className="w-full pl-4 pr-16 py-2 text-xs bg-[#FAF8F6] border border-[#E3DBD5] rounded-full focus:outline-none focus:border-[#8C827A] focus:bg-white transition shadow-2xs"
              />
              <div className="absolute right-3 top-1/2 -translate-y-1/2 flex items-center gap-1.5 text-xs text-[#8C827A]">
                {searchTerm && (
                  <button
                    type="button"
                    onClick={handleClearSearch}
                    className="p-1 hover:text-black transition cursor-pointer"
                    title="Очистить"
                  >
                    <i className="fa-solid fa-xmark text-[11px]" />
                  </button>
                )}
                <i className="fa-solid fa-magnifying-glass" />
              </div>
            </div>

            {/* Выпадающее окно результатов живого поиска */}
            {isSearchOpen && searchTerm.trim().length > 0 && (
              <div className="absolute left-0 right-0 top-full mt-2 bg-white rounded-2xl shadow-2xl border border-[#EAE3DE] overflow-hidden z-50 animate-fade-in">
                <div className="p-3 bg-[#FAF8F6] border-b border-[#EAE3DE] flex items-center justify-between">
                  <span className="text-[11px] font-bold text-[#78716C]">
                    {matchingProducts.length > 0
                      ? `Найдено: ${matchingProducts.length} ${matchingProducts.length === 1 ? 'товар' : 'товаров'}`
                      : 'Ничего не найдено'}
                  </span>
                  <span className="text-[10px] text-stone-400 font-mono">Нажмите Enter для каталога</span>
                </div>

                {matchingProducts.length > 0 ? (
                  <div className="divide-y divide-[#F2ECE8] max-h-80 overflow-y-auto">
                    {matchingProducts.map((p) => {
                      const img = Array.isArray(p.images) ? p.images[0] : (typeof p.images === 'string' && p.images.startsWith('http') ? p.images : 'https://findy.uz/cdn/shop/files/sample.jpg');
                      const bName = typeof p.brand === 'object' ? p.brand?.name : p.brand;

                      return (
                        <Link
                          key={p.id}
                          href={`/products/${p.sku || p.slug}`}
                          onClick={() => setIsSearchOpen(false)}
                          className="p-2.5 flex items-center gap-3 hover:bg-[#FAF8F6] transition cursor-pointer"
                        >
                          <div className="w-10 h-10 rounded-xl bg-stone-100 overflow-hidden flex-shrink-0 border border-[#EAE3DE]">
                            <img src={img} alt={p.title} className="w-full h-full object-cover" />
                          </div>
                          <div className="flex-1 min-w-0">
                            <span className="text-[9px] uppercase font-bold text-[#8C827A] block truncate">
                              {bName || 'Mally Collection'}
                            </span>
                            <h4 className="text-xs font-semibold text-[#1C1917] truncate leading-tight">
                              {p.title}
                            </h4>
                          </div>
                          <div className="text-right flex-shrink-0 font-mono font-bold text-xs text-[#1C1917]">
                            {formatPrice(p.price)}
                          </div>
                        </Link>
                      );
                    })}

                    <div className="p-2.5 bg-[#FAF7F5] border-t border-[#EAE3DE]">
                      <Link
                        href={`/catalog?q=${encodeURIComponent(searchTerm.trim())}`}
                        onClick={() => setIsSearchOpen(false)}
                        className="block w-full py-2 px-3 text-center text-xs font-bold text-[#1C1917] hover:bg-white rounded-xl border border-transparent hover:border-[#EAE3DE] transition"
                      >
                        Показать все результаты в каталоге →
                      </Link>
                    </div>
                  </div>
                ) : (
                  <div className="p-6 text-center space-y-2">
                    <p className="text-xs text-[#78716C]">
                      По запросу «<span className="font-semibold text-black">{searchTerm}</span>» товары не найдены.
                    </p>
                    <Link
                      href="/catalog"
                      onClick={() => setIsSearchOpen(false)}
                      className="inline-block text-xs font-bold text-amber-700 underline"
                    >
                      Перейти ко всем товарам
                    </Link>
                  </div>
                )}
              </div>
            )}
          </div>
        </div>

        {/* Логотип по центру */}
        <Link href="/" className="flex flex-col items-center">
          <div className="font-serif text-2xl sm:text-4xl font-bold tracking-wider text-[#1C1917] leading-none">
            mally
          </div>
          <span className="text-[8px] sm:text-[9px] uppercase tracking-[0.25em] sm:tracking-[0.3em] text-[#8C827A] font-medium mt-0.5">
            Beauty without limits
          </span>
        </Link>

        {/* Правый блок: Профиль и Корзина */}
        <div className="flex items-center gap-1.5 sm:gap-3">
          {isAuthenticated && user ? (
            <Link
              href="/profile"
              className="flex items-center gap-2 p-1.5 sm:px-3 sm:py-1.5 rounded-full hover:bg-[#FAF8F6] text-[#1C1917] border border-transparent hover:border-[#EAE3DE] transition cursor-pointer"
              title={t('nav.profile', 'Личный кабинет')}
            >
              <div className="w-7 h-7 rounded-full bg-[#1C1917] text-white flex items-center justify-center text-xs font-serif font-bold">
                {user.fullName ? user.fullName[0].toUpperCase() : 'M'}
              </div>
              <span className="hidden sm:inline text-xs font-semibold max-w-[100px] truncate">
                {user.fullName ? user.fullName.split(' ')[0] : t('nav.profile', 'Профиль')}
              </span>
            </Link>
          ) : (
            <button
              type="button"
              onClick={() => openAuthModal(t('profile.need_login', 'Войдите для доступа к профилю и оформлению заказов'))}
              className="flex items-center gap-1.5 p-2 rounded-full hover:bg-[#FAF8F6] text-[#1C1917] transition cursor-pointer"
              title={t('nav.login', 'Войти в личный кабинет')}
            >
              <i className="fa-regular fa-user text-base" />
              <span className="hidden sm:inline text-xs font-medium">{t('nav.login', 'Войти')}</span>
            </button>
          )}

          {/* Избранное */}
          <Link
            href="/favorites"
            className="relative flex items-center justify-center p-2 rounded-full hover:bg-[#FAF8F6] text-[#1C1917] transition cursor-pointer"
            title={t('nav.favorites', 'Избранные товары')}
          >
            <i className="fa-regular fa-heart text-lg" />
            {favoritesCount > 0 && (
              <span className="absolute top-0 right-0 bg-[#E11D48] text-white text-[10px] w-4 h-4 rounded-full flex items-center justify-center font-bold">
                {favoritesCount}
              </span>
            )}
          </Link>

          {/* Корзина */}
          <Link
            href="/cart"
            className="relative flex items-center justify-center p-2 rounded-full hover:bg-[#FAF8F6] text-[#1C1917] transition cursor-pointer"
            title={t('nav.cart', 'Перейти в корзину')}
          >
            <i className="fa-solid fa-bag-shopping text-lg" />
            {totalCount > 0 && (
              <span className="absolute top-0 right-0 bg-[#E11D48] text-white text-[10px] w-4 h-4 rounded-full flex items-center justify-center font-bold">
                {totalCount}
              </span>
            )}
          </Link>
        </div>
      </div>

      {/* 3. Горизонтальная навигация по категориям */}
      <nav className="bg-[#1C1917] text-[#FAF7F5] overflow-x-auto scrollbar-none">
        <div className="max-w-7xl mx-auto px-4 flex items-center sm:justify-center gap-5 sm:gap-6 py-2.5 text-xs font-medium whitespace-nowrap">
          {categories.map((cat) => {
            const isCatalogLink = cat === 'Каталог' || cat === 'Все товары';
            const targetUrl = isCatalogLink ? '/catalog' : `/catalog?category=${encodeURIComponent(cat)}`;
            const isSelected = selectedCategory === cat;

            return (
              <Link
                key={cat}
                href={targetUrl}
                onClick={() => onSelectCategory && onSelectCategory(cat)}
                className={`transition hover:text-white cursor-pointer ${
                  isSelected ? 'text-white font-bold underline underline-offset-4' : 'text-[#D5CCC7]'
                } ${cat === 'Скидки' ? 'text-rose-400 font-semibold' : ''}`}
              >
                {cat}
              </Link>
            );
          })}
        </div>
      </nav>

      {/* 4. Мобильное выдвижное меню */}
      {isMobileMenuOpen && (
        <div className="fixed inset-0 z-50 md:hidden flex">
          <div
            className="fixed inset-0 bg-black/40 backdrop-blur-md transition-opacity duration-300"
            onClick={() => setIsMobileMenuOpen(false)}
          />

          <div className="relative w-[85%] max-w-xs bg-white text-[#1C1917] shadow-2xl flex flex-col h-full z-10 animate-slide-in">
            <div className="p-4 border-b border-[#EAE3DE] flex items-center justify-between bg-[#FAF8F6]">
              <Link
                href="/"
                onClick={() => setIsMobileMenuOpen(false)}
                className="flex flex-col"
              >
                <span className="font-serif text-2xl font-bold tracking-wider text-[#1C1917] leading-none">
                  mally
                </span>
                <span className="text-[8px] uppercase tracking-[0.25em] text-[#8C827A] font-medium mt-0.5">
                  Beauty without limits
                </span>
              </Link>

              <div className="flex items-center gap-2">
                <div className="flex items-center bg-[#FAF8F6] border border-[#EAE3DE] rounded-full p-0.5 text-[10px] font-semibold">
                  <button
                    type="button"
                    onClick={() => setLanguage('ru')}
                    className={`px-2 py-0.5 rounded-full transition cursor-pointer ${language === 'ru' ? 'bg-[#1C1917] text-white' : 'text-[#8C827A]'}`}
                  >
                    RU
                  </button>
                  <button
                    type="button"
                    onClick={() => setLanguage('uz')}
                    className={`px-2 py-0.5 rounded-full transition cursor-pointer ${language === 'uz' ? 'bg-[#1C1917] text-white' : 'text-[#8C827A]'}`}
                  >
                    UZ
                  </button>
                  <button
                    type="button"
                    onClick={() => setLanguage('en')}
                    className={`px-2 py-0.5 rounded-full transition cursor-pointer ${language === 'en' ? 'bg-[#1C1917] text-white' : 'text-[#8C827A]'}`}
                  >
                    EN
                  </button>
                </div>
                <button
                  type="button"
                  onClick={() => setIsMobileMenuOpen(false)}
                  className="w-8 h-8 rounded-full bg-white border border-[#EAE3DE] flex items-center justify-center text-[#1C1917] hover:bg-[#F0EAE5] transition shadow-xs cursor-pointer"
                  aria-label="Закрыть меню"
                >
                  <i className="fa-solid fa-xmark text-sm" />
                </button>
              </div>
            </div>

            {/* Живой поиск в мобильном меню */}
            <div ref={mobileSearchRef} className="p-3 border-b border-[#EAE3DE] relative">
              <div className="relative">
                <input
                  type="text"
                  placeholder={t('nav.search', 'Поиск косметики...')}
                  value={searchTerm}
                  onChange={(e) => handleInputChange(e.target.value)}
                  onKeyDown={handleSearchKeyDown}
                  className="w-full pl-9 pr-8 py-2 text-xs bg-[#FAF8F6] border border-[#E3DBD5] rounded-xl focus:outline-none focus:border-[#1C1917] transition"
                />
                <i className="fa-solid fa-magnifying-glass absolute left-3 top-1/2 -translate-y-1/2 text-xs text-[#8C827A]" />
                {searchTerm && (
                  <button
                    type="button"
                    onClick={handleClearSearch}
                    className="absolute right-2.5 top-1/2 -translate-y-1/2 p-1 text-[#8C827A] hover:text-black cursor-pointer"
                  >
                    <i className="fa-solid fa-xmark text-xs" />
                  </button>
                )}
              </div>

              {isSearchOpen && searchTerm.trim().length > 0 && matchingProducts.length > 0 && (
                <div className="mt-2 bg-white rounded-xl shadow-lg border border-[#EAE3DE] divide-y divide-[#F2ECE8] max-h-56 overflow-y-auto">
                  {matchingProducts.map((p) => (
                    <Link
                      key={p.id}
                      href={`/products/${p.sku || p.slug}`}
                      onClick={() => {
                        setIsSearchOpen(false);
                        setIsMobileMenuOpen(false);
                      }}
                      className="p-2 flex items-center gap-2 hover:bg-[#FAF8F6]"
                    >
                      <div className="flex-1 min-w-0">
                        <h4 className="text-xs font-semibold text-[#1C1917] truncate">{p.title}</h4>
                        <span className="text-[10px] text-stone-500 font-mono">{formatPrice(p.price)}</span>
                      </div>
                    </Link>
                  ))}
                </div>
              )}
            </div>

            {/* Авторизация / Профиль */}
            <div className="p-3.5 border-b border-[#EAE3DE] bg-[#FAF8F6]">
              {isAuthenticated && user ? (
                <Link
                  href="/profile"
                  onClick={() => setIsMobileMenuOpen(false)}
                  className="flex items-center gap-3 p-2 rounded-xl bg-white border border-[#EAE3DE] hover:border-[#1C1917] transition cursor-pointer"
                >
                  <div className="w-8 h-8 rounded-full bg-[#1C1917] text-white flex items-center justify-center text-xs font-serif font-bold">
                    {user.fullName ? user.fullName[0].toUpperCase() : 'M'}
                  </div>
                  <div className="flex-1 truncate">
                    <span className="text-xs font-bold text-[#1C1917] block truncate">
                      {user.fullName || 'Покупатель'}
                    </span>
                    <span className="text-[10px] text-[#8C827A] font-mono">
                      {user.phone}
                    </span>
                  </div>
                </Link>
              ) : (
                <button
                  type="button"
                  onClick={() => {
                    setIsMobileMenuOpen(false);
                    openAuthModal(t('profile.need_login', 'Войдите для доступа к профилю'));
                  }}
                  className="w-full py-2.5 px-3 rounded-xl bg-[#1C1917] text-white text-xs font-semibold flex items-center justify-center gap-2 cursor-pointer"
                >
                  <i className="fa-regular fa-user text-xs" />
                  <span>{t('profile.login_btn', 'Войти в личный кабинет')}</span>
                </button>
              )}
            </div>

            {/* Ссылки категорий */}
            <div className="flex-1 overflow-y-auto p-4 space-y-1">
              <span className="text-[10px] uppercase tracking-wider font-bold text-[#8C827A] px-2 block mb-2">
                {t('nav.categories', 'Категории')}
              </span>
              {categories.map((cat) => (
                <Link
                  key={cat}
                  href={cat === 'Каталог' ? '/catalog' : `/catalog?category=${encodeURIComponent(cat)}`}
                  onClick={() => {
                    if (onSelectCategory) onSelectCategory(cat);
                    setIsMobileMenuOpen(false);
                  }}
                  className="block px-3 py-2 rounded-xl text-xs font-medium text-[#1C1917] hover:bg-[#FAF8F6] transition cursor-pointer"
                >
                  {cat}
                </Link>
              ))}

              <div className="pt-4 mt-4 border-t border-[#EAE3DE] space-y-1">
                <Link
                  href="/track"
                  onClick={() => setIsMobileMenuOpen(false)}
                  className="flex items-center gap-2.5 px-3 py-2 rounded-xl text-xs font-medium text-[#1C1917] hover:bg-[#FAF8F6] cursor-pointer"
                >
                  <i className="fa-solid fa-location-dot text-amber-600 text-xs" />
                  <span>{t('footer.tracking', 'Отследить доставку')}</span>
                </Link>
                <Link
                  href="/returns"
                  onClick={() => setIsMobileMenuOpen(false)}
                  className="flex items-center gap-2.5 px-3 py-2 rounded-xl text-xs font-medium text-[#1C1917] hover:bg-[#FAF8F6] cursor-pointer"
                >
                  <i className="fa-solid fa-shield-halved text-emerald-600 text-xs" />
                  <span>{t('footer.returns', 'Политика и регламент')}</span>
                </Link>
                <a
                  href="/admin"
                  className="flex items-center gap-2.5 px-3 py-2 rounded-xl text-xs font-medium text-[#8C827A] hover:text-black hover:bg-[#FAF8F6] cursor-pointer"
                >
                  <i className="fa-solid fa-gear text-xs" />
                  <span>Панель управления (Admin)</span>
                </a>
              </div>
            </div>
          </div>
        </div>
      )}
    </header>
  );
}
