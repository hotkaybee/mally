<?php

namespace Database\Seeders;

use App\Models\Brand;
use App\Models\Category;
use App\Models\Product;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class ProductSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        DB::table('products')->delete();

        // Get Victoria's Secret brand ID
        $victoriasSecretBrand = Brand::where('name', 'Victoria\'s Secret')->first();

        if (!$victoriasSecretBrand) {
            $this->command->error('Victoria\'s Secret brand not found. Please run BrandSeeder first.');
            return;
        }

        // Get a default category (we'll use the first available category)
        $defaultCategory = Category::first();

        if (!$defaultCategory) {
            $this->command->error('No categories found. Please run CategorySeeder first.');
            return;
        }

        $products = [
            'Blue shorts pyjama',
            'Bombshell amalfi lotion',
            'Very sexy orchid 50 ml',
            'Coco passion shimmer butter',
            'Supper berdy scrub',
            'Dreamy petals',
            'Very sexy sea 50 ml',
            'Bombshell midnight 100 ml',
            'Bombshell escape lotion',
            'Bombshell midnight lotion',
            'Bombshell midnight mist',
            'Bombshell mist',
            'Bombshell lotion',
            'Daring mist',
            'Daring lotion',
            'Very sexy night lotion',
            'Very sexy oasis lotion',
            'Coconut lotion',
            'Mango lotion',
            'Cocumber body wash',
            'Coco scrub wash',
            'Love spell lotion',
            'Lush lotus lotion',
            'Cocumber lotion',
            'Lavander lotion',
            'Pear glace lotion',
            'Velvet petals daydream lotion',
            'Pure seduction daydream lotion',
            'Velvet petals lotion',
            'Pure seduction lotion',
            'Lotus lotion',
            'Almond blossom & milk lotion',
            'Love spell mist',
            'Aquatic allure mist',
        ];

        foreach ($products as $productName) {
            Product::create([
                'brand_id' => $victoriasSecretBrand->id,
                'category_id' => $defaultCategory->id,
                'name' => $productName,
                'description' => null,
                'comment' => null,
                'quantity' => 1,
                'price_original' => 0.00,
                'markup' => 0,
                'selling_price' => 0.00,
                'website_price' => 0.00,
                'image' => null,
                'link' => null,
                'status' => 'in_stock',
            ]);
        }

        $this->command->info('Created ' . count($products) . ' Victoria\'s Secret products.');
    }
}
