<?php

namespace App\Http\Controllers;

use App\Models\Business;
use App\Services\RatingService;
use Illuminate\Http\Request;

class TestRatingController extends Controller
{
    public function check($id)
    {
        $business = Business::with('reviews')->find($id);
        
        $approved = $business->reviews()->where('status', 'approved')->get();
        
        return response()->json([
            'business' => [
                'id' => $business->id,
                'name' => $business->name,
                'average_rating' => $business->average_rating,
                'total_reviews' => $business->total_reviews,
            ],
            'approved_reviews' => $approved->map(function ($review) {
                return [
                    'id' => $review->id,
                    'rating' => $review->rating,
                    'status' => $review->status,
                ];
            }),
            'approved_count' => $approved->count(),
            'average' => $approved->avg('rating'),
        ]);
    }
}