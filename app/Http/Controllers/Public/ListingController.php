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
                'reviews' => fn($q) => $q->where('status', 'approved')
                    ->with('user:id,name')
                    ->latest(),
                'owner:id,name,role',
                'owner.activeSubscription.plan',
            ])
            ->withCount(['businessReviews', 'images'])
            ->withAvg('businessReviews', 'rating')
            ->firstOrFail();

        return Inertia::render('Public/ListingProfile', [
            'listing' => (new ListingDirectoryResource($listing))->resolve(),
            'reviews' => $listing->reviews->map(fn($review) => [
                'id' => $review->id,
                'rating' => $review->rating,
                'content' => $review->content,
                'created_at' => $review->created_at?->toDateString(),
                'user_name' => $review->user?->name,
            ])->values(),
        ]);
    }
}
