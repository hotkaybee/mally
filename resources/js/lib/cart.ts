export interface CartItem {
  id: string;
  sku: string;
  title: string;
  brand: string;
  price: number;
  priceUsd?: number | null;
  oldPrice?: number | null;
  image: string;
  quantity: number;
  maxQuantity: number;
}

export function mergeCarts(cartA: any[], cartB: any[]): CartItem[] {
  const map = new Map<string, CartItem>();

  const processItems = (items: any[]) => {
    if (!Array.isArray(items)) return;
    for (const item of items) {
      if (!item) continue;
      const key = String(item.id || item.sku || '');
      if (!key) continue;

      const maxQty = Math.max(1, Number(item.maxQuantity) || 99);
      const qty = Math.max(1, Number(item.quantity) || 1);

      if (map.has(key)) {
        const existing = map.get(key)!;
        const combinedMaxQty = Math.max(existing.maxQuantity || 99, maxQty);
        const combinedQty = Math.min((existing.quantity || 1) + qty, combinedMaxQty);

        map.set(key, {
          ...existing,
          ...item,
          id: key,
          sku: item.sku || existing.sku || key,
          quantity: combinedQty,
          maxQuantity: combinedMaxQty,
        });
      } else {
        map.set(key, {
          id: key,
          sku: item.sku || key,
          title: item.title || 'Товар',
          brand: item.brand || 'Mally Collection',
          price: Number(item.price) || 0,
          priceUsd: item.priceUsd !== undefined ? item.priceUsd : null,
          oldPrice: item.oldPrice !== undefined ? item.oldPrice : null,
          image: item.image || 'https://findy.uz/cdn/shop/files/sample.jpg',
          quantity: qty,
          maxQuantity: maxQty,
        });
      }
    }
  };

  processItems(cartA);
  processItems(cartB);

  return Array.from(map.values());
}
