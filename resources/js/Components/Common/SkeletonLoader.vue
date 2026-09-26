<!-- resources/js/Components/Common/SkeletonLoader.vue -->
<template>
    <!-- KPI cards: grid of stat cards with a big number + small label -->
    <div v-if="variant === 'kpi'" class="grid grid-cols-2 sm:grid-cols-4 gap-4">
        <div v-for="n in count" :key="n"
            class="bg-white dark:bg-gray-800 rounded-2xl border border-gray-100 dark:border-gray-700 p-5 animate-pulse">
            <div class="flex items-center justify-between mb-3">
                <div class="h-2.5 w-20 bg-gray-200 dark:bg-gray-700 rounded"></div>
                <div class="h-4 w-4 bg-gray-200 dark:bg-gray-700 rounded"></div>
            </div>
            <div class="h-7 w-24 bg-gray-200 dark:bg-gray-700 rounded mb-2"></div>
            <div class="h-3 w-32 bg-gray-200 dark:bg-gray-700 rounded"></div>
        </div>
    </div>

    <!-- Stat: single stat block (label + value) -->
    <div v-else-if="variant === 'stat'"
        class="bg-white dark:bg-gray-800 rounded-2xl border border-gray-100 dark:border-gray-700 p-4 animate-pulse">
        <div class="h-2.5 w-16 bg-gray-200 dark:bg-gray-700 rounded mb-3"></div>
        <div class="h-6 w-20 bg-gray-200 dark:bg-gray-700 rounded"></div>
    </div>

    <!-- Chart: header + big chart area -->
    <div v-else-if="variant === 'chart'"
        class="bg-white dark:bg-gray-800 rounded-2xl border border-gray-100 dark:border-gray-700 overflow-hidden animate-pulse">
        <div class="px-6 py-4 border-b border-gray-100 dark:border-gray-700 flex items-center justify-between">
            <div class="space-y-2">
                <div class="h-3.5 w-40 bg-gray-200 dark:bg-gray-700 rounded"></div>
                <div class="h-2.5 w-56 bg-gray-200 dark:bg-gray-700 rounded"></div>
            </div>
            <div class="h-3 w-24 bg-gray-200 dark:bg-gray-700 rounded"></div>
        </div>
        <div class="p-6 h-64">
            <div
                class="w-full h-full bg-gradient-to-br from-gray-100 to-gray-50 dark:from-gray-700/40 dark:to-gray-800/40 rounded-xl">
            </div>
        </div>
    </div>

    <!-- List: rows with avatar + 2 lines -->
    <div v-else-if="variant === 'list'"
        class="bg-white dark:bg-gray-800 rounded-2xl border border-gray-100 dark:border-gray-700 overflow-hidden animate-pulse">
        <div class="px-6 py-4 border-b border-gray-100 dark:border-gray-700 flex items-center justify-between">
            <div class="flex items-center gap-3">
                <div class="w-9 h-9 rounded-xl bg-gray-200 dark:bg-gray-700"></div>
                <div class="space-y-1.5">
                    <div class="h-3 w-32 bg-gray-200 dark:bg-gray-700 rounded"></div>
                    <div class="h-2.5 w-24 bg-gray-200 dark:bg-gray-700 rounded"></div>
                </div>
            </div>
            <div class="h-3 w-16 bg-gray-200 dark:bg-gray-700 rounded"></div>
        </div>
        <div class="divide-y divide-gray-100 dark:divide-gray-700">
            <div v-for="n in count" :key="n" class="px-6 py-4 flex items-start gap-3">
                <div class="w-9 h-9 rounded-2xl bg-gray-200 dark:bg-gray-700 flex-shrink-0"></div>
                <div class="flex-1 min-w-0 space-y-2">
                    <div class="flex items-center justify-between gap-2">
                        <div class="h-3.5 w-28 bg-gray-200 dark:bg-gray-700 rounded"></div>
                        <div class="h-3 w-16 bg-gray-200 dark:bg-gray-700 rounded"></div>
                    </div>
                    <div class="h-2.5 w-full bg-gray-200 dark:bg-gray-700 rounded"></div>
                    <div class="h-2.5 w-3/4 bg-gray-200 dark:bg-gray-700 rounded"></div>
                </div>
            </div>
        </div>
    </div>

    <!-- Card: dashboard card with header + stacked lines -->
    <div v-else-if="variant === 'card'"
        class="bg-white dark:bg-gray-800 rounded-2xl border border-gray-100 dark:border-gray-700 overflow-hidden animate-pulse">
        <div class="px-6 py-4 border-b border-gray-100 dark:border-gray-700 flex items-center gap-3">
            <div class="w-9 h-9 rounded-xl bg-gray-200 dark:bg-gray-700"></div>
            <div class="space-y-1.5">
                <div class="h-3 w-32 bg-gray-200 dark:bg-gray-700 rounded"></div>
                <div class="h-2.5 w-40 bg-gray-200 dark:bg-gray-700 rounded"></div>
            </div>
        </div>
        <div class="p-6 space-y-3">
            <div v-for="n in count" :key="n" class="space-y-2">
                <div class="flex items-center justify-between">
                    <div class="h-3 w-24 bg-gray-200 dark:bg-gray-700 rounded"></div>
                    <div class="h-3 w-12 bg-gray-200 dark:bg-gray-700 rounded"></div>
                </div>
                <div class="h-2 w-full bg-gray-100 dark:bg-gray-700 rounded-full">
                    <div class="h-full w-1/2 bg-gray-200 dark:bg-gray-600 rounded-full"></div>
                </div>
            </div>
        </div>
    </div>

    <!-- Coupons: 3-column ticket row -->
    <div v-else-if="variant === 'coupons'"
        class="bg-white dark:bg-gray-800 rounded-2xl border border-gray-100 dark:border-gray-700 overflow-hidden animate-pulse">
        <div class="px-6 py-4 border-b border-gray-100 dark:border-gray-700 flex items-center gap-3">
            <div class="w-9 h-9 rounded-xl bg-gray-200 dark:bg-gray-700"></div>
            <div class="space-y-1.5">
                <div class="h-3 w-28 bg-gray-200 dark:bg-gray-700 rounded"></div>
                <div class="h-2.5 w-20 bg-gray-200 dark:bg-gray-700 rounded"></div>
            </div>
        </div>
        <div
            class="grid grid-cols-1 sm:grid-cols-3 divide-y sm:divide-y-0 sm:divide-x divide-gray-100 dark:divide-gray-700">
            <div v-for="n in count" :key="n" class="p-5 space-y-2">
                <div class="h-4 w-32 bg-gray-200 dark:bg-gray-700 rounded"></div>
                <div class="flex items-center gap-2">
                    <div class="h-5 w-16 bg-gray-200 dark:bg-gray-700 rounded"></div>
                    <div class="h-3 w-20 bg-gray-200 dark:bg-gray-700 rounded"></div>
                </div>
            </div>
        </div>
    </div>

    <!-- Category strip: horizontal row of icon tiles -->
    <div v-else-if="variant === 'category-strip'"
        class="bg-white dark:bg-gray-800 border-b border-gray-100 dark:border-gray-700 py-4 sm:py-6 animate-pulse">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <!-- Mobile: horizontal row -->
            <div class="sm:hidden flex gap-2 overflow-hidden">
                <div v-for="n in (count || 8)" :key="n"
                    class="flex-shrink-0 w-20 flex flex-col items-center gap-1.5 p-2">
                    <div class="w-12 h-12 rounded-2xl bg-gray-200 dark:bg-gray-700"></div>
                    <div class="h-2.5 w-14 bg-gray-200 dark:bg-gray-700 rounded"></div>
                </div>
            </div>
            <!-- Desktop: centered row -->
            <div class="hidden sm:flex items-start justify-center gap-4 lg:gap-6">
                <div v-for="n in (count || 8)" :key="n" class="flex flex-col items-center gap-2 p-2 min-w-[80px]">
                    <div class="w-14 h-14 rounded-2xl bg-gray-200 dark:bg-gray-700"></div>
                    <div class="h-3 w-16 bg-gray-200 dark:bg-gray-700 rounded"></div>
                </div>
            </div>
        </div>
    </div>

    <!-- Grid cards: small tile grid — mobile carousel, desktop grid -->
    <div v-else-if="variant === 'grid-cards'" class="animate-pulse">
        <!-- Mobile: horizontal scroll row -->
        <div class="sm:hidden -mx-4 px-4 flex gap-3 overflow-hidden">
            <div v-for="n in (count || 6)" :key="n"
                class="flex-shrink-0 w-[45%] bg-white dark:bg-gray-800 rounded-2xl border border-gray-100 dark:border-gray-700 p-3.5 text-center">
                <div class="w-8 h-8 mx-auto bg-gray-200 dark:bg-gray-700 rounded mb-2"></div>
                <div class="h-3 w-3/4 mx-auto bg-gray-200 dark:bg-gray-700 rounded mb-2"></div>
                <div class="h-3 w-16 mx-auto bg-gray-200 dark:bg-gray-700 rounded-full"></div>
            </div>
        </div>
        <!-- Desktop: grid -->
        <div class="hidden sm:grid sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-6 gap-3 sm:gap-4">
            <div v-for="n in (count || 6)" :key="n"
                class="bg-white dark:bg-gray-800 rounded-2xl border border-gray-100 dark:border-gray-700 p-3.5 sm:p-5 text-center">
                <div class="w-8 h-8 mx-auto bg-gray-200 dark:bg-gray-700 rounded mb-2 sm:mb-3"></div>
                <div class="h-3 w-3/4 mx-auto bg-gray-200 dark:bg-gray-700 rounded mb-2"></div>
                <div class="h-3 w-16 mx-auto bg-gray-200 dark:bg-gray-700 rounded-full"></div>
            </div>
        </div>
    </div>

    <!-- Business card: mimics BusinessCard.vue layout -->
    <div v-else-if="variant === 'business-card'"
        class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-100 dark:border-gray-700 overflow-hidden animate-pulse">
        <!-- Cover -->
        <div class="w-full h-48 bg-gray-200 dark:bg-gray-700"></div>
        <!-- Body with overlapping logo -->
        <div class="p-4 pt-7 relative">
            <!-- Logo overlapping cover -->
            <div class="absolute -top-7 left-4">
                <div
                    class="w-14 h-14 rounded-2xl bg-gray-200 dark:bg-gray-700 border-4 border-white dark:border-gray-800">
                </div>
            </div>
            <!-- Name -->
            <div class="ml-16 space-y-2">
                <div class="h-4 w-3/4 bg-gray-200 dark:bg-gray-700 rounded"></div>
                <div class="flex gap-1">
                    <div class="h-5 w-14 bg-gray-200 dark:bg-gray-700 rounded-full"></div>
                    <div class="h-5 w-12 bg-gray-200 dark:bg-gray-700 rounded-full"></div>
                </div>
            </div>
            <!-- Description lines -->
            <div class="mt-4 space-y-2">
                <div class="h-3 w-full bg-gray-200 dark:bg-gray-700 rounded"></div>
                <div class="h-3 w-2/3 bg-gray-200 dark:bg-gray-700 rounded"></div>
            </div>
            <!-- Footer row -->
            <div class="flex items-center gap-3 mt-4 pt-4 border-t border-gray-100 dark:border-gray-700">
                <div class="h-3 w-16 bg-gray-200 dark:bg-gray-700 rounded"></div>
                <div class="h-3 w-10 bg-gray-200 dark:bg-gray-700 rounded"></div>
                <div class="h-3 w-10 bg-gray-200 dark:bg-gray-700 rounded"></div>
            </div>
        </div>
    </div>

    <!-- Review card compact: mimics ReviewCardCompact.vue -->
    <div v-else-if="variant === 'review-card'"
        class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-100 dark:border-gray-700 p-5 animate-pulse">
        <!-- Header: avatar + name + rating -->
        <div class="flex items-start gap-3 mb-3">
            <div class="w-10 h-10 rounded-2xl bg-gray-200 dark:bg-gray-700 flex-shrink-0"></div>
            <div class="flex-1 min-w-0 space-y-2">
                <div class="h-3.5 w-1/2 bg-gray-200 dark:bg-gray-700 rounded"></div>
                <div class="flex gap-0.5">
                    <div v-for="i in 5" :key="i" class="w-3 h-3 bg-gray-200 dark:bg-gray-700 rounded"></div>
                </div>
            </div>
        </div>
        <!-- Body lines -->
        <div class="space-y-2">
            <div class="h-3 w-full bg-gray-200 dark:bg-gray-700 rounded"></div>
            <div class="h-3 w-5/6 bg-gray-200 dark:bg-gray-700 rounded"></div>
            <div class="h-3 w-2/3 bg-gray-200 dark:bg-gray-700 rounded"></div>
        </div>
        <!-- Business tag -->
        <div class="mt-4 pt-3 border-t border-gray-100 dark:border-gray-700">
            <div class="h-3 w-32 bg-gray-200 dark:bg-gray-700 rounded"></div>
        </div>
    </div>
</template>

<script setup>
    defineProps({
        /**
         * One of: kpi | stat | chart | list | card | coupons
         */
        variant: {
            type: String,
            default: 'stat',
        },
        /**
         * Repeat count for variants that support repetition.
         * - kpi: number of KPI cards (default 4)
         * - list: number of list rows (default 3)
         * - card: number of bar rows inside the card (default 4)
         * - coupons: number of coupon cells (default 3)
         * Ignored by: stat, chart
         */
        count: {
            type: Number,
            default: 0,
        },
    });
</script>