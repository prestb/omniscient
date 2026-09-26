<!-- resources/js/Pages/Admin/Invitations/Index.vue -->
<template>
    <AuthenticatedLayout>

        <Head title="Invitations" />

        <!-- COMPACT HEADER -->
        <PageHeader color="amber" :breadcrumb="[
            { label: 'Admin', href: '/admin/dashboard' },
            { label: 'Invitations' }
        ]">
            <template #icon>
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                    d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />
            </template>
            <template #title>Invitations</template>
            <template #subtitle>Manage and track business invitations</template>
            <template #actions>
                <a href="/admin/invitations/create"
                    class="inline-flex items-center gap-2 px-4 py-2 bg-gradient-to-r from-primary-600 to-primary-700 text-white rounded-xl font-semibold hover:from-primary-700 hover:to-primary-800 transition-all shadow-lg shadow-primary-500/25 text-sm">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                    </svg>
                    Send Invitation
                </a>
            </template>
        </PageHeader>

        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8 space-y-6">


            <!-- STATS -->
            <div class="grid grid-cols-2 sm:grid-cols-4 gap-4">
                <div
                    class="bg-white dark:bg-gray-800 rounded-2xl border border-gray-100 dark:border-gray-700 p-4 hover:shadow-md transition-shadow">
                    <p class="text-[10px] font-bold text-gray-400 dark:text-gray-500 uppercase tracking-widest">Total
                    </p>
                    <p class="text-2xl font-bold text-gray-900 dark:text-white tracking-tight mt-1">{{
                        getStatusCount('total')
                        }}</p>
                </div>
                <div
                    class="bg-white dark:bg-gray-800 rounded-2xl border border-gray-100 dark:border-gray-700 p-4 hover:shadow-md transition-shadow">
                    <p class="text-[10px] font-bold text-gray-400 dark:text-gray-500 uppercase tracking-widest">Pending
                    </p>
                    <p class="text-2xl font-bold text-amber-600 tracking-tight mt-1">{{ getStatusCount('pending') }}</p>
                </div>
                <div
                    class="bg-white dark:bg-gray-800 rounded-2xl border border-gray-100 dark:border-gray-700 p-4 hover:shadow-md transition-shadow">
                    <p class="text-[10px] font-bold text-gray-400 dark:text-gray-500 uppercase tracking-widest">Used</p>
                    <p class="text-2xl font-bold text-emerald-600 tracking-tight mt-1">{{ getStatusCount('used') }}</p>
                </div>
                <div
                    class="bg-white dark:bg-gray-800 rounded-2xl border border-gray-100 dark:border-gray-700 p-4 hover:shadow-md transition-shadow">
                    <p class="text-[10px] font-bold text-gray-400 dark:text-gray-500 uppercase tracking-widest">Expired
                    </p>
                    <p class="text-2xl font-bold text-red-600 tracking-tight mt-1">{{ getStatusCount('expired') }}</p>
                </div>
            </div>

            <!-- FILTERS -->
            <div class="bg-white dark:bg-gray-800 rounded-2xl border border-gray-100 dark:border-gray-700 p-5">
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-3 items-end">
                    <div class="lg:col-span-1">
                        <label
                            class="block text-[10px] font-bold text-gray-500 dark:text-gray-400 uppercase tracking-widest mb-2">
                            Status
                        </label>
                        <select v-model="localFilters.status" @change="applyFilters"
                            class="w-full px-3 py-2.5 border border-gray-200 dark:border-gray-600 rounded-xl bg-white dark:bg-gray-900 text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-primary-500 focus:border-transparent transition-colors text-sm">
                            <option value="">All Statuses</option>
                            <option value="pending">Pending</option>
                            <option value="used">Used</option>
                            <option value="expired">Expired</option>
                        </select>
                    </div>

                    <div class="lg:col-span-2">
                        <label
                            class="block text-[10px] font-bold text-gray-500 dark:text-gray-400 uppercase tracking-widest mb-2">
                            Search
                        </label>
                        <div class="relative">
                            <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none">
                                <svg class="h-4 w-4 text-gray-400" fill="none" stroke="currentColor"
                                    viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                                </svg>
                            </div>
                            <input type="text" v-model="localFilters.search" @keyup.enter="applyFilters"
                                placeholder="Search by name or email…"
                                class="w-full pl-10 pr-4 py-2.5 border border-gray-200 dark:border-gray-600 rounded-xl bg-white dark:bg-gray-900 text-gray-900 dark:text-white placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-primary-500 focus:border-transparent transition-colors text-sm" />
                        </div>
                    </div>

                    <div class="flex gap-2">
                        <button @click="applyFilters"
                            class="flex-1 inline-flex items-center justify-center gap-2 px-4 py-2.5 bg-gradient-to-r from-primary-600 to-primary-700 text-white rounded-xl hover:from-primary-700 hover:to-primary-800 transition-all shadow-lg shadow-primary-500/25 font-semibold text-sm">
                            Apply
                        </button>
                        <button @click="resetFilters"
                            class="inline-flex items-center justify-center px-4 py-2.5 border border-gray-300 dark:border-gray-600 text-gray-700 dark:text-gray-300 rounded-xl hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors font-semibold text-sm">
                            Reset
                        </button>
                    </div>
                </div>
            </div>

            <!-- TABLE -->
            <div
                class="bg-white dark:bg-gray-800 rounded-2xl border border-gray-100 dark:border-gray-700 overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-100 dark:divide-gray-700">
                        <thead>
                            <tr class="bg-gray-50 dark:bg-gray-900/50">
                                <th
                                    class="px-6 py-4 text-left text-[10px] font-bold text-gray-500 dark:text-gray-400 uppercase tracking-widest">
                                    Invitee</th>
                                <th
                                    class="px-6 py-4 text-left text-[10px] font-bold text-gray-500 dark:text-gray-400 uppercase tracking-widest">
                                    Business</th>
                                <th
                                    class="px-6 py-4 text-left text-[10px] font-bold text-gray-500 dark:text-gray-400 uppercase tracking-widest">
                                    Status</th>
                                <th
                                    class="px-6 py-4 text-left text-[10px] font-bold text-gray-500 dark:text-gray-400 uppercase tracking-widest">
                                    Expires</th>
                                <th
                                    class="px-6 py-4 text-left text-[10px] font-bold text-gray-500 dark:text-gray-400 uppercase tracking-widest">
                                    Actions</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-100 dark:divide-gray-700">
                            <tr v-for="invitation in invitations.data" :key="invitation.id"
                                class="hover:bg-gray-50/50 dark:hover:bg-gray-700/30 transition-colors">
                                <td class="px-6 py-4">
                                    <div class="flex items-center gap-3">
                                        <div
                                            class="w-10 h-10 rounded-xl bg-gradient-to-br from-primary-400 to-primary-600 flex items-center justify-center text-white font-bold text-sm flex-shrink-0 shadow-sm">
                                            {{ getInitials(invitation.name) }}
                                        </div>
                                        <div class="min-w-0">
                                            <div class="text-sm font-bold text-gray-900 dark:text-white tracking-tight">
                                                {{
                                                invitation.name }}</div>
                                            <div
                                                class="text-xs text-gray-500 dark:text-gray-400 flex items-center gap-1 mt-0.5">
                                                <svg class="w-3 h-3" fill="none" stroke="currentColor"
                                                    viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round"
                                                        stroke-width="2"
                                                        d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />
                                                </svg>
                                                {{ invitation.email }}
                                            </div>
                                            <div v-if="invitation.phone"
                                                class="text-xs text-gray-400 flex items-center gap-1 mt-0.5">
                                                <svg class="w-3 h-3" fill="none" stroke="currentColor"
                                                    viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round"
                                                        stroke-width="2"
                                                        d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z" />
                                                </svg>
                                                {{ invitation.phone }}
                                            </div>
                                        </div>
                                    </div>
                                </td>

                                <td class="px-6 py-4">
                                    <div class="flex items-center gap-2">
                                        <svg class="w-4 h-4 text-gray-400 flex-shrink-0" fill="none"
                                            stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" />
                                        </svg>
                                        <span class="text-sm text-gray-700 dark:text-gray-300">{{
                                            invitation.business_name ||
                                            'Not specified' }}</span>
                                    </div>
                                </td>

                                <td class="px-6 py-4">
                                    <span :class="getStatusBadge(invitation)">
                                        {{ getStatusLabel(invitation) }}
                                    </span>
                                </td>

                                <td class="px-6 py-4">
                                    <div class="flex flex-col">
                                        <span class="text-sm text-gray-700 dark:text-gray-300 font-medium">{{
                                            formatDate(invitation.expires_at) }}</span>
                                        <span v-if="!invitation.used_at && !isExpired(invitation.expires_at)"
                                            class="text-xs mt-0.5"
                                            :class="getDaysRemainingClass(invitation.expires_at)">
                                            {{ getDaysRemaining(invitation.expires_at) }}
                                        </span>
                                    </div>
                                </td>

                                <td class="px-6 py-4">
                                    <div class="flex flex-wrap gap-1.5">
                                        <button v-if="!invitation.used_at && !isExpired(invitation.expires_at)"
                                            @click="resend(invitation)"
                                            class="inline-flex items-center gap-1.5 px-3 py-1.5 bg-blue-50 dark:bg-blue-900/30 text-blue-700 dark:text-blue-400 text-xs font-bold uppercase tracking-wide rounded-lg hover:bg-blue-100 dark:hover:bg-blue-900/50 transition-colors">
                                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor"
                                                viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                    d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                                            </svg>
                                            Resend
                                        </button>
                                        <button v-if="!invitation.used_at" @click="deleteInvitation(invitation)"
                                            class="inline-flex items-center gap-1.5 px-3 py-1.5 bg-red-50 dark:bg-red-900/30 text-red-700 dark:text-red-400 text-xs font-bold uppercase tracking-wide rounded-lg hover:bg-red-100 dark:hover:bg-red-900/50 transition-colors">
                                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor"
                                                viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                    d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                            </svg>
                                            Delete
                                        </button>
                                        <span v-else
                                            class="text-xs text-gray-400 dark:text-gray-500 font-medium self-center">Used</span>
                                    </div>
                                </td>
                            </tr>

                            <!-- EMPTY STATE -->
                            <tr v-if="!invitations.data || invitations.data.length === 0">
                                <td colspan="5" class="px-6 py-16 text-center">
                                    <div class="flex flex-col items-center">
                                        <div
                                            class="w-20 h-20 rounded-2xl bg-gradient-to-br from-gray-100 to-gray-50 dark:from-gray-700 dark:to-gray-800 flex items-center justify-center mb-4 shadow-sm">
                                            <svg class="w-10 h-10 text-gray-400" fill="none" stroke="currentColor"
                                                viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                    d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />
                                            </svg>
                                        </div>
                                        <h3 class="text-xl font-bold text-gray-900 dark:text-white tracking-tight mb-1">
                                            No
                                            invitations sent yet</h3>
                                        <p class="text-sm text-gray-500 dark:text-gray-400 mb-6">Click "Send Invitation"
                                            to
                                            invite someone</p>
                                        <a href="/admin/invitations/create"
                                            class="inline-flex items-center gap-2 px-6 py-3 bg-gradient-to-r from-primary-600 to-primary-700 text-white rounded-xl hover:from-primary-700 hover:to-primary-800 transition-all shadow-lg shadow-primary-500/25 hover:-translate-y-0.5 font-semibold text-sm">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                    d="M12 4v16m8-8H4" />
                                            </svg>
                                            Send First Invitation
                                        </a>
                                    </div>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <!-- PAGINATION -->
                <div v-if="invitations.data && invitations.data.length > 0"
                    class="px-6 py-4 border-t border-gray-100 dark:border-gray-700">
                    <Pagination :links="invitations.links" />
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>

<script setup>
    import { reactive } from 'vue';
    import { Head, router } from '@inertiajs/vue3';
    import { useToast } from '@/composables/useToast';
    import { useConfirm } from '@/composables/useConfirm';
    import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
    import Pagination from '@/Components/Pagination.vue';
    import PageHeader from '@/Components/PageHeader.vue';

    const props = defineProps({
        invitations: Object,
        filters: Object,
    });

    const { error } = useToast();
    const { confirm: confirmDialog } = useConfirm();

    // ⚠️ Renamed to avoid shadowing the `filters` prop
    const localFilters = reactive({
        status: props.filters?.status || '',
        search: props.filters?.search || '',
    });

    // ============== STATUS HELPERS ==============
    const isExpired = (expiresAt) => {
        return new Date(expiresAt) < new Date();
    };

    const getStatusCount = (status) => {
        if (!props.invitations?.data) return 0;
        if (status === 'total') return props.invitations.data.length;

        return props.invitations.data.filter(inv => {
            if (status === 'pending') return !inv.used_at && !isExpired(inv.expires_at);
            if (status === 'used') return inv.used_at;
            if (status === 'expired') return isExpired(inv.expires_at) && !inv.used_at;
            return false;
        }).length;
    };

    // ============== DISPLAY HELPERS ==============
    const getInitials = (name) => {
        if (!name) return '?';
        return name.split(' ').map(n => n[0]).join('').toUpperCase().slice(0, 2);
    };

    const getStatusBadge = (invitation) => {
        if (invitation.used_at) {
            return 'inline-flex items-center px-2.5 py-1 text-[10px] font-bold uppercase tracking-wide rounded-full bg-emerald-100 text-emerald-700 dark:bg-emerald-900/30 dark:text-emerald-400';
        }
        if (isExpired(invitation.expires_at)) {
            return 'inline-flex items-center px-2.5 py-1 text-[10px] font-bold uppercase tracking-wide rounded-full bg-red-100 text-red-700 dark:bg-red-900/30 dark:text-red-400';
        }
        return 'inline-flex items-center px-2.5 py-1 text-[10px] font-bold uppercase tracking-wide rounded-full bg-amber-100 text-amber-700 dark:bg-amber-900/30 dark:text-amber-400';
    };

    const getStatusLabel = (invitation) => {
        if (invitation.used_at) return 'Used';
        if (isExpired(invitation.expires_at)) return 'Expired';
        return 'Pending';
    };

    const getDaysRemaining = (expiresAt) => {
        const now = new Date();
        const expiry = new Date(expiresAt);
        const diffTime = expiry - now;
        const diffDays = Math.ceil(diffTime / (1000 * 60 * 60 * 24));
        return `${diffDays} day${diffDays > 1 ? 's' : ''} remaining`;
    };

    const getDaysRemainingClass = (expiresAt) => {
        const now = new Date();
        const expiry = new Date(expiresAt);
        const diffTime = expiry - now;
        const diffDays = Math.ceil(diffTime / (1000 * 60 * 60 * 24));
        if (diffDays <= 3) return 'text-red-600 dark:text-red-400 font-bold';
        if (diffDays <= 7) return 'text-amber-600 dark:text-amber-400 font-medium';
        return 'text-gray-400';
    };

    const formatDate = (date) => {
        if (!date) return 'N/A';
        return new Date(date).toLocaleDateString('en-US', {
            year: 'numeric',
            month: 'short',
            day: 'numeric'
        });
    };

    // ============== FILTERS ==============
    const applyFilters = () => {
        router.get('/admin/invitations', localFilters, {
            preserveState: true,
            preserveScroll: true,
        });
    };

    const resetFilters = () => {
        localFilters.status = '';
        localFilters.search = '';
        applyFilters();
    };

    // ============== ACTIONS ==============
    const resend = async (invitation) => {
        const confirmed = await confirmDialog({
            title: 'Resend invitation?',
            message: `A fresh invitation email will be sent to ${invitation.email}. The expiry date will be reset to 7 days from now.`,
            confirmText: 'Resend',
            cancelText: 'Cancel',
            variant: 'primary',
        });

        if (!confirmed) return;

        router.post(`/admin/invitations/${invitation.id}/resend`, {}, {
            preserveScroll: true,
            // ✅ No success toast — the controller flashes
            //    'Invitation resent successfully!'
            onError: () => {
                // Client-side fallback for network errors. If the invitation
                // was already used, the controller returns back()->with('error')
                // which the layout shows.
                error('Resend Failed ❌', `Failed to resend invitation to ${invitation.email}.`, { duration: 4000 });
            },
        });
    };

    const deleteInvitation = async (invitation) => {
        const confirmed = await confirmDialog({
            title: 'Delete invitation?',
            message: `Invitation to ${invitation.email} will be permanently removed. This action cannot be undone.`,
            confirmText: 'Delete',
            cancelText: 'Cancel',
            variant: 'danger',
        });

        if (!confirmed) return;

        router.delete(`/admin/invitations/${invitation.id}`, {
            preserveScroll: true,
            // ✅ No success toast — the controller flashes
            //    'Invitation deleted successfully.'
            onError: () => {
                error('Delete Failed ❌', `Failed to delete invitation to ${invitation.email}.`, { duration: 4000 });
            },
        });
    };
</script>