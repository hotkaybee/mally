<?php

namespace App\Http\Controllers;

use App\Services\TelegramAuthService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Receives updates for the storefront login bot. Register it with
 * setWebhook(url=<APP_URL>/api/telegram/webhook, secret_token=TELEGRAM_AUTH_WEBHOOK_SECRET);
 * requests without that secret are rejected.
 */
class TelegramWebhookController extends Controller
{
    public function __invoke(Request $request, TelegramAuthService $telegram): JsonResponse
    {
        $secret = (string) config('services.telegram.auth_webhook_secret');
        $given = (string) $request->header('X-Telegram-Bot-Api-Secret-Token', '');

        if ($secret === '' || ! hash_equals($secret, $given)) {
            return response()->json(['ok' => false], 401);
        }

        $telegram->handleUpdate((array) $request->json()->all());

        // Always 200 for a valid call, so Telegram doesn't retry the update.
        return response()->json(['ok' => true]);
    }
}
