<!-- resources/js/Components/NavLink.vue -->
<template>
    <Link :href="href" :class="classes">
        <slot />
    </Link>
</template>

<script setup>
import { Link } from '@inertiajs/vue3';
import { computed } from 'vue';

const props = defineProps({
    href: {
        type: String,
        required: true,
    },
    active: {
        type: Boolean,
        default: false,
    },
});

const classes = computed(() => {
    return props.active
        ? 'px-3 py-2 text-sm font-semibold rounded-xl bg-primary-50 text-primary-700 dark:bg-primary-900/30 dark:text-primary-300'
        : 'px-3 py-2 text-sm font-semibold rounded-xl text-gray-600 hover:bg-gray-100 hover:text-gray-900 dark:text-gray-400 dark:hover:bg-gray-800 dark:hover:text-white transition-colors';
});
</script>