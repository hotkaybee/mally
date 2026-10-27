<?php

use App\Http\Controllers\TelegramWebhookController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Telegram login webhook
|--------------------------------------------------------------------------
*/

Route::post('/telegram/webhook', TelegramWebhookController::class)
    ->middleware('throttle:120,1');
