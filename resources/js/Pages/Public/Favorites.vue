<template>
    <PublicLayout>
        <Head title="My Favorites" />

        <div class="bg-gray-50 dark:bg-gray-900 min-h-screen">
            <!-- Header -->
            <div class="bg-white dark:bg-gray-800 border-b border-gray-200 dark:border-gray-700">
                <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
                    <div class="flex items-center gap-4">
                        <div class="w-14 h-14 rounded-2xl bg-gradient-to-br from-red-500 to-pink-600 flex items-center justify-center shadow-lg shadow-red-500/30">
                            <svg class="w-7 h-7 text-white" fill="currentColor" viewBox="0 0 24 24">
                                <path d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z" />
                            </svg>
                        </div>
                        <div>
                            <h1 class="text-3xl font-black text-gray-900 dark:text-white">My Favorites</h1>
                            <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">
                                {{ totalCount }} {{ totalCount === 1 ? 'saved business' : 'saved businesses' }}
                            </p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Content -->
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
                <!-- Empty State -->
                <div v-if="businesses.data.length === 0" class="bg-white dark:bg-gray-800 rounded-3xl shadow-sm border border-gray-100 dark:border-gray-700 p-12 text-center">
                    <div class="w-24 h-24 rounded-full bg-gray-100 dark:bg-gray-700 flex items-center justify-center mx-auto mb-4">
                        <svg class="w-12 h-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z" />
                        </svg>
                    </div>
                    <h3 class="text-xl font-bold text-gray-900 dark:text-white mb-2">
                        No favorites yet
                    </h3>
                    <p class="text-gray-500 dark:text-gray-400 mb-6 max-w-sm mx-auto">
                        Start saving businesses you love by clicking the heart icon on any business card.
                    </p>
                    <a href="/directory" 
                       class="inline-flex items-center gap-2 px-6 py-3 bg-primary-600 text-white rounded-xl font-semibold hover:bg-primary-700 transition-colors shadow-lg shadow-primary-500/30">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                        </svg>
                        Browse Directory
                    </a>
                </div>

                <!-- Grid -->
                <div v-else class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6">
                    <ListingCard 
                        v-for="listing in businesses.data" 
                        :key="listing.id" 
                        :listing="listing" 
                    />
                </div>

                <!-- Pagination -->
                <div v-if="businesses.links && businesses.links.length > 3" class="mt-8 flex justify-center">
                    <div class="flex flex-wrap items-center gap-1">
                        <template v-for="(link, index) in businesses.links" :key="index">
                            <span v-if="!link.url" 
                                  class="px-3 py-2 text-sm text-gray-400 cursor-not-allowed" 
                                  v-html="link.label"></span>
                            <a v-else :href="link.url"
                               class="px-3 py-2 text-sm rounded-lg transition-colors"
                               :class="link.active 
                                   ? 'bg-primary-600 text-white font-semibold' 
                                   : 'text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700'"
                               v-html="link.label"></a>
                        </template>
                    </div>
                </div>
            </div>
        </div>
    </PublicLayout>
</template>

<script setup>
import { Head } from '@inertiajs/vue3';
import PublicLayout from '@/Layouts/PublicLayout.vue';
import ListingCard from '@/Components/Public/ListingCard.vue';

const props = defineProps({
    businesses: Object,
    totalCount: Number,
});
</script>