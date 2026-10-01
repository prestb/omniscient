<?php

namespace App\Services;

use App\Models\Business;
use App\Support\ListingType;

/**
 * ListingMigrationAnalyzer
 *
 * ─────────────────────────────────────────────────────────────────────────
 * PHASE 3 — READ-ONLY migration readiness analysis
 * ─────────────────────────────────────────────────────────────────────────
 * Given a Business, answers the question:
 *
 *   "If this Business and its Locations were converted into independent
 *    Listings, exactly what data would move, what is ambiguous, and what
 *    would require a product decision?"
 *
 * ── HARD GUARANTEE: this class performs NO writes. ──
 * It only calls read methods (counts, relationship reads). It never calls
 * save()/update()/create()/delete() or issues raw write statements. A test
 * asserts the database is byte-for-byte unchanged after analysis.
 *
 * This deliberately does NOT migrate anything. It produces a classification
 * report that a human/product decision can act on later.
 *
 * Classification buckets:
 *   A clearly_business_level — safe to retain at the parent/business identity.
 *   B clearly_location_level — can potentially move to an independent listing
 *                              (geo/address/phone/hours keyed by location_id).
 *   C shared_ambiguous       — requires an explicit migration policy.
 *   D derived_system         — should be regenerated, not copied.
 */
class ListingMigrationAnalyzer
{
    public const CLASS_BUSINESS_LEVEL = 'clearly_business_level';
    public const CLASS_LOCATION_LEVEL = 'clearly_location_level';
    public const CLASS_SHARED_AMBIGUOUS = 'shared_ambiguous';
    public const CLASS_DERIVED_SYSTEM = 'derived_system';

    /**
     * @deprecated Use {@see self::CLASS_LOCATION_LEVEL}.
     */
    public const CLASS_BRANCH_LEVEL = self::CLASS_LOCATION_LEVEL;

    /**
     * Analyse a single business. Read-only.
     *
     * @return array{
     *     business: array,
     *     candidates: array<int, array>,
     *     classification: array<string, array<int, array>>,
     *     requires_policy: array<int, string>
     * }
     */
    public function analyze(Business $business): array
    {
        $business->loadMissing([
            'owner',
            'locations.city',
            'locations.region',
            'categories',
        ]);

        $branches = $business->locations;

        // ── Candidate listings: one per location (today's natural mapping) ──
        $candidates = [];
        foreach ($branches as $branch) {
            $candidates[] = [
                'name' => $branch->name ?: $business->name,
                'type' => ListingType::BUSINESS->value,
                'source_location_id' => $branch->id,
                'location' => [
                    'city' => $branch->city?->name,
                    'region' => $branch->region?->name,
                    'address' => $branch->address,
                ],
                'is_primary' => (bool) $branch->is_primary,
            ];
        }

        $classification = [
            self::CLASS_BUSINESS_LEVEL => $this->businessLevelItems($business),
            self::CLASS_LOCATION_LEVEL => $this->locationLevelItems($business),
            self::CLASS_SHARED_AMBIGUOUS => $this->sharedAmbiguousItems($business),
            self::CLASS_DERIVED_SYSTEM => $this->derivedSystemItems($business),
        ];

        return [
            'business' => [
                'id' => $business->id,
                'name' => $business->name,
                'slug' => $business->slug,
                // PHASE 9 — listing type now lives on Listings, not Business.
                // An organization is reported as type 'business' for continuity
                // of this read-only analysis.
                'listing_type' => ListingType::BUSINESS->value,
                'status' => $business->status,
                'owner' => [
                    'id' => $business->owner?->id,
                    'name' => $business->owner?->name,
                ],
            ],
            'candidates' => $candidates,
            'classification' => $classification,
            'requires_policy' => $this->requiresPolicy(),
        ];
    }

    /**
     * A. Clearly business-level — identity that belongs to the parent.
     *
     * @return array<int, array{entity:string,count:?int,reason:string}>
     */
    protected function businessLevelItems(Business $business): array
    {
        return [
            ['entity' => 'business.identity', 'count' => 1, 'reason' => 'Parent name/slug/description/owner'],
            ['entity' => 'business.categories', 'count' => $business->categories->count(), 'reason' => 'Discovery metadata tied to the brand'],
        ];
    }

    /**
     * B. Clearly location-level — genuinely per-location records that already
     * key on location_id and can move to an independent listing.
     *
     * @return array<int, array{entity:string,count:int,reason:string}>
     */
    protected function locationLevelItems(Business $business): array
    {
        $branchIds = $business->locations->pluck('id');

        return [
            [
                'entity' => 'location.place',
                'count' => $branchIds->count(),
                'reason' => 'Geo/address/phone/whatsapp are per-location',
            ],
            [
                'entity' => 'location.hours',
                'count' => \App\Models\LocationHour::whereIn('location_id', $branchIds)->count(),
                'reason' => 'Weekly hours are keyed by location_id',
            ],
            [
                'entity' => 'location.special_hours',
                'count' => \App\Models\LocationHourOverride::whereIn('location_id', $branchIds)->count(),
                'reason' => 'Date overrides are keyed by location_id',
            ],
        ];
    }

    /**
     * C. Shared/ambiguous — currently attached to business_id and therefore
     * SHARED across branches. Moving them requires a product decision; they
     * must NOT be blindly copied to every candidate listing.
     *
     * @return array<int, array{entity:string,count:int,reason:string}>
     */
    protected function sharedAmbiguousItems(Business $business): array
    {
        foreach (['services', 'contacts', 'images', 'reviews', 'leads'] as $rel) {
            $business->loadCount($rel);
        }

        // Coupons have no direct Business relationship (they are queried via
        // Coupon::where('business_id', ...)), so count them explicitly.
        $couponCount = \App\Models\Coupon::where('business_id', $business->id)->count();

        return [
            ['entity' => 'services', 'count' => (int) ($business->services_count ?? 0), 'reason' => 'Business-wide today; per-location editability unknown'],
            ['entity' => 'contacts', 'count' => (int) ($business->contacts_count ?? 0), 'reason' => 'Could be shared or location-specific'],
            ['entity' => 'images', 'count' => (int) ($business->images_count ?? 0), 'reason' => 'Brand assets (logo/cover) vs location gallery'],
            ['entity' => 'reviews', 'count' => (int) ($business->reviews_count ?? 0), 'reason' => 'Cannot be auto-attributed to a single location'],
            ['entity' => 'leads', 'count' => (int) ($business->leads_count ?? 0), 'reason' => 'Optional branch_id; assignment needs policy'],
            ['entity' => 'coupons', 'count' => $couponCount, 'reason' => 'Business-level ownership today'],
        ];
    }

    /**
     * D. Derived / system data — regenerate rather than copy.
     *
     * @return array<int, array{entity:string,reason:string}>
     */
    protected function derivedSystemItems(Business $business): array
    {
        return [
            ['entity' => 'business_analytics', 'reason' => 'Historic aggregates — regenerate per listing'],
            ['entity' => 'business.slug', 'reason' => 'Unique key must be regenerated per listing'],
            ['entity' => 'ratings_aggregate', 'reason' => 'Derived from reviews'],
        ];
    }

    /**
     * Relationships that still require an explicit product decision.
     *
     * @return array<int, string>
     */
    protected function requiresPolicy(): array
    {
        return [
            'reviews',
            'services',
            'categories',
            'images',
            'contacts',
            'leads',
            'coupons',
            'analytics',
            'favorites',
        ];
    }
}
