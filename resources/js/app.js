// resources/js/app.js
import { createInertiaApp } from '@inertiajs/vue3';
import { resolvePageComponent } from 'laravel-vite-plugin/inertia-helpers';
import { createApp, h } from 'vue';
import { ZiggyVue } from '../../vendor/tightenco/ziggy';
import axios from 'axios';
import '../css/app.css';

// Import Toast system
import ToastContainer from '@/Components/Toast/ToastContainer.vue';
import { useToast } from '@/composables/useToast';

// ============== GLOBAL AXIOS 429 INTERCEPTOR ==============
// Shows a friendly toast whenever any axios call hits a rate limit.
// Page navigations render the 429 error page; this handles JSON calls
// (favorites toggle, coupon token generation, review submit, etc.)
const { warning: toastWarning } = useToast();

axios.interceptors.response.use(
    (response) => response,
    (error) => {
        if (error?.response?.status === 429) {
            const message =
                error.response.data?.message ||
                "You're doing that too quickly. Please wait a moment.";

            const retryAfter = error.response.headers?.['retry-after'];
            const seconds = retryAfter ? parseInt(retryAfter, 10) : null;

            const fullMessage = seconds && seconds > 0
                ? `${message} Try again in ${seconds}s.`
                : message;

            toastWarning('Slow down', fullMessage, { duration: 4000 });
        }
        return Promise.reject(error);
    }
);

// ✅ Register the service worker on app load (not just on push-enable).
//    This enables offline fallback for ALL visitors, not just push subscribers.
if ('serviceWorker' in navigator && typeof window !== 'undefined') {
    window.addEventListener('load', () => {
        navigator.serviceWorker.register('/sw.js').catch((err) => {
            console.warn('[SW] Registration failed:', err);
        });
    });
}

createInertiaApp({
    title: (title) => `Omniscient - ${title}`,
    resolve: (name) => resolvePageComponent(
        `./Pages/${name}.vue`,
        import.meta.glob('./Pages/**/*.vue')
    ),
    setup({ el, App, props, plugin }) {
        // Use window.Ziggy if available, or fallback to props.ziggy
        const ziggy = window.Ziggy || props.ziggy || { routes: {} };

        const app = createApp({ render: () => h(App, props) })
            .use(plugin)
            .use(ZiggyVue, ziggy);

        // Register Toast Container
        app.component('ToastContainer', ToastContainer);

        return app.mount(el);
    },
    progress: {
        color: '#0284c7',
    },
});