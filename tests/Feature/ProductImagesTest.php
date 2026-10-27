<?php

namespace Tests\Feature;

use App\Filament\Resources\Products\Pages\EditProduct;
use App\Filament\Resources\Products\Pages\ViewProduct;
use App\Models\Brand;
use App\Models\Category;
use App\Models\Product;
use App\Models\User;
use App\Services\ExchangeRateService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Tests\TestCase;

class ProductImagesTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutVite();
        Http::fake();
        Storage::fake(Product::IMAGE_DISK);
        Cache::put(ExchangeRateService::CACHE_KEY, [
            'rate' => 12199.57,
            'date' => '11.10.2026',
            'source' => 'cbu.uz',
            'isLive' => true,
            'updatedAt' => now()->toIso8601String(),
        ], 3600);
    }

    private function product(array $attrs = []): Product
    {
        return Product::create(array_merge([
            'brand_id' => Brand::firstOrCreate(['name' => 'Hourglass'])->id,
            'category_id' => Category::firstOrCreate(['name_ru' => 'Макияж'], ['name_en' => 'Makeup', 'name_uz' => 'Makiyaj'])->id,
            'name' => 'Ambient Lighting Brush',
            'sku' => 'ML-IMG-1',
            'quantity' => 0,
            'selling_price' => 0,
            'status' => 'in_stock',
        ], $attrs));
    }

    private function productPage(Product $product)
    {
        return $this->get(route('product.detail', ['slug' => $product->sku]))->assertOk();
    }

    public function test_photos_uploaded_in_the_admin_are_stored_on_the_public_disk_and_shown_on_the_product_page(): void
    {
        $product = $this->product();
        $this->actingAs(User::factory()->create());

        Livewire::test(EditProduct::class, ['record' => $product->getRouteKey()])
            ->fillForm(['images' => [
                UploadedFile::fake()->image('front.jpg', 1200, 1200),
                UploadedFile::fake()->image('back.png', 1200, 1200),
            ]])
            ->call('save')
            ->assertHasNoFormErrors();

        $paths = $product->fresh()->images;
        $this->assertCount(2, $paths);
        foreach ($paths as $path) {
            $this->assertStringStartsWith(Product::IMAGE_DIRECTORY.'/', $path);
            Storage::disk(Product::IMAGE_DISK)->assertExists($path);
        }

        $page = $this->productPage($product);
        foreach ($paths as $path) {
            $page->assertSee(Storage::disk(Product::IMAGE_DISK)->url($path), false);
        }

        Livewire::test(ViewProduct::class, ['record' => $product->getRouteKey()])->assertOk();
    }

    public function test_photos_must_be_square_jpeg_png_or_webp(): void
    {
        $product = $this->product();
        $this->actingAs(User::factory()->create());

        foreach ([UploadedFile::fake()->image('wide.jpg', 1200, 800), UploadedFile::fake()->create('logo.svg', 10, 'image/svg+xml')] as $file) {
            Livewire::test(EditProduct::class, ['record' => $product->getRouteKey()])
                ->fillForm(['images' => [$file]])
                ->call('save')
                ->assertHasFormErrors(['images']);
        }

        $this->assertNull($product->fresh()->images);
    }

    public function test_main_photo_is_first_and_a_product_without_photos_gets_the_placeholder(): void
    {
        $product = $this->product(['images' => ['products/front.jpg', 'products/back.jpg']]);

        $this->assertSame(Storage::disk(Product::IMAGE_DISK)->url('products/front.jpg'), $product->mainImageUrl());
        $this->assertCount(2, $product->imageUrls());
        $this->assertSame(Product::placeholderUrl(), $this->product(['sku' => 'ML-IMG-2', 'images' => null])->mainImageUrl());
    }

    public function test_removed_photos_and_photos_of_a_deleted_product_are_deleted_from_the_disk(): void
    {
        $disk = Storage::disk(Product::IMAGE_DISK);
        $disk->put('products/front.jpg', 'front');
        $disk->put('products/back.jpg', 'back');
        $product = $this->product(['images' => ['products/front.jpg', 'products/back.jpg']]);

        $product->update(['images' => ['products/back.jpg']]);
        $disk->assertMissing('products/front.jpg');
        $disk->assertExists('products/back.jpg');

        $product->delete();
        $disk->assertMissing('products/back.jpg');
    }
}
