<!-- resources/js/Pages/Public/ListingProfile.vue -->
<template>
    <PublicLayout>
        <Head :title="`${listing.name} · Omniscient`" />

        <!-- ==================== COVER ==================== -->
        <div class="relative h-64 md:h-96 bg-gray-900 overflow-hidden">
            <OptimizedImage v-if="listing.cover_image" :path="listing.cover_image" size="large" :alt="listing.name"
                img-class="w-full h-full object-cover"
                fallback-class="w-full h-full bg-gradient-to-br from-primary-700 via-primary-600 to-primary-800" />
            <div v-else class="w-full h-full bg-gradient-to-br from-primary-700 via-primary-600 to-primary-800"></div>
            <div class="absolute inset-0 bg-gradient-to-t from-black/80 via-black/30 to-black/10"></div>

            <div class="absolute bottom-0 left-0 right-0">
                <div class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8 pb-6">
                    <span class="inline-flex px-2.5 py-1 rounded-full text-[11px] font-bold uppercase tracking-widest bg-white/15 text-white backdrop-blur">
                        {{ listing.listing_type }}
                    </span>
                    <h1 class="mt-2 text-3xl sm:text-4xl font-bold text-white tracking-tight">{{ listing.name }}</h1>

                    <div class="mt-2 flex flex-wrap items-center gap-3 text-sm text-white/85">
                        <span v-if="listing.categories?.length">
                            {{ listing.categories.map(c => c.name).join(' · ') }}
                        </span>
                        <!-- Location is optional: only rendered when present -->
                        <span v-if="listing.location?.city" class="inline-flex items-center gap-1">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M17.657 16.657L13.414 20.9a2 2 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z M15 11a3 3 0 11-6 0 3 3 0 016 0z" />
                            </svg>
                            {{ listing.location.city }}
                        </span>
                        <span v-if="listing.average_rating" class="inline-flex items-center gap-1">
                            ★ {{ listing.average_rating }} ({{ listing.reviews_count }})
                        </span>
                    </div>
                </div>
            </div>
        </div>

        <div class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8 py-8 grid grid-cols-1 lg:grid-cols-3 gap-8">
            <!-- ==================== MAIN ==================== -->
            <div class="lg:col-span-2 space-y-6">
                <section v-if="listing.description" class="bg-white dark:bg-gray-800 rounded-2xl border border-gray-100 dark:border-gray-700 p-6">
                    <h2 class="text-sm font-bold uppercase tracking-widest text-gray-500 mb-3">About</h2>
                    <p class="text-sm text-gray-700 dark:text-gray-300 whitespace-pre-line">{{ listing.description }}</p>
                </section>

                <section v-if="listing.categories?.length" class="bg-white dark:bg-gray-800 rounded-2xl border border-gray-100 dark:border-gray-700 p-6">
                    <h2 class="text-sm font-bold uppercase tracking-widest text-gray-500 mb-3">Categories</h2>
                    <div class="flex flex-wrap gap-2">
                        <span v-for="c in listing.categories" :key="c.id"
                            class="inline-flex px-3 py-1 rounded-full text-xs font-semibold bg-primary-50 dark:bg-primary-900/20 text-primary-700 dark:text-primary-300">
                            {{ c.name }}
                        </span>
                    </div>
                </section>

                <section v-if="listing.locations?.[0]?.hours?.length" class="bg-white dark:bg-gray-800 rounded-2xl border border-gray-100 dark:border-gray-700 p-6">
                    <h2 class="text-sm font-bold uppercase tracking-widest text-gray-500 mb-3">Opening hours</h2>
                    <ul class="text-sm text-gray-700 dark:text-gray-300 space-y-1">
                        <li v-for="h in listing.locations[0].hours" :key="h.day" class="flex justify-between gap-4">
                            <span class="capitalize">Day {{ h.day }}</span>
                            <span v-if="h.is_closed" class="text-gray-400">Closed</span>
                            <span v-else>{{ h.opens_at }} – {{ h.closes_at }}</span>
                        </li>
                    </ul>
                </section>

                <!-- ==================== REVIEWS ==================== -->
                <section class="bg-white dark:bg-gray-800 rounded-2xl border border-gray-100 dark:border-gray-700 p-6">
                    <h2 class="text-sm font-bold uppercase tracking-widest text-gray-500 mb-3">Reviews</h2>
                    <div v-if="!reviews.length" class="text-sm text-gray-500 dark:text-gray-400">No reviews yet.</div>
                    <ul v-else class="space-y-4">
                        <li v-for="r in reviews" :key="r.id" class="border-b border-gray-100 dark:border-gray-700 pb-3 last:border-0">
                            <div class="flex items-center justify-between">
                                <span class="text-sm font-semibold text-gray-900 dark:text-white">{{ r.user_name || 'Anonymous' }}</span>
                                <span class="text-xs text-gray-400">{{ r.created_at }}</span>
                            </div>
                            <div class="text-amber-500 text-sm">{{ '★'.repeat(r.rating) }}</div>
                            <p class="text-sm text-gray-700 dark:text-gray-300 mt-1">{{ r.content }}</p>
                        </li>
                    </ul>
                </section>
            </div>

            <!-- ==================== SIDEBAR ==================== -->
            <aside class="space-y-6">
                <!-- Organization context: the Listing stays canonical -->
                <section v-if="listing.business_id" class="bg-white dark:bg-gray-800 rounded-2xl border border-gray-100 dark:border-gray-700 p-6">
                    <h2 class="text-sm font-bold uppercase tracking-widest text-gray-500 mb-3">Organization</h2>
                    <a :href="`/business/${listing.business_slug}`"
                        class="text-sm font-semibold text-primary-600 hover:text-primary-700">
                        {{ listing.business_name }}
                    </a>
                    <p class="text-xs text-gray-400 mt-1">This Listing is one presence of that organization.</p>
                </section>

                <!-- Physical place (optional) -->
                <section v-if="listing.location" class="bg-white dark:bg-gray-800 rounded-2xl border border-gray-100 dark:border-gray-700 p-6">
                    <h2 class="text-sm font-bold uppercase tracking-widest text-gray-500 mb-3">Where</h2>
                    <p class="text-sm text-gray-700 dark:text-gray-300">
                        <span v-if="listing.location.address">{{ listing.location.address }}<br /></span>
                        <span v-if="listing.location.city">{{ listing.location.city }}</span>
                    </p>
                    <a v-if="listing.location.phone" :href="`tel:${listing.location.phone}`"
                        @click="trackClick('phone')"
                        class="inline-block text-sm text-gray-700 dark:text-gray-300 mt-3">
                        📞 {{ listing.location.phone }}
                    </a>
                </section>
            </aside>
        </div>
    </PublicLayout>
</template>

<script setup>
import PublicLayout from '@/Layouts/PublicLayout.vue';
import OptimizedImage from '@/Components/Public/OptimizedImage.vue';
import { Head } from '@inertiajs/vue3';
import axios from 'axios';
import { onMounted } from 'vue';

const props = defineProps({
    listing: { type: Object, required: true },
    reviews: { type: Array, default: () => [] },
});

/**
 * PHASE 11 / WAVE 1D-3 — CANONICAL LISTING TRACKING (PATH A).
 *
 * Analytics are Listing-owned, so every event carries the EXPLICIT Listing id
 * from this page's own props — never a Business id, and never a Listing
 * resolved through a Business.
 */
const trackClick = (type) => {
    axios.post(`/analytics/listing/${props.listing.id}/track-click/${type}`).catch(() => {});
};

onMounted(() => {
    axios.post(`/analytics/listing/${props.listing.id}/track-view`).catch(() => {});
});
</script>
