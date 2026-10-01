<!-- resources/js/Pages/Owner/Subscription/Index.vue -->
<template>
    <AuthenticatedLayout>
        <!-- COMPACT HEADER -->
        <PageHeader color="primary" :breadcrumb="[
            { label: 'Dashboard', href: '/owner/dashboard' },
            { label: 'Subscription' }
        ]">
            <template #icon>
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                    d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z" />
            </template>
            <template #title>Your subscription</template>
            <template #subtitle>{{ business?.name }}</template>
            <template #actions>
                <a href="/owner/subscription/renew"
                    class="inline-flex items-center gap-2 px-4 py-2 bg-gradient-to-r from-primary-600 to-primary-700 text-white rounded-xl font-semibold hover:from-primary-700 hover:to-primary-800 transition-all shadow-lg shadow-primary-500/25 text-sm">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                    </svg>
                    {{ subscription ? 'Manage plan' : 'Get a plan' }}
                </a>
                <button @click="refreshStatus" :disabled="loading"
                    class="inline-flex items-center gap-2 px-4 py-2 border border-gray-300 dark:border-gray-600 text-gray-700 dark:text-gray-300 rounded-xl hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors text-sm font-semibold disabled:opacity-50">
                    <svg class="w-4 h-4" :class="loading ? 'animate-spin' : ''" fill="none"
                        stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                    </svg>
                    Refresh
                </button>
            </template>
        </PageHeader>

        <div class="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8 py-8 space-y-6">

            <!-- ==================== CREDIT BANNER ==================== -->
            <div v-if="totalCreditBalance > 0"
                class="bg-gradient-to-br from-emerald-50 to-green-50 dark:from-emerald-900/20 dark:to-green-900/10 border border-emerald-200 dark:border-emerald-800 rounded-2xl p-5 shadow-sm">
                <div class="flex items-start gap-4">
                    <div class="w-11 h-11 rounded-xl bg-emerald-100 dark:bg-emerald-900/40 flex items-center justify-center flex-shrink-0">
                        <svg class="w-5 h-5 text-emerald-600 dark:text-emerald-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                    </div>
                    <div class="flex-1">
                        <p class="text-sm font-bold text-emerald-800 dark:text-emerald-300">
                            Saved credit available
                        </p>
                        <p class="text-2xl font-bold text-emerald-900 dark:text-emerald-200 tracking-tight mt-0.5">
                            {{ formatPrice(totalCreditBalance) }}
                        </p>
                        <p class="text-xs text-emerald-700 dark:text-emerald-400 mt-1">
                            This credit will apply automatically to your next upgrade, downgrade, or renewal.
                        </p>
                    </div>
                </div>
            </div>

            <!-- ==================== CURRENT SUBSCRIPTION ==================== -->
            <div v-if="subscription" class="bg-white dark:bg-gray-800 rounded-2xl shadow-sm border border-gray-100 dark:border-gray-700 overflow-hidden">

                <!-- Header -->
                <div
                    class="px-6 py-5 border-b border-gray-100 dark:border-gray-700 bg-gradient-to-br from-gray-50 to-white dark:from-gray-800 dark:to-gray-800/50 flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4">
                    <div class="flex items-center gap-4">
                        <div
                            class="w-12 h-12 rounded-2xl bg-gradient-to-br from-primary-500 to-primary-700 flex items-center justify-center shadow-lg shadow-primary-500/25">
                            <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                            </svg>
                        </div>
                        <div>
                            <span class="text-[10px] font-bold text-gray-400 dark:text-gray-500 uppercase tracking-widest">Current
                                plan</span>
                            <p class="text-xl font-bold text-gray-900 dark:text-white tracking-tight">{{ subscription.plan?.name }}</p>
                        </div>
                    </div>
                    <span :class="statusClass(subscription.status)">
                        {{ subscription.status_label }}
                    </span>
                </div>

                <div class="p-6 space-y-6">

                    <!-- Price strip -->
                    <div class="grid grid-cols-2 sm:grid-cols-4 gap-4">
                        <div class="bg-gray-50 dark:bg-gray-900/50 rounded-xl p-4">
                            <p class="text-[10px] uppercase tracking-widest text-gray-400 dark:text-gray-500 font-bold">Duration</p>
                            <p class="text-sm font-semibold text-gray-900 dark:text-white mt-1">{{ subscription.duration_label || 'N/A'
                                }}</p>
                        </div>
                        <div class="bg-gray-50 dark:bg-gray-900/50 rounded-xl p-4">
                            <p class="text-[10px] uppercase tracking-widest text-gray-400 dark:text-gray-500 font-bold">Total price</p>
                            <p class="text-sm font-semibold text-gray-900 dark:text-white mt-1">{{ subscription.formatted_price || 'N/A'
                                }}</p>
                        </div>
                        <div class="bg-gray-50 dark:bg-gray-900/50 rounded-xl p-4">
                            <p class="text-[10px] uppercase tracking-widest text-gray-400 dark:text-gray-500 font-bold">Monthly</p>
                            <p class="text-sm font-semibold text-gray-900 dark:text-white mt-1">{{ subscription.formatted_monthly_price
                                || 'N/A' }}</p>
                        </div>
                        <div class="bg-gray-50 dark:bg-gray-900/50 rounded-xl p-4">
                            <p class="text-[10px] uppercase tracking-widest text-gray-400 dark:text-gray-500 font-bold">Discount</p>
                            <p class="text-sm font-semibold text-gray-900 dark:text-white mt-1">
                                {{ subscription.discount_percentage || 0 }}%
                            </p>
                        </div>
                    </div>

                    <!-- Plan limits -->
                    <div class="border-t border-gray-100 dark:border-gray-700 pt-6">
                        <p class="text-[10px] uppercase tracking-widest text-gray-400 dark:text-gray-500 font-bold mb-4">Plan includes</p>
                        <div class="grid grid-cols-3 gap-4">
                            <div class="text-center">
                                <p class="text-2xl font-bold text-gray-900 dark:text-white">
                                    {{ formatLimit(subscription.plan?.max_branches) }}
                                </p>
                                <p class="text-xs text-gray-500 dark:text-gray-400 mt-0.5">Locations</p>
                            </div>
                            <div class="text-center">
                                <p class="text-2xl font-bold text-gray-900 dark:text-white">
                                    {{ formatLimit(subscription.plan?.max_businesses) }}
                                </p>
                                <p class="text-xs text-gray-500 dark:text-gray-400 mt-0.5">Businesses</p>
                            </div>
                            <div class="text-center">
                                <p class="text-2xl font-bold text-gray-900 dark:text-white">
                                    {{ formatLimit(subscription.plan?.max_images) }}
                                </p>
                                <p class="text-xs text-gray-500 dark:text-gray-400 mt-0.5">Images</p>
                            </div>
                        </div>
                    </div>

                    <!-- Subscription period -->
                    <div class="border-t border-gray-100 dark:border-gray-700 pt-6">
                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <p
                                    class="text-[10px] uppercase tracking-widest text-gray-400 dark:text-gray-500 font-bold flex items-center gap-1.5">
                                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                                    </svg>
                                    Starts
                                </p>
                                <p class="text-sm font-semibold text-gray-900 dark:text-white mt-1">{{
                                    formatDate(subscription.start_date) }}</p>
                            </div>
                            <div>
                                <p
                                    class="text-[10px] uppercase tracking-widest text-gray-400 dark:text-gray-500 font-bold flex items-center gap-1.5">
                                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                                    </svg>
                                    {{ subscription.status === 'grace_period' ? 'Suspends' : 'Ends' }}
                                </p>
                                <p class="text-sm font-semibold mt-1"
                                    :class="subscription.status === 'grace_period' ? 'text-orange-700 dark:text-orange-400' : 'text-gray-900 dark:text-white'">
                                    {{ subscription.status === 'grace_period' && subscription.grace_period_ends_at
                                        ? formatDate(subscription.grace_period_ends_at)
                                        : formatDate(subscription.end_date) }}
                                </p>
                            </div>
                        </div>

                        <!-- Progress -->
                        <div v-if="subscription.days_remaining !== null" class="mt-5">
                            <div class="flex items-end justify-between mb-2">
                                <span class="text-xs text-gray-500 dark:text-gray-400">Time remaining</span>
                                <span class="text-sm font-bold"
                                    :class="getDaysRemainingClass(subscription.days_remaining)">
                                    {{ subscription.days_remaining }} {{ subscription.days_remaining === 1 ? 'day' :
                                    'days' }}
                                </span>
                            </div>
                            <div class="w-full bg-gray-100 dark:bg-gray-700 rounded-full h-2.5 overflow-hidden">
                                <div class="h-full rounded-full transition-all duration-1000"
                                    :class="getProgressBarClass(subscription.days_remaining)"
                                    :style="{ width: `${Math.min(100, (subscription.days_remaining / 365) * 100)}%` }">
                                </div>
                            </div>
                        </div>

                        <!-- Grace period banner (highest priority) -->
                        <div v-if="subscription.status === 'grace_period'"
                            class="mt-4 p-4 bg-orange-50 dark:bg-orange-900/20 rounded-xl border border-orange-200 dark:border-orange-800">
                            <div class="flex items-start gap-3">
                                <svg class="w-5 h-5 text-orange-600 dark:text-orange-400 flex-shrink-0 mt-0.5" fill="none"
                                    stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                                </svg>
                                <div class="flex-1 min-w-0">
                                    <p class="text-sm font-bold text-orange-800 dark:text-orange-300">
                                        Grace period — account suspension imminent
                                    </p>
                                    <p class="text-sm text-orange-700 dark:text-orange-400 mt-1">
                                        Your subscription has expired. Your account will be
                                        <strong>suspended on {{ formatDate(subscription.grace_period_ends_at)
                                            }}</strong>.
                                    </p>
                                    <div v-if="graceDaysRemaining !== null && graceDaysRemaining > 0"
                                        class="mt-2 inline-flex items-center gap-2 px-3 py-1 bg-orange-100 dark:bg-orange-900/40 text-orange-800 dark:text-orange-300 rounded-lg text-xs font-bold uppercase tracking-wide">
                                        <span class="w-1.5 h-1.5 rounded-full bg-orange-500 animate-pulse"></span>
                                        {{ graceDaysRemaining }} {{ graceDaysRemaining === 1 ? 'day' : 'days' }} until
                                        suspension
                                    </div>
                                    <div v-else
                                        class="mt-2 inline-flex items-center gap-2 px-3 py-1 bg-red-100 dark:bg-red-900/40 text-red-800 dark:text-red-300 rounded-lg text-xs font-bold uppercase tracking-wide">
                                        <span class="w-1.5 h-1.5 rounded-full bg-red-500 animate-pulse"></span>
                                        Suspension imminent
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Expired (no grace period) -->
                        <div v-else-if="subscription.days_remaining === 0"
                            class="mt-4 p-4 bg-red-50 dark:bg-red-900/20 rounded-xl border border-red-200 dark:border-red-800 flex items-start gap-3">
                            <svg class="w-5 h-5 text-red-600 dark:text-red-400 flex-shrink-0 mt-0.5" fill="none" stroke="currentColor"
                                viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                            </svg>
                            <p class="text-sm text-red-700 dark:text-red-400">
                                Your subscription has expired. Please renew to continue.
                            </p>
                        </div>

                        <!-- Expiring soon -->
                        <div v-else-if="subscription.days_remaining < 7"
                            class="mt-4 p-4 bg-amber-50 dark:bg-amber-900/20 rounded-xl border border-amber-200 dark:border-amber-800 flex items-start gap-3">
                            <svg class="w-5 h-5 text-amber-600 dark:text-amber-400 flex-shrink-0 mt-0.5" fill="none" stroke="currentColor"
                                viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                            <p class="text-sm text-amber-700 dark:text-amber-400">
                                Expiring soon. Renew to avoid interruption.
                            </p>
                        </div>
                    </div>

                    <!-- Primary action -->
                    <div class="border-t border-gray-100 dark:border-gray-700 pt-6">
                        <a href="/owner/subscription/renew"
                            class="w-full inline-flex items-center justify-center gap-2 px-6 py-3 bg-gradient-to-r from-primary-600 to-primary-700 text-white rounded-xl hover:from-primary-700 hover:to-primary-800 transition-all duration-200 shadow-lg shadow-primary-500/25 hover:shadow-primary-500/40 hover:-translate-y-0.5 font-semibold text-sm">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                            </svg>
                            {{ subscription.days_remaining < 30 ? 'Renew now' : 'Manage plan' }} </a>
                    </div>
                </div>
            </div>

            <!-- ==================== NO SUBSCRIPTION ==================== -->
            <div v-else class="bg-white dark:bg-gray-800 rounded-2xl shadow-sm border border-gray-100 dark:border-gray-700 p-12 text-center">
                <div
                    class="w-20 h-20 bg-gradient-to-br from-primary-100 to-primary-200 dark:from-primary-900/40 dark:to-primary-900/20 rounded-2xl flex items-center justify-center mx-auto mb-5 shadow-sm">
                    <svg class="w-10 h-10 text-primary-600 dark:text-primary-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                </div>
                <h3 class="text-xl font-bold text-gray-900 dark:text-white mb-2 tracking-tight">No active subscription</h3>
                <p class="text-gray-500 dark:text-gray-400 max-w-md mx-auto mb-6">
                    Your business doesn't have an active subscription yet. Subscribe to unlock all features.
                </p>
                <a href="/owner/subscription/renew"
                    class="inline-flex items-center gap-2 px-6 py-3 bg-gradient-to-r from-primary-600 to-primary-700 text-white rounded-xl hover:from-primary-700 hover:to-primary-800 transition-all duration-200 shadow-lg shadow-primary-500/25 hover:shadow-primary-500/40 hover:-translate-y-0.5 font-semibold text-sm">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                    Get a subscription
                </a>
            </div>

            <!-- ==================== SUBSCRIPTION HISTORY ==================== -->
            <div v-if="subscriptionHistory && subscriptionHistory.length > 0"
                class="bg-white dark:bg-gray-800 rounded-2xl shadow-sm border border-gray-100 dark:border-gray-700 overflow-hidden">

                <div
                    class="px-6 py-4 border-b border-gray-100 dark:border-gray-700 bg-gradient-to-br from-gray-50 to-white dark:from-gray-800 dark:to-gray-800/50 flex justify-between items-center">
                    <div class="flex items-center gap-3">
                        <div class="w-9 h-9 rounded-xl bg-gray-100 dark:bg-gray-700 flex items-center justify-center">
                            <svg class="w-4 h-4 text-gray-500 dark:text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                        </div>
                        <div>
                            <h3 class="text-sm font-bold text-gray-900 dark:text-white tracking-tight">Subscription history</h3>
                            <p class="text-xs text-gray-400 dark:text-gray-500">{{ subscriptionHistory.length }} record{{
                                subscriptionHistory.length !== 1 ? 's' : '' }}</p>
                        </div>
                    </div>
                </div>

                <div class="divide-y divide-gray-100 dark:divide-gray-700">
                    <div v-for="sub in subscriptionHistory" :key="sub.id"
                        class="px-6 py-4 hover:bg-gray-50/50 dark:hover:bg-gray-700/30 transition-colors flex flex-col sm:flex-row justify-between items-start sm:items-center gap-3">
                        <div class="flex items-start gap-4">
                            <div
                                class="w-10 h-10 rounded-xl bg-gradient-to-br from-gray-100 to-gray-50 dark:from-gray-700 dark:to-gray-800 flex items-center justify-center flex-shrink-0">
                                <span class="text-sm font-bold text-gray-500 dark:text-gray-400">{{ (sub.plan?.name || '?').charAt(0)
                                    }}</span>
                            </div>
                            <div>
                                <p class="text-sm font-semibold text-gray-900 dark:text-white">{{ sub.plan?.name }}</p>
                                <p class="text-xs text-gray-400 dark:text-gray-500 flex items-center gap-1.5 mt-0.5">
                                    <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                                    </svg>
                                    {{ formatDate(sub.start_date) }} → {{ formatDate(sub.end_date) }}
                                </p>
                                <p class="text-xs text-gray-400 dark:text-gray-500 mt-0.5">
                                    {{ sub.duration_label || 'N/A' }} · {{ sub.formatted_price || 'N/A' }}
                                </p>
                            </div>
                        </div>
                        <span :class="statusClass(sub.status)" class="flex-shrink-0">
                            {{ sub.status_label }}
                        </span>
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>

<script setup>
    import { ref, computed, onMounted } from 'vue';
    import { router } from '@inertiajs/vue3';
    import { useToast } from '@/composables/useToast';
    import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
    import axios from 'axios';
    import { useStatusBadge } from '@/composables/useStatusBadge';
    import PageHeader from '@/Components/PageHeader.vue';

    onMounted(() => {
        const pendingTransId = sessionStorage.getItem('pending_transaction');
        if (pendingTransId) {
            checkPendingTransaction(pendingTransId);
        }
    });

    const checkPendingTransaction = async (transId) => {
        try {
            const response = await axios.get(`/payment/fapshi/status/${transId}`);
            if (response.data.status === 'SUCCESSFUL') {
                sessionStorage.removeItem('pending_transaction');
                success('Payment Successful!', 'Your subscription is now active.');
                router.reload();
            }
        } catch (err) {
            console.error('Error checking pending transaction:', err);
        }
    };


    const props = defineProps({
        business: Object,
        subscription: Object,
        subscriptionHistory: Array,
        totalCreditBalance: { type: Number, default: 0 },
    });

    const { success } = useToast();
    const { subscription: statusClass } = useStatusBadge();
    const loading = ref(false);


    // Grace period countdown (client-side, from grace_period_ends_at)
    const graceDaysRemaining = computed(() => {
        if (!props.subscription?.grace_period_ends_at) return null;
        const end = new Date(props.subscription.grace_period_ends_at);
        const now = new Date();
        const diffMs = end - now;
        if (diffMs <= 0) return 0;
        return Math.ceil(diffMs / (1000 * 60 * 60 * 24));
    });


    const formatDate = (date) => {
        if (!date) return 'N/A';
        return new Date(date).toLocaleDateString('en-US', {
            year: 'numeric',
            month: 'short',
            day: 'numeric'
        });
    };

    const formatPrice = (price) => {
        if (price === null || price === undefined || price === '') return 'N/A';
        const num = Number(price);
        if (isNaN(num)) return 'N/A';
        if (num === 0) return 'Free';
        return new Intl.NumberFormat('en-US', {
            style: 'currency',
            currency: 'XAF',
            minimumFractionDigits: 0,
        }).format(num);
    };

    // ✅ Handles both 999 and -1 as "unlimited"
    const formatLimit = (value) => {
        const n = Number(value);
        if (isNaN(n)) return '—';
        if (n === 999 || n === -1) return '∞';
        if (n === 0) return '0';
        return n;
    };


    const getDaysRemainingClass = (days) => {
        if (days === null || days === undefined) return 'text-gray-500 dark:text-gray-400';
        if (days < 7) return 'text-red-600 dark:text-red-400';
        if (days < 30) return 'text-yellow-600 dark:text-yellow-400';
        return 'text-green-600 dark:text-green-400';
    };

    const getProgressBarClass = (days) => {
        if (days === null || days === undefined) return 'bg-gray-300 dark:bg-gray-600';
        if (days < 7) return 'bg-red-500';
        if (days < 30) return 'bg-yellow-500';
        return 'bg-green-500';
    };

    const getRenewalMessage = (status) => {
        const messages = {
            'expiring_soon': 'Your subscription is expiring soon. Please renew to continue enjoying our services.',
            'expired': 'Your subscription has expired. Please keep your business listing active.',
            'grace_period': 'Your subscription is in the grace period. Please renew immediately to avoid suspension.',
        };
        return messages[status] || 'Please renew your subscription.';
    };

    const refreshStatus = () => {
        loading.value = true;
        router.reload({
            onFinish: () => { loading.value = false; },
            onSuccess: () => { success('Refreshed! 🔄', 'Subscription status has been updated.', { duration: 3000 }); }
        });
    };
</script>