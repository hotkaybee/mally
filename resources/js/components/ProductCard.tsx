import React from 'react';
import { useNavigate } from 'react-router-dom';
import Link from './Link';
import { useCart } from '../context/CartContext';
import { useFavorites } from '../context/FavoritesContext';
import { useLanguage } from '../context/LanguageContext';
import { formatUSD } from '../shared/formatters';

export interface ProductCardProps {
  product: {
    id?: string | number;
    sku?: string;
    slug?: string;
    title?: string;
    name?: string;
    description?: string;
    price?: number;
    selling_price?: number;
    oldPrice?: number | null;
    old_price?: number | null;
    quantity?: number;
    inStock?: boolean;
    status?: string;
    images?: string[] | string;
    image?: string | null;
    isNew?: boolean;
    brand?: { name: string } | string;
    category?: { name: string; name_ru?: string } | string;
  };
}

export default function ProductCard({ product }: ProductCardProps) {
  const navigate = useNavigate();
  const { addToCart, updateQuantity, getItemQuantity, formatPrice } = useCart();
  const { isFavorite, toggleFavorite } = useFavorites();
  const { t, language } = useLanguage();

  const prodKey = String(product.id || product.sku || 'prod');
  const qtyInCart = getItemQuantity(prodKey);
  const favorited = isFavorite(prodKey);

  const rawTitle = (product as any).title || (product as any).name || 'Косметика Mally';
  const displayTitle = (language === 'uz' && (product as any).titleUz)
    ? (product as any).titleUz
    : (language === 'en' && (product as any).titleEn ? (product as any).titleEn : rawTitle);

  const quantity = product.quantity ?? 10;
  const isOutOfStock = product.status === 'OUT_OF_STOCK' || product.status === 'out_of_stock' || quantity <= 0 || product.inStock === false;
  const isComingSoon = product.status === 'COMING_SOON';

  let mainImage = 'https://findy.uz/cdn/shop/files/sample.jpg';
  if (Array.isArray(product.images) && product.images.length > 0) {
    mainImage = product.images[0];
  } else if (typeof product.image === 'string' && product.image.length > 0) {
    mainImage = product.image;
  } else if (typeof product.images === 'string') {
    try {
      const parsed = JSON.parse(product.images);
      if (Array.isArray(parsed) && parsed.length > 0) mainImage = parsed[0];
      else if (product.images.startsWith('http')) mainImage = product.images;
    } catch {
      if (product.images.startsWith('http')) mainImage = product.images;
    }
  }

  const rawPrice = product.price ?? product.selling_price ?? 35;
  const price = typeof rawPrice === 'number' && rawPrice > 1000 ? rawPrice / 12850 : Number(rawPrice) || 35;

  const rawOldPrice = product.oldPrice ?? product.old_price ?? null;
  const oldPrice = rawOldPrice ? (typeof rawOldPrice === 'number' && rawOldPrice > 1000 ? rawOldPrice / 12850 : Number(rawOldPrice)) : null;

  const discountPercent =
    oldPrice && oldPrice > price
      ? Math.round(((oldPrice - price) / oldPrice) * 100)
      : null;

  // URL strictly uses the ARTIKUL (SKU)
  const productSku = String(product.sku || (product.id ? `ML-P-${product.id}` : 'ML-ITEM')).trim();
  const productUrl = `/products/${encodeURIComponent(productSku)}`;

  const brandName = typeof product.brand === 'object' && product.brand !== null ? product.brand?.name : (product.brand || 'Mally Collection');

  // Клик по карточке — переход на саму страницу товара
  const handleCardClick = (e: React.MouseEvent) => {
    const target = e.target as HTMLElement;
    if (target.closest('button') || target.closest('a')) {
      return;
    }
    navigate(productUrl);
  };

  return (
    <div
      onClick={handleCardClick}
      className="group relative flex flex-col bg-white rounded-3xl p-3 sm:p-3.5 border border-[#EAE3DE] hover:border-[#C4B7AF] hover:shadow-xl transition-all duration-300 cursor-pointer select-none"
    >
      {/* 1. Блок фото с зумом при наведении */}
      <div className="relative aspect-square w-full rounded-2xl overflow-hidden bg-[#FAF7F5]">
        <img
          src={mainImage}
          alt={displayTitle}
          className="w-full h-full object-cover object-center group-hover:scale-105 transition-transform duration-500 ease-out"
          loading="lazy"
        />

        {/* Бейджи статуса */}
        <div className="absolute top-2.5 left-2.5 flex flex-col gap-1 z-10 pointer-events-none">
          {isOutOfStock ? (
            <span className="px-2.5 py-1 bg-stone-900/85 backdrop-blur-sm text-stone-200 text-[10px] uppercase tracking-wider font-bold rounded-full shadow-sm">
              {t('product.out_of_stock', 'Распродано')}
            </span>
          ) : isComingSoon ? (
            <span className="px-2.5 py-1 bg-[#D97706]/90 backdrop-blur-sm text-white text-[10px] uppercase tracking-wider font-bold rounded-full shadow-sm">
              {language === 'uz' ? 'Tez orada' : 'Скоро'}
            </span>
          ) : (
            <>
              {product.isNew && (
                <span className="px-2.5 py-1 bg-[#1C1917] text-white text-[10px] uppercase tracking-wider font-bold rounded-full shadow-sm">
                  NEW
                </span>
              )}
              {discountPercent && (
                <span className="px-2.5 py-1 bg-[#E11D48] text-white text-[10px] uppercase tracking-wider font-bold rounded-full shadow-sm">
                  -{discountPercent}%
                </span>
              )}
            </>
          )}
        </div>

        {/* Кнопка "В избранное" */}
        <button
          type="button"
          onClick={(e) => {
            e.preventDefault();
            e.stopPropagation();
            toggleFavorite({
              id: prodKey,
              sku: product.sku || prodKey,
              slug: productSku,
              title: displayTitle,
              price: price,
              oldPrice: oldPrice,
              image: mainImage,
              brand: brandName,
              inStock: !isOutOfStock,
            });
          }}
          className={`absolute top-2.5 right-2.5 z-20 w-8 h-8 rounded-full flex items-center justify-center transition shadow-sm cursor-pointer ${
            favorited
              ? 'bg-rose-50 text-rose-600 border border-rose-200 shadow-rose-100'
              : 'bg-white/90 backdrop-blur-sm text-neutral-500 hover:text-rose-600 hover:bg-white border border-neutral-200/60'
          }`}
          aria-label={favorited ? 'Удалить из избранного' : 'Добавить в избранное'}
          title={favorited ? 'В избранном' : 'Добавить в избранное'}
        >
          <i className={`${favorited ? 'fa-solid text-rose-500' : 'fa-regular'} fa-heart text-xs`} />
        </button>
      </div>

      {/* 2. Информация о товаре */}
      <div className="flex flex-col flex-1 mt-3">
        <div className="flex items-center justify-between gap-1 text-[11px] text-[#8C827A]">
          <span className="font-semibold uppercase tracking-wider truncate">
            {brandName}
          </span>
          {product.sku && (
            <span className="font-mono text-[10px] text-stone-400 flex-shrink-0">
              {product.sku}
            </span>
          )}
        </div>

        <Link
          href={productUrl}
          className="block mt-1"
        >
          <h3 className="text-xs sm:text-sm font-medium text-[#1C1917] group-hover:text-[#685F58] transition-colors line-clamp-2 min-h-[36px] leading-snug">
            {displayTitle}
          </h3>
        </Link>

        {/* Звезды рейтинга и остаток на складе */}
        <div className="mt-1.5 flex items-center justify-between gap-1">
          <div className="flex items-center gap-0.5 text-amber-400 text-[10px]">
            <i className="fa-solid fa-star" />
            <i className="fa-solid fa-star" />
            <i className="fa-solid fa-star" />
            <i className="fa-solid fa-star" />
            <i className="fa-solid fa-star-half-stroke" />
            <span className="text-stone-600 font-mono font-bold ml-1 text-[11px]">4.9</span>
          </div>

          {!isOutOfStock && (
            <span className={`inline-flex items-center gap-1 text-[10px] font-medium px-2 py-0.5 rounded-full ${
              quantity <= 5
                ? 'bg-amber-50 text-amber-700 border border-amber-200'
                : 'bg-emerald-50 text-emerald-700 border border-emerald-200'
            }`}>
              <span className={`w-1.5 h-1.5 rounded-full ${quantity <= 5 ? 'bg-amber-500 animate-pulse' : 'bg-emerald-500'}`} />
              <span>{quantity <= 5 ? `${t('product.remaining', 'Осталось')} ${quantity} ${t('product.pcs', 'шт.')}` : `${quantity} ${t('product.pcs', 'шт.')}`}</span>
            </span>
          )}
        </div>

        {/* Цены в выбранной валюте (USD $ / UZS сум) */}
        <div className="mt-2 flex items-baseline gap-2">
          <span className={`text-sm sm:text-base font-bold ${discountPercent ? 'text-[#E11D48]' : 'text-[#1C1917]'}`}>
            {formatPrice(price)}
          </span>
          {oldPrice && oldPrice > price && (
            <span className="text-[11px] text-[#A8A29E] line-through">
              {formatPrice(oldPrice)}
            </span>
          )}
        </div>

        {/* 3. Кнопка или счетчик на карточке */}
        <div className="mt-3 pt-1" onClick={(e) => e.stopPropagation()}>
          {isOutOfStock ? (
            /* Кнопка: «Распродано» -> при наведении «Заказать из США» через Telegram */
            <a
              href={`https://t.me/mallyuz?text=${encodeURIComponent(
                `Здравствуйте! Хочу оформить заказ из США на товар: ${displayTitle} (Артикул: ${product.sku || prodKey}). Сориентируйте по срокам доставки и деталям.`
              )}`}
              target="_blank"
              rel="noreferrer"
              className="group/oos relative w-full h-10 rounded-2xl bg-stone-100 hover:bg-amber-50 text-stone-600 hover:text-amber-900 border border-stone-200 hover:border-amber-300 text-xs font-semibold transition-all duration-300 flex items-center justify-center overflow-hidden cursor-pointer"
              title="Товар распродан. Наведите для заказа из США через Telegram"
            >
              <div className="absolute inset-0 flex items-center justify-center gap-1.5 transition-transform duration-300 ease-out group-hover/oos:-translate-y-full">
                <i className="fa-solid fa-box-open text-xs text-stone-400" />
                <span>{t('product.out_of_stock', 'Распродано')}</span>
              </div>
              <div className="absolute inset-0 flex items-center justify-center gap-1.5 text-amber-900 font-bold transition-transform duration-300 ease-out translate-y-full group-hover/oos:translate-y-0">
                <i className="fa-solid fa-plane-departure text-xs text-amber-600" />
                <span>{t('product.order_us', 'Заказать из США')}</span>
              </div>
            </a>
          ) : isComingSoon ? (
            <button
              disabled
              className="w-full py-2.5 px-3 rounded-2xl bg-[#FEF3C7] text-[#92400E] text-xs font-medium cursor-not-allowed text-center"
            >
              {language === 'uz' ? 'Kutilmoqda' : 'Ожидается поступление'}
            </button>
          ) : qtyInCart > 0 ? (
            /* Активный счетчик [- count +] И РЯДОМ кнопка "В корзину" */
            <div className="flex items-center gap-1.5 w-full animate-fade-in">
              <div className="flex-1 py-1 px-1.5 rounded-2xl bg-[#1C1917] text-white flex items-center justify-between shadow-sm">
                <button
                  type="button"
                  onClick={(e) => {
                    e.preventDefault();
                    e.stopPropagation();
                    updateQuantity(prodKey, qtyInCart - 1);
                  }}
                  className="w-7 h-7 rounded-xl bg-white/10 hover:bg-white/20 flex items-center justify-center text-sm font-bold transition active:scale-95 cursor-pointer"
                  title="Уменьшить"
                >
                  −
                </button>
                <div className="flex items-center gap-1 px-1">
                  <span className="text-xs font-bold leading-none">{qtyInCart}</span>
                  <span className="text-[10px] text-[#D5CCC7] leading-none">{t('cart.qty', 'шт.')}</span>
                </div>
                <button
                  type="button"
                  onClick={(e) => {
                    e.preventDefault();
                    e.stopPropagation();
                    if (qtyInCart >= quantity) return;
                    updateQuantity(prodKey, qtyInCart + 1);
                  }}
                  disabled={qtyInCart >= quantity}
                  className={`w-7 h-7 rounded-xl flex items-center justify-center text-sm font-bold transition ${
                    qtyInCart >= quantity
                      ? 'bg-white/5 text-stone-500 opacity-40 cursor-not-allowed'
                      : 'bg-white/10 hover:bg-white/20 text-white active:scale-95 cursor-pointer'
                  }`}
                  title={qtyInCart >= quantity ? `Максимум на складе (${quantity} шт.)` : "Увеличить"}
                >
                  +
                </button>
              </div>

              <Link
                href="/cart"
                className="py-2 px-3 rounded-2xl bg-[#FAF7F5] hover:bg-[#EAE3DE] text-[#1C1917] border border-[#EAE3DE] hover:border-[#1C1917] text-xs font-bold transition flex items-center gap-1.5 shadow-2xs whitespace-nowrap cursor-pointer"
                title="Перейти в корзину"
              >
                <i className="fa-solid fa-bag-shopping text-xs" />
                <span>{t('nav.cart', 'В корзину')}</span>
              </Link>
            </div>
          ) : (
            <button
              type="button"
              onClick={(e) => {
                e.preventDefault();
                e.stopPropagation();
                addToCart(product, false);
              }}
              className="w-full py-2.5 px-4 rounded-2xl bg-[#1C1917] hover:bg-black text-white text-xs font-semibold transition-all duration-200 flex items-center justify-center gap-1.5 shadow-sm active:scale-[0.98] cursor-pointer"
            >
              <i className="fa-solid fa-plus text-xs" />
              <span>{t('product.add_to_cart', 'В корзину')}</span>
            </button>
          )}
        </div>
      </div>
    </div>
  );
}
