<!-- resources/js/Components/Common/SwipeableListItem.vue -->
<template>
    <!-- Non-touch devices: render children directly, zero wrapper overhead -->
    <template v-if="!isTouch">
        <slot />
    </template>

    <!-- Touch devices: swipe-enabled wrapper -->
    <div
        v-else
        ref="rootEl"
        class="relative overflow-hidden rounded-2xl touch-pan-y select-none"
        @touchstart.passive="onTouchStart"
        @touchmove="onTouchMove"
        @touchend="onTouchEnd"
        @touchcancel="onTouchEnd"
    >
        <!-- Delete action behind the card -->
        <div
            class="absolute inset-y-0 right-0 flex items-stretch"
            :style="{ width: revealWidth + 'px' }"
            aria-hidden="true"
        >
            <button
                type="button"
                @click.stop="onDeleteTap"
                class="flex-1 flex flex-col items-center justify-center gap-1 bg-gradient-to-br from-red-500 to-red-600 text-white font-bold text-xs uppercase tracking-wider active:from-red-600 active:to-red-700 transition-colors"
                :aria-label="`Delete ${label}`"
            >
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                          d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                </svg>
                <span>Delete</span>
            </button>
        </div>

        <!-- Foreground: user's card -->
        <div
            class="relative will-change-transform"
            :style="foregroundStyle"
            @transitionend="onTransitionEnd"
        >
            <slot />
        </div>
    </div>
</template>

<script setup>
import { ref, computed, onMounted, onBeforeUnmount } from 'vue';

const props = defineProps({
    // The item being swiped — used only for the aria-label text
    item: { type: [Object, null], default: null },
    // Human label for the delete button aria — e.g. "business", "branch", "coupon"
    label: { type: String, default: 'item' },
    // How far the delete button reveals (px)
    revealWidth: { type: Number, default: 88 },
    // How far you must drag before the button "sticks" (px)
    threshold: { type: Number, default: 60 },
});

const emit = defineEmits(['delete']);

// ============== TOUCH DETECTION ==============
const isTouch = ref(false);

let mql = null;
const handleMediaChange = (e) => {
    isTouch.value = e.matches;
};

onMounted(() => {
    if (typeof window === 'undefined' || !window.matchMedia) return;
    mql = window.matchMedia('(hover: none) and (pointer: coarse)');
    isTouch.value = mql.matches;
    // Safari < 14 fallback
    if (mql.addEventListener) {
        mql.addEventListener('change', handleMediaChange);
    } else if (mql.addListener) {
        mql.addListener(handleMediaChange);
    }
});

onBeforeUnmount(() => {
    if (!mql) return;
    if (mql.removeEventListener) {
        mql.removeEventListener('change', handleMediaChange);
    } else if (mql.removeListener) {
        mql.removeListener(handleMediaChange);
    }
});

// ============== GESTURE STATE ==============
const rootEl = ref(null);
const offset = ref(0);           // current translateX (negative = swiped left)
const isDragging = ref(false);   // actively tracking a touch
const isRevealed = ref(false);   // button stuck open
const hasTransition = ref(true); // disable transition while dragging

let startX = 0;
let startY = 0;
let startOffset = 0;
let axisLocked = null;           // 'x' | 'y' | null

// ============== STYLES ==============
const foregroundStyle = computed(() => ({
    transform: `translate3d(${offset.value}px, 0, 0)`,
    transition: hasTransition.value
        ? 'transform 220ms cubic-bezier(0.22, 1, 0.36, 1)'
        : 'none',
}));

// ============== TOUCH HANDLERS ==============
const onTouchStart = (e) => {
    if (!e.touches || e.touches.length !== 1) return;
    const t = e.touches[0];
    startX = t.clientX;
    startY = t.clientY;
    startOffset = offset.value;
    isDragging.value = true;
    axisLocked = null;
    hasTransition.value = false;
};

const onTouchMove = (e) => {
    if (!isDragging.value || !e.touches || e.touches.length !== 1) return;
    const t = e.touches[0];
    const dx = t.clientX - startX;
    const dy = t.clientY - startY;

    // Lock axis on first meaningful move — prevents fighting vertical scroll
    if (axisLocked === null) {
        if (Math.abs(dx) < 8 && Math.abs(dy) < 8) return;
        axisLocked = Math.abs(dx) > Math.abs(dy) ? 'x' : 'y';
    }

    if (axisLocked === 'y') return; // let the page scroll

    // Only allow leftward drag (or drag back to close if already revealed)
    let next = startOffset + dx;
    if (next > 0) next = 0;                             // can't pull right past rest
    if (next < -props.revealWidth) {
        // rubber-band past the reveal
        const over = Math.abs(next) - props.revealWidth;
        next = -(props.revealWidth + over * 0.35);
    }

    offset.value = next;

    // preventDefault to stop browser from doing anything sideways
    if (e.cancelable) e.preventDefault();
};

const onTouchEnd = () => {
    if (!isDragging.value) return;
    isDragging.value = false;
    hasTransition.value = true;

    if (axisLocked !== 'x') {
        // No horizontal gesture — snap back to current state
        offset.value = isRevealed.value ? -props.revealWidth : 0;
        return;
    }

    const swipedEnough = Math.abs(offset.value) >= props.threshold;

    if (swipedEnough) {
        isRevealed.value = true;
        offset.value = -props.revealWidth;
    } else {
        isRevealed.value = false;
        offset.value = 0;
    }
};

const onTransitionEnd = () => {
    // no-op — hook for future extension
};

// ============== TAP HANDLERS ==============
const onDeleteTap = () => {
    // Close the reveal, then emit — parent decides what to do (open ConfirmModal, etc.)
    isRevealed.value = false;
    offset.value = 0;
    emit('delete', props.item);
};

// Close if user taps the foreground while revealed
const onForegroundTap = () => {
    if (isRevealed.value) {
        isRevealed.value = false;
        offset.value = 0;
    }
};

// expose close so parent can force-close (e.g. after confirm)
const close = () => {
    isRevealed.value = false;
    offset.value = 0;
};
defineExpose({ close });
</script>