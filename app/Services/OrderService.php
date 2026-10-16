<?php

namespace App\Services;

use App\Exceptions\CheckoutException;
use App\Jobs\NotifyAdminsAboutOrder;
use App\Models\Customer;
use App\Models\Order;
use App\Models\OrderProduct;
use App\Models\Product;
use App\Models\ProductReceipt;
use Illuminate\Support\Facades\DB;
use Throwable;

/**
 * Places an order for the storefront checkout and the bot API.
 *
 * - Only existing, sellable products from the database; client prices are
 *   never used. Amounts are USD (like the admin); the CBU rate and the so'm
 *   total are stored as a snapshot only.
 * - Stock is taken from receipts oldest-first (FIFO). A line that needs more
 *   than one receipt is split into one OrderProduct per receipt; the
 *   OrderProductObserver performs the actual deduction.
 * - Everything runs in one transaction with the product and receipt rows
 *   locked, so parallel orders can't oversell.
 */
class OrderService
{
    public const MAX_QUANTITY_PER_LINE = 50;

    public function __construct(
        private readonly StorefrontService $storefront,
        private readonly ExchangeRateService $rates,
    ) {}

    /**
     * @param  array<int, int>  $quantities  product id => quantity
     * @param  array{customer_name: string, customer_phone: string, city?: ?string, address?: ?string, comment?: ?string, promo_code?: ?string}  $details
     *
     * @throws CheckoutException
     */
    public function placeOrder(Customer $customer, array $quantities, array $details): Order
    {
        $quantities = array_filter($quantities, fn ($qty) => (int) $qty > 0);
        if ($quantities === []) {
            throw new CheckoutException(__('Your cart is empty.'));
        }

        $rate = (float) ($this->rates->getUsdRate()['rate'] ?? 0);
        $promoPercent = $this->storefront->promoPercent($details['promo_code'] ?? null);

        $order = DB::transaction(function () use ($customer, $quantities, $details, $rate, $promoPercent) {
            $order = Order::create([
                'customer_id' => $customer->id,
                'secure_hash' => bin2hex(random_bytes(16)),
                'customer_name' => $details['customer_name'],
                'customer_phone' => $details['customer_phone'],
                'city' => $details['city'] ?? null,
                'address' => $details['address'] ?? null,
                'comment' => $details['comment'] ?? null,
                'promo_code' => $promoPercent > 0 ? strtoupper(trim((string) $details['promo_code'])) : null,
                'discount_amount' => 0,
                'amount' => 0,
                'amount_paid' => 0,
                'status' => 'processing',
            ]);

            $subtotal = 0.0;
            ksort($quantities); // stable lock order across concurrent orders

            foreach ($quantities as $productId => $qty) {
                $subtotal += $this->addLine($order, (int) $productId, (int) $qty);
            }

            $discount = round($subtotal * $promoPercent / 100, 2);
            $amount = max(0.0, round($subtotal - $discount, 2));

            $order->discount_amount = $discount;
            $order->amount = $amount;
            $order->exchange_rate = $rate > 0 ? $rate : null;
            $order->save();

            return $order;
        });

        $this->notifyAdmins($order);

        return $order;
    }

    /**
     * Recomputes the order total from its saved lines (for orders edited in
     * the admin): Σ line totals − the stored discount. amount_uzs follows at
     * the order's own rate (OrderObserver).
     */
    public function recalculateAmount(Order $order): void
    {
        $subtotal = (float) $order->orderProducts()->sum('total_price');

        $order->amount = max(0.0, round($subtotal - (float) $order->discount_amount, 2));
        $order->save();
    }

    /**
     * Announces the order in the admin bot. With the sync queue the job would
     * run inside the shopper's request, so it waits until the response is
     * sent; a Telegram problem never fails the order.
     */
    private function notifyAdmins(Order $order): void
    {
        try {
            $job = new NotifyAdminsAboutOrder($order->id);

            config('queue.default') === 'sync'
                ? dispatch($job)->afterResponse()
                : dispatch($job)->afterCommit();
        } catch (Throwable $e) {
            report($e);
        }
    }

    /**
     * Adds one product to the order, split across receipts. Returns the line total in USD.
     */
    private function addLine(Order $order, int $productId, int $qty): float
    {
        $product = Product::lockForUpdate()->find($productId);

        if (! $product || ! $this->storefront->isSellable($product)) {
            throw new CheckoutException(__('":title" is no longer available.', ['title' => $product?->name ?? '#'.$productId]));
        }

        if ($qty > self::MAX_QUANTITY_PER_LINE) {
            throw new CheckoutException(__('Not enough ":title" in stock.', ['title' => $product->name]));
        }

        $receipts = ProductReceipt::where('product_id', $product->id)
            ->where('quantity_remaining', '>', 0)
            ->orderBy('received_at')
            ->orderBy('id')
            ->lockForUpdate()
            ->get();

        if ($receipts->sum('quantity_remaining') < $qty) {
            throw new CheckoutException(__('Not enough ":title" in stock.', ['title' => $product->name]));
        }

        $unitPrice = round((float) $product->selling_price, 2);
        $left = $qty;

        foreach ($receipts as $receipt) {
            if ($left === 0) {
                break;
            }

            $take = min($left, (int) $receipt->quantity_remaining);
            OrderProduct::create([
                'order_id' => $order->id,
                'product_id' => $product->id,
                'product_receipt_id' => $receipt->id,
                'quantity' => $take,
                'price' => $unitPrice,
                'total_price' => round($unitPrice * $take, 2),
            ]);
            $left -= $take;
        }

        return round($unitPrice * $qty, 2);
    }
}
