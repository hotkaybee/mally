<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use App\Models\Order;
use App\Models\OrderProduct;
use App\Models\Product;
use App\Models\ProductReceipt;
use App\Services\OrderNumberRetryService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class OrderController extends Controller
{
    public function __construct(
        private readonly OrderNumberRetryService $orderNumberRetry,
    ) {}

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
            'items' => 'required|array|min:1',
            'items.*.id' => 'required|integer|exists:products,id',
            'items.*.quantity' => 'required|integer|min:1',
        ]);

        $order = $this->orderNumberRetry->run(function (int $orderNumber) use ($validated): Order {
            $parts = explode(' ', trim($validated['customerName']), 2);
            $customer = Customer::firstOrCreate(
                ['phone_number' => trim($validated['customerPhone'])],
                [
                    'first_name' => $parts[0] ?: 'Покупатель',
                    'last_name' => $parts[1] ?? '',
                    'email' => 'tg_'.Str::random(6).'@mally.uz',
                    'source' => 'instagram',
                ]
            );

            $secureHash = bin2hex(random_bytes(16));

            $rawPromo = strtoupper(trim((string) ($validated['promoCode'] ?? '')));
            $validPromo = $rawPromo === 'MALLY10' ? 'MALLY10' : null;

            $order = Order::create([
                'customer_id' => $customer->id,
                'order_number' => $orderNumber,
                'secure_hash' => $secureHash,
                'customer_name' => trim($validated['customerName']),
                'customer_phone' => trim($validated['customerPhone']),
                'city' => $validated['city'] ?? 'Ташкент',
                'address' => $validated['address'] ?? '',
                'comment' => $validated['comment'] ?? null,
                'promo_code' => $validPromo,
                'discount_amount' => 0,
                'amount' => 0,
                'amount_paid' => 0,
                'status' => 'processing',
                'payment_method' => $validated['paymentMethod'] ?? 'card_fast',
                'payment_status' => 'PENDING',
            ]);

            $subtotalAmount = 0.0;

            foreach ($validated['items'] as $itemData) {
                $product = Product::lockForUpdate()->findOrFail($itemData['id']);
                $qty = (int) $itemData['quantity'];

                if ($product->status === 'out_of_stock' || (int) $product->quantity < $qty) {
                    abort(422, "Недостаточно товара «{$product->name}» на складе.");
                }

                $unitPrice = (float) $product->selling_price;
                $lineTotal = round($unitPrice * $qty, 2);

                $receipt = ProductReceipt::where('product_id', $product->id)
                    ->where('quantity_remaining', '>=', $qty)
                    ->orderBy('id', 'asc')
                    ->first();

                OrderProduct::create([
                    'order_id' => $order->id,
                    'product_id' => $product->id,
                    'product_receipt_id' => $receipt?->id,
                    'quantity' => $qty,
                    'price' => $unitPrice,
                    'unit_cost' => $receipt?->unit_cost ?? 0,
                    'total_price' => $lineTotal,
                ]);

                $subtotalAmount += $lineTotal;
            }

            $discountAmount = $validPromo === 'MALLY10' ? round($subtotalAmount * 0.10, 2) : 0.0;
            $totalAmount = max(0.0, round($subtotalAmount - $discountAmount, 2));

            $order->update([
                'discount_amount' => $discountAmount,
                'amount' => $totalAmount,
            ]);

            return $order;
        });

        return response()->json([
            'success' => true,
            'orderNumber' => $order->order_number,
            'orderHash' => $order->secure_hash,
            'totalAmount' => (float) $order->amount,
        ], 201);
    }
}
