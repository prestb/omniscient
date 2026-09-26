<!-- resources/js/Pages/Public/Locations.vue -->
<template>
    <PublicLayout>
        <div class="bg-white dark:bg-gray-800 border-b border-gray-200 dark:border-gray-700">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-6">
                <h1 class="text-2xl font-bold text-gray-900 dark:text-white">Locations</h1>
                <p class="text-sm text-gray-500 dark:text-gray-400">Browse businesses by location</p>
            </div>
        </div>

        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                <div v-for="country in countries" :key="country.id"
                     class="bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700 overflow-hidden">
                    <div class="p-6">
                        <h3 class="text-lg font-semibold text-gray-900 dark:text-white">{{ country.name }}</h3>
                        <p class="text-sm text-gray-500 dark:text-gray-400">{{ country.regions?.length || 0 }} regions</p>
                    </div>
                    <div v-if="country.regions && country.regions.length > 0" class="border-t border-gray-100 dark:border-gray-700">
                        <div v-for="region in country.regions.slice(0, 5)" :key="region.id"
                             class="px-6 py-3 hover:bg-gray-50 dark:hover:bg-gray-700/50 transition-colors">
                            <div class="flex items-center justify-between">
                                <a :href="`/directory?region_id=${region.id}`" class="text-sm text-gray-700 dark:text-gray-300 hover:text-primary-600 dark:hover:text-primary-400">
                                    {{ region.name }}
                                </a>
                                <span class="text-xs text-gray-400 dark:text-gray-500">{{ region.cities?.length || 0 }} cities</span>
                            </div>
                            <div v-if="region.cities && region.cities.length > 0" class="flex flex-wrap gap-1 mt-1">
                                <span v-for="city in region.cities.slice(0, 3)" :key="city.id"
                                      class="text-xs text-gray-400 dark:text-gray-500">
                                    <a :href="`/directory?city_id=${city.id}`" class="hover:text-primary-600 dark:hover:text-primary-400">
                                        {{ city.name }}
                                    </a>
                                </span>
                                <span v-if="region.cities.length > 3" class="text-xs text-gray-400 dark:text-gray-500">
                                    +{{ region.cities.length - 3 }} more
                                </span>
                            </div>
                        </div>
                        <div v-if="country.regions.length > 5" class="px-6 py-3 bg-gray-50 dark:bg-gray-900/50 text-center">
                            <span class="text-xs text-gray-400 dark:text-gray-500">+{{ country.regions.length - 5 }} more regions</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </PublicLayout>
</template>

<script setup>
import PublicLayout from '@/Layouts/PublicLayout.vue';

defineProps({
    countries: Array,
});
</script>