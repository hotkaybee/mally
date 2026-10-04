<?php

namespace App\Observers;

use App\Models\ProductReceipt;
use App\Services\InventoryService;

class ProductReceiptObserver
{
    public function __construct(private InventoryService $inventory) {}

    /**
     * A new receipt starts fully in stock.
     */
    public function creating(ProductReceipt $receipt): void
    {
        $receipt->quantity_remaining = $receipt->quantity;
    }

    /**
     * If an admin corrects the received quantity, shift the remaining count by
     * the same delta so already-sold units stay accounted for.
     */
    public function updating(ProductReceipt $receipt): void
    {
        if ($receipt->isDirty('quantity')) {
            $delta = (int) $receipt->quantity - (int) $receipt->getOriginal('quantity');
            $receipt->quantity_remaining = max(0, (int) $receipt->quantity_remaining + $delta);
        }
    }

    public function saved(ProductReceipt $receipt): void
    {
        $this->inventory->syncProductCaches($receipt->product);
    }

    public function deleted(ProductReceipt $receipt): void
    {
        $this->inventory->syncProductCaches($receipt->product);
    }
}
