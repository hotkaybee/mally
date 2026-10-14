<?php

namespace Tests\Feature;

use App\Livewire\Storefront\HomePage;
use App\Models\Customer;
use App\Services\PhoneVerificationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Log\Events\MessageLogged;
use Illuminate\Support\Facades\Event;
use Livewire\Features\SupportLockedProperties\CannotUpdateLockedPropertyException;
use Livewire\Livewire;
use Tests\TestCase;

class StorefrontAuthTest extends TestCase
{
    use RefreshDatabase;

    private const PHONE = '+998 90 123 45 67';

    private ?string $lastCode = null;

    protected function setUp(): void
    {
        parent::setUp();

        Event::listen(MessageLogged::class, function (MessageLogged $e) {
            if (preg_match('/login code for .*: (\d{6})$/', $e->message, $m)) {
                $this->lastCode = $m[1];
            }
        });
    }

    private function requestCode(string $phone = self::PHONE)
    {
        return Livewire::test(HomePage::class)
            ->call('openAuthModal')
            ->set('authPhone', $phone)
            ->call('sendAuthCode')
            ->assertSet('authStep', 'code');
    }

    private function existingCustomer(array $attrs = []): Customer
    {
        return Customer::create(array_merge([
            'first_name' => 'Old',
            'last_name' => 'Owner',
            'email' => 'old@example.com',
            'phone_number' => '+998901234567',
            'source' => 'friend',
        ], $attrs));
    }

    public function test_correct_code_signs_in_the_existing_customer(): void
    {
        $customer = $this->existingCustomer();

        $this->requestCode()
            ->set('authCode', $this->lastCode)
            ->call('verifyAuthCode')
            ->assertSet('isAuthModalOpen', false);

        $this->assertSame($customer->id, session('mally_customer_id'));
        // Signing in doesn't rewrite the stored phone.
        $this->assertSame('+998901234567', $customer->fresh()->phone_number);
    }

    public function test_old_universal_codes_are_rejected(): void
    {
        $this->existingCustomer();
        $component = $this->requestCode();

        foreach (['7788', '1111'] as $code) {
            $component->set('authCode', $code)->call('verifyAuthCode')->assertSet('authStep', 'code');
        }

        $this->assertNull(session('mally_customer_id'));
    }

    public function test_code_is_never_exposed_to_the_browser(): void
    {
        $component = $this->requestCode();

        $this->assertNotNull($this->lastCode);
        $this->assertStringNotContainsString($this->lastCode, $component->html());
        $this->assertStringNotContainsString($this->lastCode, json_encode($component->snapshot));
    }

    public function test_code_expires(): void
    {
        $this->existingCustomer();
        $component = $this->requestCode();

        $this->travel(PhoneVerificationService::CODE_TTL_SECONDS + 1)->seconds();

        $component->set('authCode', $this->lastCode)->call('verifyAuthCode')->assertSet('authStep', 'phone');
        $this->assertNull(session('mally_customer_id'));
    }

    public function test_code_is_burned_after_too_many_wrong_attempts(): void
    {
        $this->existingCustomer();
        $component = $this->requestCode();
        $wrong = $this->lastCode === '000000' ? '111111' : '000000';

        for ($i = 0; $i < PhoneVerificationService::MAX_ATTEMPTS; $i++) {
            $component->set('authCode', $wrong)->call('verifyAuthCode');
        }

        $component->assertSet('authStep', 'phone');
        $component->set('authCode', $this->lastCode)->call('verifyAuthCode');
        $this->assertNull(session('mally_customer_id'));
    }

    public function test_code_cannot_be_reused(): void
    {
        $this->existingCustomer();
        $this->requestCode()->set('authCode', $this->lastCode)->call('verifyAuthCode');
        $code = $this->lastCode;
        session()->forget('mally_customer_id');

        $this->assertSame(PhoneVerificationService::EXPIRED, app(PhoneVerificationService::class)->verify($code));
    }

    public function test_resend_is_rate_limited(): void
    {
        $this->requestCode();
        $first = $this->lastCode;

        Livewire::test(HomePage::class)
            ->set('authPhone', self::PHONE)
            ->call('sendAuthCode')
            ->assertSet('authStep', 'phone')
            ->assertDispatched('toast');

        $this->assertSame($first, $this->lastCode);
    }

    public function test_auth_step_cannot_be_forced_from_the_browser(): void
    {
        $component = Livewire::test(HomePage::class)->call('openAuthModal');

        $component->call('setAuthStep', 'complete-profile')->assertSet('authStep', 'phone');

        $this->expectException(CannotUpdateLockedPropertyException::class);
        $component->set('authStep', 'complete-profile');
    }

    public function test_registration_requires_a_verified_phone(): void
    {
        Livewire::test(HomePage::class)
            ->set('authPhone', self::PHONE)
            ->set('authFirstName', 'Mallory')
            ->call('completeAuthRegister');

        $this->assertSame(0, Customer::count());
        $this->assertNull(session('mally_customer_id'));
    }

    public function test_new_customer_registers_with_the_verified_phone_only(): void
    {
        $this->requestCode()
            ->set('authCode', $this->lastCode)
            ->call('verifyAuthCode')
            ->assertSet('authStep', 'complete-profile')
            // Tampering with the phone after verification must not matter.
            ->set('authPhone', '+998 99 999 99 99')
            ->set('authFirstName', 'Dilnoza')
            ->set('authAddress', 'Amir Temur 1')
            ->call('completeAuthRegister');

        $customer = Customer::sole();
        $this->assertSame(self::PHONE, $customer->phone_number);
        $this->assertSame('Ташкент', $customer->city);
        $this->assertSame('Amir Temur 1', $customer->address);
        $this->assertNull($customer->instagram);
        $this->assertSame($customer->id, session('mally_customer_id'));
    }

    public function test_registration_is_rejected_when_the_phone_already_has_an_account(): void
    {
        $component = $this->requestCode()
            ->set('authCode', $this->lastCode)
            ->call('verifyAuthCode')
            ->assertSet('authStep', 'complete-profile');

        // Someone else registered the same number in the meantime.
        $existing = $this->existingCustomer(['phone_number' => '901234567']);

        $component->set('authFirstName', 'Intruder')->call('completeAuthRegister')->assertSet('authStep', 'phone');

        $this->assertSame(1, Customer::count());
        $this->assertSame('Old', $existing->fresh()->first_name);
        $this->assertNull(session('mally_customer_id'));
    }

    public function test_phone_login_is_unavailable_without_an_sms_provider_in_production(): void
    {
        $this->app['env'] = 'production';

        Livewire::test(HomePage::class)
            ->set('authPhone', self::PHONE)
            ->call('sendAuthCode')
            ->assertSet('authStep', 'phone')
            ->assertDispatched('toast');

        $this->assertNull($this->lastCode);
    }
}
