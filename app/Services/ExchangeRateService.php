<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class ExchangeRateService
{
    public const CBU_URL = 'https://cbu.uz/ru/arkhiv-kursov-valyut/json/';
    public const CACHE_KEY = 'cbu_usd_exchange_rate';
    public const LAST_KNOWN_KEY = 'cbu_usd_last_known_rate';
    public const CACHE_TTL_SECONDS = 1800; // 30 minutes

    /**
     * Retrieve the USD/UZS exchange rate from CBU with caching and fallback.
     *
     * @return array{
     *     rate: int|null,
     *     nominal: float,
     *     currency: string,
     *     date: string|null,
     *     diff: string|null,
     *     source: string,
     *     isLive: bool,
     *     stale?: bool,
     *     error?: string,
     *     updatedAt: string
     * }
     */
    public function getUsdRate(bool $forceRefresh = false): array
    {
        // 1. Check live cache if not forcing refresh
        if (! $forceRefresh) {
            $cached = Cache::get(self::CACHE_KEY);
            if (is_array($cached) && isset($cached['rate']) && $cached['rate'] > 0) {
                return $cached;
            }
        }

        // 2. Fetch live data from official Central Bank of Uzbekistan API
        try {
            $response = Http::timeout(6)->get(self::CBU_URL);

            if ($response->successful()) {
                $items = $response->json();

                if (is_array($items)) {
                    foreach ($items as $item) {
                        if (($item['Ccy'] ?? '') === 'USD') {
                            $rawRate = str_replace(',', '.', (string) ($item['Rate'] ?? '0'));
                            $rateFloat = (float) $rawRate;
                            $nominal = (float) ($item['Nominal'] ?? 1);
                            $ratePerUnit = $nominal > 0 ? $rateFloat / $nominal : $rateFloat;

                            if ($ratePerUnit > 0) {
                                $data = [
                                    'rate' => (int) round($ratePerUnit),
                                    'nominal' => $nominal,
                                    'currency' => 'USD',
                                    'date' => (string) ($item['Date'] ?? date('d.m.Y')),
                                    'diff' => (string) ($item['Diff'] ?? '0'),
                                    'source' => 'cbu.uz',
                                    'isLive' => true,
                                    'updatedAt' => now()->toIso8601String(),
                                ];

                                // Cache fresh rate for 24 hours
                                Cache::put(self::CACHE_KEY, $data, self::CACHE_TTL_SECONDS);

                                // Save as long-term fallback in case CBU is unreachable in the future
                                Cache::forever(self::LAST_KNOWN_KEY, $data);

                                return $data;
                            }
                        }
                    }
                }
            }
        } catch (\Throwable $e) {
            Log::warning('ExchangeRateService: CBU API request failed: '.$e->getMessage());
        }

        // 3. Fallback: return last known successfully fetched rate with stale indicator
        $lastKnown = Cache::get(self::LAST_KNOWN_KEY);
        if (is_array($lastKnown) && isset($lastKnown['rate']) && $lastKnown['rate'] > 0) {
            $lastKnown['isLive'] = false;
            $lastKnown['stale'] = true;

            return $lastKnown;
        }

        // 4. In case no rate has ever been fetched and CBU is down
        return [
            'rate' => null,
            'nominal' => 1.0,
            'currency' => 'USD',
            'date' => null,
            'diff' => null,
            'source' => 'cbu.uz',
            'isLive' => false,
            'error' => 'Exchange rate currently unavailable from CBU',
            'updatedAt' => now()->toIso8601String(),
        ];
    }
}
