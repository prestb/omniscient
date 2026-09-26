<!-- resources/js/Pages/Payment/FapshiCheckout.vue -->
<template>
    <PublicLayout>
        <div class="max-w-2xl mx-auto px-4 py-12">
            <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-lg border border-gray-100 dark:border-gray-700 overflow-hidden">
                <!-- Header (colored gradient — works in both modes) -->
                <div class="bg-gradient-to-r from-primary-600 to-primary-700 px-6 py-8 text-white">
                    <h1 class="text-2xl font-bold">Complete Your Payment</h1>
                    <p class="text-primary-100 mt-1">Secure payment via Fapshi</p>
                </div>

                <!-- Content -->
                <div class="p-6 space-y-6">
                    <!-- Order Summary -->
                    <div class="bg-gray-50 dark:bg-gray-900/50 rounded-xl p-4">
                        <h3 class="text-sm font-semibold text-gray-700 dark:text-gray-300">Order Summary</h3>
                        <div class="mt-2 space-y-2 text-sm">
                            <div class="flex justify-between">
                                <span class="text-gray-600 dark:text-gray-400">Plan</span>
                                <span class="font-medium text-gray-900 dark:text-white">{{ plan?.name }}</span>
                            </div>
                            <div class="flex justify-between">
                                <span class="text-gray-600 dark:text-gray-400">Duration</span>
                                <span class="font-medium text-gray-900 dark:text-white">{{ formatDuration() }}</span>
                            </div>
                            <div class="flex justify-between">
                                <span class="text-gray-600 dark:text-gray-400">Action</span>
                                <span class="font-medium text-gray-900 dark:text-white">{{ getActionLabel() }}</span>
                            </div>
                            <div class="border-t border-gray-200 dark:border-gray-700 pt-2 flex justify-between">
                                <span class="font-bold text-gray-900 dark:text-white">Total</span>
                                <span class="font-bold text-primary-600 dark:text-primary-400">{{ formatPrice(totalPrice) }}</span>
                            </div>
                        </div>
                    </div>

                    <!-- Phone Input -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                            Mobile Money Phone Number *
                        </label>
                        <input 
                            type="tel" 
                            v-model="form.phone"
                            placeholder="670000000"
                            class="w-full px-3 py-2.5 rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-900 text-gray-900 dark:text-white placeholder-gray-400 dark:placeholder-gray-500 focus:border-primary-500 focus:ring-2 focus:ring-primary-500 focus:outline-none transition-colors"
                            required
                        />
                        <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">
                            Enter the phone number linked to your mobile money account
                        </p>
                    </div>

                    <!-- Medium Selection -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                            Payment Method
                        </label>
                        <select 
                            v-model="form.medium"
                            class="w-full px-3 py-2.5 rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-900 text-gray-900 dark:text-white focus:border-primary-500 focus:ring-2 focus:ring-primary-500 focus:outline-none transition-colors"
                        >
                            <option value="">Auto-detect</option>
                            <option value="mobile money">MTN Mobile Money</option>
                            <option value="orange money">Orange Money</option>
                        </select>
                    </div>

                    <!-- Terms -->
                    <div class="flex items-start gap-2">
                        <input type="checkbox" v-model="form.agreed"
                               class="mt-0.5 rounded border-gray-300 dark:border-gray-600 text-primary-600 focus:ring-primary-500" />
                        <label class="text-sm text-gray-600 dark:text-gray-400">
                            I agree to the <a href="#" class="text-primary-600 dark:text-primary-400 hover:underline">Terms &amp; Conditions</a> and confirm the payment details above.
                        </label>
                    </div>

                    <!-- Submit -->
                    <button 
                        @click="submitPayment"
                        :disabled="loading || !form.agreed || !form.phone"
                        class="w-full inline-flex items-center justify-center gap-2 px-6 py-3 bg-gradient-to-r from-primary-600 to-primary-700 text-white rounded-xl font-semibold text-base hover:from-primary-700 hover:to-primary-800 transition-all shadow-lg shadow-primary-500/25 hover:-translate-y-0.5 disabled:opacity-50 disabled:cursor-not-allowed disabled:hover:translate-y-0"
                    >
                        <span v-if="loading" class="flex items-center gap-2">
                            <svg class="animate-spin h-4 w-4" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                            </svg>
                            Sending payment request...
                        </span>
                        <span v-else>Pay {{ formatPrice(totalPrice) }} via Mobile Money</span>
                    </button>

                    <!-- Payment Status -->
                    <div v-if="statusMessage" 
                         class="mt-4 p-4 rounded-lg border"
                         :class="statusType === 'success'
                             ? 'bg-green-50 dark:bg-green-900/20 border-green-200 dark:border-green-800 text-green-700 dark:text-green-400'
                             : statusType === 'pending'
                                 ? 'bg-yellow-50 dark:bg-yellow-900/20 border-yellow-200 dark:border-yellow-800 text-yellow-700 dark:text-yellow-400'
                                 : 'bg-red-50 dark:bg-red-900/20 border-red-200 dark:border-red-800 text-red-700 dark:text-red-400'">
                        <div class="flex items-center gap-3">
                            <span class="text-2xl">
                                {{ statusType === 'success' ? '✅' : statusType === 'pending' ? '⏳' : '❌' }}
                            </span>
                            <div>
                                <p class="font-medium">{{ statusMessage }}</p>
                                <p v-if="transactionId" class="text-xs opacity-75">Transaction ID: {{ transactionId }}</p>
                            </div>
                        </div>
                    </div>

                    <!-- Polling Status -->
                    <div v-if="transactionId && statusType === 'pending'"
                         class="mt-4 p-4 bg-yellow-50 dark:bg-yellow-900/20 border border-yellow-200 dark:border-yellow-800 rounded-lg">
                        <div class="flex items-center gap-3">
                            <div class="w-6 h-6 border-4 border-yellow-500 border-t-transparent rounded-full animate-spin"></div>
                            <div>
                                <p class="text-yellow-800 dark:text-yellow-300 font-medium">Waiting for payment confirmation...</p>
                                <p class="text-xs text-yellow-600 dark:text-yellow-400 mt-1">Please check your phone and complete the payment.</p>
                                <p class="text-xs text-yellow-600 dark:text-yellow-400 mt-1">We'll automatically activate your subscription once confirmed.</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </PublicLayout>
</template>

<script setup>
import { ref, onMounted } from 'vue';
import { router } from '@inertiajs/vue3';
import { useToast } from '@/composables/useToast';
import PublicLayout from '@/Layouts/PublicLayout.vue';
import axios from 'axios';

// ✅ Props with proper types
const props = defineProps({
    plan: Object,
    duration: {
        type: Number,
        required: true,
    },
    totalPrice: {
        type: Number,
        required: true,
    },
    billingType: String,
    actionType: String,
    subscriptionId: {
        type: Number,
        default: null,
    },
    user: Object,
});

const { success, error } = useToast();

const form = ref({
    phone: '',
    medium: '',
    name: props.user?.name || '',
    email: props.user?.email || '',
    agreed: false,
});

const loading = ref(false);
const statusType = ref(null); // 'pending', 'success', 'error'
const statusMessage = ref('');
const transactionId = ref(null);

const getActionLabel = () => {
    const labels = {
        'new': 'New Subscription',
        'renew': 'Renewal',
        'upgrade': 'Upgrade',
    };
    return labels[props.actionType] || 'Subscription';
};

const formatDuration = () => {
    const d = Number(props.duration || 0);
    if (props.billingType === 'yearly') {
        return `${d} ${d === 1 ? 'year' : 'years'}`;
    }
    return `${d} ${d === 1 ? 'month' : 'months'}`;
};

const formatPrice = (price) => {
    return new Intl.NumberFormat('fr-CM', {
        style: 'currency',
        currency: 'XAF',
        minimumFractionDigits: 0,
    }).format(price);
};

// ✅ Log props on mount
onMounted(() => {
    console.log('FapshiCheckout Props:', {
        plan: props.plan,
        duration: props.duration,
        totalPrice: props.totalPrice,
        billingType: props.billingType,
        actionType: props.actionType,
        subscriptionId: props.subscriptionId,
        user: props.user,
    });
});

const submitPayment = async () => {
    if (!form.value.phone) {
        error('Phone Number Required', 'Please enter your phone number.');
        return;
    }

    loading.value = true;
    statusType.value = null;
    statusMessage.value = '';

    // Log the request data
    console.log('Submitting payment with data:', {
        plan_id: props.plan.id,
        duration: props.duration,
        billing_type: props.billingType,
        action_type: props.actionType,
        total_price: props.totalPrice,
        subscription_id: props.subscriptionId,
        phone: form.value.phone,
        medium: form.value.medium,
        name: form.value.name,
        email: form.value.email,
    });

    try {
        const response = await axios.post('/payment/fapshi/initiate', {
            plan_id: props.plan.id,
            duration: props.duration,
            billing_type: props.billingType,
            action_type: props.actionType,
            total_price: props.totalPrice,
            subscription_id: props.subscriptionId,
            phone: form.value.phone,
            medium: form.value.medium,
            name: form.value.name,
            email: form.value.email,
        });

        console.log('Payment initiation response:', response.data);

        if (response.data.success) {
            transactionId.value = response.data.transId;
            statusType.value = 'pending';
            statusMessage.value = 'Payment request sent to your phone. Please check your mobile money app.';
            
            // ✅ Start polling for status
            startPolling(transactionId.value);
        } else {
            statusType.value = 'error';
            statusMessage.value = response.data.message || 'Payment initiation failed. Please try again.';
            error('Payment Initiation Failed', statusMessage.value);
        }
    } catch (err) {
        console.error('Payment error:', err);
        console.error('Error response:', err.response);
        console.error('Error data:', err.response?.data);
        
        statusType.value = 'error';
        statusMessage.value = err.response?.data?.message || 'Failed to initiate payment. Please try again.';
        error('Payment Failed', statusMessage.value);
    } finally {
        loading.value = false;
    }
};

const startPolling = (transId) => {
    let attempts = 0;
    const maxAttempts = 60; // 5 minutes

    const pollInterval = setInterval(async () => {
        attempts++;
        
        try {
            const response = await axios.get(`/payment/fapshi/status/${transId}`);
            console.log(`Polling attempt ${attempts}:`, response.data);
            
            // ✅ Check the status directly
            const status = response.data.status;
            
            if (status === 'SUCCESSFUL') {
                clearInterval(pollInterval);
                statusType.value = 'success';
                statusMessage.value = 'Payment successful! Your subscription is now active. 🎉';
                
                // ✅ Show success toast
                success('Payment Successful!', 'Your subscription has been activated.');
                
                // ✅ Redirect after 3 seconds
                setTimeout(() => {
                    router.visit('/owner/subscription');
                }, 3000);
            } else if (status === 'FAILED' || status === 'EXPIRED') {
                clearInterval(pollInterval);
                statusType.value = 'error';
                statusMessage.value = response.data.message || 'Payment failed. Please try again.';
                error('Payment Failed', statusMessage.value);
            } else {
                // PENDING or CREATED - still waiting
                console.log('Payment status:', status);
            }
        } catch (err) {
            console.error('Polling error:', err);
        }

        if (attempts >= maxAttempts) {
            clearInterval(pollInterval);
            statusType.value = 'error';
            statusMessage.value = 'Payment took too long. Please check your transaction status or try again.';
            error('Timeout', statusMessage.value);
        }
    }, 5000);
};
</script>