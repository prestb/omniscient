<!-- resources/js/Pages/Owner/Reviews/Show.vue -->
<template>
    <AuthenticatedLayout>
        <!-- COMPACT HEADER -->
        <PageHeader color="amber" :breadcrumb="[
            { label: 'Dashboard', href: '/owner/dashboard' },
            { label: 'Reviews', href: `/owner/businesses/${business.id}/reviews` },
            { label: 'Review Details' }
        ]">
            <template #icon>
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                    d="M11.049 2.927c.3-.921 1.603-.921 1.902 0l1.519 4.674a1 1 0 00.95.69h4.915c.969 0 1.371 1.24.588 1.81l-3.976 2.888a1 1 0 00-.363 1.118l1.518 4.674c.3.922-.755 1.688-1.538 1.118l-3.976-2.888a1 1 0 00-1.176 0l-3.976 2.888c-.783.57-1.838-.197-1.538-1.118l1.518-4.674a1 1 0 00-.363-1.118l-3.976-2.888c-.784-.57-.38-1.81.588-1.81h4.914a1 1 0 00.951-.69l1.519-4.674z" />
            </template>
            <template #title>Review Details</template>
            <template #subtitle>{{ business?.name }}</template>
            <template #actions>
                <span :class="statusClass(review.status)"
                    class="inline-flex items-center px-3 py-1.5 text-xs font-bold uppercase tracking-wide rounded-full border border-gray-200 dark:border-gray-600">
                    {{ review.status.charAt(0).toUpperCase() + review.status.slice(1) }}
                </span>
                <a :href="`/owner/businesses/${business.id}/reviews`"
                    class="inline-flex items-center gap-2 px-4 py-2 border border-gray-300 dark:border-gray-600 text-gray-700 dark:text-gray-300 rounded-xl hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors text-sm font-semibold">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M10 19l-7-7m0 0l7-7m-7 7h18" />
                    </svg>
                    Back
                </a>
            </template>
        </PageHeader>

        <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 py-8 space-y-6">
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                <!-- Review Content -->
                <div class="lg:col-span-2">
                    <div
                        class="bg-white dark:bg-gray-800 rounded-2xl shadow-sm border border-gray-100 dark:border-gray-700 overflow-hidden hover:shadow-md transition-shadow">
                        <div
                            class="px-6 py-4 border-b border-gray-100 dark:border-gray-700 bg-gradient-to-r from-gray-50 to-white dark:from-gray-800 dark:to-gray-800/50 flex items-center gap-2">
                            <svg class="w-5 h-5 text-gray-400 dark:text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M11.049 2.927c.3-.921 1.603-.921 1.902 0l1.519 4.674a1 1 0 00.95.69h4.915c.969 0 1.371 1.24.588 1.81l-3.976 2.888a1 1 0 00-.363 1.118l1.518 4.674c.3.922-.755 1.688-1.538 1.118l-3.976-2.888a1 1 0 00-1.176 0l-3.976 2.888c-.783.57-1.838-.197-1.538-1.118l1.518-4.674a1 1 0 00-.363-1.118l-3.976-2.888c-.784-.57-.38-1.81.588-1.81h4.914a1 1 0 00.951-.69l1.519-4.674z" />
                            </svg>
                            <h3 class="text-sm font-semibold text-gray-700 dark:text-gray-300">Review Details</h3>
                            <span class="ml-auto text-xs text-gray-400 dark:text-gray-500">ID: #{{ review.id }}</span>
                        </div>

                        <div class="p-6 space-y-5">
                            <!-- Reviewer -->
                            <div class="flex items-start gap-4">
                                <div
                                    class="w-12 h-12 rounded-2xl bg-gradient-to-br from-primary-400 to-primary-600 flex items-center justify-center text-white text-lg font-bold flex-shrink-0 shadow-lg shadow-primary-100 dark:shadow-none">
                                    {{ getInitials(review.reviewer_name || 'Anonymous') }}
                                </div>
                                <div>
                                    <p class="text-sm font-semibold text-gray-900 dark:text-white">{{ review.reviewer_name ||
                                        'Anonymous' }}</p>
                                    <p class="text-xs text-gray-400 dark:text-gray-500 flex items-center gap-1">
                                        <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M16 12a4 4 0 10-8 0 4 4 0 008 0zm0 0v1.5a2.5 2.5 0 005 0V12a9 9 0 10-9 9m4.5-1.206a8.959 8.959 0 01-4.5 1.207" />
                                        </svg>
                                        {{ review.reviewer_email || 'No email provided' }}
                                    </p>
                                </div>
                            </div>

                            <!-- Rating -->
                            <div class="bg-gray-50 dark:bg-gray-900/50 rounded-xl p-4">
                                <label class="text-xs font-medium text-gray-400 dark:text-gray-500 uppercase tracking-wider">Rating</label>
                                <div class="flex items-center gap-3 mt-0.5">
                                    <div class="flex text-2xl">
                                        <span v-for="i in 5" :key="i" class="transition-all duration-300"
                                            :class="i <= review.rating ? 'text-yellow-400 scale-100' : 'text-gray-200 dark:text-gray-700 scale-90'">
                                            ★
                                        </span>
                                    </div>
                                    <span class="text-sm font-bold text-gray-900 dark:text-white">{{ review.rating }}/5</span>
                                    <span class="text-xs text-gray-400 dark:text-gray-500">{{ getRatingLabel(review.rating) }}</span>
                                </div>
                            </div>

                            <!-- Title -->
                            <div>
                                <label class="text-xs font-medium text-gray-400 dark:text-gray-500 uppercase tracking-wider">Title</label>
                                <p class="text-sm font-semibold text-gray-900 dark:text-white mt-0.5">{{ review.title || 'No title provided' }}
                                </p>
                            </div>

                            <!-- Content -->
                            <div>
                                <label
                                    class="text-xs font-medium text-gray-400 dark:text-gray-500 uppercase tracking-wider">Content</label>
                                <div class="mt-0.5 p-4 bg-gray-50 dark:bg-gray-900/50 rounded-xl border border-gray-100 dark:border-gray-700">
                                    <p class="text-sm text-gray-700 dark:text-gray-300 whitespace-pre-wrap leading-relaxed">{{
                                        review.content }}
                                    </p>
                                </div>
                            </div>

                            <!-- Images -->
                            <div v-if="review.images && review.images.length > 0">
                                <label
                                    class="text-xs font-medium text-gray-400 dark:text-gray-500 uppercase tracking-wider flex items-center gap-1.5 mb-2">
                                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z" />
                                    </svg>
                                    Images
                                </label>
                                <ReviewImageGallery :images="review.images" size="md" />
                            </div>

                            <!-- Submitted -->
                            <div class="pt-3 border-t border-gray-100 dark:border-gray-700">
                                <label
                                    class="text-xs font-medium text-gray-400 dark:text-gray-500 uppercase tracking-wider flex items-center gap-1.5">
                                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                                    </svg>
                                    Submitted
                                </label>
                                <p class="text-sm text-gray-700 dark:text-gray-300 mt-0.5">{{ formatDate(review.created_at) }}</p>
                            </div>

                            <!-- Status Info -->
                            <div v-if="review.approved_at" class="pt-3 border-t border-gray-100 dark:border-gray-700">
                                <label
                                    class="text-xs font-medium text-gray-400 dark:text-gray-500 uppercase tracking-wider">Approved</label>
                                <p class="text-sm text-gray-700 dark:text-gray-300 mt-0.5 flex items-center gap-2">
                                    <svg class="w-4 h-4 text-green-500 dark:text-green-400" fill="none" stroke="currentColor"
                                        viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M5 13l4 4L19 7" />
                                    </svg>
                                    {{ formatDate(review.approved_at) }}
                                </p>
                            </div>
                            <div v-if="review.rejected_at" class="pt-3 border-t border-gray-100 dark:border-gray-700">
                                <label
                                    class="text-xs font-medium text-gray-400 dark:text-gray-500 uppercase tracking-wider">Rejected</label>
                                <p class="text-sm text-gray-700 dark:text-gray-300 mt-0.5 flex items-center gap-2">
                                    <svg class="w-4 h-4 text-red-500 dark:text-red-400" fill="none" stroke="currentColor"
                                        viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M6 18L18 6M6 6l12 12" />
                                    </svg>
                                    {{ formatDate(review.rejected_at) }}
                                </p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Sidebar -->
                <div class="lg:col-span-1 space-y-6">
                    <!-- Reply Form -->
                    <div
                        class="bg-white dark:bg-gray-800 rounded-2xl shadow-sm border border-gray-100 dark:border-gray-700 overflow-hidden hover:shadow-md transition-shadow sticky top-6">
                        <div
                            class="px-6 py-4 border-b border-gray-100 dark:border-gray-700 bg-gradient-to-r from-gray-50 to-white dark:from-gray-800 dark:to-gray-800/50 flex items-center gap-2">
                            <svg class="w-5 h-5 text-gray-400 dark:text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M3 10h10a8 8 0 018 8v2M3 10l6 6m-6-6l6-6" />
                            </svg>
                            <h3 class="text-sm font-semibold text-gray-700 dark:text-gray-300">Reply to Review</h3>
                        </div>
                        <div class="p-6">
                            <div v-if="review.status !== 'approved'"
                                class="mb-4 p-3 bg-yellow-50 dark:bg-yellow-900/20 rounded-xl border border-yellow-200 dark:border-yellow-800 text-sm text-yellow-700 dark:text-yellow-400">
                                ⚠️ You can only reply to approved reviews.
                            </div>
                            <form @submit.prevent="submitReply">
                                <div class="mb-4">
                                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">Your Reply</label>

                                    <!-- ✅ Template chips -->
                                    <div class="flex flex-wrap gap-1.5 mb-2.5">
                                        <button v-for="template in replyTemplates" :key="template.key" type="button"
                                            @click="applyTemplate(template)"
                                            :disabled="review.status !== 'approved' || replying"
                                            class="inline-flex items-center gap-1 px-2.5 py-1 bg-gray-50 dark:bg-gray-700 hover:bg-primary-50 dark:hover:bg-primary-900/30 text-gray-700 dark:text-gray-300 hover:text-primary-700 dark:hover:text-primary-400 border border-gray-200 dark:border-gray-600 hover:border-primary-300 dark:hover:border-primary-700 rounded-lg text-[11px] font-semibold transition-colors disabled:opacity-50 disabled:cursor-not-allowed"
                                            :title="'Insert: ' + template.content">
                                            <span>{{ template.icon }}</span>
                                            {{ template.label }}
                                        </button>
                                    </div>

                                    <div class="relative">
                                        <div class="absolute top-3 left-3 flex items-start pointer-events-none">
                                            <svg class="h-5 w-5 text-gray-400 dark:text-gray-500" fill="none" stroke="currentColor"
                                                viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                    d="M4 6h16M4 12h16M4 18h7" />
                                            </svg>
                                        </div>
                                        <textarea v-model="replyContent" rows="4" maxlength="500"
                                            class="w-full pl-10 pr-4 py-2.5 border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-900 text-gray-900 dark:text-white placeholder-gray-400 dark:placeholder-gray-500 rounded-xl focus:outline-none focus:ring-2 focus:ring-primary-500 focus:border-transparent transition-colors resize-none"
                                            placeholder="Write your reply to this review..."
                                            :disabled="review.status !== 'approved' || replying" required></textarea>
                                    </div>
                                    <div class="flex justify-between mt-1">
                                        <p class="text-xs text-gray-400 dark:text-gray-500">Be professional and courteous</p>
                                        <span class="text-xs text-gray-400 dark:text-gray-500">{{ replyContent ? replyContent.length : 0 }}
                                            /
                                            500</span>
                                    </div>
                                </div>
                                <button type="submit"
                                    class="w-full inline-flex items-center justify-center gap-2 px-4 py-2.5 bg-gradient-to-r from-primary-600 to-primary-700 text-white rounded-xl hover:from-primary-700 hover:to-primary-800 transition-all duration-200 shadow-lg shadow-primary-100 dark:shadow-none hover:shadow-primary-200 font-medium text-sm disabled:opacity-50 disabled:cursor-not-allowed"
                                    :disabled="review.status !== 'approved' || replying || !replyContent.trim()">
                                    <span v-if="replying" class="flex items-center gap-2">
                                        <svg class="animate-spin h-4 w-4" fill="none" viewBox="0 0 24 24">
                                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor"
                                                stroke-width="4"></circle>
                                            <path class="opacity-75" fill="currentColor"
                                                d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z">
                                            </path>
                                        </svg>
                                        Sending...
                                    </span>
                                    <span v-else>
                                        <svg class="w-4 h-4 inline mr-1.5" fill="none" stroke="currentColor"
                                            viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M3 10h10a8 8 0 018 8v2M3 10l6 6m-6-6l6-6" />
                                        </svg>
                                        Send Reply
                                    </span>
                                </button>
                            </form>
                        </div>
                    </div>

                    <!-- Existing Replies -->
                    <div v-if="review.replies && review.replies.length > 0"
                        class="bg-white dark:bg-gray-800 rounded-2xl shadow-sm border border-gray-100 dark:border-gray-700 overflow-hidden hover:shadow-md transition-shadow">
                        <div
                            class="px-6 py-4 border-b border-gray-100 dark:border-gray-700 bg-gradient-to-r from-gray-50 to-white dark:from-gray-800 dark:to-gray-800/50 flex items-center gap-2">
                            <svg class="w-5 h-5 text-gray-400 dark:text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M3 10h10a8 8 0 018 8v2M3 10l6 6m-6-6l6-6" />
                            </svg>
                            <h3 class="text-sm font-semibold text-gray-700 dark:text-gray-300">Your Replies</h3>
                            <span class="bg-gray-100 dark:bg-gray-700 text-gray-600 dark:text-gray-300 text-xs font-medium px-2.5 py-0.5 rounded-full">
                                {{ review.replies.length }}
                            </span>
                        </div>
                        <div class="p-6 space-y-4">
                            <div v-for="reply in review.replies" :key="reply.id"
                                class="border-b border-gray-100 dark:border-gray-700 last:border-0 pb-4 last:pb-0">
                                <div class="flex items-center gap-2">
                                    <div
                                        class="w-8 h-8 rounded-full bg-primary-100 dark:bg-primary-900/40 flex items-center justify-center text-primary-600 dark:text-primary-400 font-semibold text-xs flex-shrink-0">
                                        {{ getInitials(business?.owner?.name || 'You') }}
                                    </div>
                                    <div>
                                        <span class="text-xs font-medium text-gray-700 dark:text-gray-300">You</span>
                                        <span class="text-xs text-gray-400 dark:text-gray-500 ml-2">{{ formatDate(reply.created_at)
                                            }}</span>
                                    </div>
                                </div>
                                <p class="text-sm text-gray-700 dark:text-gray-300 mt-1.5 leading-relaxed pl-10">{{ reply.content }}</p>
                            </div>
                        </div>
                    </div>

                    <!-- PHASE 21D - organization context is OPTIONAL. A
                         Business-less Professional is a first-class Listing, so this
                         card is omitted entirely rather than rendering an empty shell
                         with a /business/undefined link. -->
                    <div v-if="business"
                        class="bg-white dark:bg-gray-800 rounded-2xl shadow-sm border border-gray-100 dark:border-gray-700 overflow-hidden hover:shadow-md transition-shadow">
                        <div
                            class="px-6 py-4 border-b border-gray-100 dark:border-gray-700 bg-gradient-to-r from-gray-50 to-white dark:from-gray-800 dark:to-gray-800/50 flex items-center gap-2">
                            <svg class="w-5 h-5 text-gray-400 dark:text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" />
                            </svg>
                            <h3 class="text-sm font-semibold text-gray-700 dark:text-gray-300">Business</h3>
                        </div>
                        <div class="p-6">
                            <a :href="`/business/${business?.slug}`" target="_blank"
                                class="text-sm font-semibold text-primary-600 dark:text-primary-400 hover:text-primary-800 dark:hover:text-primary-300 transition-colors flex items-center gap-1 group">
                                {{ business?.name }}
                                <svg class="w-4 h-4 transform group-hover:translate-x-1 transition-transform"
                                    fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M9 5l7 7-7 7" />
                                </svg>
                            </a>
                            <div class="mt-2 flex items-center gap-3">
                                <div class="flex items-center gap-1">
                                    <span class="text-yellow-400 text-sm">★</span>
                                    <span class="text-sm font-semibold text-gray-900 dark:text-white">{{ business?.average_rating || 0
                                        }}</span>
                                </div>
                                <span class="text-xs text-gray-300 dark:text-gray-600">•</span>
                                <span class="text-xs text-gray-500 dark:text-gray-400">{{ business?.total_reviews || 0 }} reviews</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>

<script setup>
    import { ref } from 'vue';
    import { router } from '@inertiajs/vue3';
    import { useToast } from '@/composables/useToast';
    import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
    import { useStatusBadge } from '@/composables/useStatusBadge';
    import PageHeader from '@/Components/PageHeader.vue';
    import ReviewImageGallery from '@/Components/Public/ReviewImageGallery.vue';
    const props = defineProps({
        business: Object,
        review: Object,
    });

    const { error } = useToast();
    const { review: statusClass } = useStatusBadge();
    const replyContent = ref('');
    const replying = ref(false);

    // ============== REPLY TEMPLATES ==============
    // Quick-insert snippets for common review scenarios.
    // Inserted as-is into the textarea; owner can edit before submitting.
    const replyTemplates = [
        {
            key: 'positive',
            label: 'Thank positively',
            icon: '😊',
            content: "Thank you so much for the kind words! We're glad you enjoyed your experience and look forward to seeing you again.",
        },
        {
            key: 'negative',
            label: 'Apologize',
            icon: '🙏',
            content: "We're sorry to hear about your experience. We'd love to make this right — please reach out to us directly so we can resolve it.",
        },
        {
            key: 'neutral',
            label: 'Thank for feedback',
            icon: '💬',
            content: 'Thanks for taking the time to leave a review. We appreciate the feedback and will use it to improve.',
        },
        {
            key: 'issue',
            label: 'Address an issue',
            icon: '🔧',
            content: 'We apologize for the inconvenience. Please contact us so we can resolve this for you.',
        },
    ];

    const applyTemplate = (template) => {
        // Replace the textarea content with the template.
        // If there's existing text, append with a space separator.
        const existing = replyContent.value.trim();
        if (existing) {
            replyContent.value = existing + '\n\n' + template.content;
        } else {
            replyContent.value = template.content;
        }
    };

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

    const submitReply = () => {
        if (!replyContent.value.trim()) {
            error('Reply Required ✏️', 'Please enter your reply before submitting.', { duration: 3000 });
            return;
        }

        replying.value = true;
        router.post(`/owner/businesses/${props.business.id}/reviews/${props.review.id}/reply`, {
            content: replyContent.value
        }, {
            preserveScroll: true,
            onFinish: () => {
                replying.value = false;
            },
            // ✅ No success toast — the controller flashes
            //    'Reply added successfully.' and AuthenticatedLayout
            //    shows it once. Also no router.reload() — the
            //    controller's redirect()->back() already re-renders
            //    the page with the new reply.
            onSuccess: () => {
                // Reset the textarea so the user can see the empty form
                // after the page re-renders with their new reply visible.
                replyContent.value = '';
            },
            onError: (errors) => {
                // Client-side fallback: usually validation or plan-gate
                // errors that don't come back as a flash.
                const errorMsg = errors?.content || 'Failed to send reply. Please try again.';
                error(
                    'Reply Failed ❌',
                    errorMsg,
                    { duration: 4000 }
                );
            },
        });
    };
</script>