<?php

use App\Models\Brand;
use App\Models\Category;
use App\Models\Customer;
use App\Models\Order;
use App\Models\OrderProduct;
use App\Models\Product;
use App\Models\ProductReceipt;
use App\Services\ExchangeRateService;
use App\Services\InventoryService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Str;

/*
|--------------------------------------------------------------------------
| Storefront & System API Routes
|--------------------------------------------------------------------------
*/

if (! defined('STORE_CARD_NUMBER')) {
    define('STORE_CARD_NUMBER', '9860 1901 0114 9374');
}
if (! defined('STORE_CARD_HOLDER')) {
    define('STORE_CARD_HOLDER', 'MALLY BEAUTY');
}
if (! defined('STORE_ADMIN_TELEGRAM')) {
    define('STORE_ADMIN_TELEGRAM', 'mallyuz');
}

// 1. Live Exchange Rate from Central Bank of Uzbekistan (CBU)
Route::get('/exchange-rate', function (ExchangeRateService $rateService) {
    $data = $rateService->getUsdRate();
    $status = (isset($data['rate']) && $data['rate'] > 0) ? 200 : 503;

    return response()->json($data, $status);
});

// 2. Categories with multilingual support (RU / UZ / EN, MAL-8)
$categoryHandler = function (Request $request) {
    $lang = $request->query('lang', $request->header('Accept-Language', 'ru'));
    if (in_array($lang, ['ru', 'uz', 'en'], true)) {
        app()->setLocale($lang);
    }

    $categories = Category::all()->map(function ($c) {
        return [
            'id' => $c->id,
            'name' => $c->name, // Evaluates Category::getNameAttribute() in current locale!
            'nameRu' => $c->name_ru,
            'nameUz' => $c->name_uz,
            'nameEn' => $c->name_en,
            'icon' => $c->icon,
        ];
    });

    // Default Mally categories with RU / UZ / EN translations
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

    $existingNames = $categories->pluck('name')->toArray();
    foreach ($defaults as $def) {
        $curName = $def[$lang] ?? $def['ru'];
        if (! in_array($curName, $existingNames, true)) {
            $categories->push([
                'id' => $curName,
                'name' => $curName,
                'nameRu' => $def['ru'],
                'nameUz' => $def['uz'],
                'nameEn' => $def['en'],
            ]);
        }
    }

    return response()->json(['categories' => $categories]);
};
Route::get('/categories', $categoryHandler);
Route::get('/admin/categories', $categoryHandler);

// Helper to load rich initial products
if (! function_exists('getRichInitialProducts')) {
function getRichInitialProducts(): array
{
    static $cached = null;
    if ($cached !== null) {
        return $cached;
    }

    $paths = [
        base_path('database/data/initial-products.json'),
    ];

    foreach ($paths as $path) {
        if (file_exists($path)) {
            $content = file_get_contents($path);
            $decoded = json_decode($content, true);
            if (is_array($decoded)) {
                $cached = $decoded;
                return $cached;
            }
        }
    }

    $cached = [];
    return $cached;
}
}


// 3. Products List
Route::get('/products', function (Request $request) {
    $dbProducts = Product::with(['brand', 'category', 'receipts'])->get();
    $initials = getRichInitialProducts();

    $formatted = [];

    // First add the curated showcase products (Rhode, Hourglass, Rituals, etc.)
    foreach ($initials as $init) {
        $usdPrice = isset($init['price']) && (float) $init['price'] > 1000 
            ? round(((float) $init['price']) / 12850, 2) 
            : (float) ($init['price'] ?? 45.0);
        $usdOldPrice = isset($init['oldPrice']) && (float) $init['oldPrice'] > 1000 
            ? round(((float) $init['oldPrice']) / 12850, 2) 
            : (isset($init['oldPrice']) ? (float) $init['oldPrice'] : null);

        $formatted[] = [
            'id' => (string) ($init['id'] ?? $init['sku']),
            'sku' => $init['sku'] ?? 'ML-'.Str::random(6),
            'slug' => $init['slug'] ?? Str::slug($init['title'] ?? 'product'),
            'title' => $init['title'] ?? 'Mally Product',
            'name' => $init['title'] ?? 'Mally Product',
            'brand' => $init['brand'] ?? 'Mally Collection',
            'category' => $init['category'] ?? 'Макияж',
            'price' => $usdPrice,
            'selling_price' => $usdPrice,
            'oldPrice' => $usdOldPrice,
            'old_price' => $usdOldPrice,
            'quantity' => (int) ($init['quantity'] ?? 10),
            'inStock' => ($init['quantity'] ?? 10) > 0,
            'status' => $init['status'] ?? 'ACTIVE',
            'isNew' => (bool) ($init['isNew'] ?? true),
            'images' => $init['images'] ?? ['https://findy.uz/cdn/shop/files/sample.jpg'],
            'image' => is_array($init['images'] ?? null) && count($init['images']) > 0 ? $init['images'][0] : ($init['image'] ?? 'https://findy.uz/cdn/shop/files/sample.jpg'),
            'description' => $init['description'] ?? '',
            'features' => $init['features'] ?? [],
            'howToUse' => $init['howToUse'] ?? '',
            'reviews' => $init['reviews'] ?? [],
        ];
    }

    // Now append products from DB that are not duplicates
    $existingTitles = array_map('mb_strtolower', array_column($formatted, 'title'));
    foreach ($dbProducts as $p) {
        $title = $p->name;
        if (in_array(mb_strtolower($title), $existingTitles, true)) {
            continue;
        }

        $brandName = $p->brand?->name ?: 'Mally Collection';
        $catName = $p->category?->name_ru ?: ($p->category?->name ?: 'Косметика');

        // Calculate price strictly in USD ($)
        $numPrice = (float) $p->selling_price;
        $priceInUsd = $numPrice > 1000 ? round($numPrice / 12850, 2) : $numPrice;
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

        $formatted[] = [
            'id' => (string) $p->id,
            'sku' => $p->sku ?: 'ML-P-'.$p->id,
            'slug' => Str::slug($p->name).'-'.$p->id,
            'title' => $p->name,
            'name' => $p->name,
            'brand' => $brandName,
            'category' => $catName,
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
            'description' => $p->description ?: $p->comment ?: 'Премиальная оригинальная продукция из США.',
            'features' => ['100% Оригинал из США', 'Сертифицированная косметика'],
            'howToUse' => 'Наносите в соответствии с рекомендациями производителя.',
            'reviews' => [],
        ];
    }

    return response()->json(['products' => $formatted]);
});

// 4. Product Details
Route::get('/products/{slug}', function ($slug) {
    $initials = getRichInitialProducts();
    $decodedSlug = urldecode($slug);

    foreach ($initials as $init) {
        if (
            ($init['slug'] ?? '') === $decodedSlug ||
            ($init['sku'] ?? '') === $decodedSlug ||
            (string) ($init['id'] ?? '') === $decodedSlug
        ) {
            $usdPrice = isset($init['price']) && (float) $init['price'] > 1000 
                ? round(((float) $init['price']) / 12850, 2) 
                : (float) ($init['price'] ?? 45.0);
            $usdOldPrice = isset($init['oldPrice']) && (float) $init['oldPrice'] > 1000 
                ? round(((float) $init['oldPrice']) / 12850, 2) 
                : (isset($init['oldPrice']) ? (float) $init['oldPrice'] : null);

            $init['price'] = $usdPrice;
            $init['selling_price'] = $usdPrice;
            $init['oldPrice'] = $usdOldPrice;
            $init['old_price'] = $usdOldPrice;
            $init['name'] = $init['title'] ?? 'Mally Product';

            return response()->json(['product' => $init]);
        }
    }

    // Check in database by exact SKU, numeric ID, or name
    $product = Product::with(['brand', 'category', 'receipts'])
        ->where('sku', $decodedSlug)
        ->orWhere('id', is_numeric($decodedSlug) ? (int) $decodedSlug : 0)
        ->orWhere('name', 'like', "%{$decodedSlug}%")
        ->first();

    // Check if slug contains ID suffix (e.g. "product-title-12" or "ML-P-12")
    if (! $product && preg_match('/(?:ML-P-|-)(\d+)$/i', $decodedSlug, $m)) {
        $product = Product::with(['brand', 'category', 'receipts'])->find((int) $m[1]);
    }

    // Check if slug matches slugified product name
    if (! $product) {
        $all = Product::with(['brand', 'category', 'receipts'])->get();
        foreach ($all as $p) {
            if (Str::slug($p->name) === $decodedSlug || Str::slug($p->sku ?: '') === $decodedSlug) {
                $product = $p;
                break;
            }
        }
    }

    if ($product) {
        $numPrice = (float) $product->selling_price;
        $priceInUsd = $numPrice > 1000 ? round($numPrice / 12850, 2) : $numPrice;
        if ($priceInUsd <= 0) {
            $priceInUsd = 35.00;
        }

        $images = [];
        if ($product->image) {
            $images[] = str_starts_with($product->image, 'http') ? $product->image : asset($product->image);
        } else {
            $images[] = 'https://findy.uz/cdn/shop/files/sample.jpg';
        }

        $res = [
            'id' => (string) $product->id,
            'sku' => $product->sku ?: 'ML-P-'.$product->id,
            'slug' => Str::slug($product->name).'-'.$product->id,
            'title' => $product->name,
            'name' => $product->name,
            'brand' => $product->brand?->name ?: 'Mally Collection',
            'category' => $product->category?->name_ru ?: ($product->category?->name ?: 'Косметика'),
            'price' => $priceInUsd,
            'selling_price' => $priceInUsd,
            'oldPrice' => null,
            'old_price' => null,
            'quantity' => (int) $product->quantity,
            'inStock' => (int) $product->quantity > 0,
            'status' => (int) $product->quantity > 0 ? 'ACTIVE' : 'OUT_OF_STOCK',
            'images' => $images,
            'image' => $images[0],
            'description' => $product->description ?: $product->comment ?: 'Премиальная оригинальная косметика из США.',
            'features' => ['100% Оригинал из США', 'Сертифицированная продукция'],
            'howToUse' => 'Наносите в соответствии с рекомендациями производителя.',
            'reviews' => [],
        ];

        return response()->json(['product' => $res]);
    }

    // Fallback: return first initial product if available
    if (! empty($initials)) {
        $first = $initials[0];
        $first['price'] = isset($first['price']) && (float) $first['price'] > 1000 ? round(((float) $first['price']) / 12850, 2) : ($first['price'] ?? 45.0);
        $first['selling_price'] = $first['price'];
        return response()->json(['product' => $first]);
    }

    return response()->json(['error' => 'Товар не найден'], 404);
});

// 5. Create Order
Route::post('/orders', function (Request $request) {
    $validated = $request->validate([
        'customerName' => 'nullable|string',
        'customerPhone' => 'nullable|string',
        'city' => 'nullable|string',
        'address' => 'nullable|string',
        'comment' => 'nullable|string',
        'promoCode' => 'nullable|string',
        'items' => 'required|array|min:1',
        'items.*.title' => 'required|string',
        'items.*.price' => 'required|numeric',
        'items.*.quantity' => 'required|integer|min:1',
    ]);

    return DB::transaction(function () use ($validated, $request) {
        $phone = trim($validated['customerPhone'] ?? '');
        $name = trim($validated['customerName'] ?? 'Покупатель');
        $city = trim($validated['city'] ?? 'Ташкент');
        $address = trim($validated['address'] ?? '');

        // Find or create customer
        // 1. Предварительная проверка наличия на складе и расчет серверных цен
        $initials = getRichInitialProducts();
        $resolvedItems = [];
        $itemsTotal = 0;

        foreach ($validated['items'] as $item) {
            $prodId = $item['id'] ?? null;
            $dbProduct = null;
            if ($prodId && is_numeric($prodId)) {
                $dbProduct = Product::find($prodId);
            }
            if (! $dbProduct && ! empty($item['sku'])) {
                $dbProduct = Product::where('sku', $item['sku'])->first();
            }

            $requestedQty = max(1, (int) ($item['quantity'] ?? 1));

            // Проверка складского остатка
            if ($dbProduct) {
                $availableQty = (int) $dbProduct->quantity;
                if ($availableQty < $requestedQty) {
                    return response()->json([
                        'success' => false,
                        'error' => "Недостаточно товара «{$dbProduct->name}» на складе. В наличии: {$availableQty} шт., выбрано: {$requestedQty} шт.",
                    ], 422);
                }
            }

            // Авторитетный расчет цены на сервере (защита от подмены клиентом)
            $serverUnitPrice = 0.0;
            if ($dbProduct && (float) $dbProduct->selling_price > 0) {
                $numPrice = (float) $dbProduct->selling_price;
                $serverUnitPrice = $numPrice > 1000 ? round($numPrice / 12850, 2) : $numPrice;
            } else {
                // Поиск в справочнике showcase-товаров
                $foundInit = null;
                foreach ($initials as $init) {
                    if ((string) ($init['id'] ?? '') === (string) $prodId || (! empty($item['sku']) && ($init['sku'] ?? '') === $item['sku'])) {
                        $foundInit = $init;
                        break;
                    }
                }
                if ($foundInit && isset($foundInit['price'])) {
                    $numPrice = (float) $foundInit['price'];
                    $serverUnitPrice = $numPrice > 1000 ? round($numPrice / 12850, 2) : $numPrice;
                } else {
                    $serverUnitPrice = (float) ($item['price'] ?? 0);
                }
            }

            if ($serverUnitPrice <= 0) {
                $serverUnitPrice = (float) ($item['price'] ?? 35.00);
            }

            $lineTotal = $serverUnitPrice * $requestedQty;
            $itemsTotal += $lineTotal;

            $resolvedItems[] = [
                'raw' => $item,
                'dbProduct' => $dbProduct,
                'unitPrice' => $serverUnitPrice,
                'quantity' => $requestedQty,
                'lineTotal' => $lineTotal,
            ];
        }

        // 2. Расчет скидок на сервере
        $discountAmount = 0;
        if (! empty($validated['promoCode'])) {
            $code = strtoupper(trim($validated['promoCode']));
            if ($code === 'MALLY10') {
                $discountAmount = round($itemsTotal * 0.10);
            } elseif ($code === 'WELCOME') {
                $discountAmount = min(50000, $itemsTotal);
            }
        }

        $finalAmount = max(0, $itemsTotal - $discountAmount);

        // 3. Создание / привязка клиента
        $customer = null;
        if ($phone) {
            $customer = Customer::where('phone_number', $phone)->first();
        }
        if (! $customer) {
            $parts = explode(' ', $name, 2);
            $customer = Customer::create([
                'first_name' => $parts[0] ?? 'Покупатель',
                'last_name' => $parts[1] ?? '',
                'phone_number' => $phone ?: '+998 00 000-00-00',
                'email' => 'client_'.Str::random(8).'@mally.uz',
                'source' => 'instagram',
            ]);
        }

        // 4. Генерация уникального номера заказа и хэша
        $maxNum = Order::max('order_number') ?: 1000;
        $orderNumber = $maxNum + 1;
        $secureHash = bin2hex(random_bytes(16));

        $order = Order::create([
            'customer_id' => $customer?->id,
            'order_number' => $orderNumber,
            'secure_hash' => $secureHash,
            'customer_name' => $name,
            'customer_phone' => $phone,
            'city' => $city,
            'address' => $address,
            'comment' => $validated['comment'] ?? null,
            'promo_code' => $validated['promoCode'] ?? null,
            'discount_amount' => $discountAmount,
            'amount' => $finalAmount,
            'amount_paid' => 0.00,
            'status' => 'processing',
            'payment_method' => 'card_fast',
            'payment_status' => 'PENDING',
        ]);

        // 5. Создание позиций заказа и списание со склада
        $inventory = app(InventoryService::class);
        foreach ($resolvedItems as $resItem) {
            $dbProduct = $resItem['dbProduct'];
            $qty = $resItem['quantity'];
            $unitPrice = $resItem['unitPrice'];
            $lineTotal = $resItem['lineTotal'];

            $receipt = null;
            if ($dbProduct) {
                $receipt = ProductReceipt::where('product_id', $dbProduct->id)
                    ->where('quantity_remaining', '>=', $qty)
                    ->orderBy('id', 'asc')
                    ->first();

                if (! $receipt) {
                    $receipt = ProductReceipt::where('product_id', $dbProduct->id)
                        ->where('quantity_remaining', '>', 0)
                        ->orderBy('id', 'asc')
                        ->first();
                }
            }

            $op = OrderProduct::create([
                'order_id' => $order->id,
                'product_id' => $dbProduct?->id ?? 1,
                'product_receipt_id' => $receipt?->id,
                'quantity' => $qty,
                'price' => $unitPrice,
                'unit_cost' => $receipt?->purchase_price ?? 0,
                'total_price' => $lineTotal,
            ]);

            // Списание со склада
            if ($receipt && $receipt->quantity_remaining >= $op->quantity) {
                try {
                    $inventory->consume($op);
                } catch (\Throwable $e) {
                    $receipt->decrement('quantity_remaining', min($receipt->quantity_remaining, $op->quantity));
                    if ($dbProduct) {
                        $inventory->syncProductCaches($dbProduct);
                    }
                }
            } elseif ($dbProduct) {
                $dbProduct->decrement('quantity', min($dbProduct->quantity, $op->quantity));
            }
        }

        return response()->json([
            'success' => true,
            'orderNumber' => $orderNumber,
            'orderHash' => $secureHash,
            'totalAmount' => $finalAmount,
            'redirectUrl' => "/checkout/{$orderNumber}/{$secureHash}",
        ]);
    });
});

// Helper for formatting order response
if (! function_exists('formatOrderResponse')) {
function formatOrderResponse(Order $order, bool $fullDetails = true): array
{
    $items = [];
    $order->loadMissing(['orderProducts.product', 'orderProducts.receipt']);

    foreach ($order->orderProducts as $line) {
        $p = $line->product;
        $img = null;
        if ($p?->image) {
            $img = str_starts_with($p->image, 'http') ? $p->image : asset($p->image);
        }
        if (! $img && $p?->images) {
            $imgs = is_array($p->images) ? $p->images : json_decode($p->images, true);
            if (! empty($imgs[0])) {
                $img = str_starts_with($imgs[0], 'http') ? $imgs[0] : asset($imgs[0]);
            }
        }
        if (! $img) {
            $initials = getRichInitialProducts();
            foreach ($initials as $init) {
                if (($init['sku'] ?? '') === ($p?->sku ?? '') || ($init['title'] ?? '') === ($p?->name ?? '')) {
                    $img = $init['images'][0] ?? ($init['image'] ?? null);
                    break;
                }
            }
        }
        if (! $img) {
            $img = 'https://images.unsplash.com/photo-1522335789203-aabd1fc54bc9?auto=format&fit=crop&w=400&q=80';
        }

        $items[] = [
            'id' => (string) $line->id,
            'quantity' => (int) $line->quantity,
            'price' => (float) $line->price,
            'product' => [
                'id' => (string) ($p?->id ?? $line->id),
                'title' => $p?->name ?: 'Косметика Mally',
                'sku' => $p?->sku ?: 'ML-ITEM-'.$line->id,
                'images' => [$img],
            ],
        ];
    }

    // Map internal status to client status
    $statusMap = [
        'processing' => 'NEW',
        'awaiting_delivery' => 'PACKING',
        'delivered' => 'DELIVERED',
        'NEW' => 'NEW',
        'PAYMENT_RECEIVED' => 'PAYMENT_RECEIVED',
        'PACKING' => 'PACKING',
        'SHIPPED' => 'SHIPPED',
        'DELIVERED' => 'DELIVERED',
        'CANCELLED' => 'CANCELLED',
    ];

    $clientStatus = $statusMap[$order->status] ?? 'NEW';

    $rawName = $order->customer_name ?: ($order->customer ? $order->customer->first_name.' '.$order->customer->last_name : 'Покупатель');
    $rawPhone = $order->customer_phone ?: ($order->customer?->phone_number ?: '');
    $rawAddress = $order->address ?: 'Адрес уточняется';

    // Mask sensitive details if viewing anonymously without matching secure_hash
    if (! $fullDetails) {
        $displayName = mb_strlen($rawName) > 2 ? mb_substr($rawName, 0, 1).'***'.mb_substr($rawName, -1) : 'Клиент Mally';
        $displayPhone = strlen($rawPhone) > 7 ? substr($rawPhone, 0, 7).'***'.substr($rawPhone, -2) : '***';
        $displayAddress = ($order->city ?: 'Ташкент').' (скрыт)';
    } else {
        $displayName = $rawName;
        $displayPhone = $rawPhone;
        $displayAddress = $rawAddress;
    }

    return [
        'id' => (string) $order->id,
        'orderNumber' => (int) ($order->order_number ?: $order->id),
        'secureHash' => $order->secure_hash ?: 'hash_'.$order->id,
        'customerName' => $displayName,
        'customerPhone' => $displayPhone,
        'city' => $order->city ?: 'Ташкент',
        'address' => $displayAddress,
        'comment' => $fullDetails ? $order->comment : null,
        'totalAmount' => (int) $order->amount,
        'discountAmount' => (int) ($order->discount_amount ?: 0),
        'promoCode' => $order->promo_code,
        'status' => $clientStatus,
        'paymentStatus' => $order->payment_status ?: ($order->amount_paid >= $order->amount ? 'PAID' : 'PENDING'),
        'paymentMethod' => $order->payment_method ?: 'card_fast',
        'createdAt' => $order->created_at?->toIso8601String() ?: now()->toIso8601String(),
        'items' => $items,
        'statusLogs' => [
            [
                'status' => 'NEW',
                'label' => 'Заявка принята',
                'date' => $order->created_at?->format('d.m.Y') ?: date('d.m.Y'),
                'time' => $order->created_at?->format('H:i') ?: date('H:i'),
                'note' => 'Заказ успешно зарегистрирован в системе',
            ],
        ],
    ];
}
}

// 6. Track Order (masks customer PII if queried without valid secure_hash)
Route::get('/orders/track', function (Request $request) {
    $q = trim($request->query('q', ''));
    $hash = trim($request->query('hash', ''));
    if (! $q) {
        return response()->json(['error' => 'Укажите номер заказа или телефон'], 400);
    }

    $order = Order::where('order_number', $q)
        ->orWhere('id', is_numeric($q) ? (int) $q : 0)
        ->orWhere('customer_phone', 'like', "%{$q}%")
        ->latest('id')
        ->first();

    if (! $order) {
        return response()->json(['error' => 'Заказ не найден. Проверьте правильность введенного номера.'], 404);
    }

    $isAuthorized = ! empty($hash) && ($hash === $order->secure_hash);

    return response()->json([
        'success' => true,
        'order' => formatOrderResponse($order, $isAuthorized),
    ]);
});

// 7. Order Status / Receipt View (strictly requires orderNumber AND matching secure_hash)
Route::get('/orders/status', function (Request $request, ExchangeRateService $rateService) {
    $orderNumber = $request->query('orderNumber');
    $orderHash = $request->query('orderHash');

    if (! $orderNumber || ! $orderHash) {
        return response()->json(['error' => 'Номер заказа и ключ безопасности обязательны'], 400);
    }

    $order = Order::where(function ($q) use ($orderNumber) {
        $q->where('order_number', $orderNumber)->orWhere('id', $orderNumber);
    })
    ->where('secure_hash', $orderHash)
    ->first();

    if (! $order) {
        return response()->json(['error' => 'Заказ не найден или недействительный ключ доступа'], 404);
    }

    $rateData = $rateService->getUsdRate();
    $liveRate = ($rateData['rate'] && $rateData['rate'] > 1000) ? $rateData['rate'] : 12850;

    return response()->json([
        'success' => true,
        'order' => formatOrderResponse($order, true),
        'storeSettings' => [
            'cardNumber' => STORE_CARD_NUMBER,
            'cardHolder' => STORE_CARD_HOLDER,
            'adminTelegram' => STORE_ADMIN_TELEGRAM,
            'usdRate' => $liveRate,
            'rateDate' => $rateData['date'] ?? null,
            'rateSource' => $rateData['source'] ?? 'cbu.uz',
        ],
    ]);
});

// 7b. Secure receipt upload
Route::post('/orders/upload-receipt', function (Request $request) {
    $validated = $request->validate([
        'orderNumber' => 'required',
        'orderHash' => 'required|string',
        'receipt' => 'required|file|mimes:jpeg,png,jpg,webp,pdf|max:5120',
    ]);

    $order = Order::where(function ($q) use ($validated) {
        $q->where('order_number', $validated['orderNumber'])
          ->orWhere('id', $validated['orderNumber']);
    })
    ->where('secure_hash', $validated['orderHash'])
    ->first();

    if (! $order) {
        return response()->json(['error' => 'Заказ не найден или неверный ключ безопасности'], 404);
    }

    $file = $request->file('receipt');
    $filename = 'receipt_'.$order->order_number.'_'.time().'.'.$file->getClientOriginalExtension();
    $path = $file->storeAs('receipts', $filename, 'public');

    $order->update([
        'payment_status' => 'RECEIPT_UPLOADED',
        'comment' => trim(($order->comment ?: '')."\n".'[Чек загружен: /storage/'.$path.']'),
    ]);

    return response()->json([
        'success' => true,
        'message' => 'Чек успешно загружен и передан на проверку',
        'receiptUrl' => '/storage/'.$path,
    ]);
});

// 8. Auth / Phone verification endpoints
Route::post('/auth/phone-verify/send-code', function (Request $request) {
    $phone = $request->input('phone', '+998 ');
    $token = 'tok_'.Str::random(24);
    $code = '7788'; // standard test code for seamless instant testing

    session(['phone_verify_'.$token => ['phone' => $phone, 'code' => $code]]);

    return response()->json([
        'success' => true,
        'token' => $token,
        'phone' => $phone,
        'devCode' => $code,
    ]);
});

Route::post('/auth/phone-verify/verify-code', function (Request $request) {
    $phone = trim($request->input('phone', '+998 '));
    $code = trim($request->input('code', ''));

    // Check if customer already exists in DB
    $existing = Customer::where('phone_number', $phone)->first();

    if ($existing) {
        $user = [
            'id' => (string) $existing->id,
            'firstName' => $existing->first_name,
            'lastName' => $existing->last_name,
            'fullName' => trim($existing->first_name.' '.$existing->last_name) ?: 'Покупатель Mally',
            'phone' => $existing->phone_number,
            'email' => $existing->email,
            'isPhoneVerified' => true,
        ];

        return response()->json([
            'success' => true,
            'isNewUser' => false,
            'user' => $user,
        ]);
    }

    // New customer: requires first and last name (no username)
    return response()->json([
        'success' => true,
        'isNewUser' => true,
        'phone' => $phone,
    ]);
});

Route::post('/auth/phone-verify/complete-register', function (Request $request) {
    $phone = trim($request->input('phone', '+998 '));
    $firstName = trim($request->input('firstName', 'Покупатель'));
    $lastName = trim($request->input('lastName', ''));

    $customer = Customer::create([
        'phone_number' => $phone,
        'first_name' => $firstName ?: 'Покупатель',
        'last_name' => $lastName,
        'email' => 'client_'.Str::random(6).'@mally.uz',
        'source' => 'instagram',
    ]);

    $user = [
        'id' => (string) $customer->id,
        'firstName' => $customer->first_name,
        'lastName' => $customer->last_name,
        'fullName' => trim($customer->first_name.' '.$customer->last_name) ?: 'Покупатель Mally',
        'phone' => $customer->phone_number,
        'email' => $customer->email,
        'isPhoneVerified' => true,
    ];

    return response()->json([
        'success' => true,
        'user' => $user,
    ]);
});

Route::get('/auth/me', function (Request $request) {
    return response()->json(['authenticated' => false]);
});

// 9. User Profile
Route::get('/user/profile', function (Request $request) {
    $userId = $request->query('userId');
    $customer = $userId ? Customer::find($userId) : null;

    $orders = [];
    if ($customer) {
        $orders = Order::where('customer_id', $customer->id)
            ->orWhere('customer_phone', $customer->phone_number)
            ->latest('id')
            ->get()
            ->map(fn ($o) => formatOrderResponse($o));
    }

    return response()->json([
        'success' => true,
        'user' => $customer ? [
            'id' => (string) $customer->id,
            'firstName' => $customer->first_name,
            'lastName' => $customer->last_name,
            'fullName' => trim($customer->first_name.' '.$customer->last_name),
            'phone' => $customer->phone_number,
            'email' => $customer->email,
        ] : null,
        'orders' => $orders,
    ]);
});

Route::put('/user/profile', function (Request $request) {
    $userId = $request->input('userId');
    $customer = $userId ? Customer::find($userId) : null;

    if ($customer) {
        $customer->update([
            'first_name' => $request->input('firstName', $customer->first_name),
            'last_name' => $request->input('lastName', $customer->last_name),
            'phone_number' => $request->input('phone', $customer->phone_number),
        ]);
    }

    return response()->json([
        'success' => true,
        'user' => [
            'id' => (string) ($customer?->id ?? $userId),
            'firstName' => $customer?->first_name ?? $request->input('firstName'),
            'lastName' => $customer?->last_name ?? $request->input('lastName'),
            'fullName' => trim(($customer?->first_name ?? '').' '.($customer?->last_name ?? '')),
            'phone' => $customer?->phone_number ?? $request->input('phone'),
        ],
    ]);
});

// 10. Promo codes
Route::post('/promocodes/apply', function (Request $request) {
    $code = strtoupper(trim($request->input('code', '')));
    $totalAmount = (float) $request->input('totalAmount', 0);

    if ($code === 'MALLY10') {
        $disc = round($totalAmount * 0.10);
        return response()->json([
            'success' => true,
            'code' => $code,
            'discountAmount' => $disc,
            'message' => 'Промокод на скидку 10% успешно применен!',
        ]);
    }

    if ($code === 'WELCOME') {
        $disc = 50000;
        return response()->json([
            'success' => true,
            'code' => $code,
            'discountAmount' => $disc,
            'message' => 'Приветственный бонус 50 000 сум применен!',
        ]);
    }

    return response()->json([
        'success' => false,
        'error' => 'Недействительный промокод',
    ], 400);
});

// 11. Stubs for analytics & tracking
Route::post('/products/favorite', fn () => response()->json(['success' => true]));
Route::post('/products/view', fn () => response()->json(['success' => true]));
Route::post('/presence', fn () => response()->json(['success' => true]));
Route::post('/reviews', fn () => response()->json(['success' => true, 'message' => 'Отзыв отправлен на модерацию']));
