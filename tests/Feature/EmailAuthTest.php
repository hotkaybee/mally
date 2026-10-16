<?php

namespace Tests\Feature;

use App\Livewire\Storefront\HomePage;
use App\Mail\AuthCodeMail;
use App\Models\AuthCode;
use App\Models\Customer;
use App\Models\User;
use App\Services\EmailAuthService;
use Exception;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Schema;
use Livewire\Livewire;
use Tests\TestCase;

class EmailAuthTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Cache::flush();
    }

    public function test_stage1_auth_codes_and_users_phone_migrations_up_and_down_on_test_db(): void
    {
        $this->assertSame(':memory:', config('database.connections.sqlite.database'));

        $this->assertTrue(Schema::hasTable('auth_codes'));
        $this->assertTrue(Schema::hasColumns('auth_codes', [
            'id',
            'email',
            'purpose',
            'code_hash',
            'expires_at',
            'attempts',
            'used_at',
            'created_at',
            'updated_at',
        ]));

        $indexes = collect(Schema::getIndexes('auth_codes'));
        $this->assertTrue(
            $indexes->contains(fn (array $idx) => $idx['columns'] === ['email']),
            'Expected index on email column.'
        );
        $this->assertTrue(
            $indexes->contains(fn (array $idx) => $idx['columns'] === ['email', 'purpose']),
            'Expected composite index on (email, purpose) columns.'
        );

        $this->assertTrue(Schema::hasColumn('users', 'phone'));
        $this->assertFalse(Schema::hasColumn('users', 'role'));

        $code = AuthCode::create([
            'email' => 'test@example.com',
            'purpose' => AuthCode::PURPOSE_REGISTER,
            'code_hash' => 'hashed-secret',
            'expires_at' => now()->addMinutes(10),
        ]);

        $code->refresh();
        $this->assertSame(0, $code->attempts);
        $this->assertNull($code->used_at);
        $this->assertArrayNotHasKey('code_hash', $code->toArray());

        // Verify rollback and re-migration on the test database
        $phoneMigration = require base_path('database/migrations/2026_10_12_000001_add_phone_to_users_table.php');
        $authCodesMigration = require base_path('database/migrations/2026_10_12_000000_create_auth_codes_table.php');
        // A later migration indexes users.phone; it is rolled back first, as in a real rollback.
        $indexMigration = require base_path('database/migrations/2026_10_17_000000_add_lookup_indexes.php');

        $indexMigration->down();
        $phoneMigration->down();
        $authCodesMigration->down();

        $this->assertFalse(Schema::hasTable('auth_codes'));
        $this->assertFalse(Schema::hasColumn('users', 'phone'));

        $authCodesMigration->up();
        $phoneMigration->up();
        $indexMigration->up();

        $this->assertTrue(Schema::hasTable('auth_codes'));
        $this->assertTrue(Schema::hasColumn('users', 'phone'));
    }

    public function test_stage2_sends_6_digit_code_stores_only_hash_and_invalidates_previous_codes(): void
    {
        Mail::fake();
        $service = app(EmailAuthService::class);

        $firstRes = $service->sendCode('Aliya@Example.com', AuthCode::PURPOSE_REGISTER, '10.0.0.1');
        $this->assertTrue($firstRes['success']);

        $firstRecord = AuthCode::first();
        $this->assertNotNull($firstRecord);
        $this->assertSame('aliya@example.com', $firstRecord->email);
        $this->assertSame(AuthCode::PURPOSE_REGISTER, $firstRecord->purpose);
        $this->assertNull($firstRecord->used_at);

        $sentCode = null;
        Mail::assertSent(AuthCodeMail::class, function (AuthCodeMail $mail) use (&$sentCode) {
            $sentCode = $mail->code;
            $envelope = $mail->envelope();
            $rendered = $mail->render();

            return $mail->hasTo('aliya@example.com')
                && $envelope->subject === 'Код подтверждения Mally Store'
                && $envelope->from->name === 'Mally Store'
                && preg_match('/^\d{6}$/', $mail->code) === 1
                && str_contains($rendered, '<table')
                && str_contains($rendered, 'MALLY')
                && str_contains($rendered, '#FAF7F5')
                && str_contains($rendered, '#FCECEC')
                && str_contains($rendered, $mail->code);
        });

        $this->assertNotNull($sentCode);
        $this->assertNotSame($sentCode, $firstRecord->code_hash);
        $this->assertTrue(Hash::check($sentCode, $firstRecord->code_hash));
        $this->assertStringNotContainsString($sentCode, json_encode($firstRes));

        // Clear cooldown to request a second code and verify the first code is invalidated
        Cache::forget($service->cooldownCacheKey('aliya@example.com'));

        $secondRes = $service->sendCode('aliya@example.com', AuthCode::PURPOSE_REGISTER, '10.0.0.1');
        $this->assertTrue($secondRes['success']);

        $this->assertNotNull($firstRecord->fresh()->used_at);
        $activeCodes = AuthCode::where('email', 'aliya@example.com')
            ->where('purpose', AuthCode::PURPOSE_REGISTER)
            ->whereNull('used_at')
            ->get();
        $this->assertCount(1, $activeCodes);
    }

    public function test_stage2_enforces_60_second_cooldown_and_hourly_limits_per_email_and_ip(): void
    {
        Mail::fake();
        $service = app(EmailAuthService::class);

        $first = $service->sendCode('cooldown@example.com', AuthCode::PURPOSE_REGISTER, '10.0.0.10');
        $this->assertTrue($first['success']);

        // Immediate repeat within 60s must be rejected by cooldown
        $second = $service->sendCode('cooldown@example.com', AuthCode::PURPOSE_REGISTER, '10.0.0.10');
        $this->assertFalse($second['success']);
        $this->assertSame('cooldown', $second['error'] ?? null);

        // Hourly limit: 5 requests per hour per email (across different IPs after cooldown)
        for ($i = 2; $i <= 5; $i++) {
            Cache::forget($service->cooldownCacheKey('cooldown@example.com'));
            $res = $service->sendCode('cooldown@example.com', AuthCode::PURPOSE_REGISTER, "10.0.0.{$i}");
            $this->assertTrue($res['success'], "Expected attempt #{$i} for email to succeed.");
        }

        Cache::forget($service->cooldownCacheKey('cooldown@example.com'));
        $sixthEmailAttempt = $service->sendCode('cooldown@example.com', AuthCode::PURPOSE_REGISTER, '10.0.0.99');
        $this->assertFalse($sixthEmailAttempt['success']);
        $this->assertSame('rate_limit', $sixthEmailAttempt['error'] ?? null);

        // Hourly limit: 5 requests per hour per IP (across different emails)
        $sharedIp = '192.168.100.55';
        RateLimiter::clear($service->ipHourlyRateKey($sharedIp));

        for ($i = 1; $i <= 5; $i++) {
            $res = $service->sendCode("user{$i}@example.com", AuthCode::PURPOSE_REGISTER, $sharedIp);
            $this->assertTrue($res['success'], "Expected attempt #{$i} from IP to succeed.");
        }

        $sixthIpAttempt = $service->sendCode('user6@example.com', AuthCode::PURPOSE_REGISTER, $sharedIp);
        $this->assertFalse($sixthIpAttempt['success']);
        $this->assertSame('rate_limit', $sixthIpAttempt['error'] ?? null);
    }

    public function test_stage2_mail_failure_returns_safe_error_and_does_not_claim_sent(): void
    {
        Mail::shouldReceive('to')
            ->once()
            ->andThrow(new Exception('SMTP connection refused at 127.0.0.1:2525'));

        $service = app(EmailAuthService::class);
        $res = $service->sendCode('broken@example.com', AuthCode::PURPOSE_REGISTER, '10.0.0.50');

        $this->assertFalse($res['success']);
        $this->assertSame('mail_failed', $res['error'] ?? null);
        $this->assertStringNotContainsString('отправлено', mb_strtolower($res['message']));
        $this->assertStringNotContainsString('отправлен', mb_strtolower($res['message']));
        $this->assertStringNotContainsString('SMTP connection refused', $res['message']);
        $this->assertSame(0, AuthCode::where('email', 'broken@example.com')->count());
    }

    public function test_login_with_valid_email_and_password_succeeds_without_sending_code(): void
    {
        Mail::fake();

        $user = User::factory()->create([
            'name' => 'Малика Каримова',
            'email' => 'malika@example.com',
            'phone' => '+998 90 123 45 67',
            'password' => Hash::make('SecretPass123'),
        ]);

        Livewire::test(HomePage::class)
            ->call('openAuthModal')
            ->assertSet('authStep', 'login')
            ->assertSee('Вход в аккаунт')
            ->assertSee('Зарегистрироваться')
            ->set('authEmail', 'MALIKA@example.com')
            ->set('authPassword', 'SecretPass123')
            ->call('loginWithPassword')
            ->assertSet('isAuthModalOpen', false)
            ->assertHasNoErrors();

        Mail::assertNothingSent();
        $this->assertSame($user->id, session('mally_user_id'));
        $this->assertNotNull(session('mally_customer_id'));
    }

    public function test_login_with_wrong_password_fails_with_generic_error(): void
    {
        User::factory()->create([
            'email' => 'user@example.com',
            'password' => Hash::make('CorrectPass123'),
        ]);

        Livewire::test(HomePage::class)
            ->call('openAuthModal')
            ->set('authEmail', 'user@example.com')
            ->set('authPassword', 'WrongPassword999')
            ->call('loginWithPassword')
            ->assertSet('isAuthModalOpen', true)
            ->assertSet('authStep', 'login')
            ->assertSet('authOfferRegistration', false)
            ->assertHasErrors(['authPassword'])
            ->assertSee('Неверный email или пароль.');

        $this->assertNull(session('mally_user_id'));
        $this->assertNull(session('mally_customer_id'));
    }

    public function test_login_with_nonexistent_email_shows_not_found_message_and_offers_registration_with_same_email(): void
    {
        $component = Livewire::test(HomePage::class)
            ->call('openAuthModal')
            ->set('authEmail', 'Nobody@Example.com')
            ->set('authPassword', 'AnyPassword123')
            ->call('loginWithPassword')
            ->assertSet('isAuthModalOpen', true)
            ->assertSet('authStep', 'login')
            ->assertSet('authOfferRegistration', true)
            ->assertHasErrors(['authEmail'])
            ->assertSee('Аккаунта с таким email нет.')
            ->assertSee('Зарегистрироваться с этим email');

        $this->assertNull(session('mally_user_id'));
        $this->assertNull(session('mally_customer_id'));

        // Clicking "Зарегистрироваться с этим email" opens registration with the already entered email pre-filled
        $component
            ->call('startRegistrationWithEmail')
            ->assertSet('authStep', 'register-email')
            ->assertSet('authEmail', 'nobody@example.com')
            ->assertSet('authOfferRegistration', false)
            ->assertHasNoErrors();
    }

    public function test_registration_with_existing_email_shows_message_and_returns_to_login_with_email(): void
    {
        Mail::fake();

        User::factory()->create([
            'email' => 'existing@example.com',
            'password' => Hash::make('ExistingPass123'),
        ]);

        Livewire::test(HomePage::class)
            ->call('openAuthModal')
            ->call('openRegister')
            ->assertSet('authStep', 'register-email')
            ->set('authEmail', 'EXISTING@example.com')
            ->call('sendRegisterCode')
            ->assertSet('authStep', 'login')
            ->assertSet('authEmail', 'existing@example.com')
            ->assertHasErrors(['authEmail'])
            ->assertSee('Этот email уже зарегистрирован. Войдите с паролем.');

        Mail::assertNothingSent();
    }

    public function test_login_with_empty_email_or_empty_password_returns_validation_error(): void
    {
        Livewire::test(HomePage::class)
            ->call('openAuthModal')
            ->set('authEmail', '')
            ->set('authPassword', 'SomePassword123')
            ->call('loginWithPassword')
            ->assertHasErrors(['authEmail'])
            ->assertSee('Введите корректный адрес электронной почты.');

        User::factory()->create([
            'email' => 'user@example.com',
            'password' => Hash::make('CorrectPass123'),
        ]);

        Livewire::test(HomePage::class)
            ->call('openAuthModal')
            ->set('authEmail', 'user@example.com')
            ->set('authPassword', '')
            ->call('loginWithPassword')
            ->assertHasErrors(['authPassword'])
            ->assertSee('Введите пароль.');
    }

    public function test_login_rate_limiting_blocks_sixth_attempt_within_a_minute(): void
    {
        User::factory()->create([
            'email' => 'throttled@example.com',
            'password' => Hash::make('CorrectPass123'),
        ]);

        $component = Livewire::test(HomePage::class)
            ->call('openAuthModal')
            ->set('authEmail', 'throttled@example.com');

        for ($i = 1; $i <= 5; $i++) {
            $component
                ->set('authPassword', 'WrongPass'.$i)
                ->call('loginWithPassword')
                ->assertSee('Неверный email или пароль.');
        }

        // 6th attempt even with the right password is blocked by rate limiter
        $component
            ->set('authPassword', 'CorrectPass123')
            ->call('loginWithPassword')
            ->assertHasErrors(['authPassword'])
            ->assertSee('Слишком много попыток входа.');

        $this->assertNull(session('mally_user_id'));
    }

    public function test_password_spraying_across_many_accounts_from_one_ip_is_throttled(): void
    {
        User::factory()->create([
            'email' => 'victim@example.com',
            'password' => Hash::make('CorrectPass123'),
        ]);
        $auth = app(EmailAuthService::class);

        // One wrong guess per account: never trips the per-account limit.
        for ($i = 1; $i <= EmailAuthService::MAX_LOGIN_FAILURES_PER_IP; $i++) {
            $this->assertFalse($auth->attemptLogin("guess{$i}@example.com", 'Password1', '10.0.0.9')['success']);
        }

        $blocked = $auth->attemptLogin('victim@example.com', 'CorrectPass123', '10.0.0.9');
        $this->assertSame('rate_limit', $blocked['error'] ?? null);

        // Another IP is not affected.
        $this->assertTrue($auth->attemptLogin('victim@example.com', 'CorrectPass123', '10.0.0.10')['success']);
    }

    public function test_registration_sends_hashed_code_and_rejects_wrong_code_without_changing_step(): void
    {
        Mail::fake();

        $component = Livewire::test(HomePage::class)
            ->call('openAuthModal')
            ->call('openRegister')
            ->assertSet('authStep', 'register-email')
            ->set('authEmail', 'newbuyer@example.com')
            ->call('sendRegisterCode')
            ->assertSet('authStep', 'register-code');

        Mail::assertSent(AuthCodeMail::class, function (AuthCodeMail $mail) {
            return $mail->hasTo('newbuyer@example.com')
                && $mail->purpose === AuthCode::PURPOSE_REGISTER
                && $mail->envelope()->subject === 'Код подтверждения Mally Store'
                && $mail->envelope()->from->name === 'Mally Store';
        });

        $authCode = AuthCode::where('email', 'newbuyer@example.com')
            ->where('purpose', AuthCode::PURPOSE_REGISTER)
            ->firstOrFail();

        // Ensure raw 6-digit code is not stored in code_hash
        $this->assertNotSame(6, strlen($authCode->code_hash));
        $this->assertTrue(str_starts_with($authCode->code_hash, '$2y$'));

        // Enter wrong code
        $component
            ->set('authCode', '000000')
            ->call('verifyRegisterCode')
            ->assertSet('authStep', 'register-code')
            ->assertHasErrors(['authCode'])
            ->assertSee('Неверный код подтверждения.');

        $this->assertSame(1, $authCode->fresh()->attempts);
    }

    public function test_registration_rejects_expired_code(): void
    {
        AuthCode::create([
            'email' => 'expired@example.com',
            'purpose' => AuthCode::PURPOSE_REGISTER,
            'code_hash' => Hash::make('123456'),
            'expires_at' => now()->subMinutes(11),
            'attempts' => 0,
            'used_at' => null,
        ]);

        Livewire::test(HomePage::class)
            ->call('openAuthModal')
            ->set('authEmail', 'expired@example.com')
            ->set('authCode', '123456')
            ->call('verifyRegisterCode')
            ->assertSet('authStep', 'register-code')
            ->assertHasErrors(['authCode'])
            ->assertSee('Срок действия кода истёк');
    }

    public function test_registration_rejects_reused_code(): void
    {
        AuthCode::create([
            'email' => 'reused@example.com',
            'purpose' => AuthCode::PURPOSE_REGISTER,
            'code_hash' => Hash::make('654321'),
            'expires_at' => now()->addMinutes(10),
            'attempts' => 0,
            'used_at' => null,
        ]);

        // First verification succeeds
        Livewire::test(HomePage::class)
            ->call('openAuthModal')
            ->set('authEmail', 'reused@example.com')
            ->set('authCode', '654321')
            ->call('verifyRegisterCode')
            ->assertSet('authStep', 'register-profile')
            ->assertHasNoErrors();

        // Second verification with the same code is rejected
        Livewire::test(HomePage::class)
            ->call('openAuthModal')
            ->set('authEmail', 'reused@example.com')
            ->set('authCode', '654321')
            ->call('verifyRegisterCode')
            ->assertSet('authStep', 'register-code')
            ->assertHasErrors(['authCode'])
            ->assertSee('Код недействителен или уже был использован.');
    }

    public function test_registration_validates_password_length_confirmation_and_phone(): void
    {
        AuthCode::create([
            'email' => 'validating@example.com',
            'purpose' => AuthCode::PURPOSE_REGISTER,
            'code_hash' => Hash::make('222333'),
            'expires_at' => now()->addMinutes(10),
            'attempts' => 0,
            'used_at' => null,
        ]);

        $component = Livewire::test(HomePage::class)
            ->call('openAuthModal')
            ->set('authEmail', 'validating@example.com')
            ->set('authCode', '222333')
            ->call('verifyRegisterCode')
            ->assertSet('authStep', 'register-profile');

        // 1. Empty / invalid phone
        $component
            ->set('authName', 'Азиза')
            ->set('authPhone', '12345')
            ->set('authPassword', 'ValidPass123')
            ->set('authPasswordConfirmation', 'ValidPass123')
            ->call('completeEmailRegistration')
            ->assertHasErrors(['authPhone'])
            ->assertSee('Введите корректный номер телефона');

        // 2. Password shorter than 8 characters
        $component
            ->set('authPhone', '90 123 45 67')
            ->set('authPassword', 'short12')
            ->set('authPasswordConfirmation', 'short12')
            ->call('completeEmailRegistration')
            ->assertHasErrors(['authPassword'])
            ->assertSee('Пароль должен содержать минимум 8 символов.');

        // 3. Passwords do not match
        $component
            ->set('authPassword', 'ValidPass123')
            ->set('authPasswordConfirmation', 'DifferentPass123')
            ->call('completeEmailRegistration')
            ->assertHasErrors(['authPasswordConfirmation'])
            ->assertSee('Пароли не совпадают.');

        $this->assertDatabaseMissing('users', ['email' => 'validating@example.com']);
    }

    public function test_registration_does_not_create_account_before_code_verification(): void
    {
        Mail::fake();

        Livewire::test(HomePage::class)
            ->call('openAuthModal')
            ->call('openRegister')
            ->set('authEmail', 'unverified@example.com')
            ->call('sendRegisterCode')
            ->assertSet('authStep', 'register-code')
            // Attempt to call completeEmailRegistration directly without verifying the code
            ->set('authName', 'Хакер')
            ->set('authPhone', '+998 90 111 22 33')
            ->set('authPassword', 'StrongPass123')
            ->set('authPasswordConfirmation', 'StrongPass123')
            ->call('completeEmailRegistration')
            ->assertSet('authStep', 'register-email')
            ->assertHasErrors(['authEmail']);

        $this->assertDatabaseMissing('users', ['email' => 'unverified@example.com']);
        $this->assertDatabaseMissing('customers', ['email' => 'unverified@example.com']);
    }

    public function test_full_registration_creates_user_with_hashed_password_and_normalized_phone(): void
    {
        AuthCode::create([
            'email' => 'complete@example.com',
            'purpose' => AuthCode::PURPOSE_REGISTER,
            'code_hash' => Hash::make('888999'),
            'expires_at' => now()->addMinutes(10),
            'attempts' => 0,
            'used_at' => null,
        ]);

        Livewire::test(HomePage::class)
            ->call('openAuthModal')
            ->set('authEmail', 'complete@example.com')
            ->set('authCode', '888999')
            ->call('verifyRegisterCode')
            ->assertSet('authStep', 'register-profile')
            // Try tampering with public authEmail property; registration must use session email
            ->set('authEmail', 'tampered@example.com')
            ->set('authName', 'Дильноза Алиева')
            ->set('authPhone', '901234567')
            ->set('authPassword', 'MySecurePass123')
            ->set('authPasswordConfirmation', 'MySecurePass123')
            ->call('completeEmailRegistration')
            ->assertSet('isAuthModalOpen', false)
            ->assertHasNoErrors();

        $user = User::where('email', 'complete@example.com')->firstOrFail();
        $this->assertSame('Дильноза Алиева', $user->name);
        $this->assertSame('+998 90 123 45 67', $user->phone);
        $this->assertFalse(Schema::hasColumn('users', 'role'));
        $this->assertTrue(Hash::check('MySecurePass123', $user->password));
        $this->assertDatabaseMissing('users', ['email' => 'tampered@example.com']);
        $this->assertSame($user->id, session('mally_user_id'));
        $this->assertNotNull(session('mally_customer_id'));
        $this->assertSame('website', Customer::findOrFail(session('mally_customer_id'))->source);
    }

    public function test_password_reset_flow_sends_reset_code_verifies_updates_hashed_password_and_logs_in_immediately(): void
    {
        Mail::fake();

        $user = User::factory()->create([
            'email' => 'resetme@example.com',
            'password' => Hash::make('OldPassword123'),
        ]);

        $component = Livewire::test(HomePage::class)
            ->call('openAuthModal')
            ->call('openForgotPassword')
            ->assertSet('authStep', 'reset-email')
            ->set('authEmail', 'resetme@example.com')
            ->call('sendPasswordResetCode')
            ->assertSet('authStep', 'reset-code');

        Mail::assertSent(AuthCodeMail::class, function (AuthCodeMail $mail) {
            return $mail->hasTo('resetme@example.com')
                && $mail->purpose === AuthCode::PURPOSE_RESET;
        });

        // Replace code hash with known code for testing verification
        $authCode = AuthCode::where('email', 'resetme@example.com')
            ->where('purpose', AuthCode::PURPOSE_RESET)
            ->latest('id')
            ->firstOrFail();
        $authCode->update(['code_hash' => Hash::make('445566')]);

        // Invalid code fails
        $component
            ->set('authCode', '111222')
            ->call('verifyPasswordResetCode')
            ->assertSet('authStep', 'reset-code')
            ->assertHasErrors(['authCode']);

        // Valid code transitions to reset-password step
        $component
            ->set('authCode', '445566')
            ->call('verifyPasswordResetCode')
            ->assertSet('authStep', 'reset-password');

        // Mismatched confirmation password fails on authPasswordConfirmation
        $component
            ->set('authPassword', 'NewPassword789')
            ->set('authPasswordConfirmation', 'Mismatch789')
            ->call('completePasswordReset')
            ->assertSet('authStep', 'reset-password')
            ->assertHasErrors(['authPasswordConfirmation'])
            ->assertSee('Пароли не совпадают.');

        // Suppose another unused reset code was also created before password reset completes
        $unusedOldResetCode = AuthCode::create([
            'email' => 'resetme@example.com',
            'purpose' => AuthCode::PURPOSE_RESET,
            'code_hash' => Hash::make('333222'),
            'expires_at' => now()->addMinutes(10),
            'attempts' => 0,
            'used_at' => null,
        ]);

        // Saving matching password updates hashed password, logs in immediately, and closes the modal
        $component
            ->set('authPassword', 'NewPassword789')
            ->set('authPasswordConfirmation', 'NewPassword789')
            ->call('completePasswordReset')
            ->assertSet('isAuthModalOpen', false)
            ->assertHasNoErrors();

        $user->refresh();
        $this->assertFalse(Hash::check('OldPassword123', $user->password));
        $this->assertTrue(Hash::check('NewPassword789', $user->password));
        $this->assertSame($user->id, session('mally_user_id'));
        $this->assertNotNull(session('mally_customer_id'));

        // 1) Reusing the verified reset code (445566) after password reset fails
        Livewire::test(HomePage::class)
            ->call('openAuthModal')
            ->set('authEmail', 'resetme@example.com')
            ->set('authCode', '445566')
            ->call('verifyPasswordResetCode')
            ->assertSet('authStep', 'reset-code')
            ->assertHasErrors(['authCode'])
            ->assertSee('Код недействителен или уже был использован.');

        // 2) Any other previously issued unused reset code (333222) is also invalidated after password reset
        $this->assertNotNull($unusedOldResetCode->fresh()->used_at);
        Livewire::test(HomePage::class)
            ->call('openAuthModal')
            ->set('authEmail', 'resetme@example.com')
            ->set('authCode', '333222')
            ->call('verifyPasswordResetCode')
            ->assertSet('authStep', 'reset-code')
            ->assertHasErrors(['authCode'])
            ->assertSee('Код недействителен или уже был использован.');
    }

    public function test_password_reset_rejects_expired_code_and_does_not_leak_email_existence(): void
    {
        Mail::fake();

        // Requesting reset for unknown email transitions to reset-code without sending email
        Livewire::test(HomePage::class)
            ->call('openAuthModal')
            ->call('openForgotPassword')
            ->set('authEmail', 'ghost@example.com')
            ->call('sendPasswordResetCode')
            ->assertSet('authStep', 'reset-code');

        Mail::assertNothingSent();

        // Expired reset code is rejected
        AuthCode::create([
            'email' => 'expiredreset@example.com',
            'purpose' => AuthCode::PURPOSE_RESET,
            'code_hash' => Hash::make('777888'),
            'expires_at' => now()->subMinute(),
            'attempts' => 0,
            'used_at' => null,
        ]);

        Livewire::test(HomePage::class)
            ->call('openAuthModal')
            ->set('authEmail', 'expiredreset@example.com')
            ->set('authCode', '777888')
            ->call('verifyPasswordResetCode')
            ->assertSet('authStep', 'reset-code')
            ->assertHasErrors(['authCode'])
            ->assertSee('Срок действия кода истёк');
    }

    public function test_resend_code_before_60_seconds_cooldown_is_blocked(): void
    {
        Mail::fake();

        Livewire::test(HomePage::class)
            ->call('openAuthModal')
            ->call('openRegister')
            ->set('authEmail', 'cooldown@example.com')
            ->call('sendRegisterCode')
            ->assertSet('authStep', 'register-code')
            ->call('resendRegisterCode')
            ->assertHasErrors(['authCode'])
            ->assertSee('Повторная отправка кода возможна через');

        Mail::assertSentCount(1);
    }

    public function test_five_wrong_code_attempts_burn_the_code(): void
    {
        AuthCode::create([
            'email' => 'burn@example.com',
            'purpose' => AuthCode::PURPOSE_REGISTER,
            'code_hash' => Hash::make('999111'),
            'expires_at' => now()->addMinutes(10),
            'attempts' => 0,
            'used_at' => null,
        ]);

        $component = Livewire::test(HomePage::class)
            ->call('openAuthModal')
            ->set('authEmail', 'burn@example.com');

        for ($i = 1; $i <= 5; $i++) {
            $component
                ->set('authCode', '00000'.$i)
                ->call('verifyRegisterCode')
                ->assertSet('authStep', 'register-code')
                ->assertHasErrors(['authCode']);
        }

        // 6th attempt with the actual valid code must now fail because the code is burned
        $component
            ->set('authCode', '999111')
            ->call('verifyRegisterCode')
            ->assertSet('authStep', 'register-code')
            ->assertHasErrors(['authCode']);
    }

    private function sendRegisterCode(EmailAuthService $service, string $email): string
    {
        $code = null;
        Mail::fake();
        Cache::forget($service->cooldownCacheKey($email));
        $this->assertTrue($service->sendCode($email, AuthCode::PURPOSE_REGISTER, '10.0.0.9')['success']);
        Mail::assertSent(AuthCodeMail::class, function (AuthCodeMail $mail) use (&$code) {
            $code = $mail->code;

            return true;
        });

        return $code;
    }

    private function wrongCode(string $code): string
    {
        return $code === '000000' ? '111111' : '000000';
    }

    public function test_code_is_not_checked_once_attempts_are_used_up_even_if_correct(): void
    {
        $service = app(EmailAuthService::class);
        $code = $this->sendRegisterCode($service, 'race@example.com');

        // State after five parallel wrong guesses that all read the code
        // before any of them could burn it.
        for ($i = 0; $i < EmailAuthService::MAX_CODE_ATTEMPTS; $i++) {
            RateLimiter::hit($service->codeAttemptsKey('race@example.com', AuthCode::PURPOSE_REGISTER), 600);
        }

        $res = $service->verifyCode('race@example.com', AuthCode::PURPOSE_REGISTER, $code);
        $this->assertFalse($res['success']);
        $this->assertSame('max_attempts', $res['error']);
    }

    public function test_successful_code_check_resets_the_attempt_counter(): void
    {
        $service = app(EmailAuthService::class);
        $code = $this->sendRegisterCode($service, 'reset@example.com');

        for ($i = 0; $i < EmailAuthService::MAX_CODE_ATTEMPTS - 1; $i++) {
            $this->assertSame('wrong_code', $service->verifyCode('reset@example.com', AuthCode::PURPOSE_REGISTER, $this->wrongCode($code))['error']);
        }

        $this->assertTrue($service->verifyCode('reset@example.com', AuthCode::PURPOSE_REGISTER, $code)['success']);
        $this->assertSame(0, RateLimiter::attempts($service->codeAttemptsKey('reset@example.com', AuthCode::PURPOSE_REGISTER)));
    }

    public function test_new_code_resets_the_attempt_counter(): void
    {
        $service = app(EmailAuthService::class);
        $code = $this->sendRegisterCode($service, 'again@example.com');

        for ($i = 0; $i < EmailAuthService::MAX_CODE_ATTEMPTS - 1; $i++) {
            $service->verifyCode('again@example.com', AuthCode::PURPOSE_REGISTER, $this->wrongCode($code));
        }

        $code = $this->sendRegisterCode($service, 'again@example.com');
        for ($i = 0; $i < EmailAuthService::MAX_CODE_ATTEMPTS - 1; $i++) {
            $this->assertSame('wrong_code', $service->verifyCode('again@example.com', AuthCode::PURPOSE_REGISTER, $this->wrongCode($code))['error']);
        }

        $this->assertTrue($service->verifyCode('again@example.com', AuthCode::PURPOSE_REGISTER, $code)['success']);
    }

    public function test_hourly_rate_limits_for_email_and_ip_block_sixth_request(): void
    {
        Mail::fake();
        $service = app(EmailAuthService::class);

        // 1. Test 5 requests/hour per email (clearing 60s cooldown and IP limiter between requests)
        for ($i = 1; $i <= 5; $i++) {
            Cache::forget($service->cooldownCacheKey('hourly@example.com'));
            RateLimiter::clear($service->ipHourlyRateKey('127.0.0.1'));

            Livewire::test(HomePage::class)
                ->call('openAuthModal')
                ->call('openRegister')
                ->set('authEmail', 'hourly@example.com')
                ->call('sendRegisterCode')
                ->assertSet('authStep', 'register-code');
        }

        Cache::forget($service->cooldownCacheKey('hourly@example.com'));
        RateLimiter::clear($service->ipHourlyRateKey('127.0.0.1'));

        Livewire::test(HomePage::class)
            ->call('openAuthModal')
            ->call('openRegister')
            ->set('authEmail', 'hourly@example.com')
            ->call('sendRegisterCode')
            ->assertSet('authStep', 'register-email')
            ->assertHasErrors(['authEmail'])
            ->assertSee('Превышен лимит отправки кодов');

        // 2. Test 5 requests/hour per IP across different emails
        RateLimiter::clear($service->ipHourlyRateKey('127.0.0.1'));
        for ($i = 1; $i <= 5; $i++) {
            Livewire::test(HomePage::class)
                ->call('openAuthModal')
                ->call('openRegister')
                ->set('authEmail', "ipuser{$i}@example.com")
                ->call('sendRegisterCode')
                ->assertSet('authStep', 'register-code');
        }

        Livewire::test(HomePage::class)
            ->call('openAuthModal')
            ->call('openRegister')
            ->set('authEmail', 'ipuser6@example.com')
            ->call('sendRegisterCode')
            ->assertSet('authStep', 'register-email')
            ->assertHasErrors(['authEmail'])
            ->assertSee('Превышен лимит отправки кодов');
    }

    public function test_external_redirect_url_is_ignored_and_sanitized_to_internal_path(): void
    {
        User::factory()->create([
            'email' => 'redirect@example.com',
            'password' => Hash::make('Password12345'),
        ]);

        Livewire::test(HomePage::class)
            ->call('openAuthModal', 'Войдите в аккаунт', 'https://evil.example.com/phishing')
            ->assertSet('authRedirectTo', '/')
            ->set('authEmail', 'redirect@example.com')
            ->set('authPassword', 'Password12345')
            ->call('loginWithPassword')
            ->assertRedirect('/');

        // Protocol-relative URL is also rejected
        Livewire::test(HomePage::class)
            ->call('openAuthModal', 'Войдите в аккаунт', '//evil.example.com/phishing')
            ->assertSet('authRedirectTo', '/');

        // Internal path is preserved
        Livewire::test(HomePage::class)
            ->call('openAuthModal', 'Войдите в аккаунт', '/profile')
            ->assertSet('authRedirectTo', '/profile');
    }

    public function test_end_to_end_flow_with_log_mailer_and_admin_login_page_intact(): void
    {
        config(['mail.default' => 'log']);
        $logPath = storage_path('logs/laravel.log');
        $initialSize = file_exists($logPath) ? filesize($logPath) : 0;

        // 1. Open modal -> try nonexistent email -> get offer to register with same email
        $component = Livewire::test(HomePage::class)
            ->call('openAuthModal', 'Sign in to view your orders and profile')
            ->assertDontSeeHtml('<span>Sign in to view your orders and profile</span>')
            ->assertDontSee('Код подтверждения отправлен на вашу электронную почту.')
            ->set('authEmail', 'e2e.buyer@example.com')
            ->set('authPassword', 'AnyPass123')
            ->call('loginWithPassword')
            ->assertSee('Аккаунта с таким email нет.')
            ->assertSee('Зарегистрироваться с этим email')
            ->call('startRegistrationWithEmail')
            ->assertSet('authStep', 'register-email')
            ->assertSet('authEmail', 'e2e.buyer@example.com')
            ->call('sendRegisterCode')
            ->assertSet('authStep', 'register-code');

        // Extract 6-digit registration code from storage/logs/laravel.log
        clearstatcache(true, $logPath);
        $logTail = (string) file_get_contents($logPath, false, null, $initialSize);
        preg_match_all('/\b(\d{6})\b/', $logTail, $matches);
        $regCode = end($matches[1]);
        $this->assertMatchesRegularExpression('/^\d{6}$/', (string) $regCode);

        // 2. Verify registration code and complete profile
        $component
            ->set('authCode', (string) $regCode)
            ->call('verifyRegisterCode')
            ->assertSet('authStep', 'register-profile')
            ->set('authName', 'Камила Рустамова')
            ->set('authPhone', '+998 90 555 44 33')
            ->set('authPassword', 'InitialPass123')
            ->set('authPasswordConfirmation', 'InitialPass123')
            ->call('completeEmailRegistration')
            ->assertSet('isAuthModalOpen', false);

        $this->assertNotNull(session('mally_user_id'));
        $this->assertNotNull(session('mally_customer_id'));

        // 3. Logout
        $component->call('logoutCustomer');
        $this->assertNull(session('mally_user_id'));
        $this->assertNull(session('mally_customer_id'));

        // 4. Wrong password login attempt
        $component
            ->call('openAuthModal')
            ->set('authEmail', 'e2e.buyer@example.com')
            ->set('authPassword', 'WrongPass000')
            ->call('loginWithPassword')
            ->assertSee('Неверный email или пароль.');

        // 5. Password reset via code from storage/logs/laravel.log and immediate auto-login
        Cache::flush();
        clearstatcache(true, $logPath);
        $beforeResetOffset = file_exists($logPath) ? filesize($logPath) : 0;

        $component
            ->call('openForgotPassword')
            ->assertSet('authStep', 'reset-email')
            ->call('sendPasswordResetCode')
            ->assertSet('authStep', 'reset-code');

        clearstatcache(true, $logPath);
        $resetLogTail = (string) file_get_contents($logPath, false, null, $beforeResetOffset);
        preg_match_all('/\b(\d{6})\b/', $resetLogTail, $resetMatches);
        $resetCode = end($resetMatches[1]);
        $this->assertMatchesRegularExpression('/^\d{6}$/', (string) $resetCode);

        $component
            ->set('authCode', (string) $resetCode)
            ->call('verifyPasswordResetCode')
            ->assertSet('authStep', 'reset-password')
            ->set('authPassword', 'UpdatedPass999')
            ->set('authPasswordConfirmation', 'UpdatedPass999')
            ->call('completePasswordReset')
            ->assertSet('isAuthModalOpen', false);

        $this->assertNotNull(session('mally_user_id'));
        $this->assertNotNull(session('mally_customer_id'));

        // 6. Verify /admin/login opens normally
        $this->get('/admin/login')->assertOk();
    }
}
