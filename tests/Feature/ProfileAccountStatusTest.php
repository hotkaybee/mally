<?php

namespace Tests\Feature;

use App\Livewire\Storefront\ProfilePage;
use App\Models\User;
use App\Services\CustomerAccountService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * The profile must tell the truth about what is confirmed. It used to show
 * "Confirmed" next to the phone and "Device linked" for everybody, also for
 * accounts that have no phone at all (Google sign-ups).
 */
class ProfileAccountStatusTest extends TestCase
{
    use RefreshDatabase;

    private const PHONE = '+998 90 123 45 67';

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
        app()->setLocale('ru');
        session(['mally_locale' => 'ru']);
    }

    private function signIn(array $attrs = []): User
    {
        $user = new User(array_merge([
            'name' => 'Muhammadali Botirov',
            'email' => 'ali@gmail.com',
        ], $attrs));
        $user->forceFill(['email_verified_at' => $attrs['email_verified_at'] ?? now()])->save();

        app(CustomerAccountService::class)->login($user);

        return $user;
    }

    private function profile()
    {
        return Livewire::test(ProfilePage::class)->set('activeTab', 'details');
    }

    public function test_an_account_without_a_phone_is_not_shown_as_confirmed(): void
    {
        $this->signIn();

        $this->profile()
            ->assertSeeHtml('data-phone-status="missing"')
            ->assertDontSeeHtml('data-phone-status="verified"')
            ->assertDontSee('Устройство привязано');
    }

    public function test_an_account_without_a_phone_is_offered_telegram_to_add_and_confirm_one(): void
    {
        $this->signIn();

        $this->profile()
            ->assertSee('Добавьте Telegram, чтобы подтвердить номер телефона.')
            ->assertSeeHtml('wire:click="openLinkTelegram"');
    }

    public function test_a_phone_confirmed_through_telegram_is_shown_as_confirmed(): void
    {
        $this->signIn(['phone' => self::PHONE, 'telegram_id' => '555', 'phone_verified_at' => now()]);

        $this->profile()
            ->assertSeeHtml('data-phone-status="verified"')
            ->assertSee(self::PHONE)
            ->assertSee('Устройство привязано');
    }

    public function test_a_phone_typed_at_registration_is_not_shown_as_confirmed(): void
    {
        $this->signIn(['phone' => self::PHONE]);

        $this->profile()
            ->assertSeeHtml('data-phone-status="unverified"')
            ->assertDontSeeHtml('data-phone-status="verified"')
            ->assertDontSee('Устройство привязано');
    }

    public function test_a_phone_that_was_changed_after_confirmation_is_not_shown_as_confirmed(): void
    {
        $user = $this->signIn(['phone' => self::PHONE, 'telegram_id' => '555', 'phone_verified_at' => now()]);
        // The customer row carries a different number than the confirmed one.
        $user->customer()->update(['phone_number' => '+998 91 000 00 00']);
        app(CustomerAccountService::class)->login($user->refresh());

        $this->profile()
            ->assertDontSeeHtml('data-phone-status="verified"');
    }

    public function test_the_email_status_follows_email_verified_at(): void
    {
        $this->signIn();
        $this->profile()->assertSeeHtml('data-email-status="verified"')->assertSee('ali@gmail.com');

        $user = User::sole();
        $user->forceFill(['email_verified_at' => null])->save();
        app(CustomerAccountService::class)->login($user->refresh());

        $this->profile()->assertSeeHtml('data-email-status="unverified"');
    }

    public function test_an_account_without_an_email_shows_no_email_line(): void
    {
        $this->signIn(['email' => null, 'phone' => self::PHONE, 'telegram_id' => '555', 'phone_verified_at' => now()]);

        $this->profile()
            ->assertDontSeeHtml('data-email-status')
            ->assertSeeHtml('data-phone-status="verified"');
    }
}
