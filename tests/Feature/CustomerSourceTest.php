<?php

namespace Tests\Feature;

use App\Models\Customer;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CustomerSourceTest extends TestCase
{
    use RefreshDatabase;

    public function test_customer_can_come_from_telegram(): void
    {
        $customer = Customer::create([
            'first_name' => 'Test',
            'last_name' => 'Customer',
            'email' => 'telegram@example.com',
            'source' => 'telegram',
        ]);

        $this->assertSame('telegram', $customer->refresh()->source);
    }

    public function test_customer_can_come_from_the_website(): void
    {
        $customer = Customer::create([
            'first_name' => 'Test',
            'last_name' => 'Customer',
            'email' => 'website@example.com',
            'source' => 'website',
        ]);

        $this->assertSame('website', $customer->refresh()->source);
    }

    public function test_existing_sources_still_work(): void
    {
        foreach (['word_of_mouth', 'friend', 'instagram', 'telegram'] as $source) {
            $customer = Customer::create([
                'first_name' => 'Test',
                'last_name' => $source,
                'email' => "{$source}@example.com",
                'source' => $source,
            ]);

            $this->assertSame($source, $customer->refresh()->source);
        }
    }
}
