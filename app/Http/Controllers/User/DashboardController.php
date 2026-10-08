<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Models\Business;
use App\Models\Review;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;

class DashboardController extends Controller
{
    public function index()
    {
        $user = Auth::user();

        // Redirect owners/admins to their dashboards
        if ($user->hasBusinessAccess()) {
            return redirect()->route($user->isOwner() ? 'owner.dashboard' : 'admin.dashboard');
        }

        // Fetch user's data
        $favoriteCount = $user->favorites()->count();
        $reviewCount = Review::where('user_id', $user->id)->count();
        $unreadNotifications = $user->unreadNotifications()->count();

        // Recent favorites — PHASE 11 / WAVE 1D-5A: favorites are LISTING-owned.
        // Emitted through ListingDirectoryResource so this screen consumes the
        // same Listing shape as the directory and the Favorites page. No
        // Business-shaped compatibility transformer.
        $recentFavorites = $user->favoriteListings()
            ->with([
                'business:id,name,slug',
                'location.city',
                'location.country',
                'location.hours',
                'images',
                'categories',
                'owner.activeSubscription.plan',
            ])
            ->withCount('reviews')
            ->withAvg('reviews', 'rating')
            ->take(6)
            ->get()
            ->map(fn($listing) => (new \App\Http\Resources\ListingDirectoryResource($listing))->resolve())
            ->map(fn(array $data) => array_merge($data, ['is_favorited' => true]));

        // Recent reviews
        // PHASE 21C-R1 - a Review belongs to a LISTING; `business` was removed
        // from the model, so eager-loading it raised BadMethodCallException.
        $recentReviews = Review::where('user_id', $user->id)
            ->with(['listing:id,name,slug'])
            ->latest()
            ->take(5)
            ->get();

        // ✅ Get user's business (if any) — for the pending state card
        $userBusiness = Business::where('owner_id', $user->id)
            ->latest()
            ->first();

        return Inertia::render('User/Dashboard', [
            'user' => $user,
            'stats' => [
                'favorites' => $favoriteCount,
                'reviews' => $reviewCount,
                'notifications' => $unreadNotifications,
            ],
            'recentFavorites' => $recentFavorites,
            'recentReviews' => $recentReviews,
            'userBusiness' => $userBusiness, // ✅ NEW
        ]);
    }
}