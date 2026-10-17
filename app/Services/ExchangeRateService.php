<?php

namespace App\Services;

use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * USD→UZS rate from the Central Bank of Uzbekistan (cbu.uz).
 *
 * Page loads only read the cache; the rate is refreshed by the scheduled
 * `exchange-rate:refresh` command. If cbu.uz is unreachable, the last known
 * rate is used for up to LAST_KNOWN_MAX_AGE_HOURS, after that no rate is
 * returned (UZS prices are hidden rather than guessed).
 */
class ExchangeRateService
{
    public const CBU_USD_URL = 'https://cbu.uz/ru/arkhiv-kursov-valyut/json/USD/';

    public const CBU_ALL_URL = 'https://cbu.uz/ru/arkhiv-kursov-valyut/json/';

    public const CACHE_KEY = 'cbu_usd_exchange_rate';

    public const LAST_KNOWN_KEY = 'cbu_usd_last_known_rate';

    public const FETCH_LOCK_KEY = 'cbu_usd_fetch_attempt';

    public const CACHE_TTL_SECONDS = 3600;

    public const LAST_KNOWN_MAX_AGE_HOURS = 72;

    /** Minimum gap between on-demand fetches when the cache is empty. */
    public const FETCH_RETRY_SECONDS = 300;

    private ?array $memoryCachedRate = null;

    public function getUsdRate(): array
    {
        if ($this->memoryCachedRate !== null) {
            return $this->memoryCachedRate;
        }

        $cached = Cache::get(self::CACHE_KEY);
        if ($this->isValid($cached)) {
            return $this->memoryCachedRate = $cached;
        }

        // Cold cache (e.g. the scheduler has not run yet): try cbu.uz at most
        // once per FETCH_RETRY_SECONDS so a cbu.uz outage cannot slow every page.
        if (Cache::add(self::FETCH_LOCK_KEY, true, self::FETCH_RETRY_SECONDS)) {
            $fresh = $this->refresh();
            if ($fresh !== null) {
                return $this->memoryCachedRate = $fresh;
            }
        }

        $lastKnown = Cache::get(self::LAST_KNOWN_KEY);
        if ($this->isValid($lastKnown) && $this->isRecent($lastKnown)) {
            return $this->memoryCachedRate = array_merge($lastKnown, ['isLive' => false]);
        }

        return $this->memoryCachedRate = [
            'rate' => null,
            'currency' => 'USD',
            'date' => null,
            'source' => 'cbu.uz',
            'isLive' => false,
            'updatedAt' => null,
        ];
    }

    /**
     * Fetch the current rate from cbu.uz and cache it. Returns null on failure.
     */
    public function refresh(): ?array
    {
        foreach ([self::CBU_USD_URL, self::CBU_ALL_URL] as $url) {
            try {
                $response = Http::timeout(4)->get($url);
            } catch (\Throwable $e) {
                Log::warning('ExchangeRateService: cbu.uz request failed', ['url' => $url, 'error' => $e->getMessage()]);

                continue;
            }

            if (! $response->successful() || ! is_array($response->json())) {
                Log::warning('ExchangeRateService: unexpected cbu.uz response', ['url' => $url, 'status' => $response->status()]);

                continue;
            }

            $data = $this->parse($response->json());
            if ($data !== null) {
                Cache::put(self::CACHE_KEY, $data, self::CACHE_TTL_SECONDS);
                Cache::forever(self::LAST_KNOWN_KEY, $data);
                $this->memoryCachedRate = $data;

                return $data;
            }
        }

        return null;
    }

    private function parse(array $items): ?array
    {
        foreach ($items as $item) {
            if (! is_array($item) || ($item['Ccy'] ?? '') !== 'USD') {
                continue;
            }

            $rate = (float) str_replace(',', '.', (string) ($item['Rate'] ?? '0'));
            $nominal = (float) ($item['Nominal'] ?? 1) ?: 1.0;
            $perUnit = $rate / $nominal;

            if ($perUnit <= 0) {
                return null;
            }

            return [
                'rate' => round($perUnit, 2),
                'currency' => 'USD',
                'date' => (string) ($item['Date'] ?? ''),
                'source' => 'cbu.uz',
                'isLive' => true,
                'updatedAt' => now()->toIso8601String(),
            ];
        }

        return null;
    }

    private function isValid(mixed $data): bool
    {
        return is_array($data) && isset($data['rate']) && (float) $data['rate'] > 0;
    }

    private function isRecent(array $data): bool
    {
        if (empty($data['updatedAt'])) {
            return false;
        }

        try {
            return Carbon::parse($data['updatedAt'])->gt(now()->subHours(self::LAST_KNOWN_MAX_AGE_HOURS));
        } catch (\Throwable) {
            return false;
        }
    }
}
