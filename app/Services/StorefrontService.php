<?php

namespace App\Services;

use App\Models\Category;
use App\Models\Customer;
use App\Models\Product;
use App\Models\Review;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;

class StorefrontService
{
    public const PRODUCTS_CACHE_KEY = 'mally_storefront_products_v3';

    private ?array $cachedExchangeRate = null;

    private ?array $cachedAllProducts = null;

    private array $cachedHeaderCategories = [];

    private ?Customer $cachedCustomer = null;

    private bool $customerResolved = false;

    public function __construct(
        private readonly ExchangeRateService $exchangeRateService,
    ) {}

    public function getLocale(): string
    {
        if (session()->has('mally_locale')) {
            $saved = (string) session('mally_locale');
            if (in_array($saved, ['ru', 'uz', 'en'], true)) {
                return $saved;
            }
        }

        // При первом входе определяем язык из браузера и запоминаем в сессии
        $detected = 'ru';
        $acceptLang = strtolower((string) request()->header('Accept-Language', ''));
        if ($acceptLang !== '') {
            $parts = explode(',', $acceptLang);
            foreach ($parts as $part) {
                $code = substr(trim(explode(';', $part)[0]), 0, 2);
                if (in_array($code, ['ru', 'uz', 'en'], true)) {
                    $detected = $code;
                    break;
                }
            }
        }

        session(['mally_locale' => $detected]);
        app()->setLocale($detected);

        return $detected;
    }

    public function setLocale(string $lang): void
    {
        if (in_array($lang, ['ru', 'uz', 'en'], true)) {
            session(['mally_locale' => $lang]);
            app()->setLocale($lang);
        }
    }

    public function normalizeAndValidateUzPhone(string $rawPhone): ?string
    {
        $digits = preg_replace('/\D/', '', $rawPhone);
        if (! $digits) {
            return null;
        }

        if (strlen($digits) === 9) {
            $digits = '998'.$digits;
        }

        if (strlen($digits) !== 12 || ! str_starts_with($digits, '998')) {
            return null;
        }

        $sub = substr($digits, 3); // 9 цифр после 998

        return sprintf(
            '+998 %s %s %s %s',
            substr($sub, 0, 2),
            substr($sub, 2, 3),
            substr($sub, 5, 2),
            substr($sub, 7, 2)
        );
    }

    public function getCurrency(): string
    {
        $curr = session('mally_currency', 'USD');

        return in_array($curr, ['USD', 'UZS'], true) ? $curr : 'USD';
    }

    public function setCurrency(string $currency): void
    {
        if (in_array($currency, ['USD', 'UZS'], true)) {
            session(['mally_currency' => $currency]);
        }
    }

    public function getExchangeRateData(): array
    {
        if ($this->cachedExchangeRate === null) {
            $data = $this->exchangeRateService->getUsdRate();
            $rate = (isset($data['rate']) && $data['rate'] > 0) ? (float) $data['rate'] : null;
            $this->cachedExchangeRate = [
                'rate' => $rate,
                'source' => $data['source'] ?? 'cbu.uz',
                'date' => $data['date'] ?? date('Y-m-d'),
            ];
        }

        return $this->cachedExchangeRate;
    }

    public function getExchangeRate(): float
    {
        return (float) ($this->getExchangeRateData()['rate'] ?? 0.0);
    }

    /**
     * All prices in the database are USD. UZS is only a display conversion.
     */
    public function formatPrice(float|int|string|null $priceUsd, ?string $forceCurrency = null): string
    {
        $usd = (float) ($priceUsd ?? 0);
        $currency = $forceCurrency ?? $this->getCurrency();

        if ($currency === 'UZS') {
            $rate = $this->getExchangeRate();
            if ($rate <= 0) {
                return '—';
            }

            return number_format((int) round($usd * $rate), 0, '.', ' ').' сум';
        }

        return '$'.number_format($usd, 2, '.', ',');
    }

    public function getHeaderCategories(?string $lang = null): array
    {
        $lang = $lang ?? $this->getLocale();
        app()->setLocale($lang);

        if (isset($this->cachedHeaderCategories[$lang])) {
            return $this->cachedHeaderCategories[$lang];
        }

        return $this->cachedHeaderCategories[$lang] = Cache::remember("mally_header_cats_v2_{$lang}", 300, function () use ($lang) {
            $defaults = [
                ['ru' => 'Каталог', 'uz' => 'Katalog', 'en' => 'Catalog'],
                ['ru' => 'Наборы', 'uz' => "To'plamlar", 'en' => 'Sets'],
                ['ru' => 'Бренды', 'uz' => 'Brendlar', 'en' => 'Brands'],
                ['ru' => 'Макияж', 'uz' => 'Makiyaj', 'en' => 'Makeup'],
                ['ru' => 'Уход', 'uz' => 'Parvarish', 'en' => 'Skincare'],
                ['ru' => 'Парфюмерия', 'uz' => 'Parfyumeriya', 'en' => 'Fragrance'],
                ['ru' => 'Аксессуары', 'uz' => 'Aksessuarlar', 'en' => 'Accessories'],
                ['ru' => 'Скидки', 'uz' => 'Chegirmalar', 'en' => 'Discounts'],
            ];

            $names = [];
            foreach ($defaults as $def) {
                $names[] = $def[$lang] ?? $def['ru'];
            }

            $dbCategories = Category::all();
            foreach ($dbCategories as $cat) {
                $catName = $cat->name;
                if ($catName && ! in_array($catName, $names, true)) {
                    $names[] = $catName;
                }
            }

            return $names;
        });
    }

    /**
     * Storefront catalogue: real products from the database only.
     * Cached briefly for listings; cart and checkout always read live rows.
     */
    public function getAllProducts(): array
    {
        if ($this->cachedAllProducts !== null) {
            return $this->cachedAllProducts;
        }

        return $this->cachedAllProducts = Cache::remember(self::PRODUCTS_CACHE_KEY, 120, function () {
            return Product::with(['brand', 'category'])
                ->orderBy('id')
                ->get()
                ->map(fn (Product $p) => $this->formatProduct($p))
                ->all();
        });
    }

    public static function forgetProductsCache(): void
    {
        Cache::forget(self::PRODUCTS_CACHE_KEY);
    }

    public function findProduct(string $slugOrSku, bool $withReviews = false): ?array
    {
        $product = $this->findProductBySlugOrId($slugOrSku);

        return $product ? $this->formatProduct($product, $withReviews) : null;
    }

    /**
     * Whether the product can be sold at all (has stock, a price and isn't
     * switched off in the admin).
     */
    public function isSellable(Product $product): bool
    {
        return (int) $product->quantity > 0
            && $product->status !== 'out_of_stock'
            && (float) $product->selling_price > 0;
    }

    /**
     * Units that can still be sold, read live from the receipts. Never shown
     * to customers.
     */
    public function availableQuantity(Product $product): int
    {
        if ($product->status === 'out_of_stock' || (float) $product->selling_price <= 0) {
            return 0;
        }

        return max(0, (int) $product->receipts()->sum('quantity_remaining'));
    }

    public function getDatabaseReviewsForProduct(?int $productId): array
    {
        if (! $productId) {
            return [];
        }

        return Review::with(['product', 'order.customer'])
            ->where('is_approved', true)
            ->where('product_id', $productId)
            ->latest('id')
            ->get()
            ->map(fn (Review $rev) => [
                'id' => 'db_rev_'.$rev->id,
                'author' => $rev->author_name,
                'city' => $rev->author_city,
                'rating' => (int) $rev->rating,
                'date' => $rev->created_at?->timezone('Asia/Tashkent')->format('d.m.Y'),
                'title' => $rev->product?->name ?: '',
                'comment' => $rev->comment,
                'text' => $rev->comment,
                'photos' => is_array($rev->photos) ? $rev->photos : [],
            ])
            ->all();
    }

    public function getRecommendedProducts(array $currentProduct, int $limit = 4): array
    {
        $all = $this->getAllProducts();
        $curId = (string) ($currentProduct['id'] ?? '');
        $brandName = mb_strtolower((string) ($currentProduct['brand'] ?? ''));
        $catName = mb_strtolower((string) ($currentProduct['category'] ?? ''));

        $available = array_values(array_filter($all, fn ($p) => (string) $p['id'] !== $curId));

        $sameBrand = array_values(array_filter($available, fn ($p) => mb_strtolower((string) ($p['brand'] ?? '')) === $brandName));
        $sameCat = array_values(array_filter($available, fn ($p) => mb_strtolower((string) ($p['category'] ?? '')) === $catName && mb_strtolower((string) ($p['brand'] ?? '')) !== $brandName));
        $others = array_values(array_filter($available, fn ($p) => mb_strtolower((string) ($p['brand'] ?? '')) !== $brandName && mb_strtolower((string) ($p['category'] ?? '')) !== $catName));

        return array_slice(array_merge($sameBrand, $sameCat, $others), 0, $limit);
    }

    // =========================================================================
    // SERVER-SIDE CART (Laravel Session)
    // =========================================================================

    public function getCart(): array
    {
        $cart = session('mally_cart', []);

        return is_array($cart) ? array_values($cart) : [];
    }

    public function saveCart(array $cart): void
    {
        session(['mally_cart' => array_values($cart)]);
    }

    public function getItemQuantityInCart(string $key): int
    {
        foreach ($this->getCart() as $item) {
            if ((string) ($item['id'] ?? '') === $key || (string) ($item['sku'] ?? '') === $key) {
                return max(0, (int) ($item['quantity'] ?? 0));
            }
        }

        return 0;
    }

    public function addToCart(string $productKey): array
    {
        $product = $this->findProductBySlugOrId($productKey);
        if (! $product) {
            return ['success' => false, 'message' => __('This product is no longer available.'), 'type' => 'error'];
        }

        $available = $this->availableQuantity($product);
        if ($available <= 0) {
            return ['success' => false, 'message' => __('This product is sold out.'), 'type' => 'warning'];
        }

        $cart = $this->getCart();
        $id = (string) $product->id;
        $found = false;

        foreach ($cart as &$item) {
            if ((string) ($item['id'] ?? '') === $id) {
                $currentQty = max(0, (int) ($item['quantity'] ?? 0));
                if ($currentQty + 1 > $available) {
                    $item['quantity'] = min($currentQty, $available);
                    $this->saveCart($cart);

                    return ['success' => false, 'message' => __('No more units of this product are available.'), 'type' => 'warning'];
                }
                $item['quantity'] = $currentQty + 1;
                $item['price'] = (float) $product->selling_price;
                $found = true;
                break;
            }
        }
        unset($item);

        if (! $found) {
            $formatted = $this->formatProduct($product);
            $cart[] = [
                'id' => $id,
                'sku' => $formatted['sku'],
                'title' => $formatted['title'],
                'brand' => $formatted['brand'],
                'price' => $formatted['price'],
                'image' => $formatted['image'],
                'quantity' => 1,
            ];
        }

        $this->saveCart($cart);

        return [
            'success' => true,
            'message' => $this->t('product.added_to_cart', 'Товар добавлен в корзину!'),
            'type' => 'success',
        ];
    }

    public function updateCartQuantity(string $key, int $qty): array
    {
        if ($qty <= 0) {
            $this->removeFromCart($key);

            return ['success' => true];
        }

        $product = $this->findProductBySlugOrId($key);
        $cart = $this->getCart();
        $warning = null;
        $updatedCart = [];

        foreach ($cart as $item) {
            if ($this->cartItemMatches($item, $key, $product)) {
                $available = $product ? $this->availableQuantity($product) : 0;
                if ($available <= 0) {
                    $warning = __('This product is sold out and was removed from your cart.');

                    continue;
                }

                if ($qty > $available) {
                    $qty = $available;
                    $warning = __('No more units of this product are available.');
                }

                $item['quantity'] = $qty;
                $item['price'] = (float) $product->selling_price;
            }

            $updatedCart[] = $item;
        }

        $this->saveCart($updatedCart);

        return $warning
            ? ['success' => false, 'message' => $warning, 'type' => 'warning']
            : ['success' => true];
    }

    /** Whether one more unit of this product may be added to the cart. */
    public function canIncrease(string $productKey): bool
    {
        $product = $this->findProductBySlugOrId($productKey);

        return $product !== null && $this->getItemQuantityInCart($productKey) < $this->availableQuantity($product);
    }

    private function cartItemMatches(array $item, string $key, ?Product $product): bool
    {
        if ($product && (string) ($item['id'] ?? '') === (string) $product->id) {
            return true;
        }

        return (string) ($item['id'] ?? '') === $key || (string) ($item['sku'] ?? '') === $key;
    }

    public function removeFromCart(string $key): void
    {
        $cart = array_filter($this->getCart(), function ($item) use ($key) {
            return (string) ($item['id'] ?? '') !== $key && (string) ($item['sku'] ?? '') !== $key;
        });

        $this->saveCart(array_values($cart));
    }

    public function clearCart(): void
    {
        session()->forget(['mally_cart', 'mally_order_note']);
    }

    public function getCartCount(): int
    {
        return array_reduce($this->getCart(), fn (int $sum, array $item) => $sum + max(0, (int) ($item['quantity'] ?? 0)), 0);
    }

    public function getCartTotalUsd(): float
    {
        return (float) ($this->getCartItemsWithProducts()['subtotal_usd'] ?? 0.0);
    }

    /**
     * Revalidate the session cart against live price and stock. Unavailable
     * items are removed, quantities are capped, and the caller is told
     * whether anything changed so checkout can't proceed on stale data.
     */
    public function validateAndSyncCart(): array
    {
        $cart = $this->getCart();
        $products = $this->productsForCart($cart);
        $syncedCart = [];
        $messages = [];

        foreach ($cart as $item) {
            $title = (string) ($item['title'] ?? '');
            $rawQty = (int) ($item['quantity'] ?? 0);
            $product = $products[(int) ($item['id'] ?? 0)] ?? null;

            if (! $product || $rawQty <= 0) {
                $messages[] = __('":title" is no longer available and was removed from your cart.', ['title' => $title]);

                continue;
            }

            $available = $this->availableQuantity($product);
            if ($available <= 0) {
                $messages[] = __('":title" is sold out and was removed from your cart.', ['title' => $product->name]);

                continue;
            }

            $livePrice = round((float) $product->selling_price, 2);
            if (abs(round((float) ($item['price'] ?? $livePrice), 2) - $livePrice) >= 0.01) {
                $messages[] = __('The price of ":title" has changed to :price.', ['title' => $product->name, 'price' => $this->formatPrice($livePrice)]);
            }

            if ($rawQty > $available) {
                $rawQty = $available;
                $messages[] = __('Fewer units of ":title" are available now; the quantity was reduced.', ['title' => $product->name]);
            }

            $item['title'] = $product->name;
            $item['price'] = $livePrice;
            $item['quantity'] = $rawQty;
            unset($item['maxQuantity']);

            $syncedCart[] = $item;
        }

        $this->saveCart($syncedCart);

        return [
            'valid' => empty($messages),
            'messages' => $messages,
            'cart' => $this->getCartItemsWithProducts(),
        ];
    }

    /**
     * @return array<int, Product> keyed by product id
     */
    private function productsForCart(array $cart): array
    {
        $ids = array_filter(array_map(fn ($item) => (int) ($item['id'] ?? 0), $cart));

        return $ids === []
            ? []
            : Product::with(['brand', 'category'])->whereIn('id', $ids)->get()->keyBy('id')->all();
    }

    // =========================================================================
    // SERVER-SIDE FAVORITES (Laravel Session)
    // =========================================================================

    public function getFavoriteKeys(): array
    {
        $favs = session('mally_favorites', []);

        return is_array($favs) ? array_values(array_unique($favs)) : [];
    }

    public function isFavorite(string $productKey): bool
    {
        $keys = $this->getFavoriteKeys();
        if (empty($keys)) {
            return false;
        }
        if (in_array($productKey, $keys, true)) {
            return true;
        }
        $product = $this->findProduct($productKey);
        if ($product) {
            return in_array((string) $product['id'], $keys, true) || in_array((string) $product['sku'], $keys, true);
        }

        return false;
    }

    public function toggleFavorite(string $productKey): bool
    {
        $product = $this->findProduct($productKey);
        $canonicalKey = $product ? (string) ($product['sku'] ?? $product['id']) : $productKey;
        $altKey = $product ? (string) ($product['id'] ?? $productKey) : $productKey;

        $keys = $this->getFavoriteKeys();
        if (in_array($canonicalKey, $keys, true) || in_array($altKey, $keys, true) || in_array($productKey, $keys, true)) {
            $keys = array_values(array_filter($keys, fn ($k) => $k !== $canonicalKey && $k !== $altKey && $k !== $productKey));
            session(['mally_favorites' => $keys]);

            return false;
        }

        $keys[] = $canonicalKey;
        session(['mally_favorites' => array_values(array_unique($keys))]);

        return true;
    }

    public function getFavoriteProducts(): array
    {
        $keys = $this->getFavoriteKeys();
        if (empty($keys)) {
            return [];
        }

        $all = $this->getAllProducts();
        $result = [];
        foreach ($all as $p) {
            if (in_array((string) $p['sku'], $keys, true) || in_array((string) $p['id'], $keys, true)) {
                $result[] = $p;
            }
        }

        return $result;
    }

    public function getFavoritesCount(): int
    {
        return count($this->getFavoriteKeys());
    }

    // =========================================================================
    // SERVER-SIDE CUSTOMER AUTHENTICATION (Session + DB)
    // =========================================================================

    public function getCurrentCustomer(): ?Customer
    {
        $customerId = session('mally_customer_id');
        if (! $customerId) {
            $this->cachedCustomer = null;
            $this->customerResolved = false;

            return null;
        }

        if ($this->customerResolved && $this->cachedCustomer && (int) $this->cachedCustomer->id === (int) $customerId) {
            return $this->cachedCustomer;
        }

        $this->cachedCustomer = Customer::find($customerId);
        $this->customerResolved = true;

        return $this->cachedCustomer;
    }

    public function getAuthenticatedCustomer(): ?Customer
    {
        return $this->getCurrentCustomer();
    }

    public function loginCustomer(Customer $customer): void
    {
        // New session id on login, so a planted session id can't be reused.
        session()->regenerate();
        session(['mally_customer_id' => $customer->id]);
        $this->cachedCustomer = $customer;
        $this->customerResolved = true;
    }

    public function logoutCustomer(): void
    {
        session()->invalidate();
        session()->regenerateToken();
        $this->cachedCustomer = null;
        $this->customerResolved = false;
    }

    // =========================================================================
    // PROMO CODES & CART DETAILS
    // =========================================================================

    public function getPromoCode(): ?string
    {
        $code = session('mally_promo_code');
        if (! is_string($code) || $this->promoPercent($code) === 0) {
            if ($code !== null) {
                session()->forget('mally_promo_code');
            }

            return null;
        }

        return strtoupper(trim($code));
    }

    /** Discount percent for a promo code, 0 if the code isn't valid. */
    public function promoPercent(?string $code): int
    {
        $configured = strtoupper(trim((string) config('services.store.promo_code')));
        $clean = strtoupper(trim((string) $code));

        return $configured !== '' && $clean === $configured
            ? (int) config('services.store.promo_percent', 0)
            : 0;
    }

    public function applyPromoCode(string $code, ?float $totalAmountUsd = null): array|bool
    {
        $clean = strtoupper(trim($code));
        $percent = $this->promoPercent($clean);
        $subtotal = $totalAmountUsd ?? $this->getCartTotalUsd();

        if ($percent > 0) {
            session(['mally_promo_code' => $clean]);

            return $totalAmountUsd === null ? true : [
                'success' => true,
                'code' => $clean,
                'discountUsd' => round($subtotal * $percent / 100, 2),
            ];
        }

        session()->forget('mally_promo_code');

        return $totalAmountUsd === null ? false : [
            'success' => false,
            'error' => __('Invalid promo code'),
        ];
    }

    /**
     * Cart lines with live product data. Prices are USD from the database;
     * lines whose product no longer exists are skipped.
     */
    public function getCartItemsWithProducts(): array
    {
        $cart = $this->getCart();
        $products = $this->productsForCart($cart);
        $items = [];
        $subtotalUsd = 0.0;

        foreach ($cart as $c) {
            $product = $products[(int) ($c['id'] ?? 0)] ?? null;
            if (! $product) {
                continue;
            }

            $formatted = $this->formatProduct($product);
            $qty = max(1, (int) ($c['quantity'] ?? 1));
            $lineTotal = round($formatted['price'] * $qty, 2);
            $subtotalUsd += $lineTotal;

            $items[] = [
                'quantity' => $qty,
                'line_total_usd' => $lineTotal,
                'product' => array_merge($formatted, [
                    'canIncrease' => $qty < $this->availableQuantity($product),
                ]),
            ];
        }

        $promoCode = $this->getPromoCode();
        $discountPercent = $this->promoPercent($promoCode);
        $discountUsd = round($subtotalUsd * $discountPercent / 100, 2);

        return [
            'items' => $items,
            'subtotal_usd' => round($subtotalUsd, 2),
            'discount_usd' => $discountUsd,
            'discount_percent' => $discountPercent,
            'promo_code' => $promoCode,
            'total_usd' => max(0.0, round($subtotalUsd - $discountUsd, 2)),
        ];
    }

    public function getFeaturedProducts(int $limit = 4): array
    {
        return array_slice($this->getAllProducts(), 0, $limit);
    }

    /**
     * Customer-facing product data. Exposes availability only, never the
     * stock count.
     */
    public function formatProduct(Product $p, bool $withReviews = false): array
    {
        $category = $p->category;
        $catNameRu = $category?->name_ru ?: ($category?->name ?: 'Косметика');
        $price = round((float) $p->selling_price, 2);
        $img = $p->image ? (str_starts_with($p->image, 'http') ? $p->image : asset($p->image)) : Product::placeholderUrl();
        $inStock = $this->isSellable($p);

        return [
            'id' => (string) $p->id,
            'sku' => $p->sku ?: 'ML-P-'.$p->id,
            'slug' => Str::slug($p->name).'-'.$p->id,
            'title' => $p->name,
            'name' => $p->name,
            'brand' => $p->brand?->name ?: 'Mally Collection',
            'category' => $catNameRu,
            'category_en' => $category?->name_en ?: $catNameRu,
            'category_uz' => $category?->name_uz ?: $catNameRu,
            'price' => $price,
            'selling_price' => $price,
            'oldPrice' => null,
            'old_price' => null,
            'inStock' => $inStock,
            'status' => $inStock ? 'ACTIVE' : 'OUT_OF_STOCK',
            'isNew' => $p->created_at && $p->created_at->gt(now()->subDays(30)),
            'image' => $img,
            'images' => [$img],
            'description' => $p->description ?: 'Премиальная оригинальная продукция из США.',
            'features' => ['100% Оригинал из США', 'Сертифицированная косметика'],
            'howToUse' => 'Наносите в соответствии с рекомендациями производителя.',
            'reviews' => $withReviews ? $this->getDatabaseReviewsForProduct($p->id) : [],
        ];
    }

    public function findProductBySlugOrId(string $slug): ?Product
    {
        $decoded = trim(urldecode($slug));
        if ($decoded === '') {
            return null;
        }

        $product = Product::with(['brand', 'category'])->where('sku', $decoded)->first();

        if (! $product && ctype_digit($decoded)) {
            $product = Product::with(['brand', 'category'])->find((int) $decoded);
        }

        if (! $product && preg_match('/(?:ML-P-|-)(\d+)$/i', $decoded, $m)) {
            $product = Product::with(['brand', 'category'])->find((int) $m[1]);
        }

        return $product;
    }

    // =========================================================================
    // TRANSLATIONS (RU / UZ / EN)
    // =========================================================================

    public function t(string $key, ?string $fallback = null): string
    {
        $lang = $this->getLocale();
        $dict = self::TRANSLATIONS[$lang] ?? self::TRANSLATIONS['ru'];

        return $dict[$key] ?? self::TRANSLATIONS['ru'][$key] ?? $fallback ?? $key;
    }

    public const TRANSLATIONS = [
        'ru' => [
            'nav.catalog' => 'Каталог',
            'nav.search' => 'Поиск косметики...',
            'nav.profile' => 'Профиль',
            'nav.favorites' => 'Избранное',
            'nav.cart' => 'Корзина',
            'nav.admin' => 'Админ-панель',
            'nav.login' => 'Войти',
            'nav.logout' => 'Выйти',
            'nav.orders' => 'Мои заказы',
            'nav.shipping_banner' => 'Отправка через Яндекс Go · Доставка оплачивается покупателем по тарифу',
            'nav.support' => 'Служба заботы @mallyuz',
            'nav.brands' => 'Бренды',
            'nav.categories' => 'Категории',
            'nav.all_products' => 'Все товары',
            'nav.contact' => 'Контактная форма',
            'nav.home' => 'Главная',
            'product.add_to_cart' => 'В корзину',
            'product.in_cart' => 'В корзине',
            'product.out_of_stock' => 'Распродано',
            'product.in_stock' => 'В наличии',
            'product.sku' => 'Арт:',
            'product.price' => 'сум',
            'product.quick_view' => 'Быстрый просмотр',
            'product.added_to_cart' => 'Товар добавлен в корзину!',
            'product.hit' => 'ХИТ',
            'product.new' => 'NEW',
            'product.order_us' => 'Заказать из США',
            'product.price_usd' => 'Стоимость в долларах США ($)',
            'product.price_uzs' => 'Стоимость в сумах (UZS)',
            'product.price_label' => 'Стоимость',
            'product.month_batch' => 'Партия этого месяца',
            'product.pcs' => 'шт.',
            'product.of_total' => 'из',
            'product.remaining' => 'Осталось',
            'product.sold_month' => 'Продано за месяц',
            'product.awaiting_stock' => 'Ожидается новое поступление',
            'product.batch_sold_out' => 'Партия полностью распродана',
            'product.savings' => 'Экономия',
            'product.order_tg' => 'Заказать байером из США в Telegram',
            'product.tg_note' => 'Выкупаем напрямую в Sephora / Ulta USA по фиксированной стоимости',
            'product.max_stock_selected' => 'Выбрано максимальное количество товара на складе',
            'product.buy_tg' => 'Купить в Telegram',
            'product.usa_origin' => '100% Оригинал из США',
            'product.batch_verified' => 'Батч-код проверяется',
            'product.desc_texture' => 'Описание и текстура',
            'product.desc_subtitle' => 'Особенности текстуры, формула и эффект от применения',
            'product.key_benefits' => 'Ключевые преимущества формулы:',
            'product.ingredients_title' => 'Состав и компоненты',
            'product.ingredients_subtitle' => 'Стандарты чистоты Clean Beauty, безопасность и активные вещества',
            'product.how_to_use_title' => 'Способ применения',
            'product.how_to_use_subtitle' => 'Пошаговая инструкция и профессиональные советы',
            'product.specs_title' => 'Характеристики товара',
            'product.param' => 'Параметр',
            'product.val' => 'Значение',
            'product.sku_label' => 'Артикул (SKU)',
            'product.brand' => 'Бренд',
            'product.category' => 'Категория',
            'product.origin_country' => 'Страна закупки',
            'product.origin_usa' => 'США (Официальные дистрибьюторы Sephora USA / Ulta)',
            'product.delivery_guarantee_title' => 'Доставка и гарантии',
            'product.recommended' => 'Вам также может понравиться',
            'product.recommended_subtitle' => 'Средства из этой коллекции и похожие бестселлеры',
            'product.copied' => 'Скопировано',
            'product.copy_sku' => 'Копировать артикул',
            'catalog.title' => 'Все товары и наборы',
            'catalog.all' => 'Все товары',
            'catalog.categories' => 'Категории',
            'catalog.brands' => 'Бренды',
            'catalog.price_filter' => 'Фильтр по цене',
            'catalog.sort' => 'Сортировка:',
            'catalog.sort_newest' => 'Сначала новинки',
            'catalog.sort_price_asc' => 'По возрастанию цены',
            'catalog.sort_price_desc' => 'По убыванию цены',
            'catalog.sort_popular' => 'По популярности',
            'catalog.not_found' => 'Товары не найдены',
            'catalog.reset_filters' => 'Сбросить фильтры',
            'catalog.showing' => 'Показано',
            'catalog.of' => 'из',
            'catalog.products' => 'товаров',
            'catalog.page_prev' => 'Назад',
            'catalog.page_next' => 'Вперед',
            'catalog.all_brands' => 'Все бренды',
            'catalog.sort_recommended' => 'Рекомендуемые',
            'cart.title' => 'Ваша корзина',
            'cart.empty_title' => 'В вашей корзине пока пусто',
            'cart.empty_desc' => 'Откройте наш каталог и выберите оригинальную косметику премиум-брендов из США.',
            'cart.to_catalog' => 'Перейти в каталог',
            'cart.summary' => 'Итого к оплате:',
            'cart.checkout' => 'Оформить заказ',
            'cart.clear' => 'Очистить корзину',
            'cart.item_count' => 'товаров',
            'cart.qty' => 'шт.',
            'cart.discount' => 'Скидка:',
            'cart.note' => 'Примечание к заказу',
            'cart.shipping_calc' => 'Ориентировочная стоимость доставки',
            'profile.title' => 'Личный кабинет',
            'profile.orders_tab' => 'Мои заказы',
            'profile.details_tab' => 'Личные данные',
            'profile.first_name' => 'Имя',
            'profile.last_name' => 'Фамилия',
            'profile.email' => 'Электронная почта',
            'profile.phone' => 'Номер телефона',
            'profile.city' => 'Город',
            'profile.address' => 'Адрес доставки',
            'profile.save' => 'Сохранить изменения',
            'profile.saving' => 'Сохранение...',
            'profile.saved_success' => 'Данные профиля успешно сохранены',
            'profile.no_orders' => 'У вас пока нет заказов',
            'profile.order_num' => 'Заказ №',
            'profile.order_items' => 'Состав заказа',
            'profile.status' => 'Статус',
            'profile.total' => 'Итого',
            'profile.logout' => 'Выйти',
            'profile.need_login' => 'Войдите, чтобы просмотреть заказы и профиль',
            'profile.device_bound' => 'Устройство привязано',
            'profile.receipt_and_status' => 'Чек и статус',
            'profile.login_btn' => 'Войти или зарегистрироваться',
            'profile.login_desc' => 'Авторизуйтесь через телефон для просмотра истории заказов и статуса доставки.',
            'status.new' => 'Новый заказ',
            'status.payment_received' => 'Оплачен',
            'status.packing' => 'Комплектуется',
            'status.shipped' => 'В доставке',
            'status.delivered' => 'Вручен',
            'status.cancelled' => 'Отменён',
            'footer.rights' => 'Все права защищены.',
            'footer.returns' => 'Политика и регламент',
            'footer.tracking' => 'Отследить доставку',
        ],
        'uz' => [
            'nav.catalog' => 'Katalog',
            'nav.search' => 'Kosmetika qidirish...',
            'nav.profile' => 'Profil',
            'nav.favorites' => 'Sevimlilar',
            'nav.cart' => 'Savat',
            'nav.admin' => 'Admin panel',
            'nav.login' => 'Kirish',
            'nav.logout' => 'Chiqish',
            'nav.orders' => 'Buyurtmalarim',
            'nav.shipping_banner' => "Yandex Go orqali jo'natish · Yetkazib berish xaridor tomonidan to'lanadi",
            'nav.support' => "Qo'llab-quvvatlash @mallyuz",
            'nav.brands' => 'Brendlar',
            'nav.categories' => 'Kategoriyalar',
            'nav.all_products' => 'Barcha mahsulotlar',
            'nav.contact' => 'Aloqa formasi',
            'nav.home' => 'Bosh sahifa',
            'product.add_to_cart' => "Savatga qo'shish",
            'product.in_cart' => 'Savatda',
            'product.out_of_stock' => "Sotuvda yo'q",
            'product.in_stock' => 'Sotuvda bor',
            'product.sku' => 'Art:',
            'product.price' => "so'm",
            'product.added_to_cart' => "Mahsulot savatga qo'shildi!",
            'product.order_us' => 'AQSHdan buyurtma qilish',
            'product.price_usd' => 'Narxi ($)',
            'product.price_uzs' => "Narxi so'mda (UZS)",
            'product.month_batch' => 'Ushbu oy partiyasi',
            'product.pcs' => 'dona',
            'product.of_total' => 'dan',
            'product.remaining' => 'Qoldi',
            'product.sold_month' => 'Bu oyda sotildi',
            'product.awaiting_stock' => 'Yangi partiya kutilmoqda',
            'product.batch_sold_out' => "Partiya to'liq sotildi",
            'product.savings' => 'Tejamkorlik',
            'product.order_tg' => 'Telegram orqali AQSHdan buyurtma qilish',
            'product.tg_note' => "Sephora / Ulta USA do'konlaridan to'g'ridan-to'g'ri xarid qilamiz",
            'product.max_stock_selected' => 'Ombordagi maksimal miqdor tanlandi',
            'product.buy_tg' => 'Telegram orqali xarid qilish',
            'product.usa_origin' => '100% AQSHdan original',
            'product.batch_verified' => 'Batch-kod tekshiriladi',
            'product.desc_texture' => 'Tavsif va tuzilishi',
            'product.desc_subtitle' => 'Tuzilish xususiyatlari, formula va natija',
            'product.key_benefits' => 'Formulaning asosiy afzalliklari:',
            'product.ingredients_title' => 'Tarkibi va faol moddalar',
            'product.ingredients_subtitle' => "To'liq tarkib (INCI) va parvarishlovchi moddalar",
            'product.how_to_use_title' => "Qo'llash usuli va maslahatlar",
            'product.how_to_use_subtitle' => "Go'zallik mutaxassislaridan bosqichma-bosqich ko'rsatma",
            'product.specs_title' => 'Xususiyatlari va tafsilotlari',
            'product.sku_label' => 'Artikul (SKU)',
            'product.brand' => 'Brend',
            'product.category' => 'Kategoriya',
            'product.origin_country' => 'Ishlab chiqarilgan mamlakat',
            'product.origin_usa' => 'AQSH (Original)',
            'product.delivery_guarantee_title' => "Yetkazib berish va o'ziga xoslik kafolati",
            'product.recommended' => 'Sizga ham yoqishi mumkin',
            'product.recommended_subtitle' => "Ushbu toifadagi va brenddagi o'xshash mahsulotlar",
            'catalog.title' => 'Kosmetika katalogi',
            'catalog.all' => 'Barcha mahsulotlar',
            'catalog.sort_newest' => 'Avval yangilari',
            'catalog.sort_price_asc' => "Narx o'sishi bo'yicha",
            'catalog.sort_price_desc' => "Narx kamayishi bo'yicha",
            'catalog.not_found' => 'Mahsulotlar topilmadi',
            'catalog.reset_filters' => 'Filtrlarni bekor qilish',
            'catalog.products' => 'mahsulot',
            'catalog.all_brands' => 'Barcha brendlar',
            'catalog.sort_recommended' => 'Tavsiya etilganlar',
            'cart.title' => 'Savatingiz',
            'cart.empty_title' => "Savatingiz hozircha bo'sh",
            'cart.empty_desc' => 'Katalogimizni oching va AQSHdan original kosmetikani tanlang.',
            'cart.to_catalog' => "Katalogga o'tish",
            'cart.summary' => "Jami to'lov:",
            'cart.checkout' => 'Buyurtma berish',
            'cart.qty' => 'dona',
            'profile.title' => 'Shaxsiy kabinet',
            'profile.orders_tab' => 'Mening buyurtmalarim',
            'profile.details_tab' => "Shaxsiy ma'lumotlar",
            'profile.first_name' => 'Ism',
            'profile.last_name' => 'Familiya',
            'profile.phone' => 'Telefon raqami',
            'profile.city' => 'Shahar',
            'profile.address' => 'Yetkazib berish manzili',
            'profile.save' => "O'zgarishlarni saqlash",
            'profile.saving' => 'Saqlanmoqda...',
            'profile.saved_success' => "Ma'lumotlar muvaffaqiyatli saqlandi",
            'profile.no_orders' => "Sizda hali buyurtmalar yo'q",
            'profile.order_num' => 'Buyurtma №',
            'profile.logout' => 'Chiqish',
            'profile.need_login' => "Buyurtmalarni ko'rish uchun tizimga kiring",
            'profile.device_bound' => 'Qurilmaga biriktirilgan',
            'profile.receipt_and_status' => 'Chek va holat',
            'profile.login_btn' => "Kirish yoki ro'yxatdan o'tish",
            'profile.login_desc' => "Buyurtmalaringiz tarixi va holatini ko'rish uchun telefoningiz orqali tizimga kiring.",
            'status.new' => 'Yangi',
            'status.payment_received' => "To'langan",
            'status.packing' => "Yig'ilmoqda",
            'status.shipped' => 'Yetkazilmoqda',
            'status.delivered' => 'Yetkazildi',
            'status.cancelled' => 'Bekor qilingan',
            'footer.rights' => 'Barcha huquqlar himoyalangan.',
            'footer.returns' => 'Qaytarish va almashtirish',
            'footer.tracking' => 'Buyurtmani kuzatish',
        ],
        'en' => [
            'nav.catalog' => 'Catalog',
            'nav.search' => 'Search beauty products...',
            'nav.profile' => 'Profile',
            'nav.favorites' => 'Favorites',
            'nav.cart' => 'Cart',
            'nav.admin' => 'Admin Panel',
            'nav.login' => 'Sign In',
            'nav.logout' => 'Sign Out',
            'nav.orders' => 'My Orders',
            'nav.shipping_banner' => 'Fast dispatch via Yandex Go · Delivery paid by customer at courier tariff',
            'nav.support' => 'Customer Care @mallyuz',
            'nav.brands' => 'Brands',
            'nav.categories' => 'Categories',
            'nav.all_products' => 'All Products',
            'nav.contact' => 'Contact Form',
            'nav.home' => 'Home',
            'product.add_to_cart' => 'Add to Cart',
            'product.in_cart' => 'In Cart',
            'product.out_of_stock' => 'Out of Stock',
            'product.in_stock' => 'In Stock',
            'product.sku' => 'SKU:',
            'product.price' => 'UZS',
            'product.added_to_cart' => 'Added to your cart!',
            'product.order_us' => 'Order from USA',
            'product.price_usd' => 'Price ($)',
            'product.price_uzs' => 'Price in UZS',
            'product.month_batch' => "This Month's Batch",
            'product.pcs' => 'pcs',
            'product.of_total' => 'of',
            'product.remaining' => 'Remaining',
            'product.sold_month' => 'Sold this month',
            'product.awaiting_stock' => 'Restock expected soon',
            'product.batch_sold_out' => 'Batch completely sold out',
            'product.savings' => 'Savings',
            'product.order_tg' => 'Order via Telegram Personal Shopper',
            'product.tg_note' => 'Purchased directly from Sephora / Ulta USA at fixed rates',
            'product.max_stock_selected' => 'Maximum stock quantity selected',
            'product.buy_tg' => 'Buy in Telegram',
            'product.usa_origin' => '100% Authentic from USA',
            'product.batch_verified' => 'Batch code verifiable',
            'product.desc_texture' => 'Description & Texture',
            'product.desc_subtitle' => 'Texture details, formula, and results',
            'product.key_benefits' => 'Key Formula Benefits:',
            'product.ingredients_title' => 'Ingredients & Actives',
            'product.ingredients_subtitle' => 'Full ingredient list (INCI) and active compounds',
            'product.how_to_use_title' => 'How to Apply & Tips',
            'product.how_to_use_subtitle' => 'Step-by-step beauty expert & makeup artist guide',
            'product.specs_title' => 'Specifications & Details',
            'product.sku_label' => 'SKU',
            'product.brand' => 'Brand',
            'product.category' => 'Category',
            'product.origin_country' => 'Country of Origin',
            'product.origin_usa' => 'USA (Authentic)',
            'product.delivery_guarantee_title' => 'Delivery & Authenticity Guarantee',
            'product.recommended' => 'You may also like',
            'product.recommended_subtitle' => 'Related products from this category and brand',
            'catalog.title' => 'Cosmetics Catalog',
            'catalog.all' => 'All Products',
            'catalog.sort_newest' => 'Newest First',
            'catalog.sort_price_asc' => 'Price: Low to High',
            'catalog.sort_price_desc' => 'Price: High to Low',
            'catalog.not_found' => 'No products found',
            'catalog.reset_filters' => 'Reset Filters',
            'catalog.products' => 'products',
            'catalog.all_brands' => 'All Brands',
            'catalog.sort_recommended' => 'Recommended',
            'cart.title' => 'Shopping Cart',
            'cart.empty_title' => 'Your cart is currently empty',
            'cart.empty_desc' => 'Browse our catalog and discover 100% authentic luxury beauty brands direct from the USA.',
            'cart.to_catalog' => 'Browse Catalog',
            'cart.summary' => 'Total Amount:',
            'cart.checkout' => 'Proceed to Checkout',
            'cart.qty' => 'pcs',
            'profile.title' => 'My Account',
            'profile.orders_tab' => 'My Orders',
            'profile.details_tab' => 'Personal Details',
            'profile.first_name' => 'First Name',
            'profile.last_name' => 'Last Name',
            'profile.phone' => 'Phone Number',
            'profile.city' => 'City',
            'profile.address' => 'Shipping Address',
            'profile.save' => 'Save Changes',
            'profile.saving' => 'Saving...',
            'profile.saved_success' => 'Profile updated successfully',
            'profile.no_orders' => 'You do not have any orders yet',
            'profile.order_num' => 'Order #',
            'profile.logout' => 'Sign Out',
            'profile.need_login' => 'Sign in to view your orders and profile',
            'profile.device_bound' => 'Device Linked',
            'profile.receipt_and_status' => 'Receipt & Status',
            'profile.login_btn' => 'Sign In / Register',
            'profile.login_desc' => 'Verify your phone number to access order history and live delivery tracking.',
            'status.new' => 'New',
            'status.payment_received' => 'Payment Received',
            'status.packing' => 'Packing',
            'status.shipped' => 'Shipped',
            'status.delivered' => 'Delivered',
            'status.cancelled' => 'Cancelled',
            'footer.rights' => 'All rights reserved.',
            'footer.returns' => 'Returns & Exchanges',
            'footer.tracking' => 'Track Order',
        ],
    ];
}
