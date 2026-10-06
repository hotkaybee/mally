<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CategoryTranslationTest extends TestCase
{
    use RefreshDatabase;

    private function makeCategory(array $overrides = []): Category
    {
        return Category::create(array_merge([
            'name_en' => 'Lipstick',
            'name_ru' => 'Помада',
            'name_uz' => "Lab bo'yog'i",
        ], $overrides));
    }

    public function test_name_follows_the_active_locale(): void
    {
        $category = $this->makeCategory();

        foreach (['en' => 'Lipstick', 'ru' => 'Помада', 'uz' => "Lab bo'yog'i"] as $locale => $expected) {
            app()->setLocale($locale);

            $this->assertSame($expected, $category->fresh()->name);
        }
    }

    public function test_name_falls_back_when_the_active_locale_is_untranslated(): void
    {
        $category = $this->makeCategory(['name_uz' => null]);

        app()->setLocale('uz');

        $this->assertSame('Lipstick', $category->fresh()->name);
    }

    public function test_name_is_blank_when_nothing_is_translated(): void
    {
        $category = $this->makeCategory(['name_en' => '', 'name_ru' => null, 'name_uz' => null]);

        app()->setLocale('ru');

        $this->assertSame('', $category->fresh()->name);
    }

    public function test_name_column_maps_the_locale_to_a_real_column(): void
    {
        app()->setLocale('ru');
        $this->assertSame('name_ru', Category::nameColumn());

        app()->setLocale('uz');
        $this->assertSame('name_uz', Category::nameColumn());

        // An unsupported locale must still resolve to a column that exists.
        app()->setLocale('de');
        $this->assertSame('name_en', Category::nameColumn());

        $this->assertSame(['name_en', 'name_ru', 'name_uz'], Category::nameColumns());
    }

    public function test_categories_can_be_ordered_by_the_active_locale_in_sql(): void
    {
        $this->makeCategory(['name_en' => 'Blush', 'name_ru' => 'Яблоко', 'name_uz' => 'Olma']);
        $this->makeCategory(['name_en' => 'Zest', 'name_ru' => 'Абрикос', 'name_uz' => 'O\'rik']);

        app()->setLocale('ru');

        // Ordering must use the Russian column, not the English one.
        $first = Category::orderBy(Category::nameColumn())->first();

        $this->assertSame('Абрикос', $first->name_ru);
    }

    public function test_the_seeded_catalogue_is_fully_translated(): void
    {
        $this->seed(\Database\Seeders\CategorySeeder::class);

        $this->assertSame(27, Category::count());
        $this->assertSame(0, Category::whereNull('name_ru')->orWhereNull('name_uz')->count());
    }

    /**
     * End-to-end: the product list renders the category in the active locale,
     * which is the whole point of the change.
     */
    public function test_product_list_shows_the_category_in_the_active_locale(): void
    {
        config(['app.env' => 'local']);
        $this->actingAs(User::factory()->create());

        $category = $this->makeCategory();
        Product::create([
            'category_id' => $category->id,
            'name' => 'Test product',
            'quantity' => 0,
            'selling_price' => 0,
            'status' => 'in_stock',
        ]);

        app()->setLocale('ru');
        $this->get('/admin/products')->assertOk()->assertSee('Помада');

        app()->setLocale('uz');
        $this->get('/admin/products')->assertOk()->assertSee("Lab bo'yog'i");
    }
}
