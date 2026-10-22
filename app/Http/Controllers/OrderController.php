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

        $name = trim($validated['customerName']);
        $quantities = [];
        foreach ($validated['items'] as $item) {
            $quantities[(int) $item['id']] = ($quantities[(int) $item['id']] ?? 0) + (int) $item['quantity'];
        }

        $parts = explode(' ', $name, 2);
        // createOrFirst: if a parallel order just created this phone, use that customer.
        $customer = Customer::wherePhone($phone)->first() ?? Customer::createOrFirst(['phone_number' => $phone], [
            'first_name' => $parts[0] ?: 'Покупатель',
            'last_name' => $parts[1] ?? '',
            'city' => $validated['city'] ?? null,
            'address' => $validated['address'] ?? null,
            'source' => 'telegram',
        ]);

        // The bot's phone is typed, not verified: an order is never attached
        // to a storefront account (it would show up in that account's
        // profile). It keeps the typed name, phone and address for delivery.
        if ($customer->user_id !== null) {
            $customer = null;
        }

        try {
            $order = $this->orders->placeOrder($customer, $quantities, [
                'customer_name' => $name,
                'customer_phone' => $phone,
                'city' => $validated['city'] ?? 'Ташкент',
                'address' => $validated['address'] ?? null,
                'comment' => $validated['comment'] ?? null,
                'promo_code' => $validated['promoCode'] ?? null,
            ]);
        } catch (CheckoutException $e) {
            return response()->json(['error' => $e->getMessage()], 422);
        }

        return response()->json([
            'success' => true,
            'orderNumber' => $order->order_number,
            'orderHash' => $order->secure_hash,
            'totalAmount' => (float) $order->amount,
            'currency' => 'USD',
        ], 201);
    }

    /**
     * Order status for its owner: needs both the order number and its secret hash.
     */
    public function track(Request $request): JsonResponse
    {
        $number = trim((string) $request->query('order', ''));
        $hash = trim((string) $request->query('hash', ''));

        $order = ctype_digit($number) && $hash !== ''
            ? Order::with('orderProducts.product')->withNumber((int) $number)->first()
            : null;

        if (! $order || empty($order->secure_hash) || ! hash_equals((string) $order->secure_hash, $hash)) {
            return response()->json(['error' => __('Order not found')], 404);
        }

        return response()->json([
            'success' => true,
            'order' => [
                'orderNumber' => $order->order_number,
                'status' => $order->status,
                'isPaid' => $order->isPaid(),
                'amountPaid' => (float) $order->amount_paid,
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
