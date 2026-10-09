import React, { createContext, useContext, useEffect, useState } from 'react';

export interface FavoriteProduct {
  id: string;
  sku?: string;
  slug?: string;
  title: string;
  price: number;
  oldPrice?: number | null;
  image?: string;
  brand?: string;
  inStock?: boolean;
}

interface FavoritesContextType {
  favorites: FavoriteProduct[];
  isFavorite: (productId: string) => boolean;
  toggleFavorite: (product: FavoriteProduct) => void;
  removeFavorite: (productId: string) => void;
  favoritesCount: number;
}

const FavoritesContext = createContext<FavoritesContextType | undefined>(undefined);

export function FavoritesProvider({ children }: { children: React.ReactNode }) {
  const [favorites, setFavorites] = useState<FavoriteProduct[]>([]);
  const [isLoaded, setIsLoaded] = useState(false);

  useEffect(() => {
    try {
      const stored = localStorage.getItem('mally_favorites_v1');
      if (stored) {
        setFavorites(JSON.parse(stored));
      }
    } catch (e) {
      console.error('[Favorites] Error loading from storage:', e);
    } finally {
      setIsLoaded(true);
    }
  }, []);

  const saveFavorites = (items: FavoriteProduct[]) => {
    setFavorites(items);
    try {
      localStorage.setItem('mally_favorites_v1', JSON.stringify(items));
    } catch (e) {
      console.error('[Favorites] Error saving to storage:', e);
    }
  };

  const isFavorite = (productId: string): boolean => {
    return favorites.some((item) => item.id === productId);
  };

  const toggleFavorite = (product: FavoriteProduct) => {
    const exists = isFavorite(product.id);
    let updated: FavoriteProduct[];

    if (exists) {
      updated = favorites.filter((item) => item.id !== product.id);
    } else {
      updated = [product, ...favorites.filter((item) => item.id !== product.id)];
    }

    saveFavorites(updated);

    fetch('/api/products/favorite', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({
        productId: product.id,
        isFavorite: !exists,
      }),
    }).catch(() => {});
  };

  const removeFavorite = (productId: string) => {
    const updated = favorites.filter((item) => item.id !== productId);
    saveFavorites(updated);

    fetch('/api/products/favorite', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({
        productId,
        isFavorite: false,
      }),
    }).catch(() => {});
  };

  return (
    <FavoritesContext.Provider
      value={{
        favorites,
        isFavorite,
        toggleFavorite,
        removeFavorite,
        favoritesCount: favorites.length,
      }}
    >
      {children}
    </FavoritesContext.Provider>
  );
}

export function useFavorites() {
  const context = useContext(FavoritesContext);
  if (!context) {
    throw new Error('useFavorites must be used within a FavoritesProvider');
  }
  return context;
}
