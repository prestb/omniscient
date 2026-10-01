<!-- resources/js/Pages/Admin/Plans/Index.vue -->
<template>
    <AuthenticatedLayout>
        <!-- COMPACT HEADER -->
        <PageHeader color="pink" :breadcrumb="[
            { label: 'Admin', href: '/admin/dashboard' },
            { label: 'Plans' }
        ]">
            <template #icon>
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                    d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4" />
            </template>
            <template #title>Subscription plans</template>
            <template #subtitle>Manage subscription plans and pricing</template>
            <template #actions>
                <a href="/admin/plans/create"
                    class="inline-flex items-center gap-2 px-4 py-2 bg-gradient-to-r from-primary-600 to-primary-700 text-white rounded-xl font-semibold hover:from-primary-700 hover:to-primary-800 transition-all shadow-lg shadow-primary-500/25 text-sm">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                    </svg>
                    Add Plan
                </a>
            </template>
        </PageHeader>

        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8 space-y-6">

            <!-- STATS -->
            <div class="grid grid-cols-2 sm:grid-cols-4 gap-4">
                <div
                    class="bg-white dark:bg-gray-800 rounded-2xl border border-gray-100 dark:border-gray-700 p-4 hover:shadow-md transition-shadow">
                    <p class="text-[10px] font-bold text-gray-400 dark:text-gray-500 uppercase tracking-widest">Total
                        plans</p>
                    <p class="text-2xl font-bold text-gray-900 dark:text-white tracking-tight mt-1">{{
                        getStatusCount('total')
                        }}</p>
                </div>
                <div
                    class="bg-white dark:bg-gray-800 rounded-2xl border border-gray-100 dark:border-gray-700 p-4 hover:shadow-md transition-shadow">
                    <p class="text-[10px] font-bold text-gray-400 dark:text-gray-500 uppercase tracking-widest">Active
                    </p>
                    <p class="text-2xl font-bold text-emerald-600 dark:text-emerald-400 tracking-tight mt-1">{{
                        getStatusCount('active') }}
                    </p>
                </div>
                <div
                    class="bg-white dark:bg-gray-800 rounded-2xl border border-gray-100 dark:border-gray-700 p-4 hover:shadow-md transition-shadow">
                    <p class="text-[10px] font-bold text-gray-400 dark:text-gray-500 uppercase tracking-widest">Featured
                    </p>
                    <p class="text-2xl font-bold text-amber-600 dark:text-amber-400 tracking-tight mt-1">{{
                        getStatusCount('featured') }}
                    </p>
                </div>
                <div
                    class="bg-white dark:bg-gray-800 rounded-2xl border border-gray-100 dark:border-gray-700 p-4 hover:shadow-md transition-shadow">
                    <p class="text-[10px] font-bold text-gray-400 dark:text-gray-500 uppercase tracking-widest">Inactive
                    </p>
                    <p class="text-2xl font-bold text-gray-500 dark:text-gray-400 tracking-tight mt-1">{{
                        getStatusCount('inactive') }}</p>
                </div>
            </div>

            <!-- TABLE -->
            <div
                class="bg-white dark:bg-gray-800 rounded-2xl border border-gray-100 dark:border-gray-700 overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                        <thead class="bg-gray-50 dark:bg-gray-900/50">
                            <tr>
                                <th
                                    class="px-6 py-4 text-left text-[10px] font-bold text-gray-500 dark:text-gray-400 uppercase tracking-widest">
                                    Plan</th>
                                <th
                                    class="px-6 py-4 text-left text-[10px] font-bold text-gray-500 dark:text-gray-400 uppercase tracking-widest">
                                    Monthly price</th>
                                <th
                                    class="px-6 py-4 text-left text-[10px] font-bold text-gray-500 dark:text-gray-400 uppercase tracking-widest">
                                    Yearly price</th>
                                <th
                                    class="px-6 py-4 text-left text-[10px] font-bold text-gray-500 dark:text-gray-400 uppercase tracking-widest">
                                    Limits</th>
                                <th
                                    class="px-6 py-4 text-left text-[10px] font-bold text-gray-500 dark:text-gray-400 uppercase tracking-widest">
                                    Status</th>
                                <th
                                    class="px-6 py-4 text-right text-[10px] font-bold text-gray-500 dark:text-gray-400 uppercase tracking-widest">
                                    Actions</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                            <tr v-for="plan in plans.data" :key="plan.id" class="transition-colors" :class="plan.is_featured
                                ? 'bg-amber-50/40 dark:bg-amber-900/10 hover:bg-amber-50/60 dark:hover:bg-amber-900/20'
                                : 'hover:bg-gray-50/50 dark:hover:bg-gray-700/30'">
                                <td class="px-6 py-4">
                                    <div class="flex items-center gap-3 min-w-0">
                                        <div
                                            class="w-10 h-10 rounded-2xl bg-gradient-to-br from-primary-500 to-primary-700 flex items-center justify-center flex-shrink-0 shadow-md">
                                            <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor"
                                                viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                    d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                                            </svg>
                                        </div>
                                        <div class="min-w-0">
                                            <div class="flex items-center gap-2 flex-wrap">
                                                <p
                                                    class="text-sm font-bold text-gray-900 dark:text-white truncate tracking-tight">
                                                    {{
                                                        plan.name
                                                    }}</p>
                                                <span v-if="plan.is_featured"
                                                    class="inline-flex items-center gap-1 px-2 py-0.5 text-[10px] font-bold uppercase tracking-wide bg-amber-100 dark:bg-amber-900/30 text-amber-700 dark:text-amber-400 rounded-full border border-amber-200 dark:border-amber-800">
                                                    <svg class="w-3 h-3" fill="currentColor" viewBox="0 0 20 20">
                                                        <path
                                                            d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z" />
                                                    </svg>
                                                    Featured
                                                </span>
                                            </div>
                                            <p v-if="plan.description"
                                                class="text-xs text-gray-400 dark:text-gray-500 truncate mt-0.5">{{
                                                    plan.description }}</p>
                                        </div>
                                    </div>
                                </td>
                                <td class="px-6 py-4">
                                    <span class="text-sm font-bold text-gray-900 dark:text-white tracking-tight">
                                        {{ plan.price_monthly ? formatPrice(plan.price_monthly) : '—' }}
                                    </span>
                                </td>
                                <td class="px-6 py-4">
                                    <div>
                                        <span class="text-sm font-bold text-gray-900 dark:text-white tracking-tight">
                                            {{ plan.price_yearly ? formatPrice(plan.price_yearly) : '—' }}
                                        </span>
                                        <span v-if="plan.price_yearly && plan.price_monthly"
                                            class="block text-[10px] font-bold uppercase tracking-wide text-emerald-600 dark:text-emerald-400 mt-0.5">
                                            Save {{ calculateSavings(plan) }}
                                        </span>
                                    </div>
                                </td>
                                <td class="px-6 py-4">
                                    <div class="flex flex-col gap-1 text-xs">
                                        <span class="text-gray-600 dark:text-gray-300 flex items-center gap-1.5">
                                            <svg class="w-3.5 h-3.5 text-gray-400 dark:text-gray-500 flex-shrink-0"
                                                fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                    d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" />
                                            </svg>
                                            <span class="font-semibold text-gray-900 dark:text-white">{{
                                                plan.max_locations ===
                                                    999 ? '∞'
                                                    :


                                                plan.max_locations }}</span>
                                            locations
                                        </span>
                                        <span class="text-gray-600 dark:text-gray-300 flex items-center gap-1.5">
                                            <svg class="w-3.5 h-3.5 text-gray-400 dark:text-gray-500 flex-shrink-0"
                                                fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                    d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z" />
                                            </svg>
                                            <span class="font-semibold text-gray-900 dark:text-white">{{ plan.max_images
                                                }}</span>
                                            images
                                        </span>
                                    </div>
                                </td>
                                <td class="px-6 py-4">
                                    <span :class="plan.is_active ? statusActive : statusInactive">
                                        {{ plan.is_active ? 'Active' : 'Inactive' }}
                                    </span>
                                </td>
                                <td class="px-6 py-4">
                                    <div class="flex flex-wrap gap-1.5 justify-end">
                                        <a :href="`/admin/plans/${plan.id}/edit`"
                                            class="inline-flex items-center gap-1.5 px-3 py-1.5 bg-gray-50 dark:bg-gray-700 text-gray-700 dark:text-gray-300 text-xs font-bold uppercase tracking-wide rounded-lg hover:bg-gray-100 dark:hover:bg-gray-600 transition-colors">
                                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor"
                                                viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                    d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                                            </svg>
                                            Edit
                                        </a>
                                        <button @click="togglePlan(plan)"
                                            class="inline-flex items-center gap-1.5 px-3 py-1.5 text-xs font-bold uppercase tracking-wide rounded-lg transition-colors"
                                            :class="plan.is_active
                                                ? 'bg-amber-50 dark:bg-amber-900/30 text-amber-700 dark:text-amber-400 hover:bg-amber-100 dark:hover:bg-amber-900/50'
                                                : 'bg-emerald-50 dark:bg-emerald-900/30 text-emerald-700 dark:text-emerald-400 hover:bg-emerald-100 dark:hover:bg-emerald-900/50'">
                                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor"
                                                viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                    d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728A9 9 0 015.636 5.636m12.728 12.728L5.636 5.636" />
                                            </svg>
                                            {{ plan.is_active ? 'Deactivate' : 'Activate' }}
                                        </button>
                                        <button @click="deletePlan(plan)"
                                            class="inline-flex items-center gap-1.5 px-3 py-1.5 bg-red-50 dark:bg-red-900/30 text-red-700 dark:text-red-400 text-xs font-bold uppercase tracking-wide rounded-lg hover:bg-red-100 dark:hover:bg-red-900/50 transition-colors">
                                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor"
                                                viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                    d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                            </svg>
                                            Delete
                                        </button>
                                    </div>
                                </td>
                            </tr>
                            <tr v-if="!plans.data || plans.data.length === 0">
                                <td colspan="6" class="px-6 py-16 text-center">
                                    <div
                                        class="w-20 h-20 mx-auto mb-4 rounded-2xl bg-gradient-to-br from-gray-100 to-gray-50 dark:from-gray-700 dark:to-gray-800 flex items-center justify-center shadow-sm">
                                        <svg class="w-10 h-10 text-gray-400 dark:text-gray-500" fill="none"
                                            stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                                        </svg>
                                    </div>
                                    <p class="text-gray-600 dark:text-gray-300 font-bold tracking-tight">No plans
                                        created yet
                                    </p>
                                    <p class="text-sm text-gray-400 dark:text-gray-500 mt-1">Click "Add Plan" to create
                                        your
                                        first
                                        subscription
                                        plan</p>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
                <div v-if="plans.links && plans.links.length > 3"
                    class="px-6 py-4 border-t border-gray-100 dark:border-gray-700">
                    <Pagination :links="plans.links" />
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
    import Pagination from '@/Components/Pagination.vue';
    import PageHeader from '@/Components/PageHeader.vue';

    const props = defineProps({
        plans: Object,
    });

    const { error } = useToast();
    const { confirm: confirmDialog } = useConfirm();

    const statusActive = 'inline-flex items-center px-2.5 py-1 text-[10px] font-bold uppercase tracking-wide rounded-full bg-emerald-100 text-emerald-700 border border-emerald-200 dark:bg-emerald-900/30 dark:text-emerald-400 dark:border-emerald-800';
    const statusInactive = 'inline-flex items-center px-2.5 py-1 text-[10px] font-bold uppercase tracking-wide rounded-full bg-gray-100 text-gray-600 border border-gray-200 dark:bg-gray-700 dark:text-gray-300 dark:border-gray-600';

    const getStatusCount = (status) => {
        if (!props.plans?.data) return 0;
        if (status === 'total') return props.plans.data.length;
        if (status === 'active') return props.plans.data.filter(p => p.is_active).length;
        if (status === 'inactive') return props.plans.data.filter(p => !p.is_active).length;
        if (status === 'featured') return props.plans.data.filter(p => p.is_featured).length;
        return 0;
    };

    const formatPrice = (price) => {
        if (!price) return 'N/A';
        return new Intl.NumberFormat('en-US', {
            style: 'currency',
            currency: 'XAF',
            minimumFractionDigits: 0,
        }).format(price);
    };

    const calculateSavings = (plan) => {
        if (!plan.price_monthly || !plan.price_yearly) return '0%';
        const monthlyTotal = plan.price_monthly * 12;
        const savings = ((monthlyTotal - plan.price_yearly) / monthlyTotal) * 100;
        return `${Math.round(savings)}%`;
    };

    const togglePlan = async (plan) => {
        const action = plan.is_active ? 'Deactivate' : 'Activate';

        const confirmed = await confirmDialog({
            title: `${action} plan?`,
            message: plan.is_active
                ? `"${plan.name}" will be hidden from the public pricing page. Existing subscribers are unaffected.`
                : `"${plan.name}" will become available for new subscriptions.`,
            confirmText: action,
            cancelText: 'Cancel',
            variant: plan.is_active ? 'warning' : 'primary',
        });

        if (!confirmed) return;

        router.post(`/admin/plans/${plan.id}/toggle`, {}, {
            preserveScroll: true,
            // ✅ No success toast — the controller flashes
            //    'Plan status updated successfully.'
            onError: () => {
                error('Action Failed ❌', `Failed to ${plan.is_active ? 'deactivate' : 'activate'} plan.`, { duration: 4000 });
            },
        });
    };

    const deletePlan = async (plan) => {
        const confirmed = await confirmDialog({
            title: 'Delete plan?',
            message: `"${plan.name}" will be permanently deleted. Note: plans with existing subscriptions cannot be deleted — deactivate them instead.`,
            confirmText: 'Delete',
            cancelText: 'Cancel',
            variant: 'danger',
        });

        if (!confirmed) return;

        router.delete(`/admin/plans/${plan.id}`, {
            preserveScroll: true,
            // ✅ No success toast — the controller flashes
            //    'Plan deleted successfully.' or the "Cannot delete plan..." error.
            onError: () => {
                error('Delete Failed ❌', 'Failed to delete plan. Please try again.', { duration: 4000 });
            },
        });
    };
</script>