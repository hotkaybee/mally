<?php

namespace App\Observers;

use App\Models\OrderProduct;
use App\Models\ProductReceipt;
use App\Services\InventoryService;
use Illuminate\Support\Facades\DB;

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

    /**
     * Refuse an oversold line before its row is inserted, so a failed save
     * outside a transaction leaves no line without stock behind.
     */
    public function creating(OrderProduct $line): void
    {
        $this->inventory->ensureAvailable($line->product_receipt_id, (int) $line->quantity);
    }

    public function created(OrderProduct $line): void
    {
        $this->inventory->consume($line);
    }

    /**
     * Return the units the line previously held, then draw the new amounts —
     * covers quantity changes and switching to a different receipt. Both
     * steps share one transaction: if the new amount can't be drawn, the
     * returned units are rolled back too.
     */
    public function updating(OrderProduct $line): void
    {
        DB::transaction(function () use ($line) {
            $this->inventory->release(
                $line->getOriginal('product_receipt_id'),
                (int) $line->getOriginal('quantity'),
            );

            $this->inventory->consume($line);
        });
    }

    public function deleted(OrderProduct $line): void
    {
        $this->inventory->release($line->product_receipt_id, (int) $line->quantity);
    }
}
