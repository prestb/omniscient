<!-- resources/js/Pages/Public/Directory.vue -->
<template>
    <PublicLayout>
        <!-- Header (inlined — PublicLayout has no #header slot) -->
        <div class="bg-white dark:bg-gray-800 border-b border-gray-200 dark:border-gray-700">
            <div
                class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-4 sm:py-6 flex items-center justify-between gap-4 flex-wrap">
                <div>
                    <h1 class="text-xl sm:text-2xl font-bold text-gray-900 dark:text-white">Business Directory</h1>
                    <p class="text-xs sm:text-sm text-gray-500 dark:text-gray-400 mt-0.5">Find businesses, services, and
                        institutions</p>
                </div>

                <!-- ✅ View toggle -->
                <div class="inline-flex items-center gap-1 p-1 bg-gray-100 dark:bg-gray-700 rounded-xl">
                    <button type="button" @click="switchView('list')" :class="viewMode === 'list'
                        ? 'bg-white dark:bg-gray-800 text-gray-900 dark:text-white shadow-sm'
                        : 'text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-200'"
                        class="inline-flex items-center gap-1.5 px-3 py-2 rounded-lg text-sm font-semibold transition-all">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M4 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2V6zM14 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2V6zM4 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2v-2zM14 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2v-2z" />
                        </svg>
                        List
                    </button>
                    <button type="button" @click="switchView('map')" :class="viewMode === 'map'
                        ? 'bg-white dark:bg-gray-800 text-gray-900 dark:text-white shadow-sm'
                        : 'text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-200'"
                        class="inline-flex items-center gap-1.5 px-3 py-2 rounded-lg text-sm font-semibold transition-all">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M9 20l-5.447-2.724A1 1 0 013 16.382V5.618a1 1 0 011.447-.894L9 7m0 13l6-3m-6 3V7m6 10l4.553 2.276A1 1 0 0021 18.382V7.618a1 1 0 00-.553-.894L15 4m0 13V4m0 0L9 7" />
                        </svg>
                        Map
                    </button>
                </div>
            </div>
        </div>

        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-5 sm:py-8">
            <!-- Enhanced Filters -->
            <DirectoryFilters :filters="filters" :categories="categories" :countries="countries" class="mb-4 sm:mb-6"
                @applied="onFiltersApplied" />

            <!-- ✅ Location-aware category chips (only when no filters active) -->
            <LocationChips v-if="!hasActiveFilters" class="mb-4 sm:mb-6" />

            <!-- ==================== MAP VIEW ==================== -->
            <div v-if="viewMode === 'map'">
                <DirectoryMap :businesses="mapBusinesses" />
            </div>

            <!-- ==================== LIST VIEW ==================== -->
            <div v-else>
                <div v-if="listings.data && listings.data.length > 0">
                    <div class="grid grid-cols-2 sm:grid-cols-2 lg:grid-cols-3 gap-3 sm:gap-6">
                        <!-- Naming debt (1D-6): BusinessCard's prop is `business`, the entity is a Listing. -->
                        <BusinessCard v-for="listing in listings.data" :key="listing.id" :business="listing" />
                    </div>

                    <Pagination :links="listings.links" :from="listings.from || 0" :to="listings.to || 0"
                        :total="listings.total || 0" class="mt-6" />
                </div>

                <!-- Empty State -->
                <div v-else
                    class="text-center py-12 bg-white dark:bg-gray-800 rounded-2xl shadow-sm border border-gray-100 dark:border-gray-700">
                    <div
                        class="w-16 h-16 bg-gray-100 dark:bg-gray-700 rounded-full flex items-center justify-center mx-auto mb-4">
                        <svg class="w-8 h-8 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                        </svg>
                    </div>
                    <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-2">No businesses found</h3>
                    <p class="text-gray-500 dark:text-gray-400 text-sm">Try adjusting your filters or search terms.</p>
                </div>
            </div>
        </div>
    </PublicLayout>
</template>

<script setup>
    import { computed } from 'vue';
    import { router } from '@inertiajs/vue3';
    import PublicLayout from '@/Layouts/PublicLayout.vue';
    import BusinessCard from '@/Components/Public/BusinessCard.vue';
    import Pagination from '@/Components/Pagination.vue';
    import DirectoryFilters from '@/Components/Public/DirectoryFilters.vue';
    import DirectoryMap from '@/Components/Public/DirectoryMap.vue';
    import LocationChips from '@/Components/Public/LocationChips.vue';
    import {
        readDirectoryFilters,
        readDirectoryViewMode,
        writeDirectoryViewMode,
    } from '@/composables/useDirectoryFilters';

    const props = defineProps({
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

    // ✅ Chips shown only when no filters are applied (they're a discovery aid)
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
            f.verified === 'true' ||
            f.verified === true ||
            f.has_photos === 'true' ||
            f.has_photos === true ||
            f.has_whatsapp === 'true' ||
            f.has_whatsapp === true ||
            (f.min_rating && f.min_rating > 0)
        );
    });

    // In map mode, `businesses` is an object with { data: [...] } shape.
    // In list mode, it's a Laravel paginator.
    const mapBusinesses = computed(() => {
        if (Array.isArray(props.listings)) return props.listings;
        return props.listings?.data || [];
    });

    // ============== VIEW SWITCH ==============
    const switchView = (mode) => {
        if (mode === viewMode.value) return;

        // ✅ Persist user's view choice via cookie
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
</script>