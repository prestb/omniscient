<!-- resources/js/Pages/Public/Explore.vue -->
<template>
    <PublicLayout>
        <Head>
            <title>{{ seo.title }}</title>
            <meta name="description" :content="seo.description" />
            <link rel="canonical" :href="seo.canonical" />
            <meta property="og:title" :content="seo.title" />
            <meta property="og:description" :content="seo.description" />
            <meta property="og:type" content="website" />
            <meta property="og:url" :content="seo.canonical" />
        </Head>

        <!-- Hero -->
        <section class="bg-gradient-to-br from-primary-700 via-primary-600 to-primary-800 text-white">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-10 sm:py-14">
                <div class="flex items-center gap-2 mb-3">
                    <svg class="w-5 h-5 text-primary-200" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M9 20l-5.447-2.724A1 1 0 013 16.382V5.618a1 1 0 011.447-.894L9 7m0 13l6-3m-6 3V7m6 10l4.553 2.276A1 1 0 0021 18.382V7.618a1 1 0 00-.553-.894L15 4m0 13V4m0 0L9 7" />
                    </svg>
                    <span class="text-xs uppercase tracking-widest text-primary-100 font-semibold">Explore</span>
                </div>
                <h1 class="text-2xl sm:text-4xl font-bold tracking-tight">{{ hero.title }}</h1>
                <p class="text-sm sm:text-base text-primary-100 mt-2">{{ hero.subtitle }}</p>
            </div>
        </section>

        <!-- Rows -->
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8 sm:py-12 space-y-8 sm:space-y-10">
            <ExploreCarousel v-for="row in rows" :key="`${row.category_id}-${row.city_id ?? 'all'}`" :row="row" />

            <!-- Empty state -->
            <div v-if="rows.length === 0"
                class="text-center py-16 bg-white dark:bg-gray-800 rounded-2xl border border-gray-100 dark:border-gray-700">
                <div class="w-16 h-16 bg-gray-100 dark:bg-gray-700 rounded-full flex items-center justify-center mx-auto mb-4">
                    <svg class="w-8 h-8 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                    </svg>
                </div>
                <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-2">Not enough data yet</h3>
                <p class="text-gray-500 dark:text-gray-400 text-sm">Check back soon as more businesses join.</p>
                <Link href="/directory"
                    class="inline-flex items-center gap-2 px-5 py-2.5 mt-4 bg-gradient-to-r from-primary-600 to-primary-700 text-white rounded-xl font-semibold text-sm hover:from-primary-700 hover:to-primary-800 transition-all shadow-lg shadow-primary-500/25">
                    Browse Directory
                </Link>
            </div>
        </div>
    </PublicLayout>
</template>

<script setup>
import { Head, Link } from '@inertiajs/vue3';
import PublicLayout from '@/Layouts/PublicLayout.vue';
import ExploreCarousel from '@/Components/Public/ExploreCarousel.vue';

defineProps({
    mode: { type: String, default: 'global' },
    city: { type: Object, default: null },
    rows: { type: Array, default: () => [] },
    hero: { type: Object, required: true },
    seo: { type: Object, required: true },
});
</script>