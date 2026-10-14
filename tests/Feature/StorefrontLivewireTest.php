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
use App\Models\Product;
use App\Services\ExchangeRateService;
use App\Services\StorefrontService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
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
