<?php

namespace App\Jobs;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Http;

/**
 * Sends a plain-text message to one Telegram chat via the store bot.
 * Plain text (no parse_mode) so user input can't break the formatting.
 */
class SendTelegramMessage implements ShouldQueue
{
    use Queueable;

    public int $tries = 3;

    public array $backoff = [10, 60];

    public function __construct(
        public string $chatId,
        public string $text,
    ) {}

    public function handle(): void
    {
        $token = trim((string) config('services.telegram.bot_token'));
        if ($token === '') {
            return;
        }

        try {
            Http::timeout(10)
                ->post("https://api.telegram.org/bot{$token}/sendMessage", [
                    'chat_id' => $this->chatId,
                    'text' => $this->text,
                    'disable_web_page_preview' => true,
                ])
                ->throw();
        } catch (\Throwable $e) {
            // Connection errors include the request URL, which contains the bot token.
            throw new \RuntimeException('Telegram sendMessage failed: '.str_replace($token, '***', $e->getMessage()));
        }
    }
}
