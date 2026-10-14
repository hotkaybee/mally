<?php

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
