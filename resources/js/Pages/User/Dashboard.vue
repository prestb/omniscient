<template>
    <AuthenticatedLayout>
        <template #header>
            <div>
                <h1 class="text-2xl font-bold text-gray-900 dark:text-white">
                    Welcome back, {{ user.name }} 👋
                </h1>
                <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">
                    Manage your favorites, reviews, and account
                </p>
            </div>
        </template>

        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-6 space-y-6">
            <!-- Stats -->
            <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                <a href="/favorites"
                   class="bg-white dark:bg-gray-800 rounded-2xl shadow-sm border border-gray-100 dark:border-gray-700 p-5 hover:shadow-md hover:border-red-200 dark:hover:border-red-800 transition-all">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-sm text-gray-500 dark:text-gray-400">Favorites</p>
                            <p class="text-3xl font-black text-gray-900 dark:text-white mt-1">
                                {{ stats.favorites }}
                            </p>
                        </div>
                        <div class="w-14 h-14 rounded-2xl bg-gradient-to-br from-red-500 to-pink-600 flex items-center justify-center shadow-lg shadow-red-500/20">
                            <svg class="w-7 h-7 text-white" fill="currentColor" viewBox="0 0 24 24">
                                <path d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z" />
                            </svg>
                        </div>
                    </div>
                </a>

                <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-sm border border-gray-100 dark:border-gray-700 p-5">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-sm text-gray-500 dark:text-gray-400">My Reviews</p>
                            <p class="text-3xl font-black text-gray-900 dark:text-white mt-1">
                                {{ stats.reviews }}
                            </p>
                        </div>
                        <div class="w-14 h-14 rounded-2xl bg-gradient-to-br from-amber-400 to-orange-500 flex items-center justify-center shadow-lg shadow-amber-500/20">
                            <svg class="w-7 h-7 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11.049 2.927c.3-.921 1.603-.921 1.902 0l1.519 4.674a1 1 0 00.95.69h4.915c.969 0 1.371 1.24.588 1.81l-3.976 2.888a1 1 0 00-.363 1.118l1.518 4.674c.3.922-.755 1.688-1.538 1.118l-3.976-2.888a1 1 0 00-1.176 0l-3.976 2.888c-.783.57-1.838-.197-1.538-1.118l1.518-4.674a1 1 0 00-.363-1.118l-3.976-2.888c-.784-.57-.38-1.81.588-1.81h4.914a1 1 0 00.951-.69l1.519-4.674z" />
                            </svg>
                        </div>
                    </div>
                </div>

                <a href="/profile"
                   class="bg-white dark:bg-gray-800 rounded-2xl shadow-sm border border-gray-100 dark:border-gray-700 p-5 hover:shadow-md transition-all">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-sm text-gray-500 dark:text-gray-400">Notifications</p>
                            <p class="text-3xl font-black text-gray-900 dark:text-white mt-1">
                                {{ stats.notifications }}
                            </p>
                        </div>
                        <div class="w-14 h-14 rounded-2xl bg-gradient-to-br from-blue-500 to-cyan-600 flex items-center justify-center shadow-lg shadow-blue-500/20">
                            <svg class="w-7 h-7 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9" />
                            </svg>
                        </div>
                    </div>
                </a>
            </div>


            <!-- ✅ Business Owner CTA / Pending Business State -->
<!-- CASE 1: User has a business (draft/submitted/approved/published) -->
<div v-if="userBusiness" 
     class="bg-gradient-to-r from-blue-50 to-cyan-50 dark:from-blue-950/30 dark:to-cyan-950/30 border-2 border-blue-200 dark:border-blue-800 rounded-2xl p-6">
    <div class="flex flex-col sm:flex-row items-start gap-4">
        <div class="w-14 h-14 rounded-2xl bg-gradient-to-br from-blue-500 to-cyan-600 flex items-center justify-center shadow-lg shadow-blue-500/30 flex-shrink-0">
            <svg class="w-7 h-7 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" />
            </svg>
        </div>
        <div class="flex-1 min-w-0">
            <div class="flex items-center gap-2 flex-wrap mb-1">
                <h3 class="font-bold text-gray-900 dark:text-white text-lg truncate">
                    {{ userBusiness.name }}
                </h3>
                <span :class="getBusinessStatusBadge(userBusiness.status)" 
                      class="text-[10px] font-bold px-2.5 py-0.5 rounded-full uppercase tracking-wider flex-shrink-0">
                    {{ userBusiness.status }}
                </span>
            </div>
            <p class="text-sm text-gray-600 dark:text-gray-400">
                {{ getBusinessMessage(userBusiness) }}
            </p>
            <div class="mt-3 flex flex-wrap gap-2">
                <a v-if="userBusiness.status === 'draft'"
                   :href="`/owner/businesses/${userBusiness.id}/edit`"
                   class="inline-flex items-center gap-2 px-5 py-2.5 bg-gradient-to-r from-blue-500 to-cyan-600 text-white rounded-xl font-semibold hover:from-blue-600 hover:to-cyan-700 transition-all shadow-lg shadow-blue-500/30 text-sm">
                    Continue Setup
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7l5 5m0 0l-5 5m5-5H6" />
                    </svg>
                </a>
                <a v-if="userBusiness.status === 'published'"
                   href="/owner/dashboard"
                   class="inline-flex items-center gap-2 px-5 py-2.5 bg-gradient-to-r from-green-500 to-emerald-600 text-white rounded-xl font-semibold hover:from-green-600 hover:to-emerald-700 transition-all shadow-lg shadow-green-500/30 text-sm">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2V6zM14 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2V6zM4 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2v-2zM14 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2v-2z" />
                    </svg>
                    Go to Owner Dashboard
                </a>
            </div>
        </div>
    </div>
</div>

<!-- CASE 2: User has NO business — show "Own a business?" CTA -->
<div v-else 
     class="bg-gradient-to-r from-amber-50 via-yellow-50 to-orange-50 dark:from-amber-950/30 dark:via-yellow-950/30 dark:to-orange-950/30 border-2 border-amber-200 dark:border-amber-800 rounded-2xl p-6">
    <div class="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4">
        <div class="flex items-start gap-4">
            <div class="w-14 h-14 rounded-2xl bg-gradient-to-br from-amber-500 to-orange-500 flex items-center justify-center shadow-lg shadow-amber-500/30 flex-shrink-0">
                <svg class="w-7 h-7 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" />
                </svg>
            </div>
            <div>
                <h3 class="font-bold text-gray-900 dark:text-white text-lg">
                    Own a business? Get listed for free.
                </h3>
                <p class="text-sm text-gray-600 dark:text-gray-400 mt-1 max-w-lg">
                    Join thousands of businesses on Omniscient and reach new customers in your area.
                </p>
                <div class="flex flex-wrap gap-x-4 gap-y-1 mt-2 text-xs text-amber-700 dark:text-amber-400">
                    <span class="flex items-center gap-1">✓ Free listing</span>
                    <span class="flex items-center gap-1">✓ Analytics included</span>
                    <span class="flex items-center gap-1">✓ Customer reviews</span>
                </div>
            </div>
        </div>
        <a href="/owner/businesses/create"
           class="inline-flex items-center gap-2 px-6 py-3 bg-gradient-to-r from-amber-500 to-orange-500 text-white rounded-xl font-semibold hover:from-amber-600 hover:to-orange-600 transition-all shadow-lg shadow-amber-500/30 whitespace-nowrap">
            List Your Business
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7l5 5m0 0l-5 5m5-5H6" />
            </svg>
        </a>
    </div>
</div>

            <!-- Quick Links -->
            <div class="bg-gradient-to-r from-primary-600 via-primary-700 to-purple-700 rounded-2xl p-6 text-white shadow-lg">
                <h2 class="text-lg font-bold mb-3">Quick Actions</h2>
                <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
                    <a href="/directory"
                       class="bg-white/10 hover:bg-white/20 backdrop-blur-sm rounded-xl p-4 text-center transition-all">
                        <svg class="w-7 h-7 mx-auto mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                        </svg>
                        <span class="text-sm font-medium">Browse Businesses</span>
                    </a>
                    <a href="/favorites"
                       class="bg-white/10 hover:bg-white/20 backdrop-blur-sm rounded-xl p-4 text-center transition-all">
                        <svg class="w-7 h-7 mx-auto mb-2" fill="currentColor" viewBox="0 0 24 24">
                            <path d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z" />
                        </svg>
                        <span class="text-sm font-medium">My Favorites</span>
                    </a>
                    <a href="/profile"
                       class="bg-white/10 hover:bg-white/20 backdrop-blur-sm rounded-xl p-4 text-center transition-all">
                        <svg class="w-7 h-7 mx-auto mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                        </svg>
                        <span class="text-sm font-medium">Edit Profile</span>
                    </a>
                </div>
            </div>

            <!-- Recent Favorites -->
            <div v-if="recentFavorites.length > 0" class="bg-white dark:bg-gray-800 rounded-2xl shadow-sm border border-gray-100 dark:border-gray-700 overflow-hidden">
                <div class="px-6 py-4 border-b border-gray-100 dark:border-gray-700 flex items-center justify-between">
                    <h3 class="text-sm font-semibold text-gray-900 dark:text-white flex items-center gap-2">
                        <span>❤️</span>
                        Recent Favorites
                    </h3>
                    <a href="/favorites" class="text-xs font-semibold text-primary-600 hover:text-primary-800">
                        View All →
                    </a>
                </div>
                <div class="p-6 grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
                    <a v-for="business in recentFavorites" :key="business.id"
                       :href="`/business/${business.slug}`"
                       class="flex items-center gap-3 p-3 rounded-xl bg-gray-50 dark:bg-gray-900/50 hover:bg-gray-100 dark:hover:bg-gray-700/50 transition-colors">
                        <div class="w-12 h-12 rounded-xl bg-primary-100 dark:bg-primary-900/30 flex items-center justify-center font-bold text-primary-600 flex-shrink-0">
                            {{ business.name?.charAt(0) || '?' }}
                        </div>
                        <div class="min-w-0">
                            <p class="text-sm font-semibold text-gray-900 dark:text-white truncate">
                                {{ business.name }}
                            </p>
                            <p class="text-xs text-gray-500 dark:text-gray-400 truncate">
                                {{ business.primary_branch?.city?.name || 'View business' }}
                            </p>
                        </div>
                    </a>
                </div>
            </div>

            <!-- Recent Reviews -->
            <div v-if="recentReviews.length > 0" class="bg-white dark:bg-gray-800 rounded-2xl shadow-sm border border-gray-100 dark:border-gray-700 overflow-hidden">
                <div class="px-6 py-4 border-b border-gray-100 dark:border-gray-700">
                    <h3 class="text-sm font-semibold text-gray-900 dark:text-white flex items-center gap-2">
                        <span>⭐</span>
                        My Recent Reviews
                    </h3>
                </div>
                <div class="divide-y divide-gray-100 dark:divide-gray-700">
                    <div v-for="review in recentReviews" :key="review.id"
                         class="px-6 py-4">
                        <div class="flex items-start gap-3">
                            <div class="flex-1 min-w-0">
                                <div class="flex items-center gap-2 flex-wrap">
                                    <a :href="`/business/${review.business?.slug}`"
                                       class="text-sm font-semibold text-primary-600 hover:text-primary-800">
                                        {{ review.business?.name }}
                                    </a>
                                    <div class="flex text-yellow-400 text-xs">
                                        <span v-for="i in 5" :key="i">
                                            {{ i <= review.rating ? '★' : '☆' }}
                                        </span>
                                    </div>
                                </div>
                                <p class="text-sm text-gray-600 dark:text-gray-400 mt-1 line-clamp-2">
                                    {{ review.content }}
                                </p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Empty state for new users -->
            <div v-if="recentFavorites.length === 0 && recentReviews.length === 0"
                 class="bg-white dark:bg-gray-800 rounded-2xl shadow-sm border border-gray-100 dark:border-gray-700 p-12 text-center">
                <div class="w-20 h-20 rounded-full bg-primary-50 dark:bg-primary-900/30 flex items-center justify-center mx-auto mb-4">
                    <span class="text-4xl">🎉</span>
                </div>
                <h3 class="text-xl font-bold text-gray-900 dark:text-white mb-2">
                    Welcome to Omniscient!
                </h3>
                <p class="text-sm text-gray-500 dark:text-gray-400 max-w-md mx-auto mb-6">
                    Start by exploring the directory, saving your favorite businesses, and leaving reviews to help others.
                </p>
                <a href="/directory"
                   class="inline-flex items-center gap-2 px-6 py-3 bg-primary-600 text-white rounded-xl font-semibold hover:bg-primary-700 transition-colors shadow-lg shadow-primary-500/30">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                    </svg>
                    Explore Directory
                </a>
            </div>
        </div>
    </AuthenticatedLayout>
</template>

<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';

const props = defineProps({
    user: Object,
    stats: Object,
    recentFavorites: Array,
    recentReviews: Array,
    userBusiness: Object, // ✅ NEW
});

const getBusinessStatusBadge = (status) => {
    const classes = {
        draft: 'bg-gray-100 text-gray-700 dark:bg-gray-700 dark:text-gray-300',
        submitted: 'bg-yellow-100 text-yellow-700 dark:bg-yellow-900/30 dark:text-yellow-400',
        approved: 'bg-blue-100 text-blue-700 dark:bg-blue-900/30 dark:text-blue-400',
        published: 'bg-green-100 text-green-700 dark:bg-green-900/30 dark:text-green-400',
        rejected: 'bg-red-100 text-red-700 dark:bg-red-900/30 dark:text-red-400',
        suspended: 'bg-red-100 text-red-700 dark:bg-red-900/30 dark:text-red-400',
    };
    return classes[status] || classes.draft;
};

const getBusinessMessage = (business) => {
    if (!business) return '';
    switch (business.status) {
        case 'draft':
            return 'Your business is still a draft. Complete the setup and submit for review.';
        case 'submitted':
            return '⏳ Your business is pending admin review. We\'ll notify you within 24-48 hours.';
        case 'approved':
            return '✅ Your business is approved! Set up your subscription to make it live.';
        case 'published':
            return '🎉 Your business is live! Manage it from your owner dashboard.';
        case 'rejected':
            return `❌ Your business was rejected. ${business.rejection_reason || 'Check the details and resubmit.'}`;
        default:
            return 'Manage your business profile.';
    }
};
</script>