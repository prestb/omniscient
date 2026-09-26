<!-- resources/js/Components/Dropdown.vue -->
<template>
    <div class="relative" @click.away="close">
        <div @click="toggle">
            <slot name="trigger" />
        </div>

        <div v-show="open" class="absolute z-50 mt-2 w-56 rounded-lg shadow-lg bg-white ring-1 ring-black ring-opacity-5" :class="alignmentClasses">
            <div class="py-1" role="menu">
                <slot name="content" />
            </div>
        </div>
    </div>
</template>

<script setup>
import { ref, computed } from 'vue';

const props = defineProps({
    align: {
        type: String,
        default: 'right',
    },
    width: {
        type: String,
        default: '48',
    },
});

const open = ref(false);

const alignmentClasses = computed(() => {
    if (props.align === 'right') {
        return 'origin-top-right right-0';
    } else if (props.align === 'left') {
        return 'origin-top-left left-0';
    } else {
        return 'origin-top-right right-0';
    }
});

const toggle = () => {
    open.value = !open.value;
};

const close = () => {
    open.value = false;
};
</script>