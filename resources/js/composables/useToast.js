// composables/useToast.js
import { ref, reactive } from 'vue';

const toasts = ref([]);
let idCounter = 0;

// ✅ Central duration map — the source of truth.
//    Callers can no longer override these per-toast (except for very short ones).
const TOAST_DURATIONS = {
    success: 3000,
    error: 5000,
    warning: 4000,
    info: 3000,
};

// Per-call overrides are only honored when BELOW this threshold
// (e.g., a 1200ms "Copied!" toast is a legitimate intentional override).
const DURATION_MIN_OVERRIDE = 1000;

// Track which flash messages have already been shown this page-load
const shownFlashes = new Set();

export const markFlashShown = (type, message) => {
    shownFlashes.add(`${type}:${message}`);
};

export const wasFlashShown = (type, message) => {
    return shownFlashes.has(`${type}:${message}`);
};

export const useToast = () => {
    const showToast = (type, title, message, options = {}) => {
        const id = ++idCounter;

        // ✅ Standard duration comes from the type map.
        let duration = TOAST_DURATIONS[type] ?? TOAST_DURATIONS.info;

        // Honor explicit opt-outs (0 = manual dismiss) and short overrides (<1000ms)
        if (options.duration === 0) {
            duration = 0;
        } else if (typeof options.duration === 'number' && options.duration < DURATION_MIN_OVERRIDE) {
            duration = options.duration;
        }

        const toast = reactive({
            id,
            type,
            title,
            message,
            duration,
            progress: 100,
            actions: options.actions || [],
            dismissible: options.dismissible !== false,
            createdAt: Date.now(),
            isPaused: false,
            timer: null,
            progressInterval: null,
        });

        toasts.value.unshift(toast);

        if (duration > 0) {
            startAutoDismiss(toast);
        }

        return toast;
    };

    const startAutoDismiss = (toast) => {
        const interval = 50;
        const steps = toast.duration / interval;
        let currentStep = 0;

        toast.progressInterval = setInterval(() => {
            if (!toast.isPaused) {
                currentStep++;
                toast.progress = Math.max(0, 100 - (currentStep / steps) * 100);
            }
        }, interval);

        toast.timer = setTimeout(() => {
            dismissToast(toast.id);
        }, toast.duration);
    };

    const dismissToast = (id) => {
        const index = toasts.value.findIndex(t => t.id === id);
        if (index !== -1) {
            const toast = toasts.value[index];
            if (toast.timer) clearTimeout(toast.timer);
            if (toast.progressInterval) clearInterval(toast.progressInterval);
            toasts.value.splice(index, 1);
        }
    };

    const dismissAll = () => {
        toasts.value.forEach(toast => {
            if (toast.timer) clearTimeout(toast.timer);
            if (toast.progressInterval) clearInterval(toast.progressInterval);
        });
        toasts.value = [];
    };

    const pauseToast = (id) => {
        const toast = toasts.value.find(t => t.id === id);
        if (toast) {
            toast.isPaused = true;
            if (toast.timer) clearTimeout(toast.timer);
            if (toast.progressInterval) clearInterval(toast.progressInterval);
        }
    };

    const resumeToast = (id) => {
        const toast = toasts.value.find(t => t.id === id);
        if (toast && toast.isPaused) {
            toast.isPaused = false;
            startAutoDismiss(toast);
        }
    };

    const success = (title, message, options = {}) => showToast('success', title, message, options);
    const error = (title, message, options = {}) => showToast('error', title, message, options);
    const warning = (title, message, options = {}) => showToast('warning', title, message, options);
    const info = (title, message, options = {}) => showToast('info', title, message, options);

    return {
        toasts,
        showToast,
        dismissToast,
        dismissAll,
        pauseToast,
        resumeToast,
        success,
        error,
        warning,
        info,
    };
};