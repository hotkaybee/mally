<?php

namespace Tests\Feature;

use App\Jobs\NotifyAdminsAboutOrder;
use App\Livewire\Storefront\CheckoutPage;
use App\Livewire\Storefront\HomePage;
use App\Models\AuthCode;
use App\Models\Customer;
use App\Models\Order;
use App\Models\Product;
use App\Models\User;
use App\Services\EmailAuthService;
use App\Services\ExchangeRateService;
use App\Services\StorefrontService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Tests\TestCase;

class ProductionHardeningTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Cache::put(ExchangeRateService::CACHE_KEY, ['rate' => 12000, 'source' => 'cbu.uz', 'isLive' => true], 3600);
        Bus::fake([NotifyAdminsAboutOrder::class]);
    }

    private function product(int $stock = 40): Product
    {
        $product = Product::create(['name' => 'Brush', 'sku' => 'ML-BRUSH', 'quantity' => 0, 'selling_price' => 0, 'status' => 'in_stock']);
        $product->receipts()->create([
            'purchase_price' => 5, 'delivery_cost' => 1, 'markup' => 0, 'selling_price' => 20,
            'website_price' => 0, 'quantity' => $stock, 'received_at' => now(),
        ]);

        return $product->fresh();
    }

    private function signedInCustomer(): Customer
    {
        $customer = Customer::create([
            'first_name' => 'Madina', 'last_name' => 'U', 'email' => 'madina@example.com',
            'phone_number' => '+998 90 123 45 67', 'city' => 'Ташкент', 'address' => 'ул. 1', 'source' => 'instagram',
        ]);
        session(['mally_customer_id' => $customer->id]);

        return $customer;
    }

    private function checkoutOnce(): void
    {
        app(StorefrontService::class)->addToCart('ML-BRUSH');
        Livewire::test(CheckoutPage::class)->call('placeOrder');
    }

    public function test_one_account_cannot_reserve_stock_with_endless_unpaid_orders(): void
    {
        $this->product();
        $customer = $this->signedInCustomer();

        for ($i = 0; $i < CheckoutPage::MAX_OPEN_UNPAID_ORDERS + 1; $i++) {
            $this->checkoutOnce();
        }

        $this->assertSame(CheckoutPage::MAX_OPEN_UNPAID_ORDERS, Order::where('customer_id', $customer->id)->count());

        // Once one is paid, the customer can order again.
        Order::first()->update(['amount_paid' => Order::first()->amount]);
        $this->checkoutOnce();
        $this->assertSame(CheckoutPage::MAX_OPEN_UNPAID_ORDERS + 1, Order::count());
    }

    public function test_parallel_checkouts_of_one_customer_run_one_at_a_time(): void
    {
        $this->product();
        $customer = $this->signedInCustomer();

        // Another request of the same customer is placing an order right now.
        $lock = Cache::lock('checkout-customer:'.$customer->id, 30);
        $lock->get();

        $this->checkoutOnce();
        $this->assertSame(0, Order::count());

        $lock->release();
        $this->checkoutOnce();
        $this->assertSame(1, Order::count());
    }

    public function test_a_session_without_a_password_fingerprint_is_signed_out(): void
    {
        $user = User::create(['name' => 'A', 'email' => 'a@example.com', 'password' => Hash::make('Pass12345')]);
        $customer = Customer::create(['user_id' => $user->id, 'first_name' => 'A', 'last_name' => 'B', 'email' => 'a@example.com', 'source' => 'website']);

        // Signed in before fingerprints existed: it must not adopt the current password.
        session(['mally_customer_id' => $customer->id, 'mally_user_id' => $user->id]);

        $this->assertNull(app(StorefrontService::class)->getCurrentCustomer());
        $this->assertNull(session('mally_customer_id'));
    }

    public function test_promo_codes_cannot_be_guessed_endlessly(): void
    {
        $this->product();
        $this->signedInCustomer();
        app(StorefrontService::class)->addToCart('ML-BRUSH');

        $page = Livewire::test(CheckoutPage::class);
        for ($i = 0; $i < CheckoutPage::MAX_PROMO_ATTEMPTS; $i++) {
            $page->set('promoCode', 'GUESS'.$i)->call('applyPromo');
        }

        // Even the real code is refused until the limit expires.
        $page->set('promoCode', 'MALLY10')->call('applyPromo')->assertSet('appliedPromo', null);
    }

    public function test_one_account_is_locked_after_failures_from_many_ips(): void
    {
        User::create(['name' => 'A', 'email' => 'victim@example.com', 'password' => Hash::make('RightPass123')]);
        $auth = app(EmailAuthService::class);

        for ($i = 1; $i <= EmailAuthService::MAX_LOGIN_FAILURES_PER_ACCOUNT; $i++) {
            $auth->attemptLogin('victim@example.com', 'wrong', "10.0.{$i}.1");
        }

        $res = $auth->attemptLogin('victim@example.com', 'RightPass123', '10.9.9.9');
        $this->assertFalse($res['success']);
        $this->assertSame('rate_limit', $res['error']);
    }

    public function test_password_reset_signs_out_the_accounts_other_sessions(): void
    {
        $user = User::create(['name' => 'A', 'email' => 'a@example.com', 'password' => Hash::make('OldPass123')]);
        $customer = Customer::create(['user_id' => $user->id, 'first_name' => 'A', 'last_name' => 'B', 'email' => 'a@example.com', 'source' => 'website']);

        // Another browser signed in with the old password.
        app(StorefrontService::class)->loginCustomer($customer);
        $this->assertNotNull(app(StorefrontService::class)->getCurrentCustomer());

        app(EmailAuthService::class)->resetPassword('a@example.com', 'NewPass1234');

        $this->app->forgetInstance(StorefrontService::class);
        $this->assertNull(app(StorefrontService::class)->getCurrentCustomer());
        $this->assertNull(session('mally_customer_id'));
    }

    public function test_an_old_email_confirmation_cannot_reset_a_password(): void
    {
        $user = User::create(['name' => 'A', 'email' => 'a@example.com', 'password' => Hash::make('OldPass123')]);
        session(['mally_verified_reset_email' => 'a@example.com', 'mally_verified_reset_email_at' => time() - 3600]);

        Livewire::test(HomePage::class)
            ->set('authPassword', 'NewPass1234')
            ->set('authPasswordConfirmation', 'NewPass1234')
            ->call('completePasswordReset')
            ->assertHasErrors('authEmail');

        $this->assertTrue(Hash::check('OldPass123', $user->fresh()->password));
    }

    public function test_client_ip_comes_from_a_trusted_proxy_only(): void
    {
        Route::get('/_test/ip', fn () => request()->ip());

        $this->withServerVariables(['REMOTE_ADDR' => '172.18.0.1'])
            ->withHeader('X-Forwarded-For', '203.0.113.7')
            ->get('/_test/ip')
            ->assertSeeText('203.0.113.7');

        // Anyone else can't pick their own IP (and dodge the rate limits).
        $this->withServerVariables(['REMOTE_ADDR' => '198.51.100.20'])
            ->withHeader('X-Forwarded-For', '203.0.113.7')
            ->get('/_test/ip')
            ->assertSeeText('198.51.100.20');
    }

    public function test_product_images_and_descriptions_for_customers(): void
    {
        Storage::fake('public');
        Storage::disk('public')->put('products/a.jpg', 'jpeg');

        $uploaded = new Product(['image' => 'products/a.jpg']);
        $this->assertSame(Storage::disk('public')->url('products/a.jpg'), $uploaded->imageUrl());
        $this->assertSame('https://cdn.example.com/x.jpg', (new Product(['image' => 'https://cdn.example.com/x.jpg']))->imageUrl());
        $this->assertSame(Product::placeholderUrl(), (new Product)->imageUrl());
        $this->assertStringNotContainsString('findy.uz', Product::placeholderUrl());

        // The admin's internal note is never shown as the description.
        $product = $this->product();
        $product->update(['comment' => 'Закупка у Ахмеда, наценка 40%']);
        $card = app(StorefrontService::class)->formatProduct($product->fresh());
        $this->assertStringNotContainsString('Ахмеда', $card['description']);
    }

    public function test_login_codes_are_not_written_to_the_log_in_production(): void
    {
        Mail::fake();
        $this->app['env'] = 'production';
        config(['mail.default' => 'log']);

        $res = app(EmailAuthService::class)->sendCode('new@example.com', AuthCode::PURPOSE_REGISTER, '10.0.0.1');

        $this->assertFalse($res['success']);
        Mail::assertNothingSent();
        $this->assertSame(0, AuthCode::count());
    }

    public function test_expired_login_codes_are_pruned(): void
    {
        AuthCode::create(['email' => 'old@example.com', 'purpose' => 'register', 'code_hash' => 'x', 'expires_at' => now()->subDays(2)]);
        AuthCode::create(['email' => 'new@example.com', 'purpose' => 'register', 'code_hash' => 'x', 'expires_at' => now()->addMinutes(5)]);

        $this->artisan('model:prune', ['--model' => [AuthCode::class]])->assertSuccessful();

        $this->assertSame(['new@example.com'], AuthCode::pluck('email')->all());
    }
}
