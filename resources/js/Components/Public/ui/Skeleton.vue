<!-- resources/js/Components/Public/ui/Skeleton.vue -->
<!--
  PHASE 16C — SKELETON PRIMITIVE.

  The audit found four skeleton systems. This is the primitive they reduce to:
  a single accessible-neutral shimmer block. It deliberately does NOT contain
  full ListingCard markup — the previous Common/SkeletonLoader copied the card's
  structure, which meant every card change had to be mirrored in a loader.

  Accessibility: a loading placeholder carries no information, so the whole
  region is aria-hidden. Announcing placeholder boxes is worse than silence.
-->
<script setup>
import { computed } from 'vue';

const props = defineProps({
    /** Tailwind sizing classes for the block, e.g. "h-4 w-3/4". */
    shape: { type: String, default: 'h-4 w-full' },
    rounded: {
        type: String,
        default: 'control',
        validator: (v) => ['none', 'control', 'card', 'pill'].includes(v),
    },
});

const radius = {
    none: '',
    control: 'rounded-control',
    card: 'rounded-card',
    pill: 'rounded-pill',
};

const classes = computed(() => [
    props.shape,
    radius[props.rounded],
    'bg-gray-200 dark:bg-gray-700 animate-pulse',
]);
</script>

<template>
    <div :class="classes" aria-hidden="true" />
</template>
