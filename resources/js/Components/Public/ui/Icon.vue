<!-- resources/js/Components/Public/ui/Icon.vue -->
<!--
  PHASE 16B — ICON PRIMITIVE.

  Before this, every icon was a repeated inline <svg> with its own size,
  stroke-width and alignment. This primitive establishes one sizing scale,
  one stroke convention and correct decorative-vs-labelled semantics.

  Icons are supplied as a slot (the existing inline <svg> paths), so no icon
  dependency is introduced and existing assets keep working. Migration of icon
  consumers happens in later phases, not here.
-->
<script setup>
import { computed } from 'vue';

const props = defineProps({
    size: {
        type: String,
        default: 'md',
        validator: (v) => ['xs', 'sm', 'md', 'lg', 'xl'].includes(v),
    },
    /**
     * Accessible name. When omitted the icon is treated as DECORATIVE
     * (aria-hidden) — the correct default for an icon beside a text label.
     */
    label: { type: String, default: null },
});

const sizes = {
    xs: 'w-3 h-3',
    sm: 'w-3.5 h-3.5',
    md: 'w-4 h-4',
    lg: 'w-5 h-5',
    xl: 'w-6 h-6',
};

const classes = computed(() => [
    sizes[props.size],
    'shrink-0',
    // Icons align to the text baseline box rather than the line box, which is
    // the alignment bug the inline SVGs kept re-solving per component.
    'inline-block align-middle',
]);
</script>

<template>
    <span
        :class="classes"
        :aria-hidden="label ? undefined : 'true'"
        :role="label ? 'img' : undefined"
        :aria-label="label || undefined"
    >
        <slot />
    </span>
</template>
