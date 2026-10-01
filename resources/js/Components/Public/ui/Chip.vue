<!-- resources/js/Components/Public/ui/Chip.vue -->
<!--
  PHASE 16B — CHIP PRIMITIVE.

  For category, location, filter and refinement affordances. Presentation and
  interaction only: a Chip NEVER owns filtering logic. Phase 16A found
  LocationChips and FilterTags each re-implementing this shape.

  `selected` drives aria-pressed when interactive; a decorative chip stays a
  plain span so it is not announced as a control.
-->
<script setup>
import { computed } from 'vue';

const props = defineProps({
    selected: { type: Boolean, default: false },
    /** Interactive chips are buttons; passive chips are spans. */
    interactive: { type: Boolean, default: false },
    removable: { type: Boolean, default: false },
    disabled: { type: Boolean, default: false },
    size: {
        type: String,
        default: 'md',
        validator: (v) => ['sm', 'md'].includes(v),
    },
});

const emit = defineEmits(['remove']);

const sizes = {
    // min-h keeps the tap target usable at the compact size.
    sm: 'px-2.5 py-1 min-h-7 text-caption',
    md: 'px-3 py-1.5 min-h-9 text-body-sm',
};

const classes = computed(() => [
    'inline-flex items-center gap-1.5 rounded-pill font-medium border',
    'transition-colors duration-fast ease-standard',
    props.selected
        ? 'bg-primary-600 border-primary-600 text-white'
        : 'bg-surface dark:bg-gray-800 border-hairline dark:border-hairline-dark text-gray-700 dark:text-gray-300',
    props.interactive && !props.disabled
        ? props.selected
            ? 'hover:bg-primary-700'
            : 'hover:border-primary-400 hover:text-primary-700 dark:hover:text-primary-300'
        : '',
    props.interactive
        ? 'focus:outline-none focus-visible:ring-2 focus-visible:ring-primary-500 focus-visible:ring-offset-2 cursor-pointer'
        : '',
    props.disabled ? 'opacity-50 cursor-not-allowed' : '',
    sizes[props.size],
]);
</script>

<template>
    <component
        :is="interactive ? 'button' : 'span'"
        :type="interactive ? 'button' : undefined"
        :class="classes"
        :disabled="interactive ? disabled : undefined"
        :aria-pressed="interactive ? String(selected) : undefined"
    >
        <slot />

        <button
            v-if="removable"
            type="button"
            class="ml-0.5 -mr-1 inline-flex h-5 w-5 items-center justify-center rounded-pill hover:bg-black/10 dark:hover:bg-white/10 focus:outline-none focus-visible:ring-2 focus-visible:ring-white"
            :aria-label="`Remove`"
            @click.stop="emit('remove')"
        >
            <svg class="h-3 w-3" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
            </svg>
        </button>
    </component>
</template>
