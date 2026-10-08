<?php

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Http\Resources\ListingDirectoryResource;
use App\Models\Business;
use App\Models\Category;
use App\Models\Listing;
use App\Models\Review;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;
use Inertia\Inertia;

class HomeController extends Controller
{
    public function index()
    {
        // PHASE 11 / WAVE 1D-1 — public discovery is LISTING-based.
        $featuredListings = Listing::with([
            'business:id,name,slug,logo,cover_image',
            'location.country',
            'location.region',
            'location.city',
            'location.hours',
            'location.hourOverrides',   // ✅ for is_open_now override awareness
            'categories',
            'services' => fn($q) => $q->whereNull('hidden_at'),
            'images' => fn($q) => $q->whereNull('hidden_at'),
            'owner:id,name,role',
            'owner.activeSubscription.plan',   // ✅ avoid N+1 on feature_flags
        ])
            ->where('is_featured', true)
            ->where('status', Listing::STATUS_PUBLISHED)
            ->whereNull('hidden_at')
            ->withCount(['reviews', 'images'])
            ->withAvg('reviews', 'rating')
            ->inRandomOrder()
            ->take(6)
            ->get();

        $recentListings = Listing::with([
            'business:id,name,slug,logo,cover_image',
            'location.country',
            'location.region',
            'location.city',
            'location.hours',
            'location.hourOverrides',   // ✅ for is_open_now override awareness
            'categories',
            'services' => fn($q) => $q->whereNull('hidden_at'),
            'images' => fn($q) => $q->whereNull('hidden_at'),
            'owner:id,name,role',
            'owner.activeSubscription.plan',   // ✅ avoid N+1 on feature_flags
        ])
            ->where('status', Listing::STATUS_PUBLISHED)
            ->whereNull('hidden_at')
            ->withCount(['reviews', 'images'])
            ->withAvg('reviews', 'rating')
            ->latest()
            ->take(8)
            ->get();

        // ============== POPULAR CATEGORIES (grid below — top 6) ==============
        $popularCategories = Category::withCount([
            'listings' => function ($q) {
                $q->where('status', Listing::STATUS_PUBLISHED)
                    ->whereNull('listings.hidden_at');
            }
        ])
            ->having('listings_count', '>', 0)
            ->orderBy('listings_count', 'desc')
            ->take(6)
            ->get();

        // ============== STRIP CATEGORIES (top 10, compact icon strip) ==============
        $stripCategories = Category::withCount([
            'listings' => function ($q) {
                $q->where('status', Listing::STATUS_PUBLISHED)
                    ->whereNull('listings.hidden_at');
            }
        ])
            ->having('listings_count', '>', 0)
            ->orderBy('listings_count', 'desc')
            ->take(10)
            ->get(['id', 'name', 'icon', 'slug'])
            ->map(function ($cat) {
                return [
                    'id' => $cat->id,
                    'name' => $cat->name,
                    'icon' => $cat->icon ?: '📁',
                    'slug' => $cat->slug,
                    'listings_count' => $cat->listings_count,
                ];
            });

        // ============== LATEST REVIEWS (cached 5 min) ==============
        $latestReviews = Cache::remember('home.latest_reviews', now()->addMinutes(5), function () {
            // PHASE 21C-R1 — a Review belongs to a LISTING. The publicly
            // reachable entity is the Listing, so the visibility filter applies
            // to it; the Business is optional context reached through it.
            return Review::query()
                ->where('status', 'approved')
                ->whereHas('listing', function ($q) {
                    $q->where('status', Listing::STATUS_PUBLISHED)
                        ->whereNull('hidden_at');
                })
                ->with(['user:id,name', 'listing:id,name,slug,business_id', 'listing.business:id,name,slug'])
                ->latest()
                ->take(6)
                ->get()
                ->map(function ($review) {
                    return [
                        'id' => $review->id,
                        'rating' => (int) $review->rating,
                        'title' => $review->title,
                        'content' => Str::limit($review->content, 180),
                        'reviewer_name' => $review->reviewer_name,
                        'created_at' => $review->created_at->diffForHumans(),
                        // The Listing is the reviewed entity; the Business is
                        // exposed only as optional organization context.
                        'listing' => [
                            'id' => $review->listing?->id,
                            'name' => $review->listing?->name,
                            'slug' => $review->listing?->slug,
                        ],
                        'business' => [
                            'id' => $review->listing?->business?->id,
                            'name' => $review->listing?->business?->name,
                            'slug' => $review->listing?->business?->slug,
                        ],
                    ];
                })
                ->toArray();
        });

        // ============== HERO STATS (cached 1 hour) ==============
        $stats = Cache::remember('home.stats', now()->addHour(), function () {
            return [
                'listings' => Listing::where('status', Listing::STATUS_PUBLISHED)
                    ->whereNull('hidden_at')
                    ->count(),
                'reviews' => Review::where('status', 'approved')->count(),
                'categories' => Category::count(),
            ];
        });

        return Inertia::render('Public/Home', [
            'featuredListings' => ListingDirectoryResource::collection($featuredListings)->resolve(),
            'recentListings' => ListingDirectoryResource::collection($recentListings)->resolve(),
            'popularCategories' => $popularCategories,
            'stripCategories' => $stripCategories,
            'latestReviews' => $latestReviews,
            'stats' => $stats,
        ]);
    }
}