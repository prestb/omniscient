<!-- resources/js/Components/Public/ui/Input.vue -->
<!--
  PHASE 16B — INPUT PRIMITIVE.

  Established for later phases; existing SearchBar/TextInput are deliberately
  NOT refactored here (scope discipline). This gives 16F a coherent control to
  build a SearchField on.

  `label` is required so an unlabelled control cannot be introduced, and the
  error message is wired through aria-describedby + aria-invalid rather than
  being decorative red text.
-->
<script setup>
import { computed } from 'vue';

const props = defineProps({
    /** REQUIRED — a control without a label is an accessibility defect. */
    label: { type: String, required: true },
    modelValue: { type: [String, Number], default: '' },
    type: { type: String, default: 'text' },
    placeholder: { type: String, default: '' },
    error: { type: String, default: null },
    hint: { type: String, default: null },
    disabled: { type: Boolean, default: false },
    /** Visually hides the label while keeping it for assistive tech. */
    hideLabel: { type: Boolean, default: false },
});

defineEmits(['update:modelValue']);

const uid = `i${Math.random().toString(36).slice(2, 9)}`;
const inputId = `input-${uid}`;
const errorId = `${inputId}-error`;
const hintId = `${inputId}-hint`;

const describedBy = computed(() => {
    const ids = [];
    if (props.error) ids.push(errorId);
    if (props.hint) ids.push(hintId);
    return ids.length ? ids.join(' ') : undefined;
});

const classes = computed(() => [
    'w-full min-h-11 px-3.5 py-2 rounded-control border bg-surface dark:bg-gray-900',
    'text-body text-ink dark:text-gray-100 placeholder:text-ink-subtle',
    'transition-colors duration-fast ease-standard',
    'focus:outline-none focus-visible:ring-2 focus-visible:ring-primary-500 focus-visible:border-transparent',
    props.error
        ? 'border-danger'
        : 'border-hairline dark:border-hairline-dark',
    props.disabled ? 'opacity-50 cursor-not-allowed' : '',
]);
</script>

<template>
    <div>
        <label
            :for="inputId"
            :class="hideLabel ? 'sr-only' : 'block text-label uppercase text-ink-muted dark:text-gray-400 mb-1.5'"
        >
            {{ label }}
        </label>

        <input
            :id="inputId"
            :type="type"
            :value="modelValue"
            :placeholder="placeholder"
            :disabled="disabled"
            :class="classes"
            :aria-invalid="error ? 'true' : undefined"
            :aria-describedby="describedBy"
            @input="$emit('update:modelValue', $event.target.value)"
        />

        <p v-if="hint && !error" :id="hintId" class="mt-1 text-caption text-ink-subtle">
            {{ hint }}
        </p>

        <p v-if="error" :id="errorId" class="mt-1 text-caption font-medium text-danger">
            {{ error }}
        </p>
    </div>
</template>
