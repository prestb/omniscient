<!-- resources/js/Components/Pagination.vue -->
<template>
    <div v-if="links.length > 3" class="flex flex-wrap items-center justify-between">
        <!-- Mobile: simple prev/next style row -->
        <div class="flex-1 flex justify-between sm:hidden">
            <Link
                v-for="(link, index) in links"
                :key="index"
                :href="link.url || '#'"
                class="relative inline-flex items-center px-4 py-2 text-sm font-medium rounded-md"
                :class="[
                    link.active
                        ? 'bg-primary-600 text-white'
                        : 'bg-white dark:bg-gray-800 text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700',
                    !link.url ? 'opacity-50 cursor-not-allowed' : ''
                ]"
                v-html="link.label"
                :preserve-scroll="true"
            />
        </div>

        <!-- Desktop -->
        <div class="hidden sm:flex-1 sm:flex sm:items-center sm:justify-between">
            <div>
                <p class="text-sm text-gray-700 dark:text-gray-300">
                    Showing
                    <span class="font-medium">{{ from }}</span>
                    to
                    <span class="font-medium">{{ to }}</span>
                    of
                    <span class="font-medium">{{ total }}</span>
                    results
                </p>
            </div>

            <div>
                <nav class="relative z-0 inline-flex rounded-md shadow-sm -space-x-px" aria-label="Pagination">
                    <Link
                        v-for="(link, index) in links"
                        :key="index"
                        :href="link.url || '#'"
                        class="relative inline-flex items-center px-4 py-2 text-sm font-medium"
                        :class="[
                            link.active
                                ? 'z-10 bg-primary-600 border-primary-600 text-white'
                                : 'bg-white dark:bg-gray-800 border-gray-300 dark:border-gray-600 text-gray-500 dark:text-gray-400 hover:bg-gray-50 dark:hover:bg-gray-700',
                            index === 0 ? 'rounded-l-md' : '',
                            index === links.length - 1 ? 'rounded-r-md' : '',
                            !link.url ? 'opacity-50 cursor-not-allowed' : '',
                            'border'
                        ]"
                        v-html="link.label"
                        :preserve-scroll="true"
                    />
                </nav>
            </div>
        </div>
    </div>
</template>

<script setup>
import { Link } from '@inertiajs/vue3';

defineProps({
    links: {
        type: Array,
        required: true,
    },
    from: {
        type: Number,
        default: 0,
    },
    to: {
        type: Number,
        default: 0,
    },
    total: {
        type: Number,
        default: 0,
    },
});
</script>