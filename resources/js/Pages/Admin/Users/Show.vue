<!-- resources/js/Pages/Admin/Users/Show.vue -->
<template>
    <AuthenticatedLayout>
        <!-- COMPACT HEADER -->
        <PageHeader color="indigo" :breadcrumb="[
            { label: 'Admin', href: '/admin/dashboard' },
            { label: 'Users', href: '/admin/users' },
            { label: user.name }
        ]">
            <template #icon>
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                    d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
            </template>
            <template #title>{{ user.name }}</template>
            <template #subtitle>{{ user.email }}</template>
            <template #actions>
                <span :class="roleClass(user.role)">
                    {{ user.role.replace('_', ' ').toUpperCase() }}
                </span>
                <span :class="statusClass(user.status)">
                    {{ user.status.charAt(0).toUpperCase() + user.status.slice(1) }}
                </span>
                <a :href="`/admin/users/${user.id}/edit`"
                    class="inline-flex items-center gap-2 px-4 py-2 bg-gradient-to-r from-primary-600 to-primary-700 text-white rounded-xl font-semibold hover:from-primary-700 hover:to-primary-800 transition-all shadow-lg shadow-primary-500/25 text-sm">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                    </svg>
                    Edit User
                </a>
            </template>
        </PageHeader>

        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8 space-y-6">
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                <!-- User Info Card -->
                <div class="lg:col-span-1">
                    <div
                        class="bg-white dark:bg-gray-800 rounded-2xl shadow-sm border border-gray-100 dark:border-gray-700 overflow-hidden hover:shadow-md transition-shadow sticky top-6">
                        <div
                            class="px-6 py-4 border-b border-gray-100 dark:border-gray-700 bg-gradient-to-r from-gray-50 to-white dark:from-gray-800 dark:to-gray-800/50 flex items-center gap-2">
                            <svg class="w-5 h-5 text-gray-400 dark:text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                            </svg>
                            <h3 class="text-sm font-semibold text-gray-700 dark:text-gray-300">User Information</h3>
                        </div>
                        <div class="p-6 space-y-4">
                            <div class="group">
                                <label class="text-xs font-medium text-gray-400 dark:text-gray-500 uppercase tracking-wider">Name</label>
                                <p class="text-sm font-semibold text-gray-900 dark:text-white mt-0.5">{{ user.name }}</p>
                            </div>
                            <div class="group">
                                <label
                                    class="text-xs font-medium text-gray-400 dark:text-gray-500 uppercase tracking-wider flex items-center gap-1.5">
                                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M16 12a4 4 0 10-8 0 4 4 0 008 0zm0 0v1.5a2.5 2.5 0 005 0V12a9 9 0 10-9 9m4.5-1.206a8.959 8.959 0 01-4.5 1.207" />
                                    </svg>
                                    Email
                                </label>
                                <p class="text-sm text-gray-700 dark:text-gray-300 mt-0.5">{{ user.email }}</p>
                            </div>
                            <div class="group">
                                <label
                                    class="text-xs font-medium text-gray-400 dark:text-gray-500 uppercase tracking-wider flex items-center gap-1.5">
                                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z" />
                                    </svg>
                                    Phone
                                </label>
                                <p class="text-sm text-gray-700 dark:text-gray-300 mt-0.5">{{ user.phone || 'Not set' }}</p>
                            </div>
                            <div class="group">
                                <label class="text-xs font-medium text-gray-400 dark:text-gray-500 uppercase tracking-wider">Role</label>
                                <div class="mt-0.5">
                                    <span :class="roleClass(user.role)">
                                        {{ user.role.replace('_', ' ').toUpperCase() }}
                                    </span>
                                </div>
                            </div>
                            <div class="group">
                                <label class="text-xs font-medium text-gray-400 dark:text-gray-500 uppercase tracking-wider">Status</label>
                                <div class="mt-0.5">
                                    <span :class="statusClass(user.status)">
                                        {{ user.status.charAt(0).toUpperCase() + user.status.slice(1) }}
                                    </span>
                                </div>
                            </div>
                            <div class="group pt-2 border-t border-gray-100 dark:border-gray-700">
                                <label
                                    class="text-xs font-medium text-gray-400 dark:text-gray-500 uppercase tracking-wider flex items-center gap-1.5">
                                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                                    </svg>
                                    Joined
                                </label>
                                <p class="text-sm text-gray-700 dark:text-gray-300 mt-0.5">{{ formatDate(user.created_at) }}</p>
                            </div>
                            <div class="group">
                                <label
                                    class="text-xs font-medium text-gray-400 dark:text-gray-500 uppercase tracking-wider flex items-center gap-1.5">
                                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                                    </svg>
                                    Last Login
                                </label>
                                <p class="text-sm text-gray-700 dark:text-gray-300 mt-0.5">{{ user.last_login_at ?
                                    formatDate(user.last_login_at) :
                                    'Never' }}</p>
                                <p v-if="user.last_login_ip" class="text-xs text-gray-400 dark:text-gray-500 mt-0.5">IP: {{
                                    user.last_login_ip }}
                                </p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Businesses -->
                <div class="lg:col-span-2 space-y-6">
                    <div
                        class="bg-white dark:bg-gray-800 rounded-2xl shadow-sm border border-gray-100 dark:border-gray-700 overflow-hidden hover:shadow-md transition-shadow">
                        <div
                            class="px-6 py-4 border-b border-gray-100 dark:border-gray-700 bg-gradient-to-r from-gray-50 to-white dark:from-gray-800 dark:to-gray-800/50 flex justify-between items-center">
                            <div class="flex items-center gap-2">
                                <svg class="w-5 h-5 text-gray-400 dark:text-gray-500" fill="none" stroke="currentColor"
                                    viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" />
                                </svg>
                                <h3 class="text-sm font-semibold text-gray-700 dark:text-gray-300">Businesses</h3>
                                <span class="bg-gray-100 dark:bg-gray-700 text-gray-600 dark:text-gray-300 text-xs font-medium px-2.5 py-0.5 rounded-full">
                                    {{ user.businesses?.length || 0 }}
                                </span>
                            </div>
                        </div>

                        <div v-if="user.businesses && user.businesses.length > 0" class="divide-y divide-gray-100 dark:divide-gray-700">
                            <div v-for="business in user.businesses" :key="business.id"
                                class="px-6 py-4 hover:bg-gray-50 dark:hover:bg-gray-700/30 transition-colors group">
                                <div
                                    class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-3">
                                    <div class="flex items-start gap-3">
                                        <div
                                            class="w-10 h-10 rounded-xl bg-primary-100 dark:bg-primary-900/30 flex items-center justify-center flex-shrink-0 group-hover:bg-primary-200 dark:group-hover:bg-primary-900/50 transition-colors">
                                            <span class="text-primary-600 dark:text-primary-400 font-semibold text-sm">{{
                                                getInitials(business.name)
                                                }}</span>
                                        </div>
                                        <div>
                                            <p
                                                class="text-sm font-medium text-gray-900 dark:text-white group-hover:text-primary-600 dark:group-hover:text-primary-400 transition-colors">
                                                {{ business.name }}
                                            </p>
                                            <div class="flex flex-wrap gap-1 mt-1">
                                                <span v-for="category in business.categories?.slice(0, 3)"
                                                    :key="category.id"
                                                    class="inline-flex items-center px-2 py-0.5 bg-primary-50 dark:bg-primary-900/30 text-primary-700 dark:text-primary-400 text-xs rounded-full">
                                                    {{ category.name }}
                                                </span>
                                                <span v-if="business.categories?.length > 3"
                                                    class="inline-flex items-center px-2 py-0.5 bg-gray-100 dark:bg-gray-700 text-gray-600 dark:text-gray-300 text-xs rounded-full">
                                                    +{{ business.categories.length - 3 }}
                                                </span>
                                            </div>
                                            <p class="text-xs text-gray-400 dark:text-gray-500 flex items-center gap-3 mt-1">
                                                <span class="flex items-center gap-1">
                                                    <svg class="w-3 h-3" fill="none" stroke="currentColor"
                                                        viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round"
                                                            stroke-width="2"
                                                            d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" />
                                                    </svg>
                                                    {{ business.branches?.length || 0 }} branches
                                                </span>
                                                <span class="flex items-center gap-1">
                                                    <svg class="w-3 h-3" fill="none" stroke="currentColor"
                                                        viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round"
                                                            stroke-width="2"
                                                            d="M11.049 2.927c.3-.921 1.603-.921 1.902 0l1.519 4.674a1 1 0 00.95.69h4.915c.969 0 1.371 1.24.588 1.81l-3.976 2.888a1 1 0 00-.363 1.118l1.518 4.674c.3.922-.755 1.688-1.538 1.118l-3.976-2.888a1 1 0 00-1.176 0l-3.976 2.888c-.783.57-1.838-.197-1.538-1.118l1.518-4.674a1 1 0 00-.363-1.118l-3.976-2.888c-.784-.57-.38-1.81.588-1.81h4.914a1 1 0 00.951-.69l1.519-4.674z" />
                                                    </svg>
                                                    {{ business.average_rating || 0 }} ★
                                                </span>
                                            </p>
                                        </div>
                                    </div>
                                    <div class="flex items-center gap-3 flex-shrink-0">
                                        <span :class="businessStatusClass(business.status)">
                                            {{ business.status.charAt(0).toUpperCase() + business.status.slice(1) }}
                                        </span>
                                        <a :href="`/admin/businesses/${business.id}`"
                                            class="text-primary-600 dark:text-primary-400 hover:text-primary-800 dark:hover:text-primary-300 text-sm font-medium opacity-0 group-hover:opacity-100 transition-opacity">
                                            View →
                                        </a>
                                    </div>
                                </div>
                                <div v-if="business.active_subscription" class="mt-2 pt-2 border-t border-gray-100 dark:border-gray-700">
                                    <span class="inline-flex items-center gap-1 text-xs text-green-600 dark:text-green-400">
                                        <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                                        </svg>
                                        {{ business.active_subscription.plan?.name }} Plan
                                    </span>
                                </div>
                                <div v-else class="mt-2 pt-2 border-t border-gray-100 dark:border-gray-700">
                                    <span class="inline-flex items-center gap-1 text-xs text-gray-400 dark:text-gray-500">
                                        <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M6 18L18 6M6 6l12 12" />
                                        </svg>
                                        No active subscription
                                    </span>
                                </div>
                            </div>
                        </div>

                        <div v-else class="p-8 text-center">
                            <div
                                class="w-16 h-16 bg-gray-100 dark:bg-gray-700 rounded-full flex items-center justify-center mx-auto mb-4">
                                <svg class="w-8 h-8 text-gray-400 dark:text-gray-500" fill="none" stroke="currentColor"
                                    viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" />
                                </svg>
                            </div>
                            <p class="text-gray-500 dark:text-gray-400 font-medium">No businesses owned by this user</p>
                            <p class="text-sm text-gray-400 dark:text-gray-500">They haven't created any businesses yet</p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Quick Actions -->
            <div
                class="mt-6 bg-white dark:bg-gray-800 rounded-2xl shadow-sm border border-gray-100 dark:border-gray-700 overflow-hidden hover:shadow-md transition-shadow">
                <div
                    class="px-6 py-4 border-b border-gray-100 dark:border-gray-700 bg-gradient-to-r from-gray-50 to-white dark:from-gray-800 dark:to-gray-800/50 flex items-center gap-2">
                    <svg class="w-5 h-5 text-gray-400 dark:text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M13 10V3L4 14h7v7l9-11h-7z" />
                    </svg>
                    <h3 class="text-sm font-semibold text-gray-700 dark:text-gray-300">Quick Actions</h3>
                </div>
                <div class="p-6 flex flex-wrap gap-3">
                    <button v-if="user.status === 'active'" @click="toggleUserStatus"
                        class="inline-flex items-center gap-2 px-5 py-2.5 bg-gradient-to-r from-yellow-500 to-yellow-600 text-white rounded-xl hover:from-yellow-600 hover:to-yellow-700 transition-all duration-200 shadow-lg shadow-yellow-100 dark:shadow-none hover:shadow-yellow-200 text-sm font-medium">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728A9 9 0 015.636 5.636m12.728 12.728L5.636 5.636" />
                        </svg>
                        Suspend User
                    </button>
                    <button v-if="user.status === 'suspended'" @click="toggleUserStatus"
                        class="inline-flex items-center gap-2 px-5 py-2.5 bg-gradient-to-r from-green-500 to-green-600 text-white rounded-xl hover:from-green-600 hover:to-green-700 transition-all duration-200 shadow-lg shadow-green-100 dark:shadow-none hover:shadow-green-200 text-sm font-medium">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M13 10V3L4 14h7v7l9-11h-7z" />
                        </svg>
                        Activate User
                    </button>
                    <button @click="deleteUser"
                        class="inline-flex items-center gap-2 px-5 py-2.5 bg-gradient-to-r from-red-500 to-red-600 text-white rounded-xl hover:from-red-600 hover:to-red-700 transition-all duration-200 shadow-lg shadow-red-100 dark:shadow-none hover:shadow-red-200 text-sm font-medium">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                        </svg>
                        Delete User
                    </button>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>


<script setup>
    import { router } from '@inertiajs/vue3';
    import { useToast } from '@/composables/useToast';
    import { useConfirm } from '@/composables/useConfirm';
    import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
    import { useStatusBadge } from '@/composables/useStatusBadge';
    import PageHeader from '@/Components/PageHeader.vue';

    const props = defineProps({
        user: Object,
    });

    const { error } = useToast();
    const { confirm: confirmDialog } = useConfirm();
    const { user: statusClass, role: roleClass, business: businessStatusClass } = useStatusBadge();

    const getInitials = (name) => {
        if (!name) return '?';
        return name.split(' ').map(n => n[0]).join('').toUpperCase().slice(0, 2);
    };

    const formatDate = (date) => {
        if (!date) return 'N/A';
        return new Date(date).toLocaleDateString('en-US', {
            year: 'numeric',
            month: 'short',
            day: 'numeric',
            hour: '2-digit',
            minute: '2-digit'
        });
    };

    const toggleUserStatus = async () => {
        const action = props.user.status === 'active' ? 'suspend' : 'activate';
        const isDanger = action === 'suspend';

        const confirmed = await confirmDialog({
            title: `${action === 'suspend' ? 'Suspend' : 'Activate'} user?`,
            message: props.user.status === 'active'
                ? `${props.user.name} will be suspended and unable to log in. Their businesses will remain unaffected.`
                : `${props.user.name} will be activated and able to log in again.`,
            confirmText: action === 'suspend' ? 'Suspend User' : 'Activate User',
            cancelText: 'Cancel',
            variant: isDanger ? 'danger' : 'success',
        });

        if (!confirmed) return;

        router.post(`/admin/users/${props.user.id}/toggle-status`, {}, {
            preserveScroll: true,
            // ✅ No success toast — the controller flashes
            //    'User suspended successfully.' / 'User active successfully.'
            onError: () => {
                error('Action Failed ❌', `Failed to ${action} user.`, { duration: 4000 });
            },
        });
    };

    const deleteUser = async () => {
        const confirmed = await confirmDialog({
            title: 'Delete user?',
            message: `"${props.user.name}" will be deleted along with all their businesses. Note: users with active subscriptions or the last super admin cannot be deleted.`,
            confirmText: 'Delete User',
            cancelText: 'Cancel',
            variant: 'danger',
        });

        if (!confirmed) return;

        router.delete(`/admin/users/${props.user.id}`, {
            preserveScroll: true,
            // ✅ No success toast — the controller flashes
            //    'User deleted successfully.' and redirects to
            //    /admin/users, where the toast fires on the index.
            onError: () => {
                error('Delete Failed ❌', 'Failed to delete user. Please try again.', { duration: 4000 });
            },
        });
    };
</script>