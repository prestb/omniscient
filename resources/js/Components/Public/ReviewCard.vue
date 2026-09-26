<!-- resources/js/Components/Public/ReviewCard.vue -->
<template>
    <div class="group bg-white dark:bg-gray-800 rounded-2xl border border-gray-100 dark:border-gray-700 p-6 hover:shadow-xl transition-all duration-300 hover:-translate-y-0.5">
        <div class="flex items-start gap-4">
            <!-- Avatar -->
            <div class="flex-shrink-0">
                <div class="w-12 h-12 rounded-2xl bg-gradient-to-br from-primary-400 to-primary-600 flex items-center justify-center text-white font-bold text-base shadow-lg shadow-primary-100/50 dark:shadow-none">
                    {{ getInitials(review.reviewer_name) }}
                </div>
            </div>
            
            <!-- Content -->
            <div class="flex-1 min-w-0">
                <!-- Header -->
                <div class="flex items-start justify-between flex-wrap gap-2">
                    <div>
                        <p class="font-semibold text-gray-900 dark:text-white text-base">
                            {{ review.reviewer_name }}
                        </p>
                        <div class="flex items-center gap-2 mt-1 flex-wrap">
                            <!-- Rating Stars -->
                            <div class="flex items-center gap-0.5">
                                <span v-for="i in 5" :key="i" class="text-sm transition-all duration-300"
                                      :class="i <= review.rating ? 'text-yellow-400 scale-100' : 'text-gray-200 dark:text-gray-600 scale-90'">
                                    ★
                                </span>
                            </div>
                            <!-- Rating Badge -->
                            <span 
                                class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-medium"
                                :class="getRatingBadgeClass(review.rating)"
                            >
                                {{ getRatingLabel(review.rating) }}
                            </span>
                            <!-- Date -->
                            <span class="text-xs text-gray-400 dark:text-gray-500 flex items-center gap-1">
                                <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                                </svg>
                                {{ formatDate(review.created_at) }}
                            </span>
                        </div>
                    </div>
                    
                    <!-- Status Badge -->
                    <span v-if="review.status === 'pending'" 
                          class="inline-flex items-center gap-1 px-2.5 py-1 bg-yellow-50 dark:bg-yellow-900/20 text-yellow-700 dark:text-yellow-400 text-xs font-medium rounded-full border border-yellow-200 dark:border-yellow-800 flex-shrink-0">
                        <span class="w-1.5 h-1.5 bg-yellow-500 rounded-full animate-pulse"></span>
                        Pending
                    </span>
                    <span v-if="review.status === 'approved'" 
                          class="inline-flex items-center gap-1 px-2.5 py-1 bg-green-50 dark:bg-green-900/20 text-green-700 dark:text-green-400 text-xs font-medium rounded-full border border-green-200 dark:border-green-800 flex-shrink-0">
                        <svg class="w-3 h-3" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" />
                        </svg>
                        Verified
                    </span>
                </div>

                <!-- Title -->
                <p v-if="review.title" class="font-semibold text-gray-900 dark:text-white mt-3 text-base">
                    {{ review.title }}
                </p>

                <!-- Content -->
                <p class="text-gray-600 dark:text-gray-400 text-sm mt-2 leading-relaxed">
                    {{ review.content }}
                </p>

                <!-- Photos (if any) -->
                <div v-if="review.images && review.images.length > 0" class="mt-3">
                    <ReviewImageGallery :images="review.images" size="sm" alt="Review photo" />
                </div>

                <!-- Owner Reply -->
                <div v-if="review.replies && review.replies.length > 0" 
                     class="mt-4 bg-gradient-to-r from-blue-50 to-blue-50/50 dark:from-blue-900/20 dark:to-blue-900/10 rounded-xl p-4 border border-blue-100 dark:border-blue-800">
                    <div class="flex items-center gap-2">
                        <svg class="w-4 h-4 text-blue-500 dark:text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h10a8 8 0 018 8v2M3 10l6 6m-6-6l6-6" />
                        </svg>
                        <span class="text-xs font-semibold text-blue-700 dark:text-blue-300">Owner Response</span>
                        <span class="text-xs text-gray-400 dark:text-gray-500">• {{ formatDate(review.replies[0].created_at) }}</span>
                    </div>
                    <p class="text-sm text-gray-700 dark:text-gray-300 mt-1.5 leading-relaxed">{{ review.replies[0].content }}</p>
                </div>

                <!-- Helpful Actions -->
                <div class="flex items-center gap-4 mt-4 pt-3 border-t border-gray-100 dark:border-gray-700">
                    <button @click="toggleHelpful" 
                            class="flex items-center gap-1.5 text-xs text-gray-400 dark:text-gray-500 hover:text-primary-600 dark:hover:text-primary-400 transition-colors group">
                        <svg class="w-4 h-4 transition-transform group-hover:scale-110" :class="isHelpful ? 'text-primary-600 dark:text-primary-400 fill-primary-600 dark:fill-primary-400' : 'text-gray-400 dark:text-gray-500'" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 10h4.764a2 2 0 011.789 2.894l-3.5 7A2 2 0 0115.263 21h-4.017c-.163 0-.326-.02-.485-.06L7 20m7-10V5a2 2 0 00-2-2h-.095c-.5 0-.905.405-.905.905 0 .714-.211 1.412-.608 2.006L7 11v9m7-10h-2M7 20H5a2 2 0 01-2-2v-6a2 2 0 012-2h2.5" />
                        </svg>
                        <span>{{ isHelpful ? 'Helpful' : 'Helpful' }}</span>
                        <span class="text-xs text-gray-300 dark:text-gray-600">•</span>
                        <span class="text-xs text-gray-400 dark:text-gray-500">{{ helpfulCount }}</span>
                    </button>
                    
                    <button class="flex items-center gap-1.5 text-xs text-gray-400 dark:text-gray-500 hover:text-primary-600 dark:hover:text-primary-400 transition-colors group">
                        <svg class="w-4 h-4 transition-transform group-hover:scale-110" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8.684 13.342C8.886 12.938 9 12.482 9 12c0-.482-.114-.938-.316-1.342m0 2.684a3 3 0 110-2.684m0 2.684l6.632 3.316m-6.632-6l6.632-3.316m0 0a3 3 0 105.367-2.684 3 3 0 00-5.367 2.684zm0 9.316a3 3 0 105.368 2.684 3 3 0 00-5.368-2.684z" />
                        </svg>
                        Share
                    </button>
                    
                    <button v-if="canReply" @click="openReplyForm" class="flex items-center gap-1.5 text-xs text-gray-400 dark:text-gray-500 hover:text-primary-600 dark:hover:text-primary-400 transition-colors group">
                        <svg class="w-4 h-4 transition-transform group-hover:scale-110" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h10a8 8 0 018 8v2M3 10l6 6m-6-6l6-6" />
                        </svg>
                        Reply
                    </button>
                </div>
            </div>
        </div>
    </div>
</template>

<script setup>
import { ref, computed } from 'vue';
import RatingDisplay from '@/Components/Public/RatingDisplay.vue';
import ReviewImageGallery from '@/Components/Public/ReviewImageGallery.vue';
const props = defineProps({
    review: {
        type: Object,
        required: true
    },
    canReply: {
        type: Boolean,
        default: false
    }
});

const isHelpful = ref(false);
const helpfulCount = ref(props.review.helpful_count || 0);

const getInitials = (name) => {
    if (!name) return '?';
    return name.split(' ').map(n => n[0]).join('').toUpperCase().slice(0, 2);
};

const formatDate = (date) => {
    if (!date) return 'N/A';
    return new Date(date).toLocaleDateString('en-US', {
        year: 'numeric',
        month: 'short',
        day: 'numeric'
    });
};

const getRatingLabel = (rating) => {
    const value = Number(rating) || 0;
    if (value >= 4.5) return 'Excellent';
    if (value >= 3.5) return 'Good';
    if (value >= 2.5) return 'Average';
    if (value >= 1.5) return 'Fair';
    return 'Poor';
};

const getRatingBadgeClass = (rating) => {
    const value = Number(rating) || 0;
    if (value >= 4.5) return 'bg-green-100 dark:bg-green-900/30 text-green-700 dark:text-green-400';
    if (value >= 3.5) return 'bg-blue-100 dark:bg-blue-900/30 text-blue-700 dark:text-blue-400';
    if (value >= 2.5) return 'bg-yellow-100 dark:bg-yellow-900/30 text-yellow-700 dark:text-yellow-400';
    if (value >= 1.5) return 'bg-orange-100 dark:bg-orange-900/30 text-orange-700 dark:text-orange-400';
    return 'bg-red-100 dark:bg-red-900/30 text-red-700 dark:text-red-400';
};

const toggleHelpful = () => {
    isHelpful.value = !isHelpful.value;
    if (isHelpful.value) {
        helpfulCount.value++;
    } else {
        helpfulCount.value--;
    }
    // Here you would make an API call to save the helpful vote
};

const openReplyForm = () => {
    // Emit event to parent component to open reply form
    // This would trigger a modal or inline reply form
    console.log('Open reply form for review:', props.review.id);
};
</script>

<style scoped>
/* Smooth transitions */
.transition-all {
    transition-property: all;
    transition-timing-function: cubic-bezier(0.4, 0, 0.2, 1);
    transition-duration: 300ms;
}
</style>