<?php

namespace App\Services;

use App\Models\User;
use App\Support\Entitlement;
use App\Support\ListingType;

/**
 * EntitlementService
 *
 * The single canonical API through which the rest of the application asks
 * entitlement questions. This is the "Entitlement layer" and "Usage layer"
 * described in the Phase 1 responsibility model:
 *
 *   Entitlement layer  → can canUse() / canAdd() / canCreate(listingType)
 *   Usage layer        → usage() / remaining() / limit()
 *
 * It intentionally DELEGATES to the existing `HasPlanFeatures` trait so the
 * current behaviour is preserved exactly — no behaviour change in Phase 1.
 * The value it adds is:
 *
 *   1. A stable, injectable, testable façade (the trait stays as the
 *      implementation detail on User).
 *   2. Entitlement checks expressed as *questions* using the Entitlement
 *      vocabulary, so no code needs to know a plan's name.
 *   3. A listing-type aware entry point (canCreate(ListingType::BUSINESS)),
 *      which is how Professional and Store will be gated when they ship.
 *
 * Controllers and policies should depend on THIS, not on the trait directly.
 */
class EntitlementService
{
    /**
     * Can this account use a boolean feature entitlement?
     */
    public function canUse(User $user, string $feature): bool
    {
        return $user->canUse($feature);
    }

    /**
     * Can this account add one more unit of a quota resource?
     */
    public function canAdd(User $user, string $entitlement): bool
    {
        return $user->canAdd($entitlement);
    }

    /**
     * Can this account create another listing of the given type?
     *
     * This is the future-proof entry point. For BUSINESS it maps to the
     * `listings` quota; for PROFESSIONAL / STORE it will map to their own
     * quotas once implemented.
     *
     * NOTE (Phase 3): this is the QUOTA half of the decision only. The full
     * authoritative answer — quota AND type-allowed — is
     * {@see canCreateListingType()}. Keep the two concepts separate (§5).
     */
    public function canCreate(User $user, ListingType $type): bool
    {
        return $this->canAdd($user, $type->creationEntitlement());
    }

    /**
     * Does the account's plan ALLOW this listing type at all?
     *
     * This is the TYPE half of the decision (independent of quota). It reads
     * the plan's `features` JSON by the type's canonical entitlement key and
     * falls back to the type's `allowedByDefault()` when the key is absent
     * (see ListingType for the business back-compatibility rule).
     *
     * No plan names are inspected — only the entitlement contract.
     */
    public function allowsListingType(User $user, ListingType $type): bool
    {
        $plan = $user->getCurrentPlan();

        if (!$plan) {
            return false;
        }

        $key = $type->creationEntitlement();
        $features = $plan->features ?? [];

        if (!array_key_exists($key, $features)) {
            return $type->allowedByDefault();
        }

        return !empty($features[$key]);
    }

    /**
     * THE authoritative answer: may this account create a listing of this
     * type right now?
     *
     * Composes the two orthogonal rules and is the ONLY place they are
     * combined. Prefer this over calling canCreate()/allowsListingType()
     * individually when a controller needs a yes/no gate.
     */
    public function canCreateListingType(User $user, ListingType $type): bool
    {
        if ($user->isSuperAdmin()) {
            return true;
        }

        return $this->allowsListingType($user, $type)
            && $this->canCreate($user, $type);
    }

    /**
     * The single authoritative LISTING usage calculation (Phase 9 §24).
     *
     * A "listing" is one {@see \App\Models\Listing} row. Businesses
     * (organizations) and Locations are NOT listings and are never counted
     * here. The quota is measured against the account's plan via the
     * `listings` entitlement key (backed by Listing::countFor()).
     *
     * @return array{type:string,current:int,limit:int,remaining:int,unlimited:bool,over_quota:bool}
     */
    public function listingQuota(User $user): array
    {
        $type = ListingType::BUSINESS;
        $entitlement = $type->creationEntitlement();

        $limit = $this->limit($user, $entitlement);
        $current = $this->usage($user, $entitlement);
        $unlimited = ($limit === -1 || $limit === 999);

        return [
            'type' => $type->value,
            'current' => $current,
            'limit' => $limit,
            'remaining' => $unlimited ? -1 : max(0, $limit - $current),
            'unlimited' => $unlimited,
            'over_quota' => !$unlimited && $current > $limit,
        ];
    }

    /**
     * Which listing TYPES may this account create (the type axis only).
     *
     * @return array<string,bool>  keyed by ListingType::value
     */
    public function allowedListingTypes(User $user): array
    {
        $out = [];

        foreach (ListingType::cases() as $type) {
            $out[$type->value] = $this->allowsListingType($user, $type);
        }

        return $out;
    }

    /**
     * A read-only over-quota report (no writes). Mirrors the shape the
     * dashboard/enforcement layer consumes, computed from the single
     * authoritative listingQuota() + the account's type allowances.
     *
     * @return array{is_over:bool,grace_active:bool,grace_ends_at:?string,listing_quota:array,allowed_listing_types:array}
     */
    public function overQuotaReport(User $user): array
    {
        $subscription = $user->active_subscription;
        $graceEndsAt = $subscription?->downgrade_grace_ends_at;
        $graceActive = $graceEndsAt && \Carbon\Carbon::parse($graceEndsAt)->isFuture();

        $quota = $this->listingQuota($user);

        return [
            'is_over' => $quota['over_quota'],
            'grace_active' => (bool) $graceActive,
            'grace_ends_at' => $graceEndsAt ? \Carbon\Carbon::parse($graceEndsAt)->toDateString() : null,
            'listing_quota' => $quota,
            'allowed_listing_types' => $this->allowedListingTypes($user),
        ];
    }

    /**
     * Current usage count for a quota entitlement.
     */
    public function usage(User $user, string $entitlement): int
    {
        return $user->getCurrentUsage($entitlement);
    }

    /**
     * Remaining quota for an entitlement (-1 = unlimited).
     */
    public function remaining(User $user, string $entitlement): int
    {
        return $user->remaining($entitlement);
    }

    /**
     * The configured limit for an entitlement on the account's current plan.
     */
    public function limit(User $user, string $entitlement): int
    {
        $plan = $user->getCurrentPlan();
        return $plan ? $plan->getLimit($entitlement) : 0;
    }

    /**
     * Snapshot of all quota entitlements for dashboards / usage pages.
     *
     * Phase 3 (additive): the existing numeric-keyed quota entries are
     * PRESERVED unchanged so no caller breaks. Three listing-domain keys are
     * added alongside them:
     *
     *   'listing_quota'          → the single authoritative listing usage
     *   'allowed_listing_types'  → which types the plan permits
     *   'over_quota'             → read-only over-quota + grace report
     *
     * @return array<string, mixed>
     */
    public function summary(User $user): array
    {
        $out = [];

        foreach (Entitlement::quotaKeys() as $key) {
            // Only surface resources the account actually models today.
            if (
                !in_array($key, [
                    Entitlement::CREATE_LISTING,
                    Entitlement::CREATE_LOCATION,
                    Entitlement::CREATE_SERVICE,
                    Entitlement::CREATE_IMAGE,
                    Entitlement::CREATE_COUPON
                ], true)
            ) {
                continue;
            }

            $limit = $this->limit($user, $key);
            $out[$key] = [
                'current' => $this->usage($user, $key),
                'limit' => $limit,
                'remaining' => $this->remaining($user, $key),
                'unlimited' => ($limit === -1 || $limit === 999),
            ];
        }

        // ── Phase 3 additions (Listing-domain awareness) ──────────────
        $out['listing_quota'] = $this->listingQuota($user);
        $out['allowed_listing_types'] = $this->allowedListingTypes($user);
        $out['over_quota'] = $this->overQuotaReport($user);

        return $out;
    }
}
