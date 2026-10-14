<?php

namespace Tests\Feature;

use App\Livewire\Storefront\CartPage;
use App\Livewire\Storefront\CatalogPage;
use App\Livewire\Storefront\CheckoutPage;
use App\Livewire\Storefront\ContactPage;
use App\Livewire\Storefront\FavoritesPage;
use App\Livewire\Storefront\HomePage;
use App\Livewire\Storefront\ProductDetailPage;
use App\Livewire\Storefront\ProfilePage;
use App\Livewire\Storefront\ReturnsPage;
use App\Models\Brand;
use App\Models\Category;
use App\Models\Customer;
use App\Models\Product;
use App\Services\ExchangeRateService;
use App\Services\OrderService;
use App\Services\StorefrontService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Livewire\Exceptions\MethodNotFoundException;
use Livewire\Livewire;
use Tests\TestCase;

class StorefrontLivewireTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Cache::put(ExchangeRateService::CACHE_KEY, [
            'rate' => 12199.57,
            'date' => '11.10.2026',
            'source' => 'cbu.uz',
            'isLive' => true,
            'updatedAt' => now()->toIso8601String(),
        ], 3600);
    }

    private function product(string $name = 'Ambient Lighting Brush', string $sku = 'ML-HG-BRUSH', float $price = 57.15, int $qty = 3): Product
    {
        $brand = Brand::firstOrCreate(['name' => 'Hourglass']);
        $category = Category::firstOrCreate(['name_ru' => 'Макияж'], ['name_en' => 'Makeup', 'name_uz' => 'Makiyaj']);

        $product = Product::create([
            'brand_id' => $brand->id,
            'category_id' => $category->id,
            'name' => $name,
            'sku' => $sku,
            'quantity' => 0,
            'selling_price' => 0,
            'status' => 'in_stock',
        ]);

        if ($qty > 0) {
            $product->receipts()->create([
                'purchase_price' => 20, 'delivery_cost' => 5, 'markup' => 0,
                'selling_price' => $price, 'website_price' => 0,
                'quantity' => $qty, 'received_at' => now()->subDay(),
            ]);
        }

        return $product->fresh();
    }

    public function test_all_storefront_routes_and_livewire_components_render(): void
    {
        $this->product();

        $this->get(route('home'))->assertOk()->assertSeeLivewire(HomePage::class);
        $this->get(route('catalog'))->assertOk()->assertSeeLivewire(CatalogPage::class);
        $this->get(route('product.detail', ['slug' => 'ML-HG-BRUSH']))->assertOk()->assertSeeLivewire(ProductDetailPage::class);
        $this->get(route('cart'))->assertOk()->assertSeeLivewire(CartPage::class);
        $this->get(route('favorites'))->assertOk()->assertSeeLivewire(FavoritesPage::class);
        $this->get(route('profile'))->assertOk()->assertSeeLivewire(ProfilePage::class);
        $this->get(route('returns'))->assertOk()->assertSeeLivewire(ReturnsPage::class);
        $this->get(route('contact'))->assertOk()->assertSeeLivewire(ContactPage::class);
    }

    public function test_catalog_shows_only_database_products_and_search_works(): void
    {
        $this->product();
        $this->product('Rare Beauty Blush', 'ML-RB-1', 30, 2);

        Livewire::test(CatalogPage::class)
            ->set('searchQuery', 'Ambient')
            ->assertSee('Ambient Lighting Brush')
            ->assertDontSee('Rare Beauty Blush');

        // The old showcase JSON items are gone.
        $this->assertNull(app(StorefrontService::class)->findProduct('ML-RIT-AYUR'));
    }

    public function test_missing_product_returns_404(): void
    {
        $this->assertNull(app(StorefrontService::class)->findProduct('non-existent-sku-999999'));
        $this->get(route('product.detail', ['slug' => 'non-existent-sku-999999']))->assertNotFound();
    }

    public function test_promo_code_validation_rejects_welcome_and_accepts_mally10(): void
    {
        $service = app(StorefrontService::class);

        $this->assertFalse($service->applyPromoCode('WELCOME', 100.0)['success']);
        $this->assertNull($service->getPromoCode());

        $mallyRes = $service->applyPromoCode('mally10', 100.0);
        $this->assertTrue($mallyRes['success']);
        $this->assertSame('MALLY10', $service->getPromoCode());
        $this->assertEquals(10.0, $mallyRes['discountUsd']);
    }

    public function test_cart_revalidates_stale_price_and_stock_against_database(): void
    {
        $product = $this->product('Test Ambient Brush', 'ML-TEST-BRUSH', 60.0, 2);

        session(['mally_cart' => [[
            'id' => (string) $product->id,
            'sku' => 'ML-TEST-BRUSH',
            'title' => 'Test Ambient Brush',
            'price' => 45.0,
            'quantity' => 5,
        ]]]);

        $validation = app(StorefrontService::class)->validateAndSyncCart();

        $this->assertFalse($validation['valid']);
        $this->assertSame(2, $validation['cart']['items'][0]['quantity']);
        $this->assertEquals(60.0, $validation['cart']['items'][0]['product']['price']);
        $this->assertFalse($validation['cart']['items'][0]['product']['canIncrease']);
    }

    public function test_storefront_shows_availability_but_never_the_stock_count(): void
    {
        $this->product(qty: 7);
        $this->product('Sold Out Tint', 'ML-SOLD', 20, 0);

        $detail = $this->get(route('product.detail', ['slug' => 'ML-HG-BRUSH']))->assertOk();
        $detail->assertSee('В наличии');
        $detail->assertDontSee('7 шт');

        $this->get(route('product.detail', ['slug' => 'ML-SOLD']))->assertOk()->assertSee('Распродано');

        $api = $this->getJson('/api/products')->assertOk()->json('products');
        $this->assertArrayNotHasKey('quantity', $api[0]);
        $this->assertArrayHasKey('inStock', $api[0]);
    }

    public function test_no_public_page_or_endpoint_exposes_the_stock_count(): void
    {
        $this->product(qty: 437);
        $this->product('Sold Out Tint', 'ML-SOLD', 20, 0);
        $customer = Customer::create([
            'first_name' => 'Ali', 'last_name' => 'Valiev', 'email' => 'ali@example.com', 'phone_number' => '+998 90 111 22 33',
            'city' => 'Ташкент', 'address' => 'Navoi 1', 'source' => 'website',
        ]);
        session(['mally_customer_id' => $customer->id]);
        app(StorefrontService::class)->addToCart('ML-HG-BRUSH');

        // The count must not appear as a number next to a unit, nor in any snapshot.
        $pages = [
            CatalogPage::class => [],
            ProductDetailPage::class => ['slug' => 'ML-HG-BRUSH'],
            CartPage::class => [],
            CheckoutPage::class => [],
        ];
        foreach ($pages as $class => $params) {
            $component = Livewire::test($class, $params);
            $this->assertDoesNotMatchRegularExpression('/\b437\b/', strip_tags($component->html()), $class);
            $this->assertStringNotContainsString('437', json_encode($component->snapshot['data']), $class);
        }

        $this->get(route('catalog'))->assertSee('Sold Out Tint')->assertSee('Распродано');

        foreach (['/api/products' => 'products.0', '/api/products/ML-HG-BRUSH' => 'product'] as $url => $path) {
            $json = $this->getJson($url)->assertOk();
            $json->assertJsonPath($path.'.inStock', true);
            foreach (['quantity', 'stock', 'quantity_remaining', 'maxQuantity', 'available'] as $key) {
                $json->assertJsonMissingPath($path.'.'.$key);
            }
        }
    }

    public function test_plus_button_stops_at_available_stock(): void
    {
        $product = $this->product(qty: 2);
        $service = app(StorefrontService::class);

        $this->assertTrue($service->addToCart('ML-HG-BRUSH')['success']);
        $this->assertTrue($service->canIncrease('ML-HG-BRUSH'));
        $this->assertTrue($service->addToCart('ML-HG-BRUSH')['success']);
        $this->assertFalse($service->canIncrease('ML-HG-BRUSH'));

        $third = $service->addToCart('ML-HG-BRUSH');
        $this->assertFalse($third['success']);
        $this->assertStringNotContainsString('2', $third['message']);

        // A direct Livewire call with a bigger number is capped as well.
        Livewire::test(CartPage::class)->call('updateCartQuantity', (string) $product->id, 50);
        $this->assertSame(2, $service->getItemQuantityInCart((string) $product->id));
    }

    public function test_sold_out_product_cannot_be_added_and_restock_makes_it_available(): void
    {
        $product = $this->product(qty: 0);
        $service = app(StorefrontService::class);

        $this->assertFalse($service->addToCart('ML-HG-BRUSH')['success']);
        $this->assertFalse($service->findProduct('ML-HG-BRUSH')['inStock']);

        $product->receipts()->create([
            'purchase_price' => 20, 'delivery_cost' => 5, 'markup' => 0,
            'selling_price' => 50, 'website_price' => 0, 'quantity' => 4, 'received_at' => now(),
        ]);

        $this->assertTrue($service->findProduct('ML-HG-BRUSH')['inStock']);
        $this->assertTrue($service->addToCart('ML-HG-BRUSH')['success']);
    }

    public function test_customer_details_cannot_be_loaded_by_id_from_the_browser(): void
    {
        $product = $this->product();
        $victim = Customer::create([
            'first_name' => 'Victim', 'last_name' => 'Person', 'email' => 'victim@example.com',
            'phone_number' => '+998 90 765 43 21', 'city' => 'Бухара', 'address' => 'Secret street 7', 'source' => 'website',
        ]);
        session(['mally_cart' => [['id' => (string) $product->id, 'title' => $product->name, 'price' => 57.15, 'quantity' => 1]]]);

        foreach ([ProfilePage::class, CheckoutPage::class, ContactPage::class] as $class) {
            $component = Livewire::test($class);

            try {
                $component->call('onCustomerAuthenticated', $victim->id);
            } catch (MethodNotFoundException) {
                // expected: the hook is not callable from the browser
            }

            $snapshot = json_encode($component->snapshot, JSON_UNESCAPED_UNICODE);
            $this->assertStringNotContainsString('Secret street 7', $snapshot, $class);
            $this->assertStringNotContainsString('765 43 21', $snapshot, $class);
            $this->assertStringNotContainsString('Victim', $snapshot, $class);
        }
    }

    public function test_catalog_render_does_not_query_per_product_card(): void
    {
        for ($i = 1; $i <= 18; $i++) {
            $this->product('Product '.$i, 'SKU-'.$i, 10 + $i, 5);
        }
        $service = app(StorefrontService::class);
        $service->toggleFavorite('SKU-1');
        $service->getAllProducts(); // warm the catalogue cache

        DB::flushQueryLog();
        DB::enableQueryLog();
        Livewire::test(CatalogPage::class)->assertSee('Product 18');
        $queries = count(DB::getQueryLog());
        DB::disableQueryLog();

        $this->assertLessThanOrEqual(10, $queries, "Catalog render ran {$queries} queries");
    }

    public function test_catalog_page_size_is_limited_to_the_offered_options(): void
    {
        for ($i = 1; $i <= 20; $i++) {
            $this->product('Product '.$i, 'SKU-'.$i, 10 + $i, 1);
        }

        foreach ([1000, -5, 0] as $size) {
            $this->assertCount(18, Livewire::test(CatalogPage::class)->set('pageSize', $size)->viewData('paginatedProducts'));
        }
        $this->assertCount(20, Livewire::test(CatalogPage::class)->set('pageSize', 36)->viewData('paginatedProducts'));
    }

    public function test_quantity_requests_never_reveal_the_stock_or_exceed_the_order_limit(): void
    {
        $this->product(qty: 7);
        $big = $this->product('Big batch', 'ML-BIG', 5.0, 80);
        $service = app(StorefrontService::class);

        // Asking for far more than is in stock keeps the current quantity.
        $service->addToCart('ML-HG-BRUSH');
        $result = $service->updateCartQuantity('ML-HG-BRUSH', 999);
        $this->assertFalse($result['success']);
        $this->assertSame(1, $service->getItemQuantityInCart('ML-HG-BRUSH'));

        // The cart never allows more than one order line accepts.
        $service->addToCart('ML-BIG');
        $service->updateCartQuantity('ML-BIG', OrderService::MAX_QUANTITY_PER_LINE + 10);
        $this->assertSame(1, $service->getItemQuantityInCart('ML-BIG'));
        $service->updateCartQuantity('ML-BIG', OrderService::MAX_QUANTITY_PER_LINE);
        $this->assertSame(OrderService::MAX_QUANTITY_PER_LINE, $service->getItemQuantityInCart('ML-BIG'));
        $this->assertFalse($service->canIncrease('ML-BIG'));
        $this->assertTrue($service->validateAndSyncCart()['valid']);
        $this->assertSame((string) $big->id, $service->getCart()[1]['id']);
    }

    public function test_session_only_stores_bounded_client_input(): void
    {
        $this->product();
        $service = app(StorefrontService::class);

        $service->toggleFavorite(str_repeat('x', 5000));
        $this->assertSame([], $service->getFavoriteKeys());
        $this->assertTrue($service->toggleFavorite('ML-HG-BRUSH'));

        Livewire::test(CartPage::class)->set('orderNote', str_repeat('a', 5000));
        $this->assertSame(1000, mb_strlen(session('mally_order_note')));
    }

    public function test_currency_switch_only_changes_display(): void
    {
        $product = $this->product(price: 57.15);
        $service = app(StorefrontService::class);

        $this->assertSame('$57.15', $service->formatPrice(57.15));
        $service->setCurrency('UZS');
        $this->assertSame('697 205 сум', $service->formatPrice(57.15));
        $this->assertSame('57.15', $product->fresh()->selling_price);

        // Without a known rate no so'm price is invented.
        Cache::flush();
        Cache::put(ExchangeRateService::FETCH_LOCK_KEY, true, 300);
        $this->app->forgetInstance(ExchangeRateService::class);
        $this->app->forgetInstance(StorefrontService::class);
        $this->assertSame('—', app(StorefrontService::class)->formatPrice(57.15));
    }
}
