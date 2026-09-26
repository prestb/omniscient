<!-- resources/js/Components/DropdownLink.vue -->
<template>
    <Link
        v-if="!asButton"
        :href="href"
        class="flex items-center gap-2 px-4 py-2.5 text-sm text-gray-700 hover:bg-gray-50 transition-colors"
    >
        <slot />
    </Link>
    <button
        v-else
        type="submit"
        class="flex items-center gap-2 w-full text-left px-4 py-2.5 text-sm text-gray-700 hover:bg-gray-50 transition-colors"
        @click="$emit('click')"
    >
        <slot />
    </button>
</template>

<script setup>
import { Link } from '@inertiajs/vue3';

const props = defineProps({
    href: {
        type: String,
        default: '#',
    },
    asButton: {
        type: Boolean,
        default: false,
    },
    method: {
        type: String,
        default: 'get',
    },
});
</script>