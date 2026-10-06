<?php

namespace Database\Seeders;

use App\Models\Category;
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
            ['name_en' => 'Lipstick', 'name_ru' => 'Помада', 'name_uz' => 'Lab bo\'yog\'i', 'icon' => 'heroicon-o-swatch'],
            ['name_en' => 'Lip Pencil', 'name_ru' => 'Карандаш для губ', 'name_uz' => 'Lab qalami', 'icon' => 'heroicon-o-pencil'],
            ['name_en' => 'Lip Gloss', 'name_ru' => 'Блеск для губ', 'name_uz' => 'Lab yaltiratgichi', 'icon' => 'heroicon-o-sparkles'],
            ['name_en' => 'Foundation', 'name_ru' => 'Тональный крем', 'name_uz' => 'Tonal krem', 'icon' => 'heroicon-o-beaker'],
            ['name_en' => 'Concealer', 'name_ru' => 'Консилер', 'name_uz' => 'Konsiler', 'icon' => 'heroicon-o-eye-dropper'],
            ['name_en' => 'Powder', 'name_ru' => 'Пудра', 'name_uz' => 'Pudra', 'icon' => 'heroicon-o-inbox'],
            ['name_en' => 'Blush', 'name_ru' => 'Румяна', 'name_uz' => 'Yuz qizartirgich', 'icon' => 'heroicon-o-heart'],
            ['name_en' => 'Bronzer', 'name_ru' => 'Бронзер', 'name_uz' => 'Bronzer', 'icon' => 'heroicon-o-sun'],
            ['name_en' => 'Highlighter', 'name_ru' => 'Хайлайтер', 'name_uz' => 'Xaylayter', 'icon' => 'heroicon-o-sparkles'],
            ['name_en' => 'Eyeshadow', 'name_ru' => 'Тени для век', 'name_uz' => 'Ko\'z soyasi', 'icon' => 'heroicon-o-eye'],
            ['name_en' => 'Eyeliner', 'name_ru' => 'Подводка для глаз', 'name_uz' => 'Ko\'z chizg\'ichi', 'icon' => 'heroicon-o-pencil-square'],
            ['name_en' => 'Wand', 'name_ru' => 'Кисть-аппликатор', 'name_uz' => 'Applikator', 'icon' => 'heroicon-o-sparkles'],
            ['name_en' => 'Mascara', 'name_ru' => 'Тушь для ресниц', 'name_uz' => 'Tush', 'icon' => 'heroicon-o-eye'],
            ['name_en' => 'Eyebrow Pencil', 'name_ru' => 'Карандаш для бровей', 'name_uz' => 'Qosh qalami', 'icon' => 'heroicon-o-pencil'],
            ['name_en' => 'Serum', 'name_ru' => 'Сыворотка', 'name_uz' => 'Zardob', 'icon' => 'heroicon-o-beaker'],
            ['name_en' => 'Moisturizer', 'name_ru' => 'Увлажняющий крем', 'name_uz' => 'Namlantiruvchi krem', 'icon' => 'heroicon-o-cloud'],
            ['name_en' => 'Cleanser', 'name_ru' => 'Очищающее средство', 'name_uz' => 'Tozalovchi vosita', 'icon' => 'heroicon-o-beaker'],
            ['name_en' => 'Toner', 'name_ru' => 'Тоник', 'name_uz' => 'Tonik', 'icon' => 'heroicon-o-beaker'],
            ['name_en' => 'Face Mask', 'name_ru' => 'Маска для лица', 'name_uz' => 'Yuz niqobi', 'icon' => 'heroicon-o-face-smile'],
            ['name_en' => 'Sunscreen', 'name_ru' => 'Солнцезащитный крем', 'name_uz' => 'Quyoshdan himoya krem', 'icon' => 'heroicon-o-sun'],
            ['name_en' => 'Perfume', 'name_ru' => 'Парфюм', 'name_uz' => 'Parfyum', 'icon' => 'heroicon-o-sparkles'],
            ['name_en' => 'Body Lotion', 'name_ru' => 'Лосьон для тела', 'name_uz' => 'Tana losyoni', 'icon' => 'heroicon-o-user'],
            ['name_en' => 'Body Wash', 'name_ru' => 'Гель для душа', 'name_uz' => 'Dush geli', 'icon' => 'heroicon-o-user'],
            ['name_en' => 'Shampoo', 'name_ru' => 'Шампунь', 'name_uz' => 'Shampun', 'icon' => 'heroicon-o-beaker'],
            ['name_en' => 'Conditioner', 'name_ru' => 'Кондиционер для волос', 'name_uz' => 'Soch konditsioneri', 'icon' => 'heroicon-o-beaker'],
            ['name_en' => 'Hair Mask', 'name_ru' => 'Маска для волос', 'name_uz' => 'Soch niqobi', 'icon' => 'heroicon-o-beaker'],
            ['name_en' => 'Hair Oil', 'name_ru' => 'Масло для волос', 'name_uz' => 'Soch moyi', 'icon' => 'heroicon-o-beaker'],
        ];

        foreach ($categories as $category) {
            Category::create($category);
        }
    }
}
