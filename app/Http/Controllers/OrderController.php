<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use App\Models\Order;
use App\Models\OrderProduct;
use App\Models\Product;
use App\Models\ProductReceipt;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class OrderController extends Controller
{
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

        return DB::transaction(function () use ($validated) {
            $parts = explode(' ', trim($validated['customerName']), 2);
            $customer = Customer::firstOrCreate(
                ['phone_number' => trim($validated['customerPhone'])],
                [
                    'first_name' => $parts[0] ?: 'Покупатель',
                    'last_name' => $parts[1] ?? '',
                    'email' => 'tg_'.Str::random(6).'@mally.uz',
                    'source' => 'telegram',
                ]
            );

            $maxNum = (int) (Order::max('order_number') ?: 1000);
            $orderNumber = $maxNum + 1;
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
                    'unit_cost' => $receipt?->purchase_price ?? 0,
                    'total_price' => $lineTotal,
                ]);

                if (! $receipt) {
                    $product->decrement('quantity', $qty);
                }

                $subtotalAmount += $lineTotal;
            }

            $discountAmount = $validPromo === 'MALLY10' ? round($subtotalAmount * 0.10, 2) : 0.0;
            $totalAmount = max(0.0, round($subtotalAmount - $discountAmount, 2));

            $order->update([
                'discount_amount' => $discountAmount,
                'amount' => $totalAmount,
            ]);

            return response()->json([
                'success' => true,
                'orderNumber' => $order->order_number,
                'orderHash' => $order->secure_hash,
                'totalAmount' => $totalAmount,
            ], 201);
        });
    }

    public function track(Request $request): JsonResponse
    {
        $query = trim((string) $request->query('q', $request->query('order', '')));

        if ($query === '') {
            return response()->json(['error' => 'Укажите номер заказа'], 400);
        }

        $order = Order::with(['orderProducts.product'])
            ->where('order_number', $query)
            ->orWhere('id', is_numeric($query) ? (int) $query : 0)
            ->latest('id')
            ->first();

        if (! $order) {
            return response()->json(['error' => 'Заказ не найден'], 404);
        }

        return response()->json([
            'success' => true,
            'order' => [
                'orderNumber' => $order->order_number ?: $order->id,
                'status' => $order->status,
                'paymentStatus' => $order->payment_status,
                'totalAmount' => (float) $order->amount,
                'city' => $order->city,
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
