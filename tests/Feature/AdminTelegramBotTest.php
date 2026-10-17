<?php

namespace Tests\Feature;

use App\Jobs\NotifyAdminsAboutOrder;
use App\Models\Brand;
use App\Models\Category;
use App\Models\Customer;
use App\Models\Order;
use App\Models\Product;
use App\Services\AdminTelegramBot;
use App\Services\ExchangeRateService;
use App\Services\OrderService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class AdminTelegramBotTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config([
            'services.telegram.bot_token' => 'ADMIN_TOKEN',
            'services.telegram.auth_bot_token' => 'LOGIN_TOKEN',
            'services.telegram.admin_chat_ids' => '111, 222',
        ]);
        Cache::put(ExchangeRateService::CACHE_KEY, ['rate' => 12000, 'source' => 'cbu.uz', 'isLive' => true], 3600);
        Http::fake(['api.telegram.org/*' => Http::response(['ok' => true, 'result' => true])]);
    }

    private function product(string $sku = '618313', int $stock = 3): Product
    {
        $product = Product::create([
            'brand_id' => Brand::firstOrCreate(['name' => 'Rhode'])->id,
            'category_id' => Category::firstOrCreate(['name_ru' => 'Макияж'], ['name_en' => 'Makeup', 'name_uz' => 'Makiyaj'])->id,
            'name' => 'Rhode Pocket Blush Sprinkle',
            'sku' => $sku,
            'description' => 'Карманные кремовые румяна.',
            'quantity' => 0,
            'selling_price' => 0,
            'status' => 'in_stock',
        ]);
        $product->receipts()->create([
            'purchase_price' => 9, 'delivery_cost' => 2, 'markup' => 0,
            'selling_price' => 25, 'website_price' => 0,
            'quantity' => $stock, 'received_at' => now()->subDay(),
        ]);

        return $product->fresh();
    }

    private function order(): Order
    {
        $customer = Customer::create([
            'first_name' => 'Muhammadali', 'last_name' => 'Botirov', 'email' => 'ali@example.com',
            'phone_number' => '+998950057404', 'telegram' => '@muhammadali', 'source' => 'telegram',
        ]);

        return app(OrderService::class)->placeOrder($customer, [$this->product()->id => 2], [
            'customer_name' => 'Muhammadali Botirov',
            'customer_phone' => '+998950057404',
            'city' => 'Ташкент',
            'address' => 'проспект Амира Темура, 4А',
        ]);
    }

    private function message(string $text, string $chatId = '111'): array
    {
        return ['update_id' => 1, 'message' => [
            'message_id' => 5, 'text' => $text,
            'chat' => ['id' => (int) $chatId, 'type' => 'private'],
            'from' => ['id' => (int) $chatId, 'is_bot' => false],
        ]];
    }

    /** @return list<array{0: Request}> */
    private function sent(string $method): array
    {
        return Http::recorded(fn (Request $r) => str_ends_with($r->url(), '/'.$method))->all();
    }

    public function test_placing_an_order_queues_the_admin_notification(): void
    {
        Bus::fake([NotifyAdminsAboutOrder::class]);

        $order = $this->order();

        Bus::assertDispatched(NotifyAdminsAboutOrder::class, fn ($job) => $job->orderId === $order->id);
    }

    public function test_new_order_goes_to_every_admin_chat_with_status_buttons(): void
    {
        Bus::fake([NotifyAdminsAboutOrder::class]);
        $order = $this->order();

        (new NotifyAdminsAboutOrder($order->id))->handle(app(AdminTelegramBot::class));

        $messages = $this->sent('sendMessage');
        $this->assertCount(2, $messages);
        $this->assertSame(['111', '222'], array_map(fn ($m) => (string) $m[0]['chat_id'], $messages));

        $request = $messages[0][0];
        $this->assertStringContainsString("НОВЫЙ ЗАКАЗ № {$order->order_number}", $request['text']);
        $this->assertStringContainsString('Rhode Pocket Blush Sprinkle (Арт: 618313)', $request['text']);
        $this->assertStringContainsString('2 шт. × $25.00 = $50.00', $request['text']);
        $this->assertStringContainsString('ИТОГО К ОПЛАТЕ: $50.00', $request['text']);
        $this->assertStringContainsString('@muhammadali', $request['text']);
        $this->assertArrayNotHasKey('parse_mode', $request->data());

        $buttons = json_decode($request['reply_markup'], true)['inline_keyboard'][0];
        $this->assertSame(["s:{$order->id}:awaiting_delivery", "s:{$order->id}:delivered"], array_column($buttons, 'callback_data'));
    }

    public function test_admin_gets_an_order_by_its_number(): void
    {
        Bus::fake([NotifyAdminsAboutOrder::class]);
        $order = $this->order();

        app(AdminTelegramBot::class)->handleUpdate($this->message('#'.$order->order_number));

        $messages = $this->sent('sendMessage');
        $this->assertCount(1, $messages);
        $this->assertStringContainsString("ЗАКАЗ № {$order->order_number}", $messages[0][0]['text']);
        $this->assertStringContainsString('+998950057404', $messages[0][0]['text']);
    }

    public function test_order_card_shows_the_paid_state_and_som_total_from_the_order(): void
    {
        Bus::fake([NotifyAdminsAboutOrder::class]);
        $order = $this->order();
        $bot = app(AdminTelegramBot::class);

        $bot->handleUpdate($this->message((string) $order->order_number));
        $text = $this->sent('sendMessage')[0][0]['text'];
        $this->assertStringContainsString('(ожидает оплаты)', $text);
        $this->assertStringContainsString('ИТОГО К ОПЛАТЕ: $50.00 (≈ 600 000 сум)', $text);

        $order->update(['amount_paid' => 50]);
        $bot->handleUpdate($this->message((string) $order->order_number));
        $text = $this->sent('sendMessage')[1][0]['text'];
        $this->assertStringContainsString('(оплачен)', $text);
        $this->assertStringContainsString('💳 Оплачено: $50.00', $text);
    }

    public function test_admin_gets_a_product_card_by_sku_with_exact_stock_and_cost(): void
    {
        $this->product();

        app(AdminTelegramBot::class)->handleUpdate($this->message('618313'));

        $text = $this->sent('sendMessage')[0][0]['text'];
        $this->assertStringContainsString('Артикул (SKU): 618313', $text);
        $this->assertStringContainsString('В наличии (3 шт.)', $text);
        $this->assertStringContainsString('Розничная цена: $25.00', $text);
        $this->assertStringContainsString('Себестоимость: ~$11.00', $text);
    }

    public function test_product_with_a_photo_is_sent_as_a_photo_with_the_card_as_caption(): void
    {
        $this->product()->update(['image' => 'https://cdn.example.com/blush.jpg']);

        app(AdminTelegramBot::class)->handleUpdate($this->message('618313'));

        $photo = $this->sent('sendPhoto');
        $this->assertCount(1, $photo);
        $this->assertSame('https://cdn.example.com/blush.jpg', $photo[0][0]['photo']);
        $this->assertStringContainsString('Себестоимость', $photo[0][0]['caption']);
        $this->assertLessThanOrEqual(1024, mb_strlen($photo[0][0]['caption']));
        $this->assertCount(0, $this->sent('sendMessage'));
    }

    public function test_status_button_updates_the_order(): void
    {
        Bus::fake([NotifyAdminsAboutOrder::class]);
        $order = $this->order();

        app(AdminTelegramBot::class)->handleUpdate(['update_id' => 2, 'callback_query' => [
            'id' => 'cb1', 'data' => "s:{$order->id}:delivered",
            'from' => ['id' => 111], 'message' => ['message_id' => 9, 'chat' => ['id' => 111]],
        ]]);

        $this->assertSame('delivered', $order->fresh()->status);
        $this->assertStringContainsString('✅ Доставлен', $this->sent('editMessageText')[0][0]['text']);
        $this->assertCount(1, $this->sent('answerCallbackQuery'));
    }

    public function test_strangers_get_nothing_but_their_chat_id(): void
    {
        Bus::fake([NotifyAdminsAboutOrder::class]);
        $order = $this->order();

        $bot = app(AdminTelegramBot::class);
        $bot->handleUpdate($this->message((string) $order->order_number, '999'));
        $bot->handleUpdate($this->message('618313', '999'));
        $bot->handleUpdate(['update_id' => 3, 'callback_query' => [
            'id' => 'cb2', 'data' => "s:{$order->id}:delivered",
            'from' => ['id' => 999], 'message' => ['message_id' => 9, 'chat' => ['id' => 999]],
        ]]);

        $this->assertSame('processing', $order->fresh()->status);
        $this->assertCount(0, $this->sent('editMessageText'));
        foreach ($this->sent('sendMessage') as [$request]) {
            $this->assertStringContainsString('только для администраторов', $request['text']);
            $this->assertStringNotContainsString('+998', $request['text']);
            $this->assertStringNotContainsString('шт.', $request['text']);
        }
    }

    public function test_telegram_failure_does_not_break_the_order(): void
    {
        Http::fake(['api.telegram.org/*' => Http::response(['ok' => false, 'description' => 'Forbidden'], 403)]);

        $order = $this->order();
        (new NotifyAdminsAboutOrder($order->id))->handle(app(AdminTelegramBot::class));

        $this->assertDatabaseHas('orders', ['id' => $order->id]);
    }

    public function test_admin_bot_does_not_poll_when_it_shares_the_login_bot_token(): void
    {
        config(['services.telegram.auth_bot_token' => 'ADMIN_TOKEN']);

        $this->assertFalse(app(AdminTelegramBot::class)->isConfigured());
    }
}
