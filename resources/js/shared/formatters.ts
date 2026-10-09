/**
 * Formats numeric price into currency based on active selection (USD $ or UZS сум).
 */
export function formatCurrency(
  price: number | string | null | undefined,
  currency: 'USD' | 'UZS' = 'USD',
  exchangeRate: number = 12850
): string {
  if (price === null || price === undefined || price === '') {
    return currency === 'UZS' ? '0 сум' : '$0.00';
  }
  const num = typeof price === 'string' ? parseFloat(price) : price;
  if (isNaN(num)) {
    return currency === 'UZS' ? '0 сум' : '$0.00';
  }

  const rate = exchangeRate && exchangeRate > 1000 ? exchangeRate : 12850;
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
}

/**
 * Universal price formatter respecting active currency (UZS/USD) from localStorage.
 */
export function formatUSD(price: number | string | null | undefined): string {
  let curr: 'USD' | 'UZS' = 'USD';
  if (typeof window !== 'undefined') {
    try {
      const saved = localStorage.getItem('mally_currency');
      if (saved === 'UZS' || saved === 'USD') curr = saved;
    } catch {}
  }
  return formatCurrency(price, curr);
}

/**
 * Format date string into Russian locale representation.
 */
export function formatDate(dateString: string | null | undefined): string {
  if (!dateString) return '';
  try {
    const d = new Date(dateString);
    return new Intl.DateTimeFormat('ru-RU', {
      day: 'numeric',
      month: 'long',
      year: 'numeric',
    }).format(d);
  } catch {
    return dateString;
  }
}
