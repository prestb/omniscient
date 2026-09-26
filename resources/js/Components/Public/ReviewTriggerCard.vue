<!-- resources/js/Components/Public/ReviewTriggerCard.vue -->
<template>
    <button type="button"
            @click="$emit('open')"
            class="w-full bg-white dark:bg-gray-800 rounded-2xl border border-gray-100 dark:border-gray-700 shadow-sm overflow-hidden text-left hover:border-amber-200 dark:hover:border-amber-800 hover:shadow-md transition-all group">
        <div class="px-5 py-4 flex items-center gap-3">
            <div class="w-10 h-10 rounded-xl bg-gradient-to-br from-amber-400 to-yellow-500 flex items-center justify-center shadow-lg shadow-amber-500/25 flex-shrink-0 group-hover:scale-105 transition-transform">
                <svg class="w-5 h-5 text-white" fill="currentColor" viewBox="0 0 20 20">
                    <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z" />
                </svg>
            </div>
            <div class="min-w-0 flex-1">
                <h3 class="text-sm font-bold text-gray-900 dark:text-white tracking-tight">
                    Write a review
                </h3>
                <p class="text-[11px] text-gray-500 dark:text-gray-400 truncate">
                    Share your experience
                </p>
            </div>
            <svg class="w-4 h-4 text-gray-400 group-hover:text-amber-500 group-hover:translate-x-0.5 transition-all flex-shrink-0"
                 fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M9 5l7 7-7 7" />
            </svg>
        </div>
    </button>
</template>

<script setup>
defineEmits(['open']);
</script>