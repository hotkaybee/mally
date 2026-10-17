<?php

namespace App\Console\Commands;

use App\Services\AdminTelegramBot;
use Illuminate\Console\Command;
use Throwable;

/**
 * Long-polls the admin bot so admins get answers to order numbers, SKUs and
 * status buttons at once. Part of `composer dev` and the Docker supervisor;
 * idles (never exits) when the admin bot isn't configured.
 */
class TelegramAdminPollCommand extends Command
{
    protected $signature = 'telegram:admin-poll';

    protected $description = 'Long-polling worker for the Telegram admin bot (orders, SKUs)';

    public function handle(AdminTelegramBot $bot): int
    {
        if (! $bot->isConfigured()) {
            $this->info('Admin bot is not configured (TELEGRAM_BOT_TOKEN, TELEGRAM_ADMIN_CHAT_IDS) or shares the login bot token; nothing to poll.');

            while (true) {
                sleep(3600);
            }
        }

        $this->info('Polling the Telegram admin bot...');

        while (true) {
            try {
                $bot->pollUpdates(25);
            } catch (Throwable $e) {
                $this->warn('Poll error: '.str_replace($bot->botToken(), '***', $e->getMessage()));
                sleep(1);
            }

            // Guards against a tight loop when Telegram answers with an error at once.
            usleep(200000);
        }
    }
}
