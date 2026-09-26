<template>
    <!-- ============================================================
         MOBILE (< lg): Compact bar + bottom sheet
         ============================================================ -->
    <div class="lg:hidden">
        <!-- Compact bar -->
        <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-sm border border-gray-100 dark:border-gray-700 p-3">
            <div class="flex items-center gap-2">
                <!-- Search input (with autocomplete) -->
                <div class="flex-1">
                    <SearchAutocomplete v-model="local.search" placeholder="Search..."
                        input-class="w-full pl-9 pr-3 py-2.5 border border-gray-200 dark:border-gray-600 rounded-xl bg-gray-50 dark:bg-gray-900 text-gray-900 dark:text-white placeholder-gray-400 text-sm focus:ring-2 focus:ring-primary-500 focus:border-transparent focus:bg-white dark:focus:bg-gray-900"
                        @select-category="onSelectCategory" @select-search="onSelectSearch" />
                </div>

                <!-- Filters button with badge -->
                <button type="button" @click="openSheet"
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

            <!-- Active chips row (mobile) -->
            <div v-if="hasActiveFilters" class="mt-2.5 flex flex-wrap items-center gap-1.5">
                <button v-for="chip in activeChips" :key="chip.key" @click="removeChip(chip.key)"
                    class="inline-flex items-center gap-1 px-2 py-1 bg-gray-100 dark:bg-gray-700 border border-gray-200 dark:border-gray-600 text-gray-700 dark:text-gray-300 rounded-full text-[11px] font-medium hover:border-primary-400 hover:text-primary-600 transition-all group">
                    <span class="truncate max-w-[120px]">{{ chip.label }}</span>
                    <svg class="w-2.5 h-2.5 flex-shrink-0 group-hover:scale-110 transition-transform" fill="none"
                        stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
                <button type="button" @click="reset"
                    class="text-[11px] font-semibold text-primary-600 dark:text-primary-400 hover:text-primary-800 ml-1">
                    Clear all
                </button>
            </div>
        </div>

        <!-- Bottom sheet -->
        <MobileFilterSheet :show="showSheet" @close="closeSheet" @apply="applyAndClose" @reset="reset">
            <template #title>Filters</template>

            <!-- Sort -->
            <div class="mb-5">
                <label
                    class="block text-[10px] font-bold text-gray-500 dark:text-gray-400 uppercase tracking-widest mb-2">
                    Sort By
                </label>
                <select v-model="local.sort"
                    class="w-full px-3 py-2.5 border border-gray-200 dark:border-gray-600 rounded-xl bg-white dark:bg-gray-900 text-gray-900 dark:text-white text-sm focus:ring-2 focus:ring-primary-500">
                    <option value="newest">Newest First</option>
                    <option value="rating">Highest Rated</option>
                    <option value="reviews">Most Reviewed</option>
                    <option value="name">Name A-Z</option>
                </select>
            </div>

            <!-- Quick filters -->
            <div class="mb-5">
                <label
                    class="block text-[10px] font-bold text-gray-500 dark:text-gray-400 uppercase tracking-widest mb-2">
                    Quick Filters
                </label>
                <div class="flex flex-wrap gap-2">
                    <button type="button" @click="local.open_now = !local.open_now"
                        :class="local.open_now
                            ? 'bg-green-500 text-white border-green-500'
                            : 'bg-white dark:bg-gray-900 text-gray-700 dark:text-gray-300 border-gray-300 dark:border-gray-600'"
                        class="inline-flex items-center gap-1.5 px-3 py-2 rounded-full border text-sm font-medium transition-all">
                        <span class="w-1.5 h-1.5 rounded-full"
                            :class="local.open_now ? 'bg-white animate-pulse' : 'bg-green-500'"></span>
                        Open Now
                    </button>
                    <button type="button" @click="local.verified = !local.verified"
                        :class="local.verified
                            ? 'bg-blue-500 text-white border-blue-500'
                            : 'bg-white dark:bg-gray-900 text-gray-700 dark:text-gray-300 border-gray-300 dark:border-gray-600'"
                        class="inline-flex items-center gap-1.5 px-3 py-2 rounded-full border text-sm font-medium transition-all">
                        ✓ Verified
                    </button>
                    <button type="button" @click="local.featured = !local.featured"
                        :class="local.featured
                            ? 'bg-amber-500 text-white border-amber-500'
                            : 'bg-white dark:bg-gray-900 text-gray-700 dark:text-gray-300 border-gray-300 dark:border-gray-600'"
                        class="inline-flex items-center gap-1.5 px-3 py-2 rounded-full border text-sm font-medium transition-all">
                        ⭐ Featured
                    </button>
                    <button type="button" @click="local.has_photos = !local.has_photos"
                        :class="local.has_photos
                            ? 'bg-purple-500 text-white border-purple-500'
                            : 'bg-white dark:bg-gray-900 text-gray-700 dark:text-gray-300 border-gray-300 dark:border-gray-600'"
                        class="inline-flex items-center gap-1.5 px-3 py-2 rounded-full border text-sm font-medium transition-all">
                        📸 Gallery
                    </button>
                    <button type="button" @click="local.has_whatsapp = !local.has_whatsapp"
                        :class="local.has_whatsapp
                            ? 'bg-green-600 text-white border-green-600'
                            : 'bg-white dark:bg-gray-900 text-gray-700 dark:text-gray-300 border-gray-300 dark:border-gray-600'"
                        class="inline-flex items-center gap-1.5 px-3 py-2 rounded-full border text-sm font-medium transition-all">
                        💬 WhatsApp
                    </button>
                </div>
            </div>

            <!-- Category -->
            <div class="mb-5">
                <label
                    class="block text-[10px] font-bold text-gray-500 dark:text-gray-400 uppercase tracking-widest mb-2">
                    Category
                </label>
                <select v-model="local.category"
                    class="w-full px-3 py-2.5 border border-gray-200 dark:border-gray-600 rounded-xl bg-white dark:bg-gray-900 text-gray-900 dark:text-white text-sm focus:ring-2 focus:ring-primary-500">
                    <option value="">All Categories</option>
                    <option v-for="category in categories" :key="category.id" :value="category.id">
                        {{ category.name }}
                    </option>
                </select>
            </div>

            <!-- Location -->
            <div class="mb-5">
                <label
                    class="block text-[10px] font-bold text-gray-500 dark:text-gray-400 uppercase tracking-widest mb-2">
                    Location
                </label>
                <div class="space-y-2">
                    <select v-model="local.country_id" @change="onCountryChange"
                        class="w-full px-3 py-2.5 border border-gray-200 dark:border-gray-600 rounded-xl bg-white dark:bg-gray-900 text-gray-900 dark:text-white text-sm focus:ring-2 focus:ring-primary-500">
                        <option value="">All Countries</option>
                        <option v-for="country in countries" :key="country.id" :value="country.id">
                            {{ country.name }}
                        </option>
                    </select>

                    <select v-model="local.region_id" @change="onRegionChange" :disabled="!local.country_id"
                        class="w-full px-3 py-2.5 border border-gray-200 dark:border-gray-600 rounded-xl bg-white dark:bg-gray-900 text-gray-900 dark:text-white text-sm focus:ring-2 focus:ring-primary-500 disabled:opacity-50">
                        <option value="">All Regions</option>
                        <option v-for="region in availableRegions" :key="region.id" :value="region.id">
                            {{ region.name }}
                        </option>
                    </select>

                    <select v-model="local.city_id" :disabled="!local.region_id"
                        class="w-full px-3 py-2.5 border border-gray-200 dark:border-gray-600 rounded-xl bg-white dark:bg-gray-900 text-gray-900 dark:text-white text-sm focus:ring-2 focus:ring-primary-500 disabled:opacity-50">
                        <option value="">All Cities</option>
                        <option v-for="city in availableCities" :key="city.id" :value="city.id">
                            {{ city.name }}
                        </option>
                    </select>
                </div>
            </div>

            <!-- Min rating -->
            <div class="mb-2">
                <label
                    class="block text-[10px] font-bold text-gray-500 dark:text-gray-400 uppercase tracking-widest mb-2">
                    Minimum Rating
                </label>
                <div class="flex flex-wrap gap-2">
                    <button v-for="r in [0, 3, 4, 4.5]" :key="r" type="button" @click="local.min_rating = r"
                        :class="local.min_rating == r
                            ? 'bg-primary-600 text-white border-primary-600'
                            : 'bg-white dark:bg-gray-900 text-gray-700 dark:text-gray-300 border-gray-300 dark:border-gray-600'"
                        class="inline-flex items-center gap-1 px-3 py-2 rounded-lg border text-sm font-medium transition-all">
                        <template v-if="r === 0">Any</template>
                        <template v-else>⭐ {{ r }}+</template>
                    </button>
                </div>
            </div>
        </MobileFilterSheet>
    </div>

    <!-- ============================================================
         DESKTOP (lg+): Inline panel (existing behavior)
         ============================================================ -->
    <div
        class="hidden lg:block bg-white dark:bg-gray-800 rounded-2xl shadow-sm border border-gray-100 dark:border-gray-700 overflow-hidden">
        <div class="p-4 sm:p-5">
            <form @submit.prevent="apply" class="space-y-4">
                <!-- Row 1: Search + Sort -->
                <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
                    <div class="sm:col-span-2">
                        <label class="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1">Search</label>
                        <SearchAutocomplete v-model="local.search" placeholder="Search businesses, services..."
                            input-class="w-full pl-10 pr-4 py-2.5 border border-gray-300 dark:border-gray-600 rounded-xl bg-white dark:bg-gray-900 text-gray-900 dark:text-white placeholder-gray-400 text-sm focus:ring-2 focus:ring-primary-500 focus:border-transparent"
                            @select-category="onSelectCategory" @select-search="onSelectSearch" />
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1">Sort By</label>
                        <select v-model="local.sort"
                            class="w-full px-3 py-2.5 border border-gray-300 dark:border-gray-600 rounded-xl bg-white dark:bg-gray-900 text-gray-900 dark:text-white text-sm focus:ring-2 focus:ring-primary-500">
                            <option value="newest">Newest First</option>
                            <option value="rating">Highest Rated</option>
                            <option value="reviews">Most Reviewed</option>
                            <option value="name">Name A-Z</option>
                        </select>
                    </div>
                </div>

                <!-- Row 2: Quick filter buttons -->
                <div class="flex flex-wrap items-center gap-2">
                    <button type="button" @click="toggle('open_now')"
                        :class="local.open_now
                            ? 'bg-green-500 text-white border-green-500'
                            : 'bg-white dark:bg-gray-900 text-gray-700 dark:text-gray-300 border-gray-300 dark:border-gray-600 hover:border-green-400'"
                        class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-full border text-xs font-medium transition-all">
                        <span class="w-1.5 h-1.5 rounded-full"
                            :class="local.open_now ? 'bg-white animate-pulse' : 'bg-green-500'"></span>
                        Open Now
                    </button>

                    <button type="button" @click="toggle('verified')"
                        :class="local.verified
                            ? 'bg-blue-500 text-white border-blue-500'
                            : 'bg-white dark:bg-gray-900 text-gray-700 dark:text-gray-300 border-gray-300 dark:border-gray-600 hover:border-blue-400'"
                        class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-full border text-xs font-medium transition-all">
                        <svg class="w-3.5 h-3.5" viewBox="0 0 24 24" fill="currentColor">
                            <path fill-rule="evenodd"
                                d="M8.603 3.799A4.49 4.49 0 0112 2.25c1.357 0 2.573.6 3.397 1.549a4.49 4.49 0 013.498 1.307 4.491 4.491 0 011.307 3.497A4.49 4.49 0 0121.75 12a4.49 4.49 0 01-1.549 3.397 4.491 4.491 0 01-1.307 3.497 4.491 4.491 0 01-3.497 1.307A4.49 4.49 0 0112 21.75a4.49 4.49 0 01-3.397-1.549 4.49 4.49 0 01-3.498-1.306 4.491 4.491 0 01-1.307-3.498A4.49 4.49 0 012.25 12c0-1.357.6-2.573 1.549-3.397a4.49 4.49 0 011.307-3.497 4.49 4.49 0 013.497-1.307zm7.007 6.387a.75.75 0 10-1.22-.872l-3.236 4.53L9.53 12.22a.75.75 0 00-1.06 1.06l2.25 2.25a.75.75 0 001.14-.094l3.75-5.25z"
                                clip-rule="evenodd" />
                        </svg>
                        Verified
                    </button>

                    <button type="button" @click="toggle('featured')"
                        :class="local.featured
                            ? 'bg-amber-500 text-white border-amber-500'
                            : 'bg-white dark:bg-gray-900 text-gray-700 dark:text-gray-300 border-gray-300 dark:border-gray-600 hover:border-amber-400'"
                        class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-full border text-xs font-medium transition-all">
                        <span>⭐</span>
                        Featured
                    </button>

                    <button type="button" @click="toggle('has_photos')"
                        :class="local.has_photos
                            ? 'bg-purple-500 text-white border-purple-500'
                            : 'bg-white dark:bg-gray-900 text-gray-700 dark:text-gray-300 border-gray-300 dark:border-gray-600 hover:border-purple-400'"
                        class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-full border text-xs font-medium transition-all">
                        <span>📸</span>
                        Gallery Photos
                    </button>

                    <button type="button" @click="toggle('has_whatsapp')"
                        :class="local.has_whatsapp
                            ? 'bg-green-600 text-white border-green-600'
                            : 'bg-white dark:bg-gray-900 text-gray-700 dark:text-gray-300 border-gray-300 dark:border-gray-600 hover:border-green-500'"
                        class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-full border text-xs font-medium transition-all">
                        <span>💬</span>
                        WhatsApp
                    </button>

                    <button type="button" @click="showMore = !showMore"
                        class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-full border border-gray-300 dark:border-gray-600 text-xs font-medium text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700 transition-all">
                        <svg class="w-3.5 h-3.5 transition-transform" :class="showMore ? 'rotate-180' : ''" fill="none"
                            stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                        </svg>
                        {{ showMore ? 'Less' : 'More' }} Filters
                    </button>
                </div>

                <!-- Expandable More Filters -->
                <Transition enter-active-class="transition duration-200 ease-out"
                    enter-from-class="opacity-0 -translate-y-2" enter-to-class="opacity-100 translate-y-0"
                    leave-active-class="transition duration-150 ease-in" leave-from-class="opacity-100 translate-y-0"
                    leave-to-class="opacity-0 -translate-y-2">
                    <div v-show="showMore"
                        class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-3 pt-3 border-t border-gray-100 dark:border-gray-700">
                        <!-- Category -->
                        <div>
                            <label
                                class="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1">Category</label>
                            <select v-model="local.category"
                                class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-900 text-gray-900 dark:text-white text-sm focus:ring-2 focus:ring-primary-500">
                                <option value="">All Categories</option>
                                <option v-for="category in categories" :key="category.id" :value="category.id">
                                    {{ category.name }}
                                </option>
                            </select>
                        </div>

                        <!-- Country -->
                        <div>
                            <label
                                class="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1">Country</label>
                            <select v-model="local.country_id" @change="onCountryChange"
                                class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-900 text-gray-900 dark:text-white text-sm focus:ring-2 focus:ring-primary-500">
                                <option value="">All Countries</option>
                                <option v-for="country in countries" :key="country.id" :value="country.id">
                                    {{ country.name }}
                                </option>
                            </select>
                        </div>

                        <!-- Region -->
                        <div>
                            <label
                                class="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1">Region</label>
                            <select v-model="local.region_id" @change="onRegionChange" :disabled="!local.country_id"
                                class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-900 text-gray-900 dark:text-white text-sm focus:ring-2 focus:ring-primary-500 disabled:opacity-50">
                                <option value="">All Regions</option>
                                <option v-for="region in availableRegions" :key="region.id" :value="region.id">
                                    {{ region.name }}
                                </option>
                            </select>
                        </div>

                        <!-- City -->
                        <div>
                            <label class="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1">City</label>
                            <select v-model="local.city_id" :disabled="!local.region_id"
                                class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-900 text-gray-900 dark:text-white text-sm focus:ring-2 focus:ring-primary-500 disabled:opacity-50">
                                <option value="">All Cities</option>
                                <option v-for="city in availableCities" :key="city.id" :value="city.id">
                                    {{ city.name }}
                                </option>
                            </select>
                        </div>

                        <!-- Min Rating -->
                        <div class="sm:col-span-2 lg:col-span-4">
                            <label class="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1">Minimum
                                Rating</label>
                            <div class="flex flex-wrap gap-2">
                                <button v-for="r in [0, 3, 4, 4.5]" :key="r" type="button" @click="local.min_rating = r"
                                    :class="local.min_rating == r
                                        ? 'bg-primary-600 text-white border-primary-600'
                                        : 'bg-white dark:bg-gray-900 text-gray-700 dark:text-gray-300 border-gray-300 dark:border-gray-600 hover:border-primary-400'"
                                    class="inline-flex items-center gap-1 px-3 py-1.5 rounded-lg border text-xs font-medium transition-all">
                                    <template v-if="r === 0">Any Rating</template>
                                    <template v-else><span>⭐</span> {{ r }}+ Stars</template>
                                </button>
                            </div>
                        </div>
                    </div>
                </Transition>

                <!-- Action buttons -->
                <div class="flex items-center justify-between gap-3 pt-3 border-t border-gray-100 dark:border-gray-700">
                    <div class="flex items-center gap-3">
                        <button type="submit"
                            class="inline-flex items-center gap-2 px-5 py-2.5 bg-gradient-to-r from-primary-600 to-primary-700 text-white rounded-xl font-medium text-sm hover:from-primary-700 hover:to-primary-800 transition-all shadow-lg shadow-primary-500/30">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2.586a1 1 0 01-.293.707l-6.414 6.414a1 1 0 00-.293.707V17l-4 4v-6.586a1 1 0 00-.293-.707L3.293 7.293A1 1 0 013 6.586V4z" />
                            </svg>
                            Apply Filters
                        </button>
                        <button v-if="hasActiveFilters" type="button" @click="reset"
                            class="text-sm text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-300 font-medium">
                            Clear All
                        </button>
                    </div>
                    <div v-if="hasActiveFilters" class="text-xs text-gray-500 dark:text-gray-400">
                        {{ activeFilterCount }} filter{{ activeFilterCount > 1 ? 's' : '' }} active
                    </div>
                </div>
            </form>
        </div>

        <!-- Active Filter Chips (desktop) -->
        <div v-if="hasActiveFilters"
            class="px-4 sm:px-5 py-3 bg-gray-50 dark:bg-gray-900/50 border-t border-gray-100 dark:border-gray-700 flex flex-wrap items-center gap-2">
            <span class="text-xs font-medium text-gray-500 dark:text-gray-400 mr-1">Active:</span>
            <button v-for="chip in activeChips" :key="chip.key" @click="removeChip(chip.key)"
                class="inline-flex items-center gap-1.5 px-2.5 py-1 bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-600 text-gray-700 dark:text-gray-300 rounded-full text-xs font-medium hover:border-primary-400 hover:text-primary-600 dark:hover:text-primary-400 transition-all group">
                <span>{{ chip.label }}</span>
                <svg class="w-3 h-3 group-hover:scale-110 transition-transform" fill="none" stroke="currentColor"
                    viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                </svg>
            </button>
        </div>
    </div>
</template>

<script setup>
    import { ref, reactive, computed, onMounted } from 'vue';
    import { router } from '@inertiajs/vue3';
    import axios from 'axios';
    import MobileFilterSheet from './MobileFilterSheet.vue';
    import SearchAutocomplete from './SearchAutocomplete.vue';
    import {
        writeDirectoryFilters,
        clearDirectoryFilters,
        writeDirectoryViewMode,
    } from '@/composables/useDirectoryFilters';

    const props = defineProps({
        filters: { type: Object, default: () => ({}) },
        categories: { type: Array, default: () => [] },
        countries: { type: Array, default: () => [] },
    });

    // ============ STATE ============
    const showMore = ref(false);
    const showSheet = ref(false);

    const local = reactive({
        search: props.filters?.search || '',
        category: props.filters?.category || '',
        country_id: props.filters?.country_id || '',
        region_id: props.filters?.region_id || '',
        city_id: props.filters?.city_id || '',
        open_now: props.filters?.open_now === 'true' || props.filters?.open_now === true,
        featured: props.filters?.featured === 'true' || props.filters?.featured === true,
        verified: props.filters?.verified === 'true' || props.filters?.verified === true,
        has_photos: props.filters?.has_photos === 'true' || props.filters?.has_photos === true,
        has_whatsapp: props.filters?.has_whatsapp === 'true' || props.filters?.has_whatsapp === true,
        min_rating: props.filters?.min_rating || 0,
        sort: props.filters?.sort || 'newest',
    });

    const availableRegions = ref([]);
    const availableCities = ref([]);

    // ============ SHEET CONTROLS ============
    const openSheet = () => { showSheet.value = true; };
    const closeSheet = () => { showSheet.value = false; };
    const applyAndClose = () => { apply(); showSheet.value = false; };

    // ============ CHIPS ============
    const hasActiveFilters = computed(() => activeChips.value.length > 0);
    const activeFilterCount = computed(() => activeChips.value.length);

    const activeChips = computed(() => {
        const chips = [];

        if (local.search) chips.push({ key: 'search', label: `Search: "${local.search}"` });
        if (local.category) {
            const cat = props.categories.find(c => c.id == local.category);
            chips.push({ key: 'category', label: `Category: ${cat?.name || local.category}` });
        }
        if (local.country_id) {
            const country = props.countries.find(c => c.id == local.country_id);
            chips.push({ key: 'country_id', label: `Country: ${country?.name || local.country_id}` });
        }
        if (local.region_id) {
            const region = availableRegions.value.find(r => r.id == local.region_id);
            chips.push({ key: 'region_id', label: `Region: ${region?.name || local.region_id}` });
        }
        if (local.city_id) {
            const city = availableCities.value.find(c => c.id == local.city_id);
            chips.push({ key: 'city_id', label: `City: ${city?.name || local.city_id}` });
        }
        if (local.open_now) chips.push({ key: 'open_now', label: '🟢 Open Now' });
        if (local.featured) chips.push({ key: 'featured', label: '⭐ Featured' });
        if (local.verified) chips.push({ key: 'verified', label: '✓ Verified' });
        if (local.has_photos) chips.push({ key: 'has_photos', label: '📸 Gallery Photos' });
        if (local.has_whatsapp) chips.push({ key: 'has_whatsapp', label: '💬 WhatsApp' });
        if (local.min_rating > 0) chips.push({ key: 'min_rating', label: `⭐ ${local.min_rating}+ Stars` });
        if (local.sort !== 'newest') chips.push({ key: 'sort', label: `Sort: ${local.sort}` });

        return chips;
    });

    // ============ TOGGLE (desktop: auto-apply) ============
    const toggle = (key) => {
        local[key] = !local[key];
        apply();
    };

    // ============ LOCATION LOADERS ============
    const loadRegions = async (preserveRegion = false, preserveCity = false) => {
        if (!local.country_id) {
            availableRegions.value = [];
            availableCities.value = [];
            local.region_id = '';
            local.city_id = '';
            return;
        }

        const previousRegion = preserveRegion ? local.region_id : '';
        const previousCity = preserveCity ? local.city_id : '';

        try {
            const response = await axios.get('/api/locations/regions', {
                params: { country_id: local.country_id },
            });
            availableRegions.value = response.data;

            if (previousRegion && response.data.some(r => r.id == previousRegion)) {
                local.region_id = previousRegion;
            } else {
                local.region_id = '';
                local.city_id = '';
                availableCities.value = [];
                return;
            }
        } catch (error) {
            console.error('Error loading regions:', error);
            availableRegions.value = [];
            local.region_id = '';
            local.city_id = '';
            availableCities.value = [];
            return;
        }

        if (local.region_id) {
            await loadCities(preserveCity ? previousCity : false);
        }
    };

    const loadCities = async (preserveCity = false) => {
        if (!local.region_id) {
            availableCities.value = [];
            local.city_id = '';
            return;
        }

        const previousCity = preserveCity ? local.city_id : '';

        try {
            const response = await axios.get('/api/locations/cities', {
                params: { region_id: local.region_id },
            });
            availableCities.value = response.data;

            if (previousCity && response.data.some(c => c.id == previousCity)) {
                local.city_id = previousCity;
            } else {
                local.city_id = '';
            }
        } catch (error) {
            console.error('Error loading cities:', error);
            availableCities.value = [];
            local.city_id = '';
        }
    };

    const onCountryChange = () => {
        local.region_id = '';
        local.city_id = '';
        availableCities.value = [];
        loadRegions();
    };

    const onRegionChange = () => {
        local.city_id = '';
        loadCities();
    };

    // ============ APPLY ============
    const apply = () => {
        const params = {};

        // Only include non-empty values
        if (local.search) params.search = local.search;
        if (local.category) params.category = local.category;
        if (local.country_id) params.country_id = local.country_id;
        if (local.region_id) params.region_id = local.region_id;
        if (local.city_id) params.city_id = local.city_id;
        if (local.open_now) params.open_now = 'true';
        if (local.featured) params.featured = 'true';
        if (local.verified) params.verified = 'true';
        if (local.has_photos) params.has_photos = 'true';
        if (local.has_whatsapp) params.has_whatsapp = 'true';
        if (local.min_rating > 0) params.min_rating = local.min_rating;
        if (local.sort && local.sort !== 'newest') params.sort = local.sort;

        // ✅ Persist the filter set (excluding `view`) to localStorage
        writeDirectoryFilters(params);

        // ✅ Persist the current view mode separately
        const currentView = props.filters?.view === 'map' ? 'map' : 'list';
        writeDirectoryViewMode(currentView);

        // ✅ Preserve the current view mode (list / map)
        if (props.filters?.view === 'map') {
            params.view = 'map';
        }

        router.get('/directory', params, {
            preserveState: true,
            preserveScroll: true,
        });
    };

    // Called when search input loses focus (mobile) — apply if changed
    const maybeApplyOnSearchBlur = () => {
        const originalSearch = props.filters?.search || '';
        if (local.search !== originalSearch) {
            apply();
        }
    };

    // ✅ Called by SearchAutocomplete when a category suggestion is picked.
    //    Fills `local.category` and applies immediately.
    const onSelectCategory = (cat) => {
        local.search = '';
        local.category = cat.id;
        apply();
    };

    // ✅ Called by SearchAutocomplete when a smart-search suggestion is picked
    //    (e.g., "Supermarkets near me" or "Supermarkets in Buea").
    //    Routes to the full /directory search page so the parser processes it.
    const onSelectSearch = (suggestion) => {
        const params = new URLSearchParams();
        params.set('search', suggestion.query);

        // Preserve current filters
        if (local.open_now) params.set('open_now', 'true');
        if (local.sort && local.sort !== 'newest') params.set('sort', local.sort);

        router.get('/directory', Object.fromEntries(params));
    };

    // ============ RESET ============
    const reset = () => {
        local.search = '';
        local.category = '';
        local.country_id = '';
        local.region_id = '';
        local.city_id = '';
        local.open_now = false;
        local.featured = false;
        local.verified = false;
        local.has_photos = false;
        local.has_whatsapp = false;
        local.min_rating = 0;
        local.sort = 'newest';
        availableRegions.value = [];
        availableCities.value = [];

        // ✅ Wipe persisted filters (but keep view mode)
        clearDirectoryFilters();

        apply();
    };

    // ============ REMOVE CHIP ============
    const removeChip = (key) => {
        if (['open_now', 'featured', 'verified', 'has_photos', 'has_whatsapp'].includes(key)) {
            local[key] = false;
        } else if (key === 'min_rating') {
            local.min_rating = 0;
        } else if (key === 'sort') {
            local.sort = 'newest';
        } else {
            local[key] = '';
            if (key === 'country_id') {
                local.region_id = '';
                local.city_id = '';
                availableRegions.value = [];
                availableCities.value = [];
            }
            if (key === 'region_id') {
                local.city_id = '';
                availableCities.value = [];
            }
        }
        apply();
    };

    // ============ INIT ============
    onMounted(async () => {
        if (local.country_id) {
            await loadRegions(true, true);
        }
    });
</script>