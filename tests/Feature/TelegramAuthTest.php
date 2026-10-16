<?php

namespace Tests\Feature;

use App\Livewire\Storefront\HomePage;
use App\Livewire\Storefront\ProfilePage;
use App\Models\Customer;
use App\Models\Order;
use App\Models\TelegramLoginRequest;
use App\Models\User;
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
            ->assertDontSeeHtml('wire:model="authPassword"')
            ->set('authName', 'Ali Valiev')
            ->call('completeTelegramRegistration')
            ->assertSet('isAuthModalOpen', false);

        $user = User::sole();
        $this->assertSame(self::TG_ID, $user->telegram_id);
        $this->assertSame(self::PHONE, $user->phone);
        $this->assertNotNull($user->phone_verified_at);
        $this->assertFalse($user->is_admin);
        // No password was asked for: phone + password can't sign into this account.
        $this->assertFalse(Hash::check('', $user->password));
        $this->assertFalse(app(EmailAuthService::class)->attemptLogin(self::PHONE, 'Password123', '10.1.1.1')['success']);

        $customer = Customer::sole();
        $this->assertSame($user->id, $customer->user_id);
        $this->assertSame('@ali_tg', $customer->telegram);
        $this->assertSame('telegram', $customer->source);
        $this->assertSame($customer->id, session('mally_customer_id'));
        $this->assertNotSame($sessionBefore, session()->getId(), 'session id must be regenerated on login');

        // The bot really asked for the contact and the code went to that chat.
        Http::assertSent(fn (Request $r) => str_contains($r->url(), '/sendMessage') && str_contains((string) $r['reply_markup'], 'request_contact'));
        $this->assertSame(1, TelegramLoginRequest::where('status', 'used')->count());
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
        $this->assertNotSame('used', $request->fresh()->status);
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

    public function test_existing_phone_is_not_merged_or_signed_in_and_can_be_linked_with_the_password(): void
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

        // The owner proves the account with its password -> Telegram gets linked.
        Livewire::test(HomePage::class)->call('openAuthModal')
            ->set('authEmail', self::PHONE)
            ->set('authPassword', 'OwnerPass123')
            ->call('loginWithPassword')
            ->assertSet('isAuthModalOpen', false);

        $this->assertSame(self::TG_ID, $owner->fresh()->telegram_id);
        $this->assertNotNull($owner->fresh()->phone_verified_at);
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
            ->call('completeTelegramRegistration')
            ->assertSet('authStep', 'login');

        $this->assertSame(1, User::count());
        $this->assertSame('Gmail Owner', User::sole()->name);
        $this->assertNull(session('mally_customer_id'));
    }

    public function test_crm_customer_with_the_same_phone_keeps_its_orders(): void
    {
        // A customer created by the admin/bot API: same phone, no login.
        $crm = Customer::create([
            'first_name' => 'Old', 'last_name' => 'Client', 'email' => 'crm@example.com',
            'phone_number' => self::PHONE, 'source' => 'telegram',
        ]);
        $order = Order::create(['customer_id' => $crm->id, 'amount' => 10, 'status' => 'processing']);

        $this->throughBot()
            ->set('authTelegramCode', $this->codeSentByBot())
            ->call('verifyTelegramCode')
            ->set('authName', 'Ali')
            ->call('completeTelegramRegistration');

        $mine = Customer::where('user_id', User::sole()->id)->sole();
        $this->assertNotSame($crm->id, $mine->id);
        $this->assertSame($crm->id, $order->fresh()->customer_id);
        $this->assertNull($crm->fresh()->user_id);

        // The new account sees only its own orders.
        $this->assertSame([], Livewire::test(ProfilePage::class)->viewData('orders')->pluck('id')->all());
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

    public function test_signed_in_user_links_telegram_from_the_profile(): void
    {
        $user = $this->emailUser(['phone' => '+998 91 000 00 00']);
        Livewire::test(HomePage::class)->call('openAuthModal')
            ->set('authEmail', 'owner@gmail.com')->set('authPassword', 'OwnerPass123')->call('loginWithPassword');

        $component = Livewire::test(ProfilePage::class)->call('startTelegramPhoneVerification')
            ->assertSet('authStep', 'telegram-wait');
        $this->botStart($this->tokenFrom($component));
        $this->botContact();
        $component->call('checkTelegramAuthStatus')
            ->set('authTelegramCode', $this->codeSentByBot())
            ->call('verifyTelegramCode');

        $user->refresh();
        $this->assertSame(self::TG_ID, $user->telegram_id);
        $this->assertSame(self::PHONE, $user->phone);
        $this->assertNotNull($user->phone_verified_at);
    }

    public function test_telegram_already_linked_elsewhere_is_not_moved(): void
    {
        $other = $this->emailUser(['email' => 'other@gmail.com', 'phone' => '+998 93 333 33 33', 'telegram_id' => self::TG_ID]);
        $me = $this->emailUser(['phone' => '+998 91 000 00 00']);
        Livewire::test(HomePage::class)->call('openAuthModal')
            ->set('authEmail', 'owner@gmail.com')->set('authPassword', 'OwnerPass123')->call('loginWithPassword');

        $component = Livewire::test(ProfilePage::class)->call('startTelegramPhoneVerification');
        $this->botStart($this->tokenFrom($component));
        $this->botContact('+998 97 777 77 77');
        $component->call('checkTelegramAuthStatus')
            ->set('authTelegramCode', $this->codeSentByBot())
            ->call('verifyTelegramCode')
            ->assertDispatched('toast');

        $this->assertNull($me->fresh()->telegram_id);
        $this->assertSame(self::TG_ID, $other->fresh()->telegram_id);
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
