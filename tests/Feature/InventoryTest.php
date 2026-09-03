<?php

namespace Tests\Feature;

use App\Models\Order;
use App\Models\Product;
use App\Models\ProductBatch;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use RuntimeException;
use Tests\TestCase;

class InventoryTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @return array{0: Product, 1: ProductBatch}
     */
    private function productWithBatch(int $qty, float $purchase, float $delivery, float $selling): array
    {
        $product = Product::create([
            'name' => 'Test product',
            'quantity' => 0,
            'selling_price' => 0,
            'status' => 'in_stock',
        ]);

        $batch = $product->batches()->create([
            'purchase_price' => $purchase,
            'delivery_cost' => $delivery,
            'markup' => 0,
            'selling_price' => $selling,
            'website_price' => 0,
            'quantity' => $qty,
            'received_at' => now(),
        ]);

        return [$product->refresh(), $batch->refresh()];
    }

    private function makeOrder(): Order
    {
        return Order::create([
            'customer_id' => null,
            'amount' => 0,
            'amount_paid' => 0,
            'status' => 'processing',
        ]);
    }

    public function test_creating_a_batch_syncs_product_caches(): void
    {
        [$product, $batch] = $this->productWithBatch(qty: 5, purchase: 10, delivery: 2, selling: 20);

        $this->assertSame(5, $product->quantity);
        $this->assertSame('20.00', $product->selling_price);
        $this->assertSame(5, $batch->quantity_remaining);
    }

    public function test_newest_batch_drives_the_product_selling_price(): void
    {
        [$product] = $this->productWithBatch(qty: 5, purchase: 10, delivery: 2, selling: 20);

        $product->batches()->create([
            'purchase_price' => 15, 'delivery_cost' => 3, 'markup' => 0,
            'selling_price' => 30, 'website_price' => 0, 'quantity' => 4, 'received_at' => now()->addDay(),
        ]);
        $product->refresh();

        $this->assertSame(9, $product->quantity);
        $this->assertSame('30.00', $product->selling_price);
    }

    public function test_selling_freezes_unit_cost_and_decrements_stock(): void
    {
        [$product, $batch] = $this->productWithBatch(qty: 5, purchase: 30, delivery: 5, selling: 70);
        $order = $this->makeOrder();

        $line = $order->orderProducts()->create([
            'product_id' => $product->id,
            'product_batch_id' => $batch->id,
            'quantity' => 2,
            'price' => 70,
            'total_price' => 140,
        ]);

        $this->assertSame('35.00', $line->fresh()->unit_cost);       // purchase + delivery
        $this->assertSame(3, $batch->fresh()->quantity_remaining);   // 5 - 2
        $this->assertSame(3, $product->fresh()->quantity);
    }

    public function test_editing_a_line_quantity_adjusts_stock(): void
    {
        [$product, $batch] = $this->productWithBatch(qty: 5, purchase: 30, delivery: 5, selling: 70);
        $order = $this->makeOrder();
        $line = $order->orderProducts()->create([
            'product_id' => $product->id, 'product_batch_id' => $batch->id,
            'quantity' => 2, 'price' => 70, 'total_price' => 140,
        ]);

        $line->update(['quantity' => 4, 'total_price' => 280]);

        $this->assertSame(1, $batch->fresh()->quantity_remaining);   // 5 - 4
        $this->assertSame(1, $product->fresh()->quantity);
    }

    public function test_deleting_an_order_restores_stock(): void
    {
        [$product, $batch] = $this->productWithBatch(qty: 5, purchase: 30, delivery: 5, selling: 70);
        $order = $this->makeOrder();
        $order->orderProducts()->create([
            'product_id' => $product->id, 'product_batch_id' => $batch->id,
            'quantity' => 3, 'price' => 70, 'total_price' => 210,
        ]);
        $this->assertSame(2, $batch->fresh()->quantity_remaining);

        $order->delete();

        $this->assertSame(5, $batch->fresh()->quantity_remaining);
        $this->assertSame(5, $product->fresh()->quantity);
    }

    public function test_overselling_a_batch_is_blocked(): void
    {
        [$product, $batch] = $this->productWithBatch(qty: 3, purchase: 30, delivery: 5, selling: 70);
        $order = $this->makeOrder();

        $threw = false;
        try {
            DB::transaction(function () use ($order, $product, $batch) {
                $order->orderProducts()->create([
                    'product_id' => $product->id, 'product_batch_id' => $batch->id,
                    'quantity' => 5, 'price' => 70, 'total_price' => 350,
                ]);
            });
        } catch (RuntimeException $e) {
            $threw = true;
        }

        $this->assertTrue($threw, 'Expected a RuntimeException when overselling.');
        $this->assertSame(3, $batch->fresh()->quantity_remaining);
        $this->assertSame(0, $order->orderProducts()->count());
    }
}
