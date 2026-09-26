<!-- resources/js/Pages/Public/BusinessProfile.vue -->
<template>
    <PublicLayout>
        <!-- ==================== COVER HERO ==================== -->
        <div class="relative h-80 md:h-[440px] lg:h-[500px] bg-gray-900 overflow-hidden">
            <OptimizedImage v-if="business.cover_image" :path="business.cover_image" size="large" :alt="business.name"
                img-class="w-full h-full object-cover"
                fallback-class="w-full h-full bg-gradient-to-br from-primary-700 via-primary-600 to-primary-800" />
            <div v-else class="w-full h-full bg-gradient-to-br from-primary-700 via-primary-600 to-primary-800"></div>

            <!-- Gradient overlay -->
            <div class="absolute inset-0 bg-gradient-to-t from-black/80 via-black/30 to-black/10"></div>

            <!-- Decorative dot pattern -->
            <div class="absolute inset-0 opacity-[0.06]"
                style="background-image: radial-gradient(circle, white 1px, transparent 1px); background-size: 22px 22px;">
            </div>

            <!-- Status badges (top right) -->
            <!-- ✅ Favorite heart (top left) -->
            <div class="absolute top-4 left-4 z-10">
                <button type="button" @click.stop="toggleFavorite" :disabled="favProcessing"
                    :aria-label="isFavorited ? 'Remove from favorites' : 'Add to favorites'" :class="[
                        'inline-flex items-center justify-center gap-1.5 px-3 py-1.5 rounded-full backdrop-blur-sm shadow-lg border transition-all active:scale-95',
                        isFavorited
                            ? 'bg-white/95 border-white/40 text-red-500 hover:bg-white'
                            : 'bg-black/30 border-white/20 text-white hover:bg-black/50',
                        favProcessing ? 'opacity-50 cursor-wait' : 'cursor-pointer'
                    ]">
                    <svg class="w-4 h-4 transition-transform" :class="isFavorited ? 'scale-110' : 'scale-100'"
                        :fill="isFavorited ? 'currentColor' : 'none'" stroke="currentColor" stroke-width="2"
                        viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round"
                            d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z" />
                    </svg>
                    <span class="hidden sm:inline text-xs font-bold uppercase tracking-wide">
                        {{ isFavorited ? 'Saved' : 'Save' }}
                    </span>
                </button>
            </div>

            <!-- Status badges (top right) -->
            <div class="absolute top-4 right-4 flex flex-wrap gap-2 justify-end max-w-[90%]">
                <div v-if="business.feature_flags?.verified_badge"
                    class="px-3 py-1.5 bg-blue-600/90 backdrop-blur-sm text-white text-xs font-bold uppercase tracking-wide rounded-full flex items-center gap-1.5 shadow-lg shadow-blue-500/30 border border-blue-400/40">
                    <svg class="w-3.5 h-3.5" viewBox="0 0 24 24" fill="currentColor">
                        <path fill-rule="evenodd"
                            d="M8.603 3.799A4.49 4.49 0 0112 2.25c1.357 0 2.573.6 3.397 1.549a4.49 4.49 0 013.498 1.307 4.491 4.491 0 011.307 3.497A4.49 4.49 0 0121.75 12a4.49 4.49 0 01-1.549 3.397 4.491 4.491 0 01-1.307 3.497 4.491 4.491 0 01-3.497 1.307A4.49 4.49 0 0112 21.75a4.49 4.49 0 01-3.397-1.549 4.49 4.49 0 01-3.498-1.306 4.491 4.491 0 01-1.307-3.498A4.49 4.49 0 012.25 12c0-1.357.6-2.573 1.549-3.397a4.49 4.49 0 011.307-3.497 4.49 4.49 0 013.497-1.307zm7.007 6.387a.75.75 0 10-1.22-.872l-3.236 4.53L9.53 12.22a.75.75 0 00-1.06 1.06l2.25 2.25a.75.75 0 001.14-.094l3.75-5.25z"
                            clip-rule="evenodd" />
                    </svg>
                    Verified
                </div>
                <div v-if="business.is_featured"
                    class="px-3 py-1.5 bg-gradient-to-r from-amber-400 to-yellow-500 text-amber-900 text-xs font-bold uppercase tracking-wide rounded-full flex items-center gap-1.5 shadow-lg shadow-amber-500/30">
                    <svg class="w-3.5 h-3.5" fill="currentColor" viewBox="0 0 20 20">
                        <path
                            d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z" />
                    </svg>
                    Featured
                </div>
                <div v-if="isOpen"
                    class="px-3 py-1.5 bg-emerald-500 text-white text-xs font-bold uppercase tracking-wide rounded-full flex items-center gap-1.5 shadow-lg shadow-emerald-500/30">
                    <span class="w-1.5 h-1.5 bg-white rounded-full animate-pulse"></span>
                    Open Now
                </div>
                <div v-else-if="business.status === 'published'"
                    class="px-3 py-1.5 bg-gray-700/90 backdrop-blur-sm text-white text-xs font-bold uppercase tracking-wide rounded-full shadow-lg">
                    Closed
                </div>
            </div>

            <!-- Business info overlay (bottom) -->
            <div class="absolute bottom-0 left-0 right-0 p-6 md:p-8">
                <div class="max-w-7xl mx-auto flex items-end gap-6">
                    <!-- Logo -->
                    <div
                        class="w-24 h-24 md:w-32 md:h-32 rounded-3xl bg-white shadow-2xl border-4 border-white flex items-center justify-center overflow-hidden flex-shrink-0">
                        <OptimizedImage v-if="business.logo" :path="business.logo" size="thumb" :alt="business.name"
                            img-class="w-full h-full object-contain p-3"
                            fallback-class="text-3xl md:text-4xl font-bold text-primary-600 tracking-tight"
                            :name="business.name" />
                        <span v-else class="text-3xl md:text-4xl font-bold text-primary-600 tracking-tight">
                            {{ getInitials(business.name) }}
                        </span>
                    </div>

                    <div class="text-white min-w-0 flex-1">
                        <h1
                            class="text-2xl md:text-4xl font-bold tracking-tight drop-shadow-lg flex items-center gap-2 flex-wrap">
                            {{ business.name }}
                            <svg v-if="business.feature_flags?.verified_badge"
                                class="w-6 h-6 md:w-7 md:h-7 text-blue-400 drop-shadow-lg flex-shrink-0"
                                viewBox="0 0 24 24" fill="currentColor">
                                <path fill-rule="evenodd"
                                    d="M8.603 3.799A4.49 4.49 0 0112 2.25c1.357 0 2.573.6 3.397 1.549a4.49 4.49 0 013.498 1.307 4.491 4.491 0 011.307 3.497A4.49 4.49 0 0121.75 12a4.49 4.49 0 01-1.549 3.397 4.491 4.491 0 01-1.307 3.497 4.491 4.491 0 01-3.497 1.307A4.49 4.49 0 0112 21.75a4.49 4.49 0 01-3.397-1.549 4.49 4.49 0 01-3.498-1.306 4.491 4.491 0 01-1.307-3.498A4.49 4.49 0 012.25 12c0-1.357.6-2.573 1.549-3.397a4.49 4.49 0 011.307-3.497 4.49 4.49 0 013.497-1.307zm7.007 6.387a.75.75 0 10-1.22-.872l-3.236 4.53L9.53 12.22a.75.75 0 00-1.06 1.06l2.25 2.25a.75.75 0 001.14-.094l3.75-5.25z"
                                    clip-rule="evenodd" />
                            </svg>
                        </h1>



                        <div class="flex flex-wrap items-center gap-3 mt-3">
                            <span class="text-white/90 text-sm font-semibold">
                                {{ getPrimaryCategory() }}
                            </span>
                            <span class="text-white/40">•</span>
                            <div class="flex items-center gap-2">
                                <div class="flex text-amber-400 text-sm">
                                    <span v-for="i in 5" :key="i">
                                        {{ i <= Math.round(business.average_rating || 0) ? "★" : "☆" }} </span>
                                </div>
                                <span class="text-white/90 text-sm font-semibold">
                                    {{ business.average_rating || 0 }}
                                </span>
                                <span class="text-white/70 text-xs">
                                    ({{ business.reviews_count || 0 }} reviews)
                                </span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- ==================== MAIN CONTENT ==================== -->
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
            <!-- Back link -->
            <a href="/directory"
                class="inline-flex items-center gap-2 text-sm text-gray-600 dark:text-gray-400 hover:text-primary-600 dark:hover:text-primary-400 transition-colors mb-6 bg-white dark:bg-gray-800 px-4 py-2 rounded-xl shadow-sm border border-gray-100 dark:border-gray-700 font-medium">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M10 19l-7-7m0 0l7-7m-7 7h18" />
                </svg>
                Back to Directory
            </a>

            <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
                <!-- ==================== LEFT COLUMN ==================== -->
                <div class="lg:col-span-2 space-y-6">

                    <!-- ABOUT -->
                    <div
                        class="bg-white dark:bg-gray-800 rounded-2xl border border-gray-100 dark:border-gray-700 p-6 md:p-8">
                        <div class="flex items-center gap-3 mb-5">
                            <div
                                class="w-10 h-10 rounded-xl bg-primary-50 dark:bg-primary-900/30 flex items-center justify-center">
                                <svg class="w-5 h-5 text-primary-600 dark:text-primary-400" fill="none"
                                    stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                </svg>
                            </div>
                            <h2 class="text-lg font-bold text-gray-900 dark:text-white tracking-tight">About</h2>
                        </div>
                        <ExpandableText :text="business.description || 'No description available.'" :lines="4"
                            text-class="text-gray-600 dark:text-gray-400 leading-relaxed" />
                    </div>

                    <!-- SERVICES -->
                    <div v-if="business.services && business.services.length > 0"
                        class="bg-white dark:bg-gray-800 rounded-2xl border border-gray-100 dark:border-gray-700 p-6 md:p-8">
                        <div class="flex items-center gap-3 mb-5">
                            <div
                                class="w-10 h-10 rounded-xl bg-blue-50 dark:bg-blue-900/30 flex items-center justify-center">
                                <svg class="w-5 h-5 text-blue-600 dark:text-blue-400" fill="none" stroke="currentColor"
                                    viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10" />
                                </svg>
                            </div>
                            <h2 class="text-lg font-bold text-gray-900 dark:text-white tracking-tight">Services &
                                Products</h2>
                            <span
                                class="bg-gray-100 dark:bg-gray-700 text-gray-600 dark:text-gray-300 text-xs font-bold px-2.5 py-0.5 rounded-full ml-auto">
                                {{ business.services.length }}
                            </span>
                        </div>
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                            <div v-for="service in business.services" :key="service.id"
                                class="bg-gray-50 dark:bg-gray-900/50 rounded-xl p-4 border border-gray-100 dark:border-gray-700 hover:border-primary-200 dark:hover:border-primary-800 hover:bg-primary-50/20 dark:hover:bg-primary-900/10 transition-colors">
                                <p class="text-sm font-bold text-gray-900 dark:text-white">{{ service.name }}</p>
                                <ExpandableText v-if="service.description" :text="service.description" :lines="3"
                                    text-class="text-xs text-gray-500 mt-1 leading-relaxed dark:text-gray-400" />
                            </div>
                        </div>
                    </div>

                    <!-- GALLERY -->
                    <div v-if="galleryImages && galleryImages.length > 0"
                        class="bg-white dark:bg-gray-800 rounded-2xl border border-gray-100 dark:border-gray-700 p-6 md:p-8">
                        <div class="flex items-center gap-3 mb-5">
                            <div
                                class="w-10 h-10 rounded-xl bg-purple-50 dark:bg-purple-900/30 flex items-center justify-center">
                                <svg class="w-5 h-5 text-purple-600 dark:text-purple-400" fill="none"
                                    stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z" />
                                </svg>
                            </div>
                            <h2 class="text-lg font-bold text-gray-900 dark:text-white tracking-tight">Gallery</h2>
                            <span
                                class="bg-gray-100 dark:bg-gray-700 text-gray-600 dark:text-gray-300 text-xs font-bold px-2.5 py-0.5 rounded-full">
                                {{ galleryImages.length }}
                            </span>
                        </div>
                        <div class="grid grid-cols-2 sm:grid-cols-3 gap-3">
                            <div v-for="(image, index) in galleryImages" :key="image.id" @click="openLightbox(index)"
                                class="aspect-square rounded-xl overflow-hidden border border-gray-200 dark:border-gray-700 hover:shadow-lg transition-shadow cursor-pointer group relative">
                                <OptimizedImage :path="image.path" :alt="image.caption || 'Gallery image'" size="thumb"
                                    img-class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-300"
                                    fallback-class="w-full h-full flex items-center justify-center bg-gray-100 dark:bg-gray-700" />
                                <div
                                    class="absolute inset-0 bg-black/0 group-hover:bg-black/30 transition-colors flex items-center justify-center">
                                    <svg class="w-8 h-8 text-white opacity-0 group-hover:opacity-100 transition-opacity"
                                        fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0zM10 7v6m-3-3h6" />
                                    </svg>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- BRANCHES SECTION (child component) -->
                    <BranchesSection v-if="business.branches && business.branches.length > 0"
                        :branches="business.branches" :has-access="hasFeature('branch_hours')" />



                    <!-- REVIEWS -->
                    <div id="reviews"
                        class="bg-white dark:bg-gray-800 rounded-2xl border border-gray-100 dark:border-gray-700 p-6 md:p-8">
                        <div class="flex items-center justify-between gap-3 mb-6 flex-wrap">
                            <div class="flex items-center gap-3 flex-wrap">
                                <div
                                    class="w-10 h-10 rounded-xl bg-amber-50 dark:bg-amber-900/30 flex items-center justify-center flex-shrink-0">
                                    <svg class="w-5 h-5 text-amber-500 dark:text-amber-400" fill="currentColor"
                                        viewBox="0 0 20 20">
                                        <path
                                            d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z" />
                                    </svg>
                                </div>
                                <div class="flex items-center gap-2 flex-wrap">
                                    <h2 class="text-lg font-bold text-gray-900 dark:text-white tracking-tight">Reviews
                                    </h2>
                                    <span v-if="business.reviews_count > 0"
                                        class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full bg-amber-100 dark:bg-amber-900/30 text-amber-700 dark:text-amber-400 text-xs font-bold">
                                        <span class="text-amber-500">★</span>
                                        {{ Number(business.average_rating || 0).toFixed(1) }}
                                    </span>
                                    <span class="text-xs text-gray-500">
                                        {{ business.reviews_count || 0 }} review{{ (business.reviews_count || 0) === 1 ?
                                            '' : 's' }}
                                    </span>
                                </div>
                            </div>
                            <div class="flex items-center gap-3">
                                <button type="button" @click="showReviewModal = true"
                                    class="text-sm text-primary-600 dark:text-primary-400 hover:text-primary-800 dark:hover:text-primary-300 font-semibold transition-colors inline-flex items-center gap-1">
                                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5"
                                            d="M12 4v16m8-8H4" />
                                    </svg>
                                    Write
                                </button>
                                <a :href="`/business/${business.id}/reviews`"
                                    class="text-sm text-primary-600 dark:text-primary-400 hover:text-primary-800 dark:hover:text-primary-300 font-semibold transition-colors">
                                    See all →
                                </a>
                            </div>
                        </div>



                        <!-- Recent reviews -->
                        <div v-if="business.reviews && business.reviews.length > 0" class="space-y-4">
                            <div v-for="review in business.reviews" :key="review.id"
                                class="bg-white dark:bg-gray-800 rounded-2xl border border-gray-100 dark:border-gray-700 p-5 hover:shadow-md transition-shadow">
                                <div class="flex items-start gap-4">
                                    <div
                                        class="w-12 h-12 rounded-2xl bg-gradient-to-br from-primary-500 to-primary-700 flex items-center justify-center text-white font-bold text-base shadow-md flex-shrink-0">
                                        {{ getInitials(review.user?.name || "Anonymous") }}
                                    </div>
                                    <div class="flex-1 min-w-0">
                                        <div class="flex items-center justify-between flex-wrap gap-2">
                                            <div>
                                                <p class="font-bold text-gray-900 dark:text-white tracking-tight">
                                                    {{ review.user?.name || "Anonymous" }}
                                                </p>
                                                <div class="flex items-center gap-2 mt-0.5">
                                                    <div class="flex text-amber-400 text-sm">
                                                        <span v-for="i in 5" :key="i">
                                                            {{ i <= review.rating ? "★" : "☆" }} </span>
                                                    </div>
                                                    <span class="text-xs text-gray-400 dark:text-gray-500">• {{
                                                        formatDate(review.created_at) }}</span>
                                                </div>
                                            </div>
                                            <span v-if="review.status === 'pending'"
                                                class="text-[10px] font-bold uppercase tracking-wide bg-amber-100 dark:bg-amber-900/30 text-amber-700 dark:text-amber-400 px-2.5 py-1 rounded-full flex-shrink-0">
                                                Pending
                                            </span>
                                        </div>
                                        <p v-if="review.title" class="font-semibold text-gray-900 dark:text-white mt-2">
                                            {{ review.title }}
                                        </p>
                                        <p class="text-gray-600 dark:text-gray-400 text-sm mt-1 leading-relaxed">
                                            {{ review.content }}
                                        </p>

                                        <!-- Review photos -->
                                        <div v-if="review.images && review.images.length > 0" class="mt-3">
                                            <ReviewImageGallery :images="review.images" size="sm" alt="Review photo" />
                                        </div>

                                        <!-- Owner reply -->
                                        <div v-if="review.replies && review.replies.length > 0"
                                            class="mt-3 bg-blue-50 dark:bg-blue-900/20 rounded-xl p-4 border border-blue-100 dark:border-blue-800">
                                            <div class="flex items-center gap-2">
                                                <svg class="w-4 h-4 text-blue-500" fill="none" stroke="currentColor"
                                                    viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round"
                                                        stroke-width="2" d="M3 10h10a8 8 0 018 8v2M3 10l6 6m-6-6l6-6" />
                                                </svg>
                                                <span
                                                    class="text-xs font-bold text-blue-700 dark:text-blue-300 uppercase tracking-wide">Owner
                                                    response</span>
                                                <span class="text-xs text-gray-400 dark:text-gray-500">• {{
                                                    formatDate(review.replies[0].created_at) }}</span>
                                            </div>
                                            <p class="text-sm text-gray-700 dark:text-gray-300 mt-1.5 leading-relaxed">
                                                {{ review.replies[0].content }}
                                            </p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div v-if="business.reviews_count > 3" class="text-center mt-4">
                                <a :href="`/business/${business.id}/reviews`"
                                    class="inline-flex items-center gap-2 text-sm text-primary-600 dark:text-primary-400 hover:text-primary-800 dark:hover:text-primary-300 font-semibold transition-colors">
                                    View all {{ business.reviews_count }} reviews
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M9 5l7 7-7 7" />
                                    </svg>
                                </a>
                            </div>
                        </div>

                        <div v-else class="text-center py-8">
                            <div
                                class="w-16 h-16 bg-gradient-to-br from-amber-100 to-amber-50 rounded-2xl flex items-center justify-center mx-auto mb-3">
                                <span class="text-3xl">💬</span>
                            </div>
                            <p class="text-gray-600 dark:text-gray-300 font-semibold">No reviews yet</p>
                            <p class="text-sm text-gray-400 dark:text-gray-500 mt-1">Be the first to review this
                                business!</p>
                        </div>
                    </div>

                    <!-- COUPONS (child component) -->
                    <BusinessCoupons v-if="coupons && coupons.length > 0" :coupons="coupons" :business="business"
                        class="mt-6" />
                </div>

                <!-- ==================== RIGHT COLUMN (SIDEBAR) ==================== -->
                <div class="lg:col-span-1 space-y-6">

                    <!-- ✅ COUPONS (top of sidebar) -->
                    <BusinessCouponsSidebar v-if="coupons && coupons.length > 0" :coupons="coupons"
                        :business="business" />

                    <!-- CONTACT -->
                    <div class="bg-white dark:bg-gray-800 rounded-2xl border border-gray-100 dark:border-gray-700 p-6">
                        <div class="flex items-center gap-3 mb-5">
                            <div
                                class="w-10 h-10 rounded-xl bg-emerald-50 dark:bg-emerald-900/30 flex items-center justify-center">
                                <svg class="w-5 h-5 text-emerald-600 dark:text-emerald-400" fill="none"
                                    stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z" />
                                </svg>
                            </div>
                            <h3 class="text-sm font-bold text-gray-900 dark:text-white tracking-tight">Contact</h3>
                        </div>
                        <div class="space-y-3">
                            <!-- Phone unlocked -->
                            <a v-if="contactPhone && hasFeature('phone_display')" :href="`tel:${contactPhone}`"
                                @click="trackClick('phone')"
                                class="flex items-center gap-3 p-3 bg-gray-50 dark:bg-gray-900/50 rounded-xl hover:bg-emerald-50 dark:hover:bg-emerald-900/20 transition-colors cursor-pointer group">
                                <div
                                    class="w-9 h-9 bg-emerald-100 dark:bg-emerald-900/40 rounded-lg flex items-center justify-center group-hover:bg-emerald-200 dark:group-hover:bg-emerald-900/60 transition-colors flex-shrink-0">
                                    <svg class="w-4 h-4 text-emerald-600 dark:text-emerald-400" fill="none"
                                        stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z" />
                                    </svg>
                                </div>
                                <span
                                    class="text-sm text-gray-700 group-hover:text-gray-900 dark:text-white transition-colors truncate">
                                    {{ contactPhone }}
                                </span>
                            </a>

                            <!-- Phone locked -->
                            <button v-else-if="contactPhone" @click="showUpgrade('Phone Display', 'Starter')"
                                class="flex items-center gap-3 p-3 bg-gray-50 dark:bg-gray-900/50 rounded-xl hover:bg-amber-50 dark:hover:bg-amber-900/20 transition-colors cursor-pointer group w-full text-left">
                                <div
                                    class="w-9 h-9 bg-amber-100 dark:bg-amber-900/40 rounded-lg flex items-center justify-center group-hover:bg-amber-200 dark:group-hover:bg-amber-900/60 transition-colors flex-shrink-0">
                                    <svg class="w-4 h-4 text-amber-600 dark:text-amber-400" fill="none"
                                        stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" />
                                    </svg>
                                </div>
                                <div class="flex-1 min-w-0">
                                    <span class="text-sm font-semibold text-gray-700 block">Phone Number</span>
                                    <p class="text-xs text-amber-600 font-medium">Upgrade to unlock</p>
                                </div>
                            </button>

                            <!-- WhatsApp unlocked -->
                            <a v-if="contactWhatsApp && hasFeature('whatsapp_button')"
                                :href="`https://wa.me/${contactWhatsApp.replace(/[^0-9]/g, '')}`" target="_blank"
                                @click="trackClick('whatsapp')"
                                class="flex items-center gap-3 p-3 bg-gray-50 dark:bg-gray-900/50 rounded-xl hover:bg-emerald-50 dark:hover:bg-emerald-900/20 transition-colors cursor-pointer group">
                                <div
                                    class="w-9 h-9 bg-emerald-100 dark:bg-emerald-900/40 rounded-lg flex items-center justify-center group-hover:bg-emerald-200 dark:group-hover:bg-emerald-900/60 transition-colors flex-shrink-0">
                                    <svg class="w-4 h-4 text-emerald-600 dark:text-emerald-400" fill="currentColor"
                                        viewBox="0 0 24 24">
                                        <path
                                            d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.872.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413Z" />
                                    </svg>
                                </div>
                                <span
                                    class="text-sm text-gray-700 group-hover:text-gray-900 dark:text-white transition-colors font-medium">
                                    WhatsApp
                                </span>
                            </a>

                            <!-- WhatsApp locked -->
                            <button v-else-if="contactWhatsApp" @click="showUpgrade('WhatsApp Button', 'Starter')"
                                class="flex items-center gap-3 p-3 bg-gray-50 dark:bg-gray-900/50 rounded-xl hover:bg-amber-50 dark:hover:bg-amber-900/20 transition-colors cursor-pointer group w-full text-left">
                                <div
                                    class="w-9 h-9 bg-amber-100 dark:bg-amber-900/40 rounded-lg flex items-center justify-center group-hover:bg-amber-200 dark:group-hover:bg-amber-900/60 transition-colors flex-shrink-0">
                                    <svg class="w-4 h-4 text-amber-600 dark:text-amber-400" fill="none"
                                        stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" />
                                    </svg>
                                </div>
                                <div class="flex-1 min-w-0">
                                    <span class="text-sm font-semibold text-gray-700 block">WhatsApp</span>
                                    <p class="text-xs text-amber-600 font-medium">Upgrade to unlock</p>
                                </div>
                            </button>

                            <!-- Email -->
                            <a v-if="business.email" :href="`mailto:${business.email}`"
                                class="flex items-center gap-3 p-3 bg-gray-50 dark:bg-gray-900/50 rounded-xl hover:bg-blue-50 dark:hover:bg-blue-900/20 transition-colors cursor-pointer group">
                                <div
                                    class="w-9 h-9 bg-blue-100 dark:bg-blue-900/40 rounded-lg flex items-center justify-center group-hover:bg-blue-200 dark:group-hover:bg-blue-900/60 transition-colors flex-shrink-0">
                                    <svg class="w-4 h-4 text-blue-600 dark:text-blue-400" fill="none"
                                        stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />
                                    </svg>
                                </div>
                                <span
                                    class="text-sm text-gray-700 group-hover:text-gray-900 dark:text-white transition-colors truncate">
                                    {{ business.email }}
                                </span>
                            </a>

                            <!-- Website -->
                            <a v-if="business.website" :href="business.website" target="_blank"
                                @click="trackClick('website')"
                                class="flex items-center gap-3 p-3 bg-gray-50 dark:bg-gray-900/50 rounded-xl hover:bg-purple-50 dark:hover:bg-purple-900/20 transition-colors cursor-pointer group">
                                <div
                                    class="w-9 h-9 bg-purple-100 dark:bg-purple-900/40 rounded-lg flex items-center justify-center group-hover:bg-purple-200 dark:group-hover:bg-purple-900/60 transition-colors flex-shrink-0">
                                    <svg class="w-4 h-4 text-purple-600 dark:text-purple-400" fill="none"
                                        stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M21 12a9 9 0 01-9 9m9-9a9 9 0 00-9-9m9 9H3m9 9a9 9 0 01-9-9m9 9c1.657 0 3-4.03 3-9s-1.343-9-3-9m0 18c-1.657 0-3-4.03-3-9s1.343-9 3-9" />
                                    </svg>
                                </div>
                                <span
                                    class="text-sm text-gray-700 group-hover:text-gray-900 dark:text-white transition-colors font-medium">
                                    Website
                                </span>
                            </a>
                        </div>
                    </div>

                    <!-- ✅ REVIEW TRIGGER -->
                    <ReviewTriggerCard @open="showReviewModal = true" />

                    <!-- ✅ LEAD TRIGGER (when feature enabled) -->
                    <LeadTriggerCard v-if="business.feature_flags?.lead_capture" @open="showLeadModal = true" />

                    <!-- Locked hint for owner/admin (when lead_capture is not enabled) -->
                    <div v-else-if="$page.props.auth?.user && ($page.props.auth.user.id === business.owner_id || $page.props.auth.user.role === 'admin' || $page.props.auth.user.role === 'super_admin')"
                        class="bg-gradient-to-br from-primary-50 to-purple-50 dark:from-primary-950/20 dark:to-purple-950/20 border border-primary-200 dark:border-primary-800 rounded-2xl p-5">
                        <div class="flex items-start gap-3 mb-3">
                            <div
                                class="w-10 h-10 rounded-xl bg-white dark:bg-gray-800 flex items-center justify-center flex-shrink-0 shadow-sm">
                                <svg class="w-5 h-5 text-gray-600 dark:text-gray-400" fill="none" stroke="currentColor"
                                    viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />
                                </svg>
                            </div>
                            <div class="min-w-0">
                                <div class="flex items-center gap-2 flex-wrap mb-1">
                                    <h3 class="text-sm font-bold text-gray-900 dark:text-white">Enable Contact Form</h3>
                                    <span
                                        class="text-[9px] bg-primary-600 text-white px-2 py-0.5 rounded-full font-bold tracking-wide uppercase">Growth+</span>
                                </div>
                                <p class="text-xs text-gray-600 dark:text-gray-400 leading-relaxed">
                                    Let customers send inquiries directly. Get notified and manage leads in one place.
                                </p>
                            </div>
                        </div>
                        <a href="/owner/subscription/renew"
                            class="w-full inline-flex items-center justify-center gap-2 px-4 py-2.5 bg-gradient-to-r from-primary-600 to-primary-700 text-white rounded-xl font-semibold hover:from-primary-700 hover:to-primary-800 transition-all shadow-lg shadow-primary-500/30 text-xs">
                            Upgrade
                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M13 7l5 5m0 0l-5 5m5-5H6" />
                            </svg>
                        </a>
                    </div>


                    <!-- SOCIAL MEDIA (excludes phone + whatsapp — those live in Contact) -->
                    <div v-if="socialContacts.length > 0"
                        class="bg-white dark:bg-gray-800 rounded-2xl border border-gray-100 dark:border-gray-700 p-6">
                        <div class="flex items-center gap-3 mb-5">
                            <div
                                class="w-10 h-10 rounded-xl bg-pink-50 dark:bg-pink-900/30 flex items-center justify-center">
                                <svg class="w-5 h-5 text-pink-600 dark:text-pink-400" fill="none" stroke="currentColor"
                                    viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M21 12a9 9 0 01-9 9m9-9a9 9 0 00-9-9m9 9H3m9 9a9 9 0 01-9-9m9 9c1.657 0 3-4.03 3-9s-1.343-9-3-9m0 18c-1.657 0-3-4.03-3-9s1.343-9 3-9" />
                                </svg>
                            </div>
                            <h3 class="text-sm font-bold text-gray-900 dark:text-white tracking-tight">Social Media</h3>
                        </div>
                        <div class="flex flex-wrap gap-2">
                            <a v-for="contact in socialContacts" :key="contact.id" :href="getSocialUrl(contact)"
                                target="_blank" @click="trackClick('social')"
                                class="flex items-center gap-2 px-4 py-2.5 bg-gray-50 dark:bg-gray-900/50 rounded-xl hover:bg-primary-50 dark:hover:bg-primary-900/20 transition-colors text-sm text-gray-700 dark:text-gray-300 hover:text-primary-600 dark:hover:text-primary-400 cursor-pointer border border-transparent hover:border-primary-200 dark:hover:border-primary-800 font-medium">
                                <ContactIcon :type="contact.type" size="sm" class="text-gray-500 dark:text-gray-400" />
                                {{ getSocialLabel(contact.type) }}
                            </a>
                        </div>
                    </div>


                    <!-- SHARE -->
                    <div class="bg-white dark:bg-gray-800 rounded-2xl border border-gray-100 dark:border-gray-700 p-6">
                        <div class="flex items-center gap-3 mb-5">
                            <div
                                class="w-10 h-10 rounded-xl bg-indigo-50 dark:bg-indigo-900/30 flex items-center justify-center">
                                <svg class="w-5 h-5 text-indigo-600 dark:text-indigo-400" fill="none"
                                    stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M8.684 13.342C8.886 12.938 9 12.482 9 12c0-.482-.114-.938-.316-1.342m0 2.684a3 3 0 110-2.684m0 2.684l6.632 3.316m-6.632-6l6.632-3.316m0 0a3 3 0 105.367-2.684 3 3 0 00-5.367 2.684zm0 9.316a3 3 0 105.368 2.684 3 3 0 00-5.368-2.684z" />
                                </svg>
                            </div>
                            <h3 class="text-sm font-bold text-gray-900 dark:text-white tracking-tight">Share</h3>
                        </div>

                        <!-- Primary CTA: Web Share (native) on mobile, Copy Link on desktop -->
                        <button @click="handlePrimaryShare"
                            class="w-full inline-flex items-center justify-center gap-2 px-4 py-2.5 bg-gradient-to-r from-primary-600 to-primary-700 text-white rounded-xl hover:from-primary-700 hover:to-primary-800 transition-all shadow-sm text-sm font-semibold mb-3">
                            <!-- Native share icon (mobile) -->
                            <svg v-if="canNativeShare" class="w-4 h-4" fill="none" stroke="currentColor"
                                viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M8.684 13.342C8.886 12.938 9 12.482 9 12c0-.482-.114-.938-.316-1.342m0 2.684a3 3 0 110-2.684m0 2.684l6.632 3.316m-6.632-6l6.632-3.316m0 0a3 3 0 105.367-2.684 3 3 0 00-5.367 2.684zm0 9.316a3 3 0 105.368 2.684 3 3 0 00-5.368-2.684z" />
                            </svg>
                            <!-- Link icon (desktop copy) -->
                            <svg v-else class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M13.828 10.172a4 4 0 00-5.656 0l-4 4a4 4 0 105.656 5.656l1.102-1.101m-.758-4.899a4 4 0 005.656 0l4-4a4 4 0 00-5.656-5.656l-1.1 1.1" />
                            </svg>
                            {{ canNativeShare ? 'Share this business' : 'Copy link' }}
                        </button>

                        <!-- Row of brand share buttons -->
                        <div class="flex flex-wrap gap-2 justify-center">
                            <!-- Facebook -->
                            <button @click="shareOnFacebook" type="button" aria-label="Share on Facebook"
                                class="w-10 h-10 rounded-xl bg-[#1877F2] text-white hover:bg-[#1666D9] transition-colors flex items-center justify-center shadow-sm">
                                <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 24 24">
                                    <path
                                        d="M24 12.073c0-6.627-5.373-12-12-12s-12 5.373-12 12c0 5.99 4.388 10.954 10.125 11.854v-8.385H7.078v-3.47h3.047V9.43c0-3.007 1.792-4.669 4.533-4.669 1.312 0 2.686.235 2.686.235v2.953H15.83c-1.491 0-1.956.925-1.956 1.874v2.25h3.328l-.532 3.47h-2.796v8.385C19.612 23.027 24 18.062 24 12.073z" />
                                </svg>
                            </button>

                            <!-- X / Twitter -->
                            <button @click="shareOnTwitter" type="button" aria-label="Share on X"
                                class="w-10 h-10 rounded-xl bg-black text-white hover:bg-gray-900 transition-colors flex items-center justify-center shadow-sm">
                                <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 24 24">
                                    <path
                                        d="M18.244 2.25h3.308l-7.227 8.26 8.502 11.24H16.17l-5.214-6.817L4.99 21.75H1.68l7.73-8.835L1.254 2.25H8.08l4.713 6.231zm-1.161 17.52h1.833L7.084 4.126H5.117z" />
                                </svg>
                            </button>

                            <!-- LinkedIn -->
                            <button @click="shareOnLinkedIn" type="button" aria-label="Share on LinkedIn"
                                class="w-10 h-10 rounded-xl bg-[#0A66C2] text-white hover:bg-[#0956A8] transition-colors flex items-center justify-center shadow-sm">
                                <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 24 24">
                                    <path
                                        d="M20.447 20.452h-3.554v-5.569c0-1.328-.027-3.037-1.852-3.037-1.853 0-2.136 1.445-2.136 2.939v5.667H9.351V9h3.414v1.561h.046c.477-.9 1.637-1.85 3.37-1.85 3.601 0 4.267 2.37 4.267 5.455v6.286zM5.337 7.433c-1.144 0-2.063-.926-2.063-2.065 0-1.138.92-2.063 2.063-2.063 1.14 0 2.064.925 2.064 2.063 0 1.139-.925 2.065-2.064 2.065zm1.782 13.019H3.555V9h3.564v11.452zM22.225 0H1.771C.792 0 0 .774 0 1.729v20.542C0 23.227.792 24 1.771 24h20.451C23.2 24 24 23.227 24 22.271V1.729C24 .774 23.2 0 22.222 0h.003z" />
                                </svg>
                            </button>

                            <!-- WhatsApp -->
                            <button @click="shareOnWhatsApp" type="button" aria-label="Share on WhatsApp"
                                class="w-10 h-10 rounded-xl bg-[#25D366] text-white hover:bg-[#20BD5A] transition-colors flex items-center justify-center shadow-sm">
                                <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 24 24">
                                    <path
                                        d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.872.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413Z" />
                                </svg>
                            </button>

                            <!-- Telegram -->
                            <button @click="shareOnTelegram" type="button" aria-label="Share on Telegram"
                                class="w-10 h-10 rounded-xl bg-[#0088CC] text-white hover:bg-[#0077B3] transition-colors flex items-center justify-center shadow-sm">
                                <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 24 24">
                                    <path
                                        d="M11.944 0A12 12 0 0 0 0 12a12 12 0 0 0 12 12 12 12 0 0 0 12-12A12 12 0 0 0 12 0a12 12 0 0 0-.056 0zm4.962 7.224c.1-.002.321.023.465.14a.506.506 0 0 1 .171.325c.016.093.036.306.02.472-.18 1.898-.962 6.502-1.36 8.627-.168.9-.499 1.201-.82 1.23-.696.065-1.225-.46-1.9-.902-1.056-.693-1.653-1.124-2.678-1.8-1.185-.78-.417-1.21.258-1.91.177-.184 3.247-2.977 3.307-3.23.007-.032.014-.15-.056-.212s-.174-.041-.249-.024c-.106.024-1.793 1.14-5.061 3.345-.48.33-.913.49-1.302.48-.428-.008-1.252-.241-1.865-.44-.752-.245-1.349-.374-1.297-.789.027-.216.325-.437.893-.663 3.498-1.524 5.83-2.529 6.998-3.014 3.332-1.386 4.025-1.627 4.476-1.635z" />
                                </svg>
                            </button>
                        </div>
                    </div>

                    <!-- ✅ RELATED BUSINESSES (sidebar) -->
                    <RelatedBusinesses :businesses="relatedBusinesses" />

                    <!-- ✅ COLLECTION CROSS-LINK -->
                    <div v-if="collectionCrossLink"
                        class="bg-white dark:bg-gray-800 rounded-2xl border border-gray-100 dark:border-gray-700 p-5">
                        <Link :href="collectionCrossLink.href"
                            class="inline-flex items-center gap-2 text-sm font-semibold text-primary-600 dark:text-primary-400 hover:text-primary-800 dark:hover:text-primary-300 transition-colors group">
                            <svg class="w-4 h-4 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M17 8l4 4m0 0l-4 4m4-4H3" />
                            </svg>
                            <span>{{ collectionCrossLink.label }}</span>
                        </Link>
                    </div>
                </div>
            </div>
        </div>

        <!-- ==================== FORM MODALS ==================== -->
        <ReviewFormModal v-model:show="showReviewModal" :business-id="business.id" :business-name="business.name"
            @submitted="handleReviewSubmitted" />

        <LeadCaptureFormModal v-if="business.feature_flags?.lead_capture" v-model:show="showLeadModal"
            :business="business" :business-name="business.name" />


        <!-- ==================== GALLERY LIGHTBOX ==================== -->
        <div v-if="selectedImage" class="fixed inset-0 bg-black/95 z-50 flex items-center justify-center p-4"
            @click="closeLightbox">
            <div class="relative w-full max-w-6xl mx-auto" @click.stop>
                <button @click="closeLightbox"
                    class="absolute -top-12 right-0 text-white hover:text-gray-300 transition-colors z-20">
                    <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>

                <div class="relative flex items-center justify-center w-full" style="height: 85vh">
                    <OptimizedImage :path="selectedImage.path" :alt="selectedImage.caption || 'Gallery image'"
                        size="large"
                        img-class="max-w-full max-h-full w-auto h-auto object-contain rounded-lg shadow-2xl"
                        loading="eager" @load="onImageLoad" />

                    <div v-if="imageLoading" class="absolute inset-0 flex items-center justify-center">
                        <div class="w-12 h-12 border-4 border-white/20 border-t-white rounded-full animate-spin"></div>
                    </div>
                </div>

                <div v-if="selectedImage.caption"
                    class="absolute bottom-6 left-0 right-0 text-center text-white text-sm bg-black/50 py-2 px-4 mx-auto max-w-md rounded-lg backdrop-blur-sm">
                    {{ selectedImage.caption }}
                </div>

                <button v-if="hasPrevImage" @click.stop="prevImage"
                    class="absolute left-2 md:left-4 top-1/2 -translate-y-1/2 bg-white/20 hover:bg-white/30 text-white p-3 rounded-full transition-colors backdrop-blur-sm z-10">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
                    </svg>
                </button>

                <button v-if="hasNextImage" @click.stop="nextImage"
                    class="absolute right-2 md:right-4 top-1/2 -translate-y-1/2 bg-white/20 hover:bg-white/30 text-white p-3 rounded-full transition-colors backdrop-blur-sm z-10">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                    </svg>
                </button>

                <div
                    class="absolute -bottom-10 left-1/2 -translate-x-1/2 text-white/60 text-sm bg-black/50 px-4 py-1 rounded-full whitespace-nowrap">
                    {{ currentImageIndex + 1 }} / {{ galleryImages.length }}
                </div>
            </div>
        </div>
    </PublicLayout>
</template>

<script setup>
    import { ref, computed, onMounted, onUnmounted } from "vue";
    import axios from "axios";
    import { Link, router, usePage } from "@inertiajs/vue3";
    import PublicLayout from "@/Layouts/PublicLayout.vue";
    import ReviewFormModal from "@/Components/Public/ReviewFormModal.vue";
    import ReviewTriggerCard from "@/Components/Public/ReviewTriggerCard.vue";
    import LeadCaptureFormModal from "@/Components/Public/LeadCaptureFormModal.vue";
    import LeadTriggerCard from "@/Components/Public/LeadTriggerCard.vue";
    import BusinessCouponsSidebar from "@/Components/Public/BusinessCouponsSidebar.vue";
    import { useUpgradeModal } from "@/composables/useUpgradeModal";
    import VerifiedBadge from "@/Components/VerifiedBadge.vue";
    import LeadCaptureForm from "@/Components/Public/LeadCaptureForm.vue";
    import BranchesSection from "@/Components/Public/BranchesSection.vue";
    import ContactIcon from "@/Components/ContactIcon.vue";
    import ReviewImageGallery from "@/Components/Public/ReviewImageGallery.vue";
    import RelatedBusinesses from "@/Components/Public/RelatedBusinesses.vue";
    import ExpandableText from "@/Components/Public/ExpandableText.vue";
    import { useToast } from "@/composables/useToast";
    import OptimizedImage from "@/Components/Public/OptimizedImage.vue";

    const { show: showUpgrade } = useUpgradeModal();
    const { success, error } = useToast();

    const hasFeature = (feature) => {
        return props.business?.feature_flags?.[feature] ?? true;
    };

    const props = defineProps({
        business: {
            type: Object,
            required: true,
        },
        coupons: {
            type: Array,
            default: () => [],
        },
        primaryBranch: {
            type: Object,
            default: null,
        },
        isOpen: {
            type: Boolean,
            default: false,
        },
        ratingBreakdown: {
            type: Object,
            default: () => ({ 5: 0, 4: 0, 3: 0, 2: 0, 1: 0 }),
        },
        relatedBusinesses: {
            type: Array,
            default: () => [],
        },
    });

    const page = usePage();

    const isFavorited = ref(props.business.is_favorited || false);
    const favProcessing = ref(false);

    const isLoggedIn = computed(() => !!page.props.auth?.user);

    const toggleFavorite = async () => {
        if (favProcessing.value) return;

        if (!isLoggedIn.value) {
            router.visit("/login?redirect=" + encodeURIComponent(window.location.pathname));
            return;
        }

        favProcessing.value = true;
        const previous = isFavorited.value;
        isFavorited.value = !previous;

        try {
            const response = await axios.post(
                `/favorites/${props.business.id}/toggle`,
                {},
                {
                    headers: {
                        "X-CSRF-TOKEN": document.querySelector('meta[name="csrf-token"]')?.content,
                    },
                }
            );

            if (response.data.success) {
                isFavorited.value = response.data.is_favorited;

                if (response.data.is_favorited) {
                    success('Added to favorites ❤️', props.business.name, { duration: 2500 });
                } else {
                    success('Removed from favorites', props.business.name, { duration: 2500 });
                }
            }
        } catch (err) {
            isFavorited.value = previous;
            console.error("Favorite toggle failed:", err);
            error('Error', err.response?.data?.message || 'Failed to update favorite.', { duration: 3000 });
        } finally {
            favProcessing.value = false;
        }
    };

    // ============== Gallery Lightbox ==============
    const selectedImage = ref(null);
    const currentImageIndex = ref(0);
    const imageLoading = ref(false);

    const galleryImages = computed(() => {
        return props.business.gallery_images || props.business.galleryImages || [];
    });
    const openLightbox = (index) => {
        if (!galleryImages.value[index]) return;
        imageLoading.value = true;
        currentImageIndex.value = index;
        selectedImage.value = galleryImages.value[index];
        document.body.style.overflow = "hidden";
    };

    const closeLightbox = () => {
        selectedImage.value = null;
        document.body.style.overflow = "";
        imageLoading.value = false;  // ← cleanup
    };

    const nextImage = () => {
        if (currentImageIndex.value < galleryImages.value.length - 1) {
            currentImageIndex.value++;
            selectedImage.value = galleryImages.value[currentImageIndex.value];
            imageLoading.value = true;
        }
    };

    const prevImage = () => {
        if (currentImageIndex.value > 0) {
            currentImageIndex.value--;
            selectedImage.value = galleryImages.value[currentImageIndex.value];
            imageLoading.value = true;
        }
    };

    const hasNextImage = computed(() => {
        return currentImageIndex.value < galleryImages.value.length - 1;
    });

    const hasPrevImage = computed(() => {
        return currentImageIndex.value > 0;
    });

    const onImageLoad = () => {
        imageLoading.value = false;
    };

    const getImageUrl = (image) => {
        if (!image) return null;
        if (image.full_url) return image.full_url;
        if (image.url) return image.url;
        if (image.path) {
            if (image.path.startsWith("http://") || image.path.startsWith("https://")) {
                return image.path;
            }
            return "/storage/" + image.path;
        }
        return null;
    };

    const getFullImageUrl = (image) => {
        if (!image) return null;
        if (image.full_url) return image.full_url;
        if (image.url) return image.url;
        if (image.path) {
            if (image.path.startsWith("http://") || image.path.startsWith("https://")) {
                return image.path;
            }
            return "/storage/" + image.path;
        }
        return null;
    };

    const handleImageError = (event) => {
        const target = event.target;
        if (target) {
            target.style.display = "none";
            console.warn("Failed to load image:", target.src);
        }
    };

    // ============== Image Helpers ==============
    const getLogoUrl = () => {
        if (props.business.logo_url) return props.business.logo_url;
        if (props.business.logo && typeof props.business.logo === "object" && props.business.logo.path) {
            return "/storage/" + props.business.logo.path;
        }
        if (props.business.logo && typeof props.business.logo === "string") {
            if (props.business.logo.startsWith("http")) return props.business.logo;
            return "/storage/" + props.business.logo;
        }
        return null;
    };

    const getCoverImageUrl = () => {
        if (props.business.cover_image_url) return props.business.cover_image_url;
        if (props.business.cover_image && typeof props.business.cover_image === "object" && props.business.cover_image.path) {
            return "/storage/" + props.business.cover_image.path;
        }
        if (props.business.cover_image && typeof props.business.cover_image === "string") {
            if (props.business.cover_image.startsWith("http")) return props.business.cover_image;
            return "/storage/" + props.business.cover_image;
        }
        return null;
    };

    // ============== Helpers ==============
    const getInitials = (name) => {
        if (!name) return "?";
        return name.split(" ").map((n) => n[0]).join("").toUpperCase().slice(0, 2);
    };

    const getPrimaryCategory = () => {
        if (!props.business.categories || props.business.categories.length === 0) {
            return "Uncategorized";
        }
        if (props.business.categories[0]?.pivot) {
            const primary = props.business.categories.find((c) => c.pivot?.is_primary);
            return primary?.name || props.business.categories[0]?.name || "Uncategorized";
        }
        return props.business.categories[0]?.name || "Uncategorized";
    };

    const formatDate = (date) => {
        if (!date) return "N/A";
        return new Date(date).toLocaleDateString("en-US", {
            year: "numeric",
            month: "short",
            day: "numeric",
        });
    };

    const branchHours = computed(() => {
        if (!props.primaryBranch?.hours) return [];
        const hours = props.primaryBranch.hours;
        const result = [];

        for (let i = 0; i < 7; i++) {
            const dayHours = hours.filter((h) => h.day_of_week === i);

            if (dayHours.length === 0) {
                result.push("Closed");
            } else {
                const formattedHours = dayHours
                    .map((h) => {
                        if (h.formatted_hours) return h.formatted_hours;
                        if (h.is_closed) return "Closed";
                        if (h.is_24h) return "Open 24 Hours";
                        if (h.opens_at && h.closes_at) {
                            try {
                                const open = new Date("2000-01-01T" + h.opens_at).toLocaleTimeString("en-US", {
                                    hour: "2-digit", minute: "2-digit", hour12: true,
                                });
                                const close = new Date("2000-01-01T" + h.closes_at).toLocaleTimeString("en-US", {
                                    hour: "2-digit", minute: "2-digit", hour12: true,
                                });
                                return `${open} - ${close}`;
                            } catch (e) {
                                return `${h.opens_at} - ${h.closes_at}`;
                            }
                        }
                        return "Open";
                    })
                    .join(", ");
                result.push(formattedHours);
            }
        }
        return result;
    });

    const getRatingPercentage = (star) => {
        const total = props.business.reviews_count || 0;
        if (total === 0) return 0;
        const count = props.ratingBreakdown?.[star] || 0;
        return Math.round((count / total) * 100);
    };

    // ============== Social ==============
    const getSocialUrl = (contact) => {
        const urls = {
            facebook: `https://facebook.com/${contact.value}`,
            instagram: `https://instagram.com/${contact.value}`,
            tiktok: `https://tiktok.com/@${contact.value}`,
            twitter: `https://x.com/${contact.value}`,
            youtube: `https://youtube.com/@${contact.value}`,
            linkedin: `https://linkedin.com/company/${contact.value}`,
            whatsapp: `https://wa.me/${contact.value.replace(/[^0-9]/g, "")}`,
            phone: `tel:${contact.value}`,
            other: contact.value.startsWith("http") ? contact.value : `https://${contact.value}`,
        };
        return urls[contact.type] || contact.value;
    };


    const getSocialLabel = (type) => {
        const labels = {
            phone: "Phone", whatsapp: "WhatsApp", facebook: "Facebook", instagram: "Instagram",
            tiktok: "TikTok", twitter: "Twitter", youtube: "YouTube", linkedin: "LinkedIn", other: "Other",
        };
        return labels[type] || "Other";
    };

    // ============== Tracking ==============
    const trackClick = (type) => {
        const businessId = props.business.id;
        axios.post(`/analytics/track-click/${businessId}/${type}`)
            .then(() => console.log(`${type} click tracked successfully`))
            .catch((error) => console.error(`Error tracking ${type} click:`, error));
    };

    // ============== Share ==============
    const canNativeShare = ref(
        typeof navigator !== 'undefined' && typeof navigator.share === 'function'
    );

    const shareUrl = () => window.location.href;
    const shareTitle = () => props.business.name;
    const shareText = () => `Check out ${props.business.name} on Omniscient!`;

    const handlePrimaryShare = async () => {
        if (canNativeShare.value) {
            try {
                await navigator.share({
                    title: shareTitle(),
                    text: shareText(),
                    url: shareUrl(),
                });
            } catch (e) {
                // User cancelled or share failed — no-op
            }
        } else {
            await copyLink();
        }
    };

    const copyLink = async () => {
        const url = shareUrl();
        try {
            if (navigator.clipboard && window.isSecureContext) {
                await navigator.clipboard.writeText(url);
            } else {
                // Fallback for non-secure contexts / older browsers
                const ta = document.createElement('textarea');
                ta.value = url;
                ta.style.position = 'fixed';
                ta.style.opacity = '0';
                document.body.appendChild(ta);
                ta.select();
                document.execCommand('copy');
                document.body.removeChild(ta);
            }
            success('Link copied', 'The business link is on your clipboard.', { duration: 2500 });
        } catch (e) {
            error('Copy failed', 'Please copy the URL from the address bar.', { duration: 3000 });
        }
    };

    const shareOnFacebook = () => {
        window.open(`https://www.facebook.com/sharer/sharer.php?u=${encodeURIComponent(shareUrl())}`, "_blank", "width=600,height=400");
    };

    const shareOnTwitter = () => {
        window.open(`https://twitter.com/intent/tweet?text=${encodeURIComponent(shareText())}&url=${encodeURIComponent(shareUrl())}`, "_blank", "width=600,height=400");
    };

    const shareOnLinkedIn = () => {
        window.open(`https://www.linkedin.com/sharing/share-offsite/?url=${encodeURIComponent(shareUrl())}`, "_blank", "width=600,height=400");
    };

    const shareOnWhatsApp = () => {
        window.open(`https://wa.me/?text=${encodeURIComponent(shareText() + ' ' + shareUrl())}`, "_blank");
    };

    const shareOnTelegram = () => {
        window.open(`https://t.me/share/url?url=${encodeURIComponent(shareUrl())}&text=${encodeURIComponent(shareText())}`, "_blank");
    };

    // ============== Modal State ==============
    const showReviewModal = ref(false);
    const showLeadModal = ref(false);

    // ============== Handlers ==============
    const handleReviewSubmitted = () => {
        router.reload({ only: ['business'] });
    };

    const handleKeydown = (e) => {
        if (!selectedImage.value) return;
        if (e.key === "Escape") closeLightbox();
        if (e.key === "ArrowRight") nextImage();
        if (e.key === "ArrowLeft") prevImage();
    };

    onMounted(() => {
        document.addEventListener("keydown", handleKeydown);
    });

    onUnmounted(() => {
        document.removeEventListener("keydown", handleKeydown);
        document.body.style.overflow = "";
    });

    // ============== Branch Helpers ==============
    const getBranchAddress = (branch) => {
        if (!branch) return "Address not set";
        const parts = [];
        if (branch.address) parts.push(branch.address);
        if (branch.city?.name) parts.push(branch.city.name);
        if (branch.region?.name) parts.push(branch.region.name);
        if (branch.country?.name) parts.push(branch.country.name);
        return parts.join(", ") || "Address not set";
    };

    /**
     * ✅ Google Maps directions URL — opens native app on mobile.
     *    Prefers coordinates when available, falls back to address.
     */
    const getDirectionsUrl = (branch) => {
        if (!branch) return '#';
        const lat = branch.latitude;
        const lng = branch.longitude;
        const destination = (lat && lng)
            ? `${lat},${lng}`
            : encodeURIComponent(getBranchAddress(branch));
        return `https://www.google.com/maps/dir/?api=1&destination=${destination}`;
    };

    const getSortedHours = (hours) => {
        if (!hours) return [];
        return [...hours].sort((a, b) => a.day_of_week - b.day_of_week);
    };

    const getDayName = (dayNumber) => {
        const days = ["Sunday", "Monday", "Tuesday", "Wednesday", "Thursday", "Friday", "Saturday"];
        return days[dayNumber] || "Unknown";
    };

    const getTodayName = () => getDayName(new Date().getDay());

    const getHourDisplay = (hour) => {
        if (!hour) return "Not set";
        if (hour.is_closed) return "Closed";
        if (hour.is_24h) return "24 Hours";
        if (!hour.opens_at || !hour.closes_at) return "Not set";

        try {
            const open = new Date("2000-01-01T" + hour.opens_at).toLocaleTimeString("en-US", {
                hour: "2-digit", minute: "2-digit", hour12: true,
            });
            const close = new Date("2000-01-01T" + hour.closes_at).toLocaleTimeString("en-US", {
                hour: "2-digit", minute: "2-digit", hour12: true,
            });
            return `${open} - ${close}`;
        } catch (e) {
            return `${hour.opens_at} - ${hour.closes_at}`;
        }
    };

    const isHourActive = (hour) => {
        if (!hour) return false;
        if (hour.is_closed) return false;
        if (hour.is_24h) return true;
        if (!hour.opens_at || !hour.closes_at) return false;

        const now = new Date();
        const today = now.getDay();
        const currentMinutes = now.getHours() * 60 + now.getMinutes();

        if (hour.day_of_week !== today) return false;

        const [openH, openM] = hour.opens_at.split(":").map(Number);
        const [closeH, closeM] = hour.closes_at.split(":").map(Number);
        const openMinutes = openH * 60 + openM;
        const closeMinutes = closeH * 60 + closeM;

        if (closeMinutes < openMinutes) {
            return currentMinutes >= openMinutes || currentMinutes <= closeMinutes;
        }

        return currentMinutes >= openMinutes && currentMinutes <= closeMinutes;
    };

    const getTodayHours = (branch) => {
        if (!branch?.hours) return null;
        const today = new Date().getDay();
        const hour = branch.hours.find((h) => h.day_of_week === today);
        return hour ? getHourDisplay(hour) : null;
    };

    // ============== Branch Computed ==============
    const showAllBranches = ref(false);

    const otherBranches = computed(() => {
        if (!props.business.branches) return [];
        return props.business.branches.filter((b) => !b.is_primary);
    });

    const openBranchesCount = computed(() => {
        if (!props.business.branches) return 0;
        return props.business.branches.filter((b) => b.is_open_now).length;
    });

    // ============== Contact Helpers ==============
    // Phone: prefer primary branch, fall back to a `phone`-type contact
    const contactPhone = computed(() => {
        if (props.primaryBranch?.phone) return props.primaryBranch.phone;
        const c = (props.business.contacts || []).find((x) => x.type === 'phone');
        return c?.value || null;
    });

    // WhatsApp: same logic
    const contactWhatsApp = computed(() => {
        if (props.primaryBranch?.whatsapp) return props.primaryBranch.whatsapp;
        const c = (props.business.contacts || []).find((x) => x.type === 'whatsapp');
        return c?.value || null;
    });

    // Social-only contacts (exclude phone + whatsapp)
    const socialContacts = computed(() => {
        return (props.business.contacts || []).filter(
            (c) => c.type !== 'phone' && c.type !== 'whatsapp'
        );
    });

    const collectionCrossLink = computed(() => {
        // Business must have a primary category + a primary branch city
        const category = props.business.categories?.[0];
        const branch = props.primaryBranch;
        if (!category?.slug || !branch?.city?.slug) return null;
        return {
            href: `/${category.slug}-in-${branch.city.slug}`,
            label: `More ${category.name} in ${branch.city.name}`,
        };
    });
</script>