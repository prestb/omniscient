<!-- resources/js/Components/Owner/CouponScannerModal.vue -->
<template>
    <Teleport to="body">
        <Transition
            enter-active-class="transition duration-200 ease-out"
            enter-from-class="opacity-0"
            enter-to-class="opacity-100"
            leave-active-class="transition duration-150 ease-in"
            leave-from-class="opacity-100"
            leave-to-class="opacity-0"
        >
            <div v-if="isOpen"
                 class="fixed inset-0 z-[150] flex items-center justify-center p-4 bg-black/80 backdrop-blur-sm"
                 @click.self="close">
                <Transition
                    enter-active-class="transition duration-300 ease-out"
                    enter-from-class="opacity-0 scale-95 translate-y-4"
                    enter-to-class="opacity-100 scale-100 translate-y-0"
                    appear
                >
                    <div class="bg-white dark:bg-gray-800 rounded-3xl shadow-2xl max-w-md w-full overflow-hidden">

                        <!-- Header -->
                        <div class="px-6 py-5 border-b border-gray-100 dark:border-gray-700 flex items-center justify-between">
                            <div class="flex items-center gap-3 min-w-0">
                                <div class="w-10 h-10 rounded-xl bg-gradient-to-br from-primary-500 to-purple-600 flex items-center justify-center flex-shrink-0">
                                    <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                              d="M12 4v1m6 11h2m-6 0h-2v4m0-11v3m0 0h.01M12 12h4.01M16 20h4M4 12h4m12 0h.01M5 8h2a1 1 0 001-1V5a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1zm14 0h2a1 1 0 001-1V5a1 1 0 00-1-1h-2a1 1 0 00-1 1v2a1 1 0 001 1zM5 20h2a1 1 0 001-1v-2a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1z" />
                                    </svg>
                                </div>
                                <div class="min-w-0">
                                    <h3 class="text-base font-bold text-gray-900 dark:text-white tracking-tight truncate">
                                        Scan Coupon
                                    </h3>
                                    <p class="text-xs text-gray-500 dark:text-gray-400 truncate">
                                        Point the camera at the customer's QR code
                                    </p>
                                </div>
                            </div>
                            <button @click="close"
                                    class="w-8 h-8 flex items-center justify-center rounded-lg text-gray-400 hover:text-gray-700 dark:hover:text-gray-200 hover:bg-gray-100 dark:hover:bg-gray-700 transition-colors flex-shrink-0">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                          d="M6 18L18 6M6 6l12 12" />
                                </svg>
                            </button>
                        </div>

                        <!-- Body -->
                        <div class="p-6">
                            <!-- Permission / init errors -->
                            <div v-if="errorMessage"
                                 class="bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800 rounded-xl p-4 mb-4">
                                <div class="flex items-start gap-2">
                                    <svg class="w-5 h-5 text-red-600 dark:text-red-400 flex-shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                              d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                                    </svg>
                                    <div class="min-w-0">
                                        <p class="text-sm font-bold text-red-800 dark:text-red-300">
                                            {{ errorTitle || 'Camera error' }}
                                        </p>
                                        <p class="text-xs text-red-700 dark:text-red-400 mt-0.5 leading-relaxed">
                                            {{ errorMessage }}
                                        </p>
                                    </div>
                                </div>
                            </div>

                            <!-- Video viewport -->
                            <div class="relative rounded-2xl overflow-hidden bg-black aspect-square">
                                <video
                                    ref="videoEl"
                                    class="absolute inset-0 w-full h-full object-cover"
                                    playsinline
                                    muted
                                    autoplay
                                ></video>

                                <!-- Scanning overlay -->
                                <div v-if="isScanning && !errorMessage"
                                     class="absolute inset-0 pointer-events-none">
                                    <!-- Corner brackets -->
                                    <div class="absolute inset-0 flex items-center justify-center">
                                        <div class="relative w-56 h-56 sm:w-64 sm:h-64">
                                            <!-- Top-left -->
                                            <div class="absolute top-0 left-0 w-10 h-10 border-t-4 border-l-4 border-white/90 rounded-tl-lg"></div>
                                            <!-- Top-right -->
                                            <div class="absolute top-0 right-0 w-10 h-10 border-t-4 border-r-4 border-white/90 rounded-tr-lg"></div>
                                            <!-- Bottom-left -->
                                            <div class="absolute bottom-0 left-0 w-10 h-10 border-b-4 border-l-4 border-white/90 rounded-bl-lg"></div>
                                            <!-- Bottom-right -->
                                            <div class="absolute bottom-0 right-0 w-10 h-10 border-b-4 border-r-4 border-white/90 rounded-br-lg"></div>

                                            <!-- Scanning line animation -->
                                            <div class="absolute inset-x-0 top-0 h-0.5 bg-primary-500 shadow-[0_0_12px_rgba(59,130,246,0.8)] animate-scan"></div>
                                        </div>
                                    </div>
                                </div>

                                <!-- Loading state -->
                                <div v-if="isInitializing"
                                     class="absolute inset-0 bg-black/70 flex flex-col items-center justify-center text-white">
                                    <svg class="w-10 h-10 animate-spin" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                              d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                                    </svg>
                                    <p class="text-xs mt-3 opacity-80">Starting camera…</p>
                                </div>
                            </div>

                            <p class="text-[11px] text-gray-400 text-center mt-4 leading-relaxed">
                                Align the QR code inside the frame. It will be detected automatically.
                            </p>
                        </div>

                    </div>
                </Transition>
            </div>
        </Transition>
    </Teleport>
</template>

<script setup>
import { ref, watch, onBeforeUnmount, nextTick } from 'vue';
import { router } from '@inertiajs/vue3';
import jsQR from 'jsqr';

const props = defineProps({
    isOpen: {
        type: Boolean,
        default: false,
    },
});

const emit = defineEmits(['close']);

const videoEl = ref(null);
const isScanning = ref(false);
const isInitializing = ref(false);
const errorMessage = ref('');
const errorTitle = ref('');

let stream = null;
let animationFrameId = null;
let canvasEl = null;
let ctx = null;
let lastDecodeAttempt = 0;

const DECODE_INTERVAL_MS = 300; // Throttle decode attempts

// ============== LIFECYCLE ==============

watch(
    () => props.isOpen,
    async (open) => {
        if (open) {
            resetError();
            await nextTick();
            await startCamera();
        } else {
            stopCamera();
        }
    }
);

onBeforeUnmount(() => {
    stopCamera();
});

// ============== CAMERA ==============

const startCamera = async () => {
    errorMessage.value = '';
    errorTitle.value = '';
    isInitializing.value = true;

    // Check browser support
    if (!navigator.mediaDevices || !navigator.mediaDevices.getUserMedia) {
        isInitializing.value = false;
        errorTitle.value = 'Camera not available';
        errorMessage.value = 'Your browser does not support camera access, or you are not on HTTPS.';
        return;
    }

    try {
        stream = await navigator.mediaDevices.getUserMedia({
            video: {
                facingMode: { ideal: 'environment' }, // prefer rear camera
                width: { ideal: 1280 },
                height: { ideal: 720 },
            },
            audio: false,
        });

        if (!videoEl.value) {
            stopCamera();
            return;
        }

        videoEl.value.srcObject = stream;
        await videoEl.value.play();

        // Prepare hidden canvas for frame decoding
        canvasEl = document.createElement('canvas');
        ctx = canvasEl.getContext('2d', { willReadFrequently: true });

        isInitializing.value = false;
        isScanning.value = true;
        tick();
    } catch (err) {
        isInitializing.value = false;
        isScanning.value = false;

        if (err.name === 'NotAllowedError' || err.name === 'SecurityError') {
            errorTitle.value = 'Camera permission denied';
            errorMessage.value = 'Enable camera access in your browser settings and try again.';
        } else if (err.name === 'NotFoundError' || err.name === 'OverconstrainedError') {
            errorTitle.value = 'No camera found';
            errorMessage.value = 'This device does not have a camera available.';
        } else if (err.name === 'NotReadableError') {
            errorTitle.value = 'Camera in use';
            errorMessage.value = 'Another app is using the camera. Close it and try again.';
        } else {
            errorTitle.value = 'Camera error';
            errorMessage.value = err.message || 'Could not start the camera.';
        }
    }
};

const stopCamera = () => {
    isScanning.value = false;

    if (animationFrameId) {
        cancelAnimationFrame(animationFrameId);
        animationFrameId = null;
    }

    if (stream) {
        stream.getTracks().forEach((t) => t.stop());
        stream = null;
    }

    if (videoEl.value) {
        try {
            videoEl.value.srcObject = null;
        } catch (e) {
            // ignore
        }
    }

    canvasEl = null;
    ctx = null;
    lastDecodeAttempt = 0;
};

// ============== DECODE LOOP ==============

const tick = () => {
    if (!isScanning.value) return;

    const now = performance.now();

    if (
        videoEl.value &&
        videoEl.value.readyState === videoEl.value.HAVE_ENOUGH_DATA &&
        now - lastDecodeAttempt >= DECODE_INTERVAL_MS
    ) {
        lastDecodeAttempt = now;
        decodeFrame();
    }

    animationFrameId = requestAnimationFrame(tick);
};

const decodeFrame = () => {
    const video = videoEl.value;
    if (!video || !canvasEl || !ctx) return;

    const w = video.videoWidth;
    const h = video.videoHeight;
    if (!w || !h) return;

    canvasEl.width = w;
    canvasEl.height = h;

    ctx.drawImage(video, 0, 0, w, h);

    let imageData;
    try {
        imageData = ctx.getImageData(0, 0, w, h);
    } catch (e) {
        return; // tainted canvas — should not happen with getUserMedia
    }

    const code = jsQR(imageData.data, w, h, {
        inversionAttempts: 'dontInvert',
    });

    if (code && code.data) {
        handleDecodedText(code.data);
    }
};

// ============== HANDLE DECODED VALUE ==============

const handleDecodedText = (text) => {
    // We expect the QR to contain either:
    //   https://{host}/redeem/{token}
    //   or a bare 64-char token (if the QR was generated with just the raw token)
    const token = extractToken(text);

    if (!token) {
        // Not our QR — show error, keep scanning
        errorTitle.value = 'Not a coupon QR';
        errorMessage.value = 'This QR code is not a valid Omniscient coupon. Please try again.';
        // Auto-clear after a moment so scanning continues
        setTimeout(() => resetError(), 2200);
        return;
    }

    // Stop scanning and route to the confirm page
    isScanning.value = false;
    stopCamera();

    emit('close');
    router.visit(`/redeem/${token}`);
};

const extractToken = (text) => {
    if (!text) return null;

    // Match the /redeem/{token} path in a URL
    try {
        const url = new URL(text, window.location.origin);
        const match = url.pathname.match(/^\/redeem\/([A-Za-z0-9]{32,})$/);
        if (match) return match[1];
    } catch (e) {
        // Not a URL — fall through
    }

    // Fallback: bare token (alphanumeric, >= 32 chars)
    const bare = text.trim();
    if (/^[A-Za-z0-9]{32,}$/.test(bare)) {
        return bare;
    }

    return null;
};

// ============== UTIL ==============

const resetError = () => {
    errorMessage.value = '';
    errorTitle.value = '';
};

const close = () => {
    stopCamera();
    emit('close');
};
</script>

<style scoped>
@keyframes scan {
    0% {
        transform: translateY(0);
        opacity: 0.9;
    }
    50% {
        opacity: 1;
    }
    100% {
        transform: translateY(14rem);
        opacity: 0.6;
    }
}

.animate-scan {
    animation: scan 2s ease-in-out infinite alternate;
}
</style>