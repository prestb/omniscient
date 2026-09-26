<!-- resources/js/Components/Public/ReviewImageGallery.vue -->
<template>
    <div>
        <!-- Thumbnails grid -->
        <div class="flex flex-wrap gap-2">
            <button v-for="(image, index) in images" :key="index" type="button" @click="open(index)"
                :class="thumbnailClass"
                class="relative rounded-xl overflow-hidden border border-gray-200 dark:border-gray-600 hover:shadow-md transition-shadow cursor-pointer group/photo">
                <OptimizedImage :path="image" :alt="`${alt} ${index + 1}`" size="thumb"
                    img-class="w-full h-full object-cover group-hover/photo:scale-110 transition-transform duration-300"
                    fallback-class="w-full h-full flex items-center justify-center bg-gray-100 dark:bg-gray-700" />
            </button>
        </div>

        <!-- Lightbox -->
        <Teleport to="body">
            <Transition enter-active-class="transition-opacity duration-200 ease-out" enter-from-class="opacity-0"
                enter-to-class="opacity-100" leave-active-class="transition-opacity duration-150 ease-in"
                leave-from-class="opacity-100" leave-to-class="opacity-0">
                <div v-if="isOpen"
                    class="fixed inset-0 z-[100] flex items-center justify-center bg-black/95 backdrop-blur-sm p-4"
                    @click.self="close">
                    <!-- Close button -->
                    <button @click="close"
                        class="absolute top-4 right-4 p-2 rounded-xl text-white/70 hover:text-white hover:bg-white/10 transition-colors z-10">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>

                    <!-- Prev button -->
                    <button v-if="images.length > 1" @click.stop="prev"
                        class="absolute left-2 md:left-6 top-1/2 -translate-y-1/2 p-3 rounded-full bg-white/10 hover:bg-white/20 text-white transition-colors z-10">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
                        </svg>
                    </button>

                    <!-- Image -->
                    <div class="relative max-w-5xl max-h-full w-full h-full flex items-center justify-center">
                        <div class="max-w-full max-h-[85vh]" @click.stop>
                            <OptimizedImage :path="images[currentIndex]" :alt="`${alt} ${currentIndex + 1}`"
                                size="large" loading="eager"
                                img-class="max-w-full max-h-[85vh] object-contain rounded-lg shadow-2xl"
                                fallback-class="w-full h-64 flex items-center justify-center" />
                        </div>
                    </div>

                    <!-- Next button -->
                    <button v-if="images.length > 1" @click.stop="next"
                        class="absolute right-2 md:right-6 top-1/2 -translate-y-1/2 p-3 rounded-full bg-white/10 hover:bg-white/20 text-white transition-colors z-10">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                        </svg>
                    </button>

                    <!-- Index indicator -->
                    <div v-if="images.length > 1"
                        class="absolute bottom-4 left-1/2 -translate-x-1/2 px-4 py-1.5 bg-black/60 backdrop-blur-sm text-white text-sm font-medium rounded-full">
                        {{ currentIndex + 1 }} / {{ images.length }}
                    </div>
                </div>
            </Transition>
        </Teleport>
    </div>
</template>

<script setup>
    import { ref, computed, onMounted, onUnmounted, watch } from 'vue';
    import OptimizedImage from './OptimizedImage.vue';


    const props = defineProps({
        images: {
            type: Array,
            default: () => [],
        },
        alt: {
            type: String,
            default: 'Review image',
        },
        size: {
            type: String,
            default: 'md',
            validator: (v) => ['sm', 'md', 'lg'].includes(v),
        },
    });

    const isOpen = ref(false);
    const currentIndex = ref(0);

    const thumbnailClass = computed(() => {
        const sizes = {
            sm: 'w-16 h-16',
            md: 'w-20 h-20',
            lg: 'w-24 h-24',
        };
        return sizes[props.size] || sizes.md;
    });

    const open = (index) => {
        currentIndex.value = index;
        isOpen.value = true;
        document.body.style.overflow = 'hidden';
    };

    const close = () => {
        isOpen.value = false;
        document.body.style.overflow = '';
    };

    const next = () => {
        if (props.images.length < 2) return;
        currentIndex.value = (currentIndex.value + 1) % props.images.length;
    };

    const prev = () => {
        if (props.images.length < 2) return;
        currentIndex.value = (currentIndex.value - 1 + props.images.length) % props.images.length;
    };

    const handleKeydown = (e) => {
        if (!isOpen.value) return;
        if (e.key === 'Escape') close();
        if (e.key === 'ArrowRight') next();
        if (e.key === 'ArrowLeft') prev();
    };

    onMounted(() => {
        document.addEventListener('keydown', handleKeydown);
    });

    onUnmounted(() => {
        document.removeEventListener('keydown', handleKeydown);
        document.body.style.overflow = '';
    });

    // Safety: close if images array changes/empties
    watch(() => props.images, (newVal) => {
        if (!newVal || newVal.length === 0) close();
    }, { deep: true });
</script>