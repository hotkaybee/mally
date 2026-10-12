<?php

namespace App\Observers;

use App\Models\OrderProduct;
use App\Models\ProductReceipt;
use App\Services\InventoryService;

class OrderProductObserver
{
    public function __construct(private InventoryService $inventory) {}

    /**
     * Freeze the unit cost from the chosen receipt onto the line, so profit
     * stays correct even if the receipt is edited later.
     */
    public function saving(OrderProduct $line): void
    {
        if ($line->product_receipt_id) {
            $receipt = ProductReceipt::find($line->product_receipt_id);
            $line->unit_cost = $receipt ? $receipt->unit_cost : 0;
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
     * covers quantity changes and switching to a different receipt.
     */
    public function updating(OrderProduct $line): void
    {
        $originalProductId = $line->getOriginal('product_id') ?? $line->product_id;

        $this->inventory->release(
            $line->getOriginal('product_receipt_id') ? (int) $line->getOriginal('product_receipt_id') : null,
            (int) $line->getOriginal('quantity'),
            $originalProductId ? (int) $originalProductId : null,
        );

        $this->inventory->consume($line);
    }

    public function deleted(OrderProduct $line): void
    {
        $this->inventory->release(
            $line->product_receipt_id ? (int) $line->product_receipt_id : null,
            (int) $line->quantity,
            $line->product_id ? (int) $line->product_id : null,
        );
    }
}
