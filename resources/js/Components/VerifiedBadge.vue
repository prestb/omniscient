<!-- resources/js/Components/VerifiedBadge.vue -->
<template>
    <span class="inline-flex items-center gap-1.5 rounded-full font-bold transition-all"
          :class="badgeClasses">
        <!-- Locked -->
        <template v-if="!verified">
            <svg class="flex-shrink-0" :class="iconSize" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" />
            </svg>
            <span v-if="showLabel" class="uppercase tracking-wide">{{ lockedLabel }}</span>
        </template>

        <!-- Verified -->
        <template v-else>
            <svg class="flex-shrink-0" :class="iconSize" viewBox="0 0 24 24" fill="currentColor">
                <path fill-rule="evenodd" d="M8.603 3.799A4.49 4.49 0 0112 2.25c1.357 0 2.573.6 3.397 1.549a4.49 4.49 0 013.498 1.307 4.491 4.491 0 011.307 3.497A4.49 4.49 0 0121.75 12a4.49 4.49 0 01-1.549 3.397 4.491 4.491 0 01-1.307 3.497 4.491 4.491 0 01-3.497 1.307A4.49 4.49 0 0112 21.75a4.49 4.49 0 01-3.397-1.549 4.49 4.49 0 01-3.498-1.306 4.491 4.491 0 01-1.307-3.498A4.49 4.49 0 012.25 12c0-1.357.6-2.573 1.549-3.397a4.49 4.49 0 011.307-3.497 4.49 4.49 0 013.497-1.307zm7.007 6.387a.75.75 0 10-1.22-.872l-3.236 4.53L9.53 12.22a.75.75 0 00-1.06 1.06l2.25 2.25a.75.75 0 001.14-.094l3.75-5.25z" clip-rule="evenodd" />
            </svg>
            <span v-if="showLabel" class="uppercase tracking-wide">{{ verifiedLabel }}</span>
        </template>
    </span>
</template>

<script setup>
import { computed } from 'vue';

const props = defineProps({
    verified: {
        type: Boolean,
        default: false,
    },
    size: {
        type: String,
        default: 'md',
        validator: (value) => ['sm', 'md', 'lg'].includes(value),
    },
    showLabel: {
        type: Boolean,
        default: true,
    },
    variant: {
        type: String,
        default: 'auto',
        validator: (value) => ['auto', 'light', 'solid'].includes(value),
    },
    verifiedLabel: {
        type: String,
        default: 'Verified',
    },
    lockedLabel: {
        type: String,
        default: 'Not Verified',
    },
});

const iconSize = computed(() => {
    const sizes = {
        sm: 'w-3 h-3',
        md: 'w-4 h-4',
        lg: 'w-5 h-5',
    };
    return sizes[props.size];
});

const badgeClasses = computed(() => {
    const basePadding = {
        sm: 'px-2 py-0.5 text-[10px]',
        md: 'px-2.5 py-1 text-[11px]',
        lg: 'px-3 py-1.5 text-xs',
    }[props.size];

    if (props.verified) {
        if (props.variant === 'solid') {
            return `${basePadding} bg-blue-600 text-white shadow-md shadow-blue-500/30`;
        }
        return `${basePadding} bg-blue-100 text-blue-700 dark:bg-blue-900/30 dark:text-blue-400 border border-blue-200 dark:border-blue-800`;
    }

    if (props.variant === 'solid') {
        return `${basePadding} bg-amber-500 text-white shadow-md shadow-amber-500/30`;
    }
    return `${basePadding} bg-amber-50 text-amber-700 dark:bg-amber-900/30 dark:text-amber-400 border border-amber-200 dark:border-amber-800`;
});
</script>