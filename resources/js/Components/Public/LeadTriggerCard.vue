<!-- resources/js/Components/Public/LeadTriggerCard.vue -->
<template>
    <button type="button"
            @click="$emit('open')"
            class="w-full bg-white dark:bg-gray-800 rounded-2xl border border-gray-100 dark:border-gray-700 shadow-sm overflow-hidden text-left hover:border-primary-200 dark:hover:border-primary-800 hover:shadow-md transition-all group">
        <div class="px-5 py-4 flex items-center gap-3">
            <div class="w-10 h-10 rounded-xl bg-gradient-to-br from-primary-500 to-purple-600 flex items-center justify-center shadow-lg shadow-primary-500/25 flex-shrink-0 group-hover:scale-105 transition-transform">
                <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                          d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />
                </svg>
            </div>
            <div class="min-w-0 flex-1">
                <h3 class="text-sm font-bold text-gray-900 dark:text-white tracking-tight">
                    Send a message
                </h3>
                <p class="text-[11px] text-gray-500 dark:text-gray-400 truncate">
                    We'll get back to you soon
                </p>
            </div>
            <svg class="w-4 h-4 text-gray-400 group-hover:text-primary-500 group-hover:translate-x-0.5 transition-all flex-shrink-0"
                 fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M9 5l7 7-7 7" />
            </svg>
        </div>
    </button>
</template>

<script setup>
defineEmits(['open']);
</script>