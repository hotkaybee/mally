<?php

namespace App\Services;

use App\Models\Order;
use App\Models\Product;
use App\Models\ProductReceipt;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Throwable;

/**
 * The staff bot (TELEGRAM_BOT_TOKEN): announces new orders with status
 * buttons, shows an order by its number and a product card by its SKU.
 *
 * It answers only chats listed in TELEGRAM_ADMIN_CHAT_IDS — those see
 * customer phones, exact stock and cost prices, so every other chat gets
 * nothing but its own chat id (to help set the bot up). Messages are plain
 * text (no parse_mode), so customer input can't break the formatting.
 */
class AdminTelegramBot
{
    public const STATUSES = [
        'processing' => '🆕 В обработке',
        'awaiting_delivery' => '🚚 Ожидает доставки',
        'delivered' => '✅ Доставлен',
    ];

    private const LINE = '━━━━━━━━━━━━━━━━━━━━';

    /** Telegram's limit for a photo caption. */
    private const CAPTION_LIMIT = 1024;

    private const SEARCH_LIMIT = 10;

    public function botToken(): string
    {
        return trim((string) config('services.telegram.bot_token'));
    }

    /**
     * Configured and not the login bot: when both use one token the login
     * bot already reads the updates, and two readers would steal them.
     */
    public function isConfigured(): bool
    {
        $token = $this->botToken();

        return $token !== ''
            && $this->adminChatIds() !== []
            && $token !== trim((string) config('services.telegram.auth_bot_token'));
    }

    /** @return list<string> */
    public function adminChatIds(): array
    {
        return array_values(array_filter(array_map('trim', explode(',', (string) config('services.telegram.admin_chat_ids')))));
    }

    public function isAdminChat(string $chatId): bool
    {
        return $chatId !== '' && in_array($chatId, $this->adminChatIds(), true);
    }

    // ----------------------------------------------------------- new orders

    public function notifyNewOrder(Order $order): void
    {
        if ($this->botToken() === '') {
            return;
        }

        $text = $this->orderText($order, true);
        $keyboard = $this->orderKeyboard($order);

        foreach ($this->adminChatIds() as $chatId) {
            $this->call('sendMessage', ['chat_id' => $chatId, 'text' => $text, 'reply_markup' => $keyboard]);
        }
    }

    // -------------------------------------------------------------- updates

    public function handleUpdate(array $update): void
    {
        if (is_array($update['callback_query'] ?? null)) {
            $this->handleCallback($update['callback_query']);

            return;
        }

        $message = $update['message'] ?? null;
        if (! is_array($message) || ! empty($message['from']['is_bot'])) {
            return;
        }

        $chatId = (string) ($message['chat']['id'] ?? '');
        $text = trim((string) ($message['text'] ?? ''));
        if ($chatId === '' || $text === '') {
            return;
        }

        if (! $this->isAdminChat($chatId)) {
            // Only answer private chats, so the bot stays quiet in groups it was added to.
            if (($message['chat']['type'] ?? '') === 'private') {
                $this->send($chatId, "Этот бот только для администраторов Mally.\nВаш chat id: {$chatId}");
            }

            return;
        }

        $this->handleAdminText($chatId, $text);
    }

    private function handleAdminText(string $chatId, string $text): void
    {
        // "/order 1042", "/sku 618313", "#1042", "№1042", "/start" (also "/cmd@BotName").
        $command = '';
        if (str_starts_with($text, '/')) {
            [$command, $text] = array_pad(preg_split('/\s+/', $text, 2), 2, '');
            $command = strtolower(explode('@', $command)[0]);
            $text = trim($text);
        }

        if ($text === '' || in_array($command, ['/start', '/help'], true)) {
            $this->send($chatId, "Mally — бот администратора.\n\n"
                ."• Номер заказа (например 1042 или #1042) — карточка заказа с кнопками статуса.\n"
                ."• Артикул товара (SKU) — карточка товара с фото, остатком и себестоимостью.\n"
                ."• Часть названия — список подходящих товаров.\n\n"
                .'О новых заказах с сайта и из бота-магазина сообщу сам.');

            return;
        }

        $query = trim((string) preg_replace('/^[#№\s]+/u', '', $text));
        $order = $command !== '/sku' && ctype_digit($query)
            ? Order::where('order_number', (int) $query)->first()
            : null;
        $product = $command !== '/order' ? Product::where('sku', $query)->first() : null;

        if ($order) {
            $this->send($chatId, $this->orderText($order, false), $this->orderKeyboard($order));
        }

        if ($product) {
            $this->sendProduct($chatId, $product);
        }

        if ($order || $product) {
            return;
        }

        if ($command !== '/order' && mb_strlen($query) >= 3) {
            $matches = Product::where('name', 'like', '%'.addcslashes($query, '%_\\').'%')
                ->orderBy('name')
                ->limit(self::SEARCH_LIMIT + 1)
                ->get(['id', 'name', 'sku']);

            if ($matches->count() === 1) {
                $this->sendProduct($chatId, Product::find($matches->first()->id));

                return;
            }

            if ($matches->isNotEmpty()) {
                $buttons = $matches->take(self::SEARCH_LIMIT)
                    ->map(fn (Product $p) => [['text' => Str::limit($p->name, 60), 'callback_data' => 'p:'.$p->id]])
                    ->all();
                $more = $matches->count() > self::SEARCH_LIMIT ? "\nПоказаны первые ".self::SEARCH_LIMIT.', уточните запрос.' : '';

                $this->send($chatId, "🔎 Найдено по «{$query}»:{$more}", ['inline_keyboard' => $buttons]);

                return;
            }
        }

        $this->send($chatId, "Ничего не найдено по «{$query}».\nОтправьте номер заказа, артикул (SKU) или часть названия товара.");
    }

    private function handleCallback(array $callback): void
    {
        $chatId = (string) ($callback['message']['chat']['id'] ?? '');
        $messageId = (int) ($callback['message']['message_id'] ?? 0);
        $callbackId = (string) ($callback['id'] ?? '');
        $data = (string) ($callback['data'] ?? '');

        if (! $this->isAdminChat($chatId)) {
            $this->answerCallback($callbackId, 'Нет доступа.');

            return;
        }

        // Change status: "s:<order id>:<status>".
        if (preg_match('/^s:(\d+):([a-z_]+)$/', $data, $m) && isset(self::STATUSES[$m[2]])) {
            $order = Order::find((int) $m[1]);
            if (! $order) {
                $this->answerCallback($callbackId, 'Заказ не найден.');

                return;
            }

            if ($order->status !== $m[2]) {
                $order->update(['status' => $m[2]]);
            }

            $this->call('editMessageText', [
                'chat_id' => $chatId,
                'message_id' => $messageId,
                'text' => $this->orderText($order->fresh(), false),
                'reply_markup' => $this->orderKeyboard($order),
            ]);
            $this->answerCallback($callbackId, 'Статус: '.self::STATUSES[$m[2]]);

            return;
        }

        // Product from a search list: "p:<product id>".
        if (preg_match('/^p:(\d+)$/', $data, $m) && $product = Product::find((int) $m[1])) {
            $this->answerCallback($callbackId);
            $this->sendProduct($chatId, $product);

            return;
        }

        $this->answerCallback($callbackId, 'Устаревшая кнопка.');
    }

    // ---------------------------------------------------------------- texts

    public function orderText(Order $order, bool $isNew): string
    {
        $order->loadMissing(['customer', 'orderProducts.product']);
        $customer = $order->customer;

        $lines = $order->orderProducts
            ->groupBy('product_id')
            ->values()
            ->map(function ($rows, int $i) {
                $product = $rows->first()->product;
                $qty = (int) $rows->sum('quantity');
                $price = (float) $rows->first()->price;
                $sku = filled($product?->sku) ? 'Арт: '.$product->sku : 'ID: '.$rows->first()->product_id;

                return ($i + 1).'. '.($product?->name ?? 'Товар удалён')." ({$sku})\n"
                    ."   └ {$qty} шт. × ".$this->usd($price).' = '.$this->usd((float) $rows->sum('total_price'));
            });

        $buyer = $customer
            ? trim(collect([
                filled($customer->telegram) ? '@'.ltrim($customer->telegram, '@') : null,
                '('.trim($customer->first_name.' '.$customer->last_name).')',
                filled($customer->phone_number) ? '[тел: '.$customer->phone_number.']' : null,
            ])->filter()->implode(' '))
            : '—';

        $address = collect([$order->city, $order->address])->filter(fn ($v) => filled($v))->implode(', ');
        $paid = $order->payment_status === 'PAID' ? 'оплачен' : 'ожидает оплаты';
        $phoneDigits = preg_replace('/\D/', '', (string) $order->customer_phone);

        $text = ($isNew ? '🛍 НОВЫЙ ЗАКАЗ' : '🧾 ЗАКАЗ')." № {$order->order_number} в Mally!\n"
            ."Beauty without limits\n\n"
            .'📊 Статус: '.(self::STATUSES[$order->status] ?? $order->status)." ({$paid})\n"
            .'🕒 Создан: '.$order->created_at?->timezone('Asia/Tashkent')->format('d.m.Y H:i')."\n"
            ."👤 Аккаунт (заказчик): {$buyer}\n"
            ."📦 Получатель (доставка): {$order->customer_name}\n"
            ."📞 Телефон получателя: {$order->customer_phone}"
            .($phoneDigits !== '' ? " (Написать в TG: https://t.me/+{$phoneDigits})" : '')."\n"
            .'📍 Адрес доставки: '.($address !== '' ? $address : '—')."\n"
            .(filled($order->comment) ? "💬 Комментарий: {$order->comment}\n" : '')
            ."\n📦 Состав заказа ({$lines->count()} поз.):\n"
            .$lines->implode("\n")."\n\n"
            .'🎟 Промокод: '.(filled($order->promo_code)
                ? $order->promo_code.' (скидка '.$this->usd((float) $order->discount_amount).')'
                : 'Не использован')."\n"
            .'💰 ИТОГО К ОПЛАТЕ: '.$this->usd((float) $order->amount)
            .($order->amount_uzs ? ' (≈ '.number_format((int) $order->amount_uzs, 0, '.', ' ').' сум)' : '')."\n";

        if ((float) $order->amount_paid > 0) {
            $text .= '💳 Оплачено: '.$this->usd((float) $order->amount_paid)."\n";
        }

        try {
            $text .= "\n🔗 Админка: ".route('filament.admin.resources.orders.view', $order)."\n";
        } catch (Throwable) {
            // Panel routes aren't registered in every context; the link is optional.
        }

        return $text."\n👇 Управляйте заказом прямо из Telegram кнопками ниже:";
    }

    public function productText(Product $product, int $limit = 4096): string
    {
        $product->loadMissing(['brand', 'category']);

        $receipts = ProductReceipt::where('product_id', $product->id)->get();
        $inStock = $receipts->where('quantity_remaining', '>', 0);
        $stock = (int) $inStock->sum('quantity_remaining');

        // Average landed cost (закуп + карго) of the units still in stock,
        // or of the latest receipt when nothing is left.
        $cost = $stock > 0
            ? $inStock->sum(fn (ProductReceipt $r) => $r->unit_cost * $r->quantity_remaining) / $stock
            : $receipts->sortBy([['received_at', 'desc'], ['id', 'desc']])->first()?->unit_cost;

        $stockText = match (true) {
            $stock > 0 => "🟢 В наличии ({$stock} шт.)",
            $product->status === 'on_the_way' => '🟡 В пути (0 шт.)',
            default => '🔴 Нет в наличии (0 шт.)',
        };

        $head = '💄 '.$product->name."\n".self::LINE."\n"
            .'🏷️ Артикул (SKU): '.(filled($product->sku) ? $product->sku : '—')." · ID {$product->id}\n"
            .($product->brand ? '🏢 Бренд: '.$product->brand->name."\n" : '')
            .($product->category ? '📂 Категория: '.$product->category->name."\n" : '')
            ."📦 Склад: {$stockText}\n\n"
            .'💰 Розничная цена: '.$this->usd((float) $product->selling_price)."\n"
            .'📥 Себестоимость: '.($cost !== null ? '~'.$this->usd((float) $cost) : '—')."\n";

        $tail = "\n".self::LINE;
        $description = trim(strip_tags((string) $product->description));

        if ($description !== '') {
            $room = $limit - mb_strlen($head) - mb_strlen($tail) - 4;
            $head .= "\n📝 ".Str::limit($description, max(0, min(300, $room)), '...')."\n";
        }

        return mb_substr($head.$tail, 0, $limit);
    }

    // ------------------------------------------------------------ transport

    private function sendProduct(string $chatId, Product $product): void
    {
        $photo = $this->productPhoto($product);

        if ($photo !== null) {
            $caption = $this->productText($product, self::CAPTION_LIMIT);
            $sent = is_string($photo)
                ? $this->call('sendPhoto', ['chat_id' => $chatId, 'photo' => $photo, 'caption' => $caption])
                : $this->call('sendPhoto', ['chat_id' => $chatId, 'caption' => $caption], $photo);

            if ($sent) {
                return;
            }
        }

        // No photo, or Telegram couldn't fetch it: the card as plain text.
        $this->send($chatId, $this->productText($product));
    }

    /**
     * A public URL Telegram can fetch itself, or a local file to upload
     * (works on localhost too), or null.
     *
     * @return string|array{path: string, name: string}|null
     */
    private function productPhoto(Product $product): string|array|null
    {
        $image = trim((string) $product->image);
        if ($image === '') {
            return null;
        }

        if (preg_match('#^https?://#i', $image)) {
            return $image;
        }

        $relative = ltrim(str_replace('\\', '/', $image), '/');
        foreach (['public', 'local'] as $disk) {
            try {
                if (Storage::disk($disk)->exists($relative)) {
                    return ['path' => Storage::disk($disk)->path($relative), 'name' => basename($relative)];
                }
            } catch (Throwable) {
                // Disk not configured — try the next place.
            }
        }

        $public = public_path($relative);

        return is_file($public) && ! str_contains($relative, '..') ? ['path' => $public, 'name' => basename($relative)] : null;
    }

    private function send(string $chatId, string $text, ?array $replyMarkup = null): void
    {
        $this->call('sendMessage', array_filter([
            'chat_id' => $chatId,
            'text' => mb_substr($text, 0, 4096),
            'reply_markup' => $replyMarkup,
        ]));
    }

    private function answerCallback(string $callbackId, string $text = ''): void
    {
        if ($callbackId !== '') {
            $this->call('answerCallbackQuery', array_filter(['callback_query_id' => $callbackId, 'text' => $text]));
        }
    }

    /** @return array<string, mixed> */
    private function orderKeyboard(Order $order): array
    {
        $buttons = collect(self::STATUSES)
            ->except($order->status)
            ->map(fn (string $label, string $status) => ['text' => $label, 'callback_data' => "s:{$order->id}:{$status}"])
            ->values()
            ->all();

        return ['inline_keyboard' => [$buttons]];
    }

    /**
     * Calls the Bot API; failures are logged (token masked), never thrown,
     * so a Telegram outage can't break checkout or the worker.
     *
     * @param  array{path: string, name: string}|null  $photoFile
     */
    private function call(string $method, array $payload, ?array $photoFile = null): bool
    {
        $token = $this->botToken();
        if ($token === '') {
            return false;
        }

        if (isset($payload['reply_markup']) && is_array($payload['reply_markup'])) {
            $payload['reply_markup'] = json_encode($payload['reply_markup'], JSON_UNESCAPED_UNICODE);
        }

        try {
            $request = Http::timeout($photoFile ? 30 : 10)->connectTimeout(5);
            if ($photoFile) {
                $request = $request->attach('photo', file_get_contents($photoFile['path']), $photoFile['name']);
            }

            $response = $request->post("https://api.telegram.org/bot{$token}/{$method}", $payload);
            if ($response->successful()) {
                return true;
            }

            Log::warning("Admin bot {$method} failed", ['status' => $response->status(), 'error' => $response->json('description')]);
        } catch (Throwable $e) {
            Log::warning("Admin bot {$method} failed", ['error' => str_replace($token, '***', $e->getMessage())]);
        }

        return false;
    }

    /**
     * Fetch updates with getUpdates (the telegram:admin-poll worker).
     */
    public function pollUpdates(int $longPollTimeout = 0): void
    {
        $token = $this->botToken();
        if ($token === '') {
            return;
        }

        $offsetKey = 'tg_admin_update_offset:'.substr(hash('sha256', $token), 0, 12);

        try {
            $response = Http::timeout(max(3, $longPollTimeout + 2))
                ->connectTimeout(3)
                ->get("https://api.telegram.org/bot{$token}/getUpdates", [
                    'offset' => (int) Cache::get($offsetKey, 0) ?: null,
                    'limit' => 30,
                    'timeout' => $longPollTimeout,
                    'allowed_updates' => json_encode(['message', 'callback_query']),
                ]);
        } catch (Throwable $e) {
            Log::warning('Admin bot getUpdates failed', ['error' => str_replace($token, '***', $e->getMessage())]);

            return;
        }

        foreach ((array) $response->json('result', []) as $update) {
            if (! is_array($update)) {
                continue;
            }
            Cache::put($offsetKey, (int) ($update['update_id'] ?? 0) + 1, 86400 * 7);

            try {
                $this->handleUpdate($update);
            } catch (Throwable $e) {
                // One bad update must not stop the bot or be fetched again forever.
                report($e);
            }
        }
    }

    private function usd(float $amount): string
    {
        return '$'.number_format($amount, 2, '.', ',');
    }
}
