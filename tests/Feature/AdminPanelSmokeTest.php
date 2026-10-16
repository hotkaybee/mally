<?php

namespace Tests\Feature;

use App\Filament\Resources\Orders\Pages\EditOrder;
use App\Filament\Resources\Orders\Pages\ListOrders;
use App\Filament\Resources\Reviews\Pages\EditReview;
use App\Filament\Resources\Reviews\Pages\ListReviews;
use App\Models\Customer;
use App\Models\Order;
use App\Models\Product;
use App\Models\Review;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class AdminPanelSmokeTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Rendering these pages builds the full Filament schemas — including the
     * order form's receipt-options and maxValue closures and the Receipt
     * form's price calculator — so a 200 proves there are no render-time
     * errors.
     */
    #[DataProvider('adminPages')]
    public function test_admin_page_renders(string $url): void
    {
        // The panel allows any authenticated user in a local environment (the
        // app has no custom FilamentUser gate); mirror that here.
        config(['app.env' => 'local']);
        $this->actingAs(User::factory()->admin()->create());

        $this->get($url)->assertOk();
    }

    public static function adminPages(): array
    {
        return [
            'receipts list' => ['/admin/receipts'],
            'receipt create' => ['/admin/receipts/create'],
            'orders create' => ['/admin/orders/create'],
            'products list' => ['/admin/products'],
            'products create' => ['/admin/products/create'],
            'categories list' => ['/admin/categories'],
            'categories create' => ['/admin/categories/create'],
            'reviews list' => ['/admin/reviews'],
            'customers create' => ['/admin/customers/create'],
        ];
    }

    public function test_editing_an_order_keeps_its_lines_valid_and_recomputes_the_total(): void
    {
        config(['app.env' => 'local']);
        $this->actingAs(User::factory()->admin()->create());

        $product = Product::create(['name' => 'Blush', 'quantity' => 0, 'selling_price' => 0, 'status' => 'in_stock']);
        $receipt = $product->receipts()->create([
            'purchase_price' => 9, 'delivery_cost' => 2, 'markup' => 0, 'selling_price' => 25,
            'website_price' => 0, 'quantity' => 3, 'received_at' => now(),
        ]);
        $customer = Customer::create(['first_name' => 'Ali', 'last_name' => 'V', 'email' => 'a@example.com', 'phone_number' => '+998901234567', 'source' => 'instagram']);
        $order = Order::create(['customer_id' => $customer->id, 'amount' => 999, 'amount_paid' => 0, 'status' => 'processing']);
        // The line takes every unit: the receipt now has 0 left.
        $order->orderProducts()->create([
            'product_id' => $product->id, 'product_receipt_id' => $receipt->id,
            'quantity' => 3, 'price' => 25, 'total_price' => 75,
        ]);

        $this->get('/admin/orders')->assertOk();
        $this->get("/admin/orders/{$order->id}/edit")->assertOk();
        $this->get("/admin/receipts/{$receipt->id}/edit")->assertOk();

        // The units the line already holds count as available to it.
        Livewire::test(EditOrder::class, ['record' => $order->getRouteKey()])
            ->call('save')
            ->assertHasNoFormErrors();

        $this->assertEquals(75.0, (float) $order->fresh()->amount);
        $this->assertSame(0, $receipt->fresh()->quantity_remaining);
    }

    public function test_orders_and_reviews_show_the_derived_order_number(): void
    {
        config(['app.env' => 'local']);
        $this->actingAs(User::factory()->admin()->create());

        $first = Order::create(['amount' => 10, 'status' => 'processing']);
        $second = Order::create(['amount' => 20, 'status' => 'processing']);
        $review = Review::create(['order_id' => $second->id, 'author_name' => 'A', 'comment' => 'Nice', 'rating' => 5]);

        Livewire::test(ListOrders::class)
            ->searchTable((string) $second->order_number)
            ->assertCanSeeTableRecords([$second])
            ->assertCanNotSeeTableRecords([$first])
            ->searchTable('')
            ->sortTable('order_number', 'desc')
            ->assertCanSeeTableRecords([$second, $first], inOrder: true);

        Livewire::test(ListReviews::class)->assertSee((string) $second->order_number);
        Livewire::test(EditReview::class, ['record' => $review->getRouteKey()])
            ->assertSchemaStateSet(['order_id' => $second->order_number]);
    }
}
