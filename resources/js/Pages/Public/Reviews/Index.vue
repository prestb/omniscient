<!-- resources/js/Pages/Public/Reviews/Index.vue -->
<template>
    <PublicLayout>
        <!-- Header (inlined — PublicLayout has no #header slot) -->
        <div class="bg-white dark:bg-gray-800 border-b border-gray-200 dark:border-gray-700">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-6">
                <div class="flex items-center gap-4 flex-wrap">
                    <a :href="`/listing/${listing.slug}`"
                       class="text-primary-600 dark:text-primary-400 hover:text-primary-800 dark:hover:text-primary-300 font-semibold transition-colors">
                        ← Back to Listing
                    </a>
                    <h1 class="text-2xl font-bold text-gray-900 dark:text-white">
                        Reviews for {{ listing.name }}
                    </h1>
                </div>
                <div class="flex items-center gap-3 mt-2 flex-wrap">
                    <div class="flex items-center gap-1">
                        <span v-for="i in 5" :key="i"
                              class="text-xl"
                              :class="i <= Math.round(rating) ? 'text-yellow-400' : 'text-gray-300 dark:text-gray-600'">
                            ⭐
                        </span>
                    </div>
                    <span class="text-sm font-medium text-gray-900 dark:text-white">
                        {{ rating }} out of 5
                    </span>
                    <span class="text-sm text-gray-500 dark:text-gray-400">
                        ({{ reviewsCount }} reviews)
                    </span>
                </div>
            </div>
        </div>

        <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
            <!-- Rating Breakdown -->

            <!-- Reviews List -->
            <div v-if="reviews.data && reviews.data.length > 0">
                <div class="flex items-center justify-between mb-4 flex-wrap gap-3">
                    <p class="text-sm text-gray-500 dark:text-gray-400">
                        Showing {{ reviews.from }} - {{ reviews.to }} of {{ reviews.total }} reviews
                    </p>
                    <div class="flex items-center gap-2">
                        <label class="text-sm text-gray-600 dark:text-gray-400">Sort by:</label>
                        <select v-model="sort" @change="applySort"
                                class="px-3 py-1.5 text-sm border border-gray-200 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-900 text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-primary-500 focus:border-transparent">
                            <option value="latest">Latest</option>
                            <option value="highest">Highest Rating</option>
                            <option value="lowest">Lowest Rating</option>
                        </select>
                    </div>
                </div>

                <ReviewCard v-for="review in reviews.data" :key="review.id" :review="review" />

                <div class="mt-6">
                    <Pagination :links="reviews.links" />
                </div>
            </div>

            <!-- Empty State -->
            <div v-else class="text-center py-12 bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700">
                <div class="text-6xl mb-4">📝</div>
                <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-2">No Reviews Yet</h3>
                <p class="text-gray-500 dark:text-gray-400">Be the first to review this Listing!</p>
                <a :href="`/listing/${listing.slug}`"
                    class="mt-4 inline-flex text-sm font-semibold text-primary-600 dark:text-primary-400 hover:text-primary-800 dark:hover:text-primary-300">
                    View the Listing →
                </a>
            </div>
        </div>
    </PublicLayout>
</template>

<script setup>
import { ref } from 'vue';
import { router } from '@inertiajs/vue3';
import PublicLayout from '@/Layouts/PublicLayout.vue';
import ReviewCard from '@/Components/Public/ReviewCard.vue';
import Pagination from '@/Components/Pagination.vue';

const props = defineProps({
    // The reviewed entity. A Review belongs to a Listing.
    listing: { type: Object, required: true },
    /** Optional organization context; NULL for an independent Professional. */
    business: { type: Object, default: null },
    rating: { type: [Number, String], default: 0 },
    reviewsCount: { type: [Number, String], default: 0 },
    reviews: { type: Object, required: true },
});

const sort = ref('latest');

const applySort = () => {
    // PHASE 21D - the Business-scoped review route was deleted in 21C-R1.
    router.get(`/listing/${props.listing.slug}/reviews`, { sort: sort.value });
};
</script>