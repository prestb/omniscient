// resources/js/composables/useGeolocation.js
//
// Small wrapper around the browser Geolocation API.
// Single-shot — requests once, caches the result for the session.

import { ref } from 'vue';

export function useGeolocation() {
    const coords = ref(null);      // { lat, lng } or null
    const loading = ref(false);
    const error = ref(null);       // 'denied' | 'unavailable' | 'timeout' | null
    let cached = null;

    const locate = () => {
        // Return cached result if we already have one
        if (cached) {
            coords.value = cached;
            return Promise.resolve(cached);
        }

        if (typeof navigator === 'undefined' || !navigator.geolocation) {
            error.value = 'unavailable';
            return Promise.reject(new Error('unavailable'));
        }

        loading.value = true;
        error.value = null;

        return new Promise((resolve, reject) => {
            navigator.geolocation.getCurrentPosition(
                (pos) => {
                    const c = {
                        lat: pos.coords.latitude,
                        lng: pos.coords.longitude,
                    };
                    cached = c;
                    coords.value = c;
                    loading.value = false;
                    resolve(c);
                },
                (err) => {
                    loading.value = false;
                    if (err.code === 1) error.value = 'denied';
                    else if (err.code === 2) error.value = 'unavailable';
                    else if (err.code === 3) error.value = 'timeout';
                    else error.value = 'unknown';
                    reject(err);
                },
                {
                    enableHighAccuracy: false,
                    timeout: 8000,
                    maximumAge: 300000, // 5 min — good enough for filtering
                }
            );
        });
    };

    const clearError = () => { error.value = null; };

    return { coords, loading, error, locate, clearError };
}