import React, { useEffect } from 'react';
import { BrowserRouter, Routes, Route, useLocation } from 'react-router-dom';
import { LanguageProvider } from '../context/LanguageContext';
import { ToastProvider } from '../context/ToastContext';
import { FavoritesProvider } from '../context/FavoritesContext';
import { CartProvider } from '../context/CartContext';

import HomePage from '../pages/HomePage';
import CatalogPage from '../pages/CatalogPage';
import CartPage from '../pages/CartPage';
import CheckoutPage from '../pages/CheckoutPage';
import OrderReceiptPage from '../pages/OrderReceiptPage';
import TrackPage from '../pages/TrackPage';
import ProfilePage from '../pages/ProfilePage';
import FavoritesPage from '../pages/FavoritesPage';
import ProductDetailPage from '../pages/ProductDetailPage';
import ReturnsPage from '../pages/ReturnsPage';

function ScrollToTop() {
  const { pathname } = useLocation();

  useEffect(() => {
    window.scrollTo(0, 0);
  }, [pathname]);

  return null;
}

export default function StoreApp() {
  return (
    <LanguageProvider>
      <ToastProvider>
        <FavoritesProvider>
          <CartProvider>
            <BrowserRouter>
              <ScrollToTop />
              <Routes>
                <Route path="/" element={<HomePage />} />
                <Route path="/catalog" element={<CatalogPage />} />
                <Route path="/cart" element={<CartPage />} />
                <Route path="/checkout" element={<CheckoutPage />} />
                <Route path="/checkout/:orderNumber" element={<OrderReceiptPage />} />
                <Route path="/checkout/:orderNumber/:orderHash" element={<OrderReceiptPage />} />
                <Route path="/track" element={<TrackPage />} />
                <Route path="/profile" element={<ProfilePage />} />
                <Route path="/favorites" element={<FavoritesPage />} />
                <Route path="/products/:slug" element={<ProductDetailPage />} />
                <Route path="/returns" element={<ReturnsPage />} />
                <Route path="*" element={<HomePage />} />
              </Routes>
            </BrowserRouter>
          </CartProvider>
        </FavoritesProvider>
      </ToastProvider>
    </LanguageProvider>
  );
}
