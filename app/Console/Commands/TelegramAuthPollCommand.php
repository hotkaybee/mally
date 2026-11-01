<?php

namespace App\Console\Commands;

use App\Services\TelegramAuthService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;
use Throwable;

/**
 * Long-polls the login bot so it answers within a second, even while the
 * shopper's browser tab is in the background. Run it as a worker (it is part
 * of `composer dev` and the Docker supervisor). With a webhook configured
 * Telegram delivers updates itself, so the worker only idles — it never
 * exits, so it can't take other supervised processes down with it.
 */
class TelegramAuthPollCommand extends Command
{
    protected $signature = 'telegram:auth-poll';

    protected $description = 'Fast long-polling worker for the Telegram Auth/Verification bot';

    public function handle(TelegramAuthService $telegramAuth): int
    {
        if (! $telegramAuth->isConfigured() || trim((string) config('services.telegram.auth_webhook_secret')) !== '') {
            $this->info('Telegram login bot uses a webhook or is not configured; nothing to poll.');

            return self::SUCCESS;
        }

        $this->info('Polling the Telegram login bot...');

        while (true) {
            try {
                Cache::put('mally_tg_poller_alive', true, 40);
                // Telegram holds the request up to 25 s and answers at once
                // when a message arrives.
                $telegramAuth->pollUpdates(25);
            } catch (Throwable $e) {
                $this->warn('Poll error: '.str_replace($telegramAuth->botToken(), '***', $e->getMessage()));
                sleep(1);
            }

            // Guards against a tight loop when Telegram answers with an error at once.
            usleep(200000);
        }
    }
}
