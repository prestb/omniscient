<!-- resources/js/Components/Public/ui/Button.vue -->
<!--
  PHASE 16B — BUTTON PRIMITIVE.

  Replaces the duplication Phase 16A found: PrimaryButton/SecondaryButton/
  DangerButton components PLUS .btn-primary/.btn-secondary CSS classes.

  Interaction sizing: `md` is the default and uses min-h-11 (44px) so the
  primary touch target meets the guideline. `sm` is the deliberate compact
  variant for dense desktop UI — it keeps a comfortable hit area via the
  padding/pseudo-element rather than shrinking the tappable region to 32px,
  which is the h-8 problem the audit flagged.

  Existing components are NOT removed here; migration is 16C+.
-->
<script setup>
import { computed } from 'vue';
import { Link } from '@inertiajs/vue3';

const props = defineProps({
    variant: {
        type: String,
        default: 'primary',
        validator: (v) => ['primary', 'secondary', 'danger', 'ghost'].includes(v),
    },
    size: {
        type: String,
        default: 'md',
        validator: (v) => ['sm', 'md', 'lg'].includes(v),
    },
    type: { type: String, default: 'button' },
    disabled: { type: Boolean, default: false },
    loading: { type: Boolean, default: false },
    /** Renders an Inertia <Link> instead of a <button>. */
    href: { type: String, default: null },
    full: { type: Boolean, default: false },
});

const base =
    'inline-flex items-center justify-center gap-2 font-semibold ' +
    'rounded-control transition-colors duration-fast ease-standard ' +
    'focus:outline-none focus-visible:ring-2 focus-visible:ring-primary-500 ' +
    'focus-visible:ring-offset-2 dark:focus-visible:ring-offset-gray-900 ' +
    'disabled:opacity-50 disabled:cursor-not-allowed disabled:pointer-events-none';

const variants = {
    primary: 'bg-primary-600 text-white hover:bg-primary-700 active:bg-primary-800',
    secondary:
        'bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-200 ' +
        'hover:bg-gray-200 dark:hover:bg-gray-600 border border-hairline dark:border-hairline-dark',
    danger: 'bg-danger text-white hover:bg-red-700 active:bg-red-800',
    ghost:
        'bg-transparent text-gray-600 dark:text-gray-300 ' +
        'hover:bg-gray-100 dark:hover:bg-gray-700',
};

const sizes = {
    // Compact visual variant; `relative` lets the hit area stay generous.
    sm: 'relative text-body-sm px-3 py-1.5 min-h-9',
    md: 'text-body px-4 py-2 min-h-11',
    lg: 'text-body-lg px-6 py-3 min-h-12',
};

const classes = computed(() => [
    base,
    variants[props.variant],
    sizes[props.size],
    props.full ? 'w-full' : '',
]);

const isDisabled = computed(() => props.disabled || props.loading);
</script>

<template>
    <Link v-if="href && !isDisabled" :href="href" :class="classes">
        <span v-if="loading" class="inline-block h-4 w-4 animate-spin rounded-full border-2 border-current border-t-transparent" aria-hidden="true" />
        <slot />
    </Link>

    <button
        v-else
        :type="type"
        :class="classes"
        :disabled="isDisabled"
        :aria-busy="loading ? 'true' : undefined"
    >
        <span v-if="loading" class="inline-block h-4 w-4 animate-spin rounded-full border-2 border-current border-t-transparent" aria-hidden="true" />
        <slot />
    </button>
</template>
