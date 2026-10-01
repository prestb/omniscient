<?php

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Models\Favorite;
use App\Models\Listing;
use Illuminate\Http\Request;
use Inertia\Inertia;

/**
 * PHASE 11 / WAVE 1D-5A — FAVORITES ARE LISTING-OWNED.
 *
 *   User -> Favorite -> Listing
 *
 * `listing_id` is the authoritative key. `business_id` is gone from the schema
 * and from this controller: a Business is never the favorite target, and no
 * Listing is ever resolved through a Business.
 *
 * The heart in BusinessCard.vue already sends the LISTING id, because the
 * directory renders Listings through that card.
 */
class FavoriteController extends Controller
{
    /**
     * Show the user's favorited Listings.
     */
    public function index(Request $request)
    {
        $user = $request->user();

        if (!$user) {
            return redirect()->route('login');
        }

        $favorites = $user->favoriteListings()
            ->with([
                'business:id,name,slug',
                'location.city',
                'location.country',
                'images',
                'categories',
            ])
            ->withCount('reviews')
            ->withAvg('reviews', 'rating')
            ->paginate(12);

        // The existing Favorites UI consumes a ListingDirectoryResource-shaped
        // payload under the `businesses` prop. That prop NAME is 1D-6 naming
        // debt; the DATA is Listings, which is the architectural truth.
        $listings = $favorites->through(function (Listing $listing) {
            return array_merge(
                (new \App\Http\Resources\ListingDirectoryResource($listing))->resolve(),
                ['is_favorited' => true]
            );
        });

        return Inertia::render('Public/Favorites', [
            'businesses' => $listings,
            'totalCount' => $user->favorites()->count(),
        ]);
    }

    /**
     * Toggle a LISTING as favorite (AJAX).
     */
    public function toggle(Request $request, Listing $listing)
    {
        $user = $request->user();

        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'Please log in to save favorites.',
                'requires_auth' => true,
            ], 401);
        }

        // Lookup is keyed by user_id + listing_id — never by Business.
        $existing = Favorite::where('user_id', $user->id)
            ->where('listing_id', $listing->id)
            ->first();

        if ($existing) {
            $existing->delete();
            $isFavorited = false;
            $message = 'Removed from favorites.';
        } else {
            Favorite::create([
                'user_id' => $user->id,
                'listing_id' => $listing->id,
            ]);
            $isFavorited = true;
            $message = 'Added to favorites!';
        }

        return response()->json([
            'success' => true,
            'is_favorited' => $isFavorited,
            'message' => $message,
            'total_favorites' => $user->favorites()->count(),
        ]);
    }

    /**
     * Remove a specific favorited LISTING.
     */
    public function destroy(Request $request, Listing $listing)
    {
        $user = $request->user();

        if (!$user) {
            return back()->with('error', 'Please log in.');
        }

        Favorite::where('user_id', $user->id)
            ->where('listing_id', $listing->id)
            ->delete();

        return back()->with('success', 'Removed from favorites.');
    }
}
