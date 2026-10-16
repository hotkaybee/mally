<?php

namespace App\Observers;

use App\Models\Order;
use App\Services\InventoryService;

class OrderObserver
{
    public function __construct(private InventoryService $inventory) {}

    /**
     * Orders typed in the admin get what checkout orders get: a secret hash
     * for the receipt link and the recipient copied from the customer.
     */
    public function creating(Order $order): void
    {
        $order->secure_hash = $order->secure_hash ?: bin2hex(random_bytes(16));

        if ($order->customer && blank($order->customer_name)) {
            $order->customer_name = trim($order->customer->first_name.' '.$order->customer->last_name);
        }
        if ($order->customer && blank($order->customer_phone)) {
            $order->customer_phone = $order->customer->phone_number;
        }
    }

    /**
     * Payment status follows the money actually received.
     */
    public function saving(Order $order): void
    {
        if ($order->isDirty(['amount', 'amount_paid']) && $order->payment_status !== 'REFUNDED') {
            $amount = (float) $order->amount;
            $paid = (float) $order->amount_paid;

            $order->payment_status = match (true) {
                $amount > 0 && $paid >= $amount => 'PAID',
                $paid > 0 => 'PARTIAL',
                default => 'PENDING',
            };
        }
    }

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
     *
     * A delivered order is never deleted: its goods have left the shop, so
     * returning them to stock would sell units that don't exist, and the
     * sale would vanish from history.
     */
    public function deleting(Order $order): bool
    {
        if ($order->status === 'delivered') {
            return false;
        }

        foreach ($order->orderProducts as $line) {
            $this->inventory->release($line->product_receipt_id, (int) $line->quantity);
        }

        return true;
    }
}
