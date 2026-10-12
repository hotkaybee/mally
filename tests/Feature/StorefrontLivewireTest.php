<?php

namespace Tests\Feature;

use App\Livewire\Storefront\CartPage;
use App\Livewire\Storefront\CatalogPage;
use App\Livewire\Storefront\CheckoutPage;
use App\Livewire\Storefront\ContactPage;
use App\Livewire\Storefront\FavoritesPage;
use App\Livewire\Storefront\HomePage;
use App\Livewire\Storefront\OrderReceiptPage;
use App\Livewire\Storefront\ProductDetailPage;
use App\Livewire\Storefront\ProfilePage;
use App\Livewire\Storefront\ReturnsPage;
use App\Models\Brand;
use App\Models\Category;
use App\Models\Customer;
use App\Models\Order;
use App\Models\OrderProduct;
use App\Models\Product;
use App\Services\ExchangeRateService;
use App\Services\OrderNumberRetryService;
use App\Services\StorefrontService;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Livewire\Livewire;
use RuntimeException;
use Tests\TestCase;

class StorefrontLivewireTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Cache::put(ExchangeRateService::CACHE_KEY, [
            'rate' => 12500.0,
            'source' => 'cbu.uz',
            'date' => '2026-10-11',
            'fetchedAt' => now()->toIso8601String(),
            'isLive' => true,
        ], 3600);
    }

    public function test_all_storefront_routes_and_livewire_components_render(): void
    {
        $this->get(route('home'))->assertOk()->assertSeeLivewire(HomePage::class);
        $this->get(route('catalog'))->assertOk()->assertSeeLivewire(CatalogPage::class);
        $this->get(route('product.detail', ['slug' => 'ML-HG-BRUSH']))->assertOk()->assertSeeLivewire(ProductDetailPage::class);
        $this->get(route('cart'))->assertOk()->assertSeeLivewire(CartPage::class);
        $this->get(route('favorites'))->assertOk()->assertSeeLivewire(FavoritesPage::class);
        $this->get(route('profile'))->assertOk()->assertSeeLivewire(ProfilePage::class);
        $this->get(route('returns'))->assertOk()->assertSeeLivewire(ReturnsPage::class);
        $this->get(route('contact'))->assertOk()->assertSeeLivewire(ContactPage::class);
    }

    public function test_catalog_filtering_and_search_work(): void
    {
        Livewire::test(CatalogPage::class)
            ->set('searchQuery', 'Hourglass')
            ->assertSee('Ambient Lighting');
    }

    public function test_missing_product_returns_404(): void
    {
        $service = app(StorefrontService::class);
        $this->assertNull($service->findProduct('non-existent-sku-999999'));
        $this->get(route('product.detail', ['slug' => 'non-existent-sku-999999']))->assertNotFound();
    }

    public function test_promo_code_validation_rejects_welcome_and_accepts_mally10(): void
    {
        $service = app(StorefrontService::class);

        $welcomeRes = $service->applyPromoCode('WELCOME', 100.0);
        $this->assertFalse($welcomeRes['success'] ?? true);
        $this->assertNull($service->getPromoCode());

        $mallyRes = $service->applyPromoCode('MALLY10', 100.0);
        $this->assertTrue($mallyRes['success'] ?? false);
        $this->assertSame('MALLY10', $service->getPromoCode());
        $this->assertEquals(10.0, $mallyRes['discountUsd']);
    }

    public function test_cart_revalidates_stale_price_and_stock_against_database(): void
    {
        $brand = Brand::create(['name' => 'Hourglass']);
        $category = Category::create([
            'name_ru' => 'Макияж',
            'name_en' => 'Makeup',
            'name_uz' => 'Makiyaj',
        ]);

        $product = Product::create([
            'brand_id' => $brand->id,
            'category_id' => $category->id,
            'name' => 'Test Ambient Brush',
            'sku' => 'ML-TEST-BRUSH',
            'selling_price' => 60.0,
            'quantity' => 2,
            'status' => 'in_stock',
        ]);

        session([
            'mally_cart' => [
                [
                    'id' => (string) $product->id,
                    'sku' => 'ML-TEST-BRUSH',
                    'title' => 'Test Ambient Brush',
                    'brand' => 'Hourglass',
                    'price' => 45.0,
                    'quantity' => 5,
                    'maxQuantity' => 10,
                ],
            ],
        ]);

        $service = app(StorefrontService::class);
        $validation = $service->validateAndSyncCart();

        $this->assertFalse($validation['valid']);
        $this->assertTrue($validation['price_changed']);
        $this->assertTrue($validation['stock_changed']);
        $this->assertSame(2, $validation['cart']['items'][0]['quantity']);
        $this->assertEquals(60.0, $validation['cart']['items'][0]['product']['price']);
    }

    public function test_checkout_preserves_city_and_creates_order_and_protects_receipt_access(): void
    {
        $brand = Brand::create(['name' => 'Hourglass']);
        $category = Category::create([
            'name_ru' => 'Кисти для макияжа',
            'name_en' => 'Makeup Brushes',
            'name_uz' => 'Makiyaj cho‘tkalari',
        ]);

        $product = Product::create([
            'brand_id' => $brand->id,
            'category_id' => $category->id,
            'name' => 'Двухсторонняя кисть Ambient Lighting Edit Unlocked Brush / Love',
            'sku' => 'ML-HG-BRUSH',
            'selling_price' => 55.00,
            'quantity' => 8,
            'status' => 'in_stock',
        ]);

        $customer = Customer::create([
            'first_name' => 'Мадина',
            'last_name' => 'Умарова',
            'email' => 'madina@mally.uz',
            'phone_number' => '+998 90 123 45 67',
            'instagram' => 'Самарканд | ул. Регистан 10',
            'source' => 'instagram',
        ]);

        session(['mally_customer_id' => $customer->id]);
        app(StorefrontService::class)->addToCart('ML-HG-BRUSH');

        Livewire::test(CheckoutPage::class)
            ->assertSet('city', 'Самарканд')
            ->assertSet('address', 'ул. Регистан 10')
            ->call('placeOrder');

        $order = Order::first();
        $this->assertNotNull($order);
        $this->assertSame('Самарканд', $order->city);
        $this->assertNotEmpty($order->secure_hash);
        $this->assertSame(7, $product->fresh()->quantity);

        // Authorized via secure_hash
        session()->forget('mally_customer_id');
        $this->get(route('checkout.receipt', [
            'orderNumber' => $order->order_number,
            'orderHash' => $order->secure_hash,
        ]))->assertOk()->assertSeeLivewire(OrderReceiptPage::class);

        // Blocked when guest accesses receipt without secure_hash
        $this->get(route('checkout.receipt', [
            'orderNumber' => $order->order_number,
        ]))->assertForbidden();
    }

    public function test_high_usd_selling_price_is_never_treated_as_uzs_and_converts_using_exchange_rate(): void
    {
        $brand = Brand::create(['name' => 'La Mer']);
        $category = Category::create([
            'name_ru' => 'Уход',
            'name_en' => 'Skincare',
            'name_uz' => 'Parvarish',
        ]);

        $product = Product::create([
            'brand_id' => $brand->id,
            'category_id' => $category->id,
            'name' => 'Luxury Trunk Vault Set',
            'sku' => 'ML-LUX-1200',
            'selling_price' => 1200.00,
            'quantity' => 5,
            'status' => 'in_stock',
        ]);

        $service = app(StorefrontService::class);
        $formatted = $service->formatProduct($product);

        $this->assertSame(1200.0, $formatted['price']);
        $this->assertSame(1200.0, $formatted['selling_price']);
        $this->assertSame('$1,200.00', $service->formatPrice(1200.00, 'USD'));
        $this->assertSame('15 000 000 сум', $service->formatPrice(1200.00, 'UZS'));
        $this->assertSame(15000000, $service->convertUsdToUzs(1200.00));

        // When exchange rate is missing/zero, UZS conversion fails safely
        Cache::put(ExchangeRateService::CACHE_KEY, [
            'rate' => null,
            'source' => 'cbu.uz',
            'date' => null,
            'fetchedAt' => now()->toIso8601String(),
            'isLive' => false,
        ], 3600);

        $freshService = new StorefrontService(new class extends ExchangeRateService
        {
            public function getUsdRate(bool $forceRefresh = false): array
            {
                return [
                    'rate' => null,
                    'source' => 'cbu.uz',
                    'date' => null,
                    'fetchedAt' => now()->toIso8601String(),
                    'isLive' => false,
                ];
            }
        });

        $this->assertSame('-', $freshService->formatPrice(1200.00, 'UZS'));

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Курс валют временно недоступен. Пожалуйста, попробуйте чуть позже.');
        $freshService->convertUsdToUzs(1200.00);
    }

    public function test_checkout_uses_database_selling_price_and_ignores_stale_cart_price(): void
    {
        $brand = Brand::create(['name' => 'Dyson']);
        $category = Category::create([
            'name_ru' => 'Стайлеры',
            'name_en' => 'Stylers',
            'name_uz' => 'Staylerlar',
        ]);

        $product = Product::create([
            'brand_id' => $brand->id,
            'category_id' => $category->id,
            'name' => 'Dyson Pro Set',
            'sku' => 'ML-DYSON-1200',
            'selling_price' => 1200.00,
            'quantity' => 4,
            'status' => 'in_stock',
        ]);

        $customer = Customer::create([
            'first_name' => 'Азиза',
            'last_name' => 'Каримова',
            'email' => 'aziza@mally.uz',
            'phone_number' => '+998 90 111 22 33',
            'instagram' => 'Ташкент | ул. Амира Темура 1',
            'source' => 'instagram',
        ]);

        // Client/session cart has a tampered/stale low price (10 USD instead of 1200 USD)
        session([
            'mally_customer_id' => $customer->id,
            'mally_cart' => [
                [
                    'id' => (string) $product->id,
                    'sku' => 'ML-DYSON-1200',
                    'title' => 'Dyson Pro Set',
                    'price' => 10.00,
                    'quantity' => 2,
                ],
            ],
        ]);

        Livewire::test(CheckoutPage::class)
            ->call('placeOrder');

        $order = Order::first();
        $this->assertNotNull($order);
        // 1200 USD * 12500 UZS/USD = 15,000,000 UZS per unit; 2 units = 30,000,000 UZS
        $this->assertEquals(30000000.0, (float) $order->amount);

        $line = OrderProduct::where('order_id', $order->id)->first();
        $this->assertNotNull($line);
        $this->assertEquals(15000000.0, (float) $line->price);
        $this->assertEquals(30000000.0, (float) $line->total_price);
        $this->assertSame(2, $product->fresh()->quantity);
    }

    public function test_checkout_fails_cleanly_when_cart_product_is_deleted_without_creating_placeholder_records(): void
    {
        $customer = Customer::create([
            'first_name' => 'Лола',
            'last_name' => 'Юсупова',
            'email' => 'lola@mally.uz',
            'phone_number' => '+998 90 555 66 77',
            'instagram' => 'Ташкент | ул. Ойбека 12',
            'source' => 'instagram',
        ]);

        session([
            'mally_customer_id' => $customer->id,
            'mally_cart' => [
                [
                    'id' => '99999',
                    'sku' => 'ML-DELETED-ITEM',
                    'title' => 'Удалённая палетка',
                    'brand' => 'Ghost Brand',
                    'category' => 'Ghost Category',
                    'price' => 50.00,
                    'quantity' => 1,
                    'stock' => 5,
                    'inStock' => true,
                ],
            ],
        ]);

        Livewire::test(CheckoutPage::class)
            ->call('placeOrder')
            ->assertDispatched(
                'toast',
                message: 'Товар «Удалённая палетка» больше не доступен. Обновите корзину.',
                type: 'error'
            );

        $this->assertSame(0, Product::count());
        $this->assertSame(0, Brand::count());
        $this->assertSame(0, Category::count());
        $this->assertSame(0, Order::count());
        $this->assertSame(0, OrderProduct::count());
    }

    public function test_order_number_retry_service_retries_on_duplicate_order_number_and_fails_after_max_attempts(): void
    {
        $existingOrder = Order::create([
            'customer_id' => null,
            'order_number' => 1001,
            'amount' => 1000,
            'amount_paid' => 0,
            'status' => 'processing',
        ]);

        $retryService = app(OrderNumberRetryService::class);
        $attempts = 0;

        $order = $retryService->run(function (int $orderNumber) use ($existingOrder, &$attempts): Order {
            $attempts++;

            return Order::create([
                'customer_id' => null,
                // First attempt simulates a race condition colliding with 1001; retry uses fresh $orderNumber (1002)
                'order_number' => $attempts === 1 ? $existingOrder->order_number : $orderNumber,
                'amount' => 2000,
                'amount_paid' => 0,
                'status' => 'processing',
            ]);
        });

        $this->assertSame(2, $attempts);
        $this->assertSame(1002, (int) $order->order_number);

        // Verify failure after max attempts when every attempt collides
        $failAttempts = 0;

        try {
            $retryService->run(function () use ($existingOrder, &$failAttempts): Order {
                $failAttempts++;

                return Order::create([
                    'customer_id' => null,
                    'order_number' => $existingOrder->order_number,
                    'amount' => 3000,
                    'amount_paid' => 0,
                    'status' => 'processing',
                ]);
            }, maxAttempts: 3);

            $this->fail('Expected QueryException after retry limit was reached.');
        } catch (QueryException $e) {
            $this->assertSame(3, $failAttempts);
        }
    }

    public function test_order_number_retry_service_does_not_retry_non_order_number_database_exceptions(): void
    {
        $retryService = app(OrderNumberRetryService::class);
        $attempts = 0;

        try {
            $retryService->run(function (int $orderNumber) use (&$attempts): Order {
                $attempts++;

                // Trigger a foreign key constraint violation on orders.customer_id
                return Order::create([
                    'customer_id' => 999999,
                    'order_number' => $orderNumber,
                    'amount' => 1000,
                    'amount_paid' => 0,
                    'status' => 'processing',
                ]);
            }, maxAttempts: 3);

            $this->fail('Expected QueryException for foreign key violation.');
        } catch (QueryException $e) {
            $this->assertSame(1, $attempts);
        }
    }

    public function test_removed_public_order_tracking_endpoint_returns_404(): void
    {
        $this->getJson('/api/orders/track?q=1001')->assertNotFound();
    }

    public function test_customer_review_persists_to_database_and_appears_on_product(): void
    {
        $customer = Customer::create([
            'first_name' => 'Дильноза',
            'last_name' => 'Каримова',
            'email' => 'dilnoza@mally.uz',
            'phone_number' => '+998 99 822 28 80',
            'instagram' => 'Бухара | ул. Навои 15',
            'source' => 'instagram',
        ]);

        session(['mally_customer_id' => $customer->id]);

        Livewire::test(ProfilePage::class)
            ->call('openProductReview', 'ML-HG-BRUSH', 'Двухсторонняя кисть Ambient Lighting Edit Unlocked Brush / Love', 'https://findy.uz/cdn/shop/files/hourglass-brush-1.jpg', 1005)
            ->set('reviewRating', 5)
            ->set('reviewComment', 'Прекрасная кисть, доставили быстро в Бухару!')
            ->call('submitReview')
            ->assertSet('reviewTarget', null)
            ->assertSet('reviewComment', '');

        $this->assertDatabaseHas('reviews', [
            'customer_id' => $customer->id,
            'product_sku' => 'ML-HG-BRUSH',
            'order_number' => 1005,
            'city' => 'Бухара',
            'rating' => 5,
            'comment' => 'Прекрасная кисть, доставили быстро в Бухару!',
        ]);

        $product = app(StorefrontService::class)->findProduct('ML-HG-BRUSH');
        $this->assertNotNull($product);
        $this->assertNotEmpty($product['reviews']);
        $this->assertSame('Прекрасная кисть, доставили быстро в Бухару!', $product['reviews'][0]['comment']);
    }
}
