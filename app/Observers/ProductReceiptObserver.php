<?php

namespace App\Observers;

use App\Models\Product;
use App\Models\ProductReceipt;
use App\Services\InventoryService;
use RuntimeException;

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
     * the same delta so already-sold units stay accounted for. The quantity
     * can't drop below what was already sold, and a receipt that has sales
     * can't move to another product — both would invent or lose stock.
     */
    public function updating(ProductReceipt $receipt): void
    {
        if ($receipt->isDirty('quantity')) {
            $sold = (int) $receipt->getOriginal('quantity') - (int) $receipt->getOriginal('quantity_remaining');

            if ((int) $receipt->quantity < $sold) {
                throw new RuntimeException("Receipt #{$receipt->id}: {$sold} units are already sold, the quantity can't be lower.");
            }

            $receipt->quantity_remaining = (int) $receipt->quantity - $sold;
        }

        if ($receipt->isDirty('product_id') && $receipt->orderProducts()->exists()) {
            throw new RuntimeException("Receipt #{$receipt->id} has sales and can't be moved to another product.");
        }
    }

    public function saved(ProductReceipt $receipt): void
    {
        $this->syncProduct($receipt->product_id);

        if ($receipt->wasChanged('product_id')) {
            $this->syncProduct($receipt->getOriginal('product_id'));
        }
    }

    /**
     * A receipt that has order lines is the history of those sales; deleting
     * it would leave lines without stock to return or re-draw.
     */
    public function deleting(ProductReceipt $receipt): bool
    {
        return ! $receipt->orderProducts()->exists();
    }

    public function deleted(ProductReceipt $receipt): void
    {
        $this->syncProduct($receipt->product_id);
    }

    private function syncProduct(?int $productId): void
    {
        if ($productId && $product = Product::find($productId)) {
            $this->inventory->syncProductCaches($product);
        }
    }
}
