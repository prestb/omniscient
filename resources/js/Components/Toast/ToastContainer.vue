<!-- resources/js/Components/Toast/ToastContainer.vue -->
<template>
    <Teleport to="body">
        <div
            class="fixed z-50 pointer-events-none"
            :class="positionClasses"
            role="region"
            aria-label="Notifications"
        >
            <div class="space-y-2 sm:space-y-3 pointer-events-auto">
                <ToastItem
                    v-for="toast in toasts"
                    :key="toast.id"
                    :toast="toast"
                    @dismiss="dismissToast(toast.id)"
                    @pause="pauseToast(toast.id)"
                    @resume="resumeToast(toast.id)"
                />
            </div>
        </div>
    </Teleport>
</template>

<script setup>
import { computed } from 'vue';
import { useToast } from '@/composables/useToast';
import ToastItem from './ToastItem.vue';

const { toasts, dismissToast, pauseToast, resumeToast } = useToast();

const positionClasses = computed(() => {
    // Mobile: below the mobile navbar (~56px), edges pinned with small margin
    // Desktop: top-right corner, capped width
    return [
        'top-16 sm:top-4',
        'left-2 right-2 sm:left-auto sm:right-4',
        'sm:max-w-sm',
    ].join(' ');
});
</script>