<?php

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Models\Business;
use App\Models\Category;
use App\Models\City;
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

        // Build search query
        $search = Business::search($effectiveQuery);

        // Apply filters using WHERE clauses
        $search->where('status', 'published');
        $search->where('has_active_subscription', true);
        $search->where('hidden', false);   // ✅ skip hidden businesses

        if ($categoryId) {
            $search->where('category_ids', (int) $categoryId);
        }

        if ($cityId) {
            // ✅ Filter on the union of all branches' city_ids, so multi-branch
            //    businesses match a search in any of their cities.
            $search->where('city_ids', (int) $cityId);
        }

        if ($regionId) {
            $search->where('region_ids', (int) $regionId);
        }

        if ($featured) {
            $search->where('is_featured', true);
        }

        if ($openNow) {
            $search->where('is_open_now', true);
        }

        $businesses = $search->paginate(12);

        // Get filter data
        $categories = Category::active()->root()->ordered()->get();
        $regions = Region::active()->get();
        $cities = City::active()->get();

        return Inertia::render('Public/Search/Index', [
            'businesses' => $businesses,
            'query' => $query,
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
            $suggestions = Business::search($query)
                ->where('status', 'published')
                ->where('has_active_subscription', true)
                ->where('hidden', false)   // ✅ skip hidden businesses
                ->take(5)
                ->get()
                ->map(function ($business) {
                    $branch = $business->primaryLocation ?? $business->locations->first();
                    $category = $business->categories->first();

                    return [
                        'id' => $business->id,
                        'name' => $business->name,
                        'slug' => $business->slug,
                        'category' => $category?->name,
                        'city' => $branch?->city?->name,
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
                        'businesses_count' => $category->listings_count,
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