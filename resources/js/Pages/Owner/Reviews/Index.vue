<!-- resources/js/Pages/Owner/Reviews/Index.vue -->
<template>
    <AuthenticatedLayout>
        <!-- COMPACT HEADER -->
        <PageHeader color="amber" :breadcrumb="[
            { label: 'Dashboard', href: '/owner/dashboard' },
            { label: 'Reviews' }
        ]">
            <template #icon>
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                    d="M11.049 2.927c.3-.921 1.603-.921 1.902 0l1.519 4.674a1 1 0 00.95.69h4.915c.969 0 1.371 1.24.588 1.81l-3.976 2.888a1 1 0 00-.363 1.118l1.518 4.674c.3.922-.755 1.688-1.538 1.118l-3.976-2.888a1 1 0 00-1.176 0l-3.976 2.888c-.783.57-1.838-.197-1.538-1.118l1.518-4.674a1 1 0 00-.363-1.118l-3.976-2.888c-.784-.57-.38-1.81.588-1.81h4.914a1 1 0 00.951-.69l1.519-4.674z" />
            </template>
            <template #title>Customer reviews</template>
            <template #subtitle>{{ business?.name }}</template>
            <template #actions>
                <span
                    class="inline-flex items-center gap-2 px-3 py-2 bg-amber-50 dark:bg-amber-900/30 border border-amber-200 dark:border-amber-800 rounded-xl text-sm font-semibold text-amber-700 dark:text-amber-400">
                    <span class="text-amber-500 dark:text-amber-400">★</span>
                    {{ business.average_rating || 0 }}
                    <span class="text-xs text-amber-600/70 dark:text-amber-400/70 font-medium">
                        · {{ business.total_reviews || 0 }} review{{ (business.total_reviews || 0) !== 1 ? 's' : '' }}
                    </span>
                </span>
            </template>
        </PageHeader>

        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8 space-y-6">

            <!-- RATING SUMMARY -->
            <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-sm border border-gray-100 dark:border-gray-700 p-6">
                <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                    <!-- Left: big avg -->
                    <div class="text-center lg:text-left">
                        <p class="text-[10px] uppercase tracking-widest text-gray-400 dark:text-gray-500 font-bold mb-1">Overall</p>
                        <p class="text-5xl font-bold text-gray-900 dark:text-white tracking-tight leading-none">{{ business.average_rating || 0 }}</p>
                        <div class="flex items-center gap-1 justify-center lg:justify-start mt-2">
                            <span v-for="i in 5" :key="i" class="text-xl transition-all duration-300"
                                  :class="i <= Math.round(business.average_rating || 0) ? 'text-amber-400' : 'text-gray-200 dark:text-gray-700'">
                                ★
                            </span>
                        </div>
                        <p class="text-sm text-gray-500 dark:text-gray-400 mt-2">{{ business.total_reviews || 0 }} total reviews</p>
                    </div>

                    <!-- Middle: distribution -->
                    <div class="lg:col-span-1">
                        <p class="text-[10px] uppercase tracking-widest text-gray-400 dark:text-gray-500 font-bold mb-3">Distribution</p>
                        <div v-for="rating in [5,4,3,2,1]" :key="rating" class="flex items-center gap-2 mb-1.5 last:mb-0">
                            <span class="text-xs text-gray-600 dark:text-gray-400 w-3 font-medium">{{ rating }}</span>
                            <span class="text-amber-400 text-xs">★</span>
                            <div class="flex-1 h-2 bg-gray-100 dark:bg-gray-700 rounded-full overflow-hidden">
                                <div class="h-full bg-gradient-to-r from-amber-400 to-amber-500 rounded-full transition-all duration-1000"
                                     :style="{ width: getRatingPercentage(rating) + '%' }"></div>
                            </div>
                            <span class="text-xs text-gray-400 dark:text-gray-500 w-8 text-right font-medium">{{ getRatingCount(rating) }}</span>
                        </div>
                    </div>

                    <!-- Right: status summary -->
                    <div class="lg:border-l lg:border-gray-100 dark:lg:border-gray-700 lg:pl-6">
                        <p class="text-[10px] uppercase tracking-widest text-gray-400 dark:text-gray-500 font-bold mb-3">By status</p>
                        <div class="space-y-2.5">
                            <div class="flex items-center justify-between">
                                <div class="flex items-center gap-2">
                                    <span class="w-2.5 h-2.5 rounded-full bg-emerald-500"></span>
                                    <span class="text-sm text-gray-600 dark:text-gray-400">Approved</span>
                                </div>
                                <span class="text-sm font-semibold text-gray-900 dark:text-white">{{ getStatusCount('approved') }}</span>
                            </div>
                            <div class="flex items-center justify-between">
                                <div class="flex items-center gap-2">
                                    <span class="w-2.5 h-2.5 rounded-full bg-amber-500"></span>
                                    <span class="text-sm text-gray-600 dark:text-gray-400">Pending</span>
                                </div>
                                <span class="text-sm font-semibold text-gray-900 dark:text-white">{{ getStatusCount('pending') }}</span>
                            </div>
                            <div class="flex items-center justify-between">
                                <div class="flex items-center gap-2">
                                    <span class="w-2.5 h-2.5 rounded-full bg-red-500"></span>
                                    <span class="text-sm text-gray-600 dark:text-gray-400">Rejected</span>
                                </div>
                                <span class="text-sm font-semibold text-gray-900 dark:text-white">{{ getStatusCount('rejected') }}</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- FILTERS -->
            <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-sm border border-gray-100 dark:border-gray-700 p-5">
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 items-end">
                    <div>
                        <label class="block text-[10px] uppercase tracking-widest text-gray-400 dark:text-gray-500 font-bold mb-2">Status</label>
                        <select v-model="filters.status" @change="applyFilters"
                                class="w-full px-4 py-2.5 border border-gray-200 dark:border-gray-600 rounded-xl focus:outline-none focus:ring-2 focus:ring-primary-500 focus:border-transparent transition-colors text-sm bg-white dark:bg-gray-900 text-gray-900 dark:text-white appearance-none">
                            <option value="">All status</option>
                            <option value="pending">Pending</option>
                            <option value="approved">Approved</option>
                            <option value="rejected">Rejected</option>
                        </select>
                    </div>
                    <div class="lg:col-span-2">
                        <label class="block text-[10px] uppercase tracking-widest text-gray-400 dark:text-gray-500 font-bold mb-2">Search</label>
                        <input type="text" v-model="filters.search" @keyup.enter="applyFilters"
                               placeholder="Search reviews…"
                               class="w-full px-4 py-2.5 border border-gray-200 dark:border-gray-600 bg-white dark:bg-gray-900 text-gray-900 dark:text-white placeholder-gray-400 dark:placeholder-gray-500 rounded-xl focus:outline-none focus:ring-2 focus:ring-primary-500 focus:border-transparent transition-colors text-sm" />
                    </div>
                    <div class="flex gap-2">
                        <button @click="applyFilters"
                                class="flex-1 px-5 py-2.5 bg-gradient-to-r from-primary-600 to-primary-700 text-white rounded-xl hover:from-primary-700 hover:to-primary-800 transition-all shadow-lg shadow-primary-500/25 hover:shadow-primary-500/40 font-semibold text-sm">
                            Apply
                        </button>
                        <button @click="resetFilters"
                                class="px-5 py-2.5 border border-gray-300 dark:border-gray-600 text-gray-700 dark:text-gray-300 rounded-xl hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors font-semibold text-sm">
                            Reset
                        </button>
                    </div>
                </div>
            </div>

            <!-- REVIEWS LIST -->
            <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-sm border border-gray-100 dark:border-gray-700 overflow-hidden">
                <div class="px-6 py-4 border-b border-gray-100 dark:border-gray-700 bg-gradient-to-br from-gray-50 to-white dark:from-gray-800 dark:to-gray-800/50 flex justify-between items-center">
                    <div class="flex items-center gap-3">
                        <div class="w-9 h-9 rounded-xl bg-amber-50 dark:bg-amber-900/30 flex items-center justify-center">
                            <svg class="w-4 h-4 text-amber-600 dark:text-amber-400" fill="currentColor" viewBox="0 0 20 20">
                                <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z" />
                            </svg>
                        </div>
                        <div>
                            <h3 class="text-sm font-bold text-gray-900 dark:text-white tracking-tight">All reviews</h3>
                            <p class="text-xs text-gray-400 dark:text-gray-500">{{ reviews.total || 0 }} total</p>
                        </div>
                    </div>
                </div>

                <div v-if="reviews.data && reviews.data.length > 0" class="divide-y divide-gray-100 dark:divide-gray-700">
                    <div v-for="review in reviews.data" :key="review.id"
                         class="p-6 transition-colors"
                         :class="review.status === 'pending'
                             ? 'bg-amber-50/30 dark:bg-amber-900/10 hover:bg-amber-50/60 dark:hover:bg-amber-900/20'
                             : 'hover:bg-gray-50/50 dark:hover:bg-gray-700/30'">
                        <div class="flex flex-col sm:flex-row sm:items-start justify-between gap-4">
                            <div class="flex-1 min-w-0">
                                <!-- Reviewer -->
                                <div class="flex items-center gap-3">
                                    <div class="w-11 h-11 rounded-2xl bg-gradient-to-br from-primary-500 to-primary-700 flex items-center justify-center text-white font-bold text-sm flex-shrink-0 shadow-md">
                                        {{ getInitials(review.user?.name || 'Anonymous') }}
                                    </div>
                                    <div class="min-w-0">
                                        <div class="flex items-center gap-2 flex-wrap">
                                            <span class="text-sm font-bold text-gray-900 dark:text-white">{{ review.user?.name || 'Anonymous' }}</span>
                                            <span class="text-xs text-gray-400 dark:text-gray-500">•</span>
                                            <span class="text-xs text-gray-400 dark:text-gray-500">{{ formatDate(review.created_at) }}</span>
                                        </div>
                                        <div class="flex items-center gap-1 mt-1">
                                            <span v-for="i in review.rating" :key="i" class="text-sm text-amber-400">★</span>
                                            <span v-for="i in (5 - review.rating)" :key="'empty-' + i" class="text-sm text-gray-200 dark:text-gray-700">★</span>
                                            <span class="text-xs text-gray-400 dark:text-gray-500 ml-1.5 font-medium">{{ review.rating }}/5</span>
                                        </div>
                                    </div>
                                </div>

                                <!-- Content -->
                                <div class="mt-4">
                                    <p v-if="review.title" class="font-bold text-gray-900 dark:text-white text-sm">{{ review.title }}</p>
                                    <p class="text-gray-600 dark:text-gray-400 text-sm mt-1 leading-relaxed">{{ review.content }}</p>
                                </div>

                                <!-- Photos -->
                                <div v-if="review.images && review.images.length > 0" class="mt-3">
                                    <ReviewImageGallery :images="review.images" size="sm" />
                                </div>

                                <!-- Action row -->
                                <div class="flex flex-wrap items-center gap-3 mt-4">
                                    <span :class="statusClass(review.status)">
                                        {{ review.status.charAt(0).toUpperCase() + review.status.slice(1) }}
                                    </span>

                                    <a v-if="canReply"
                                       :href="`/owner/businesses/${business.id}/reviews/${review.id}`"
                                       class="inline-flex items-center gap-1 text-sm text-primary-600 dark:text-primary-400 hover:text-primary-800 dark:hover:text-primary-300 font-semibold transition-colors group">
                                        View & reply
                                        <svg class="w-4 h-4 transform group-hover:translate-x-0.5 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                                        </svg>
                                    </a>

                                    <button v-else
                                            @click="handleReplyClick"
                                            class="inline-flex items-center gap-1 text-sm text-amber-600 dark:text-amber-400 hover:text-amber-800 dark:hover:text-amber-300 font-semibold transition-colors">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" />
                                        </svg>
                                        Reply locked
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                                        </svg>
                                    </button>
                                </div>
                            </div>

                            <!-- Reply status pill -->
                            <div class="flex-shrink-0">
                                <span v-if="review.replies && review.replies.length > 0"
                                      class="inline-flex items-center gap-1.5 px-3 py-1.5 bg-emerald-50 dark:bg-emerald-900/30 text-emerald-700 dark:text-emerald-400 text-xs font-bold rounded-full border border-emerald-200 dark:border-emerald-800">
                                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7" />
                                    </svg>
                                    Replied
                                </span>
                                <span v-if="!canReply && (!review.replies || review.replies.length === 0)"
                                      class="inline-flex items-center gap-1.5 px-3 py-1.5 bg-amber-50 dark:bg-amber-900/30 text-amber-700 dark:text-amber-400 text-xs font-bold rounded-full border border-amber-200 dark:border-amber-800">
                                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" />
                                    </svg>
                                    Upgrade to reply
                                </span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Empty state -->
                <div v-else class="p-12 text-center">
                    <div class="w-20 h-20 bg-gradient-to-br from-amber-100 to-amber-200 dark:from-amber-900/40 dark:to-amber-900/20 rounded-2xl flex items-center justify-center mx-auto mb-5 shadow-sm">
                        <svg class="w-10 h-10 text-amber-600 dark:text-amber-400" fill="currentColor" viewBox="0 0 20 20">
                            <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z" />
                        </svg>
                    </div>
                    <h3 class="text-xl font-bold text-gray-900 dark:text-white tracking-tight mb-2">No reviews yet</h3>
                    <p class="text-gray-500 dark:text-gray-400 text-sm max-w-md mx-auto">
                        Your business hasn't received any reviews yet. Reviews from customers will appear here.
                    </p>
                </div>

                <!-- Pagination -->
                <div v-if="reviews.data && reviews.data.length > 0" class="px-6 py-4 border-t border-gray-100 dark:border-gray-700">
                    <Pagination :links="reviews.links" />
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>

<script setup>
import { reactive } from 'vue';
import { router } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import Pagination from '@/Components/Pagination.vue';
import { useUpgradeModal } from '@/composables/useUpgradeModal';
import { useStatusBadge } from '@/composables/useStatusBadge';
import ReviewImageGallery from '@/Components/Public/ReviewImageGallery.vue';
import PageHeader from '@/Components/PageHeader.vue';

const { show: showUpgrade } = useUpgradeModal();

const handleReplyClick = () => {
    showUpgrade('Review Responses', 'Starter', [
        'Respond to customer reviews',
        'Build trust with future customers',
        'Improve your business reputation',
    ]);
};

const props = defineProps({
    business: Object,
    reviews: Object,
    filters: Object,
    canReply: {
        type: Boolean,
        default: false,
    },
});

const filters = reactive({
    status: props.filters?.status || '',
    search: props.filters?.search || '',
});

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

const getRatingPercentage = (rating) => {
    const total = props.business?.total_reviews || 0;
    if (total === 0) return 0;
    const count = props.reviews.data?.filter(r => r.rating === rating).length || 0;
    return Math.round((count / total) * 100);
};

const getRatingCount = (rating) => {
    return props.reviews.data?.filter(r => r.rating === rating).length || 0;
};

const getStatusCount = (status) => {
    if (!props.reviews.data) return 0;
    return props.reviews.data.filter(r => r.status === status).length;
};

const { review: statusClass } = useStatusBadge();

const applyFilters = () => {
    router.get(`/owner/businesses/${props.business.id}/reviews`, filters, {
        preserveState: true,
        preserveScroll: true,
    });
};

const resetFilters = () => {
    filters.status = '';
    filters.search = '';
    applyFilters();
};
</script>