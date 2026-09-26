<?php

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Http\Resources\BusinessDirectoryResource;
use App\Models\Business;
use App\Models\Category;
use App\Models\City;
use App\Models\Country;
use App\Models\Region;
use App\Services\SearchIntentParser;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Carbon\Carbon;

class DirectoryController extends Controller
{
    public function index(Request $request)
    {
        // ============== ✅ RESTORE FILTERS FROM COOKIE ==============
        // If the request is truly bare (no filter params, ignoring `page`),
        // and we have stored filters / view mode in cookies, redirect to the
        // canonical filtered URL. One request, one render, no flash.
        //
        // Guard against loops: the redirect itself carries params, so the
        // second request falls through to the normal query path below.
        $filterParams = $request->except(['page']);
        if (empty($filterParams)) {
            $redirectParams = [];

            // Filters cookie (JSON-encoded object)
            $filtersCookie = $request->cookie('directory_filters_v1');
            if ($filtersCookie) {
                $decoded = json_decode($filtersCookie, true);
                if (is_array($decoded)) {
                    foreach ($decoded as $k => $v) {
                        if ($v === null || $v === '' || $v === false || $v === 0 || $v === '0') {
                            continue;
                        }
                        $redirectParams[$k] = $v;
                    }
                }
            }

            // View mode cookie — only `map` matters; `list` is the default
            $viewCookie = $request->cookie('directory_view_mode_v1');
            if ($viewCookie === 'map') {
                $redirectParams['view'] = 'map';
            }

            if (!empty($redirectParams)) {
                return redirect()->route('directory', $redirectParams, 302);
            }
        }

        // ============== ✅ PARSE "X in Y" INTENT ==============
        // If the user typed a category + city pattern into the search box,
        // extract them. Merge-only: fills null slots, never overrides.
        $intent = app(SearchIntentParser::class)->parse($request->input('search'));
        $parsedCategoryId = $request->input('category') ?: $intent['category_id'];
        $parsedCityId = $request->input('city_id') ?: $intent['city_id'];
        $parsedSearch = $intent['cleaned_query'] !== null
            ? $intent['cleaned_query']
            : $request->input('search');

        $query = Business::query()
            ->with([
                'categories',
                'primaryBranch',
                'branches' => function ($q) {
                    $q->whereNull('hidden_at');   // ✅ skip hidden branches
                },
                'branches.city',
                'branches.region',
                'branches.country',
                'branches.hours',
                'branches.hourOverrides',   // ✅ for is_open_now override awareness
                'logo',
                'coverImage',
                'galleryImages' => function ($q) {
                    $q->whereNull('hidden_at');   // ✅ skip hidden gallery images
                },
                'owner:id,name,role',
                'owner.activeSubscription.plan',   // ✅ avoid N+1 on feature_flags per owner
            ])
            ->withCount('galleryImages')
            ->withCount('reviews')                    // ✅ always load for the accessor
            ->withAvg('reviews', 'rating')            // ✅ always load for the accessor
            ->where('status', 'published')
            ->whereNull('hidden_at');   // ✅ skip hidden businesses

        // ============== SEARCH (post-parse) ==============
        if (!empty($parsedSearch)) {
            $search = $parsedSearch;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('description', 'like', "%{$search}%")
                    ->orWhereHas('services', function ($sq) use ($search) {
                        $sq->where('name', 'like', "%{$search}%");
                    });
            });
        }

        // ============== CATEGORY (post-parse) ==============
        if (!empty($parsedCategoryId)) {
            $query->whereHas('categories', function ($q) use ($parsedCategoryId) {
                $q->where('categories.id', $parsedCategoryId);
            });
        }

        // ============== LOCATION ==============
        if ($request->filled('country_id')) {
            $query->whereHas('branches', function ($q) use ($request) {
                $q->where('country_id', $request->country_id);
            });
        }

        if ($request->filled('region_id')) {
            $query->whereHas('branches', function ($q) use ($request) {
                $q->where('region_id', $request->region_id);
            });
        }

        if (!empty($parsedCityId)) {
            $query->whereHas('branches', function ($q) use ($parsedCityId) {
                $q->where('city_id', $parsedCityId);
            });
        }

        // ============== OPEN NOW (override-aware) ==============
        if ($request->filled('open_now') && $request->open_now == 'true') {
            $currentDayOfWeek = now()->dayOfWeek;
            $currentTime = now()->format('H:i:s');
            $today = today()->toDateString();

            $query->whereHas('branches', function ($q) use ($currentDayOfWeek, $currentTime, $today) {
                // Case A: branch has NO closed override today AND weekly hours say open
                $q->where(function ($sub) use ($currentDayOfWeek, $currentTime, $today) {
                    // No closed override today
                    $sub->whereDoesntHave('hourOverrides', function ($o) use ($today) {
                        $o->whereDate('date', $today)->where('is_closed', true);
                    });

                    // No special-hours override today
                    $sub->whereDoesntHave('hourOverrides', function ($o) use ($today) {
                        $o->whereDate('date', $today)
                            ->where('is_closed', false)
                            ->whereNotNull('opens_at')
                            ->whereNotNull('closes_at');
                    });

                    // Weekly hours say open
                    $sub->whereHas('hours', function ($h) use ($currentDayOfWeek, $currentTime) {
                        $h->where('day_of_week', $currentDayOfWeek)
                            ->where('is_closed', false)
                            ->where(function ($query) use ($currentTime) {
                                $query->where('is_24h', true)
                                    ->orWhere(function ($q) use ($currentTime) {
                                        $q->whereTime('opens_at', '<=', $currentTime)
                                            ->whereTime('closes_at', '>=', $currentTime);
                                    })
                                    ->orWhere(function ($q) use ($currentTime) {
                                        $q->whereTime('opens_at', '>', 'closes_at')
                                            ->where(function ($sub) use ($currentTime) {
                                                $sub->whereTime('opens_at', '<=', $currentTime)
                                                    ->orWhereTime('closes_at', '>=', $currentTime);
                                            });
                                    });
                            });
                    });
                });

                // Case B: OR branch has special hours today and current time falls inside
                $q->orWhereHas('hourOverrides', function ($o) use ($today, $currentTime) {
                    $o->whereDate('date', $today)
                        ->where('is_closed', false)
                        ->whereNotNull('opens_at')
                        ->whereNotNull('closes_at')
                        ->where(function ($t) use ($currentTime) {
                            // Same-day window
                            $t->where(function ($w) use ($currentTime) {
                                $w->whereTime('opens_at', '<=', $currentTime)
                                    ->whereTime('closes_at', '>=', $currentTime)
                                    ->whereColumn('opens_at', '<=', 'closes_at');
                            })
                                // Overnight window
                                ->orWhere(function ($w) use ($currentTime) {
                                $w->whereColumn('opens_at', '>', 'closes_at')
                                    ->where(function ($sub) use ($currentTime) {
                                        $sub->whereTime('opens_at', '<=', $currentTime)
                                            ->orWhereTime('closes_at', '>=', $currentTime);
                                    });
                            });
                        });
                });
            });
        }

        // ============== FEATURED ONLY ==============
        if ($request->filled('featured') && $request->featured == 'true') {
            $query->where('is_featured', true);
        }

        // ============== VERIFIED ONLY ==============
        // verified_badge is plan-derived — filter runs post-pagination on the collection.

        // ============== MIN RATING ==============
        if ($request->filled('min_rating')) {
            $minRating = (float) $request->min_rating;
            $query->whereRaw(
                '(SELECT COALESCE(AVG(rating), 0) FROM reviews WHERE reviews.business_id = businesses.id AND reviews.status = ?) >= ?',
                ['approved', $minRating]
            );
        }

        // ============== HAS PHOTOS (gallery only) ==============
        if ($request->filled('has_photos') && $request->has_photos == 'true') {
            $query->whereHas('galleryImages');
        }

        // ============== HAS WHATSAPP ==============
        if ($request->filled('has_whatsapp') && $request->has_whatsapp == 'true') {
            $query->whereHas('branches', function ($q) {
                $q->whereNotNull('whatsapp')
                    ->where('whatsapp', '!=', '');
            });
        }

        // ============== SORT ==============
        $sort = $request->input('sort', 'newest');

        switch ($sort) {
            case 'rating':
                $query->withAvg('reviews', 'rating')
                    ->orderByDesc('reviews_avg_rating');
                break;
            case 'reviews':
                $query->withCount('reviews')
                    ->orderByDesc('reviews_count');
                break;
            case 'name':
                $query->orderBy('name', 'asc');
                break;
            case 'newest':
            default:
                $query->orderByDesc('is_featured')
                    ->latest('published_at');
                break;
        }

        // ============== VIEW MODE ==============
        $viewMode = $request->input('view', 'list');

        // ============== APPLY VERIFIED FILTER (post-query) ==============
        // (Runs on the collection for both list and map.)
        if ($request->filled('verified') && $request->verified == 'true') {
            // For the LIST: filter the paginated collection
            // For the MAP: we need to fetch everything first, so handle below
        }

        if ($viewMode === 'map') {
            // MAP MODE — fetch all matching businesses with coordinates,
            // no pagination, minimal payload.
            $allBusinesses = $query->get();

            // Post-filter: verified
            if ($request->filled('verified') && $request->verified == 'true') {
                $allBusinesses = $allBusinesses->filter(function ($business) {
                    return $business->hasVerifiedBadgeFeature();
                })->values();
            }

            // Post-filter: only businesses with at least one coordinate-bearing branch
            $mapBusinesses = $allBusinesses
                ->filter(function ($business) {
                    return $business->branches->contains(function ($branch) {
                        return $branch->latitude !== null && $branch->longitude !== null;
                    });
                })
                ->values()
                ->map(function ($business) {
                    return $this->mapPinPayload($business);
                });

            $businesses = [
                'data' => $mapBusinesses,
                'total' => $mapBusinesses->count(),
                // No pagination metadata for map mode
                'from' => null,
                'to' => null,
                'links' => [],
            ];
        } else {
            // LIST MODE — paginate as before
            $businesses = $query->paginate(12)->withQueryString();

            if ($request->filled('verified') && $request->verified == 'true') {
                $businesses->setCollection(
                    $businesses->getCollection()->filter(function ($business) {
                        return $business->hasVerifiedBadgeFeature();
                    })->values()
                );
            }

            // ============== FAVORITES (list mode only) ==============
            $userFavoriteIds = [];
            if (auth()->check()) {
                $userFavoriteIds = \App\Models\Favorite::where('user_id', auth()->id())
                    ->pluck('business_id')
                    ->toArray();
            }

            $businesses->getCollection()->transform(function ($business) use ($userFavoriteIds) {
                $resource = (new BusinessDirectoryResource($business))->resolve();
                $resource['is_favorited'] = in_array($business->id, $userFavoriteIds);
                return $resource;
            });
        }

        // ============== FILTER DATA ==============
        $countries = Country::active()->get();
        $categories = Category::active()->root()->ordered()->get();

        return Inertia::render('Public/Directory', [
            'businesses' => $businesses,
            'countries' => $countries,
            'categories' => $categories,
            'filters' => array_merge(
                $request->only([
                    'search',
                    'category',
                    'country_id',
                    'region_id',
                    'city_id',
                    'open_now',
                    'featured',
                    'verified',
                    'min_rating',
                    'has_photos',
                    'has_whatsapp',
                    'sort',
                    'view',
                ]),
                [
                    // ✅ Reflect parsed intent in the frontend filter state
                    'category' => $parsedCategoryId ?: null,
                    'city_id' => $parsedCityId ?: null,
                    'search' => $parsedSearch ?: null,
                ]
            ),
            'viewMode' => $viewMode,
        ]);
    }

    /**
     * Build the minimal payload for a map pin.
     */
    private function mapPinPayload($business): array
    {
        // Use the primary branch's coordinates if available,
        // otherwise the first branch that has them.
        $primary = $business->branches->firstWhere('is_primary', true)
            ?? $business->branches->first();

        $branchWithCoords = null;
        if ($primary && $primary->latitude !== null && $primary->longitude !== null) {
            $branchWithCoords = $primary;
        } else {
            $branchWithCoords = $business->branches->first(function ($branch) {
                return $branch->latitude !== null && $branch->longitude !== null;
            });
        }

        return [
            'id' => $business->id,
            'name' => $business->name,
            'slug' => $business->slug,
            'latitude' => (float) $branchWithCoords->latitude,
            'longitude' => (float) $branchWithCoords->longitude,
            'category' => $business->categories->first()?->name ?? 'Uncategorized',
            'rating' => round((float) $business->average_rating, 1),
            'reviews_count' => $business->total_reviews,
            'cover_image_url' => $business->cover_image_url,
            'logo_url' => $business->logo_url,
            'is_featured' => (bool) $business->is_featured,
            'is_verified' => $business->hasVerifiedBadgeFeature(),
            'address' => $branchWithCoords->full_address,
        ];

    }
    public function show($slug)
    {
        $business = Business::query()
            ->with([
                'categories',
                'branches' => function ($query) {
                    $query->whereNull('hidden_at')   // ✅ skip hidden branches
                        ->with(['country', 'region', 'city', 'area', 'hours']);
                },
                'branches.hours',
                'services' => function ($query) {
                    $query->whereNull('hidden_at');  // ✅ skip hidden services
                },
                'contacts',
                'logo',
                'coverImage',
                'galleryImages' => function ($query) {
                    $query->whereNull('hidden_at');  // ✅ skip hidden images
                },
                'owner',
                'owner.activeSubscription.plan',   // ✅ avoid N+1 on feature_flags
                'branches.hourOverrides',           // ✅ needed for is_open_now
                'reviews' => function ($query) {
                    $query->where('status', 'approved')
                        ->with(['user', 'replies.user'])
                        ->latest()
                        ->take(10);
                },
            ])
            ->where('slug', $slug)
            ->where('status', 'published')
            ->whereNull('hidden_at')   // ✅ 404 if business is hidden
            ->withCount('reviews')
            ->withAvg('reviews', 'rating')
            ->firstOrFail();


        // ✅ Round the withAvg result so `business.reviews_avg_rating` matches
        //    the rounded `average_rating` accessor.
        if (isset($business->reviews_avg_rating)) {
            $business->reviews_avg_rating = $business->reviews_avg_rating !== null
                ? round((float) $business->reviews_avg_rating, 1)
                : 0;
        }

        // ✅ Override-aware state — delegate entirely to Branch accessors
        $business->branches->each(function ($branch) {
            $override = $branch->today_override;
            $special = $branch->today_special_hours;

            $branch->has_override_today = $override !== null;
            $branch->is_special_hours = $special !== null;
            $branch->override_note = $override?->note;
            $branch->override_opens_at = $special?->opens_at?->format('H:i');
            $branch->override_closes_at = $special?->closes_at?->format('H:i');
            $branch->is_open_now = $branch->is_open_now; // triggers accessor
        });

        // ✅ Calculate overall open status
        $isOpen = $business->branches->contains('is_open_now', true);

        // Check if business has active subscription
        $owner = $business->owner;
        $hasActiveSubscription = $owner && $owner->active_subscription !== null;

        if (!$hasActiveSubscription) {
            abort(404);
        }
        // Get primary branch
        $primaryBranch = $business->branches->firstWhere('is_primary', true) ?? $business->branches->first();

        // Get rating breakdown
        $ratingBreakdown = [
            5 => 0,
            4 => 0,
            3 => 0,
            2 => 0,
            1 => 0,
        ];

        $ratings = $business->reviews()
            ->where('status', 'approved')
            ->select('rating', \DB::raw('count(*) as count'))
            ->groupBy('rating')
            ->get();

        foreach ($ratings as $r) {
            $ratingBreakdown[$r->rating] = $r->count;
        }


        // ✅ Debug: Check if coupons query returns data
        $coupons = \App\Models\Coupon::where('business_id', $business->id)
            ->whereNull('hidden_at')   // ✅ skip hidden coupons
            ->where('is_active', true)
            ->where(function ($q) {
                $q->whereNull('expires_at')
                    ->orWhere('expires_at', '>', now());
            })
            ->where(function ($q) {
                $q->whereNull('starts_at')
                    ->orWhere('starts_at', '<=', now());
            })
            ->where(function ($q) {
                $q->whereNull('usage_limit')
                    ->orWhereColumn('usage_count', '<', 'usage_limit');
            })
            ->latest()
            ->get()
            ->map(function ($coupon) {
                return [
                    'id' => $coupon->id,
                    'title' => $coupon->title,
                    'description' => $coupon->description,
                    'code' => $coupon->code,
                    'discount_type' => $coupon->discount_type,
                    'discount_value' => (float) $coupon->discount_value,
                    'min_purchase' => $coupon->min_purchase ? (float) $coupon->min_purchase : null,
                    'max_discount' => $coupon->max_discount ? (float) $coupon->max_discount : null,
                    'usage_limit' => $coupon->usage_limit,
                    'usage_count' => $coupon->usage_count,
                    'expires_at' => $coupon->expires_at?->toIso8601String(),
                ];
            });

        // 🐛 DEBUG: Log what we found
        \Log::info('Business Profile - Coupons loaded', [
            'business_id' => $business->id,
            'business_name' => $business->name,
            'coupon_count' => $coupons->count(),
            'coupons' => $coupons->toArray(),
        ]);


        $businessData = $business->toArray();
        $businessData['feature_flags'] = $business->feature_flags;
        $businessData['is_favorited'] = auth()->check() && $business->isFavoritedBy(auth()->user());

        // ✅ Map integration — expose coordinates for the profile map
        $businessData['coordinates'] = $this->resolveBusinessCoordinates($business);

        // ✅ Related businesses — tiered (same category + same city → same category → same city)
        $relatedBusinesses = $this->resolveRelatedBusinesses($business);

        return Inertia::render('Public/BusinessProfile', [
            'business' => $business,
            'coupons' => $coupons,
            'primaryBranch' => $primaryBranch,
            'isOpen' => $primaryBranch ? $primaryBranch->is_open_now : false,
            'ratingBreakdown' => $ratingBreakdown,
            'coordinates' => $businessData['coordinates'],   // ✅ top-level for convenience
            'relatedBusinesses' => $relatedBusinesses,
        ]);
    }

    public function categories()
    {
        $categories = Category::active()
            ->with([
                'children' => function ($query) {
                    $query->active()->ordered();
                }
            ])
            ->root()
            ->ordered()
            ->get();

        return Inertia::render('Public/Categories', [
            'categories' => $categories,
        ]);
    }

    public function locations()
    {
        $countries = Country::active()
            ->with([
                'regions' => function ($query) {
                    $query->active()->with([
                        'cities' => function ($query) {
                            $query->active();
                        }
                    ]);
                }
            ])
            ->get();

        return Inertia::render('Public/Locations', [
            'countries' => $countries,
        ]);
    }

    public function getRegions(Request $request)
    {
        $regions = Region::active()
            ->where('country_id', $request->country_id)
            ->get(['id', 'name']);

        return response()->json($regions);
    }

    public function getCities(Request $request)
    {
        $cities = City::active()
            ->where('region_id', $request->region_id)
            ->get(['id', 'name']);

        return response()->json($cities);
    }

    // ============== BRANCH HELPERS ==============

    /**
     * Check if a branch is currently open
     */
    private function isBranchOpen($branch)
    {
        if (!$branch) {
            return false;
        }

        // If branch has no hours, assume closed
        if (!$branch->hours || $branch->hours->isEmpty()) {
            return false;
        }

        $currentDay = now()->dayOfWeek; // 0=Sunday, 1=Monday, etc.
        $currentTime = now()->format('H:i:s');

        // Find hours for today
        $todayHours = $branch->hours->firstWhere('day_of_week', $currentDay);

        // If no hours for today or closed
        if (!$todayHours || $todayHours->is_closed) {
            return false;
        }

        // If 24 hours
        if ($todayHours->is_24h) {
            return true;
        }

        $opens = $todayHours->opens_at;
        $closes = $todayHours->closes_at;

        // If no open/close times set
        if (!$opens || !$closes) {
            return false;
        }

        // Handle overnight hours (e.g., 22:00 - 02:00)
        if ($closes < $opens) {
            return $currentTime >= $opens || $currentTime <= $closes;
        }

        return $currentTime >= $opens && $currentTime <= $closes;
    }


    /**
     * Return [lat, lng, branch_id, address] for the primary branch,
     * or the first branch that has coordinates, or null.
     */
    private function resolveBusinessCoordinates($business): ?array
    {
        $primary = $business->branches->firstWhere('is_primary', true)
            ?? $business->branches->first();

        $candidates = collect([$primary])
            ->merge($business->branches)
            ->filter()
            ->unique('id');

        foreach ($candidates as $branch) {
            if ($branch->latitude !== null && $branch->longitude !== null) {
                return [
                    'latitude' => (float) $branch->latitude,
                    'longitude' => (float) $branch->longitude,
                    'branch_id' => $branch->id,
                    'branch_name' => $branch->name,
                    'full_address' => $branch->full_address,
                ];
            }
        }

        return null;
    }

    /**
     * Get branch address
     */
    private function getBranchAddress($branch)
    {
        if (!$branch) {
            return 'Address not set';
        }

        $parts = [];
        if ($branch->address) {
            $parts[] = $branch->address;
        }
        if ($branch->city && $branch->city->name) {
            $parts[] = $branch->city->name;
        }
        if ($branch->region && $branch->region->name) {
            $parts[] = $branch->region->name;
        }
        if ($branch->country && $branch->country->name) {
            $parts[] = $branch->country->name;
        }

        return !empty($parts) ? implode(', ', $parts) : 'Address not set';
    }

        /**
     * ✅ Tiered related-business resolver.
     *
     *    Tier 1: same primary category + same primary-branch city
     *    Tier 2: same primary category, any city
     *    Tier 3: same city, any category
     *
     *    Excludes the current business, hidden, and unpublished.
     *    Caps at 8. Prioritizes verified → featured → newest.
     */
    private function resolveRelatedBusinesses(Business $business, int $limit = 8): array
    {
        $categoryId = $business->categories->firstWhere('pivot.is_primary', true)?->id
            ?? $business->categories->first()?->id;

        $cityId = $business->branches->firstWhere('is_primary', true)?->city_id
            ?? $business->branches->first()?->city_id;

        if (!$categoryId && !$cityId) {
            return [];
        }

        $baseQuery = Business::query()
            ->with([
                'categories',
                'primaryBranch',
                'branches' => fn($q) => $q->whereNull('hidden_at'),
                'branches.city',
                'branches.region',
                'branches.country',
                'branches.hours',
                'branches.hourOverrides',
                'logo',
                'coverImage',
                'galleryImages' => fn($q) => $q->whereNull('hidden_at'),
                'owner:id,name,role',
                'owner.activeSubscription.plan',
            ])
            ->where('id', '!=', $business->id)
            ->where('status', 'published')
            ->whereNull('hidden_at')
            ->withCount(['reviews', 'galleryImages'])
            ->withAvg('reviews', 'rating');

        $collected = collect();

        // Tier 1 — same category + same city
        if ($categoryId && $cityId) {
            $tier1 = (clone $baseQuery)
                ->whereHas('categories', fn($q) => $q->where('categories.id', $categoryId))
                ->whereHas('branches', fn($q) => $q->where('city_id', $cityId))
                ->orderByDesc('is_featured')
                ->latest('published_at')
                ->take($limit)
                ->get();

            $collected = $collected->merge($tier1);
        }

        // Tier 2 — same category, any city
        if ($categoryId && $collected->count() < 4) {
            $tier2 = (clone $baseQuery)
                ->whereNotIn('id', $collected->pluck('id'))
                ->whereHas('categories', fn($q) => $q->where('categories.id', $categoryId))
                ->orderByDesc('is_featured')
                ->latest('published_at')
                ->take($limit - $collected->count())
                ->get();

            $collected = $collected->merge($tier2);
        }

        // Tier 3 — same city, any category
        if ($cityId && $collected->count() < 4) {
            $tier3 = (clone $baseQuery)
                ->whereNotIn('id', $collected->pluck('id'))
                ->whereHas('branches', fn($q) => $q->where('city_id', $cityId))
                ->orderByDesc('is_featured')
                ->latest('published_at')
                ->take($limit - $collected->count())
                ->get();

            $collected = $collected->merge($tier3);
        }

        // Trim to limit and transform via resource
        return BusinessDirectoryResource::collection(
            $collected->take($limit)->values()
        )->resolve();
    }
    
}