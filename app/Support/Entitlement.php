<?php

namespace App\Support;

/**
 * Entitlement
 *
 * The canonical vocabulary of "capabilities" an account may or may not
 * have, based on its active plan. This is deliberately a set of STRING
 * CONSTANTS — not a hard-coded business branch — so application code asks
 * *questions* ("is this allowed?") instead of inspecting plan identity.
 *
 * ─────────────────────────────────────────────────────────────────────────
 * WHY THIS EXISTS (Phase 1)
 * ─────────────────────────────────────────────────────────────────────────
 * The Phase 0 audit found entitlement decisions scattered across:
 *   - HasPlanFeatures::canUse()/canAdd()  (string literals like 'coupons')
 *   - CheckPlanFeature / CheckPlanLimit    (route middleware args)
 *   - PlanEnforcementService               (resource names)
 *   - Business::has*Feature() accessors    (more literals)
 *
 * This class centralises the NAMES so that renaming or adding a capability
 * is a one-line change and can never drift between layers.
 *
 * ─────────────────────────────────────────────────────────────────────────
 * TWO KINDS OF ENTITLEMENT
 * ─────────────────────────────────────────────────────────────────────────
 *  1. FEATURE entitlements (boolean) — "can this account use X at all?"
 *     e.g. FEATURED_LISTING, LEAD_CAPTURE, COUPONS.
 *     Stored in `plans.features` JSON and read via Plan::hasFeature().
 *
 *  2. QUOTA entitlements (numeric) — "how many X may this account have?"
 *     e.g. CREATE_BUSINESS (count), CREATE_BRANCH (count).
 *     Stored as `plans.max_*` columns and read via Plan::getLimit().
 *
 * Both flows are exposed through a single API:
 * {@see \App\Services\EntitlementService}.
 *
 * IMPORTANT: Do NOT branch on plan names anywhere. Plans are configurable
 * product data; entitlements are the contract code depends on.
 */
final class Entitlement
{
    // ─────────────────────────────────────────────────────────────
        // QUOTA / RESOURCE entitlements (numeric limits)
    // Keys map to Plan resource names used by HasPlanFeatures + plans.max_*
    // ─────────────────────────────────────────────────────────────
    /**
     * PHASE 11 — Max number of LISTINGS owned by the account.
     *
     * A "listing" is one {@see \App\Models\Listing} row. Businesses
     * (organizations) are NOT counted here. Replaces the misleading historical
     * `businesses` key (which already counted Listings under the hood).
     */
    public const CREATE_LISTING = 'listings';

    /**
     * PHASE 11 — Max number of LOCATIONS (physical places) for the account.
     *
     * Replaces the historical `branches` key. "Branch" is no longer a domain
     * concept; Location is the universal physical-place entity.
     */
    public const CREATE_LOCATION = 'locations';

    /** Max number of services. */
    public const CREATE_SERVICE = 'services';

    /** Max number of images. */
    public const CREATE_IMAGE = 'images';

    /** Max number of coupons. */
    public const CREATE_COUPON = 'coupons';

    /** Max number of staff / team members (future team management). */
    public const ADD_STAFF = 'staff';

    // ─────────────────────────────────────────────────────────────
    // FUTURE listing-type quotas (declared now, gated later)
    // These resolve to the same resource-limit machinery when the
    // respective listing types ship.
    // ─────────────────────────────────────────────────────────────

    /** Max number of professional listings (Phase: future). */
    public const CREATE_PROFESSIONAL = 'professionals';

    /** Max number of store listings (Phase: future). */
    public const CREATE_STORE = 'stores';

    // ─────────────────────────────────────────────────────────────
    // FEATURE entitlements (boolean capabilities → plans.features JSON)
    // ─────────────────────────────────────────────────────────────

    public const FEATURED_LISTING = 'featured_listing';
    public const VERIFIED_BADGE = 'verified_badge';
    public const WHATSAPP_BUTTON = 'whatsapp_button';
    public const PHONE_DISPLAY = 'phone_display';
    public const LEAD_CAPTURE = 'lead_capture';
    public const COUPONS = 'coupons';
    public const RESPOND_TO_REVIEWS = 'respond_to_reviews';
    public const ADVANCED_ANALYTICS = 'advanced_analytics';

    /**
     * All quota/resource entitlement keys (those handled by getLimit()).
     *
     * @return array<int, string>
     */
    public static function quotaKeys(): array
    {
        return [
            self::CREATE_LISTING,
            self::CREATE_LOCATION,
            self::CREATE_SERVICE,
            self::CREATE_IMAGE,
            self::CREATE_COUPON,
                        self::ADD_STAFF,
            self::CREATE_PROFESSIONAL,
            self::CREATE_STORE,
        ];
    }

    /**
     * All boolean feature entitlement keys.
     *
     * @return array<int, string>
     */
    public static function featureKeys(): array
    {
        return [
            self::FEATURED_LISTING,
            self::VERIFIED_BADGE,
            self::WHATSAPP_BUTTON,
            self::PHONE_DISPLAY,
            self::LEAD_CAPTURE,
            self::COUPONS,
            self::RESPOND_TO_REVIEWS,
            self::ADVANCED_ANALYTICS,
        ];
    }
}
