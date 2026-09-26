<?php

namespace App\Services;

use App\Models\Business;
use Illuminate\Support\Facades\Log;

class RatingService
{
    public static function updateBusinessRating($businessId)
    {
        $business = Business::find($businessId);
        
        if (!$business) {
            Log::warning('Business not found for rating update', ['business_id' => $businessId]);
            return false;
        }

        // Force refresh to get latest data
        $business->refresh();
        
        // Get all approved reviews - using a fresh query
        $approvedReviews = $business->reviews()
            ->where('status', 'approved')
            ->get();
        
        $total = $approvedReviews->count();
        
        Log::info('Calculating rating for business', [
            'business_id' => $businessId,
            'total_approved_reviews' => $total,
            'review_ids' => $approvedReviews->pluck('id')->toArray()
        ]);

        if ($total === 0) {
            $business->update([
                'average_rating' => 0,
                'total_reviews' => 0,
            ]);
            Log::info('Business rating reset to 0', ['business_id' => $businessId]);
            return true;
        }

        $average = $approvedReviews->avg('rating');
        
        $business->update([
            'average_rating' => round($average, 1),
            'total_reviews' => $total,
        ]);

        Log::info('Business rating updated', [
            'business_id' => $businessId,
            'average_rating' => $business->average_rating,
            'total_reviews' => $business->total_reviews,
        ]);

        return true;
    }

    public static function recalculateAll()
    {
        $businesses = Business::all();
        $count = 0;
        
        foreach ($businesses as $business) {
            self::updateBusinessRating($business->id);
            $count++;
        }
        
        Log::info('Recalculated all business ratings', ['count' => $count]);
        return $count;
    }
}