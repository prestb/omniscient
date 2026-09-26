<!-- resources/js/Components/Owner/LocationPicker.vue -->
<template>
    <div>
        <div class="flex items-center justify-between mb-2">
            <label class="block text-xs font-medium text-gray-600 dark:text-gray-400">
                Coordinates
                <span class="text-gray-400 normal-case text-[10px]">(optional)</span>
            </label>
            <button v-if="hasCoordinates"
                    type="button"
                    @click="clearCoordinates"
                    class="text-[11px] font-semibold text-red-500 hover:text-red-700 dark:hover:text-red-300 transition-colors">
                Clear coordinates
            </button>
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
            <!-- Latitude -->
            <div>
                <label class="block text-[10px] font-bold uppercase tracking-widest text-gray-400 dark:text-gray-500 mb-1.5">
                    Latitude
                </label>
                <input type="number"
                       :value="latitude"
                       @input="onLatInput"
                       step="any"
                       min="-90"
                       max="90"
                       placeholder="e.g., 4.0511000"
                       class="w-full px-3 py-2 border border-gray-200 dark:border-gray-600 rounded-xl bg-white dark:bg-gray-900 text-gray-900 dark:text-white text-xs font-mono placeholder-gray-400 dark:placeholder-gray-500 focus:outline-none focus:ring-2 focus:ring-primary-500 focus:border-transparent transition-colors" />
            </div>

            <!-- Longitude -->
            <div>
                <label class="block text-[10px] font-bold uppercase tracking-widest text-gray-400 dark:text-gray-500 mb-1.5">
                    Longitude
                </label>
                <input type="number"
                       :value="longitude"
                       @input="onLngInput"
                       step="any"
                       min="-180"
                       max="180"
                       placeholder="e.g., 9.7043000"
                       class="w-full px-3 py-2 border border-gray-200 dark:border-gray-600 rounded-xl bg-white dark:bg-gray-900 text-gray-900 dark:text-white text-xs font-mono placeholder-gray-400 dark:placeholder-gray-500 focus:outline-none focus:ring-2 focus:ring-primary-500 focus:border-transparent transition-colors" />
            </div>
        </div>

        <!-- Helper tip -->
        <p class="mt-2 text-[11px] text-gray-400 dark:text-gray-500 leading-relaxed">
            Tip: In Google Maps, long-press the exact spot and copy the two numbers that appear.
        </p>

        <!-- Partial-coordinates warning -->
        <p v-if="hasPartialCoordinates" class="mt-1.5 text-[11px] text-amber-600 dark:text-amber-400 leading-relaxed">
            ⚠︎ Enter both latitude and longitude to save a location.
        </p>
    </div>
</template>

<script setup>
import { computed } from 'vue';

const props = defineProps({
    latitude: {
        type: [Number, String, null],
        default: null,
    },
    longitude: {
        type: [Number, String, null],
        default: null,
    },
    // Kept in the prop contract for backward-compat with callers.
    // No longer used by this component now that the map is gone.
    address: { type: String, default: '' },
    city: { type: String, default: '' },
    region: { type: String, default: '' },
    country: { type: String, default: '' },
});

const emit = defineEmits(['update:latitude', 'update:longitude']);

const isFilled = (v) => v !== null && v !== '' && v !== undefined;

const hasCoordinates = computed(() => isFilled(props.latitude) && isFilled(props.longitude));

const hasPartialCoordinates = computed(() => {
    const lat = isFilled(props.latitude);
    const lng = isFilled(props.longitude);
    return (lat && !lng) || (!lat && lng);
});

const roundCoord = (n) => Math.round(n * 1e7) / 1e7;

const clearCoordinates = () => {
    emit('update:latitude', null);
    emit('update:longitude', null);
};

const onLatInput = (e) => {
    const raw = e.target.value;
    if (raw === '' || raw === null || raw === undefined) {
        emit('update:latitude', null);
        return;
    }
    const num = parseFloat(raw);
    if (isNaN(num) || num < -90 || num > 90) return;
    emit('update:latitude', roundCoord(num));
};

const onLngInput = (e) => {
    const raw = e.target.value;
    if (raw === '' || raw === null || raw === undefined) {
        emit('update:longitude', null);
        return;
    }
    const num = parseFloat(raw);
    if (isNaN(num) || num < -180 || num > 180) return;
    emit('update:longitude', roundCoord(num));
};
</script>