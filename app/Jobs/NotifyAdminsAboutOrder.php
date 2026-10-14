<?php

namespace App\Jobs;

use App\Models\Order;
use App\Services\AdminTelegramBot;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

/**
 * Tells the admin chats about a new storefront or bot order.
 */
class NotifyAdminsAboutOrder implements ShouldQueue
{
    use Queueable;

    public function __construct(public int $orderId) {}

    public function handle(AdminTelegramBot $bot): void
    {
        if ($order = Order::find($this->orderId)) {
            $bot->notifyNewOrder($order);
        }
    }
}
