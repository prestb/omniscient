<!-- resources/js/Components/Public/ExpandableText.vue -->
<template>
    <div>
        <p :class="[textClass, { 'expandable-text-clamped': !expanded }]"
           :style="!expanded ? `-webkit-line-clamp: ${lines};` : ''">
            {{ text }}
        </p>

        <button v-if="isOverflowing" type="button" @click="expanded = !expanded"
            class="mt-1.5 text-xs font-semibold text-primary-600 dark:text-primary-400 hover:text-primary-800 dark:hover:text-primary-300 transition-colors inline-flex items-center gap-1">
            {{ expanded ? 'See less' : 'See more' }}
            <svg class="w-3 h-3 transition-transform" :class="expanded ? 'rotate-180' : ''"
                fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
            </svg>
        </button>
    </div>
</template>

<script setup>
import { ref, computed } from 'vue';

const props = defineProps({
    text: { type: String, default: '' },
    lines: { type: Number, default: 4 },
    textClass: { type: String, default: '' },
});

const expanded = ref(false);

// Heuristic: consider it overflowing if longer than roughly lines × 60 chars
const isOverflowing = computed(() => {
    return props.text && props.text.length > props.lines * 60;
});
</script>

<style scoped>
.expandable-text-clamped {
    display: -webkit-box;
    -webkit-box-orient: vertical;
    overflow: hidden;
}
</style>