<?php

namespace App\Traits;

use App\Models\Plan;

trait HasPlanFeatures
{
    /**
     * Check if user has a specific feature
     */
    public function canUse(string $feature): bool
    {
        $plan = $this->getCurrentPlan();

        // No plan = free plan features
        if (!$plan) {
            return false;
        }

        return $plan->hasFeature($feature);
    }

    /**
     * Check if user can add more of a resource
     */
    public function canAdd(string $resource): bool
    {
        $plan = $this->getCurrentPlan();

        if (!$plan) {
            return false;
        }

        // Unlimited
        if ($plan->isUnlimited($resource)) {
            return true;
        }

        $limit = $plan->getLimit($resource);
        $current = $this->getCurrentUsage($resource);

        return $current < $limit;
    }

    /**
     * Get remaining quota for a resource
     */
    public function remaining(string $resource): int
    {
        $plan = $this->getCurrentPlan();

        if (!$plan || $plan->isUnlimited($resource)) {
            return -1; // unlimited
        }

        $limit = $plan->getLimit($resource);
        $current = $this->getCurrentUsage($resource);

        return max(0, $limit - $current);
    }

    /**
     * Get current usage for a resource
     */
        public function getCurrentUsage(string $resource): int
    {
                return match ($resource) {
            // PHASE 9/11 — the LISTING quota counts Listing records, the
            // canonical discoverable entity. Businesses are organizations and
            // are NOT counted against listing quota.
            'listings' => \App\Models\Listing::countFor($this),
            'locations' => $this->getLocationsCount(),
            'services' => $this->getServicesCount(),
            'images' => $this->getImagesCount(),
            'coupons' => method_exists($this, 'coupons') ? $this->coupons()->count() : 0,
            default => 0,
        };
    }

                protected function getLocationsCount(): int
    {
        // belonging to the account's organizations.
        // PHASE 22A - Locations are ACCOUNT-owned. Counting them through the
        // account's Business ids made a Business-less Professional's own
        // Locations invisible to the quota, so it could exceed the limit.
        return \App\Models\Location::where('owner_id', $this->id)->count();
    }

        protected function getServicesCount(): int
    {
        if (!method_exists($this, 'listings'))
            return 0;
        return \App\Models\ListingService::whereIn(
            'listing_id',
            $this->listings()->pluck('id')
        )->count();
    }

    protected function getImagesCount(): int
    {
        if (!method_exists($this, 'listings'))
            return 0;
        return \App\Models\ListingImage::whereIn(
            'listing_id',
            $this->listings()->pluck('id')
        )->count();
    }

    /**
     * Get the current plan
     */
    public function getCurrentPlan(): ?Plan
    {
        // Active subscription (now user-based)
        $subscription = $this->active_subscription;
        if ($subscription && $subscription->plan) {
            return $subscription->plan;
        }

        // Fallback to free plan
        return Plan::where('tier', 'free')->first();
    }

    /**
     * Get the plan tier name
     */
    public function getPlanTier(): string
    {
        $plan = $this->getCurrentPlan();
        return $plan?->tier ?? 'free';
    }

    /**
     * Check if user is on a specific tier or higher
     */
    public function isAtLeast(string $tier): bool
    {
        $tiers = ['free' => 0, 'starter' => 1, 'growth' => 2, 'premium' => 3];
        $userTier = $this->getPlanTier();

        return ($tiers[$userTier] ?? 0) >= ($tiers[$tier] ?? 0);
    }
}