<!-- resources/js/Components/Public/ui/IconButton.vue -->
<!--
  PHASE 16B — ICON BUTTON PRIMITIVE.

  An icon-only control has no visible text, so an accessible name is MANDATORY.
  `label` is a required prop, which makes inaccessible usage a build-time error
  rather than something the audit has to find later.

  Default size is `md` (44px square) for a comfortable touch target.
-->
<script setup>
import { computed } from 'vue';

const props = defineProps({
    /** REQUIRED. Screen-reader name — never omit for an icon-only control. */
    label: { type: String, required: true },
    variant: {
        type: String,
        default: 'ghost',
        validator: (v) => ['ghost', 'solid', 'danger'].includes(v),
    },
    size: {
        type: String,
        default: 'md',
        validator: (v) => ['sm', 'md', 'lg'].includes(v),
    },
    type: { type: String, default: 'button' },
    disabled: { type: Boolean, default: false },
});

const variants = {
    ghost: 'text-gray-500 dark:text-gray-400 hover:bg-gray-100 dark:hover:bg-gray-700 hover:text-gray-700 dark:hover:text-gray-200',
    solid: 'bg-primary-600 text-white hover:bg-primary-700',
    danger: 'text-danger hover:bg-danger-soft',
};

const sizes = {
    sm: 'h-9 w-9',
    md: 'h-11 w-11',
    lg: 'h-12 w-12',
};

const classes = computed(() => [
    'inline-flex items-center justify-center rounded-control',
    'transition-colors duration-fast ease-standard',
    'focus:outline-none focus-visible:ring-2 focus-visible:ring-primary-500 focus-visible:ring-offset-2',
    variants[props.variant],
    sizes[props.size],
    'disabled:opacity-50 disabled:cursor-not-allowed',
]);
</script>

<template>
    <button :type="type" :class="classes" :disabled="disabled" :aria-label="label" :title="label">
        <slot />
    </button>
</template>
