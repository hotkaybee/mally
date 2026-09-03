<?php

namespace App\Observers;

use App\Models\ProductBatch;
use App\Services\InventoryService;

class ProductBatchObserver
{
    public function __construct(private InventoryService $inventory) {}

    /**
     * A new batch starts fully in stock.
     */
    public function creating(ProductBatch $batch): void
    {
        $batch->quantity_remaining = $batch->quantity;
    }

    /**
     * If an admin corrects the received quantity, shift the remaining count by
     * the same delta so already-sold units stay accounted for.
     */
    public function updating(ProductBatch $batch): void
    {
        if ($batch->isDirty('quantity')) {
            $delta = (int) $batch->quantity - (int) $batch->getOriginal('quantity');
            $batch->quantity_remaining = max(0, (int) $batch->quantity_remaining + $delta);
        }
    }

    public function saved(ProductBatch $batch): void
    {
        $this->inventory->syncProductCaches($batch->product);
    }

    public function deleted(ProductBatch $batch): void
    {
        $this->inventory->syncProductCaches($batch->product);
    }
}
