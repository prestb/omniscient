<!-- resources/js/Components/Public/Shell/PublicDesktopNav.vue -->
<!--
  PHASE 16D — PUBLIC DESKTOP NAVIGATION.

  Phase 16A found the public nav exposed `/directory`, `/explore`, `/pricing`,
  `/contact` — a directory/marketing set with no Search, Categories, Locations
  or Saved. It also found the shell read as admin-adjacent rather than as a
  discovery product.

  This reorganises navigation around DISCOVERY TASKS:
    Explore · Search · Categories · Locations
  Pricing/Contact move to secondary (they are commercial/informational, not
  discovery). No new routes were invented — every href already exists.

  Active state is exact-path based, so a Listing page cannot light up an
  unrelated destination. Active is never colour alone.
-->
<script setup>
import { computed } from 'vue';
import { Link, usePage } from '@inertiajs/vue3';

const page = usePage();

const isActive = (path) => {
    const current = page.url ?? '';
    return current === path || current.startsWith(path + '/') || current.startsWith(path + '?');
};

const destinations = [
    { label: 'Explore', href: '/explore' },
    { label: 'Search', href: '/search' },
    { label: 'Categories', href: '/categories' },
    { label: 'Locations', href: '/locations' },
];

const isAuthenticated = computed(() => Boolean(page.props.auth?.user));
</script>

<template>
    <nav class="hidden md:flex items-center gap-1" aria-label="Discovery">
        <Link
            v-for="d in destinations"
            :key="d.href"
            :href="d.href"
            class="relative px-3 py-2 rounded-control text-body font-semibold transition-colors duration-fast"
            :class="isActive(d.href)
                ? 'text-primary-700 dark:text-primary-300'
                : 'text-ink-muted dark:text-gray-400 hover:text-ink dark:hover:text-gray-100 hover:bg-gray-100 dark:hover:bg-gray-800'"
            :aria-current="isActive(d.href) ? 'page' : undefined"
        >
            {{ d.label }}
            <span
                v-if="isActive(d.href)"
                class="absolute inset-x-3 -bottom-0.5 h-0.5 rounded-pill bg-primary-600 dark:bg-primary-400"
                aria-hidden="true"
            />
        </Link>
    </nav>
</template>
