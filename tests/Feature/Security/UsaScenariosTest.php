<?php

namespace Tests\Feature\Security;

use App\Livewire\Storefront\CartPage;
use App\Livewire\Storefront\HomePage;
use App\Livewire\Storefront\ProductDetailPage;
use App\Livewire\Storefront\ProfilePage;
use App\Models\Brand;
use App\Models\Category;
use App\Models\Customer;
use App\Models\Order;
use App\Models\Product;
use App\Models\Review;
use App\Models\User;
use App\Services\CustomerAccountService;
use App\Services\ExchangeRateService;
use App\Services\GoogleAuthService;
use App\Services\OrderService;
use App\Services\StorefrontService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\RateLimiter;
use Laravel\Socialite\Contracts\Factory;
use Laravel\Socialite\Facades\Socialite;
use Laravel\Socialite\Two\GoogleProvider;
use Laravel\Socialite\Two\InvalidStateException;
use Laravel\Socialite\Two\User as SocialiteUser;
use Livewire\Features\SupportLockedProperties\CannotUpdateLockedPropertyException;
use Livewire\Livewire;
use Mockery;
use Tests\TestCase;

/**
 * Ultimate Security Auditor scenarios (SC-xxxx in .security-audit/state.json).
 * Every test asserts the SECURE behaviour: a failing test is a vulnerability.
 */
class UsaScenariosTest extends TestCase
{
    use RefreshDatabase;

    private const CANARY = 'usa-canary-7f3a91';

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
        Cache::flush();
        app()->setLocale('ru');
        session(['mally_locale' => 'ru']);

        config([
            'services.google.client_id' => 'test-client-id',
            'services.google.client_secret' => 'test-client-secret',
            'services.telegram.auth_bot_token' => 'TEST_BOT_TOKEN',
            'services.telegram.auth_bot_username' => 'mally_test_bot',
            'services.telegram.auth_webhook_secret' => 'whsec',
        ]);
        Cache::put(ExchangeRateService::CACHE_KEY, ['rate' => 12000, 'source' => 'cbu.uz', 'isLive' => true, 'updatedAt' => now()->toIso8601String()], 3600);
        Http::fake(['api.telegram.org/*' => Http::response(['ok' => true])]);
    }

    // ------------------------------------------------------------- helpers

    private function fakeGoogle($provider): void
    {
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

    private function fakeGoogleRedirect(): void
    {
        $provider = Mockery::mock(GoogleProvider::class);
        $provider->shouldReceive('scopes')->andReturnSelf();
        $provider->shouldReceive('with')->andReturnSelf();
        $provider->shouldReceive('redirect')->andReturn(redirect('https://accounts.google.com/o/oauth2/auth'));
        $this->fakeGoogle($provider);
    }

    private function product(string $sku = 'ML-USA-1', int $qty = 5): Product
    {
        $product = Product::create([
            'brand_id' => Brand::firstOrCreate(['name' => 'Hourglass'])->id,
            'category_id' => Category::firstOrCreate(['name_ru' => 'Макияж'], ['name_en' => 'Makeup', 'name_uz' => 'Makiyaj'])->id,
            'name' => 'Brush '.$sku,
            'sku' => $sku,
            'quantity' => 0,
            'selling_price' => 0,
            'status' => 'in_stock',
        ]);
        $product->receipts()->create([
            'purchase_price' => 20, 'delivery_cost' => 5, 'markup' => 0, 'selling_price' => 40, 'website_price' => 0,
            'quantity' => $qty, 'received_at' => now()->subDays(5),
        ]);

        return $product->fresh();
    }

    /** A shopper with an account and a customer row. */
    private function shopper(string $email, array $attrs = []): User
    {
        $user = User::create(array_merge(['name' => 'Shopper', 'email' => $email, 'password' => 'GoodPass12345'], $attrs));
        app(CustomerAccountService::class)->customerFor($user);

        return $user->refresh();
    }

    private function signIn(User $user): void
    {
        app(CustomerAccountService::class)->login($user->refresh());
    }

    private function deliveredOrder(User $owner, Product $product): Order
    {
        $customer = $owner->customer()->first();
        $order = app(OrderService::class)->placeOrder($customer, [$product->id => 1], [
            'customer_name' => 'Test', 'customer_phone' => '+998 90 123 45 67', 'promo_code' => null,
        ]);
        $order->update(['status' => 'delivered']);

        return $order->refresh();
    }

    // ------------------------------------------------------------ scenarios

    /** SC-0001 */
    public function test_sc_0001_google_return_path_never_points_outside_the_site(): void
    {
        $hostile = [
            '//evil.example', '/\\evil.example', 'https://evil.example', 'javascript:alert(1)',
            "/\t/evil.example", "/\n/evil.example", "/\r\nX-Injected: 1", '/ /evil.example', '/%2F/evil.example',
        ];

        foreach ($hostile as $path) {
            $this->fakeGoogleRedirect();
            $this->get('/auth/google?redirect='.rawurlencode($path))->assertRedirect();

            $stored = (string) session(GoogleAuthService::SESSION_REDIRECT);
            // After the browser removes tabs and newlines a stored path must not become "//host".
            $browserSees = preg_replace('/[\t\r\n]/', '', $stored);
            $this->assertStringStartsNotWith('//', $browserSees, 'open redirect via '.json_encode($path));
            $this->assertStringStartsNotWith('/\\', $browserSees, 'open redirect via '.json_encode($path));
            $this->assertDoesNotMatchRegularExpression('/[\r\n\t]/', $stored, 'control characters kept in '.json_encode($path));
        }
    }

    /** SC-0002 */
    public function test_sc_0002_telegram_webhook_rejects_forged_updates(): void
    {
        $update = ['update_id' => 1, 'message' => ['chat' => ['id' => 1, 'type' => 'private'], 'from' => ['id' => 1, 'is_bot' => false], 'text' => '/start login_abc']];

        $this->postJson('/api/telegram/webhook', $update)->assertStatus(401);
        $this->postJson('/api/telegram/webhook', $update, ['X-Telegram-Bot-Api-Secret-Token' => 'wrong'])->assertStatus(401);

        // Secret not configured: an empty header must not be accepted either.
        config(['services.telegram.auth_webhook_secret' => '']);
        $this->postJson('/api/telegram/webhook', $update, ['X-Telegram-Bot-Api-Secret-Token' => ''])->assertStatus(401);

        config(['services.telegram.auth_webhook_secret' => 'whsec']);
        $this->postJson('/api/telegram/webhook', $update, ['X-Telegram-Bot-Api-Secret-Token' => 'whsec'])->assertOk();
    }

    /** SC-0003 */
    public function test_sc_0003_a_shopper_cannot_open_the_admin_panel(): void
    {
        $shopper = $this->shopper('shopper@gmail.com');

        $this->actingAs($shopper)->get('/admin')->assertForbidden();
        $this->actingAs($shopper)->get('/admin/products')->assertForbidden();
    }

    /** SC-0004 */
    public function test_sc_0004_tampered_profile_properties_do_not_change_phone_or_email(): void
    {
        $user = $this->shopper('owner@gmail.com', ['phone' => '+998 90 123 45 67', 'telegram_id' => '555', 'phone_verified_at' => now()]);
        $user->customer()->update(['phone_number' => '+998 90 123 45 67']);
        $this->signIn($user);

        Livewire::test(ProfilePage::class)
            ->set('phone', '+998 99 999 99 99')
            ->set('firstName', 'Renamed')
            ->call('saveSettings');

        $user->refresh();
        $this->assertSame('+998 90 123 45 67', $user->phone);
        $this->assertNotNull($user->phone_verified_at);
        $this->assertSame('owner@gmail.com', $user->email);
        $this->assertSame('+998 90 123 45 67', $user->customer()->value('phone_number'), 'The confirmed phone is not editable from the browser.');
        $this->assertSame('Renamed', $user->customer()->value('first_name'));

        $this->expectException(CannotUpdateLockedPropertyException::class);
        Livewire::test(ProfilePage::class)->set('reviewTarget', ['productId' => '1', 'orderNumber' => 1]);
    }

    /** SC-0005 */
    public function test_sc_0005_customer_b_cannot_review_customer_as_order(): void
    {
        $product = $this->product();
        $a = $this->shopper('a@gmail.com');
        $b = $this->shopper('b@gmail.com');
        $order = $this->deliveredOrder($a, $product);
        $this->signIn($b);

        Livewire::test(ProfilePage::class)
            ->call('openProductReview', (string) $product->id, 'x', 'https://x.example/i.jpg', (int) $order->order_number)
            ->assertSet('reviewTarget', null)
            ->set('reviewComment', 'hello from B')
            ->call('submitReview');

        $this->assertSame(0, Review::count());
    }

    /** SC-0006 */
    public function test_sc_0006_the_session_id_changes_when_signing_in_with_google(): void
    {
        $before = session()->getId();
        $raw = ['sub' => 'g-1', 'email' => 'fix@gmail.com', 'email_verified' => true, 'given_name' => 'A', 'family_name' => 'B', 'name' => 'A B'];
        $user = (new SocialiteUser)->setRaw($raw)->map(['id' => 'g-1', 'name' => 'A B', 'email' => 'fix@gmail.com']);
        $provider = Mockery::mock(GoogleProvider::class);
        $provider->shouldReceive('user')->andReturn($user);
        $this->fakeGoogle($provider);

        $this->get('/auth/google/callback?code=c&state=s')->assertRedirect();

        $this->assertNotNull(session('mally_customer_id'));
        $this->assertNotSame($before, session()->getId(), 'A pre-set session id survived the sign-in.');
    }

    /** SC-0007 */
    public function test_sc_0007_the_sign_in_form_does_not_reveal_which_emails_exist(): void
    {
        $this->shopper('exists@gmail.com');

        $known = Livewire::test(HomePage::class)->call('openAuthModal')
            ->set('authEmail', 'exists@gmail.com')->set('authPassword', 'WrongPass12345')->call('loginWithPassword')
            ->get('authError');
        $unknown = Livewire::test(HomePage::class)->call('openAuthModal')
            ->set('authEmail', 'nobody@gmail.com')->set('authPassword', 'WrongPass12345')->call('loginWithPassword')
            ->get('authError');

        $this->assertNotSame('', $known);
        $this->assertSame($known, $unknown);
    }

    /** SC-0008 */
    public function test_sc_0008_cart_quantities_stay_inside_the_stock(): void
    {
        $product = $this->product('ML-USA-QTY', 3);
        $component = Livewire::test(CartPage::class);

        $component->call('addToCart', 'ML-USA-QTY');
        foreach ([-5, 999999, PHP_INT_MAX] as $qty) {
            $component->call('updateCartQty', 'ML-USA-QTY', $qty);
            foreach (app(StorefrontService::class)->getCart() as $item) {
                $this->assertGreaterThanOrEqual(1, (int) $item['quantity'], "qty $qty");
                $this->assertLessThanOrEqual(3, (int) $item['quantity'], "qty $qty");
            }
        }

        $component->call('updateCartQty', 'ML-USA-QTY', 0);
        $this->assertSame([], app(StorefrontService::class)->getCart(), 'Zero removes the line.');
    }

    /** SC-0009 */
    public function test_sc_0009_names_and_review_comments_are_escaped(): void
    {
        $product = $this->product('ML-USA-XSS');
        $user = $this->shopper('xss@gmail.com');
        $user->customer()->update(['first_name' => '<script>alert("'.self::CANARY.'")</script>', 'last_name' => '"><img src=x onerror=alert(1)>']);
        $order = $this->deliveredOrder($user, $product);
        Review::create([
            'product_id' => $product->id, 'order_id' => $order->id, 'rating' => 5, 'is_approved' => true,
            'comment' => '<img src=x onerror=alert("'.self::CANARY.'")>',
        ]);
        $this->signIn($user->refresh());

        Livewire::test(ProfilePage::class)->assertDontSeeHtml('<script>alert("'.self::CANARY.'")')->assertDontSeeHtml('<img src=x onerror=alert(1)>');
        Livewire::test(ProductDetailPage::class, ['slug' => 'ML-USA-XSS'])->assertDontSeeHtml('<img src=x onerror=alert("'.self::CANARY);
    }

    /** SC-0010 */
    public function test_sc_0010_pages_carry_basic_hardening_headers(): void
    {
        $response = $this->get('/profile')->assertOk();

        $csp = (string) $response->headers->get('Content-Security-Policy');
        $this->assertTrue(
            $response->headers->has('X-Frame-Options') || str_contains($csp, 'frame-ancestors'),
            'The page can be framed by any site (clickjacking).'
        );
        $this->assertSame('nosniff', $response->headers->get('X-Content-Type-Options'));
        $this->assertNotEmpty($response->headers->get('Referrer-Policy'));
    }

    /** SC-0011 */
    public function test_sc_0011_livewire_updates_need_a_csrf_token(): void
    {
        // The CSRF middleware is switched off while "testing"; run it as in production.
        $this->app['env'] = 'production';

        $this->post('/livewire/update', ['components' => []], ['X-Livewire' => '1'])->assertStatus(419);
    }

    /** SC-0012 */
    public function test_sc_0012_the_google_start_route_is_rate_limited(): void
    {
        $this->fakeGoogleRedirect();
        $last = null;
        for ($i = 0; $i < 22; $i++) {
            $last = $this->get('/auth/google');
        }

        $last->assertStatus(429);
    }

    /** SC-0013 */
    public function test_sc_0013_the_review_image_comes_from_the_database_not_from_the_browser(): void
    {
        $product = $this->product('ML-USA-IMG');
        $user = $this->shopper('img@gmail.com');
        $order = $this->deliveredOrder($user, $product);
        $this->signIn($user);

        Livewire::test(ProfilePage::class)
            ->call('openProductReview', (string) $product->id, 'x', 'javascript:alert(1)" onerror="alert(2)', (int) $order->order_number)
            ->assertDontSeeHtml('javascript:alert(1)')
            ->assertDontSeeHtml('onerror="alert(2)');
    }

    /** SC-0014 */
    public function test_sc_0014_a_forged_state_or_missing_code_signs_nobody_in(): void
    {
        $provider = Mockery::mock(GoogleProvider::class);
        $provider->shouldReceive('user')->andThrow(new InvalidStateException('state mismatch SECRET-TRACE'));
        $this->fakeGoogle($provider);

        $this->get('/auth/google/callback?code=c&state=forged')->assertRedirect('/');
        $this->assertNull(session('mally_customer_id'));
        $this->assertStringNotContainsString('SECRET-TRACE', (string) session('mally_toast.message'));
        $this->assertSame(0, User::count());

        $this->get('/auth/google/callback')->assertRedirect('/');
        $this->assertSame(0, User::count());
    }

    /** SC-0015 */
    public function test_sc_0015_a_telegram_or_phone_of_another_account_cannot_be_linked(): void
    {
        $victim = $this->shopper('victim@gmail.com', ['phone' => '+998 90 123 45 67', 'telegram_id' => '900', 'phone_verified_at' => now()]);
        $attacker = $this->shopper('attacker@gmail.com');
        $accounts = app(CustomerAccountService::class);

        $res = $accounts->linkTelegram($attacker, ['telegram_user_id' => '900', 'telegram_username' => 'victim', 'phone' => '+998 90 123 45 67']);
        $this->assertSame(CustomerAccountService::TELEGRAM_TAKEN, $res['result']);
        $this->assertNull($attacker->fresh()->telegram_id);

        // A fresh Telegram that shares the victim's phone: linked without taking the phone.
        $res = $accounts->linkTelegram($attacker, ['telegram_user_id' => '901', 'telegram_username' => 'att', 'phone' => '+998 90 123 45 67']);
        $this->assertFalse($res['phone_saved']);
        $this->assertNull($attacker->fresh()->phone);
        $this->assertSame('900', $victim->fresh()->telegram_id);

        RateLimiter::clear('x');
    }
}
