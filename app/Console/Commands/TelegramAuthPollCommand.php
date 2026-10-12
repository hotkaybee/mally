<?php

namespace App\Console\Commands;

use App\Services\TelegramAuthService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;
use Throwable;

class TelegramAuthPollCommand extends Command
{
    protected $signature = 'telegram:auth-poll';

    protected $description = 'Fast long-polling worker for the Telegram Auth/Verification bot';

    public function handle(TelegramAuthService $telegramAuth): int
    {
        $this->info('Starting fast Telegram Auth Bot poller...');

        while (true) {
            try {
                Cache::put('mally_tg_poller_alive', true, 10);
                $telegramAuth->pollUpdates(2);
            } catch (Throwable $e) {
                $this->warn('Poll error: '.$e->getMessage());
                usleep(500000);
            }

            usleep(150000); // 150ms between cycles
        }
    }
}
