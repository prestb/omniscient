<!-- resources/js/Pages/Public/RedeemCoupon.vue -->
<template>
    <PublicLayout>
        <div class="min-h-[70vh] flex items-center justify-center px-4 py-10 sm:py-16">
            <div class="w-full max-w-lg">

                <!-- ==================== READY ==================== -->
                <div v-if="state === 'ready'"
                     class="bg-white dark:bg-gray-800 rounded-3xl shadow-xl border border-gray-100 dark:border-gray-700 overflow-hidden">

                    <!-- Header -->
                    <div class="relative bg-gradient-to-br from-primary-600 via-primary-700 to-purple-700 px-6 py-6 text-white overflow-hidden">
                        <div class="absolute inset-0 opacity-[0.06]"
                             style="background-image: radial-gradient(circle, white 1px, transparent 1px); background-size: 22px 22px;"></div>
                        <div class="absolute -top-16 -right-16 w-56 h-56 bg-primary-400/30 rounded-full blur-3xl"></div>

                        <div class="relative flex items-center gap-3">
                            <div class="w-12 h-12 rounded-2xl bg-white/10 backdrop-blur-sm border border-white/20 flex items-center justify-center flex-shrink-0">
                                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                          d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                                </svg>
                            </div>
                            <div class="min-w-0">
                                <p class="text-[10px] uppercase tracking-widest text-white/70 font-bold">
                                    Confirm redemption
                                </p>
                                <h1 class="text-xl sm:text-2xl font-bold tracking-tight truncate">
                                    Redeem Coupon
                                </h1>
                            </div>
                        </div>
                    </div>

                    <!-- Body -->
                    <div class="p-6 space-y-5">

                        <!-- Coupon summary -->
                        <div class="bg-gradient-to-br from-primary-50 to-purple-50 dark:from-primary-950/30 dark:to-purple-950/30 rounded-2xl border border-primary-100 dark:border-primary-900/50 p-5">
                            <div class="flex items-start gap-4">
                                <div class="w-16 h-16 rounded-2xl bg-gradient-to-br from-primary-500 to-purple-600 flex flex-col items-center justify-center text-white flex-shrink-0 shadow-lg shadow-primary-500/30">
                                    <span class="text-xl font-black leading-none tracking-tight">
                                        {{ coupon.discount_type === 'percentage'
                                            ? coupon.discount_value + '%'
                                            : formatShort(coupon.discount_value) }}
                                    </span>
                                    <span class="text-[9px] font-bold text-white/80 tracking-wider uppercase mt-0.5">
                                        {{ coupon.discount_type === 'percentage' ? 'off' : 'XAF' }}
                                    </span>
                                </div>
                                <div class="min-w-0 flex-1">
                                    <h2 class="text-base font-bold text-gray-900 dark:text-white tracking-tight">
                                        {{ coupon.title }}
                                    </h2>
                                    <p v-if="coupon.description" class="text-xs text-gray-600 dark:text-gray-400 mt-1 line-clamp-2 leading-relaxed">
                                        {{ coupon.description }}
                                    </p>
                                    <div class="flex flex-wrap gap-2 mt-2.5 text-[11px] font-medium">
                                        <span v-if="coupon.code" class="inline-flex items-center gap-1 px-2 py-0.5 bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-600 rounded font-mono font-bold text-gray-700 dark:text-gray-300">
                                            {{ coupon.code }}
                                        </span>
                                        <span v-if="coupon.min_purchase"
                                              class="inline-flex items-center gap-1 px-2 py-0.5 bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-600 rounded text-gray-600 dark:text-gray-300">
                                            Min {{ formatPrice(coupon.min_purchase) }} XAF
                                        </span>
                                        <span class="inline-flex items-center gap-1 px-2 py-0.5 bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-600 rounded text-gray-600 dark:text-gray-300">
                                            {{ coupon.usage_count }} / {{ coupon.usage_limit || '∞' }} used
                                        </span>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Customer -->
                        <div class="bg-gray-50 dark:bg-gray-900/50 rounded-2xl border border-gray-100 dark:border-gray-700 p-5">
                            <p class="text-[10px] uppercase tracking-widest text-gray-400 font-bold mb-3">
                                Customer
                            </p>
                            <div class="flex items-center gap-3">
                                <div class="w-11 h-11 rounded-2xl bg-gradient-to-br from-amber-400 to-amber-600 flex items-center justify-center text-white font-bold text-sm flex-shrink-0 shadow-md">
                                    {{ getInitials(customer.name) }}
                                </div>
                                <div class="min-w-0">
                                    <p class="text-sm font-bold text-gray-900 dark:text-white truncate">
                                        {{ customer.name }}
                                    </p>
                                    <p class="text-xs text-gray-500 dark:text-gray-400 truncate">
                                        {{ customer.email }}
                                    </p>
                                </div>
                            </div>
                        </div>

                                                <!-- Location selector (only if multiple) -->
                        <div v-if="locations.length > 1">
                            <label class="block text-[10px] uppercase tracking-widest text-gray-400 font-bold mb-2">
                                Which location?
                            </label>
                            <select v-model="form.location_id"
                                    class="w-full px-4 py-2.5 border border-gray-200 dark:border-gray-600 rounded-xl bg-white dark:bg-gray-900 text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-primary-500 focus:border-transparent transition-colors text-sm">
                                <option :value="null">Not specified</option>
                                <option v-for="location in locations" :key="location.id" :value="location.id">
                                    {{ location.name || 'Unnamed Location' }}{{ location.is_primary ? ' (Primary)' : '' }}
                                </option>
                            </select>
                        </div>

                        <!-- Notes -->
                        <div>
                            <label class="block text-[10px] uppercase tracking-widest text-gray-400 font-bold mb-2">
                                Note <span class="text-gray-400 normal-case text-[10px]">(optional)</span>
                            </label>
                            <input v-model="form.notes"
                                   type="text"
                                   maxlength="500"
                                   placeholder="e.g., Walked in with a friend, order 1,240 XAF"
                                   class="w-full px-4 py-2.5 border border-gray-200 dark:border-gray-600 rounded-xl bg-white dark:bg-gray-900 text-gray-900 dark:text-white placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-primary-500 focus:border-transparent transition-colors text-sm" />
                        </div>

                        <!-- Error message -->
                        <div v-if="confirmError"
                             class="bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800 rounded-xl p-3 flex items-start gap-2">
                            <svg class="w-4 h-4 text-red-600 dark:text-red-400 flex-shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                      d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                            </svg>
                            <p class="text-xs text-red-700 dark:text-red-400 font-medium">
                                {{ confirmError }}
                            </p>
                        </div>

                        <!-- Actions -->
                        <div class="flex flex-col-reverse sm:flex-row gap-3 pt-2">
                            <button type="button"
                                    @click="cancel"
                                    class="w-full sm:w-auto px-6 py-3 border border-gray-300 dark:border-gray-600 text-gray-700 dark:text-gray-300 rounded-xl font-semibold hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors text-sm">
                                Cancel
                            </button>
                            <button type="button"
                                    @click="confirmRedemption"
                                    :disabled="confirming"
                                    class="flex-1 inline-flex items-center justify-center gap-2 px-6 py-3 bg-gradient-to-r from-emerald-500 to-emerald-600 text-white rounded-xl font-bold text-sm shadow-lg shadow-emerald-500/25 hover:from-emerald-600 hover:to-emerald-700 hover:-translate-y-0.5 transition-all disabled:opacity-60 disabled:cursor-not-allowed disabled:hover:translate-y-0">
                                <svg v-if="confirming" class="w-4 h-4 animate-spin" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                          d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                                </svg>
                                <svg v-else class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5"
                                          d="M5 13l4 4L19 7" />
                                </svg>
                                {{ confirming ? 'Redeeming…' : 'Confirm Redemption' }}
                            </button>
                        </div>
                    </div>
                </div>

                <!-- ==================== SUCCESS ==================== -->
                <div v-else-if="state === 'success'"
                     class="bg-white dark:bg-gray-800 rounded-3xl shadow-xl border border-gray-100 dark:border-gray-700 overflow-hidden">
                    <div class="p-8 text-center">
                        <div class="w-20 h-20 rounded-full bg-gradient-to-br from-emerald-400 to-emerald-600 flex items-center justify-center mx-auto mb-5 shadow-lg shadow-emerald-500/30">
                            <svg class="w-10 h-10 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3"
                                      d="M5 13l4 4L19 7" />
                            </svg>
                        </div>
                        <h2 class="text-2xl font-bold text-gray-900 dark:text-white tracking-tight mb-2">
                            Coupon Redeemed!
                        </h2>
                        <p class="text-sm text-gray-500 dark:text-gray-400 mb-6 max-w-sm mx-auto leading-relaxed">
                            The discount has been applied and logged. The customer can now continue.
                        </p>
                        <button @click="returnToDashboard"
                                class="inline-flex items-center gap-2 px-6 py-3 bg-gradient-to-r from-primary-600 to-primary-700 text-white rounded-xl font-semibold hover:from-primary-700 hover:to-primary-800 transition-all shadow-lg shadow-primary-500/25 text-sm">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                      d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6" />
                            </svg>
                            Back to Dashboard
                        </button>
                    </div>
                </div>

                <!-- ==================== ERROR STATES ==================== -->
                <div v-else
                     class="bg-white dark:bg-gray-800 rounded-3xl shadow-xl border border-gray-100 dark:border-gray-700 overflow-hidden">
                    <div class="p-8 text-center">
                        <div class="w-20 h-20 rounded-full flex items-center justify-center mx-auto mb-5"
                             :class="errorStateClasses.icon">
                            <svg class="w-10 h-10" :class="errorStateClasses.iconColor" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path v-if="state === 'expired'"
                                      stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                      d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                                <path v-else-if="state === 'already_redeemed'"
                                      stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                      d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                                <path v-else-if="state === 'forbidden'"
                                      stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                      d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" />
                                <path v-else
                                      stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                      d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                            </svg>
                        </div>

                        <h2 class="text-xl font-bold text-gray-900 dark:text-white tracking-tight mb-2">
                            {{ errorStateClasses.title }}
                        </h2>
                        <p class="text-sm text-gray-500 dark:text-gray-400 mb-2 max-w-sm mx-auto leading-relaxed">
                            {{ message || errorStateClasses.defaultMessage }}
                        </p>
                        <p v-if="state === 'already_redeemed' && redeemed_at"
                           class="text-xs text-gray-400 mb-6">
                            Redeemed {{ formatDate(redeemed_at) }}
                        </p>
                        <div v-else class="mb-6"></div>

                        <button @click="returnToDashboard"
                                class="inline-flex items-center gap-2 px-6 py-3 border border-gray-300 dark:border-gray-600 text-gray-700 dark:text-gray-300 rounded-xl font-semibold hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors text-sm">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                      d="M10 19l-7-7m0 0l7-7m-7 7h18" />
                            </svg>
                            Back to Dashboard
                        </button>
                    </div>
                </div>

            </div>
        </div>
    </PublicLayout>
</template>

<script setup>
import { ref, reactive, computed } from 'vue';
import { router, usePage } from '@inertiajs/vue3';
import axios from 'axios';
import PublicLayout from '@/Layouts/PublicLayout.vue';

const props = defineProps({
    state: {
        type: String,
        default: 'ready',
    },
    token: {
        type: String,
        default: null,
    },
    coupon: {
        type: Object,
        default: null,
    },
    customer: {
        type: Object,
        default: null,
    },
    business: {
        type: Object,
        default: null,
    },
        locations: {
        type: Array,
        default: () => [],
    },
    expires_at: {
        type: String,
        default: null,
    },
    message: {
        type: String,
        default: null,
    },
    redeemed_at: {
        type: String,
        default: null,
    },
});

const page = usePage();

const confirming = ref(false);
const confirmError = ref('');
const internalState = ref(props.state);

// Keep internal state in sync if the page re-renders with a new state
const state = computed(() => internalState.value);

const form = reactive({
    location_id: null,
    notes: '',
});

// If there's exactly one location, preselect it
if (props.locations && props.locations.length === 1) {
    form.location_id = props.locations[0].id;
}

// ============== HELPERS ==============
const getInitials = (name) => {
    if (!name) return '?';
    return name
        .split(' ')
        .map((n) => n[0])
        .join('')
        .toUpperCase()
        .slice(0, 2);
};

const formatPrice = (price) => {
    if (!price && price !== 0) return '0';
    return new Intl.NumberFormat('en-US').format(Math.round(price));
};

const formatShort = (value) => {
    if (!value) return '0';
    if (value >= 1000000) return (value / 1000000).toFixed(1).replace('.0', '') + 'M';
    if (value >= 1000) return (value / 1000).toFixed(1).replace('.0', '') + 'K';
    return Math.round(value).toString();
};

const formatDate = (date) => {
    if (!date) return '';
    return new Date(date).toLocaleString('en-US', {
        month: 'short',
        day: 'numeric',
        year: 'numeric',
        hour: '2-digit',
        minute: '2-digit',
    });
};

// ============== ERROR STATE STYLING ==============
const errorStateClasses = computed(() => {
    const map = {
        invalid: {
            icon: 'bg-red-100 dark:bg-red-900/30',
            iconColor: 'text-red-600 dark:text-red-400',
            title: 'Invalid Link',
            defaultMessage: 'This redemption link is not valid. Please ask the customer to generate a new QR code.',
        },
        forbidden: {
            icon: 'bg-amber-100 dark:bg-amber-900/30',
            iconColor: 'text-amber-600 dark:text-amber-400',
            title: 'Not Authorized',
            defaultMessage: 'You do not have permission to redeem this coupon.',
        },
        already_redeemed: {
            icon: 'bg-emerald-100 dark:bg-emerald-900/30',
            iconColor: 'text-emerald-600 dark:text-emerald-400',
            title: 'Already Redeemed',
            defaultMessage: 'This coupon has already been redeemed.',
        },
        expired: {
            icon: 'bg-amber-100 dark:bg-amber-900/30',
            iconColor: 'text-amber-600 dark:text-amber-400',
            title: 'QR Code Expired',
            defaultMessage: 'This QR code has expired. Please ask the customer to generate a new one.',
        },
        coupon_invalid: {
            icon: 'bg-red-100 dark:bg-red-900/30',
            iconColor: 'text-red-600 dark:text-red-400',
            title: 'Coupon Unavailable',
            defaultMessage: 'This coupon is no longer active or valid.',
        },
    };

    return map[state.value] || map.invalid;
});

// ============== ACTIONS ==============
const confirmRedemption = async () => {
    if (!props.token) return;
    if (confirming.value) return;

    confirming.value = true;
    confirmError.value = '';

    try {
        const response = await axios.post(
            `/api/redemption/${props.token}/confirm`,
                        {
                location_id: form.location_id,
                notes: form.notes || null,
            }
        );

        if (response.data?.success) {
            internalState.value = 'success';
        } else {
            confirmError.value = response.data?.message || 'Could not redeem this coupon.';
        }
    } catch (err) {
        if (err.response?.status === 401) {
            router.visit('/login?redirect=' + encodeURIComponent(window.location.pathname));
            return;
        }

        confirmError.value =
            err.response?.data?.message ||
            'Could not redeem this coupon. Please try again.';
    } finally {
        confirming.value = false;
    }
};

const cancel = () => {
    router.visit('/owner/dashboard');
};

const returnToDashboard = () => {
    // If the user is an owner, send to their dashboard;
    // otherwise to home.
    const user = page.props.auth?.user;
    if (user?.role === 'owner') {
        router.visit('/owner/dashboard');
    } else if (user?.role === 'admin' || user?.role === 'super_admin') {
        router.visit('/admin/dashboard');
    } else {
        router.visit('/');
    }
};
</script>

<style scoped>
.line-clamp-2 {
    display: -webkit-box;
    -webkit-line-clamp: 2;
    -webkit-box-orient: vertical;
    overflow: hidden;
}
</style>