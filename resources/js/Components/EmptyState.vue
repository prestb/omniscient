<!-- resources/js/Components/EmptyState.vue -->
<template>
    <div class="flex flex-col items-center justify-center py-16 px-6 text-center">

        <!-- Icon -->
        <div v-if="icon" class="text-5xl mb-5">
            {{ icon }}
        </div>
        <div v-else class="w-20 h-20 rounded-2xl bg-gradient-to-br from-gray-100 to-gray-50 flex items-center justify-center mb-5 shadow-sm">
            <svg class="w-10 h-10 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.172 16.172a4 4 0 015.656 0M9 10h.01M15 10h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
            </svg>
        </div>

        <!-- Title -->
        <h3 class="text-xl font-bold text-gray-900 tracking-tight mb-2">
            {{ title }}
        </h3>

        <!-- Description -->
        <p class="text-gray-500 max-w-sm mb-6 leading-relaxed">
            {{ description }}
        </p>

        <!-- Action slot -->
        <slot name="action">
            <button v-if="actionText && actionUrl"
                    @click="handleAction"
                    class="inline-flex items-center gap-2 px-6 py-3 bg-gradient-to-r from-primary-600 to-primary-700 text-white rounded-xl font-semibold hover:from-primary-700 hover:to-primary-800 transition-all shadow-lg shadow-primary-500/25 hover:shadow-primary-500/40 hover:-translate-y-0.5 text-sm">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                </svg>
                {{ actionText }}
            </button>
        </slot>

        <!-- Additional content slot -->
        <slot name="content"></slot>
    </div>
</template>

<script setup>
import { router } from '@inertiajs/vue3';

const props = defineProps({
    title: {
        type: String,
        required: true,
    },
    description: {
        type: String,
        required: true,
    },
    icon: {
        type: String,
        default: null,
    },
    actionText: {
        type: String,
        default: null,
    },
    actionUrl: {
        type: String,
        default: null,
    },
    actionMethod: {
        type: String,
        default: 'get',
    },
});

const handleAction = () => {
    if (props.actionMethod === 'get') {
        router.visit(props.actionUrl);
    } else {
        router.post(props.actionUrl);
    }
};
</script>