<?php

namespace App\Services;

use App\Models\Category;
use App\Models\Customer;
use App\Models\Product;
use App\Models\Review;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

class StorefrontService
{
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

    public function formatPrice(float|int|string|null $priceVal, ?string $forceCurrency = null): string
    {
        $num = (float) ($priceVal ?? 0);
        $rate = $this->getExchangeRate();
        $usd = ($num > 1000 && $rate > 0) ? ($num / $rate) : $num;
        $currency = $forceCurrency ?? $this->getCurrency();

        if ($currency === 'UZS') {
            if ($rate <= 0) {
                return '-';
            }
            $uzs = (int) round($usd * $rate);

            return number_format($uzs, 0, '.', ' ').' сум';
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

    public function getRichInitialProducts(): array
    {
        $path = base_path('database/data/initial-products.json');
        if (file_exists($path)) {
            $decoded = json_decode((string) file_get_contents($path), true);
            if (is_array($decoded)) {
                return $decoded;
            }
        }

        return [];
    }

    public function getAllProducts(): array
    {
        if ($this->cachedAllProducts !== null) {
            return $this->cachedAllProducts;
        }

        $this->cachedAllProducts = Cache::remember('mally_all_products_v2', 120, function () {
            return $this->buildAllProductsArray();
        });

        return $this->cachedAllProducts;
    }

    private function buildAllProductsArray(): array
    {
        $dbProducts = Product::with(['brand', 'category'])->get();
        $initials = $this->getRichInitialProducts();
        $formatted = [];
        $liveRate = $this->getExchangeRate();

        foreach ($initials as $init) {
            $usdPrice = (isset($init['price']) && (float) $init['price'] > 1000 && $liveRate > 0)
                ? round(((float) $init['price']) / $liveRate, 2)
                : (float) ($init['price'] ?? 45.0);
            $usdOldPrice = (isset($init['oldPrice']) && (float) $init['oldPrice'] > 1000 && $liveRate > 0)
                ? round(((float) $init['oldPrice']) / $liveRate, 2)
                : (isset($init['oldPrice']) ? (float) $init['oldPrice'] : null);

            $images = is_array($init['images'] ?? null) && count($init['images']) > 0
                ? $init['images']
                : [$init['image'] ?? 'https://findy.uz/cdn/shop/files/sample.jpg'];

            $qty = (int) ($init['quantity'] ?? 10);

            $formatted[] = [
                'id' => (string) ($init['id'] ?? $init['sku']),
                'sku' => (string) ($init['sku'] ?? 'ML-'.Str::upper(Str::random(6))),
                'slug' => (string) ($init['slug'] ?? Str::slug($init['title'] ?? 'product')),
                'title' => (string) ($init['title'] ?? 'Mally Product'),
                'name' => (string) ($init['title'] ?? 'Mally Product'),
                'brand' => (string) ($init['brand'] ?? 'Mally Collection'),
                'category' => (string) ($init['category'] ?? 'Макияж'),
                'category_en' => (string) ($init['category'] ?? 'Makeup'),
                'category_uz' => (string) ($init['category'] ?? 'Makiyaj'),
                'price' => $usdPrice,
                'selling_price' => $usdPrice,
                'oldPrice' => $usdOldPrice,
                'old_price' => $usdOldPrice,
                'quantity' => $qty,
                'inStock' => $qty > 0 && ($init['status'] ?? 'ACTIVE') !== 'OUT_OF_STOCK',
                'status' => $init['status'] ?? ($qty > 0 ? 'ACTIVE' : 'OUT_OF_STOCK'),
                'isNew' => (bool) ($init['isNew'] ?? true),
                'images' => $images,
                'image' => $images[0],
                'description' => (string) ($init['description'] ?? ''),
                'features' => is_array($init['features'] ?? null) ? $init['features'] : [],
                'howToUse' => (string) ($init['howToUse'] ?? ''),
                'reviews' => is_array($init['reviews'] ?? null) ? $init['reviews'] : [],
            ];
        }

        $existingTitles = array_map('mb_strtolower', array_column($formatted, 'title'));

        foreach ($dbProducts as $p) {
            $title = (string) $p->name;
            if (in_array(mb_strtolower($title), $existingTitles, true)) {
                continue;
            }

            $brandName = $p->brand?->name ?: 'Mally Collection';
            $catNameRu = $p->category?->name_ru ?: ($p->category?->name ?: 'Косметика');
            $catNameEn = $p->category?->name_en ?: $catNameRu;
            $catNameUz = $p->category?->name_uz ?: $catNameRu;

            $numPrice = (float) $p->selling_price;
            $priceInUsd = ($numPrice > 1000 && $liveRate > 0) ? round($numPrice / $liveRate, 2) : $numPrice;
            if ($priceInUsd <= 0) {
                $priceInUsd = 35.00;
            }

            $images = [];
            if ($p->image) {
                $images[] = str_starts_with($p->image, 'http') ? $p->image : asset($p->image);
            } else {
                $images[] = 'https://findy.uz/cdn/shop/files/sample.jpg';
            }

            $qty = (int) $p->quantity;
            $inStock = $qty > 0 && $p->status !== 'out_of_stock';
            $sku = $p->sku ?: 'ML-P-'.$p->id;

            $formatted[] = [
                'id' => (string) $p->id,
                'sku' => $sku,
                'slug' => Str::slug($p->name).'-'.$p->id,
                'title' => $p->name,
                'name' => $p->name,
                'brand' => $brandName,
                'category' => $catNameRu,
                'category_en' => $catNameEn,
                'category_uz' => $catNameUz,
                'price' => $priceInUsd,
                'selling_price' => $priceInUsd,
                'oldPrice' => null,
                'old_price' => null,
                'quantity' => $qty,
                'inStock' => $inStock,
                'status' => $inStock ? 'ACTIVE' : 'OUT_OF_STOCK',
                'isNew' => $p->created_at && $p->created_at->gt(now()->subDays(30)),
                'images' => $images,
                'image' => $images[0],
                'description' => $p->description ?: ($p->comment ?: 'Премиальная оригинальная продукция из США.'),
                'features' => ['100% Оригинал из США', 'Сертифицированная косметика'],
                'howToUse' => 'Наносите в соответствии с рекомендациями производителя.',
                'reviews' => [],
            ];
        }

        $this->cachedAllProducts = $formatted;

        return $this->cachedAllProducts;
    }

    public function findProduct(string $slugOrSku): ?array
    {
        $target = mb_strtolower(trim(urldecode($slugOrSku)));
        if ($target === '') {
            return null;
        }

        $targetAlpha = preg_replace('/[^a-z0-9]/i', '', $target);
        $all = $this->getAllProducts();
        $matched = null;

        foreach ($all as $p) {
            $pSku = mb_strtolower(trim((string) ($p['sku'] ?? '')));
            $pId = mb_strtolower(trim((string) ($p['id'] ?? '')));
            $pSlug = mb_strtolower(trim((string) ($p['slug'] ?? '')));
            $pTitle = mb_strtolower(trim((string) ($p['title'] ?? '')));

            if ($pSku === $target || $pId === $target || 'ml-p-'.$pId === $target || $pSlug === $target) {
                $matched = $p;
                break;
            }
            if ($targetAlpha !== '' && $pSku !== '' && preg_replace('/[^a-z0-9]/i', '', $pSku) === $targetAlpha) {
                $matched = $p;
                break;
            }
            if ($pTitle !== '' && $pTitle === $target) {
                $matched = $p;
                break;
            }
        }

        if (! $matched && preg_match('/(?:ML-P-|-)(\d+)$/i', $target, $m)) {
            $idMatch = $m[1];
            foreach ($all as $p) {
                if ((string) $p['id'] === (string) $idMatch) {
                    $matched = $p;
                    break;
                }
            }
        }

        if (! $matched) {
            $dbProd = $this->findProductBySlugOrId($slugOrSku);
            if ($dbProd) {
                return $this->formatProduct($dbProd);
            }

            return null;
        }

        return $this->overlayLiveDatabaseState($matched);
    }

    /**
     * Refresh price, stock quantity, availability, and customer reviews from the
     * database so stale cache or session data cannot mask server-side changes.
     */
    private function overlayLiveDatabaseState(array $productData): array
    {
        $id = $productData['id'] ?? null;
        $sku = trim((string) ($productData['sku'] ?? ''));
        $title = trim((string) ($productData['title'] ?? $productData['name'] ?? ''));

        $dbProduct = null;
        if (is_numeric($id)) {
            $dbProduct = Product::find((int) $id);
        }
        if (! $dbProduct && $sku !== '') {
            $dbProduct = Product::where('sku', $sku)->first();
        }
        if (! $dbProduct && $title !== '') {
            $dbProduct = Product::where('name', $title)->first();
        }

        if ($dbProduct) {
            $liveRate = $this->getExchangeRate();
            $numPrice = (float) $dbProduct->selling_price;
            $priceInUsd = ($numPrice > 1000 && $liveRate > 0) ? round($numPrice / $liveRate, 2) : $numPrice;
            if ($priceInUsd <= 0) {
                $priceInUsd = (float) ($productData['price'] ?? 35.00);
            }

            $qty = max(0, (int) $dbProduct->quantity);
            $inStock = $qty > 0 && ! in_array(strtolower((string) $dbProduct->status), ['out_of_stock'], true);

            $productData['id'] = (string) $dbProduct->id;
            if ($dbProduct->sku) {
                $productData['sku'] = (string) $dbProduct->sku;
                $sku = (string) $dbProduct->sku;
            }
            $productData['price'] = $priceInUsd;
            $productData['selling_price'] = $priceInUsd;
            $productData['quantity'] = $qty;
            $productData['inStock'] = $inStock;
            $productData['status'] = $inStock ? 'ACTIVE' : 'OUT_OF_STOCK';
        }

        $dbReviews = $this->getDatabaseReviewsForProduct(
            $dbProduct?->id ?? (is_numeric($id) ? (int) $id : null),
            $sku !== '' ? $sku : null,
            $title !== '' ? $title : null
        );
        $initialReviews = is_array($productData['reviews'] ?? null) ? $productData['reviews'] : [];
        $productData['reviews'] = array_merge($dbReviews, $initialReviews);

        return $productData;
    }

    public function getDatabaseReviewsForProduct(?int $productId, ?string $sku, ?string $title): array
    {
        if (! Schema::hasTable('reviews')) {
            return [];
        }

        $query = Review::query()->where('is_approved', true);
        $hasCondition = false;

        $query->where(function ($q) use ($productId, $sku, $title, &$hasCondition) {
            if ($productId !== null && $productId > 0) {
                $q->orWhere('product_id', $productId);
                $hasCondition = true;
            }
            if ($sku !== null && trim($sku) !== '') {
                $q->orWhere('product_sku', trim($sku));
                $hasCondition = true;
            }
            if ($title !== null && trim($title) !== '') {
                $q->orWhere('product_title', trim($title));
                $hasCondition = true;
            }
        });

        if (! $hasCondition) {
            return [];
        }

        return $query->latest('id')->get()->map(function (Review $rev) {
            return [
                'id' => 'db_rev_'.$rev->id,
                'author' => $rev->author_name,
                'city' => $rev->city ?: 'Ташкент',
                'rating' => (int) $rev->rating,
                'date' => $rev->created_at ? $rev->created_at->format('d.m.Y') : now()->format('d.m.Y'),
                'title' => $rev->product_title ?: '',
                'comment' => $rev->comment,
                'text' => $rev->comment,
                'photos' => is_array($rev->photos) ? $rev->photos : [],
            ];
        })->all();
    }

    public function getRecommendedProducts(array $currentProduct, int $limit = 4): array
    {
        $all = $this->getAllProducts();
        $curSku = mb_strtolower((string) ($currentProduct['sku'] ?? $currentProduct['id'] ?? ''));
        $brandName = mb_strtolower((string) ($currentProduct['brand'] ?? ''));
        $catName = mb_strtolower((string) ($currentProduct['category'] ?? ''));

        $available = array_values(array_filter($all, function ($p) use ($curSku) {
            return mb_strtolower((string) ($p['sku'] ?? $p['id'] ?? '')) !== $curSku;
        }));

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
        $product = $this->findProduct($productKey);
        if (! $product) {
            return ['success' => false, 'message' => 'Товар не найден', 'type' => 'error'];
        }

        $stockCount = max(0, (int) ($product['quantity'] ?? 0));
        if (! ($product['inStock'] ?? true) || $stockCount <= 0) {
            return ['success' => false, 'message' => 'Товар распродан на складе', 'type' => 'warning'];
        }

        $cart = $this->getCart();
        $sku = (string) ($product['sku'] ?? $product['id']);
        $id = (string) ($product['id'] ?? $sku);
        $found = false;

        foreach ($cart as &$item) {
            if ((string) $item['id'] === $id || (string) $item['sku'] === $sku || (string) $item['sku'] === $productKey) {
                $currentQty = max(0, (int) ($item['quantity'] ?? 0));
                if ($currentQty >= $stockCount) {
                    $item['quantity'] = $stockCount;
                    $item['maxQuantity'] = $stockCount;
                    $item['price'] = (float) $product['price'];
                    $this->saveCart($cart);

                    return [
                        'success' => false,
                        'message' => "Нельзя добавить больше: на складе доступно всего {$stockCount} шт.",
                        'type' => 'warning',
                    ];
                }
                $item['quantity'] = min($stockCount, $currentQty + 1);
                $item['maxQuantity'] = $stockCount;
                $item['price'] = (float) $product['price'];
                $found = true;
                break;
            }
        }
        unset($item);

        if (! $found) {
            $cart[] = [
                'id' => $id,
                'sku' => $sku,
                'title' => $product['title'],
                'brand' => $product['brand'],
                'price' => (float) $product['price'],
                'oldPrice' => $product['oldPrice'] ?? null,
                'image' => $product['image'] ?? ($product['images'][0] ?? 'https://findy.uz/cdn/shop/files/sample.jpg'),
                'quantity' => 1,
                'maxQuantity' => $stockCount,
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

            return ['success' => true, 'message' => 'Товар удален из корзины', 'type' => 'info'];
        }

        $cart = $this->getCart();
        $warning = null;
        $updatedCart = [];

        foreach ($cart as $item) {
            if ((string) ($item['id'] ?? '') === $key || (string) ($item['sku'] ?? '') === $key) {
                $liveProduct = $this->findProduct((string) ($item['sku'] ?? $item['id'] ?? $key));
                if (! $liveProduct || ! ($liveProduct['inStock'] ?? true) || (int) ($liveProduct['quantity'] ?? 0) <= 0) {
                    $warning = 'Товар закончился на складе и был удален из корзины.';

                    continue;
                }

                $maxLimit = max(0, (int) $liveProduct['quantity']);
                if ($qty > $maxLimit) {
                    $qty = $maxLimit;
                    $warning = "На складе доступно максимум {$maxLimit} шт.";
                }

                $item['quantity'] = max(1, $qty);
                $item['maxQuantity'] = $maxLimit;
                $item['price'] = (float) $liveProduct['price'];
            }

            $updatedCart[] = $item;
        }

        $this->saveCart($updatedCart);

        if ($warning) {
            return ['success' => false, 'message' => $warning, 'type' => 'warning'];
        }

        return ['success' => true];
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
     * Revalidate session cart items against live server product price and stock.
     * Synchronizes the session cart and reports any discrepancies so checkout
     * cannot proceed on stale data.
     */
    public function validateAndSyncCart(): array
    {
        $cart = $this->getCart();
        $syncedCart = [];
        $messages = [];
        $priceChanged = false;
        $stockChanged = false;

        foreach ($cart as $item) {
            $key = (string) ($item['sku'] ?? $item['id'] ?? '');
            $title = (string) ($item['title'] ?? 'Товар');
            $rawQty = (int) ($item['quantity'] ?? 0);

            if ($rawQty <= 0) {
                $stockChanged = true;
                $messages[] = "Некорректное количество для товара «{$title}». Позиция удалена.";

                continue;
            }

            $liveProduct = $this->findProduct($key);
            if (! $liveProduct) {
                $stockChanged = true;
                $messages[] = "Товар «{$title}» больше недоступен и был удален из корзины.";

                continue;
            }

            $liveTitle = (string) ($liveProduct['title'] ?? $title);
            $liveStock = max(0, (int) ($liveProduct['quantity'] ?? 0));
            $inStock = (bool) ($liveProduct['inStock'] ?? ($liveStock > 0)) && $liveStock > 0;
            $livePrice = round((float) ($liveProduct['price'] ?? 0), 2);
            $sessionPrice = round((float) ($item['price'] ?? $livePrice), 2);

            if (! $inStock) {
                $stockChanged = true;
                $messages[] = "Товар «{$liveTitle}» закончился на складе и был удален из корзины.";

                continue;
            }

            if (abs($sessionPrice - $livePrice) >= 0.01) {
                $priceChanged = true;
                $messages[] = "Цена товара «{$liveTitle}» изменилась ({$this->formatPrice($livePrice)}).";
            }

            $finalQty = $rawQty;
            if ($rawQty > $liveStock) {
                $stockChanged = true;
                $finalQty = $liveStock;
                $messages[] = "Остаток товара «{$liveTitle}» уменьшился: доступно максимум {$liveStock} шт.";
            }

            $item['id'] = (string) ($liveProduct['id'] ?? $item['id']);
            $item['sku'] = (string) ($liveProduct['sku'] ?? $item['sku']);
            $item['title'] = $liveTitle;
            $item['brand'] = $liveProduct['brand'] ?? ($item['brand'] ?? 'Mally Collection');
            $item['price'] = $livePrice;
            $item['quantity'] = $finalQty;
            $item['maxQuantity'] = $liveStock;

            $syncedCart[] = $item;
        }

        $this->saveCart($syncedCart);

        return [
            'valid' => ! $priceChanged && ! $stockChanged && empty($messages),
            'price_changed' => $priceChanged,
            'stock_changed' => $stockChanged,
            'messages' => $messages,
            'cart' => $this->getCartItemsWithProducts(),
        ];
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
        if (! is_string($code) || strtoupper(trim($code)) !== 'MALLY10') {
            if ($code !== null) {
                session()->forget('mally_promo_code');
            }

            return null;
        }

        return 'MALLY10';
    }

    public function applyPromoCode(string $code, ?float $totalAmountUsd = null): array|bool
    {
        $clean = strtoupper(trim($code));
        $subtotal = $totalAmountUsd ?? $this->getCartTotalUsd();

        if ($clean === 'MALLY10') {
            session(['mally_promo_code' => $clean]);
            $disc = round($subtotal * 0.10, 2);

            return $totalAmountUsd === null ? true : [
                'success' => true,
                'code' => $clean,
                'discountUsd' => $disc,
                'message' => 'Промокод на скидку 10% успешно применен!',
            ];
        }

        session()->forget('mally_promo_code');

        return $totalAmountUsd === null ? false : [
            'success' => false,
            'error' => 'Недействительный промокод',
        ];
    }

    public function getCartItemsWithProducts(): array
    {
        $cart = $this->getCart();
        $items = [];
        $subtotalUsd = 0.0;

        foreach ($cart as $c) {
            $key = (string) ($c['sku'] ?? $c['id'] ?? '');
            $found = $this->findProduct($key);

            // Всегда берем актуальную серверную цену и остаток товара, а не устаревшие данные из сессии
            $price = $found ? (float) $found['price'] : (float) ($c['price'] ?? 35);
            $stock = $found ? max(0, (int) ($found['quantity'] ?? 0)) : max(0, (int) ($c['maxQuantity'] ?? 0));
            $qty = max(1, (int) ($c['quantity'] ?? 1));
            $lineTotal = round($price * $qty, 2);
            $subtotalUsd += $lineTotal;

            $items[] = [
                'quantity' => $qty,
                'line_total_usd' => $lineTotal,
                'product' => [
                    'id' => $found['id'] ?? $c['id'] ?? $key,
                    'sku' => $found['sku'] ?? $c['sku'] ?? $key,
                    'slug' => $found['slug'] ?? $c['sku'] ?? $key,
                    'name' => $found['name'] ?? $c['title'] ?? 'Mally Product',
                    'title' => $found['title'] ?? $c['title'] ?? 'Mally Product',
                    'brand' => $found['brand'] ?? $c['brand'] ?? 'Mally Collection',
                    'category' => $found['category'] ?? 'Косметика',
                    'image' => $found['image'] ?? $c['image'] ?? 'https://findy.uz/cdn/shop/files/sample.jpg',
                    'price' => $price,
                    'stock' => $stock,
                    'inStock' => $found ? (bool) ($found['inStock'] ?? ($stock > 0)) : ($stock > 0),
                ],
            ];
        }

        $promoCode = $this->getPromoCode();
        $discountPercent = $promoCode === 'MALLY10' ? 10 : 0;
        $discountUsd = $discountPercent > 0 ? round($subtotalUsd * ($discountPercent / 100), 2) : 0.0;
        $shippingUsd = ($subtotalUsd >= 50 || $subtotalUsd <= 0) ? 0.0 : 2.5;
        $totalUsd = max(0.0, round($subtotalUsd - $discountUsd + $shippingUsd, 2));

        return [
            'items' => $items,
            'subtotal_usd' => round($subtotalUsd, 2),
            'discount_usd' => $discountUsd,
            'discount_percent' => $discountPercent,
            'promo_code' => $promoCode,
            'shipping_usd' => $shippingUsd,
            'total_usd' => $totalUsd,
        ];
    }

    public function getFeaturedProducts(int $limit = 4): array
    {
        return array_slice($this->getAllProducts(), 0, $limit);
    }

    public function formatProduct(Product $p): array
    {
        $brandName = $p->brand?->name ?: 'Mally Collection';
        $catNameRu = $p->category?->name_ru ?: ($p->category?->name ?: 'Косметика');
        $numPrice = (float) $p->selling_price;
        $liveRate = $this->getExchangeRate();
        $priceInUsd = ($numPrice > 1000 && $liveRate > 0) ? round($numPrice / $liveRate, 2) : $numPrice;
        if ($priceInUsd <= 0) {
            $priceInUsd = 35.00;
        }
        $img = $p->image ? (str_starts_with($p->image, 'http') ? $p->image : asset($p->image)) : 'https://findy.uz/cdn/shop/files/sample.jpg';
        $qty = max(0, (int) $p->quantity);

        return [
            'id' => (string) $p->id,
            'sku' => $p->sku ?: 'ML-P-'.$p->id,
            'slug' => Str::slug($p->name).'-'.$p->id,
            'title' => $p->name,
            'name' => $p->name,
            'brand' => $brandName,
            'category' => $catNameRu,
            'price' => $priceInUsd,
            'selling_price' => $priceInUsd,
            'quantity' => $qty,
            'inStock' => $qty > 0 && $p->status !== 'out_of_stock',
            'image' => $img,
            'images' => [$img],
            'description' => $p->description ?: ($p->comment ?: 'Премиальная оригинальная продукция из США.'),
            'features' => ['100% Оригинал из США', 'Сертифицированная косметика'],
            'howToUse' => 'Наносите в соответствии с рекомендациями производителя.',
            'reviews' => $this->getDatabaseReviewsForProduct($p->id, $p->sku, $p->name),
        ];
    }

    public function findProductBySlugOrId(string $slug): ?Product
    {
        $decoded = urldecode($slug);

        $product = Product::with(['brand', 'category'])
            ->where('sku', $decoded)
            ->orWhere('id', is_numeric($decoded) ? (int) $decoded : 0)
            ->first();

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
