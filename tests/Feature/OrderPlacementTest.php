<?php

namespace Tests\Feature;

use App\Exceptions\CheckoutException;
use App\Livewire\Storefront\CheckoutPage;
use App\Livewire\Storefront\OrderReceiptPage;
use App\Livewire\Storefront\ProfilePage;
use App\Models\Brand;
use App\Models\Category;
use App\Models\Customer;
use App\Models\Order;
use App\Models\Product;
use App\Models\ProductReceipt;
use App\Models\Review;
use App\Models\User;
use App\Services\ExchangeRateService;
use App\Services\OrderService;
use App\Services\StorefrontService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Livewire\Features\SupportLockedProperties\CannotUpdateLockedPropertyException;
use Livewire\Livewire;
use Tests\TestCase;

class OrderPlacementTest extends TestCase
{
    use RefreshDatabase;

    private const RATE = 12199.57;

    protected function setUp(): void
    {
        parent::setUp();

        Cache::put(ExchangeRateService::CACHE_KEY, [
            'rate' => self::RATE,
            'source' => 'cbu.uz',
            'isLive' => true,
            'updatedAt' => now()->toIso8601String(),
        ], 3600);
    }

    private function product(float $price = 57.15, array $receiptQuantities = [5], string $sku = 'ML-HG-BRUSH'): Product
    {
        $product = Product::create([
            'brand_id' => Brand::firstOrCreate(['name' => 'Hourglass'])->id,
            'category_id' => Category::firstOrCreate(['name_ru' => 'Макияж'], ['name_en' => 'Makeup', 'name_uz' => 'Makiyaj'])->id,
            'name' => 'Ambient Lighting Brush '.$sku,
            'sku' => $sku,
            'quantity' => 0,
            'selling_price' => 0,
            'status' => 'in_stock',
        ]);

        foreach ($receiptQuantities as $i => $qty) {
            $product->receipts()->create([
                'purchase_price' => 20, 'delivery_cost' => 5, 'markup' => 0,
                'selling_price' => $price, 'website_price' => 0,
                'quantity' => $qty, 'received_at' => now()->subDays(10 - $i),
            ]);
        }

        return $product->fresh();
    }

    private function customer(array $attrs = []): Customer
    {
        return Customer::create(array_merge([
            'first_name' => 'Мадина',
            'last_name' => 'Умарова',
            'email' => 'madina'.uniqid().'@example.com',
            'phone_number' => '+998 90 123 45 67',
            'city' => 'Самарканд',
            'address' => 'ул. Регистан 10',
            'source' => 'instagram',
        ], $attrs));
    }

    private function assertLocked(\Closure $attempt): void
    {
        try {
            $attempt();
        } catch (CannotUpdateLockedPropertyException) {
            $this->addToAssertionCount(1);

            return;
        }

        $this->fail('Expected the property to be #[Locked].');
    }

    private function place(Customer $customer, array $quantities, ?string $promo = null): Order
    {
        return app(OrderService::class)->placeOrder($customer, $quantities, [
            'customer_name' => 'Test', 'customer_phone' => $customer->phone_number, 'promo_code' => $promo,
        ]);
    }

    // ---------------------------------------------------------------- checkout

    public function test_checkout_saves_usd_amounts_with_a_rate_snapshot(): void
    {
        $product = $this->product(57.15);
        $customer = $this->customer();
        session(['mally_customer_id' => $customer->id]);
        app(StorefrontService::class)->addToCart('ML-HG-BRUSH');
        app(StorefrontService::class)->addToCart('ML-HG-BRUSH');
        app(StorefrontService::class)->applyPromoCode('MALLY10');

        Livewire::test(CheckoutPage::class)
            ->assertSet('city', 'Самарканд')
            ->assertSet('address', 'ул. Регистан 10')
            ->call('placeOrder')
            ->assertRedirect();

        $order = Order::sole();
        $order->refresh();
        $this->assertEquals(102.87, (float) $order->amount);          // 2 × 57.15 − 10 %
        $this->assertEquals(11.43, (float) $order->discount_amount);
        $this->assertEquals(self::RATE, (float) $order->exchange_rate);
        $this->assertSame((int) round(102.87 * self::RATE), (int) $order->amount_uzs);
        $this->assertEquals(57.15, (float) $order->orderProducts->first()->price);
        $this->assertSame('Самарканд', $order->city);
        $this->assertSame(1000 + $order->id, (int) $order->order_number);
        $this->assertSame(3, $product->fresh()->quantity);
        $this->assertNull($customer->fresh()->instagram);
        $this->assertSame([], app(StorefrontService::class)->getCart());
    }

    public function test_correcting_the_amount_keeps_the_som_total_at_the_order_rate(): void
    {
        $product = $this->product(57.15);
        $order = $this->place($this->customer(), [$product->id => 1]);

        // Today's rate changes; the order keeps its own.
        Cache::put(ExchangeRateService::CACHE_KEY, ['rate' => 13000.0, 'isLive' => true], 3600);
        $order->update(['amount' => 50.00]);

        $this->assertSame((int) round(50.00 * self::RATE), (int) $order->fresh()->amount_uzs);
    }

    public function test_checkout_rejects_unknown_products_and_creates_nothing(): void
    {
        $customer = $this->customer();
        session([
            'mally_customer_id' => $customer->id,
            'mally_cart' => [['id' => '999', 'sku' => 'ML-HG-BRUSH-FAKE', 'title' => 'Showcase item', 'price' => 35, 'quantity' => 1]],
        ]);
        $products = Product::count();

        $this->expectException(CheckoutException::class);
        try {
            $this->place($customer, [999 => 1]);
        } finally {
            $this->assertSame($products, Product::count());
            $this->assertSame(0, Brand::where('name', 'Mally Collection')->count());
            $this->assertSame(0, Order::count());
        }
    }

    public function test_client_prices_and_discounts_are_ignored(): void
    {
        $product = $this->product(80.00);
        $customer = $this->customer();
        session([
            'mally_customer_id' => $customer->id,
            'mally_cart' => [['id' => (string) $product->id, 'title' => 'x', 'price' => 1.00, 'quantity' => 1]],
        ]);

        $component = Livewire::test(CheckoutPage::class);

        // Locked: the browser can't set a discount, promo or a fresh submit token.
        $this->assertLocked(fn () => $component->set('discountUsd', 79.0));
        $this->assertLocked(fn () => $component->set('appliedPromo', 'MALLY10'));
        $this->assertLocked(fn () => $component->set('checkoutToken', 'forged'));

        // First attempt reports the price change, the second one places the order at the DB price.
        $component->call('placeOrder');
        $this->assertSame(0, Order::count());
        $component->call('placeOrder');

        $this->assertEquals(80.00, (float) Order::sole()->amount);
    }

    public function test_insufficient_stock_is_rejected_without_touching_stock(): void
    {
        $product = $this->product(50, [2]);
        $customer = $this->customer();

        try {
            $this->place($customer, [$product->id => 3]);
            $this->fail('Expected CheckoutException');
        } catch (CheckoutException $e) {
            $this->assertStringNotContainsString('2', $e->getMessage()); // no exact stock leak
        }

        $this->assertSame(0, Order::count());
        $this->assertSame(2, $product->fresh()->quantity);
    }

    public function test_a_line_is_split_across_receipts_oldest_first(): void
    {
        $product = $this->product(50, [2, 3]);
        [$old, $new] = $product->receipts()->orderBy('received_at')->get()->all();

        $order = $this->place($this->customer(), [$product->id => 4]);

        $lines = $order->orderProducts()->orderBy('id')->get();
        $this->assertCount(2, $lines);
        $this->assertSame([$old->id, 2], [$lines[0]->product_receipt_id, $lines[0]->quantity]);
        $this->assertSame([$new->id, 2], [$lines[1]->product_receipt_id, $lines[1]->quantity]);
        $this->assertEquals(200.00, (float) $order->fresh()->amount);
        $this->assertSame(0, $old->fresh()->quantity_remaining);
        $this->assertSame(1, $new->fresh()->quantity_remaining);
        $this->assertSame(1, $product->fresh()->quantity);
    }

    public function test_back_to_back_orders_cannot_oversell(): void
    {
        $product = $this->product(50, [3]);

        $this->place($this->customer(), [$product->id => 2]);

        $this->expectException(CheckoutException::class);
        try {
            $this->place($this->customer(['phone_number' => '+998 91 000 00 00']), [$product->id => 2]);
        } finally {
            $this->assertSame(1, $product->fresh()->quantity);
            $this->assertGreaterThanOrEqual(0, ProductReceipt::min('quantity_remaining'));
        }
    }

    public function test_double_submit_places_a_single_order(): void
    {
        $product = $this->product(50, [5]);
        $customer = $this->customer();
        session(['mally_customer_id' => $customer->id]);
        app(StorefrontService::class)->addToCart('ML-HG-BRUSH');

        $component = Livewire::test(CheckoutPage::class);
        $token = $component->get('checkoutToken');
        $component->call('placeOrder');

        // Same page submitted again (e.g. double click with a stale session cart).
        session(['mally_cart' => [['id' => (string) $product->id, 'price' => 50, 'quantity' => 1]]]);
        $component->call('placeOrder');

        $this->assertNotSame('', $token);
        $this->assertSame(1, Order::count());
        $this->assertSame(4, $product->fresh()->quantity);
    }

    public function test_order_numbers_follow_ids_and_are_unique(): void
    {
        $product = $this->product(10, [10]);
        $a = $this->place($this->customer(), [$product->id => 1]);
        $b = $this->place($this->customer(['phone_number' => '+998 91 111 11 11']), [$product->id => 1]);

        $this->assertSame(1000 + $a->id, (int) $a->order_number);
        $this->assertSame(1000 + $b->id, (int) $b->order_number);
        $this->assertNotSame($a->order_number, $b->order_number);
    }

    public function test_technical_errors_are_not_shown_to_the_customer(): void
    {
        $this->product(50, [5]);
        $customer = $this->customer();
        session(['mally_customer_id' => $customer->id]);
        app(StorefrontService::class)->addToCart('ML-HG-BRUSH');

        $this->mock(OrderService::class)
            ->shouldReceive('placeOrder')
            ->andThrow(new \RuntimeException('SQLSTATE[23000]: secret table details'));

        Livewire::test(CheckoutPage::class)
            ->call('placeOrder')
            ->assertDispatched('toast', fn ($name, $params) => ! str_contains($params['message'], 'SQLSTATE'));
    }

    // --------------------------------------------------------------------- API

    public function test_api_orders_require_the_bot_token(): void
    {
        $product = $this->product();
        $payload = ['customerName' => 'Ali', 'customerPhone' => '+998 93 555 44 33', 'items' => [['id' => $product->id, 'quantity' => 1]]];

        config(['services.store.orders_api_token' => '']);
        $this->postJson('/api/orders', $payload)->assertUnauthorized();

        config(['services.store.orders_api_token' => 'secret-token']);
        $this->postJson('/api/orders', $payload, ['X-Api-Token' => 'wrong'])->assertUnauthorized();
        $this->assertSame(0, Order::count());
    }

    public function test_api_order_from_a_new_customer_is_created_in_usd(): void
    {
        config(['services.store.orders_api_token' => 'secret-token']);
        $product = $this->product(57.15);

        $response = $this->postJson('/api/orders', [
            'customerName' => 'Ali Valiev',
            'customerPhone' => '+998 93 555 44 33',
            'city' => 'Ташкент',
            'items' => [['id' => $product->id, 'quantity' => 1]],
        ], ['X-Api-Token' => 'secret-token'])->assertCreated();

        $this->assertSame('USD', $response->json('currency'));
        $this->assertEquals(57.15, $response->json('totalAmount'));
        $this->assertSame('telegram', Customer::where('phone_number', '+998 93 555 44 33')->sole()->source);
    }

    public function test_api_order_is_never_attached_to_a_storefront_account_by_phone(): void
    {
        config(['services.store.orders_api_token' => 'secret-token']);
        $product = $this->product(57.15);
        // A shopper account that typed (but never verified) this phone.
        $user = User::factory()->create(['phone' => '+998 93 555 44 33']);
        $account = $this->customer(['user_id' => $user->id, 'phone_number' => '+998 93 555 44 33']);

        $this->postJson('/api/orders', [
            'customerName' => 'Ali Valiev',
            'customerPhone' => '+998 93 555 44 33',
            'address' => 'Real owner street 1',
            'items' => [['id' => $product->id, 'quantity' => 1]],
        ], ['X-Api-Token' => 'secret-token'])->assertCreated();

        $order = Order::sole();
        $this->assertNotSame($account->id, $order->customer_id);
        $this->assertNull($order->customer->user_id);

        session(['mally_customer_id' => $account->id]);
        $this->assertCount(0, Livewire::test(ProfilePage::class)->viewData('orders'));
    }

    public function test_api_retry_with_the_same_idempotency_key_places_one_order(): void
    {
        config(['services.store.orders_api_token' => 'secret-token']);
        $product = $this->product(57.15, [5]);
        $payload = [
            'customerName' => 'Ali Valiev', 'customerPhone' => '+998 93 555 44 33',
            // Client-sent money is ignored.
            'items' => [['id' => $product->id, 'quantity' => 2, 'price' => 0.01]],
            'totalAmount' => 1, 'discount' => 79,
        ];
        $headers = ['X-Api-Token' => 'secret-token', 'Idempotency-Key' => 'bot-msg-4711'];

        $first = $this->postJson('/api/orders', $payload, $headers)->assertCreated();
        $retry = $this->postJson('/api/orders', $payload, $headers)->assertOk();

        $this->assertSame($first->json('orderNumber'), $retry->json('orderNumber'));
        $this->assertSame(1, Order::count());
        $this->assertEquals(114.30, (float) Order::sole()->amount);
        $this->assertSame(3, $product->fresh()->quantity);

        // A different key is a different order.
        $this->postJson('/api/orders', $payload, ['Idempotency-Key' => 'bot-msg-4712'] + $headers)->assertCreated();
        $this->assertSame(2, Order::count());
    }

    public function test_api_rejects_unknown_products_without_creating_them(): void
    {
        config(['services.store.orders_api_token' => 'secret-token']);
        $count = Product::count();

        $this->postJson('/api/orders', [
            'customerName' => 'Ali', 'customerPhone' => '+998 93 555 44 33',
            'items' => [['id' => 4242, 'quantity' => 1]],
        ], ['X-Api-Token' => 'secret-token'])->assertStatus(422);

        $this->assertSame($count, Product::count());
        $this->assertSame(0, Order::count());
    }

    public function test_order_tracking_needs_the_secret_hash(): void
    {
        $product = $this->product();
        $order = $this->place($this->customer(), [$product->id => 1]);

        $this->getJson('/api/orders/track?order='.$order->order_number)->assertNotFound();
        $this->getJson('/api/orders/track?q='.$order->order_number)->assertNotFound();
        $this->getJson('/api/orders/track?order='.$order->order_number.'&hash=wrong')->assertNotFound();
        $this->getJson('/api/orders/track?order='.$order->order_number.'&hash='.$order->secure_hash)
            ->assertOk()
            ->assertJsonMissingPath('order.city');
    }

    // ------------------------------------------------------- receipt & profile

    public function test_receipt_is_only_visible_to_the_owner_or_with_the_hash(): void
    {
        $product = $this->product();
        $owner = $this->customer();
        $order = $this->place($owner, [$product->id => 1]);

        $this->get(route('checkout.receipt', ['orderNumber' => $order->order_number, 'orderHash' => $order->secure_hash]))
            ->assertOk()->assertSeeLivewire(OrderReceiptPage::class);
        $this->get(route('checkout.receipt', ['orderNumber' => $order->order_number]))->assertForbidden();
        $this->get(route('checkout.receipt', ['orderNumber' => $order->order_number, 'orderHash' => 'wrong']))->assertForbidden();
        $this->get(route('checkout.receipt', ['orderNumber' => $order->order_number, 'orderHash' => substr($order->secure_hash, 0, 16)]))->assertForbidden();

        // Another account with the same phone number is not the owner.
        $samePhone = $this->customer(['email' => 'other@example.com']);
        session(['mally_customer_id' => $samePhone->id]);
        $this->get(route('checkout.receipt', ['orderNumber' => $order->order_number]))->assertForbidden();

        // The order id is not accepted instead of the number.
        session(['mally_customer_id' => $owner->id]);
        $this->get(route('checkout.receipt', ['orderNumber' => $order->id]))->assertNotFound();
        $this->get(route('checkout.receipt', ['orderNumber' => $order->order_number]))->assertOk();
    }

    public function test_profile_cannot_take_another_accounts_phone_or_admin_rights(): void
    {
        $other = User::factory()->create(['phone' => '+998 91 222 33 44']);
        $this->customer(['user_id' => $other->id, 'phone_number' => '+998 91 222 33 44', 'email' => 'other@example.com']);
        $me = User::factory()->create(['phone' => '+998 90 123 45 67']);
        $mine = $this->customer(['user_id' => $me->id]);
        session(['mally_customer_id' => $mine->id, 'mally_user_id' => $me->id]);

        Livewire::test(ProfilePage::class)
            ->set('phone', '+998 91 222 33 44')
            ->call('saveSettings');

        $this->assertSame('+998 90 123 45 67', $mine->fresh()->phone_number);
        $this->assertSame('+998 90 123 45 67', $me->fresh()->phone);
        $this->assertSame('+998 91 222 33 44', $other->fresh()->phone);

        // is_admin is never mass-assignable.
        $this->assertFalse((bool) User::create([
            'name' => 'x', 'email' => 'x@example.com', 'password' => 'Secret123', 'is_admin' => true,
        ])->fresh()->is_admin);
        $me->update(['is_admin' => true]);
        $this->assertFalse((bool) $me->fresh()->is_admin);
    }

    public function test_profile_lists_only_the_customers_own_orders(): void
    {
        $product = $this->product(10, [10]);
        $me = $this->customer();
        $stranger = $this->customer(['email' => 'stranger@example.com']); // same phone
        $mine = $this->place($me, [$product->id => 1]);
        $theirs = $this->place($stranger, [$product->id => 1]);

        session(['mally_customer_id' => $me->id]);
        $orders = Livewire::test(ProfilePage::class)->viewData('orders');

        $this->assertSame([$mine->id], $orders->pluck('id')->all());
        $this->assertNotContains($theirs->id, $orders->pluck('id')->all());
    }

    // ----------------------------------------------------------------- reviews

    private function deliveredOrder(Customer $customer, Product $product): Order
    {
        $order = $this->place($customer, [$product->id => 1]);
        $order->update(['status' => 'delivered']);

        return $order;
    }

    public function test_review_requires_a_delivered_own_order_with_the_product(): void
    {
        $product = $this->product(10, [10]);
        $other = $this->product(10, [10], 'ML-OTHER');
        $me = $this->customer();
        $stranger = $this->customer(['email' => 'stranger@example.com', 'phone_number' => '+998 91 222 22 22']);

        $notDelivered = $this->place($me, [$product->id => 1]);
        $theirs = $this->deliveredOrder($stranger, $product);
        $mine = $this->deliveredOrder($me, $product);

        session(['mally_customer_id' => $me->id]);

        foreach ([
            [$product->id, $notDelivered->order_number],
            [$product->id, $theirs->order_number],
            [$other->id, $mine->order_number],
        ] as [$productId, $orderNumber]) {
            Livewire::test(ProfilePage::class)
                ->call('openProductReview', (string) $productId, 'x', 'https://x', (int) $orderNumber)
                ->assertSet('reviewTarget', null);
        }

        $this->assertSame(0, Review::count());
    }

    public function test_review_target_cannot_be_forged_and_reviews_wait_for_moderation(): void
    {
        $product = $this->product(10, [10]);
        $me = $this->customer();
        $order = $this->deliveredOrder($me, $product);
        session(['mally_customer_id' => $me->id]);

        $component = Livewire::test(ProfilePage::class);
        $this->assertLocked(fn () => $component->set('reviewTarget', ['productId' => '1', 'orderNumber' => 1]));

        $component->call('openProductReview', (string) $product->id, 'forged title', 'https://x', (int) $order->order_number)
            ->set('reviewComment', 'Great brush')
            ->set('reviewPhotos', ['javascript:alert(1)', 'https://img.example.com/a.jpg'])
            ->call('submitReview');

        $review = Review::sole();
        $this->assertFalse($review->is_approved);
        $this->assertSame($product->name, $review->product_title);
        $this->assertSame(['https://img.example.com/a.jpg'], $review->photos);
        $this->assertSame([], app(StorefrontService::class)->getDatabaseReviewsForProduct($product->id));

        // A second review for the same order line is refused.
        Livewire::test(ProfilePage::class)
            ->call('openProductReview', (string) $product->id, 'x', 'https://x', (int) $order->order_number)
            ->set('reviewComment', 'Again')
            ->call('submitReview');
        $this->assertSame(1, Review::count());

        $review->update(['is_approved' => true]);
        $this->assertCount(1, app(StorefrontService::class)->getDatabaseReviewsForProduct($product->id));
    }
}
