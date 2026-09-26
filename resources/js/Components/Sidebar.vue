<!-- resources/js/Components/Sidebar.vue -->
<template>
    <!-- Desktop sidebar (always visible on lg+) -->
    <aside class="hidden lg:flex lg:flex-col fixed left-0 top-0 bottom-0 w-60 bg-white dark:bg-gray-900 border-r border-gray-200 dark:border-gray-800 z-40">
        <!-- Logo / Brand -->
        <div class="h-16 flex items-center px-5 border-b border-gray-200 dark:border-gray-800 flex-shrink-0">
            <Link href="/" class="flex items-center gap-2.5 text-base font-bold text-gray-900 dark:text-white tracking-tight group">
                <div class="w-8 h-8 rounded-xl bg-gradient-to-br from-primary-500 to-primary-700 flex items-center justify-center shadow-lg shadow-primary-500/25 group-hover:shadow-primary-500/40 transition-shadow">
                    <svg class="w-4 h-4 text-white" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M21 10.5V19a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h9.5" />
                        <polyline points="16 2 22 8 16 8" />
                        <line x1="10" y1="14" x2="21" y2="14" />
                        <line x1="10" y1="18" x2="18" y2="18" />
                        <line x1="3" y1="10" x2="8" y2="10" />
                    </svg>
                </div>
                <span>Omniscient</span>
            </Link>
        </div>

        <!-- Sections -->
        <nav class="flex-1 overflow-y-auto py-4 px-3">
            <div v-for="section in sidebarSections" :key="section.label" class="mb-5 last:mb-0">
                <p class="px-3 mb-2 text-[10px] font-bold uppercase tracking-widest text-gray-400 dark:text-gray-500">
                    {{ section.label }}
                </p>
                <ul class="space-y-0.5">
                    <li v-for="item in section.items" :key="item.href">
                        <Link
                            :href="item.href"
                            class="flex items-center gap-2.5 px-3 py-2 rounded-xl text-sm font-medium transition-colors group"
                            :class="isActive(item)
                                ? 'bg-primary-50 dark:bg-primary-900/30 text-primary-700 dark:text-primary-300'
                                : 'text-gray-600 dark:text-gray-400 hover:bg-gray-50 dark:hover:bg-gray-800 hover:text-gray-900 dark:hover:text-gray-200'"
                        >
                            <NavIcon :name="item.icon" size="sm" class="flex-shrink-0" />
                            <span class="truncate">{{ item.label }}</span>
                        </Link>
                    </li>
                </ul>
            </div>
        </nav>

        <!-- Footer -->
        <div class="px-4 py-3 border-t border-gray-200 dark:border-gray-800 flex-shrink-0">
            <p class="text-[10px] text-gray-400 dark:text-gray-500 font-medium uppercase tracking-widest">
                {{ panelLabel }}
            </p>
        </div>
    </aside>

    <!-- Mobile drawer -->
    <Teleport to="body">
        <!-- Backdrop -->
        <Transition
            enter-active-class="transition-opacity duration-200 ease-out"
            enter-from-class="opacity-0"
            enter-to-class="opacity-100"
            leave-active-class="transition-opacity duration-150 ease-in"
            leave-from-class="opacity-100"
            leave-to-class="opacity-0"
        >
            <div v-if="mobileOpen"
                 class="lg:hidden fixed inset-0 z-50 bg-black/60 backdrop-blur-sm"
                 @click="emit('close')">
            </div>
        </Transition>

        <!-- Drawer -->
        <Transition
            enter-active-class="transition-transform duration-300 ease-out"
            enter-from-class="-translate-x-full"
            enter-to-class="translate-x-0"
            leave-active-class="transition-transform duration-200 ease-in"
            leave-from-class="translate-x-0"
            leave-to-class="-translate-x-full"
        >
            <aside v-if="mobileOpen"
                   class="lg:hidden fixed left-0 top-0 bottom-0 w-72 max-w-[85vw] bg-white dark:bg-gray-900 border-r border-gray-200 dark:border-gray-800 z-50 flex flex-col">

                <!-- Close button -->
                <button @click="emit('close')"
                        class="absolute top-3.5 right-3 p-2 rounded-xl text-gray-400 hover:text-gray-700 dark:hover:text-gray-200 hover:bg-gray-100 dark:hover:bg-gray-800 transition-colors z-10"
                        aria-label="Close menu">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>

                <!-- Logo / Brand -->
                <div class="h-16 flex items-center px-5 border-b border-gray-200 dark:border-gray-800 flex-shrink-0">
                    <Link href="/"
                          @click="emit('close')"
                          class="flex items-center gap-2.5 text-base font-bold text-gray-900 dark:text-white tracking-tight group">
                        <div class="w-8 h-8 rounded-xl bg-gradient-to-br from-primary-500 to-primary-700 flex items-center justify-center shadow-lg shadow-primary-500/25 group-hover:shadow-primary-500/40 transition-shadow">
                            <svg class="w-4 h-4 text-white" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                                <path d="M21 10.5V19a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h9.5" />
                                <polyline points="16 2 22 8 16 8" />
                                <line x1="10" y1="14" x2="21" y2="14" />
                                <line x1="10" y1="18" x2="18" y2="18" />
                                <line x1="3" y1="10" x2="8" y2="10" />
                            </svg>
                        </div>
                        <span>Omniscient</span>
                    </Link>
                </div>

                <!-- Sections -->
                <nav class="flex-1 overflow-y-auto py-4 px-3">
                    <div v-for="section in sidebarSections" :key="section.label" class="mb-5 last:mb-0">
                        <p class="px-3 mb-2 text-[10px] font-bold uppercase tracking-widest text-gray-400 dark:text-gray-500">
                            {{ section.label }}
                        </p>
                        <ul class="space-y-0.5">
                            <li v-for="item in section.items" :key="item.href">
                                <Link
                                    :href="item.href"
                                    @click="emit('close')"
                                    class="flex items-center gap-2.5 px-3 py-2 rounded-xl text-sm font-medium transition-colors group"
                                    :class="isActive(item)
                                        ? 'bg-primary-50 dark:bg-primary-900/30 text-primary-700 dark:text-primary-300'
                                        : 'text-gray-600 dark:text-gray-400 hover:bg-gray-50 dark:hover:bg-gray-800 hover:text-gray-900 dark:hover:text-gray-200'"
                                >
                                    <NavIcon :name="item.icon" size="sm" class="flex-shrink-0" />
                                    <span class="truncate">{{ item.label }}</span>
                                </Link>
                            </li>
                        </ul>
                    </div>
                </nav>

                <!-- Footer -->
                <div class="px-4 py-3 border-t border-gray-200 dark:border-gray-800 flex-shrink-0">
                    <p class="text-[10px] text-gray-400 dark:text-gray-500 font-medium uppercase tracking-widest">
                        {{ panelLabel }}
                    </p>
                </div>
            </aside>
        </Transition>
    </Teleport>
</template>

<script setup>
import { computed } from 'vue';
import { Link, usePage } from '@inertiajs/vue3';
import NavIcon from '@/Components/NavIcon.vue';
import { useNavigation } from '@/composables/useNavigation';

const props = defineProps({
    user: {
        type: Object,
        default: null,
    },
    mobileOpen: {
        type: Boolean,
        default: false,
    },
});

const emit = defineEmits(['close']);

const page = usePage();
const { sidebarSectionsFor, isItemActive } = useNavigation();

const sidebarSections = computed(() => sidebarSectionsFor(props.user?.role || 'user'));

const panelLabel = computed(() => {
    const role = props.user?.role;
    if (role === 'owner') return 'Business panel';
    if (role === 'super_admin') return 'Super admin panel';
    if (role === 'admin') return 'Admin panel';
    return 'Panel';
});

const isActive = (item) => isItemActive(item, page.url);
</script>