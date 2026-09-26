<!-- resources/js/Pages/Admin/Payments/Show.vue -->
<template>
    <AuthenticatedLayout>
        <!-- COMPACT HEADER -->
        <PageHeader color="emerald" :breadcrumb="[
            { label: 'Admin', href: '/admin/dashboard' },
            { label: 'Payments', href: '/admin/payments' },
            { label: '#' + (transaction.transaction_id || transaction.id) }
        ]">
            <template #icon>
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                    d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z" />
            </template>
            <template #title>{{ formatPrice(transaction.amount) }}</template>
            <template #subtitle>{{ transaction.user?.name || 'N/A' }}</template>
            <template #actions>
                <span :class="statusClass(transaction.status)">
                    {{ statusLabel(transaction.status) }}
                </span>
                <a href="/admin/payments"
                    class="inline-flex items-center gap-2 px-4 py-2 border border-gray-300 dark:border-gray-600 text-gray-700 dark:text-gray-300 rounded-xl hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors text-sm font-semibold">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M10 19l-7-7m0 0l7-7m-7 7h18" />
                    </svg>
                    Back
                </a>
            </template>
        </PageHeader>

        <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 py-8 space-y-6">

            <!-- SUMMARY -->
            <div class="grid grid-cols-2 sm:grid-cols-4 gap-4">
                <div class="bg-white dark:bg-gray-800 rounded-2xl border border-gray-100 dark:border-gray-700 p-4 hover:shadow-md transition-shadow">
                    <p class="text-[10px] font-bold text-gray-400 dark:text-gray-500 uppercase tracking-widest">Amount</p>
                    <p class="text-xl font-bold text-gray-900 dark:text-white tracking-tight mt-1">{{ formatPrice(transaction.amount) }}
                    </p>
                </div>
                <div class="bg-white dark:bg-gray-800 rounded-2xl border border-gray-100 dark:border-gray-700 p-4 hover:shadow-md transition-shadow">
                    <p class="text-[10px] font-bold text-gray-400 dark:text-gray-500 uppercase tracking-widest">Method</p>
                    <p class="text-base font-semibold text-gray-700 dark:text-gray-300 flex items-center gap-1.5 mt-1">
                        <span class="text-xl">{{ getMethodIcon(transaction.payment_method) }}</span>
                        {{ getMethodLabel(transaction.payment_method) }}
                    </p>
                </div>
                <div class="bg-white dark:bg-gray-800 rounded-2xl border border-gray-100 dark:border-gray-700 p-4 hover:shadow-md transition-shadow">
                    <p class="text-[10px] font-bold text-gray-400 dark:text-gray-500 uppercase tracking-widest">Transaction ID</p>
                    <p class="text-sm font-mono font-semibold text-gray-700 dark:text-gray-300 mt-1 truncate">#{{
                        transaction.transaction_id ||
                        transaction.id }}</p>
                </div>
                <div class="bg-white dark:bg-gray-800 rounded-2xl border border-gray-100 dark:border-gray-700 p-4 hover:shadow-md transition-shadow">
                    <p class="text-[10px] font-bold text-gray-400 dark:text-gray-500 uppercase tracking-widest">Date</p>
                    <p class="text-sm font-semibold text-gray-700 dark:text-gray-300 mt-1">
                        {{ formatDate(transaction.confirmed_at || transaction.created_at) }}
                    </p>
                </div>
            </div>

            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

                <!-- MAIN CONTENT -->
                <div class="lg:col-span-2">
                    <div class="bg-white dark:bg-gray-800 rounded-2xl border border-gray-100 dark:border-gray-700 overflow-hidden">
                        <div
                            class="px-6 py-4 border-b border-gray-100 dark:border-gray-700 bg-gradient-to-br from-gray-50 to-white dark:from-gray-800 dark:to-gray-800/50 flex items-center gap-3">
                            <div class="w-9 h-9 rounded-xl bg-primary-50 dark:bg-primary-900/30 flex items-center justify-center">
                                <svg class="w-4 h-4 text-primary-600 dark:text-primary-400" fill="none" stroke="currentColor"
                                    viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M3 10h18M3 14h18m-9-4v8m-7 0h14a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v8a2 2 0 002 2z" />
                                </svg>
                            </div>
                            <div>
                                <h3 class="text-sm font-bold text-gray-900 dark:text-white tracking-tight">Transaction information</h3>
                                <p class="text-xs text-gray-400 dark:text-gray-500">Full details of this payment</p>
                            </div>
                        </div>
                        <div class="p-6 space-y-5">

                            <!-- User -->
                            <div>
                                <p
                                    class="text-[10px] font-bold text-gray-400 dark:text-gray-500 uppercase tracking-widest flex items-center gap-1.5">
                                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                                    </svg>
                                    User
                                </p>
                                <p class="text-sm font-bold text-gray-900 dark:text-white mt-1">{{ transaction.user?.name || 'N/A' }}
                                </p>
                                <p class="text-xs text-gray-400 dark:text-gray-500">{{ transaction.user?.email || 'No email' }}</p>
                            </div>

                            <!-- Plan -->
                            <div>
                                <p
                                    class="text-[10px] font-bold text-gray-400 dark:text-gray-500 uppercase tracking-widest flex items-center gap-1.5">
                                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                                    </svg>
                                    Plan
                                </p>
                                <p class="text-sm font-bold text-gray-900 dark:text-white mt-1">{{ transaction.plan?.name || 'N/A' }}
                                </p>
                                <p class="text-xs text-gray-400 dark:text-gray-500 mt-0.5">{{ transaction.duration_months || 0 }} months
                                </p>
                            </div>

                            <!-- Amount & Method -->
                            <div class="grid grid-cols-2 gap-4 bg-gray-50 dark:bg-gray-900/50 rounded-xl p-4">
                                <div>
                                    <p class="text-[10px] font-bold text-gray-400 dark:text-gray-500 uppercase tracking-widest">Amount</p>
                                    <p class="text-xl font-bold text-primary-600 dark:text-primary-400 mt-1">{{
                                        formatPrice(transaction.amount) }}</p>
                                </div>
                                <div>
                                    <p class="text-[10px] font-bold text-gray-400 dark:text-gray-500 uppercase tracking-widest">Method</p>
                                    <p class="text-sm font-bold text-gray-700 dark:text-gray-300 mt-1 flex items-center gap-1.5">
                                        <span class="text-xl">{{ getMethodIcon(transaction.payment_method) }}</span>
                                        {{ getMethodLabel(transaction.payment_method) }}
                                    </p>
                                </div>
                            </div>

                            <!-- Transaction ID -->
                            <div>
                                <p
                                    class="text-[10px] font-bold text-gray-400 dark:text-gray-500 uppercase tracking-widest flex items-center gap-1.5">
                                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M7 20l4-16m2 16l4-16M6 9h14M4 15h14" />
                                    </svg>
                                    Transaction ID
                                </p>
                                <p class="text-sm text-gray-700 dark:text-gray-300 mt-1 font-mono">{{ transaction.transaction_id || 'N/A'
                                }}</p>
                            </div>

                            <!-- Financial Transaction ID -->
                            <div v-if="transaction.payment_data?.financialTransId">
                                <p class="text-[10px] font-bold text-gray-400 dark:text-gray-500 uppercase tracking-widest">Financial
                                    Transaction
                                    ID</p>
                                <p class="text-sm text-gray-700 dark:text-gray-300 mt-1 font-mono">{{
                                    transaction.payment_data.financialTransId }}
                                </p>
                            </div>

                            <!-- Action Type -->
                            <div>
                                <p class="text-[10px] font-bold text-gray-400 dark:text-gray-500 uppercase tracking-widest">Action Type</p>
                                <p class="text-sm text-gray-700 dark:text-gray-300 mt-1 capitalize">{{ transaction.action_type || 'N/A' }}
                                </p>
                            </div>

                            <!-- Phone -->
                            <div>
                                <p class="text-[10px] font-bold text-gray-400 dark:text-gray-500 uppercase tracking-widest">Phone Number
                                </p>
                                <p class="text-sm text-gray-700 dark:text-gray-300 mt-1">{{ transaction.phone || 'N/A' }}</p>
                            </div>

                            <!-- Email -->
                            <div>
                                <p class="text-[10px] font-bold text-gray-400 dark:text-gray-500 uppercase tracking-widest">Email</p>
                                <p class="text-sm text-gray-700 dark:text-gray-300 mt-1">{{ transaction.email || 'N/A' }}</p>
                            </div>

                            <!-- Subscription -->
                            <div v-if="transaction.subscription" class="pt-4 border-t border-gray-100 dark:border-gray-700">
                                <p class="text-[10px] font-bold text-gray-400 dark:text-gray-500 uppercase tracking-widest">Related
                                    subscription
                                </p>
                                <p class="text-sm text-gray-700 dark:text-gray-300 mt-1">
                                    #{{ transaction.subscription.id }} · <span class="font-semibold">{{
                                        transaction.subscription.status }}</span>
                                </p>
                                <p class="text-xs text-gray-400 dark:text-gray-500 mt-0.5">
                                    {{ formatDate(transaction.subscription.start_date) }} → {{
                                        formatDate(transaction.subscription.end_date) }}
                                </p>
                            </div>

                            <!-- Payment Data -->
                            <div v-if="transaction.payment_data" class="pt-4 border-t border-gray-100 dark:border-gray-700">
                                <p class="text-[10px] font-bold text-gray-400 dark:text-gray-500 uppercase tracking-widest">Payment data
                                </p>
                                <pre
                                    class="text-xs text-gray-600 dark:text-gray-300 mt-2 bg-gray-50 dark:bg-gray-900/50 p-4 rounded-xl overflow-x-auto max-h-64">{{ JSON.stringify(transaction.payment_data, null, 2) }}</pre>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- SIDEBAR -->
                <div class="lg:col-span-1 space-y-6">

                    <!-- Actions -->
                    <div class="bg-white dark:bg-gray-800 rounded-2xl border border-gray-100 dark:border-gray-700 overflow-hidden sticky top-6">
                        <div
                            class="px-5 py-4 border-b border-gray-100 dark:border-gray-700 bg-gradient-to-br from-gray-50 to-white dark:from-gray-800 dark:to-gray-800/50 flex items-center gap-3">
                            <div class="w-9 h-9 rounded-xl bg-primary-50 dark:bg-primary-900/30 flex items-center justify-center">
                                <svg class="w-4 h-4 text-primary-600 dark:text-primary-400" fill="none" stroke="currentColor"
                                    viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M13 10V3L4 14h7v7l9-11h-7z" />
                                </svg>
                            </div>
                            <h3 class="text-sm font-bold text-gray-900 dark:text-white tracking-tight">Actions</h3>
                        </div>
                        <div class="p-5 space-y-3">
                            <a href="/admin/payments"
                                class="w-full inline-flex items-center justify-center gap-2 px-4 py-2.5 border border-gray-300 dark:border-gray-600 text-gray-700 dark:text-gray-300 rounded-xl hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors font-semibold text-sm">
                                View all transactions
                            </a>
                            <a :href="`/admin/payments/${transaction.id}/edit`"
                                class="w-full inline-flex items-center justify-center gap-2 px-4 py-2.5 bg-gradient-to-r from-primary-600 to-primary-700 text-white rounded-xl hover:from-primary-700 hover:to-primary-800 transition-all shadow-lg shadow-primary-500/25 hover:-translate-y-0.5 font-semibold text-sm">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                                </svg>
                                Edit transaction
                            </a>
                        </div>
                    </div>

                    <!-- Danger Zone -->
                    <div class="bg-gradient-to-br from-red-50 to-red-50/50 dark:from-red-900/20 dark:to-red-900/10 border border-red-200 dark:border-red-800 rounded-2xl p-5">
                        <div class="flex items-start gap-3 mb-3">
                            <div class="w-9 h-9 rounded-xl bg-red-100 dark:bg-red-900/40 flex items-center justify-center flex-shrink-0">
                                <svg class="w-4 h-4 text-red-600 dark:text-red-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                                </svg>
                            </div>
                            <div>
                                <p class="text-sm font-bold text-red-900 dark:text-red-400 tracking-tight">Danger zone</p>
                                <p class="text-xs text-red-700 dark:text-red-300 mt-0.5">Permanently delete this transaction</p>
                            </div>
                        </div>
                        <button @click="deleteTransaction"
                            class="w-full inline-flex items-center justify-center gap-2 px-4 py-2.5 bg-gradient-to-r from-red-500 to-red-600 text-white rounded-xl hover:from-red-600 hover:to-red-700 transition-all shadow-lg shadow-red-500/25 hover:-translate-y-0.5 font-semibold text-sm">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                            </svg>
                            Delete transaction
                        </button>
                    </div>
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
        transaction: Object,
    });

    const { error } = useToast();
    const { confirm: confirmDialog } = useConfirm();
    const { payment: statusClass, paymentLabel: statusLabel } = useStatusBadge();

    const getMethodIcon = (method) => {
        const icons = {
            'fapshi': '💳',
            'mobile_money': '📱',
            'cash': '💵',
            'bank_transfer': '🏦',
            'card': '💳',
            'other': '🔗',
        };
        return icons[method] || '🔗';
    };

    const getMethodLabel = (method) => {
        const labels = {
            'fapshi': 'Fapshi',
            'mobile_money': 'Mobile Money',
            'cash': 'Cash',
            'bank_transfer': 'Bank Transfer',
            'card': 'Card',
            'other': 'Other',
        };
        return labels[method] || method;
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

    const formatPrice = (price) => {
        if (!price) return '0 FCFA';
        return new Intl.NumberFormat('en-US', {
            style: 'currency',
            currency: 'XAF',
            minimumFractionDigits: 0,
        }).format(price);
    };

    const deleteTransaction = async () => {
        const confirmed = await confirmDialog({
            title: 'Delete transaction?',
            message: 'This transaction record will be permanently deleted. This action cannot be undone.',
            confirmText: 'Delete',
            cancelText: 'Cancel',
            variant: 'danger',
        });

        if (!confirmed) return;

        router.delete(`/admin/payments/${props.transaction.id}`, {
            preserveScroll: true,
            // ✅ No success toast — the controller flashes
            //    'Transaction deleted successfully.' and redirects to
            //    /admin/payments.
            //
            // ✅ No router.visit() — the server redirect already navigates.
            onError: () => {
                error('Delete Failed ❌', 'Failed to delete transaction. Please try again.', { duration: 4000 });
            },
        });
    };
</script>