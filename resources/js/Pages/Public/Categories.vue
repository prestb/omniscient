<!-- resources/js/Pages/Public/Categories.vue -->
<template>
    <PublicLayout>
        <template #header>
            <div class="bg-white dark:bg-gray-800 border-b border-gray-200 dark:border-gray-700">
                <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-6">
                    <h1 class="text-2xl font-bold text-gray-900 dark:text-white">Categories</h1>
                    <p class="text-sm text-gray-500 dark:text-gray-400">Browse businesses by category</p>
                </div>
            </div>
        </template>

        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6">
                <div v-for="category in categories" :key="category.id"
                    class="group bg-white dark:bg-gray-800 rounded-2xl shadow-sm border border-gray-100 dark:border-gray-700 overflow-hidden hover:shadow-md hover:-translate-y-0.5 transition-all duration-300">
                    <a :href="`/directory?category=${category.id}`" class="block p-6">
                        <div class="flex items-center gap-4">
                            <div
                                class="w-14 h-14 rounded-2xl bg-gradient-to-br from-primary-50 to-primary-100 dark:from-primary-900/30 dark:to-primary-900/20 flex items-center justify-center flex-shrink-0 text-primary-600 dark:text-primary-400 shadow-sm group-hover:shadow-md transition-shadow">
                                <CategoryIcon :icon="category.icon" size="xl" />
                            </div>
                            <div class="flex-1">
                                <h3 class="text-lg font-semibold text-gray-900 dark:text-white">{{ category.name }}</h3>
                                <p v-if="category.description" class="text-sm text-gray-500 dark:text-gray-400">{{ category.description }}
                                </p>
                            </div>
                            <svg class="w-5 h-5 text-gray-400 dark:text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M9 5l7 7-7 7" />
                            </svg>
                        </div>
                        <div v-if="category.children && category.children.length > 0" class="mt-3 flex flex-wrap gap-1">
                            <span v-for="child in category.children.slice(0, 4)" :key="child.id"
                                class="text-xs bg-gray-100 dark:bg-gray-700 text-gray-600 dark:text-gray-300 px-2 py-0.5 rounded-full">
                                {{ child.name }}
                            </span>
                            <span v-if="category.children.length > 4" class="text-xs text-gray-400 dark:text-gray-500">
                                +{{ category.children.length - 4 }} more
                            </span>
                        </div>
                    </a>
                </div>
            </div>
        </div>
    </PublicLayout>
</template>

<script setup>
    import PublicLayout from '@/Layouts/PublicLayout.vue';
    import CategoryIcon from '@/Components/CategoryIcon.vue';
    defineProps({
        categories: Array,
    });
</script>