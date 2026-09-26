<!-- resources/js/Components/Public/StarRating.vue -->
<template>
    <div class="flex items-center gap-0.5">
        <span 
            v-for="star in 5" 
            :key="star" 
            class="text-sm"
            :class="star <= ratingNumber
                ? 'text-amber-400'
                : 'text-gray-300 dark:text-gray-600'"
        >
            ⭐
        </span>
        <span v-if="showRating" class="text-xs text-gray-400 dark:text-gray-500 ml-1">({{ ratingNumber }})</span>
    </div>
</template>

<script setup>
import { computed } from 'vue';

const props = defineProps({
    rating: {
        type: [Number, String],
        required: true
    },
    showRating: {
        type: Boolean,
        default: false
    }
});

const ratingNumber = computed(() => {
    return Number(props.rating) || 0;
});
</script>