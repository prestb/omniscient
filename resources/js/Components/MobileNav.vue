<template>
    <nav class="lg:hidden fixed bottom-0 left-0 right-0 z-50 bg-white/95 dark:bg-gray-900/95 backdrop-blur-md border-t border-gray-200 dark:border-gray-800 safe-area-padding">
        <div class="flex items-center justify-around h-16 max-w-lg mx-auto">
            <Link
                v-for="item in mobileItems"
                :key="item.href"
                :href="item.href"
                class="flex flex-col items-center justify-center flex-1 h-full relative group transition-colors"
                :class="isActive(item)
                    ? 'text-primary-600 dark:text-primary-400'
                    : 'text-gray-500 dark:text-gray-400 hover:text-gray-900 dark:hover:text-gray-200'"
            >
                <!-- Active pill -->
                <div
                    v-if="isActive(item)"
                    class="absolute top-1 left-1/2 -translate-x-1/2 w-12 h-1 bg-primary-600 dark:bg-primary-400 rounded-full"
                ></div>

                <!-- Icon -->
                <NavIcon
                    :name="item.icon"
                    size="lg"
                    class="transition-transform group-hover:scale-110 group-active:scale-95"
                />

                <!-- Label -->
                <span
                    class="text-[10px] mt-0.5 font-bold tracking-tight"
                    :class="isActive(item) ? 'font-bold' : 'font-medium'"
                >
                    {{ item.label }}
                </span>

                <!-- Badge -->
                <span
                    v-if="item.label === 'Contacts' && notificationCount > 0"
                    class="absolute top-1 right-1/4 min-w-[18px] h-[18px] px-1 bg-red-500 text-white text-[10px] font-bold rounded-full flex items-center justify-center shadow-md"
                >
                    {{ notificationCount > 99 ? '99+' : notificationCount }}
                </span>
            </Link>
        </div>
    </nav>
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
    notificationCount: {
        type: Number,
        default: 0,
    },
});

const page = usePage();
const { mobileItemsFor, isItemActive } = useNavigation();

const mobileItems = computed(() => mobileItemsFor(props.user?.role || 'user'));

const isActive = (item) => isItemActive(item, page.url);
</script>

<style scoped>
.safe-area-padding {
    padding-bottom: env(safe-area-inset-bottom);
}
</style>