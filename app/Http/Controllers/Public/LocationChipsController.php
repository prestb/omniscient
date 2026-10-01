<?php

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Models\Business;
use App\Models\City;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class LocationChipsController extends Controller
{
    private const CACHE_TTL = 86400;          // 24h for IP → city
    private const CHIPS_CACHE_TTL = 3600;     // 1h for city → chips
    private const BREAKER_KEY = 'location_chips.breaker';
    private const BREAKER_TTL = 600;          // 10 min circuit breaker
    private const LOOKUP_TIMEOUT = 2;         // 2 second timeout
    private const MAX_CHIPS = 6;

    /**
     * GET /api/location-chips
     *
     * Returns:
     *   {
     *     "city": { "id": 5, "name": "Limbe" } | null,
     *     "chips": [
     *         { "category_id": 15, "category_name": "Supermarkets", "label": "Supermarkets in Limbe", "count": 12 },
     *         ...
     *     ]
     *   }
     */
    public function index(Request $request)
    {
        $ip = $request->ip();
        $cityId = $this->resolveCityIdForIp($ip);

        if ($cityId === null) {
            // Fallback — global top categories, no city filter
            return response()->json([
                'city' => null,
                'chips' => $this->globalTopCategories(),
            ]);
        }

        $city = City::find($cityId);
        if (!$city) {
            return response()->json([
                'city' => null,
                'chips' => $this->globalTopCategories(),
            ]);
        }

        return response()->json([
            'city' => [
                'id' => $city->id,
                'name' => $city->name,
            ],
            'chips' => $this->topCategoriesForCity($city),
        ]);
    }

    /**
     * Resolve a visitor IP to a city_id.
     *
     * Order:
     *   1. Cache hit (24h per /24 prefix)
     *   2. Circuit breaker open → return null (skip lookup)
     *   3. ip-api.com lookup → match city name → return id (or null)
     */
    private function resolveCityIdForIp(?string $ip): ?int
    {
        if (!$ip || $this->isPrivateIp($ip)) {
            return null;
        }

        $prefix = $this->ipPrefix($ip);
        $cacheKey = "location_chips.ip.{$prefix}";

        $cached = Cache::get($cacheKey);
        if ($cached !== null) {
            return $cached === 'null' ? null : (int) $cached;
        }

        // Circuit breaker — if we've failed recently, don't try
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
                Cache::put($cacheKey, 'null', self::CACHE_TTL);
                return null;
            }

            $cityName = trim((string) $response->json('city', ''));
            if ($cityName === '') {
                Cache::put($cacheKey, 'null', self::CACHE_TTL);
                return null;
            }

            // Case-insensitive + accent-tolerant match against cities table
            $cityId = $this->matchCityByName($cityName);

            if ($cityId === null) {
                Cache::put($cacheKey, 'null', self::CACHE_TTL);
                return null;
            }

            Cache::put($cacheKey, $cityId, self::CACHE_TTL);
            return $cityId;
        } catch (\Throwable $e) {
            Log::warning('IpLocation lookup failed', [
                'ip' => $ip,
                'error' => $e->getMessage(),
            ]);
            $this->tripBreaker($e->getMessage());
            return null;
        }
    }

    /**
     * Match a city name from IP lookup against the `cities` table.
     * Case-insensitive, accent-stripped, prefix fallback.
     */
    private function matchCityByName(string $name): ?int
    {
        $normalized = $this->normalize($name);

        // Build a normalized map once per hour
        $map = Cache::remember('location_chips.city_map', self::CHIPS_CACHE_TTL, function () {
            return City::query()
                ->get(['id', 'name'])
                ->mapWithKeys(fn($c) => [$this->normalize($c->name) => $c->id])
                ->all();
        });

        if (isset($map[$normalized])) {
            return $map[$normalized];
        }

        // Prefix match (e.g. "Limbe" vs "Limbe I")
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
        // Strip accents (é → e, ï → i, etc.)
        $converted = @iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $s);
        return $converted !== false ? $converted : $s;
    }

    /**
     * Top N categories by business count in a given city.
     */
    private function topCategoriesForCity(City $city): array
    {
        $cacheKey = "location_chips.city.{$city->id}.top";

        return Cache::remember($cacheKey, self::CHIPS_CACHE_TTL, function () use ($city) {
            // Get category IDs that have ≥1 published business in this city
            $rows = \DB::table('business_categories')
                ->join('businesses', 'businesses.id', '=', 'business_categories.business_id')
                ->join('locations', 'locations.business_id', '=', 'businesses.id')
                ->join('categories', 'categories.id', '=', 'business_categories.category_id')
                ->where('businesses.status', 'published')
                ->whereNull('businesses.hidden_at')
                ->whereNull('businesses.deleted_at')
                ->whereNull('locations.hidden_at')
                ->where('locations.city_id', $city->id)
                ->groupBy('categories.id', 'categories.name')
                ->selectRaw('categories.id as category_id, categories.name as category_name, COUNT(DISTINCT businesses.id) as count')
                ->orderByDesc('count')
                ->limit(self::MAX_CHIPS)
                ->get();

            return $rows->map(function ($row) use ($city) {
                return [
                    'category_id' => (int) $row->category_id,
                    'category_name' => $row->category_name,
                    'label' => $row->category_name . ' in ' . $city->name,
                    'count' => (int) $row->count,
                ];
            })->all();
        });
    }

    /**
     * Global top categories (no city). Used when IP can't be resolved.
     */
    private function globalTopCategories(): array
    {
        return Cache::remember('location_chips.global.top', self::CHIPS_CACHE_TTL, function () {
            $rows = \DB::table('business_categories')
                ->join('businesses', 'businesses.id', '=', 'business_categories.business_id')
                ->join('categories', 'categories.id', '=', 'business_categories.category_id')
                ->where('businesses.status', 'published')
                ->whereNull('businesses.hidden_at')
                ->whereNull('businesses.deleted_at')
                ->groupBy('categories.id', 'categories.name')
                ->selectRaw('categories.id as category_id, categories.name as category_name, COUNT(DISTINCT businesses.id) as count')
                ->orderByDesc('count')
                ->limit(self::MAX_CHIPS)
                ->get();

            return $rows->map(function ($row) {
                return [
                    'category_id' => (int) $row->category_id,
                    'category_name' => $row->category_name,
                    'label' => $row->category_name,
                    'count' => (int) $row->count,
                ];
            })->all();
        });
    }

    private function ipPrefix(string $ip): string
    {
        // Take first 3 octets of IPv4 for shared caching (/24)
        if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4)) {
            $parts = explode('.', $ip);
            return "{$parts[0]}.{$parts[1]}.{$parts[2]}";
        }
        // IPv6 — just use the whole thing (rare in this context)
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