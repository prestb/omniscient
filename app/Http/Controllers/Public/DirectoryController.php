<?php

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Http\Resources\ListingDirectoryResource;
use App\Models\Business;
use App\Models\Category;
use App\Models\City;
use App\Models\Country;
use App\Models\Listing;
use App\Support\DiscoverySort;
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

        // PHASE 11 / WAVE 1D-1 — public discovery queries LISTINGS.
        // A Business is an organization; it is never the discoverable result.
        $query = Listing::query()
            ->with([
                'business:id,name,slug,logo,cover_image',
                'location.city',
                'location.region',
                'location.country',
                'location.hours',
                'location.hourOverrides',   // ✅ for is_open_now override awareness
                'categories',
                'services' => fn($q) => $q->whereNull('hidden_at'),
                'images' => fn($q) => $q->whereNull('hidden_at'),
                'owner:id,name,role',
                'owner.activeSubscription.plan',   // ✅ avoid N+1 on feature_flags per owner
            ])
            ->withCount(['images', 'businessReviews'])        // ✅ always load for the accessors
            ->withAvg('businessReviews', 'rating')            // ✅ always load for the accessors
            ->where('status', 'published')
            ->whereNull('hidden_at');   // ✅ skip hidden listings

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
        // PHASE 11 / WAVE 1D-1 — a Listing owns its discovery taxonomy.
        if (!empty($parsedCategoryId)) {
            $query->whereHas('categories', fn($q) => $q->where('categories.id', (int) $parsedCategoryId));
        }

        // ============== LOCATION (0 or 1 physical place) ==============
        if ($request->filled('country_id')) {
            $query->whereHas('location', function ($q) use ($request) {
                $q->where('country_id', $request->country_id);
            });
        }

        if ($request->filled('region_id')) {
            $query->whereHas('location', function ($q) use ($request) {
                $q->where('region_id', $request->region_id);
            });
        }

        if (!empty($parsedCityId)) {
            $query->whereHas('location', function ($q) use ($parsedCityId) {
                $q->where('city_id', $parsedCityId);
            });
        }

        // ============== OPEN NOW (override-aware) ==============
        if ($request->filled('open_now') && $request->open_now == 'true') {
            $currentDayOfWeek = now()->dayOfWeek;
            $currentTime = now()->format('H:i:s');
            $today = today()->toDateString();

            $query->whereHas('location', function ($q) use ($currentDayOfWeek, $currentTime, $today) {
                // Case A: the place has NO closed override today AND weekly hours say open
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
            // PHASE 11 — Reviews are BUSINESS-owned, so the rating filter matches
            // against the LISTING's OWNING BUSINESS aggregate. `reviews.listing_id`
            // no longer exists; joining on `business_id` is the canonical path.
            $query->whereRaw(
                '(SELECT COALESCE(AVG(rating), 0) FROM reviews WHERE reviews.business_id = listings.business_id AND reviews.status = ?) >= ?',
                ['approved', $minRating]
            );
        }

        // ============== HAS PHOTOS (gallery only) ==============
        if ($request->filled('has_photos') && $request->has_photos == 'true') {
            $query->whereHas('images');
        }

        // ============== HAS WHATSAPP ==============
        if ($request->filled('has_whatsapp') && $request->has_whatsapp == 'true') {
            $query->whereHas('location', function ($q) {
                $q->whereNotNull('whatsapp')
                    ->where('whatsapp', '!=', '');
            });
        }

        // ============== SORT ==============
        $sort = DiscoverySort::normalize($request->input('sort', 'newest'));

        switch ($sort) {
            case DiscoverySort::RATING:
                $query->withAvg('businessReviews', 'rating')
                    ->orderByDesc('business_reviews_avg_rating');
                break;
            case DiscoverySort::REVIEWS:
                $query->withCount('businessReviews')
                    ->orderByDesc('business_reviews_count');
                break;
            case 'name':
                $query->orderBy('name', 'asc');
                break;
            case DiscoverySort::NEWEST:
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
            // MAP MODE — fetch all matching listings with coordinates,
            // no pagination, minimal payload.
            $allListings = $query->get();

            // Post-filter: verified
            if ($request->filled('verified') && $request->verified == 'true') {
                $allListings = $allListings->filter(function ($listing) {
                    return (bool) $listing->owner?->canUse('verified_badge');
                })->values();
            }

            // Post-filter: only listings whose single Location carries coordinates
            $mapListings = $allListings
                ->filter(function ($listing) {
                    return $listing->location !== null
                        && $listing->location->latitude !== null
                        && $listing->location->longitude !== null;
                })
                ->values()
                ->map(function ($listing) {
                    return $this->mapPinPayload($listing);
                });

            $listings = [
                'data' => $mapListings,
                'total' => $mapListings->count(),
                // No pagination metadata for map mode
                'from' => null,
                'to' => null,
                'links' => [],
            ];
        } else {
            // LIST MODE — paginate
            $listings = $query->paginate(12)->withQueryString();

            if ($request->filled('verified') && $request->verified == 'true') {
                $listings->setCollection(
                    $listings->getCollection()->filter(function ($listing) {
                        return (bool) $listing->owner?->canUse('verified_badge');
                    })->values()
                );
            }

            // ============== FAVORITES (list mode only) ==============
            $userFavoriteIds = [];
            if (auth()->check()) {
                $userFavoriteIds = \App\Models\Favorite::where('user_id', auth()->id())
                    ->pluck('listing_id')
                    ->toArray();
            }

            $listings->getCollection()->transform(function ($listing) use ($userFavoriteIds) {
                $resource = (new ListingDirectoryResource($listing))->resolve();
                $resource['is_favorited'] = in_array($listing->id, $userFavoriteIds);
                return $resource;
            });
        }

        // ============== FILTER DATA ==============
        $countries = Country::active()->get();
        $categories = Category::active()->root()->ordered()->get();

        return Inertia::render('Public/Directory', [
            'listings' => $listings,
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
    private function mapPinPayload($listing): array
    {
        // PHASE 11 / WAVE 1D-1 — a Listing has ZERO OR ONE Location.
        $location = $listing->location;

        $cover = $listing->images->firstWhere('type', \App\Models\ListingImage::TYPE_COVER);
        $logo = $listing->images->firstWhere('type', \App\Models\ListingImage::TYPE_LOGO);

        return [
            'id' => $listing->id,
            'type' => 'listing',
            'listing_type' => $listing->getListingType()->value,
            'name' => $listing->name,
            'slug' => $listing->slug,
            'latitude' => (float) $location->latitude,
            'longitude' => (float) $location->longitude,
            'category' => $listing->categories->first()?->name ?? 'Uncategorized',
            'rating' => round((float) ($listing->business_reviews_avg_rating ?? 0), 1),
            'reviews_count' => (int) ($listing->business_reviews_count ?? 0),
            'cover_image_url' => $cover?->url ?? $listing->business?->cover_image_url,
            'logo_url' => $logo?->url ?? $listing->business?->logo_url,
            'is_featured' => (bool) $listing->is_featured,
            // PHASE 14 — `is_verified` REMOVED.
            //
            // It was the owner's PAID `verified_badge` plan feature, so it told
            // visitors a Listing had been verified when the only thing that happened
            // was that the owner bought a plan tier. No Listing verification process
            // exists (no `verified_at`, no workflow).
            //
            // Not renamed to `has_verified_badge_feature` either: nothing in the map
            // needs the owner's plan. Paid capability is exposed only where it is
            // honestly labelled (feature_flags / `verified_badge`).
            'address' => $location->full_address,
        ];
    }
    public function show($slug)
    {
        $business = Business::query()
            ->with([
                'locations' => function ($query) {
                    $query->whereNull('hidden_at')   // ✅ skip hidden branches
                        ->with(['country', 'region', 'city', 'area', 'hours']);
                },
                'locations.hours',
                'services' => function ($query) {
                    // Qualified: `services()` is a hasManyThrough across
                    // listing_services + listings, both of which have hidden_at.
                    $query->whereNull('listing_services.hidden_at');
                },
                'contacts',
                // PHASE 11 / WAVE 1D-3 — organization branding comes from the
                // Business-owned `businesses.logo` / `businesses.cover_image`
                // columns. There is no longer a through-Listing branding relation.
                'galleryImages' => function ($query) {
                    $query->whereNull('listing_images.hidden_at');
                },
                'owner',
                'owner.activeSubscription.plan',   // ✅ avoid N+1 on feature_flags
                'locations.hourOverrides',           // ✅ needed for is_open_now
                // PHASE 11 / WAVE 1D-2 — the organization page lists its
                // LISTINGS. Each is independently addressable at /listing/{slug};
                // the organization is never collapsed into one discovery result.
                'listings' => function ($query) {
                    $query->where('status', Listing::STATUS_PUBLISHED)
                        ->whereNull('listings.hidden_at')
                        ->with(['location.city', 'categories', 'images'])
                        ->withCount('businessReviews')
                        ->withAvg('businessReviews', 'rating');
                },
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
        $business->locations->each(function ($branch) {
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
        $isOpen = $business->locations->contains('is_open_now', true);

        // Check if business has active subscription
        $owner = $business->owner;
        $hasActiveSubscription = $owner && $owner->active_subscription !== null;

        if (!$hasActiveSubscription) {
            abort(404);
        }
        // Get primary branch
        $primaryLocation = $business->locations->firstWhere('is_primary', true) ?? $business->locations->first();

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

        // ✅ FIX (Phase 1): Removed production debug logging that fired an
        //    `info`-level log (including the full coupon payload) on EVERY
        //    business-profile view. This was audit-identified log spam and a
        //    minor PII / performance concern on a high-traffic public route.

        $businessData = $business->toArray();
        $businessData['feature_flags'] = $business->feature_flags;
        $businessData['is_favorited'] = auth()->check() && $business->isFavoritedBy(auth()->user());

        // ✅ Map integration — expose coordinates for the profile map
        $businessData['coordinates'] = $this->resolveBusinessCoordinates($business);

        // ✅ Related businesses — tiered (same category + same city → same category → same city)
        $relatedBusinesses = $this->resolveRelatedBusinesses($business);

        return Inertia::render('Public/BusinessProfile', [
            'business' => $business,
            // PHASE 11 / WAVE 1D-2 — the organization's Listings. This route is
            // the ORGANIZATION page; each Listing is its own discoverable entity
            // with its own /listing/{slug} identity.
            'listings' => ListingDirectoryResource::collection($business->listings)->resolve(),
            'coupons' => $coupons,
            'primaryLocation' => $primaryLocation,
            'isOpen' => $primaryLocation ? $primaryLocation->is_open_now : false,
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
     * Return [lat, lng, location_id, address] for the primary location,
     * or the first location that has coordinates, or null.
     */
    private function resolveBusinessCoordinates($business): ?array
    {
        $primary = $business->locations->firstWhere('is_primary', true)
            ?? $business->locations->first();

        $candidates = collect([$primary])
            ->merge($business->locations)
            ->filter()
            ->unique('id');

        foreach ($candidates as $branch) {
            if ($branch->latitude !== null && $branch->longitude !== null) {
                return [
                    'latitude' => (float) $branch->latitude,
                    'longitude' => (float) $branch->longitude,
                    'location_id' => $branch->id,
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

        $cityId = $business->locations->firstWhere('is_primary', true)?->city_id
            ?? $business->locations->first()?->city_id;

        if (!$categoryId && !$cityId) {
            return [];
        }

        $baseQuery = Business::query()
            ->with([
                'primaryLocation',
                'locations' => fn($q) => $q->whereNull('hidden_at'),
                'locations.city',
                'locations.region',
                'locations.country',
                'locations.hours',
                'locations.hourOverrides',
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
                ->withDiscoverableListingInCategory($categoryId)
                ->whereHas('locations', fn($q) => $q->where('city_id', $cityId))
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
                ->withDiscoverableListingInCategory($categoryId)
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
                ->whereHas('locations', fn($q) => $q->where('city_id', $cityId))
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