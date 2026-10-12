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

    public function test_product_without_receipt_deducts_and_restores_stock_on_line_and_order_deletion(): void
    {
        $product = Product::create([
            'name' => 'No Receipt Product',
            'sku' => 'ML-NO-RECEIPT',
            'quantity' => 10,
            'selling_price' => 45.00,
            'status' => 'in_stock',
        ]);

        $order = $this->makeOrder();

        $line = $order->orderProducts()->create([
            'product_id' => $product->id,
            'product_receipt_id' => null,
            'quantity' => 3,
            'price' => 45,
            'total_price' => 135,
        ]);

        $this->assertSame('0.00', $line->fresh()->unit_cost);
        $this->assertSame(7, $product->fresh()->quantity);

        // Editing line quantity adjusts product stock
        $line->update(['quantity' => 4, 'total_price' => 180]);
        $this->assertSame(6, $product->fresh()->quantity);

        // Deleting a single order item restores stock
        $line->delete();
        $this->assertSame(10, $product->fresh()->quantity);

        // Creating another line and deleting the parent order restores stock
        $order->orderProducts()->create([
            'product_id' => $product->id,
            'product_receipt_id' => null,
            'quantity' => 6,
            'price' => 45,
            'total_price' => 270,
        ]);
        $this->assertSame(4, $product->fresh()->quantity);

        $order->delete();
        $this->assertSame(10, $product->fresh()->quantity);
    }

    public function test_overselling_a_product_without_receipt_is_blocked(): void
    {
        $product = Product::create([
            'name' => 'Limited No Receipt Product',
            'sku' => 'ML-LIM-NO-RECEIPT',
            'quantity' => 2,
            'selling_price' => 50.00,
            'status' => 'in_stock',
        ]);

        $order = $this->makeOrder();

        $threw = false;
        try {
            DB::transaction(function () use ($order, $product) {
                $order->orderProducts()->create([
                    'product_id' => $product->id,
                    'product_receipt_id' => null,
                    'quantity' => 5,
                    'price' => 50,
                    'total_price' => 250,
                ]);
            });
        } catch (RuntimeException $e) {
            $threw = true;
        }

        $this->assertTrue($threw, 'Expected a RuntimeException when overselling a product without receipts.');
        $this->assertSame(2, $product->fresh()->quantity);
        $this->assertSame(0, $order->orderProducts()->count());
    }
}
