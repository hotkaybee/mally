<?php

namespace Tests\Feature;

use App\Filament\Resources\Orders\Pages\ListOrders;
use App\Filament\Widgets\RecentOrdersWidget;
use App\Models\Customer;
use App\Models\Order;
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

    /** Bot orders for a phone that belongs to a website account have no customer. */
    public function test_orders_without_a_customer_show_the_typed_name(): void
    {
        $this->actingAs(User::factory()->admin()->create());
        $customer = Customer::create(['first_name' => 'Madina', 'last_name' => 'Umarova', 'phone_number' => '+998 90 111 11 11', 'source' => 'instagram']);
        Order::create(['customer_id' => $customer->id, 'amount' => 10, 'status' => 'processing']);
        Order::create(['customer_id' => null, 'customer_name' => 'Ali Valiev', 'customer_phone' => '+998 93 555 44 33', 'amount' => 20, 'status' => 'processing']);

        foreach ([ListOrders::class, RecentOrdersWidget::class] as $component) {
            Livewire::test($component)->assertOk()->assertSee('Madina Umarova')->assertSee('Ali Valiev');
        }
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
}
