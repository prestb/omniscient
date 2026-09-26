<!-- resources/js/Components/Public/BusinessCouponsSidebar.vue -->
<template>
    <div v-if="coupons.length > 0"
         class="bg-white dark:bg-gray-800 rounded-2xl shadow-sm border border-gray-100 dark:border-gray-700 overflow-hidden">

        <!-- Header -->
        <div class="px-5 py-4 border-b border-gray-100 dark:border-gray-700 bg-gradient-to-br from-primary-50 to-purple-50 dark:from-primary-950/20 dark:to-purple-950/20">
            <div class="flex items-center justify-between gap-2">
                <div class="flex items-center gap-2.5 min-w-0">
                    <div class="w-9 h-9 rounded-xl bg-gradient-to-br from-primary-500 to-purple-600 flex items-center justify-center shadow-lg shadow-primary-500/25 flex-shrink-0">
                        <svg class="w-4 h-4 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                  d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z" />
                        </svg>
                    </div>
                    <div class="min-w-0">
                        <h3 class="text-sm font-bold text-gray-900 dark:text-white tracking-tight">
                            Available Offers
                        </h3>
                        <p class="text-[11px] text-gray-500 dark:text-gray-400 truncate">
                            {{ coupons.length }} {{ coupons.length === 1 ? 'coupon' : 'coupons' }}
                        </p>
                    </div>
                </div>
                <span class="inline-flex items-center gap-1 px-2 py-1 bg-emerald-100 dark:bg-emerald-900/30 text-emerald-700 dark:text-emerald-400 text-[9px] font-bold uppercase tracking-wide rounded-full flex-shrink-0">
                    <span class="w-1 h-1 bg-emerald-500 rounded-full animate-pulse"></span>
                    Active
                </span>
            </div>
        </div>

        <!-- Compact list -->
        <div class="divide-y divide-gray-100 dark:divide-gray-700">
            <button v-for="coupon in visibleCoupons"
                    :key="coupon.id"
                    type="button"
                    @click="openRedeem(coupon)"
                    :disabled="generatingFor === coupon.id"
                    class="w-full text-left p-4 hover:bg-gray-50/70 dark:hover:bg-gray-700/40 transition-colors disabled:opacity-60">
                <div class="flex items-start gap-3">
                    <!-- Discount badge -->
                    <div class="w-14 h-14 rounded-xl bg-gradient-to-br from-primary-500 via-primary-600 to-purple-600 flex flex-col items-center justify-center text-white shadow-md shadow-primary-500/25 flex-shrink-0">
                        <span class="text-base font-bold leading-none tracking-tight">
                            {{ coupon.discount_type === 'percentage'
                                ? coupon.discount_value + '%'
                                : formatShort(coupon.discount_value) }}
                        </span>
                        <span class="text-[8px] font-bold text-white/80 tracking-wider uppercase mt-0.5">
                            {{ coupon.discount_type === 'percentage' ? 'off' : 'XAF' }}
                        </span>
                    </div>

                    <!-- Content -->
                    <div class="flex-1 min-w-0">
                        <div class="flex items-start justify-between gap-2 mb-1">
                            <h4 class="text-sm font-bold text-gray-900 dark:text-white leading-snug line-clamp-2">
                                {{ coupon.title }}
                            </h4>
                            <span v-if="getDaysRemaining(coupon.expires_at) !== null"
                                  class="flex-shrink-0 inline-flex items-center gap-1 px-1.5 py-0.5 text-[9px] font-bold uppercase tracking-wide rounded-full"
                                  :class="urgencyClass(coupon.expires_at)">
                                {{ getExpiryShort(coupon.expires_at) }}
                            </span>
                        </div>

                        <!-- Meta line -->
                        <div class="flex flex-wrap items-center gap-x-2 gap-y-0.5 text-[11px] text-gray-500 dark:text-gray-400 font-medium">
                            <span v-if="coupon.min_purchase" class="inline-flex items-center gap-1">
                                Min {{ formatPrice(coupon.min_purchase) }}
                            </span>
                            <span v-if="coupon.min_purchase && coupon.usage_limit" class="text-gray-300 dark:text-gray-600">·</span>
                            <span v-if="coupon.usage_limit" class="inline-flex items-center gap-1">
                                {{ Math.max(0, coupon.usage_limit - (coupon.usage_count || 0)) }} left
                            </span>
                        </div>

                        <!-- CTA row -->
                        <div class="mt-2 flex items-center gap-2">
                            <span class="flex-1 inline-flex items-center justify-center gap-1.5 px-2.5 py-1.5 bg-gradient-to-r from-primary-600 to-purple-600 text-white rounded-lg text-[11px] font-bold uppercase tracking-wide shadow-sm">
                                <svg v-if="generatingFor === coupon.id" class="w-3 h-3 animate-spin" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                                </svg>
                                <svg v-else class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                          d="M12 4v1m6 11h2m-6 0h-2v4m0-11v3m0 0h.01M12 12h4.01M16 20h4M4 12h4m12 0h.01M5 8h2a1 1 0 001-1V5a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1zm14 0h2a1 1 0 001-1V5a1 1 0 00-1-1h-2a1 1 0 00-1 1v2a1 1 0 001 1zM5 20h2a1 1 0 001-1v-2a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1z" />
                                </svg>
                                {{ generatingFor === coupon.id ? 'Generating' : 'Redeem' }}
                            </span>
                            <button v-if="coupon.code"
                                    type="button"
                                    @click.stop="copyCode(coupon)"
                                    :class="copiedId === coupon.id
                                        ? 'bg-emerald-500 text-white border-emerald-500'
                                        : 'bg-white dark:bg-gray-900 text-gray-600 dark:text-gray-300 border-gray-200 dark:border-gray-600 hover:border-primary-300'"
                                    class="flex-shrink-0 inline-flex items-center gap-1 px-2.5 py-1.5 border rounded-lg text-[11px] font-semibold transition-colors"
                                    :title="copiedId === coupon.id ? 'Copied!' : `Copy code ${coupon.code}`">
                                <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path v-if="copiedId === coupon.id" stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7" />
                                    <path v-else stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                          d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z" />
                                </svg>
                                {{ copiedId === coupon.id ? 'Copied' : 'Code' }}
                            </button>
                        </div>
                    </div>
                </div>
            </button>
        </div>

        <!-- See all (when more than 3) -->
        <button v-if="coupons.length > 3"
                type="button"
                @click="showAllModal = true"
                class="w-full px-5 py-3 border-t border-gray-100 dark:border-gray-700 bg-gray-50 dark:bg-gray-900/30 text-xs font-bold text-primary-600 dark:text-primary-400 hover:bg-gray-100 dark:hover:bg-gray-800 transition-colors inline-flex items-center justify-center gap-1.5">
            See all {{ coupons.length }} offers
            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
            </svg>
        </button>

        <!-- Footer hint -->
        <div v-else class="px-5 py-2.5 border-t border-gray-100 dark:border-gray-700 bg-gray-50 dark:bg-gray-900/30">
            <p class="text-[10px] text-gray-500 dark:text-gray-400 text-center font-medium leading-relaxed">
                Redeem to get a QR code, or show the code at checkout.
            </p>
        </div>

        <!-- Shared QR modal -->
        <CouponQRModal
            :show="showModal"
            :state="modalState"
            :coupon="activeCoupon"
            :qr-url="qrUrl"
            :error-message="errorMessage"
            :seconds-left="secondsLeft"
            @close="closeModal"
            @retry="retryGenerate"
        />

        <!-- See-all modal -->
        <Teleport to="body">
            <Transition
                enter-active-class="transition duration-200 ease-out"
                enter-from-class="opacity-0"
                enter-to-class="opacity-100"
                leave-active-class="transition duration-150 ease-in"
                leave-from-class="opacity-100"
                leave-to-class="opacity-0"
            >
                <div v-if="showAllModal"
                     class="fixed inset-0 z-[140] flex items-end sm:items-center justify-center p-0 sm:p-4 bg-black/60 backdrop-blur-sm"
                     @click.self="showAllModal = false">
                    <div class="bg-white dark:bg-gray-800 rounded-t-3xl sm:rounded-3xl shadow-2xl max-w-lg w-full max-h-[80vh] overflow-hidden flex flex-col">
                        <div class="px-6 py-4 border-b border-gray-100 dark:border-gray-700 flex items-center justify-between flex-shrink-0">
                            <div>
                                <h3 class="text-base font-bold text-gray-900 dark:text-white tracking-tight">
                                    All Offers
                                </h3>
                                <p class="text-xs text-gray-500 dark:text-gray-400">
                                    {{ coupons.length }} active coupons
                                </p>
                            </div>
                            <button @click="showAllModal = false"
                                    class="w-8 h-8 flex items-center justify-center rounded-lg text-gray-400 hover:text-gray-700 dark:hover:text-gray-200 hover:bg-gray-100 dark:hover:bg-gray-700 transition-colors">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                                </svg>
                            </button>
                        </div>
                        <div class="overflow-y-auto divide-y divide-gray-100 dark:divide-gray-700">
                            <button v-for="coupon in coupons"
                                    :key="coupon.id"
                                    type="button"
                                    @click="openRedeemFromAll(coupon)"
                                    class="w-full text-left p-4 hover:bg-gray-50/70 dark:hover:bg-gray-700/40 transition-colors">
                                <div class="flex items-center gap-3">
                                    <div class="w-12 h-12 rounded-xl bg-gradient-to-br from-primary-500 to-purple-600 flex flex-col items-center justify-center text-white flex-shrink-0">
                                        <span class="text-sm font-bold leading-none">
                                            {{ coupon.discount_type === 'percentage' ? coupon.discount_value + '%' : formatShort(coupon.discount_value) }}
                                        </span>
                                        <span class="text-[8px] font-bold text-white/80 uppercase mt-0.5">
                                            {{ coupon.discount_type === 'percentage' ? 'off' : 'XAF' }}
                                        </span>
                                    </div>
                                    <div class="flex-1 min-w-0">
                                        <p class="text-sm font-bold text-gray-900 dark:text-white truncate">
                                            {{ coupon.title }}
                                        </p>
                                        <p class="text-xs text-gray-500 dark:text-gray-400 truncate">
                                            {{ getExpiryText(coupon.expires_at) || 'No expiry' }}
                                        </p>
                                    </div>
                                    <svg class="w-4 h-4 text-gray-400 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                                    </svg>
                                </div>
                            </button>
                        </div>
                    </div>
                </div>
            </Transition>
        </Teleport>
    </div>
</template>

<script setup>
import { ref, computed, onUnmounted } from 'vue';
import { router, usePage } from '@inertiajs/vue3';
import axios from 'axios';
import CouponQRModal from './CouponQRModal.vue';

const props = defineProps({
    coupons: {
        type: Array,
        required: true,
        default: () => [],
    },
    business: {
        type: Object,
        default: null,
    },
});

const page = usePage();
const copiedId = ref(null);
const showAllModal = ref(false);

// ============== MODAL STATE ==============
const showModal = ref(false);
const modalState = ref('idle');
const activeCoupon = ref(null);
const qrUrl = ref('');
const errorMessage = ref('');
const secondsLeft = ref(0);
const generatingFor = ref(null);

let countdownInterval = null;

// ============== VISIBLE (first 3 if more than 3) ==============
const visibleCoupons = computed(() => {
    if (props.coupons.length <= 3) return props.coupons;
    return props.coupons.slice(0, 3);
});

// ============== HELPERS ==============
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

const getDaysRemaining = (expiresAt) => {
    if (!expiresAt) return null;
    const diff = Math.ceil((new Date(expiresAt) - new Date()) / (1000 * 60 * 60 * 24));
    return diff >= 0 ? diff : null;
};

const getExpiryShort = (expiresAt) => {
    const days = getDaysRemaining(expiresAt);
    if (days === null) return '';
    if (days === 0) return 'Today';
    if (days === 1) return '1d';
    if (days <= 7) return `${days}d`;
    return new Date(expiresAt).toLocaleDateString('en-US', { month: 'short', day: 'numeric' });
};

const getExpiryText = (expiresAt) => {
    const days = getDaysRemaining(expiresAt);
    if (days === null) return '';
    if (days === 0) return 'Ends today';
    if (days === 1) return 'Ends tomorrow';
    if (days <= 7) return `${days} days left`;
    return `Ends ${new Date(expiresAt).toLocaleDateString('en-US', { month: 'short', day: 'numeric' })}`;
};

const urgencyClass = (expiresAt) => {
    const days = getDaysRemaining(expiresAt);
    if (days === null) return 'bg-gray-100 text-gray-600 dark:bg-gray-700 dark:text-gray-300';
    if (days <= 1) return 'bg-red-100 text-red-700 dark:bg-red-900/30 dark:text-red-400';
    if (days <= 3) return 'bg-orange-100 text-orange-700 dark:bg-orange-900/30 dark:text-orange-400';
    if (days <= 7) return 'bg-amber-100 text-amber-700 dark:bg-amber-900/30 dark:text-amber-400';
    return 'bg-blue-100 text-blue-700 dark:bg-blue-900/30 dark:text-blue-400';
};

// ============== COPY CODE ==============
const copyCode = async (coupon) => {
    try {
        await navigator.clipboard.writeText(coupon.code);
        copiedId.value = coupon.id;
        setTimeout(() => {
            if (copiedId.value === coupon.id) copiedId.value = null;
        }, 2000);
    } catch (err) {
        console.error('Failed to copy code:', err);
    }
};

// ============== REDEEM FLOW ==============
const isLoggedIn = computed(() => !!page.props.auth?.user);

const openRedeem = (coupon) => {
    if (!isLoggedIn.value) {
        router.visit('/login?redirect=' + encodeURIComponent(window.location.pathname));
        return;
    }

    activeCoupon.value = coupon;
    showModal.value = true;
    generateToken();
};

const openRedeemFromAll = (coupon) => {
    showAllModal.value = false;
    openRedeem(coupon);
};

const closeModal = () => {
    showModal.value = false;
    stopCountdown();
    modalState.value = 'idle';
    activeCoupon.value = null;
    qrUrl.value = '';
    errorMessage.value = '';
    secondsLeft.value = 0;
};

const retryGenerate = () => {
    if (!activeCoupon.value) return;
    generateToken();
};

const generateToken = async () => {
    if (!activeCoupon.value) return;

    modalState.value = 'generating';
    generatingFor.value = activeCoupon.value.id;
    errorMessage.value = '';

    try {
        const response = await axios.post(
            `/api/coupons/${activeCoupon.value.id}/generate-token`
        );

        if (response.data?.success) {
            qrUrl.value = response.data.data.url;
            secondsLeft.value = response.data.data.expires_in_seconds || 600;
            modalState.value = 'ready';
            startCountdown();
        } else {
            errorMessage.value = response.data?.message || 'Could not generate QR code.';
            modalState.value = 'error';
        }
    } catch (err) {
        if (err.response?.status === 401) {
            router.visit('/login?redirect=' + encodeURIComponent(window.location.pathname));
            return;
        }
        errorMessage.value =
            err.response?.data?.message ||
            'Could not generate QR code. Please try again.';
        modalState.value = 'error';
    } finally {
        generatingFor.value = null;
    }
};

const startCountdown = () => {
    stopCountdown();
    countdownInterval = setInterval(() => {
        secondsLeft.value = Math.max(0, secondsLeft.value - 1);
        if (secondsLeft.value <= 0) {
            stopCountdown();
            modalState.value = 'expired';
        }
    }, 1000);
};

const stopCountdown = () => {
    if (countdownInterval) {
        clearInterval(countdownInterval);
        countdownInterval = null;
    }
};

onUnmounted(() => {
    stopCountdown();
});
</script>

<style scoped>
.line-clamp-2 {
    display: -webkit-box;
    -webkit-line-clamp: 2;
    -webkit-box-orient: vertical;
    overflow: hidden;
}
</style>