import React, { useState, useEffect, useCallback, useMemo } from 'react';
import { useSearchParams } from 'react-router-dom';
import Header from '../components/Header';
import Footer from '../components/Footer';
import ProductCard from '../components/ProductCard';
import { INITIAL_PRODUCTS } from '../data/initialProducts';
import { useLanguage } from '../context/LanguageContext';

export default function CatalogPage() {
  const { t, language } = useLanguage();
  const [searchParams] = useSearchParams();

  const [allProducts, setAllProducts] = useState<any[]>(INITIAL_PRODUCTS);
  const [products, setProducts] = useState<any[]>([]);
  const [brands, setBrands] = useState<string[]>([]);
  const [loading, setLoading] = useState(false);

  const initialCat = searchParams.get('category') || 'Каталог';
  const initialBrand = searchParams.get('brand') || 'ALL';
  const initialSearch = searchParams.get('q') || searchParams.get('search') || '';

  const [selectedCategory, setSelectedCategory] = useState(initialCat);
  const [searchQuery, setSearchQuery] = useState(initialSearch);
  const [selectedBrand, setSelectedBrand] = useState(initialBrand);
  const [sortBy, setSortBy] = useState('recommended');

  const [currentPage, setCurrentPage] = useState(1);
  const [pageSize, setPageSize] = useState(18);

  useEffect(() => {
    const cat = searchParams.get('category');
    if (cat) setSelectedCategory(cat);
    const br = searchParams.get('brand');
    if (br) setSelectedBrand(br);
    const q = searchParams.get('q') || searchParams.get('search');
    if (q !== null && q !== undefined) setSearchQuery(q);
  }, [searchParams]);

  const fetchProducts = useCallback(async () => {
    try {
      const res = await fetch(`/api/products?lang=${language}`);
      const data = await res.json();
      const raw = data.products || [];

      setAllProducts(raw);

      const bSet = Array.from(new Set(raw.map((p: any) => {
        const b = p.brand;
        return typeof b === 'object' ? b?.name : b;
      }).filter(Boolean))) as string[];
      setBrands(bSet);
    } catch (e) {
      console.error('Failed to fetch products', e);
    } finally {
      setLoading(false);
    }
  }, [language]);

  useEffect(() => {
    fetchProducts();
  }, [fetchProducts]);

  useEffect(() => {
    let filtered = [...allProducts];

    if (selectedCategory && selectedCategory !== 'Каталог' && selectedCategory !== 'Все товары') {
      if (selectedCategory === 'Скидки') {
        filtered = filtered.filter((p) => p.oldPrice && p.oldPrice > p.price);
      } else {
        filtered = filtered.filter((p) => {
          const cName = typeof p.category === 'object' ? p.category?.name : p.category;
          return cName === selectedCategory;
        });
      }
    }

    if (selectedBrand !== 'ALL') {
      filtered = filtered.filter((p) => {
        const bName = typeof p.brand === 'object' ? p.brand?.name : p.brand;
        return bName === selectedBrand;
      });
    }

    if (searchQuery) {
      const q = searchQuery.toLowerCase().trim();
      filtered = filtered.filter((p) => {
        const bName = (typeof p.brand === 'object' ? p.brand?.name : p.brand) || '';
        const itemTitle = String(p.title || p.name || '').toLowerCase();
        return (
          itemTitle.includes(q) ||
          bName.toLowerCase().includes(q) ||
          (p.sku || '').toLowerCase().includes(q)
        );
      });
    }

    if (sortBy === 'price_asc') filtered.sort((a, b) => a.price - b.price);
    else if (sortBy === 'price_desc') filtered.sort((a, b) => b.price - a.price);
    else if (sortBy === 'newest') filtered.sort((a, b) => (b.isNew ? 1 : 0) - (a.isNew ? 1 : 0));

    setProducts(filtered);
    setCurrentPage(1);
  }, [allProducts, selectedCategory, selectedBrand, sortBy, searchQuery]);

  const totalPages = Math.max(1, Math.ceil(products.length / pageSize));
  const paginatedProducts = useMemo(() => {
    const start = (currentPage - 1) * pageSize;
    return products.slice(start, start + pageSize);
  }, [products, currentPage, pageSize]);

  return (
    <div className="min-h-screen bg-[#FCECEC] text-[#2C2A29] flex flex-col font-sans">
      <Header
        selectedCategory={selectedCategory}
        onSelectCategory={setSelectedCategory}
        searchQuery={searchQuery}
        onSearchChange={setSearchQuery}
      />

      <main className="max-w-[1720px] mx-auto px-4 sm:px-6 lg:px-8 py-8 w-full flex-1">
        <div className="flex flex-col sm:flex-row justify-between items-start sm:items-center pb-6 border-b border-[#EAE3DE] gap-4">
          <div>
            <h1 className="font-serif text-3xl font-bold text-[#1C1917]">
              {selectedCategory === 'Каталог'
                ? t('catalog.title', 'Все товары и наборы')
                : (selectedCategory === 'Все товары' ? t('catalog.all', 'Все товары') : selectedCategory)}
            </h1>
            <p className="text-xs text-[#78716C] mt-1">
              {language === 'uz'
                ? "AQSH va Yevropadan to'g'ridan-to'g'ri original kosmetika, butun O'zbekiston bo'ylab yetkazib berish"
                : (language === 'en'
                  ? "Direct original beauty products from USA and Europe with delivery across Uzbekistan"
                  : "Оригинальная косметика напрямую из США и Европы с доставкой по Узбекистану")}
              {!loading && <span className="ml-2 text-[#a8a29e]">· {products.length} {t('catalog.products', 'товаров')}</span>}
            </p>
          </div>

          <div className="flex flex-wrap items-center gap-3 text-xs">
            <select
              value={selectedBrand}
              onChange={(e) => setSelectedBrand(e.target.value)}
              className="px-3 py-2 bg-white border border-[#E3DBD5] rounded-xl focus:outline-none cursor-pointer"
            >
              <option value="ALL">{t('catalog.all_brands', 'Все бренды')}</option>
              {brands.map((b) => (
                <option key={b} value={b}>{b}</option>
              ))}
            </select>

            <select
              value={sortBy}
              onChange={(e) => setSortBy(e.target.value)}
              className="px-3 py-2 bg-white border border-[#E3DBD5] rounded-xl focus:outline-none font-medium cursor-pointer"
            >
              <option value="recommended">{t('catalog.sort_recommended', 'Рекомендуемые')}</option>
              <option value="newest">{t('catalog.sort_newest', 'Сначала новинки')}</option>
              <option value="price_asc">{t('catalog.sort_price_asc', 'По возрастанию цены')}</option>
              <option value="price_desc">{t('catalog.sort_price_desc', 'По убыванию цены')}</option>
            </select>

            <select
              value={pageSize}
              onChange={(e) => {
                setPageSize(Number(e.target.value));
                setCurrentPage(1);
              }}
              className="px-2.5 py-2 bg-white border border-[#E3DBD5] rounded-xl focus:outline-none text-[11px] cursor-pointer"
              title="Показывать по"
            >
              <option value={18}>18 / стр.</option>
              <option value={36}>36 / стр.</option>
              <option value={72}>72 / стр.</option>
            </select>
          </div>
        </div>

        {/* Сетка товаров с адаптивным увеличением на больших экранах */}
        {loading ? (
          <div className="py-24 text-center text-[#78716C] space-y-3">
            <i className="fa-solid fa-spinner fa-spin text-3xl text-[#1C1917]" />
            <p className="text-xs font-medium">Загрузка каталога оригинальной косметики...</p>
          </div>
        ) : products.length === 0 ? (
          <div className="bg-white rounded-3xl p-16 text-center max-w-md mx-auto my-12 border border-[#EAE3DE] space-y-4">
            <div className="w-16 h-16 mx-auto rounded-full bg-[#FAF7F5] flex items-center justify-center text-2xl text-[#8C827A]">
              <i className="fa-solid fa-magnifying-glass" />
            </div>
            <h3 className="font-serif text-xl font-bold">{t('catalog.not_found', 'Товары не найдены')}</h3>
            <p className="text-xs text-[#78716C]">
              Попробуйте изменить запрос или сбросить фильтры
            </p>
            <button
              type="button"
              onClick={() => {
                setSelectedCategory('Каталог');
                setSelectedBrand('ALL');
                setSearchQuery('');
              }}
              className="px-6 py-2.5 bg-[#1C1917] text-white rounded-xl text-xs font-semibold hover:bg-black transition cursor-pointer"
            >
              {t('catalog.reset_filters', 'Сбросить фильтры')}
            </button>
          </div>
        ) : (
          <>
            <div className="grid grid-cols-2 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-4 xl:grid-cols-5 2xl:grid-cols-6 gap-3 sm:gap-4 lg:gap-5 mt-6">
              {paginatedProducts.map((p) => (
                <ProductCard
                  key={p.id || p.sku}
                  product={p}
                />
              ))}
            </div>

            {/* Пагинация */}
            {totalPages > 1 && (
              <div className="flex items-center justify-center gap-2 mt-12">
                <button
                  type="button"
                  onClick={() => setCurrentPage((p) => Math.max(1, p - 1))}
                  disabled={currentPage === 1}
                  className="px-3 py-2 rounded-xl border border-[#EAE3DE] bg-white text-xs font-medium disabled:opacity-40 cursor-pointer"
                >
                  ←
                </button>
                {Array.from({ length: totalPages }).map((_, i) => {
                  const pNum = i + 1;
                  return (
                    <button
                      key={pNum}
                      type="button"
                      onClick={() => setCurrentPage(pNum)}
                      className={`w-9 h-9 rounded-xl text-xs font-semibold transition cursor-pointer ${
                        currentPage === pNum
                          ? 'bg-[#1C1917] text-white shadow-sm'
                          : 'bg-white border border-[#EAE3DE] text-[#1C1917] hover:border-[#1C1917]'
                      }`}
                    >
                      {pNum}
                    </button>
                  );
                })}
                <button
                  type="button"
                  onClick={() => setCurrentPage((p) => Math.min(totalPages, p + 1))}
                  disabled={currentPage === totalPages}
                  className="px-3 py-2 rounded-xl border border-[#EAE3DE] bg-white text-xs font-medium disabled:opacity-40 cursor-pointer"
                >
                  →
                </button>
              </div>
            )}
          </>
        )}
      </main>

      <Footer />
    </div>
  );
}
