<?php

namespace App\Http\Controllers;

use App\Models\Category;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CategoryController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $lang = $request->query('lang', $request->header('Accept-Language', 'ru'));
        if (in_array($lang, ['ru', 'uz', 'en'], true)) {
            app()->setLocale($lang);
        }

        $categories = Category::query()
            ->orderBy('id')
            ->get()
            ->map(fn (Category $c) => [
                'id' => $c->id,
                'name' => $c->name,
                'nameRu' => $c->name_ru,
                'nameUz' => $c->name_uz,
                'nameEn' => $c->name_en,
                'icon' => $c->icon,
            ]);

        return response()->json(['categories' => $categories]);
    }
}
