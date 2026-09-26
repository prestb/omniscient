<?php

namespace App\Services;

use App\Models\Business;
use App\Models\Category;
use App\Models\City;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;

class ExploreService
{
    public const MIN_PER_ROW = 2;
    public const BUSINESSES_PER_ROW = 8;
    public const MIN_ROWS_FOR_CITY_MODE = 3;
    private const ROW_CACHE_TTL = 600;   // 10 min
    private const LIST_CACHE_TTL = 3600;

    /**
     * Curated rows — order matters (visual priority).
     */
    private const CURATED_SLUGS = [
        'restaurants',
        'hotels',
        'supermarkets',
        'pharmacies',
        'barbershops',
        'salons',
        'electronics-stores',
        'real-estate',
    ];

    /**
     * Build the row configuration for /explore.
     *
     * Returns:
     *   [
     *     'mode' => 'city' | 'global',
     *     'city' => ['id' => X, 'name' => 'Buea'] | null,
     *     'rows' => [
     *       ['category_id' => X, 'category_name' => 'Restaurants', 'category_slug' => 'restaurants', 'city_id' => Y, 'title' => 'Restaurants in Buea', 'see_all_url' => '/restaurants-in-buea'],
     *       ...
     *     ],
     *   ]
     */
    public function buildFeed(?City $city): array
    {
        $categories = $this->curatedCategories();

        if (!$city) {
            return [
                'mode' => 'global',
                'city' => null,
                'rows' => $this->buildGlobalRows($categories),
            ];
        }

        $cityRows = $this->buildCityRows($categories, $city);

        if (count($cityRows) < self::MIN_ROWS_FOR_CITY_MODE) {
            // Not enough city-local data — fall back to global mode
            return [
                'mode' => 'global',
                'city' => null,
                'rows' => $this->buildGlobalRows($categories),
            ];
        }

        return [
            'mode' => 'city',
            'city' => ['id' => $city->id, 'name' => $city->name],
            'rows' => $cityRows,
        ];
    }

    /**
     * Fetch businesses for a single row.
     * Used by /api/explore/row and by buildCityRows/buildGlobalRows.
     */
    public function fetchRowBusinesses(int $categoryId, ?int $cityId): array
    {
        $cacheKey = "explore.row.{$categoryId}." . ($cityId ?? 'all');

        return Cache::remember($cacheKey, self::ROW_CACHE_TTL, function () use ($categoryId, $cityId) {
            $q = Business::query()
                ->with([
                    'categories',
                    'primaryBranch',
                    'branches' => fn($x) => $x->whereNull('hidden_at'),
                    'branches.city',
                    'branches.region',
                    'branches.country',
                    'logo',
                    'coverImage',
                ])
                ->where('status', Business::STATUS_PUBLISHED)
                ->whereNull('hidden_at')
                ->whereHas('categories', fn($x) => $x->where('categories.id', $categoryId))
                ->withCount('reviews')
                ->withAvg('reviews', 'rating');

            if ($cityId) {
                $q->whereHas('branches', fn($x) => $x->where('city_id', $cityId));
            }

            $q->orderByDesc('is_featured')->latest('published_at');

            return $q->take(self::BUSINESSES_PER_ROW)
                ->get()
                ->map(fn($b) => $this->cardPayload($b))
                ->all();
        });
    }

    // ============== INTERNAL ==============

    private function curatedCategories(): array
    {
        return Cache::remember('explore.curated_categories', self::LIST_CACHE_TTL, function () {
            return Category::query()
                ->whereIn('slug', self::CURATED_SLUGS)
                ->get(['id', 'name', 'slug'])
                ->keyBy('slug')
                ->all();
        });
    }

    private function buildCityRows(array $categories, City $city): array
    {
        $rows = [];

        foreach (self::CURATED_SLUGS as $slug) {
            $category = $categories[$slug] ?? null;
            if (!$category) {
                continue;
            }

            $count = $this->countForCity($category->id, $city->id);
            if ($count < self::MIN_PER_ROW) {
                continue;
            }

            $rows[] = [
                'category_id' => $category->id,
                'category_name' => $category->name,
                'category_slug' => $category->slug,
                'city_id' => $city->id,
                'title' => $category->name . ' in ' . $city->name,
                'see_all_url' => '/' . $category->slug . '-in-' . $city->slug,
            ];
        }

        return $rows;
    }

    private function buildGlobalRows(array $categories): array
    {
        $rows = [];

        foreach (self::CURATED_SLUGS as $slug) {
            $category = $categories[$slug] ?? null;
            if (!$category) {
                continue;
            }

            $count = $this->countGlobal($category->id);
            if ($count < self::MIN_PER_ROW) {
                continue;
            }

            $rows[] = [
                'category_id' => $category->id,
                'category_name' => $category->name,
                'category_slug' => $category->slug,
                'city_id' => null,
                'title' => 'Popular ' . $category->name,
                'see_all_url' => '/directory?category=' . $category->id,
            ];
        }

        return $rows;
    }

    private function countForCity(int $categoryId, int $cityId): int
    {
        return Cache::remember("explore.count.{$categoryId}.{$cityId}", self::ROW_CACHE_TTL, function () use ($categoryId, $cityId) {
            return Business::query()
                ->where('status', Business::STATUS_PUBLISHED)
                ->whereNull('hidden_at')
                ->whereHas('categories', fn($q) => $q->where('categories.id', $categoryId))
                ->whereHas('branches', fn($q) => $q->where('city_id', $cityId))
                ->count();
        });
    }

    private function countGlobal(int $categoryId): int
    {
        return Cache::remember("explore.count.{$categoryId}.all", self::ROW_CACHE_TTL, function () use ($categoryId) {
            return Business::query()
                ->where('status', Business::STATUS_PUBLISHED)
                ->whereNull('hidden_at')
                ->whereHas('categories', fn($q) => $q->where('categories.id', $categoryId))
                ->count();
        });
    }

    /**
     * Slim payload for the ExploreCard (not the full BusinessDirectoryResource).
     */
    private function cardPayload(Business $business): array
    {
        $primary = $business->primaryBranch
            ?? $business->branches->firstWhere('is_primary', true)
            ?? $business->branches->first();

        $category = $business->categories->first();

        return [
            'id' => $business->id,
            'name' => $business->name,
            'slug' => $business->slug,
            // ✅ Raw paths for OptimizedImage (variants)
            'cover_image' => $business->cover_image,
            'logo' => $business->logo,
            // Keep URLs for backwards compatibility / fallbacks
            'cover_image_url' => $business->cover_image_url,
            'logo_url' => $business->logo_url,
            'average_rating' => $business->average_rating,
            'reviews_count' => $business->total_reviews,
            'category' => $category?->name,
            'city' => $primary?->city?->name,
        ];
    }
}