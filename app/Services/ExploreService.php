<?php

namespace App\Services;

use App\Models\Business;
use App\Models\Category;
use App\Models\City;
use App\Models\Listing;
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
            $q = Listing::query()
                ->with([
                    'business:id,name,slug,logo,cover_image',
                    'location.city',
                    'location.region',
                    'location.country',
                    'categories',
                    'images',
                ])
                ->where('status', Listing::STATUS_PUBLISHED)
                ->whereNull('hidden_at')
                ->whereHas('categories', fn($c) => $c->where('categories.id', $categoryId))
                ->withCount('businessReviews')
                ->withAvg('businessReviews', 'rating');

            if ($cityId) {
                $q->whereHas('location', fn($x) => $x->where('city_id', $cityId));
            }

            $q->orderByDesc('is_featured')->latest('published_at');

            return $q->take(self::BUSINESSES_PER_ROW)
                ->get()
                ->map(fn($l) => $this->cardPayload($l))
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
            return Listing::query()
                ->where('status', Listing::STATUS_PUBLISHED)
                ->whereNull('hidden_at')
                ->whereHas('categories', fn($q) => $q->where('categories.id', $categoryId))
                ->whereHas('location', fn($q) => $q->where('city_id', $cityId))
                ->count();
        });
    }

    private function countGlobal(int $categoryId): int
    {
        return Cache::remember("explore.count.{$categoryId}.all", self::ROW_CACHE_TTL, function () use ($categoryId) {
            return Listing::query()
                ->where('status', Listing::STATUS_PUBLISHED)
                ->whereNull('hidden_at')
                ->whereHas('categories', fn($q) => $q->where('categories.id', $categoryId))
                ->count();
        });
    }

    /**
     * Slim payload for the ExploreCard — a LISTING.
     */
    private function cardPayload(Listing $listing): array
    {
        $location = $listing->location;
        $category = $listing->categories->first();
        $logo = $listing->images->firstWhere('type', \App\Models\ListingImage::TYPE_LOGO);
        $cover = $listing->images->firstWhere('type', \App\Models\ListingImage::TYPE_COVER);

        return [
            'id' => $listing->id,
            'type' => 'listing',
            'listing_type' => $listing->getListingType()->value,
            'name' => $listing->name,
            'slug' => $listing->slug,
            // ✅ Raw paths for OptimizedImage (variants)
            'cover_image' => $cover?->path ?? $listing->business?->cover_image,
            'logo' => $logo?->path ?? $listing->business?->logo,
            // Keep URLs for backwards compatibility / fallbacks
            'cover_image_url' => $cover?->url ?? $listing->business?->cover_image_url,
            'logo_url' => $logo?->url ?? $listing->business?->logo_url,
            'average_rating' => $listing->business_reviews_avg_rating,
            'reviews_count' => (int) ($listing->business_reviews_count ?? 0),
            'category' => $category?->name,
            'city' => $location?->city?->name,
        ];
    }
}