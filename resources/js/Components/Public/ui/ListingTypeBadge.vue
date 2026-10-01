<!-- resources/js/Components/Public/ui/ListingTypeBadge.vue -->
<!--
  PHASE 16C — LISTING TYPE PRESENTATION.

  Phase 16A found ListingType exists in the backend but is invisible to public
  users — the product could not say "this is a professional" vs "this is a
  store". This is a thin consumer of the 16B Badge primitive.

  Only the three types the enum actually defines are handled. An unknown or
  future value (Event, Job, …) fails GRACEFULLY: it renders nothing rather than
  inventing a label, so a future enum addition cannot produce misleading text.

  Type is never communicated by colour alone — the label text always carries it.
-->
<script setup>
import { computed } from 'vue';
import Badge from './Badge.vue';

const props = defineProps({
    type: { type: [String, Object], default: null },
    size: {
        type: String,
        default: 'sm',
        validator: (v) => ['sm', 'md'].includes(v),
    },
});

/**
 * Maps the ListingType enum value to a presentation label.
 * Deliberately exhaustive over the CURRENT enum only.
 */
const TYPES = {
    business: { label: 'Business', variant: 'primary' },
    professional: { label: 'Professional', variant: 'info' },
    store: { label: 'Store', variant: 'success' },
};

const normalised = computed(() => {
    const t = props.type;

    if (!t) return null;

    // ListingType may arrive as a PHP enum object ({ value: 'business' }) or
    // as a plain string, depending on the resource.
    const raw = typeof t === 'string' ? t : (t.value ?? String(t));

    return String(raw).toLowerCase();
});

/** Unknown values resolve to null and render nothing — never a guessed label. */
const config = computed(() => TYPES[normalised.value] ?? null);
</script>

<template>
    <Badge v-if="config" :variant="config.variant" :size="size">
        {{ config.label }}
    </Badge>
</template>
