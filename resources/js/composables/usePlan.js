import { computed } from 'vue';
import { usePage } from '@inertiajs/vue3';

/**
 * WAVE A1.1 - THE FRONTEND UNLIMITED-LIMIT POLICY.
 *
 * This mirrors the backend policy established in Wave A1 so the two can never
 * disagree about the same plan:
 *
 *   -1   unlimited            (the canonical sentinel)
 *   999  unlimited            (legacy alias - accepted so a plan is never shown
 *                             as finite on one page and unlimited on another)
 *   null NOT unlimited        (unconfigured; the backend columns are NOT NULL,
 *                             so null means "not loaded" and must fail CLOSED)
 *   0    a zero allowance     (finite, not unlimited)
 *   n>0  a finite limit
 *
 * The backend equivalent is App\Models\Plan::isUnlimitedLimit().
 */
export const UNLIMITED = -1;
export const LEGACY_UNLIMITED = 999;

export function isUnlimitedLimit(limit) {
    if (limit === null || limit === undefined || limit === '') return false;
    const n = Number(limit);
    if (Number.isNaN(n)) return false;
    return n === UNLIMITED || n === LEGACY_UNLIMITED;
}

/**
 * Render a limit for display. Unlimited renders as the established infinity
 * glyph; an unconfigured limit renders as an em dash rather than a number, so
 * "not configured" is never mistaken for a zero allowance.
 */
export function formatLimit(limit) {
    if (isUnlimitedLimit(limit)) return '∞';
    if (limit === null || limit === undefined || limit === '') return '—';
    return String(limit);
}

export function usePlan() {
    const page = usePage();
    const user = computed(() => page.props.auth?.user);
    const plan = computed(() => page.props.auth?.plan);
    const usage = computed(() => page.props.auth?.usage || {});

    const can = (feature) => {
        if (!plan.value) return false;
        const features = plan.value.features || {};
        return !!features[feature];
    };

    const canAdd = (resource) => {
        if (!plan.value) return false;
        const limits = {
            // PHASE 11 / WAVE 1D-4 — a Business is an optional organization and
            // is NOT a quota unit, so there is no `businesses` limit.
            locations: plan.value.max_locations,
            services: plan.value.max_services,
            images: plan.value.max_images,
            coupons: plan.value.max_coupons,
        };
        const limit = limits[resource];
        // WAVE A1.1 - isUnlimitedLimit() (not `-1 || null`): an unconfigured
        // limit must fail CLOSED exactly as the backend does.
        if (isUnlimitedLimit(limit)) return true;
        if (limit === null || limit === undefined) return false;
        const current = usage.value[resource] || 0;
        return current < limit;
    };

    const remaining = (resource) => {
        if (!plan.value) return 0;
        const limits = {
            // PHASE 11 / WAVE 1D-4 — a Business is an optional organization and
            // is NOT a quota unit, so there is no `businesses` limit.
            locations: plan.value.max_locations,
            services: plan.value.max_services,
            images: plan.value.max_images,
            coupons: plan.value.max_coupons,
        };
        const limit = limits[resource];
        // WAVE A1.1 - -1 signals unlimited, matching the backend's remaining().
        if (isUnlimitedLimit(limit)) return -1;
        if (limit === null || limit === undefined) return 0;
        const current = usage.value[resource] || 0;
        return Math.max(0, limit - current);
    };

    const tier = computed(() => plan.value?.tier || 'free');
    const isAtLeast = (t) => {
        const tiers = { free: 0, starter: 1, growth: 2, premium: 3 };
        return (tiers[tier.value] || 0) >= (tiers[t] || 0);
    };

    const isFree = computed(() => tier.value === 'free');
    const isStarter = computed(() => tier.value === 'starter');
    const isGrowth = computed(() => tier.value === 'growth');
    const isPremium = computed(() => tier.value === 'premium');

    return {
        plan,
        usage,
        tier,
        isFree,
        isStarter,
        isGrowth,
        isPremium,
        can,
        canAdd,
        remaining,
        isAtLeast,
        isUnlimitedLimit,
        formatLimit,
    };
}