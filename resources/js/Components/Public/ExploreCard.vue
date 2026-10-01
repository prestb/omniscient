<!-- resources/js/Components/Public/ExploreCard.vue -->
<template>
    <Link :href="`/listing/${listing.slug}`" class="block group h-full">
        <div
            class="bg-white dark:bg-gray-800 rounded-2xl border border-gray-100 dark:border-gray-700 overflow-hidden hover:shadow-lg transition-all duration-300 hover:-translate-y-0.5 h-full">
            <!-- Image -->
            <div class="relative aspect-[3/2] bg-gray-100 dark:bg-gray-700 overflow-hidden">
                <OptimizedImage v-if="listing.cover_image" :path="listing.cover_image" :name="listing.name"
                    size="thumb" :alt="listing.name"
                    img-class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-500"
                    fallback-class="w-full h-full flex items-center justify-center bg-gradient-to-br from-primary-500 to-primary-600" />

                <div v-else-if="listing.logo"
                    class="w-full h-full flex items-center justify-center bg-gradient-to-br from-gray-50 to-gray-100 dark:from-gray-700 dark:to-gray-800 p-3">
                    <OptimizedImage :path="listing.logo" :name="listing.name" size="thumb" :alt="listing.name"
                        img-class="w-12 h-12 object-contain"
                        fallback-class="text-2xl font-bold text-primary-600 dark:text-primary-400" />
                </div>

                <div v-else
                    class="w-full h-full flex items-center justify-center bg-gradient-to-br from-primary-500 to-primary-600">
                    <span class="text-2xl font-bold text-white opacity-90">
                        {{ getInitials(listing.name) }}
                    </span>
                </div>
            </div>

            <!-- Content -->
            <div class="p-2.5">
                <h3
                    class="text-xs font-bold text-gray-900 dark:text-white group-hover:text-primary-600 dark:group-hover:text-primary-400 transition-colors line-clamp-1 leading-snug">
                    {{ listing.name }}
                </h3>

                <div class="flex items-center gap-1 mt-1 text-[11px] text-gray-500 dark:text-gray-400">
                    <span class="flex items-center gap-0.5">
                        <span class="text-amber-400">★</span>
                        <span class="font-semibold text-gray-700 dark:text-gray-300">{{
                            formatRating(listing.average_rating) }}</span>
                    </span>
                    <span class="text-gray-300 dark:text-gray-600">·</span>
                    <span>{{ listing.reviews_count || 0 }}</span>
                </div>

                <p v-if="listing.category" class="text-[10px] text-gray-400 dark:text-gray-500 mt-0.5 line-clamp-1">
                    {{ listing.category }}<template v-if="listing.city"> · {{ listing.city }}</template>
                </p>
            </div>
        </div>
    </Link>
</template>

<script setup>
    import { Link } from '@inertiajs/vue3';
    import OptimizedImage from './OptimizedImage.vue';


    defineProps({
        listing: { type: Object, required: true },
    });

    const getInitials = (name) => {
        if (!name) return '?';
        return name.split(' ').map(n => n[0]).join('').toUpperCase().slice(0, 2);
    };

    const formatRating = (value) => {
        if (value === null || value === undefined) return '0.0';
        const n = Number(value);
        return isNaN(n) ? '0.0' : n.toFixed(1);
    };
</script>