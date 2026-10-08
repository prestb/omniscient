<?php

namespace App\Observers;

use App\Models\Review;
use Illuminate\Support\Facades\Log;

/**
 * PHASE 21C-R1 — the Business rating projection is DERIVED, not owned.
 *
 * This observer used to read `$review->business` and `$review->business_id`,
 * both of which described the retired review-ownership model, so every review
 * create/update/delete raised BadMethodCallException.
 *
 * The Business no longer owns Reviews. Its stored `average_rating` /
 * `total_reviews` columns are now a PROJECTION of the approved reviews belonging
 * to its LISTINGS — the same aggregate `Business::reviews()` computes on demand.
 * The projection is kept because the public frontend still reads those columns,
 * but it carries no ownership semantics.
 *
 * A Business-less Listing has no projection to update, which is correct: its own
 * reputation is read from its own reviews.
 */
class ReviewObserver
{
    public function created(Review $review): void
    {
        $this->syncBusinessRating($review);
    }

    public function updated(Review $review): void
    {
        if ($review->isDirty('status') || $review->isDirty('rating')) {
            $this->syncBusinessRating($review);
        }
    }

    public function deleted(Review $review): void
    {
        $this->syncBusinessRating($review);
    }

    /**
     * Refresh the owning Business's derived rating projection.
     *
     * The Business is resolved THROUGH THE LISTING — the only ownership path.
     * The aggregate spans every Listing the Business owns.
     */
    private function syncBusinessRating(Review $review): void
    {
        // The Listing is the owner. A Business-less Listing has none.
        $listing = $review->listing;

        if (!$listing || !$listing->business_id) {
            // Nothing to project. The Listing's own reputation is derived from
            // its own reviews and needs no stored copy.
            return;
        }

        $business = $listing->business;

        if (!$business) {
            return;
        }

        // Derived across ALL of the Business's Listings.
        $total = $business->reviews()->count();

        if ($total === 0) {
            $business->update([
                'average_rating' => 0,
                'total_reviews' => 0,
            ]);

            return;
        }

        $average = $business->reviews()->avg('reviews.rating');

        $business->update([
            'average_rating' => round((float) $average, 1),
            'total_reviews' => $total,
        ]);

        Log::info('Business rating projection refreshed', [
            'business_id' => $business->id,
            'total_reviews' => $total,
        ]);
    }
}
