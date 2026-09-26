<!-- resources/js/Components/Common/PullToRefresh.vue -->
<template>
    <div ref="rootEl" class="relative">
        <!-- Floating indicator (rendered only on touch devices) -->
        <Transition
            enter-active-class="transition duration-150 ease-out"
            enter-from-class="opacity-0"
            enter-to-class="opacity-100"
            leave-active-class="transition duration-200 ease-in"
            leave-from-class="opacity-100"
            leave-to-class="opacity-0"
        >
            <div
                v-if="isTouch && (isDragging || isRefreshing)"
                class="pointer-events-none fixed left-1/2 -translate-x-1/2 z-[150] transition-transform"
                :style="{ top: `${indicatorTop}px` }"
            >
                <div
                    class="flex items-center gap-2 px-4 py-2.5 rounded-full shadow-xl border backdrop-blur-md"
                    :class="indicatorClasses"
                >
                    <!-- Spinner icon (rotating when refreshing, static when armed, arrow when pulling) -->
                    <div
                        class="w-4 h-4 flex items-center justify-center transition-transform"
                        :style="{ transform: `rotate(${iconRotation}deg)` }"
                    >
                        <!-- Refreshing: spinning loader -->
                        <svg
                            v-if="isRefreshing"
                            class="w-4 h-4 animate-spin"
                            fill="none"
                            stroke="currentColor"
                            viewBox="0 0 24 24"
                        >
                            <path
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                stroke-width="2.5"
                                d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"
                            />
                        </svg>

                        <!-- Armed: checkmark that will spin into refresh -->
                        <svg
                            v-else-if="isArmed"
                            class="w-4 h-4"
                            fill="none"
                            stroke="currentColor"
                            viewBox="0 0 24 24"
                        >
                            <path
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                stroke-width="2.5"
                                d="M5 13l4 4L19 7"
                            />
                        </svg>

                        <!-- Pulling: down arrow -->
                        <svg
                            v-else
                            class="w-4 h-4"
                            fill="none"
                            stroke="currentColor"
                            viewBox="0 0 24 24"
                        >
                            <path
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                stroke-width="2.5"
                                d="M19 14l-7 7m0 0l-7-7m7 7V3"
                            />
                        </svg>
                    </div>

                    <span class="text-xs font-semibold whitespace-nowrap">
                        {{ indicatorLabel }}
                    </span>
                </div>
            </div>
        </Transition>

        <!-- Content -->
        <slot />
    </div>
</template>

<script setup>
import { ref, computed, onMounted, onBeforeUnmount } from 'vue';

const props = defineProps({
    // Distance in px the user must pull before the refresh fires on release
    threshold: { type: Number, default: 70 },
    // Max visual pull distance (rubber-band caps out at this)
    maxPull: { type: Number, default: 120 },
    // Resistance multiplier — smaller = harder to pull
    resistance: { type: Number, default: 0.5 },
    // Minimum time the spinner shows (avoids flash on fast reloads)
    minSpinnerMs: { type: Number, default: 400 },
});

const emit = defineEmits(['refresh']);

// ============== TOUCH DETECTION ==============
const isTouch = ref(false);

let mql = null;
const handleMediaChange = (e) => {
    isTouch.value = e.matches;
};

// ============== GESTURE STATE ==============
const rootEl = ref(null);
const isDragging = ref(false);
const isRefreshing = ref(false);
const pullDistance = ref(0);

let startY = 0;
let startX = 0;
let axisLocked = null; // 'x' | 'y' | null
let refreshStartedAt = 0;

// ============== COMPUTED ==============
const isArmed = computed(() => pullDistance.value >= props.threshold);

const indicatorTop = computed(() => {
    // 16px baseline, drifts downward with pull — clamps so it stays visible
    const base = 16;
    const offset = Math.min(pullDistance.value * 0.6, 60);
    return base + offset;
});

const iconRotation = computed(() => {
    if (isRefreshing.value) return 0; // spinner animates itself
    if (isArmed.value) return 0;
    // Arrow rotates as you pull (max 180°)
    return Math.min((pullDistance.value / props.threshold) * 180, 180);
});

const indicatorLabel = computed(() => {
    if (isRefreshing.value) return 'Refreshing…';
    if (isArmed.value) return 'Release to refresh';
    return 'Pull to refresh';
});

const indicatorClasses = computed(() => {
    if (isRefreshing.value) {
        return 'bg-white/90 dark:bg-gray-800/90 border-primary-200 dark:border-primary-800 text-primary-700 dark:text-primary-300';
    }
    if (isArmed.value) {
        return 'bg-white/95 dark:bg-gray-800/95 border-emerald-200 dark:border-emerald-800 text-emerald-700 dark:text-emerald-300';
    }
    return 'bg-white/80 dark:bg-gray-800/80 border-gray-200 dark:border-gray-600 text-gray-600 dark:text-gray-300';
});

// ============== LIFECYCLE ==============
onMounted(() => {
    if (typeof window === 'undefined' || !window.matchMedia) return;
    mql = window.matchMedia('(hover: none) and (pointer: coarse)');
    isTouch.value = mql.matches;
    if (mql.addEventListener) {
        mql.addEventListener('change', handleMediaChange);
    } else if (mql.addListener) {
        mql.addListener(handleMediaChange);
    }

    // Attach listeners on the root element (parent handles non-touch by not wiring)
    if (rootEl.value) {
        rootEl.value.addEventListener('touchstart', onTouchStart, { passive: true });
        rootEl.value.addEventListener('touchmove', onTouchMove, { passive: false });
        rootEl.value.addEventListener('touchend', onTouchEnd);
        rootEl.value.addEventListener('touchcancel', onTouchEnd);
    }
});

onBeforeUnmount(() => {
    if (mql) {
        if (mql.removeEventListener) {
            mql.removeEventListener('change', handleMediaChange);
        } else if (mql.removeListener) {
            mql.removeListener(handleMediaChange);
        }
    }
    if (rootEl.value) {
        rootEl.value.removeEventListener('touchstart', onTouchStart);
        rootEl.value.removeEventListener('touchmove', onTouchMove);
        rootEl.value.removeEventListener('touchend', onTouchEnd);
        rootEl.value.removeEventListener('touchcancel', onTouchEnd);
    }
});

// ============== TOUCH HANDLERS ==============
const onTouchStart = (e) => {
    // Only when the page is at the very top of the document
    if (window.scrollY > 0) return;
    if (isRefreshing.value) return;
    if (!e.touches || e.touches.length !== 1) return;

    const t = e.touches[0];
    startY = t.clientY;
    startX = t.clientX;
    axisLocked = null;
    isDragging.value = true;
};

const onTouchMove = (e) => {
    if (!isDragging.value || isRefreshing.value) return;
    if (!e.touches || e.touches.length !== 1) return;
    if (window.scrollY > 0) {
        // User scrolled mid-gesture — abandon
        isDragging.value = false;
        pullDistance.value = 0;
        return;
    }

    const t = e.touches[0];
    const dy = t.clientY - startY;
    const dx = t.clientX - startX;

    // Axis lock — give horizontal carousels priority
    if (axisLocked === null) {
        if (Math.abs(dy) < 8 && Math.abs(dx) < 8) return;
        axisLocked = Math.abs(dy) > Math.abs(dx) ? 'y' : 'x';
    }

    if (axisLocked === 'x') {
        // Horizontal gesture — release control back to native (carousel swipe)
        isDragging.value = false;
        pullDistance.value = 0;
        return;
    }

    // Only allow downward pull
    if (dy <= 0) {
        pullDistance.value = 0;
        return;
    }

    // Resistance curve — soft cap
    const damped = Math.min(dy * props.resistance, props.maxPull);
    pullDistance.value = damped;

    // Prevent native scroll/bounce while actively pulling down
    if (e.cancelable) e.preventDefault();
};

const onTouchEnd = () => {
    if (!isDragging.value) {
        // Reset if not in a real gesture
        pullDistance.value = 0;
        return;
    }

    isDragging.value = false;

    if (isArmed.value) {
        // Fire refresh — indicator switches to spinner state
        triggerRefresh();
    } else {
        // Snap back
        pullDistance.value = 0;
    }
};

// ============== REFRESH FLOW ==============
const triggerRefresh = () => {
    isRefreshing.value = true;
    refreshStartedAt = Date.now();
    emit('refresh');
};

/**
 * Parent MUST call this (via ref) when its reload finishes.
 * Ensures the spinner shows for at least `minSpinnerMs`.
 */
const done = () => {
    if (!isRefreshing.value) return;

    const elapsed = Date.now() - refreshStartedAt;
    const remaining = Math.max(0, props.minSpinnerMs - elapsed);

    setTimeout(() => {
        isRefreshing.value = false;
        pullDistance.value = 0;
    }, remaining);
};

defineExpose({ done });
</script>