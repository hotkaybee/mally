<?php

namespace App\Providers;

use App\Services\ExchangeRateService;
use App\Services\StorefrontService;
use Illuminate\Http\Middleware\TrustProxies;
use Illuminate\Support\Facades\URL;
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
        // Set here, not in bootstrap/app.php: config (and a cached config)
        // isn't loaded yet when the middleware is configured.
        TrustProxies::at(config('app.trusted_proxies'));

        if ($this->app->isProduction() && str_starts_with((string) config('app.url'), 'https://')) {
            URL::forceScheme('https');
        }
    }
}
