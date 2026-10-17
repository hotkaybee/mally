<?php

namespace Tests\Feature;

use App\Services\ExchangeRateService;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class ExchangeRateServiceTest extends TestCase
{
    private function cbuResponse(string $rate = '12 199,57'): array
    {
        return [['Ccy' => 'USD', 'Rate' => str_replace(' ', '', $rate), 'Nominal' => '1', 'Date' => '14.10.2026']];
    }

    public function test_refresh_parses_and_caches_the_rate(): void
    {
        Http::fake(['cbu.uz/*' => Http::response($this->cbuResponse())]);

        $data = app(ExchangeRateService::class)->refresh();

        $this->assertSame(12199.57, $data['rate']);
        $this->assertSame(12199.57, Cache::get(ExchangeRateService::CACHE_KEY)['rate']);
        $this->assertSame(12199.57, Cache::get(ExchangeRateService::LAST_KNOWN_KEY)['rate']);
    }

    public function test_cached_rate_is_served_without_calling_cbu(): void
    {
        Http::fake();
        Cache::put(ExchangeRateService::CACHE_KEY, ['rate' => 12000.0, 'updatedAt' => now()->toIso8601String()], 3600);

        $this->assertSame(12000.0, app(ExchangeRateService::class)->getUsdRate()['rate']);
        Http::assertNothingSent();
    }

    public function test_falls_back_to_last_known_rate_when_cbu_is_down(): void
    {
        Http::fake(['cbu.uz/*' => Http::response('', 500)]);
        Cache::forever(ExchangeRateService::LAST_KNOWN_KEY, [
            'rate' => 11900.0,
            'updatedAt' => now()->subHours(5)->toIso8601String(),
        ]);

        $data = app(ExchangeRateService::class)->getUsdRate();

        $this->assertSame(11900.0, $data['rate']);
        $this->assertFalse($data['isLive']);
    }

    public function test_too_old_last_known_rate_is_not_used(): void
    {
        Http::fake(['cbu.uz/*' => Http::response('', 500)]);
        Cache::forever(ExchangeRateService::LAST_KNOWN_KEY, [
            'rate' => 11900.0,
            'updatedAt' => now()->subDays(10)->toIso8601String(),
        ]);

        $this->assertNull(app(ExchangeRateService::class)->getUsdRate()['rate']);
    }

    public function test_cbu_is_not_retried_on_every_page_load_during_an_outage(): void
    {
        Http::fake(['cbu.uz/*' => Http::response('', 500)]);

        app(ExchangeRateService::class)->getUsdRate();
        $this->app->forgetInstance(ExchangeRateService::class);
        app(ExchangeRateService::class)->getUsdRate();

        // One attempt = two URLs; the second page load must not try again.
        Http::assertSentCount(2);
    }

    public function test_malformed_response_is_rejected(): void
    {
        Http::fake(['cbu.uz/*' => Http::response([['Ccy' => 'USD', 'Rate' => 'abc']])]);

        $this->assertNull(app(ExchangeRateService::class)->refresh());
        $this->assertNull(Cache::get(ExchangeRateService::CACHE_KEY));
    }
}
