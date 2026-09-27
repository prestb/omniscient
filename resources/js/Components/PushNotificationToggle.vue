<!-- resources/js/Components/PushNotificationToggle.vue -->
<template>
    <div class="inline-flex items-center gap-3">
        <!-- Notification Status -->
        <div class="flex items-center gap-2">
            <span v-if="isSupported" class="text-xs text-gray-500 dark:text-gray-400">
                {{ isSubscribed ? '' : '' }}
            </span>
            <span v-else class="text-xs text-gray-400 dark:text-gray-500">
                ⚠️ Not supported
            </span>
        </div>

        <!-- Toggle Button -->
        <button
            @click="togglePushNotifications"
            :disabled="isLoading || !isSupported"
            class="relative inline-flex items-center h-6 rounded-full w-11 transition-colors focus:outline-none focus:ring-2 focus:ring-primary-500 focus:ring-offset-2 dark:focus:ring-offset-gray-900"
            :class="isSubscribed ? 'bg-primary-600' : 'bg-gray-300 dark:bg-gray-600'"
        >
            <span
                class="inline-block h-4 w-4 transform rounded-full bg-white transition-transform"
                :class="isSubscribed ? 'translate-x-6' : 'translate-x-1'"
            />
        </button>

        <!-- Status Message -->
        <span v-if="statusMessage" class="text-xs" :class="statusType === 'success' ? 'text-green-600 dark:text-green-400' : 'text-red-600 dark:text-red-400'">
            {{ statusMessage }}
        </span>
    </div>
</template>

<script setup>
import { ref, onMounted } from 'vue';
import { useToast } from '@/composables/useToast';

const { success, error } = useToast();

const isSupported = ref(false);
const isSubscribed = ref(false);
const isLoading = ref(false);
const statusMessage = ref('');
const statusType = ref('');
let swRegistration = null;
let pushSubscription = null;

// ✅ Check if VAPID key exists
const VAPID_PUBLIC_KEY = import.meta.env.VITE_VAPID_PUBLIC_KEY;

console.log('VAPID Public Key:', VAPID_PUBLIC_KEY ? '✅ Set' : '❌ Missing');

onMounted(async () => {
    await initPushNotifications();
});

/**
 * Initialize push notifications
 */
const initPushNotifications = async () => {
    try {
        // Check if browser supports push notifications
        if (!('serviceWorker' in navigator) || !('PushManager' in window)) {
            console.warn('Push notifications not supported');
            isSupported.value = false;
            return;
        }

        isSupported.value = true;

        // ✅ Check if VAPID key is set
        if (!VAPID_PUBLIC_KEY || VAPID_PUBLIC_KEY === '') {
            console.warn('VAPID public key not set. Please add VITE_VAPID_PUBLIC_KEY to .env');
            statusMessage.value = '⚠️ Push notifications not configured';
            statusType.value = 'error';
            return;
        }

        // Register service worker
        swRegistration = await navigator.serviceWorker.register('/sw.js');
        console.log('Service Worker registered');

        // Check if already subscribed
        pushSubscription = await swRegistration.pushManager.getSubscription();
        isSubscribed.value = !!pushSubscription;

        if (isSubscribed.value) {
            await verifySubscription(pushSubscription);
        }
    } catch (err) {
        console.error('Push init error:', err);
        isSupported.value = false;
    }
};

/**
 * Toggle push notifications on/off
 */
const togglePushNotifications = async () => {
    if (isLoading.value) return;

    isLoading.value = true;
    statusMessage.value = '';
    statusType.value = '';

    try {
        if (isSubscribed.value) {
            await unsubscribePush();
        } else {
            await subscribePush();
        }
    } catch (err) {
        console.error('Toggle error:', err);
        statusMessage.value = err.message || 'An error occurred';
        statusType.value = 'error';
        error('Push Notification Error', statusMessage.value);
    } finally {
        isLoading.value = false;
    }
};

/**
 * Subscribe to push notifications
 */
const subscribePush = async () => {
    try {
        // ✅ Check if VAPID key is set
        if (!VAPID_PUBLIC_KEY || VAPID_PUBLIC_KEY === '') {
            throw new Error('VAPID public key not configured');
        }

        // Request permission
        const permission = await Notification.requestPermission();
        
        if (permission !== 'granted') {
            statusMessage.value = 'Permission denied. Please allow notifications.';
            statusType.value = 'error';
            error('Permission Denied', statusMessage.value);
            return;
        }

        // ✅ Check if service worker is registered
        if (!swRegistration) {
            swRegistration = await navigator.serviceWorker.register('/sw.js');
        }

        // Subscribe to push
        try {
            pushSubscription = await swRegistration.pushManager.subscribe({
                userVisibleOnly: true,
                applicationServerKey: urlBase64ToUint8Array(VAPID_PUBLIC_KEY),
            });
        } catch (err) {
            console.error('Push subscription error:', err);
            throw new Error('Failed to subscribe: ' + err.message);
        }

        console.log('Push subscription:', pushSubscription);

        // Save subscription to server
        const response = await axios.post('/api/push/subscribe', {
            endpoint: pushSubscription.endpoint,
            keys: {
                p256dh: btoa(String.fromCharCode.apply(null, new Uint8Array(pushSubscription.getKey('p256dh')))),
                auth: btoa(String.fromCharCode.apply(null, new Uint8Array(pushSubscription.getKey('auth')))),
            },
            user_agent: navigator.userAgent,
        });

        console.log('Server response:', response.data);

        if (response.data.success) {
            isSubscribed.value = true;
            statusMessage.value = '';
            statusType.value = 'success';
            success('Notifications Enabled', 'You will now receive push notifications.');
        }
    } catch (err) {
        console.error('Subscribe error:', err);
        statusMessage.value = err.message || 'Failed to subscribe';
        statusType.value = 'error';
        throw err;
    }
};

/**
 * Unsubscribe from push notifications
 */
const unsubscribePush = async () => {
    try {
        if (pushSubscription) {
            await pushSubscription.unsubscribe();
        }

        // Remove from server
        if (pushSubscription) {
            await axios.post('/api/push/unsubscribe', {
                endpoint: pushSubscription.endpoint,
            });
        }

        isSubscribed.value = false;
        pushSubscription = null;
        statusMessage.value = '';
        statusType.value = 'success';
        success('Notifications Disabled', 'You will no longer receive push notifications.');
    } catch (err) {
        console.error('Unsubscribe error:', err);
        statusMessage.value = err.message || 'Failed to unsubscribe';
        statusType.value = 'error';
        throw err;
    }
};

/**
 * Verify subscription with server
 */
const verifySubscription = async (subscription) => {
    try {
        console.log('Subscription verified');
    } catch (err) {
        console.error('Verification error:', err);
        await unsubscribePush();
    }
};

/**
 * Helper: Convert base64 to Uint8Array
 */
const urlBase64ToUint8Array = (base64String) => {
    // ✅ Check if base64String is valid
    if (!base64String || base64String === '') {
        console.error('Invalid base64 string provided to urlBase64ToUint8Array');
        return new Uint8Array(0);
    }

    try {
        const padding = '='.repeat((4 - base64String.length % 4) % 4);
        const base64 = (base64String + padding)
            .replace(/\-/g, '+')
            .replace(/_/g, '/');

        const rawData = window.atob(base64);
        const outputArray = new Uint8Array(rawData.length);

        for (let i = 0; i < rawData.length; ++i) {
            outputArray[i] = rawData.charCodeAt(i);
        }
        return outputArray;
    } catch (err) {
        console.error('Error converting base64 to Uint8Array:', err);
        return new Uint8Array(0);
    }
};
</script>