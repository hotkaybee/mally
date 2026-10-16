<?php

namespace Tests\Feature;

use App\Livewire\Storefront\CartPage;
use App\Livewire\Storefront\CatalogPage;
use App\Livewire\Storefront\ContactPage;
use App\Livewire\Storefront\FavoritesPage;
use App\Livewire\Storefront\HomePage;
use App\Livewire\Storefront\ProductDetailPage;
use App\Livewire\Storefront\ProfilePage;
use App\Livewire\Storefront\ReturnsPage;
use App\Models\Brand;
use App\Models\Category;
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

    public function test_product_without_photo_uses_the_local_placeholder(): void
    {
        $data = app(StorefrontService::class)->formatProduct($this->product());

        $this->assertSame(Product::placeholderUrl(), $data['image']);
        $this->assertFileExists(public_path('images/product-placeholder.svg'));
        $this->get(route('product.detail', ['slug' => 'ML-HG-BRUSH']))
            ->assertOk()
            ->assertSee('images/product-placeholder.svg')
            ->assertDontSee('findy.uz');
    }

    public function test_description_keeps_line_breaks_and_stays_escaped(): void
    {
        $this->product()->update(['description' => "Line one\nLine two <b>bold</b>"]);

        $this->get(route('product.detail', ['slug' => 'ML-HG-BRUSH']))
            ->assertOk()
            ->assertSee('<p class="whitespace-pre-line">Line one'."\n".'Line two &lt;b&gt;bold&lt;/b&gt;</p>', false);
    }

    public function test_admin_comment_never_reaches_the_storefront(): void
    {
        $product = $this->product();
        $product->update(['description' => null, 'comment' => 'SECRET-SUPPLIER-NOTE']);

        $data = app(StorefrontService::class)->formatProduct($product->fresh(), true);

        $this->assertStringNotContainsString('SECRET-SUPPLIER-NOTE', json_encode($data, JSON_UNESCAPED_UNICODE));
        $this->assertNotSame('', $data['description']);

        $this->get(route('product.detail', ['slug' => 'ML-HG-BRUSH']))
            ->assertOk()
            ->assertDontSee('SECRET-SUPPLIER-NOTE');
    }
}
