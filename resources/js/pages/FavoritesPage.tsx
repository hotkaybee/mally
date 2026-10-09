import React, { useState } from 'react';
import Link from '../components/Link';
import Header from '../components/Header';
import Footer from '../components/Footer';
import ProductCard from '../components/ProductCard';
import { useFavorites } from '../context/FavoritesContext';

export default function FavoritesPage() {
  const { favorites } = useFavorites();

  return (
    <div className="min-h-screen bg-[#FCECEC] flex flex-col font-sans text-[#1C1917]">
      <Header />

      <main className="max-w-[1720px] mx-auto px-4 sm:px-6 lg:px-8 py-8 w-full flex-1">
        <div className="text-xs text-[#8C827A] flex items-center gap-2 mb-6">
          <Link href="/" className="hover:text-black transition">
            Главная
          </Link>
          <span>/</span>
          <span className="text-[#1C1917] font-medium">Мои любимые товары</span>
        </div>

        <div className="flex flex-col sm:flex-row sm:items-center justify-between gap-4 mb-8 pb-4 border-b border-[#EAE3DE]">
          <div>
            <h1 className="font-serif text-2xl sm:text-3xl font-bold text-[#1C1917] flex items-center gap-3">
              <span>Избранное</span>
              <span className="text-xs px-3 py-1 bg-white border border-[#EAE3DE] rounded-full font-sans font-semibold text-[#8C827A]">
                {favorites.length}
              </span>
            </h1>
            <p className="text-xs text-[#78716C] mt-1">
              Товары, которые вы сохранили. Они доступны для быстрого заказа в любое время.
            </p>
          </div>

          <Link
            href="/catalog"
            className="inline-flex items-center gap-2 text-xs font-semibold text-[#1C1917] hover:underline"
          >
            <span>В каталог товаров</span>
            <i className="fa-solid fa-arrow-right text-[10px]" />
          </Link>
        </div>

        {favorites.length === 0 ? (
          <div className="bg-white rounded-3xl p-12 text-center max-w-lg mx-auto border border-[#EAE3DE] shadow-xs my-8 space-y-4">
            <div className="w-16 h-16 mx-auto rounded-full bg-rose-50 text-rose-500 flex items-center justify-center text-2xl">
              <i className="fa-regular fa-heart" />
            </div>
            <h2 className="font-serif text-xl font-bold text-[#1C1917]">В избранном пока пусто</h2>
            <p className="text-xs text-[#78716C] leading-relaxed">
              Нажимайте на сердечко рядом с понравившимися товарами в каталоге, чтобы не потерять их и следить за наличием!
            </p>
            <div className="pt-2">
              <Link
                href="/catalog"
                className="inline-block px-6 py-3.5 bg-[#1C1917] text-white rounded-2xl text-xs font-semibold uppercase tracking-wider hover:bg-black transition shadow-sm"
              >
                Перейти в каталог
              </Link>
            </div>
          </div>
        ) : (
          <div className="grid grid-cols-2 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-4 xl:grid-cols-5 2xl:grid-cols-6 gap-3 sm:gap-4 lg:gap-5">
            {favorites.map((item) => (
              <ProductCard
                key={item.id}
                product={item}
              />
            ))}
          </div>
        )}
      </main>

      <Footer />
    </div>
  );
}
