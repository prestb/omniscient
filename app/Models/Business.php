<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

/**
 * Business
 *
 * ─────────────────────────────────────────────────────────────────────────
 * PHASE 9 — BUSINESS IS AN ORGANIZATION (not a discoverable entity)
 * ─────────────────────────────────────────────────────────────────────────
  * A Business is an organization / brand / aggregate owned by an Account.
 * It is NOT the canonical discoverable entity — {@see \App\Models\Listing}
 * is. A Business may own MANY Listings (e.g. a multi-location brand), each
 * of which may have its own Location.
 *
 *   Account → Business (org) → Listing* → Location?
 *
 * There is NO `listing_type` column on `businesses`: listing type lives solely
 * on `listings.type` (App\Support\ListingType) — one source of truth.
 * See docs/PHASE_9_LISTING_CORE_IMPLEMENTATION.md.
 *
 * Ownership: `owner_id → User` remains the Account edge for the organization.
 */
class Business extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'owner_id',
        'name',
        'slug',
        'description',
        'logo',
        'cover_image',
        'email',
        'website',
        // 'phone',
        'status',
        'is_featured',
        'reviewed_by',
        'reviewed_at',
        'submitted_at',
        'published_at',
        'hidden_at',
    ];

    protected $casts = [
        'is_featured' => 'boolean',
        'reviewed_at' => 'datetime',
        'submitted_at' => 'datetime',
        'published_at' => 'datetime',
        'hidden_at' => 'datetime',
    ];

    protected $appends = [
        'feature_flags',
        // PHASE 11 / WAVE 1C — categories are DERIVED from the organization's
        // listings (there is no `business_categories` pivot). Appended so the
        // existing Inertia payloads keep exposing `business.categories`.
        'categories',
    ];

    // Status Constants
    const STATUS_DRAFT = 'draft';
    const STATUS_SUBMITTED = 'submitted';
    const STATUS_APPROVED = 'approved';
    const STATUS_PUBLISHED = 'published';
    const STATUS_REJECTED = 'rejected';
    const STATUS_SUSPENDED = 'suspended';

    const STATUS_INACTIVE = 'inactive'; // ✅ NEW

    // Boot method
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($business) {
            if (empty($business->slug)) {
                $business->slug = self::generateUniqueSlug($business->name);
            }
        });

        static::updating(function ($business) {
            if ($business->isDirty('name')) {
                $business->slug = self::generateUniqueSlug($business->name, $business->id);
            }
        });
    }

    /**
     * Generate a slug that does not collide with any existing business.
     *
     * Appends -2, -3, -4, ... when the base slug already exists.
     * Pass $ignoreId when updating so a business doesn't collide with itself.
     */
    protected static function generateUniqueSlug(string $name, ?int $ignoreId = null): string
    {
        $base = Str::slug($name) ?: 'business';
        $slug = $base;
        $i = 1;

        while (
            self::withTrashed()
                ->where('slug', $slug)
                ->when($ignoreId, fn($q) => $q->where('id', '!=', $ignoreId))
                ->exists()
        ) {
            $slug = $base . '-' . (++$i);
        }

        return $slug;
    }

    // ============== SUBSCRIPTION METHODS ==============



    /**
     * Check if user can add another physical Location to this organization.
     *
     * PHASE 11 — the canonical plan column is `max_locations` and the quota key is
     * `locations` (Entitlement::CREATE_LOCATION).
     */
    public function canCreateLocation()
    {
        $owner = $this->owner;
        if (!$owner)
            return false;

        if ($owner->role === 'super_admin') {
            return true;
        }

        $subscription = $owner->active_subscription;
        if (!$subscription) {
            return false;
        }

                $maxBranches = $subscription->plan->max_locations ?? 0;
        if ($maxBranches === -1 || $maxBranches === 999) {
            return true;
        }

        $branchCount = $this->locations()->count();
        return $branchCount < $maxBranches;
    }

    /**
     * Get remaining location slots for this business.
     */
    public function getRemainingLocationSlots()
    {
        $owner = $this->owner;
        if (!$owner)
            return 0;

        if ($owner->role === 'super_admin') {
            return PHP_INT_MAX;
        }

        $subscription = $owner->active_subscription;
        if (!$subscription) {
            return 0;
        }

                $maxBranches = $subscription->plan->max_locations ?? 0;
        if ($maxBranches === -1 || $maxBranches === 999) {
            return PHP_INT_MAX;
        }

        $branchCount = $this->locations()->count();
        return max(0, $maxBranches - $branchCount);
    }

    /**
     * Check if user can upload images
     */
    public function canUploadImages()
    {
        $owner = $this->owner;
        if (!$owner)
            return false;

        if ($owner->role === 'super_admin') {
            return true;
        }

        $subscription = $owner->active_subscription;
        if (!$subscription) {
            return false;
        }

        $maxImages = $subscription->plan->max_images ?? 0;
        if ($maxImages === -1 || $maxImages === 999) {
            return true;
        }

        $imageCount = $this->images()->count();
        return $imageCount < $maxImages;
    }

    /**
     * Get remaining image slots
     */
    public function getRemainingImageSlots()
    {
        $owner = $this->owner;
        if (!$owner)
            return 0;

        if ($owner->role === 'super_admin') {
            return PHP_INT_MAX;
        }

        $subscription = $owner->active_subscription;
        if (!$subscription) {
            return 0;
        }

        $maxImages = $subscription->plan->max_images ?? 0;

        // ✅ FIX (Phase 1): was `$maxImages === 99 - 19` (= 80), an inconsistent
        //    magic value that did not match the unlimited sentinels (-1 / 999)
        //    used by every sibling method. Unlimited plans now report remaining
        //    image slots correctly.
        if ($maxImages === -1 || $maxImages === 999) {
            return PHP_INT_MAX;
        }

        $imageCount = $this->images()->count();
        return max(0, $maxImages - $imageCount);
    }

    /**
     * Check if business has reached its limits
     */
    public function hasReachedLimit($type)
    {
        switch ($type) {
            case 'locations':
                return !$this->canCreateLocation();
            case 'images':
                return !$this->canUploadImages();
            default:
                return false;
        }
    }

    // ============== RELATIONSHIPS ==============

    public function owner()
    {
        return $this->belongsTo(User::class, 'owner_id');
    }

    /**
     * PHASE 9 — the organization's Listings (its discoverable entities).
     *
     * A multi-location brand owns one Listing per location; a single-location
     * business owns a single Listing. Listings are the canonical discoverable
     * entity — Business is the organization that groups them.
     */
        public function listings()
    {
        return $this->hasMany(Listing::class);
    }

    /**
     * PHASE 11 / WAVE 1D-3 — `primaryListing()` was DELETED here.
     *
     * It returned the organization's "primary" Listing (oldest published, else
     * oldest) and existed only so owner-facing routes addressed by `{business}`
     * could reach Listing-owned children. Every such operation has been migrated
     * to explicit Listing context (services, contacts, images, analytics,
     * categories), so nothing may select a representative Listing for a Business
     * any more.
     *
     * A Business is an OPTIONAL organization that may own zero, one or many
     * Listings. Aggregating across ALL of them (`$business->listings()`) remains
     * legitimate — see the Business aggregate analytics dashboard. Choosing ONE
     * of them to perform a Listing-owned operation is not.
     *
     * No replacement resolver exists or may be introduced.
     */

    /**
     * PHASE 11 / WAVE 1C — constrain to organizations that own a DISCOVERABLE
     * Listing in the given category.
     *
     * Category ownership belongs to the LISTING, so an organization is matched
     * through its listings. There is no business-level taxonomy to match on.
     */
    public function scopeWithDiscoverableListingInCategory($query, int $categoryId)
    {
        return $query->whereHas('listings', function ($q) use ($categoryId) {
            $q->where('status', Listing::STATUS_PUBLISHED)
                ->whereNull('listings.hidden_at')
                ->whereHas('categories', fn($c) => $c->where('categories.id', $categoryId));
        });
    }

        /**
         * PHASE 10 — the organization's physical Locations (places).
         *
         * A Location is a universal physical place; `business_id` on `locations`
         * is OPTIONAL, so this relation may be empty even when the organization
         * has Listings (standalone-location listings).
         */
        public function locations()
        {
            return $this->hasMany(Location::class);
        }

        public function primaryLocation()
        {
            return $this->hasOne(Location::class)->where('is_primary', true);
        }

    /**
     * PHASE 11 / WAVE 1C — categories are owned by LISTINGS, never by the
     * organization.
     *
     * There is no `business_categories` pivot and no stored organization-level
     * taxonomy. The organization exposes the UNION of its listings' categories,
     * derived on read:
     *
     *     Business → Listings → Categories
     *
     * `pivot` is preserved from `listing_categories` because existing consumers
     * (public cards, profile pages, owner forms) read `pivot.is_primary`.
     *
     * @return \Illuminate\Support\Collection<int, Category>
     */
    public function getCategoriesAttribute()
    {
        return $this->listings()
            ->with('categories')
            ->get()
            ->pluck('categories')
            ->flatten()
            ->unique('id')
            ->values();
    }

        /**
     * PHASE 11 / WAVE 1B — organization-level media is an AGGREGATE across the
     * organization's listings. Media rows are listing-owned (`listing_id`);
     * they are read here through the Listing so organization dashboards keep
     * working. There is no `business_id` on `listing_images`.
     */
    public function images()
    {
        return $this->hasManyThrough(
            ListingImage::class,
            Listing::class,
            'business_id', // FK on listings
            'listing_id',  // FK on listing_images
            'id',          // local key on businesses
            'id'           // local key on listings
        )->orderBy('listing_images.sort_order');
    }

    /**
     * PHASE 11 / WAVE 1D-3 — ORGANIZATION BRANDING IS BUSINESS-OWNED.
     *
     * `logo()` and `coverImage()` used to be `hasOneThrough(ListingImage, Listing)`
     * relations: the organization's branding was read out of an arbitrarily
     * chosen Listing's media. A Business may own several Listings, so those
     * relations had no legitimate answer to "which Listing's logo is the
     * organization's logo?".
     *
     * They are REMOVED. Organization branding is the Business-owned
     * `businesses.logo` / `businesses.cover_image` columns, exposed through the
     * existing `logo_url` / `cover_image_url` accessors. Branding never resolves
     * through a Listing.
     *
     * Listing presentation media is Listing-owned and lives in `listing_images`
     * (see App\Models\ListingImage and Owner\ListingImageController).
     */

    public function galleryImages()
    {
        return $this->hasManyThrough(
            ListingImage::class,
            Listing::class,
            'business_id',
            'listing_id',
            'id',
            'id'
        )
            ->where('listing_images.type', ListingImage::TYPE_GALLERY)
            ->orderBy('listing_images.sort_order');
    }

    /**
     * PHASE 11 / WAVE 1B — organization-level services are an AGGREGATE across
     * the organization's listings. Services are listing-owned (`listing_id`).
     */
    public function services()
    {
        return $this->hasManyThrough(
            ListingService::class,
            Listing::class,
            'business_id',
            'listing_id',
            'id',
            'id'
        )->orderBy('listing_services.sort_order');
    }

    /**
     * PHASE 11 / WAVE 1B — organization-level contacts are an AGGREGATE across
     * the organization's listings. Contacts are listing-owned (`listing_id`).
     */
    public function contacts()
    {
        return $this->hasManyThrough(
            ListingContact::class,
            Listing::class,
            'business_id',
            'listing_id',
            'id',
            'id'
        )->orderBy('listing_contacts.sort_order');
    }

    public function subscriptions()
    {
        return $this->hasMany(Subscription::class);
    }




    /**
     * Get the owner's active subscription (business uses owner's plan)
     */
    public function getActiveSubscriptionAttribute()
    {
        return $this->owner?->active_subscription;
    }

    /**
     * Check if this business has an active subscription (via owner)
     */
    public function hasActiveSubscription(): bool
    {
        return $this->owner?->active_subscription !== null;
    }

    /**
     * ✅ Attribute accessor — lets `$business->has_active_subscription` work.
     *
     *    Without this, the attribute read returns null (no column, no accessor),
     *    which breaks `toSearchableArray()` indexing (subscription shows as false
     *    for every business in Meilisearch).
     */
    public function getHasActiveSubscriptionAttribute(): bool
    {
        return $this->hasActiveSubscription();
    }

    public function reviews()
    {
        return $this->hasMany(Review::class)->approved();
    }

    public function allReviews()
    {
        return $this->hasMany(Review::class);
    }

    // ============== SCOPES ==============

    public function scopePublished($query)
    {
        return $query->where('status', self::STATUS_PUBLISHED);
    }

    public function scopeActive($query)
    {
        return $query->whereIn('status', [self::STATUS_PUBLISHED, self::STATUS_APPROVED]);
    }

    public function scopeFeatured($query)
    {
        return $query->where('is_featured', true);
    }

    /**
     * PHASE 9 — the authoritative LISTING COUNT for an account now lives on
     * {@see \App\Models\Listing::countFor()}. Businesses are organizations,
     * not discoverable listings, so counting them is no longer meaningful
     * for quota. This method is retained as a thin, clearly-named alias for
     * ORGANIZATION counting (used by organization-level dashboards), NOT for
     * listing quota.
     */
    public static function organizationsCountFor(User|int $account): int
    {
        $ownerId = $account instanceof User ? $account->id : $account;

        return self::query()
            ->where('owner_id', $ownerId)
            ->whereNotIn('status', ['deleted', 'rejected'])
            ->count();
    }

    // ✅ NEW — Plan-enforcement visibility scopes
    public function scopeVisible($query)
    {
        return $query->whereNull('hidden_at');
    }

    public function scopeHidden($query)
    {
        return $query->whereNotNull('hidden_at');
    }

    public function isHidden(): bool
    {
        return $this->hidden_at !== null;
    }

    // ============== ACCESSORS ==============

    protected ?float $memoizedAverageRating = null;

    public function getAverageRatingAttribute()
    {
        if ($this->memoizedAverageRating !== null) {
            return $this->memoizedAverageRating;
        }

        // ✅ Prefer eager-loaded aggregate (withAvg) — avoids N+1 in lists
        if (array_key_exists('reviews_avg_rating', $this->attributes)) {
            $val = $this->attributes['reviews_avg_rating'];
            $this->memoizedAverageRating = $val !== null ? round((float) $val, 1) : 0.0;
            return $this->memoizedAverageRating;
        }

        $avg = $this->reviews()->where('status', 'approved')->avg('rating');

        $this->memoizedAverageRating = $avg ? round((float) $avg, 1) : 0.0;

        return $this->memoizedAverageRating;
    }
    protected ?int $memoizedTotalReviews = null;

    public function getTotalReviewsAttribute()
    {
        if ($this->memoizedTotalReviews !== null) {
            return $this->memoizedTotalReviews;
        }

        // ✅ Prefer eager-loaded aggregate (withCount) — avoids N+1 in lists
        if (array_key_exists('reviews_count', $this->attributes)) {
            $this->memoizedTotalReviews = (int) $this->attributes['reviews_count'];
            return $this->memoizedTotalReviews;
        }

        $this->memoizedTotalReviews = $this->reviews()
            ->where('status', 'approved')
            ->count();

        return $this->memoizedTotalReviews;
    }

    public function getLogoUrlAttribute()
    {
        if ($this->logo) {
            return asset('storage/' . $this->logo);
        }
        return null;
    }

    public function getCoverImageUrlAttribute()
    {
        if ($this->cover_image) {
            return asset('storage/' . $this->cover_image);
        }
        return null;
    }

    public function getInitialsAttribute()
    {
        $words = explode(' ', $this->name);
        $initials = '';
        foreach ($words as $word) {
            $initials .= strtoupper(substr($word, 0, 1));
        }
        return substr($initials, 0, 2);
    }

    public function getStatusBadgeAttribute()
    {
        $badges = [
            self::STATUS_DRAFT => 'badge-draft',
            self::STATUS_SUBMITTED => 'badge-pending',
            self::STATUS_APPROVED => 'badge-active',
            self::STATUS_PUBLISHED => 'badge-published',
            self::STATUS_INACTIVE => 'badge-inactive', // ✅ NEW
            self::STATUS_REJECTED => 'badge-suspended',
            self::STATUS_SUSPENDED => 'badge-suspended',
        ];
        return $badges[$this->status] ?? 'badge-draft';
    }

    public function getStatusLabelAttribute()
    {
        return ucfirst(str_replace('_', ' ', $this->status));
    }

    // ============== HELPER METHODS ==============

    public function isPublished()
    {
        return $this->status === self::STATUS_PUBLISHED;
    }

    public function isDraft()
    {
        return $this->status === self::STATUS_DRAFT;
    }

    public function isSubmitted()
    {
        return $this->status === self::STATUS_SUBMITTED;
    }

    public function canBeEditedBy(User $user)
    {
        return $user->isAdmin() || $user->id === $this->owner_id;
    }


    /**
     * Check if business is currently live
     */
    public function isLive(): bool
    {
        return $this->status === self::STATUS_PUBLISHED;
    }

    /**
     * Check if business is inactive (owner paused it)
     */
    public function isInactive(): bool
    {
        return $this->status === self::STATUS_INACTIVE;
    }

    /**
     * Check if owner can toggle this business active/inactive
     */
    public function canBeToggled(): bool
    {
        return in_array($this->status, [
            self::STATUS_PUBLISHED,
            self::STATUS_INACTIVE,
        ]);
    }

    /**
     * Check if business is visible to the public
     */
    public function isVisibleToPublic(): bool
    {
        return $this->status === self::STATUS_PUBLISHED;
    }

        // ============== LISTING RELATIONSHIP (Phase 9) ==============
    //
    // Business is an ORGANIZATION. Its discoverable entities are Listings
    // ({@see listings()}). Listing-specific actions (type, lifecycle,
    // ownership-in-listing-terms) live on the Listing model.

    // ============== FEATURE FLAG METHODS ==============

    /**
     * Feature flags accessor (auto-included in JSON)
     */
    public function getFeatureFlagsAttribute(): array
    {
        return [
            'whatsapp_button' => $this->hasWhatsAppFeature(),
            'phone_display' => $this->hasPhoneFeature(),
            'respond_to_reviews' => $this->hasReviewResponseFeature(),
            'verified_badge' => $this->hasVerifiedBadgeFeature(),
            'featured_listing' => $this->hasFeaturedListingFeature(),
            'lead_capture' => $this->hasLeadCaptureFeature(), // ✅ New
        ];
    }

    public function hasWhatsAppFeature(): bool
    {
        return $this->ownerCanUseFeature('whatsapp_button');
    }

    public function hasPhoneFeature(): bool
    {
        return $this->ownerCanUseFeature('phone_display');
    }

    public function hasReviewResponseFeature(): bool
    {
        return $this->ownerCanUseFeature('respond_to_reviews');
    }

    public function hasVerifiedBadgeFeature(): bool
    {
        return $this->ownerCanUseFeature('verified_badge');
    }

    public function hasFeaturedListingFeature(): bool
    {
        return $this->ownerCanUseFeature('featured_listing');
    }

    public function ownerCanUseFeature(string $feature): bool
    {
        $owner = $this->owner;

        if (!$owner) {
            return false;
        }

        // Super admin always has access
        if ($owner->role === 'super_admin') {
            return true;
        }

        // Prefer new HasPlanFeatures trait
        if (method_exists($owner, 'canUse')) {
            return $owner->canUse($feature);
        }

        // Fallback: check subscription plan features JSON
        $subscription = $owner->activeSubscription ?? $owner->active_subscription ?? null;
        if (!$subscription || !$subscription->plan) {
            return false;
        }

        $features = $subscription->plan->features ?? [];
        return !empty($features[$feature]);
    }

    public function leads()
    {
        return $this->hasMany(Lead::class);
    }


    public function hasLeadCaptureFeature(): bool
    {
        return $this->ownerCanUseFeature('lead_capture');
    }

    public function canUseCoupons(): bool
    {
        return $this->ownerCanUseFeature('coupons');
    }

    public function favorites()
    {
        return $this->hasMany(Favorite::class);
    }

    public function favoritedBy()
    {
        return $this->belongsToMany(User::class, 'favorites')->withTimestamps();
    }

    public function isFavoritedBy(?User $user): bool
    {
        if (!$user)
            return false;
        return $this->favorites()->where('user_id', $user->id)->exists();
    }

}