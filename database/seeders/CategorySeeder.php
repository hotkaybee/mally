<?php

namespace Database\Seeders;

use App\Models\Category;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class CategorySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        DB::table('categories')->delete();

        $categories = [
            ['name' => 'Lipstick', 'icon' => 'heroicon-o-swatch'],
            ['name' => 'Lip Pencil', 'icon' => 'heroicon-o-pencil'],
            ['name' => 'Lip Gloss', 'icon' => 'heroicon-o-sparkles'],
            ['name' => 'Foundation', 'icon' => 'heroicon-o-beaker'],
            ['name' => 'Concealer', 'icon' => 'heroicon-o-eye-dropper'],
            ['name' => 'Powder', 'icon' => 'heroicon-o-inbox'],
            ['name' => 'Blush', 'icon' => 'heroicon-o-heart'],
            ['name' => 'Bronzer', 'icon' => 'heroicon-o-sun'],
            ['name' => 'Highlighter', 'icon' => 'heroicon-o-sparkles'],
            ['name' => 'Eyeshadow', 'icon' => 'heroicon-o-eye'],
            ['name' => 'Eyeliner', 'icon' => 'heroicon-o-pencil-square'],
            ['name' => 'Wand', 'icon' => 'heroicon-o-sparkles'],
            ['name' => 'Mascara', 'icon' => 'heroicon-o-eye'],
            ['name' => 'Eyebrow Pencil', 'icon' => 'heroicon-o-pencil'],
            ['name' => 'Serum', 'icon' => 'heroicon-o-beaker'],
            ['name' => 'Moisturizer', 'icon' => 'heroicon-o-cloud'],
            ['name' => 'Cleanser', 'icon' => 'heroicon-o-beaker'],
            ['name' => 'Toner', 'icon' => 'heroicon-o-beaker'],
            ['name' => 'Face Mask', 'icon' => 'heroicon-o-face-smile'],
            ['name' => 'Sunscreen', 'icon' => 'heroicon-o-sun'],
            ['name' => 'Perfume', 'icon' => 'heroicon-o-sparkles'],
            ['name' => 'Body Lotion', 'icon' => 'heroicon-o-user'],
            ['name' => 'Body Wash', 'icon' => 'heroicon-o-user'],
            ['name' => 'Shampoo', 'icon' => 'heroicon-o-beaker'],
            ['name' => 'Conditioner', 'icon' => 'heroicon-o-beaker'],
            ['name' => 'Hair Mask', 'icon' => 'heroicon-o-beaker'],
            ['name' => 'Hair Oil', 'icon' => 'heroicon-o-beaker'],
        ];

        foreach ($categories as $category) {
            Category::create($category);
        }
    }
}
