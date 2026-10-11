<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Services\StorefrontService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ProductController extends Controller
{
    public function __construct(
        protected StorefrontService $storefrontService
    ) {}

    public function index(Request $request): JsonResponse
    {
        $query = Product::with(['brand', 'category']);

        if ($search = trim((string) $request->query('q', ''))) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('sku', 'like', "%{$search}%");
            });
        }

        if ($categoryId = $request->query('category_id')) {
            $query->where('category_id', $categoryId);
        }

        $products = $query->get()->map(
            fn (Product $product) => $this->storefrontService->formatProduct($product)
        );

        return response()->json(['products' => $products]);
    }

    public function show(string $slug): JsonResponse
    {
        $product = $this->storefrontService->findProductBySlugOrId($slug);

        if (! $product) {
            return response()->json(['error' => 'Товар не найден'], 404);
        }

        return response()->json([
            'product' => $this->storefrontService->formatProduct($product),
        ]);
    }
}
