<template>
    <AuthenticatedLayout>
        <!-- COMPACT HEADER -->
        <PageHeader color="primary" :breadcrumb="[
            { label: 'Admin', href: '/admin/dashboard' },
            { label: 'Businesses' }
        ]">
            <template #icon>
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                    d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" />
            </template>
            <template #title>Manage businesses</template>
            <template #subtitle>All businesses on the platform</template>
            <template #actions>
                <label
                    class="inline-flex items-center gap-2 px-4 py-2 bg-gray-50 dark:bg-gray-700 border border-gray-200 dark:border-gray-600 rounded-xl cursor-pointer hover:bg-gray-100 dark:hover:bg-gray-600 transition-colors text-sm font-semibold text-gray-700 dark:text-gray-300">
                    <input type="checkbox" :checked="!!filters.show_deleted" @change="toggleDeleted"
                        class="rounded border-gray-300 text-primary-600 focus:ring-primary-500" />
                    <span>Show deleted</span>
                    <span v-if="stats.deleted > 0" class="px-2 py-0.5 text-[10px] font-bold rounded-full"
                        :class="filters.show_deleted ? 'bg-red-500 text-white' : 'bg-gray-200 dark:bg-gray-600 text-gray-600 dark:text-gray-300'">
                        {{ stats.deleted }}
                    </span>
                </label>
            </template>
        </PageHeader>

        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8 space-y-6">

            <!-- STATS -->
            <div class="grid grid-cols-2 sm:grid-cols-4 gap-4">
                <div
                    class="bg-white dark:bg-gray-800 rounded-2xl border border-gray-100 dark:border-gray-700 p-4 hover:shadow-md transition-shadow">
                    <p class="text-[10px] font-bold text-gray-400 dark:text-gray-500 uppercase tracking-widest">Total
                    </p>
                    <p class="text-2xl font-bold text-gray-900 dark:text-white tracking-tight mt-1">{{ stats.total }}
                    </p>
                </div>
                <div
                    class="bg-white dark:bg-gray-800 rounded-2xl border border-gray-100 dark:border-gray-700 p-4 hover:shadow-md transition-shadow">
                    <p class="text-[10px] font-bold text-gray-400 dark:text-gray-500 uppercase tracking-widest">
                        Published</p>
                    <p class="text-2xl font-bold text-emerald-600 tracking-tight mt-1">{{ stats.published }}</p>
                </div>
                <div
                    class="bg-white dark:bg-gray-800 rounded-2xl border border-gray-100 dark:border-gray-700 p-4 hover:shadow-md transition-shadow">
                    <p class="text-[10px] font-bold text-gray-400 dark:text-gray-500 uppercase tracking-widest">Pending
                    </p>
                    <p class="text-2xl font-bold text-amber-600 tracking-tight mt-1">{{ stats.pending }}</p>
                </div>
                <div
                    class="bg-white dark:bg-gray-800 rounded-2xl border border-gray-100 dark:border-gray-700 p-4 hover:shadow-md transition-shadow">
                    <p class="text-[10px] font-bold text-gray-400 dark:text-gray-500 uppercase tracking-widest">Deleted
                    </p>
                    <p class="text-2xl font-bold text-red-600 tracking-tight mt-1">{{ stats.deleted }}</p>
                </div>
            </div>

            <!-- FILTERS -->
            <div class="bg-white dark:bg-gray-800 rounded-2xl border border-gray-100 dark:border-gray-700 p-5">
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 items-end">
                    <div class="lg:col-span-2">
                        <label
                            class="block text-[10px] font-bold text-gray-400 dark:text-gray-500 uppercase tracking-widest mb-2">Search</label>
                        <div class="relative">
                            <div class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none">
                                <svg class="h-4 w-4 text-gray-400" fill="none" stroke="currentColor"
                                    viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                                </svg>
                            </div>
                            <input type="text" v-model="filters.search" @keyup.enter="applyFilters"
                                placeholder="Search businesses or owners…"
                                class="w-full pl-11 pr-4 py-2.5 border border-gray-200 dark:border-gray-600 rounded-xl bg-white dark:bg-gray-900 text-gray-900 dark:text-white placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-primary-500 focus:border-transparent transition-colors text-sm" />
                        </div>
                    </div>
                    <div>
                        <label
                            class="block text-[10px] font-bold text-gray-400 dark:text-gray-500 uppercase tracking-widest mb-2">Status</label>
                        <select v-model="filters.status" @change="applyFilters" :disabled="!!filters.show_deleted"
                            class="w-full px-4 py-2.5 border border-gray-200 dark:border-gray-600 rounded-xl bg-white dark:bg-gray-900 text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-primary-500 focus:border-transparent transition-colors text-sm appearance-none disabled:opacity-50">
                            <option value="">All statuses</option>
                            <option v-for="status in statuses" :key="status" :value="status">
                                {{ formatStatus(status) }}
                            </option>
                        </select>
                    </div>
                    <div class="flex gap-2">
                        <button @click="applyFilters"
                            class="flex-1 px-5 py-2.5 bg-gradient-to-r from-primary-600 to-primary-700 text-white rounded-xl hover:from-primary-700 hover:to-primary-800 transition-all shadow-lg shadow-primary-500/25 hover:shadow-primary-500/40 font-semibold text-sm">
                            Apply
                        </button>
                        <button @click="resetFilters"
                            class="px-5 py-2.5 border border-gray-300 dark:border-gray-600 text-gray-700 dark:text-gray-300 rounded-xl hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors font-semibold text-sm">
                            Reset
                        </button>
                    </div>
                </div>

                <!-- Active chips -->
                <div v-if="hasActiveFilters"
                    class="mt-4 pt-4 border-t border-gray-100 dark:border-gray-700 flex flex-wrap items-center gap-2">
                    <span class="text-xs text-gray-500 dark:text-gray-400 font-medium">Active:</span>
                    <span v-if="filters.show_deleted"
                        class="inline-flex items-center gap-1.5 px-2.5 py-1 bg-red-100 dark:bg-red-900/30 text-red-700 dark:text-red-400 rounded-lg text-xs font-bold uppercase tracking-wide">
                        Deleted only
                        <button @click="clearDeletedFilter" class="hover:text-red-900">
                            <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M6 18L18 6M6 6l12 12" />
                            </svg>
                        </button>
                    </span>
                    <span v-if="filters.search"
                        class="inline-flex items-center gap-1.5 px-2.5 py-1 bg-primary-100 dark:bg-primary-900/30 text-primary-700 dark:text-primary-400 rounded-lg text-xs font-bold">
                        Search: {{ filters.search }}
                    </span>
                    <span v-if="filters.status"
                        class="inline-flex items-center gap-1.5 px-2.5 py-1 bg-primary-100 dark:bg-primary-900/30 text-primary-700 dark:text-primary-400 rounded-lg text-xs font-bold">
                        Status: {{ formatStatus(filters.status) }}
                    </span>
                </div>
            </div>

            <!-- LIST -->
            <div
                class="bg-white dark:bg-gray-800 rounded-2xl border border-gray-100 dark:border-gray-700 overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                        <thead class="bg-gray-50 dark:bg-gray-900/50">
                            <tr>
                                <th
                                    class="px-6 py-4 text-left text-[10px] font-bold text-gray-500 dark:text-gray-400 uppercase tracking-widest">
                                    Business</th>
                                <th
                                    class="px-6 py-4 text-left text-[10px] font-bold text-gray-500 dark:text-gray-400 uppercase tracking-widest">
                                    Owner</th>
                                <th
                                    class="px-6 py-4 text-left text-[10px] font-bold text-gray-500 dark:text-gray-400 uppercase tracking-widest">
                                    Status</th>
                                <th
                                    class="px-6 py-4 text-left text-[10px] font-bold text-gray-500 dark:text-gray-400 uppercase tracking-widest">
                                    Created</th>
                                <th
                                    class="px-6 py-4 text-right text-[10px] font-bold text-gray-500 dark:text-gray-400 uppercase tracking-widest">
                                    Actions</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                            <tr v-for="business in businesses.data" :key="business.id" class="transition-colors" :class="business.deleted_at
                                ? 'bg-red-50/40 dark:bg-red-950/20 opacity-75'
                                : 'hover:bg-gray-50 dark:hover:bg-gray-700/30'">
                                <td class="px-6 py-4">
                                    <div class="flex items-center gap-3 min-w-0">
                                        <div class="w-10 h-10 rounded-2xl flex items-center justify-center font-bold text-sm flex-shrink-0 shadow-sm"
                                            :class="business.deleted_at
                                                ? 'bg-red-100 dark:bg-red-900/30 text-red-600'
                                                : 'bg-gradient-to-br from-primary-500 to-primary-700 text-white'">
                                            {{ getInitials(business.name) }}
                                        </div>
                                        <div class="min-w-0">
                                            <p
                                                class="text-sm font-bold text-gray-900 dark:text-white truncate tracking-tight">
                                                {{ business.name }}</p>
                                            <p class="text-xs text-gray-400">#{{ business.id }}</p>
                                        </div>
                                    </div>
                                </td>
                                <td class="px-6 py-4">
                                    <p class="text-sm text-gray-900 dark:text-white font-medium">{{ business.owner?.name
                                        ||
                                        'N/A' }}</p>
                                    <p class="text-xs text-gray-400">{{ business.owner?.email || '' }}</p>
                                </td>
                                <td class="px-6 py-4">
                                    <span v-if="business.deleted_at"
                                        class="inline-flex items-center gap-1.5 px-2.5 py-1 text-[10px] font-bold uppercase tracking-wide rounded-full bg-red-100 text-red-700 dark:bg-red-900/30 dark:text-red-400">
                                        <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                        </svg>
                                        Deleted {{ formatRelativeTime(business.deleted_at) }}
                                    </span>
                                    <span v-else :class="statusClass(business.status)">
                                        {{ formatStatus(business.status) }}
                                    </span>
                                </td>
                                <td class="px-6 py-4 text-sm text-gray-500 dark:text-gray-400">
                                    {{ formatDate(business.created_at) }}
                                </td>
                                <td class="px-6 py-4">
                                    <div class="flex items-center justify-end gap-2">
                                        <template v-if="!business.deleted_at">
                                            <a :href="`/admin/businesses/${business.id}`"
                                                class="w-8 h-8 inline-flex items-center justify-center text-gray-500 hover:text-primary-600 hover:bg-primary-50 dark:hover:bg-primary-900/20 rounded-lg transition-colors"
                                                title="View">
                                                <svg class="w-4 h-4" fill="none" stroke="currentColor"
                                                    viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round"
                                                        stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                                    <path stroke-linecap="round" stroke-linejoin="round"
                                                        stroke-width="2"
                                                        d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                                                </svg>
                                            </a>
                                        </template>
                                        <template v-else>
                                            <button @click="restoreBusiness(business)"
                                                class="inline-flex items-center gap-1.5 px-3 py-1.5 bg-emerald-50 dark:bg-emerald-900/30 text-emerald-700 dark:text-emerald-400 text-xs font-bold uppercase tracking-wide rounded-lg hover:bg-emerald-100 dark:hover:bg-emerald-900/50 transition-colors"
                                                title="Restore">
                                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor"
                                                    viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round"
                                                        stroke-width="2"
                                                        d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                                                </svg>
                                                Restore
                                            </button>
                                            <button @click="forceDeleteBusiness(business)"
                                                class="inline-flex items-center gap-1.5 px-3 py-1.5 bg-red-50 dark:bg-red-900/30 text-red-700 dark:text-red-400 text-xs font-bold uppercase tracking-wide rounded-lg hover:bg-red-100 dark:hover:bg-red-900/50 transition-colors"
                                                title="Delete Forever">
                                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor"
                                                    viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round"
                                                        stroke-width="2"
                                                        d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                                </svg>
                                                Delete
                                            </button>
                                        </template>
                                    </div>
                                </td>
                            </tr>

                            <!-- Empty state -->
                            <tr v-if="!businesses.data || businesses.data.length === 0">
                                <td colspan="5" class="px-6 py-16 text-center">
                                    <div
                                        class="w-20 h-20 mx-auto mb-4 rounded-2xl bg-gradient-to-br from-gray-100 to-gray-50 dark:from-gray-700 dark:to-gray-800 flex items-center justify-center shadow-sm">
                                        <svg class="w-10 h-10 text-gray-400" fill="none" stroke="currentColor"
                                            viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" />
                                        </svg>
                                    </div>
                                    <p class="text-gray-600 dark:text-gray-300 font-bold tracking-tight">
                                        {{ filters.show_deleted ? 'No deleted businesses' : 'No businesses found' }}
                                    </p>
                                    <p class="text-sm text-gray-400 mt-1">
                                        {{ filters.show_deleted
                                            ? 'Nothing has been deleted yet'
                                            : 'Try adjusting your filters' }}
                                    </p>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <!-- Pagination -->
                <div v-if="businesses.links && businesses.links.length > 3"
                    class="px-6 py-4 border-t border-gray-100 dark:border-gray-700">
                    <Pagination :links="businesses.links" />
                </div>
            </div>
        </div>

        <AdminBusinessActionModal :is-open="actionModal.open" :action="actionModal.action"
            :business="actionModal.business" @close="actionModal.open = false" @confirm="handleModalConfirm" />
    </AuthenticatedLayout>
</template>

<script setup>
    import { ref, reactive, computed } from 'vue';
    import { router } from '@inertiajs/vue3';
    import { useToast } from '@/composables/useToast';
    import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
    // import Breadcrumb from '@/Components/Breadcrumb.vue';
    import Pagination from '@/Components/Pagination.vue';
    import AdminBusinessActionModal from '@/Components/Admin/AdminBusinessActionModal.vue';
    import PageHeader from '@/Components/PageHeader.vue';
    import { useStatusBadge } from '@/composables/useStatusBadge';

    const actionModal = ref({ open: false, action: 'restore', business: null });

    const restoreBusiness = (business) => {
        actionModal.value = { open: true, action: 'restore', business };
    };

    const forceDeleteBusiness = (business) => {
        actionModal.value = { open: true, action: 'force-delete', business };
    };

    const handleModalConfirm = () => {
        const { action, business } = actionModal.value;

        if (action === 'restore') {
            router.post(`/admin/businesses/${business.id}/restore`, {}, {
                preserveScroll: true,
                // ✅ No success toast — the controller flashes
                //    "Business '<name>' has been restored successfully."
                //    and AuthenticatedLayout shows it once.
                onSuccess: () => {
                    actionModal.value.open = false;
                },
                onError: () => {
                    // Client-side fallback for network / unexpected errors.
                    showError('Failed', 'Could not restore business.');
                },
            });
        } else {
            router.delete(`/admin/businesses/${business.id}/force-delete`, {
                preserveScroll: true,
                // ✅ No success toast — the controller flashes
                //    "Business '<name>' has been permanently deleted."
                onSuccess: () => {
                    actionModal.value.open = false;
                },
                onError: () => {
                    showError('Failed', 'Could not delete business.');
                },
            });
        }
    };

    const props = defineProps({
        businesses: Object,
        categories: Array,
        filters: Object,
        stats: Object,
        statuses: Array,
    });

    const { error: showError } = useToast();

    const filters = reactive({
        search: props.filters?.search || '',
        status: props.filters?.status || '',
        category_id: props.filters?.category_id || '',
        show_deleted: props.filters?.show_deleted || null,
    });

    const hasActiveFilters = computed(() => {
        return !!(filters.search || filters.status || filters.category_id || filters.show_deleted);
    });

    const applyFilters = () => {
        router.get('/admin/businesses', filters, {
            preserveState: true,
            preserveScroll: true,
        });
    };

    const resetFilters = () => {
        Object.keys(filters).forEach(k => filters[k] = '');
        applyFilters();
    };

    const toggleDeleted = (e) => {
        const newFilters = { ...filters, show_deleted: e.target.checked ? 1 : null };
        router.get('/admin/businesses', newFilters, {
            preserveState: true,
            preserveScroll: true,
        });
    };

    const clearDeletedFilter = () => {
        const newFilters = { ...filters };
        delete newFilters.show_deleted;
        router.get('/admin/businesses', newFilters, { preserveState: true });
    };

    const getInitials = (name) => {
        if (!name) return '?';
        return name.split(' ').map(n => n[0]).join('').toUpperCase().slice(0, 2);
    };

    const formatDate = (date) => {
        if (!date) return 'N/A';
        return new Date(date).toLocaleDateString('en-US', {
            year: 'numeric', month: 'short', day: 'numeric'
        });
    };

    const formatRelativeTime = (date) => {
        if (!date) return '';
        const diff = Math.floor((Date.now() - new Date(date)) / 1000);
        if (diff < 60) return 'just now';
        if (diff < 3600) return Math.floor(diff / 60) + 'm ago';
        if (diff < 86400) return Math.floor(diff / 3600) + 'h ago';
        if (diff < 604800) return Math.floor(diff / 86400) + 'd ago';
        return new Date(date).toLocaleDateString('en-US', { month: 'short', day: 'numeric' });
    };

    const formatStatus = (status) => {
        if (!status) return '';
        return status.replace(/_/g, ' ').replace(/\b\w/g, l => l.toUpperCase());
    };

    const { business: statusClass } = useStatusBadge();
</script>