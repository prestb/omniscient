<!-- resources/js/Layouts/GuestLayout.vue -->
<template>
    <div class="min-h-screen bg-gray-50 dark:bg-gray-900 flex flex-col">

        <!-- ==================== NAV ==================== -->
        <nav class="bg-white/80 dark:bg-gray-900/80 backdrop-blur-md border-b border-gray-200 dark:border-gray-800 sticky top-0 z-40">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="flex justify-between h-16">
                    <div class="flex items-center">
                        <Link href="/" class="flex items-center gap-2.5 text-xl font-bold text-gray-900 dark:text-white tracking-tight group">
                            <div class="w-9 h-9 rounded-xl bg-gradient-to-br from-primary-500 to-primary-700 flex items-center justify-center shadow-lg shadow-primary-500/25 group-hover:shadow-primary-500/40 transition-shadow">
                                <svg class="w-5 h-5 text-white" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                                    <path d="M21 10.5V19a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h9.5" />
                                    <polyline points="16 2 22 8 16 8" />
                                    <line x1="10" y1="14" x2="21" y2="14" />
                                    <line x1="10" y1="18" x2="18" y2="18" />
                                    <line x1="3" y1="10" x2="8" y2="10" />
                                </svg>
                            </div>
                            <span class="hidden sm:inline">Omniscient</span>
                        </Link>
                    </div>

                    <div class="flex items-center gap-3">
                        <Link href="/directory"
                              class="hidden sm:inline-flex text-sm text-gray-600 dark:text-gray-400 hover:text-gray-900 dark:hover:text-white transition-colors font-medium">
                            Browse Directory
                        </Link>

                        <!-- ✅ Theme toggle — available on public pages -->
                        <DarkModeToggle />
                    </div>
                </div>
            </div>
        </nav>

        <!-- ==================== CONTENT ==================== -->
        <div class="relative flex-1 py-8 sm:py-12">
            <!-- Decorative dot pattern -->
            <div class="absolute inset-0 opacity-[0.03] dark:opacity-[0.06] pointer-events-none"
                 style="background-image: radial-gradient(circle, #000 1px, transparent 1px); background-size: 24px 24px;"></div>

            <!-- Blurred blobs -->
            <div class="absolute -top-32 -left-32 w-96 h-96 bg-primary-200/40 dark:bg-primary-900/20 rounded-full blur-3xl pointer-events-none"></div>
            <div class="absolute -bottom-32 -right-32 w-96 h-96 bg-purple-200/30 dark:bg-purple-900/20 rounded-full blur-3xl pointer-events-none"></div>

            <div class="relative max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <slot name="content">
                    <slot />
                </slot>
            </div>
        </div>

        <!-- ==================== FOOTER ==================== -->
        <footer class="bg-white dark:bg-gray-900 border-t border-gray-200 dark:border-gray-800">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-5">
                <div class="flex flex-col sm:flex-row items-center justify-between gap-3">
                    <p class="text-sm text-gray-500 dark:text-gray-400">
                        &copy; {{ new Date().getFullYear() }} Omniscient. All rights reserved.
                    </p>
                    <div class="flex items-center gap-5">
                        <Link href="/about" class="text-xs text-gray-500 dark:text-gray-400 hover:text-primary-600 dark:hover:text-primary-400 transition-colors font-medium">
                            About
                        </Link>
                        <Link href="/contact" class="text-xs text-gray-500 dark:text-gray-400 hover:text-primary-600 dark:hover:text-primary-400 transition-colors font-medium">
                            Contact
                        </Link>
                        <a href="#" class="text-xs text-gray-500 dark:text-gray-400 hover:text-primary-600 dark:hover:text-primary-400 transition-colors font-medium">
                            Privacy
                        </a>
                        <a href="#" class="text-xs text-gray-500 dark:text-gray-400 hover:text-primary-600 dark:hover:text-primary-400 transition-colors font-medium">
                            Terms
                        </a>
                    </div>
                </div>
            </div>
        </footer>

        <!-- ==================== TOASTS ==================== -->
        <ToastContainer />
    </div>
</template>

<script setup>
import { Link } from '@inertiajs/vue3';
import ToastContainer from '@/Components/Toast/ToastContainer.vue';
import DarkModeToggle from '@/Components/DarkModeToggle.vue';
</script>