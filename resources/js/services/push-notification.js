// resources/js/services/push-notification.js
export class PushNotificationService {
    constructor() {
        this.vapidPublicKey = import.meta.env.VITE_VAPID_PUBLIC_KEY;
        this.registration = null;
        this.isSupported = 'serviceWorker' in navigator && 'PushManager' in window;
    }

    async init() {
        if (!this.isSupported) {
            console.warn('Push notifications not supported');
            return false;
        }

        try {
            this.registration = await navigator.serviceWorker.ready;
            return true;
        } catch (error) {
            console.error('Failed to initialize push notifications:', error);
            return false;
        }
    }

    async subscribe() {
        if (!this.isSupported) {
            alert('Push notifications are not supported in this browser');
            return false;
        }

        // Check permission
        const permission = await Notification.requestPermission();
        if (permission !== 'granted') {
            alert('Please allow notifications to receive updates');
            return false;
        }

        try {
            const subscription = await this.registration.pushManager.subscribe({
                userVisibleOnly: true,
                applicationServerKey: this.urlBase64ToUint8Array(this.vapidPublicKey)
            });

            // Send subscription to server
            const response = await fetch('/api/push/subscribe', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                },
                body: JSON.stringify(subscription)
            });

            if (!response.ok) {
                throw new Error('Failed to save subscription');
            }

            console.log('Push subscription successful');
            localStorage.setItem('push-subscribed', 'true');
            return true;

        } catch (error) {
            console.error('Push subscription error:', error);
            alert('Failed to subscribe to notifications');
            return false;
        }
    }

    async unsubscribe() {
        try {
            const subscription = await this.registration.pushManager.getSubscription();
            if (subscription) {
                // Send unsubscribe to server
                await fetch('/api/push/unsubscribe', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                    },
                    body: JSON.stringify({ endpoint: subscription.endpoint })
                });

                await subscription.unsubscribe();
                localStorage.removeItem('push-subscribed');
                console.log('Push unsubscribed');
                return true;
            }
        } catch (error) {
            console.error('Unsubscribe error:', error);
            return false;
        }
    }

    urlBase64ToUint8Array(base64String) {
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
    }

    async getSubscription() {
        if (!this.registration) return null;
        return await this.registration.pushManager.getSubscription();
    }

    isSubscribed() {
        return localStorage.getItem('push-subscribed') === 'true';
    }
}

export default new PushNotificationService();