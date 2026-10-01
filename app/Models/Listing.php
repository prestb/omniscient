<?php

namespace App\Models;

use App\Support\ListingType;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;
use Laravel\Scout\Searchable;

/**
 * Listing
 *
 * ─────────────────────────────────────────────────────────────────────────
 * PHASE 9 — THE CANONICAL DISCOVERABLE ENTITY
 * ─────────────────────────────────────────────────────────────────────────
 * A Listing is the thing users discover in Omniscient. It is owned by an
 * Account (= User) and has:
 *
 *   - a `type`             (business | professional | store)
 *   - an identity          (name, slug, description)
 *   - an optional org       (business_id → Business)
 *   - an optional location  (location_id → Location, a physical place)
 *   - a lifecycle           (status + published_at + hidden_at)
 *   - its own children      (categories, services, media, contacts, reviews,
 *                            analytics, leads, favorites, coupons)
 *
 * Invariants (see docs/PHASE_9_LISTING_CORE_IMPLEMENTATION.md):
 *   - A Listing has ZERO OR ONE Location. Multiple physical locations are
 *     represented by MULTIPLE Listings under the same Business — never one
 *     Listing with many locations.
 *   - `business_id = NULL` is a standalone listing (e.g. a locationless
 *     Professional). It is NOT required that every Listing belong to a
 *     Business.
 *   - Ownership is the Account (owner_id). Business is an organization the
 *     listing MAY belong to, not the owner.
 */
class Listing extends Model
{
    use HasFactory, SoftDeletes, Searchable;

    protected $fillable = [
        'owner_id',
        'business_id',
        'location_id',
        'type',
        'name',
        'slug',
        'description',
        'status',
        'is_featured',
        'published_at',
        'hidden_at',
    ];

    protected $casts = [
        'is_featured' => 'boolean',
        'published_at' => 'datetime',
        'hidden_at' => 'datetime',
    ];

    // ── Lifecycle constants (reuse the project's existing vocabulary) ──
    const STATUS_DRAFT = 'draft';
    const STATUS_SUBMITTED = 'submitted';
    const STATUS_APPROVED = 'approved';
    const STATUS_PUBLISHED = 'published';
    const STATUS_REJECTED = 'rejected';
    const STATUS_SUSPENDED = 'suspended';
    const STATUS_INACTIVE = 'inactive';

    protected static function boot()
    {
        parent::boot();

        static::creating(function (Listing $listing) {
            if (empty($listing->slug)) {
                $listing->slug = self::generateUniqueSlug($listing->name);
            }

            if (empty($listing->type)) {
                $listing->type = ListingType::BUSINESS->value;
            }

            // A listing created without an explicit owner inherits its
            // organization's owner, if it has one.
            if (empty($listing->owner_id) && !empty($listing->business_id)) {
                $listing->owner_id = Business::find($listing->business_id)?->owner_id;
            }
        });

        static::updating(function (Listing $listing) {
            if ($listing->isDirty('name')) {
                $listing->slug = self::generateUniqueSlug($listing->name, $listing->id);
            }
        });
    }

    /**
     * Generate a slug unique across ALL listings.
     */
    protected static function generateUniqueSlug(string $name, ?int $ignoreId = null): string
    {
        $base = Str::slug($name) ?: 'listing';
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

    // ============== TYPE ==============

    /**
     * The listing type, resolved through the canonical type axis.
     */
    public function getListingType(): ListingType
    {
        return $this->type instanceof ListingType
            ? $this->type
            : ListingType::fromStored($this->type);
    }

    public function isListingType(ListingType $type): bool
    {
        return $this->getListingType() === $type;
    }

    public function scopeOfType($query, ListingType $type)
    {
        return $query->where('type', $type->value);
    }

    // ============== RELATIONSHIPS ==============

    /** The Account that owns this listing. */
    public function owner()
    {
        return $this->belongsTo(User::class, 'owner_id');
    }

    /** The organization this listing belongs to (optional). */
    public function business()
    {
        return $this->belongsTo(Business::class);
    }

    /** The physical location of this listing (optional, at most one). */
    public function location()
    {
        return $this->belongsTo(Location::class, 'location_id');
    }

    public function categories()
    {
        return $this->belongsToMany(Category::class, 'listing_categories')
            ->withPivot(['is_primary', 'sort_order'])
            ->withTimestamps()
            ->orderByPivot('sort_order');
    }

    public function services()
    {
        return $this->hasMany(ListingService::class)->orderBy('sort_order');
    }

    public function images()
    {
        return $this->hasMany(ListingImage::class)->orderBy('sort_order');
    }

    public function contacts()
    {
        return $this->hasMany(ListingContact::class)->orderBy('sort_order');
    }

    /**
     * PHASE 11 — Listings do NOT own Reviews.
     *
     * `reviews()` was a hasMany on `reviews.listing_id`, a column no application
     * writer ever populated. It was therefore permanently empty while the
     * Listing profile page rendered it, presenting a Business-owned resource as
     * a Listing-owned feature. Removed with the column, not replaced.
     *
     * Listings DISPLAY the owning Business's aggregate through
     * {@see businessReviews()} below.
     */


    /**
     * PHASE 11 — BUSINESS REVIEW AGGREGATE (Step 1 of review attribution integrity).
     *
     * Reviews belong to the owning BUSINESS, not to this Listing. This relation
     * exists so a Listing can DISPLAY its organization's review metrics:
     *
     *     Listing -> Business -> Review
     *
     * It is deliberately NOT named `reviews()`. "Which reviews belong to this
     * Listing?" has no answer — they belong to the Business. The name states the
     * source, and `withCount` / `withAvg` / `orderByDesc` all work natively
     * against it at SQL level, so browse sorting needs no query redesign.
     *
     * `reviews()` above is the obsolete Listing-owned relation; it is scheduled
     * for removal in Step 2 once every consumer has moved here.
     */
    public function businessReviews()
    {
        return $this->hasManyThrough(
            Review::class,
            Business::class,
            'id',          // businesses.id          (intermediate key)
            'business_id', // reviews.business_id    (final model key)
            'business_id', // listings.business_id   (local key)
            'id'           // businesses.id          (intermediate local key)
        );
    }

    public function allReviews()
    {
        return $this->hasMany(Review::class);
    }

    public function analytics()
    {
        return $this->hasMany(ListingAnalytics::class);
    }

    public function leads()
    {
        return $this->hasMany(Lead::class);
    }

    public function coupons()
    {
        return $this->hasMany(Coupon::class);
    }

    public function favorites()
    {
        return $this->hasMany(Favorite::class);
    }

    // ============== SCOPES ==============

    public function scopePublished($query)
    {
        return $query->where('status', self::STATUS_PUBLISHED);
    }

    public function scopeVisible($query)
    {
        return $query->whereNull('hidden_at');
    }

    public function scopeHidden($query)
    {
        return $query->whereNotNull('hidden_at');
    }

    // ============== HELPERS ==============

    public function isPublished(): bool
    {
        return $this->status === self::STATUS_PUBLISHED;
    }

    public function isHidden(): bool
    {
        return $this->hidden_at !== null;
    }

    public function isVisibleToPublic(): bool
    {
        return $this->status === self::STATUS_PUBLISHED && $this->hidden_at === null;
    }

    public function canBeEditedBy(User $user): bool
    {
        return $user->isAdmin() || $user->id === $this->owner_id;
    }

    /**
     * Listing count for an account (the authoritative quota metric).
     * Branches/Locations are NOT listings and are never counted here.
     */
    public static function countFor(User|int $account): int
    {
        $ownerId = $account instanceof User ? $account->id : $account;

        return self::query()
            ->where('owner_id', $ownerId)
            ->whereNotIn('status', ['deleted', 'rejected'])
            ->count();
    }

    // ============== SEARCHABLE ==============

    public function toSearchableArray()
    {
        $this->loadMissing([
            'location.city',
            'location.region',
            'location.country',
            'location.hours',
            'business',
            'categories',
            'services',
            'owner.activeSubscription',
        ]);

        $location = $this->location;

        return [
            'id' => $this->id,
            'name' => $this->name,
            'description' => $this->description,
            'slug' => $this->slug,
            'type' => $this->getListingType()->value,
            'status' => $this->status,
            'is_featured' => (bool) $this->is_featured,
            'hidden' => $this->hidden_at !== null,
            'created_at' => $this->created_at?->timestamp,
            'published_at' => $this->published_at?->timestamp,

            // Contextual organization reference — NOT a separate result identity.
            'business_id' => $this->business_id,

            'location_id' => $this->location_id,
            'city_id' => $location?->city_id,
            'region_id' => $location?->region_id,
            'country_id' => $location?->country_id,
            'city' => $location?->city?->name,
            'region' => $location?->region?->name,
            'country' => $location?->country?->name,
            'address' => $location?->address,
            'is_open_now' => (bool) ($location?->is_open_now ?? false),

            'category_ids' => $this->categories->pluck('id')->values()->all(),
            'categories_names' => $this->categories->pluck('name')->values()->all(),
            'services_names' => $this->services->pluck('name')->values()->all(),

            // PHASE 11 / WAVE 1D-1 — subscriptions are ACCOUNT-scoped, so the
            // paid-visibility rule lives on the owner. This preserves the
            // previous Business-level `has_active_subscription` filter meaning
            // without keeping Business as a search entity.
            'has_active_subscription' => (bool) ($this->owner?->activeSubscription),
        ];
    }

    public function searchableAs(): string
    {
        return 'listings';
    }
}
