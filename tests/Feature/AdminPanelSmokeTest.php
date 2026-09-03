<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class AdminPanelSmokeTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Rendering these pages builds the full Filament schemas — including the
     * order form's batch-options and maxValue closures and the Batch form's
     * price calculator — so a 200 proves there are no render-time errors.
     */
    #[DataProvider('adminPages')]
    public function test_admin_page_renders(string $url): void
    {
        // The panel allows any authenticated user in a local environment (the
        // app has no custom FilamentUser gate); mirror that here.
        config(['app.env' => 'local']);
        $this->actingAs(User::factory()->create());

        $this->get($url)->assertOk();
    }

    public static function adminPages(): array
    {
        return [
            'batches list' => ['/admin/batches'],
            'batch create' => ['/admin/batches/create'],
            'orders create' => ['/admin/orders/create'],
            'products list' => ['/admin/products'],
        ];
    }
}
