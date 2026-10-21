<?php

namespace Tests\Feature;

use App\Filament\Resources\Customers\Pages\CreateCustomer;
use App\Filament\Resources\Customers\Pages\EditCustomer;
use App\Models\Customer;
use App\Models\User;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Livewire\Livewire;
use RuntimeException;
use Tests\TestCase;

class CustomerContactTest extends TestCase
{
    use RefreshDatabase;

    private const MIGRATION = 'database/migrations/2026_10_21_000000_normalize_customer_phones_and_make_email_optional.php';

    private function customer(array $attrs = []): Customer
    {
        return Customer::create(array_merge([
            'first_name' => 'Aziza',
            'last_name' => 'Karimova',
            'source' => 'instagram',
        ], $attrs));
    }

    public function test_email_is_optional(): void
    {
        $a = $this->customer(['phone_number' => '+998 90 111 11 11']);
        $b = $this->customer(['phone_number' => '+998 90 222 22 22']);

        $this->assertNull($a->fresh()->email);
        $this->assertNull($b->fresh()->email);
    }

    public function test_real_emails_stay_unique(): void
    {
        $this->customer(['email' => 'aziza@example.com']);

        $this->expectException(UniqueConstraintViolationException::class);
        $this->customer(['email' => 'aziza@example.com']);
    }

    public function test_phone_is_stored_in_one_format(): void
    {
        $formats = ['901234567', '998901234567', '+998901234567', '+998 (90) 123-45-67', ' 90 123 45 67 '];

        foreach ($formats as $i => $typed) {
            $customer = $this->customer(['phone_number' => $typed]);
            $this->assertSame('+998 90 123 45 67', $customer->fresh()->phone_number, $typed);
            $customer->delete();
        }

        $this->assertSame('+7 999 123 45 67', $this->customer(['phone_number' => ' +7 999 123 45 67 '])->fresh()->phone_number);
        $this->assertNull($this->customer(['phone_number' => '  '])->fresh()->phone_number);
    }

    public function test_the_same_phone_cannot_belong_to_two_customers(): void
    {
        $this->customer(['phone_number' => '+998 90 123 45 67']);

        $this->expectException(UniqueConstraintViolationException::class);
        $this->customer(['phone_number' => '901234567']);
    }

    public function test_phone_lookup_accepts_any_format(): void
    {
        $customer = $this->customer(['phone_number' => '+998 90 123 45 67']);

        $this->assertTrue(Customer::wherePhone('998901234567')->sole()->is($customer));
        $this->assertTrue(Customer::wherePhone('(90) 123-45-67')->sole()->is($customer));
    }

    public function test_admin_form_rejects_a_phone_another_customer_has(): void
    {
        config(['app.env' => 'local']);
        $this->actingAs(User::factory()->create());
        $existing = $this->customer(['phone_number' => '+998 90 123 45 67']);

        Livewire::test(CreateCustomer::class)
            ->fillForm(['first_name' => 'New', 'last_name' => 'One', 'phone_number' => '90 123-45-67', 'source' => 'friend'])
            ->call('create')
            ->assertHasFormErrors(['phone_number']);

        $this->assertSame(1, Customer::count());

        // Saving a customer with their own number is fine, and email can stay empty.
        Livewire::test(EditCustomer::class, ['record' => $existing->getRouteKey()])
            ->fillForm(['first_name' => 'Renamed', 'email' => null])
            ->call('save')
            ->assertHasNoFormErrors();

        $this->assertSame('Renamed', $existing->fresh()->first_name);
    }

    public function test_migration_formats_phones_and_clears_made_up_emails(): void
    {
        $this->artisan('migrate:rollback', ['--path' => self::MIGRATION])->assertSuccessful();

        $insert = fn (string $email, ?string $phone) => DB::table('customers')->insertGetId([
            'first_name' => 'A', 'last_name' => 'B', 'email' => $email, 'phone_number' => $phone,
            'source' => 'instagram', 'created_at' => now(), 'updated_at' => now(),
        ]);
        $site = $insert('client_ab12cd34ef56@mally.uz', '998901234567');
        $bot = $insert('tg_ab12cd34ef56@mally.uz', '+998 91 000 00 00');
        $real = $insert('aziza@example.com', '(93) 555-44-33');
        $noPhone = $insert('nophone@example.com', '');

        $this->artisan('migrate', ['--path' => self::MIGRATION])->assertSuccessful();

        $row = fn (int $id) => DB::table('customers')->find($id);
        $this->assertSame('+998 90 123 45 67', $row($site)->phone_number);
        $this->assertNull($row($site)->email);
        $this->assertNull($row($bot)->email);
        $this->assertSame('+998 93 555 44 33', $row($real)->phone_number);
        $this->assertSame('aziza@example.com', $row($real)->email);
        $this->assertNull($row($noPhone)->phone_number);
    }

    public function test_migration_stops_without_changes_when_customers_share_a_phone(): void
    {
        $this->artisan('migrate:rollback', ['--path' => self::MIGRATION])->assertSuccessful();

        foreach (['first@example.com' => '+998901234567', 'second@example.com' => '90 123 45 67'] as $email => $phone) {
            DB::table('customers')->insert([
                'first_name' => 'A', 'last_name' => 'B', 'email' => $email, 'phone_number' => $phone,
                'source' => 'instagram', 'created_at' => now(), 'updated_at' => now(),
            ]);
        }

        try {
            $this->artisan('migrate', ['--path' => self::MIGRATION]);
            $this->fail('The migration should stop on duplicate phones.');
        } catch (RuntimeException $e) {
            $this->assertStringContainsString('+998 90 123 45 67', $e->getMessage());
        }

        // Nothing was rewritten.
        $this->assertSame(['+998901234567', '90 123 45 67'], DB::table('customers')->orderBy('id')->pluck('phone_number')->all());
    }
}
