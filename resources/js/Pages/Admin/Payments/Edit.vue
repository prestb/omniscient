<!-- resources/js/Pages/Admin/Payments/Edit.vue -->
<template>
    <AuthenticatedLayout>
        <!-- COMPACT HEADER -->
        <PageHeader color="emerald" :breadcrumb="[
            { label: 'Admin', href: '/admin/dashboard' },
            { label: 'Payments', href: '/admin/payments' },
            { label: '#' + (transaction.transaction_id || transaction.id), href: `/admin/payments/${transaction.id}` },
            { label: 'Edit' }
        ]">
            <template #icon>
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                    d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
            </template>
            <template #title>Edit {{ formatPrice(transaction.amount) }}</template>
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

        <div class="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8 py-8 space-y-6">

            <!-- SUMMARY -->
            <div class="bg-white dark:bg-gray-800 rounded-2xl border border-gray-100 dark:border-gray-700 p-6">
                <div class="grid grid-cols-2 sm:grid-cols-4 gap-4">
                    <div>
                        <p class="text-[10px] font-bold text-gray-400 dark:text-gray-500 uppercase tracking-widest">Amount</p>
                        <p class="text-xl font-bold text-gray-900 dark:text-white tracking-tight mt-1">{{
                            formatPrice(transaction.amount) }}</p>
                    </div>
                    <div>
                        <p class="text-[10px] font-bold text-gray-400 dark:text-gray-500 uppercase tracking-widest">Method</p>
                        <p class="text-sm font-bold text-gray-700 dark:text-gray-300 flex items-center gap-1.5 mt-1">
                            <span class="text-lg">{{ getMethodIcon(transaction.payment_method) }}</span>
                            {{ getMethodLabel(transaction.payment_method) }}
                        </p>
                    </div>
                    <div>
                        <p class="text-[10px] font-bold text-gray-400 dark:text-gray-500 uppercase tracking-widest">Transaction ID</p>
                        <p class="text-sm font-mono text-gray-700 dark:text-gray-300 mt-1 truncate">#{{ transaction.transaction_id ||
                            transaction.id }}</p>
                    </div>
                    <div>
                        <p class="text-[10px] font-bold text-gray-400 dark:text-gray-500 uppercase tracking-widest">Status</p>
                        <div class="mt-1.5">
                            <span :class="statusClass(transaction.status)">
                                {{ statusLabel(transaction.status) }}
                            </span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- FORM -->
            <div class="bg-white dark:bg-gray-800 rounded-2xl border border-gray-100 dark:border-gray-700 overflow-hidden">
                <div
                    class="px-6 py-4 border-b border-gray-100 dark:border-gray-700 bg-gradient-to-br from-gray-50 to-white dark:from-gray-800 dark:to-gray-800/50 flex items-center gap-3">
                    <div class="w-9 h-9 rounded-xl bg-primary-50 dark:bg-primary-900/30 flex items-center justify-center">
                        <svg class="w-4 h-4 text-primary-600 dark:text-primary-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                        </svg>
                    </div>
                    <div>
                        <h3 class="text-sm font-bold text-gray-900 dark:text-white tracking-tight">Transaction details</h3>
                        <p class="text-xs text-gray-400 dark:text-gray-500">Fields marked * are required</p>
                    </div>
                </div>

                <div class="p-6">
                    <form @submit.prevent="submit" class="space-y-5">

                        <!-- Read-only user -->
                        <div>
                            <label
                                class="block text-[10px] font-bold text-gray-500 dark:text-gray-400 uppercase tracking-widest mb-2">User</label>
                            <div class="px-4 py-2.5 bg-gray-50 dark:bg-gray-900/50 rounded-xl text-gray-700 dark:text-gray-300 text-sm">
                                {{ transaction.user?.name || 'N/A' }} ({{ transaction.user?.email || 'No email' }})
                            </div>
                        </div>

                        <!-- Read-only plan -->
                        <div>
                            <label
                                class="block text-[10px] font-bold text-gray-500 dark:text-gray-400 uppercase tracking-widest mb-2">Plan</label>
                            <div class="px-4 py-2.5 bg-gray-50 dark:bg-gray-900/50 rounded-xl text-gray-700 dark:text-gray-300 text-sm">
                                {{ transaction.plan?.name || 'N/A' }} · {{ transaction.duration_months || 0 }} months
                            </div>
                        </div>

                        <!-- Amount + Method -->
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <div>
                                <label class="block text-[10px] font-bold text-gray-500 dark:text-gray-400 uppercase tracking-widest mb-2">
                                    Amount <span class="text-red-500">*</span>
                                </label>
                                <div class="relative">
                                    <div class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none">
                                        <span class="text-gray-400 dark:text-gray-500 font-semibold text-xs">FCFA</span>
                                    </div>
                                    <input type="number" v-model="form.amount" step="0.01" min="0" required
                                        class="w-full pl-16 pr-4 py-2.5 border border-gray-200 dark:border-gray-600 bg-white dark:bg-gray-900 text-gray-900 dark:text-white placeholder-gray-400 dark:placeholder-gray-500 rounded-xl focus:outline-none focus:ring-2 focus:ring-primary-500 focus:border-transparent transition-colors text-sm" />
                                </div>
                                <p v-if="form.errors.amount" class="text-red-500 dark:text-red-400 text-xs font-medium mt-1.5">{{
                                    form.errors.amount }}</p>
                            </div>
                            <div>
                                <label class="block text-[10px] font-bold text-gray-500 dark:text-gray-400 uppercase tracking-widest mb-2">
                                    Payment method <span class="text-red-500">*</span>
                                </label>
                                <select v-model="form.payment_method" required
                                    class="w-full px-4 py-2.5 border border-gray-200 dark:border-gray-600 bg-white dark:bg-gray-900 text-gray-900 dark:text-white rounded-xl focus:outline-none focus:ring-2 focus:ring-primary-500 focus:border-transparent transition-colors text-sm appearance-none">
                                    <option value="fapshi">💳 Fapshi</option>
                                    <option value="mobile_money">📱 Mobile Money</option>
                                    <option value="cash">💵 Cash</option>
                                    <option value="bank_transfer">🏦 Bank Transfer</option>
                                    <option value="card">💳 Card</option>
                                    <option value="other">🔗 Other</option>
                                </select>
                                <p v-if="form.errors.payment_method" class="text-red-500 dark:text-red-400 text-xs font-medium mt-1.5">{{
                                    form.errors.payment_method }}</p>
                            </div>
                        </div>

                        <!-- Status -->
                        <div>
                            <label class="block text-[10px] font-bold text-gray-500 dark:text-gray-400 uppercase tracking-widest mb-2">
                                Status <span class="text-red-500">*</span>
                            </label>
                            <select v-model="form.status" required
                                class="w-full px-4 py-2.5 border border-gray-200 dark:border-gray-600 bg-white dark:bg-gray-900 text-gray-900 dark:text-white rounded-xl focus:outline-none focus:ring-2 focus:ring-primary-500 focus:border-transparent transition-colors text-sm appearance-none">
                                <option value="created">Created</option>
                                <option value="pending">Pending</option>
                                <option value="successful">Successful</option>
                                <option value="failed">Failed</option>
                                <option value="expired">Expired</option>
                            </select>
                            <p v-if="form.errors.status" class="text-red-500 dark:text-red-400 text-xs font-medium mt-1.5">{{
                                form.errors.status
                                }}</p>
                        </div>

                        <!-- Read-only transaction ID -->
                        <div>
                            <label
                                class="block text-[10px] font-bold text-gray-500 dark:text-gray-400 uppercase tracking-widest mb-2">Transaction
                                ID</label>
                            <div class="px-4 py-2.5 bg-gray-50 dark:bg-gray-900/50 rounded-xl text-gray-700 dark:text-gray-300 font-mono text-sm">
                                {{ transaction.transaction_id || 'N/A' }}
                            </div>
                        </div>

                        <!-- Read-only phone -->
                        <div>
                            <label
                                class="block text-[10px] font-bold text-gray-500 dark:text-gray-400 uppercase tracking-widest mb-2">Phone
                                number</label>
                            <div class="px-4 py-2.5 bg-gray-50 dark:bg-gray-900/50 rounded-xl text-gray-700 dark:text-gray-300 text-sm">
                                {{ transaction.phone || 'N/A' }}
                            </div>
                        </div>

                        <!-- Confirmed At -->
                        <div>
                            <label class="block text-[10px] font-bold text-gray-500 dark:text-gray-400 uppercase tracking-widest mb-2">
                                Confirmed date
                            </label>
                            <input type="datetime-local" v-model="form.confirmed_at"
                                class="w-full px-4 py-2.5 border border-gray-200 dark:border-gray-600 bg-white dark:bg-gray-900 text-gray-900 dark:text-white rounded-xl focus:outline-none focus:ring-2 focus:ring-primary-500 focus:border-transparent transition-colors text-sm" />
                            <p v-if="form.errors.confirmed_at" class="text-red-500 dark:text-red-400 text-xs font-medium mt-1.5">{{
                                form.errors.confirmed_at }}</p>
                        </div>

                        <!-- Actions -->
                        <div class="flex flex-col-reverse sm:flex-row justify-end gap-3 pt-6 border-t border-gray-100 dark:border-gray-700">
                            <a href="/admin/payments"
                                class="px-6 py-2.5 border border-gray-300 dark:border-gray-600 text-gray-700 dark:text-gray-300 rounded-xl hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors font-semibold text-sm text-center">
                                Cancel
                            </a>
                            <button type="submit" :disabled="form.processing"
                                class="inline-flex items-center justify-center gap-2 px-6 py-2.5 bg-gradient-to-r from-primary-600 to-primary-700 text-white rounded-xl hover:from-primary-700 hover:to-primary-800 transition-all shadow-lg shadow-primary-500/25 hover:-translate-y-0.5 font-semibold text-sm disabled:opacity-50 disabled:cursor-not-allowed disabled:hover:translate-y-0">
                                <span v-if="form.processing" class="flex items-center gap-2">
                                    <svg class="animate-spin h-4 w-4" fill="none" viewBox="0 0 24 24">
                                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor"
                                            stroke-width="4"></circle>
                                        <path class="opacity-75" fill="currentColor"
                                            d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z">
                                        </path>
                                    </svg>
                                    Updating…
                                </span>
                                <span v-else class="flex items-center gap-2">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5"
                                            d="M5 13l4 4L19 7" />
                                    </svg>
                                    Update transaction
                                </span>
                            </button>
                        </div>
                    </form>
                </div>
            </div>

            <!-- DANGER ZONE -->
            <div class="bg-gradient-to-br from-red-50 to-red-50/50 dark:from-red-900/20 dark:to-red-900/10 border border-red-200 dark:border-red-800 rounded-2xl p-6">
                <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                    <div class="flex items-start gap-4">
                        <div class="w-11 h-11 rounded-xl bg-red-100 dark:bg-red-900/40 flex items-center justify-center flex-shrink-0">
                            <svg class="w-5 h-5 text-red-600 dark:text-red-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                            </svg>
                        </div>
                        <div>
                            <h4 class="text-sm font-bold text-red-900 dark:text-red-400 tracking-tight">Delete transaction</h4>
                            <p class="text-xs text-red-700 dark:text-red-300 mt-1">
                                Permanently delete this transaction record. Cannot be undone.
                            </p>
                        </div>
                    </div>
                    <button @click="deleteTransaction"
                        class="inline-flex items-center gap-2 px-6 py-2.5 bg-gradient-to-r from-red-500 to-red-600 text-white rounded-xl hover:from-red-600 hover:to-red-700 transition-all shadow-lg shadow-red-500/25 hover:-translate-y-0.5 font-semibold text-sm whitespace-nowrap flex-shrink-0">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                        </svg>
                        Delete transaction
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
        transaction: Object,
    });

    const { error } = useToast();
    const { confirm: confirmDialog } = useConfirm();
    const { payment: statusClass, paymentLabel: statusLabel } = useStatusBadge();

    // ✅ useForm — gives form.errors + form.processing automatically
    const form = useForm({
        amount: props.transaction.amount,
        payment_method: props.transaction.payment_method || 'fapshi',
        status: props.transaction.status,
        confirmed_at: props.transaction.confirmed_at
            ? new Date(props.transaction.confirmed_at).toISOString().slice(0, 16)
            : '',
    });

    const formatPrice = (price) => {
        if (!price) return '0 FCFA';
        return new Intl.NumberFormat('en-US', {
            style: 'currency',
            currency: 'XAF',
            minimumFractionDigits: 0,
        }).format(price);
    };

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

    const submit = () => {
        form.put(`/admin/payments/${props.transaction.id}`, {
            preserveScroll: true,
            // ✅ No success toast — the controller flashes
            //    'Transaction updated successfully.' and redirects to
            //    /admin/payments.
            //
            // ✅ No router.visit() — the server redirect already navigates.
            onError: (errors) => {
                const firstError = Object.values(errors)[0];
                error('Update Failed ❌', firstError || 'Failed to update transaction. Please check the form and try again.', { duration: 5000 });
            },
        });
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
            onError: () => {
                error('Delete Failed ❌', 'Failed to delete transaction. Please try again.', { duration: 4000 });
            },
        });
    };
</script>