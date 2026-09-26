<?php

namespace App\Services;

use App\Models\Business;
use App\Models\Category;
use App\Models\City;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;

/**
 * CollectionService
 *
 * Resolves "{category}-in-{city}" slugs into entities + query helpers
 * for the collection pages (#14a).
 *
 * Every collection page is dynamic — no DB table needed. Any
 * {category} in {city} combo that has ≥ MIN_BUSINESSES published
 * businesses becomes a valid page.
 */
class CollectionService
{
    public const MIN_BUSINESSES = 2;
    private const RESOLUTION_CACHE_TTL = 3600;    // 1 hour
    private const CROSSLINK_CACHE_TTL = 3600;     // 1 hour
    private const PER_PAGE = 12;

    /**
     * Parse a collection slug like "hotels-in-buea" into
     * ['category' => Category|null, 'city' => City|null].
     *
     * Returns null if the slug doesn't match the pattern.
     */
    public function resolveSlug(string $slug): ?array
    {
        $slug = trim(strtolower($slug));
        if ($slug === '' || !str_contains($slug, '-in-')) {
            return null;
        }

        // Cache the resolution — slug → [category_id, city_id] or 'invalid'
        $cacheKey = "collection.resolve.{$slug}";
        $cached = Cache::get($cacheKey);

        if ($cached === 'invalid') {
            return null;
        }

        if (is_array($cached)) {
            $category = Category::find($cached['category_id']);
            $city = City::find($cached['city_id']);
            if (!$category || !$city) {
                return null;
            }
            return ['category' => $category, 'city' => $city];
        }

        // Split on the LAST occurrence of "-in-"
        // Handles categories with "in" in the name (e.g. "check-in")
        $pos = strrpos($slug, '-in-');
        if ($pos === false) {
            Cache::put($cacheKey, 'invalid', self::RESOLUTION_CACHE_TTL);
            return null;
        }

        $categorySlug = substr($slug, 0, $pos);
        $citySlug = substr($slug, $pos + 4); // skip "-in-"

        if ($categorySlug === '' || $citySlug === '') {
            Cache::put($cacheKey, 'invalid', self::RESOLUTION_CACHE_TTL);
            return null;
        }

        $category = Category::where('slug', $categorySlug)->first();
        $city = City::where('slug', $citySlug)->first();

        if (!$category || !$city) {
            Cache::put($cacheKey, 'invalid', self::RESOLUTION_CACHE_TTL);
            return null;
        }

        Cache::put($cacheKey, [
            'category_id' => $category->id,
            'city_id' => $city->id,
        ], self::RESOLUTION_CACHE_TTL);

        return ['category' => $category, 'city' => $city];
    }

    /**
     * Build a collection slug from a category + city.
     */
    public function buildSlug(Category $category, City $city): string
    {
        return Str::slug($category->name) . '-in-' . Str::slug($city->name);
    }

    /**
     * Base query: published businesses in {category} AND {city}.
     * Applies across ALL branches (multi-location aware).
     */
    public function businessesQuery(Category $category, City $city)
    {
        return Business::query()
            ->with([
                'categories',
                'primaryBranch',
                'branches' => fn($q) => $q->whereNull('hidden_at'),
                'branches.city',
                'branches.region',
                'branches.country',
                'branches.hours',
                'branches.hourOverrides',
                'logo',
                'coverImage',
                'galleryImages' => fn($q) => $q->whereNull('hidden_at'),
                'owner:id,name,role',
                'owner.activeSubscription.plan',
            ])
            ->where('status', Business::STATUS_PUBLISHED)
            ->whereNull('hidden_at')
            ->whereHas('categories', fn($q) => $q->where('categories.id', $category->id))
            ->whereHas('branches', fn($q) => $q->where('city_id', $city->id))
            ->withCount(['reviews', 'galleryImages'])
            ->withAvg('reviews', 'rating');
    }

    /**
     * Count published businesses for a category + city.
     * Used for threshold checks.
     */
    public function countBusinesses(Category $category, City $city): int
    {
        $cacheKey = "collection.count.{$category->id}.{$city->id}";

        return Cache::remember($cacheKey, self::CROSSLINK_CACHE_TTL, function () use ($category, $city) {
            return Business::query()
                ->where('status', Business::STATUS_PUBLISHED)
                ->whereNull('hidden_at')
                ->whereHas('categories', fn($q) => $q->where('categories.id', $category->id))
                ->whereHas('branches', fn($q) => $q->where('city_id', $city->id))
                ->count();
        });
    }

    /**
     * Should this combo become a valid collection page?
     */
    public function isValidCollection(Category $category, City $city): bool
    {
        return $this->countBusinesses($category, $city) >= self::MIN_BUSINESSES;
    }

    /**
     * Other categories that exist in the same city.
     * Returns up to $limit categories, excluding $excludeCategoryId.
     *
     * @return array<array{category: Category, count: int, slug: string}>
     */
    public function otherCategoriesInCity(City $city, ?int $excludeCategoryId = null, int $limit = 6): array
    {
        $cacheKey = "collection.other_cats.{$city->id}.{$excludeCategoryId}.{$limit}";

        return Cache::remember($cacheKey, self::CROSSLINK_CACHE_TTL, function () use ($city, $excludeCategoryId, $limit) {
            $rows = \DB::table('business_categories')
                ->join('businesses', 'businesses.id', '=', 'business_categories.business_id')
                ->join('branches', 'branches.business_id', '=', 'businesses.id')
                ->join('categories', 'categories.id', '=', 'business_categories.category_id')
                ->where('businesses.status', Business::STATUS_PUBLISHED)
                ->whereNull('businesses.hidden_at')
                ->whereNull('businesses.deleted_at')
                ->whereNull('branches.hidden_at')
                ->where('branches.city_id', $city->id)
                ->when($excludeCategoryId, fn($q) => $q->where('categories.id', '!=', $excludeCategoryId))
                ->groupBy('categories.id', 'categories.name', 'categories.slug')
                ->havingRaw('COUNT(DISTINCT businesses.id) >= ?', [self::MIN_BUSINESSES])
                ->selectRaw('categories.id, categories.name, categories.slug, COUNT(DISTINCT businesses.id) as count')
                ->orderByDesc('count')
                ->limit($limit)
                ->get();

            return $rows->map(fn($r) => [
                'category' => (object) [
                    'id' => (int) $r->id,
                    'name' => $r->name,
                    'slug' => $r->slug,
                ],
                'count' => (int) $r->count,
                'slug' => $r->slug . '-in-' . Str::slug($city->name),
            ])->all();
        });
    }

    /**
     * Other cities that have this category.
     * Returns up to $limit cities, excluding $excludeCityId.
     *
     * @return array<array{city: City, count: int, slug: string}>
     */
    public function otherCitiesWithCategory(Category $category, ?int $excludeCityId = null, int $limit = 6): array
    {
        $cacheKey = "collection.other_cities.{$category->id}.{$excludeCityId}.{$limit}";

        return Cache::remember($cacheKey, self::CROSSLINK_CACHE_TTL, function () use ($category, $excludeCityId, $limit) {
            $rows = \DB::table('business_categories')
                ->join('businesses', 'businesses.id', '=', 'business_categories.business_id')
                ->join('branches', 'branches.business_id', '=', 'businesses.id')
                ->join('cities', 'cities.id', '=', 'branches.city_id')
                ->where('businesses.status', Business::STATUS_PUBLISHED)
                ->whereNull('businesses.hidden_at')
                ->whereNull('businesses.deleted_at')
                ->whereNull('branches.hidden_at')
                ->where('business_categories.category_id', $category->id)
                ->when($excludeCityId, fn($q) => $q->where('cities.id', '!=', $excludeCityId))
                ->groupBy('cities.id', 'cities.name', 'cities.slug')
                ->havingRaw('COUNT(DISTINCT businesses.id) >= ?', [self::MIN_BUSINESSES])
                ->selectRaw('cities.id, cities.name, cities.slug, COUNT(DISTINCT businesses.id) as count')
                ->orderByDesc('count')
                ->limit($limit)
                ->get();

            return $rows->map(fn($r) => [
                'city' => (object) [
                    'id' => (int) $r->id,
                    'name' => $r->name,
                    'slug' => $r->slug,
                ],
                'count' => (int) $r->count,
                'slug' => Str::slug($category->name) . '-in-' . $r->slug,
            ])->all();
        });
    }

    /**
     * Total published businesses for a category (no city filter).
     * Used for the "View all {category}" breadcrumb segment link.
     */
    public function countCategoryTotal(Category $category): int
    {
        $cacheKey = "collection.cat_total.{$category->id}";

        return Cache::remember($cacheKey, self::CROSSLINK_CACHE_TTL, function () use ($category) {
            return Business::query()
                ->where('status', Business::STATUS_PUBLISHED)
                ->whereNull('hidden_at')
                ->whereHas('categories', fn($q) => $q->where('categories.id', $category->id))
                ->count();
        });
    }

    public function perPage(): int
    {
        return self::PER_PAGE;
    }
}