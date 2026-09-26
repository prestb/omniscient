<!-- resources/js/Components/Owner/StickyFormActions.vue -->
<template>
    <Teleport to="body">
        <Transition
            enter-active-class="transition-transform duration-300 ease-out"
            enter-from-class="translate-y-full"
            enter-to-class="translate-y-0"
            leave-active-class="transition-transform duration-200 ease-in"
            leave-from-class="translate-y-0"
            leave-to-class="translate-y-full">
            <div v-if="show && dirty"
                 class="lg:hidden fixed left-0 right-0 z-30 bg-white dark:bg-gray-800 border-t border-gray-200 dark:border-gray-700 shadow-2xl shadow-gray-900/10"
                 style="bottom: calc(4rem + env(safe-area-inset-bottom));">
                <div class="px-4 py-3 flex items-center gap-3">

                    <!-- Unsaved indicator -->
                    <div class="flex items-center gap-2 flex-shrink-0">
                        <span class="w-2 h-2 rounded-full bg-amber-500 animate-pulse"></span>
                        <span class="text-[10px] font-bold uppercase tracking-widest text-gray-500 dark:text-gray-400">
                            Unsaved
                        </span>
                    </div>

                    <!-- Spacer -->
                    <div class="flex-1"></div>

                    <!-- Cancel -->
                    <button type="button"
                            @click="$emit('cancel')"
                            :disabled="processing"
                            class="flex-shrink-0 px-4 py-2.5 text-sm font-semibold text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700 rounded-xl transition-colors disabled:opacity-50">
                        Cancel
                    </button>

                    <!-- Save -->
                    <button type="button"
                            @click="$emit('save')"
                            :disabled="processing || !canSave"
                            class="flex-shrink-0 inline-flex items-center justify-center gap-2 px-6 py-2.5 bg-gradient-to-r from-primary-600 to-primary-700 text-white rounded-xl font-semibold text-sm hover:from-primary-700 hover:to-primary-800 transition-all shadow-lg shadow-primary-500/30 disabled:opacity-50 disabled:cursor-not-allowed">
                        <svg v-if="processing" class="animate-spin h-4 w-4" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                        </svg>
                        <svg v-else class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                        </svg>
                        {{ processing ? 'Saving…' : saveLabel }}
                    </button>
                </div>
            </div>
        </Transition>
    </Teleport>
</template>

<script setup>
const props = defineProps({
    /**
     * Controls whether the bar can render at all.
     * Typically: only when the mobile viewport is active AND form is ready.
     * Combine with `dirty` for the final visibility.
     */
    show: {
        type: Boolean,
        default: true,
    },
    /**
     * Whether the form has unsaved changes.
     * Typically: form.isDirty (Inertia useForm) or manually tracked.
     */
    dirty: {
        type: Boolean,
        default: false,
    },
    /**
     * Whether the save button should be enabled (e.g., required fields are filled).
     */
    canSave: {
        type: Boolean,
        default: true,
    },
    /**
     * Whether a save/cancel operation is in progress.
     */
    processing: {
        type: Boolean,
        default: false,
    },
    /**
     * Save button label.
     */
    saveLabel: {
        type: String,
        default: 'Save',
    },
});

defineEmits(['cancel', 'save']);
</script>