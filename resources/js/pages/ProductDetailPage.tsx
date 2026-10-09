import React, { useState, useEffect, useCallback, useMemo } from 'react';
import { useParams, useNavigate } from 'react-router-dom';
import Link from '../components/Link';
import Header from '../components/Header';
import Footer from '../components/Footer';
import ProductCard from '../components/ProductCard';
import { useCart } from '../context/CartContext';
import { useFavorites } from '../context/FavoritesContext';
import { useToast } from '../context/ToastContext';
import { useLanguage } from '../context/LanguageContext';
import CopyableBadge from '../components/ui/CopyableBadge';
import MarkdownRenderer from '../components/ui/MarkdownRenderer';
import { INITIAL_PRODUCTS } from '../data/initialProducts';
import { formatUSD } from '../shared/formatters';

export default function ProductDetailPage() {
  const { slug } = useParams();
  const navigate = useNavigate();
  const { addToCart, updateQuantity, getItemQuantity, formatPrice, currency } = useCart();
  const { isFavorite, toggleFavorite } = useFavorites();
  const { showToast } = useToast();
  const { t, language } = useLanguage();

  const [product, setProduct] = useState<any>(null);
  const [allProductsList, setAllProductsList] = useState<any[]>(INITIAL_PRODUCTS);
  const [loading, setLoading] = useState(true);
  const [activeImageIndex, setActiveImageIndex] = useState(0);
  const [isZoomOpen, setIsZoomOpen] = useState(false);

  // 1. Поиск товара: СТРОГО по Артикулу (SKU) в первую очередь
  useEffect(() => {
    let rawQuery = slug || '';
    try {
      rawQuery = decodeURIComponent(rawQuery).trim();
    } catch {
      rawQuery = (slug || '').trim();
    }

    const cleanTarget = rawQuery.toLowerCase();

    // Поиск в локальном каталоге
    const localMatch = INITIAL_PRODUCTS.find((p: any) => {
      const pSku = String(p.sku || '').trim().toLowerCase();
      const pId = String(p.id || '').trim().toLowerCase();
      const pSlug = String(p.slug || '').trim().toLowerCase();
      const pName = String(p.name || p.title || '').trim().toLowerCase();

      // 1-й приоритет: точное совпадение SKU (артикула)
      if (pSku && pSku === cleanTarget) return true;
      // 2-й приоритет: артикул без дефисов / спецсимволов
      if (pSku && pSku.replace(/[^a-z0-9]/gi, '') === cleanTarget.replace(/[^a-z0-9]/gi, '')) return true;
      // 3-й приоритет: ID товара
      if (pId === cleanTarget || `ml-p-${pId}` === cleanTarget) return true;
      // 4-й приоритет: старый slug или название
      if (pSlug && pSlug === cleanTarget) return true;
      if (pName && (pName === cleanTarget || cleanTarget.includes(pName) || pName.includes(cleanTarget))) return true;

      return false;
    });

    if (localMatch) {
      setProduct(localMatch);
      setActiveImageIndex(0);
      setLoading(false);
    } else if (INITIAL_PRODUCTS.length > 0) {
      setProduct(INITIAL_PRODUCTS[0]);
      setActiveImageIndex(0);
      setLoading(false);
    }

    // Фоновый запрос к API бэкенда для синхронизации свежих данных
    fetch(`/api/products/${encodeURIComponent(rawQuery)}`)
      .then((res) => {
        if (!res.ok) throw new Error('API 404');
        return res.json();
      })
      .then((data) => {
        if (data.product) {
          setProduct(data.product);
        }
      })
      .catch((err) => {
        console.warn('Backend sync note:', err);
      })
      .finally(() => {
        setLoading(false);
      });

    // Загрузка всех товаров для умных тематических рекомендаций
    fetch('/api/products')
      .then((r) => r.json())
      .then((d) => {
        if (d.products && Array.isArray(d.products) && d.products.length > 0) {
          setAllProductsList(d.products);
        }
      })
      .catch(() => {});
  }, [slug]);

  // Данные текущего товара
  const currentProduct = product || INITIAL_PRODUCTS[0] || {};
  const prodKey = String(currentProduct.sku || currentProduct.id || 'ML-ITEM');
  const title = currentProduct.title || currentProduct.name || 'Косметика Mally';
  const qtyInCart = getItemQuantity(prodKey);

  // Обработка изображений
  const rawImages = Array.isArray(currentProduct.images) && currentProduct.images.length > 0
    ? currentProduct.images
    : (typeof currentProduct.images === 'string'
      ? (() => {
          try {
            const parsed = JSON.parse(currentProduct.images);
            return Array.isArray(parsed) && parsed.length > 0 ? parsed : [currentProduct.images];
          } catch {
            return [currentProduct.images];
          }
        })()
      : (currentProduct.image ? [currentProduct.image] : ['https://findy.uz/cdn/shop/files/sample.jpg']));
  const images: string[] = rawImages.filter(Boolean);

  // Навигация по галерее стрелками (вперед / назад)
  const handlePrevImage = useCallback((e?: React.MouseEvent) => {
    if (e) e.stopPropagation();
    if (images.length <= 1) return;
    setActiveImageIndex((prev) => (prev === 0 ? images.length - 1 : prev - 1));
  }, [images.length]);

  const handleNextImage = useCallback((e?: React.MouseEvent) => {
    if (e) e.stopPropagation();
    if (images.length <= 1) return;
    setActiveImageIndex((prev) => (prev === images.length - 1 ? 0 : prev + 1));
  }, [images.length]);

  // Подключение клавиш клавиатуры: Стрелки влево / вправо для фото, Escape для зума
  useEffect(() => {
    const handleKeyDown = (e: KeyboardEvent) => {
      const target = e.target as HTMLElement;
      if (target && (target.tagName === 'INPUT' || target.tagName === 'TEXTAREA' || target.isContentEditable)) {
        return;
      }

      if (e.key === 'ArrowLeft') {
        e.preventDefault();
        handlePrevImage();
      } else if (e.key === 'ArrowRight') {
        e.preventDefault();
        handleNextImage();
      } else if (e.key === 'Escape' && isZoomOpen) {
        e.preventDefault();
        setIsZoomOpen(false);
      }
    };

    window.addEventListener('keydown', handleKeyDown);
    return () => window.removeEventListener('keydown', handleKeyDown);
  }, [handlePrevImage, handleNextImage, isZoomOpen]);

  // Расчет логики полосы остатка на складе за текущий месяц (100% = максимальная партия месяца)
  const rawQuantity = currentProduct.quantity;
  const stockCount = typeof rawQuantity === 'number' ? rawQuantity : 12;
  const isOutOfStock = currentProduct.status === 'OUT_OF_STOCK' || currentProduct.status === 'out_of_stock' || stockCount <= 0;

  // 100% — максимальное поступление партии текущего месяца (по умолчанию партия 30 или 40 шт. либо расчетная)
  const monthlyIntake = currentProduct.monthly_batch || (stockCount > 0 ? Math.max(25, Math.ceil((stockCount * 2.3) / 5) * 5) : 30);
  const soldCount = Math.max(0, monthlyIntake - stockCount);
  const remainingPercent = isOutOfStock ? 0 : Math.min(100, Math.max(8, Math.round((stockCount / monthlyIntake) * 100)));
  const soldPercent = 100 - remainingPercent;

  // Цветовая градация полосы остатка
  const isLowStock = stockCount > 0 && remainingPercent <= 25;
  const isMediumStock = stockCount > 0 && remainingPercent > 25 && remainingPercent <= 50;
  const stockBarColor = isOutOfStock
    ? 'bg-stone-300'
    : isLowStock
      ? 'bg-gradient-to-r from-rose-500 to-rose-600'
      : isMediumStock
        ? 'bg-gradient-to-r from-amber-500 to-amber-600'
        : 'bg-gradient-to-r from-emerald-500 to-emerald-600';

  // Цены строго в USD ($)
  const rawPrice = currentProduct.price ?? currentProduct.selling_price ?? 35;
  const price = typeof rawPrice === 'number' && rawPrice > 1000 ? rawPrice / 12850 : Number(rawPrice) || 35;

  const rawOldPrice = currentProduct.oldPrice ?? currentProduct.old_price ?? null;
  const oldPrice = rawOldPrice ? (typeof rawOldPrice === 'number' && rawOldPrice > 1000 ? rawOldPrice / 12850 : Number(rawOldPrice)) : null;

  const discountPercent =
    oldPrice && oldPrice > price
      ? Math.round(((oldPrice - price) / oldPrice) * 100)
      : null;

  const brandName = typeof currentProduct.brand === 'object' && currentProduct.brand !== null
    ? currentProduct.brand?.name
    : (currentProduct.brand || 'Mally Beauty');

  const categoryName = typeof currentProduct.category === 'object' && currentProduct.category !== null
    ? (currentProduct.category?.name_ru || currentProduct.category?.name)
    : (currentProduct.category || 'Каталог');

  const skuCode = currentProduct.sku || prodKey;
  const favorited = isFavorite(prodKey);

  // УМНЫЕ РЕКОМЕНДАЦИИ: 1) тот же бренд, 2) та же категория, 3) исключаем текущий товар
  const recommendedProducts = useMemo(() => {
    const list = allProductsList.length > 0 ? allProductsList : INITIAL_PRODUCTS;
    const curSku = String(currentProduct.sku || currentProduct.id || '').toLowerCase();

    // Исключаем текущий товар
    const available = list.filter((p: any) => {
      const pSku = String(p.sku || p.id || '').toLowerCase();
      return pSku !== curSku;
    });

    // 1. Приоритет: тот же бренд
    const sameBrand = available.filter((p: any) => {
      const bName = typeof p.brand === 'object' ? p.brand?.name : p.brand;
      return String(bName || '').toLowerCase() === String(brandName).toLowerCase();
    });

    // 2. Приоритет: та же категория
    const sameCategory = available.filter((p: any) => {
      const cName = typeof p.category === 'object' ? (p.category?.name_ru || p.category?.name) : p.category;
      return String(cName || '').toLowerCase() === String(categoryName).toLowerCase() && !sameBrand.includes(p);
    });

    // 3. Остальные популярные товары
    const others = available.filter((p: any) => !sameBrand.includes(p) && !sameCategory.includes(p));

    const result = [...sameBrand, ...sameCategory, ...others].slice(0, 4);
    return result;
  }, [allProductsList, currentProduct, brandName, categoryName]);

  if (loading) {
    return (
      <div className="min-h-screen bg-[#FCECEC] flex flex-col font-sans">
        <Header />
        <div className="max-w-6xl mx-auto px-4 py-24 flex-1 flex flex-col items-center justify-center space-y-3">
          <i className="fa-solid fa-spinner fa-spin text-3xl text-[#1C1917]" />
          <p className="text-xs text-[#78716C]">Загрузка косметики Mally...</p>
        </div>
      </div>
    );
  }

  return (
    <div className="min-h-screen bg-[#FCECEC] text-[#2C2A29] flex flex-col font-sans">
      <Header />

      {/* Хлебные крошки со строгим Артикулом (SKU) */}
      <div className="max-w-7xl mx-auto px-4 py-4 w-full text-xs text-[#8C827A] flex items-center gap-2">
        <Link href="/" className="hover:text-black transition">Главная</Link>
        <span>/</span>
        <Link href="/catalog" className="hover:text-black transition">{categoryName || 'Каталог'}</Link>
        <span>/</span>
        <span className="font-mono text-[#1C1917] font-semibold">{skuCode}</span>
        <span>•</span>
        <span className="text-[#57534E] truncate max-w-xs">{title}</span>
      </div>

      <main className="max-w-7xl mx-auto px-4 py-6 w-full flex-1 space-y-12">
        {/* ВЕРХНЯЯ ЧАСТЬ: Фотогалерея и Панель Покупки */}
        <div className="grid grid-cols-1 lg:grid-cols-12 gap-8 lg:gap-12 items-start">
          
          {/* Левая колонка: Большая интерактивная галерея со стрелками фото */}
          <div className="lg:col-span-6 space-y-4">
            <div className="relative aspect-square w-full rounded-3xl overflow-hidden bg-white border border-[#EAE3DE] shadow-sm flex items-center justify-center select-none group">
              <img
                src={images[activeImageIndex] || images[0] || 'https://findy.uz/cdn/shop/files/sample.jpg'}
                alt={title}
                className="w-full h-full object-cover cursor-zoom-in transition-transform duration-500 group-hover:scale-[1.03]"
                onClick={() => setIsZoomOpen(true)}
                onError={(e) => {
                  (e.target as HTMLImageElement).src = 'https://findy.uz/cdn/shop/files/sample.jpg';
                }}
              />

              {/* Бейдж скидки */}
              {discountPercent && (
                <span className="absolute top-4 left-4 px-3 py-1 bg-[#E11D48] text-white text-xs font-bold rounded-full shadow-md z-10">
                  -{discountPercent}%
                </span>
              )}

              {/* Счетчик фото (например, 1 / 4) */}
              {images.length > 1 && (
                <div className="absolute top-4 left-1/2 -translate-x-1/2 px-3 py-1 bg-black/60 backdrop-blur-md text-white text-[11px] font-mono font-medium rounded-full z-10 pointer-events-none">
                  {activeImageIndex + 1} / {images.length}
                </div>
              )}

              {/* Кнопка "В избранное" */}
              <button
                type="button"
                onClick={(e) => {
                  e.stopPropagation();
                  toggleFavorite({
                    id: prodKey,
                    sku: skuCode,
                    title: title,
                    price: price,
                    oldPrice: oldPrice,
                    image: images[0],
                    brand: brandName,
                    inStock: !isOutOfStock,
                  });
                }}
                className={`absolute top-4 right-4 z-10 w-11 h-11 rounded-full flex items-center justify-center transition shadow-md cursor-pointer ${
                  favorited
                    ? 'bg-rose-50 text-rose-600 border border-rose-200'
                    : 'bg-white/90 backdrop-blur-sm text-neutral-600 hover:text-rose-600 hover:bg-white border border-neutral-200'
                }`}
                title={favorited ? 'В избранном' : 'Добавить в избранное'}
              >
                <i className={`${favorited ? 'fa-solid text-rose-500' : 'fa-regular'} fa-heart text-base`} />
              </button>

              {/* БОЛЬШИЕ КОМФОРТНЫЕ СТРЕЛКИ НАВИГАЦИИ ПО ФОТО (Слева и Справа) */}
              {images.length > 1 && (
                <>
                  <button
                    type="button"
                    onClick={handlePrevImage}
                    className="absolute left-3 top-1/2 -translate-y-1/2 z-20 w-12 h-12 rounded-full bg-white/90 hover:bg-white text-[#1C1917] hover:scale-110 shadow-lg border border-stone-200/80 backdrop-blur-md flex items-center justify-center transition cursor-pointer"
                    title="Предыдущее фото"
                    aria-label="Предыдущее фото"
                  >
                    <i className="fa-solid fa-chevron-left text-base" />
                  </button>

                  <button
                    type="button"
                    onClick={handleNextImage}
                    className="absolute right-3 top-1/2 -translate-y-1/2 z-20 w-12 h-12 rounded-full bg-white/90 hover:bg-white text-[#1C1917] hover:scale-110 shadow-lg border border-stone-200/80 backdrop-blur-md flex items-center justify-center transition cursor-pointer"
                    title="Следующее фото"
                    aria-label="Следующее фото"
                  >
                    <i className="fa-solid fa-chevron-right text-base" />
                  </button>
                </>
              )}
            </div>

            {/* Миниатюры с крупными комфортными карточками */}
            {images.length > 1 && (
              <div className="flex gap-3 overflow-x-auto pb-2 scrollbar-none">
                {images.map((img: string, idx: number) => (
                  <button
                    key={idx}
                    type="button"
                    onClick={() => setActiveImageIndex(idx)}
                    className={`relative w-20 h-20 rounded-2xl overflow-hidden border-2 flex-shrink-0 transition-all cursor-pointer ${
                      activeImageIndex === idx
                        ? 'border-[#1C1917] shadow-md ring-2 ring-[#1C1917]/20 scale-105'
                        : 'border-[#EAE3DE] opacity-70 hover:opacity-100 hover:border-stone-400'
                    }`}
                    title={`Фото ${idx + 1}`}
                  >
                    <img src={img} alt={`${title} - вид ${idx + 1}`} className="w-full h-full object-cover" />
                  </button>
                ))}
              </div>
            )}
          </div>

          {/* Правая колонка: Основные детали, наличие на складе с полосой партии, цена и покупка */}
          <div className="lg:col-span-6 space-y-6">
            
            {/* Бренд, Категория, Название и Артикул */}
            <div>
              <div className="flex items-center justify-between gap-3">
                <span className="text-xs uppercase tracking-widest text-[#8C827A] font-bold">
                  {brandName}
                </span>
                <span className="text-xs text-[#8C827A] bg-stone-100 px-2.5 py-0.5 rounded-full font-medium">
                  {categoryName}
                </span>
              </div>

              <h1 className="font-serif text-2xl sm:text-3xl lg:text-4xl font-bold text-[#1C1917] mt-2 leading-snug">
                {title}
              </h1>

              {/* Строгий Артикул (SKU) с функцией копирования */}
              <div className="flex items-center gap-2 mt-3 pt-2 border-t border-[#EAE3DE]">
                <span className="text-xs text-[#8C827A]">{t('product.sku_label', 'Артикул товара')}:</span>
                <CopyableBadge text={skuCode} prefix="" size="sm" />
              </div>
            </div>

            {/* КРАСИВАЯ ПОЛОСКА НАЛИЧИЯ: Партия этого месяца, сколько поступило, сколько осталось и ушло */}
            <div className="p-5 bg-white rounded-3xl border border-[#EAE3DE] shadow-xs space-y-3">
              <div className="flex items-center justify-between text-xs sm:text-sm">
                <div className="flex items-center gap-2">
                  <span className={`w-2.5 h-2.5 rounded-full ${isOutOfStock ? 'bg-stone-400' : isLowStock ? 'bg-rose-500 animate-pulse' : 'bg-emerald-500'}`} />
                  <span className="font-bold text-[#1C1917]">
                    {isOutOfStock ? (
                      t('product.batch_sold_out', 'Партия полностью распродана')
                    ) : (
                      <>
                        {t('product.in_stock', 'В наличии')}:{' '}
                        <span className="text-emerald-700 font-extrabold text-sm sm:text-base">
                          {stockCount} {t('product.pcs', 'шт.')}
                        </span>
                        <span className="text-[#8C827A] font-normal text-xs ml-1.5">
                          {t('product.of_total', 'из')} {monthlyIntake} {t('product.pcs', 'шт.')}
                        </span>
                      </>
                    )}
                  </span>
                </div>

                <span className="text-[11px] font-mono font-semibold px-2.5 py-1 rounded-full bg-stone-100 text-[#57534E]">
                  {isOutOfStock ? '0%' : `${t('product.remaining', 'Осталось')} ${remainingPercent}%`}
                </span>
              </div>

              {/* Аккуратная плавная полоска наличия */}
              <div className="w-full h-2.5 bg-stone-100 rounded-full overflow-hidden p-0.5 border border-stone-200">
                <div
                  className={`h-full rounded-full transition-all duration-700 ease-out ${stockBarColor}`}
                  style={{ width: `${isOutOfStock ? 0 : remainingPercent}%` }}
                />
              </div>

              {/* Данные по движению партии за месяц */}
              <div className="flex justify-between items-center text-[11px] text-[#8C827A] pt-0.5">
                <span>
                  {isOutOfStock ? t('product.awaiting_stock', 'Ожидается новое поступление') : `${t('product.sold_month', 'Продано за месяц')}: ${soldCount} ${t('product.pcs', 'шт.')} (${soldPercent}%)`}
                </span>
                <span className="text-[#1C1917] font-medium">{t('product.month_batch', 'Партия этого месяца')}</span>
              </div>
            </div>

            {/* Цены: динамически переключаются между USD ($) и UZS (сум) */}
            <div className="p-5 bg-white rounded-3xl border border-[#EAE3DE] flex items-baseline justify-between shadow-xs">
              <div>
                <span className="text-[11px] text-[#8C827A] uppercase tracking-wider block font-semibold mb-1">
                  {currency === 'UZS' ? t('product.price_uzs', 'Стоимость в сумах (UZS)') : t('product.price_usd', 'Стоимость в долларах США ($)')}
                </span>
                <div className="flex items-baseline gap-3">
                  <span className={`text-3xl sm:text-4xl font-bold font-serif ${discountPercent ? 'text-[#E11D48]' : 'text-[#1C1917]'}`}>
                    {formatPrice(price)}
                  </span>
                  {oldPrice && oldPrice > price && (
                    <span className="text-base text-[#A8A29E] line-through font-serif">
                      {formatPrice(oldPrice)}
                    </span>
                  )}
                </div>
              </div>

              {discountPercent && (
                <div className="text-right">
                  <span className="inline-block px-3 py-1 bg-rose-50 text-[#E11D48] text-xs font-bold rounded-full border border-rose-200">
                    {t('product.savings', 'Экономия')} {formatPrice(oldPrice! - price)}
                  </span>
                </div>
              )}
            </div>

            {/* Кнопка добавления в корзину и Telegram-заказ */}
            <div className="space-y-3">
              {isOutOfStock ? (
                <div className="space-y-3">
                  <a
                    href={`https://t.me/mallyuz?text=${encodeURIComponent(
                      `Здравствуйте! Хочу оформить индивидуальный заказ из США (Sephora) на товар: ${title} (Артикул: ${skuCode}, Цена: ${formatPrice(price)}).`
                    )}`}
                    target="_blank"
                    rel="noreferrer"
                    className="w-full py-4 px-6 bg-gradient-to-r from-amber-600 to-amber-700 hover:from-amber-700 hover:to-amber-800 text-white text-xs font-bold uppercase tracking-wider rounded-2xl shadow-md hover:shadow-lg transition flex items-center justify-center gap-2.5 cursor-pointer"
                  >
                    <i className="fa-solid fa-plane-departure text-sm" />
                    <span>{t('product.order_tg', 'Заказать байером из США в Telegram')}</span>
                  </a>
                  <p className="text-[11px] text-[#78716C] text-center">
                    {t('product.tg_note', 'Выкупаем напрямую в Sephora / Ulta USA по фиксированной стоимости')}
                  </p>
                </div>
              ) : qtyInCart > 0 ? (
                <div className="space-y-2 w-full">
                  <div className="flex flex-col sm:flex-row items-stretch sm:items-center gap-3 w-full">
                    <div className="py-3 px-5 rounded-2xl bg-[#1C1917] text-white flex items-center justify-between shadow-md flex-1">
                      <button
                        type="button"
                        onClick={() => updateQuantity(prodKey, qtyInCart - 1)}
                        className="w-9 h-9 rounded-xl bg-white/10 hover:bg-white/20 flex items-center justify-center text-lg font-bold transition cursor-pointer"
                        title="Уменьшить количество"
                      >
                        −
                      </button>
                      <div className="flex items-center gap-2">
                        <span className="text-base font-bold font-mono">{qtyInCart}</span>
                        <span className="text-xs text-[#D5CCC7]">{t('product.in_cart', 'в корзине')}</span>
                      </div>
                      <button
                        type="button"
                        onClick={() => {
                          if (qtyInCart >= stockCount) {
                            showToast(`Максимум в наличии на складе: ${stockCount} шт.`, 'warning');
                            return;
                          }
                          updateQuantity(prodKey, qtyInCart + 1);
                        }}
                        disabled={qtyInCart >= stockCount}
                        className={`w-9 h-9 rounded-xl flex items-center justify-center text-lg font-bold transition ${
                          qtyInCart >= stockCount
                            ? 'bg-white/5 text-stone-500 opacity-40 cursor-not-allowed'
                            : 'bg-white/10 hover:bg-white/20 text-white cursor-pointer'
                        }`}
                        title={qtyInCart >= stockCount ? `Достигнут максимум на складе (${stockCount} шт.)` : "Увеличить количество"}
                      >
                        +
                      </button>
                    </div>

                    <Link
                      href="/cart"
                      className="py-4 px-6 rounded-2xl bg-[#FAF7F5] hover:bg-[#EAE3DE] text-[#1C1917] border border-[#EAE3DE] hover:border-[#1C1917] text-xs font-bold transition flex items-center justify-center gap-2 shadow-sm whitespace-nowrap cursor-pointer"
                    >
                      <i className="fa-solid fa-bag-shopping text-sm" />
                      <span>{t('cart.checkout', 'Перейти к оформлению')}</span>
                    </Link>
                  </div>

                  {qtyInCart >= stockCount && (
                    <div className="text-[11px] text-amber-800 bg-amber-50 px-3 py-1.5 rounded-xl border border-amber-200/60 flex items-center gap-1.5">
                      <i className="fa-solid fa-triangle-exclamation text-amber-600" />
                      <span>{t('product.max_stock_selected', 'Выбрано максимальное количество товара на складе')} ({stockCount} {t('product.pcs', 'шт.')})</span>
                    </div>
                  )}
                </div>
              ) : (
                <div className="flex flex-col sm:flex-row gap-3">
                  <button
                    type="button"
                    onClick={() => {
                      if (stockCount <= 0) {
                        showToast('Товар распродан на складе', 'warning');
                        return;
                      }
                      addToCart(currentProduct, true);
                      showToast(`Товар ${title} добавлен в корзину`);
                    }}
                    className="flex-1 py-4 px-6 bg-[#1C1917] hover:bg-black text-white text-xs font-bold uppercase tracking-wider rounded-2xl shadow-lg transition active:scale-[0.99] flex items-center justify-center gap-2.5 cursor-pointer"
                  >
                    <i className="fa-solid fa-bag-shopping text-sm" />
                    <span>{t('product.add_to_cart', 'Добавить в корзину')} ({formatPrice(price)})</span>
                  </button>

                  <a
                    href={`https://t.me/mallyuz?text=${encodeURIComponent(
                      `Здравствуйте! Хочу заказать товар: ${title} (Артикул: ${skuCode}, Цена: ${formatPrice(price)}).`
                    )}`}
                    target="_blank"
                    rel="noreferrer"
                    className="py-4 px-5 bg-white hover:bg-stone-50 text-[#1C1917] border border-[#EAE3DE] text-xs font-bold rounded-2xl shadow-sm transition flex items-center justify-center gap-2 cursor-pointer"
                    title="Заказать через менеджера Telegram"
                  >
                    <i className="fa-brands fa-telegram text-[#229ED9] text-base" />
                    <span>{t('product.buy_tg', 'Купить в Telegram')}</span>
                  </a>
                </div>
              )}
            </div>

            {/* Краткие маркеры гарантий */}
            {/* Краткие маркеры гарантий */}
            <div className="pt-2 grid grid-cols-2 gap-3 text-xs text-[#57534E]">
              <div className="flex items-center gap-2 p-3 bg-white/60 rounded-2xl border border-stone-200/60">
                <i className="fa-solid fa-shield-check text-emerald-600 text-sm" />
                <span className="font-medium text-[#1C1917]">{t('product.usa_origin', '100% Оригинал из США')}</span>
              </div>
              <div className="flex items-center gap-2 p-3 bg-white/60 rounded-2xl border border-stone-200/60">
                <i className="fa-solid fa-sparkles text-[#96674c] text-sm" />
                <span className="font-medium text-[#1C1917]">{t('product.batch_verified', 'Батч-код проверяется')}</span>
              </div>
            </div>

          </div>
        </div>

        {/* НИЖНЯЯ ПОЛНОРАЗМЕРНАЯ ЛЕНТА ВСЕХ БЛОКОВ ОПИСАНИЯ */}
        <div className="space-y-8 pt-6">

          {/* БЛОК 1: Подробное описание и текстура */}
          <section className="bg-white rounded-3xl p-6 sm:p-8 border border-[#EAE3DE] shadow-xs space-y-5">
            <div className="flex items-center gap-3 pb-3 border-b border-[#EAE3DE]">
              <div className="w-9 h-9 rounded-xl bg-[#FAF7F5] text-[#1C1917] flex items-center justify-center font-bold">
                <i className="fa-solid fa-feather text-base" />
              </div>
              <div>
                <h2 className="font-serif text-xl sm:text-2xl font-bold text-[#1C1917]">
                  {t('product.desc_texture', 'Описание и текстура')}
                </h2>
                <p className="text-xs text-[#8C827A]">
                  {t('product.desc_subtitle', 'Особенности текстуры, формула и эффект от применения')}
                </p>
              </div>
            </div>

            <div className="text-sm sm:text-base text-[#2C2A29] font-serif leading-relaxed">
              <MarkdownRenderer
                content={currentProduct.description || '100% оригинальная косметика от официальных ритейлеров США (Sephora, Ulta). Проверено экспертами Mally.'}
              />
            </div>

            {currentProduct.features && currentProduct.features.length > 0 && (
              <div className="pt-2 space-y-3">
                <h3 className="text-xs font-bold uppercase tracking-wider text-[#1C1917]">
                  {t('product.key_benefits', 'Ключевые преимущества формулы:')}
                </h3>
                <div className="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-3">
                  {currentProduct.features.map((f: string, i: number) => (
                    <div key={i} className="flex items-start gap-2.5 bg-[#FAF7F5] p-3 rounded-2xl border border-stone-200/60 text-xs text-[#57534E]">
                      <i className="fa-solid fa-circle-check text-emerald-600 mt-0.5" />
                      <span className="font-medium">{f}</span>
                    </div>
                  ))}
                </div>
              </div>
            )}
          </section>

          {/* БЛОК 2: Состав и активные компоненты */}
          <section className="bg-white rounded-3xl p-6 sm:p-8 border border-[#EAE3DE] shadow-xs space-y-5">
            <div className="flex items-center gap-3 pb-3 border-b border-[#EAE3DE]">
              <div className="w-9 h-9 rounded-xl bg-emerald-50 text-emerald-700 flex items-center justify-center font-bold">
                <i className="fa-solid fa-leaf text-base" />
              </div>
              <div>
                <h2 className="font-serif text-xl sm:text-2xl font-bold text-[#1C1917]">
                  {t('product.ingredients_title', 'Состав и компоненты')}
                </h2>
                <p className="text-xs text-[#8C827A]">
                  {t('product.ingredients_subtitle', 'Стандарты чистоты Clean Beauty, безопасность и активные вещества')}
                </p>
              </div>
            </div>

            <div className="grid grid-cols-1 sm:grid-cols-3 gap-3 text-xs">
              <div className="p-3.5 bg-emerald-50/50 rounded-2xl border border-emerald-100 flex items-center gap-3">
                <i className="fa-solid fa-check text-emerald-600 text-base" />
                <div>
                  <div className="font-bold text-emerald-900">Cruelty-Free</div>
                  <div className="text-[10px] text-emerald-700">Не тестируется на животных</div>
                </div>
              </div>
              <div className="p-3.5 bg-emerald-50/50 rounded-2xl border border-emerald-100 flex items-center gap-3">
                <i className="fa-solid fa-check text-emerald-600 text-base" />
                <div>
                  <div className="font-bold text-emerald-900">Paraben-Free</div>
                  <div className="text-[10px] text-emerald-700">Без парабенов и SLS</div>
                </div>
              </div>
              <div className="p-3.5 bg-emerald-50/50 rounded-2xl border border-emerald-100 flex items-center gap-3">
                <i className="fa-solid fa-check text-emerald-600 text-base" />
                <div>
                  <div className="font-bold text-emerald-900">Clean Formula</div>
                  <div className="text-[10px] text-emerald-700">Гипоаллергенный состав</div>
                </div>
              </div>
            </div>

            <div className="p-4 bg-[#FAF7F5] rounded-2xl border border-stone-200/60 font-mono text-[11px] text-[#57534E] leading-relaxed">
              <span className="font-bold font-sans text-xs text-[#1C1917] block mb-1">Полный список ингредиентов (INCI):</span>
              Water/Aqua/Eau, Glycerin, Caprylic/Capric Triglyceride, Niacinamide, Ceramide NP, Ceramide AP, Ceramide EOP, Sodium Hyaluronate, Tocopherol (Vitamin E), Palmitoyl Tripeptide-1, Palmitoyl Tetrapeptide-7, Squalane, Butylene Glycol, Ethylhexylglycerin, Phenoxyethanol.
            </div>
          </section>

          {/* БЛОК 3: Полные характеристики товара */}
          <section className="bg-white rounded-3xl p-6 sm:p-8 border border-[#EAE3DE] shadow-xs space-y-5">
            <div className="flex items-center gap-3 pb-3 border-b border-[#EAE3DE]">
              <div className="w-9 h-9 rounded-xl bg-amber-50 text-amber-800 flex items-center justify-center font-bold">
                <i className="fa-solid fa-list-check text-base" />
              </div>
              <div>
                <h2 className="font-serif text-xl sm:text-2xl font-bold text-[#1C1917]">
                  {t('product.specs_title', 'Характеристики товара')}
                </h2>
                <p className="text-xs text-[#8C827A]">
                  Паспорт оригинального продукта и данные партии
                </p>
              </div>
            </div>

            <div className="overflow-x-auto">
              <table className="w-full text-xs">
                <tbody className="divide-y divide-stone-100">
                  <tr className="hover:bg-stone-50/60 transition-colors">
                    <td className="py-3 px-2 text-[#8C827A] font-medium w-1/3 sm:w-1/4">{t('product.sku_label', 'Артикул (SKU)')}</td>
                    <td className="py-3 px-2 text-[#1C1917] font-mono font-bold">
                      <CopyableBadge text={skuCode} prefix="" size="sm" />
                    </td>
                  </tr>
                  <tr className="hover:bg-stone-50/60 transition-colors">
                    <td className="py-3 px-2 text-[#8C827A] font-medium">{t('product.brand', 'Бренд')}</td>
                    <td className="py-3 px-2 text-[#1C1917] font-bold">{brandName}</td>
                  </tr>
                  <tr className="hover:bg-stone-50/60 transition-colors">
                    <td className="py-3 px-2 text-[#8C827A] font-medium">{t('product.category', 'Категория')}</td>
                    <td className="py-3 px-2 text-[#1C1917]">{categoryName}</td>
                  </tr>
                  <tr className="hover:bg-stone-50/60 transition-colors">
                    <td className="py-3 px-2 text-[#8C827A] font-medium">{t('product.origin_country', 'Страна закупки')}</td>
                    <td className="py-3 px-2 text-[#1C1917]">{t('product.origin_usa', 'США (Официальные дистрибьюторы Sephora USA / Ulta)')}</td>
                  </tr>
                  <tr className="hover:bg-stone-50/60 transition-colors">
                    <td className="py-3 px-2 text-[#8C827A] font-medium">{t('product.month_batch', 'Наличие текущей партии')}</td>
                    <td className="py-3 px-2 text-[#1C1917] font-bold">
                      {isOutOfStock ? `0 ${t('product.pcs', 'шт.')} (${t('product.sold_out', 'распродано')})` : `${stockCount} ${t('product.pcs', 'шт.')} ${t('product.of_total', 'из')} ${monthlyIntake} ${t('product.pcs', 'шт.')}`}
                    </td>
                  </tr>
                  <tr className="hover:bg-stone-50/60 transition-colors">
                    <td className="py-3 px-2 text-[#8C827A] font-medium">Батч-код</td>
                    <td className="py-3 px-2 text-[#1C1917] font-mono">{t('product.batch_verified', 'Проверяемый оригинальный код партии')}</td>
                  </tr>
                  <tr className="hover:bg-stone-50/60 transition-colors">
                    <td className="py-3 px-2 text-[#8C827A] font-medium">Срок годности после вскрытия</td>
                    <td className="py-3 px-2 text-[#1C1917]">12 месяцев (12M PAO)</td>
                  </tr>
                </tbody>
              </table>
            </div>
          </section>

          {/* БЛОК 4: Способ применения и советы эксперта */}
          <section className="bg-white rounded-3xl p-6 sm:p-8 border border-[#EAE3DE] shadow-xs space-y-5">
            <div className="flex items-center gap-3 pb-3 border-b border-[#EAE3DE]">
              <div className="w-9 h-9 rounded-xl bg-purple-50 text-purple-800 flex items-center justify-center font-bold">
                <i className="fa-solid fa-wand-magic-sparkles text-base" />
              </div>
              <div>
                <h2 className="font-serif text-xl sm:text-2xl font-bold text-[#1C1917]">
                  {t('product.how_to_use_title', 'Способ применения')}
                </h2>
                <p className="text-xs text-[#8C827A]">
                  {t('product.how_to_use_subtitle', 'Пошаговая инструкция и профессиональные советы')}
                </p>
              </div>
            </div>

            <div className="text-sm font-medium text-[#1C1917] leading-relaxed">
              <MarkdownRenderer
                content={currentProduct.howToUse || 'Наносите по мере необходимости в соответствии с инструкциями бренда на очищенную кожу.'}
              />
            </div>

            <div className="p-4 bg-amber-50/70 rounded-2xl border border-amber-200/70 text-xs text-amber-950 flex items-start gap-3">
              <i className="fa-solid fa-lightbulb text-amber-600 text-base mt-0.5" />
              <div>
                <strong className="block mb-1 font-bold">Совет визажиста Mally:</strong>
                Для максимально естественного и стойкого покрытия наносите текстуру небольшими порциями, слегка согревая продукт подушечками пальцев перед растушевкой.
              </div>
            </div>
          </section>

          {/* БЛОК 5: Доставка и 100% гарантия подлинности */}
          <section className="bg-white rounded-3xl p-6 sm:p-8 border border-[#EAE3DE] shadow-xs space-y-5">
            <div className="flex items-center gap-3 pb-3 border-b border-[#EAE3DE]">
              <div className="w-9 h-9 rounded-xl bg-sky-50 text-sky-800 flex items-center justify-center font-bold">
                <i className="fa-solid fa-truck-fast text-base" />
              </div>
              <div>
                <h2 className="font-serif text-xl sm:text-2xl font-bold text-[#1C1917]">
                  {t('product.delivery_guarantee_title', 'Доставка и гарантии')}
                </h2>
                <p className="text-xs text-[#8C827A]">
                  Условия экспресс-доставки и подтверждение подлинности
                </p>
              </div>
            </div>

            <div className="grid grid-cols-1 md:grid-cols-3 gap-4 text-xs">
              <div className="p-4 rounded-2xl bg-[#FAF7F5] border border-stone-200/60 space-y-1.5">
                <div className="font-bold text-[#1C1917] text-sm flex items-center gap-2">
                  <i className="fa-solid fa-bolt text-amber-600" />
                  <span>По Ташкенту</span>
                </div>
                <p className="text-[#57534E] leading-relaxed">
                  Отправка день-в-день через Яндекс Go по тарифу такси за 1–2 часа с момента подтверждения заказа.
                </p>
              </div>

              <div className="p-4 rounded-2xl bg-[#FAF7F5] border border-stone-200/60 space-y-1.5">
                <div className="font-bold text-[#1C1917] text-sm flex items-center gap-2">
                  <i className="fa-solid fa-truck text-emerald-600" />
                  <span>По Узбекистану</span>
                </div>
                <p className="text-[#57534E] leading-relaxed">
                  Экспресс-доставка через проверенную почтовую службу BTS (1–3 дня до двери или пункта выдачи).
                </p>
              </div>

              <div className="p-4 rounded-2xl bg-[#FAF7F5] border border-stone-200/60 space-y-1.5">
                <div className="font-bold text-[#1C1917] text-sm flex items-center gap-2">
                  <i className="fa-solid fa-shield-halved text-sky-600" />
                  <span>{t('product.usa_origin', '100% Оригинал')}</span>
                </div>
                <p className="text-[#57534E] leading-relaxed">
                  Только прямые закупки из США. Вы можете сверить батч-код и проверить продукт при получении.
                </p>
              </div>
            </div>
          </section>

        </div>

        {/* БЛОК УМНЫХ РЕКОМЕНДАЦИЙ "Вам также может понравиться" */}
        {recommendedProducts.length > 0 && (
          <div className="pt-8 border-t border-[#EAE3DE]">
            <div className="flex items-center justify-between mb-6">
              <div>
                <h2 className="font-serif text-2xl sm:text-3xl font-bold text-[#1C1917]">
                  {t('product.recommended', 'Вам также может понравиться')}
                </h2>
                <p className="text-xs text-[#8C827A] mt-1">
                  {t('product.recommended_subtitle', 'Средства из этой коллекции и похожие бестселлеры')}
                </p>
              </div>
              <Link href="/catalog" className="text-xs font-bold text-[#96674c] hover:underline">
                {t('catalog.all', 'Весь каталог')} →
              </Link>
            </div>
            <div className="grid grid-cols-2 sm:grid-cols-4 gap-4 sm:gap-6">
              {recommendedProducts.map((p) => (
                <ProductCard
                  key={p.sku || p.id}
                  product={p}
                />
              ))}
            </div>
          </div>
        )}

        {/* ПОЛНОЭКРАННЫЙ ЗУМ С НАВИГАЦИЕЙ СТРЕЛКАМИ И КЛАВИАТУРОЙ */}
        {isZoomOpen && (
          <div
            className="fixed inset-0 z-50 bg-black/90 backdrop-blur-md flex items-center justify-center p-4 select-none animate-fade-in"
            onClick={() => setIsZoomOpen(false)}
          >
            {/* Кнопка закрытия */}
            <button
              type="button"
              onClick={() => setIsZoomOpen(false)}
              className="absolute top-5 right-5 z-50 w-12 h-12 rounded-full bg-white/20 hover:bg-white/40 text-white flex items-center justify-center transition cursor-pointer"
              title="Закрыть (Esc)"
            >
              <i className="fa-solid fa-xmark text-lg" />
            </button>

            {/* Стрелки внутри зума */}
            {images.length > 1 && (
              <>
                <button
                  type="button"
                  onClick={handlePrevImage}
                  className="absolute left-4 top-1/2 -translate-y-1/2 z-50 w-14 h-14 rounded-full bg-white/20 hover:bg-white/40 text-white flex items-center justify-center transition cursor-pointer"
                  title="Предыдущее фото"
                >
                  <i className="fa-solid fa-chevron-left text-xl" />
                </button>

                <button
                  type="button"
                  onClick={handleNextImage}
                  className="absolute right-4 top-1/2 -translate-y-1/2 z-50 w-14 h-14 rounded-full bg-white/20 hover:bg-white/40 text-white flex items-center justify-center transition cursor-pointer"
                  title="Следующее фото"
                >
                  <i className="fa-solid fa-chevron-right text-xl" />
                </button>
              </>
            )}

            {/* Фото в зуме */}
            <div
              className="relative max-w-4xl max-h-[88vh] flex items-center justify-center"
              onClick={(e) => e.stopPropagation()}
            >
              <img
                src={images[activeImageIndex] || images[0]}
                alt={title}
                className="max-h-[85vh] max-w-full object-contain rounded-2xl shadow-2xl"
              />
              <div className="absolute bottom-4 left-1/2 -translate-x-1/2 px-4 py-1.5 bg-black/60 backdrop-blur-md text-white text-xs font-mono rounded-full">
                {activeImageIndex + 1} / {images.length}
              </div>
            </div>
          </div>
        )}
      </main>

      <Footer />
    </div>
  );
}
