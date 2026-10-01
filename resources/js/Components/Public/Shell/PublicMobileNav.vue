<!-- resources/js/Components/Public/Shell/PublicMobileNav.vue -->
<!--
  PHASE 16D — PUBLIC MOBILE BOTTOM NAVIGATION.

  Replaces the absolute-dropdown mobile menu as the PRIMARY mobile navigation.
  It exposes only destinations that actually exist and that a guest can reach:

    Explore    /explore      public
    Search     /search       public
    Saved      /favorites    AUTH ONLY -> routes to /login for a guest
    Menu       opens the secondary Sheet (categories, locations, pricing,
               contact, account)

  Guest behaviour is deliberate: "Saved" is not hidden, because a hidden
  destination teaches the user nothing. It links to the login flow instead of
  silently failing, which is the graceful path the brief requires.

  Active state uses aria-current AND a visual indicator, never colour alone.
  Touch targets are >= 44px. Safe-area inset is applied here; page clearance is
  the shell's job, not each page's.
-->
<script setup>
import { computed } from 'vue';
import { Link, usePage } from '@inertiajs/vue3';

const emit = defineEmits(['open-menu']);

const page = usePage();

const isAuthenticated = computed(() => Boolean(page.props.auth?.user));

/** Exact paths, so a Listing page can never light up an unrelated destination. */
const isActive = (path) => {
    const current = page.url ?? '';
    return current === path || current.startsWith(path + '/') || current.startsWith(path + '?');
};

const items = computed(() => [
    { key: 'explore', label: 'Explore', href: '/explore', icon: 'compass' },
    { key: 'search', label: 'Search', href: '/search', icon: 'search' },
    {
        key: 'saved',
        label: 'Saved',
        // A guest is sent to the existing auth flow rather than a dead link.
        href: isAuthenticated.value ? '/favorites' : '/login',
        icon: 'heart',
        active: isAuthenticated.value ? isActive('/favorites') : false,
    },
    { key: 'menu', label: 'Menu', action: 'menu', icon: 'menu' },
]);
</script>

<template>
    <nav
        class="md:hidden fixed bottom-0 inset-x-0 z-40 border-t border-hairline dark:border-hairline-dark bg-surface dark:bg-gray-900"
        style="padding-bottom: env(safe-area-inset-bottom);"
        aria-label="Primary"
    >
        <ul class="grid grid-cols-4">
            <li v-for="item in items" :key="item.key">
                <button
                    v-if="item.action === 'menu'"
                    type="button"
                    class="flex w-full min-h-14 flex-col items-center justify-center gap-0.5 px-1 py-2 text-ink-muted dark:text-gray-400 transition-colors duration-fast"
                    @click="emit('open-menu')"
                >
                    <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
                    </svg>
                    <span class="text-[10px] font-semibold">{{ item.label }}</span>
                </button>

                <Link
                    v-else
                    :href="item.href"
                    class="flex w-full min-h-14 flex-col items-center justify-center gap-0.5 px-1 py-2 transition-colors duration-fast"
                    :class="isActive(item.href) || item.active
                        ? 'text-primary-600 dark:text-primary-400'
                        : 'text-ink-muted dark:text-gray-400'"
                    :aria-current="(isActive(item.href) || item.active) ? 'page' : undefined"
                >
                    <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                        <path
                            v-if="item.icon === 'compass'"
                            stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8"
                        />
                        <path
                            v-else-if="item.icon === 'search'"
                            stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M21 21l-4.35-4.35M17 11a6 6 0 11-12 0 6 6 0 0112 0z"
                        />
                        <path
                            v-else
                            stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z"
                        />
                    </svg>
                    <span class="text-[10px] font-semibold">{{ item.label }}</span>

                    <!-- Active is not colour alone. -->
                    <span
                        v-if="isActive(item.href) || item.active"
                        class="mt-0.5 h-0.5 w-5 rounded-pill bg-primary-600 dark:bg-primary-400"
                        aria-hidden="true"
                    />
                </Link>
            </li>
        </ul>
    </nav>
</template>
