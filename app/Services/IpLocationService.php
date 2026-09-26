<?php

namespace App\Services;

use App\Models\City;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Resolves a visitor IP to a city_id using ip-api.com, with:
 *   - 24h cache per /24 prefix
 *   - 10-min circuit breaker if the provider fails
 *   - accented/case-insensitive city name matching
 */
class IpLocationService
{
    private const IP_CACHE_TTL = 86400;
    private const CITY_MAP_TTL = 3600;
    private const BREAKER_KEY = 'ip_location.breaker';
    private const BREAKER_TTL = 600;
    private const LOOKUP_TIMEOUT = 2;

    /**
     * @return int|null City ID, or null if unresolvable
     */
    public function resolveCityId(?string $ip): ?int
    {
        if (!$ip || $this->isPrivateIp($ip)) {
            return null;
        }

        $prefix = $this->ipPrefix($ip);
        $cacheKey = "ip_location.ip.{$prefix}";

        $cached = Cache::get($cacheKey);
        if ($cached !== null) {
            return $cached === 'null' ? null : (int) $cached;
        }

        if (Cache::has(self::BREAKER_KEY)) {
            return null;
        }

        try {
            $response = Http::timeout(self::LOOKUP_TIMEOUT)
                ->get("http://ip-api.com/json/{$ip}", [
                    'fields' => 'status,country,regionName,city',
                ]);

            if (!$response->ok() || ($response->json('status') ?? '') !== 'success') {
                $this->tripBreaker('non-success response');
                Cache::put($cacheKey, 'null', self::IP_CACHE_TTL);
                return null;
            }

            $cityName = trim((string) $response->json('city', ''));
            if ($cityName === '') {
                Cache::put($cacheKey, 'null', self::IP_CACHE_TTL);
                return null;
            }

            $cityId = $this->matchCityByName($cityName);

            if ($cityId === null) {
                Cache::put($cacheKey, 'null', self::IP_CACHE_TTL);
                return null;
            }

            Cache::put($cacheKey, $cityId, self::IP_CACHE_TTL);
            return $cityId;
        } catch (\Throwable $e) {
            Log::warning('IpLocationService lookup failed', [
                'ip' => $ip,
                'error' => $e->getMessage(),
            ]);
            $this->tripBreaker($e->getMessage());
            return null;
        }
    }

    /**
     * @return City|null
     */
    public function resolveCity(?string $ip): ?City
    {
        $cityId = $this->resolveCityId($ip);
        return $cityId ? City::find($cityId) : null;
    }

    private function matchCityByName(string $name): ?int
    {
        $normalized = $this->normalize($name);

        $map = Cache::remember('ip_location.city_map', self::CITY_MAP_TTL, function () {
            return City::query()
                ->get(['id', 'name'])
                ->mapWithKeys(fn($c) => [$this->normalize($c->name) => $c->id])
                ->all();
        });

        if (isset($map[$normalized])) {
            return $map[$normalized];
        }

        foreach ($map as $cityName => $id) {
            if (str_starts_with($cityName, $normalized)) {
                return $id;
            }
        }

        return null;
    }

    private function normalize(string $s): string
    {
        $s = preg_replace('/\s+/', ' ', trim($s));
        $s = strtolower($s);
        $converted = @iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $s);
        return $converted !== false ? $converted : $s;
    }

    private function ipPrefix(string $ip): string
    {
        if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4)) {
            $parts = explode('.', $ip);
            return "{$parts[0]}.{$parts[1]}.{$parts[2]}";
        }
        return $ip;
    }

    private function isPrivateIp(string $ip): bool
    {
        return !filter_var(
            $ip,
            FILTER_VALIDATE_IP,
            FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE
        );
    }

    private function tripBreaker(string $reason): void
    {
        Cache::put(self::BREAKER_KEY, $reason, self::BREAKER_TTL);
    }
}