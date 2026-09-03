<?php

namespace App\Services;

use App\Models\OrderProduct;
use App\Models\Product;
use App\Models\ProductBatch;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class InventoryService
{
    /**
     * Recompute a product's cached stock and selling price from its batches.
     * quantity = total units remaining across all batches.
     * selling_price = the most recently received batch's selling price
     * (kept as-is when the product has no batches).
     */
    public function syncProductCaches(Product $product): void
    {
        $remaining = (int) $product->batches()->sum('quantity_remaining');

        $latest = $product->batches()
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
     * Draw the line's quantity out of its chosen batch. Throws when the batch
     * does not have enough units left. Caches are refreshed via batch events.
     */
    public function consume(OrderProduct $line): void
    {
        if (! $line->product_batch_id || $line->quantity <= 0) {
            return;
        }

        DB::transaction(function () use ($line) {
            $batch = ProductBatch::lockForUpdate()->find($line->product_batch_id);

            if (! $batch) {
                return;
            }

            if ($batch->quantity_remaining < $line->quantity) {
                throw new RuntimeException(
                    "Insufficient stock in batch #{$batch->id}: {$batch->quantity_remaining} left, {$line->quantity} requested."
                );
            }

            $batch->decrement('quantity_remaining', $line->quantity);
            $this->syncProductCaches($batch->product);
        });
    }

    /**
     * Return units to a batch (order line edited/removed, order deleted).
     */
    public function release(?int $batchId, int $quantity): void
    {
        if (! $batchId || $quantity <= 0) {
            return;
        }

        DB::transaction(function () use ($batchId, $quantity) {
            $batch = ProductBatch::lockForUpdate()->find($batchId);

            if (! $batch) {
                return;
            }

            $batch->increment('quantity_remaining', $quantity);
            $this->syncProductCaches($batch->product);
        });
    }
}
