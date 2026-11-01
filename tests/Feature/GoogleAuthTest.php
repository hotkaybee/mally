<?php

namespace Tests\Feature;

use App\Livewire\Storefront\HomePage;
use App\Livewire\Storefront\ProfilePage;
use App\Models\Customer;
use App\Models\TelegramLoginRequest;
use App\Models\User;
use App\Services\CustomerAccountService;
use App\Services\EmailAuthService;
use App\Services\GoogleAuthService;
use App\Services\TelegramAuthService;
use Exception;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Testing\TestResponse;
use Laravel\Socialite\Facades\Socialite;
use Laravel\Socialite\Two\GoogleProvider;
use Laravel\Socialite\Two\User as SocialiteUser;
use Livewire\Livewire;
use Mockery;
use Tests\TestCase;

class GoogleAuthTest extends TestCase
{
    use RefreshDatabase;

    private const TG_ID = '777000222';

    private const PHONE = '+998 90 123 45 67';

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
        Cache::flush();
        RateLimiter::clear('tg-login-start:127.0.0.1');
        app()->setLocale('ru');
        session(['mally_locale' => 'ru']);

        config([
            'services.google.client_id' => 'test-client-id',
            'services.google.client_secret' => 'test-client-secret',
            'services.telegram.auth_bot_token' => 'TEST_BOT_TOKEN',
            'services.telegram.auth_bot_username' => 'mally_test_bot',
            'services.telegram.auth_webhook_secret' => 'whsec',
        ]);

        // No test touches the network.
        Http::fake(['api.telegram.org/*' => Http::response(['ok' => true])]);
    }

    // ------------------------------------------------------------- helpers

    /** What Google returns for the signed-in person (OpenID userinfo). */
    private function googleProfile(array $override = []): array
    {
        return array_merge([
            'sub' => 'google-sub-1',
            'email' => 'malika@gmail.com',
            'email_verified' => true,
            'given_name' => 'Malika',
            'family_name' => 'Karimova',
            'name' => 'Malika Karimova',
        ], $override);
    }

    private function fakeGoogleReturns(array $raw): void
    {
        $user = (new SocialiteUser)->setRaw($raw)->map([
            'id' => $raw['sub'],
            'name' => $raw['name'] ?? null,
            'email' => $raw['email'] ?? null,
        ]);

        $provider = Mockery::mock(GoogleProvider::class);
        $provider->shouldReceive('user')->andReturn($user);
        Socialite::shouldReceive('driver')->with('google')->andReturn($provider);
    }

    private function googleCallback(array $raw): TestResponse
    {
        $this->fakeGoogleReturns($raw);

        return $this->get('/auth/google/callback?code=auth-code&state=state');
    }

    private function emailUser(array $attrs = []): User
    {
        return User::create(array_merge([
            'name' => 'Gmail Owner',
            'email' => 'malika@gmail.com',
            'password' => 'OwnerPass123',
        ], $attrs));
    }

    /** A new Google account that is already signed in (the state after the callback). */
    private function signedInGoogleUser(): User
    {
        $res = app(CustomerAccountService::class)->resolveGoogle([
            'sub' => 'google-sub-1',
            'email' => 'malika@gmail.com',
            'email_verified' => true,
            'first_name' => 'Malika',
            'last_name' => 'Karimova',
        ]);
        app(CustomerAccountService::class)->login($res['user']);

        return $res['user'];
    }

    private function tokenFrom($component): string
    {
        preg_match('/start=login_([0-9a-f]+)$/', $component->get('authTelegramBotUrl'), $m);

        return $m[1];
    }

    private function bot(array $message): void
    {
        app(TelegramAuthService::class)->handleUpdate(['update_id' => random_int(1, 1_000_000), 'message' => array_merge([
            'chat' => ['id' => (int) self::TG_ID, 'type' => 'private'],
            'from' => ['id' => (int) self::TG_ID, 'is_bot' => false, 'first_name' => 'Malika', 'username' => 'malika_tg'],
        ], $message)]);
    }

    private function codeSentByBot(): ?string
    {
        $code = null;
        Http::recorded(function (Request $request) use (&$code) {
            if (str_contains($request->url(), '/sendMessage') && preg_match('/Mally: (\d{6})/', (string) ($request['text'] ?? ''), $m)) {
                $code = $m[1];
            }

            return true;
        });

        return $code;
    }

    /** Signed in, on the "add Telegram" offer, bot already started. */
    private function telegramLinkStarted(User $user)
    {
        $component = Livewire::test(HomePage::class)
            ->call('openLinkTelegram')
            ->assertSet('authStep', 'offer-telegram')
            ->call('startTelegramLink')
            ->assertSet('authStep', 'telegram-wait')
            ->assertSet('authTelegramPurpose', 'link');

        $this->bot(['text' => '/start login_'.$this->tokenFrom($component)]);

        return $component;
    }

    // ----------------------------------------------------- redirect to Google

    public function test_sign_in_and_register_windows_have_the_google_button(): void
    {
        Livewire::test(HomePage::class)
            ->call('openAuthModal')
            ->assertSeeHtml('href="'.route('auth.google').'"')
            ->call('openRegister')
            ->assertSet('authStep', 'register-email')
            ->assertSeeHtml('href="'.route('auth.google').'"');
    }

    public function test_redirect_asks_google_for_openid_email_profile_and_keeps_a_safe_return_path(): void
    {
        $provider = Mockery::mock(GoogleProvider::class);
        $provider->shouldReceive('scopes')->with(['openid', 'email', 'profile'])->once()->andReturnSelf();
        $provider->shouldReceive('with')->with(['prompt' => 'select_account'])->once()->andReturnSelf();
        $provider->shouldReceive('redirect')->once()->andReturn(redirect('https://accounts.google.com/o/oauth2/auth'));
        Socialite::shouldReceive('driver')->with('google')->andReturn($provider);

        $this->get('/auth/google?redirect=/cart')
            ->assertRedirect('https://accounts.google.com/o/oauth2/auth');

        $this->assertSame('/cart', session(GoogleAuthService::SESSION_REDIRECT));
    }

    public function test_an_external_return_path_is_dropped(): void
    {
        $provider = Mockery::mock(GoogleProvider::class);
        $provider->shouldReceive('scopes')->andReturnSelf();
        $provider->shouldReceive('with')->andReturnSelf();
        $provider->shouldReceive('redirect')->andReturn(redirect('https://accounts.google.com/o/oauth2/auth'));
        Socialite::shouldReceive('driver')->with('google')->andReturn($provider);

        $this->get('/auth/google?redirect=https://evil.example/steal')->assertRedirect();

        $this->assertSame('/', session(GoogleAuthService::SESSION_REDIRECT));
    }

    public function test_without_google_credentials_the_button_says_it_is_unavailable(): void
    {
        config(['services.google.client_id' => '', 'services.google.client_secret' => '']);
        Socialite::shouldReceive('driver')->never();

        $this->get('/auth/google')->assertRedirect(route('home'));
        $this->assertSame('Вход через Google временно недоступен.', session('mally_toast.message'));

        $this->get('/auth/google/callback?code=x&state=y')->assertRedirect(route('home'));
        $this->assertSame(0, User::count());
    }

    // --------------------------------------------------------- the callback

    public function test_new_google_user_gets_an_account_verified_email_and_is_signed_in(): void
    {
        session([GoogleAuthService::SESSION_REDIRECT => '/cart']);

        $this->googleCallback($this->googleProfile())->assertRedirect('/cart');

        $user = User::sole();
        $this->assertSame('malika@gmail.com', $user->email);
        $this->assertSame('google-sub-1', $user->google_id);
        $this->assertNotNull($user->email_verified_at, 'Google confirmed the email: no code or letter needed.');
        $this->assertNull($user->password, 'A Google account has no local password until the owner sets one.');
        $this->assertFalse($user->is_admin);
        $this->assertSame('Malika Karimova', $user->name);

        $customer = Customer::sole();
        $this->assertSame($user->id, $customer->user_id);
        $this->assertSame('Malika', $customer->first_name);
        $this->assertSame('Karimova', $customer->last_name);
        $this->assertSame('malika@gmail.com', $customer->email);

        $this->assertSame($customer->id, session('mally_customer_id'));
        $this->assertSame('onboarding', session(GoogleAuthService::SESSION_OPEN));
    }

    public function test_signing_in_again_does_not_create_a_duplicate_or_ask_for_onboarding_again(): void
    {
        $this->googleCallback($this->googleProfile())->assertRedirect();
        session()->flush();
        session(['mally_locale' => 'ru']);

        // The person changed their Google name and email: still the same account (sub).
        $this->googleCallback($this->googleProfile(['email' => 'new.address@gmail.com', 'given_name' => 'Other']))->assertRedirect();

        $this->assertSame(1, User::count());
        $this->assertSame(1, Customer::count());
        $this->assertSame('malika@gmail.com', User::sole()->email);
        $this->assertNotNull(session('mally_customer_id'));
        $this->assertNull(session(GoogleAuthService::SESSION_OPEN));
    }

    public function test_cancelling_on_the_google_screen_signs_nobody_in(): void
    {
        Socialite::shouldReceive('driver')->never();

        $this->get('/auth/google/callback?error=access_denied&state=s')->assertRedirect('/');

        $this->assertSame(0, User::count());
        $this->assertNull(session('mally_customer_id'));
        $this->assertSame('Вход через Google отменён.', session('mally_toast.message'));
    }

    public function test_opening_the_callback_without_a_code_does_nothing(): void
    {
        Socialite::shouldReceive('driver')->never();

        $this->get('/auth/google/callback')->assertRedirect('/');

        $this->assertSame(0, User::count());
    }

    public function test_a_failed_token_exchange_shows_a_generic_message_not_the_exception(): void
    {
        $provider = Mockery::mock(GoogleProvider::class);
        $provider->shouldReceive('user')->andThrow(new Exception('client_secret=SECRET invalid_grant'));
        Socialite::shouldReceive('driver')->with('google')->andReturn($provider);

        $this->get('/auth/google/callback?code=bad&state=s')->assertRedirect('/');

        $this->assertSame(0, User::count());
        $toast = session('mally_toast.message');
        $this->assertSame('Не удалось войти через Google. Попробуйте ещё раз.', $toast);
        $this->assertStringNotContainsString('SECRET', $toast);
    }

    public function test_an_unconfirmed_google_email_creates_no_account_and_signs_nobody_in(): void
    {
        $this->googleCallback($this->googleProfile(['email_verified' => false]))->assertRedirect('/');

        $this->assertSame(0, User::count());
        $this->assertSame(0, Customer::count());
        $this->assertNull(session('mally_customer_id'));
        $this->assertStringContainsString('не подтвердил', (string) session('mally_toast.message'));
    }

    public function test_an_unconfirmed_google_email_never_takes_over_the_account_with_that_email(): void
    {
        $owner = $this->emailUser();

        $this->googleCallback($this->googleProfile(['email_verified' => false]))->assertRedirect('/');

        $this->assertNull($owner->fresh()->google_id);
        $this->assertNull(session('mally_customer_id'));
        $this->assertNull(session(GoogleAuthService::SESSION_LINK));
    }

    public function test_email_verified_as_the_string_false_is_not_verified(): void
    {
        $this->googleCallback($this->googleProfile(['email_verified' => 'false']))->assertRedirect('/');

        $this->assertSame(0, User::count());
    }

    public function test_email_verified_as_the_string_true_is_verified(): void
    {
        $this->googleCallback($this->googleProfile(['email_verified' => 'true']))->assertRedirect('/');

        $this->assertNotNull(User::sole()->email_verified_at);
    }

    public function test_an_account_is_found_by_sub_never_by_email(): void
    {
        $this->emailUser(['email' => 'someone.else@gmail.com']);

        // A different Google account that merely claims the email is not that user.
        $this->googleCallback($this->googleProfile(['sub' => 'attacker-sub', 'email' => 'someone.else@gmail.com']))->assertRedirect('/');

        $this->assertNull(session('mally_customer_id'));
        $this->assertNull(User::where('email', 'someone.else@gmail.com')->value('google_id'));
    }

    // ------------------------------------------- email already has an account

    public function test_a_matching_email_is_not_merged_automatically(): void
    {
        $owner = $this->emailUser();

        $this->googleCallback($this->googleProfile())->assertRedirect('/');

        $this->assertSame(1, User::count());
        $this->assertNull($owner->fresh()->google_id, 'No silent merge.');
        $this->assertNull(session('mally_customer_id'), 'Not signed in until the owner proves the account is theirs.');
        $this->assertSame('link', session(GoogleAuthService::SESSION_OPEN));
        $this->assertSame($owner->id, session(GoogleAuthService::SESSION_LINK)['user_id']);
    }

    public function test_the_owner_links_google_with_the_account_password(): void
    {
        $owner = $this->emailUser();
        $this->googleCallback($this->googleProfile())->assertRedirect('/');

        Livewire::test(HomePage::class)
            ->assertSet('isAuthModalOpen', true)
            ->assertSet('authStep', 'google-link')
            ->assertSet('authEmail', 'malika@gmail.com')
            ->set('authPassword', 'WrongPass123')
            ->call('linkGoogleWithPassword')
            ->assertHasErrors('authPassword')
            ->assertSet('authStep', 'google-link');

        $this->assertNull($owner->fresh()->google_id, 'A wrong password links nothing.');
        $this->assertNull(session('mally_customer_id'));

        Livewire::test(HomePage::class)
            ->call('openAuthModal')
            ->assertSet('authStep', 'login');

        // A fresh page load re-reads the pending link only once (flash), so open it directly.
        session([GoogleAuthService::SESSION_OPEN => 'link']);
        Livewire::test(HomePage::class)
            ->assertSet('authStep', 'google-link')
            ->set('authPassword', 'OwnerPass123')
            ->call('linkGoogleWithPassword')
            ->assertSet('isAuthModalOpen', false);

        $owner->refresh();
        $this->assertSame('google-sub-1', $owner->google_id);
        $this->assertNotNull($owner->email_verified_at);
        $this->assertSame(1, User::count());
        $this->assertNotNull(session('mally_customer_id'));
        $this->assertNull(session(GoogleAuthService::SESSION_LINK), 'The pending link is single-use.');

        // From now on Google signs in by sub, without the password.
        session()->flush();
        $this->googleCallback($this->googleProfile())->assertRedirect('/');
        $this->assertNotNull(session('mally_customer_id'));
        $this->assertSame(1, User::count());
    }

    public function test_linking_is_rate_limited_like_any_password_attempt(): void
    {
        $this->emailUser();
        $this->googleCallback($this->googleProfile())->assertRedirect('/');

        $component = Livewire::test(HomePage::class)->assertSet('authStep', 'google-link');
        for ($i = 0; $i < 12; $i++) {
            $component->set('authPassword', 'Wrong'.$i.'Pass')->call('linkGoogleWithPassword');
        }

        // Even the right password is refused once the limit is hit.
        $component->set('authPassword', 'OwnerPass123')->call('linkGoogleWithPassword')
            ->assertHasErrors('authPassword');
        $this->assertNull(User::sole()->google_id);
    }

    public function test_the_pending_link_expires(): void
    {
        $owner = $this->emailUser();
        $this->googleCallback($this->googleProfile())->assertRedirect('/');

        $this->travel(GoogleAuthService::LINK_TTL_MINUTES + 1)->minutes();

        Livewire::test(HomePage::class)->assertSet('authStep', 'login');
        $this->assertNull($owner->fresh()->google_id);
    }

    public function test_an_admin_email_is_never_linked_or_signed_in(): void
    {
        $admin = User::factory()->admin()->create(['email' => 'malika@gmail.com']);

        $this->googleCallback($this->googleProfile())->assertRedirect('/');

        $this->assertNull($admin->fresh()->google_id);
        $this->assertNull(session('mally_customer_id'));
        $this->assertNull(session(GoogleAuthService::SESSION_LINK));
        $this->assertSame(1, User::count());
    }

    public function test_an_account_already_linked_to_another_google_cannot_be_linked_again(): void
    {
        $this->emailUser(['google_id' => 'someone-elses-sub']);

        $this->googleCallback($this->googleProfile())->assertRedirect('/');

        $this->assertNull(session(GoogleAuthService::SESSION_LINK));
        $this->assertNull(session('mally_customer_id'));
        $this->assertSame('someone-elses-sub', User::sole()->google_id);
    }

    public function test_linking_refuses_a_different_email_than_the_account_has(): void
    {
        $owner = $this->emailUser();

        $result = app(CustomerAccountService::class)->linkGoogle($owner, 'google-sub-1', 'other@gmail.com');

        $this->assertSame(CustomerAccountService::GOOGLE_EMAIL_TAKEN, $result);
        $this->assertNull($owner->fresh()->google_id);
    }

    // ------------------------------------------ optional password after Google

    public function test_a_new_google_user_is_offered_a_password_and_can_set_it(): void
    {
        $user = $this->signedInGoogleUser();
        session([GoogleAuthService::SESSION_OPEN => 'onboarding']);

        Livewire::test(HomePage::class)
            ->assertSet('isAuthModalOpen', true)
            ->assertSet('authStep', 'set-password')
            ->assertSeeHtml('wire:model="authPassword"')
            ->assertSeeHtml('wire:model="authPasswordConfirmation"')
            ->assertSeeHtml('wire:click="skipSetPassword"')
            ->set('authPassword', 'GoodPass1234')
            ->set('authPasswordConfirmation', 'GoodPass1234')
            ->call('setAccountPassword')
            ->assertHasNoErrors()
            ->assertSet('authStep', 'offer-telegram');

        $user->refresh();
        $this->assertTrue($user->hasPassword());
        $this->assertNotSame('GoodPass1234', $user->password, 'Only a hash is stored.');
        $this->assertTrue(Hash::check('GoodPass1234', $user->password));
        $this->assertTrue(app(EmailAuthService::class)->attemptLogin('malika@gmail.com', 'GoodPass1234', '10.1.1.1')['success']);
        $this->assertNotNull(session('mally_customer_id'), 'Setting the password keeps the customer signed in.');
    }

    public function test_the_password_must_match_and_be_long_enough(): void
    {
        $user = $this->signedInGoogleUser();
        session([GoogleAuthService::SESSION_OPEN => 'onboarding']);

        Livewire::test(HomePage::class)
            ->set('authPassword', 'GoodPass1234')
            ->set('authPasswordConfirmation', 'Different1234')
            ->call('setAccountPassword')
            ->assertHasErrors('authPasswordConfirmation')
            ->assertSet('authStep', 'set-password')
            ->set('authPassword', 'short')
            ->set('authPasswordConfirmation', 'short')
            ->call('setAccountPassword')
            ->assertHasErrors('authPassword')
            ->assertSet('authStep', 'set-password');

        $this->assertFalse($user->fresh()->hasPassword());
    }

    public function test_skipping_the_password_and_telegram_leaves_a_working_google_account(): void
    {
        $user = $this->signedInGoogleUser();
        session([GoogleAuthService::SESSION_OPEN => 'onboarding']);

        Livewire::test(HomePage::class)
            ->assertSet('authStep', 'set-password')
            ->call('skipSetPassword')
            ->assertSet('authStep', 'offer-telegram')
            ->assertSet('isAuthModalOpen', true)
            ->call('skipTelegramOffer')
            ->assertSet('isAuthModalOpen', false);

        $user->refresh();
        $this->assertFalse($user->hasPassword());
        $this->assertNull($user->telegram_id);

        // The account still works through Google alone.
        session()->flush();
        $this->googleCallback($this->googleProfile())->assertRedirect('/');
        $this->assertSame($user->id, User::sole()->id);
        $this->assertNotNull(session('mally_customer_id'));
    }

    public function test_a_google_account_without_password_cannot_be_entered_with_an_empty_or_guessed_password(): void
    {
        $this->signedInGoogleUser();
        $auth = app(EmailAuthService::class);

        $this->assertFalse($auth->attemptLogin('malika@gmail.com', '', '10.1.1.2')['success']);
        $this->assertFalse($auth->attemptLogin('malika@gmail.com', 'anything12345', '10.1.1.2')['success']);
    }

    public function test_setting_a_password_is_only_possible_for_an_account_that_has_none(): void
    {
        $user = $this->signedInGoogleUser();
        $user->update(['password' => 'ExistingPass123']);
        app(CustomerAccountService::class)->login($user->refresh());

        Livewire::test(HomePage::class)
            ->set('authPassword', 'ReplacedPass123')
            ->set('authPasswordConfirmation', 'ReplacedPass123')
            ->call('setAccountPassword');

        $this->assertTrue(Hash::check('ExistingPass123', $user->fresh()->password), 'No password change without proving the old one.');
    }

    public function test_nobody_signed_out_can_set_a_password_or_see_the_onboarding(): void
    {
        $user = User::create(['name' => 'X', 'email' => 'x@gmail.com', 'google_id' => 'sub-x', 'password' => null]);
        session([GoogleAuthService::SESSION_OPEN => 'onboarding']);

        Livewire::test(HomePage::class)
            ->assertSet('isAuthModalOpen', false)
            ->set('authPassword', 'GoodPass1234')
            ->set('authPasswordConfirmation', 'GoodPass1234')
            ->call('setAccountPassword');

        $this->assertFalse($user->fresh()->hasPassword());
    }

    // --------------------------------------------- optional Telegram after Google

    public function test_telegram_can_be_added_to_the_profile_through_the_bot(): void
    {
        $user = $this->signedInGoogleUser();
        $component = $this->telegramLinkStarted($user);

        // The code arrives at once: the phone is not needed to link.
        $component->call('checkTelegramAuthStatus')->assertSet('authStep', 'telegram-code');

        $component->set('authTelegramCode', $this->codeSentByBot())
            ->call('verifyTelegramCode')
            ->assertSet('isAuthModalOpen', false);

        $user->refresh();
        $this->assertSame(self::TG_ID, $user->telegram_id);
        $this->assertNull($user->phone, 'Telegram alone does not give a phone number.');
        $this->assertNull($user->phone_verified_at);
        $this->assertSame($user->id, User::sole()->id, 'Still the same account, nothing created.');
        $this->assertSame('@malika_tg', Customer::sole()->telegram);
    }

    public function test_a_phone_shared_in_the_bot_is_saved_and_confirmed(): void
    {
        $user = $this->signedInGoogleUser();
        $component = $this->telegramLinkStarted($user);

        $this->bot(['contact' => ['phone_number' => self::PHONE, 'user_id' => (int) self::TG_ID, 'first_name' => 'Malika']]);

        $component->call('checkTelegramAuthStatus')->assertSet('authStep', 'telegram-code')
            ->set('authTelegramCode', $this->codeSentByBot())
            ->call('verifyTelegramCode')
            ->assertSet('isAuthModalOpen', false);

        $user->refresh();
        $this->assertSame(self::TG_ID, $user->telegram_id);
        $this->assertSame(self::PHONE, $user->phone);
        $this->assertNotNull($user->phone_verified_at);
        $this->assertSame(self::PHONE, Customer::sole()->phone_number);
    }

    public function test_somebody_elses_contact_does_not_add_a_phone(): void
    {
        $user = $this->signedInGoogleUser();
        $component = $this->telegramLinkStarted($user);

        $this->bot(['contact' => ['phone_number' => self::PHONE, 'user_id' => 999, 'first_name' => 'Other']]);

        $component->call('checkTelegramAuthStatus')
            ->set('authTelegramCode', $this->codeSentByBot())
            ->call('verifyTelegramCode');

        $user->refresh();
        $this->assertSame(self::TG_ID, $user->telegram_id);
        $this->assertNull($user->phone);
    }

    public function test_a_phone_that_belongs_to_another_account_is_skipped_but_telegram_is_still_linked(): void
    {
        User::create(['name' => 'Phone Owner', 'email' => 'po@gmail.com', 'phone' => self::PHONE, 'password' => 'OwnerPass123']);
        $user = $this->signedInGoogleUser();
        $component = $this->telegramLinkStarted($user);

        $this->bot(['contact' => ['phone_number' => self::PHONE, 'user_id' => (int) self::TG_ID, 'first_name' => 'Malika']]);

        $component->call('checkTelegramAuthStatus')
            ->set('authTelegramCode', $this->codeSentByBot())
            ->call('verifyTelegramCode')
            ->assertSet('isAuthModalOpen', false);

        $user->refresh();
        $this->assertSame(self::TG_ID, $user->telegram_id);
        $this->assertNull($user->phone, 'Accounts are never merged through a shared phone.');
    }

    public function test_a_telegram_linked_to_another_profile_is_refused(): void
    {
        User::create(['name' => 'Other', 'email' => 'o@gmail.com', 'telegram_id' => self::TG_ID, 'password' => 'OwnerPass123']);
        $user = $this->signedInGoogleUser();
        $component = $this->telegramLinkStarted($user);

        $component->call('checkTelegramAuthStatus')
            ->set('authTelegramCode', $this->codeSentByBot())
            ->call('verifyTelegramCode')
            ->assertSet('isAuthModalOpen', false)
            ->assertDispatched('toast');

        $this->assertNull($user->fresh()->telegram_id);
        // And nobody was signed in as the other profile.
        $this->assertSame($user->customer()->value('id'), session('mally_customer_id'));
    }

    public function test_an_expired_telegram_link_is_not_accepted(): void
    {
        $user = $this->signedInGoogleUser();
        $component = $this->telegramLinkStarted($user);
        $component->call('checkTelegramAuthStatus')->assertSet('authStep', 'telegram-code');
        $code = $this->codeSentByBot();

        TelegramLoginRequest::query()->update(['expires_at' => now()->subMinute()]);

        $component->set('authTelegramCode', $code)
            ->call('verifyTelegramCode')
            ->assertSet('authStep', 'telegram-wait')
            ->assertHasErrors('authTelegram');

        $this->assertNull($user->fresh()->telegram_id);
    }

    public function test_a_wrong_telegram_code_links_nothing(): void
    {
        $user = $this->signedInGoogleUser();
        $component = $this->telegramLinkStarted($user);
        $component->call('checkTelegramAuthStatus');

        $component->set('authTelegramCode', '000000')
            ->call('verifyTelegramCode')
            ->assertHasErrors('authTelegramCode');

        $this->assertNull($user->fresh()->telegram_id);
    }

    public function test_the_link_code_is_single_use(): void
    {
        $user = $this->signedInGoogleUser();
        $component = $this->telegramLinkStarted($user);
        $component->call('checkTelegramAuthStatus');
        $code = $this->codeSentByBot();

        $component->set('authTelegramCode', $code)->call('verifyTelegramCode');
        $this->assertSame(self::TG_ID, $user->fresh()->telegram_id);

        $user->update(['telegram_id' => null]);
        $component->set('authTelegramCode', $code)->call('verifyTelegramCode');

        $this->assertNull($user->fresh()->telegram_id);
    }

    public function test_the_telegram_link_never_signs_anyone_in(): void
    {
        // The request is made by a signed-out browser: the code must not log in the Telegram owner.
        $owner = User::create(['name' => 'Tg Owner', 'email' => 'tg@gmail.com', 'telegram_id' => self::TG_ID, 'password' => 'OwnerPass123']);

        $started = app(TelegramAuthService::class)->start(TelegramLoginRequest::PURPOSE_LINK);
        preg_match('/start=login_([0-9a-f]+)$/', $started['bot_url'], $m);
        $this->bot(['text' => '/start login_'.$m[1]]);
        $code = $this->codeSentByBot();

        $component = Livewire::test(HomePage::class);
        $this->assertNull(session('mally_customer_id'));
        $this->assertNotNull($code);
        $this->assertSame(TelegramLoginRequest::PURPOSE_LINK, TelegramLoginRequest::sole()->purpose);

        // Only a signed-in customer can finish a link; here nobody is signed in.
        $component->call('verifyTelegramCode');
        $this->assertNull(session('mally_customer_id'));
        $this->assertSame($owner->id, User::sole()->id);
    }

    // --------------------------------------------------------------- profile

    public function test_the_profile_offers_the_skipped_steps_later(): void
    {
        $user = $this->signedInGoogleUser();

        Livewire::test(ProfilePage::class)
            ->set('activeTab', 'details')
            ->assertSeeHtml('wire:click="openSetPassword"')
            ->assertSeeHtml('wire:click="openLinkTelegram"')
            ->call('openSetPassword')
            ->assertSet('isAuthModalOpen', true)
            ->assertSet('authStep', 'set-password');

        $user->update(['password' => 'GoodPass1234', 'telegram_id' => self::TG_ID]);
        app(CustomerAccountService::class)->login($user->refresh());

        Livewire::test(ProfilePage::class)
            ->set('activeTab', 'details')
            ->assertDontSeeHtml('wire:click="openSetPassword"')
            ->assertDontSeeHtml('wire:click="openLinkTelegram"');
    }

    public function test_signed_out_visitors_cannot_open_the_account_steps(): void
    {
        Livewire::test(HomePage::class)
            ->call('openSetPassword')
            ->assertSet('isAuthModalOpen', false)
            ->call('openLinkTelegram')
            ->assertSet('isAuthModalOpen', false)
            ->call('startTelegramLink')
            ->assertSet('authStep', 'login');
    }
}
