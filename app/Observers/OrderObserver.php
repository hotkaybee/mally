<?php

namespace App\Observers;

use App\Models\Order;
use App\Services\InventoryService;

class OrderObserver
{
    public function __construct(private InventoryService $inventory) {}

    /**
     * When the USD amount of an existing order is corrected, keep the so'm
     * total shown on the receipt in step — at the rate stored on the order,
     * never today's rate.
     */
    public function updating(Order $order): void
    {
        if ($order->isDirty('amount') && ! $order->isDirty('amount_uzs') && (float) $order->exchange_rate > 0) {
            $order->amount_uzs = (int) round((float) $order->amount * (float) $order->exchange_rate);
        }
    }

    /**
     * Deleting an order removes its order_product rows through a database-level
     * cascade, which does not fire Eloquent events. Release their stock here
     * before the rows disappear.
     */
    public function deleting(Order $order): void
    {
        foreach ($order->orderProducts as $line) {
            $this->inventory->release($line->product_receipt_id, (int) $line->quantity);
        }
    }
}
