<?php

namespace Tests\Feature;

use App\Filament\Resources\Orders\Pages\CreateOrder;
use App\Filament\Resources\Orders\Pages\EditOrder;
use App\Models\Customer;
use App\Models\Order;
use App\Models\OrderProduct;
use App\Models\Product;
use App\Models\ProductReceipt;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class AdminOrderFormTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
        app()->setLocale('ru');
        $this->actingAs(User::factory()->admin()->create());
    }

    private function receipt(float $selling = 40, int $qty = 10): ProductReceipt
    {
        $product = Product::create(['name' => 'Double Wear', 'quantity' => 0, 'selling_price' => 0, 'status' => 'in_stock']);

        return $product->receipts()->create([
            'purchase_price' => 19, 'delivery_cost' => 0, 'markup' => 0,
            'selling_price' => $selling, 'website_price' => 0, 'quantity' => $qty, 'received_at' => now(),
        ]);
    }

    private function customer(string $name): Customer
    {
        return Customer::create(['first_name' => $name, 'last_name' => 'B', 'source' => 'instagram']);
    }

    /** An order with one line of 2 × $40 and a $5 discount. */
    private function order(Customer $customer): Order
    {
        $receipt = $this->receipt();
        $order = Order::create(['customer_id' => $customer->id, 'amount' => 75, 'amount_paid' => 0, 'discount_amount' => 5, 'status' => 'processing']);
        OrderProduct::create([
            'order_id' => $order->id, 'product_id' => $receipt->product_id, 'product_receipt_id' => $receipt->id,
            'quantity' => 2, 'price' => 40,
        ]);

        return $order;
    }

    public function test_the_customer_of_an_existing_order_cannot_be_changed(): void
    {
        $owner = $this->customer('Owner');
        $other = $this->customer('Other');
        $order = $this->order($owner);

        Livewire::test(EditOrder::class, ['record' => $order->getRouteKey()])
            ->assertFormFieldIsDisabled('customer_id')
            ->fillForm(['customer_id' => $other->id])
            ->call('save')
            ->assertHasNoFormErrors();

        $this->assertSame($owner->id, $order->fresh()->customer_id);
    }

    public function test_a_new_order_gets_its_total_from_the_lines_not_from_the_form(): void
    {
        $customer = $this->customer('Aziza');
        $receipt = $this->receipt(selling: 40);

        $form = Livewire::test(CreateOrder::class);
        // The form starts with one empty product line.
        $line = 'data.orderProducts.'.array_key_first($form->get('data.orderProducts'));

        $form->fillForm(['customer_id' => $customer->id, 'amount' => 1, 'amount_paid' => 0, 'status' => 'processing'])
            ->set("{$line}.product_id", $receipt->product_id)
            ->set("{$line}.product_receipt_id", $receipt->id)
            ->set("{$line}.quantity", 3)
            ->set("{$line}.price", 40)
            ->set("{$line}.total_price", 1)
            ->call('create')
            ->assertHasNoFormErrors();

        $order = Order::sole();
        $this->assertEquals(120, (float) $order->amount);
        $this->assertSame('120.00', $order->orderProducts()->sole()->total_price);
        $this->assertSame($customer->id, $order->customer_id);
    }

    public function test_editing_the_lines_recalculates_the_total_and_keeps_the_discount(): void
    {
        $order = $this->order($this->customer('Aziza'));
        $line = $order->orderProducts()->sole();

        Livewire::test(EditOrder::class, ['record' => $order->getRouteKey()])
            ->set("data.orderProducts.record-{$line->id}.quantity", 3)
            ->set('data.amount', 1)
            ->call('save')
            ->assertHasNoFormErrors();

        // 3 × $40 − $5 discount
        $this->assertEquals(115, (float) $order->fresh()->amount);
        $this->assertSame('120.00', $line->fresh()->total_price);
    }

    public function test_order_pages_have_proper_titles(): void
    {
        $order = $this->order($this->customer('Aziza'));

        $this->get("/admin/orders/{$order->id}/edit")
            ->assertOk()
            ->assertSee('Редактирование заказа №'.$order->order_number)
            ->assertSee('Товары в заказе')
            ->assertDontSee('Order products');

        $this->get("/admin/orders/{$order->id}")->assertOk()->assertSee('Заказ №'.$order->order_number);
        $this->get('/admin/orders/create')->assertOk()->assertSee('Новый заказ');
    }
}
