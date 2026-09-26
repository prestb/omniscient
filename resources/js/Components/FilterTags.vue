<template>
    <div v-if="hasActiveFilters" class="flex flex-wrap items-center gap-2 mb-4">
        <span class="text-sm text-gray-500 font-medium mr-1">Filters:</span>
        
        <button
            v-for="filter in activeFilters"
            :key="filter.key"
            @click="removeFilter(filter.key)"
            class="inline-flex items-center gap-1 px-3 py-1 bg-primary-50 text-primary-700 rounded-full text-sm hover:bg-primary-100 transition-colors group"
        >
            <span class="flex items-center gap-1">
                <span v-if="filter.icon" class="text-xs">{{ filter.icon }}</span>
                {{ filter.label }}
            </span>
            <svg class="w-3.5 h-3.5 group-hover:scale-110 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
            </svg>
        </button>

        <button
            @click="clearAllFilters"
            class="text-xs text-gray-400 hover:text-gray-600 transition-colors"
        >
            Clear all
        </button>
    </div>
</template>

<script setup>
import { computed } from 'vue';

const props = defineProps({
    filters: {
        type: Object,
        required: true,
        default: () => ({})
    },
    filterLabels: {
        type: Object,
        required: true,
        default: () => ({})
    },
    filterIcons: {
        type: Object,
        default: () => ({})
    }
});

const emit = defineEmits(['remove', 'clear']);

const activeFilters = computed(() => {
    const active = [];
    for (const [key, value] of Object.entries(props.filters)) {
        if (value !== null && value !== undefined && value !== '' && value !== false) {
            let label = props.filterLabels[key] || key;
            // If value is not boolean, append it
            if (typeof value !== 'boolean' && value !== true) {
                if (typeof value === 'string' && props.filterLabels[value]) {
                    label = props.filterLabels[value];
                } else {
                    label = `${label}: ${value}`;
                }
            }
            active.push({
                key,
                label,
                icon: props.filterIcons[key] || null,
                value
            });
        }
    }
    return active;
});

const hasActiveFilters = computed(() => activeFilters.value.length > 0);

const removeFilter = (key) => {
    emit('remove', key);
};

const clearAllFilters = () => {
    emit('clear');
};
</script>