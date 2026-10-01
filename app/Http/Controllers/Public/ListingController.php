<?php

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Http\Resources\ListingDirectoryResource;
use App\Models\Listing;
use Inertia\Inertia;

/**
 * PHASE 11 / WAVE 1D-2 — the canonical public Listing page.
 *
 * GET /listing/{slug} resolves a LISTING directly by its own slug. It is not a
 * Business page and does not go through any Business bridge.
 *
 * A Listing with `location_id = NULL` is valid (Invariant D): every
 * location-specific accessor below is null-safe.
 */
class ListingController extends Controller
{
    public function show(string $slug)
    {
        $listing = Listing::query()
            ->where('slug', $slug)
            // Public visibility follows the existing status authority.
            ->where('status', Listing::STATUS_PUBLISHED)
            ->whereNull('hidden_at')
            ->with([
                'business:id,name,slug,logo,cover_image,description',
                'location.city',
                'location.region',
                'location.country',
                'location.hours',
                'location.hourOverrides',
                'categories',
                'services' => fn($q) => $q->whereNull('hidden_at'),
                'images' => fn($q) => $q->whereNull('hidden_at'),
                'contacts',
                // PHASE 11 — Reviews are BUSINESS-owned. This page does not load a
                // Listing review collection: no such collection exists. The
                // Listing's displayed rating/count come from the owning Business's
                // aggregate via businessReviews() below.
                'owner:id,name,role',
                'owner.activeSubscription.plan',
            ])
            ->withCount(['businessReviews', 'images'])
            ->withAvg('businessReviews', 'rating')
            ->firstOrFail();

        return Inertia::render('Public/ListingProfile', [
            'listing' => (new ListingDirectoryResource($listing))->resolve(),
            'seo' => $this->seoFor($listing),
        ]);
    }

    /**
     * PHASE 15B — deterministic metadata from EXISTING Listing data.
     * No fabricated copy: the description is the owner's own text, or a
     * composed fallback from real fields (name, category, city).
     * The canonical is built with url(), so the domain comes from APP_URL.
     */
    private function seoFor(Listing $listing): array
    {
        $description = trim((string) $listing->description);

        if ($description === '') {
            $description = trim(implode(' ', array_filter([
                $listing->name,
                $listing->categories->isNotEmpty() ? 'in ' . $listing->categories->pluck('name')->take(3)->implode(', ') : null,
                $listing->location?->city?->name ? 'located in ' . $listing->location->city->name : null,
            ])));
        }

        if (mb_strlen($description) > 155) {
            $description = rtrim(mb_substr($description, 0, 152)) . '...';
        }

        $image = $listing->images->firstWhere('type', 'cover')?->url
            ?? $listing->images->firstWhere('type', 'logo')?->url
            ?? $listing->business?->cover_image_url;

        return [
            'title' => $listing->name . ' - Omniscient',
            'description' => $description,
            'canonical' => url('/listing/' . $listing->slug),
            'type' => 'website',
            'image' => $image ?: null,
        ];
    }
}
