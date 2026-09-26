<!-- resources/js/Components/Public/ExploreCarousel.vue -->
<template>
    <section ref="root" class="space-y-3">
        <!-- Header -->
        <div class="flex items-center justify-between gap-3 flex-wrap">
            <Link :href="row.see_all_url"
                class="text-base sm:text-lg font-bold text-gray-900 dark:text-white tracking-tight hover:text-primary-600 dark:hover:text-primary-400 transition-colors inline-flex items-center gap-1.5 group">
                {{ row.title }}
                <!-- ✅ Always-visible "See all" chevron — mobile + desktop -->
                <span class="inline-flex items-center gap-1 text-sm font-semibold text-primary-600 dark:text-primary-400 group-hover:gap-2 transition-all">
                    <span class="hidden sm:inline">See all</span>
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M9 5l7 7-7 7" />
                    </svg>
                </span>
            </Link>

            <!-- Desktop arrows -->
            <div v-show="hasScroll" class="hidden sm:flex items-center gap-2">
                <button type="button" @click="scrollBy(-1)" :disabled="!canScrollLeft"
                    class="w-8 h-8 rounded-full border border-gray-200 dark:border-gray-600 bg-white dark:bg-gray-800 flex items-center justify-center text-gray-600 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors disabled:opacity-40 disabled:cursor-not-allowed">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
                    </svg>
                </button>
                <button type="button" @click="scrollBy(1)" :disabled="!canScrollRight"
                    class="w-8 h-8 rounded-full border border-gray-200 dark:border-gray-600 bg-white dark:bg-gray-800 flex items-center justify-center text-gray-600 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors disabled:opacity-40 disabled:cursor-not-allowed">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                    </svg>
                </button>
            </div>
        </div>

        <!-- Loading skeletons -->
        <div v-if="loading" class="flex gap-3 overflow-hidden">
            <div v-for="n in 6" :key="n" class="flex-shrink-0 w-[160px] sm:w-[180px]">
                <div class="bg-white dark:bg-gray-800 rounded-2xl border border-gray-100 dark:border-gray-700 overflow-hidden">
                    <div class="aspect-[3/2] bg-gray-100 dark:bg-gray-700 animate-pulse"></div>
                    <div class="p-2.5 space-y-2">
                        <div class="h-3 bg-gray-100 dark:bg-gray-700 rounded animate-pulse"></div>
                        <div class="h-2.5 bg-gray-100 dark:bg-gray-700 rounded w-2/3 animate-pulse"></div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Cards -->
        <div v-else-if="businesses.length > 0" class="relative">
            <div ref="scroller" class="flex gap-3 overflow-x-auto scrollbar-hide snap-x snap-mandatory pb-1 -mx-4 px-4 sm:mx-0 sm:px-0"
                @scroll="updateArrows">
                <div v-for="biz in businesses" :key="biz.id" class="flex-shrink-0 w-[160px] sm:w-[180px] snap-start">
                    <ExploreCard :business="biz" />
                </div>
            </div>
        </div>

        <!-- Empty -->
        <div v-else-if="!loading && loadedOnce" class="text-sm text-gray-400 dark:text-gray-500 py-4">
            No businesses in this category yet.
        </div>
    </section>
</template>

<script setup>
import { ref, onMounted, onUnmounted, nextTick } from 'vue';
import { Link } from '@inertiajs/vue3';
import axios from 'axios';
import ExploreCard from './ExploreCard.vue';

const props = defineProps({
    row: { type: Object, required: true },
});

const businesses = ref([]);
const loading = ref(false);
const loadedOnce = ref(false);

const scroller = ref(null);
const canScrollLeft = ref(false);
const canScrollRight = ref(false);
const hasScroll = ref(false);

const root = ref(null);
let observer = null;
let resizeObserver = null;

const updateArrows = () => {
    const el = scroller.value;
    if (!el) return;
    const maxScroll = el.scrollWidth - el.clientWidth;
    hasScroll.value = maxScroll > 4;
    canScrollLeft.value = el.scrollLeft > 4;
    canScrollRight.value = maxScroll > 4 && el.scrollLeft < maxScroll - 4;
};

const scrollBy = (direction) => {
    const el = scroller.value;
    if (!el) return;
    el.scrollBy({ left: el.clientWidth * 0.85 * direction, behavior: 'smooth' });
};

const fetchRow = async () => {
    loading.value = true;
    try {
        const params = { category: props.row.category_id };
        if (props.row.city_id) params.city_id = props.row.city_id;
        const res = await axios.get('/api/explore/row', { params });
        businesses.value = res.data.businesses || [];
    } catch (e) {
        businesses.value = [];
    } finally {
        loading.value = false;
        loadedOnce.value = true;
        await nextTick();
        updateArrows();
    }
};

onMounted(() => {
    // Lazy-load when this row scrolls into view
    if (typeof IntersectionObserver !== 'undefined') {
        observer = new IntersectionObserver((entries) => {
            entries.forEach((entry) => {
                if (entry.isIntersecting && !loadedOnce.value && !loading.value) {
                    fetchRow();
                    observer?.disconnect();
                }
            });
        }, { rootMargin: '300px 0px' });
        observer.observe(root.value);
    } else {
        // Fallback — load immediately
        fetchRow();
    }

    if (typeof ResizeObserver !== 'undefined' && scroller.value) {
        resizeObserver = new ResizeObserver(updateArrows);
        resizeObserver.observe(scroller.value);
    }
});

onUnmounted(() => {
    observer?.disconnect();
    resizeObserver?.disconnect();
});
</script>

