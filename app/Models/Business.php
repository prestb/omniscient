<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;
use Laravel\Scout\Searchable;

class Business extends Model
{
    use HasFactory, SoftDeletes, Searchable;

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

    // ✅ ADD THIS
    protected $appends = [
        'feature_flags',
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
     * Check if business can create a new business
     */
    public static function canCreateBusiness($ownerId)
    {
        $owner = User::find($ownerId);
        if (!$owner)
            return false;

        // Super admin can create unlimited
        if ($owner->role === 'super_admin') {
            return true;
        }

        // Get active subscription
        $subscription = $owner->active_subscription;

        // No active subscription
        if (!$subscription) {
            return false;
        }

        // Get plan limits
        $maxBusinesses = $subscription->plan->max_businesses ?? 0;

        // Unlimited
        if ($maxBusinesses === -1 || $maxBusinesses === 999) {

            return true;
        }

        // Count existing businesses
        $businessCount = self::where('owner_id', $ownerId)
            ->whereNotIn('status', ['deleted', 'rejected'])
            ->count();

        return $businessCount < $maxBusinesses;
    }

    /**
     * Get remaining business slots
     */

    public static function getRemainingBusinessSlots($ownerId)
    {
        $owner = User::find($ownerId);
        if (!$owner)
            return 0;

        if ($owner->role === 'super_admin') {
            return PHP_INT_MAX;
        }

        $subscription = $owner->active_subscription;
        if (!$subscription) {
            return 0;
        }

        $maxBusinesses = $subscription->plan->max_businesses ?? 0;
        if ($maxBusinesses === -1 || $maxBusinesses === 999) {
            return PHP_INT_MAX;
        }

        $businessCount = self::where('owner_id', $ownerId)
            ->whereNotIn('status', ['deleted', 'rejected'])
            ->count();

        return max(0, $maxBusinesses - $businessCount);
    }

    /**
     * Check if user can create a new branch for a business
     */
    public function canCreateBranch()
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

        $maxBranches = $subscription->plan->max_branches ?? 0;
        if ($maxBranches === -1 || $maxBranches === 999) {
            return true;
        }

        $branchCount = $this->branches()->count();
        return $branchCount < $maxBranches;
    }

    /**
     * Get remaining branch slots for this business
     */
    public function getRemainingBranchSlots()
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

        $maxBranches = $subscription->plan->max_branches ?? 0;
        if ($maxBranches === -1 || $maxBranches === 999) {
            return PHP_INT_MAX;
        }

        $branchCount = $this->branches()->count();
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
        if ($maxImages === -1 || $maxImages === 99 - 19) {
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
            case 'branches':
                return !$this->canCreateBranch();
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

    public function branches()
    {
        return $this->hasMany(Branch::class);
    }

    public function primaryBranch()
    {
        return $this->hasOne(Branch::class)->where('is_primary', true);
    }

    public function categories()
    {
        return $this->belongsToMany(Category::class, 'business_categories')
            ->withPivot('is_primary')
            ->withTimestamps();
    }

    public function images()
    {
        return $this->hasMany(BusinessImage::class)->orderBy('sort_order');
    }

    public function logo()
    {
        return $this->hasOne(BusinessImage::class)->where('type', BusinessImage::TYPE_LOGO);
    }

    public function coverImage()
    {
        return $this->hasOne(BusinessImage::class)->where('type', BusinessImage::TYPE_COVER);
    }

    public function galleryImages()
    {
        return $this->hasMany(BusinessImage::class)->where('type', BusinessImage::TYPE_GALLERY)->orderBy('sort_order');
    }

    public function services()
    {
        return $this->hasMany(BusinessService::class)->orderBy('sort_order');
    }

    public function contacts()
    {
        return $this->hasMany(BusinessContact::class)->orderBy('sort_order');
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
    // ============== SEARCHABLE ==============

    /**
     * ✅ Meilisearch searchable payload.
     *
     *    Singular city_id / region_id / country_id and city / region strings
     *    refer to the PRIMARY branch — kept for backwards compatibility.
     *
     *    Plural city_ids / region_ids / country_ids are the UNION of all
     *    branches' values — used for multi-branch filtering (so a business
     *    with a branch in Yaoundé matches a Yaounde search even if its
     *    primary branch is in Buea).
     */
    public function toSearchableArray()
    {
        // Ensure branches + their locations are loaded (avoids N+1 during index)
        $this->loadMissing([
            'branches',
            'branches.city',
            'branches.region',
            'branches.country',
            'categories',
            'services',
            'owner',
            'owner.activeSubscription',
        ]);

        $primaryBranch = $this->branches->firstWhere('is_primary', true)
            ?? $this->branches->first();

        $categoryIds = $this->categories->pluck('id')->values()->all();
        $categoryNames = $this->categories->pluck('name')->values()->all();

        $serviceNames = method_exists($this, 'services')
            ? $this->services->pluck('name')->values()->all()
            : [];

        return [
            'id' => $this->id,
            'name' => $this->name,
            'description' => $this->description,
            'slug' => $this->slug,
            'status' => $this->status,
            'is_featured' => (bool) $this->is_featured,
            'average_rating' => $this->average_rating,
            'created_at' => $this->created_at?->timestamp,
            'published_at' => $this->published_at?->timestamp,
            'has_active_subscription' => (bool) $this->has_active_subscription,
            'hidden' => $this->hidden_at !== null,

            'category_ids' => $categoryIds,
            'categories_names' => $categoryNames,
            'services_names' => $serviceNames,

            // ============== PRIMARY BRANCH (singular, unchanged) ==============
            'city_id' => $primaryBranch?->city_id,
            'region_id' => $primaryBranch?->region_id,
            'country_id' => $primaryBranch?->country_id,
            'city' => $primaryBranch?->city?->name,
            'region' => $primaryBranch?->region?->name,
            'country' => $primaryBranch?->country?->name,
            'address' => $primaryBranch?->address,
            'phone' => $primaryBranch?->phone,
            'email' => $this->email,
            'website' => $this->website,

            // ============== ALL BRANCHES (plural, new) ==============
            'city_ids' => $this->branches->pluck('city_id')->filter()->unique()->values()->all(),
            'region_ids' => $this->branches->pluck('region_id')->filter()->unique()->values()->all(),
            'country_ids' => $this->branches->pluck('country_id')->filter()->unique()->values()->all(),
        ];
    }

    public function searchableAs(): string
    {
        return 'businesses';
    }


    /**
     * Custom search method with filters
     */
    public static function smartSearch($query, $filters = [])
    {
        $search = static::search($query);

        // Apply status filter
        if (isset($filters['status'])) {
            $search->where('status', $filters['status']);
        }

        // Apply featured filter
        if (isset($filters['is_featured'])) {
            $search->where('is_featured', $filters['is_featured']);
        }

        // Apply category filter
        if (isset($filters['category_id'])) {
            $search->where('category_ids', $filters['category_id']);
        }

        // Apply location filters
        if (isset($filters['city_id'])) {
            $search->where('city_id', $filters['city_id']);
        }

        if (isset($filters['region_id'])) {
            $search->where('region_id', $filters['region_id']);
        }

        // Apply open now filter
        if (isset($filters['open_now']) && $filters['open_now']) {
            $search->where('is_open_now', true);
        }

        return $search;
    }

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