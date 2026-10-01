<?php

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Http\Resources\ListingDirectoryResource;
use App\Models\Category;
use App\Models\City;
use App\Models\Listing;
use App\Support\DiscoverySort;
use App\Models\Region;
use App\Services\SearchIntentParser;
use Illuminate\Http\Request;
use Inertia\Inertia;

class SearchController extends Controller
{
    public function index(Request $request)
    {
        $query = $request->input('q', '');
        $categoryId = $request->input('category');
        $cityId = $request->input('city');
        $regionId = $request->input('region');
        $featured = $request->input('featured');
        $openNow = $request->input('open_now');

        // ✅ Parse "X in Y" / "X near Y" intent from the raw query.
        //    Merge-only: fills null slots, never overrides explicit filters.
        $intent = app(SearchIntentParser::class)->parse($query);
        if ($categoryId === null && $intent['category_id'] !== null) {
            $categoryId = $intent['category_id'];
        }
        if ($cityId === null && $intent['city_id'] !== null) {
            $cityId = $intent['city_id'];
        }

        // If the parser fully understood the intent, drop the raw text.
        // If it only partially understood, keep the extracted remainder as search.
        $effectiveQuery = $intent['cleaned_query'] ?? '';
        if ($effectiveQuery === null) {
            $effectiveQuery = '';
        }

        // PHASE 11 / WAVE 1D-1 — discovery queries LISTINGS.
        // A Business is an organization, never a competing discovery entity.
        $search = Listing::search($effectiveQuery);

        // Apply filters using WHERE clauses
        $search->where('status', 'published');
        $search->where('has_active_subscription', true);
        $search->where('hidden', false);

        if ($categoryId) {
            $search->where('category_ids', (int) $categoryId);
        }

        if ($cityId) {
            // A Listing has 0 or 1 Location, so the city filter is singular.
            $search->where('city_id', (int) $cityId);
        }

        if ($regionId) {
            $search->where('region_id', (int) $regionId);
        }

        if ($featured) {
            $search->where('is_featured', true);
        }

        if ($openNow) {
            $search->where('is_open_now', true);
        }

        // PHASE 13 — canonical discovery sort vocabulary.
        // Default is RELEVANCE, which applies NO orderBy so Meilisearch's
        // textual ranking is left completely intact.
        $sort = DiscoverySort::normalize($request->input('sort'));

        foreach (DiscoverySort::meilisearchOrder($sort) as [$column, $direction]) {
            $search->orderBy($column, $direction);
        }

        $listings = $search->paginate(12)->withQueryString();

        // Get filter data
        $categories = Category::active()->root()->ordered()->get();
        $regions = Region::active()->get();
        $cities = City::active()->get();

        return Inertia::render('Public/Search/Index', [
            'listings' => ListingDirectoryResource::collection($listings),
            // PHASE 15B — /search is a discovery UTILITY. Arbitrary q/filter/
            // sort/page permutations must not be indexed. `follow` keeps the
            // Listing links inside the results crawlable.
            'seo' => [
                'title' => $query !== '' ? $query . ' - Search - Omniscient' : 'Search - Omniscient',
                'robots' => 'noindex, follow',
                'canonical' => url('/search'),
            ],
            'query' => $query,
            'sort' => $sort,
            'sortOptions' => DiscoverySort::options(),
            'filters' => [
                'category' => $categoryId,
                'city' => $cityId,
                'region' => $regionId,
                'featured' => $featured,
                'open_now' => $openNow,
            ],
            'categories' => $categories,
            'regions' => $regions,
            'cities' => $cities,
        ]);
    }

    public function autocomplete(Request $request)
    {
        $query = $request->input('q', '');

        if (strlen($query) < 2) {
            return response()->json([
                'suggestions' => [],
                'category_suggestions' => [],
                'search_suggestions' => [],
            ]);
        }

        try {
            $suggestions = Listing::search($query)
                ->where('status', 'published')
                ->where('has_active_subscription', true)
                ->where('hidden', false)
                ->take(5)
                ->get()
                ->map(function ($listing) {
                    // PHASE 11 / WAVE 1D-1 — the discoverable identity is the
                    // LISTING. The organization is contextual metadata only.
                    return [
                        'id' => $listing->id,
                        'type' => 'listing',
                        'listing_type' => $listing->getListingType()->value,
                        'name' => $listing->name,
                        'slug' => $listing->slug,
                        'category' => $listing->categories->first()?->name,
                        'city' => $listing->location?->city?->name,
                    ];
                });

            // Add category suggestions
            //
            // ✅ Two fixes applied here:
            //    1. Tightened the match — prefix or word-boundary only.
            //       (Previously `%query%` matched mid-word, so "res" matched
            //       "Provision Stores" and "Clothing Stores".)
            //    2. Only categories with at least one published business are
            //       shown, so users never click into an empty listing.
            $categorySuggestions = Category::query()
                ->where(function ($q) use ($query) {
                    $q->where('name', 'like', "{$query}%")            // prefix
                      ->orWhere('name', 'like', "% {$query}%");       // word boundary
                })
                ->active()
                // PHASE 9 — discovery is Listing-centric. A category is only
                // suggested when it has at least one PUBLISHED, VISIBLE listing.
                ->whereHas('listings', function ($q) {
                    $q->where('status', 'published')
                      ->whereNull('hidden_at');
                })
                ->withCount(['listings' => function ($q) {
                    $q->where('status', 'published')
                      ->whereNull('hidden_at');
                }])
                ->orderByDesc('listings_count')
                ->take(5)
                ->get()
                ->map(function ($category) {
                    return [
                        'id' => $category->id,
                        'name' => $category->name,
                        'type' => 'category',
                        'icon' => $category->icon,
                        'listings_count' => $category->listings_count,
                    ];
                });

            // ✅ Smart-search suggestions ("supermarkets near me", "supermarkets in Buea")
            $userCityId = null;
            try {
                $userCityId = app(\App\Services\IpLocationService::class)->resolveCityId($request->ip());
            } catch (\Throwable $e) {
                // IP lookup failed — proceed without a user city
            }

            $searchSuggestions = app(\App\Services\SearchIntentParser::class)
                ->buildSuggestionsFor($query, $userCityId);

            return response()->json([
                'suggestions' => $suggestions,
                'category_suggestions' => $categorySuggestions,
                'search_suggestions' => $searchSuggestions,
            ]);
        } catch (\Exception $e) {
            \Log::error('Autocomplete error: ' . $e->getMessage());
            return response()->json([
                'suggestions' => [],
                'category_suggestions' => [],
                'search_suggestions' => [],
            ]);
        }
    }
}