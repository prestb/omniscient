<!-- resources/js/Components/Public/SearchAutocomplete.vue -->
<template>
    <div ref="root" class="relative">
        <!-- Input -->
        <div class="relative">
            <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                <svg class="h-4 w-4 text-gray-400 dark:text-gray-500" fill="none" stroke="currentColor"
                    viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                </svg>
            </div>

            <input ref="inputEl" v-model="query" type="text" :placeholder="placeholder" autocomplete="off"
                :class="inputClass" @input="onInput" @focus="onFocus" @keydown.down.prevent="moveHighlight(1)"
                @keydown.up.prevent="moveHighlight(-1)" @keydown.enter.prevent="onEnter" @keydown.esc="close" role="combobox" aria-autocomplete="list" :aria-controls="listboxId" :aria-activedescendant="activeDescendant"
                @blur="onBlur" />

            <!-- Loading spinner -->
            <div v-if="loading" class="absolute inset-y-0 right-0 pr-3 flex items-center pointer-events-none">
                <svg class="animate-spin h-4 w-4 text-gray-400" fill="none" viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
                </svg>
            </div>
        </div>

        <!-- Dropdown (teleported to avoid parent overflow clipping) -->
        <Teleport to="body">
            <Transition enter-active-class="transition duration-150 ease-out" enter-from-class="opacity-0 -translate-y-1"
                enter-to-class="opacity-100 translate-y-0" leave-active-class="transition duration-100 ease-in"
                leave-from-class="opacity-100 translate-y-0" leave-to-class="opacity-0 -translate-y-1">
                <div v-if="open && (businesses.length > 0 || categories.length > 0 || searches.length > 0 || query.length >= 2)"
                    :style="dropdownStyle"
                    class="fixed z-[100] bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-xl shadow-xl overflow-hidden max-h-[28rem] overflow-y-auto">

                    <!-- No results -->
                    <div v-if="!loading && businesses.length === 0 && categories.length === 0 && searches.length === 0 && query.length >= 2"
                        class="px-4 py-6 text-center">
                        <p class="text-sm text-gray-500 dark:text-gray-400">No matches for "{{ query }}"</p>
                    </div>

                    <!-- Searches (smart-search suggestions) -->
                    <div v-if="searches.length > 0">
                        <div class="px-3 pt-3 pb-1">
                            <p class="text-[10px] font-bold uppercase tracking-widest text-gray-400 dark:text-gray-500">
                                Searches</p>
                        </div>
                        <ul>
                            <li v-for="(s, idx) in searches" :key="`s-${idx}`">
                                <button type="button" @mousedown.prevent="selectSearch(s)"
                                    @mouseenter="highlighted = { type: 'search', index: idx }" :class="[
                                        'w-full text-left px-3 py-2.5 flex items-center gap-3 transition-colors',
                                        isHighlighted('search', idx)
                                            ? 'bg-primary-50 dark:bg-primary-900/20'
                                            : 'hover:bg-gray-50 dark:hover:bg-gray-700/50'
                                    ]">
                                    <div
                                        class="w-8 h-8 rounded-lg bg-primary-50 dark:bg-primary-900/30 flex items-center justify-center flex-shrink-0">
                                        <svg v-if="s.type === 'near_me'"
                                            class="w-4 h-4 text-primary-600 dark:text-primary-400" fill="none"
                                            stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z" />
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M15 11a3 3 0 11-6 0 3 3 0 016 0z" />
                                        </svg>
                                        <svg v-else class="w-4 h-4 text-primary-600 dark:text-primary-400" fill="none"
                                            stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" />
                                        </svg>
                                    </div>
                                    <div class="flex-1 min-w-0 text-left">
                                        <p class="text-sm font-semibold text-gray-900 dark:text-white truncate">{{ s.label
                                            }}</p>
                                        <p class="text-xs text-gray-500 dark:text-gray-400">
                                            <template v-if="s.type === 'near_me'">Search near your location</template>
                                            <template v-else>Filter by city</template>
                                        </p>
                                    </div>
                                    <svg class="w-3.5 h-3.5 text-gray-400 flex-shrink-0" fill="none" stroke="currentColor"
                                        viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M9 5l7 7-7 7" />
                                    </svg>
                                </button>
                            </li>
                        </ul>
                    </div>

                    <!-- Businesses -->
                    <div v-if="businesses.length > 0" class="border-t border-gray-100 dark:border-gray-700">
                        <div class="px-3 pt-3 pb-1">
                            <p class="text-[10px] font-bold uppercase tracking-widest text-gray-400 dark:text-gray-500">
                                Businesses</p>
                        </div>
                        <ul>
                            <li v-for="(biz, idx) in businesses" :key="`b-${biz.id}`">
                                <button type="button" @mousedown.prevent="selectListing(biz)"
                                    @mouseenter="highlighted = { type: 'listing', index: idx }" :class="[
                                        'w-full text-left px-3 py-2.5 flex items-center gap-3 transition-colors',
                                        isHighlighted('listing', idx)
                                            ? 'bg-primary-50 dark:bg-primary-900/20'
                                            : 'hover:bg-gray-50 dark:hover:bg-gray-700/50'
                                    ]">
                                    <div
                                        class="w-8 h-8 rounded-lg bg-primary-100 dark:bg-primary-900/40 flex items-center justify-center flex-shrink-0">
                                        <svg class="w-4 h-4 text-primary-600 dark:text-primary-400" fill="none"
                                            stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" />
                                        </svg>
                                    </div>
                                    <div class="flex-1 min-w-0">
                                        <p class="text-sm font-semibold text-gray-900 dark:text-white truncate">{{ biz.name
                                            }}</p>
                                        <p v-if="biz.category || biz.city"
                                            class="text-xs text-gray-500 dark:text-gray-400 truncate">
                                            {{ [biz.category, biz.city].filter(Boolean).join(' · ') }}
                                        </p>
                                    </div>
                                    <svg class="w-3.5 h-3.5 text-gray-400 flex-shrink-0" fill="none" stroke="currentColor"
                                        viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M9 5l7 7-7 7" />
                                    </svg>
                                </button>
                            </li>
                        </ul>
                    </div>

                    <!-- Categories -->
                    <div v-if="categories.length > 0"
                        :class="businesses.length > 0 ? 'border-t border-gray-100 dark:border-gray-700' : ''">
                        <div class="px-3 pt-3 pb-1">
                            <p class="text-[10px] font-bold uppercase tracking-widest text-gray-400 dark:text-gray-500">
                                Categories</p>
                        </div>
                        <ul>
                            <li v-for="(cat, idx) in categories" :key="`c-${cat.id}`">
                                <button type="button" @mousedown.prevent="selectCategory(cat)"
                                    @mouseenter="highlighted = { type: 'category', index: idx }" :class="[
                                        'w-full text-left px-3 py-2.5 flex items-center gap-3 transition-colors',
                                        isHighlighted('category', idx)
                                            ? 'bg-primary-50 dark:bg-primary-900/20'
                                            : 'hover:bg-gray-50 dark:hover:bg-gray-700/50'
                                    ]">
                                    <div
                                        class="w-8 h-8 rounded-lg bg-gray-100 dark:bg-gray-700 flex items-center justify-center flex-shrink-0 text-base">
                                        {{ cat.icon || '📁' }}
                                    </div>
                                    <div class="flex-1 min-w-0">
                                        <p class="text-sm font-semibold text-gray-900 dark:text-white truncate">{{ cat.name
                                            }}</p>
                                        <p class="text-xs text-gray-500 dark:text-gray-400">Browse category</p>
                                    </div>
                                    <svg class="w-3.5 h-3.5 text-gray-400 flex-shrink-0" fill="none" stroke="currentColor"
                                        viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M9 5l7 7-7 7" />
                                    </svg>
                                </button>
                            </li>
                        </ul>
                    </div>
                </div>
            </Transition>
        </Teleport>
    </div>
</template>

<script setup>
    import { listingUrl } from '@/urls';
    import { ref, watch, onMounted, onUnmounted, nextTick, computed } from 'vue';
    import { router } from '@inertiajs/vue3';
    import axios from 'axios';

    const props = defineProps({
        modelValue: { type: String, default: '' },
        placeholder: { type: String, default: 'Search businesses, services...' },
        inputClass: { type: String, default: '' },
    });

    const emit = defineEmits(['update:modelValue', 'select-category', 'select-search']);

    const root = ref(null);
    const inputEl = ref(null);
    const query = ref(props.modelValue || '');
    const open = ref(false);
    const loading = ref(false);
    const businesses = ref([]);
    const categories = ref([]);
    const searches = ref([]);
    const highlighted = ref({ type: null, index: -1 });

/**
 * PHASE 16F — the panel had keyboard navigation but NO listbox semantics, so a
 * screen reader received no suggestion state at all. `aria-activedescendant`
 * tracks the highlighted option.
 */
const listboxId = 'search-suggestions';
const activeDescendant = computed(() => {
    if (!highlighted.value.type || highlighted.value.index < 0) return undefined;
    return `suggestion-${highlighted.value.type}-${highlighted.value.index}`;
});
    const dropdownStyle = ref({});

    let debounceTimer = null;
    let blurTimer = null;

    // ✅ Compute the dropdown's fixed position based on the root element
    const updateDropdownPosition = () => {
        if (!root.value) {
            nextTick(() => {
                if (root.value) updateDropdownPosition();
            });
            return;
        }
        const rect = root.value.getBoundingClientRect();
        dropdownStyle.value = {
            top: `${rect.bottom + 8}px`,
            left: `${rect.left}px`,
            width: `${rect.width}px`,
        };
    };

    const handleResize = () => {
        if (open.value) updateDropdownPosition();
    };

    // Sync external model -> local
    watch(() => props.modelValue, (val) => {
        if (val !== query.value) {
            query.value = val || '';
        }
    });

    // Sync local -> external
    watch(query, (val) => {
        emit('update:modelValue', val);
    });

    // ✅ Reposition whenever the dropdown opens
    watch(open, (isOpen) => {
        if (isOpen) {
            nextTick(() => updateDropdownPosition());
        }
    });

    // ✅ Also reposition when content changes
    watch([businesses, categories, searches], () => {
        if (open.value) {
            nextTick(() => updateDropdownPosition());
        }
    });

    const onInput = () => {
        clearTimeout(debounceTimer);
        const q = query.value.trim();

        if (q.length < 2) {
            businesses.value = [];
            categories.value = [];
            searches.value = [];
            open.value = false;
            return;
        }

        open.value = true;
        nextTick(() => updateDropdownPosition());

        debounceTimer = setTimeout(() => fetchSuggestions(q), 300);
    };

    const fetchSuggestions = async (q) => {
        loading.value = true;
        open.value = true;

        try {
            const res = await axios.get('/search/autocomplete', { params: { q } });
            searches.value = res.data.search_suggestions || [];
            categories.value = res.data.category_suggestions || [];

            // ✅ When smart-search suggestions exist, cap businesses so all
            //    three sections fit inside the dropdown without scrolling.
            const allBusinesses = res.data.suggestions || [];
            businesses.value = searches.value.length > 0
                ? allBusinesses.slice(0, 2)
                : allBusinesses;

            highlighted.value = { type: null, index: -1 };

            // ✅ Reposition after content arrives
            await nextTick();
            updateDropdownPosition();
        } catch (e) {
            businesses.value = [];
            categories.value = [];
            searches.value = [];
        } finally {
            loading.value = false;
        }
    };

    const onFocus = () => {
        if (query.value.trim().length >= 2) {
            open.value = true;
            nextTick(() => updateDropdownPosition());
        }
    };

    const onBlur = () => {
        // Delay so click on dropdown can land before close
        clearTimeout(blurTimer);
        blurTimer = setTimeout(() => { open.value = false; }, 150);
    };

    const close = () => {
        open.value = false;
        highlighted.value = { type: null, index: -1 };
    };

    const isHighlighted = (type, index) => {
        return highlighted.value.type === type && highlighted.value.index === index;
    };

    const moveHighlight = (dir) => {
        if (!open.value) {
            if (query.value.trim().length >= 2) open.value = true;
            return;
        }

        const flat = [
            ...businesses.value.map((_, i) => ({ type: 'listing', index: i })),
            ...searches.value.map((_, i) => ({ type: 'search', index: i })),
            ...categories.value.map((_, i) => ({ type: 'category', index: i })),
        ];

        if (flat.length === 0) return;

        let current = flat.findIndex((h) =>
            h.type === highlighted.value.type && h.index === highlighted.value.index
        );

        if (current === -1) current = dir > 0 ? -1 : 0;

        let next = current + dir;
        if (next < 0) next = flat.length - 1;
        if (next >= flat.length) next = 0;

        highlighted.value = flat[next];
    };

    const onEnter = () => {
        if (highlighted.value.type === 'listing') {
            const biz = businesses.value[highlighted.value.index];
            if (biz) selectListing(biz);
            return;
        }
        if (highlighted.value.type === 'search') {
            const s = searches.value[highlighted.value.index];
            if (s) selectSearch(s);
            return;
        }
        if (highlighted.value.type === 'category') {
            const cat = categories.value[highlighted.value.index];
            if (cat) selectCategory(cat);
            return;
        }

        // No highlight → just close and let parent apply
        close();
        inputEl.value?.blur();
    };

    const selectListing = (biz) => {
        close();
        query.value = '';
        router.visit(listingUrl(biz));
    };

    const selectSearch = (suggestion) => {
        close();
        query.value = '';
        emit('select-search', suggestion);
    };

    const selectCategory = (cat) => {
        close();
        query.value = '';
        emit('select-category', cat);
    };

    // Click outside to close
    const handleClickOutside = (e) => {
        if (root.value && !root.value.contains(e.target)) {
            open.value = false;
        }
    };

    onMounted(() => {
        document.addEventListener('mousedown', handleClickOutside);
        window.addEventListener('resize', handleResize);
        window.addEventListener('scroll', handleResize, true);
    });

    onUnmounted(() => {
        document.removeEventListener('mousedown', handleClickOutside);
        window.removeEventListener('resize', handleResize);
        window.removeEventListener('scroll', handleResize, true);
        clearTimeout(debounceTimer);
        clearTimeout(blurTimer);
    });
</script>