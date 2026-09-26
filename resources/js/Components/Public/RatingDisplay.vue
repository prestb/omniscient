<!-- resources/js/Components/Public/RatingDisplay.vue -->
<template>
    <div class="flex items-center gap-2">
        
        <!-- Badge -->
        <RatingBadge :rating="ratingNumber" />
    </div>
</template>

<script setup>
import { computed } from 'vue';
import RatingBadge from '@/Components/Public/RatingBadge.vue';

const props = defineProps({
    rating: {
        type: [Number, String],
        required: true
    }
});

const ratingNumber = computed(() => {
    return Number(props.rating) || 0;
});
</script>