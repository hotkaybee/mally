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
    }

    /**
     * Draw the line's quantity out of its chosen receipt, or directly from the
     * product when no receipt is associated. Throws when the receipt or product
     * does not have enough units left. Caches are refreshed via receipt events.
     */
    public function consume(OrderProduct $line): void
    {
        if ($line->quantity <= 0) {
            return;
        }

        if (! $line->product_receipt_id) {
            if (! $line->product_id) {
                return;
            }

            DB::transaction(function () use ($line) {
                $product = Product::lockForUpdate()->find($line->product_id);

                if (! $product) {
                    return;
                }

                if ((int) $product->quantity < $line->quantity) {
                    throw new RuntimeException(
                        "Insufficient stock for product #{$product->id}: {$product->quantity} left, {$line->quantity} requested."
                    );
                }

                $product->decrement('quantity', $line->quantity);
            });

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
     * Return units to a receipt or directly to a product when no receipt was used
     * (order line edited/removed, order deleted).
     */
    public function release(?int $receiptId, int $quantity, ?int $productId = null): void
    {
        if ($quantity <= 0) {
            return;
        }

        if (! $receiptId) {
            if (! $productId) {
                return;
            }

            DB::transaction(function () use ($productId, $quantity) {
                $product = Product::lockForUpdate()->find($productId);

                if (! $product) {
                    return;
                }

                $product->increment('quantity', $quantity);
            });

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
