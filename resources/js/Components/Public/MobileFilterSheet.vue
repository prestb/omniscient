<!-- resources/js/Components/Public/MobileFilterSheet.vue -->
<!--
  PHASE 16F — MOBILE FILTER SHEET.

  This remains the single mobile filter surface (the brief forbids a second
  implementation), and the 16B `Sheet` remains the accessibility foundation —
  the dialog contract below mirrors it exactly rather than re-inventing it.

  The sheet previously had NO dialog semantics, NO Escape handling and NO focus
  management: `role` was absent, focus stayed behind the backdrop, and the only
  way out was the close button or backdrop tap. It now matches the 16B Sheet:

    role="dialog" + aria-modal + labelled title
    Escape to dismiss
    focus moved into the panel on open, restored to the trigger on close
    focus contained while open
    body scroll lock (guarded, so it is released exactly once)
    safe-area padding retained on the footer
    prefers-reduced-motion handled globally by app.css
-->
<template>
    <Teleport to="body">
        <!-- Backdrop (decorative; dismissal is also available via Escape/Close) -->
        <Transition
            enter-active-class="transition-opacity duration-300 ease-out"
            enter-from-class="opacity-0"
            enter-to-class="opacity-100"
            leave-active-class="transition-opacity duration-200 ease-in"
            leave-from-class="opacity-100"
            leave-to-class="opacity-0">
            <div v-if="show"
                 class="lg:hidden fixed inset-0 z-[60] bg-black/50 backdrop-blur-sm"
                 aria-hidden="true"
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
                 ref="panel"
                 role="dialog"
                 aria-modal="true"
                 :aria-label="resolvedTitle"
                 tabindex="-1"
                 class="lg:hidden fixed bottom-0 left-0 right-0 z-[70] bg-surface dark:bg-gray-800 rounded-t-card shadow-elevation-3 flex flex-col max-h-[90vh] focus:outline-none">

                <!-- Drag handle -->
                <div class="flex-shrink-0 pt-3 pb-1 flex justify-center">
                    <div class="w-10 h-1 bg-gray-300 dark:bg-gray-600 rounded-pill" aria-hidden="true"></div>
                </div>

                <!-- Header -->
                <div class="flex-shrink-0 px-5 py-3 border-b border-hairline dark:border-hairline-dark flex items-center justify-between">
                    <h2 class="text-heading-md text-ink dark:text-white">
                        <slot name="title">{{ resolvedTitle }}</slot>
                    </h2>
                    <button type="button"
                            @click="close"
                            class="inline-flex h-11 w-11 -mr-2 items-center justify-center rounded-control text-ink-subtle hover:text-ink dark:hover:text-gray-100 hover:bg-gray-100 dark:hover:bg-gray-700 transition-colors duration-fast focus:outline-none focus-visible:ring-2 focus-visible:ring-primary-500"
                            aria-label="Close filters">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>

                <!-- Scrollable body -->
                <div class="flex-1 overflow-y-auto px-5 py-4">
                    <slot />
                </div>

                <!-- Footer actions -->
                <div class="flex-shrink-0 px-5 py-4 border-t border-hairline dark:border-hairline-dark bg-surface dark:bg-gray-800 flex items-center gap-3 safe-area-pb">
                    <button type="button"
                            @click="reset"
                            class="flex-shrink-0 min-h-11 px-4 rounded-control text-body font-semibold text-ink-muted dark:text-gray-300 hover:text-ink dark:hover:text-white transition-colors duration-fast focus:outline-none focus-visible:ring-2 focus-visible:ring-primary-500">
                        Clear all
                    </button>
                    <button type="button"
                            @click="apply"
                            class="flex-1 inline-flex min-h-11 items-center justify-center gap-2 px-5 rounded-control bg-primary-600 text-white font-semibold text-body hover:bg-primary-700 transition-colors duration-fast focus:outline-none focus-visible:ring-2 focus-visible:ring-primary-500 focus-visible:ring-offset-2">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
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
import { computed, nextTick, onBeforeUnmount, ref, watch } from 'vue';

const props = defineProps({
    show: {
        type: Boolean,
        default: false,
    },
    /** Accessible dialog name. Falls back to the visible default title. */
    title: {
        type: String,
        default: 'Filters',
    },
});

const emit = defineEmits(['close', 'apply', 'reset']);

const panel = ref(null);
const resolvedTitle = computed(() => props.title || 'Filters');

/** Last focused element, so focus can be returned to the trigger on close. */
let lastFocused = null;
let locked = false;

const close = () => emit('close');
const apply = () => emit('apply');
const reset = () => emit('reset');

const FOCUSABLE =
    'a[href], button:not([disabled]), textarea, input, select, [tabindex]:not([tabindex="-1"])';

const onKeydown = (e) => {
    // Escape dismisses, matching the 16B Sheet contract.
    if (e.key === 'Escape') {
        close();
        return;
    }

    // Contain focus while open so Tab cannot reach the page behind.
    if (e.key === 'Tab' && panel.value) {
        const focusables = panel.value.querySelectorAll(FOCUSABLE);
        if (focusables.length === 0) return;

        const first = focusables[0];
        const last = focusables[focusables.length - 1];

        if (e.shiftKey && document.activeElement === first) {
            e.preventDefault();
            last.focus();
        } else if (!e.shiftKey && document.activeElement === last) {
            e.preventDefault();
            first.focus();
        }
    }
};

watch(() => props.show, async (isOpen) => {
    if (typeof document === 'undefined') return;

    if (isOpen) {
        lastFocused = document.activeElement;
        // Lock only once, so the unlock cannot be double-applied.
        if (!locked) {
            document.body.style.overflow = 'hidden';
            locked = true;
        }
        document.addEventListener('keydown', onKeydown);

        await nextTick();
        const target = panel.value?.querySelector(FOCUSABLE);
        (target ?? panel.value)?.focus?.();
    } else {
        document.removeEventListener('keydown', onKeydown);
        if (locked) {
            document.body.style.overflow = '';
            locked = false;
        }
        lastFocused?.focus?.();
    }
});

onBeforeUnmount(() => {
    if (typeof document === 'undefined') return;
    document.removeEventListener('keydown', onKeydown);
    if (locked) {
        document.body.style.overflow = '';
        locked = false;
    }
});
</script>

<style scoped>
.safe-area-pb {
    padding-bottom: calc(1rem + env(safe-area-inset-bottom));
}
</style>
