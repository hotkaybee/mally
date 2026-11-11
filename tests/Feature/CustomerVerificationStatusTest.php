<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * MAL-29: the admin's customer page shows whether the phone and email are
 * confirmed, with the same rules as the customer's profile.
 */
class CustomerVerificationStatusTest extends TestCase
{
    use RefreshDatabase;

    private const PHONE = '+998 99 802 24 08';

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
        app()->setLocale('ru');
        config(['app.env' => 'local']);
        $this->actingAs(User::factory()->admin()->create());
    }

    private function shopper(array $user = [], array $customer = []): Customer
    {
        $account = new User(array_merge(['name' => 'Kamr', 'email' => 'kamr@gmail.com', 'phone' => self::PHONE], $user));
        $account->forceFill([
            'email_verified_at' => array_key_exists('email_verified_at', $user) ? $user['email_verified_at'] : now(),
            'phone_verified_at' => array_key_exists('phone_verified_at', $user) ? $user['phone_verified_at'] : now(),
        ])->save();

        return Customer::create(array_merge([
            'user_id' => $account->id, 'first_name' => 'Kamr', 'last_name' => 'Beg',
            'email' => 'kamr@gmail.com', 'phone_number' => self::PHONE, 'source' => 'website',
        ], $customer));
    }

    private function page(Customer $customer)
    {
        return $this->get("/admin/customers/{$customer->id}")->assertOk();
    }

    public function test_confirmed_phone_and_email_are_marked_confirmed(): void
    {
        $customer = $this->shopper();

        $this->assertSame('verified', $customer->phoneStatus());
        $this->assertSame('verified', $customer->emailStatus());
        $this->assertSame(2, substr_count($this->page($customer)->getContent(), 'Подтверждён'));
    }

    public function test_a_phone_typed_at_registration_is_not_confirmed(): void
    {
        $customer = $this->shopper(['phone_verified_at' => null]);

        $this->assertSame('unverified', $customer->phoneStatus());
        $this->page($customer)->assertSee('Не подтверждён')->assertSee('Подтверждён');
    }

    public function test_a_phone_changed_after_confirmation_is_not_confirmed(): void
    {
        $customer = $this->shopper(customer: ['phone_number' => '+998 90 111 22 33']);

        $this->assertSame('unverified', $customer->phoneStatus());
    }

    public function test_an_unconfirmed_email_is_marked_as_such(): void
    {
        $customer = $this->shopper(['email_verified_at' => null, 'phone_verified_at' => null]);

        $this->assertSame('unverified', $customer->emailStatus());
        $this->assertSame(2, substr_count($this->page($customer)->getContent(), 'Не подтверждён'));
    }

    public function test_an_email_edited_in_the_admin_gets_no_badge(): void
    {
        $customer = $this->shopper(['phone_verified_at' => null], ['email' => 'other@example.com']);

        // Only the phone badge is shown.
        $this->assertSame(1, substr_count($this->page($customer)->getContent(), 'Не подтверждён'));
        $this->page($customer)->assertDontSee('Подтверждён');
    }

    public function test_a_customer_without_a_website_account_gets_no_badges(): void
    {
        $customer = Customer::create([
            'first_name' => 'Aziza', 'last_name' => 'K', 'email' => 'aziza@example.com',
            'phone_number' => '+998 90 123 45 67', 'source' => 'instagram',
        ]);

        $this->assertSame('missing', $customer->emailStatus());
        $this->page($customer)->assertDontSee('Подтверждён')->assertDontSee('Не подтверждён');
    }
}
