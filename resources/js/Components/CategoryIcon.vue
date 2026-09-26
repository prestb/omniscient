<!-- resources/js/Components/CategoryIcon.vue -->
<template>
    <svg
        :class="sizeClass"
        viewBox="0 0 24 24"
        fill="none"
        stroke="currentColor"
        :stroke-width="strokeWidth"
        stroke-linecap="round"
        stroke-linejoin="round"
        aria-hidden="true"
    >
        <path :d="iconPath" />
    </svg>
</template>

<script setup>
import { computed } from 'vue';
import { getCategoryIcon } from '@/data/categoryIcons';

const props = defineProps({
    icon: {
        type: String,
        default: null,
    },
    size: {
        type: String,
        default: 'md',
        validator: (v) => ['xs', 'sm', 'md', 'lg', 'xl'].includes(v),
    },
    strokeWidth: {
        type: [Number, String],
        default: 1.5,
    },
});

const iconPath = computed(() => {
    const icon = getCategoryIcon(props.icon);
    return icon.path;
});

const sizeClass = computed(() => {
    const sizes = {
        xs: 'w-3 h-3',
        sm: 'w-4 h-4',
        md: 'w-5 h-5',
        lg: 'w-6 h-6',
        xl: 'w-8 h-8',
    };
    return sizes[props.size] || sizes.md;
});
</script>