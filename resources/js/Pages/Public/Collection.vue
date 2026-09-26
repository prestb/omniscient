<!-- resources/js/Pages/Public/Collection.vue -->
<template>
    <PublicLayout>
        <!-- SEO head -->

        <Head>
            <title>{{ seo.title }}</title>
            <meta name="description" :content="seo.description" />
            <link rel="canonical" :href="seo.canonical" />
            <meta property="og:title" :content="seo.title" />
            <meta property="og:description" :content="seo.description" />
            <meta property="og:type" content="website" />
            <meta property="og:url" :content="seo.canonical" />
            <!-- JSON-LD injected via onMounted (see script below) -->
        </Head>

        <!-- ============== HERO ============== -->
        <section class="bg-white dark:bg-gray-800 border-b border-gray-200 dark:border-gray-700">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-6 sm:py-10">
                <!-- Breadcrumb -->
                <nav class="flex items-center gap-1.5 text-xs text-gray-500 dark:text-gray-400 mb-4 flex-wrap">
                    <Link href="/" class="hover:text-primary-600 dark:hover:text-primary-400 transition-colors">Home
                    </Link>
                    <span class="text-gray-300 dark:text-gray-600">›</span>
                    <Link :href="`/directory?category=${category.id}`"
                        class="hover:text-primary-600 dark:hover:text-primary-400 transition-colors">
                        {{ category.name }}
                    </Link>
                    <span class="text-gray-300 dark:text-gray-600">›</span>
                    <span class="text-gray-900 dark:text-white font-semibold">{{ city.name }}</span>
                </nav>

                <!-- H1 + subtitle -->
                <h1 class="text-2xl sm:text-4xl font-bold text-gray-900 dark:text-white tracking-tight">
                    {{ seo.h1 }}
                </h1>
                <p class="text-sm sm:text-base text-gray-600 dark:text-gray-400 mt-2">
                    {{ total }} {{ total === 1 ? 'business' : 'businesses' }}
                    <template v-if="region || country">
                        · {{ [city.name, region?.name, country?.name].filter(Boolean).join(', ') }}
                    </template>
                </p>

                <!-- Description card -->
                <div
                    class="mt-6 flex items-start gap-4 p-4 sm:p-5 rounded-2xl bg-gray-50 dark:bg-gray-900/50 border border-gray-100 dark:border-gray-700">
                    <div
                        class="w-12 h-12 rounded-xl bg-primary-50 dark:bg-primary-900/30 flex items-center justify-center flex-shrink-0">
                        <CategoryIcon :icon="category.icon" size="lg" />
                    </div>
                    <div class="min-w-0">
                        <p class="text-sm font-bold text-gray-900 dark:text-white">
                            {{ category.name }} in {{ city.name }}
                        </p>
                        <p class="text-xs sm:text-sm text-gray-600 dark:text-gray-400 mt-1 leading-relaxed">
                            Find {{ total }} {{ category.name.toLowerCase() }} in {{ city.name }}, Cameroon. Browse
                            listings, read
                            reviews, and contact directly.
                        </p>
                    </div>
                </div>
            </div>
        </section>

        <!-- ============== RESULTS ============== -->
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-6 sm:py-10">
            <!-- Sort bar -->
            <div class="flex items-center justify-between mb-4 flex-wrap gap-3">
                <p class="text-sm text-gray-500 dark:text-gray-400">
                    Showing <span class="font-semibold text-gray-900 dark:text-white">{{ businesses.from || 0 }}–{{
                        businesses.to || 0 }}</span>
                    of <span class="font-semibold text-gray-900 dark:text-white">{{ total }}</span>
                </p>

                <div class="flex items-center gap-2">
                    <label
                        class="text-xs text-gray-500 dark:text-gray-400 uppercase tracking-wide font-semibold">Sort</label>
                    <select v-model="sortLocal" @change="applySort"
                        class="px-3 py-1.5 border border-gray-200 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-900 text-gray-900 dark:text-white text-sm focus:ring-2 focus:ring-primary-500">
                        <option value="newest">Newest</option>
                        <option value="rating">Highest Rated</option>
                        <option value="reviews">Most Reviewed</option>
                        <option value="name">Name A–Z</option>
                    </select>
                </div>
            </div>

            <!-- Grid -->
            <div v-if="businesses.data && businesses.data.length > 0">
                <div class="grid grid-cols-2 sm:grid-cols-2 lg:grid-cols-3 gap-3 sm:gap-6">
                    <BusinessCard v-for="business in businesses.data" :key="business.id" :business="business" />
                </div>

                <Pagination :links="businesses.links" :from="businesses.from || 0" :to="businesses.to || 0"
                    :total="businesses.total || 0" class="mt-8" />
            </div>

            <!-- Empty state — shouldn't happen due to threshold, but defensive -->
            <div v-else
                class="text-center py-12 bg-white dark:bg-gray-800 rounded-2xl border border-gray-100 dark:border-gray-700">
                <div
                    class="w-16 h-16 bg-gray-100 dark:bg-gray-700 rounded-full flex items-center justify-center mx-auto mb-4">
                    <svg class="w-8 h-8 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                    </svg>
                </div>
                <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-2">No listings found</h3>
                <p class="text-gray-500 dark:text-gray-400 text-sm">Try browsing the full directory.</p>
                <Link href="/directory"
                    class="inline-flex items-center gap-2 px-5 py-2.5 mt-4 bg-gradient-to-r from-primary-600 to-primary-700 text-white rounded-xl font-semibold text-sm hover:from-primary-700 hover:to-primary-800 transition-all shadow-lg shadow-primary-500/25">
                    Browse All Businesses
                </Link>
            </div>

            <!-- ============== CROSS-LINKS ============== -->
            <div v-if="hasCrossLinks" class="mt-12 pt-8 border-t border-gray-200 dark:border-gray-700">
                <CollectionCrossLinks :other-categories="otherCategories" :other-cities="otherCities"
                    :city-name="city.name" :category-name="category.name" />
            </div>
        </div>
    </PublicLayout>
</template>

<script setup>
    import { ref, computed, onMounted, onUnmounted } from 'vue';
    import { Head, Link, router } from '@inertiajs/vue3';
    import PublicLayout from '@/Layouts/PublicLayout.vue';
    import BusinessCard from '@/Components/Public/BusinessCard.vue';
    import Pagination from '@/Components/Pagination.vue';
    import CategoryIcon from '@/Components/CategoryIcon.vue';
    import CollectionCrossLinks from '@/Components/Public/CollectionCrossLinks.vue';

    const props = defineProps({
        category: { type: Object, required: true },
        city: { type: Object, required: true },
        region: { type: Object, default: null },
        country: { type: Object, default: null },
        businesses: { type: Object, required: true },
        total: { type: Number, default: 0 },
        categoryTotal: { type: Number, default: 0 },
        sort: { type: String, default: 'newest' },
        otherCategories: { type: Array, default: () => [] },
        otherCities: { type: Array, default: () => [] },
        seo: { type: Object, required: true },
    });

    const sortLocal = ref(props.sort);

    const hasCrossLinks = computed(() =>
        (props.otherCategories && props.otherCategories.length > 0) ||
        (props.otherCities && props.otherCities.length > 0)
    );

    const applySort = () => {
        const slug = `${props.category.slug}-in-${props.city.slug}`;
        router.get(`/${slug}`, { sort: sortLocal.value }, {
            preserveScroll: true,
            preserveState: false,
        });
    };

    // Schema.org ItemList JSON-LD
    const schemaJson = computed(() => {
        const items = (props.businesses.data || []).map((b, i) => ({
            '@type': 'ListItem',
            position: i + 1,
            url: `${window.location.origin}/business/${b.slug}`,
            name: b.name,
        }));

        return JSON.stringify({
            '@context': 'https://schema.org',
            '@type': 'ItemList',
            name: props.seo.h1,
            numberOfItems: props.total,
            itemListElement: items,
        });
    });

    // ✅ Inject JSON-LD into <head> on mount — Vue can't compile <script> tags
    //    inside templates, so we create the element imperatively.
    const JSONLD_ID = 'collection-jsonld';

    const injectJsonLd = () => {
        // Remove any stale element (e.g. after Inertia navigation)
        document.getElementById(JSONLD_ID)?.remove();

        const script = document.createElement('script');
        script.type = 'application/ld+json';
        script.id = JSONLD_ID;
        script.textContent = schemaJson.value;
        document.head.appendChild(script);
    };

    onMounted(() => {
        injectJsonLd();
    });

    onUnmounted(() => {
        // Clean up when navigating away
        document.getElementById(JSONLD_ID)?.remove();
    });
</script>