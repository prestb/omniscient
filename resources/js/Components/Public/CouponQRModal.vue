<!-- resources/js/Components/Public/CouponQRModal.vue -->
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
            <div v-if="show"
                 class="fixed inset-0 z-[150] flex items-center justify-center p-4 bg-black/70 backdrop-blur-sm"
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
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v1m6 11h2m-6 0h-2v4m0-11v3m0 0h.01M12 12h4.01M16 20h4M4 12h4m12 0h.01M5 8h2a1 1 0 001-1V5a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1zm14 0h2a1 1 0 001-1V5a1 1 0 00-1-1h-2a1 1 0 00-1 1v2a1 1 0 001 1zM5 20h2a1 1 0 001-1v-2a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1z" />
                                    </svg>
                                </div>
                                <div class="min-w-0">
                                    <h3 class="text-base font-bold text-gray-900 dark:text-white tracking-tight truncate">
                                        Redeem Coupon
                                    </h3>
                                    <p class="text-xs text-gray-500 dark:text-gray-400 truncate">
                                        {{ coupon?.title || '' }}
                                    </p>
                                </div>
                            </div>
                            <button @click="close"
                                    aria-label="Close coupon QR"
                                    class="w-8 h-8 flex items-center justify-center rounded-lg text-gray-400 hover:text-gray-700 dark:hover:text-gray-200 hover:bg-gray-100 dark:hover:bg-gray-700 transition-colors flex-shrink-0">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                                </svg>
                            </button>
                        </div>

                        <!-- Body -->
                        <div class="p-6">
                            <!-- Generating -->
                            <div v-if="state === 'generating'" class="flex flex-col items-center justify-center py-16">
                                <svg class="w-10 h-10 animate-spin text-primary-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                                </svg>
                                <p class="text-sm text-gray-500 dark:text-gray-400 mt-4">Generating your QR code…</p>
                            </div>

                            <!-- Error -->
                            <div v-else-if="state === 'error'"
                                 class="flex flex-col items-center justify-center py-10 text-center">
                                <div class="w-14 h-14 rounded-full bg-red-100 dark:bg-red-900/30 flex items-center justify-center mb-3">
                                    <svg class="w-7 h-7 text-red-600 dark:text-red-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                                    </svg>
                                </div>
                                <p class="text-sm font-bold text-gray-900 dark:text-white">{{ errorMessage || 'Something went wrong.' }}</p>
                                <button @click="$emit('retry')"
                                        class="mt-4 inline-flex items-center gap-2 px-5 py-2.5 bg-gray-900 dark:bg-white text-white dark:text-gray-900 rounded-xl font-semibold text-sm hover:bg-gray-800 dark:hover:bg-gray-100 transition-colors">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                                    </svg>
                                    Try Again
                                </button>
                            </div>

                            <!-- Expired -->
                            <div v-else-if="state === 'expired'"
                                 class="flex flex-col items-center justify-center py-10 text-center">
                                <div class="w-14 h-14 rounded-full bg-amber-100 dark:bg-amber-900/30 flex items-center justify-center mb-3">
                                    <svg class="w-7 h-7 text-amber-600 dark:text-amber-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                                    </svg>
                                </div>
                                <p class="text-sm font-bold text-gray-900 dark:text-white">QR code expired</p>
                                <p class="text-xs text-gray-500 dark:text-gray-400 mt-1 mb-4">Generate a fresh one to continue.</p>
                                <button @click="$emit('retry')"
                                        class="inline-flex items-center gap-2 px-5 py-2.5 bg-gradient-to-r from-primary-600 to-purple-600 text-white rounded-xl font-semibold text-sm hover:from-primary-700 hover:to-purple-700 transition-all shadow-lg shadow-primary-500/25">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                                    </svg>
                                    Generate New Code
                                </button>
                            </div>

                            <!-- Ready: QR + countdown -->
                            <div v-else-if="state === 'ready'" class="flex flex-col items-center text-center">
                                <p class="text-xs uppercase tracking-widest text-gray-400 font-bold mb-1">
                                    Show this to the business
                                </p>
                                <p class="text-sm text-gray-600 dark:text-gray-400 mb-4 leading-relaxed">
                                    They'll scan it to apply your discount.
                                </p>

                                <div class="bg-white p-4 rounded-2xl border-2 border-gray-100 shadow-md">
                                    <QrcodeVue
                                        :value="qrUrl"
                                        :size="qrSize"
                                        level="M"
                                        render-as="svg"
                                        foreground="#111827"
                                        background="#ffffff"
                                    />
                                </div>

                                <div class="mt-4 inline-flex items-center gap-2 px-3.5 py-2 rounded-xl"
                                     :class="secondsLeft <= 60
                                         ? 'bg-red-50 dark:bg-red-900/20 text-red-700 dark:text-red-400 border border-red-200 dark:border-red-800'
                                         : 'bg-gray-50 dark:bg-gray-900/50 text-gray-700 dark:text-gray-300 border border-gray-200 dark:border-gray-700'">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                                    </svg>
                                    <span class="text-sm font-bold tabular-nums">
                                        Expires in {{ formattedTimeLeft }}
                                    </span>
                                </div>

                                <p class="text-[11px] text-gray-400 dark:text-gray-500 mt-3">
                                    Do not close this window while the business is scanning.
                                </p>
                            </div>
                        </div>

                    </div>
                </Transition>
            </div>
        </Transition>
    </Teleport>
</template>

<script setup>
import { computed } from 'vue';
import QrcodeVue from 'qrcode.vue';

const props = defineProps({
    show: {
        type: Boolean,
        default: false,
    },
    state: {
        type: String,
        default: 'idle', // idle | generating | ready | expired | error
    },
    coupon: {
        type: Object,
        default: null,
    },
    qrUrl: {
        type: String,
        default: '',
    },
    errorMessage: {
        type: String,
        default: '',
    },
    secondsLeft: {
        type: Number,
        default: 0,
    },
});

const emit = defineEmits(['close', 'retry']);

const qrSize = computed(() => 240);

const formattedTimeLeft = computed(() => {
    const s = Math.max(0, props.secondsLeft);
    const m = Math.floor(s / 60);
    const sec = s % 60;
    return `${m}:${sec.toString().padStart(2, '0')}`;
});

const close = () => emit('close');
</script>