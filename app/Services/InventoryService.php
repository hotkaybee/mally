<?php

namespace App\Services;

use App\Models\OrderProduct;
use App\Models\Product;
use App\Models\ProductReceipt;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class InventoryService
{
    /**
     * Recompute a product's cached stock and selling price from its receipts.
     * quantity = total units remaining across all receipts.
     * selling_price = the most recently received receipt's selling price
     * (kept as-is when the product has no receipts).
     */
    public function syncProductCaches(Product $product): void
    {
        $remaining = (int) $product->receipts()->sum('quantity_remaining');

        $latest = $product->receipts()
            ->orderByDesc('received_at')
            ->orderByDesc('id')
            ->first();

        $product->quantity = $remaining;

        if ($latest) {
            $product->selling_price = $latest->selling_price;
        }

        $product->saveQuietly();

        StorefrontService::forgetProductsCache();
    }

    /**
     * Draw the line's quantity out of its chosen receipt. Throws when the
     * receipt does not have enough units left. Caches are refreshed via
     * receipt events.
     */
    public function consume(OrderProduct $line): void
    {
        if (! $line->product_receipt_id || $line->quantity <= 0) {
            return;
        }

        DB::transaction(function () use ($line) {
            $receipt = ProductReceipt::lockForUpdate()->find($line->product_receipt_id);

            if (! $receipt) {
                return;
            }

            if ($receipt->quantity_remaining < $line->quantity) {
                throw new RuntimeException(
                    "Insufficient stock in receipt #{$receipt->id}: {$receipt->quantity_remaining} left, {$line->quantity} requested."
                );
            }

            $receipt->decrement('quantity_remaining', $line->quantity);
            $this->syncProductCaches($receipt->product);
        });
    }

    /**
     * Return units to a receipt (order line edited/removed, order deleted).
     */
    public function release(?int $receiptId, int $quantity): void
    {
        if (! $receiptId || $quantity <= 0) {
            return;
        }

        DB::transaction(function () use ($receiptId, $quantity) {
            $receipt = ProductReceipt::lockForUpdate()->find($receiptId);

            if (! $receipt) {
                return;
            }

            $receipt->increment('quantity_remaining', $quantity);
            $this->syncProductCaches($receipt->product);
        });
    }
}
