<!-- resources/js/Pages/Admin/Subscriptions/Edit.vue -->
<template>
    <AuthenticatedLayout>
        <!-- COMPACT HEADER -->
        <PageHeader color="violet" :breadcrumb="[
            { label: 'Admin', href: '/admin/dashboard' },
            { label: 'Subscriptions', href: '/admin/subscriptions' },
            { label: '#' + subscription.id, href: `/admin/subscriptions/${subscription.id}` },
            { label: 'Edit' }
        ]">
            <template #icon>
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                    d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
            </template>
            <template #title>Edit Subscription #{{ subscription.id }}</template>
            <template #subtitle>{{ subscription.user?.name || 'N/A' }}</template>
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

        <div class="max-w-3xl mx-auto px-4 sm:px-6 lg:px-8 py-6">
            <!-- Subscription Summary Card -->
            <div
                class="bg-white dark:bg-gray-800 rounded-2xl shadow-sm border border-gray-100 dark:border-gray-700 p-6 mb-6 hover:shadow-md transition-shadow">
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
                    <div>
                        <p class="text-xs text-gray-500 dark:text-gray-400 font-medium uppercase tracking-wider">Owner</p>
                        <p class="text-sm font-semibold text-gray-900 dark:text-white mt-0.5">{{ subscription.user?.name || 'N/A' }}</p>
                    </div>
                    <div>
                        <p class="text-xs text-gray-500 dark:text-gray-400 font-medium uppercase tracking-wider">Plan</p>
                        <p class="text-sm font-semibold text-gray-900 dark:text-white mt-0.5">{{ subscription.plan?.name || 'N/A' }}</p>
                    </div>
                    <div>
                        <p class="text-xs text-gray-500 dark:text-gray-400 font-medium uppercase tracking-wider">ID</p>
                        <p class="text-sm font-semibold text-gray-900 dark:text-white font-mono mt-0.5">#{{ subscription.id }}</p>
                    </div>
                    <div>
                        <p class="text-xs text-gray-500 dark:text-gray-400 font-medium uppercase tracking-wider">Days Remaining</p>
                        <p class="text-sm font-semibold mt-0.5"
                            :class="getDaysRemainingClass(subscription.days_remaining)">
                            {{ subscription.days_remaining !== null ? subscription.days_remaining + ' days' : 'N/A' }}
                        </p>
                    </div>
                </div>
                <div class="mt-3 pt-3 border-t border-gray-100 dark:border-gray-700 grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <p class="text-xs text-gray-500 dark:text-gray-400 font-medium uppercase tracking-wider">Period</p>
                        <p class="text-sm text-gray-700 dark:text-gray-300 flex items-center gap-1.5">
                            <svg class="w-4 h-4 text-gray-400 dark:text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                            </svg>
                            {{ formatDate(subscription.start_date) }}
                            <span class="text-gray-400 dark:text-gray-500">→</span>
                            {{ formatDate(subscription.end_date) }}
                        </p>
                    </div>
                    <div>
                        <p class="text-xs text-gray-500 dark:text-gray-400 font-medium uppercase tracking-wider">Progress</p>
                        <div class="flex items-center gap-2 mt-0.5">
                            <div class="flex-1 h-1.5 bg-gray-200 dark:bg-gray-700 rounded-full overflow-hidden">
                                <div class="h-full rounded-full transition-all duration-1000"
                                    :class="getProgressBarClass(subscription.days_remaining)"
                                    :style="{ width: getProgressPercentage(subscription) + '%' }"></div>
                            </div>
                            <span class="text-xs font-semibold"
                                :class="getDaysRemainingClass(subscription.days_remaining)">
                                {{ subscription.days_remaining !== null ? subscription.days_remaining + 'd' : 'N/A' }}
                            </span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Edit Form -->
            <div
                class="bg-white dark:bg-gray-800 rounded-2xl shadow-sm border border-gray-100 dark:border-gray-700 overflow-hidden hover:shadow-md transition-shadow">
                <div
                    class="px-6 py-4 border-b border-gray-100 dark:border-gray-700 bg-gradient-to-r from-gray-50 to-white dark:from-gray-800 dark:to-gray-800/50 flex items-center gap-2">
                    <svg class="w-5 h-5 text-gray-400 dark:text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                    </svg>
                    <h3 class="text-sm font-semibold text-gray-700 dark:text-gray-300">Subscription Details</h3>
                    <span class="ml-auto text-xs text-gray-400 dark:text-gray-500">All fields marked * are required</span>
                </div>

                <div class="p-6">
                    <form @submit.prevent="submit" class="space-y-6">
                        <!-- Business -->
                        <!-- Owner Selection -->
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                                Owner (User) *
                            </label>
                            <select v-model="form.user_id"
                                class="w-full pl-10 pr-4 py-2.5 border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-900 text-gray-900 dark:text-white rounded-xl focus:outline-none focus:ring-2 focus:ring-primary-500 focus:border-transparent transition-colors appearance-none"
                                required>
                                <option value="">Select User</option>
                                <option v-for="user in users" :key="user.id" :value="user.id">
                                    {{ user.name }} ({{ user.email }}) — {{ user.role }}
                                </option>
                            </select>
                            <p v-if="form.errors.user_id" class="text-red-500 dark:text-red-400 text-sm mt-1">{{ form.errors.user_id }}
                            </p>
                        </div>



                        <!-- Plan -->
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                                Plan *
                            </label>
                            <div class="relative">
                                <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                    <svg class="h-5 w-5 text-gray-400 dark:text-gray-500" fill="none" stroke="currentColor"
                                        viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                                    </svg>
                                </div>
                                <select v-model="form.plan_id"
                                    class="w-full pl-10 pr-4 py-2.5 border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-900 text-gray-900 dark:text-white rounded-xl focus:outline-none focus:ring-2 focus:ring-primary-500 focus:border-transparent transition-colors appearance-none"
                                    required>
                                    <option value="">Select Plan</option>
                                    <option v-for="plan in plans" :key="plan.id" :value="plan.id">
                                        {{ plan.name }} ({{ formatPrice(plan.price_yearly) }}/year)
                                        <span v-if="plan.is_featured" class="text-yellow-500 ml-1">⭐</span>
                                    </option>
                                </select>
                                <div class="absolute inset-y-0 right-0 pr-3 flex items-center pointer-events-none">
                                    <svg class="h-5 w-5 text-gray-400 dark:text-gray-500" fill="none" stroke="currentColor"
                                        viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M19 9l-7 7-7-7" />
                                    </svg>
                                </div>
                            </div>
                            <p v-if="form.errors.plan_id" class="text-red-500 dark:text-red-400 text-sm mt-1 flex items-center gap-1">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                </svg>
                                {{ form.errors.plan_id }}
                            </p>
                        </div>

                        <!-- Status -->
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                                Status *
                            </label>
                            <div class="relative">
                                <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                    <svg class="h-5 w-5 text-gray-400 dark:text-gray-500" fill="none" stroke="currentColor"
                                        viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                                    </svg>
                                </div>
                                <select v-model="form.status"
                                    class="w-full pl-10 pr-4 py-2.5 border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-900 text-gray-900 dark:text-white rounded-xl focus:outline-none focus:ring-2 focus:ring-primary-500 focus:border-transparent transition-colors appearance-none"
                                    required>
                                    <option value="pending">⏳ Pending</option>
                                    <option value="active">✅ Active</option>
                                    <option value="expiring_soon">⚠️ Expiring Soon</option>
                                    <option value="expired">❌ Expired</option>
                                    <option value="grace_period">🔄 Grace Period</option>
                                    <option value="suspended">🚫 Suspended</option>
                                    <option value="cancelled">🗑️ Cancelled</option>
                                </select>
                                <div class="absolute inset-y-0 right-0 pr-3 flex items-center pointer-events-none">
                                    <svg class="h-5 w-5 text-gray-400 dark:text-gray-500" fill="none" stroke="currentColor"
                                        viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M19 9l-7 7-7-7" />
                                    </svg>
                                </div>
                            </div>
                            <p v-if="form.errors.status" class="text-red-500 dark:text-red-400 text-sm mt-1 flex items-center gap-1">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                </svg>
                                {{ form.errors.status }}
                            </p>
                        </div>

                        <!-- Dates -->
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                                    Start Date *
                                </label>
                                <div class="relative">
                                    <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                        <svg class="h-5 w-5 text-gray-400 dark:text-gray-500" fill="none" stroke="currentColor"
                                            viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                                        </svg>
                                    </div>
                                    <input type="date" v-model="form.start_date"
                                        class="w-full pl-10 pr-4 py-2.5 border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-900 text-gray-900 dark:text-white rounded-xl focus:outline-none focus:ring-2 focus:ring-primary-500 focus:border-transparent transition-colors"
                                        required />
                                </div>
                                <p v-if="form.errors.start_date"
                                    class="text-red-500 dark:text-red-400 text-sm mt-1 flex items-center gap-1">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                    </svg>
                                    {{ form.errors.start_date }}
                                </p>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                                    End Date *
                                </label>
                                <div class="relative">
                                    <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                        <svg class="h-5 w-5 text-gray-400 dark:text-gray-500" fill="none" stroke="currentColor"
                                            viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                                        </svg>
                                    </div>
                                    <input type="date" v-model="form.end_date"
                                        class="w-full pl-10 pr-4 py-2.5 border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-900 text-gray-900 dark:text-white rounded-xl focus:outline-none focus:ring-2 focus:ring-primary-500 focus:border-transparent transition-colors"
                                        required />
                                </div>
                                <p v-if="form.errors.end_date"
                                    class="text-red-500 dark:text-red-400 text-sm mt-1 flex items-center gap-1">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                    </svg>
                                    {{ form.errors.end_date }}
                                </p>
                            </div>
                        </div>

                        <!-- Trial Options -->
                        <div class="flex items-center gap-4 p-4 bg-gray-50 dark:bg-gray-900/50 rounded-xl border border-gray-100 dark:border-gray-700">
                            <label class="flex items-center gap-2 text-sm text-gray-700 dark:text-gray-300 cursor-pointer">
                                <input type="checkbox" v-model="form.is_trial"
                                    class="w-4 h-4 text-primary-600 focus:ring-primary-500 border-gray-300 dark:border-gray-600 rounded transition-colors" />
                                <span class="flex items-center gap-1">
                                    <span class="text-lg">🧪</span>
                                    This is a trial subscription
                                </span>
                            </label>
                            <span v-if="form.is_trial"
                                class="ml-auto text-xs bg-purple-100 dark:bg-purple-900/30 text-purple-700 dark:text-purple-400 px-2 py-0.5 rounded-full">
                                Trial Mode
                            </span>
                        </div>

                        <div v-if="form.is_trial" class="pl-6 border-l-2 border-purple-200 dark:border-purple-800 animate-slide-down">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                                    Trial End Date
                                </label>
                                <div class="relative">
                                    <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                        <svg class="h-5 w-5 text-gray-400 dark:text-gray-500" fill="none" stroke="currentColor"
                                            viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                                        </svg>
                                    </div>
                                    <input type="date" v-model="form.trial_end_date"
                                        class="w-full pl-10 pr-4 py-2.5 border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-900 text-gray-900 dark:text-white rounded-xl focus:outline-none focus:ring-2 focus:ring-primary-500 focus:border-transparent transition-colors" />
                                </div>
                                <p v-if="form.errors.trial_end_date"
                                    class="text-red-500 dark:text-red-400 text-sm mt-1 flex items-center gap-1">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                    </svg>
                                    {{ form.errors.trial_end_date }}
                                </p>
                            </div>
                        </div>

                        <!-- Buttons -->
                        <div class="flex flex-col sm:flex-row justify-end gap-3 pt-6 border-t border-gray-200 dark:border-gray-700">
                            <a href="/admin/subscriptions"
                                class="inline-flex items-center justify-center px-6 py-2.5 border border-gray-300 dark:border-gray-600 text-gray-700 dark:text-gray-300 rounded-xl hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors font-medium text-sm">
                                Cancel
                            </a>
                            <button type="submit"
                                class="inline-flex items-center justify-center px-6 py-2.5 bg-gradient-to-r from-primary-600 to-primary-700 text-white rounded-xl hover:from-primary-700 hover:to-primary-800 transition-all duration-200 shadow-lg shadow-primary-100 dark:shadow-none hover:shadow-primary-200 font-medium text-sm disabled:opacity-50 disabled:cursor-not-allowed"
                                :disabled="form.processing">
                                <span v-if="form.processing" class="flex items-center gap-2">
                                    <svg class="animate-spin h-4 w-4" fill="none" viewBox="0 0 24 24">
                                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor"
                                            stroke-width="4"></circle>
                                        <path class="opacity-75" fill="currentColor"
                                            d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z">
                                        </path>
                                    </svg>
                                    Updating...
                                </span>
                                <span v-else>
                                    <svg class="w-4 h-4 inline mr-2" fill="none" stroke="currentColor"
                                        viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M5 13l4 4L19 7" />
                                    </svg>
                                    Update Subscription
                                </span>
                            </button>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Danger Zone -->
            <div
                class="mt-6 bg-gradient-to-r from-red-50 to-red-50/50 dark:from-red-900/20 dark:to-red-900/10 border border-red-200 dark:border-red-800 rounded-2xl p-6 hover:shadow-md transition-shadow">
                <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                    <div>
                        <div class="flex items-center gap-2">
                            <div class="w-8 h-8 rounded-full bg-red-100 dark:bg-red-900/40 flex items-center justify-center">
                                <svg class="w-4 h-4 text-red-600 dark:text-red-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                                </svg>
                            </div>
                            <h4 class="text-sm font-semibold text-red-800 dark:text-red-400">Danger Zone</h4>
                        </div>
                        <p class="text-sm text-red-700 dark:text-red-300 mt-1 ml-10">Mark this subscription as cancelled. The record is
                            preserved
                            for history.</p>
                    </div>
                    <button @click="cancelSubscription"
                        class="inline-flex items-center gap-2 px-6 py-2.5 bg-gradient-to-r from-red-500 to-red-600 text-white rounded-xl hover:from-red-600 hover:to-red-700 transition-all duration-200 shadow-lg shadow-red-100 dark:shadow-none hover:shadow-red-200 text-sm font-medium flex-shrink-0">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                        </svg>
                        Cancel Subscription
                    </button>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>


<script setup>
    import { useForm } from '@inertiajs/vue3';
    import { router } from '@inertiajs/vue3';
    import { useToast } from '@/composables/useToast';
    import { useConfirm } from '@/composables/useConfirm';
    import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
    import { useStatusBadge } from '@/composables/useStatusBadge';
    import PageHeader from '@/Components/PageHeader.vue';

    const props = defineProps({
        subscription: Object,
        users: Array,
        plans: Array,
    });

    const { error } = useToast();
    const { confirm: confirmDialog } = useConfirm();
    const { subscription: statusClass } = useStatusBadge();

    // ✅ useForm — gives form.errors + form.processing automatically
    const form = useForm({
        user_id: props.subscription.user_id,
        plan_id: props.subscription.plan_id,
        status: props.subscription.status,
        start_date: props.subscription.start_date_formatted || '',
        end_date: props.subscription.end_date_formatted || '',
        is_trial: props.subscription.is_trial || false,
        trial_end_date: props.subscription.trial_end_date_formatted || '',
    });

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

    // ✅ Now computes elapsed vs total from actual dates.
    //    Previous version hardcoded 365 days, which was wrong
    //    for monthly subscriptions.
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

    const formatPrice = (price) => {
        if (!price) return 'N/A';
        return new Intl.NumberFormat('en-US', {
            style: 'currency',
            currency: 'XAF',
            minimumFractionDigits: 0,
        }).format(price);
    };

    const submit = () => {
        form.put(`/admin/subscriptions/${props.subscription.id}`, {
            preserveScroll: true,
            // ✅ No success toast — the controller flashes
            //    'Subscription updated successfully.' and redirects to
            //    /admin/subscriptions, where the toast fires on the index.
            //
            // ✅ No router.visit() — the server redirect already navigates.
            onError: (errors) => {
                const firstError = Object.values(errors)[0];
                error('Update Failed ❌', firstError || 'Failed to update subscription. Please check the form and try again.', { duration: 5000 });
            },
        });
    };

    const cancelSubscription = async () => {
        const confirmed = await confirmDialog({
            title: 'Cancel subscription?',
            message: `This will mark the subscription for "${props.subscription.user?.name || 'this user'}" as cancelled. The record is preserved for history.`,
            confirmText: 'Cancel Subscription',
            cancelText: 'Keep Active',
            variant: 'danger',
        });

        if (!confirmed) return;

        router.post(`/admin/subscriptions/${props.subscription.id}/cancel`, {}, {
            preserveScroll: true,
            // ✅ No success toast — the controller flashes
            //    'Subscription cancelled successfully.' and redirects to
            //    /admin/subscriptions.
            //
            // ✅ No router.visit() — the server redirect already navigates.
            onError: () => {
                error('Action Failed ❌', 'Failed to cancel subscription. Please try again.', { duration: 4000 });
            },
        });
    };
</script>