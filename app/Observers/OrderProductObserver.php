<?php

namespace App\Observers;

use App\Models\OrderProduct;
use App\Models\ProductBatch;
use App\Services\InventoryService;

class OrderProductObserver
{
    public function __construct(private InventoryService $inventory) {}

    /**
     * Freeze the unit cost from the chosen batch onto the line, so profit stays
     * correct even if the batch is edited later.
     */
    public function saving(OrderProduct $line): void
    {
        if ($line->product_batch_id) {
            $batch = ProductBatch::find($line->product_batch_id);
            $line->unit_cost = $batch ? $batch->unit_cost : 0;
        } else {
            $line->unit_cost = 0;
        }
    }

    public function created(OrderProduct $line): void
    {
        $this->inventory->consume($line);
    }

    /**
     * Return the units the line previously held, then draw the new amounts —
     * covers quantity changes and switching to a different batch.
     */
    public function updating(OrderProduct $line): void
    {
        $this->inventory->release(
            $line->getOriginal('product_batch_id'),
            (int) $line->getOriginal('quantity'),
        );

        $this->inventory->consume($line);
    }

    public function deleted(OrderProduct $line): void
    {
        $this->inventory->release($line->product_batch_id, (int) $line->quantity);
    }
}
