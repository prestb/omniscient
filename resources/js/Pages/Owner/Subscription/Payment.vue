<!-- resources/js/Pages/Owner/Subscription/Payment.vue -->
<template>
    <AuthenticatedLayout>
        <!-- COMPACT HEADER -->
        <PageHeader color="violet" :breadcrumb="[
            { label: 'Dashboard', href: '/owner/dashboard' },
            { label: 'Subscription', href: '/owner/subscription' },
            { label: 'Payment' }
        ]">
            <template #icon>
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                    d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z" />
            </template>
            <template #title>Complete payment</template>
            <template #subtitle>{{ business?.name }}</template>
            <template #actions>
                <a href="/owner/subscription"
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
            <div
                class="bg-white dark:bg-gray-800 rounded-2xl shadow-sm border border-gray-100 dark:border-gray-700 overflow-hidden hover:shadow-md transition-shadow">
                <div
                    class="px-6 py-4 border-b border-gray-100 dark:border-gray-700 bg-gradient-to-r from-gray-50 to-white dark:from-gray-800 dark:to-gray-800/50 flex items-center gap-2">
                    <svg class="w-5 h-5 text-gray-400 dark:text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M3 10h18M3 14h18m-9-4v8m-7 0h14a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v8a2 2 0 002 2z" />
                    </svg>
                    <h3 class="text-sm font-semibold text-gray-700 dark:text-gray-300">Payment Details</h3>
                    <span class="ml-auto text-xs text-gray-400 dark:text-gray-500">Secure payment process</span>
                </div>

                <div class="p-6">
                    <!-- Info Alert -->
                    <div
                        class="bg-gradient-to-r from-blue-50 to-blue-100/50 dark:from-blue-900/20 dark:to-blue-900/10 border border-blue-200 dark:border-blue-800 rounded-2xl p-4 mb-6 flex items-start gap-3">
                        <div class="w-10 h-10 rounded-xl bg-blue-100 dark:bg-blue-900/40 flex items-center justify-center flex-shrink-0">
                            <svg class="w-5 h-5 text-blue-600 dark:text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                        </div>
                        <div>
                            <p class="text-sm font-semibold text-blue-800 dark:text-blue-300">Manual Payment Process</p>
                            <p class="text-sm text-blue-700 dark:text-blue-400 mt-0.5">
                                Please complete the payment manually using one of the methods below.
                                Your subscription will be activated after payment confirmation.
                            </p>
                        </div>
                    </div>

                    <!-- Plan Summary -->
                    <div
                        class="bg-gradient-to-r from-gray-50 to-gray-100/50 dark:from-gray-900/50 dark:to-gray-900/30 rounded-2xl p-5 mb-6 border border-gray-100 dark:border-gray-700">
                        <h4 class="text-sm font-semibold text-gray-700 dark:text-gray-300 mb-4 flex items-center gap-2">
                            <svg class="w-4 h-4 text-gray-400 dark:text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                            </svg>
                            Plan Summary
                        </h4>
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                            <div class="space-y-2">
                                <div
                                    class="flex justify-between items-center py-1.5 border-b border-gray-100 dark:border-gray-700 last:border-0">
                                    <span class="text-sm text-gray-600 dark:text-gray-400">Plan</span>
                                    <span class="text-sm font-semibold text-gray-900 dark:text-white">{{ plan?.name }}</span>
                                </div>
                                <div
                                    class="flex justify-between items-center py-1.5 border-b border-gray-100 dark:border-gray-700 last:border-0">
                                    <span class="text-sm text-gray-600 dark:text-gray-400">Billing Period</span>
                                    <span class="text-sm font-semibold text-gray-900 dark:text-white">{{ billingPeriod === 'yearly' ?
                                        'Yearly' :
                                        'Monthly' }}</span>
                                </div>
                                <div
                                    class="flex justify-between items-center py-1.5 border-b border-gray-100 dark:border-gray-700 last:border-0">
                                    <span class="text-sm text-gray-600 dark:text-gray-400">Price</span>
                                    <span class="text-sm font-bold text-primary-600 dark:text-primary-400">{{ formatPrice(price) }}</span>
                                </div>
                                <div
                                    class="flex justify-between items-center py-1.5 border-b border-gray-100 dark:border-gray-700 last:border-0">
                                    <span class="text-sm text-gray-600 dark:text-gray-400">End Date</span>
                                    <span class="text-sm font-semibold text-gray-900 dark:text-white">{{ formatDate(endDate) }}</span>
                                </div>
                            </div>
                            <div class="space-y-2">
                                <div
                                    class="flex justify-between items-center py-1.5 border-b border-gray-100 dark:border-gray-700 last:border-0">
                                    <span class="text-sm text-gray-600 dark:text-gray-400">Locations</span>
                                    <span class="text-sm font-semibold text-gray-900 dark:text-white">{{ plan?.max_locations === 999 ?'♾️ Unlimited' : plan?.max_locations }}</span>
                                </div>
                                <div
                                    class="flex justify-between items-center py-1.5 border-b border-gray-100 dark:border-gray-700 last:border-0">
                                    <span class="text-sm text-gray-600 dark:text-gray-400">Images</span>
                                    <span class="text-sm font-semibold text-gray-900 dark:text-white">{{ plan?.max_images }}</span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Payment Methods -->
                    <div class="border-t border-gray-100 dark:border-gray-700 pt-6">
                        <h4 class="text-sm font-semibold text-gray-700 dark:text-gray-300 mb-4 flex items-center gap-2">
                            <svg class="w-4 h-4 text-gray-400 dark:text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M3 10h18M3 14h18m-9-4v8m-7 0h14a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v8a2 2 0 002 2z" />
                            </svg>
                            Payment Methods
                        </h4>
                        <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
                            <div
                                class="flex items-center gap-3 p-4 bg-gray-50 dark:bg-gray-900/50 rounded-xl hover:bg-gray-100 dark:hover:bg-gray-900 transition-colors border border-gray-100 dark:border-gray-700">
                                <span class="text-3xl">💰</span>
                                <div>
                                    <p class="text-sm font-semibold text-gray-900 dark:text-white">Cash</p>
                                    <p class="text-xs text-gray-500 dark:text-gray-400">Pay at our office</p>
                                </div>
                            </div>
                            <div
                                class="flex items-center gap-3 p-4 bg-gray-50 dark:bg-gray-900/50 rounded-xl hover:bg-gray-100 dark:hover:bg-gray-900 transition-colors border border-gray-100 dark:border-gray-700">
                                <span class="text-3xl">📱</span>
                                <div>
                                    <p class="text-sm font-semibold text-gray-900 dark:text-white">Mobile Money</p>
                                    <p class="text-xs text-gray-500 dark:text-gray-400">MTN, Orange Money</p>
                                </div>
                            </div>
                            <div
                                class="flex items-center gap-3 p-4 bg-gray-50 dark:bg-gray-900/50 rounded-xl hover:bg-gray-100 dark:hover:bg-gray-900 transition-colors border border-gray-100 dark:border-gray-700">
                                <span class="text-3xl">🏦</span>
                                <div>
                                    <p class="text-sm font-semibold text-gray-900 dark:text-white">Bank Transfer</p>
                                    <p class="text-xs text-gray-500 dark:text-gray-400">Direct bank transfer</p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Buttons -->
                    <div class="flex flex-col sm:flex-row justify-end gap-3 pt-6 border-t border-gray-200 dark:border-gray-700">
                        <a href="/owner/subscription"
                            class="inline-flex items-center justify-center px-6 py-2.5 border border-gray-300 dark:border-gray-600 text-gray-700 dark:text-gray-300 rounded-xl hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors font-medium text-sm">
                            Cancel
                        </a>
                        <button @click="submitPayment"
                            class="inline-flex items-center justify-center px-6 py-2.5 bg-gradient-to-r from-primary-600 to-primary-700 text-white rounded-xl hover:from-primary-700 hover:to-primary-800 transition-all duration-200 shadow-lg shadow-primary-100 dark:shadow-none hover:shadow-primary-200 font-medium text-sm disabled:opacity-50 disabled:cursor-not-allowed"
                            :disabled="processing">
                            <span v-if="processing" class="flex items-center gap-2">
                                <svg class="animate-spin h-4 w-4" fill="none" viewBox="0 0 24 24">
                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor"
                                        stroke-width="4">
                                    </circle>
                                    <path class="opacity-75" fill="currentColor"
                                        d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z">
                                    </path>
                                </svg>
                                Processing...
                            </span>
                            <span v-else>
                                <svg class="w-4 h-4 inline mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M5 13l4 4L19 7" />
                                </svg>
                                Submit Payment Request
                            </span>
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>

<script setup>
    import { ref } from 'vue';
    import { router } from '@inertiajs/vue3';
    import { useToast } from '@/composables/useToast';
    import { useConfirm } from '@/composables/useConfirm';
    import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
    import PageHeader from '@/Components/PageHeader.vue';

    const props = defineProps({
        plan: Object,
        business: Object,
        billingPeriod: String,
        price: Number,
        endDate: String,
    });

    const { error } = useToast();
    const { confirm: confirmDialog } = useConfirm();

    const processing = ref(false);

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

    const submitPayment = async () => {
        const confirmed = await confirmDialog({
            title: 'Submit payment request?',
            message: 'Your request will be sent to admin for approval. You will be notified once confirmed.',
            confirmText: 'Submit Request',
            cancelText: 'Cancel',
            variant: 'primary',
        });

        if (!confirmed) return;

        processing.value = true;
        router.post('/owner/subscription/process-payment', {}, {
            preserveScroll: true,
            onFinish: () => {
                processing.value = false;
            },
            // ✅ No success toast — the controller flashes
            //    'Your subscription request has been submitted...' and
            //    redirects to /owner/subscription where the layout
            //    shows the toast once.
            //
            // ✅ No router.visit() — the server redirect already lands
            //    the user on /owner/subscription.
            onError: (errors) => {
                // Client-side fallback for validation / network errors.
                const firstError = Object.values(errors)[0];
                error(
                    'Submission Failed ❌',
                    firstError || 'Failed to submit payment request. Please try again.',
                    { duration: 4000 }
                );
            }
        });
    };
</script>