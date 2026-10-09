import React, { createContext, useContext, useState, useEffect, useRef } from 'react';
import AuthModal from '../components/AuthModal';
import { useToast } from './ToastContext';
import { mergeCarts, CartItem } from '../lib/cart';

export type { CartItem };

export interface UserData {
  id: string;
  username?: string;
  firstName?: string;
  lastName?: string;
  fullName: string;
  email?: string;
  phone: string;
  isPhoneVerified?: boolean;
  telegramId?: string;
  city?: string;
  address?: string;
}

export type Currency = 'UZS' | 'USD';

interface CartContextType {
  cart: CartItem[];
  items: CartItem[];
  currency: Currency;
  setCurrency: (c: Currency) => void;
  formatPrice: (priceInUZS: number) => string;
  exchangeRate: number;
  rateSource: string;
  rateUpdatedAt: string;
  fetchExchangeRate: () => Promise<void>;
  isCartOpen: boolean;
  setIsCartOpen: (open: boolean) => void;
  isCheckoutOpen: boolean;
  setIsCheckoutOpen: (open: boolean) => void;
  addToCart: (product: any, autoOpen?: boolean) => void;
  removeFromCart: (key: string) => void;
  updateQuantity: (key: string, quantity: number) => void;
  clearCart: () => void;
  getItemQuantity: (key: string) => number;
  getMaxQuantity: (key: string) => number;
  totalCount: number;
  totalItems: number;
  totalAmount: number;
  subtotal: number;
  freeShippingThreshold: number;
  freeShippingRemaining: number;
  
  user: UserData | null;
  isAuthenticated: boolean;
  login: (userData: UserData, serverCart?: CartItem[]) => void;
  logout: () => void;
  deviceId: string;

  isAuthModalOpen: boolean;
  setIsAuthModalOpen: (open: boolean) => void;
  authModalMessage: string;
  openAuthModal: (message?: string) => void;
}

const CartContext = createContext<CartContextType | undefined>(undefined);

export const FREE_SHIPPING_THRESHOLD = 75; // $75 USD for free shipping
export const USD_EXCHANGE_RATE = 12850;

const getOrCreateDeviceId = () => {
  if (typeof window !== 'undefined') {
    let id = localStorage.getItem('mally_device_id');
    if (!id) {
      id = (typeof crypto !== 'undefined' && crypto.randomUUID) 
        ? crypto.randomUUID() 
        : 'dev_' + Date.now() + Math.random().toString(36).substring(2, 9);
      localStorage.setItem('mally_device_id', id);
    }
    return id;
  }
  return '';
};

export function CartProvider({ children }: { children: React.ReactNode }) {
  const { showToast } = useToast();
  const [cart, setCart] = useState<CartItem[]>([]);
  const [currency, setCurrencyState] = useState<Currency>(() => {
    if (typeof window !== 'undefined') {
      try {
        const saved = localStorage.getItem('mally_currency');
        if (saved === 'UZS' || saved === 'USD') return saved;
      } catch {}
    }
    return 'USD';
  });
  const [isCartOpen, setIsCartOpen] = useState(false);
  const [isCheckoutOpen, setIsCheckoutOpen] = useState(false);
  const [user, setUser] = useState<UserData | null>(null);
  const [deviceId, setDeviceId] = useState('');
  const [exchangeRate, setExchangeRate] = useState<number>(USD_EXCHANGE_RATE);
  const [rateSource, setRateSource] = useState<string>('cbu.uz');
  const [rateUpdatedAt, setRateUpdatedAt] = useState<string>('');

  const [isAuthModalOpen, setIsAuthModalOpen] = useState(false);
  const [authModalMessage, setAuthModalMessage] = useState('');

  const openAuthModal = (message?: string) => {
    setAuthModalMessage(message || '');
    setIsAuthModalOpen(true);
  };

  const fetchExchangeRate = React.useCallback(async () => {
    try {
      const res = await fetch('/api/exchange-rate');
      const data = await res.json();
      if (data.rate && data.rate > 1000) {
        setExchangeRate(data.rate);
        setRateSource(data.source || 'cbu.uz');
        setRateUpdatedAt(data.updatedAt || new Date().toISOString());
      }
    } catch (e) {
      console.warn('[CartContext] Failed to fetch exchange rate', e);
    }
  }, []);

  useEffect(() => {
    fetchExchangeRate();
  }, [fetchExchangeRate]);

  useEffect(() => {
    const devId = getOrCreateDeviceId();
    setDeviceId(devId);

    try {
      const savedUser = localStorage.getItem('mally_user');
      if (savedUser) {
        setUser(JSON.parse(savedUser));
      }
    } catch (e) {}

    try {
      const savedCart = localStorage.getItem('mally_cart_v3');
      if (savedCart) {
        const parsed = JSON.parse(savedCart);
        if (Array.isArray(parsed) && parsed.length > 0) {
          setCart(parsed);
        }
      }
    } catch (e) {}
  }, []);

  const saveCart = (items: CartItem[]) => {
    setCart(items);
    try {
      localStorage.setItem('mally_cart_v3', JSON.stringify(items));
    } catch (e) {}
  };

  const login = (userData: UserData, serverCart?: CartItem[]) => {
    setUser(userData);
    try {
      localStorage.setItem('mally_user', JSON.stringify(userData));
    } catch (e) {}

    if (serverCart && serverCart.length > 0) {
      const merged = mergeCarts(cart, serverCart);
      saveCart(merged);
    }
  };

  const logout = () => {
    setUser(null);
    try {
      localStorage.removeItem('mally_user');
    } catch (e) {}
  };

  const setCurrency = (c: Currency) => {
    setCurrencyState(c);
    if (typeof window !== 'undefined') {
      try {
        localStorage.setItem('mally_currency', c);
      } catch {}
    }
  };

  const formatPrice = (priceVal: number): string => {
    const num = Number(priceVal) || 0;
    const rate = exchangeRate && exchangeRate > 1000 ? exchangeRate : USD_EXCHANGE_RATE;
    // Базовая стоимость: если больше 1000 — значит в сумах, переводим в USD
    const usd = num > 1000 ? num / rate : num;

    if (currency === 'UZS') {
      const uzs = Math.round(usd * rate);
      return uzs.toLocaleString('ru-RU') + ' сум';
    }

    return new Intl.NumberFormat('en-US', {
      style: 'currency',
      currency: 'USD',
      minimumFractionDigits: 2,
      maximumFractionDigits: 2,
    }).format(usd);
  };

  const getItemQuantity = (key: string): number => {
    const item = cart.find((i) => String(i.id) === String(key) || String(i.sku) === String(key));
    return item ? item.quantity : 0;
  };

  const getMaxQuantity = (key: string): number => {
    const item = cart.find((i) => String(i.id) === String(key) || String(i.sku) === String(key));
    return item ? (item.maxQuantity || 12) : 12;
  };

  // ЗАЩИТА: Добавление в корзину строго с проверкой наличия на складе
  const addToCart = (product: any, autoOpen: boolean = false) => {
    const key = String(product.id || product.sku || 'prod');
    const existing = cart.find((i) => String(i.id) === key || String(i.sku) === key);

    const rawStock = product.quantity !== undefined ? product.quantity : (existing?.maxQuantity || 12);
    const stockCount = typeof rawStock === 'number' ? Math.max(0, rawStock) : 12;

    // 1. Товар распродан на складе
    if (product.status === 'OUT_OF_STOCK' || product.status === 'out_of_stock' || stockCount <= 0) {
      showToast('Товар распродан на складе', 'warning');
      return;
    }

    let updated: CartItem[];
    if (existing) {
      // 2. Достигнут лимит наличия на складе
      if (existing.quantity >= stockCount) {
        showToast(`Нельзя добавить больше: на складе доступно всего ${stockCount} шт.`, 'warning');
        return;
      }

      const nextQty = Math.min(stockCount, existing.quantity + 1);
      updated = cart.map((i) =>
        (String(i.id) === key || String(i.sku) === key)
          ? { ...i, quantity: nextQty, maxQuantity: stockCount }
          : i
      );
    } else {
      let mainImg = 'https://findy.uz/cdn/shop/files/sample.jpg';
      if (Array.isArray(product.images) && product.images.length > 0) {
        mainImg = product.images[0];
      } else if (typeof product.images === 'string' && product.images.startsWith('http')) {
        mainImg = product.images;
      } else if (product.image) {
        mainImg = product.image;
      }

      const bName = typeof product.brand === 'object' ? product.brand?.name : product.brand;
      const rawPrice = product.price ?? product.selling_price ?? 35;
      const itemPrice = typeof rawPrice === 'number' && rawPrice > 1000 ? rawPrice / 12850 : Number(rawPrice) || 35;

      const rawOldPrice = product.oldPrice ?? product.old_price ?? null;
      const itemOldPrice = rawOldPrice ? (typeof rawOldPrice === 'number' && rawOldPrice > 1000 ? rawOldPrice / 12850 : Number(rawOldPrice)) : null;

      updated = [
        ...cart,
        {
          id: key,
          sku: product.sku || key,
          title: product.title || product.name || 'Товар Mally',
          brand: bName || 'Mally Collection',
          price: itemPrice,
          oldPrice: itemOldPrice,
          image: mainImg,
          quantity: 1,
          maxQuantity: stockCount,
        },
      ];
    }

    saveCart(updated);
    if (autoOpen) {
      setIsCartOpen(true);
    }
  };

  const removeFromCart = (key: string) => {
    const updated = cart.filter((i) => String(i.id) !== String(key) && String(i.sku) !== String(key));
    saveCart(updated);
  };

  // ЗАЩИТА: Изменение количества не может превышать лимит наличия на складе
  const updateQuantity = (key: string, qty: number) => {
    if (qty <= 0) {
      removeFromCart(key);
      return;
    }

    const target = cart.find((i) => String(i.id) === String(key) || String(i.sku) === String(key));
    const maxLimit = target?.maxQuantity || 12;

    if (qty > maxLimit) {
      showToast(`На складе доступно максимум ${maxLimit} шт.`, 'warning');
      qty = maxLimit;
    }

    const updated = cart.map((i) =>
      (String(i.id) === String(key) || String(i.sku) === String(key))
        ? { ...i, quantity: qty }
        : i
    );
    saveCart(updated);
  };

  const clearCart = () => {
    saveCart([]);
  };

  const totalCount = cart.reduce((sum, item) => sum + item.quantity, 0);
  const totalAmount = cart.reduce((sum, item) => sum + item.price * item.quantity, 0);
  const freeShippingThreshold = FREE_SHIPPING_THRESHOLD;
  const freeShippingRemaining = Math.max(0, freeShippingThreshold - totalAmount);

  return (
    <CartContext.Provider
      value={{
        cart,
        items: cart,
        currency,
        setCurrency,
        formatPrice,
        exchangeRate,
        rateSource,
        rateUpdatedAt,
        fetchExchangeRate,
        isCartOpen,
        setIsCartOpen,
        isCheckoutOpen,
        setIsCheckoutOpen,
        addToCart,
        removeFromCart,
        updateQuantity,
        clearCart,
        getItemQuantity,
        getMaxQuantity,
        totalCount,
        totalItems: totalCount,
        totalAmount,
        subtotal: totalAmount,
        freeShippingThreshold,
        freeShippingRemaining,
        user,
        isAuthenticated: !!user,
        login,
        logout,
        deviceId,
        isAuthModalOpen,
        setIsAuthModalOpen,
        authModalMessage,
        openAuthModal,
      }}
    >
      {children}
      <AuthModal
        isOpen={isAuthModalOpen}
        onClose={() => setIsAuthModalOpen(false)}
        message={authModalMessage}
      />
    </CartContext.Provider>
  );
}

export function useCart() {
  const context = useContext(CartContext);
  if (!context) {
    throw new Error('useCart must be used within a CartProvider');
  }
  return context;
}
