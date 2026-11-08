<?php

namespace Tests\Feature;

use App\Enums\OrderStatus;
use App\Filament\Resources\Orders\Pages\EditOrder;
use App\Livewire\Storefront\ProfilePage;
use App\Models\Customer;
use App\Models\Order;
use App\Models\OrderProduct;
use App\Models\Product;
use App\Models\ProductReceipt;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Livewire\Livewire;
use RuntimeException;
use Tests\TestCase;

/**
 * One list of order statuses (App\Enums\OrderStatus) for the admin, the
 * receipt and the profile. "Paid" follows from the payment. A cancelled
 * order holds no stock.
 */
class OrderStatusTest extends TestCase
{
    use RefreshDatabase;

    private const MIGRATION = 'database/migrations/2026_11_08_000000_use_one_list_of_order_statuses.php';

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
        app()->setLocale('ru');
        session(['mally_locale' => 'ru']);
    }

    private function receipt(int $qty = 5): ProductReceipt
    {
        $product = Product::create(['name' => 'Double Wear', 'quantity' => 0, 'selling_price' => 0, 'status' => 'in_stock']);

        return $product->receipts()->create([
            'purchase_price' => 19, 'delivery_cost' => 0, 'markup' => 0,
            'selling_price' => 40, 'website_price' => 0, 'quantity' => $qty, 'received_at' => now(),
        ]);
    }

    /** An order of 2 units from the receipt; the receipt then has 3 left. */
    private function order(ProductReceipt $receipt, array $attrs = []): Order
    {
        $customer = Customer::create(['first_name' => 'Aziza', 'last_name' => 'K', 'source' => 'website']);
        $order = Order::create(array_merge([
            'customer_id' => $customer->id, 'secure_hash' => str_repeat('a', 32),
            'amount' => 80, 'amount_paid' => 0, 'status' => OrderStatus::Processing,
        ], $attrs));
        OrderProduct::create([
            'order_id' => $order->id, 'product_id' => $receipt->product_id,
            'product_receipt_id' => $receipt->id, 'quantity' => 2, 'price' => 40,
        ]);

        return $order;
    }

    private function left(ProductReceipt $receipt): int
    {
        return (int) $receipt->fresh()->quantity_remaining;
    }

    // ------------------------------------------------------------ one list

    public function test_paid_follows_from_the_payment_not_from_a_status(): void
    {
        $order = $this->order($this->receipt());

        $this->assertSame('placed', $order->progressStep());
        $this->assertSame('Оформлен', $order->progressLabel());

        $order->update(['amount_paid' => 80]);
        $this->assertSame('paid', $order->progressStep());
        $this->assertSame('Оплачен', $order->progressLabel());

        $order->update(['status' => OrderStatus::Shipped]);
        $this->assertSame('shipped', $order->progressStep());
        $this->assertSame('В пути', $order->progressLabel());
    }

    public function test_the_admin_offers_the_same_statuses_the_website_shows(): void
    {
        $this->assertSame(
            ['Оформлен', 'Сборка', 'В пути', 'Вручен', 'Отменён'],
            array_map(fn (OrderStatus $s) => $s->getLabel(), OrderStatus::cases()),
        );
    }

    public function test_receipt_and_profile_show_the_status_set_in_the_admin(): void
    {
        $order = $this->order($this->receipt(), ['status' => OrderStatus::Shipped]);
        $receiptUrl = route('checkout.receipt', ['orderNumber' => $order->order_number, 'orderHash' => $order->secure_hash]);

        foreach (OrderStatus::cases() as $status) {
            $order->update(['status' => $status]);
            $this->get($receiptUrl)->assertOk();
        }

        $order->update(['status' => OrderStatus::Shipped]);
        session(['mally_customer_id' => $order->customer_id]);
        Livewire::test(ProfilePage::class)->assertSee('В пути');
    }

    // ------------------------------------------------------- cancel = stock back

    public function test_cancelling_returns_the_stock_and_bringing_back_takes_it_again(): void
    {
        $receipt = $this->receipt(5);
        $order = $this->order($receipt);
        $this->assertSame(3, $this->left($receipt));

        $order->update(['status' => OrderStatus::Cancelled]);
        $this->assertSame(5, $this->left($receipt));

        // Cancelling twice or saving other fields doesn't move stock again.
        $order->update(['status' => OrderStatus::Cancelled, 'amount_paid' => 0]);
        $this->assertSame(5, $this->left($receipt));

        $order->update(['status' => OrderStatus::Processing]);
        $this->assertSame(3, $this->left($receipt));
    }

    public function test_lines_of_a_cancelled_order_do_not_move_stock(): void
    {
        $receipt = $this->receipt(5);
        $order = $this->order($receipt, ['status' => OrderStatus::Processing]);
        $order->update(['status' => OrderStatus::Cancelled]);
        $line = $order->orderProducts()->sole();

        $line->update(['quantity' => 4]);
        $this->assertSame(5, $this->left($receipt));

        // Bringing it back takes the new quantity.
        $order->update(['status' => OrderStatus::Processing]);
        $this->assertSame(1, $this->left($receipt));
    }

    public function test_deleting_a_cancelled_order_does_not_return_its_stock_twice(): void
    {
        $receipt = $this->receipt(5);
        $order = $this->order($receipt);
        $order->update(['status' => OrderStatus::Cancelled]);

        $order->delete();

        $this->assertSame(5, $this->left($receipt));
    }

    public function test_a_cancelled_order_cannot_come_back_when_its_stock_is_gone(): void
    {
        config(['app.env' => 'local']);
        $this->actingAs(User::factory()->admin()->create());
        $receipt = $this->receipt(5);
        $order = $this->order($receipt);
        $order->update(['status' => OrderStatus::Cancelled]);
        // Someone else bought 4 of the 5 in the meantime.
        $receipt->update(['quantity_remaining' => 1]);

        Livewire::test(EditOrder::class, ['record' => $order->getRouteKey()])
            ->fillForm(['status' => OrderStatus::Processing->value])
            ->call('save')
            ->assertHasFormErrors(['status']);

        $this->assertSame(OrderStatus::Cancelled, $order->fresh()->status);
        $this->assertSame(1, $this->left($receipt));

        // Without the form, the model refuses as well and takes nothing.
        $this->expectException(RuntimeException::class);
        $order->update(['status' => OrderStatus::Processing]);
    }

    // ------------------------------------------------------------- migration

    public function test_migration_turns_awaiting_delivery_into_packing(): void
    {
        $this->artisan('migrate:rollback', ['--path' => self::MIGRATION])->assertSuccessful();

        $insert = fn (string $status) => DB::table('orders')->insertGetId([
            'amount' => 10, 'amount_paid' => 0, 'status' => $status, 'created_at' => now(), 'updated_at' => now(),
        ]);
        $processing = $insert('processing');
        $awaiting = $insert('awaiting_delivery');
        $delivered = $insert('delivered');

        $this->artisan('migrate', ['--path' => self::MIGRATION])->assertSuccessful();

        $this->assertSame(OrderStatus::Processing, Order::find($processing)->status);
        $this->assertSame(OrderStatus::Packing, Order::find($awaiting)->status);
        $this->assertSame(OrderStatus::Delivered, Order::find($delivered)->status);
    }

    public function test_migration_is_not_rolled_back_while_orders_are_cancelled(): void
    {
        $this->order($this->receipt())->update(['status' => OrderStatus::Cancelled]);

        try {
            $this->artisan('migrate:rollback', ['--path' => self::MIGRATION]);
            $this->fail('The rollback should stop on cancelled orders.');
        } catch (RuntimeException $e) {
            $this->assertStringContainsString('cancelled', $e->getMessage());
        }

        $this->assertSame(1, Order::where('status', 'cancelled')->count());
    }
}
