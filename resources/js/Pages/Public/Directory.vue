<!-- resources/js/Pages/Public/Directory.vue -->
<template>
    <PublicLayout>
        <!-- PHASE 15B - discovery UTILITY.
             Filter/sort/pagination permutations must not be indexed; `follow`
             keeps the Listing links inside the results crawlable. -->
        <Head v-if="seo">
            <title>{{ seo.title }}</title>
            <meta name="robots" :content="seo.robots || 'noindex, follow'" />
            <link rel="canonical" :href="seo.canonical" />
        </Head>

        <!-- PHASE 16F — Listing-first page identity.
             This surface returns LISTINGS; "Business Directory" named the
             organization as the discovery subject and framed the page as a
             company directory rather than a discovery surface. -->
        <div class="bg-surface dark:bg-gray-800 border-b border-hairline dark:border-hairline-dark">
            <div
                class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-4 sm:py-6 flex items-center justify-between gap-4 flex-wrap">
                <div>
                    <h1 class="text-heading-lg sm:text-heading-xl text-ink dark:text-white">Browse listings</h1>
                    <p class="text-body-sm text-ink-muted dark:text-gray-400 mt-0.5">
                        Discover businesses, stores and professionals near you
                    </p>
                </div>

                <!-- View toggle: selected state is not colour alone. -->
                <div class="inline-flex items-center gap-1 p-1 bg-gray-100 dark:bg-gray-700 rounded-control"
                    role="group" aria-label="Result view">
                    <button type="button" @click="switchView('list')" :aria-pressed="String(viewMode === 'list')"
                        :class="viewMode === 'list'
                            ? 'bg-surface dark:bg-gray-800 text-ink dark:text-white shadow-elevation-1'
                            : 'text-ink-muted dark:text-gray-400 hover:text-ink dark:hover:text-gray-200'"
                        class="inline-flex min-h-10 items-center gap-1.5 px-3 rounded-control text-body-sm font-semibold transition-all duration-fast focus:outline-none focus-visible:ring-2 focus-visible:ring-primary-500">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M4 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2V6zM14 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2V6zM4 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2v-2zM14 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2v-2z" />
                        </svg>
                        List
                    </button>
                    <button type="button" @click="switchView('map')" :aria-pressed="String(viewMode === 'map')"
                        :class="viewMode === 'map'
                            ? 'bg-surface dark:bg-gray-800 text-ink dark:text-white shadow-elevation-1'
                            : 'text-ink-muted dark:text-gray-400 hover:text-ink dark:hover:text-gray-200'"
                        class="inline-flex min-h-10 items-center gap-1.5 px-3 rounded-control text-body-sm font-semibold transition-all duration-fast focus:outline-none focus-visible:ring-2 focus-visible:ring-primary-500">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M9 20l-5.447-2.724A1 1 0 013 16.382V5.618a1 1 0 011.447-.894L9 7m0 13l6-3m-6 3V7m6 10l4.553 2.276A1 1 0 0021 18.382V7.618a1 1 0 00-.553-.894L15 4m0 13V4m0 0L9 7" />
                        </svg>
                        Map
                    </button>
                </div>
            </div>
        </div>

        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-5 sm:py-8">
            <!-- PHASE 16F — DESKTOP DISCOVERY LAYOUT.
                 At `lg` and above this becomes a persistent two-column surface:
                 a sticky filter rail beside the results/map. Below `lg` the grid
                 does not apply, so DirectoryFilters and MobileFilterSheet keep
                 their existing mobile behaviour untouched. -->
            <div class="lg:grid lg:grid-cols-[19rem_minmax(0,1fr)] lg:gap-8 lg:items-start">
                <aside class="mb-4 sm:mb-6 lg:mb-0 lg:sticky lg:top-24" aria-label="Filters">
                    <DirectoryFilters :filters="filters" :categories="categories" :countries="countries"
                        @applied="onFiltersApplied" />
                </aside>

                <div class="min-w-0">
                    <!-- Location-aware category chips (a discovery aid, so they are
                         hidden once the user has narrowed the result set). -->
                    <LocationChips v-if="!hasActiveFilters" class="mb-4 sm:mb-6" />

                    <!-- ==================== MAP VIEW ==================== -->
                    <div v-if="viewMode === 'map'">
                        <DirectoryMap :businesses="mapListings" />
                    </div>

                    <!-- ==================== ERROR ==================== -->
                    <div v-else-if="hasError"
                        class="text-center py-12 bg-surface dark:bg-gray-800 rounded-card border border-hairline dark:border-hairline-dark">
                        <h2 class="text-heading-md text-ink dark:text-white mb-2">Something went wrong</h2>
                        <p class="text-body text-ink-muted dark:text-gray-400">
                            These listings could not be loaded. Please try again.
                        </p>
                        <button type="button" @click="clearAllFilters"
                            class="mt-6 inline-flex min-h-11 items-center rounded-control bg-primary-600 px-4 text-body font-semibold text-white transition-colors duration-fast hover:bg-primary-700 focus:outline-none focus-visible:ring-2 focus-visible:ring-primary-500 focus-visible:ring-offset-2">
                            Try again
                        </button>
                    </div>

                    <!-- ==================== LIST VIEW ==================== -->
                    <div v-else>
                        <!-- PHASE 16F — loading: the 16C skeleton, aria-hidden,
                             shown only while an Inertia visit is in flight. -->
                        <div v-if="isRefreshing"
                            class="grid grid-cols-2 sm:grid-cols-2 lg:grid-cols-3 gap-3 sm:gap-6">
                            <ListingCardSkeleton v-for="n in 6" :key="n" />
                        </div>

                        <div v-else-if="listings.data && listings.data.length > 0">
                            <div class="grid grid-cols-2 sm:grid-cols-2 lg:grid-cols-3 gap-3 sm:gap-6">
                                <ListingCard v-for="listing in listings.data" :key="listing.id" :listing="listing" />
                            </div>

                            <Pagination :links="listings.links" :from="listings.from || 0" :to="listings.to || 0"
                                :total="listings.total || 0" class="mt-6" />
                        </div>

                        <!-- PHASE 16F — Empty state.
                             The resource is LISTINGS: "No businesses found" implied an
                             organization search this surface does not perform. The user
                             is also given a way out instead of a dead end. -->
                        <div v-else
                            class="text-center py-12 bg-surface dark:bg-gray-800 rounded-card border border-hairline dark:border-hairline-dark">
                            <div
                                class="w-16 h-16 bg-gray-100 dark:bg-gray-700 rounded-pill flex items-center justify-center mx-auto mb-4">
                                <svg class="w-8 h-8 text-ink-subtle" fill="none" stroke="currentColor"
                                    viewBox="0 0 24 24" aria-hidden="true">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                                </svg>
                            </div>

                            <h2 class="text-heading-md text-ink dark:text-white mb-2">
                                {{ hasActiveFilters ? 'No listings match these filters' : 'No listings here yet' }}
                            </h2>
                            <p class="text-body text-ink-muted dark:text-gray-400">
                                {{ hasActiveFilters
                                    ? 'Try removing a filter or widening your search.'
                                    : 'Try a different search, or explore by category or location.' }}
                            </p>

                            <div class="mt-6 flex flex-wrap items-center justify-center gap-3">
                                <button v-if="hasActiveFilters" type="button" @click="clearAllFilters"
                                    class="inline-flex min-h-11 items-center rounded-control bg-primary-600 px-4 text-body font-semibold text-white transition-colors duration-fast hover:bg-primary-700 focus:outline-none focus-visible:ring-2 focus-visible:ring-primary-500 focus-visible:ring-offset-2">
                                    Clear filters
                                </button>

                                <a href="/categories"
                                    class="inline-flex min-h-11 items-center rounded-control border border-hairline dark:border-hairline-dark px-4 text-body font-semibold text-ink dark:text-gray-100 transition-colors duration-fast hover:bg-gray-100 dark:hover:bg-gray-700 focus:outline-none focus-visible:ring-2 focus-visible:ring-primary-500">
                                    Browse categories
                                </a>
                                <a href="/locations"
                                    class="inline-flex min-h-11 items-center rounded-control border border-hairline dark:border-hairline-dark px-4 text-body font-semibold text-ink dark:text-gray-100 transition-colors duration-fast hover:bg-gray-100 dark:hover:bg-gray-700 focus:outline-none focus-visible:ring-2 focus-visible:ring-primary-500">
                                    Browse locations
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </PublicLayout>
</template>

<script setup>
    // PHASE 15B - indexation control
    import { Head, usePage } from '@inertiajs/vue3';
    import { computed } from 'vue';
    import { router } from '@inertiajs/vue3';
    import PublicLayout from '@/Layouts/PublicLayout.vue';
    import ListingCard from '@/Components/Public/ListingCard.vue';
    import Pagination from '@/Components/Pagination.vue';
    import DirectoryFilters from '@/Components/Public/DirectoryFilters.vue';
    import DirectoryMap from '@/Components/Public/DirectoryMap.vue';
    import LocationChips from '@/Components/Public/LocationChips.vue';
    // PHASE 16F — the consolidated 16C skeleton system (no new skeleton).
    import ListingCardSkeleton from '@/Components/Public/ui/ListingCardSkeleton.vue';
    import { onMounted, onBeforeUnmount, ref } from 'vue';
    import { writeDirectoryViewMode } from '@/composables/useDirectoryFilters';

    const props = defineProps({
        // PHASE 15B - indexation metadata from the controller.
        seo: { type: Object, default: null },
        listings: {
            type: [Object, Array],
            default: () => ({ data: [], links: [] }),
        },
        countries: Array,
        categories: Array,
        filters: Object,
        viewMode: {
            type: String,
            default: 'list',
        },
    });

    const viewMode = computed(() => props.viewMode || 'list');

    const page = usePage();

    /**
     * PHASE 16F — loading state.
     *
     * Inertia already shows a progress bar on navigation; this adds the
     * structural feedback the page was missing, using the consolidated
     * 16C skeleton rather than a new one.
     *
     * The listeners are registered inside onMounted, so nothing runs during
     * SSR and no browser API reaches a render path.
     */
    const isRefreshing = ref(false);
    let removeStart = null;
    let removeFinish = null;

    onMounted(() => {
        removeStart = router.on('start', () => {
            isRefreshing.value = true;
        });
        removeFinish = router.on('finish', () => {
            isRefreshing.value = false;
        });
    });

    onBeforeUnmount(() => {
        removeStart?.();
        removeFinish?.();
    });

    /**
     * PHASE 16F — recoverable error state.
     *
     * Surfaces only a human message; never a stack trace or API internals.
     * A validation-shaped `errors` bag is normal on a filter form, so only
     * the presence of a genuine error is treated as a failed load.
     */
    const hasError = computed(() => {
        const errors = page.props.errors || {};
        return Object.keys(errors).length > 0;
    });

    // Chips shown only when no filters are applied (they are a discovery aid).
    const hasActiveFilters = computed(() => {
        const f = props.filters || {};
        return Boolean(
            f.search ||
            f.category ||
            f.country_id ||
            f.region_id ||
            f.city_id ||
            f.open_now === 'true' ||
            f.open_now === true ||
            f.featured === 'true' ||
            f.featured === true ||
            f.has_photos === 'true' ||
            f.has_photos === true ||
            f.has_whatsapp === 'true' ||
            f.has_whatsapp === true ||
            (f.min_rating && f.min_rating > 0)
        );
    });

    /**
     * PHASE 16F — the map renders LISTINGS. This local variable was named after
     * a Business while the data has been Listing-based since Phase 1D-1.
     *
     * DirectoryMap's own prop is still called `businesses`; that is its existing
     * contract and renaming it touches 600+ lines of map internals, so it is
     * reported rather than changed in this pass.
     *
     * In map mode this is { data: [...] }; in list mode a paginator.
     */
    const mapListings = computed(() => {
        if (Array.isArray(props.listings)) return props.listings;
        return props.listings?.data || [];
    });


    // ============== VIEW SWITCH ==============
    const switchView = (mode) => {
        if (mode === viewMode.value) return;

        writeDirectoryViewMode(mode);

        const params = { ...(props.filters || {}) };

        // Clean up empty values
        Object.keys(params).forEach((k) => {
            if (params[k] === null || params[k] === '' || params[k] === undefined) {
                delete params[k];
            }
        });

        if (mode === 'map') {
            params.view = 'map';
        } else {
            delete params.view;
        }

        router.get('/directory', params, {
            preserveScroll: true,
            preserveState: false,
        });
    };

    // ============== FILTER APPLIED ==============
    // The filters component handles navigation and cookie writes itself.
    // The server-side redirect (DirectoryController::index) restores filters
    // on a bare /directory request — so no client-side restore logic is needed.
    const onFiltersApplied = () => {
        // no-op
    };

    /**
     * PHASE 16F — recovery action for the empty state. Returns to a bare
     * /directory, clearing every filter and the search term.
     */
    const clearAllFilters = () => {
        router.get('/directory', {}, { preserveScroll: false, preserveState: false });
    };
</script>
