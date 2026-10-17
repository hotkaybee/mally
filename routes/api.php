<?php

use App\Http\Controllers\CategoryController;
use App\Http\Controllers\OrderController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\TelegramWebhookController;
use App\Http\Middleware\VerifyOrdersApiToken;
use App\Services\ExchangeRateService;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| External & Telegram Bot API Routes
|--------------------------------------------------------------------------
*/

Route::middleware('throttle:60,1')->group(function () {
    Route::get('/exchange-rate', function (ExchangeRateService $rateService) {
        $data = $rateService->getUsdRate();

        return response()->json($data, ! empty($data['rate']) ? 200 : 503);
    });

    Route::get('/categories', [CategoryController::class, 'index']);
    Route::get('/products', [ProductController::class, 'index']);
    Route::get('/products/{slug}', [ProductController::class, 'show']);
});

Route::post('/orders', [OrderController::class, 'store'])
    ->middleware([VerifyOrdersApiToken::class, 'throttle:10,1']);

Route::get('/orders/track', [OrderController::class, 'track'])
    ->middleware('throttle:20,1');

Route::post('/telegram/webhook', TelegramWebhookController::class)
    ->middleware('throttle:120,1');
