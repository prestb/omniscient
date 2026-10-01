<?php

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Models\Business;
use App\Models\Listing;
use App\Services\CollectionService;
use Illuminate\Support\Facades\DB;

/**
 * PHASE 15B — DYNAMIC XML SITEMAP.
 *
 * Enumerates CANONICAL PUBLIC URLS ONLY:
 *
 *   /listing/{slug}    published, visible, not soft-deleted
 *   /business/{slug}   only when the organization has at least one published,
 *                      visible Listing (a Business row alone is not a page)
 *   /{cat}-in-{city}   only pairs that pass CollectionService::isValidCollection()
 *
 * It never emits query-parameter URLs, JSON endpoints, pagination variants,
 * unpublished/soft-deleted entities, or theoretical category x city
 * combinations. Every URL is built with url(), so the canonical domain comes
 * from APP_URL — no production domain is hardcoded.
 *
 * No table, no persistence, no cache: the sitemap is derived on request.
 */
class SitemapController extends Controller
{
    public function __construct(private CollectionService $collections)
    {
    }

    public function index()
    {
        $urls = array_merge(
            $this->listingUrls(),
            $this->businessUrls(),
            $this->collectionUrls(),
        );

        // Defensive: no duplicates, canonical URLs only.
        $urls = array_values(array_unique($urls, SORT_STRING));

        $xml = $this->render($urls);

        return response($xml, 200, [
            'Content-Type' => 'application/xml; charset=utf-8',
        ]);
    }

    /** @return array<int, string> */
    private function listingUrls(): array
    {
        return Listing::query()
            ->where('status', Listing::STATUS_PUBLISHED)
            ->whereNull('hidden_at')
            ->whereNotNull('slug')
            ->orderBy('id')
            ->pluck('slug')
            ->map(fn($slug) => url("/listing/{$slug}"))
            ->all();
    }

    /**
     * Only Businesses that actually render a meaningful public page.
     * A Business row with no published Listings is not a discovery surface.
     *
     * @return array<int, string>
     */
    private function businessUrls(): array
    {
        return Business::query()
            ->where('status', 'published')   // same gate as DirectoryController::show
            ->whereNull('hidden_at')
            ->whereNotNull('slug')
            ->whereHas('listings', function ($q) {
                $q->where('status', Listing::STATUS_PUBLISHED)
                    ->whereNull('listings.hidden_at');
            })
            ->orderBy('id')
            ->pluck('slug')
            ->map(fn($slug) => url("/business/{$slug}"))
            ->all();
    }

    /**
     * Existing curated collections only.
     *
     * Candidate pairs are discovered from real published inventory, then each is
     * CONFIRMED through the same isValidCollection() gate the public route uses,
     * so the sitemap can never advertise a slug that would 404.
     *
     * @return array<int, string>
     */
    private function collectionUrls(): array
    {
        $pairs = DB::table('listing_categories')
            ->join('listings', 'listings.id', '=', 'listing_categories.listing_id')
            ->join('locations', 'locations.id', '=', 'listings.location_id')
            ->where('listings.status', Listing::STATUS_PUBLISHED)
            ->whereNull('listings.hidden_at')
            ->whereNull('listings.deleted_at')
            ->whereNotNull('locations.city_id')
            ->distinct()
            ->select('listing_categories.category_id', 'locations.city_id')
            ->get();

        if ($pairs->isEmpty()) {
            return [];
        }

        $categories = \App\Models\Category::whereIn('id', $pairs->pluck('category_id')->unique())
            ->get()->keyBy('id');
        $cities = \App\Models\City::whereIn('id', $pairs->pluck('city_id')->unique())
            ->get()->keyBy('id');

        $urls = [];

        foreach ($pairs as $pair) {
            $category = $categories->get($pair->category_id);
            $city = $cities->get($pair->city_id);

            if (!$category || !$city) {
                continue;
            }

            // Same gate as the public route: an invalid collection 404s, so it
            // must never appear in the sitemap.
            if (!$this->collections->isValidCollection($category, $city)) {
                continue;
            }

            $urls[] = url('/' . $this->collections->buildSlug($category, $city));
        }

        return $urls;
    }

    /** @param array<int, string> $urls */
    private function render(array $urls): string
    {
        $out = '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
        $out .= '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">' . "\n";

        foreach ($urls as $url) {
            $out .= '  <url><loc>' . htmlspecialchars($url, ENT_XML1 | ENT_QUOTES, 'UTF-8') . '</loc></url>' . "\n";
        }

        $out .= '</urlset>' . "\n";

        return $out;
    }
}
