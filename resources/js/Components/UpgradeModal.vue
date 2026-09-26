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
            <div
                v-if="isOpen"
                class="fixed inset-0 z-[100] flex items-center justify-center p-4 bg-black/60 backdrop-blur-sm"
                @click.self="hide"
            >
                <Transition
                    enter-active-class="transition duration-300 ease-out"
                    enter-from-class="opacity-0 scale-95 translate-y-4"
                    enter-to-class="opacity-100 scale-100 translate-y-0"
                    appear
                >
                    <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-2xl max-w-lg w-full overflow-hidden">

                        <!-- HEADER — hero strip style -->
                        <div class="relative overflow-hidden bg-gradient-to-br from-primary-700 via-primary-600 to-primary-800 dark:from-gray-900 dark:via-gray-800 dark:to-gray-900 text-white">
                            <!-- Dot pattern -->
                            <div class="absolute inset-0 opacity-[0.06]"
                                 style="background-image: radial-gradient(circle, white 1px, transparent 1px); background-size: 22px 22px;"></div>
                            <!-- Blur orb -->
                            <div class="absolute -top-16 -right-16 w-56 h-56 bg-primary-400/30 rounded-full blur-3xl"></div>

                            <button
                                @click="hide"
                                class="absolute top-4 right-4 z-10 p-1.5 text-white/80 hover:text-white hover:bg-white/10 rounded-lg transition-colors"
                                aria-label="Close"
                            >
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                                </svg>
                            </button>

                            <div class="relative p-6">
                                <div class="flex items-start gap-4">
                                    <div class="flex-shrink-0 w-14 h-14 rounded-2xl bg-white/10 backdrop-blur-sm border border-white/20 flex items-center justify-center shadow-lg">
                                        <!-- Padlock -->
                                        <svg class="w-7 h-7" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" />
                                        </svg>
                                    </div>
                                    <div class="flex-1 min-w-0 pr-8">
                                        <span class="inline-flex items-center gap-2 px-2.5 py-0.5 rounded-full bg-white/10 border border-white/20 text-[10px] font-bold tracking-widest uppercase text-primary-100 mb-2 backdrop-blur-sm">
                                            <span class="w-1.5 h-1.5 rounded-full bg-emerald-400 animate-pulse"></span>
                                            Premium Feature
                                        </span>
                                        <h3 class="text-xl md:text-2xl font-bold tracking-tight leading-tight">
                                            Unlock {{ modalData.featureLabel }}
                                        </h3>
                                        <p class="text-primary-100 text-sm mt-1.5 leading-relaxed">
                                            Upgrade to <strong class="text-white font-bold">{{ modalData.requiredPlan }}</strong> plan to access this feature
                                        </p>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- CONTENT -->
                        <div class="p-6">

                            <!-- Benefits list (when provided) -->
                            <div v-if="modalData.benefits.length > 0" class="mb-6">
                                <p class="text-[10px] font-bold text-gray-500 dark:text-gray-400 uppercase tracking-widest mb-3">
                                    What you'll unlock
                                </p>
                                <div class="space-y-2.5">
                                    <div
                                        v-for="(benefit, i) in modalData.benefits"
                                        :key="i"
                                        class="flex items-start gap-3 text-sm text-gray-700 dark:text-gray-300 leading-relaxed"
                                    >
                                        <div class="flex-shrink-0 w-5 h-5 rounded-full bg-emerald-100 dark:bg-emerald-900/30 flex items-center justify-center mt-0.5">
                                            <svg class="w-3 h-3 text-emerald-600 dark:text-emerald-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7" />
                                            </svg>
                                        </div>
                                        <span class="font-medium">{{ benefit }}</span>
                                    </div>
                                </div>
                            </div>

                            <!-- Fallback quick benefits (when none provided) -->
                            <div v-else class="mb-6">
                                <p class="text-[10px] font-bold text-gray-500 dark:text-gray-400 uppercase tracking-widest mb-3">
                                    Why upgrade
                                </p>
                                <div class="grid grid-cols-3 gap-3">
                                    <div class="text-center p-4 rounded-2xl bg-gradient-to-br from-primary-50 to-primary-100/50 dark:from-primary-900/20 dark:to-primary-900/10 border border-primary-100 dark:border-primary-900/40">
                                        <div class="w-10 h-10 mx-auto mb-2 rounded-2xl bg-white dark:bg-gray-800 shadow-sm flex items-center justify-center">
                                            <!-- Globe -->
                                            <svg class="w-5 h-5 text-primary-600 dark:text-primary-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3.055 11H5a2 2 0 012 2v1a2 2 0 002 2 2 2 0 012 2v2.945M8 3.935V5.5A2.5 2.5 0 0010.5 8h.5a2 2 0 012 2 2 2 0 104 0 2 2 0 012-2h1.064M15 20.488V18a2 2 0 012-2h3.064M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                            </svg>
                                        </div>
                                        <p class="text-xs font-bold text-gray-900 dark:text-white tracking-tight">More Reach</p>
                                    </div>
                                    <div class="text-center p-4 rounded-2xl bg-gradient-to-br from-primary-50 to-primary-100/50 dark:from-primary-900/20 dark:to-primary-900/10 border border-primary-100 dark:border-primary-900/40">
                                        <div class="w-10 h-10 mx-auto mb-2 rounded-2xl bg-white dark:bg-gray-800 shadow-sm flex items-center justify-center">
                                            <!-- Chart Bar -->
                                            <svg class="w-5 h-5 text-primary-600 dark:text-primary-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" />
                                            </svg>
                                        </div>
                                        <p class="text-xs font-bold text-gray-900 dark:text-white tracking-tight">More Insights</p>
                                    </div>
                                    <div class="text-center p-4 rounded-2xl bg-gradient-to-br from-primary-50 to-primary-100/50 dark:from-primary-900/20 dark:to-primary-900/10 border border-primary-100 dark:border-primary-900/40">
                                        <div class="w-10 h-10 mx-auto mb-2 rounded-2xl bg-white dark:bg-gray-800 shadow-sm flex items-center justify-center">
                                            <!-- Trending Up -->
                                            <svg class="w-5 h-5 text-primary-600 dark:text-primary-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6" />
                                            </svg>
                                        </div>
                                        <p class="text-xs font-bold text-gray-900 dark:text-white tracking-tight">More Sales</p>
                                    </div>
                                </div>
                            </div>

                            <!-- ACTIONS -->
                            <div class="flex flex-col sm:flex-row gap-3">
                                <button
                                    @click="hide"
                                    class="order-2 sm:order-1 sm:flex-1 inline-flex items-center justify-center px-5 py-3 bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-200 rounded-xl font-semibold hover:bg-gray-200 dark:hover:bg-gray-600 transition-colors text-sm"
                                >
                                    Maybe Later
                                </button>
                                <a
                                    href="/owner/subscription/renew"
                                    class="order-1 sm:order-2 sm:flex-1 inline-flex items-center justify-center gap-2 px-5 py-3 bg-gradient-to-r from-primary-600 to-primary-700 text-white rounded-xl font-semibold hover:from-primary-700 hover:to-primary-800 transition-all shadow-lg shadow-primary-500/25 hover:-translate-y-0.5 text-sm"
                                >
                                    View Plans
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7l5 5m0 0l-5 5m5-5H6" />
                                    </svg>
                                </a>
                            </div>

                            <!-- Small print -->
                            <p class="mt-4 text-xs text-center text-gray-500 dark:text-gray-400">
                                💳 No credit card required • Cancel anytime
                            </p>
                        </div>
                    </div>
                </Transition>
            </div>
        </Transition>
    </Teleport>
</template>

<script setup>
import { useUpgradeModal } from '@/composables/useUpgradeModal';

const { isOpen, modalData, hide } = useUpgradeModal();
</script>