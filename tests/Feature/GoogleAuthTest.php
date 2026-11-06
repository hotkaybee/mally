<?php

namespace Tests\Feature;

use App\Livewire\Storefront\HomePage;
use App\Mail\AccountExistsMail;
use App\Mail\AuthCodeMail;
use App\Mail\GoogleLinkedMail;
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
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Testing\TestResponse;
use Laravel\Socialite\Contracts\Factory;
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

        // A plain fake (not a facade mock), so a second call in one test
        // replaces the first answer instead of queueing behind it.
        $this->app->instance(Factory::class, new class($provider) implements Factory
        {
            public function __construct(private $provider) {}

            public function driver($driver = null)
            {
                return $this->provider;
            }
        });
        Socialite::clearResolvedInstance(Factory::class);
    }

    private function googleCallback(array $raw): TestResponse
    {
        $this->fakeGoogleReturns($raw);

        return $this->get('/auth/google/callback?code=auth-code&state=state');
    }

    /** An account registered the usual way: email + password. */
    private function emailUser(array $attrs = []): User
    {
        return User::create(array_merge([
            'name' => 'Gmail Owner',
            'email' => 'malika@gmail.com',
            'password' => 'OwnerPass123',
        ], $attrs));
    }

    /** A Telegram account: phone confirmed in the bot, no email. */
    private function telegramUser(array $attrs = []): User
    {
        return User::create(array_merge([
            'name' => 'Tg Only',
            'phone' => self::PHONE,
            'telegram_id' => self::TG_ID,
            'phone_verified_at' => now(),
            'password' => 'TgPass12345',
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

    private function signIn(User $user): void
    {
        app(CustomerAccountService::class)->login($user->refresh());
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
    private function telegramLinkStarted()
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

    private function fakeRedirectProvider(array $expectedParams): void
    {
        $provider = Mockery::mock(GoogleProvider::class);
        $provider->shouldReceive('scopes')->with(['openid', 'email', 'profile'])->once()->andReturnSelf();
        $provider->shouldReceive('with')->with($expectedParams)->once()->andReturnSelf();
        $provider->shouldReceive('redirect')->once()->andReturn(redirect('https://accounts.google.com/o/oauth2/auth'));
        $this->app->instance(Factory::class, new class($provider) implements Factory
        {
            public function __construct(private $provider) {}

            public function driver($driver = null)
            {
                return $this->provider;
            }
        });
        Socialite::clearResolvedInstance(Factory::class);
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
        $this->fakeRedirectProvider(['prompt' => 'select_account']);

        $this->get('/auth/google?redirect=/cart')
            ->assertRedirect('https://accounts.google.com/o/oauth2/auth');

        $this->assertSame('/cart', session(GoogleAuthService::SESSION_REDIRECT));
    }

    public function test_an_external_return_path_is_dropped(): void
    {
        $this->fakeRedirectProvider(['prompt' => 'select_account']);

        $this->get('/auth/google?redirect=https://evil.example/steal')->assertRedirect();

        $this->assertSame('/', session(GoogleAuthService::SESSION_REDIRECT));
    }

    public function test_the_typed_email_is_passed_to_google_as_a_hint(): void
    {
        $this->fakeRedirectProvider(['prompt' => 'select_account', 'login_hint' => 'malika@gmail.com']);

        $this->get('/auth/google?login_hint=Malika@Gmail.com')->assertRedirect();
    }

    public function test_a_junk_hint_is_not_passed_to_google(): void
    {
        $this->fakeRedirectProvider(['prompt' => 'select_account']);

        $this->get('/auth/google?login_hint=not-an-email')->assertRedirect();
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
        $this->assertNull($user->password, 'No password is asked: Google already proved who this is.');
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
        session()->invalidate();
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

    // ----------------------------- an email that already has an account: no password

    public function test_a_google_email_that_already_has_an_account_signs_in_to_it_without_a_password(): void
    {
        Mail::fake();
        $owner = $this->emailUser();
        $customerId = $owner->customer()->firstOrCreate([], ['first_name' => 'Gmail', 'last_name' => 'Owner', 'source' => 'website'])->id;

        $this->googleCallback($this->googleProfile())->assertRedirect('/');

        $owner->refresh();
        $this->assertSame(1, User::count(), 'No second account.');
        $this->assertSame('google-sub-1', $owner->google_id);
        $this->assertNotNull($owner->email_verified_at);
        $this->assertSame($customerId, session('mally_customer_id'), 'Signed in to the existing account, with its orders.');
        $this->assertNull(session(GoogleAuthService::SESSION_OPEN), 'An existing account is not onboarded again.');
        $this->assertStringContainsString('привязан', (string) session('mally_toast.message'));
        $this->assertTrue(Hash::check('OwnerPass123', $owner->password), 'The old password keeps working.');

        // The owner is told that Google was linked to the account.
        Mail::assertSent(GoogleLinkedMail::class, fn ($mail) => $mail->hasTo('malika@gmail.com'));
    }

    public function test_after_linking_both_ways_of_signing_in_work_for_the_same_account(): void
    {
        $owner = $this->emailUser();
        $this->googleCallback($this->googleProfile())->assertRedirect('/');
        $customerId = session('mally_customer_id');

        session()->invalidate();
        $this->assertTrue(app(EmailAuthService::class)->attemptLogin('malika@gmail.com', 'OwnerPass123', '10.4.4.4')['success']);

        session()->invalidate();
        $this->googleCallback($this->googleProfile())->assertRedirect('/');
        $this->assertSame($customerId, session('mally_customer_id'));
        $this->assertSame(1, User::count());
        $this->assertSame($owner->id, User::sole()->id);
    }

    public function test_an_admin_email_is_never_linked_or_signed_in(): void
    {
        $admin = User::factory()->admin()->create(['email' => 'malika@gmail.com']);

        $this->googleCallback($this->googleProfile())->assertRedirect('/');

        $this->assertNull($admin->fresh()->google_id);
        $this->assertNull(session('mally_customer_id'));
        $this->assertSame(1, User::count());
    }

    public function test_an_account_that_already_has_another_google_is_not_taken_over(): void
    {
        $this->emailUser(['google_id' => 'someone-elses-sub']);

        $this->googleCallback($this->googleProfile())->assertRedirect('/');

        $this->assertNull(session('mally_customer_id'));
        $this->assertSame('someone-elses-sub', User::sole()->google_id);
    }

    public function test_the_same_google_email_cannot_be_used_by_two_google_ids(): void
    {
        $this->googleCallback($this->googleProfile())->assertRedirect('/');
        session()->invalidate();

        // Another Google identity that now claims the same address (e.g. an address that changed hands).
        $this->googleCallback($this->googleProfile(['sub' => 'other-sub']))->assertRedirect('/');

        $this->assertSame(1, User::count());
        $this->assertSame('google-sub-1', User::sole()->google_id);
        $this->assertNull(session('mally_customer_id'));
    }

    public function test_a_google_account_with_a_different_email_is_a_separate_account(): void
    {
        $owner = $this->emailUser(['email' => 'work@company.uz']);

        $this->googleCallback($this->googleProfile())->assertRedirect('/');

        $this->assertSame(2, User::count());
        $this->assertNull($owner->fresh()->google_id, 'Nothing is merged by name or by guessing.');
        $this->assertSame('malika@gmail.com', User::where('google_id', 'google-sub-1')->value('email'));
    }

    public function test_a_telegram_account_without_email_is_not_merged_with_a_google_account(): void
    {
        $telegram = $this->telegramUser();

        $this->googleCallback($this->googleProfile())->assertRedirect('/');

        $this->assertSame(2, User::count());
        $this->assertNull($telegram->fresh()->google_id);
        $this->assertNull(User::where('google_id', 'google-sub-1')->value('telegram_id'));
    }

    public function test_a_customer_without_an_account_is_not_taken_over_by_a_matching_google_email(): void
    {
        // Entered by hand in the admin: it has orders, but nobody proved it is theirs.
        $manual = Customer::create(['first_name' => 'Manual', 'last_name' => 'Entry', 'email' => 'malika@gmail.com', 'source' => 'website']);

        $this->googleCallback($this->googleProfile())->assertRedirect('/');

        $this->assertNull($manual->fresh()->user_id);
        $this->assertNotSame($manual->id, session('mally_customer_id'));
        $this->assertSame(2, Customer::count());
    }

    public function test_the_email_sign_up_does_not_create_a_second_account_for_a_google_email(): void
    {
        Mail::fake();
        $this->signedInGoogleUser();

        $res = app(EmailAuthService::class)->sendCode('malika@gmail.com', 'register', '10.2.2.2');

        $this->assertSame(1, User::count());
        $this->assertTrue($res['success'], 'The answer is the same whether or not the email has an account.');
        Mail::assertSent(AccountExistsMail::class);
        Mail::assertNotSent(AuthCodeMail::class);
    }

    public function test_a_google_account_can_get_a_password_through_the_forgot_password_email_code(): void
    {
        $user = $this->signedInGoogleUser();
        $this->assertFalse($user->hasPassword());

        $res = app(EmailAuthService::class)->resetPassword('malika@gmail.com', 'ResetPass12345');

        $this->assertTrue($res['success']);
        $user->refresh();
        $this->assertTrue(Hash::check('ResetPass12345', $user->password));
        $this->assertSame('google-sub-1', $user->google_id, 'Google stays linked.');
        $this->assertTrue(app(EmailAuthService::class)->attemptLogin('malika@gmail.com', 'ResetPass12345', '10.3.3.3')['success']);
    }

    // ------------------------------------- the sign-in form for a Google-only email

    public function test_the_sign_in_form_sends_a_google_only_email_back_to_google(): void
    {
        $this->signedInGoogleUser();
        session()->invalidate();
        session(['mally_locale' => 'ru']);

        Livewire::test(HomePage::class)
            ->call('openAuthModal')
            ->set('authEmail', 'Malika@Gmail.com')
            ->set('authPassword', '')
            ->call('loginWithPassword')
            ->assertSet('authGoogleHint', true)
            ->assertHasNoErrors()
            ->assertSee('Вы входили через Google. Пожалуйста, продолжите тем же способом')
            ->assertSeeHtml('login_hint=malika%40gmail.com');

        $this->assertNull(session('mally_customer_id'));
    }

    public function test_the_google_hint_does_not_depend_on_the_typed_password(): void
    {
        $this->signedInGoogleUser();
        session()->invalidate();

        Livewire::test(HomePage::class)
            ->call('openAuthModal')
            ->set('authEmail', 'malika@gmail.com')
            ->set('authPassword', 'some-guess-12345')
            ->call('loginWithPassword')
            ->assertSet('authGoogleHint', true)
            ->assertSet('authPassword', '');

        $this->assertNull(session('mally_customer_id'));
    }

    public function test_an_account_with_a_password_signs_in_normally_even_if_google_is_linked(): void
    {
        $this->emailUser(['google_id' => 'google-sub-1']);

        Livewire::test(HomePage::class)
            ->call('openAuthModal')
            ->set('authEmail', 'malika@gmail.com')
            ->set('authPassword', 'WrongPass1234')
            ->call('loginWithPassword')
            ->assertSet('authGoogleHint', false)
            ->assertHasErrors('authPassword');

        Livewire::test(HomePage::class)
            ->call('openAuthModal')
            ->set('authEmail', 'malika@gmail.com')
            ->set('authPassword', 'OwnerPass123')
            ->call('loginWithPassword')
            ->assertSet('isAuthModalOpen', false);
        $this->assertNotNull(session('mally_customer_id'));
    }

    public function test_an_unknown_email_gets_the_ordinary_wrong_password_answer(): void
    {
        Livewire::test(HomePage::class)
            ->call('openAuthModal')
            ->set('authEmail', 'nobody@gmail.com')
            ->set('authPassword', 'WrongPass1234')
            ->call('loginWithPassword')
            ->assertSet('authGoogleHint', false)
            ->assertHasErrors('authPassword');
    }

    public function test_the_google_hint_is_rate_limited(): void
    {
        $this->signedInGoogleUser();
        session()->invalidate();
        for ($i = 0; $i < 20; $i++) {
            RateLimiter::hit('google-only-hint:127.0.0.1', 60);
        }

        Livewire::test(HomePage::class)
            ->call('openAuthModal')
            ->set('authEmail', 'malika@gmail.com')
            ->set('authPassword', 'WrongPass1234')
            ->call('loginWithPassword')
            ->assertSet('authGoogleHint', false)
            ->assertHasErrors('authPassword');
    }

    public function test_a_google_account_cannot_be_entered_with_an_empty_or_guessed_password(): void
    {
        $this->signedInGoogleUser();
        $auth = app(EmailAuthService::class);

        $this->assertFalse($auth->attemptLogin('malika@gmail.com', '', '10.1.1.2')['success']);
        $this->assertFalse($auth->attemptLogin('malika@gmail.com', 'anything12345', '10.1.1.2')['success']);
    }

    // ------------------------------------------ adding Google from the profile

    public function test_the_profile_button_asks_google_in_link_mode_only_for_a_signed_in_account(): void
    {
        $this->fakeRedirectProvider(['prompt' => 'select_account']);
        $this->signIn($this->telegramUser());

        $this->get('/auth/google?link=1&redirect=/profile')->assertRedirect();

        $this->assertTrue(session(GoogleAuthService::SESSION_MODE_LINK));
    }

    public function test_link_mode_is_ignored_when_nobody_is_signed_in(): void
    {
        $this->fakeRedirectProvider(['prompt' => 'select_account']);

        $this->get('/auth/google?link=1')->assertRedirect();

        $this->assertNull(session(GoogleAuthService::SESSION_MODE_LINK));
    }

    public function test_a_telegram_account_adds_its_email_through_google(): void
    {
        $user = $this->telegramUser();
        $this->signIn($user);
        session([GoogleAuthService::SESSION_MODE_LINK => true, GoogleAuthService::SESSION_REDIRECT => '/profile']);

        $this->googleCallback($this->googleProfile())->assertRedirect('/profile');

        $user->refresh();
        $this->assertSame(1, User::count(), 'The same account, no new one.');
        $this->assertSame('malika@gmail.com', $user->email);
        $this->assertSame('google-sub-1', $user->google_id);
        $this->assertNotNull($user->email_verified_at);
        $this->assertSame(self::TG_ID, $user->telegram_id);
        $this->assertSame('malika@gmail.com', $user->customer()->value('email'));
        $this->assertSame($user->customer()->value('id'), session('mally_customer_id'));
    }

    public function test_adding_google_from_the_profile_refuses_an_email_of_another_account(): void
    {
        $other = $this->emailUser();
        $user = $this->telegramUser();
        $this->signIn($user);
        session([GoogleAuthService::SESSION_MODE_LINK => true]);

        $this->googleCallback($this->googleProfile())->assertRedirect('/');

        $this->assertNull($user->fresh()->email);
        $this->assertNull($user->fresh()->google_id);
        $this->assertNull($other->fresh()->google_id);
        $this->assertSame($user->customer()->value('id'), session('mally_customer_id'), 'Still the same signed-in account.');
        $this->assertSame('Этот email уже принадлежит другому профилю.', session('mally_toast.message'));
    }

    public function test_adding_google_from_the_profile_refuses_a_google_that_belongs_to_another_account(): void
    {
        $this->emailUser(['email' => 'other@gmail.com', 'google_id' => 'google-sub-1']);
        $user = $this->telegramUser();
        $this->signIn($user);
        session([GoogleAuthService::SESSION_MODE_LINK => true]);

        $this->googleCallback($this->googleProfile())->assertRedirect('/');

        $this->assertNull($user->fresh()->google_id);
        $this->assertNull($user->fresh()->email);
    }

    public function test_adding_google_from_the_profile_refuses_an_unconfirmed_email(): void
    {
        $user = $this->telegramUser();
        $this->signIn($user);
        session([GoogleAuthService::SESSION_MODE_LINK => true]);

        $this->googleCallback($this->googleProfile(['email_verified' => false]))->assertRedirect('/');

        $this->assertNull($user->fresh()->email);
        $this->assertNull($user->fresh()->google_id);
    }

    public function test_an_account_with_an_email_only_accepts_google_with_that_same_email(): void
    {
        $user = $this->emailUser(['email' => 'work@company.uz']);
        $this->signIn($user);
        session([GoogleAuthService::SESSION_MODE_LINK => true]);

        $this->googleCallback($this->googleProfile())->assertRedirect('/');

        $this->assertNull($user->fresh()->google_id);
        $this->assertSame('work@company.uz', $user->fresh()->email);
    }

    public function test_the_link_mode_flag_is_single_use(): void
    {
        $user = $this->telegramUser();
        $this->signIn($user);
        session([GoogleAuthService::SESSION_MODE_LINK => true]);
        $this->googleCallback($this->googleProfile(['email_verified' => false]))->assertRedirect('/');

        $this->assertNull(session(GoogleAuthService::SESSION_MODE_LINK));
    }

    public function test_an_admin_cannot_use_the_profile_link(): void
    {
        $res = app(CustomerAccountService::class)->attachGoogle(
            User::factory()->admin()->create(['email' => 'admin@mally.uz']),
            ['sub' => 's', 'email' => 'new@gmail.com', 'email_verified' => true, 'first_name' => 'A', 'last_name' => 'B']
        );

        $this->assertSame(CustomerAccountService::GOOGLE_ALREADY_LINKED, $res);
    }

    // --------------------------------------------- optional Telegram after Google

    public function test_a_new_google_user_is_offered_telegram_and_never_a_password(): void
    {
        $this->signedInGoogleUser();
        session([GoogleAuthService::SESSION_OPEN => 'onboarding']);

        Livewire::test(HomePage::class)
            ->assertSet('isAuthModalOpen', true)
            ->assertSet('authStep', 'offer-telegram')
            ->assertSee('Добавить Telegram в профиль?')
            ->assertDontSeeHtml('wire:model="authPassword"')
            ->assertSeeHtml('wire:click="skipTelegramOffer"');
    }

    public function test_skipping_telegram_leaves_a_working_google_account(): void
    {
        $user = $this->signedInGoogleUser();
        session([GoogleAuthService::SESSION_OPEN => 'onboarding']);

        Livewire::test(HomePage::class)
            ->call('skipTelegramOffer')
            ->assertSet('isAuthModalOpen', false);

        $user->refresh();
        $this->assertNull($user->telegram_id);
        $this->assertNull($user->phone);

        // The account still works through Google alone.
        session()->invalidate();
        $this->googleCallback($this->googleProfile())->assertRedirect('/');
        $this->assertSame($user->id, User::sole()->id);
        $this->assertNotNull(session('mally_customer_id'));
    }

    public function test_the_telegram_offer_is_not_shown_when_the_bot_is_not_set_up(): void
    {
        config(['services.telegram.auth_bot_token' => '', 'services.telegram.auth_bot_username' => '']);
        $this->signedInGoogleUser();
        session([GoogleAuthService::SESSION_OPEN => 'onboarding']);

        Livewire::test(HomePage::class)->assertSet('isAuthModalOpen', false);
    }

    public function test_nobody_signed_out_sees_the_onboarding(): void
    {
        session([GoogleAuthService::SESSION_OPEN => 'onboarding']);

        Livewire::test(HomePage::class)->assertSet('isAuthModalOpen', false);
    }

    public function test_telegram_can_be_added_to_the_profile_through_the_bot(): void
    {
        $user = $this->signedInGoogleUser();
        $component = $this->telegramLinkStarted();

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
        $component = $this->telegramLinkStarted();

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

    public function test_telegram_without_a_confirmed_phone_can_be_started_again_to_add_the_phone(): void
    {
        $user = $this->signedInGoogleUser();
        $user->forceFill(['telegram_id' => self::TG_ID])->save();
        $this->signIn($user);

        $component = $this->telegramLinkStarted();
        $this->bot(['contact' => ['phone_number' => self::PHONE, 'user_id' => (int) self::TG_ID, 'first_name' => 'Malika']]);
        $component->call('checkTelegramAuthStatus')
            ->set('authTelegramCode', $this->codeSentByBot())
            ->call('verifyTelegramCode');

        $user->refresh();
        $this->assertSame(self::PHONE, $user->phone);
        $this->assertNotNull($user->phone_verified_at);
    }

    public function test_an_account_with_a_confirmed_phone_has_nothing_to_add(): void
    {
        $this->signIn($this->telegramUser());

        Livewire::test(HomePage::class)
            ->call('openLinkTelegram')
            ->assertSet('isAuthModalOpen', false)
            ->call('startTelegramLink')
            ->assertSet('isAuthModalOpen', false);
    }

    public function test_somebody_elses_contact_does_not_add_a_phone(): void
    {
        $user = $this->signedInGoogleUser();
        $component = $this->telegramLinkStarted();

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
        $component = $this->telegramLinkStarted();

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
        $component = $this->telegramLinkStarted();

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
        $component = $this->telegramLinkStarted();
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
        $component = $this->telegramLinkStarted();
        $component->call('checkTelegramAuthStatus');

        $component->set('authTelegramCode', '000000')
            ->call('verifyTelegramCode')
            ->assertHasErrors('authTelegramCode');

        $this->assertNull($user->fresh()->telegram_id);
    }

    public function test_the_link_code_is_single_use(): void
    {
        $user = $this->signedInGoogleUser();
        $component = $this->telegramLinkStarted();
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

    public function test_signed_out_visitors_cannot_open_the_account_steps(): void
    {
        Livewire::test(HomePage::class)
            ->call('openLinkTelegram')
            ->assertSet('isAuthModalOpen', false)
            ->call('startTelegramLink')
            ->assertSet('authStep', 'login');
    }

    // ------------------------------------------ only Google-authoritative addresses

    public function test_no_notification_is_sent_for_a_new_account_or_a_repeat_sign_in(): void
    {
        Mail::fake();

        $this->googleCallback($this->googleProfile())->assertRedirect('/');
        session()->invalidate();
        $this->googleCallback($this->googleProfile())->assertRedirect('/');

        Mail::assertNotSent(GoogleLinkedMail::class);
    }

    public function test_a_mail_problem_does_not_block_the_sign_in(): void
    {
        $this->emailUser();
        Mail::shouldReceive('to')->andThrow(new Exception('smtp down'));

        $this->googleCallback($this->googleProfile())->assertRedirect('/');

        $this->assertNotNull(session('mally_customer_id'));
        $this->assertSame('google-sub-1', User::sole()->google_id);
    }

    public function test_a_workspace_address_on_its_own_hosted_domain_may_sign_in_to_the_existing_account(): void
    {
        Mail::fake();
        $owner = $this->emailUser(['email' => 'malika@company.uz']);

        $this->googleCallback($this->googleProfile(['email' => 'malika@company.uz', 'hd' => 'company.uz']))->assertRedirect('/');

        $this->assertSame('google-sub-1', $owner->fresh()->google_id);
        $this->assertNotNull(session('mally_customer_id'));
    }

    public function test_a_googlemail_address_counts_like_gmail(): void
    {
        $owner = $this->emailUser(['email' => 'malika@googlemail.com']);

        $this->googleCallback($this->googleProfile(['email' => 'malika@googlemail.com']))->assertRedirect('/');

        $this->assertSame('google-sub-1', $owner->fresh()->google_id);
    }

    public function test_an_address_google_does_not_own_cannot_sign_in_to_the_existing_account(): void
    {
        Mail::fake();
        $owner = $this->emailUser(['email' => 'malika@mail.ru']);

        $this->googleCallback($this->googleProfile(['email' => 'malika@mail.ru']))->assertRedirect('/');

        $this->assertNull($owner->fresh()->google_id);
        $this->assertNull(session('mally_customer_id'));
        $this->assertSame(1, User::count());
        $this->assertStringContainsString('уже существует', (string) session('mally_toast.message'));
        Mail::assertNotSent(GoogleLinkedMail::class);
    }

    public function test_a_company_address_without_workspace_cannot_sign_in_to_the_existing_account(): void
    {
        $owner = $this->emailUser(['email' => 'malika@company.uz']);

        // No hosted domain: a plain Google account that happens to use a company address.
        $this->googleCallback($this->googleProfile(['email' => 'malika@company.uz']))->assertRedirect('/');

        $this->assertNull($owner->fresh()->google_id);
        $this->assertNull(session('mally_customer_id'));
    }

    public function test_a_hosted_domain_that_is_not_the_address_domain_does_not_count(): void
    {
        $owner = $this->emailUser(['email' => 'malika@company.uz']);

        $this->googleCallback($this->googleProfile(['email' => 'malika@company.uz', 'hd' => 'other.uz']))->assertRedirect('/');

        $this->assertNull($owner->fresh()->google_id);
        $this->assertNull(session('mally_customer_id'));
    }

    public function test_a_new_account_can_still_be_created_for_any_confirmed_address(): void
    {
        $this->googleCallback($this->googleProfile(['email' => 'malika@mail.ru']))->assertRedirect('/');

        $this->assertSame('malika@mail.ru', User::sole()->email);
        $this->assertNotNull(session('mally_customer_id'));
    }

    public function test_the_authority_check_ignores_case_and_spaces(): void
    {
        $this->assertTrue(CustomerAccountService::isGoogleAuthoritative(' Ali@GMAIL.com ', ''));
        $this->assertTrue(CustomerAccountService::isGoogleAuthoritative('a@Company.UZ', 'company.uz'));
        $this->assertFalse(CustomerAccountService::isGoogleAuthoritative('a@gmail.com.evil.uz', ''));
        $this->assertFalse(CustomerAccountService::isGoogleAuthoritative('a@evilgmail.com', ''));
        $this->assertFalse(CustomerAccountService::isGoogleAuthoritative('a@company.uz', ''));
    }
}
