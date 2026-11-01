<?php

namespace Tests\Feature;

use App\Livewire\Storefront\HomePage;
use App\Livewire\Storefront\ProfilePage;
use App\Models\Customer;
use App\Models\Order;
use App\Models\TelegramLoginRequest;
use App\Models\User;
use App\Services\CustomerAccountService;
use App\Services\EmailAuthService;
use App\Services\TelegramAuthService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\RateLimiter;
use Livewire\Features\SupportLockedProperties\CannotUpdateLockedPropertyException;
use Livewire\Livewire;
use Tests\TestCase;

class TelegramAuthTest extends TestCase
{
    use RefreshDatabase;

    private const TG_ID = '555000111';

    private const PHONE = '+998 90 123 45 67';

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
        app()->setLocale('ru');
        session(['mally_locale' => 'ru']);

        config([
            'services.telegram.auth_bot_token' => 'TEST_BOT_TOKEN',
            'services.telegram.auth_bot_username' => 'mally_test_bot',
            // Updates come through handleUpdate()/webhook here; no getUpdates polling.
            'services.telegram.auth_webhook_secret' => 'whsec',
        ]);

        Http::fake(['api.telegram.org/*' => Http::response(['ok' => true])]);
    }

    // ------------------------------------------------------------- helpers

    private function tokenFrom($component): string
    {
        preg_match('/start=login_([0-9a-f]+)$/', $component->get('authTelegramBotUrl'), $m);

        return $m[1];
    }

    private function bot(array $message, string $fromId = self::TG_ID): void
    {
        app(TelegramAuthService::class)->handleUpdate(['update_id' => random_int(1, 1_000_000), 'message' => array_merge([
            'chat' => ['id' => (int) $fromId, 'type' => 'private'],
            'from' => ['id' => (int) $fromId, 'is_bot' => false, 'first_name' => 'Ali', 'last_name' => 'Valiev', 'username' => 'ali_tg'],
        ], $message)]);
    }

    private function botStart(string $token, string $fromId = self::TG_ID): void
    {
        $this->bot(['text' => '/start login_'.$token], $fromId);
    }

    private function botContact(string $phone = self::PHONE, string $fromId = self::TG_ID, ?string $contactUserId = null): void
    {
        $this->bot(['contact' => ['phone_number' => $phone, 'user_id' => (int) ($contactUserId ?? $fromId), 'first_name' => 'Ali']], $fromId);
    }

    /** The last code the bot sent, read from the faked sendMessage calls. */
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

    /** Runs the whole bot conversation and returns the component on the code step. */
    private function throughBot(string $phone = self::PHONE)
    {
        $component = Livewire::test(HomePage::class)->call('openAuthModal')->call('startTelegramAuth')
            ->assertSet('authStep', 'telegram-wait');

        $this->botStart($this->tokenFrom($component));
        $this->botContact($phone);

        return $component->call('checkTelegramAuthStatus')->assertSet('authStep', 'telegram-code');
    }

    private function emailUser(array $attrs = []): User
    {
        return User::create(array_merge([
            'name' => 'Gmail Owner',
            'email' => 'owner@gmail.com',
            'phone' => self::PHONE,
            'password' => 'OwnerPass123',
        ], $attrs));
    }

    // --------------------------------------------------------------- tests

    public function test_new_customer_registers_through_the_bot_code(): void
    {
        $component = $this->throughBot();
        $sessionBefore = session()->getId();

        $component->set('authTelegramCode', $this->codeSentByBot())
            ->call('verifyTelegramCode')
            ->assertSet('authStep', 'telegram-register')
            ->assertSet('authPhone', self::PHONE)
            ->assertSeeHtml('wire:model="authPassword"')
            ->set('authName', 'Ali Valiev')
            ->set('authPassword', 'TgPass1234')
            ->set('authPasswordConfirmation', 'TgPass1234')
            ->call('completeTelegramRegistration')
            ->assertSet('isAuthModalOpen', false);

        $user = User::sole();
        $this->assertSame(self::TG_ID, $user->telegram_id);
        $this->assertSame(self::PHONE, $user->phone);
        $this->assertNotNull($user->phone_verified_at);
        $this->assertFalse($user->is_admin);
        // No made-up address: Telegram accounts have no email.
        $this->assertNull($user->email);
        $this->assertFalse($user->hasRealEmail());
        // The chosen password works with the phone number as the login.
        $this->assertTrue(Hash::check('TgPass1234', $user->password));
        $this->assertTrue(app(EmailAuthService::class)->attemptLogin(self::PHONE, 'TgPass1234', '10.1.1.1')['success']);

        $customer = Customer::sole();
        $this->assertSame($user->id, $customer->user_id);
        $this->assertSame('@ali_tg', $customer->telegram);
        $this->assertSame('telegram', $customer->source);
        $this->assertNull($customer->email);
        $this->assertSame($customer->id, session('mally_customer_id'));
        $this->assertNotSame($sessionBefore, session()->getId(), 'session id must be regenerated on login');

        // The bot really asked for the contact and the code went to that chat.
        Http::assertSent(fn (Request $r) => str_contains($r->url(), '/sendMessage') && str_contains((string) $r['reply_markup'], 'request_contact'));
        $this->assertSame(1, TelegramLoginRequest::where('status', 'used')->count());
    }

    public function test_telegram_registration_needs_a_password_typed_twice(): void
    {
        $component = $this->throughBot()
            ->set('authTelegramCode', $this->codeSentByBot())
            ->call('verifyTelegramCode')
            ->set('authName', 'Ali');

        $component->set('authPassword', 'short')->set('authPasswordConfirmation', 'short')
            ->call('completeTelegramRegistration')
            ->assertHasErrors('authPassword')
            ->assertSet('authStep', 'telegram-register');

        $component->set('authPassword', 'TgPass1234')->set('authPasswordConfirmation', 'TgPass9999')
            ->call('completeTelegramRegistration')
            ->assertHasErrors('authPasswordConfirmation')
            ->assertSet('authStep', 'telegram-register');

        $this->assertSame(0, User::count());
    }

    public function test_linked_telegram_account_signs_in_directly(): void
    {
        $user = $this->emailUser(['telegram_id' => self::TG_ID, 'phone_verified_at' => now()]);

        $this->throughBot()
            ->set('authTelegramCode', $this->codeSentByBot())
            ->call('verifyTelegramCode')
            ->assertSet('isAuthModalOpen', false);

        $this->assertSame($user->id, (int) session('mally_user_id'));
        $this->assertSame(1, User::count());
    }

    public function test_wrong_code_is_rejected_and_five_wrong_codes_burn_the_request(): void
    {
        $component = $this->throughBot();
        $real = $this->codeSentByBot();
        $wrong = $real === '000000' ? '111111' : '000000';

        $component->set('authTelegramCode', $wrong)->call('verifyTelegramCode')
            ->assertSet('authStep', 'telegram-code')
            ->assertHasErrors('authTelegramCode');

        for ($i = 0; $i < TelegramAuthService::MAX_CODE_ATTEMPTS; $i++) {
            $component->set('authTelegramCode', $wrong)->call('verifyTelegramCode');
        }

        $component->set('authTelegramCode', $real)->call('verifyTelegramCode')->assertSet('authStep', 'telegram-wait');
        $this->assertNull(session('mally_customer_id'));
        $this->assertSame(0, User::count());
    }

    public function test_code_is_not_checked_once_attempts_are_used_up_even_if_correct(): void
    {
        $component = $this->throughBot();
        $request = TelegramLoginRequest::sole();

        // State after five parallel wrong guesses that all read the request
        // before any of them could burn it.
        for ($i = 0; $i < TelegramAuthService::MAX_CODE_ATTEMPTS; $i++) {
            RateLimiter::hit('tg-login-code:'.$request->id, TelegramAuthService::CODE_TTL_SECONDS);
        }

        $component->set('authTelegramCode', $this->codeSentByBot())->call('verifyTelegramCode')->assertSet('authStep', 'telegram-wait');
        $this->assertNull(session('mally_customer_id'));
        $this->assertNull($request->fresh()->code_hash);
        $this->assertSame(0, User::count());
    }

    public function test_successful_code_check_resets_the_attempt_counter(): void
    {
        $component = $this->throughBot();
        $request = TelegramLoginRequest::sole();
        $real = $this->codeSentByBot();
        $wrong = $real === '000000' ? '111111' : '000000';

        for ($i = 0; $i < TelegramAuthService::MAX_CODE_ATTEMPTS - 1; $i++) {
            $component->set('authTelegramCode', $wrong)->call('verifyTelegramCode')->assertSet('authStep', 'telegram-code');
        }

        $component->set('authTelegramCode', $real)->call('verifyTelegramCode')->assertSet('authStep', 'telegram-register');
        $this->assertSame(0, RateLimiter::attempts('tg-login-code:'.$request->id));
    }

    public function test_new_code_resets_the_attempt_counter(): void
    {
        $component = $this->throughBot();
        $request = TelegramLoginRequest::sole();
        $wrong = $this->codeSentByBot() === '000000' ? '111111' : '000000';

        for ($i = 0; $i < TelegramAuthService::MAX_CODE_ATTEMPTS - 1; $i++) {
            $component->set('authTelegramCode', $wrong)->call('verifyTelegramCode');
        }

        // The bot issues a fresh code for the same request.
        $request->update(['status' => TelegramLoginRequest::STATUS_CONTACT_REQUESTED]);
        $this->botContact();
        $real = $this->codeSentByBot();
        $wrong = $real === '000000' ? '111111' : '000000';

        for ($i = 0; $i < TelegramAuthService::MAX_CODE_ATTEMPTS - 1; $i++) {
            $component->set('authTelegramCode', $wrong)->call('verifyTelegramCode')->assertSet('authStep', 'telegram-code');
        }

        $component->set('authTelegramCode', $real)->call('verifyTelegramCode')->assertSet('authStep', 'telegram-register');
    }

    public function test_request_expires(): void
    {
        $component = $this->throughBot();
        $code = $this->codeSentByBot();

        $this->travel(TelegramAuthService::CODE_TTL_SECONDS + 1)->seconds();

        $component->set('authTelegramCode', $code)->call('verifyTelegramCode')->assertSet('authStep', 'telegram-wait');
        $this->assertSame(0, User::count());
        $this->assertSame(TelegramAuthService::EXPIRED, app(TelegramAuthService::class)->status());
    }

    public function test_request_and_code_cannot_be_reused(): void
    {
        $component = $this->throughBot();
        $token = $this->tokenFrom($component);
        $code = $this->codeSentByBot();

        $this->assertSame(TelegramAuthService::VERIFIED, app(TelegramAuthService::class)->verifyCode($code)['result']);
        $this->assertSame(TelegramAuthService::EXPIRED, app(TelegramAuthService::class)->verifyCode($code)['result']);

        // The deep link is dead too.
        Http::fake(['api.telegram.org/*' => Http::response(['ok' => true])]);
        $this->botStart($token);
        Http::assertSent(fn (Request $r) => str_contains((string) $r['text'], 'устарела или уже использована'));
    }

    public function test_request_is_bound_to_the_browser_session_that_started_it(): void
    {
        $this->throughBot();
        $code = $this->codeSentByBot();

        // Another browser (different session) with the right code still fails.
        session()->setId(str_repeat('a', 40));

        $this->assertSame(TelegramAuthService::EXPIRED, app(TelegramAuthService::class)->verifyCode($code)['result']);
    }

    public function test_bot_refuses_someone_elses_contact_and_a_second_telegram_user(): void
    {
        $component = Livewire::test(HomePage::class)->call('openAuthModal')->call('startTelegramAuth');
        $token = $this->tokenFrom($component);

        $this->botStart($token);
        // Sharing another person's contact card proves nothing.
        $this->botContact(self::PHONE, self::TG_ID, contactUserId: '999');
        $this->assertNull($this->codeSentByBot());

        // A different Telegram user can't take over the same link.
        $this->botStart($token, '777');
        $this->assertSame(self::TG_ID, TelegramLoginRequest::sole()->telegram_user_id);
        $this->assertSame('contact_requested', TelegramLoginRequest::sole()->status);
    }

    public function test_auth_steps_cannot_be_forced_from_the_browser(): void
    {
        $component = Livewire::test(HomePage::class)->call('openAuthModal');

        $component->call('setAuthStep', 'telegram-register')->assertSet('authStep', 'login');
        $component->call('completeTelegramRegistration');
        $this->assertSame(0, User::count());

        $this->expectException(CannotUpdateLockedPropertyException::class);
        $component->set('authStep', 'telegram-register');
    }

    public function test_existing_phone_is_not_merged_linked_or_signed_in(): void
    {
        $owner = $this->emailUser();
        $passwordHash = $owner->password;

        $this->throughBot()
            ->set('authTelegramCode', $this->codeSentByBot())
            ->call('verifyTelegramCode')
            ->assertSet('authStep', 'login')
            ->assertSet('authEmail', self::PHONE)
            ->assertSet('isAuthModalOpen', true);

        // Nothing was created, merged or changed, and nobody is signed in.
        $this->assertSame(1, User::count());
        $this->assertNull(session('mally_customer_id'));
        $this->assertNull($owner->fresh()->telegram_id);
        $this->assertSame($passwordHash, $owner->fresh()->password);

        // The owner signs in with the password; Telegram is still not linked.
        Livewire::test(HomePage::class)->call('openAuthModal')
            ->set('authEmail', self::PHONE)
            ->set('authPassword', 'OwnerPass123')
            ->call('loginWithPassword')
            ->assertSet('isAuthModalOpen', false);

        $this->assertNull($owner->fresh()->telegram_id);
        $this->assertSame(1, User::count());
    }

    public function test_registration_is_refused_when_the_phone_was_taken_meanwhile(): void
    {
        $component = $this->throughBot()
            ->set('authTelegramCode', $this->codeSentByBot())
            ->call('verifyTelegramCode')
            ->assertSet('authStep', 'telegram-register');

        $this->emailUser(['phone' => '901234567']);

        $component->set('authName', 'Intruder')
            ->set('authPassword', 'TgPass1234')
            ->set('authPasswordConfirmation', 'TgPass1234')
            ->call('completeTelegramRegistration')
            ->assertSet('authStep', 'login');

        $this->assertSame(1, User::count());
        $this->assertSame('Gmail Owner', User::sole()->name);
        $this->assertNull(session('mally_customer_id'));
    }

    public function test_telegram_signup_takes_over_a_hand_entered_customer_with_that_phone(): void
    {
        // A customer the admin entered by hand: same phone, no account yet.
        $crm = Customer::create([
            'first_name' => 'Old', 'last_name' => 'Client', 'email' => 'crm@example.com',
            'phone_number' => '901234567', 'city' => 'Samarkand', 'source' => 'word_of_mouth',
        ]);
        $order = Order::create(['customer_id' => $crm->id, 'amount' => 10, 'status' => 'processing']);

        $this->throughBot()
            ->set('authTelegramCode', $this->codeSentByBot())
            ->call('verifyTelegramCode')
            ->assertSet('authStep', 'telegram-register')
            ->set('authName', 'Ali')
            ->set('authPassword', 'TgPass1234')
            ->set('authPasswordConfirmation', 'TgPass1234')
            ->call('completeTelegramRegistration')
            ->assertSet('isAuthModalOpen', false);

        // No new customer: the hand-entered one now belongs to the account
        // and keeps the data entered in the admin.
        $user = User::sole();
        $this->assertSame(1, Customer::count());
        $crm->refresh();
        $this->assertSame($user->id, $crm->user_id);
        $this->assertSame('Old', $crm->first_name);
        $this->assertSame('Samarkand', $crm->city);
        $this->assertSame($crm->id, session('mally_customer_id'));

        // The account's profile shows that customer's old orders.
        $this->assertSame([$order->id], Livewire::test(ProfilePage::class)->viewData('orders')->pluck('id')->all());
    }

    public function test_a_customer_that_belongs_to_another_account_is_never_taken_over(): void
    {
        $other = User::create(['name' => 'Other', 'email' => 'other@gmail.com', 'password' => 'OtherPass123']);
        $theirs = Customer::create([
            'user_id' => $other->id, 'first_name' => 'Other', 'last_name' => '',
            'phone_number' => self::PHONE, 'source' => 'website',
        ]);

        // Telegram sign-up with that phone is refused (the phone has an account).
        $this->throughBot()
            ->set('authTelegramCode', $this->codeSentByBot())
            ->call('verifyTelegramCode')
            ->assertSet('authStep', 'login');

        $this->assertSame(1, User::count());
        $this->assertSame($other->id, $theirs->fresh()->user_id);
        $this->assertNull(session('mally_customer_id'));

        // Even a verified phone never takes it over: the account gets its own
        // customer without the phone (the real one is on the users row).
        $verified = User::create(['name' => 'Ali', 'phone' => self::PHONE, 'phone_verified_at' => now(), 'password' => 'TgPass1234']);
        $mine = app(CustomerAccountService::class)->customerFor($verified);

        $this->assertNotSame($theirs->id, $mine->id);
        $this->assertSame($verified->id, $mine->user_id);
        $this->assertNull($mine->phone_number);
        $this->assertSame($other->id, $theirs->fresh()->user_id);
    }

    public function test_email_registration_rejects_a_phone_that_belongs_to_another_account(): void
    {
        $this->emailUser(['telegram_id' => self::TG_ID]);
        session(['mally_verified_register_email' => 'new@gmail.com', 'mally_verified_register_email_at' => time()]);

        Livewire::test(HomePage::class)
            ->set('authName', 'Someone')
            ->set('authPhone', '90 123 45 67')
            ->set('authPassword', 'Password123')
            ->set('authPasswordConfirmation', 'Password123')
            ->call('completeEmailRegistration')
            ->assertHasErrors('authPhone');

        $this->assertSame(1, User::count());
    }

    public function test_password_reset_through_telegram_for_the_linked_account(): void
    {
        $user = $this->emailUser(['telegram_id' => self::TG_ID, 'phone_verified_at' => now()]);

        $component = Livewire::test(HomePage::class)->call('openAuthModal')
            ->call('openForgotPassword')
            ->call('startTelegramReset')
            ->assertSet('authStep', 'telegram-wait')
            ->assertSet('authTelegramPurpose', 'reset');
        $this->botStart($this->tokenFrom($component));
        $this->botContact();

        $code = null;
        Http::recorded(function (Request $request) use (&$code) {
            if (preg_match('/Mally: (\d{6})/', (string) ($request['text'] ?? ''), $m) && str_contains((string) $request['text'], 'восстановления')) {
                $code = $m[1];
            }

            return true;
        });
        $this->assertNotNull($code, 'the bot says the code is for a password reset');

        $component->call('checkTelegramAuthStatus')
            ->set('authTelegramCode', $code)
            ->call('verifyTelegramCode')
            ->assertSet('authStep', 'reset-password')
            ->set('authPassword', 'NewPass1234')
            ->set('authPasswordConfirmation', 'NewPass1234')
            ->call('completePasswordReset')
            ->assertSet('isAuthModalOpen', false);

        $this->assertTrue(Hash::check('NewPass1234', $user->fresh()->password));
        $this->assertSame($user->id, (int) session('mally_user_id'));
    }

    public function test_telegram_reset_is_refused_without_a_linked_account(): void
    {
        $owner = $this->emailUser();
        $passwordHash = $owner->password;

        $component = Livewire::test(HomePage::class)->call('openAuthModal')
            ->call('openForgotPassword')
            ->call('startTelegramReset');
        $this->botStart($this->tokenFrom($component));
        // Same phone as the email account, but this Telegram isn't linked to it.
        $this->botContact();

        $component->call('checkTelegramAuthStatus')
            ->set('authTelegramCode', $this->codeSentByBot())
            ->call('verifyTelegramCode')
            ->assertSet('authStep', 'reset-email')
            ->assertHasErrors('authTelegram')
            // Forcing the next step from the browser changes nothing.
            ->set('authPassword', 'NewPass1234')
            ->set('authPasswordConfirmation', 'NewPass1234')
            ->call('completePasswordReset');

        $this->assertSame($passwordHash, $owner->fresh()->password);
        $this->assertNull(session('mally_customer_id'));
    }

    public function test_webhook_requires_the_secret(): void
    {
        $update = ['update_id' => 1, 'message' => ['chat' => ['id' => 1, 'type' => 'private'], 'from' => ['id' => 1], 'text' => '/start']];

        $this->postJson('/api/telegram/webhook', $update)->assertUnauthorized();
        $this->postJson('/api/telegram/webhook', $update, ['X-Telegram-Bot-Api-Secret-Token' => 'nope'])->assertUnauthorized();
        $this->postJson('/api/telegram/webhook', $update, ['X-Telegram-Bot-Api-Secret-Token' => 'whsec'])->assertOk();
    }

    public function test_shoppers_cannot_open_the_admin_panel(): void
    {
        $shopper = $this->emailUser();
        $this->actingAs($shopper)->get('/admin')->assertForbidden();

        $this->actingAs(User::factory()->admin()->create())->get('/admin')->assertOk();
    }
}
