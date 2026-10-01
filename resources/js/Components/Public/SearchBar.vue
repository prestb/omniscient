<!-- resources/js/Components/Public/SearchBar.vue -->
<template>
    <div class="relative" ref="searchBarRef">
        <form @submit.prevent="submit" class="relative">
            <div
                class="flex items-center bg-white dark:bg-gray-800 rounded-full shadow-lg transition-all duration-300 focus-within:shadow-xl focus-within:ring-2 focus-within:ring-primary-500/20 p-1">
                <!-- Input -->
                <div class="relative flex-1">
                    <input type="text" v-model="searchQuery" @input="onInput" @focus="showSuggestions = true"
                        @blur="closeSuggestions"
                        class="w-full pl-5 pr-3 py-3 sm:py-3.5 text-gray-900 dark:text-white placeholder-gray-400 dark:placeholder-gray-500 focus:outline-none text-sm bg-transparent rounded-full"
                        placeholder="Search businesses, services, categories..." autocomplete="off"
                        aria-label="Search" />

                    <!-- Loading Spinner -->
                    <div v-if="isLoading" class="absolute right-3 top-1/2 -translate-y-1/2">
                        <svg class="animate-spin h-4 w-4 text-gray-400 dark:text-gray-500" fill="none"
                            viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4">
                            </circle>
                            <path class="opacity-75" fill="currentColor"
                                d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z">
                            </path>
                        </svg>
                    </div>

                    <!-- Clear Button -->
                    <button v-else-if="searchQuery" @click="clearSearch" type="button"
                        class="absolute right-3 top-1/2 -translate-y-1/2 text-gray-400 dark:text-gray-500 hover:text-gray-600 dark:hover:text-gray-300 transition-colors"
                        aria-label="Clear search">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>

                <!-- ✅ Lens submit button (icon only, inside pill) -->
                <button type="submit"
                    class="flex-shrink-0 w-10 h-10 sm:w-11 sm:h-11 rounded-full bg-gradient-to-r from-primary-600 to-primary-700 text-white hover:from-primary-700 hover:to-primary-800 transition-all duration-200 flex items-center justify-center shadow-sm ml-1"
                    aria-label="Search">
                    <svg class="w-4 h-4 sm:w-5 sm:h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5"
                            d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                    </svg>
                </button>
            </div>
        </form>

        <!-- Autocomplete Suggestions (teleported outside hero to avoid clipping) -->
        <Teleport to="body">
            <div v-if="showSuggestions && (suggestions.length > 0 || categorySuggestions.length > 0 || searchSuggestions.length > 0)"
                :style="dropdownStyle"
                class="fixed z-[100] bg-white dark:bg-gray-800 rounded-2xl shadow-2xl border border-gray-100 dark:border-gray-700 max-h-[28rem] overflow-y-auto overflow-x-hidden animate-slide-down">
                <!-- Recent Searches (when no results) -->
                <div v-if="!searchQuery && recentSearches.length > 0" class="py-2">
                    <div
                        class="px-4 py-2 text-xs text-gray-500 dark:text-gray-400 font-medium flex items-center justify-between">
                        <span>Recent Searches</span>
                        <button @click="clearRecentSearches"
                            class="text-primary-600 dark:text-primary-400 hover:text-primary-700 dark:hover:text-primary-300 text-[10px] font-medium">
                            Clear all
                        </button>
                    </div>
                    <div v-for="term in recentSearches" :key="term" @mousedown.prevent="selectRecentSearch(term)"
                        class="px-4 py-2 hover:bg-gray-50 dark:hover:bg-gray-700/50 cursor-pointer flex items-center gap-3 transition-colors">
                        <svg class="w-4 h-4 text-gray-400 dark:text-gray-500" fill="none" stroke="currentColor"
                            viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                        <span class="text-sm text-gray-700 dark:text-gray-300">{{ term }}</span>
                    </div>
                </div>

                <!-- ✅ Smart-search suggestions -->
                <div v-if="searchSuggestions.length > 0">
                    <div
                        class="px-4 py-2 bg-gray-50 dark:bg-gray-900/50 text-xs text-gray-500 dark:text-gray-400 font-medium border-b border-gray-100 dark:border-gray-700 flex items-center gap-2">
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                        </svg>
                        Searches
                    </div>
                    <div v-for="(s, idx) in searchSuggestions" :key="`ss-${idx}`"
                        @mousedown.prevent="selectSearchSuggestion(s)"
                        class="px-4 py-2.5 hover:bg-gray-50 dark:hover:bg-gray-700/50 cursor-pointer border-b border-gray-100 dark:border-gray-700 last:border-0 transition-colors group text-left">
                        <div class="flex items-center gap-3">
                            <!-- Contextual icon: location pin for "near me", building for city -->
                            <div
                                class="w-9 h-9 rounded-xl bg-primary-50 dark:bg-primary-900/30 flex items-center justify-center flex-shrink-0 group-hover:bg-primary-100 dark:group-hover:bg-primary-900/50 transition-colors">
                                <!-- Near me: location pin -->
                                <svg v-if="s.type === 'near_me'" class="w-4 h-4 text-primary-600 dark:text-primary-400"
                                    fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z" />
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M15 11a3 3 0 11-6 0 3 3 0 016 0z" />
                                </svg>
                                <!-- City: building icon -->
                                <svg v-else class="w-4 h-4 text-primary-600 dark:text-primary-400" fill="none"
                                    stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" />
                                </svg>
                            </div>
                            <div class="flex-1 min-w-0 text-left">
                                <p
                                    class="text-sm font-medium text-gray-900 dark:text-white group-hover:text-primary-600 dark:group-hover:text-primary-400 transition-colors truncate text-left">
                                    {{ s.label }}
                                </p>
                                <p class="text-xs text-gray-500 dark:text-gray-400 text-left">
                                    <template v-if="s.type === 'near_me'">Search near your location</template>
                                    <template v-else>Filter by city</template>
                                </p>
                            </div>
                            <span
                                class="text-xs text-gray-400 dark:text-gray-500 opacity-0 group-hover:opacity-100 transition-opacity">→</span>
                        </div>
                    </div>
                </div>

                <!-- Business Suggestions -->
                <div v-if="suggestions.length > 0"
                    class="border-t border-gray-100 dark:border-gray-700">
                    <div
                        class="px-4 py-2 bg-gray-50 dark:bg-gray-900/50 text-xs text-gray-500 dark:text-gray-400 font-medium border-b border-gray-100 dark:border-gray-700 flex items-center gap-2">
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" />
                        </svg>
                        Businesses
                    </div>
                    <div v-for="suggestion in suggestions" :key="suggestion.id"
                        @mousedown.prevent="selectSuggestion(suggestion)"
                        class="px-4 py-3 hover:bg-gray-50 dark:hover:bg-gray-700/50 cursor-pointer border-b border-gray-100 dark:border-gray-700 last:border-0 transition-colors group">
                        <div class="flex items-center gap-3">
                            <!-- Logo/Icon -->
                            <div
                                class="w-10 h-10 rounded-xl bg-gray-100 dark:bg-gray-700 flex items-center justify-center flex-shrink-0 group-hover:bg-primary-50 dark:group-hover:bg-primary-900/30 transition-colors">
                                <span class="text-lg">{{ suggestion.icon || '🏢' }}</span>
                            </div>
                            <div class="flex-1 min-w-0 text-left">
                                <!-- FIX: Use v-html to render highlighted text -->
                                <p class="text-sm font-medium text-gray-900 dark:text-white group-hover:text-primary-600 dark:group-hover:text-primary-400 transition-colors"
                                    v-html="highlightMatch(suggestion.name)">
                                </p>
                                <p class="text-xs text-gray-500 dark:text-gray-400 flex items-center gap-2">
                                    <span>{{ suggestion.category || 'Uncategorized' }}</span>
                                    <span v-if="suggestion.city" class="text-gray-300 dark:text-gray-600">•</span>
                                    <span v-if="suggestion.city">{{ suggestion.city }}</span>
                                    <span v-if="suggestion.rating" class="flex items-center gap-0.5">
                                        <span class="text-yellow-400">★</span>
                                        {{ suggestion.rating }}
                                    </span>
                                </p>
                            </div>
                            <span
                                class="text-xs text-gray-400 dark:text-gray-500 opacity-0 group-hover:opacity-100 transition-opacity">→</span>
                        </div>
                    </div>
                </div>

                <!-- Category Suggestions -->
                <div v-if="categorySuggestions.length > 0">
                    <div
                        class="px-4 py-2 bg-gray-50 dark:bg-gray-900/50 text-xs text-gray-500 dark:text-gray-400 font-medium border-b border-gray-100 dark:border-gray-700 flex items-center gap-2">
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M3 7v10a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-6l-2-2H5a2 2 0 00-2 2z" />
                        </svg>
                        Categories
                    </div>
                    <div v-for="category in categorySuggestions" :key="category.id"
                        @mousedown.prevent="selectCategory(category)"
                        class="px-4 py-3 hover:bg-gray-50 dark:hover:bg-gray-700/50 cursor-pointer border-b border-gray-100 dark:border-gray-700 last:border-0 transition-colors group">
                        <div class="flex items-center gap-3">
                            <span class="text-2xl">{{ category.icon || '📁' }}</span>
                            <div class="flex-1">
                                <!-- FIX: Use v-html to render highlighted text -->
                                <p class="text-sm font-medium text-gray-900 dark:text-white group-hover:text-primary-600 dark:group-hover:text-primary-400 transition-colors"
                                    v-html="highlightMatch(category.name)">
                                </p>
                                <p class="text-xs text-gray-500 dark:text-gray-400">{{ category.listings_count || 0 }}
                                    listings</p>
                            </div>
                            <span
                                class="text-xs text-gray-400 dark:text-gray-500 opacity-0 group-hover:opacity-100 transition-opacity">→</span>
                        </div>
                    </div>
                </div>

                <!-- No Results -->
                <div v-if="searchQuery && suggestions.length === 0 && categorySuggestions.length === 0 && searchSuggestions.length === 0"
                    class="px-4 py-8 text-center">
                    <div class="text-4xl mb-2">🔍</div>
                    <p class="text-sm text-gray-500 dark:text-gray-400">No results found for "<span
                            class="font-medium text-gray-700 dark:text-gray-300">{{ searchQuery }}</span>"</p>
                    <p class="text-xs text-gray-400 dark:text-gray-500 mt-1">Try adjusting your search terms</p>
                </div>
            </div>
        </Teleport>
    </div>
</template>

<script setup>
    import { ref, watch, onMounted, onUnmounted, nextTick } from 'vue';
    import { router } from '@inertiajs/vue3';
    import axios from 'axios';

    const props = defineProps({
        initialQuery: {
            type: String,
            default: ''
        }
    });

    const searchQuery = ref(props.initialQuery);
    const showSuggestions = ref(false);
    const searchBarRef = ref(null);
    const dropdownStyle = ref({});

    // ✅ Compute the dropdown's fixed position based on the search bar's location.
    //    Runs whenever the dropdown opens, content changes, or window resizes.
    const updateDropdownPosition = () => {
        if (!searchBarRef.value) {
            nextTick(() => {
                if (searchBarRef.value) updateDropdownPosition();
            });
            return;
        }
        const rect = searchBarRef.value.getBoundingClientRect();
        dropdownStyle.value = {
            top: `${rect.bottom + 8}px`,
            left: `${rect.left}px`,
            width: `${rect.width}px`,
        };
    };

    const suggestions = ref([]);
    const categorySuggestions = ref([]);
    const searchSuggestions = ref([]);
    const isLoading = ref(false);
    const recentSearches = ref([]);
    let debounceTimer = null;

    const handleKeyDown = (e) => {
        if ((e.metaKey || e.ctrlKey) && e.key === 'k') {
            e.preventDefault();
            document.querySelector('input[type="text"]')?.focus();
        }
        if (e.key === 'Escape') {
            showSuggestions.value = false;
            document.querySelector('input[type="text"]')?.blur();
        }
    };

    const handleResize = () => {
        if (showSuggestions.value) updateDropdownPosition();
    };

    onMounted(() => {
        const stored = localStorage.getItem('recentSearches');
        if (stored) {
            try {
                recentSearches.value = JSON.parse(stored).slice(0, 5);
            } catch (e) {
                recentSearches.value = [];
            }
        }

        document.addEventListener('keydown', handleKeyDown);
        window.addEventListener('resize', handleResize);
        window.addEventListener('scroll', handleResize, true);
    });

    onUnmounted(() => {
        document.removeEventListener('keydown', handleKeyDown);
        window.removeEventListener('resize', handleResize);
        window.removeEventListener('scroll', handleResize, true);
    });

    // ✅ Reposition whenever the dropdown opens
    watch(showSuggestions, (isOpen) => {
        if (isOpen) {
            nextTick(() => updateDropdownPosition());
        }
    });

    // ✅ Also reposition when content changes (suggestions arrive)
    watch([suggestions, searchSuggestions, categorySuggestions], () => {
        if (showSuggestions.value) {
            nextTick(() => updateDropdownPosition());
        }
    });

    const addRecentSearch = (term) => {
        if (!term.trim()) return;
        const updated = [term, ...recentSearches.value.filter(s => s !== term)].slice(0, 5);
        recentSearches.value = updated;
        localStorage.setItem('recentSearches', JSON.stringify(updated));
    };

    const clearRecentSearches = () => {
        recentSearches.value = [];
        localStorage.removeItem('recentSearches');
    };

    const onInput = () => {
        showSuggestions.value = true;
        // ✅ Position immediately when we open the dropdown
        nextTick(() => updateDropdownPosition());
        clearTimeout(debounceTimer);
        if (searchQuery.value.length >= 2) {
            debounceTimer = setTimeout(fetchSuggestions, 300);
        } else {
            suggestions.value = [];
            categorySuggestions.value = [];
            searchSuggestions.value = [];
            isLoading.value = false;
        }
    };

    const fetchSuggestions = async () => {
        if (searchQuery.value.length < 2) {
            suggestions.value = [];
            categorySuggestions.value = [];
            return;
        }

        isLoading.value = true;

        // ✅ Clear stale suggestions immediately — prevents old "Businesses"
        //    from flashing before the new response arrives.
        suggestions.value = [];
        searchSuggestions.value = [];
        categorySuggestions.value = [];

        try {
            const response = await axios.get('/search/autocomplete', {
                params: { q: searchQuery.value }
            });
            searchSuggestions.value = response.data.search_suggestions || [];
            categorySuggestions.value = response.data.category_suggestions || [];

            // ✅ When smart-search suggestions exist, cap businesses so all
            //    sections fit without excessive scrolling.
            const allBusinesses = response.data.suggestions || [];
            suggestions.value = searchSuggestions.value.length > 0
                ? allBusinesses.slice(0, 2)
                : allBusinesses;

            // ✅ Reposition after content arrives
            await nextTick();
            updateDropdownPosition();
        } catch (error) {
            console.error('Error fetching suggestions:', error);
            suggestions.value = [];
            categorySuggestions.value = [];
            searchSuggestions.value = [];
        } finally {
            isLoading.value = false;
        }
    };

    const selectSuggestion = (suggestion) => {
        showSuggestions.value = false;
        addRecentSearch(suggestion.name);
        router.visit(`/business/${suggestion.slug}`);
    };

    const selectCategory = (category) => {
        showSuggestions.value = false;
        addRecentSearch(category.name);
        router.visit(`/search?category=${category.id}`);
    };

    // ✅ Called when a smart-search suggestion is picked
    //    (e.g., "Supermarkets near me" or "Supermarkets in Buea").
    const selectSearchSuggestion = (suggestion) => {
        showSuggestions.value = false;
        addRecentSearch(suggestion.query);
        router.visit(`/directory?search=${encodeURIComponent(suggestion.query)}`);
    };

    const selectRecentSearch = (term) => {
        showSuggestions.value = false;
        searchQuery.value = term;
        router.visit(`/search?q=${encodeURIComponent(term)}`);
    };

    const submit = () => {
        showSuggestions.value = false;
        if (searchQuery.value.trim()) {
            addRecentSearch(searchQuery.value.trim());
            router.visit(`/search?q=${encodeURIComponent(searchQuery.value.trim())}`);
        }
    };

    const clearSearch = () => {
        searchQuery.value = '';
        suggestions.value = [];
        categorySuggestions.value = [];
        searchSuggestions.value = [];
        showSuggestions.value = false;
        document.querySelector('input[type="text"]')?.focus();
    };

    const closeSuggestions = () => {
        setTimeout(() => {
            showSuggestions.value = false;
        }, 200);
    };

    // FIX: Return HTML safe string for v-html
    const highlightMatch = (text) => {
        if (!searchQuery.value || !text) return text;
        const regex = new RegExp(`(${searchQuery.value.replace(/[.*+?^${}()|[\]\\]/g, '\\$&')})`, 'gi');
        const parts = text.split(regex);
        return parts.map((part) => {
            if (regex.test(part)) {
                return `<mark class="bg-yellow-200/50 dark:bg-yellow-900/40 text-gray-900 dark:text-white px-0.5 rounded">${part}</mark>`;
            }
            return part;
        }).join('');
    };

    // Watch for initial query changes
    watch(() => props.initialQuery, (newVal) => {
        searchQuery.value = newVal;
    });
</script>

<style scoped>
    @keyframes slide-down {
        from {
            opacity: 0;
            transform: translateY(-8px) scale(0.98);
        }

        to {
            opacity: 1;
            transform: translateY(0) scale(1);
        }
    }

    .animate-slide-down {
        animation: slide-down 0.2s ease-out;
    }
</style>