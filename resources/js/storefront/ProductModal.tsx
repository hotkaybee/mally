import React, { useState, useEffect, useCallback } from 'react';
import { Link } from 'react-router-dom';
import { useCart } from '../context/CartContext';
import { useFavorites } from '../context/FavoritesContext';
import { useLanguage } from '../context/LanguageContext';
import { formatUSD } from '../shared/formatters';
import MarkdownRenderer from '../components/ui/MarkdownRenderer';
import { X, Heart, ShoppingBag, ShieldCheck, Truck, Sparkles, Check, Copy, ExternalLink, ChevronLeft, ChevronRight, PackageCheck } from 'lucide-react';

interface ProductModalProps {
  product: any | null;
  onClose: () => void;
}

export const ProductModal: React.FC<ProductModalProps> = ({ product, onClose }) => {
  if (!product) return null;

  const { addToCart, setIsCartOpen, formatPrice, currency } = useCart();
  const { isFavorite, toggleFavorite } = useFavorites();
  const { t } = useLanguage();
  const [quantity, setQuantity] = useState(1);
  const [copiedSku, setCopiedSku] = useState(false);
  const [selectedImageIdx, setSelectedImageIdx] = useState(0);

  // СТРОГО Артикул (SKU) для ссылки вызова товара!
  const productSku = String(product.sku || (product.id ? `ML-P-${product.id}` : 'ML-ITEM')).trim();
  const productUrl = `/products/${encodeURIComponent(productSku)}`;

  const rawImages = Array.isArray(product.images) && product.images.length > 0
    ? product.images
    : (typeof product.images === 'string'
      ? (() => {
          try {
            const parsed = JSON.parse(product.images);
            return Array.isArray(parsed) && parsed.length > 0 ? parsed : [product.images];
          } catch {
            return [product.images];
          }
        })()
      : (product.image ? [product.image] : ['https://findy.uz/cdn/shop/files/sample.jpg']));

  const images: string[] = rawImages.filter(Boolean);

  const brandName =
    typeof product.brand === 'object' && product.brand !== null
      ? product.brand.name
      : product.brand || 'Mally Beauty';

  const categoryName =
    typeof product.category === 'object' && product.category !== null
      ? product.category.name_ru || product.category.name
      : product.category || 'Каталог';

  const title = product.title || product.name || 'Косметика Mally';

  const rawPrice = product.price ?? product.selling_price ?? 35;
  const price = typeof rawPrice === 'number' && rawPrice > 1000 ? rawPrice / 12850 : Number(rawPrice) || 35;

  const rawOldPrice = product.oldPrice ?? product.old_price ?? null;
  const oldPrice = rawOldPrice ? (typeof rawOldPrice === 'number' && rawOldPrice > 1000 ? rawOldPrice / 12850 : Number(rawOldPrice)) : null;

  const prodKey = String(product.sku || product.id || 'prod');

  // Расчет остатка на складе: 100% = максимальная партия текущего месяца
  const rawQuantity = product.quantity;
  const stockCount = typeof rawQuantity === 'number' ? rawQuantity : 12;
  const isOutOfStock = product.status === 'OUT_OF_STOCK' || product.status === 'out_of_stock' || stockCount <= 0;

  const monthlyIntake = product.monthly_batch || (stockCount > 0 ? Math.max(25, Math.ceil((stockCount * 2.3) / 5) * 5) : 30);
  const soldCount = Math.max(0, monthlyIntake - stockCount);
  const remainingPercent = isOutOfStock ? 0 : Math.min(100, Math.max(8, Math.round((stockCount / monthlyIntake) * 100)));
  const soldPercent = 100 - remainingPercent;

  const isLowStock = stockCount > 0 && remainingPercent <= 25;
  const stockBarColor = isOutOfStock
    ? 'bg-stone-300'
    : isLowStock
      ? 'bg-rose-500'
      : remainingPercent <= 50
        ? 'bg-amber-500'
        : 'bg-emerald-500';

  // Навигация стрелками по фото
  const handlePrev = useCallback((e?: React.MouseEvent) => {
    if (e) e.stopPropagation();
    if (images.length <= 1) return;
    setSelectedImageIdx((prev) => (prev === 0 ? images.length - 1 : prev - 1));
  }, [images.length]);

  const handleNext = useCallback((e?: React.MouseEvent) => {
    if (e) e.stopPropagation();
    if (images.length <= 1) return;
    setSelectedImageIdx((prev) => (prev === images.length - 1 ? 0 : prev + 1));
  }, [images.length]);

  // Поддержка клавиш со стрелками (ArrowLeft / ArrowRight) и Escape
  useEffect(() => {
    const handleKeyDown = (e: KeyboardEvent) => {
      const target = e.target as HTMLElement;
      if (target && (target.tagName === 'INPUT' || target.tagName === 'TEXTAREA')) return;

      if (e.key === 'ArrowLeft') {
        e.preventDefault();
        handlePrev();
      } else if (e.key === 'ArrowRight') {
        e.preventDefault();
        handleNext();
      } else if (e.key === 'Escape') {
        e.preventDefault();
        onClose();
      }
    };

    window.addEventListener('keydown', handleKeyDown);
    return () => window.removeEventListener('keydown', handleKeyDown);
  }, [handlePrev, handleNext, onClose]);

  const handleAddToCart = () => {
    for (let i = 0; i < quantity; i++) {
      addToCart(product, false);
    }
    setIsCartOpen(true);
    onClose();
  };

  const handleCopySku = () => {
    navigator.clipboard.writeText(productSku);
    setCopiedSku(true);
    setTimeout(() => setCopiedSku(false), 2000);
  };

  const isFavorited = isFavorite(prodKey);

  return (
    <div
      className="fixed inset-0 z-50 overflow-y-auto bg-black/60 backdrop-blur-sm flex items-center justify-center p-3 sm:p-6 animate-fade-in"
      onClick={onClose}
    >
      <div
        className="relative w-full max-w-4xl bg-white rounded-3xl shadow-2xl overflow-hidden border border-stone-200"
        onClick={(e) => e.stopPropagation()}
      >
        {/* Кнопка закрытия */}
        <button
          onClick={onClose}
          className="absolute top-4 right-4 z-20 p-2.5 rounded-full bg-white/90 hover:bg-white text-stone-600 hover:text-stone-900 shadow-md backdrop-blur-xs transition-colors cursor-pointer"
          title="Закрыть (Esc)"
        >
          <X className="w-5 h-5" />
        </button>

        <div className="grid grid-cols-1 md:grid-cols-2">
          {/* Левая сторона: Галерея фото с большими стрелками и клавиатурой */}
          <div className="p-6 sm:p-8 bg-[#FAF7F5] flex flex-col justify-between border-b md:border-b-0 md:border-r border-stone-200">
            <div className="relative aspect-square w-full rounded-2xl overflow-hidden bg-white shadow-inner flex items-center justify-center group select-none">
              <img
                src={images[selectedImageIdx] || images[0] || 'https://findy.uz/cdn/shop/files/sample.jpg'}
                alt={title}
                className="w-full h-full object-cover object-center"
                onError={(e) => {
                  (e.target as HTMLImageElement).src = 'https://findy.uz/cdn/shop/files/sample.jpg';
                }}
              />

              {/* Бейдж наличия */}
              {!isOutOfStock ? (
                <span className="absolute top-3 left-3 px-3 py-1 rounded-full text-[10px] font-bold tracking-wider uppercase bg-[#1C1917]/85 text-white backdrop-blur-xs">
                  В наличии ({stockCount} шт.)
                </span>
              ) : (
                <span className="absolute top-3 left-3 px-3 py-1 rounded-full text-[10px] font-bold tracking-wider uppercase bg-amber-600/90 text-white backdrop-blur-xs">
                  Под заказ из США
                </span>
              )}

              {/* Счетчик фото */}
              {images.length > 1 && (
                <span className="absolute top-3 right-3 px-2.5 py-0.5 rounded-full text-[10px] font-mono font-bold bg-black/50 text-white backdrop-blur-xs">
                  {selectedImageIdx + 1} / {images.length}
                </span>
              )}

              {/* БОЛЬШИЕ СТРЕЛКИ НАВИГАЦИИ ПО ФОТО */}
              {images.length > 1 && (
                <>
                  <button
                    type="button"
                    onClick={handlePrev}
                    className="absolute left-2.5 top-1/2 -translate-y-1/2 w-10 h-10 rounded-full bg-white/90 hover:bg-white text-stone-900 shadow-md flex items-center justify-center transition-transform hover:scale-110 cursor-pointer z-10"
                    title="Предыдущее фото (←)"
                  >
                    <ChevronLeft className="w-5 h-5" />
                  </button>

                  <button
                    type="button"
                    onClick={handleNext}
                    className="absolute right-2.5 top-1/2 -translate-y-1/2 w-10 h-10 rounded-full bg-white/90 hover:bg-white text-stone-900 shadow-md flex items-center justify-center transition-transform hover:scale-110 cursor-pointer z-10"
                    title="Следующее фото (→)"
                  >
                    <ChevronRight className="w-5 h-5" />
                  </button>
                </>
              )}
            </div>

            {/* Миниатюры */}
            {images.length > 1 && (
              <div className="flex gap-2 mt-4 overflow-x-auto pb-1 scrollbar-none">
                {images.map((img: string, idx: number) => (
                  <button
                    key={idx}
                    type="button"
                    onClick={() => setSelectedImageIdx(idx)}
                    className={`w-14 h-14 rounded-xl overflow-hidden border-2 transition-all cursor-pointer ${
                      selectedImageIdx === idx
                        ? 'border-[#1C1917] shadow-sm ring-1 ring-stone-900'
                        : 'border-transparent opacity-70 hover:opacity-100'
                    }`}
                  >
                    <img src={img} alt="" className="w-full h-full object-cover" />
                  </button>
                ))}
              </div>
            )}

            {/* Информационные гарантии */}
            <div className="mt-5 pt-4 border-t border-stone-200/80 space-y-2 text-xs text-stone-600">
              <div className="flex items-center gap-2">
                <ShieldCheck className="w-4 h-4 text-emerald-600 shrink-0" />
                <span>100% Оригинал из США. Батч-код проверяется.</span>
              </div>
              <div className="flex items-center gap-2">
                <Truck className="w-4 h-4 text-[#96674c] shrink-0" />
                <span>Быстрая экспресс-доставка по Ташкенту (Яндекс Go).</span>
              </div>
            </div>
          </div>

          {/* Правая сторона: Информация, наличие на складе, полоса остатка, переход на карточку */}
          <div className="p-6 sm:p-8 flex flex-col justify-between max-h-[85vh] overflow-y-auto">
            <div>
              <div className="flex items-center justify-between gap-2 mb-2">
                <span className="text-xs font-bold tracking-widest uppercase text-[#96674c]">
                  {brandName}
                </span>
                {categoryName && (
                  <span className="text-xs text-stone-400 bg-stone-100 px-2.5 py-0.5 rounded-full font-medium">
                    {categoryName}
                  </span>
                )}
              </div>

              <h2 className="text-xl sm:text-2xl font-serif text-stone-900 font-semibold leading-tight">
                {title}
              </h2>

              {/* СТРОГИЙ АРТИКУЛ (SKU) И ССЫЛКА НА КАРТОЧКУ ТОВАРА */}
              <div className="flex flex-wrap items-center gap-3 mt-2 text-xs text-stone-500">
                <button
                  type="button"
                  onClick={handleCopySku}
                  className="inline-flex items-center gap-1.5 font-mono px-2 py-0.5 bg-stone-100 rounded-lg hover:bg-stone-200 transition-colors text-stone-800 font-medium cursor-pointer"
                  title="Нажмите, чтобы скопировать артикул"
                >
                  <span>Артикул: {productSku}</span>
                  {copiedSku ? (
                    <Check className="w-3.5 h-3.5 text-emerald-600" />
                  ) : (
                    <Copy className="w-3.5 h-3.5 text-stone-400" />
                  )}
                </button>
                
                <span className="text-stone-300">•</span>
                
                {/* Ссылка на карточку товара строго по Артикулу (SKU) */}
                <Link
                  to={productUrl}
                  onClick={onClose}
                  className="inline-flex items-center gap-1.5 text-[#96674c] hover:underline font-bold"
                  title="Открыть полную карточку товара"
                >
                  <span>Полная карточка</span>
                  <ExternalLink className="w-3.5 h-3.5" />
                </Link>
              </div>

              {/* ПОЛОСА НАЛИЧИЯ: Партия месяца, сколько поступило, сколько осталось и ушло */}
              <div className="mt-4 p-3.5 rounded-2xl bg-[#FAF7F5] border border-stone-200/80 space-y-2">
                <div className="flex items-center justify-between text-xs">
                  <div className="flex items-center gap-1.5 font-bold text-stone-800">
                    <PackageCheck className="w-4 h-4 text-emerald-600" />
                    <span>
                      {isOutOfStock ? (
                        t('product.out_of_stock', 'Распродано')
                      ) : (
                        <>
                          {t('product.in_stock', 'В наличии')}: <strong className="text-emerald-700">{stockCount} {t('product.pcs', 'шт.')}</strong>{' '}
                          <span className="text-stone-500 font-normal text-[11px]">{t('product.of_total', 'из')} {monthlyIntake} {t('product.pcs', 'шт.')}</span>
                        </>
                      )}
                    </span>
                  </div>
                  <span className="text-[11px] font-mono text-stone-600 bg-white px-2 py-0.5 rounded-full border border-stone-200">
                    {isOutOfStock ? '0%' : `${t('product.remaining', 'Осталось')} ${remainingPercent}%`}
                  </span>
                </div>
                
                {/* Индикатор полосы остатка */}
                <div className="w-full h-2 bg-stone-200 rounded-full overflow-hidden">
                  <div
                    className={`h-full rounded-full transition-all duration-500 ${stockBarColor}`}
                    style={{ width: `${isOutOfStock ? 0 : remainingPercent}%` }}
                  />
                </div>

                <div className="flex justify-between items-center text-[10px] text-stone-500">
                  <span>{t('product.sold_month', 'Продано за месяц')}: {soldCount} {t('product.pcs', 'шт.')} ({soldPercent}%)</span>
                  <span className="font-medium text-stone-700">{t('product.month_batch', 'Партия этого месяца')}</span>
                </div>
              </div>

              {/* Цены в выбранной валюте (USD $ / UZS сум) */}
              <div className="mt-4 p-4 rounded-2xl bg-white border border-stone-200/80 flex items-baseline justify-between shadow-xs">
                <div>
                  <span className="text-[11px] text-stone-500 uppercase tracking-wider block font-semibold">
                    {currency === 'UZS' ? t('product.price_uzs', 'Стоимость в сумах (UZS)') : t('product.price_usd', 'Цена в долларах ($)')}
                  </span>
                  <div className="flex items-baseline gap-2 mt-0.5">
                    <span className="text-2xl sm:text-3xl font-bold text-[#1C1917]">
                      {formatPrice(price)}
                    </span>
                    {oldPrice && oldPrice > price && (
                      <span className="text-sm text-stone-400 line-through">
                        {formatPrice(oldPrice)}
                      </span>
                    )}
                  </div>
                </div>
                
                <button
                  type="button"
                  onClick={() => toggleFavorite({
                    id: prodKey,
                    sku: productSku,
                    title: title,
                    price: price,
                    oldPrice: oldPrice,
                    image: images[0],
                    brand: brandName,
                    inStock: !isOutOfStock,
                  })}
                  className={`p-3 rounded-full border transition-all cursor-pointer ${
                    isFavorited
                      ? 'bg-rose-50 border-rose-200 text-rose-500'
                      : 'bg-white border-stone-200 text-stone-600 hover:text-stone-900'
                  }`}
                  title={isFavorited ? 'В избранном' : 'Добавить в избранное'}
                >
                  <Heart className={`w-5 h-5 ${isFavorited ? 'fill-current' : ''}`} />
                </button>
              </div>

              {/* Описание товара с поддержкой Markdown */}
              {product.description && (
                <div className="mt-4">
                  <h4 className="text-xs font-bold tracking-wider uppercase text-stone-800 mb-1.5">
                    Описание
                  </h4>
                  <div className="text-xs sm:text-sm text-stone-600 leading-relaxed font-light line-clamp-6">
                    <MarkdownRenderer content={product.description} />
                  </div>
                </div>
              )}

              {/* Способ применения с поддержкой Markdown */}
              {product.howToUse && (
                <div className="mt-3 p-3 rounded-xl bg-amber-50/70 border border-amber-200/60">
                  <h4 className="text-xs font-bold tracking-wider uppercase text-amber-900 mb-1 flex items-center gap-1.5">
                    <Sparkles className="w-3.5 h-3.5 text-amber-700" />
                    Применение
                  </h4>
                  <div className="text-xs text-amber-950/80 leading-relaxed">
                    <MarkdownRenderer content={product.howToUse} />
                  </div>
                </div>
              )}
            </div>

            {/* Нижняя плашка действий: Количество и кнопка в корзину */}
            <div className="mt-5 pt-4 border-t border-stone-200 flex items-center gap-3">
              {/* Счетчик количества с защитой от превышения остатка на складе */}
              <div className="flex items-center border border-stone-300 rounded-full p-1 bg-white">
                <button
                  type="button"
                  onClick={() => setQuantity(Math.max(1, quantity - 1))}
                  className="w-8 h-8 rounded-full flex items-center justify-center text-stone-600 hover:bg-stone-100 font-bold transition-colors cursor-pointer"
                  title="Уменьшить"
                >
                  -
                </button>
                <span className="w-8 text-center font-semibold text-sm text-[#1C1917] font-mono">
                  {quantity}
                </span>
                <button
                  type="button"
                  onClick={() => setQuantity(Math.min(stockCount, quantity + 1))}
                  disabled={quantity >= stockCount}
                  className={`w-8 h-8 rounded-full flex items-center justify-center font-bold transition-colors ${
                    quantity >= stockCount
                      ? 'text-stone-300 opacity-40 cursor-not-allowed'
                      : 'text-stone-600 hover:bg-stone-100 cursor-pointer'
                  }`}
                  title={quantity >= stockCount ? `Максимум на складе (${stockCount} шт.)` : "Увеличить"}
                >
                  +
                </button>
              </div>

              {/* Кнопка "Добавить в корзину" */}
              <button
                type="button"
                onClick={handleAddToCart}
                disabled={isOutOfStock}
                className={`flex-1 py-3.5 px-6 rounded-full font-medium text-xs sm:text-sm tracking-wider uppercase transition-all shadow-md active:scale-98 flex items-center justify-center gap-2 ${
                  isOutOfStock
                    ? 'bg-stone-300 text-stone-500 cursor-not-allowed'
                    : 'bg-[#1C1917] hover:bg-[#322c28] text-white hover:shadow-lg cursor-pointer'
                }`}
              >
                <ShoppingBag className="w-4 h-4" />
                <span>{isOutOfStock ? t('product.out_of_stock', 'Распродано') : `${t('product.add_to_cart', 'Добавить')} (${formatPrice(price * quantity)})`}</span>
              </button>
            </div>
          </div>
        </div>
      </div>
    </div>
  );
};

export default ProductModal;
