<!-- resources/js/Pages/Owner/Coupons/Index.vue -->
<template>
    <AuthenticatedLayout>
        <!-- COMPACT HEADER -->
        <PageHeader color="purple" :breadcrumb="[
            { label: 'Dashboard', href: '/owner/dashboard' },
            { label: 'Coupons' }
        ]">
            <template #icon>
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                    d="M15 5v2m0 4v2m0 4v2M5 5a2 2 0 00-2 2v3a2 2 0 110 4v3a2 2 0 002 2h14a2 2 0 002-2v-3a2 2 0 110-4V7a2 2 0 00-2-2H5z" />
            </template>
            <template #title>Coupons & deals</template>
            <template #subtitle>Create coupons to attract more customers</template>
            <template #actions>
                <!-- ✅ Scan Coupon -->
                <button type="button" @click="showScanner = true"
                    class="inline-flex items-center gap-2 px-4 py-2 border border-gray-300 dark:border-gray-600 text-gray-700 dark:text-gray-300 rounded-xl hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors text-sm font-semibold">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M12 4v1m6 11h2m-6 0h-2v4m0-11v3m0 0h.01M12 12h4.01M16 20h4M4 12h4m12 0h.01M5 8h2a1 1 0 001-1V5a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1zm14 0h2a1 1 0 001-1V5a1 1 0 00-1-1h-2a1 1 0 00-1 1v2a1 1 0 001 1zM5 20h2a1 1 0 001-1v-2a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1z" />
                    </svg>
                    Scan Coupon
                </button>

                <LockedFeature feature="coupons" feature-label="Coupon Creation" required-plan="Starter" :benefits="[
                    'Attract more customers',
                    'Boost repeat visits',
                    'Track redemptions'
                ]">
                    <a href="/owner/coupons/create" :class="[
                        'inline-flex items-center gap-2 px-4 py-2 rounded-xl font-semibold transition-all text-sm',
                        canCreate
                            ? 'bg-gradient-to-r from-primary-600 to-primary-700 text-white hover:from-primary-700 hover:to-primary-800 shadow-lg shadow-primary-500/25'
                            : 'bg-gray-200 dark:bg-gray-700 text-gray-500 dark:text-gray-400 cursor-not-allowed'
                    ]">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                        </svg>
                        New Coupon
                    </a>
                </LockedFeature>
            </template>
        </PageHeader>

        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8 space-y-6">

            <!-- LIMIT WARNING -->
            <div v-if="showLimitWarning"
                class="bg-gradient-to-br from-amber-50 to-orange-50 dark:from-amber-900/20 dark:to-orange-900/20 border border-amber-200 dark:border-amber-800 rounded-2xl p-5 flex items-start gap-4">
                <div
                    class="w-11 h-11 rounded-xl bg-amber-100 dark:bg-amber-900/40 flex items-center justify-center flex-shrink-0">
                    <svg class="w-5 h-5 text-amber-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                    </svg>
                </div>
                <div class="flex-1">
                    <p class="text-sm font-bold text-amber-900 dark:text-amber-300">
                        {{ remaining === 0 ? "You've reached your coupon limit" : "You're running low on coupons" }}
                    </p>
                    <p class="text-sm text-amber-700 dark:text-amber-400 mt-0.5">
                        {{ remaining === 0 ? 'Upgrade to create more coupons' : `Only ${remaining} coupon${remaining ===
                            1 ? ''
                            : 's'} remaining` }}
                    </p>
                </div>
                <a href="/owner/subscription/renew"
                    class="flex-shrink-0 px-4 py-2 bg-amber-600 text-white rounded-xl text-xs font-bold hover:bg-amber-700 transition-colors whitespace-nowrap tracking-wide uppercase">
                    Upgrade
                </a>
            </div>

            <!-- EMPTY STATE -->
            <div v-if="!isLocked && (!coupons?.data || coupons.data.length === 0)"
                class="bg-white dark:bg-gray-800 rounded-2xl shadow-sm border border-gray-100 dark:border-gray-700 p-12 text-center">
                <div
                    class="w-20 h-20 bg-gradient-to-br from-purple-100 to-purple-200 dark:from-purple-900/40 dark:to-purple-900/20 rounded-2xl flex items-center justify-center mx-auto mb-5 shadow-sm">
                    <span class="text-4xl">🎟️</span>
                </div>
                <h3 class="text-xl font-bold text-gray-900 dark:text-white tracking-tight mb-2">No coupons yet</h3>
                <p class="text-gray-500 dark:text-gray-400 text-sm max-w-md mx-auto mb-6">
                    Create your first coupon to start attracting customers.
                </p>
                <a href="/owner/coupons/create"
                    class="inline-flex items-center gap-2 px-6 py-3 bg-gradient-to-r from-primary-600 to-primary-700 text-white rounded-xl hover:from-primary-700 hover:to-primary-800 transition-all shadow-lg shadow-primary-500/25 hover:shadow-primary-500/40 hover:-translate-y-0.5 font-semibold text-sm">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                    </svg>
                    Create Coupon
                </a>
            </div>

            <!-- COUPONS GRID -->
            <div v-else class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-5">
                <!-- ⬇️ SWIPE: wrap each coupon card in SwipeableListItem -->
                <SwipeableListItem v-for="coupon in coupons.data" :key="coupon.id" :item="coupon" label="coupon"
                    @delete="deleteCoupon">
                    <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-sm border overflow-hidden hover:shadow-xl hover:-translate-y-1 transition-all duration-300 group"
                        :class="coupon.hidden_at ? 'border-amber-200 dark:border-amber-800 opacity-75' : 'border-gray-100 dark:border-gray-700'">

                        <!-- Coupon ticket header -->
                        <div
                            class="relative bg-gradient-to-br from-primary-500 via-primary-600 to-purple-600 p-6 text-white overflow-hidden">
                            <div class="absolute inset-0 opacity-10"
                                style="background-image: radial-gradient(circle, white 1px, transparent 1px); background-size: 14px 14px;">
                            </div>

                            <!-- Hidden badge -->
                            <div v-if="coupon.hidden_at" class="absolute top-4 left-4 z-10">
                                <span
                                    class="inline-flex items-center gap-1 px-2 py-1 text-[10px] font-bold uppercase tracking-wide rounded-full backdrop-blur-sm bg-amber-500/90 border border-white/20">
                                    <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l3.59 3.59m0 0A9.953 9.953 0 0112 5c4.478 0 8.268 2.943 9.543 7a10.025 10.025 0 01-4.132 5.411m0 0L21 21" />
                                    </svg>
                                    Hidden
                                </span>
                            </div>

                            <!-- Status pill -->
                            <div class="absolute top-4 right-4 z-10">
                                <span
                                    class="inline-flex items-center px-2.5 py-1 text-[10px] font-bold uppercase tracking-wide rounded-full backdrop-blur-sm border border-white/20"
                                    :class="statusClass(getStatus(coupon))">
                                    {{ formatStatus(getStatus(coupon)) }}
                                </span>
                            </div>

                            <!-- Discount value -->
                            <div class="relative">
                                <p class="text-[10px] uppercase tracking-widest text-white/70 font-bold mb-1">Save</p>
                                <p class="text-4xl font-black tracking-tight leading-none">
                                    {{ coupon.discount_type === 'percentage'
                                        ? coupon.discount_value + '%'
                                        : formatPrice(coupon.discount_value) + ' XAF' }}
                                </p>
                                <p class="text-white/80 text-xs mt-1">OFF</p>
                            </div>

                            <!-- Perforated edge -->
                            <div class="absolute -bottom-3 left-0 right-0 flex justify-between px-6">
                                <span class="w-4 h-4 rounded-full bg-white dark:bg-gray-800 shadow-inner"></span>
                                <span class="w-4 h-4 rounded-full bg-white dark:bg-gray-800 shadow-inner"></span>
                                <span class="w-4 h-4 rounded-full bg-white dark:bg-gray-800 shadow-inner"></span>
                                <span class="w-4 h-4 rounded-full bg-white dark:bg-gray-800 shadow-inner"></span>
                                <span class="w-4 h-4 rounded-full bg-white dark:bg-gray-800 shadow-inner"></span>
                                <span class="w-4 h-4 rounded-full bg-white dark:bg-gray-800 shadow-inner"></span>
                                <span class="w-4 h-4 rounded-full bg-white dark:bg-gray-800 shadow-inner"></span>
                            </div>
                        </div>

                        <!-- Body -->
                        <div class="p-5 pt-7">
                            <h3 class="text-base font-bold text-gray-900 dark:text-white line-clamp-1 tracking-tight">
                                {{ coupon.title }}
                            </h3>
                            <p class="text-xs text-gray-400 dark:text-gray-500 mt-0.5">
                                {{ coupon.business?.name || 'Business' }}
                            </p>

                            <p
                                class="text-sm text-gray-600 dark:text-gray-300 line-clamp-2 mt-3 min-h-[40px] leading-relaxed">
                                {{ coupon.description || 'No description' }}
                            </p>

                            <!-- Usage + expiry -->
                            <div
                                class="flex items-center justify-between text-xs text-gray-500 dark:text-gray-400 mt-4 pt-4 border-t border-gray-100 dark:border-gray-700">
                                <span class="flex items-center gap-1.5">
                                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z" />
                                    </svg>
                                    <span class="font-semibold text-gray-700 dark:text-gray-300">{{ coupon.usage_count
                                        || 0
                                    }}</span>
                                    / {{ coupon.usage_limit || '∞' }}
                                </span>
                                <span v-if="coupon.expires_at" class="flex items-center gap-1.5">
                                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                                    </svg>
                                    {{ formatDate(coupon.expires_at) }}
                                </span>
                            </div>

                            <!-- Actions -->
                            <div
                                class="flex items-center gap-2 mt-4 pt-4 border-t border-gray-100 dark:border-gray-700">
                                <a :href="`/owner/coupons/${coupon.id}/analytics`"
                                    class="flex-1 inline-flex items-center justify-center gap-1.5 px-3 py-2 bg-primary-50 dark:bg-primary-900/20 hover:bg-primary-100 dark:hover:bg-primary-900/30 text-primary-700 dark:text-primary-400 text-xs font-semibold rounded-lg transition-colors">
                                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" />
                                    </svg>
                                    Analytics
                                </a>
                                <span v-if="coupon.hidden_at"
                                    class="flex-1 inline-flex items-center justify-center gap-1.5 px-3 py-2 bg-gray-100 dark:bg-gray-800 text-gray-400 text-xs font-semibold rounded-lg cursor-not-allowed"
                                    title="Delete this coupon or upgrade to edit">
                                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" />
                                    </svg>
                                    Locked
                                </span>
                                <a v-else :href="`/owner/coupons/${coupon.id}/edit`"
                                    class="flex-1 inline-flex items-center justify-center gap-1.5 px-3 py-2 bg-gray-50 dark:bg-gray-700 hover:bg-gray-100 dark:hover:bg-gray-600 text-gray-700 dark:text-gray-300 text-xs font-semibold rounded-lg transition-colors">
                                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                                    </svg>
                                    Edit
                                </a>
                                <button @click="deleteCoupon(coupon)"
                                    class="w-9 h-9 flex items-center justify-center text-red-500 hover:text-red-700 hover:bg-red-50 dark:hover:bg-red-900/20 rounded-lg transition-colors"
                                    title="Delete coupon">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                    </svg>
                                </button>
                            </div>
                        </div>
                    </div>
                </SwipeableListItem>
            </div>

            <!-- PAGINATION -->
            <Pagination v-if="coupons?.links && coupons.links.length > 3" :links="coupons.links" />
        </div>

        <!-- ✅ Coupon scanner modal -->
        <CouponScannerModal :is-open="showScanner" @close="showScanner = false" />
    </AuthenticatedLayout>
</template>

<script setup>
    import { router } from '@inertiajs/vue3';
    import { computed, ref } from 'vue';
    import { usePlan } from '@/composables/usePlan';
    import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
    import Pagination from '@/Components/Pagination.vue';
    import LockedFeature from '@/Components/LockedFeature.vue';
    import PageHeader from '@/Components/PageHeader.vue';
    import SwipeableListItem from '@/Components/Common/SwipeableListItem.vue';
    import CouponScannerModal from '@/Components/Owner/CouponScannerModal.vue';
    import { useConfirm } from '@/composables/useConfirm';

    const props = defineProps({
        coupons: {
            type: Object,
            default: () => ({ data: [], links: [] }),
        },
        canCreate: {
            type: Boolean,
            default: false,
        },
        remaining: {
            type: Number,
            default: 0,
        },
    });

    const { can } = usePlan();
    const { confirm: confirmDialog } = useConfirm();

    // ✅ Coupon scanner
    const showScanner = ref(false);

    const isLocked = computed(() => !can('coupons'));

    const showLimitWarning = computed(() => {
        return !isLocked.value && props.remaining !== -1 && props.remaining <= 1;
    });

    const formatPrice = (price) => {
        if (!price && price !== 0) return '0';
        return new Intl.NumberFormat('en-US').format(price);
    };

    const formatDate = (date) => {
        if (!date) return '';
        return new Date(date).toLocaleDateString('en-US', { month: 'short', day: 'numeric' });
    };

    const getStatus = (coupon) => {
        if (coupon.status) return coupon.status;
        if (!coupon.is_active) return 'inactive';
        if (coupon.expires_at && new Date(coupon.expires_at) < new Date()) return 'expired';
        if (coupon.starts_at && new Date(coupon.starts_at) > new Date()) return 'scheduled';
        if (coupon.usage_limit && coupon.usage_count >= coupon.usage_limit) return 'used_up';
        return 'active';
    };

    const formatStatus = (status) => {
        if (!status) return 'unknown';
        return status.replace(/_/g, ' ');
    };

    const statusClass = (status) => {
        const classes = {
            active: 'bg-emerald-500/90 text-white',
            expired: 'bg-red-500/90 text-white',
            scheduled: 'bg-blue-500/90 text-white',
            used_up: 'bg-orange-500/90 text-white',
            inactive: 'bg-gray-500/90 text-white',
        };
        return classes[status] || classes.inactive;
    };

    const deleteCoupon = async (coupon) => {
        const confirmed = await confirmDialog({
            title: 'Delete coupon?',
            message: `"${coupon.title || 'This coupon'}" will be permanently removed. This action cannot be undone.`,
            confirmText: 'Delete Coupon',
            cancelText: 'Cancel',
            variant: 'danger',
        });

        if (!confirmed) return;

        router.delete(`/owner/coupons/${coupon.id}`);
    };
</script>