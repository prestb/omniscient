<?php

namespace App\Observers;

use App\Models\Review;
use Illuminate\Support\Facades\Log;

class ReviewObserver
{
    /**
     * Handle the Review "created" event.
     */
    public function created(Review $review): void
    {
        Log::info('Review created, updating rating for business: ' . $review->business_id);
        $this->updateBusinessRating($review);
    }

    /**
     * Handle the Review "updated" event.
     */
    public function updated(Review $review): void
    {
        // Only update if status changed or rating changed
        if ($review->isDirty('status') || $review->isDirty('rating')) {
            Log::info('Review updated, updating rating for business: ' . $review->business_id);
            $this->updateBusinessRating($review);
        }
    }

    /**
     * Handle the Review "deleted" event.
     */
    public function deleted(Review $review): void
    {
        Log::info('Review deleted, updating rating for business: ' . $review->business_id);
        $this->updateBusinessRating($review);
    }

    /**
     * Update the business rating after review changes.
     */
    private function updateBusinessRating(Review $review): void
    {
        $business = $review->business;
        
        if (!$business) {
            Log::warning('Business not found for review: ' . $review->id);
            return;
        }

        // Calculate approved reviews count and average
        $total = $business->reviews()->where('status', 'approved')->count();
        
        Log::info('Calculating rating for business: ' . $business->id, [
            'total_reviews' => $total,
        ]);

        if ($total === 0) {
            $business->update([
                'average_rating' => 0,
                'total_reviews' => 0,
            ]);
            Log::info('Business rating reset to 0', ['business_id' => $business->id]);
            return;
        }

        $average = $business->reviews()->where('status', 'approved')->avg('rating');
        
        $business->update([
            'average_rating' => round($average, 1),
            'total_reviews' => $total,
        ]);

        Log::info('Business rating updated', [
            'business_id' => $business->id,
            'average_rating' => $business->average_rating,
            'total_reviews' => $business->total_reviews,
        ]);
    }
}