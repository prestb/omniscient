<!-- resources/js/Components/PageHeader.vue -->
<!--
    Compact page header for inner pages.
    Replaces the tall gradient hero strip — saves ~190px vertical space.
    Supports page-specific accent color + embedded breadcrumb.

    Usage:
        <PageHeader
            color="blue"
            :breadcrumb="[
                { label: 'Admin', href: '/admin/dashboard' },
                { label: 'Notifications' }
            ]"
        >
            <template #icon><path d="..." /></template>
            <template #title>Your notifications</template>
            <template #subtitle>Stay updated with platform activity</template>
            <template #actions>
                <button class="btn-primary">Action</button>
            </template>
        </PageHeader>
-->
<template>
    <div class="bg-white dark:bg-gray-800 border-b border-gray-100 dark:border-gray-700">
        <!-- Accent bar -->
        <div class="h-[3px]" :class="accentBarClass"></div>

        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-5">

            <!-- Breadcrumb (compact) -->
            <nav v-if="breadcrumb && breadcrumb.length > 0"
                 class="flex items-center gap-1.5 text-xs text-gray-400 dark:text-gray-500 mb-3 flex-wrap">
                <template v-for="(crumb, index) in breadcrumb" :key="index">
                    <a v-if="crumb.href && index < breadcrumb.length - 1"
                       :href="crumb.href"
                       class="hover:text-gray-600 dark:hover:text-gray-300 transition-colors font-medium">
                        {{ crumb.label }}
                    </a>
                    <span v-else
                          :class="index === breadcrumb.length - 1
                              ? 'text-gray-600 dark:text-gray-300 font-semibold'
                              : 'font-medium'">
                        {{ crumb.label }}
                    </span>
                    <svg v-if="index < breadcrumb.length - 1"
                         class="w-3 h-3 text-gray-300 dark:text-gray-600 flex-shrink-0"
                         fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                    </svg>
                </template>
            </nav>

            <!-- Icon + title + subtitle + actions -->
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                <div class="flex items-center gap-3 min-w-0">
                    <div class="w-11 h-11 rounded-2xl flex items-center justify-center flex-shrink-0"
                         :class="iconTileClass">
                        <svg class="w-5 h-5" :class="iconColorClass"
                             fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <slot name="icon">
                                <!-- Default icon: document -->
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                      d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                            </slot>
                        </svg>
                    </div>
                    <div class="min-w-0">
                        <h1 class="text-lg md:text-xl font-bold text-gray-900 dark:text-white tracking-tight truncate">
                            <slot name="title">Page Title</slot>
                        </h1>
                        <p v-if="$slots.subtitle" class="text-xs text-gray-500 dark:text-gray-400 truncate mt-0.5">
                            <slot name="subtitle" />
                        </p>
                    </div>
                </div>

                <div v-if="$slots.actions" class="flex items-center gap-2 flex-wrap">
                    <slot name="actions" />
                </div>
            </div>
        </div>
    </div>
</template>

<script setup>
import { computed } from 'vue';

const props = defineProps({
    breadcrumb: {
        type: Array,
        default: () => [],
    },
    color: {
        type: String,
        default: 'primary',
        validator: (v) => [
            'primary', 'blue', 'amber', 'emerald', 'violet', 'purple',
            'pink', 'teal', 'sky', 'indigo', 'cyan', 'gray',
        ].includes(v),
    },
});

// ─── Color map ───
// Each entry: accent bar (solid), icon tile (light bg + dark mode), icon (color)
const COLOR_MAP = {
    primary: {
        bar:   'bg-primary-500',
        tile:  'bg-primary-50 dark:bg-primary-900/30',
        icon:  'text-primary-600 dark:text-primary-400',
    },
    blue: {
        bar:   'bg-blue-500',
        tile:  'bg-blue-50 dark:bg-blue-900/30',
        icon:  'text-blue-600 dark:text-blue-400',
    },
    amber: {
        bar:   'bg-amber-500',
        tile:  'bg-amber-50 dark:bg-amber-900/30',
        icon:  'text-amber-600 dark:text-amber-400',
    },
    emerald: {
        bar:   'bg-emerald-500',
        tile:  'bg-emerald-50 dark:bg-emerald-900/30',
        icon:  'text-emerald-600 dark:text-emerald-400',
    },
    violet: {
        bar:   'bg-violet-500',
        tile:  'bg-violet-50 dark:bg-violet-900/30',
        icon:  'text-violet-600 dark:text-violet-400',
    },
    purple: {
        bar:   'bg-purple-500',
        tile:  'bg-purple-50 dark:bg-purple-900/30',
        icon:  'text-purple-600 dark:text-purple-400',
    },
    pink: {
        bar:   'bg-pink-500',
        tile:  'bg-pink-50 dark:bg-pink-900/30',
        icon:  'text-pink-600 dark:text-pink-400',
    },
    teal: {
        bar:   'bg-teal-500',
        tile:  'bg-teal-50 dark:bg-teal-900/30',
        icon:  'text-teal-600 dark:text-teal-400',
    },
    sky: {
        bar:   'bg-sky-500',
        tile:  'bg-sky-50 dark:bg-sky-900/30',
        icon:  'text-sky-600 dark:text-sky-400',
    },
    indigo: {
        bar:   'bg-indigo-500',
        tile:  'bg-indigo-50 dark:bg-indigo-900/30',
        icon:  'text-indigo-600 dark:text-indigo-400',
    },
    cyan: {
        bar:   'bg-cyan-500',
        tile:  'bg-cyan-50 dark:bg-cyan-900/30',
        icon:  'text-cyan-600 dark:text-cyan-400',
    },
    gray: {
        bar:   'bg-gray-400',
        tile:  'bg-gray-100 dark:bg-gray-800',
        icon:  'text-gray-600 dark:text-gray-400',
    },
};

const fallback = COLOR_MAP.primary;
const active = computed(() => COLOR_MAP[props.color] || fallback);

const accentBarClass = computed(() => active.value.bar);
const iconTileClass = computed(() => active.value.tile);
const iconColorClass = computed(() => active.value.icon);
</script>