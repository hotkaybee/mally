<?php

use App\Models\User;
use App\Services\ExchangeRateService;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Artisan::command('exchange-rate:refresh', function (ExchangeRateService $rates) {
    $data = $rates->refresh();

    if ($data === null) {
        $this->error('Could not fetch the USD rate from cbu.uz.');

        return 1;
    }

    $this->info("USD = {$data['rate']} UZS ({$data['date']})");

    return 0;
})->purpose('Fetch the USD→UZS rate from cbu.uz into the cache');

Schedule::command('exchange-rate:refresh')->hourly()->withoutOverlapping();

Artisan::command('admin:grant {email}', function (string $email) {
    $user = User::whereRaw('LOWER(email) = ?', [mb_strtolower(trim($email))])->first();

    if (! $user) {
        $this->error("No user with email {$email}. Create one with php artisan make:filament-user first.");

        return 1;
    }

    $user->forceFill(['is_admin' => true])->save();
    $this->info("{$user->email} can now open /admin.");

    return 0;
})->purpose('Allow a user to open the Filament admin panel');

// Deletes expired login codes and Telegram login requests (Prunable models).
Schedule::command('model:prune')->daily();
