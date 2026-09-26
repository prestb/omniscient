<template>
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-6">
        <BusinessCardSkeleton v-for="n in count" :key="n" />
    </div>
</template>

<script setup>
import BusinessCardSkeleton from './BusinessCardSkeleton.vue';

defineProps({
    count: {
        type: Number,
        default: 8
    }
});
</script>