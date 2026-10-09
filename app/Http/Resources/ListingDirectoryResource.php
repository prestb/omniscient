<?php

namespace App\Http\Resources;

use App\Models\ListingImage;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * PHASE 11 / WAVE 1D-1 — the canonical public discovery result is a LISTING.
 *
 * A Business is an organization; it is not a competing discovery entity.
 * This resource replaces `BusinessDirectoryResource` as the public result
 * contract.
 *
 * The key names deliberately mirror the previous Business-shaped contract so
 * that existing result cards keep rendering while the ENTITY is a Listing.
 * Component vocabulary (e.g. `BusinessCard`'s `business` prop) is naming debt
 * deferred to Wave 1D-6 — see docs/PHASE_11_WAVE_1D_1_SEARCH_CONVERGENCE.md.
 *
 * A Listing has ZERO OR ONE Location, so `locations` is a 0/1 collection
 * rather than the previous multi-branch union.
 */
class ListingDirectoryResource extends JsonResource
{
    /**
     * PHASE 17 — discovery payloads stay lean; only the canonical Listing
     * page opts into the full Listing-owned detail.
     */
    public function __construct($resource, private bool $detailed = false)
    {
        parent::__construct($resource);
    }

    public function toArray($request)
    {
        $location = $this->location;

        $locations = $location ? collect([$location])->map(function ($place) {
            return [
                'id' => $place->id,
                'name' => $place->name,
                'address' => $place->address,
                'city' => $place->city?->name,
                'latitude' => $place->latitude !== null ? (float) $place->latitude : null,
                'longitude' => $place->longitude !== null ? (float) $place->longitude : null,
                'is_primary' => true,
                'is_open_now' => $place->is_open_now,
                'has_override_today' => $place->today_override !== null,
                'is_special_hours' => $place->today_special_hours !== null,
                'override_note' => $place->today_override?->note,
                'override_opens_at' => $place->today_special_hours?->opens_at?->format('H:i'),
                'override_closes_at' => $place->today_special_hours?->closes_at?->format('H:i'),
                'hours' => $place->hours->map(fn($hour) => [
                    'day' => $hour->day_of_week,
                    'opens_at' => $hour->opens_at,
                    'closes_at' => $hour->closes_at,
                    'is_closed' => $hour->is_closed,
                    'is_24h' => $hour->is_24h,
                ]),
            ];
        }) : collect();

        $isOpenNow = (bool) ($location?->is_open_now ?? false);

        // Organization branding may still supply brand assets (Wave 1D §21);
        // listing-owned media wins when present.
        $logoImage = $this->relationLoaded('images')
            ? $this->images->firstWhere('type', ListingImage::TYPE_LOGO)
            : null;
        $coverImage = $this->relationLoaded('images')
            ? $this->images->firstWhere('type', ListingImage::TYPE_COVER)
            : null;

        // PHASE 11 — these are the OWNING BUSINESS's review metrics shown on a
        // Listing, not a Listing-owned rating. Reviews belong to the Business;
        // this resource merely presents the organization's aggregate.
        $rating = $this->reviews_avg_rating ?? null;
        $reviewsCount = $this->reviews_count ?? null;

        return [
            // ── Discoverable identity ────────────────────────────────
            'id' => $this->id,
            'type' => 'listing',
            'listing_type' => $this->getListingType()->value,
            'name' => $this->name,
            'slug' => $this->slug,
            'description' => $this->description,

            // ── Contextual organization reference (not a result identity) ──
            'business_id' => $this->business_id,
            'business_name' => $this->business?->name,
            'business_slug' => $this->business?->slug,

            // ── Brand assets (listing media first, organization fallback) ──
            'logo' => $logoImage?->path ?? $this->business?->logo,
            'cover_image' => $coverImage?->path ?? $this->business?->cover_image,
            'logo_url' => $logoImage?->url ?? $this->business?->logo_url,
            'cover_image_url' => $coverImage?->url ?? $this->business?->cover_image_url,

            // ── Taxonomy (Listing-owned) ─────────────────────────────
            'categories' => $this->categories,

            // ── Physical place (0 or 1) ──────────────────────────────
            'location' => $location,
            'locations' => $locations,
            // Naming debt alias retained for existing cards (removed in 1D-6).
            'branches' => $locations,
            // PHASE 22C - the dead `primary_branch` alias was removed. It had
            // no consumer in app/, tests/ or resources/js. The separate
            // `branches` alias is RETAINED: RelatedBusinesses.vue still reads it.
            'primary_location' => $location,
            'coordinates' => ($location && $location->latitude !== null && $location->longitude !== null)
                ? ['latitude' => (float) $location->latitude, 'longitude' => (float) $location->longitude]
                : null,

            // ── Visibility / lifecycle ───────────────────────────────
            'status' => $this->status,
            'is_featured' => (bool) $this->is_featured,
            'is_open_now' => $isOpenNow,
            'open_branches_count' => $isOpenNow ? 1 : 0,
            'closed_branches_count' => $isOpenNow ? 0 : ($location ? 1 : 0),

            // ── Reputation ───────────────────────────────────────────
            'rating' => $rating,
            'average_rating' => $rating,
            'reviews_count' => $reviewsCount,

            'gallery_images_count' => $this->relationLoaded('images')
                ? $this->images->where('type', ListingImage::TYPE_GALLERY)->count()
                : 0,

            'feature_flags' => [
                'verified_badge' => (bool) $this->owner?->canUse('verified_badge'),
                'featured_listing' => (bool) $this->owner?->canUse('featured_listing'),
            ],

            // ── PHASE 17: LISTING-OWNED detail (opt-in) ─────────────────
            // The controller already eager-loads these; only serialization
            // was missing. Business branding is a separate concept and is
            // never substituted for Listing media.
            $this->mergeWhen($this->detailed, [
                // PHASE 21B-F - whether /business/{slug} is actually
                // reachable, so the public Listing never renders a dead
                // organization link. Detailed mode only: discovery payloads
                // stay lean.
                'business_publicly_reachable' => (bool) ($this->business?->isPubliclyReachable() ?? false),

                'services' => $this->relationLoaded('services')
                    ? $this->services->map(fn($s) => [
                        'id' => $s->id,
                        'name' => $s->name,
                        'description' => $s->description,
                    ])->values()
                    : [],

                'gallery' => $this->relationLoaded('images')
                    ? $this->images
                        ->where('type', ListingImage::TYPE_GALLERY)
                        ->map(fn($i) => [
                            'id' => $i->id,
                            'path' => $i->path,
                            'caption' => $i->caption,
                        ])->values()
                    : [],

                // Contacts are LISTING-owned. Blank values are dropped so
                // nothing unusable or fabricated reaches the page.
                'contacts' => $this->relationLoaded('contacts')
                    ? $this->contacts
                        ->filter(fn($c) => trim((string) $c->value) !== '')
                        ->map(fn($c) => [
                            'id' => $c->id,
                            'type' => $c->type,
                            'value' => $c->value,
                            'is_primary' => (bool) $c->is_primary,
                        ])->values()
                    : [],
            ]),
        ];
    }
}
