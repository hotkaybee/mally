import React from 'react';
import { Link } from 'react-router-dom';
import { useCart } from '../context/CartContext';
import { useLanguage } from '../context/LanguageContext';
import { formatUSD } from '../shared/formatters';
import { X, Trash2, ShoppingBag, ArrowRight, ShieldCheck } from 'lucide-react';

export const CartDrawer: React.FC = () => {
  const {
    items,
    removeFromCart,
    updateQuantity,
    clearCart,
    totalItems,
    subtotal,
    isCartOpen,
    setIsCartOpen,
    setIsCheckoutOpen,
    formatPrice,
  } = useCart();
  const { t } = useLanguage();

  if (!isCartOpen) return null;

  const FREE_SHIPPING_THRESHOLD = 50; // $50 USD
  const freeShippingProgress = Math.min(100, Math.round((subtotal / FREE_SHIPPING_THRESHOLD) * 100));
  const remainingForFreeShipping = Math.max(0, FREE_SHIPPING_THRESHOLD - subtotal);

  const handleProceedToCheckout = () => {
    setIsCartOpen(false);
    setIsCheckoutOpen(true);
  };

  return (
    <div className="fixed inset-0 z-50 overflow-hidden bg-black/50 backdrop-blur-xs">
      <div className="absolute inset-0" onClick={() => setIsCartOpen(false)} />

      <div className="fixed inset-y-0 right-0 max-w-full flex pl-10">
        <div className="w-screen max-w-md bg-[#FAF7F5] shadow-2xl flex flex-col justify-between border-l border-stone-200">
          {/* Header */}
          <div className="p-6 bg-white border-b border-stone-200 flex items-center justify-between">
            <div className="flex items-center gap-3">
              <div className="p-2.5 rounded-full bg-[#FAF7F5] text-[#1C1917]">
                <ShoppingBag className="w-5 h-5" />
              </div>
              <div>
                <h3 className="text-base font-serif font-semibold text-stone-900">{t('cart.title', 'Ваша корзина')}</h3>
                <span className="text-xs text-stone-500 font-medium">
                  {totalItems > 0 ? `${totalItems} ${t('cart.item_count', 'товаров')}` : t('cart.empty_title', 'Корзина пуста')}
                </span>
              </div>
            </div>

            <button
              onClick={() => setIsCartOpen(false)}
              className="p-2 text-stone-400 hover:text-stone-700 rounded-full hover:bg-stone-100 transition-colors"
            >
              <X className="w-5 h-5" />
            </button>
          </div>

          {/* Free Shipping Progress */}
          {items.length > 0 && (
            <div className="bg-[#FAF7F5] px-6 py-3 border-b border-stone-200/80">
              <div className="flex items-center justify-between text-xs mb-1.5 font-medium">
                {remainingForFreeShipping > 0 ? (
                  <span className="text-stone-700">
                    До бесплатной доставки:{' '}
                    <strong className="text-[#1C1917]">{formatUSD(remainingForFreeShipping)}</strong>
                  </span>
                ) : (
                  <span className="text-emerald-700 font-semibold flex items-center gap-1">
                    <ShieldCheck className="w-3.5 h-3.5" />
                    Бесплатная доставка активна!
                  </span>
                )}
                <span className="text-stone-500">{freeShippingProgress}%</span>
              </div>
              <div className="w-full bg-stone-200 rounded-full h-1.5 overflow-hidden">
                <div
                  className="bg-[#1C1917] h-full rounded-full transition-all duration-300"
                  style={{ width: `${freeShippingProgress}%` }}
                />
              </div>
            </div>
          )}

          {/* Cart Items List */}
          <div className="flex-1 overflow-y-auto p-6 space-y-4">
            {items.length === 0 ? (
              <div className="h-full flex flex-col items-center justify-center text-center py-12">
                <div className="w-16 h-16 rounded-full bg-stone-100 flex items-center justify-center text-stone-400 mb-4">
                  <ShoppingBag className="w-8 h-8" />
                </div>
                <h4 className="text-base font-serif font-semibold text-stone-800">
                  В корзине пока пусто
                </h4>
                <p className="text-xs text-stone-500 mt-1 max-w-xs leading-relaxed">
                  Выберите понравившиеся товары из каталога оригинальной косметики.
                </p>
                <button
                  onClick={() => setIsCartOpen(false)}
                  className="mt-6 px-6 py-2.5 rounded-full bg-[#1C1917] text-white text-xs font-semibold tracking-wider uppercase hover:bg-stone-800 transition-colors"
                >
                  Перейти в каталог
                </button>
              </div>
            ) : (
              items.map((item) => {
                const prod = (item as any).product || item;
                const maxQty = item.maxQuantity || prod.quantity || 12;
                const img =
                  prod.image ||
                  (Array.isArray(prod.images) && prod.images[0]) ||
                  item.image ||
                  'https://images.unsplash.com/photo-1522337360788-8b13dee7a37e?auto=format&fit=crop&w=400&q=80';
                const brand =
                  typeof prod.brand === 'object' && prod.brand !== null
                    ? prod.brand.name
                    : prod.brand || item.brand || 'Mally';
                const itemPrice = Number(item.price ?? prod.selling_price ?? prod.price ?? 35);

                return (
                  <div
                    key={item.id || prod.id}
                    className="flex gap-4 p-3.5 rounded-2xl bg-white border border-stone-200/70 shadow-2xs"
                  >
                    <Link
                      to={`/products/${encodeURIComponent(prod.sku || item.sku || prod.id || item.id)}`}
                      onClick={() => setIsCartOpen(false)}
                      className="w-18 h-18 rounded-xl overflow-hidden bg-stone-50 shrink-0 border border-stone-100 block hover:opacity-90 transition-opacity"
                    >
                      <img
                        src={img}
                        alt={prod.title || prod.name || item.title}
                        className="w-full h-full object-cover object-center"
                      />
                    </Link>

                    <div className="flex-1 flex flex-col justify-between">
                      <div>
                        <div className="flex items-start justify-between gap-2">
                          <div>
                            <span className="text-[10px] font-bold uppercase tracking-wider text-[#96674c]">
                              {brand}
                            </span>
                            <Link
                              to={`/products/${encodeURIComponent(prod.sku || item.sku || prod.id || item.id)}`}
                              onClick={() => setIsCartOpen(false)}
                              className="text-xs font-medium text-stone-900 line-clamp-1 hover:text-[#96674c] transition-colors block"
                            >
                              {prod.title || prod.name || item.title}
                            </Link>
                          </div>
                          <button
                            onClick={() => removeFromCart(item.id || prod.id)}
                            className="p-1 text-stone-400 hover:text-rose-600 transition-colors"
                            title="Удалить"
                          >
                            <Trash2 className="w-3.5 h-3.5" />
                          </button>
                        </div>
                        {(prod.sku || item.sku) && (
                          <span className="text-[10px] font-mono text-stone-400">
                            {prod.sku || item.sku}
                          </span>
                        )}
                      </div>

                      <div className="flex items-center justify-between mt-2 pt-2 border-t border-stone-100">
                        {/* Quantity Counter */}
                        <div className="flex items-center border border-stone-200 rounded-full px-2 py-0.5 bg-stone-50">
                          <button
                            onClick={() => updateQuantity(item.id || prod.id, item.quantity - 1)}
                            className="text-stone-500 hover:text-stone-900 px-1 font-bold text-xs cursor-pointer"
                            title="Уменьшить"
                          >
                            -
                          </button>
                          <span className="w-6 text-center text-xs font-semibold text-stone-800">
                            {item.quantity}
                          </span>
                          <button
                            onClick={() => updateQuantity(item.id || prod.id, item.quantity + 1)}
                            disabled={item.quantity >= maxQty}
                            className={`px-1 font-bold text-xs transition-colors ${
                              item.quantity >= maxQty
                                ? 'text-stone-300 opacity-40 cursor-not-allowed'
                                : 'text-stone-500 hover:text-stone-900 cursor-pointer'
                            }`}
                            title={item.quantity >= maxQty ? `Максимум на складе (${maxQty} шт.)` : "Увеличить"}
                          >
                            +
                          </button>
                        </div>

                        <span className="text-sm font-bold text-[#1C1917]">
                          {formatPrice(itemPrice * item.quantity)}
                        </span>
                      </div>
                    </div>
                  </div>
                );
              })
            )}
          </div>

          {/* Footer & Checkout Trigger */}
          {items.length > 0 && (
            <div className="p-6 bg-white border-t border-stone-200 space-y-4">
              <div className="space-y-1.5 text-xs text-stone-600">
                <div className="flex items-center justify-between">
                  <span>{t('cart.summary', 'Подытог')}:</span>
                  <span className="font-semibold text-stone-900">{formatPrice(subtotal)}</span>
                </div>
                <div className="flex items-center justify-between">
                  <span>{t('cart.shipping_calc', 'Доставка')}:</span>
                  <span className="text-emerald-600 font-semibold">
                    {subtotal >= FREE_SHIPPING_THRESHOLD ? 'Бесплатно' : formatPrice(5)}
                  </span>
                </div>
                <div className="flex items-center justify-between pt-2 border-t border-stone-100 text-sm font-bold text-[#1C1917]">
                  <span>{t('cart.summary', 'Итого к оплате')}:</span>
                  <span className="text-lg">
                    {formatPrice(subtotal >= FREE_SHIPPING_THRESHOLD ? subtotal : subtotal + 5)}
                  </span>
                </div>
              </div>

              <div className="flex items-center gap-2">
                <button
                  onClick={clearCart}
                  className="px-4 py-3 rounded-xl border border-stone-200 text-stone-500 hover:text-rose-600 hover:bg-rose-50 text-xs font-semibold transition-colors"
                  title={t('cart.clear', 'Очистить корзину')}
                >
                  {t('cart.clear', 'Очистить')}
                </button>
                <button
                  onClick={handleProceedToCheckout}
                  className="flex-1 py-3.5 px-6 rounded-full bg-[#1C1917] hover:bg-[#322c28] text-white font-medium text-xs tracking-wider uppercase transition-all shadow-md hover:shadow-lg active:scale-98 flex items-center justify-center gap-2 cursor-pointer"
                >
                  <span>{t('cart.checkout', 'Оформить заказ')}</span>
                  <ArrowRight className="w-4 h-4" />
                </button>
              </div>
            </div>
          )}
        </div>
      </div>
    </div>
  );
};
