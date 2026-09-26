<!-- resources/js/Pages/Admin/Owners/Index.vue -->
<template>
    <AuthenticatedLayout>
        <!-- COMPACT HEADER -->
        <PageHeader color="teal" :breadcrumb="[
            { label: 'Admin', href: '/admin/dashboard' },
            { label: 'Owners' }
        ]">
            <template #icon>
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                    d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z" />
            </template>
            <template #title>Business owners</template>
            <template #subtitle>Manage owners on the platform</template>
            <template #actions>
                <span
                    class="inline-flex items-center gap-2 px-3 py-2 bg-gray-50 dark:bg-gray-700 border border-gray-200 dark:border-gray-600 rounded-xl text-sm font-semibold text-gray-700 dark:text-gray-300">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z" />
                    </svg>
                    {{ owners.total }} total
                </span>
                <a href="/admin/owners/export"
                    class="inline-flex items-center gap-2 px-4 py-2 bg-gradient-to-r from-primary-600 to-primary-700 text-white rounded-xl font-semibold hover:from-primary-700 hover:to-primary-800 transition-all shadow-lg shadow-primary-500/25 text-sm">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4" />
                    </svg>
                    Export
                </a>
            </template>
        </PageHeader>

        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8 space-y-6">

            <!-- STATS -->
            <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-5 gap-4">
                <div v-for="stat in statCards" :key="stat.label"
                    class="bg-white dark:bg-gray-800 rounded-2xl border border-gray-100 dark:border-gray-700 p-4 hover:shadow-md transition-shadow">
                    <p class="text-[10px] font-bold text-gray-400 dark:text-gray-500 uppercase tracking-widest">{{ stat.label }}</p>
                    <p class="text-2xl font-bold tracking-tight mt-1" :class="stat.color">{{ stat.value }}</p>
                </div>
            </div>

            <!-- FILTERS -->
            <div class="bg-white dark:bg-gray-800 rounded-2xl border border-gray-100 dark:border-gray-700 p-5">
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 items-end">
                    <div>
                        <label
                            class="block text-[10px] font-bold text-gray-400 dark:text-gray-500 uppercase tracking-widest mb-2">Status</label>
                        <select v-model="filterState.status" @change="applyFilters"
                            class="w-full px-4 py-2.5 border border-gray-200 dark:border-gray-600 rounded-xl bg-white dark:bg-gray-900 text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-primary-500 focus:border-transparent transition-colors text-sm appearance-none">
                            <option value="">All statuses</option>
                            <option v-for="status in statuses" :key="status" :value="status">
                                {{ status.charAt(0).toUpperCase() + status.slice(1) }}
                            </option>
                        </select>
                    </div>
                    <div class="lg:col-span-2">
                        <label
                            class="block text-[10px] font-bold text-gray-400 dark:text-gray-500 uppercase tracking-widest mb-2">Search</label>
                        <div class="relative">
                            <div class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none">
                                <svg class="h-4 w-4 text-gray-400 dark:text-gray-500" fill="none" stroke="currentColor"
                                    viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                                </svg>
                            </div>
                            <input type="text" v-model="filterState.search" @keyup.enter="applyFilters"
                                placeholder="Search by name or email…"
                                class="w-full pl-11 pr-4 py-2.5 border border-gray-200 dark:border-gray-600 rounded-xl bg-white dark:bg-gray-900 text-gray-900 dark:text-white placeholder-gray-400 dark:placeholder-gray-500 focus:outline-none focus:ring-2 focus:ring-primary-500 focus:border-transparent transition-colors text-sm" />
                        </div>
                    </div>
                    <div class="flex gap-2">
                        <button @click="applyFilters"
                            class="flex-1 px-5 py-2.5 bg-gradient-to-r from-primary-600 to-primary-700 text-white rounded-xl hover:from-primary-700 hover:to-primary-800 transition-all shadow-lg shadow-primary-500/25 font-semibold text-sm">
                            Apply
                        </button>
                        <button @click="resetFilters"
                            class="px-5 py-2.5 border border-gray-300 dark:border-gray-600 text-gray-700 dark:text-gray-300 rounded-xl hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors font-semibold text-sm">
                            Reset
                        </button>
                    </div>
                </div>
            </div>

            <!-- TABLE -->
            <div class="bg-white dark:bg-gray-800 rounded-2xl border border-gray-100 dark:border-gray-700 overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                        <thead class="bg-gray-50 dark:bg-gray-900/50">
                            <tr>
                                <th
                                    class="px-6 py-4 text-left text-[10px] font-bold text-gray-500 dark:text-gray-400 uppercase tracking-widest">
                                    Owner</th>
                                <th
                                    class="px-6 py-4 text-left text-[10px] font-bold text-gray-500 dark:text-gray-400 uppercase tracking-widest">
                                    Businesses</th>
                                <th
                                    class="px-6 py-4 text-left text-[10px] font-bold text-gray-500 dark:text-gray-400 uppercase tracking-widest">
                                    Status</th>
                                <th
                                    class="px-6 py-4 text-left text-[10px] font-bold text-gray-500 dark:text-gray-400 uppercase tracking-widest">
                                    Joined</th>
                                <th
                                    class="px-6 py-4 text-right text-[10px] font-bold text-gray-500 dark:text-gray-400 uppercase tracking-widest">
                                    Actions</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                            <tr v-for="owner in owners.data" :key="owner.id"
                                class="hover:bg-gray-50/50 dark:hover:bg-gray-700/30 transition-colors">
                                <td class="px-6 py-4">
                                    <div class="flex items-center gap-3 min-w-0">
                                        <div
                                            class="w-10 h-10 rounded-2xl bg-gradient-to-br from-primary-500 to-primary-700 flex items-center justify-center text-white text-xs font-bold flex-shrink-0 shadow-md">
                                            {{ getInitials(owner.name) }}
                                        </div>
                                        <div class="min-w-0">
                                            <p class="text-sm font-bold text-gray-900 dark:text-white truncate tracking-tight">{{
                                                owner.name }}
                                            </p>
                                            <p class="text-xs text-gray-500 dark:text-gray-400 truncate">{{ owner.email }}</p>
                                            <p v-if="owner.phone" class="text-xs text-gray-400 dark:text-gray-500 truncate mt-0.5">{{
                                                owner.phone
                                                }}</p>
                                        </div>
                                    </div>
                                </td>
                                <td class="px-6 py-4">
                                    <div v-if="owner.businesses && owner.businesses.length > 0">
                                        <div class="flex items-center gap-2">
                                            <span class="text-sm font-bold text-gray-900 dark:text-white">{{ owner.businesses.length
                                                }}</span>
                                            <span class="text-xs text-gray-400 dark:text-gray-500">{{ owner.businesses.length === 1 ?
                                                'business' :
                                                'businesses' }}</span>
                                        </div>
                                        <div class="flex flex-wrap gap-1 mt-1.5">
                                            <span v-for="business in owner.businesses.slice(0, 2)" :key="business.id"
                                                class="inline-flex items-center px-2 py-0.5 bg-primary-50 dark:bg-primary-900/30 text-primary-700 dark:text-primary-400 text-[10px] font-semibold rounded-full">
                                                {{ business.name }}
                                            </span>
                                            <span v-if="owner.businesses.length > 2"
                                                class="inline-flex items-center px-2 py-0.5 bg-gray-100 dark:bg-gray-700 text-gray-600 dark:text-gray-300 text-[10px] font-semibold rounded-full">
                                                +{{ owner.businesses.length - 2 }}
                                            </span>
                                        </div>
                                    </div>
                                    <span v-else class="text-xs text-gray-400 dark:text-gray-500">No businesses</span>
                                </td>
                                <td class="px-6 py-4">
                                    <span :class="statusClass(owner.status)">
                                        {{ owner.status.charAt(0).toUpperCase() + owner.status.slice(1) }}
                                    </span>
                                </td>
                                <td class="px-6 py-4 text-sm text-gray-500 dark:text-gray-400">
                                    {{ formatDate(owner.created_at) }}
                                </td>
                                <td class="px-6 py-4">
                                    <div class="flex flex-wrap gap-1.5 justify-end">
                                        <a :href="`/admin/owners/${owner.id}`"
                                            class="inline-flex items-center gap-1.5 px-3 py-1.5 bg-blue-50 dark:bg-blue-900/30 text-blue-700 dark:text-blue-400 text-xs font-bold uppercase tracking-wide rounded-lg hover:bg-blue-100 dark:hover:bg-blue-900/50 transition-colors">
                                            View
                                        </a>
                                        <a :href="`/admin/owners/${owner.id}/edit`"
                                            class="inline-flex items-center gap-1.5 px-3 py-1.5 bg-gray-50 dark:bg-gray-700 text-gray-700 dark:text-gray-300 text-xs font-bold uppercase tracking-wide rounded-lg hover:bg-gray-100 dark:hover:bg-gray-600 transition-colors">
                                            Edit
                                        </a>
                                        <button v-if="owner.status === 'pending'" @click="validateOwner(owner)"
                                            class="inline-flex items-center gap-1.5 px-3 py-1.5 bg-emerald-50 dark:bg-emerald-900/30 text-emerald-700 dark:text-emerald-400 text-xs font-bold uppercase tracking-wide rounded-lg hover:bg-emerald-100 dark:hover:bg-emerald-900/50 transition-colors">
                                            Validate
                                        </button>
                                        <button v-if="owner.status === 'active'" @click="suspendOwner(owner)"
                                            class="inline-flex items-center gap-1.5 px-3 py-1.5 bg-amber-50 dark:bg-amber-900/30 text-amber-700 dark:text-amber-400 text-xs font-bold uppercase tracking-wide rounded-lg hover:bg-amber-100 dark:hover:bg-amber-900/50 transition-colors">
                                            Suspend
                                        </button>
                                        <button v-if="owner.status === 'suspended'" @click="activateOwner(owner)"
                                            class="inline-flex items-center gap-1.5 px-3 py-1.5 bg-emerald-50 dark:bg-emerald-900/30 text-emerald-700 dark:text-emerald-400 text-xs font-bold uppercase tracking-wide rounded-lg hover:bg-emerald-100 dark:hover:bg-emerald-900/50 transition-colors">
                                            Activate
                                        </button>
                                        <button @click="deleteOwner(owner)"
                                            class="inline-flex items-center gap-1.5 px-3 py-1.5 bg-red-50 dark:bg-red-900/30 text-red-700 dark:text-red-400 text-xs font-bold uppercase tracking-wide rounded-lg hover:bg-red-100 dark:hover:bg-red-900/50 transition-colors">
                                            Delete
                                        </button>
                                    </div>
                                </td>
                            </tr>
                            <tr v-if="!owners.data || owners.data.length === 0">
                                <td colspan="5" class="px-6 py-16 text-center">
                                    <div
                                        class="w-20 h-20 mx-auto mb-4 rounded-2xl bg-gradient-to-br from-gray-100 to-gray-50 dark:from-gray-700 dark:to-gray-800 flex items-center justify-center">
                                        <svg class="w-10 h-10 text-gray-400 dark:text-gray-500" fill="none" stroke="currentColor"
                                            viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z" />
                                        </svg>
                                    </div>
                                    <p class="text-gray-600 dark:text-gray-300 font-bold tracking-tight">No owners found</p>
                                    <p class="text-sm text-gray-400 dark:text-gray-500 mt-1">Try adjusting your filters</p>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
                <div class="px-6 py-4 border-t border-gray-100 dark:border-gray-700">
                    <Pagination :links="owners.links" />
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>


<script setup>
    import { ref, reactive, computed, onMounted } from 'vue';
    import { router } from '@inertiajs/vue3';
    import { useToast } from '@/composables/useToast';
    import { useConfirm } from '@/composables/useConfirm';
    import axios from 'axios';
    import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
    import Pagination from '@/Components/Pagination.vue';
    import { useStatusBadge } from '@/composables/useStatusBadge';
    import PageHeader from '@/Components/PageHeader.vue';

    const props = defineProps({
        owners: Object,
        filters: Object,
        statuses: Array,
    });

    const { error } = useToast();
    const { confirm: confirmDialog } = useConfirm();
    const { user: statusClass } = useStatusBadge();

    // ✅ Renamed to avoid shadowing the `filters` prop
    const filterState = reactive({
        status: props.filters?.status || '',
        search: props.filters?.search || '',
    });

    const stats = ref({});

    const statCards = computed(() => [
        { label: 'Total', value: stats.value?.total_owners || 0, color: 'text-gray-900 dark:text-white' },
        { label: 'Active', value: stats.value?.active_owners || 0, color: 'text-emerald-600 dark:text-emerald-400' },
        { label: 'Pending', value: stats.value?.pending_owners || 0, color: 'text-amber-600 dark:text-amber-400' },
        { label: 'Suspended', value: stats.value?.suspended_owners || 0, color: 'text-red-600 dark:text-red-400' },
        { label: 'With businesses', value: stats.value?.owners_with_businesses || 0, color: 'text-purple-600 dark:text-purple-400' },
    ]);

    const getInitials = (name) => {
        if (!name) return '?';
        return name.split(' ').map(n => n[0]).join('').toUpperCase().slice(0, 2);
    };

    const formatDate = (date) => {
        if (!date) return 'N/A';
        return new Date(date).toLocaleDateString('en-US', {
            year: 'numeric',
            month: 'short',
            day: 'numeric'
        });
    };

    const applyFilters = () => {
        router.get('/admin/owners', filterState, {
            preserveState: true,
            preserveScroll: true,
        });
    };

    const resetFilters = () => {
        filterState.status = '';
        filterState.search = '';
        applyFilters();
    };

    const validateOwner = async (owner) => {
        const confirmed = await confirmDialog({
            title: 'Validate owner?',
            message: `"${owner.name}" will be approved as a business owner. They'll receive an email and in-app notification and can then manage their business.`,
            confirmText: 'Validate Owner',
            cancelText: 'Cancel',
            variant: 'primary',
        });

        if (!confirmed) return;

        router.post(`/admin/owners/${owner.id}/validate`, {}, {
            preserveScroll: true,
            // ✅ No success toast — the controller flashes
            //    'Owner account approved successfully and notifications sent.'
            onError: () => {
                error('Validation Failed ❌', `Failed to validate "${owner.name}". Please try again.`, { duration: 4000 });
            },
        });
    };

    const suspendOwner = async (owner) => {
        const confirmed = await confirmDialog({
            title: 'Suspend owner?',
            message: `"${owner.name}" will be suspended and unable to log in. Their businesses will remain unaffected.`,
            confirmText: 'Suspend',
            cancelText: 'Cancel',
            variant: 'warning',
        });

        if (!confirmed) return;

        router.post(`/admin/owners/${owner.id}/suspend`, {}, {
            preserveScroll: true,
            // ✅ No success toast — the controller flashes
            //    'Owner account suspended successfully.'
            onError: () => {
                error('Suspend Failed ❌', `Failed to suspend "${owner.name}". Please try again.`, { duration: 4000 });
            },
        });
    };

    const activateOwner = async (owner) => {
        const confirmed = await confirmDialog({
            title: 'Activate owner?',
            message: `"${owner.name}" will be activated and able to log in again. Their businesses will remain unaffected.`,
            confirmText: 'Activate',
            cancelText: 'Cancel',
            variant: 'primary',
        });

        if (!confirmed) return;

        router.post(`/admin/owners/${owner.id}/activate`, {}, {
            preserveScroll: true,
            // ✅ No success toast — the controller flashes
            //    'Owner account activated successfully.'
            onError: () => {
                error('Activation Failed ❌', `Failed to activate "${owner.name}". Please try again.`, { duration: 4000 });
            },
        });
    };

    const deleteOwner = async (owner) => {
        const confirmed = await confirmDialog({
            title: 'Delete owner?',
            message: `"${owner.name}" will be deleted along with all their businesses. Note: owners with active subscriptions cannot be deleted.`,
            confirmText: 'Delete Owner',
            cancelText: 'Cancel',
            variant: 'danger',
        });

        if (!confirmed) return;

        router.delete(`/admin/owners/${owner.id}`, {
            preserveScroll: true,
            // ✅ No success toast — the controller flashes
            //    'Owner deleted successfully.'
            onError: () => {
                error('Delete Failed ❌', `Failed to delete "${owner.name}". Please try again.`, { duration: 4000 });
            },
        });
    };

    const loadStats = async () => {
        try {
            const response = await axios.get('/admin/owners/statistics');
            stats.value = response.data;
        } catch (err) {
            console.error('Error loading stats:', err);
        }
    };

    onMounted(() => {
        loadStats();
    });
</script>