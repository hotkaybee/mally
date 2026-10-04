<?php

namespace App\Observers;

use App\Models\Order;
use App\Services\InventoryService;

class OrderObserver
{
    public function __construct(private InventoryService $inventory) {}

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
