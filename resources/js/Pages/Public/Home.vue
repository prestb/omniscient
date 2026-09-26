<!-- resources/js/Pages/Public/Home.vue -->
<template>
    <PublicLayout>
        <PullToRefresh ref="ptrRef" @refresh="refreshHome">
            <!-- ============================ HERO ============================ -->
            <section
                class="relative overflow-hidden bg-gradient-to-br from-primary-700 via-primary-600 to-primary-800 text-white">
                <!-- Decorative dot pattern -->
                <div class="absolute inset-0 opacity-[0.07]"
                    style="background-image: radial-gradient(circle, white 1px, transparent 1px); background-size: 22px 22px;">
                </div>

                <!-- Soft blurred blobs -->
                <div class="absolute -top-24 -left-24 w-96 h-96 bg-primary-400/30 rounded-full blur-3xl"></div>
                <div class="absolute -bottom-32 -right-24 w-[28rem] h-[28rem] bg-primary-500/20 rounded-full blur-3xl">
                </div>

                <div class="relative max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-14 sm:py-24 md:py-28">
                    <div class="text-center max-w-3xl mx-auto">
                        <span
                        class="inline-flex items-center gap-2 px-3 py-1 rounded-full bg-white/10 border border-white/20 text-[10px] sm:text-xs font-medium tracking-wide uppercase text-primary-100 mb-4 sm:mb-6 backdrop-blur-sm">
                        <span class="w-1.5 h-1.5 rounded-full bg-emerald-400 animate-pulse"></span>
                        Trusted by thousands across Cameroon
                    </span>

                        <h1
                            class="text-3xl sm:text-4xl md:text-6xl font-bold mb-4 sm:mb-5 leading-tight tracking-tight">
                            Discover Local
                            <span class="bg-gradient-to-r from-amber-300 to-yellow-200 bg-clip-text text-transparent">
                                Businesses
                            </span>
                            in Cameroon
                        </h1>
                        <!-- <p class="text-base sm:text-lg md:text-xl text-primary-100 mb-6 sm:mb-9 max-w-2xl mx-auto">
                        Find trusted businesses, services, and institutions near you — all in one place.
                    </p> -->

                        <!-- Search bar with glow -->
                        <div class="max-w-2xl mx-auto relative">
                            <div class="absolute -inset-1 bg-white/20 rounded-3xl blur-lg"></div>
                            <div class="relative">
                                <SearchBar />
                            </div>
                        </div>

                        <!-- CTAs -->
                        <!-- <div class="mt-6 sm:mt-8 flex flex-wrap justify-center gap-2 sm:gap-3">
                        <a href="/directory"
                            class="inline-flex items-center gap-2 px-5 sm:px-6 py-2.5 sm:py-3 bg-white text-primary-700 rounded-xl font-semibold hover:bg-gray-50 hover:shadow-xl hover:-translate-y-0.5 transition-all text-sm sm:text-base"><svg
                                class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                            </svg>
                            Browse Directory
                        </a>
                        <a href="/register"
                            class="inline-flex items-center gap-2 px-5 sm:px-6 py-2.5 sm:py-3 border-2 border-white/70 text-white rounded-xl font-semibold hover:bg-white hover:text-primary-700 transition-all text-sm sm:text-base">
                            List Your Business
                        </a>
                    </div> -->

                        <!-- Trust stat strip -->
                        <div
                            class="mt-8 sm:mt-12 grid grid-cols-3 max-w-lg mx-auto divide-x divide-white/15 border-t border-white/15 pt-5 sm:pt-6">
                            <div class="px-3">
                                <p class="text-2xl font-bold text-white">{{ stats.businesses }}+</p>
                                <p class="text-xs uppercase tracking-wide text-primary-200 mt-0.5">Businesses</p>
                            </div>
                            <div class="px-3">
                                <p class="text-2xl font-bold text-white">{{ stats.reviews }}+</p>
                                <p class="text-xs uppercase tracking-wide text-primary-200 mt-0.5">Reviews</p>
                            </div>
                            <div class="px-3">
                                <p class="text-2xl font-bold text-white">{{ stats.categories }}+</p>
                                <p class="text-xs uppercase tracking-wide text-primary-200 mt-0.5">Categories</p>
                            </div>
                        </div>
                    </div>
                </div>
            </section>

            <!-- ==================== CATEGORY ICON STRIP ==================== -->
            <SkeletonLoader v-if="isRefreshing && stripCategories.length > 0" variant="category-strip" :count="8" />
                    <section v-else-if="stripCategories.length > 0" class="bg-white dark:bg-gray-800 border-b border-gray-100 dark:border-gray-700 overflow-hidden">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-4 sm:py-6 overflow-hidden">
                    <!-- Mobile: horizontal scroll -->
                    <div
                        class="sm:hidden -mx-4 px-4 scroll-pl-4 flex gap-2 overflow-x-auto scrollbar-hide snap-x snap-mandatory">
                        <a v-for="category in stripCategories" :key="category.id"
                            :href="`/directory?category=${category.id}`"
                            class="flex-shrink-0 w-20 snap-start flex flex-col items-center gap-1.5 p-2 rounded-2xl hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors group">
                            <span
                                class="w-12 h-12 rounded-2xl bg-gradient-to-br from-primary-50 to-primary-100 dark:from-primary-900/30 dark:to-primary-900/20 flex items-center justify-center shadow-sm group-hover:shadow-md transition-shadow text-primary-600 dark:text-primary-400">
                                <CategoryIcon :icon="category.icon" size="lg" />
                            </span>
                            <span
                                class="text-[10px] font-semibold text-gray-700 dark:text-gray-300 text-center leading-tight line-clamp-2 px-1">
                                {{ category.name }}
                            </span>
                        </a>
                    </div>

                    <!-- Desktop: centered row -->
                    <div class="hidden sm:flex items-start justify-center gap-4 lg:gap-6 flex-wrap">
                        <a v-for="category in stripCategories" :key="category.id"
                            :href="`/directory?category=${category.id}`"
                            class="flex flex-col items-center gap-2 p-2 rounded-2xl hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors group min-w-[80px]">
                            <span
                                class="w-14 h-14 rounded-2xl bg-gradient-to-br from-primary-50 to-primary-100 dark:from-primary-900/30 dark:to-primary-900/20 flex items-center justify-center shadow-sm group-hover:shadow-md group-hover:-translate-y-0.5 transition-all text-primary-600 dark:text-primary-400">
                                <CategoryIcon :icon="category.icon" size="xl" />
                            </span>
                            <span
                                class="text-xs font-semibold text-gray-700 dark:text-gray-300 text-center max-w-[90px] line-clamp-2 leading-tight">
                                {{ category.name }}
                            </span>
                        </a>
                    </div>
                </div>
            </section>


            <!-- ==================== POPULAR CATEGORIES ==================== -->
            <section class="py-12 sm:py-20 bg-white dark:bg-gray-900 overflow-hidden">
                <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 overflow-hidden">
                    <div class="text-center mb-8 sm:mb-12">
                        <span class="text-xs uppercase tracking-widest text-primary-600 dark:text-primary-400 font-semibold">Explore</span>
                        <h2 class="text-2xl sm:text-3xl md:text-4xl font-bold text-gray-900 dark:text-white mt-2">Popular Categories
                        </h2>
                        <p class="text-sm sm:text-base text-gray-500 dark:text-gray-400 mt-2 sm:mt-3 max-w-xl mx-auto">Browse thousands of
                            listings across the categories people search for most.</p>
                    </div>

                    <!-- Skeleton during refresh -->
                    <SkeletonLoader v-if="isRefreshing" variant="grid-cards" :count="6" />

                    <!-- Real content -->
                    <template v-else>
                        <!-- Mobile: horizontal scroll-snap carousel -->
                        <div
                            class="sm:hidden -mx-4 px-4 scroll-pl-4 flex gap-3 overflow-x-auto scrollbar-hide snap-x snap-mandatory">
                            <a v-for="(category, idx) in popularCategories" :key="category.id"
                                :href="`/directory?category=${category.id}`"
                                class="flex-shrink-0 w-[45%] snap-start group relative p-3.5 rounded-2xl text-center border transition-all duration-300"
                                :class="idx === 0
    ? 'bg-gradient-to-br from-primary-600 to-primary-700 border-primary-700 text-white'
    : 'bg-white dark:bg-gray-800 border-gray-100 dark:border-gray-700 hover:border-primary-200 dark:hover:border-primary-800'">
                                <span class="block mb-2 transition-transform group-hover:scale-110"
                                    :class="idx === 0 ? 'text-white' : 'text-primary-600 dark:text-primary-400'">
                                    <CategoryIcon :icon="category.icon" size="lg" class="mx-auto" />
                                </span>
                                <span class="block text-sm font-semibold line-clamp-1"
                                    :class="idx === 0 ? 'text-white' : 'text-gray-900'">
                                    {{ category.name }}
                                </span>
                                <span
                                    class="mt-2 inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-medium"
                                    :class="idx === 0 ? 'bg-white/20 text-white' : 'bg-gray-100 dark:bg-gray-700 text-gray-500 dark:text-gray-400'">
                                    {{ category.businesses_count || 0 }} listings
                                </span>
                            </a>
                        </div>

                        <!-- Desktop: grid -->
                        <div class="hidden sm:grid sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-6 gap-3 sm:gap-4">
                            <a v-for="(category, idx) in popularCategories" :key="category.id"
                                :href="`/directory?category=${category.id}`"
                                class="group relative p-3.5 sm:p-5 rounded-2xl text-center border transition-all duration-300 hover:-translate-y-1 hover:shadow-lg"
                                :class="idx === 0
    ? 'bg-gradient-to-br from-primary-600 to-primary-700 border-primary-700 text-white hover:shadow-primary-500/30'
    : 'bg-white dark:bg-gray-800 border-gray-100 dark:border-gray-700 hover:border-primary-200 dark:hover:border-primary-800'">
                                <span class="block mb-2 sm:mb-3 transition-transform group-hover:scale-110"
                                    :class="idx === 0 ? 'text-white' : 'text-primary-600'">
                                    <CategoryIcon :icon="category.icon" size="lg" class="mx-auto" />
                                </span>
                                <span class="block text-sm font-semibold line-clamp-1"
                                    :class="idx === 0 ? 'text-white' : 'text-gray-900 dark:text-white'">
                                    {{ category.name }}
                                </span>
                                <span
                                    class="mt-2 inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-medium"
                                    :class="idx === 0 ? 'bg-white/20 text-white' : 'bg-gray-100 text-gray-500'">
                                    {{ category.businesses_count || 0 }} listings
                                </span>
                            </a>
                        </div>
                    </template>
                </div>
            </section>

            <!-- ==================== FEATURED BUSINESSES ==================== -->
            <section class="relative py-12 sm:py-20 bg-gradient-to-b from-amber-50/40 to-white dark:from-amber-950/10 dark:to-gray-900">
                <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                    <div class="flex flex-wrap items-end justify-between gap-3 sm:gap-4 mb-6 sm:mb-10">
                        <div>
                            <span
                                class="text-xs uppercase tracking-widest text-amber-600 font-semibold">Handpicked</span>
                            <h2
    class="text-2xl sm:text-3xl md:text-4xl font-bold text-gray-900 dark:text-white mt-2 flex items-center gap-2">
                                <!-- <span>⭐</span> Featured Businesses -->
                            </h2>
                            <p class="text-sm sm:text-base text-gray-500 dark:text-gray-400 mt-1.5 sm:mt-2">Premium listings trusted by our
                                community.</p>
                        </div>
                        <a href="/directory?featured=true"
                            class="inline-flex items-center gap-1 text-sm font-semibold text-primary-600 dark:text-primary-400 hover:text-primary-800 dark:hover:text-primary-300 group">
                            View All
                            <span class="group-hover:translate-x-1 transition-transform">→</span>
                        </a>
                    </div>

                    <!-- Skeleton during refresh -->
                    <template v-if="isRefreshing">
                        <!-- Mobile: horizontal carousel of business-card skeletons -->
                        <div class="sm:hidden -mx-4 px-4 flex gap-3 overflow-hidden">
                            <div v-for="n in 3" :key="n" class="flex-shrink-0 w-[62%]">
                                <SkeletonLoader variant="business-card" />
                            </div>
                        </div>
                        <!-- Desktop: grid of business-card skeletons -->
                        <div class="hidden sm:grid sm:grid-cols-2 lg:grid-cols-3 gap-4 sm:gap-6">
                            <SkeletonLoader v-for="n in 3" :key="n" variant="business-card" />
                        </div>
                    </template>

                    <!-- Real content -->
                    <template v-else>
                        <!-- Mobile: horizontal snap-scroll carousel -->
                        <div
                            class="sm:hidden -mx-4 px-4 scroll-pl-4 flex gap-3 overflow-x-auto scrollbar-hide snap-x snap-mandatory">
                            <div v-for="business in featuredBusinesses" :key="business.id"
                                class="flex-shrink-0 w-[62%] snap-start">
                                <BusinessCard :business="business" />
                            </div>
                        </div>

                        <!-- Desktop: grid -->
                        <div class="hidden sm:grid sm:grid-cols-2 lg:grid-cols-3 gap-4 sm:gap-6">
                            <BusinessCard v-for="business in featuredBusinesses" :key="business.id"
                                :business="business" />
                        </div>
                    </template>
                </div>
            </section>

            <!-- ==================== LATEST REVIEWS ==================== -->
            <section v-if="latestReviews.length > 0 || isRefreshing"
                class="py-12 sm:py-20 bg-gradient-to-b from-gray-50/60 to-white dark:from-gray-900/60 dark:to-gray-900">
                <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                    <div class="flex flex-wrap items-end justify-between gap-3 sm:gap-4 mb-6 sm:mb-10">
                        <div>
                            <span
                                class="text-xs uppercase tracking-widest text-primary-600 font-semibold">Community</span>
                            <h2 class="text-2xl sm:text-3xl md:text-4xl font-bold text-gray-900 dark:text-white mt-2">Latest Reviews
                            </h2>
                            <p class="text-sm sm:text-base text-gray-500 dark:text-gray-400 mt-1.5 sm:mt-2">What customers are saying about
                                local businesses.</p>
                        </div>
                    </div>

                    <!-- Skeleton during refresh -->
                    <template v-if="isRefreshing">
                        <div class="sm:hidden -mx-4 px-4 flex gap-3 overflow-hidden">
                            <div v-for="n in 3" :key="n" class="flex-shrink-0 w-[78%]">
                                <SkeletonLoader variant="review-card" />
                            </div>
                        </div>
                        <div class="hidden sm:grid sm:grid-cols-2 lg:grid-cols-3 gap-4 sm:gap-6">
                            <SkeletonLoader v-for="n in 3" :key="n" variant="review-card" />
                        </div>
                    </template>

                    <!-- Real content -->
                    <template v-else>
                        <!-- Mobile: horizontal snap-scroll carousel -->
                        <div
                            class="sm:hidden -mx-4 px-4 scroll-pl-4 flex gap-3 overflow-x-auto scrollbar-hide snap-x snap-mandatory">
                            <div v-for="review in latestReviews" :key="review.id"
                                class="flex-shrink-0 w-[78%] snap-start">
                                <ReviewCardCompact :review="review" />
                            </div>
                        </div>

                        <!-- Desktop: grid -->
                        <div class="hidden sm:grid sm:grid-cols-2 lg:grid-cols-3 gap-4 sm:gap-6">
                            <ReviewCardCompact v-for="review in latestReviews" :key="review.id" :review="review" />
                        </div>
                    </template>
                </div>
            </section>

            <!-- ==================== RECENT BUSINESSES ==================== -->
            <section class="py-12 sm:py-20 bg-white dark:bg-gray-900">
                <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                    <div class="flex flex-wrap items-end justify-between gap-3 sm:gap-4 mb-6 sm:mb-10">
                        <div>
                            <span class="text-xs uppercase tracking-widest text-primary-600 font-semibold">Fresh</span>
                            <h2 class="text-2xl sm:text-3xl md:text-4xl font-bold text-gray-900 dark:text-white mt-2">Recently Added
                            </h2>
                            <p class="text-sm sm:text-base text-gray-500 dark:text-gray-400 mt-1.5 sm:mt-2">The newest businesses joining
                                the
                                platform.</p>
                        </div>
                        <a href="/directory"
                            class="inline-flex items-center gap-1 text-sm font-semibold text-primary-600 hover:text-primary-800 group">
                            View All
                            <span class="group-hover:translate-x-1 transition-transform">→</span>
                        </a>
                    </div>

                    <!-- Skeleton during refresh -->
                    <template v-if="isRefreshing">
                        <div class="sm:hidden -mx-4 px-4 flex gap-3 overflow-hidden">
                            <div v-for="n in 3" :key="n" class="flex-shrink-0 w-[62%]">
                                <SkeletonLoader variant="business-card" />
                            </div>
                        </div>
                        <div class="hidden sm:grid sm:grid-cols-2 lg:grid-cols-4 gap-4 sm:gap-6">
                            <SkeletonLoader v-for="n in 4" :key="n" variant="business-card" />
                        </div>
                    </template>

                    <!-- Real content -->
                    <template v-else>
                        <!-- Mobile: horizontal snap-scroll carousel -->
                        <div
                            class="sm:hidden -mx-4 px-4 scroll-pl-4 flex gap-3 overflow-x-auto scrollbar-hide snap-x snap-mandatory">
                            <div v-for="business in recentBusinesses" :key="business.id"
                                class="flex-shrink-0 w-[62%] snap-start">
                                <BusinessCard :business="business" />
                            </div>
                        </div>

                        <!-- Desktop: grid -->
                        <div class="hidden sm:grid sm:grid-cols-2 lg:grid-cols-4 gap-4 sm:gap-6">
                            <BusinessCard v-for="business in recentBusinesses" :key="business.id"
                                :business="business" />
                        </div>
                    </template>
                </div>
            </section>

            <!-- ==================== CTA ==================== -->
            <section class="py-12 sm:py-20 bg-gray-50 dark:bg-gray-900">
                <div class="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8">
                    <div
                        class="relative overflow-hidden rounded-3xl bg-gradient-to-br from-primary-700 via-primary-600 to-primary-800 text-white p-6 sm:p-10 md:p-14">
                        <div class="absolute inset-0 opacity-[0.06]"
                            style="background-image: radial-gradient(circle, white 1px, transparent 1px); background-size: 22px 22px;">
                        </div>
                        <div class="absolute -top-16 -right-16 w-72 h-72 bg-primary-400/30 rounded-full blur-3xl"></div>

                        <div class="relative grid md:grid-cols-2 gap-6 sm:gap-10 items-center">
                            <div>
                                <h2 class="text-2xl sm:text-3xl md:text-4xl font-bold mb-3 sm:mb-4 leading-tight">
                                    List Your Business Today
                                </h2>
                                <p class="text-primary-100 text-sm sm:text-lg mb-5 sm:mb-8">
                                    Join thousands of businesses getting discovered by customers across Cameroon.
                                </p>
                                <div class="flex flex-wrap gap-2 sm:gap-3">
                                    <a href="/register"
                                        class="inline-flex items-center gap-2 px-6 py-3 bg-white text-primary-700 rounded-xl font-semibold hover:bg-gray-50 hover:shadow-xl transition-all">
                                        Get Started
                                        <span>→</span>
                                    </a>
                                    <a href="/pricing"
                                        class="inline-flex items-center gap-2 px-6 py-3 border-2 border-white/70 text-white rounded-xl font-semibold hover:bg-white/10 transition-all">
                                        View Pricing
                                    </a>
                                </div>
                            </div>

                            <!-- Feature list card -->
                            <div class="bg-white/10 backdrop-blur-sm rounded-2xl p-5 sm:p-6 border border-white/20">
                                <ul class="space-y-3">
                                    <li v-for="item in ctaFeatures" :key="item" class="flex items-start gap-3">
                                        <span
                                            class="mt-0.5 w-5 h-5 rounded-full bg-emerald-400 flex items-center justify-center flex-shrink-0">
                                            <svg class="w-3 h-3 text-emerald-900" fill="none" stroke="currentColor"
                                                viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3"
                                                    d="M5 13l4 4L19 7" />
                                            </svg>
                                        </span>
                                        <span class="text-sm text-primary-50">{{ item }}</span>
                                    </li>
                                </ul>
                            </div>
                        </div>
                    </div>
                </div>
            </section>
        </PullToRefresh>
    </PublicLayout>
</template>

<style scoped>
    .scrollbar-hide {
        -ms-overflow-style: none;
        scrollbar-width: none;
    }

    .scrollbar-hide::-webkit-scrollbar {
        display: none;
    }
</style>



<script setup>
    import { computed, ref } from 'vue';
    import { router } from '@inertiajs/vue3';
    import PublicLayout from '@/Layouts/PublicLayout.vue';
    import SearchBar from '@/Components/Public/SearchBar.vue';
    import BusinessCard from '@/Components/Public/BusinessCard.vue';
    import ReviewCardCompact from '@/Components/Public/ReviewCardCompact.vue';
    import CategoryIcon from '@/Components/CategoryIcon.vue';
    import PullToRefresh from '@/Components/Common/PullToRefresh.vue';
    import SkeletonLoader from '@/Components/Common/SkeletonLoader.vue';

    const props = defineProps({
        featuredBusinesses: { type: Array, default: () => [] },
        recentBusinesses: { type: Array, default: () => [] },
        popularCategories: { type: Array, default: () => [] },
        stripCategories: { type: Array, default: () => [] },
        latestReviews: { type: Array, default: () => [] },
        stats: { type: Object, default: () => ({}) },
    });

    /**
     * Stats shown in the hero strip.
     * If the controller doesn't pass `stats`, we derive safe fallbacks from
     * what we already have so nothing renders as "undefined".
     */
    const stats = computed(() => ({
        businesses: props.stats?.businesses ?? '1000',
        reviews: props.stats?.reviews ?? '5000',
        categories: props.stats?.categories ?? (props.popularCategories?.length || '20'),
    }));

    const ctaFeatures = [
        'Reach thousands of local customers',
        'Showcase photos, hours & services',
        'Respond to reviews & capture leads',
        'Verified badges on premium plans',
    ];

    // ============== PULL-TO-REFRESH ==============
    const ptrRef = ref(null);
    const isRefreshing = ref(false);

    const refreshHome = () => {
        isRefreshing.value = true;
        router.reload({
            preserveScroll: true,
            onFinish: () => {
                isRefreshing.value = false;
                ptrRef.value?.done();
            },
        });
    };
</script>