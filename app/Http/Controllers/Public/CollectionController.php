<?php

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Services\CollectionService;
use Inertia\Inertia;

class CollectionController extends Controller
{
    public function __construct(private CollectionService $collections)
    {
    }

    /**
     * GET /{slug}  where slug matches "{category}-in-{city}"
     */
    public function show(string $slug)
    {
        $resolved = $this->collections->resolveSlug($slug);

        if (!$resolved) {
            abort(404);
        }

        ['category' => $category, 'city' => $city] = $resolved;

        // Threshold gate — thin pages should not exist
        if (!$this->collections->isValidCollection($category, $city)) {
            abort(404);
        }

        $total = $this->collections->countBusinesses($category, $city);

        // Sort — same options as the directory
        $sort = request('sort', 'newest');

        $query = $this->collections->businessesQuery($category, $city);

        switch ($sort) {
            case 'rating':
                $query->orderByDesc('business_reviews_avg_rating');
                break;
            case 'reviews':
                $query->orderByDesc('business_reviews_count');
                break;
            case 'name':
                $query->orderBy('name', 'asc');
                break;
            case 'newest':
            default:
                $query->orderByDesc('is_featured')->latest('published_at');
                break;
        }

        $listings = $query->paginate($this->collections->perPage())->withQueryString();

        // PHASE 11 / WAVE 1D-1 — discovery results are LISTINGS.
        $listings->getCollection()->transform(function ($listing) {
            return (new \App\Http\Resources\ListingDirectoryResource($listing))->resolve();
        });

        // Cross-links
        $otherCategories = $this->collections->otherCategoriesInCity($city, $category->id);
        $otherCities = $this->collections->otherCitiesWithCategory($category, $city->id);

        // Breadcrumb support — total in category (across all cities)
        $categoryTotal = $this->collections->countCategoryTotal($category);

        return Inertia::render('Public/Collection', [
            'category' => $category->only(['id', 'name', 'slug', 'icon']),
            'city' => $city->only(['id', 'name', 'slug']),
            'region' => $city->region?->only(['id', 'name']) ?? null,
            'country' => $city->region?->country?->only(['id', 'name']) ?? null,
            'listings' => $listings,
            'total' => $total,
            'categoryTotal' => $categoryTotal,
            'sort' => $sort,
            'otherCategories' => $otherCategories,
            'otherCities' => $otherCities,
            'seo' => [
                'title' => "{$category->name} in {$city->name} · Omniscient",
                'description' => "Find {$total} {$category->name} in {$city->name}, Cameroon. Read reviews, view hours, and contact directly. Browse by rating, amenities, and location.",
                'canonical' => url("/{$slug}"),
                'h1' => "{$category->name} in {$city->name}",
            ],
        ]);
    }
}