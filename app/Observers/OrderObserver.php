<?php

namespace App\Observers;

use App\Enums\OrderStatus;
use App\Models\Order;
use App\Services\InventoryService;
use Illuminate\Support\Facades\DB;

/**
 * A cancelled order holds no stock: cancelling returns its units to the
 * receipts, bringing it back takes them again (OrderProductObserver leaves
 * the lines of a cancelled order alone).
 */
class OrderObserver
{
    public function __construct(private InventoryService $inventory) {}

    public function updating(Order $order): void
    {
        if (! $order->isDirty('status')) {
            return;
        }

        $wasCancelled = $order->getOriginal('status') === OrderStatus::Cancelled;

        if ($order->isCancelled() && ! $wasCancelled) {
            foreach ($order->orderProducts()->get() as $line) {
                $this->inventory->release($line->product_receipt_id, (int) $line->quantity);
            }
        } elseif ($wasCancelled && ! $order->isCancelled()) {
            // Throws when a receipt no longer has enough units; nothing is taken then.
            DB::transaction(function () use ($order) {
                foreach ($order->orderProducts()->get() as $line) {
                    $this->inventory->consume($line);
                }
            });
        }
    }

    /**
     * Deleting an order removes its order_product rows through a database-level
     * cascade, which does not fire Eloquent events. Release their stock here
     * before the rows disappear (a cancelled order has none to release).
     */
    public function deleting(Order $order): void
    {
        if ($order->isCancelled()) {
            return;
        }

        foreach ($order->orderProducts()->get() as $line) {
            $this->inventory->release($line->product_receipt_id, (int) $line->quantity);
        }
    }
}
