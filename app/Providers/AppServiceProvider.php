<?php

namespace App\Providers;

use App\Services\ExchangeRateService;
use App\Services\StorefrontService;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->singleton(ExchangeRateService::class);
        $this->app->singleton(StorefrontService::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        //
    }
}
