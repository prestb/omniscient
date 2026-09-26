<?php

namespace App\Services;

use App\Models\Category;
use App\Models\City;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;

/**
 * SearchIntentParser
 *
 * Extracts "X in Y" / "X near Y" intent from a free-text search query.
 *
 *   "Electricians in Buea"   → ['category_id' => 15, 'city_id' => 42, 'cleaned_query' => null]
 *   "electricians near buea" → same
 *   "Electricians in Unknown"→ ['category_id' => 15, 'city_id' => null, 'cleaned_query' => 'Unknown']
 *   "Random text"            → ['category_id' => null, 'city_id' => null, 'cleaned_query' => 'Random text']
 *
 * Cache: category + city name→id maps are cached for 1 hour.
 */
class SearchIntentParser
{
    private const CACHE_TTL_SECONDS = 3600;

    // Separators — first match wins, checked in order
    private const SEPARATORS = [' in ', ' near '];

    /**
     * @return array{category_id: ?int, city_id: ?int, cleaned_query: ?string}
     */
    public function parse(?string $rawQuery): array
    {
        $empty = [
            'category_id' => null,
            'city_id' => null,
            'cleaned_query' => $rawQuery,
        ];

        if (!$rawQuery || !is_string($rawQuery)) {
            return $empty;
        }

        $normalized = $this->normalize($rawQuery);
        if ($normalized === '') {
            return $empty;
        }

        // Find separator (first match wins)
        $separatorPos = null;
        $separatorLen = 0;
        foreach (self::SEPARATORS as $sep) {
            $pos = strpos($normalized, $sep);
            if ($pos !== false) {
                $separatorPos = $pos;
                $separatorLen = strlen($sep);
                break;
            }
        }

        // No "X in Y" pattern → fall through unchanged
        if ($separatorPos === null) {
            return $empty;
        }

        $leftCandidate = trim(substr($normalized, 0, $separatorPos));
        $rightCandidate = trim(substr($normalized, $separatorPos + $separatorLen));

        // Both sides need content
        if ($leftCandidate === '' || $rightCandidate === '') {
            return $empty;
        }

        $categoryId = $this->resolveCategory($leftCandidate);
        $cityId = $this->resolveCity($rightCandidate);

        // Neither matched — no regression, return as-is
        if ($categoryId === null && $cityId === null) {
            return $empty;
        }

        // Both matched — full intent captured, drop raw text
        if ($categoryId !== null && $cityId !== null) {
            return [
                'category_id' => $categoryId,
                'city_id' => $cityId,
                'cleaned_query' => null,
            ];
        }

        // Only category matched — keep right side as text search
        if ($categoryId !== null) {
            return [
                'category_id' => $categoryId,
                'city_id' => null,
                'cleaned_query' => $rightCandidate,
            ];
        }

        // Only city matched — keep left side as text search
        return [
            'category_id' => null,
            'city_id' => $cityId,
            'cleaned_query' => $leftCandidate,
        ];
    }

    /**
     * Normalize whitespace + lowercase for matching.
     */
    private function normalize(string $raw): string
    {
        $s = preg_replace('/\s+/', ' ', trim($raw));
        return strtolower($s);
    }

    // ============== CATEGORY RESOLUTION ==============

    private function resolveCategory(string $candidate): ?int
    {
        $map = $this->categoryMap();
        if (empty($map)) {
            return null;
        }

        // Exact
        if (isset($map[$candidate])) {
            return $map[$candidate];
        }

        // Singular / plural tolerance — try dropping a trailing 's'
        if (Str::endsWith($candidate, 's')) {
            $singular = substr($candidate, 0, -1);
            if (isset($map[$singular])) {
                return $map[$singular];
            }
        }

        // Also try adding an 's' (in case map stored singular but user typed singular)
        $plural = $candidate . 's';
        if (isset($map[$plural])) {
            return $map[$plural];
        }

        // Prefix match
        foreach ($map as $name => $id) {
            if (Str::startsWith($name, $candidate)) {
                return $id;
            }
        }

        // Substring match
        foreach ($map as $name => $id) {
            if (Str::contains($name, $candidate)) {
                return $id;
            }
        }

        return null;
    }

    // ============== CITY RESOLUTION ==============

    private function resolveCity(string $candidate): ?int
    {
        $map = $this->cityMap();
        if (empty($map)) {
            return null;
        }

        if (isset($map[$candidate])) {
            return $map[$candidate];
        }

        if (Str::endsWith($candidate, 's')) {
            $singular = substr($candidate, 0, -1);
            if (isset($map[$singular])) {
                return $map[$singular];
            }
        }

        foreach ($map as $name => $id) {
            if (Str::startsWith($name, $candidate)) {
                return $id;
            }
        }

        foreach ($map as $name => $id) {
            if (Str::contains($name, $candidate)) {
                return $id;
            }
        }

        return null;
    }

    // ============== CACHED MAPS ==============

    /**
     * @return array<string, int>  [lowercased_name => id]
     */
    private function categoryMap(): array
    {
        return Cache::remember('search_intent.categories', self::CACHE_TTL_SECONDS, function () {
            return Category::query()
                ->get(['id', 'name'])
                ->mapWithKeys(fn($c) => [strtolower(trim($c->name)) => $c->id])
                ->all();
        });
    }

    /**
     * @return array<string, int>
     */
    private function cityMap(): array
    {
        return Cache::remember('search_intent.cities', self::CACHE_TTL_SECONDS, function () {
            return City::query()
                ->get(['id', 'name'])
                ->mapWithKeys(fn($c) => [strtolower(trim($c->name)) => $c->id])
                ->all();
        });
    }

    /**
     * ✅ Build smart-search suggestions for a partial query.
     *
     * Given a partial query like "supermark", returns suggestions:
     *   [
     *     ['label' => 'Supermarkets near me', 'query' => 'supermarkets near me', 'type' => 'near_me'],
     *     ['label' => 'Supermarkets in Buea', 'query' => 'supermarkets in buea', 'type' => 'city'],
     *     ...
     *   ]
     *
     * Returns [] if the partial query doesn't match a known category.
     *
     * @return array<int, array{label: string, query: string, type: string}>
     */
    public function buildSuggestionsFor(string $partialQuery, ?int $userCityId = null): array
    {
        $partial = $this->normalize($partialQuery);
        if (strlen($partial) < 3) {
            return [];
        }

        // Find the category this partial matches (prefix or substring)
        $map = $this->categoryMap();
        $matchedCategoryName = null;
        $matchedCategoryId = null;

        // Exact first
        if (isset($map[$partial])) {
            $matchedCategoryName = $partial;
            $matchedCategoryId = $map[$partial];
        }

        // Prefix
        if ($matchedCategoryId === null) {
            foreach ($map as $name => $id) {
                if (Str::startsWith($name, $partial)) {
                    $matchedCategoryName = $name;
                    $matchedCategoryId = $id;
                    break;
                }
            }
        }

        // Substring
        if ($matchedCategoryId === null) {
            foreach ($map as $name => $id) {
                if (Str::contains($name, $partial)) {
                    $matchedCategoryName = $name;
                    $matchedCategoryId = $id;
                    break;
                }
            }
        }

        if ($matchedCategoryId === null) {
            return [];
        }

        // Preserve original casing for the label (from DB)
        $categoryDisplay = Cache::remember(
            "search_intent.category_name.{$matchedCategoryId}",
            self::CACHE_TTL_SECONDS,
            fn() => Category::where('id', $matchedCategoryId)->value('name') ?? $matchedCategoryName
        );

        $suggestions = [];
        $lowercaseCategory = $categoryDisplay;

        // 1. "near me" — always show first
        $suggestions[] = [
            'label' => "{$lowercaseCategory} near me",
            'query' => "{$lowercaseCategory} near me",
            'type' => 'near_me',
        ];

        // 2. User's detected city (if available) — always second
        if ($userCityId) {
            $cityName = Cache::remember(
                "search_intent.city_name.{$userCityId}",
                self::CACHE_TTL_SECONDS,
                fn() => City::where('id', $userCityId)->value('name')
            );

            if ($cityName) {
                $suggestions[] = [
                    'label' => "{$lowercaseCategory} in {$cityName}",
                    'query' => "{$lowercaseCategory} in {$cityName}",
                    'type' => 'city',
                ];
            }
        }

        // 3. Top cities with businesses in this category (excluding user's city)
        $topCities = $this->topCitiesForCategory($matchedCategoryId, $userCityId, 3);
        foreach ($topCities as $city) {
            $suggestions[] = [
                'label' => "{$lowercaseCategory} in {$city['name']}",
                'query' => "{$lowercaseCategory} in {$city['name']}",
                'type' => 'city',
            ];
        }

        // Cap at 5 total
        return array_slice($suggestions, 0, 5);
    }

    /**
     * ✅ Get top N cities with the most published businesses in a category.
     *
     * @return array<int, array{id: int, name: string, count: int}>
     */
    private function topCitiesForCategory(int $categoryId, ?int $excludeCityId = null, int $limit = 3): array
    {
        $cacheKey = "search_intent.top_cities.{$categoryId}." . ($excludeCityId ?? 'none') . ".{$limit}";

        return Cache::remember($cacheKey, self::CACHE_TTL_SECONDS, function () use ($categoryId, $excludeCityId, $limit) {
            $rows = \DB::table('business_categories')
                ->join('businesses', 'businesses.id', '=', 'business_categories.business_id')
                ->join('branches', 'branches.business_id', '=', 'businesses.id')
                ->join('cities', 'cities.id', '=', 'branches.city_id')
                ->where('businesses.status', 'published')
                ->whereNull('businesses.hidden_at')
                ->whereNull('businesses.deleted_at')
                ->whereNull('branches.hidden_at')
                ->where('business_categories.category_id', $categoryId)
                ->when($excludeCityId, fn($q) => $q->where('cities.id', '!=', $excludeCityId))
                ->groupBy('cities.id', 'cities.name')
                ->orderByRaw('COUNT(DISTINCT businesses.id) DESC')
                ->limit($limit)
                ->selectRaw('cities.id, cities.name, COUNT(DISTINCT businesses.id) as count')
                ->get();

            return $rows->map(fn($r) => [
                'id' => (int) $r->id,
                'name' => $r->name,
                'count' => (int) $r->count,
            ])->all();
        });
    }
}