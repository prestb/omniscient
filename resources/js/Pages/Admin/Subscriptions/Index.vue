<!-- resources/js/Pages/Admin/Subscriptions/Index.vue -->
<template>
    <AuthenticatedLayout>
        <!-- COMPACT HEADER -->
        <PageHeader color="violet" :breadcrumb="[
            { label: 'Admin', href: '/admin/dashboard' },
            { label: 'Subscriptions' }
        ]">
            <template #icon>
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                    d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z" />
            </template>
            <template #title>Subscriptions</template>
            <template #subtitle>Manage business subscriptions and plans</template>
            <template #actions>
                <button @click="exportCsv"
                    class="inline-flex items-center gap-2 px-4 py-2 border border-gray-300 dark:border-gray-600 text-gray-700 dark:text-gray-300 rounded-xl hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors text-sm font-semibold">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                    </svg>
                    Export CSV
                </button>
                <a href="/admin/subscriptions/create"
                    class="inline-flex items-center gap-2 px-4 py-2 bg-gradient-to-r from-primary-600 to-primary-700 text-white rounded-xl font-semibold hover:from-primary-700 hover:to-primary-800 transition-all shadow-lg shadow-primary-500/25 text-sm">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                    </svg>
                    Add Subscription
                </a>
            </template>
        </PageHeader>
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8 space-y-6">

            <!-- STATS -->
            <div v-if="stats" class="grid grid-cols-2 lg:grid-cols-4 gap-4">
                <div
                    class="bg-white dark:bg-gray-800 rounded-2xl border border-gray-100 dark:border-gray-700 p-5 hover:shadow-md transition-shadow">
                    <div class="flex items-center justify-between mb-3">
                        <div
                            class="w-11 h-11 rounded-2xl bg-emerald-50 dark:bg-emerald-900/30 flex items-center justify-center">
                            <svg class="w-5 h-5 text-emerald-600 dark:text-emerald-400" fill="none"
                                stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                        </div>
                        <span v-if="stats.growth_percent !== 0"
                            class="inline-flex items-center gap-1 px-2 py-0.5 text-[10px] font-bold uppercase tracking-wide rounded-full"
                            :class="stats.growth_percent >= 0
                                ? 'bg-emerald-100 text-emerald-700 dark:bg-emerald-900/30 dark:text-emerald-400'
                                : 'bg-red-100 text-red-700 dark:bg-red-900/30 dark:text-red-400'">
                            {{ stats.growth_percent >= 0 ? '↑' : '↓' }} {{ Math.abs(stats.growth_percent) }}%
                        </span>
                    </div>
                    <p class="text-3xl font-bold text-gray-900 dark:text-white tracking-tight">{{ stats.active_count }}
                    </p>
                    <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">Active subscriptions</p>
                </div>

                <div
                    class="bg-white dark:bg-gray-800 rounded-2xl border border-gray-100 dark:border-gray-700 p-5 hover:shadow-md transition-shadow">
                    <div class="flex items-center justify-between mb-3">
                        <div
                            class="w-11 h-11 rounded-2xl bg-blue-50 dark:bg-blue-900/30 flex items-center justify-center">
                            <svg class="w-5 h-5 text-blue-600 dark:text-blue-400" fill="none" stroke="currentColor"
                                viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                        </div>
                    </div>
                    <p class="text-2xl font-bold text-gray-900 dark:text-white tracking-tight truncate">
                        {{ formatPrice(stats.mrr) }}
                    </p>
                    <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">Monthly recurring revenue</p>
                </div>

                <div
                    class="bg-white dark:bg-gray-800 rounded-2xl border border-gray-100 dark:border-gray-700 p-5 hover:shadow-md transition-shadow">
                    <div class="flex items-center justify-between mb-3">
                        <div
                            class="w-11 h-11 rounded-2xl bg-amber-50 dark:bg-amber-900/30 flex items-center justify-center">
                            <svg class="w-5 h-5 text-amber-600 dark:text-amber-400" fill="none" stroke="currentColor"
                                viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                            </svg>
                        </div>
                        <a v-if="stats.expiring_soon > 0" href="/admin/subscriptions?expiring_in=7"
                            class="text-[10px] font-bold uppercase tracking-wide text-amber-600 dark:text-amber-400 hover:text-amber-800 dark:hover:text-amber-300">
                            View →
                        </a>
                    </div>
                    <p class="text-3xl font-bold text-gray-900 dark:text-white tracking-tight">{{ stats.expiring_soon }}
                    </p>
                    <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">Expiring in 7 days</p>
                </div>

                <div
                    class="bg-white dark:bg-gray-800 rounded-2xl border border-gray-100 dark:border-gray-700 p-5 hover:shadow-md transition-shadow">
                    <div class="flex items-center justify-between mb-3">
                        <div
                            class="w-11 h-11 rounded-2xl bg-red-50 dark:bg-red-900/30 flex items-center justify-center">
                            <svg class="w-5 h-5 text-red-600 dark:text-red-400" fill="none" stroke="currentColor"
                                viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M13 17h8m0 0V9m0 8l-8-8-4 4-6-6" />
                            </svg>
                        </div>
                    </div>
                    <p class="text-3xl font-bold text-gray-900 dark:text-white tracking-tight">{{ stats.churn_rate }}%
                    </p>
                    <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">Churn rate (this month)</p>
                </div>
            </div>

            <!-- FILTERS -->
            <div class="bg-white dark:bg-gray-800 rounded-2xl border border-gray-100 dark:border-gray-700 p-5">
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 mb-4">
                    <div class="relative">
                        <div class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none">
                            <svg class="h-4 w-4 text-gray-400 dark:text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                            </svg>
                        </div>
                        <input type="text" v-model="filterState.search" @keyup.enter="applyFilters"
                            placeholder="Search business or owner…"
                            class="w-full pl-11 pr-4 py-2.5 border border-gray-200 dark:border-gray-600 rounded-xl bg-white dark:bg-gray-900 text-gray-900 dark:text-white placeholder-gray-400 dark:placeholder-gray-500 focus:outline-none focus:ring-2 focus:ring-primary-500 focus:border-transparent transition-colors text-sm" />
                    </div>

                    <select v-model="filterState.status" @change="applyFilters"
                        class="w-full px-4 py-2.5 border border-gray-200 dark:border-gray-600 rounded-xl bg-white dark:bg-gray-900 text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-primary-500 focus:border-transparent transition-colors text-sm appearance-none">
                        <option value="">All statuses</option>
                        <option v-for="s in statuses" :key="s" :value="s">
                            {{ formatStatus(s) }}
                        </option>
                    </select>

                    <select v-model="filterState.plan_id" @change="applyFilters"
                        class="w-full px-4 py-2.5 border border-gray-200 dark:border-gray-600 rounded-xl bg-white dark:bg-gray-900 text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-primary-500 focus:border-transparent transition-colors text-sm appearance-none">
                        <option value="">All plans</option>
                        <option v-for="plan in plans" :key="plan.id" :value="plan.id">
                            {{ plan.name }}
                        </option>
                    </select>

                    <select v-model="filterState.expiring_in" @change="applyFilters"
                        class="w-full px-4 py-2.5 border border-gray-200 dark:border-gray-600 rounded-xl bg-white dark:bg-gray-900 text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-primary-500 focus:border-transparent transition-colors text-sm appearance-none">
                        <option value="">Any expiry</option>
                        <option value="3">Expiring in 3 days</option>
                        <option value="7">Expiring in 7 days</option>
                        <option value="14">Expiring in 14 days</option>
                        <option value="30">Expiring in 30 days</option>
                    </select>
                </div>

                <!-- Date range + actions -->
                <div class="flex flex-col sm:flex-row gap-3 items-stretch sm:items-center">
                    <div class="flex items-center gap-2 flex-1">
                        <input type="date" v-model="filterState.date_from"
                            class="flex-1 px-4 py-2.5 border border-gray-200 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-900 text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-primary-500 focus:border-transparent transition-colors text-sm" />
                        <span class="text-gray-400 dark:text-gray-500 text-sm font-medium">to</span>
                        <input type="date" v-model="filterState.date_to"
                            class="flex-1 px-4 py-2.5 border border-gray-200 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-900 text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-primary-500 focus:border-transparent transition-colors text-sm" />
                    </div>
                    <div class="flex gap-2">
                        <button @click="applyFilters"
                            class="px-5 py-2.5 bg-gradient-to-r from-primary-600 to-primary-700 text-white rounded-xl hover:from-primary-700 hover:to-primary-800 transition-all shadow-lg shadow-primary-500/25 font-semibold text-sm">
                            Apply
                        </button>
                        <button @click="resetFilters"
                            class="px-5 py-2.5 border border-gray-300 dark:border-gray-600 text-gray-700 dark:text-gray-300 rounded-xl hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors font-semibold text-sm">
                            Reset
                        </button>
                    </div>
                </div>

                <!-- Active filters -->
                <div v-if="hasActiveFilters"
                    class="mt-4 pt-4 border-t border-gray-100 dark:border-gray-700 flex flex-wrap items-center gap-2">
                    <span class="text-xs text-gray-500 dark:text-gray-400 font-medium">Active:</span>
                    <span v-for="(value, key) in activeFilters" :key="key"
                        class="inline-flex items-center gap-1.5 px-2.5 py-1 bg-primary-100 dark:bg-primary-900/30 text-primary-700 dark:text-primary-400 rounded-lg text-xs font-bold">
                        {{ filterLabel(key) }}: {{ value }}
                        <button @click="removeFilter(key)" class="hover:text-primary-900 dark:hover:text-primary-200">
                            <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M6 18L18 6M6 6l12 12" />
                            </svg>
                        </button>
                    </span>
                </div>
            </div>

            <!-- BULK ACTIONS -->
            <div v-if="selectedIds.length > 0"
                class="bg-gradient-to-r from-primary-600 to-purple-600 rounded-2xl shadow-lg p-4 text-white flex items-center justify-between flex-wrap gap-3">
                <div class="flex items-center gap-3">
                    <div
                        class="w-8 h-8 rounded-full bg-white/20 backdrop-blur-sm flex items-center justify-center text-sm font-bold">
                        {{ selectedIds.length }}
                    </div>
                    <span class="text-sm font-semibold">{{ selectedIds.length }} selected</span>
                    <button @click="clearSelection"
                        class="text-xs text-white/80 hover:text-white underline font-medium">
                        Clear
                    </button>
                </div>
                <div class="flex flex-wrap gap-2">
                    <button @click="bulkAction('activate')"
                        class="px-3.5 py-1.5 bg-white/20 hover:bg-white/30 backdrop-blur-sm rounded-lg text-xs font-bold uppercase tracking-wide transition-colors">
                        Activate
                    </button>
                    <button @click="bulkAction('extend')"
                        class="px-3.5 py-1.5 bg-white/20 hover:bg-white/30 backdrop-blur-sm rounded-lg text-xs font-bold uppercase tracking-wide transition-colors">
                        Extend 30 Days
                    </button>
                    <button @click="bulkAction('suspend')"
                        class="px-3.5 py-1.5 bg-white/20 hover:bg-white/30 backdrop-blur-sm rounded-lg text-xs font-bold uppercase tracking-wide transition-colors">
                        Suspend
                    </button>
                    <button @click="bulkAction('cancel')"
                        class="px-3.5 py-1.5 bg-white/20 hover:bg-white/30 backdrop-blur-sm rounded-lg text-xs font-bold uppercase tracking-wide transition-colors">
                        Cancel
                    </button>
                    <button @click="bulkAction('delete')"
                        class="px-3.5 py-1.5 bg-red-500/80 hover:bg-red-500 rounded-lg text-xs font-bold uppercase tracking-wide transition-colors">
                        Delete
                    </button>
                </div>
            </div>

            <!-- TABLE -->
            <div
                class="bg-white dark:bg-gray-800 rounded-2xl border border-gray-100 dark:border-gray-700 overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                        <thead class="bg-gray-50 dark:bg-gray-900/50">
                            <tr>
                                <th class="w-12 px-4 py-4 text-left">
                                    <input type="checkbox" :checked="allSelected" @change="toggleAll"
                                        class="rounded border-gray-300 dark:border-gray-600 text-primary-600 focus:ring-primary-500" />
                                </th>
                                <th
                                    class="px-6 py-4 text-left text-[10px] font-bold text-gray-500 dark:text-gray-400 uppercase tracking-widest">
                                    Owner</th>
                                <th
                                    class="px-6 py-4 text-left text-[10px] font-bold text-gray-500 dark:text-gray-400 uppercase tracking-widest">
                                    Plan</th>
                                <th
                                    class="px-6 py-4 text-left text-[10px] font-bold text-gray-500 dark:text-gray-400 uppercase tracking-widest">
                                    Period</th>
                                <th
                                    class="px-6 py-4 text-left text-[10px] font-bold text-gray-500 dark:text-gray-400 uppercase tracking-widest">
                                    Status</th>
                                <th
                                    class="px-6 py-4 text-right text-[10px] font-bold text-gray-500 dark:text-gray-400 uppercase tracking-widest">
                                    Actions</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                            <tr v-for="subscription in subscriptions.data" :key="subscription.id"
                                class="transition-colors" :class="selectedIds.includes(subscription.id)
                                    ? 'bg-primary-50/60 dark:bg-primary-900/10'
                                    : 'hover:bg-gray-50/50 dark:hover:bg-gray-700/30'">
                                <td class="px-4 py-4">
                                    <input type="checkbox" :value="subscription.id" v-model="selectedIds"
                                        class="rounded border-gray-300 dark:border-gray-600 text-primary-600 focus:ring-primary-500" />
                                </td>
                                <td class="px-6 py-4">
                                    <div class="flex items-center gap-3 min-w-0">
                                        <div
                                            class="w-10 h-10 rounded-2xl bg-gradient-to-br from-primary-500 to-primary-700 flex items-center justify-center text-white text-xs font-bold flex-shrink-0 shadow-md">
                                            {{ getInitials(subscription.user?.name || 'N/A') }}
                                        </div>
                                        <div class="min-w-0">
                                            <p
                                                class="text-sm font-bold text-gray-900 dark:text-white truncate tracking-tight">
                                                {{ subscription.user?.name || 'Deleted User' }}
                                            </p>
                                            <p class="text-xs text-gray-500 dark:text-gray-400 truncate">
                                                {{ subscription.user?.email || 'N/A' }}
                                            </p>
                                            <p v-if="subscription.business"
                                                class="text-xs text-gray-400 dark:text-gray-500 truncate mt-0.5">
                                                🏢 {{ subscription.business.name }}
                                            </p>
                                        </div>
                                    </div>
                                </td>
                                <td class="px-6 py-4">
                                    <p class="text-sm font-bold text-gray-900 dark:text-white tracking-tight">
                                        {{ subscription.plan?.name || 'N/A' }}
                                    </p>
                                    <p class="text-xs text-gray-500 dark:text-gray-400">
                                        {{ formatPrice(subscription.total_price) }}
                                    </p>
                                </td>
                                <td class="px-6 py-4">
                                    <p class="text-xs text-gray-700 dark:text-gray-300">
                                        {{ formatDate(subscription.start_date) }}
                                        <span class="text-gray-400 dark:text-gray-500 mx-1">→</span>
                                        {{ formatDate(subscription.end_date) }}
                                    </p>
                                    <p v-if="subscription.days_remaining !== null" class="text-xs mt-0.5 font-bold"
                                        :class="getDaysClass(subscription.days_remaining)">
                                        {{ subscription.days_remaining }} days left
                                    </p>
                                </td>
                                <td class="px-6 py-4">
                                    <span :class="statusClass(subscription.status)">
                                        {{ formatStatus(subscription.status) }}
                                    </span>
                                </td>
                                <td class="px-6 py-4">
                                    <div class="flex items-center justify-end gap-1">
                                        <a :href="`/admin/subscriptions/${subscription.id}`"
                                            class="w-8 h-8 inline-flex items-center justify-center text-gray-500 dark:text-gray-400 hover:text-primary-600 dark:hover:text-primary-400 hover:bg-primary-50 dark:hover:bg-primary-900/20 rounded-lg transition-colors"
                                            title="View">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                    d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                    d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                                            </svg>
                                        </a>
                                        <a :href="`/admin/subscriptions/${subscription.id}/edit`"
                                            class="w-8 h-8 inline-flex items-center justify-center text-gray-500 dark:text-gray-400 hover:text-primary-600 dark:hover:text-primary-400 hover:bg-primary-50 dark:hover:bg-primary-900/20 rounded-lg transition-colors"
                                            title="Edit">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                    d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                                            </svg>
                                        </a>
                                    </div>
                                </td>
                            </tr>
                            <tr v-if="!subscriptions.data || subscriptions.data.length === 0">
                                <td colspan="6" class="px-6 py-16 text-center">
                                    <div
                                        class="w-20 h-20 mx-auto mb-4 rounded-2xl bg-gradient-to-br from-gray-100 to-gray-50 dark:from-gray-700 dark:to-gray-800 flex items-center justify-center">
                                        <svg class="w-10 h-10 text-gray-400 dark:text-gray-500" fill="none" stroke="currentColor"
                                            viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                                        </svg>
                                    </div>
                                    <p class="text-gray-600 dark:text-gray-300 font-bold tracking-tight">No
                                        subscriptions found
                                    </p>
                                    <p class="text-sm text-gray-400 dark:text-gray-500 mt-1">Try adjusting your filters</p>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
                <div v-if="subscriptions.links && subscriptions.links.length > 3"
                    class="px-6 py-4 border-t border-gray-100 dark:border-gray-700">
                    <Pagination :links="subscriptions.links" />
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>


<script setup>
    import { ref, reactive, computed } from 'vue';
    import { router } from '@inertiajs/vue3';
    import { useToast } from '@/composables/useToast';
    import { useConfirm } from '@/composables/useConfirm';
    import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
    import Pagination from '@/Components/Pagination.vue';
    import { useStatusBadge } from '@/composables/useStatusBadge';
    import PageHeader from '@/Components/PageHeader.vue';

    const props = defineProps({
        subscriptions: Object,
        filters: Object,
        statuses: Array,
        plans: {
            type: Array,
            default: () => [],
        },
        stats: {
            type: Object,
            default: null,
        },
    });

    const { error } = useToast();
    const { confirm: confirmDialog } = useConfirm();

    // ✅ Renamed to avoid shadowing the `filters` prop
    const filterState = reactive({
        search: props.filters?.search || '',
        status: props.filters?.status || '',
        plan_id: props.filters?.plan_id || '',
        date_from: props.filters?.date_from || '',
        date_to: props.filters?.date_to || '',
        expiring_in: props.filters?.expiring_in || '',
    });

    const selectedIds = ref([]);

    const allSelected = computed(() => {
        return props.subscriptions?.data?.length > 0
            && selectedIds.value.length === props.subscriptions.data.length;
    });

    const hasActiveFilters = computed(() => {
        return !!(filterState.search || filterState.status || filterState.plan_id || filterState.date_from || filterState.date_to || filterState.expiring_in);
    });

    const activeFilters = computed(() => {
        const active = {};
        if (filterState.search) active.search = filterState.search;
        if (filterState.status) active.status = formatStatus(filterState.status);
        if (filterState.plan_id) {
            const plan = props.plans.find(p => p.id == filterState.plan_id);
            active.plan = plan?.name || filterState.plan_id;
        }
        if (filterState.date_from) active.from = filterState.date_from;
        if (filterState.date_to) active.to = filterState.date_to;
        if (filterState.expiring_in) active.expiring = `Within ${filterState.expiring_in}d`;
        return active;
    });

    const filterLabel = (key) => {
        const labels = {
            search: 'Search',
            status: 'Status',
            plan: 'Plan',
            from: 'From',
            to: 'To',
            expiring: 'Expiring',
        };
        return labels[key] || key;
    };

    const formatStatus = (status) => {
        if (!status) return '';
        return status.replace(/_/g, ' ').replace(/\b\w/g, l => l.toUpperCase());
    };

    const getInitials = (name) => {
        if (!name) return '?';
        return name.split(' ').map(n => n[0]).join('').toUpperCase().slice(0, 2);
    };

    const { subscription: statusClass } = useStatusBadge();

    const getDaysClass = (days) => {
        if (days < 7) return 'text-red-600 dark:text-red-400';
        if (days < 30) return 'text-amber-600 dark:text-amber-400';
        return 'text-gray-500 dark:text-gray-400';
    };

    const formatDate = (date) => {
        if (!date) return 'N/A';
        return new Date(date).toLocaleDateString('en-US', { month: 'short', day: 'numeric', year: 'numeric' });
    };

    const formatPrice = (price) => {
        if (!price) return '0 XAF';
        return new Intl.NumberFormat('en-US', {
            style: 'currency',
            currency: 'XAF',
            minimumFractionDigits: 0,
        }).format(price);
    };

    const applyFilters = () => {
        router.get('/admin/subscriptions', filterState, {
            preserveState: true,
            preserveScroll: true,
        });
    };

    const resetFilters = () => {
        Object.keys(filterState).forEach(k => filterState[k] = '');
        applyFilters();
    };

    const removeFilter = (key) => {
        const map = {
            search: 'search', status: 'status', plan: 'plan_id',
            from: 'date_from', to: 'date_to', expiring: 'expiring_in',
        };
        if (map[key]) filterState[map[key]] = '';
        applyFilters();
    };

    const toggleAll = (e) => {
        if (e.target.checked) {
            selectedIds.value = props.subscriptions.data.map(s => s.id);
        } else {
            selectedIds.value = [];
        }
    };

    const clearSelection = () => {
        selectedIds.value = [];
    };

    const bulkAction = async (action) => {
        const config = {
            activate: { label: 'Activate', variant: 'primary', verb: 'activate' },
            extend: { label: 'Extend 30 Days', variant: 'primary', verb: 'extend by 30 days' },
            suspend: { label: 'Suspend', variant: 'warning', verb: 'suspend' },
            cancel: { label: 'Cancel', variant: 'danger', verb: 'cancel' },
            delete: { label: 'Delete', variant: 'danger', verb: 'permanently delete' },
        };

        const c = config[action] || { label: 'Confirm', variant: 'primary', verb: action };

        const confirmed = await confirmDialog({
            title: `${c.label} ${selectedIds.value.length} subscription${selectedIds.value.length === 1 ? '' : 's'}?`,
            message: action === 'delete'
                ? 'Selected subscriptions will be permanently deleted. This action cannot be undone.'
                : `Selected subscriptions will be ${c.verb}d.`,
            confirmText: c.label,
            cancelText: 'Cancel',
            variant: c.variant,
        });

        if (!confirmed) return;

        router.post('/admin/subscriptions/bulk-action', {
            ids: selectedIds.value,
            action,
            extend_days: action === 'extend' ? 30 : null,
        }, {
            preserveScroll: true,
            onSuccess: () => {
                // ✅ No success toast — the controller flashes
                //    '{N} subscription(s) processed successfully.'
                clearSelection();
            },
            onError: () => {
                // Client-side fallback for validation / network errors.
                error('Bulk Action Failed ❌', 'Please try again.', { duration: 4000 });
            },
        });
    };

    const exportCsv = () => {
        // Strip empty values so the URL stays clean
        const params = new URLSearchParams();
        Object.entries(filterState).forEach(([key, value]) => {
            if (value !== '' && value !== null && value !== undefined) {
                params.append(key, value);
            }
        });
        const qs = params.toString();
        window.location.href = `/admin/subscriptions/export${qs ? '?' + qs : ''}`;
    };
</script>