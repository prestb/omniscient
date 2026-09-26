<!-- resources/js/Components/Public/RelatedBusinesses.vue -->
<template>
    <div v-if="businesses && businesses.length > 0"
        class="bg-white dark:bg-gray-800 rounded-2xl border border-gray-100 dark:border-gray-700 p-6">
        <!-- Header -->
        <div class="flex items-center gap-3 mb-5">
            <div class="w-10 h-10 rounded-xl bg-indigo-50 dark:bg-indigo-900/30 flex items-center justify-center flex-shrink-0">
                <svg class="w-5 h-5 text-indigo-600 dark:text-indigo-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10" />
                </svg>
            </div>
            <div class="min-w-0">
                <h3 class="text-sm font-bold text-gray-900 dark:text-white tracking-tight">You might also like</h3>
                <p class="text-xs text-gray-500 dark:text-gray-400 mt-0.5">Similar businesses</p>
            </div>
        </div>

        <!-- ============== DESKTOP: vertical stack ============== -->
        <div class="hidden sm:flex sm:flex-col sm:gap-3">
            <Link v-for="biz in businesses" :key="`d-${biz.id}`"
                :href="`/business/${biz.slug}`"
                class="group flex items-center gap-3 p-2 -mx-2 rounded-xl hover:bg-gray-50 dark:hover:bg-gray-700/40 transition-colors">
                <!-- Thumbnail -->
                <div class="w-14 h-14 rounded-xl bg-gray-100 dark:bg-gray-700 overflow-hidden flex-shrink-0 relative">
                    <img v-if="getThumbUrl(biz)" :src="getThumbUrl(biz)" :alt="biz.name"
                        class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-300" />
                    <div v-else
                        class="w-full h-full flex items-center justify-center bg-gradient-to-br from-primary-500 to-primary-600 text-white font-bold text-base">
                        {{ getInitials(biz.name) }}
                    </div>
                </div>

                <!-- Text -->
                <div class="flex-1 min-w-0">
                    <p class="text-sm font-semibold text-gray-900 dark:text-white truncate group-hover:text-primary-600 dark:group-hover:text-primary-400 transition-colors">
                        {{ biz.name }}
                    </p>
                    <div class="flex items-center gap-1.5 mt-0.5 text-xs text-gray-500 dark:text-gray-400">
                        <span class="flex items-center gap-0.5">
                            <span class="text-amber-400">★</span>
                            <span class="font-semibold text-gray-700 dark:text-gray-300">{{ formatRating(biz.average_rating) }}</span>
                        </span>
                        <span class="text-gray-300 dark:text-gray-600">·</span>
                        <span class="truncate">{{ getCategory(biz) }}</span>
                    </div>
                    <p v-if="getCity(biz)" class="text-xs text-gray-400 dark:text-gray-500 mt-0.5 truncate">
                        📍 {{ getCity(biz) }}
                    </p>
                </div>
            </Link>
        </div>

        <!-- ============== MOBILE: horizontal carousel ============== -->
        <div class="sm:hidden -mx-6 px-6 flex gap-3 overflow-x-auto scrollbar-hide snap-x snap-mandatory pb-1">
            <Link v-for="biz in businesses" :key="`m-${biz.id}`"
                :href="`/business/${biz.slug}`"
                class="flex-shrink-0 w-[160px] snap-start group">
                <!-- Card -->
                <div class="rounded-xl overflow-hidden border border-gray-100 dark:border-gray-700 bg-white dark:bg-gray-800 hover:shadow-md transition-shadow">
                    <!-- Image -->
                    <div class="aspect-[16/10] bg-gray-100 dark:bg-gray-700 overflow-hidden relative">
                        <img v-if="getThumbUrl(biz)" :src="getThumbUrl(biz)" :alt="biz.name"
                            class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-300" />
                        <div v-else
                            class="w-full h-full flex items-center justify-center bg-gradient-to-br from-primary-500 to-primary-600 text-white font-bold text-xl">
                            {{ getInitials(biz.name) }}
                        </div>
                    </div>
                    <!-- Text -->
                    <div class="p-2.5">
                        <p class="text-xs font-semibold text-gray-900 dark:text-white truncate leading-snug">
                            {{ biz.name }}
                        </p>
                        <div class="flex items-center gap-1 mt-1 text-[10px] text-gray-500 dark:text-gray-400">
                            <span class="text-amber-400">★</span>
                            <span class="font-semibold text-gray-700 dark:text-gray-300">{{ formatRating(biz.average_rating) }}</span>
                            <span v-if="getCity(biz)" class="truncate">· {{ getCity(biz) }}</span>
                        </div>
                    </div>
                </div>
            </Link>
        </div>
    </div>
</template>

<script setup>
import { Link } from '@inertiajs/vue3';

const props = defineProps({
    businesses: { type: Array, default: () => [] },
});

const getInitials = (name) => {
    if (!name) return '?';
    return name.split(' ').map(n => n[0]).join('').toUpperCase().slice(0, 2);
};

const formatRating = (value) => {
    if (value === null || value === undefined) return '0.0';
    const num = Number(value);
    if (isNaN(num)) return '0.0';
    return num.toFixed(1);
};

const getThumbUrl = (biz) => {
    if (!biz) return null;
    if (biz.logo_url) return biz.logo_url;
    if (biz.cover_image_url) return biz.cover_image_url;
    if (biz.logo && typeof biz.logo === 'object' && biz.logo.path) {
        return '/storage/' + biz.logo.path;
    }
    if (biz.cover_image && typeof biz.cover_image === 'object' && biz.cover_image.path) {
        return '/storage/' + biz.cover_image.path;
    }
    if (typeof biz.logo === 'string') {
        return biz.logo.startsWith('http') ? biz.logo : '/storage/' + biz.logo;
    }
    if (typeof biz.cover_image === 'string') {
        return biz.cover_image.startsWith('http') ? biz.cover_image : '/storage/' + biz.cover_image;
    }
    return null;
};

const getCategory = (biz) => {
    const cats = biz.categories || [];
    if (cats.length === 0) return 'Uncategorized';
    const primary = cats.find(c => c.pivot?.is_primary);
    return primary?.name || cats[0]?.name || 'Uncategorized';
};

const getCity = (biz) => {
    const primary = (biz.branches || []).find(b => b.is_primary) || (biz.branches || [])[0];
    return primary?.city || null;
};
</script>

