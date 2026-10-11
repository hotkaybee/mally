<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class ExchangeRateService
{
    public const CBU_USD_URL = 'https://cbu.uz/ru/arkhiv-kursov-valyut/json/USD/';

    public const CBU_ALL_URL = 'https://cbu.uz/ru/arkhiv-kursov-valyut/json/';

    public const CACHE_KEY = 'cbu_usd_exchange_rate';

    public const LAST_KNOWN_KEY = 'cbu_usd_last_known_rate';

    public const CACHE_TTL_SECONDS = 3600; // 60 minutes

    private ?array $memoryCachedRate = null;

    public function getUsdRate(bool $forceRefresh = false): array
    {
        if (! $forceRefresh && $this->memoryCachedRate !== null && ($this->memoryCachedRate['rate'] ?? 0) > 0) {
            return $this->memoryCachedRate;
        }

        if (! $forceRefresh) {
            $cached = Cache::get(self::CACHE_KEY);
            if (is_array($cached) && isset($cached['rate']) && $cached['rate'] > 0 && ($cached['isLive'] ?? false)) {
                return $this->memoryCachedRate = $cached;
            }
        }

        // Попытка получить актуальный курс с основного и резервного URL ЦБ РУз
        foreach ([self::CBU_USD_URL, self::CBU_ALL_URL] as $url) {
            try {
                $response = Http::retry(2, 200)->timeout(4)->withoutVerifying()->get($url);

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

                                    Cache::put(self::CACHE_KEY, $data, self::CACHE_TTL_SECONDS);
                                    Cache::forever(self::LAST_KNOWN_KEY, $data);

                                    return $this->memoryCachedRate = $data;
                                }
                            }
                        }
                    }
                }
            } catch (\Throwable $e) {
                Log::warning("ExchangeRateService: CBU API request failed ({$url}): ".$e->getMessage());
            }
        }

        // При сбое и отсутствии данных не ставим искусственный дефолтный курс и не кэшируем ошибку,
        // чтобы при следующем запросе система снова пыталась восстановить данные из ЦБ РУз
        return [
            'rate' => null,
            'nominal' => 1.0,
            'currency' => 'USD',
            'date' => date('d.m.Y'),
            'diff' => '0',
            'source' => 'cbu.uz',
            'isLive' => false,
            'updatedAt' => now()->toIso8601String(),
        ];
    }
}
