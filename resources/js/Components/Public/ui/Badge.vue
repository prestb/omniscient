<!-- resources/js/Components/Public/ui/Badge.vue -->
<!--
  PHASE 16B — BADGE PRIMITIVE.

  Phase 16A found status/label pills written ad hoc across the public UI with
  no shared semantics. This is the foundation for status, category and
  informational labels.

  ListingTypeBadge is deliberately NOT created here — the Listing-type UX
  belongs to Phase 16E. This primitive is what it will consume.
-->
<script setup>
import { computed } from 'vue';

const props = defineProps({
    variant: {
        type: String,
        default: 'neutral',
        validator: (v) => ['neutral', 'primary', 'success', 'warning', 'danger', 'info'].includes(v),
    },
    size: {
        type: String,
        default: 'md',
        validator: (v) => ['sm', 'md'].includes(v),
    },
    /** Render as a real element other than <span> when required (e.g. "a"). */
    as: { type: String, default: 'span' },
});

const variants = {
    neutral: 'bg-gray-100 text-gray-700 dark:bg-gray-700 dark:text-gray-200',
    primary: 'bg-primary-50 text-primary-700 dark:bg-primary-900/30 dark:text-primary-300',
    success: 'bg-success-soft text-success dark:bg-emerald-900/30 dark:text-emerald-300',
    warning: 'bg-warning-soft text-warning dark:bg-amber-900/30 dark:text-amber-300',
    danger: 'bg-danger-soft text-danger dark:bg-red-900/30 dark:text-red-300',
    info: 'bg-info-soft text-info dark:bg-blue-900/30 dark:text-blue-300',
};

const sizes = {
    sm: 'px-2 py-0.5 text-[10px]',
    md: 'px-2.5 py-1 text-caption',
};

const classes = computed(() => [
    'inline-flex items-center gap-1 rounded-pill font-semibold uppercase tracking-wide',
    variants[props.variant],
    sizes[props.size],
]);
</script>

<template>
    <component :is="as" :class="classes">
        <slot />
    </component>
</template>
