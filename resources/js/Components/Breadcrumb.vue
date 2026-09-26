<!-- resources/js/Components/Breadcrumb.vue -->
<template>
    <nav class="flex items-center text-sm mb-6" aria-label="Breadcrumb">
        <ol class="flex items-center flex-wrap gap-1">
            <li v-for="(item, index) in items" :key="index" class="flex items-center">
                <template v-if="index > 0">
                    <svg class="w-3.5 h-3.5 mx-1.5 text-gray-300 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                    </svg>
                </template>
                <a v-if="item.href"
                   :href="item.href"
                   class="transition-colors hover:text-primary-600"
                   :class="index === items.length - 1
                       ? 'text-gray-900 font-semibold'
                       : 'text-gray-500 font-medium'">
                    {{ item.label }}
                </a>
                <span v-else
                      class="text-gray-900 font-semibold">
                    {{ item.label }}
                </span>
            </li>
        </ol>
    </nav>
</template>

<script setup>
defineProps({
    items: {
        type: Array,
        required: true
    }
});
</script>