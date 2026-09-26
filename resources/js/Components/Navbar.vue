<template>
    <nav class="bg-white/90 dark:bg-gray-900/90 backdrop-blur-md border-b border-gray-200 dark:border-gray-800 sticky top-0 z-40">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex justify-between h-16">

                <!-- Left: hamburger (mobile) + logo (only for roles without sidebar) -->
                <div class="flex items-center gap-2">
                    <!-- Hamburger (only for roles with a sidebar) -->
                    <button v-if="hasSidebar"
                            @click="emit('toggle-sidebar')"
                            class="lg:hidden p-2 -ml-2 rounded-xl text-gray-500 dark:text-gray-400 hover:text-gray-900 dark:hover:text-gray-100 hover:bg-gray-100 dark:hover:bg-gray-800 transition-colors"
                            aria-label="Open menu">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
                        </svg>
                    </button>

                    <!-- Logo (hidden on desktop when sidebar is visible) -->
                    <Link v-if="!hasSidebar || true"
                          href="/"
                          :class="hasSidebar ? 'lg:opacity-0 lg:pointer-events-none' : ''"
                          class="flex items-center gap-2.5 text-xl font-bold text-gray-900 dark:text-white tracking-tight group transition-opacity">
                        <div class="w-9 h-9 rounded-xl bg-gradient-to-br from-primary-500 to-primary-700 flex items-center justify-center shadow-lg shadow-primary-500/25 group-hover:shadow-primary-500/40 transition-shadow">
                            <svg class="w-5 h-5 text-white" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                                <path d="M21 10.5V19a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h9.5" />
                                <polyline points="16 2 22 8 16 8" />
                                <line x1="10" y1="14" x2="21" y2="14" />
                                <line x1="10" y1="18" x2="18" y2="18" />
                                <line x1="3" y1="10" x2="8" y2="10" />
                            </svg>
                        </div>
                        <span class="hidden sm:inline">Omniscient</span>
                    </Link>
                </div>

                <!-- Desktop Navigation -->
                <div class="hidden lg:flex items-center gap-1">
                    <NavLink
                        v-for="item in desktopItems"
                        :key="item.href"
                        :href="item.href"
                        :active="isActive(item)"
                    >
                        <span class="flex items-center gap-1.5">
                            <NavIcon :name="item.icon" size="sm" />
                            {{ item.label }}
                            <span
                                v-if="item.label === 'Contacts' && unreadMessages > 0"
                                class="inline-flex items-center justify-center min-w-[18px] h-[18px] px-1.5 bg-red-500 text-white text-[10px] font-bold rounded-full"
                            >
                                {{ unreadMessages > 99 ? '99+' : unreadMessages }}
                            </span>
                        </span>
                    </NavLink>
                </div>

                <!-- Right side -->
                <div class="flex items-center gap-2">
                    <DarkModeToggle />
                    <PushNotificationToggle />
                    <NotificationDropdown
                        :route="notificationRoute"
                        :all-notifications-route="allNotificationsRoute"
                        :initial-count="notificationCount"
                        @count-updated="handleNotificationCount"
                    />

                    <!-- User dropdown -->
                    <div class="relative" ref="userMenuRef">
                        <button @click="toggleUserMenu"
                                class="flex items-center gap-2 pl-1 pr-2 py-1 rounded-xl hover:bg-gray-100 dark:hover:bg-gray-800 transition-colors focus:outline-none">
                            <div class="w-9 h-9 rounded-xl bg-gradient-to-br from-primary-500 to-primary-700 flex items-center justify-center text-white font-bold text-xs shadow-md">
                                {{ initials }}
                            </div>
                            <svg class="w-4 h-4 text-gray-400 transition-transform"
                                 :class="{ 'rotate-180': dropdownOpen }"
                                 fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                            </svg>
                        </button>

                        <Transition
                            enter-active-class="transition duration-150 ease-out"
                            enter-from-class="opacity-0 scale-95 -translate-y-1"
                            enter-to-class="opacity-100 scale-100 translate-y-0"
                            leave-active-class="transition duration-100 ease-in"
                            leave-from-class="opacity-100 scale-100 translate-y-0"
                            leave-to-class="opacity-0 scale-95 -translate-y-1">
                            <div v-if="dropdownOpen"
                                 class="absolute right-0 mt-2 w-72 rounded-2xl shadow-xl shadow-gray-200/50 dark:shadow-black/30 bg-white dark:bg-gray-800 border border-gray-100 dark:border-gray-700 overflow-hidden z-50">

                                <!-- User info header -->
                                <div class="px-4 py-3.5 bg-gradient-to-br from-gray-50 to-white dark:from-gray-800 dark:to-gray-800/50 border-b border-gray-100 dark:border-gray-700">
                                    <div class="flex items-center gap-3">
                                        <div class="w-10 h-10 rounded-xl bg-gradient-to-br from-primary-500 to-primary-700 flex items-center justify-center text-white font-bold text-sm shadow-md">
                                            {{ initials }}
                                        </div>
                                        <div class="min-w-0 flex-1">
                                            <p class="text-sm font-bold text-gray-900 dark:text-white truncate tracking-tight">
                                                {{ userName }}
                                            </p>
                                            <p class="text-xs text-gray-500 dark:text-gray-400 truncate">
                                                {{ userEmail }}
                                            </p>
                                        </div>
                                    </div>
                                    <div class="mt-2.5">
                                        <span class="inline-flex items-center px-2 py-0.5 text-[10px] font-bold uppercase tracking-wide rounded-full"
                                              :class="roleBadgeClass">
                                            {{ userRole }}
                                        </span>
                                    </div>
                                </div>

                                <!-- Shortcuts (mobile-only access to items not in bottom tabs) -->
                                <div class="py-1.5 max-h-[60vh] overflow-y-auto">
                                    <template v-if="shortcutItems.length > 0">
                                        <p class="px-4 pt-2 pb-1 text-[10px] font-bold text-gray-400 uppercase tracking-widest">
                                            Shortcuts
                                        </p>
                                        <Link
                                            v-for="item in shortcutItems"
                                            :key="item.href"
                                            :href="item.href"
                                            class="flex items-center gap-2.5 mx-2 px-3 py-2.5 text-sm text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700/50 rounded-xl transition-colors font-medium"
                                        >
                                            <NavIcon :name="item.icon" size="sm" class="flex-shrink-0 text-gray-400" />
                                            {{ item.label }}
                                        </Link>
                                        <div class="my-1.5 mx-3 border-t border-gray-100 dark:border-gray-700"></div>
                                    </template>

                                    <Link v-if="user?.role === 'user'"
                                          href="/owner/businesses/create"
                                          class="flex items-center gap-2.5 mx-2 px-3 py-2.5 text-sm text-amber-700 dark:text-amber-400 hover:bg-amber-50 dark:hover:bg-amber-900/20 rounded-xl transition-colors font-semibold">
                                        <NavIcon name="plus-briefcase" size="sm" class="flex-shrink-0" />
                                        List Your Business
                                    </Link>

                                    <Link v-if="user?.role === 'user'"
                                          href="/user/reviews"
                                          class="flex items-center gap-2.5 mx-2 px-3 py-2.5 text-sm text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700/50 rounded-xl transition-colors font-medium">
                                        <NavIcon name="star" size="sm" class="flex-shrink-0 text-gray-400" />
                                        My Reviews
                                    </Link>

                                    <Link href="/profile"
                                          class="flex items-center gap-2.5 mx-2 px-3 py-2.5 text-sm text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700/50 rounded-xl transition-colors font-medium">
                                        <NavIcon name="user" size="sm" class="flex-shrink-0 text-gray-400" />
                                        Profile
                                    </Link>

                                    <Link href="/dashboard"
                                          class="flex items-center gap-2.5 mx-2 px-3 py-2.5 text-sm text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700/50 rounded-xl transition-colors font-medium">
                                        <NavIcon name="grid" size="sm" class="flex-shrink-0 text-gray-400" />
                                        Dashboard
                                    </Link>

                                    <div class="my-1.5 mx-3 border-t border-gray-100 dark:border-gray-700"></div>

                                    <button @click="logout"
                                            class="flex items-center gap-2.5 w-full text-left mx-2 px-3 py-2.5 text-sm text-red-600 dark:text-red-400 hover:bg-red-50 dark:hover:bg-red-900/20 rounded-xl transition-colors font-medium">
                                        <svg class="w-4 h-4 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1" />
                                        </svg>
                                        Log Out
                                    </button>
                                </div>
                            </div>
                        </Transition>
                    </div>
                </div>
            </div>
        </div>
    </nav>

    <!-- Mobile bottom navigation -->
    <MobileNav :user="user" :notification-count="notificationCount" />
</template>

<script setup>
import { ref, computed, onMounted, onUnmounted } from 'vue';
import { Link, router, usePage } from '@inertiajs/vue3';
import NavLink from '@/Components/NavLink.vue';
import NavIcon from '@/Components/NavIcon.vue';
import NotificationDropdown from '@/Components/NotificationDropdown.vue';
import PushNotificationToggle from '@/Components/PushNotificationToggle.vue';
import DarkModeToggle from '@/Components/DarkModeToggle.vue';
import MobileNav from '@/Components/MobileNav.vue';
import { useNavigation } from '@/composables/useNavigation';

const props = defineProps({
    user: {
        type: Object,
        required: true,
    },
    notificationCount: {
        type: Number,
        default: 0,
    },
});

const emit = defineEmits(['toggle-sidebar']);

const page = usePage();
const dropdownOpen = ref(false);
const userMenuRef = ref(null);
const unreadMessages = ref(0);

const { desktopItemsFor, mobileItemsFor, isItemActive, usesSidebar } = useNavigation();

const hasSidebar = computed(() => usesSidebar(props.user?.role));


// ──── User info ────
const userName = computed(() => props.user?.name || 'User');
const userEmail = computed(() => props.user?.email || '');
const initials = computed(() => {
    const name = userName.value;
    return name
        .split(' ')
        .map((n) => n[0])
        .join('')
        .toUpperCase()
        .slice(0, 2);
});

const userRole = computed(() => {
    const role = props.user?.role;
    if (!role) return 'Guest';
    if (role === 'super_admin') return 'Super Admin';
    if (role === 'admin') return 'Admin';
    if (role === 'owner') return 'Owner';
    if (role === 'user') return 'User';
    return role;
});

const roleBadgeClass = computed(() => {
    const role = props.user?.role;
    const classes = {
        super_admin: 'bg-purple-100 dark:bg-purple-900/40 text-purple-700 dark:text-purple-300',
        admin:       'bg-blue-100 dark:bg-blue-900/40 text-blue-700 dark:text-blue-300',
        owner:       'bg-emerald-100 dark:bg-emerald-900/40 text-emerald-700 dark:text-emerald-300',
        user:        'bg-gray-100 dark:bg-gray-800 text-gray-700 dark:text-gray-300',
    };
    return classes[role] || classes.user;
});

// ──── Nav items (from composable) ────
const role = computed(() => props.user?.role || 'user');
const desktopItems = computed(() => desktopItemsFor(role.value));
const mobileItems = computed(() => mobileItemsFor(role.value));

// Items NOT shown in mobile tabs → surface them in the user dropdown as shortcuts
const shortcutItems = computed(() => {
    const inMobile = new Set(mobileItems.value.map((i) => i.label));
    return desktopItemsFor(role.value).filter((i) => !inMobile.has(i.label));
});

const isActive = (item) => isItemActive(item, page.url);

// ──── Notification routes ────
const notificationRoute = computed(() => {
    const r = props.user?.role;
    if (r === 'admin' || r === 'super_admin') return '/admin/notifications/dropdown';
    return '/owner/notifications/dropdown';
});

const allNotificationsRoute = computed(() => {
    const r = props.user?.role;
    if (r === 'admin' || r === 'super_admin') return '/admin/notifications';
    return '/owner/notifications';
});

const handleNotificationCount = () => {
    // Optional handler if you want to react to count updates
};

// ──── Dropdown ────
const toggleUserMenu = () => {
    dropdownOpen.value = !dropdownOpen.value;
};

const closeDropdown = () => {
    dropdownOpen.value = false;
};

const logout = () => {
    router.post('/logout', {}, {
        onFinish: () => {
            // Redirect handled by Laravel
        },
    });
};

const handleClickOutside = (event) => {
    if (userMenuRef.value && !userMenuRef.value.contains(event.target)) {
        closeDropdown();
    }
};

onMounted(() => {
    document.addEventListener('click', handleClickOutside);
});

onUnmounted(() => {
    document.removeEventListener('click', handleClickOutside);
});
</script>