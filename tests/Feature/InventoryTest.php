<?php

namespace Tests\Feature;

use App\Models\Order;
use App\Models\Product;
use App\Models\ProductReceipt;
use App\Services\OrderService;
use Database\Seeders\BrandSeeder;
use Database\Seeders\CategorySeeder;
use Database\Seeders\ProductSeeder;
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

    private function sell(Order $order, Product $product, ProductReceipt $receipt, int $qty, float $price = 70)
    {
        return $order->orderProducts()->create([
            'product_id' => $product->id, 'product_receipt_id' => $receipt->id,
            'quantity' => $qty, 'price' => $price, 'total_price' => 0,
        ]);
    }

    public function test_order_total_is_recomputed_from_its_lines(): void
    {
        [$product, $receipt] = $this->productWithReceipt(qty: 5, purchase: 30, delivery: 5, selling: 70);
        $order = $this->makeOrder();
        $order->update(['discount_amount' => 10, 'exchange_rate' => 12000]);

        // The total typed in the form (0 here) is ignored: price × quantity.
        $line = $this->sell($order, $product, $receipt, 2);
        $this->assertSame('140.00', $line->fresh()->total_price);

        $line->update(['quantity' => 3]);
        app(OrderService::class)->recalculateAmount($order);

        $this->assertEquals(200.0, (float) $order->fresh()->amount);   // 3 × 70 − 10
        $this->assertSame(2400000, (int) $order->fresh()->amount_uzs); // at the order's own rate
    }

    public function test_unit_cost_of_a_sold_line_stays_frozen(): void
    {
        [$product, $receipt] = $this->productWithReceipt(qty: 5, purchase: 30, delivery: 5, selling: 70);
        $line = $this->sell($this->makeOrder(), $product, $receipt, 1);

        $receipt->update(['purchase_price' => 50]);
        $line->update(['quantity' => 2]);

        $this->assertSame('35.00', $line->fresh()->unit_cost);
    }

    public function test_delivered_order_cannot_be_deleted_and_keeps_its_stock_out(): void
    {
        [$product, $receipt] = $this->productWithReceipt(qty: 5, purchase: 30, delivery: 5, selling: 70);
        $order = $this->makeOrder();
        $this->sell($order, $product, $receipt, 3);
        $order->update(['status' => 'delivered']);

        $this->assertFalse($order->delete());

        $this->assertDatabaseHas('orders', ['id' => $order->id]);
        $this->assertSame(2, $receipt->fresh()->quantity_remaining);
    }

    public function test_receipt_quantity_cannot_drop_below_what_was_sold(): void
    {
        [$product, $receipt] = $this->productWithReceipt(qty: 10, purchase: 30, delivery: 5, selling: 70);
        $order = $this->makeOrder();
        $this->sell($order, $product, $receipt, 8);

        try {
            $receipt->fresh()->update(['quantity' => 5]);
            $this->fail('Expected a RuntimeException.');
        } catch (RuntimeException) {
        }

        $receipt->fresh()->update(['quantity' => 9]);
        $this->assertSame(1, $receipt->fresh()->quantity_remaining);

        // Returning the sold units can't push stock above what was received.
        $order->delete();
        $this->assertSame(9, $receipt->fresh()->quantity_remaining);
    }

    public function test_receipt_with_sales_cannot_be_deleted_or_moved(): void
    {
        [$product, $receipt] = $this->productWithReceipt(qty: 5, purchase: 30, delivery: 5, selling: 70);
        [$other] = $this->productWithReceipt(qty: 1, purchase: 1, delivery: 0, selling: 2);
        $this->sell($this->makeOrder(), $product, $receipt, 2);

        $this->assertFalse($receipt->fresh()->delete());

        try {
            $receipt->fresh()->update(['product_id' => $other->id]);
            $this->fail('Expected a RuntimeException.');
        } catch (RuntimeException) {
        }

        $this->assertSame($product->id, $receipt->fresh()->product_id);
        $this->assertSame(3, $product->fresh()->quantity);
        $this->assertSame(1, $other->fresh()->quantity);
    }

    public function test_moving_an_unsold_receipt_updates_both_products(): void
    {
        [$product, $receipt] = $this->productWithReceipt(qty: 5, purchase: 30, delivery: 5, selling: 70);
        [$other] = $this->productWithReceipt(qty: 1, purchase: 1, delivery: 0, selling: 2);

        $receipt->update(['product_id' => $other->id]);

        $this->assertSame(0, $product->fresh()->quantity);
        $this->assertSame(6, $other->fresh()->quantity);
    }

    public function test_product_that_was_sold_cannot_be_deleted(): void
    {
        [$product, $receipt] = $this->productWithReceipt(qty: 5, purchase: 30, delivery: 5, selling: 70);
        [$unsold] = $this->productWithReceipt(qty: 1, purchase: 1, delivery: 0, selling: 2);
        $this->sell($this->makeOrder(), $product, $receipt, 1);

        $this->assertFalse($product->delete());
        $this->assertTrue($unsold->delete());
        $this->assertDatabaseHas('products', ['id' => $product->id]);
    }

    public function test_admin_order_gets_a_number_a_receipt_hash_and_a_paid_state(): void
    {
        $order = $this->makeOrder();

        $this->assertSame(1000 + $order->id, $order->fresh()->order_number);
        $this->assertSame(32, strlen($order->fresh()->secure_hash));
        $this->assertFalse($order->fresh()->isPaid());

        $order->update(['amount' => 100, 'amount_paid' => 40]);
        $this->assertFalse($order->fresh()->isPaid());

        $order->update(['amount_paid' => 100]);
        $this->assertTrue($order->fresh()->isPaid());
    }

    public function test_catalog_seeders_never_wipe_a_live_shop(): void
    {
        $this->productWithReceipt(qty: 5, purchase: 30, delivery: 5, selling: 70);
        $this->app['env'] = 'production';

        // Called directly: `db:seed` in production would first ask for confirmation.
        foreach ([BrandSeeder::class, CategorySeeder::class, ProductSeeder::class] as $seeder) {
            $this->app->make($seeder)->run();
        }

        $this->assertSame(1, Product::count());
        $this->assertSame(1, ProductReceipt::count());
    }
}
