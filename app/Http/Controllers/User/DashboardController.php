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
        $recentFavorites = $user->favoriteListings()
            ->with(['business:id,name,slug', 'location.city', 'images'])
            ->withCount('reviews')
            ->withAvg('reviews', 'rating')
            ->take(6)
            ->get()
            ->map(function ($listing) {
                return array_merge($listing->toArray(), ['is_favorited' => true]);
            });

        // Recent reviews
        $recentReviews = Review::where('user_id', $user->id)
            ->with(['business:id,name,slug'])
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