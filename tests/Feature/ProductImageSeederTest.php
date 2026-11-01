<?php

namespace Tests\Feature;

use App\Models\Brand;
use App\Models\Product;
use Database\Seeders\BrandSeeder;
use Database\Seeders\CategorySeeder;
use Database\Seeders\ProductImageSeeder;
use Database\Seeders\ProductSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ProductImageSeederTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutVite();
        Storage::fake(Product::IMAGE_DISK);
        $this->seed([BrandSeeder::class, CategorySeeder::class, ProductSeeder::class]);
    }

    private function seeded(string $name, string $brand): Product
    {
        return Product::where('name', $name)->where('brand_id', Brand::where('name', $brand)->value('id'))->sole();
    }

    public function test_every_bundled_photo_exists_and_belongs_to_a_seeded_product(): void
    {
        $keys = Product::with('brand')->get()
            ->map(fn (Product $p) => ProductImageSeeder::key((string) $p->brand?->name, $p->name))
            ->all();

        $this->assertNotEmpty(ProductImageSeeder::images());
        foreach (ProductImageSeeder::images() as $key => $file) {
            $this->assertContains($key, $keys, "No seeded product for {$key}");
            $this->assertFileExists(database_path('seeders/images/products/'.$file));
        }
    }

    public function test_photos_are_copied_to_the_public_disk_once_and_only_for_products_without_photos(): void
    {
        $withPhoto = $this->seeded('mascara', 'Sephora Collection');
        $withPhoto->update(['images' => ['products/own.jpg']]);

        $this->seed(ProductImageSeeder::class);
        $this->seed(ProductImageSeeder::class);

        $product = $this->seeded('concelear double wear', 'Estée Lauder');
        $this->assertCount(1, $product->images);
        Storage::disk(Product::IMAGE_DISK)->assertExists($product->images[0]);
        $this->assertStringStartsWith(Product::IMAGE_DIRECTORY.'/', $product->images[0]);

        $this->assertSame(['products/own.jpg'], $withPhoto->fresh()->images);
        $this->assertSame(
            Product::whereNotNull('images')->count(),
            count(Storage::disk(Product::IMAGE_DISK)->files(Product::IMAGE_DIRECTORY)) + 1,
        );
    }
}
