<?php

namespace Tests\Feature;

use App\Livewire\Storefront\ProfilePage;
use App\Models\User;
use App\Services\CustomerAccountService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * The profile must tell the truth about what is confirmed. It used to show
 * "Confirmed" next to the phone and "Device linked" for everybody, also for
 * accounts that have no phone at all (Google sign-ups).
 *
 * Phone: confirmed only through Telegram ("Continue with Telegram" until then).
 * Email: confirmed by Google or an email code ("Continue with Google" while empty).
 */
class ProfileAccountStatusTest extends TestCase
{
    use RefreshDatabase;

    private const PHONE = '+998 90 123 45 67';

    private const MIGRATION = 'database/migrations/2026_11_01_000000_add_google_auth_to_users_table.php';

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
        $user->forceFill(['email_verified_at' => array_key_exists('email_verified_at', $attrs) ? $attrs['email_verified_at'] : now()])->save();

        app(CustomerAccountService::class)->login($user);

        return $user;
    }

    private function profile()
    {
        return Livewire::test(ProfilePage::class)->set('activeTab', 'details');
    }

    // ----------------------------------------------------------------- phone

    public function test_an_account_without_a_phone_is_not_shown_as_confirmed(): void
    {
        $this->signIn();

        $this->profile()
            ->assertSeeHtml('data-phone-status="missing"')
            ->assertDontSeeHtml('data-phone-status="verified"')
            ->assertDontSee('Устройство привязано');
    }

    public function test_an_account_without_a_phone_gets_one_big_telegram_button_instead_of_a_field(): void
    {
        $this->signIn();

        $this->profile()
            ->assertSeeHtml('wire:click="openLinkTelegram"')
            ->assertSee('Продолжить с Telegram')
            ->assertDontSeeHtml('data-phone-field');
    }

    public function test_a_phone_confirmed_through_telegram_is_shown_with_a_check_and_no_button(): void
    {
        $this->signIn(['phone' => self::PHONE, 'telegram_id' => '555', 'phone_verified_at' => now()]);

        $this->profile()
            ->assertSeeHtml('data-phone-status="verified"')
            ->assertSeeHtml('data-phone-field')
            ->assertSee(self::PHONE)
            ->assertSee('Устройство привязано')
            ->assertDontSeeHtml('wire:click="openLinkTelegram"');
    }

    public function test_a_phone_typed_at_registration_is_not_confirmed_and_offers_telegram(): void
    {
        $this->signIn(['phone' => self::PHONE]);

        $this->profile()
            ->assertSeeHtml('data-phone-status="unverified"')
            ->assertDontSeeHtml('data-phone-status="verified"')
            ->assertSee(self::PHONE)
            ->assertSeeHtml('wire:click="openLinkTelegram"')
            ->assertDontSee('Устройство привязано');
    }

    public function test_a_phone_that_was_changed_after_confirmation_is_not_shown_as_confirmed(): void
    {
        $user = $this->signIn(['phone' => self::PHONE, 'telegram_id' => '555', 'phone_verified_at' => now()]);
        // The customer row carries a different number than the confirmed one.
        $user->customer()->update(['phone_number' => '+998 91 000 00 00']);
        app(CustomerAccountService::class)->login($user->refresh());

        $this->profile()->assertDontSeeHtml('data-phone-status="verified"');
    }

    public function test_telegram_without_a_confirmed_phone_still_offers_to_add_the_phone(): void
    {
        $this->signIn(['telegram_id' => '555']);

        $this->profile()
            ->assertSeeHtml('data-phone-status="missing"')
            ->assertSeeHtml('wire:click="openLinkTelegram"');
    }

    // ----------------------------------------------------------------- email

    public function test_a_confirmed_email_is_shown_with_a_check_and_no_google_button(): void
    {
        $this->signIn();

        $this->profile()
            ->assertSeeHtml('data-email-status="verified"')
            ->assertSeeHtml('data-email-field')
            ->assertSee('ali@gmail.com')
            ->assertDontSee('Продолжить с Google');
    }

    public function test_an_unconfirmed_email_is_marked_as_such(): void
    {
        $this->signIn(['email_verified_at' => null]);

        $this->profile()
            ->assertSeeHtml('data-email-status="unverified"')
            ->assertSee('ali@gmail.com')
            ->assertSee('Не подтверждён');
    }

    public function test_an_account_without_an_email_gets_one_big_google_button_instead_of_a_field(): void
    {
        $this->signIn(['email' => null, 'phone' => self::PHONE, 'telegram_id' => '555', 'phone_verified_at' => now()]);

        $this->profile()
            ->assertSeeHtml('data-email-status="missing"')
            ->assertSee('Продолжить с Google')
            ->assertSeeHtml('href="'.e(route('auth.google', ['link' => 1, 'redirect' => '/profile'])).'"')
            ->assertDontSeeHtml('data-email-field')
            ->assertSeeHtml('data-phone-status="verified"');
    }

    // --------------------------------------------------------------- the rest

    public function test_the_personal_data_form_has_name_surname_phone_and_email(): void
    {
        $this->signIn();

        $this->profile()
            ->assertSeeHtml('wire:model="firstName"')
            ->assertSeeHtml('wire:model="lastName"')
            ->assertSeeHtml('data-phone-status=')
            ->assertSeeHtml('data-email-status=');
    }

    public function test_no_password_is_ever_asked_in_the_profile(): void
    {
        $this->signIn();

        $this->profile()
            ->assertDontSeeHtml('wire:click="openSetPassword"')
            ->assertDontSeeHtml('wire:model="authPassword"');
    }

    public function test_migration_marks_emails_of_existing_shoppers_as_confirmed(): void
    {
        $this->artisan('migrate:rollback', ['--path' => self::MIGRATION])->assertSuccessful();

        $insert = fn (?string $email, bool $admin = false) => DB::table('users')->insertGetId([
            'name' => 'A', 'email' => $email, 'password' => 'x', 'is_admin' => $admin,
            'created_at' => '2026-10-20 10:00:00', 'updated_at' => now(),
        ]);
        $shopper = $insert('shopper@example.com');
        $admin = $insert('admin@example.com', true);
        $noEmail = $insert(null);

        $this->artisan('migrate', ['--path' => self::MIGRATION])->assertSuccessful();

        $verifiedAt = fn (int $id) => DB::table('users')->where('id', $id)->value('email_verified_at');
        $this->assertSame('2026-10-20 10:00:00', $verifiedAt($shopper));
        $this->assertNull($verifiedAt($admin));
        $this->assertNull($verifiedAt($noEmail));
    }
}
