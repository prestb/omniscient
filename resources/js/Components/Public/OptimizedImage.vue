<!-- resources/js/Components/Public/OptimizedImage.vue -->
<template>
    <picture v-if="rawPath">
        <source v-if="webpSrc" :srcset="webpSrc" type="image/webp" />
        <img :src="jpegSrc" :alt="alt" :class="imgClass" :loading="loading" :decoding="decoding" @error="handleError"
            @load="onLoad" />
    </picture>

    <div v-else :class="fallbackClass">
        <slot name="fallback">
            <span class="text-white font-bold">{{ initials }}</span>
        </slot>
    </div>
</template>

<script setup>
    import { ref, computed } from 'vue';

    const props = defineProps({
        path: { type: [String, Object], default: '' },
        size: { type: String, default: 'medium', validator: (v) => ['thumb', 'medium', 'large'].includes(v) },
        alt: { type: String, default: '' },
        name: { type: String, default: '' },
        imgClass: { type: String, default: '' },
        fallbackClass: { type: String, default: '' },
        loading: { type: String, default: 'lazy' },
        decoding: { type: String, default: 'async' },
    });

    const emit = defineEmits(['load', 'error']);

    const errored = ref(false);

    const rawPath = computed(() => {
        const p = props.path;
        if (!p) return '';
        if (typeof p === 'string') return p;
        if (typeof p === 'object' && typeof p.path === 'string') return p.path;
        return '';
    });

    const base = computed(() => {
        if (!rawPath.value) return '';
        return rawPath.value.replace(/\.[^/.]+$/, '');
    });

    const jpegSrc = computed(() => {
        if (!rawPath.value) return '';
        if (errored.value) return `/storage/${rawPath.value}`;
        return `/storage/${base.value}_${props.size}.jpg`;
    });

    const webpSrc = computed(() => {
        if (!rawPath.value || errored.value) return '';
        return `/storage/${base.value}_${props.size}.webp`;
    });

    const initials = computed(() => {
        if (!props.name) return '?';
        return props.name
            .split(' ')
            .map((n) => n[0])
            .join('')
            .toUpperCase()
            .slice(0, 2);
    });

    const onLoad = () => {
        emit('load');
    };

    const handleError = () => {
        errored.value = !errored.value;
        emit('error');
    };
</script>