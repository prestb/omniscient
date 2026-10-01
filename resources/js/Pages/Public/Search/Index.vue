<!-- resources/js/Pages/Public/Search/Index.vue -->
<template>
    <PublicLayout>
        <!-- Header -->
        <div class="bg-white border-b border-gray-200">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-4 sm:py-6">
                <a href="/"
                    class="text-xs sm:text-sm text-primary-600 hover:text-primary-800 font-medium inline-flex items-center gap-1">
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M10 19l-7-7m0 0l7-7m-7 7h18" />
                    </svg>
                    Back to Home
                </a>
                <h1 class="text-xl sm:text-2xl font-bold text-gray-900 mt-2">Search Results</h1>
                <p class="text-xs sm:text-sm text-gray-500 mt-1">
                    Showing results for <span class="font-semibold text-gray-900">"{{ query }}"</span>
                </p>
            </div>
        </div>

        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-5 sm:py-8 space-y-4 sm:space-y-6">

            <!-- Global search bar -->
            <SearchBar :initial-query="query" />

            <!-- ============================================================
                 MOBILE (< lg): Compact bar + bottom sheet
                 ============================================================ -->
            <div class="lg:hidden">
                <div
                    class="bg-white dark:bg-gray-800 rounded-2xl shadow-sm border border-gray-100 dark:border-gray-700 p-3">
                    <div class="flex items-center gap-2">
                        <!-- Results count -->
                        <div class="flex-1 min-w-0">
                            <p class="text-xs text-gray-500 dark:text-gray-400">
                                <span class="font-bold text-gray-900 dark:text-white">{{ listings.total || 0 }}</span>
                                result{{ listings.total === 1 ? '' : 's' }}
                            </p>
                        </div>

                        <!-- Filters button -->
                        <button type="button" @click="showSheet = true"
                            class="relative flex-shrink-0 inline-flex items-center gap-1.5 px-3.5 py-2.5 border border-gray-200 dark:border-gray-600 rounded-xl text-sm font-semibold text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2.586a1 1 0 01-.293.707l-6.414 6.414a1 1 0 00-.293.707V17l-4 4v-6.586a1 1 0 00-.293-.707L3.293 7.293A1 1 0 013 6.586V4z" />
                            </svg>
                            Filters
                            <span v-if="activeFilterCount > 0"
                                class="inline-flex items-center justify-center min-w-[20px] h-5 px-1.5 bg-primary-600 text-white text-[10px] font-bold rounded-full">
                                {{ activeFilterCount }}
                            </span>
                        </button>
                    </div>

                    <!-- Active chips row -->
                    <div v-if="hasActiveFilters" class="mt-2.5 flex flex-wrap items-center gap-1.5">
                        <button v-for="chip in activeChips" :key="chip.key" @click="removeChip(chip.key)"
                            class="inline-flex items-center gap-1 px-2 py-1 bg-gray-100 dark:bg-gray-700 border border-gray-200 dark:border-gray-600 text-gray-700 dark:text-gray-300 rounded-full text-[11px] font-medium hover:border-primary-400 hover:text-primary-600 transition-all group">
                            <span class="truncate max-w-[120px]">{{ chip.label }}</span>
                            <svg class="w-2.5 h-2.5 flex-shrink-0 group-hover:scale-110 transition-transform"
                                fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M6 18L18 6M6 6l12 12" />
                            </svg>
                        </button>
                        <button type="button" @click="resetFilters"
                            class="text-[11px] font-semibold text-primary-600 dark:text-primary-400 hover:text-primary-800 ml-1">
                            Clear all
                        </button>
                    </div>
                </div>

                <!-- Bottom sheet -->
                <MobileFilterSheet :show="showSheet" @close="showSheet = false" @apply="applyAndClose"
                    @reset="resetFilters">
                    <template #title>Filter results</template>

                    <!-- Category -->
                    <div class="mb-5">
                        <label
                            class="block text-[10px] font-bold text-gray-500 dark:text-gray-400 uppercase tracking-widest mb-2">
                            Category
                        </label>
                        <select v-model="localFilters.category"
                            class="w-full px-3 py-2.5 border border-gray-200 dark:border-gray-600 rounded-xl bg-white dark:bg-gray-900 text-gray-900 dark:text-white text-sm focus:ring-2 focus:ring-primary-500">
                            <option value="">All Categories</option>
                            <option v-for="category in categories" :key="category.id" :value="category.id">
                                {{ category.name }}
                            </option>
                        </select>
                    </div>

                    <!-- Region -->
                    <div class="mb-5">
                        <label
                            class="block text-[10px] font-bold text-gray-500 dark:text-gray-400 uppercase tracking-widest mb-2">
                            Region
                        </label>
                        <select v-model="localFilters.region"
                            class="w-full px-3 py-2.5 border border-gray-200 dark:border-gray-600 rounded-xl bg-white dark:bg-gray-900 text-gray-900 dark:text-white text-sm focus:ring-2 focus:ring-primary-500">
                            <option value="">All Regions</option>
                            <option v-for="region in regions" :key="region.id" :value="region.id">
                                {{ region.name }}
                            </option>
                        </select>
                    </div>

                    <!-- City -->
                    <div class="mb-5">
                        <label
                            class="block text-[10px] font-bold text-gray-500 dark:text-gray-400 uppercase tracking-widest mb-2">
                            City
                        </label>
                        <select v-model="localFilters.city"
                            class="w-full px-3 py-2.5 border border-gray-200 dark:border-gray-600 rounded-xl bg-white dark:bg-gray-900 text-gray-900 dark:text-white text-sm focus:ring-2 focus:ring-primary-500">
                            <option value="">All Cities</option>
                            <option v-for="city in cities" :key="city.id" :value="city.id">
                                {{ city.name }}
                            </option>
                        </select>
                    </div>

                    <!-- Featured toggle -->
                    <div class="mb-5">
                        <label
                            class="flex items-center justify-between p-4 rounded-xl border cursor-pointer transition-colors"
                            :class="localFilters.featured
                                ? 'border-amber-400 bg-amber-50 dark:bg-amber-900/20'
                                : 'border-gray-200 dark:border-gray-600'">
                            <div class="flex items-center gap-2">
                                <span class="text-lg">⭐</span>
                                <span class="text-sm font-semibold text-gray-900 dark:text-white">Featured only</span>
                            </div>
                            <input type="checkbox" v-model="localFilters.featured"
                                class="rounded border-gray-300 text-amber-500 focus:ring-amber-500" />
                        </label>
                    </div>

                    <!-- Open Now toggle -->
                    <div class="mb-2">
                        <label
                            class="flex items-center justify-between p-4 rounded-xl border cursor-pointer transition-colors"
                            :class="localFilters.open_now
                                ? 'border-green-400 bg-green-50 dark:bg-green-900/20'
                                : 'border-gray-200 dark:border-gray-600'">
                            <div class="flex items-center gap-2">
                                <span class="w-2 h-2 rounded-full bg-green-500 animate-pulse"></span>
                                <span class="text-sm font-semibold text-gray-900 dark:text-white">Open now</span>
                            </div>
                            <input type="checkbox" v-model="localFilters.open_now"
                                class="rounded border-gray-300 text-green-500 focus:ring-green-500" />
                        </label>
                    </div>
                </MobileFilterSheet>
            </div>

            <!-- ============================================================
                 DESKTOP (lg+): Inline filters (existing behavior)
                 ============================================================ -->
            <div
                class="hidden lg:block bg-white dark:bg-gray-800 rounded-2xl shadow-sm border border-gray-100 dark:border-gray-700 p-4">
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-5 gap-4">
                    <div>
                        <label
                            class="block text-[10px] font-bold text-gray-500 dark:text-gray-400 uppercase tracking-widest mb-2">Category</label>
                        <select v-model="localFilters.category" @change="applyFilters"
                            class="w-full px-3 py-2.5 border border-gray-200 dark:border-gray-600 rounded-xl bg-white dark:bg-gray-900 text-gray-900 dark:text-white text-sm focus:ring-2 focus:ring-primary-500">
                            <option value="">All Categories</option>
                            <option v-for="category in categories" :key="category.id" :value="category.id">
                                {{ category.name }}
                            </option>
                        </select>
                    </div>
                    <div>
                        <label
                            class="block text-[10px] font-bold text-gray-500 dark:text-gray-400 uppercase tracking-widest mb-2">Region</label>
                        <select v-model="localFilters.region" @change="applyFilters"
                            class="w-full px-3 py-2.5 border border-gray-200 dark:border-gray-600 rounded-xl bg-white dark:bg-gray-900 text-gray-900 dark:text-white text-sm focus:ring-2 focus:ring-primary-500">
                            <option value="">All Regions</option>
                            <option v-for="region in regions" :key="region.id" :value="region.id">
                                {{ region.name }}
                            </option>
                        </select>
                    </div>
                    <div>
                        <label
                            class="block text-[10px] font-bold text-gray-500 dark:text-gray-400 uppercase tracking-widest mb-2">City</label>
                        <select v-model="localFilters.city" @change="applyFilters"
                            class="w-full px-3 py-2.5 border border-gray-200 dark:border-gray-600 rounded-xl bg-white dark:bg-gray-900 text-gray-900 dark:text-white text-sm focus:ring-2 focus:ring-primary-500">
                            <option value="">All Cities</option>
                            <option v-for="city in cities" :key="city.id" :value="city.id">
                                {{ city.name }}
                            </option>
                        </select>
                    </div>
                    <div>
                        <label
                            class="block text-[10px] font-bold text-gray-500 dark:text-gray-400 uppercase tracking-widest mb-2">Featured</label>
                        <select v-model="localFilters.featured" @change="applyFilters"
                            class="w-full px-3 py-2.5 border border-gray-200 dark:border-gray-600 rounded-xl bg-white dark:bg-gray-900 text-gray-900 dark:text-white text-sm focus:ring-2 focus:ring-primary-500">
                            <option value="">All</option>
                            <option value="1">Featured Only</option>
                        </select>
                    </div>
                    <div class="flex items-end">
                        <label class="flex items-center gap-2 text-sm text-gray-700 dark:text-gray-300 cursor-pointer">
                            <input type="checkbox" v-model="localFilters.open_now" @change="applyFilters"
                                class="rounded border-gray-300 text-primary-600 focus:ring-primary-500" />
                            Open Now
                        </label>
                    </div>
                </div>
            </div>

            <!-- Results -->
            <div v-if="listings.data && listings.data.length > 0">
                <p class="hidden lg:block text-xs sm:text-sm text-gray-500 dark:text-gray-400 mb-4">
                    Found <span class="font-bold text-gray-900 dark:text-white">{{ listings.total }}</span> listings
                </p>
                <div class="grid grid-cols-2 sm:grid-cols-2 lg:grid-cols-3 gap-3 sm:gap-6">
                    <!-- Naming debt (Wave 1D-6): ListingCard's prop is still named
                         `business`, but the result ENTITY is now always a Listing. -->
                    <ListingCard v-for="listing in listings.data" :key="listing.id" :listing="listing" />
                </div>
                <div class="mt-6">
                    <Pagination :links="listings.links" />
                </div>
            </div>

            <!-- Empty state -->
            <div v-else
                class="text-center py-12 bg-white dark:bg-gray-800 rounded-2xl shadow-sm border border-gray-100 dark:border-gray-700">
                <div
                    class="w-16 h-16 bg-gray-100 dark:bg-gray-700 rounded-full flex items-center justify-center mx-auto mb-4">
                    <svg class="w-8 h-8 text-gray-400 dark:text-gray-500" fill="none" stroke="currentColor"
                        viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                    </svg>
                </div>
                <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-2">No results found</h3>
                <p class="text-gray-500 dark:text-gray-400 text-sm">Try adjusting your search terms or filters.</p>
                <a href="/directory"
                    class="inline-flex items-center gap-2 px-5 py-2.5 mt-4 bg-gradient-to-r from-primary-600 to-primary-700 text-white rounded-xl font-semibold text-sm hover:from-primary-700 hover:to-primary-800 transition-all shadow-lg shadow-primary-500/25">
                    Browse All Businesses
                </a>
            </div>
        </div>
    </PublicLayout>
</template>

<script setup>
    import { ref, reactive, computed } from 'vue';
    import { router } from '@inertiajs/vue3';
    import PublicLayout from '@/Layouts/PublicLayout.vue';
    import SearchBar from '@/Components/Public/SearchBar.vue';
    import ListingCard from '@/Components/Public/ListingCard.vue';
    import Pagination from '@/Components/Pagination.vue';
    import MobileFilterSheet from '@/Components/Public/MobileFilterSheet.vue';

    const props = defineProps({
        listings: Object,
        query: String,
        filters: Object,
        categories: Array,
        regions: Array,
        cities: Array,
    });

    const showSheet = ref(false);

    const localFilters = reactive({
        category: props.filters.category || '',
        region: props.filters.region || '',
        city: props.filters.city || '',
        featured: props.filters.featured || '',
        open_now: props.filters.open_now || false,
    });

    // ============ ACTIVE FILTER CHIPS ============
    const hasActiveFilters = computed(() => activeChips.value.length > 0);
    const activeFilterCount = computed(() => activeChips.value.length);

    const activeChips = computed(() => {
        const chips = [];

        if (localFilters.category) {
            const cat = props.categories.find(c => c.id == localFilters.category);
            chips.push({ key: 'category', label: `Category: ${cat?.name || localFilters.category}` });
        }
        if (localFilters.region) {
            const region = props.regions.find(r => r.id == localFilters.region);
            chips.push({ key: 'region', label: `Region: ${region?.name || localFilters.region}` });
        }
        if (localFilters.city) {
            const city = props.cities.find(c => c.id == localFilters.city);
            chips.push({ key: 'city', label: `City: ${city?.name || localFilters.city}` });
        }
        if (localFilters.featured) chips.push({ key: 'featured', label: '⭐ Featured' });
        if (localFilters.open_now) chips.push({ key: 'open_now', label: '🟢 Open Now' });

        return chips;
    });

    // ============ APPLY ============
    const applyFilters = () => {
        router.get('/search', {
            q: props.query,
            category: localFilters.category || '',
            region: localFilters.region || '',
            city: localFilters.city || '',
            featured: localFilters.featured || '',
            open_now: localFilters.open_now ? 1 : '',
        }, {
            preserveState: true,
            preserveScroll: true,
        });
    };

    const applyAndClose = () => {
        applyFilters();
        showSheet.value = false;
    };

    // ============ RESET ============
    const resetFilters = () => {
        localFilters.category = '';
        localFilters.region = '';
        localFilters.city = '';
        localFilters.featured = '';
        localFilters.open_now = false;
        applyFilters();
    };

    // ============ REMOVE CHIP ============
    const removeChip = (key) => {
        if (key === 'featured') {
            localFilters.featured = '';
        } else if (key === 'open_now') {
            localFilters.open_now = false;
        } else {
            localFilters[key] = '';
        }
        applyFilters();
    };
</script>