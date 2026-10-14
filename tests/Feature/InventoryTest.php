<?php

namespace Tests\Feature;

use App\Models\Order;
use App\Models\Product;
use App\Models\ProductReceipt;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use RuntimeException;
use Tests\TestCase;

class InventoryTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @return array{0: Product, 1: ProductReceipt}
     */
    private function productWithReceipt(int $qty, float $purchase, float $delivery, float $selling): array
    {
        $product = Product::create([
            'name' => 'Test product',
            'quantity' => 0,
            'selling_price' => 0,
            'status' => 'in_stock',
        ]);

        $receipt = $product->receipts()->create([
            'purchase_price' => $purchase,
            'delivery_cost' => $delivery,
            'markup' => 0,
            'selling_price' => $selling,
            'website_price' => 0,
            'quantity' => $qty,
            'received_at' => now(),
        ]);

        return [$product->refresh(), $receipt->refresh()];
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

    public function test_creating_a_receipt_syncs_product_caches(): void
    {
        [$product, $receipt] = $this->productWithReceipt(qty: 5, purchase: 10, delivery: 2, selling: 20);

        $this->assertSame(5, $product->quantity);
        $this->assertSame('20.00', $product->selling_price);
        $this->assertSame(5, $receipt->quantity_remaining);
    }

    public function test_newest_receipt_drives_the_product_selling_price(): void
    {
        [$product] = $this->productWithReceipt(qty: 5, purchase: 10, delivery: 2, selling: 20);

        $product->receipts()->create([
            'purchase_price' => 15, 'delivery_cost' => 3, 'markup' => 0,
            'selling_price' => 30, 'website_price' => 0, 'quantity' => 4, 'received_at' => now()->addDay(),
        ]);
        $product->refresh();

        $this->assertSame(9, $product->quantity);
        $this->assertSame('30.00', $product->selling_price);
    }

    public function test_selling_freezes_unit_cost_and_decrements_stock(): void
    {
        [$product, $receipt] = $this->productWithReceipt(qty: 5, purchase: 30, delivery: 5, selling: 70);
        $order = $this->makeOrder();

        $line = $order->orderProducts()->create([
            'product_id' => $product->id,
            'product_receipt_id' => $receipt->id,
            'quantity' => 2,
            'price' => 70,
            'total_price' => 140,
        ]);

        $this->assertSame('35.00', $line->fresh()->unit_cost);       // purchase + delivery
        $this->assertSame(3, $receipt->fresh()->quantity_remaining);   // 5 - 2
        $this->assertSame(3, $product->fresh()->quantity);
    }

    public function test_editing_a_line_quantity_adjusts_stock(): void
    {
        [$product, $receipt] = $this->productWithReceipt(qty: 5, purchase: 30, delivery: 5, selling: 70);
        $order = $this->makeOrder();
        $line = $order->orderProducts()->create([
            'product_id' => $product->id, 'product_receipt_id' => $receipt->id,
            'quantity' => 2, 'price' => 70, 'total_price' => 140,
        ]);

        $line->update(['quantity' => 4, 'total_price' => 280]);

        $this->assertSame(1, $receipt->fresh()->quantity_remaining);   // 5 - 4
        $this->assertSame(1, $product->fresh()->quantity);
    }

    public function test_failed_line_increase_does_not_create_phantom_stock(): void
    {
        [$product, $receipt] = $this->productWithReceipt(qty: 5, purchase: 30, delivery: 5, selling: 70);
        $line = $this->makeOrder()->orderProducts()->create([
            'product_id' => $product->id, 'product_receipt_id' => $receipt->id,
            'quantity' => 3, 'price' => 70, 'total_price' => 210,
        ]);

        // No outer transaction, like a plain admin save.
        try {
            $line->update(['quantity' => 6]);
            $this->fail('Expected a RuntimeException when overselling.');
        } catch (RuntimeException) {
        }

        $this->assertSame(3, $line->fresh()->quantity);
        $this->assertSame(2, $receipt->fresh()->quantity_remaining);
        $this->assertSame(2, $product->fresh()->quantity);
    }

    public function test_overselling_line_is_not_saved_without_an_outer_transaction(): void
    {
        [$product, $receipt] = $this->productWithReceipt(qty: 3, purchase: 30, delivery: 5, selling: 70);
        $order = $this->makeOrder();

        try {
            $order->orderProducts()->create([
                'product_id' => $product->id, 'product_receipt_id' => $receipt->id,
                'quantity' => 5, 'price' => 70, 'total_price' => 350,
            ]);
            $this->fail('Expected a RuntimeException when overselling.');
        } catch (RuntimeException) {
        }

        $this->assertSame(0, $order->orderProducts()->count());
        $this->assertSame(3, $receipt->fresh()->quantity_remaining);
    }

    public function test_deleting_an_order_restores_stock(): void
    {
        [$product, $receipt] = $this->productWithReceipt(qty: 5, purchase: 30, delivery: 5, selling: 70);
        $order = $this->makeOrder();
        $order->orderProducts()->create([
            'product_id' => $product->id, 'product_receipt_id' => $receipt->id,
            'quantity' => 3, 'price' => 70, 'total_price' => 210,
        ]);
        $this->assertSame(2, $receipt->fresh()->quantity_remaining);

        $order->delete();

        $this->assertSame(5, $receipt->fresh()->quantity_remaining);
        $this->assertSame(5, $product->fresh()->quantity);
    }

    public function test_overselling_a_receipt_is_blocked(): void
    {
        [$product, $receipt] = $this->productWithReceipt(qty: 3, purchase: 30, delivery: 5, selling: 70);
        $order = $this->makeOrder();

        $threw = false;
        try {
            DB::transaction(function () use ($order, $product, $receipt) {
                $order->orderProducts()->create([
                    'product_id' => $product->id, 'product_receipt_id' => $receipt->id,
                    'quantity' => 5, 'price' => 70, 'total_price' => 350,
                ]);
            });
        } catch (RuntimeException $e) {
            $threw = true;
        }

        $this->assertTrue($threw, 'Expected a RuntimeException when overselling.');
        $this->assertSame(3, $receipt->fresh()->quantity_remaining);
        $this->assertSame(0, $order->orderProducts()->count());
    }
}
