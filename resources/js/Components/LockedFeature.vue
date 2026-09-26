<!-- resources/js/Components/LockedFeature.vue -->
<template>
    <!-- Desktop + unlocked: normal inline wrapper -->
    <div class="relative hidden sm:block">
        <div class="relative">
            <slot />

            <!-- Lock overlay (desktop only) -->
            <div v-if="!isUnlocked"
                 class="absolute inset-0 bg-white/85 dark:bg-gray-900/85 backdrop-blur-sm rounded-xl flex items-center justify-center z-10 cursor-pointer"
                 @click="showUpgrade = true">
                <div class="text-center px-4">
                    <div class="inline-flex items-center justify-center w-10 h-10 rounded-xl bg-gradient-to-br from-amber-400 to-orange-500 mb-2 shadow-lg shadow-amber-500/30">
                        <svg class="w-4 h-4 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" />
                        </svg>
                    </div>
                    <p class="text-xs font-bold text-gray-900 dark:text-white tracking-tight">
                        {{ featureLabel }} locked
                    </p>
                    <p class="text-[10px] text-gray-600 dark:text-gray-400 mt-0.5">
                        {{ requiredPlan }}+ required
                    </p>
                </div>
            </div>
        </div>
    </div>

    <!-- Mobile: locked state as inline card; unlocked state renders slot normally -->
    <div class="sm:hidden">
        <template v-if="isUnlocked">
            <slot />
        </template>
        <button v-else
                @click="showUpgrade = true"
                class="w-full inline-flex items-center justify-center gap-2 px-4 py-2.5 bg-gradient-to-r from-amber-500 to-orange-500 text-white rounded-xl font-semibold shadow-lg shadow-amber-500/25 text-sm">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" />
            </svg>
            Unlock {{ featureLabel }} ({{ requiredPlan }}+)
        </button>
    </div>

    <!-- Upgrade modal (unchanged) -->
    <Teleport to="body">
        <Transition
            enter-active-class="transition duration-200 ease-out"
            enter-from-class="opacity-0"
            enter-to-class="opacity-100"
            leave-active-class="transition duration-150 ease-in"
            leave-from-class="opacity-100"
            leave-to-class="opacity-0">
            <div v-if="showUpgrade"
                 class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-gray-900/60 backdrop-blur-sm"
                 @click.self="showUpgrade = false">
                <Transition
                    enter-active-class="transition duration-200 ease-out"
                    enter-from-class="opacity-0 translate-y-4 scale-95"
                    enter-to-class="opacity-100 translate-y-0 scale-100"
                    leave-active-class="transition duration-150 ease-in"
                    leave-from-class="opacity-100 translate-y-0 scale-100"
                    leave-to-class="opacity-0 translate-y-4 scale-95">
                    <div class="bg-white dark:bg-gray-800 rounded-3xl shadow-2xl max-w-md w-full overflow-hidden">

                        <!-- Header -->
                        <div class="relative bg-gradient-to-br from-primary-600 via-primary-700 to-purple-700 p-6 text-white text-center">
                            <div class="absolute inset-0 opacity-[0.06]"
                                 style="background-image: radial-gradient(circle, white 1px, transparent 1px); background-size: 22px 22px;"></div>

                            <button @click="showUpgrade = false"
                                    class="absolute top-4 right-4 text-white/80 hover:text-white transition-colors z-10">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                                </svg>
                            </button>

                            <div class="relative">
                                <div class="inline-flex items-center justify-center w-16 h-16 rounded-2xl bg-white/20 backdrop-blur-sm mb-3">
                                    <svg class="w-7 h-7" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z" />
                                    </svg>
                                </div>
                                <h3 class="text-xl font-bold tracking-tight mb-1">Upgrade required</h3>
                                <p class="text-white/80 text-sm">
                                    {{ featureLabel }} is available on the {{ requiredPlan }} plan
                                </p>
                            </div>
                        </div>

                        <!-- Body -->
                        <div class="p-6">
                            <p class="text-sm text-gray-600 dark:text-gray-400 mb-5 text-center leading-relaxed">
                                Unlock this feature and many more by upgrading your plan.
                            </p>

                            <!-- Benefits -->
                            <div v-if="benefits.length > 0" class="space-y-2 mb-6">
                                <div v-for="(benefit, i) in benefits"
                                     :key="i"
                                     class="flex items-start gap-2.5 text-sm text-gray-700 dark:text-gray-300">
                                    <div class="flex-shrink-0 w-5 h-5 rounded-full bg-emerald-100 flex items-center justify-center mt-0.5">
                                        <svg class="w-3 h-3 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7" />
                                        </svg>
                                    </div>
                                    {{ benefit }}
                                </div>
                            </div>

                            <!-- Actions -->
                            <div class="flex gap-3">
                                <button @click="showUpgrade = false"
                                        class="flex-1 px-4 py-2.5 bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-200 rounded-xl font-semibold hover:bg-gray-200 dark:hover:bg-gray-600 transition-colors text-sm">
                                    Maybe later
                                </button>
                                <a href="/owner/subscription/renew"
                                   class="flex-1 inline-flex items-center justify-center gap-2 px-4 py-2.5 bg-gradient-to-r from-primary-600 to-primary-700 text-white rounded-xl font-semibold hover:from-primary-700 hover:to-primary-800 transition-all shadow-lg shadow-primary-500/25 text-sm">
                                    View Plans
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7l5 5m0 0l-5 5m5-5H6" />
                                    </svg>
                                </a>
                            </div>
                        </div>
                    </div>
                </Transition>
            </div>
        </Transition>
    </Teleport>
</template>

<script setup>
import { ref, computed } from 'vue';
import { usePlan } from '@/composables/usePlan';

const props = defineProps({
    feature: {
        type: String,
        required: true,
    },
    featureLabel: {
        type: String,
        default: 'This feature',
    },
    requiredPlan: {
        type: String,
        default: 'Starter',
    },
    benefits: {
        type: Array,
        default: () => [],
    },
});

const { can } = usePlan();
const showUpgrade = ref(false);

const isUnlocked = computed(() => can(props.feature));
</script>