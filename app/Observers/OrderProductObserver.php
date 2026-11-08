<?php

namespace App\Observers;

use App\Enums\OrderStatus;
use App\Models\Order;
use App\Models\OrderProduct;
use App\Models\ProductReceipt;
use App\Services\InventoryService;

class OrderProductObserver
{
    public function __construct(private InventoryService $inventory) {}

    /**
     * The line total is always price × quantity. The unit cost from the
     * chosen receipt is frozen onto the line, so profit stays correct even if
     * the receipt is edited later.
     */
    public function saving(OrderProduct $line): void
    {
        $line->total_price = round((float) $line->price * (int) $line->quantity, 2);

        if ($line->product_receipt_id) {
            $receipt = ProductReceipt::find($line->product_receipt_id);
            $line->unit_cost = $receipt ? $receipt->unit_cost : 0;
        } else {
            $line->unit_cost = 0;
        }
    }

    public function created(OrderProduct $line): void
    {
        if ($this->orderIsCancelled($line)) {
            return;
        }

        $this->inventory->consume($line);
    }

    /**
     * Return the units the line previously held, then draw the new amounts —
     * covers quantity changes and switching to a different receipt.
     */
    public function updating(OrderProduct $line): void
    {
        if ($this->orderIsCancelled($line)) {
            return;
        }

        $this->inventory->release(
            $line->getOriginal('product_receipt_id'),
            (int) $line->getOriginal('quantity'),
        );

        $this->inventory->consume($line);
    }

    public function deleted(OrderProduct $line): void
    {
        if ($this->orderIsCancelled($line)) {
            return;
        }

        $this->inventory->release($line->product_receipt_id, (int) $line->quantity);
    }

    /** A cancelled order holds no stock, so its lines don't move any (see OrderObserver). */
    private function orderIsCancelled(OrderProduct $line): bool
    {
        return Order::whereKey($line->order_id)->value('status') === OrderStatus::Cancelled;
    }
}
