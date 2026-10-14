<?php

namespace App\Http\Controllers;

use App\Exceptions\CheckoutException;
use App\Models\Customer;
use App\Models\Order;
use App\Models\OrderProduct;
use App\Services\OrderService;
use App\Services\StorefrontService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;
use Throwable;

class OrderController extends Controller
{
    public function __construct(
        private readonly OrderService $orders,
        private readonly StorefrontService $storefront,
    ) {}

    /**
     * Create an order from the Telegram bot. Protected by VerifyOrdersApiToken
     * and rate-limited in routes/api.php. Prices come from the database (USD).
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'customerName' => 'required|string|max:255',
            'customerPhone' => 'required|string|max:50',
            'city' => 'nullable|string|max:100',
            'address' => 'nullable|string|max:500',
            'paymentMethod' => 'nullable|string|max:50',
            'comment' => 'nullable|string|max:1000',
            'promoCode' => 'nullable|string|max:50',
            'items' => 'required|array|min:1|max:50',
            'items.*.id' => 'required|integer',
            'items.*.quantity' => 'required|integer|min:1|max:'.OrderService::MAX_QUANTITY_PER_LINE,
        ]);

        $phone = $this->storefront->normalizeAndValidateUzPhone($validated['customerPhone']);
        if (! $phone) {
            return response()->json(['error' => __('Please enter a valid phone number (+998 followed by 9 digits)')], 422);
        }

        // Optional Idempotency-Key: a retried request (e.g. after a timeout)
        // returns the order created by the first one instead of a new order.
        $idempotencyKey = trim((string) $request->header('Idempotency-Key', ''));
        if (strlen($idempotencyKey) > 100) {
            return response()->json(['error' => 'Idempotency-Key is too long.'], 422);
        }
        $idempotencyCacheKey = $idempotencyKey !== '' ? 'api-order-idempotency:'.hash('sha256', $idempotencyKey) : null;

        $name = trim($validated['customerName']);
        $quantities = [];
        foreach ($validated['items'] as $item) {
            $quantities[(int) $item['id']] = ($quantities[(int) $item['id']] ?? 0) + (int) $item['quantity'];
        }

        // The bot's phone is typed, not verified: reuse only a CRM/bot customer
        // row, never one owned by a storefront account (its orders would show
        // up in that account's profile).
        $customer = Customer::wherePhone($phone)->whereNull('user_id')->first();
        if (! $customer) {
            $parts = explode(' ', $name, 2);
            $customer = Customer::create([
                'first_name' => $parts[0] ?: 'Покупатель',
                'last_name' => $parts[1] ?? '',
                'phone_number' => $phone,
                'city' => $validated['city'] ?? null,
                'address' => $validated['address'] ?? null,
                'email' => 'tg_'.Str::lower(Str::random(12)).'@mally.uz',
                'source' => 'telegram',
            ]);
        }

        // Claimed right before placing the order, so every failure below
        // releases it. The stored value is 'pending' or the order id.
        if ($idempotencyCacheKey && ! Cache::add($idempotencyCacheKey, 'pending', now()->addDay())) {
            $orderId = Cache::get($idempotencyCacheKey);
            $existing = is_numeric($orderId) ? Order::find((int) $orderId) : null;

            return $existing
                ? $this->orderCreatedResponse($existing, 200)
                : response()->json(['error' => __('This order is already being processed.')], 409);
        }

        try {
            $order = $this->orders->placeOrder($customer, $quantities, [
                'customer_name' => $name,
                'customer_phone' => $phone,
                'city' => $validated['city'] ?? 'Ташкент',
                'address' => $validated['address'] ?? null,
                'comment' => $validated['comment'] ?? null,
                'promo_code' => $validated['promoCode'] ?? null,
                'payment_method' => $validated['paymentMethod'] ?? 'card_fast',
            ]);
        } catch (CheckoutException $e) {
            $idempotencyCacheKey && Cache::forget($idempotencyCacheKey);

            return response()->json(['error' => $e->getMessage()], 422);
        } catch (Throwable $e) {
            $idempotencyCacheKey && Cache::forget($idempotencyCacheKey);

            throw $e;
        }

        if ($idempotencyCacheKey) {
            Cache::put($idempotencyCacheKey, $order->id, now()->addDay());
        }

        return $this->orderCreatedResponse($order, 201);
    }

    private function orderCreatedResponse(Order $order, int $status): JsonResponse
    {
        return response()->json([
            'success' => true,
            'orderNumber' => $order->order_number,
            'orderHash' => $order->secure_hash,
            'totalAmount' => (float) $order->amount,
            'currency' => 'USD',
        ], $status);
    }

    /**
     * Order status for its owner: needs both the order number and its secret hash.
     */
    public function track(Request $request): JsonResponse
    {
        $number = trim((string) $request->query('order', ''));
        $hash = trim((string) $request->query('hash', ''));

        $order = ctype_digit($number) && $hash !== ''
            ? Order::with('orderProducts.product')->where('order_number', (int) $number)->first()
            : null;

        if (! $order || empty($order->secure_hash) || ! hash_equals((string) $order->secure_hash, $hash)) {
            return response()->json(['error' => __('Order not found')], 404);
        }

        return response()->json([
            'success' => true,
            'order' => [
                'orderNumber' => $order->order_number,
                'status' => $order->status,
                'paymentStatus' => $order->payment_status,
                'totalAmount' => (float) $order->amount,
                'currency' => 'USD',
                'createdAt' => $order->created_at?->toIso8601String(),
                'items' => $order->orderProducts->map(fn (OrderProduct $op) => [
                    'productId' => $op->product_id,
                    'name' => $op->product?->name,
                    'quantity' => $op->quantity,
                    'price' => (float) $op->price,
                ]),
            ],
        ]);
    }
}
