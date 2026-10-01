<!-- resources/js/Components/Public/ui/Sheet.vue -->
<!--
  PHASE 16B — SHEET PRIMITIVE.

  Phase 16A identified MobileFilterSheet as the strongest existing mobile
  pattern but it was a one-off. This extracts the behaviour into a reusable
  primitive for filters, navigation, actions and contextual info.

  Behaviour, all of which the existing modals lack:
    - role="dialog" + aria-modal  (Phase 16A measured role= as ZERO repo-wide)
    - Escape to dismiss
    - body scroll lock while open
    - focus moved into the sheet on open, returned to the trigger on close
    - focus is contained while open
    - prefers-reduced-motion respected via the global CSS rule
    - renders on the client only (never during SSR)

  Mobile-first: bottom-anchored sheet below `md`, centred dialog above it.
-->
<script setup>
import { computed, nextTick, onBeforeUnmount, ref, watch } from 'vue';

const props = defineProps({
    show: { type: Boolean, default: false },
    title: { type: String, default: null },
    /** 'sheet' anchors to the bottom on mobile; 'dialog' centres. */
    variant: {
        type: String,
        default: 'sheet',
        validator: (v) => ['sheet', 'dialog'].includes(v),
    },
    dismissible: { type: Boolean, default: true },
});

const emit = defineEmits(['update:show', 'close']);

const panel = ref(null);
let lastFocused = null;

const close = () => {
    if (!props.dismissible) return;
    emit('update:show', false);
    emit('close');
};

const onKeydown = (e) => {
    if (e.key === 'Escape') {
        close();
        return;
    }

    // Focus containment: keep tabbing inside the open sheet.
    if (e.key === 'Tab' && panel.value) {
        const focusables = panel.value.querySelectorAll(
            'a[href], button:not([disabled]), textarea, input, select, [tabindex]:not([tabindex="-1"])'
        );
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

let locked = false;

watch(
    () => props.show,
    async (open) => {
        if (typeof document === 'undefined') return;

        if (open) {
            lastFocused = document.activeElement;
            document.body.style.overflow = 'hidden';
            locked = true;
            document.addEventListener('keydown', onKeydown);

            await nextTick();
            const target = panel.value?.querySelector(
                'a[href], button:not([disabled]), textarea, input, select, [tabindex]:not([tabindex="-1"])'
            );
            (target ?? panel.value)?.focus?.();
        } else {
            document.removeEventListener('keydown', onKeydown);
            if (locked) {
                document.body.style.overflow = '';
                locked = false;
            }
            lastFocused?.focus?.();
        }
    }
);

onBeforeUnmount(() => {
    if (typeof document === 'undefined') return;
    document.removeEventListener('keydown', onKeydown);
    if (locked) {
        document.body.style.overflow = '';
        locked = false;
    }
});

const wrapper = computed(() =>
    props.variant === 'dialog'
        ? 'items-center justify-center p-4'
        : 'items-end justify-center md:items-center md:p-4'
);

const panelClass = computed(() =>
    props.variant === 'dialog'
        ? 'w-full max-w-md rounded-card'
        : 'w-full md:max-w-md rounded-t-card md:rounded-card'
);
</script>

<template>
    <Teleport to="body">
        <div
            v-if="show"
            class="fixed inset-0 z-50 flex bg-black/60 backdrop-blur-sm"
            :class="wrapper"
            @click.self="close"
        >
            <div
                ref="panel"
                role="dialog"
                aria-modal="true"
                :aria-label="title || undefined"
                tabindex="-1"
                class="bg-surface dark:bg-gray-800 shadow-elevation-3 max-h-[90vh] overflow-y-auto focus:outline-none"
                :class="panelClass"
            >
                <div
                    v-if="title || dismissible"
                    class="flex items-center justify-between gap-4 border-b border-hairline dark:border-hairline-dark px-5 py-4"
                >
                    <h2 v-if="title" class="text-heading-md text-ink dark:text-gray-100">{{ title }}</h2>
                    <span v-else />
                    <button
                        v-if="dismissible"
                        type="button"
                        class="inline-flex h-9 w-9 items-center justify-center rounded-control text-gray-500 hover:bg-gray-100 dark:hover:bg-gray-700 focus:outline-none focus-visible:ring-2 focus-visible:ring-primary-500"
                        aria-label="Close"
                        @click="close"
                    >
                        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>

                <slot />
            </div>
        </div>
    </Teleport>
</template>
