<!-- resources/js/Pages/Admin/Owners/Show.vue -->
<template>
    <AuthenticatedLayout>
        <!-- COMPACT HEADER -->
        <PageHeader color="teal" :breadcrumb="[
            { label: 'Admin', href: '/admin/dashboard' },
            { label: 'Owners', href: '/admin/owners' },
            { label: owner.name }
        ]">
            <template #icon>
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                    d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
            </template>
            <template #title>{{ owner.name }}</template>
            <template #subtitle>{{ owner.email }}</template>
            <template #actions>
                <span :class="userStatusClass(owner.status)">
                    {{ owner.status.charAt(0).toUpperCase() + owner.status.slice(1) }}
                </span>
                <a :href="`/admin/owners/${owner.id}/edit`"
                    class="inline-flex items-center gap-2 px-4 py-2 bg-gradient-to-r from-primary-600 to-primary-700 text-white rounded-xl font-semibold hover:from-primary-700 hover:to-primary-800 transition-all shadow-lg shadow-primary-500/25 text-sm">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                    </svg>
                    Edit Owner
                </a>
            </template>
        </PageHeader>
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8 space-y-6">

            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

                <!-- OWNER INFO -->
                <div class="lg:col-span-1">
                    <div class="bg-white dark:bg-gray-800 rounded-2xl border border-gray-100 dark:border-gray-700 overflow-hidden sticky top-6">
                        <div
                            class="px-6 py-4 border-b border-gray-100 dark:border-gray-700 bg-gradient-to-br from-gray-50 to-white dark:from-gray-800 dark:to-gray-800/50 flex items-center gap-3">
                            <div class="w-9 h-9 rounded-xl bg-blue-50 dark:bg-blue-900/30 flex items-center justify-center">
                                <svg class="w-4 h-4 text-blue-600 dark:text-blue-400" fill="none" stroke="currentColor"
                                    viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                                </svg>
                            </div>
                            <h3 class="text-sm font-bold text-gray-900 dark:text-white tracking-tight">Owner information</h3>
                        </div>
                        <div class="p-6 space-y-4">
                            <div>
                                <p class="text-[10px] font-bold text-gray-400 dark:text-gray-500 uppercase tracking-widest">Name</p>
                                <p class="text-sm font-semibold text-gray-900 dark:text-white mt-1">{{ owner.name }}</p>
                            </div>
                            <div>
                                <p class="text-[10px] font-bold text-gray-400 dark:text-gray-500 uppercase tracking-widest">Email</p>
                                <p class="text-sm text-gray-700 dark:text-gray-300 mt-1 truncate">{{ owner.email }}</p>
                            </div>
                            <div>
                                <p class="text-[10px] font-bold text-gray-400 dark:text-gray-500 uppercase tracking-widest">Phone</p>
                                <p class="text-sm text-gray-700 dark:text-gray-300 mt-1">{{ owner.phone || 'Not set' }}</p>
                            </div>
                            <div>
                                <p class="text-[10px] font-bold text-gray-400 dark:text-gray-500 uppercase tracking-widest">Status</p>
                                <div class="mt-1.5">
                                    <span :class="userStatusClass(owner.status)">
                                        {{ owner.status.charAt(0).toUpperCase() + owner.status.slice(1) }}
                                    </span>
                                </div>
                            </div>
                            <div>
                                <p class="text-[10px] font-bold text-gray-400 dark:text-gray-500 uppercase tracking-widest">Role</p>
                                <p class="text-sm text-gray-700 dark:text-gray-300 mt-1 capitalize">{{ owner.role || 'Owner' }}</p>
                            </div>
                            <div>
                                <p class="text-[10px] font-bold text-gray-400 dark:text-gray-500 uppercase tracking-widest">Joined</p>
                                <p class="text-sm text-gray-700 dark:text-gray-300 mt-1">{{ formatDate(owner.created_at) }}</p>
                            </div>
                            <div class="pt-4 border-t border-gray-100 dark:border-gray-700">
                                <p class="text-[10px] font-bold text-gray-400 dark:text-gray-500 uppercase tracking-widest">Last login</p>
                                <p class="text-sm text-gray-700 dark:text-gray-300 mt-1">
                                    {{ owner.last_login_at ? formatDate(owner.last_login_at) : 'Never' }}
                                </p>
                                <p v-if="owner.last_login_ip" class="text-xs text-gray-400 dark:text-gray-500 mt-0.5">
                                    IP: {{ owner.last_login_ip }}
                                </p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- BUSINESSES -->
                <div class="lg:col-span-2 space-y-6">
                    <div class="bg-white dark:bg-gray-800 rounded-2xl border border-gray-100 dark:border-gray-700 overflow-hidden">
                        <div
                            class="px-6 py-4 border-b border-gray-100 dark:border-gray-700 bg-gradient-to-br from-gray-50 to-white dark:from-gray-800 dark:to-gray-800/50 flex items-center justify-between">
                            <div class="flex items-center gap-3">
                                <div class="w-9 h-9 rounded-xl bg-emerald-50 dark:bg-emerald-900/30 flex items-center justify-center">
                                    <svg class="w-4 h-4 text-emerald-600 dark:text-emerald-400" fill="none" stroke="currentColor"
                                        viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" />
                                    </svg>
                                </div>
                                <div>
                                    <h3 class="text-sm font-bold text-gray-900 dark:text-white tracking-tight">Businesses</h3>
                                    <p class="text-xs text-gray-400 dark:text-gray-500">{{ owner.businesses?.length || 0 }} total</p>
                                </div>
                            </div>
                        </div>

                        <div v-if="owner.businesses && owner.businesses.length > 0" class="divide-y divide-gray-100 dark:divide-gray-700">
                            <div v-for="business in owner.businesses" :key="business.id"
                                class="px-6 py-4 hover:bg-gray-50/50 dark:hover:bg-gray-700/30 transition-colors group">
                                <div
                                    class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-3">
                                    <div class="flex items-start gap-3 min-w-0">
                                        <div
                                            class="w-10 h-10 rounded-2xl bg-gradient-to-br from-primary-500 to-primary-700 flex items-center justify-center text-white text-xs font-bold flex-shrink-0 shadow-md">
                                            {{ getInitials(business.name) }}
                                        </div>
                                        <div class="min-w-0">
                                            <p
                                                class="text-sm font-bold text-gray-900 dark:text-white group-hover:text-primary-600 dark:group-hover:text-primary-400 transition-colors truncate tracking-tight">
                                                {{ business.name }}
                                            </p>
                                            <div class="flex flex-wrap gap-1 mt-1">
                                                <span v-for="category in business.categories?.slice(0, 3)"
                                                    :key="category.id"
                                                    class="inline-flex items-center px-2 py-0.5 bg-primary-50 dark:bg-primary-900/30 text-primary-700 dark:text-primary-400 text-[10px] font-semibold rounded-full">
                                                    {{ category.name }}
                                                </span>
                                                <span v-if="business.categories?.length > 3"
                                                    class="inline-flex items-center px-2 py-0.5 bg-gray-100 dark:bg-gray-700 text-gray-600 dark:text-gray-300 text-[10px] font-semibold rounded-full">
                                                    +{{ business.categories.length - 3 }}
                                                </span>
                                            </div>
                                            <p class="text-xs text-gray-400 dark:text-gray-500 flex items-center gap-3 mt-1.5">
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
                                                    <span class="text-amber-400">★</span>
                                                    {{ business.average_rating || 0 }}
                                                </span>
                                            </p>
                                        </div>
                                    </div>
                                    <div class="flex items-center gap-3 flex-shrink-0">
                                        <span :class="businessStatusClass(business.status)">
                                            {{ business.status.charAt(0).toUpperCase() + business.status.slice(1) }}
                                        </span>
                                        <a :href="`/admin/businesses/${business.id}`"
                                            class="text-xs font-bold text-primary-600 dark:text-primary-400 hover:text-primary-800 dark:hover:text-primary-300 transition-colors">
                                            View →
                                        </a>
                                    </div>
                                </div>
                                <div v-if="business.active_subscription" class="mt-2.5 pt-2.5 border-t border-gray-100 dark:border-gray-700">
                                    <span
                                        class="inline-flex items-center gap-1.5 text-xs font-semibold text-emerald-600 dark:text-emerald-400">
                                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5"
                                                d="M5 13l4 4L19 7" />
                                        </svg>
                                        {{ business.active_subscription.plan?.name }} Plan active
                                    </span>
                                </div>
                            </div>
                        </div>

                        <div v-else class="p-12 text-center">
                            <div
                                class="w-16 h-16 mx-auto mb-4 rounded-2xl bg-gradient-to-br from-gray-100 to-gray-50 dark:from-gray-700 dark:to-gray-800 flex items-center justify-center">
                                <svg class="w-8 h-8 text-gray-400 dark:text-gray-500" fill="none" stroke="currentColor"
                                    viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" />
                                </svg>
                            </div>
                            <p class="text-gray-600 dark:text-gray-300 font-bold tracking-tight">No businesses</p>
                            <p class="text-sm text-gray-400 dark:text-gray-500 mt-1">This owner hasn't created any businesses yet</p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- QUICK ACTIONS -->
            <div class="bg-white dark:bg-gray-800 rounded-2xl border border-gray-100 dark:border-gray-700 overflow-hidden">
                <div
                    class="px-6 py-4 border-b border-gray-100 dark:border-gray-700 bg-gradient-to-br from-gray-50 to-white dark:from-gray-800 dark:to-gray-800/50 flex items-center gap-3">
                    <div class="w-9 h-9 rounded-xl bg-primary-50 dark:bg-primary-900/30 flex items-center justify-center">
                        <svg class="w-4 h-4 text-primary-600 dark:text-primary-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M13 10V3L4 14h7v7l9-11h-7z" />
                        </svg>
                    </div>
                    <h3 class="text-sm font-bold text-gray-900 dark:text-white tracking-tight">Quick actions</h3>
                </div>
                <div class="p-6 flex flex-wrap gap-3">
                    <button v-if="owner.status === 'pending'" @click="validateOwner"
                        class="inline-flex items-center gap-2 px-5 py-2.5 bg-gradient-to-r from-emerald-500 to-emerald-600 text-white rounded-xl hover:from-emerald-600 hover:to-emerald-700 transition-all shadow-lg shadow-emerald-500/25 hover:-translate-y-0.5 font-semibold text-sm">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5"
                                d="M5 13l4 4L19 7" />
                        </svg>
                        Validate owner
                    </button>
                    <button v-if="owner.status === 'active'" @click="suspendOwner"
                        class="inline-flex items-center gap-2 px-5 py-2.5 bg-gradient-to-r from-amber-500 to-amber-600 text-white rounded-xl hover:from-amber-600 hover:to-amber-700 transition-all shadow-lg shadow-amber-500/25 hover:-translate-y-0.5 font-semibold text-sm">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728A9 9 0 015.636 5.636m12.728 12.728L5.636 5.636" />
                        </svg>
                        Suspend owner
                    </button>
                    <button v-if="owner.status === 'suspended'" @click="activateOwner"
                        class="inline-flex items-center gap-2 px-5 py-2.5 bg-gradient-to-r from-emerald-500 to-emerald-600 text-white rounded-xl hover:from-emerald-600 hover:to-emerald-700 transition-all shadow-lg shadow-emerald-500/25 hover:-translate-y-0.5 font-semibold text-sm">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M13 10V3L4 14h7v7l9-11h-7z" />
                        </svg>
                        Activate owner
                    </button>
                    <button @click="deleteOwner"
                        class="inline-flex items-center gap-2 px-5 py-2.5 bg-gradient-to-r from-red-500 to-red-600 text-white rounded-xl hover:from-red-600 hover:to-red-700 transition-all shadow-lg shadow-red-500/25 hover:-translate-y-0.5 font-semibold text-sm">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                        </svg>
                        Delete owner
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
        owner: Object,
    });

    const { error } = useToast();
    const { confirm: confirmDialog } = useConfirm();
    const { user: userStatusClass, business: businessStatusClass } = useStatusBadge();

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

    const validateOwner = async () => {
        const confirmed = await confirmDialog({
            title: 'Validate owner?',
            message: `"${props.owner.name}" will be approved as a business owner. They'll receive an email and in-app notification and can then manage their business.`,
            confirmText: 'Validate Owner',
            cancelText: 'Cancel',
            variant: 'primary',
        });

        if (!confirmed) return;

        router.post(`/admin/owners/${props.owner.id}/validate`, {}, {
            preserveScroll: true,
            // ✅ No success toast — the controller flashes
            onError: () => {
                error('Validation Failed ❌', `Failed to validate "${props.owner.name}". Please try again.`, { duration: 4000 });
            },
        });
    };

    const suspendOwner = async () => {
        const confirmed = await confirmDialog({
            title: 'Suspend owner?',
            message: `"${props.owner.name}" will be suspended and unable to log in. Their businesses will remain unaffected.`,
            confirmText: 'Suspend',
            cancelText: 'Cancel',
            variant: 'warning',
        });

        if (!confirmed) return;

        router.post(`/admin/owners/${props.owner.id}/suspend`, {}, {
            preserveScroll: true,
            // ✅ No success toast — the controller flashes
            onError: () => {
                error('Suspend Failed ❌', `Failed to suspend "${props.owner.name}". Please try again.`, { duration: 4000 });
            },
        });
    };

    const activateOwner = async () => {
        const confirmed = await confirmDialog({
            title: 'Activate owner?',
            message: `"${props.owner.name}" will be activated and able to log in again. Their businesses will remain unaffected.`,
            confirmText: 'Activate',
            cancelText: 'Cancel',
            variant: 'primary',
        });

        if (!confirmed) return;

        router.post(`/admin/owners/${props.owner.id}/activate`, {}, {
            preserveScroll: true,
            // ✅ No success toast — the controller flashes
            onError: () => {
                error('Activation Failed ❌', `Failed to activate "${props.owner.name}". Please try again.`, { duration: 4000 });
            },
        });
    };

    const deleteOwner = async () => {
        const confirmed = await confirmDialog({
            title: 'Delete owner?',
            message: `"${props.owner.name}" will be deleted along with all their businesses. Note: owners with active subscriptions cannot be deleted.`,
            confirmText: 'Delete Owner',
            cancelText: 'Cancel',
            variant: 'danger',
        });

        if (!confirmed) return;

        router.delete(`/admin/owners/${props.owner.id}`, {
            preserveScroll: true,
            // ✅ No success toast — the controller flashes
            //    'Owner deleted successfully.' and redirects to
            //    /admin/owners (Index), where the toast fires.
            onError: () => {
                error('Delete Failed ❌', `Failed to delete "${props.owner.name}". Please try again.`, { duration: 4000 });
            },
        });
    };
</script>