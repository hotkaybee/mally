<?php

use App\Http\Controllers\CategoryController;
use App\Http\Controllers\OrderController;
use App\Http\Controllers\ProductController;
use App\Services\ExchangeRateService;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| External & Telegram Bot API Routes
|--------------------------------------------------------------------------
*/

Route::get('/exchange-rate', function (ExchangeRateService $rateService) {
    $data = $rateService->getUsdRate();

    return response()->json($data, ! empty($data['rate']) ? 200 : 503);
});

Route::get('/categories', [CategoryController::class, 'index']);
Route::get('/products', [ProductController::class, 'index']);
Route::get('/products/{slug}', [ProductController::class, 'show']);

Route::post('/orders', [OrderController::class, 'store']);
