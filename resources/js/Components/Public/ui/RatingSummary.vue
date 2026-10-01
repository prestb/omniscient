<!-- resources/js/Components/Public/ui/RatingSummary.vue -->
<!--
  PHASE 16C — RATING CONSOLIDATION.

  Replaces RatingBadge + RatingDisplay + StarRating.

  OWNERSHIP (established Phase 11, preserved through 14/15): reviews are
  BUSINESS-owned. A Listing displays its owning organization's aggregate. This
  component therefore NEVER presents the number as the Listing's own rating —
  the accessible name always attributes it, and `attribution` controls whether
  the visible "Business rating" label is shown.

  No review data, route or query is touched by this consolidation.
-->
<script setup>
import { computed } from 'vue';

const props = defineProps({
    rating: { type: [Number, String], required: true },
    reviewCount: { type: [Number, String], default: null },
    /** 'default' = stars + number; 'compact' = number only (dense cards). */
    mode: {
        type: String,
        default: 'default',
        validator: (v) => ['default', 'compact'].includes(v),
    },
    size: {
        type: String,
        default: 'md',
        validator: (v) => ['sm', 'md'].includes(v),
    },
    /**
     * Show the visible "Business rating" label. On a Listing page this must be
     * true so a visitor cannot mistake the aggregate for a Listing-owned score.
     */
    attribution: { type: Boolean, default: false },
});

const value = computed(() => {
    const n = Number(props.rating);
    return Number.isFinite(n) ? Math.max(0, Math.min(5, n)) : 0;
});

const count = computed(() => {
    const n = Number(props.reviewCount);
    return Number.isFinite(n) && n >= 0 ? Math.floor(n) : null;
});

const hasRating = computed(() => value.value > 0);

const filled = computed(() => Math.round(value.value));

/**
 * The ONLY rating text a screen reader receives. Stars alone are not
 * accessible, and the ownership attribution is part of the name.
 */
const accessibleLabel = computed(() => {
    if (!hasRating.value) {
        return 'No business reviews yet';
    }

    const stars = value.value.toFixed(1);
    const reviews = count.value === null
        ? ''
        : ` from ${count.value} review${count.value === 1 ? '' : 's'}`;

    return `Business rating: ${stars} out of 5${reviews}`;
});

const starSize = computed(() => (props.size === 'sm' ? 'h-3 w-3' : 'h-3.5 w-3.5'));
const textSize = computed(() => (props.size === 'sm' ? 'text-caption' : 'text-body-sm'));
</script>

<template>
    <span
        v-if="hasRating"
        class="inline-flex items-center gap-1.5"
        role="img"
        :aria-label="accessibleLabel"
    >
        <span v-if="mode === 'default'" class="flex items-center gap-0.5" aria-hidden="true">
            <svg
                v-for="i in 5"
                :key="i"
                :class="[starSize, i <= filled ? 'text-amber-400' : 'text-gray-200 dark:text-gray-600']"
                fill="currentColor"
                viewBox="0 0 24 24"
            >
                <path d="M11.049 2.927c.3-.921 1.603-.921 1.902 0l1.519 4.674a1 1 0 00.95.69h4.915c.969 0 1.371 1.24.588 1.81l-3.976 2.888a1 1 0 00-.363 1.118l1.518 4.674c.3.922-.755 1.688-1.538 1.118l-3.976-2.888a1 1 0 00-1.176 0l-3.976 2.888c-.783.57-1.838-.197-1.538-1.118l1.518-4.674a1 1 0 00-.363-1.118l-3.976-2.888c-.784-.57-.38-1.81.588-1.81h4.914a1 1 0 00.951-.69l1.519-4.674z" />
            </svg>
        </span>

        <span :class="['font-semibold text-ink dark:text-gray-100', textSize]" aria-hidden="true">
            {{ value.toFixed(1) }}
        </span>

        <span v-if="count !== null" :class="['text-ink-muted dark:text-gray-400', textSize]" aria-hidden="true">
            ({{ count }})
        </span>

        <span
            v-if="attribution"
            :class="['text-ink-subtle dark:text-gray-500', textSize]"
            aria-hidden="true"
        >
            Business rating
        </span>
    </span>

    <span
        v-else
        :class="['text-ink-subtle dark:text-gray-500', textSize]"
    >
        No reviews yet
    </span>
</template>
