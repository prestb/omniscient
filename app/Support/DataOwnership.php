<?php

namespace App\Support;

/**
 * DataOwnership
 *
 * Phase 4 — the canonical, machine-readable encoding of the data ownership
 * matrix documented in docs/PHASE_4_DATA_OWNERSHIP_POLICY.md.
 *
 * Each entity that hangs off a Business/Location is classified into exactly
 * one ownership class. This is PURE metadata (no DB access) so it can be
 * asserted in tests and reused by the read-only analyzer / future migration
 * tooling.
 *
 * Ownership classes (per Phase 4 §3):
 *   A  LISTING_OWNED       → belongs to the resulting independent listing.
 *   B  LOCATION_OWNED      → belongs to a physical/service location.
 *   C  ACCOUNT_OWNED       → belongs to the account/user, not a listing.
 *   D  SHARED_AMBIGUOUS    → schema cannot safely determine the owner.
 *   E  DERIVED_SYSTEM      → regenerate, do not copy.
 *   F  LEGACY_ADMIN        → keep for history; not active listing data.
 */
final class DataOwnership
{
    public const LISTING_OWNED = 'listing_owned';       // A
    public const LOCATION_OWNED = 'location_owned';     // B

    /**
     * @deprecated Use {@see self::LOCATION_OWNED}. Retained so any external
     * callers referencing the old "branch" terminology keep working.
     */
    public const BRANCH_OWNED = self::LOCATION_OWNED;   // B (alias)
    public const ACCOUNT_OWNED = 'account_owned';       // C
    public const SHARED_AMBIGUOUS = 'shared_ambiguous'; // D
    public const DERIVED_SYSTEM = 'derived_system';     // E
    public const LEGACY_ADMIN = 'legacy_admin';         // F

    /**
     * Canonical ownership matrix, keyed by a stable entity identifier.
     *
     * @return array<string, array{class:string,ambiguity:string,target:string}>
     */
    public static function matrix(): array
    {
        return [
            // ── Business identity → listing ──────────────────────────
            'business.identity' => [
                'class' => self::LISTING_OWNED,
                'ambiguity' => 'low',
                'target' => 'resulting listing identity',
            ],
            'business.slug' => [
                'class' => self::DERIVED_SYSTEM,
                'ambiguity' => 'low',
                'target' => 'organization slug (Listing has its own slug)',
            ],
            'listing.type' => [
                'class' => self::LISTING_OWNED,
                'ambiguity' => 'low',
                'target' => 'listings.type (canonical type axis)',
            ],

            // ── Location / hours → move with the physical place ──────
            'location.place' => [
                'class' => self::LOCATION_OWNED,
                'ambiguity' => 'low',
                'target' => 'listing location',
            ],
            'location.hours' => [
                'class' => self::LOCATION_OWNED,
                'ambiguity' => 'low',
                'target' => 'listing weekly hours',
            ],
            'location.special_hours' => [
                'class' => self::LOCATION_OWNED,
                'ambiguity' => 'low',
                'target' => 'listing date overrides',
            ],

            // ── Ambiguous business-level children ────────────────────
            'services' => [
                'class' => self::SHARED_AMBIGUOUS,
                'ambiguity' => 'high',
                'target' => 'requires policy',
            ],
            'contacts' => [
                'class' => self::SHARED_AMBIGUOUS,
                'ambiguity' => 'high',
                'target' => 'requires policy',
            ],
            'images' => [
                'class' => self::SHARED_AMBIGUOUS,
                'ambiguity' => 'medium',
                'target' => 'requires policy',
            ],
            'categories' => [
                'class' => self::SHARED_AMBIGUOUS,
                'ambiguity' => 'medium',
                'target' => 'requires policy',
            ],
            'reviews' => [
                'class' => self::SHARED_AMBIGUOUS,
                'ambiguity' => 'high',
                'target' => 'requires policy (never duplicate)',
            ],
            'leads' => [
                'class' => self::SHARED_AMBIGUOUS,
                'ambiguity' => 'high',
                'target' => 'listing_id if present, else historical',
            ],
            'coupons' => [
                'class' => self::SHARED_AMBIGUOUS,
                'ambiguity' => 'medium',
                'target' => 'requires policy',
            ],
            'favorites' => [
                'class' => self::SHARED_AMBIGUOUS,
                'ambiguity' => 'high',
                'target' => 'identifier continuity',
            ],

            // ── Derived / system ─────────────────────────────────────
            'business_analytics' => [
                'class' => self::DERIVED_SYSTEM,
                'ambiguity' => 'high',
                'target' => 'regenerate; keep historical baseline',
            ],
            'ratings_aggregate' => [
                'class' => self::DERIVED_SYSTEM,
                'ambiguity' => 'low',
                'target' => 'derived from reviews',
            ],
            'verification_badge' => [
                'class' => self::DERIVED_SYSTEM,
                'ambiguity' => 'low',
                'target' => 'plan/feature flag (account-derived)',
            ],

            // ── Legacy / audit ───────────────────────────────────────
            'coupon_redemptions' => [
                'class' => self::LEGACY_ADMIN,
                'ambiguity' => 'low',
                'target' => 'immutable audit trail',
            ],

            // ── Account-owned (never a listing child) ────────────────
            'subscriptions' => [
                'class' => self::ACCOUNT_OWNED,
                'ambiguity' => 'low',
                'target' => 'account (unchanged)',
            ],
            'owner' => [
                'class' => self::ACCOUNT_OWNED,
                'ambiguity' => 'low',
                'target' => 'account (unchanged)',
            ],
        ];
    }

    /**
     * Phase 5 — entities whose ownership was AMBIGUOUS in Phase 4 but now have
     * a definitive canonical policy (see {@see ListingOwnership}).
     *
     * The ownership CLASS (A–F) does not change — a review is still not
     * "listing-owned" by schema — but the *resolution* is now decided, so it
     * no longer blocks a future migration as an unknown.
     *
     * @return array<int, string>
     */
    public static function resolvedByPolicy(): array
    {
        return [
            'reviews',
            'services',
            'categories',
            'images',
            'contacts',
            'leads',
            'coupons',
            'favorites',
            'business_analytics',
        ];
    }

    /**
     * Whether a Phase 4 ambiguous entity now has a Phase 5 canonical policy.
     */
    public static function isResolved(string $entity): bool
    {
        return in_array($entity, self::resolvedByPolicy(), true);
    }

    /**
     * PHASE 9 — child tables that now carry a `listing_id`, i.e. the
     * discoverable-child entities a Listing can own. This records the
     * STRUCTURE that exists after the Listing core; it does NOT assert that
     * every row has been re-attributed.
     *
     * @return array<int, string>
     */
    public static function listingScopedTables(): array
    {
        return [
            'business_services',
            'business_images',
            'business_contacts',
            'reviews',
            'business_analytics',
            'leads',
            'coupons',
            'favorites',
        ];
    }

    /**
     * Ambiguous entities that STILL have no decided policy (should be empty
     * after Phase 5 — kept so regressions are detectable).
     *
     * @return array<int, string>
     */
    public static function unresolvedAmbiguousEntities(): array
    {
        return array_values(array_filter(
            self::ambiguousEntities(),
            fn($e) => !self::isResolved($e)
        ));
    }

    /**
     * Ownership class for one entity, or null if unknown.
     */
    public static function classOf(string $entity): ?string
    {
        return self::matrix()[$entity]['class'] ?? null;
    }

    /**
     * Ambiguity level ('low'|'medium'|'high') for one entity.
     */
    public static function ambiguityOf(string $entity): ?string
    {
        return self::matrix()[$entity]['ambiguity'] ?? null;
    }

    /**
     * Every entity whose ownership is not safely inferable (shared/ambiguous
     * or derived with high ambiguity) — i.e. it blocks a clean migration.
     *
     * @return array<int, string>
     */
    public static function ambiguousEntities(): array
    {
        return array_keys(array_filter(
            self::matrix(),
            fn($m) => $m['class'] === self::SHARED_AMBIGUOUS
        ));
    }

    /**
     * Entities safe to move with a physical Location into a listing (class B).
     *
     * @return array<int, string>
     */
    public static function locationOwnedEntities(): array
    {
        return array_keys(array_filter(
            self::matrix(),
            fn($m) => $m['class'] === self::LOCATION_OWNED
        ));
    }

    /**
     * @deprecated Use {@see self::locationOwnedEntities()}.
     *
     * @return array<int, string>
     */
    public static function branchOwnedEntities(): array
    {
        return self::locationOwnedEntities();
    }

    /**
     * Account-owned entities that must NEVER be re-parented to a listing.
     *
     * @return array<int, string>
     */
    public static function accountOwnedEntities(): array
    {
        return array_keys(array_filter(
            self::matrix(),
            fn($m) => $m['class'] === self::ACCOUNT_OWNED
        ));
    }
}
