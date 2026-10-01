<?php

namespace App\Services;

use App\Models\Business;
use App\Models\ListingImage;
use App\Models\ListingService;
use App\Models\Coupon;
use App\Models\Location;
use App\Models\Subscription;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

// Phase 3: PlanEnforcementService::summary() delegates to EntitlementService
// so there is ONE authoritative calculation for quotas / listing usage.

/**
 * PlanEnforcementService
 *
 * Enforces plan limits when an owner downgrades to a smaller plan.
 *
 * Design:
 *  - The owner's OLDEST business(es) stay public.
 *  - For branches: PRIMARY branch always stays public; remaining slots filled by OLDEST branches.
 *  - For services/images/coupons: the OLDEST N rows stay public.
 *  - Items beyond the limit get `hidden_at` set (after grace ends).
 *  - Owner can still see hidden items in their dashboard, but can only DELETE them.
 *  - Hiding is reversible: if the owner comes back under the limit, `hidden_at` is cleared.
 */
class PlanEnforcementService
{
    public function __construct(
        private readonly EntitlementService $entitlements
    ) {
    }

    /**
     * Apply enforcement to a single subscription.
     * Called by the daily `enforce:plan-limits` command.
     *
     * @return array{hidden:int, restored:int, breakdown:array}
     */
    public function enforce(Subscription $subscription): array
    {
        $breakdown = [
            'businesses' => ['limit' => 0, 'visible' => 0, 'over' => 0],
            'locations' => ['limit' => 0, 'visible' => 0, 'over' => 0],
            'services' => ['limit' => 0, 'visible' => 0, 'over' => 0],
            'images' => ['limit' => 0, 'visible' => 0, 'over' => 0],
            'coupons' => ['limit' => 0, 'visible' => 0, 'over' => 0],
        ];

        $hidden = 0;
        $restored = 0;

        if (!$subscription->plan) {
            return ['hidden' => 0, 'restored' => 0, 'breakdown' => $breakdown];
        }

        // ── Grace check ─────────────────────────────────────────────
        // If a downgrade grace period is active, don't hide anything yet.
        $graceActive = $subscription->downgrade_grace_ends_at
            && Carbon::parse($subscription->downgrade_grace_ends_at)->isFuture();

                // ── Businesses ──────────────────────────────────────────────
        // PHASE 11 — reads the renamed `max_listings` quota column.
        // NOTE: the deeper semantic flip (enforcing the quota against
        // Listing rows rather than Business rows) is Wave 1D; for now the
        // identifier is converged while behaviour is preserved.
        $maxBusinesses = (int) ($subscription->plan->max_listings ?? 0);
        if ($maxBusinesses === -1 || $maxBusinesses === 999) {
            // Unlimited — restore everything, no hiding
            $restored += $this->restoreAllForOwner($subscription->user_id, Business::class);
            $restored += $this->restoreAllBranches($subscription->user_id);
                        $restored += $this->restoreAllChildren($subscription->user_id, ListingService::class);
            $restored += $this->restoreAllChildren($subscription->user_id, ListingImage::class);
            $restored += $this->restoreAllChildren($subscription->user_id, Coupon::class);
            return ['hidden' => 0, 'restored' => $restored, 'breakdown' => $breakdown];
        }

        $businesses = Business::where('owner_id', $subscription->user_id)
            ->whereNotIn('status', ['deleted', 'rejected'])
            ->orderBy('id')     // oldest first
            ->get();

        $breakdown['businesses']['limit'] = $maxBusinesses;
        $breakdown['businesses']['visible'] = min($businesses->count(), $maxBusinesses);
        $breakdown['businesses']['over'] = max(0, $businesses->count() - $maxBusinesses);

        foreach ($businesses as $index => $business) {
            $shouldHide = $index >= $maxBusinesses;    // beyond limit → hide
            $result = $this->applyVisibility($business, $shouldHide, $graceActive);
            $hidden += $result['hidden'];
            $restored += $result['restored'];

            // If the business is visible, enforce its children too
            if (!$shouldHide) {
                $r = $this->enforceBranches($subscription, $business, $graceActive, $breakdown);
                $hidden += $r['hidden'];
                $restored += $r['restored'];

                $r = $this->enforceServices($subscription, $business, $graceActive, $breakdown);
                $hidden += $r['hidden'];
                $restored += $r['restored'];

                $r = $this->enforceImages($subscription, $business, $graceActive, $breakdown);
                $hidden += $r['hidden'];
                $restored += $r['restored'];

                $r = $this->enforceCoupons($subscription, $business, $graceActive, $breakdown);
                $hidden += $r['hidden'];
                $restored += $r['restored'];
            } else {
                // Business is hidden — hide all its children too
                $hidden += $this->hideAllChildren($business);
            }
        }

        // If no grace is active, clear the downgrade marker once enforced
        if (!$graceActive && $subscription->downgrade_grace_ends_at) {
            // Keep the marker for audit — but it has already been honoured.
        }

        return ['hidden' => $hidden, 'restored' => $restored, 'breakdown' => $breakdown];
    }

    // ================================================================
    // Business children enforcement
    // ================================================================

    private function enforceBranches(Subscription $sub, Business $business, bool $graceActive, array &$breakdown): array
    {
        $limit = (int) ($sub->plan->max_locations ?? 0);
        if ($limit === -1 || $limit === 999) {
            return $this->restoreChildren($business->locations());
        }

        $branches = $business->locations()
            ->orderByDesc('is_primary')   // primary first
            ->orderBy('id')               // then oldest
            ->get();

        $breakdown['locations']['limit'] += $limit;
        $breakdown['locations']['visible'] += min($branches->count(), $limit);
        $breakdown['locations']['over'] += max(0, $branches->count() - $limit);

        $hidden = 0;
        $restored = 0;

        foreach ($branches as $index => $branch) {
            $shouldHide = $index >= $limit;
            $result = $this->applyVisibility($branch, $shouldHide, $graceActive);
            $hidden += $result['hidden'];
            $restored += $result['restored'];
        }

        return ['hidden' => $hidden, 'restored' => $restored];
    }

        private function enforceServices(Subscription $sub, Business $business, bool $graceActive, array &$breakdown): array
    {
        // Note: plan table doesn't have max_services — using a sensible default
        $limit = (int) ($sub->plan->max_services ?? -1);

        // PHASE 11 / WAVE 1B — services are listing-owned. Resolve them across
        // the organization's listings.
        $listingIds = $business->listings()->pluck('id');
        $base = ListingService::whereIn('listing_id', $listingIds);

        if ($limit === -1 || $limit === 999 || $limit === 0) {
            return $this->restoreChildren((clone $base));
        }

        $services = (clone $base)->orderBy('id')->get();
        $breakdown['services']['limit'] += $limit;
        $breakdown['services']['visible'] += min($services->count(), $limit);
        $breakdown['services']['over'] += max(0, $services->count() - $limit);

        $hidden = 0;
        $restored = 0;
        foreach ($services as $index => $service) {
            $result = $this->applyVisibility($service, $index >= $limit, $graceActive);
            $hidden += $result['hidden'];
            $restored += $result['restored'];
        }
        return ['hidden' => $hidden, 'restored' => $restored];
    }

    private function enforceImages(Subscription $sub, Business $business, bool $graceActive, array &$breakdown): array
    {
        $limit = (int) ($sub->plan->max_images ?? 0);

        // PHASE 11 / WAVE 1B — media is listing-owned. Resolve across listings.
                $listingIds = $business->listings()->pluck('id');
        $base = ListingImage::whereIn('listing_id', $listingIds);

        if ($limit === -1 || $limit === 999) {
            return $this->restoreChildren((clone $base));
        }

        $images = (clone $base)->orderBy('sort_order')->orderBy('id')->get();

        $breakdown['images']['limit'] += $limit;
        $breakdown['images']['visible'] += min($images->count(), $limit);
        $breakdown['images']['over'] += max(0, $images->count() - $limit);

        $hidden = 0;
        $restored = 0;
        foreach ($images as $index => $image) {
            // Always keep logo + cover images public — they're brand-critical
            $isBrandAsset = in_array($image->type, [ListingImage::TYPE_LOGO, ListingImage::TYPE_COVER]);
            $shouldHide = !$isBrandAsset && $index >= $limit;
            $result = $this->applyVisibility($image, $shouldHide, $graceActive);
            $hidden += $result['hidden'];
            $restored += $result['restored'];
        }
        return ['hidden' => $hidden, 'restored' => $restored];
    }

    private function enforceCoupons(Subscription $sub, Business $business, bool $graceActive, array &$breakdown): array
    {
        $limit = (int) ($sub->plan->max_coupons ?? 0);
        if ($limit === -1 || $limit === 999) {
            return $this->restoreChildren(Coupon::where('business_id', $business->id));
        }

        $coupons = Coupon::where('business_id', $business->id)->orderBy('id')->get();
        $breakdown['coupons']['limit'] += $limit;
        $breakdown['coupons']['visible'] += min($coupons->count(), $limit);
        $breakdown['coupons']['over'] += max(0, $coupons->count() - $limit);

        $hidden = 0;
        $restored = 0;
        foreach ($coupons as $index => $coupon) {
            $result = $this->applyVisibility($coupon, $index >= $limit, $graceActive);
            $hidden += $result['hidden'];
            $restored += $result['restored'];
        }
        return ['hidden' => $hidden, 'restored' => $restored];
    }

    // ================================================================
    // Visibility helpers
    // ================================================================

    /**
     * Apply hide/restore to a single model.
     * Returns ['hidden' => 0|1, 'restored' => 0|1].
     */
    private function applyVisibility($model, bool $shouldHide, bool $graceActive): array
    {
        if ($shouldHide) {
            if ($graceActive) {
                return ['hidden' => 0, 'restored' => 0];
            }
            if ($model->hidden_at === null) {
                $saved = $model->update(['hidden_at' => now()]);
                return $saved
                    ? ['hidden' => 1, 'restored' => 0]
                    : ['hidden' => 0, 'restored' => 0];
            }
            return ['hidden' => 0, 'restored' => 0];
        }

        if ($model->hidden_at !== null) {
            $saved = $model->update(['hidden_at' => null]);
            return $saved
                ? ['hidden' => 0, 'restored' => 1]
                : ['hidden' => 0, 'restored' => 0];
        }
        return ['hidden' => 0, 'restored' => 0];
    }

    private function restoreChildren($query): array
    {
        $count = $query->whereNotNull('hidden_at')->update(['hidden_at' => null]);
        return ['hidden' => 0, 'restored' => $count];
    }

    private function restoreAllForOwner(int $ownerId, string $modelClass): int
    {
        return $modelClass::where('owner_id', $ownerId)
            ->whereNotNull('hidden_at')
            ->update(['hidden_at' => null]);
    }

        private function restoreAllBranches(int $ownerId): int
    {
        return DB::table('locations')
            ->join('businesses', 'businesses.id', '=', 'locations.business_id')
            ->where('businesses.owner_id', $ownerId)
            ->whereNotNull('locations.hidden_at')
            ->update(['locations.hidden_at' => null]);
    }

        private function restoreAllChildren(int $ownerId, string $modelClass): int
    {
        // PHASE 11 / WAVE 1B — children are listing-owned (`listing_id`). Resolve
        // the owner's children by joining through `listings` (which carries the
        // organization's `business_id`) and `businesses`.
        $table = (new $modelClass)->getTable();
        return DB::table($table)
            ->join('listings', 'listings.id', '=', "{$table}.listing_id")
            ->join('businesses', 'businesses.id', '=', 'listings.business_id')
            ->where('businesses.owner_id', $ownerId)
            ->whereNotNull("{$table}.hidden_at")
            ->update(["{$table}.hidden_at" => null]);
    }

        private function hideAllChildren(Business $business): int
    {
        // PHASE 11 / WAVE 1B — children are listing-owned. Resolve the
        // organization's listings, then hide their children directly.
        $listingIds = $business->listings()->pluck('id');

        $count = 0;
        $count += $business->locations()->whereNull('hidden_at')->update(['hidden_at' => now()]);
        $count += ListingService::whereIn('listing_id', $listingIds)
            ->whereNull('hidden_at')->update(['hidden_at' => now()]);
        $count += ListingImage::whereIn('listing_id', $listingIds)
            ->whereNull('hidden_at')->update(['hidden_at' => now()]);
        $count += Coupon::where('business_id', $business->id)->whereNull('hidden_at')->update(['hidden_at' => now()]);
        return $count;
    }

    /**
     * Read-only entitlement / over-quota summary for the account that owns
     * this subscription (Phase 3 §7).
     *
     * Delegates to EntitlementService so there is exactly ONE authoritative
     * calculation of quotas, listing usage, allowed listing types and
     * over-quota state — no duplicated math. Performs NO writes.
     *
     * @return array<string, mixed>
     */
    public function summary(Subscription $subscription): array
    {
        if (!$subscription->user) {
            return [];
        }

        return $this->entitlements->summary($subscription->user);
    }
}