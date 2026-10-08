<!-- resources/js/Pages/Admin/Reviews/Show.vue -->
<template>
    <AuthenticatedLayout>
        <!-- COMPACT HEADER -->
        <PageHeader color="amber" :breadcrumb="[
            { label: 'Admin', href: '/admin/dashboard' },
            { label: 'Reviews', href: '/admin/reviews' },
            { label: '#' + review.id }
        ]">
            <template #icon>
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                    d="M11.049 2.927c.3-.921 1.603-.921 1.902 0l1.519 4.674a1 1 0 00.95.69h4.915c.969 0 1.371 1.24.588 1.81l-3.976 2.888a1 1 0 00-.363 1.118l1.518 4.674c.3.922-.755 1.688-1.538 1.118l-3.976-2.888a1 1 0 00-1.176 0l-3.976 2.888c-.783.57-1.838-.197-1.538-1.118l1.518-4.674a1 1 0 00-.363-1.118l-3.976-2.888c-.784-.57-.38-1.81.588-1.81h4.914a1 1 0 00.951-.69l1.519-4.674z" />
            </template>
            <template #title>Review #{{ review.id }}</template>
            <template #subtitle>{{ review.listing?.name }}</template>
            <template #actions>
                <span :class="statusClass(review.status)">
                    {{ review.status.charAt(0).toUpperCase() + review.status.slice(1) }}
                </span>
                <a href="/admin/reviews"
                    class="inline-flex items-center gap-2 px-4 py-2 border border-gray-300 dark:border-gray-600 text-gray-700 dark:text-gray-300 rounded-xl hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors text-sm font-semibold">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M10 19l-7-7m0 0l7-7m-7 7h18" />
                    </svg>
                    Back
                </a>
            </template>
        </PageHeader>

        <div class="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8 py-8 space-y-6">
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                <!-- REVIEW CONTENT -->
                <div class="lg:col-span-2">
                    <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-sm border border-gray-100 dark:border-gray-700 overflow-hidden">
                        <div
                            class="px-6 py-4 border-b border-gray-100 dark:border-gray-700 bg-gradient-to-br from-amber-50 to-white dark:from-amber-900/20 dark:to-gray-800/50 flex items-center gap-3">
                            <div class="w-9 h-9 rounded-xl bg-amber-50 dark:bg-amber-900/30 flex items-center justify-center">
                                <svg class="w-4 h-4 text-amber-500 dark:text-amber-400" fill="currentColor" viewBox="0 0 20 20">
                                    <path
                                        d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z" />
                                </svg>
                            </div>
                            <div>
                                <h3 class="text-sm font-bold text-gray-900 dark:text-white tracking-tight">Review content</h3>
                                <p class="text-xs text-gray-400 dark:text-gray-500">Submitted by the customer</p>
                            </div>
                        </div>

                        <div class="p-6 space-y-5">
                            <!-- Reviewer -->
                            <div class="flex items-start gap-4">
                                <div
                                    class="w-12 h-12 rounded-2xl bg-gradient-to-br from-primary-500 to-primary-700 flex items-center justify-center text-white text-base font-bold flex-shrink-0 shadow-md">
                                    {{ getInitials(review.user?.name || 'Anonymous') }}
                                </div>
                                <div class="min-w-0">
                                    <p class="text-sm font-bold text-gray-900 dark:text-white tracking-tight">
                                        {{ review.user?.name || 'Anonymous' }}
                                    </p>
                                    <p class="text-xs text-gray-400 dark:text-gray-500 flex items-center gap-1 truncate">
                                        <svg class="w-3 h-3 flex-shrink-0" fill="none" stroke="currentColor"
                                            viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M16 12a4 4 0 10-8 0 4 4 0 008 0zm0 0v1.5a2.5 2.5 0 005 0V12a9 9 0 10-9 9m4.5-1.206a8.959 8.959 0 01-4.5 1.207" />
                                        </svg>
                                        {{ review.user?.email }}
                                    </p>
                                </div>
                            </div>

                            <!-- Rating -->
                            <div class="bg-gray-50 dark:bg-gray-900/50 rounded-xl p-4">
                                <p class="text-[10px] uppercase tracking-widest text-gray-400 dark:text-gray-500 font-bold">Rating</p>
                                <div class="flex items-center gap-3 mt-1">
                                    <div class="flex text-amber-400 text-2xl">
                                        <span v-for="i in 5" :key="i"
                                            :class="i <= review.rating ? '' : 'opacity-25'">★</span>
                                    </div>
                                    <span class="text-sm font-bold text-gray-900 dark:text-white">{{ review.rating }}/5</span>
                                    <span class="text-xs font-bold uppercase tracking-wide text-gray-500 dark:text-gray-400">{{
                                        getRatingLabel(review.rating) }}</span>
                                </div>
                            </div>

                            <!-- Title -->
                            <div>
                                <p class="text-[10px] uppercase tracking-widest text-gray-400 dark:text-gray-500 font-bold">Title</p>
                                <p class="text-sm font-semibold text-gray-900 dark:text-white mt-1">{{ review.title || 'No title provided' }}
                                </p>
                            </div>

                            <!-- Content -->
                            <div>
                                <p class="text-[10px] uppercase tracking-widest text-gray-400 dark:text-gray-500 font-bold">Content</p>
                                <div class="mt-1.5 p-4 bg-gray-50 dark:bg-gray-900/50 rounded-xl border border-gray-100 dark:border-gray-700">
                                    <p class="text-sm text-gray-700 dark:text-gray-300 whitespace-pre-wrap leading-relaxed">{{
                                        review.content }}
                                    </p>
                                </div>
                            </div>

                            <!-- Images -->
                            <div v-if="review.images && review.images.length > 0">
                                <p
                                    class="text-[10px] uppercase tracking-widest text-gray-400 dark:text-gray-500 font-bold flex items-center gap-1.5 mb-2">
                                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z" />
                                    </svg>
                                    Images
                                </p>
                                <ReviewImageGallery :images="review.images" size="md" />
                            </div>

                            <!-- Submitted -->
                            <div class="pt-4 border-t border-gray-100 dark:border-gray-700">
                                <p
                                    class="text-[10px] uppercase tracking-widest text-gray-400 dark:text-gray-500 font-bold flex items-center gap-1.5">
                                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                                    </svg>
                                    Submitted
                                </p>
                                <p class="text-sm text-gray-700 dark:text-gray-300 mt-1 font-medium">{{ formatDate(review.created_at) }}
                                </p>
                            </div>

                            <!-- Approved/Rejected info -->
                            <div v-if="review.approved_at || review.rejected_at" class="pt-4 border-t border-gray-100 dark:border-gray-700">
                                <p class="text-[10px] uppercase tracking-widest text-gray-400 dark:text-gray-500 font-bold">
                                    {{ review.status === 'approved' ? 'Approved' : 'Rejected' }}
                                </p>
                                <p class="text-sm text-gray-700 dark:text-gray-300 mt-1">
                                    <span class="font-medium">{{ formatDate(review.approved_at || review.rejected_at)
                                    }}</span>
                                    <span class="text-xs text-gray-400 dark:text-gray-500"> · by {{ review.approved_by?.name || 'Admin'
                                    }}</span>
                                </p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- SIDEBAR -->
                <div class="lg:col-span-1 space-y-6">

                    <!-- Actions -->
                    <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-sm border border-gray-100 dark:border-gray-700 overflow-hidden sticky top-6">
                        <div
                            class="px-5 py-4 border-b border-gray-100 dark:border-gray-700 bg-gradient-to-br from-gray-50 to-white dark:from-gray-800 dark:to-gray-800/50 flex items-center gap-3">
                            <div class="w-9 h-9 rounded-xl bg-primary-50 dark:bg-primary-900/30 flex items-center justify-center">
                                <svg class="w-4 h-4 text-primary-600 dark:text-primary-400" fill="none" stroke="currentColor"
                                    viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M13 10V3L4 14h7v7l9-11h-7z" />
                                </svg>
                            </div>
                            <h3 class="text-sm font-bold text-gray-900 dark:text-white tracking-tight">Actions</h3>
                        </div>
                        <div class="p-5 space-y-3">
                            <button v-if="review.status === 'pending'" @click="approveReview"
                                class="w-full inline-flex items-center justify-center gap-2 px-4 py-2.5 bg-gradient-to-r from-emerald-500 to-emerald-600 text-white rounded-xl hover:from-emerald-600 hover:to-emerald-700 transition-all shadow-lg shadow-emerald-500/25 hover:shadow-emerald-500/40 hover:-translate-y-0.5 font-semibold text-sm">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5"
                                        d="M5 13l4 4L19 7" />
                                </svg>
                                Approve Review
                            </button>
                            <button v-if="review.status === 'pending'" @click="rejectReview"
                                class="w-full inline-flex items-center justify-center gap-2 px-4 py-2.5 bg-gradient-to-r from-red-500 to-red-600 text-white rounded-xl hover:from-red-600 hover:to-red-700 transition-all shadow-lg shadow-red-500/25 hover:shadow-red-500/40 hover:-translate-y-0.5 font-semibold text-sm">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M6 18L18 6M6 6l12 12" />
                                </svg>
                                Reject Review
                            </button>
                            <button @click="deleteReview"
                                class="w-full inline-flex items-center justify-center gap-2 px-4 py-2.5 border border-red-300 dark:border-red-700 text-red-600 dark:text-red-400 rounded-xl hover:bg-red-50 dark:hover:bg-red-900/30 transition-colors font-semibold text-sm">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                </svg>
                                Delete Review
                            </button>
                        </div>
                    </div>

                    <!-- Business info -->
                    <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-sm border border-gray-100 dark:border-gray-700 overflow-hidden">
                        <div
                            class="px-5 py-4 border-b border-gray-100 dark:border-gray-700 bg-gradient-to-br from-gray-50 to-white dark:from-gray-800 dark:to-gray-800/50 flex items-center gap-3">
                            <div class="w-9 h-9 rounded-xl bg-emerald-50 dark:bg-emerald-900/30 flex items-center justify-center">
                                <svg class="w-4 h-4 text-emerald-600 dark:text-emerald-400" fill="none" stroke="currentColor"
                                    viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" />
                                </svg>
                            </div>
                            <h3 class="text-sm font-bold text-gray-900 dark:text-white tracking-tight">Business</h3>
                        </div>
                        <div class="p-5">
                            <a :href="`/admin/businesses/${review.listing?.id}`"
                                class="text-sm font-bold text-primary-600 dark:text-primary-400 hover:text-primary-800 dark:hover:text-primary-300 transition-colors flex items-center gap-1 group">
                                {{ review.listing?.name }}
                                <svg class="w-4 h-4 transform group-hover:translate-x-1 transition-transform"
                                    fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M9 5l7 7-7 7" />
                                </svg>
                            </a>
                            <div class="mt-2 flex items-center gap-3">
                                <div class="flex items-center gap-1">
                                    <span class="text-amber-400 text-sm">★</span>
                                    <span class="text-sm font-bold text-gray-900 dark:text-white">{{ review.listing?.average_rating ||
                                        0
                                    }}</span>
                                </div>
                                <span class="text-gray-300 dark:text-gray-600">•</span>
                                <span class="text-xs text-gray-500 dark:text-gray-400">{{ review.listing?.total_reviews || 0 }}
                                    reviews</span>
                            </div>
                            <div class="mt-2 flex flex-wrap gap-1">
                                <span v-for="category in review.listing?.business?.categories?.slice(0, 2)" :key="category.id"
                                    class="inline-flex items-center px-2 py-0.5 bg-primary-50 dark:bg-primary-900/30 text-primary-700 dark:text-primary-400 text-[10px] font-semibold rounded-full">
                                    {{ category.name }}
                                </span>
                            </div>
                        </div>
                    </div>

                    <!-- Owner info -->
                    <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-sm border border-gray-100 dark:border-gray-700 overflow-hidden">
                        <div
                            class="px-5 py-4 border-b border-gray-100 dark:border-gray-700 bg-gradient-to-br from-gray-50 to-white dark:from-gray-800 dark:to-gray-800/50 flex items-center gap-3">
                            <div class="w-9 h-9 rounded-xl bg-blue-50 dark:bg-blue-900/30 flex items-center justify-center">
                                <svg class="w-4 h-4 text-blue-600 dark:text-blue-400" fill="none" stroke="currentColor"
                                    viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                                </svg>
                            </div>
                            <h3 class="text-sm font-bold text-gray-900 dark:text-white tracking-tight">Business owner</h3>
                        </div>
                        <div class="p-5">
                            <div class="flex items-center gap-3">
                                <div
                                    class="w-10 h-10 rounded-2xl bg-primary-100 dark:bg-primary-900/30 flex items-center justify-center text-primary-600 dark:text-primary-400 font-bold text-sm flex-shrink-0">
                                    {{ getInitials(review.listing?.owner?.name) }}
                                </div>
                                <div class="min-w-0">
                                    <p class="text-sm font-bold text-gray-900 dark:text-white truncate">
                                        {{ review.listing?.owner?.name || 'N/A' }}
                                    </p>
                                    <p class="text-xs text-gray-400 dark:text-gray-500 flex items-center gap-1 truncate">
                                        <svg class="w-3 h-3 flex-shrink-0" fill="none" stroke="currentColor"
                                            viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M16 12a4 4 0 10-8 0 4 4 0 008 0zm0 0v1.5a2.5 2.5 0 005 0V12a9 9 0 10-9 9m4.5-1.206a8.959 8.959 0 01-4.5 1.207" />
                                        </svg>
                                        {{ review.listing?.owner?.email }}
                                    </p>
                                </div>
                            </div>
                            <a :href="`/admin/owners/${review.listing?.owner?.id}`"
                                class="mt-3 inline-flex items-center gap-1 text-xs text-primary-600 dark:text-primary-400 hover:text-primary-800 dark:hover:text-primary-300 transition-colors font-semibold">
                                View owner profile
                                <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M9 5l7 7-7 7" />
                                </svg>
                            </a>
                        </div>
                    </div>

                    <!-- Owner replies -->
                    <div v-if="review.replies && review.replies.length > 0"
                        class="bg-white dark:bg-gray-800 rounded-2xl shadow-sm border border-gray-100 dark:border-gray-700 overflow-hidden">
                        <div
                            class="px-5 py-4 border-b border-gray-100 dark:border-gray-700 bg-gradient-to-br from-gray-50 to-white dark:from-gray-800 dark:to-gray-800/50 flex items-center gap-3">
                            <div class="w-9 h-9 rounded-xl bg-blue-50 dark:bg-blue-900/30 flex items-center justify-center">
                                <svg class="w-4 h-4 text-blue-600 dark:text-blue-400" fill="none" stroke="currentColor"
                                    viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M3 10h10a8 8 0 018 8v2M3 10l6 6m-6-6l6-6" />
                                </svg>
                            </div>
                            <h3 class="text-sm font-bold text-gray-900 dark:text-white tracking-tight">Owner replies</h3>
                            <span class="bg-gray-100 dark:bg-gray-700 text-gray-600 dark:text-gray-300 text-[10px] font-bold px-2 py-0.5 rounded-full">
                                {{ review.replies.length }}
                            </span>
                        </div>
                        <div class="p-5 space-y-4">
                            <div v-for="reply in review.replies" :key="reply.id"
                                class="border-b border-gray-100 dark:border-gray-700 last:border-0 pb-3 last:pb-0">
                                <div class="flex items-start gap-3">
                                    <div
                                        class="w-8 h-8 rounded-2xl bg-blue-100 dark:bg-blue-900/30 flex items-center justify-center text-blue-600 dark:text-blue-400 font-bold text-[10px] flex-shrink-0">
                                        {{ getInitials(reply.user?.name) }}
                                    </div>
                                    <div class="min-w-0">
                                        <p class="text-sm text-gray-700 dark:text-gray-300 leading-relaxed">{{ reply.content }}</p>
                                        <p class="text-xs text-gray-400 dark:text-gray-500 mt-1 flex items-center gap-2">
                                            <span class="font-semibold">{{ reply.user?.name || 'Owner' }}</span>
                                            <span class="text-gray-300 dark:text-gray-600">·</span>
                                            <span>{{ formatDate(reply.created_at) }}</span>
                                        </p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>


<script setup>
    import { router } from '@inertiajs/vue3';
    import { useToast } from '@/composables/useToast';
    import { useConfirm } from '@/composables/useConfirm';
    import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
    import { useStatusBadge } from '@/composables/useStatusBadge';
    import PageHeader from '@/Components/PageHeader.vue';
    import ReviewImageGallery from '@/Components/Public/ReviewImageGallery.vue';

    const props = defineProps({
        review: Object,
    });

    const { error } = useToast();
    const { confirm: confirmDialog } = useConfirm();
    const { review: statusClass } = useStatusBadge();

    const getInitials = (name) => {
        if (!name) return '?';
        return name.split(' ').map(n => n[0]).join('').toUpperCase().slice(0, 2);
    };

    const getRatingLabel = (rating) => {
        const value = Number(rating) || 0;
        if (value >= 4.5) return 'Excellent';
        if (value >= 3.5) return 'Good';
        if (value >= 2.5) return 'Average';
        if (value >= 1.5) return 'Fair';
        return 'Poor';
    };

    const formatDate = (date) => {
        if (!date) return 'N/A';
        return new Date(date).toLocaleDateString('en-US', {
            year: 'numeric',
            month: 'short',
            day: 'numeric',
            hour: '2-digit',
            minute: '2-digit'
        });
    };

    const approveReview = async () => {
        const confirmed = await confirmDialog({
            title: 'Approve review?',
            message: 'This review will become publicly visible on the business profile. The reviewer will be notified.',
            confirmText: 'Approve',
            cancelText: 'Cancel',
            variant: 'primary',
        });

        if (!confirmed) return;

        router.post(`/admin/reviews/${props.review.id}/approve`, {}, {
            preserveScroll: true,
            // ✅ No success toast — the controller flashes
            //    'Review approved successfully.'
            onError: () => {
                error('Approval Failed ❌', 'Failed to approve the review. Please try again.', { duration: 4000 });
            },
        });
    };

    const rejectReview = async () => {
        const confirmed = await confirmDialog({
            title: 'Reject review?',
            message: 'This review will be hidden from the public. The reviewer will be notified.',
            confirmText: 'Reject',
            cancelText: 'Cancel',
            variant: 'danger',
        });

        if (!confirmed) return;

        router.post(`/admin/reviews/${props.review.id}/reject`, {}, {
            preserveScroll: true,
            // ✅ No success toast — the controller flashes
            //    'Review rejected successfully.'
            onError: () => {
                error('Rejection Failed ❌', 'Failed to reject the review. Please try again.', { duration: 4000 });
            },
        });
    };

    const deleteReview = async () => {
        const confirmed = await confirmDialog({
            title: 'Delete review?',
            message: 'This review will be permanently deleted. This action cannot be undone.',
            confirmText: 'Delete',
            cancelText: 'Cancel',
            variant: 'danger',
        });

        if (!confirmed) return;

        router.delete(`/admin/reviews/${props.review.id}`, {
            preserveScroll: true,
            // ✅ No success toast — the controller flashes
            //    'Review deleted successfully.' and redirects to
            //    /admin/reviews where the toast fires on the index.
            onError: () => {
                error('Delete Failed ❌', 'Failed to delete the review. Please try again.', { duration: 4000 });
            },
        });
    };
</script>