<?php

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Models\Business;
use App\Models\Favorite;
use Illuminate\Http\Request;
use Inertia\Inertia;

class FavoriteController extends Controller
{
    /**
     * Show user's favorites page
     */
    public function index(Request $request)
    {
        $user = $request->user();

        if (!$user) {
            return redirect()->route('login');
        }

        $favorites = $user->favoriteBusinesses()
            ->with([
                'categories',
                'primaryBranch',
                'primaryBranch.city',
                'primaryBranch.country',
                'logo',
                'coverImage',
            ])
            ->withCount('reviews')
            ->withAvg('reviews', 'rating')
            ->orderBy('favorites.created_at', 'desc')
            ->paginate(12);

        // Transform for BusinessCard
        $businesses = $favorites->through(function ($business) {
            return array_merge($business->toArray(), [
                'is_favorited' => true,
            ]);
        });

        return Inertia::render('Public/Favorites', [
            'businesses' => $businesses,
            'totalCount' => $user->favorites()->count(),
        ]);
    }

    /**
     * Toggle a business as favorite (AJAX)
     */
    public function toggle(Request $request, Business $business)
    {
        $user = $request->user();

        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'Please log in to save favorites.',
                'requires_auth' => true,
            ], 401);
        }

        $existing = Favorite::where('user_id', $user->id)
            ->where('business_id', $business->id)
            ->first();

        if ($existing) {
            $existing->delete();
            $isFavorited = false;
            $message = 'Removed from favorites.';
        } else {
            Favorite::create([
                'user_id' => $user->id,
                'business_id' => $business->id,
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
     * Remove a specific favorite
     */
    public function destroy(Request $request, Business $business)
    {
        $user = $request->user();

        if (!$user) {
            return back()->with('error', 'Please log in.');
        }

        Favorite::where('user_id', $user->id)
            ->where('business_id', $business->id)
            ->delete();

        return back()->with('success', 'Removed from favorites.');
    }
}