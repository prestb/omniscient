<!-- resources/js/Pages/Admin/Subscriptions/Show.vue -->
<template>
    <AuthenticatedLayout>
        <!-- COMPACT HEADER -->
        <PageHeader color="violet" :breadcrumb="[
            { label: 'Admin', href: '/admin/dashboard' },
            { label: 'Subscriptions', href: '/admin/subscriptions' },
            { label: '#' + subscription.id }
        ]">
            <template #icon>
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                    d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z" />
            </template>
            <template #title>Subscription #{{ subscription.id }}</template>
            <template #subtitle>{{ subscription.user?.name || subscription.business?.name || 'N/A' }}</template>
            <template #actions>
                <span :class="statusClass(subscription.status)">
                    {{ subscription.status_label }}
                </span>
                <a href="/admin/subscriptions"
                    class="inline-flex items-center gap-2 px-4 py-2 border border-gray-300 dark:border-gray-600 text-gray-700 dark:text-gray-300 rounded-xl hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors text-sm font-semibold">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M10 19l-7-7m0 0l7-7m-7 7h18" />
                    </svg>
                    Back
                </a>
            </template>
        </PageHeader>

        <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 py-6">
            <!-- Summary Cards -->
            <div class="grid grid-cols-2 sm:grid-cols-4 gap-4 mb-6">
                <div
                    class="bg-white dark:bg-gray-800 rounded-2xl shadow-sm border border-gray-100 dark:border-gray-700 p-4 hover:shadow-md transition-shadow">
                    <p class="text-xs text-gray-500 dark:text-gray-400 font-medium uppercase tracking-wider">Plan</p>
                    <p class="text-sm font-semibold text-gray-900 dark:text-white mt-0.5">{{ subscription.plan?.name }}</p>
                </div>
                <div
                    class="bg-white dark:bg-gray-800 rounded-2xl shadow-sm border border-gray-100 dark:border-gray-700 p-4 hover:shadow-md transition-shadow">
                    <p class="text-xs text-gray-500 dark:text-gray-400 font-medium uppercase tracking-wider">Status</p>
                    <span :class="statusClass(subscription.status)" class="mt-0.5 inline-block">
                        {{ subscription.status_label }}
                    </span>
                </div>
                <div
                    class="bg-white dark:bg-gray-800 rounded-2xl shadow-sm border border-gray-100 dark:border-gray-700 p-4 hover:shadow-md transition-shadow">
                    <p class="text-xs text-gray-500 dark:text-gray-400 font-medium uppercase tracking-wider">ID</p>
                    <p class="text-sm font-semibold text-gray-900 dark:text-white font-mono mt-0.5">#{{ subscription.id }}</p>
                </div>
                <div
                    class="bg-white dark:bg-gray-800 rounded-2xl shadow-sm border border-gray-100 dark:border-gray-700 p-4 hover:shadow-md transition-shadow">
                    <p class="text-xs text-gray-500 dark:text-gray-400 font-medium uppercase tracking-wider">Days Remaining</p>
                    <p class="text-sm font-semibold mt-0.5" :class="getDaysRemainingClass(subscription.days_remaining)">
                        {{ subscription.days_remaining !== null ? subscription.days_remaining + ' days' : 'N/A' }}
                    </p>
                </div>
            </div>

            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                <!-- Main Info -->
                <div class="lg:col-span-2">
                    <div
                        class="bg-white dark:bg-gray-800 rounded-2xl shadow-sm border border-gray-100 dark:border-gray-700 overflow-hidden hover:shadow-md transition-shadow">
                        <div
                            class="px-6 py-4 border-b border-gray-100 dark:border-gray-700 bg-gradient-to-r from-gray-50 to-white dark:from-gray-800 dark:to-gray-800/50 flex items-center gap-2">
                            <svg class="w-5 h-5 text-gray-400 dark:text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                            </svg>
                            <h3 class="text-sm font-semibold text-gray-700 dark:text-gray-300">Subscription Information</h3>
                        </div>

                        <div class="p-6 space-y-5">
                            <!-- Business -->
                            <!-- Owner (User) -->
                            <div>
                                <label
                                    class="text-xs font-medium text-gray-400 dark:text-gray-500 uppercase tracking-wider flex items-center gap-1.5">
                                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                                    </svg>
                                    Owner
                                </label>
                                <a :href="`/admin/users/${subscription.user?.id}`"
                                    class="text-sm font-semibold text-primary-600 dark:text-primary-400 hover:text-primary-800 dark:hover:text-primary-300 transition-colors mt-0.5 block group">
                                    {{ subscription.user?.name || 'Deleted User' }}
                                    <svg class="w-4 h-4 inline ml-1 transform group-hover:translate-x-1 transition-transform"
                                        fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M9 5l7 7-7 7" />
                                    </svg>
                                </a>
                                <p class="text-xs text-gray-500 dark:text-gray-400 mt-0.5">
                                    {{ subscription.user?.email || 'N/A' }}
                                </p>
                            </div>

                            <!-- Business (optional, if linked) -->
                            <div v-if="subscription.business">
                                <label
                                    class="text-xs font-medium text-gray-400 dark:text-gray-500 uppercase tracking-wider flex items-center gap-1.5">
                                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" />
                                    </svg>
                                    Linked Business
                                </label>
                                <a :href="`/admin/businesses/${subscription.business.id}`"
                                    class="text-sm font-semibold text-gray-700 dark:text-gray-300 hover:text-primary-600 dark:hover:text-primary-400 transition-colors mt-0.5 block">
                                    {{ subscription.business.name }}
                                </a>
                            </div>

                            <!-- Plan -->
                            <div>
                                <label
                                    class="text-xs font-medium text-gray-400 dark:text-gray-500 uppercase tracking-wider flex items-center gap-1.5">
                                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                                    </svg>
                                    Plan
                                </label>
                                <p class="text-sm font-semibold text-gray-900 dark:text-white mt-0.5">{{ subscription.plan?.name }}</p>
                                <p v-if="subscription.plan?.description" class="text-xs text-gray-400 dark:text-gray-500 mt-0.5">{{
                                    subscription.plan.description }}</p>
                                <div class="flex items-center gap-3 mt-1">
                                    <span class="text-xs text-gray-500 dark:text-gray-400 flex items-center gap-1">
                                        <span class="text-green-600 dark:text-green-400 font-bold">{{
                                            formatPrice(subscription.plan?.price_yearly)
                                        }}</span>
                                        <span class="text-gray-400 dark:text-gray-500">/ year</span>
                                    </span>
                                    <span v-if="subscription.plan?.is_featured"
                                        class="inline-flex items-center gap-0.5 px-2 py-0.5 text-[10px] font-medium bg-yellow-100 dark:bg-yellow-900/30 text-yellow-700 dark:text-yellow-400 rounded-full border border-yellow-200 dark:border-yellow-800">
                                        <svg class="w-3 h-3" fill="currentColor" viewBox="0 0 20 20">
                                            <path
                                                d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z" />
                                        </svg>
                                        Featured
                                    </span>
                                </div>
                            </div>

                            <!-- Dates -->
                            <div class="bg-gray-50 dark:bg-gray-900/50 rounded-xl p-4">
                                <div class="grid grid-cols-2 gap-4">
                                    <div>
                                        <label class="text-xs font-medium text-gray-400 dark:text-gray-500 uppercase tracking-wider">Start
                                            Date</label>
                                        <p class="text-sm font-semibold text-gray-900 dark:text-white mt-0.5 flex items-center gap-1.5">
                                            <svg class="w-4 h-4 text-gray-400 dark:text-gray-500" fill="none" stroke="currentColor"
                                                viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                    d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                                            </svg>
                                            {{ formatDate(subscription.start_date) }}
                                        </p>
                                    </div>
                                    <div>
                                        <label class="text-xs font-medium text-gray-400 dark:text-gray-500 uppercase tracking-wider">End
                                            Date</label>
                                        <p class="text-sm font-semibold text-gray-900 dark:text-white mt-0.5 flex items-center gap-1.5">
                                            <svg class="w-4 h-4 text-gray-400 dark:text-gray-500" fill="none" stroke="currentColor"
                                                viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                    d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                                            </svg>
                                            {{ formatDate(subscription.end_date) }}
                                        </p>
                                    </div>
                                </div>
                                <div v-if="subscription.days_remaining !== null"
                                    class="mt-3 pt-3 border-t border-gray-200 dark:border-gray-700">
                                    <div class="flex items-center gap-3">
                                        <div class="flex-1 h-2 bg-gray-200 dark:bg-gray-700 rounded-full overflow-hidden">
                                            <div class="h-full rounded-full transition-all duration-1000"
                                                :class="getProgressBarClass(subscription.days_remaining)"
                                                :style="{ width: getProgressPercentage(subscription) + '%' }"></div>
                                        </div>
                                        <span class="text-xs font-semibold"
                                            :class="getDaysRemainingClass(subscription.days_remaining)">
                                            {{ subscription.days_remaining }} days left
                                        </span>
                                    </div>
                                </div>
                            </div>

                            <!-- Trial Info -->
                            <div v-if="subscription.is_trial"
                                class="bg-purple-50 dark:bg-purple-900/20 rounded-xl p-4 border border-purple-200 dark:border-purple-800">
                                <div class="flex items-center gap-2">
                                    <span class="text-lg">🧪</span>
                                    <div>
                                        <p class="text-sm font-medium text-purple-800 dark:text-purple-300">Trial Subscription</p>
                                        <p class="text-xs text-purple-600 dark:text-purple-400">Trial ends: {{
                                            formatDate(subscription.trial_end_date) }}</p>
                                    </div>
                                </div>
                            </div>

                            <!-- Cancelled Info -->
                            <div v-if="subscription.cancelled_at"
                                class="bg-red-50 dark:bg-red-900/20 rounded-xl p-4 border border-red-200 dark:border-red-800">
                                <div class="flex items-center gap-2">
                                    <span class="text-lg">🚫</span>
                                    <div>
                                        <p class="text-sm font-medium text-red-800 dark:text-red-300">Subscription Cancelled</p>
                                        <p class="text-xs text-red-600 dark:text-red-400">Cancelled on: {{
                                            formatDateTime(subscription.cancelled_at) }}</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Sidebar -->
                <div class="lg:col-span-1 space-y-6">
                    <!-- Actions -->
                    <div
                        class="bg-white dark:bg-gray-800 rounded-2xl shadow-sm border border-gray-100 dark:border-gray-700 overflow-hidden hover:shadow-md transition-shadow sticky top-6">
                        <div
                            class="px-6 py-4 border-b border-gray-100 dark:border-gray-700 bg-gradient-to-r from-gray-50 to-white dark:from-gray-800 dark:to-gray-800/50 flex items-center gap-2">
                            <svg class="w-5 h-5 text-gray-400 dark:text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M13 10V3L4 14h7v7l9-11h-7z" />
                            </svg>
                            <h3 class="text-sm font-semibold text-gray-700 dark:text-gray-300">Actions</h3>
                        </div>
                        <div class="p-6 space-y-3">
                            <a :href="`/admin/subscriptions/${subscription.id}/edit`"
                                class="w-full inline-flex items-center justify-center gap-2 px-4 py-2.5 bg-gradient-to-r from-primary-600 to-primary-700 text-white rounded-xl hover:from-primary-700 hover:to-primary-800 transition-all duration-200 shadow-lg shadow-primary-100 dark:shadow-none hover:shadow-primary-200 font-medium text-sm">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                                </svg>
                                Edit Subscription
                            </a>
                            <a :href="`/admin/payments/create?subscription=${subscription.id}`"
                                class="w-full inline-flex items-center justify-center gap-2 px-4 py-2.5 border border-gray-300 dark:border-gray-600 text-gray-700 dark:text-gray-300 rounded-xl hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors font-medium text-sm">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M3 10h18M3 14h18m-9-4v8m-7 0h14a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v8a2 2 0 002 2z" />
                                </svg>
                                Record Payment
                            </a>
                            <button v-if="subscription.status === 'active'" @click="suspendSubscription"
                                class="w-full inline-flex items-center justify-center gap-2 px-4 py-2.5 border border-yellow-300 dark:border-yellow-700 text-yellow-600 dark:text-yellow-400 rounded-xl hover:bg-yellow-50 dark:hover:bg-yellow-900/30 transition-colors font-medium text-sm">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728A9 9 0 015.636 5.636m12.728 12.728L5.636 5.636" />
                                </svg>
                                Suspend
                            </button>
                            <button v-if="subscription.status === 'suspended'" @click="activateSubscription"
                                class="w-full inline-flex items-center justify-center gap-2 px-4 py-2.5 border border-green-300 dark:border-green-700 text-green-600 dark:text-green-400 rounded-xl hover:bg-green-50 dark:hover:bg-green-900/30 transition-colors font-medium text-sm">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M13 10V3L4 14h7v7l9-11h-7z" />
                                </svg>
                                Activate
                            </button>
                            <button @click="cancelSubscription"
                                class="w-full inline-flex items-center justify-center gap-2 px-4 py-2.5 border border-red-300 dark:border-red-700 text-red-600 dark:text-red-400 rounded-xl hover:bg-red-50 dark:hover:bg-red-900/30 transition-colors font-medium text-sm">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                </svg>
                                Cancel Subscription
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Transactions History (system-recorded, from Fapshi) -->
            <div
                class="mt-6 bg-white dark:bg-gray-800 rounded-2xl shadow-sm border border-gray-100 dark:border-gray-700 overflow-hidden hover:shadow-md transition-shadow">
                <div
                    class="px-6 py-4 border-b border-gray-100 dark:border-gray-700 bg-gradient-to-r from-gray-50 to-white dark:from-gray-800 dark:to-gray-800/50 flex items-center justify-between">
                    <div class="flex items-center gap-2">
                        <svg class="w-5 h-5 text-gray-400 dark:text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M3 10h18M3 14h18m-9-4v8m-7 0h14a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v8a2 2 0 002 2z" />
                        </svg>
                        <h3 class="text-sm font-semibold text-gray-700 dark:text-gray-300">Payment History</h3>
                        <span class="bg-gray-100 dark:bg-gray-700 text-gray-600 dark:text-gray-300 text-xs font-medium px-2.5 py-0.5 rounded-full">
                            {{ subscription.transactions?.length || 0 }}
                        </span>
                    </div>
                    <span class="text-xs text-gray-400 dark:text-gray-500">Total: {{ formatPrice(totalTransactions) }}</span>
                </div>

                <div v-if="subscription.transactions && subscription.transactions.length > 0"
                    class="divide-y divide-gray-100 dark:divide-gray-700">
                    <div v-for="transaction in subscription.transactions" :key="transaction.id"
                        class="px-6 py-4 hover:bg-gray-50 dark:hover:bg-gray-700/30 transition-colors flex flex-col sm:flex-row justify-between items-start sm:items-center gap-2">
                        <div class="min-w-0">
                            <p class="text-sm font-semibold text-gray-900 dark:text-white">{{ formatPrice(transaction.amount) }}</p>
                            <p class="text-xs text-gray-400 dark:text-gray-500 flex items-center gap-2 flex-wrap mt-0.5">
                                <span class="flex items-center gap-1">
                                    <span class="text-base">{{ getMethodIcon(transaction.payment_method) }}</span>
                                    {{ formatPaymentMethod(transaction.payment_method) }}
                                </span>
                                <span class="text-gray-300 dark:text-gray-600">•</span>
                                <span>{{ transaction.duration_months || 0 }} months</span>
                                <span class="text-gray-300 dark:text-gray-600">•</span>
                                <span>{{ transaction.plan?.name || 'N/A' }}</span>
                                <span class="text-gray-300 dark:text-gray-600">•</span>
                                <span>{{ formatDateTime(transaction.confirmed_at || transaction.created_at) }}</span>
                            </p>
                            <p v-if="transaction.transaction_id" class="text-xs font-mono text-gray-400 dark:text-gray-500 mt-1 truncate">
                                {{ transaction.transaction_id }}
                            </p>
                        </div>
                        <div class="flex items-center gap-3 flex-shrink-0">
                            <span :class="paymentTransactionClass(transaction.status)">
                                {{ transaction.status.charAt(0).toUpperCase() + transaction.status.slice(1) }}
                            </span>
                        </div>
                    </div>
                </div>
                <div v-else class="px-6 py-8 text-center">
                    <div class="flex flex-col items-center">
                        <div class="w-12 h-12 bg-gray-100 dark:bg-gray-700 rounded-full flex items-center justify-center mb-3">
                            <svg class="w-6 h-6 text-gray-400 dark:text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M3 10h18M3 14h18m-9-4v8m-7 0h14a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v8a2 2 0 002 2z" />
                            </svg>
                        </div>
                        <p class="text-sm text-gray-500 dark:text-gray-400">No transactions recorded for this subscription</p>
                        <p class="text-xs text-gray-400 dark:text-gray-500 mt-0.5">System transactions will appear here</p>
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>


<script setup>
    import { computed } from 'vue';
    import { router } from '@inertiajs/vue3';
    import { useToast } from '@/composables/useToast';
    import { useConfirm } from '@/composables/useConfirm';
    import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
    import { useStatusBadge } from '@/composables/useStatusBadge';
    import PageHeader from '@/Components/PageHeader.vue';

    const props = defineProps({
        subscription: Object,
    });

    const { error } = useToast();
    const { confirm: confirmDialog } = useConfirm();
    const { subscription: statusClass, payment: paymentTransactionClass } = useStatusBadge();

    const totalTransactions = computed(() => {
        if (!props.subscription.transactions) return 0;
        return props.subscription.transactions.reduce((sum, t) => sum + t.amount, 0);
    });

    const getMethodIcon = (method) => {
        const icons = {
            'mobile_money': '📱',
            'cash': '💵',
            'bank_transfer': '🏦',
            'card': '💳',
            'other': '🔗',
            'fapshi': '💳',
        };
        return icons[method] || '💳';
    };

    const formatPaymentMethod = (method) => {
        if (!method) return 'Fapshi';
        const labels = {
            'mobile_money': 'Mobile Money',
            'cash': 'Cash',
            'bank_transfer': 'Bank Transfer',
            'card': 'Card',
            'other': 'Other',
            'fapshi': 'Fapshi',
        };
        return labels[method] || method.charAt(0).toUpperCase() + method.slice(1);
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

    // ✅ Now takes the full subscription and computes elapsed vs total
    //    from the actual start/end dates. Previous version hardcoded 365
    //    days, which was wrong for monthly subscriptions.
    const getProgressPercentage = (subscription) => {
        if (!subscription?.start_date || !subscription?.end_date) return 0;

        const start = new Date(subscription.start_date).getTime();
        const end = new Date(subscription.end_date).getTime();
        const now = Date.now();

        const total = end - start;
        if (total <= 0) return 0;

        const elapsed = now - start;
        return Math.min(Math.max((elapsed / total) * 100, 0), 100);
    };

    const formatDate = (date) => {
        if (!date) return 'N/A';
        return new Date(date).toLocaleDateString('en-US', {
            year: 'numeric',
            month: 'short',
            day: 'numeric'
        });
    };

    const formatDateTime = (date) => {
        if (!date) return 'N/A';
        return new Date(date).toLocaleString('en-US', {
            year: 'numeric',
            month: 'short',
            day: 'numeric',
            hour: '2-digit',
            minute: '2-digit'
        });
    };

    const formatPrice = (price) => {
        if (!price) return '0 FCFA';
        return new Intl.NumberFormat('en-US', {
            style: 'currency',
            currency: 'XAF',
            minimumFractionDigits: 0,
        }).format(price);
    };

    const suspendSubscription = async () => {
        const confirmed = await confirmDialog({
            title: 'Suspend subscription?',
            message: 'This subscription will be suspended. The owner\'s plan features will be disabled until reactivated.',
            confirmText: 'Suspend',
            cancelText: 'Cancel',
            variant: 'warning',
        });

        if (!confirmed) return;

        router.post(`/admin/subscriptions/${props.subscription.id}/suspend`, {}, {
            preserveScroll: true,
            // ✅ No success toast — the controller flashes
            //    'Subscription suspended successfully.'
            onError: () => {
                error('Action Failed ❌', 'Failed to suspend subscription. Please try again.', { duration: 4000 });
            },
        });
    };

    const activateSubscription = async () => {
        const confirmed = await confirmDialog({
            title: 'Activate subscription?',
            message: 'This subscription will be marked active. The owner\'s plan features will be enabled.',
            confirmText: 'Activate',
            cancelText: 'Cancel',
            variant: 'primary',
        });

        if (!confirmed) return;

        router.post(`/admin/subscriptions/${props.subscription.id}/activate`, {}, {
            preserveScroll: true,
            // ✅ No success toast — the controller flashes
            //    'Subscription activated successfully.'
            onError: () => {
                error('Action Failed ❌', 'Failed to activate subscription. Please try again.', { duration: 4000 });
            },
        });
    };

    const cancelSubscription = async () => {
        const confirmed = await confirmDialog({
            title: 'Cancel subscription?',
            message: 'This subscription will be cancelled. This action cannot be undone from the admin panel.',
            confirmText: 'Cancel Subscription',
            cancelText: 'Keep Active',
            variant: 'danger',
        });

        if (!confirmed) return;

        router.post(`/admin/subscriptions/${props.subscription.id}/cancel`, {}, {
            preserveScroll: true,
            // ✅ No success toast — the controller flashes
            //    'Subscription cancelled successfully.'
            onError: () => {
                error('Action Failed ❌', 'Failed to cancel subscription. Please try again.', { duration: 4000 });
            },
        });
    };
</script>