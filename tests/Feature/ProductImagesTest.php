<?php

namespace Tests\Feature;

use App\Filament\Resources\Products\Pages\EditProduct;
use App\Filament\Resources\Products\Pages\ViewProduct;
use App\Models\Brand;
use App\Models\Category;
use App\Models\Product;
use App\Models\User;
use App\Services\ExchangeRateService;
use App\Services\StorefrontService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Tests\TestCase;

class ProductImagesTest extends TestCase
{
    use RefreshDatabase;

    private const MIGRATION = 'database/migrations/2026_10_27_000000_replace_product_image_with_images.php';

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
            'sku' => 'ML-HG-BRUSH',
            'quantity' => 0,
            'selling_price' => 0,
            'status' => 'in_stock',
        ], $attrs));
    }

    public function test_admin_uploads_several_photos_in_order_to_the_public_disk(): void
    {
        config(['app.env' => 'local']);
        $this->actingAs(User::factory()->create());

        $product = $this->product();

        Livewire::test(EditProduct::class, ['record' => $product->getRouteKey()])
            ->fillForm([
                'images' => [UploadedFile::fake()->image('front.jpg', 1200, 1200), UploadedFile::fake()->image('back.png', 1200, 1200)],
            ])
            ->call('save')
            ->assertHasNoFormErrors();

        $images = $product->fresh()->images;
        $this->assertCount(2, $images);
        foreach ($images as $path) {
            $this->assertStringStartsWith(Product::IMAGE_DIRECTORY.'/', $path);
            Storage::disk(Product::IMAGE_DISK)->assertExists($path);
        }

        $page = $this->get(route('product.detail', ['slug' => 'ML-HG-BRUSH']))->assertOk();
        foreach ($images as $path) {
            $page->assertSee(Storage::disk(Product::IMAGE_DISK)->url($path), false);
        }

        Livewire::test(ViewProduct::class, ['record' => $product->getRouteKey()])->assertOk();
    }

    public function test_photos_must_be_jpeg_png_or_webp(): void
    {
        config(['app.env' => 'local']);
        $this->actingAs(User::factory()->create());

        $product = $this->product();

        Livewire::test(EditProduct::class, ['record' => $product->getRouteKey()])
            ->fillForm(['images' => [UploadedFile::fake()->create('logo.svg', 10, 'image/svg+xml')]])
            ->call('save')
            ->assertHasFormErrors(['images']);

        $this->assertNull($product->fresh()->images);
    }

    public function test_product_page_shows_all_photos_with_the_first_as_main(): void
    {
        $product = $this->product(['images' => ['products/front.webp', 'products/back.webp']]);
        $front = Storage::disk(Product::IMAGE_DISK)->url('products/front.webp');
        $back = Storage::disk(Product::IMAGE_DISK)->url('products/back.webp');

        $data = app(StorefrontService::class)->formatProduct($product);
        $this->assertSame($front, $data['image']);
        $this->assertSame([$front, $back], $data['images']);
        $this->assertSame($front, $product->mainImageUrl());

        $this->get(route('product.detail', ['slug' => 'ML-HG-BRUSH']))
            ->assertOk()
            ->assertSee('products/front.webp')
            ->assertSee('products/back.webp');
    }

    public function test_product_without_photos_uses_the_placeholder(): void
    {
        $product = $this->product();

        $this->assertSame([], $product->imageUrls());
        $this->assertSame(Product::placeholderUrl(), $product->mainImageUrl());
        $this->assertSame([Product::placeholderUrl()], app(StorefrontService::class)->formatProduct($product)['images']);
    }

    public function test_full_urls_are_kept_as_they_are(): void
    {
        $product = $this->product(['images' => ['https://cdn.example.com/a.jpg']]);

        $this->assertSame(['https://cdn.example.com/a.jpg'], $product->imageUrls());
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

    public function test_migration_moves_the_single_image_into_the_list(): void
    {
        $this->artisan('migrate:rollback', ['--path' => self::MIGRATION])->assertSuccessful();

        $insert = fn (?string $image) => DB::table('products')->insertGetId([
            'name' => 'P', 'quantity' => 0, 'selling_price' => 0, 'status' => 'in_stock', 'image' => $image,
            'created_at' => now(), 'updated_at' => now(),
        ]);
        $with = $insert('products/old.jpg');
        $without = $insert(null);

        $this->artisan('migrate', ['--path' => self::MIGRATION])->assertSuccessful();

        $this->assertSame(['products/old.jpg'], Product::find($with)->images);
        $this->assertNull(Product::find($without)->images);
    }
}
