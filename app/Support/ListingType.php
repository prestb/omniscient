<?php

namespace App\Support;

/**
 * ListingType
 *
 * The canonical, extensible taxonomy of public-facing "listings" in
 * Omniscient. A listing is any public, discoverable presence owned by an
 * account (e.g. a business, a professional, a store).
 *
 * ─────────────────────────────────────────────────────────────────────────
 * PHASE 1 NOTE (architecture only — no data migration performed)
 * ─────────────────────────────────────────────────────────────────────────
 * The current implementation physically stores only BUSINESS listings
 * (the `businesses` table, plus its `branches`). This enum establishes the
 * TYPE axis of the future common Listing model *without* requiring a
 * destructive migration now.
 *
 * The existing `Business` entity is treated as the *first concrete
 * implementation* of the Listing concept — see the Phase 1 report
 * (Option C recommendation). Professional and Store will be added later as
 * additional type values backed by specialized metadata, NOT as three
 * independent systems.
 *
 * Persistence: the `value` is a stable, URL/DB-safe string. When a
 * `listing_type` column is eventually introduced, existing Business rows
 * backfill to {@see ListingType::BUSINESS}.
 */
enum ListingType: string
{
    /** A service provider / company with one or more physical locations. */
    case BUSINESS = 'business';

    /** An individual offering services (e.g. a freelancer, tradesperson). */
    case PROFESSIONAL = 'professional';

    /** A retail presence with products (future; no e-commerce yet). */
    case STORE = 'store';

    /**
     * CONTRACT B (reconciled) - every declared Listing type is SHIPPABLE.
     *
     * Business, Professional and Store are all backed by the same concrete
     * `listings` entity: the type is persisted, validated, editable, indexed in
     * Meilisearch as a filterable attribute, filterable in admin, rendered on the
     * public page and covered by the Listing journey tests.
     *
     * This previously returned only BUSINESS and described the others as
     * "design-only in Phase 1". That was stale metadata which contradicted the
     * shipped product, and it is corrected here rather than acted upon.
     *
     * @return array<int, self>
     */
    public static function implemented(): array
    {
        return [self::BUSINESS, self::PROFESSIONAL, self::STORE];
    }

    /**
     * Human-readable label (UI-safe).
     */
    public function label(): string
    {
        return match ($this) {
            self::BUSINESS => 'Business',
            self::PROFESSIONAL => 'Professional',
            self::STORE => 'Store',
        };
    }

    /**
     * Whether this type has a concrete implementation today.
     */
    public function isImplemented(): bool
    {
        return in_array($this, self::implemented(), true);
    }

    /**
     * Resolve a listing type from a raw stored value, tolerating null or an
     * unknown string by falling back to the canonical initial type
     * (BUSINESS). Used by the `businesses.listing_type` enum cast so legacy
     * rows created before Phase 2 never throw.
     *
     * @param  string|\App\Support\ListingType|null  $value
     */
    public static function fromStored(string|self|null $value): self
    {
        if ($value instanceof self) {
            return $value;
        }

        if ($value === null || $value === '') {
            return self::BUSINESS;
        }

        return self::tryFrom($value) ?? self::BUSINESS;
    }

    /**
     * The entitlement key that gates *creating* a listing of this type.
     * Keeps type→entitlement mapping in ONE place so code never needs
     * `if ($plan === 'premium')` style branching.
     */
        public function creationEntitlement(): string
    {
        return match ($this) {
            // PHASE 11 — the type-agnostic LISTING quota (was `businesses`).
            self::BUSINESS => Entitlement::CREATE_LISTING,
            self::PROFESSIONAL => Entitlement::CREATE_PROFESSIONAL,
            self::STORE => Entitlement::CREATE_STORE,
        };
    }

    /**
     * CONTRACT B (reconciled) - Listing-type availability is NOT restricted by a
     * separate plan entitlement. Every type is allowed when the plan's `features`
     * JSON does not mention it.
     *
     * What actually governs whether an account may create a Listing is the
     * account-level LISTING QUOTA (`plan.max_listings`, enforced by
     * `canAdd('listings')` and the `plan.limit:listings` route middleware on
     * creation). Type access is not a second, independent gate.
     *
     * This previously returned `$this === self::BUSINESS`, so an absent flag
     * denied Professional and Store - a rule the shipped product never applied,
     * because nothing in the application consults this method for authorization.
     * Returning `true` keeps the metadata consistent with Contract B and ensures
     * the mechanism cannot contradict it if it is ever wired up.
     */
    public function allowedByDefault(): bool
    {
        return true;
    }
}
