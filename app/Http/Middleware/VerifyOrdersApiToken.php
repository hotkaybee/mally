<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Only the store's Telegram bot may create orders through the API. It sends
 * the shared secret from ORDERS_API_TOKEN in the X-Api-Token header. With
 * no token configured the endpoint stays closed.
 */
class VerifyOrdersApiToken
{
    public function handle(Request $request, Closure $next): Response
    {
        $expected = (string) config('services.store.orders_api_token');
        $given = (string) $request->header('X-Api-Token', '');

        if ($expected === '' || ! hash_equals($expected, $given)) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }

        return $next($request);
    }
}
