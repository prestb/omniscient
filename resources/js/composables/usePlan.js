import { computed } from 'vue';
import { usePage } from '@inertiajs/vue3';

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
            businesses: plan.value.max_businesses,
            branches: plan.value.max_branches,
            services: plan.value.max_services,
            images: plan.value.max_images,
            coupons: plan.value.max_coupons,
        };
        const limit = limits[resource];
        if (limit === -1 || limit === null) return true;
        const current = usage.value[resource] || 0;
        return current < limit;
    };

    const remaining = (resource) => {
        if (!plan.value) return 0;
        const limits = {
            businesses: plan.value.max_businesses,
            branches: plan.value.max_branches,
            services: plan.value.max_services,
            images: plan.value.max_images,
            coupons: plan.value.max_coupons,
        };
        const limit = limits[resource];
        if (limit === -1 || limit === null) return -1;
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
    };
}