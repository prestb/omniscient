<!-- resources/js/Components/Public/BusinessCoupons.vue -->
<template>
    <div v-if="coupons.length > 0" class="bg-white dark:bg-gray-800 rounded-2xl shadow-sm border border-gray-100 dark:border-gray-700 overflow-hidden">

        <!-- Header -->
        <div class="px-6 py-5 border-b border-gray-100 dark:border-gray-700 bg-gradient-to-br from-primary-50 to-purple-50 dark:from-primary-950/20 dark:to-purple-950/20">
            <div class="flex items-center justify-between gap-3 flex-wrap">
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 rounded-xl bg-gradient-to-br from-primary-500 to-purple-600 flex items-center justify-center shadow-lg shadow-primary-500/25">
                        <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z" />
                        </svg>
                    </div>
                    <div>
                        <h2 class="text-lg font-bold text-gray-900 dark:text-white tracking-tight">
                            Available Offers
                        </h2>
                        <p class="text-xs text-gray-500 dark:text-gray-400 mt-0.5">
                            {{ coupons.length }} {{ coupons.length === 1 ? 'coupon' : 'coupons' }} available
                        </p>
                    </div>
                </div>
                <span class="hidden sm:inline-flex items-center gap-1.5 px-3 py-1.5 bg-emerald-100 dark:bg-emerald-900/30 text-emerald-700 dark:text-emerald-400 text-[10px] font-bold uppercase tracking-wide rounded-full">
                    <span class="w-1.5 h-1.5 bg-emerald-500 rounded-full animate-pulse"></span>
                    Active
                </span>
            </div>
        </div>

        <!-- List -->
        <div class="divide-y divide-gray-100 dark:divide-gray-700">
            <div v-for="coupon in coupons"
                 :key="coupon.id"
                 class="p-5 sm:p-6 hover:bg-gray-50/50 dark:hover:bg-gray-700/30 transition-colors">
                <div class="flex flex-col sm:flex-row gap-4">

                    <!-- Ticket visual -->
                    <div class="flex-shrink-0 flex sm:flex-col items-center sm:items-start gap-3 sm:gap-2">
                        <div class="relative">
                            <div class="w-20 h-20 sm:w-24 sm:h-24 rounded-2xl bg-gradient-to-br from-primary-500 via-primary-600 to-purple-600 flex flex-col items-center justify-center text-white shadow-lg shadow-primary-500/30">
                                <span class="text-2xl sm:text-3xl font-bold leading-none tracking-tight">
                                    {{ coupon.discount_type === 'percentage'
                                        ? coupon.discount_value + '%'
                                        : formatShort(coupon.discount_value) }}
                                </span>
                                <span class="text-[10px] sm:text-xs font-bold text-white/80 tracking-wider uppercase mt-1">
                                    {{ coupon.discount_type === 'percentage' ? 'off' : 'XAF off' }}
                                </span>
                            </div>
                            <div class="absolute -left-2 top-1/2 -translate-y-1/2 w-3 h-3 bg-white dark:bg-gray-800 rounded-full"></div>
                            <div class="absolute -right-2 top-1/2 -translate-y-1/2 w-3 h-3 bg-white dark:bg-gray-800 rounded-full"></div>
                        </div>
                    </div>

                    <!-- Content -->
                    <div class="flex-1 min-w-0">
                        <div class="flex items-start justify-between gap-2 mb-1.5">
                            <h3 class="text-base sm:text-lg font-bold text-gray-900 dark:text-white tracking-tight">
                                {{ coupon.title }}
                            </h3>
                            <span v-if="getDaysRemaining(coupon.expires_at) !== null"
                                  class="flex-shrink-0 inline-flex items-center gap-1 px-2 py-1 text-[10px] font-bold uppercase tracking-wide rounded-full"
                                  :class="urgencyClass(coupon.expires_at)">
                                <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                                </svg>
                                {{ getExpiryText(coupon.expires_at) }}
                            </span>
                        </div>

                        <p v-if="coupon.description" class="text-sm text-gray-600 dark:text-gray-400 mb-3 line-clamp-2 leading-relaxed">
                            {{ coupon.description }}
                        </p>

                        <!-- Details -->
                        <div class="flex flex-wrap items-center gap-x-4 gap-y-1.5 mb-3 text-xs text-gray-500 dark:text-gray-400 font-medium">
                            <span v-if="coupon.min_purchase" class="inline-flex items-center gap-1.5">
                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 11-4 0 2 2 0 014 0z" />
                                </svg>
                                Min {{ formatPrice(coupon.min_purchase) }} XAF
                            </span>
                            <span v-if="coupon.max_discount" class="inline-flex items-center gap-1.5">
                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z" />
                                </svg>
                                Max {{ formatPrice(coupon.max_discount) }} XAF
                            </span>
                            <span v-if="coupon.usage_limit" class="inline-flex items-center gap-1.5">
                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z" />
                                </svg>
                                {{ Math.max(0, coupon.usage_limit - (coupon.usage_count || 0)) }} left
                            </span>
                        </div>

                        <!-- Action row -->
                        <div class="flex flex-col sm:flex-row items-stretch sm:items-center gap-2">

                            <!-- Code + Copy (if code exists) -->
                            <div v-if="coupon.code" class="flex-1">
                                <div class="flex items-center gap-2">
                                    <div class="flex-1 flex items-center gap-2 px-3 py-2 bg-gray-100 dark:bg-gray-900/50 border border-dashed border-gray-300 dark:border-gray-600 rounded-xl">
                                        <svg class="w-4 h-4 text-gray-400 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z" />
                                        </svg>
                                        <span class="font-mono font-bold text-sm text-gray-900 dark:text-white tracking-wider">
                                            {{ coupon.code }}
                                        </span>
                                    </div>
                                    <button @click="copyCode(coupon)"
                                            class="flex-shrink-0 px-3 py-2 rounded-xl text-sm font-semibold transition-all active:scale-95"
                                            :class="copiedId === coupon.id
                                                ? 'bg-emerald-500 text-white'
                                                : 'bg-gray-900 dark:bg-white text-white dark:text-gray-900 hover:bg-gray-800 dark:hover:bg-gray-100'">
                                        <span v-if="copiedId === coupon.id" class="flex items-center gap-1">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7" />
                                            </svg>
                                            <span class="hidden sm:inline">Copied</span>
                                        </span>
                                        <span v-else class="flex items-center gap-1">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z" />
                                            </svg>
                                            <span class="hidden sm:inline">Copy</span>
                                        </span>
                                    </button>
                                </div>
                            </div>

                            <!-- Redeem Now (primary CTA — always visible) -->
                            <button @click="openRedeemModal(coupon)"
                                    :disabled="generatingFor === coupon.id"
                                    class="flex-1 inline-flex items-center justify-center gap-2 px-4 py-2.5 bg-gradient-to-r from-primary-600 to-purple-600 text-white rounded-xl font-semibold hover:from-primary-700 hover:to-purple-700 transition-all shadow-lg shadow-primary-500/25 hover:-translate-y-0.5 active:scale-95 disabled:opacity-60 disabled:cursor-not-allowed disabled:hover:translate-y-0 text-sm">
                                <svg v-if="generatingFor === coupon.id" class="w-4 h-4 animate-spin" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                                </svg>
                                <svg v-else class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v1m6 11h2m-6 0h-2v4m0-11v3m0 0h.01M12 12h4.01M16 20h4M4 12h4m12 0h.01M5 8h2a1 1 0 001-1V5a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1zm14 0h2a1 1 0 001-1V5a1 1 0 00-1-1h-2a1 1 0 00-1 1v2a1 1 0 001 1zM5 20h2a1 1 0 001-1v-2a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1z" />
                                </svg>
                                {{ generatingFor === coupon.id ? 'Generating…' : 'Redeem Now' }}
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Footer -->
        <div class="px-6 py-3 border-t border-gray-100 dark:border-gray-700 bg-gray-50 dark:bg-gray-900/30">
            <p class="text-xs text-gray-500 dark:text-gray-400 text-center flex items-center justify-center gap-1.5 font-medium">
                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
                Redeem online to get a QR code, or show the code at checkout.
            </p>
        </div>

        <!-- ==================== QR MODAL ==================== -->
        <Teleport to="body">
            <Transition
                enter-active-class="transition duration-200 ease-out"
                enter-from-class="opacity-0"
                enter-to-class="opacity-100"
                leave-active-class="transition duration-150 ease-in"
                leave-from-class="opacity-100"
                leave-to-class="opacity-0"
            >
                <div v-if="showModal"
                     class="fixed inset-0 z-[150] flex items-center justify-center p-4 bg-black/70 backdrop-blur-sm"
                     @click.self="closeModal">
                    <Transition
                        enter-active-class="transition duration-300 ease-out"
                        enter-from-class="opacity-0 scale-95 translate-y-4"
                        enter-to-class="opacity-100 scale-100 translate-y-0"
                        appear
                    >
                       <div class="bg-white dark:bg-gray-800 rounded-3xl shadow-2xl max-w-md w-full overflow-hidden">

                            <!-- Header -->
                           <div class="px-6 py-5 border-b border-gray-100 dark:border-gray-700 flex items-center justify-between">
                                <div class="flex items-center gap-3 min-w-0">
                                    <div class="w-10 h-10 rounded-xl bg-gradient-to-br from-primary-500 to-purple-600 flex items-center justify-center flex-shrink-0">
                                        <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v1m6 11h2m-6 0h-2v4m0-11v3m0 0h.01M12 12h4.01M16 20h4M4 12h4m12 0h.01M5 8h2a1 1 0 001-1V5a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1zm14 0h2a1 1 0 001-1V5a1 1 0 00-1-1h-2a1 1 0 00-1 1v2a1 1 0 001 1zM5 20h2a1 1 0 001-1v-2a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1z" />
                                        </svg>
                                    </div>
                                    <div class="min-w-0">
                                       <h3 class="text-base font-bold text-gray-900 dark:text-white tracking-tight truncate">
                                            Redeem Coupon
                                        </h3>
                                       <p class="text-xs text-gray-500 dark:text-gray-400 truncate">
                                            {{ activeCoupon?.title }}
                                        </p>
                                    </div>
                                </div>
                                <button @click="closeModal"class="w-8 h-8 flex items-center justify-center rounded-lg text-gray-400 dark:text-gray-500 hover:text-gray-700 dark:hover:text-gray-200 hover:bg-gray-100 dark:hover:bg-gray-700 transition-colors flex-shrink-0" >
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                                    </svg>
                                </button>
                            </div>

                            <!-- Body -->
                            <div class="p-6">
                                <!-- Generating -->
                                <div v-if="modalState === 'generating'" class="flex flex-col items-center justify-center py-16">
                                    <svg class="w-10 h-10 animate-spin text-primary-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                                    </svg>
                                   <p class="text-sm text-gray-500 dark:text-gray-400 mt-4">Generating your QR code…</p>
                                </div>

                                <!-- Error -->
                                <div v-else-if="modalState === 'error'"
                                     class="flex flex-col items-center justify-center py-10 text-center">
                                  <div class="w-14 h-14 rounded-full bg-red-100 dark:bg-red-900/30 flex items-center justify-center mb-3">
                                        <svg class="w-7 h-7 text-red-600 dark:text-red-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                                        </svg>
                                    </div>
                                    <p class="text-sm font-bold text-gray-900 dark:text-white"> {{ errorMessage|| 'Something went wrong.' }}</p>
                                    <button @click="retryGenerate"
                                            class="mt-4 inline-flex items-center gap-2 px-5 py-2.5 bg-gray-900 dark:bg-white text-white dark:text-gray-900 rounded-xl font-semibold text-sm hover:bg-gray-800 dark:hover:bg-gray-100 transition-colors">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                                        </svg>
                                        Try Again
                                    </button>
                                </div>

                                <!-- Expired -->
                                <div v-else-if="modalState === 'expired'"
                                     class="flex flex-col items-center justify-center py-10 text-center">
                                    <div class="w-14 h-14 rounded-full bg-amber-100 dark:bg-amber-900/30 flex items-center justify-center mb-3">
                                        <svg class="w-7 h-7 text-amber-600 dark:text-amber-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                                        </svg>
                                    </div>
                                    <p class="text-sm font-bold text-gray-900 dark:text-white">QR code expired</p>
                                    <p class="text-xs text-gray-500 dark:text-gray-400 mt-1 mb-4">Generate a fresh one to continue.</p>
                                    <button @click="retryGenerate"
                                            class="inline-flex items-center gap-2 px-5 py-2.5 bg-gradient-to-r from-primary-600 to-purple-600 text-white rounded-xl font-semibold text-sm hover:from-primary-700 hover:to-purple-700 transition-all shadow-lg shadow-primary-500/25">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                                        </svg>
                                        Generate New Code
                                    </button>
                                </div>

                                <!-- Ready: QR code + countdown -->
                                <div v-else-if="modalState === 'ready'" class="flex flex-col items-center text-center">
                                    <p class="text-xs uppercase tracking-widest text-gray-400 dark:text-gray-500 font-bold mb-1">
                                        Show this to the business
                                    </p>
                                    <p class="text-sm text-gray-600 dark:text-gray-400 mb-4 leading-relaxed">
                                        They'll scan it to apply your discount.
                                    </p>

                                    <!-- QR -->
                                    <div class="bg-white p-4 rounded-2xl border-2 border-gray-100 shadow-md">
                                        <QrcodeVue
                                            :value="qrUrl"
                                            :size="qrSize"
                                            level="M"
                                            render-as="svg"
                                            foreground="#111827"
                                            background="#ffffff"
                                        />
                                    </div>

                                    <!-- Countdown -->
                                    <div class="mt-4 inline-flex items-center gap-2 px-3.5 py-2 rounded-xl"
                                         :class="secondsLeft <= 60
                                             ? 'dark:bg-red-900/20 dark:text-red-400 dark:border-red-800'
                                             : 'dark:bg-gray-900/50 dark:text-gray-300 dark:border-gray-700'">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                                        </svg>
                                        <span class="text-sm font-bold tabular-nums">
                                            Expires in {{ formattedTimeLeft }}
                                        </span>
                                    </div>

                                    <p class="text-[11px] text-gray-400 dark:text-gray-500 mt-3">
                                        Do not close this window while the business is scanning.
                                    </p>
                                </div>
                            </div>

                        </div>
                    </Transition>
                </div>
            </Transition>
        </Teleport>
    </div>
</template>

<script setup>
import { ref, computed, onUnmounted } from 'vue';
import { router, usePage } from '@inertiajs/vue3';
import axios from 'axios';
import QrcodeVue from 'qrcode.vue';

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

// ============== MODAL STATE ==============
const showModal = ref(false);
const modalState = ref('idle'); // idle | generating | ready | expired | error
const activeCoupon = ref(null);
const qrUrl = ref('');
const errorMessage = ref('');
const secondsLeft = ref(0);
const generatingFor = ref(null);

let countdownInterval = null;

const qrSize = computed(() => {
    // Responsive-ish; qrcode.vue renders at a fixed size, so pick something safe
    // 240 works on all but the smallest phones at 320px width
    return 240;
});

const formattedTimeLeft = computed(() => {
    const s = Math.max(0, secondsLeft.value);
    const m = Math.floor(s / 60);
    const sec = s % 60;
    return `${m}:${sec.toString().padStart(2, '0')}`;
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

const getExpiryText = (expiresAt) => {
    const days = getDaysRemaining(expiresAt);
    if (days === null) return '';
    if (days === 0) return 'Ends today';
    if (days === 1) return 'Ends tomorrow';
    if (days <= 3) return `${days} days left`;
    if (days <= 7) return `Ends in ${days} days`;
    return `Ends ${new Date(expiresAt).toLocaleDateString('en-US', { month: 'short', day: 'numeric' })}`;
};

const urgencyClass = (expiresAt) => {
    const days = getDaysRemaining(expiresAt);
    if (days === null) return 'bg-gray-100 dark:bg-gray-700 text-gray-600 dark:text-gray-300';
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
            if (copiedId.value === coupon.id) {
                copiedId.value = null;
            }
        }, 2000);
    } catch (err) {
        console.error('Failed to copy code:', err);
    }
};

// ============== REDEEM FLOW ==============
const isLoggedIn = computed(() => !!page.props.auth?.user);

const openRedeemModal = (coupon) => {
    // Anonymous → redirect to login, preserving the current URL
    if (!isLoggedIn.value) {
        router.visit('/login?redirect=' + encodeURIComponent(window.location.pathname));
        return;
    }

    activeCoupon.value = coupon;
    showModal.value = true;
    generateToken();
};

const closeModal = () => {
    showModal.value = false;
    stopCountdown();
    // Reset state
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
            // Token expired or session died — send to login
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