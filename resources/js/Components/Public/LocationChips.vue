<!-- resources/js/Components/Public/LocationChips.vue -->
<template>
    <div v-if="loading" class="flex gap-1.5 overflow-hidden">
        <div v-for="n in 4" :key="n"
            class="flex-shrink-0 h-7 w-32 bg-gray-100 dark:bg-gray-800 rounded-full animate-pulse"></div>
    </div>

    <div v-else-if="chips.length > 0" class="space-y-2">
        <!-- Header -->
        <div class="flex items-center gap-1.5">
            <svg class="w-3.5 h-3.5 text-primary-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                    d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z" />
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                    d="M15 11a3 3 0 11-6 0 3 3 0 016 0z" />
            </svg>
            <p class="text-[11px] font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wide" style="margin-top: 10px;">
                <template v-if="city">Near you in {{ city.name }}</template>
                <template v-else>Popular categories</template>
            </p>
        </div>

        <!-- Chips row with fade hints -->
        <div class="relative -mx-4 sm:mx-0">
            <div ref="scroller" class="px-4 sm:px-0 flex gap-1.5 overflow-x-auto scrollbar-hide"
                @scroll="updateFade">
                <Link v-for="chip in chips" :key="chip.category_id"
                    :href="`/directory?category=${chip.category_id}${city ? `&city_id=${city.id}` : ''}`"
                    class="flex-shrink-0 inline-flex items-center gap-1.5 px-2.5 py-1.5 rounded-full border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 text-gray-700 dark:text-gray-300 hover:border-primary-400 hover:text-primary-600 dark:hover:text-primary-400 hover:bg-primary-50/50 dark:hover:bg-primary-900/20 transition-all group">
                    <span class="text-xs font-medium whitespace-nowrap">
                        {{ chip.label }}
                    </span>
                    <span
                        class="text-[10px] font-bold px-1.5 py-0.5 rounded-full bg-gray-100 dark:bg-gray-700 text-gray-500 dark:text-gray-400 group-hover:bg-primary-100 dark:group-hover:bg-primary-900/40 group-hover:text-primary-600 dark:group-hover:text-primary-400 transition-colors">
                        {{ chip.count }}
                    </span>
                </Link>
            </div>

            <!-- ✅ Right-edge fade — solid at the edge, gone inward -->
            <div v-show="canScrollRight"
                class="pointer-events-none absolute top-0 right-0 h-full w-20 bg-gradient-to-l from-gray-50 to-transparent dark:from-gray-900"></div>

            <!-- ✅ Left-edge fade — appears after user scrolls right -->
            <div v-show="canScrollLeft"
                class="pointer-events-none absolute top-0 left-0 h-full w-20 bg-gradient-to-r from-gray-50 to-transparent dark:from-gray-900"></div>
        </div>
    </div>
</template>

<script setup>
import { ref, watch, onMounted, onUnmounted, nextTick } from 'vue';
import { Link } from '@inertiajs/vue3';
import axios from 'axios';

const chips = ref([]);
const city = ref(null);
const loading = ref(true);

// ✅ Fade hint state
const scroller = ref(null);
const canScrollLeft = ref(false);
const canScrollRight = ref(false);

const updateFade = () => {
    const el = scroller.value;
    if (!el) return;
    const maxScroll = el.scrollWidth - el.clientWidth;
    canScrollLeft.value = el.scrollLeft > 4;
    canScrollRight.value = maxScroll > 4 && el.scrollLeft < maxScroll - 4;
};

let resizeObserver = null;

const fetchChips = async () => {
    try {
        const res = await axios.get('/api/location-chips');
        chips.value = res.data.chips || [];
        city.value = res.data.city || null;
    } catch (e) {
        chips.value = [];
        city.value = null;
    } finally {
        loading.value = false;
    }
};

// ✅ Recalculate fade whenever chips change (covers slow network + async render)
watch(chips, async () => {
    await nextTick();
    updateFade();
});

onMounted(async () => {
    await fetchChips();
    await nextTick();
    updateFade();

    if (typeof ResizeObserver !== 'undefined' && scroller.value) {
        resizeObserver = new ResizeObserver(updateFade);
        resizeObserver.observe(scroller.value);
    }
});

onUnmounted(() => {
    resizeObserver?.disconnect();
});
</script>

