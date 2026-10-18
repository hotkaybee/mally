<?php

namespace Tests\Feature;

use App\Models\Brand;
use App\Models\Product;
use Database\Seeders\BrandSeeder;
use Database\Seeders\CategorySeeder;
use Database\Seeders\ProductSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProductSeederCategoryTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutVite();
        $this->seed([BrandSeeder::class, CategorySeeder::class, ProductSeeder::class]);
    }

    private function categoryOf(string $name, string $brand): ?string
    {
        $product = Product::with('category')
            ->where('name', $name)
            ->where('brand_id', Brand::where('name', $brand)->value('id'))
            ->sole();

        return $product->category?->name_en;
    }

    public function test_products_get_the_category_their_name_matches(): void
    {
        $this->assertSame('Mascara', $this->categoryOf('mascara', 'Sephora Collection'));
        $this->assertSame('Perfume', $this->categoryOf('bomshell 7ml', "Victoria's Secret"));
        $this->assertSame('Body Lotion', $this->categoryOf('bomshell lotion', "Victoria's Secret"));
        $this->assertSame('Moisturizer', $this->categoryOf('moisturise surge 30 ml', 'Clinique'));
        $this->assertSame('Eyeshadow', $this->categoryOf('eyes to mesmerise', 'Charlotte Tilbury'));
        $this->assertSame('Foundation', $this->categoryOf('skin glow forever', 'Dior'));
    }

    public function test_exceptions_win_over_the_name_rules(): void
    {
        $this->assertNull($this->categoryOf('mist', 'Rare Beauty'));
        $this->assertNull($this->categoryOf('primer spf', 'Charlotte Tilbury'));
        $this->assertNull($this->categoryOf('Skin glow', 'Shiseido'));
        $this->assertSame('Blush', $this->categoryOf('face pallete', 'Natasha Denona'));
    }

    public function test_unmatched_products_get_no_category_instead_of_lipstick(): void
    {
        $this->assertTrue(Product::whereNull('category_id')->exists());

        $lipstickNames = Product::whereHas('category', fn ($query) => $query->where('name_en', 'Lipstick'))
            ->pluck('name');

        foreach ($lipstickNames as $name) {
            $this->assertMatchesRegularExpression('/pomada|lipstick/i', $name);
        }
    }
}
