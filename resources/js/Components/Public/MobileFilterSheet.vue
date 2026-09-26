<!-- resources/js/Components/Public/MobileFilterSheet.vue -->
<template>
    <Teleport to="body">
        <!-- Backdrop -->
        <Transition
            enter-active-class="transition-opacity duration-300 ease-out"
            enter-from-class="opacity-0"
            enter-to-class="opacity-100"
            leave-active-class="transition-opacity duration-200 ease-in"
            leave-from-class="opacity-100"
            leave-to-class="opacity-0">
            <div v-if="show"
                 class="lg:hidden fixed inset-0 z-[60] bg-black/50 backdrop-blur-sm"
                 @click="close" />
        </Transition>

        <!-- Bottom sheet -->
        <Transition
            enter-active-class="transition-transform duration-300 ease-out"
            enter-from-class="translate-y-full"
            enter-to-class="translate-y-0"
            leave-active-class="transition-transform duration-200 ease-in"
            leave-from-class="translate-y-0"
            leave-to-class="translate-y-full">
            <div v-if="show"
                 class="lg:hidden fixed bottom-0 left-0 right-0 z-[70] bg-white dark:bg-gray-800 rounded-t-3xl shadow-2xl flex flex-col max-h-[90vh]">

                <!-- Drag handle -->
                <div class="flex-shrink-0 pt-3 pb-1 flex justify-center">
                    <div class="w-10 h-1 bg-gray-300 dark:bg-gray-600 rounded-full"></div>
                </div>

                <!-- Header -->
                <div class="flex-shrink-0 px-5 py-3 border-b border-gray-100 dark:border-gray-700 flex items-center justify-between">
                    <h3 class="text-base font-bold text-gray-900 dark:text-white tracking-tight">
                        <slot name="title">Filters</slot>
                    </h3>
                    <button @click="close"
                            class="p-2 -mr-2 rounded-lg text-gray-400 hover:text-gray-600 dark:hover:text-gray-200 hover:bg-gray-100 dark:hover:bg-gray-700 transition-colors"
                            aria-label="Close">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>

                <!-- Scrollable body -->
                <div class="flex-1 overflow-y-auto px-5 py-4">
                    <slot />
                </div>

                <!-- Footer actions -->
                <div class="flex-shrink-0 px-5 py-4 border-t border-gray-100 dark:border-gray-700 bg-white dark:bg-gray-800 flex items-center gap-3 safe-area-pb">
                    <button type="button"
                            @click="reset"
                            class="flex-shrink-0 px-4 py-3 text-sm font-semibold text-gray-600 dark:text-gray-300 hover:text-gray-900 dark:hover:text-white transition-colors">
                        Clear all
                    </button>
                    <button type="button"
                            @click="apply"
                            class="flex-1 inline-flex items-center justify-center gap-2 px-5 py-3 bg-gradient-to-r from-primary-600 to-primary-700 text-white rounded-xl font-semibold text-sm hover:from-primary-700 hover:to-primary-800 transition-all shadow-lg shadow-primary-500/30">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2.586a1 1 0 01-.293.707l-6.414 6.414a1 1 0 00-.293.707V17l-4 4v-6.586a1 1 0 00-.293-.707L3.293 7.293A1 1 0 013 6.586V4z" />
                        </svg>
                        Apply Filters
                    </button>
                </div>
            </div>
        </Transition>
    </Teleport>
</template>

<script setup>
import { watch } from 'vue';

const props = defineProps({
    show: {
        type: Boolean,
        default: false,
    },
});

const emit = defineEmits(['close', 'apply', 'reset']);

const close = () => emit('close');
const apply = () => emit('apply');
const reset = () => emit('reset');

// Lock body scroll while sheet is open
watch(() => props.show, (isOpen) => {
    if (typeof document !== 'undefined') {
        document.body.style.overflow = isOpen ? 'hidden' : '';
    }
});
</script>

<style scoped>
.safe-area-pb {
    padding-bottom: calc(1rem + env(safe-area-inset-bottom));
}
</style>