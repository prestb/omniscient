<!-- resources/js/Components/Toast/ToastItem.vue -->
<template>
    <div
        class="relative rounded-xl shadow-lg overflow-hidden transition-all duration-300"
        :class="[
            toastClasses,
            'transform',
            isVisible ? 'translate-x-0 opacity-100' : 'translate-x-full opacity-0'
        ]"
        role="alert"
        @mouseenter="handleMouseEnter"
        @mouseleave="handleMouseLeave"
    >
        <!-- Progress Bar -->
        <div
            class="absolute bottom-0 left-0 h-0.5 sm:h-1 transition-all duration-300"
            :class="progressBarClass"
            :style="{ width: toast.progress + '%' }"
        ></div>

        <!-- Inner content: tighter on mobile, roomier on desktop -->
        <div class="p-3 pr-10 sm:p-4 sm:pr-12">
            <!-- Icon and Content -->
            <div class="flex items-start gap-2.5 sm:gap-3">
                <!-- Icon: smaller on mobile -->
                <div class="flex-shrink-0 mt-0.5 text-base sm:text-xl leading-none">
                    {{ icon }}
                </div>

                <!-- Content -->
                <div class="flex-1 min-w-0">
                    <h4 class="text-sm font-semibold leading-snug" :class="titleClass">
                        {{ toast.title }}
                    </h4>
                    <p class="text-xs sm:text-sm mt-0.5 leading-snug" :class="messageClass">
                        {{ toast.message }}
                    </p>

                    <!-- Actions -->
                    <div v-if="toast.actions && toast.actions.length > 0" class="mt-2 flex flex-wrap gap-2">
                        <button
                            v-for="(action, index) in toast.actions"
                            :key="index"
                            @click="handleAction(action)"
                            class="px-2.5 py-1 text-[11px] sm:text-xs font-medium rounded-lg transition-colors"
                            :class="actionButtonClass"
                        >
                            {{ action.label }}
                        </button>
                    </div>
                </div>
            </div>

            <!-- Dismiss Button: larger touch target on mobile -->
            <button
                v-if="toast.dismissible !== false"
                @click="emit('dismiss')"
                class="absolute top-2 right-2 sm:top-3 sm:right-3 p-1.5 sm:p-0 text-gray-400 hover:text-gray-600 transition-colors rounded-lg hover:bg-black/5"
                aria-label="Dismiss notification"
            >
                <svg class="w-3.5 h-3.5 sm:w-4 sm:h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                </svg>
            </button>
        </div>
    </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue';

const props = defineProps({
    toast: {
        type: Object,
        required: true,
    },
});

const emit = defineEmits(['dismiss', 'pause', 'resume']);

const isVisible = ref(false);

// Icon mapping
const iconMap = {
    success: '✅',
    error: '❌',
    warning: '⚠️',
    info: 'ℹ️',
};

const icon = computed(() => iconMap[props.toast.type] || '📢');

// Color classes
const toastClasses = computed(() => {
    const classes = {
        success: 'bg-green-50 border border-green-300 dark:bg-green-900/30 dark:border-green-700',
        error: 'bg-red-50 border border-red-300 dark:bg-red-900/30 dark:border-red-700',
        warning: 'bg-yellow-50 border border-yellow-300 dark:bg-yellow-900/30 dark:border-yellow-700',
        info: 'bg-blue-50 border border-blue-300 dark:bg-blue-900/30 dark:border-blue-700',
    };
    return classes[props.toast.type] || classes.info;
});

const titleClass = computed(() => {
    const classes = {
        success: 'text-green-800 dark:text-green-300',
        error: 'text-red-800 dark:text-red-300',
        warning: 'text-yellow-800 dark:text-yellow-300',
        info: 'text-blue-800 dark:text-blue-300',
    };
    return classes[props.toast.type] || classes.info;
});

const messageClass = computed(() => {
    const classes = {
        success: 'text-green-700 dark:text-green-400',
        error: 'text-red-700 dark:text-red-400',
        warning: 'text-yellow-700 dark:text-yellow-400',
        info: 'text-blue-700 dark:text-blue-400',
    };
    return classes[props.toast.type] || classes.info;
});

const progressBarClass = computed(() => {
    const classes = {
        success: 'bg-green-500 dark:bg-green-400',
        error: 'bg-red-500 dark:bg-red-400',
        warning: 'bg-yellow-500 dark:bg-yellow-400',
        info: 'bg-blue-500 dark:bg-blue-400',
    };
    return classes[props.toast.type] || classes.info;
});

const actionButtonClass = computed(() => {
    const classes = {
        success: 'bg-green-100 text-green-800 hover:bg-green-200 dark:bg-green-800/50 dark:text-green-300 dark:hover:bg-green-700/50',
        error: 'bg-red-100 text-red-800 hover:bg-red-200 dark:bg-red-800/50 dark:text-red-300 dark:hover:bg-red-700/50',
        warning: 'bg-yellow-100 text-yellow-800 hover:bg-yellow-200 dark:bg-yellow-800/50 dark:text-yellow-300 dark:hover:bg-yellow-700/50',
        info: 'bg-blue-100 text-blue-800 hover:bg-blue-200 dark:bg-blue-800/50 dark:text-blue-300 dark:hover:bg-blue-700/50',
    };
    return classes[props.toast.type] || classes.info;
});

// Lifecycle
onMounted(() => {
    setTimeout(() => {
        isVisible.value = true;
    }, 50);
});

// Methods
const handleMouseEnter = () => {
    emit('pause');
};

const handleMouseLeave = () => {
    emit('resume');
};

const handleAction = (action) => {
    if (action.onClick) {
        action.onClick();
    }
    if (action.dismiss !== false) {
        emit('dismiss');
    }
};
</script>