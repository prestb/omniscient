<!-- resources/js/Pages/Admin/Payments/Create.vue -->
<template>
    <AuthenticatedLayout>
        <!-- COMPACT HEADER -->
        <PageHeader color="emerald" :breadcrumb="[
            { label: 'Admin', href: '/admin/dashboard' },
            { label: 'Payments', href: '/admin/payments' },
            { label: 'Record Payment' }
        ]">
            <template #icon>
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                    d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z" />
            </template>
            <template #title>Record payment</template>
            <template #subtitle>Record a manual payment from a business</template>
            <template #actions>
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

            <!-- INFO CARD -->
            <div
                class="bg-gradient-to-br from-blue-50 to-indigo-50 dark:from-blue-900/20 dark:to-indigo-900/10 border border-blue-200 dark:border-blue-800 rounded-2xl p-5 flex items-start gap-4">
                <div class="w-11 h-11 rounded-xl bg-blue-100 dark:bg-blue-900/40 flex items-center justify-center flex-shrink-0">
                    <svg class="w-5 h-5 text-blue-600 dark:text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                </div>
                <div>
                    <p class="text-sm font-bold text-blue-900 dark:text-blue-300 tracking-tight">Payment recording</p>
                    <p class="text-xs text-blue-700 dark:text-blue-400 mt-1">
                        Select a business and subscription to record a manual payment. The subscription will be
                        automatically
                        updated.
                    </p>
                </div>
            </div>

            <!-- FORM -->
            <div class="bg-white dark:bg-gray-800 rounded-2xl border border-gray-100 dark:border-gray-700 overflow-hidden">
                <div
                    class="px-6 py-4 border-b border-gray-100 dark:border-gray-700 bg-gradient-to-br from-gray-50 to-white dark:from-gray-800 dark:to-gray-800/50 flex items-center gap-3">
                    <div class="w-9 h-9 rounded-xl bg-primary-50 dark:bg-primary-900/30 flex items-center justify-center">
                        <svg class="w-4 h-4 text-primary-600 dark:text-primary-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M3 10h18M3 14h18m-9-4v8m-7 0h14a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v8a2 2 0 002 2z" />
                        </svg>
                    </div>
                    <div>
                        <h3 class="text-sm font-bold text-gray-900 dark:text-white tracking-tight">Payment details</h3>
                        <p class="text-xs text-gray-400 dark:text-gray-500">Fields marked * are required</p>
                    </div>
                </div>

                <div class="p-6">
                    <form @submit.prevent="submit" class="space-y-5">

                        <!-- Business -->
                        <div>
                            <label class="block text-[10px] font-bold text-gray-500 dark:text-gray-400 uppercase tracking-widest mb-2">
                                Business <span class="text-red-500">*</span>
                            </label>
                            <select v-model="form.business_id" @change="loadSubscriptions" required
                                class="w-full px-4 py-2.5 border border-gray-200 dark:border-gray-600 rounded-xl bg-white dark:bg-gray-900 text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-primary-500 focus:border-transparent transition-colors text-sm appearance-none">
                                <option value="">Select business</option>
                                <option v-for="business in businesses" :key="business.id" :value="business.id">
                                    {{ business.name }}
                                </option>
                            </select>
                            <p v-if="form.errors.business_id" class="text-red-500 dark:text-red-400 text-xs font-medium mt-1.5">{{
                                form.errors.business_id }}</p>
                        </div>

                        <!-- Subscription -->
                        <div>
                            <label class="block text-[10px] font-bold text-gray-500 dark:text-gray-400 uppercase tracking-widest mb-2">
                                Subscription <span class="text-red-500">*</span>
                            </label>
                            <select v-model="form.subscription_id" required
                                class="w-full px-4 py-2.5 border border-gray-200 dark:border-gray-600 rounded-xl bg-white dark:bg-gray-900 text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-primary-500 focus:border-transparent transition-colors text-sm appearance-none">
                                <option value="">Select subscription</option>
                                <option v-for="sub in subscriptions" :key="sub.id" :value="sub.id">
                                    {{ sub.plan_name }} – {{ formatDate(sub.start_date) }} to {{
                                    formatDate(sub.end_date) }} ({{
                                    sub.status_label }})
                                </option>
                            </select>
                            <p v-if="form.errors.subscription_id" class="text-red-500 dark:text-red-400 text-xs font-medium mt-1.5">{{
                                form.errors.subscription_id }}</p>
                            <p v-if="subscriptions.length === 0 && form.business_id"
                                class="text-amber-600 dark:text-amber-400 text-xs font-medium mt-1.5 flex items-center gap-1.5">
                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                                </svg>
                                No subscriptions found for this business.
                            </p>
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
                                    <input type="number" v-model="form.amount" step="0.01" min="0" placeholder="0.00"
                                        required
                                        class="w-full pl-16 pr-4 py-2.5 border border-gray-200 dark:border-gray-600 rounded-xl bg-white dark:bg-gray-900 text-gray-900 dark:text-white placeholder-gray-400 dark:placeholder-gray-500 focus:outline-none focus:ring-2 focus:ring-primary-500 focus:border-transparent transition-colors text-sm" />
                                </div>
                                <p v-if="form.errors.amount" class="text-red-500 dark:text-red-400 text-xs font-medium mt-1.5">{{
                                    form.errors.amount }}</p>
                            </div>
                            <div>
                                <label class="block text-[10px] font-bold text-gray-500 dark:text-gray-400 uppercase tracking-widest mb-2">
                                    Payment method <span class="text-red-500">*</span>
                                </label>
                                <select v-model="form.method" required
                                    class="w-full px-4 py-2.5 border border-gray-200 dark:border-gray-600 rounded-xl bg-white dark:bg-gray-900 text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-primary-500 focus:border-transparent transition-colors text-sm appearance-none">
                                    <option value="mobile_money">📱 Mobile Money</option>
                                    <option value="cash">💵 Cash</option>
                                    <option value="bank_transfer">🏦 Bank Transfer</option>
                                    <option value="card">💳 Card</option>
                                    <option value="other">🔗 Other</option>
                                </select>
                                <p v-if="form.errors.method" class="text-red-500 dark:text-red-400 text-xs font-medium mt-1.5">{{
                                    form.errors.method }}</p>
                            </div>
                        </div>

                        <!-- Reference -->
                        <div>
                            <label class="block text-[10px] font-bold text-gray-500 dark:text-gray-400 uppercase tracking-widest mb-2">
                                Reference
                            </label>
                            <input type="text" v-model="form.reference"
                                class="w-full px-4 py-2.5 border border-gray-200 dark:border-gray-600 rounded-xl bg-white dark:bg-gray-900 text-gray-900 dark:text-white placeholder-gray-400 dark:placeholder-gray-500 focus:outline-none focus:ring-2 focus:ring-primary-500 focus:border-transparent transition-colors text-sm"
                                placeholder="Transaction reference number" />
                            <p v-if="form.errors.reference" class="text-red-500 dark:text-red-400 text-xs font-medium mt-1.5">{{
                                form.errors.reference }}</p>
                        </div>

                        <!-- Payment Date -->
                        <div>
                            <label class="block text-[10px] font-bold text-gray-500 dark:text-gray-400 uppercase tracking-widest mb-2">
                                Payment date <span class="text-red-500">*</span>
                            </label>
                            <input type="date" v-model="form.paid_at" required
                                class="w-full px-4 py-2.5 border border-gray-200 dark:border-gray-600 rounded-xl bg-white dark:bg-gray-900 text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-primary-500 focus:border-transparent transition-colors text-sm" />
                            <p v-if="form.errors.paid_at" class="text-red-500 dark:text-red-400 text-xs font-medium mt-1.5">{{
                                form.errors.paid_at
                                }}</p>
                        </div>

                        <!-- Notes -->
                        <div>
                            <label class="block text-[10px] font-bold text-gray-500 dark:text-gray-400 uppercase tracking-widest mb-2">
                                Notes
                            </label>
                            <textarea v-model="form.notes" rows="3"
                                class="w-full px-4 py-2.5 border border-gray-200 dark:border-gray-600 rounded-xl bg-white dark:bg-gray-900 text-gray-900 dark:text-white placeholder-gray-400 dark:placeholder-gray-500 focus:outline-none focus:ring-2 focus:ring-primary-500 focus:border-transparent transition-colors resize-none text-sm"
                                placeholder="Additional notes about this payment"></textarea>
                            <p v-if="form.errors.notes" class="text-red-500 dark:text-red-400 text-xs font-medium mt-1.5">{{
                                form.errors.notes }}
                            </p>
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
                                    Recording…
                                </span>
                                <span v-else class="flex items-center gap-2">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5"
                                            d="M5 13l4 4L19 7" />
                                    </svg>
                                    Record Payment
                                </span>
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>


<script setup>
    import { ref, onMounted } from 'vue';
    import { useForm } from '@inertiajs/vue3';
    import { useToast } from '@/composables/useToast';
    import axios from 'axios';
    import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
    import PageHeader from '@/Components/PageHeader.vue';

    const props = defineProps({
        businesses: Array,
        subscriptions: Array,
        selectedBusinessId: Number,
    });

    const { error } = useToast();

    // ✅ useForm — gives form.errors + form.processing automatically
    const form = useForm({
        business_id: props.selectedBusinessId || '',
        subscription_id: '',
        amount: '',
        method: 'mobile_money',
        reference: '',
        notes: '',
        paid_at: new Date().toISOString().split('T')[0],
    });

    const subscriptions = ref(props.subscriptions || []);

    const formatDate = (date) => {
        if (!date) return 'N/A';
        return new Date(date).toLocaleDateString('en-US', {
            year: 'numeric',
            month: 'short',
            day: 'numeric'
        });
    };

    const loadSubscriptions = async () => {
        if (!form.business_id) {
            subscriptions.value = [];
            return;
        }
        try {
            const response = await axios.get(`/admin/api/subscriptions?business_id=${form.business_id}`);
            subscriptions.value = response.data;
            form.subscription_id = '';
        } catch (err) {
            console.error('Error loading subscriptions:', err);
            subscriptions.value = [];
        }
    };

    onMounted(() => {
        if (form.business_id) {
            loadSubscriptions();
        }
    });

    const submit = () => {
        form.post('/admin/payments', {
            preserveScroll: true,
            // ✅ No success toast — the controller flashes
            //    'Payment recorded successfully.' and redirects to
            //    /admin/payments.
            //
            // ✅ No router.visit() — the server redirect already navigates.
            //
            // ⚠️ NOTE: This creates a `Payment` model record (payments table).
            //    The admin payments Index lists PaymentTransaction records
            //    from a different table — so newly-created manual payments
            //    do NOT appear in the list. See architectural backlog item.
            onError: (errors) => {
                const firstError = Object.values(errors)[0];
                error('Recording Failed ❌', firstError || 'Failed to record payment. Please check the form and try again.', { duration: 5000 });
            },
        });
    };
</script>