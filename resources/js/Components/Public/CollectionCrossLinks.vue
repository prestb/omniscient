<!-- resources/js/Components/Public/CollectionCrossLinks.vue -->
<template>
    <div class="space-y-8">
        <!-- Other categories in this city -->
        <div v-if="otherCategories && otherCategories.length > 0">
            <h2 class="text-sm font-bold text-gray-900 dark:text-white uppercase tracking-wide mb-3">
                Other categories in {{ cityName }}
            </h2>
            <div class="flex flex-wrap gap-2">
                <Link v-for="item in otherCategories" :key="item.category.id"
                    :href="`/${item.slug}`"
                    class="inline-flex items-center gap-2 px-3 py-1.5 rounded-full border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 text-gray-700 dark:text-gray-300 hover:border-primary-400 hover:text-primary-600 dark:hover:text-primary-400 transition-all text-sm font-medium group">
                    <span>{{ item.category.name }} in {{ cityName }}</span>
                    <span class="text-[10px] font-bold px-1.5 py-0.5 rounded-full bg-gray-100 dark:bg-gray-700 text-gray-500 dark:text-gray-400 group-hover:bg-primary-100 dark:group-hover:bg-primary-900/40 group-hover:text-primary-600">
                        {{ item.count }}
                    </span>
                </Link>
            </div>
        </div>

        <!-- Other cities with this category -->
        <div v-if="otherCities && otherCities.length > 0">
            <h2 class="text-sm font-bold text-gray-900 dark:text-white uppercase tracking-wide mb-3">
                {{ categoryName }} in other cities
            </h2>
            <div class="flex flex-wrap gap-2">
                <Link v-for="item in otherCities" :key="item.city.id"
                    :href="`/${item.slug}`"
                    class="inline-flex items-center gap-2 px-3 py-1.5 rounded-full border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 text-gray-700 dark:text-gray-300 hover:border-primary-400 hover:text-primary-600 dark:hover:text-primary-400 transition-all text-sm font-medium group">
                    <span>{{ categoryName }} in {{ item.city.name }}</span>
                    <span class="text-[10px] font-bold px-1.5 py-0.5 rounded-full bg-gray-100 dark:bg-gray-700 text-gray-500 dark:text-gray-400 group-hover:bg-primary-100 dark:group-hover:bg-primary-900/40 group-hover:text-primary-600">
                        {{ item.count }}
                    </span>
                </Link>
            </div>
        </div>
    </div>
</template>

<script setup>
import { Link } from '@inertiajs/vue3';

defineProps({
    otherCategories: { type: Array, default: () => [] },
    otherCities: { type: Array, default: () => [] },
    cityName: { type: String, required: true },
    categoryName: { type: String, required: true },
});
</script>