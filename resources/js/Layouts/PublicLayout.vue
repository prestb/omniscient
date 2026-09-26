<!-- resources/js/Layouts/PublicLayout.vue -->
<template>
    <div class="min-h-screen bg-gray-50 dark:bg-gray-900">
        <!-- Public Navigation -->
        <nav ref="navRef"
            class="relative bg-white dark:bg-gray-900 shadow-sm sticky top-0 z-40 border-b border-gray-200 dark:border-gray-800">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="flex justify-between h-16">
                    <div class="flex items-center">
                        <Link href="/" class="flex items-center gap-2 text-xl font-bold text-gray-900 dark:text-white">
                            <svg class="w-8 h-8 text-primary-600 dark:text-primary-400" viewBox="0 0 24 24" fill="none"
                                stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <path d="M21 10.5V19a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h9.5" />
                                <polyline points="16 2 22 8 16 8" />
                                <line x1="10" y1="14" x2="21" y2="14" />
                                <line x1="10" y1="18" x2="18" y2="18" />
                                <line x1="3" y1="10" x2="8" y2="10" />
                            </svg>
                            Omniscient
                        </Link>
                    </div>

                    <div class="hidden md:flex items-center space-x-6">
                        <Link href="/directory"
                            class="text-gray-700 dark:text-gray-300 hover:text-primary-600 dark:hover:text-primary-400 transition-colors">
                            Directory
                        </Link>
                        <Link href="/explore"
                            class="text-gray-700 dark:text-gray-300 hover:text-primary-600 dark:hover:text-primary-400 transition-colors">
                            Explore
                        </Link>
                        <Link href="/pricing"
                            class="text-gray-700 dark:text-gray-300 hover:text-primary-600 dark:hover:text-primary-400 transition-colors">
                            Pricing
                        </Link>
                        <Link href="/contact"
                            class="text-gray-700 dark:text-gray-300 hover:text-primary-600 dark:hover:text-primary-400 transition-colors">
                            Contact
                        </Link>

                        <!-- Theme toggle -->
                        <DarkModeToggle />

                        <!-- Auth Links -->
                        <Link v-if="$page.props.auth?.user"
                            :href="$page.props.auth.user.role === 'user' ? '/user/dashboard' : '/dashboard'"
                            class="px-4 py-2 bg-primary-600 text-white rounded-lg hover:bg-primary-700 transition-colors text-sm font-medium">
                            {{ $page.props.auth.user.role === 'user' ? 'My Account' : 'Dashboard' }}
                        </Link>
                        <Link v-else href="/register"
                            class="px-4 py-2 bg-primary-600 text-white rounded-lg hover:bg-primary-700 transition-colors text-sm font-medium">
                            Get Started
                        </Link>
                    </div>

                    <!-- Mobile: theme toggle + menu button -->
                    <div class="flex md:hidden items-center gap-1">
                        <DarkModeToggle />
                        <button @click="toggleMobileMenu"
                            class="p-2 rounded-lg hover:bg-gray-100 dark:hover:bg-gray-800 transition-colors">
                            <svg class="w-6 h-6 text-gray-600 dark:text-gray-300" fill="none" stroke="currentColor"
                                viewBox="0 0 24 24">
                                <path v-if="!isMobileMenuOpen" stroke-linecap="round" stroke-linejoin="round"
                                    stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
                                <path v-else stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M6 18L18 6M6 6l12 12" />
                            </svg>
                        </button>
                    </div>
                </div>
            </div>

            <!-- Mobile Menu — absolute so it overlays content below -->
            <Transition enter-active-class="transition duration-200 ease-out"
                enter-from-class="opacity-0 -translate-y-2" enter-to-class="opacity-100 translate-y-0"
                leave-active-class="transition duration-150 ease-in" leave-from-class="opacity-100 translate-y-0"
                leave-to-class="opacity-0 -translate-y-2">
                <div v-if="isMobileMenuOpen"
                    class="md:hidden absolute top-full left-0 right-0 border-t border-gray-200 dark:border-gray-800 bg-white dark:bg-gray-900 shadow-lg">
                    <div class="px-4 py-2.5 space-y-1.5">
                        <Link href="/directory"
                            class="block px-3 py-2 rounded-lg hover:bg-gray-50 dark:hover:bg-gray-800 text-gray-900 dark:text-gray-100 transition-colors"
                            @click="isMobileMenuOpen = false">Directory</Link>
                        <Link href="/explore"
                            class="block px-3 py-2 rounded-lg hover:bg-gray-50 dark:hover:bg-gray-800 text-gray-900 dark:text-gray-100 transition-colors"
                            @click="isMobileMenuOpen = false">Explore</Link>
                        <Link href="/pricing"
                            class="block px-3 py-2 rounded-lg hover:bg-gray-50 dark:hover:bg-gray-800 text-gray-900 dark:text-gray-100 transition-colors"
                            @click="isMobileMenuOpen = false">Pricing</Link>
                        <Link href="/contact"
                            class="block px-3 py-2 rounded-lg hover:bg-gray-50 dark:hover:bg-gray-800 text-gray-900 dark:text-gray-100 transition-colors"
                            @click="isMobileMenuOpen = false">Contact</Link>
                        <div class="border-t border-gray-200 dark:border-gray-700 my-2"></div>
                        <Link v-if="$page.props.auth?.user" href="/dashboard"
                            class="block px-3 py-2 bg-primary-600 text-white rounded-lg text-center"
                            @click="isMobileMenuOpen = false">
                            Dashboard
                        </Link>
                        <Link v-else href="/login"
                            class="block px-3 py-2 bg-primary-600 text-white rounded-lg text-center"
                            @click="isMobileMenuOpen = false">
                            Sign In
                        </Link>
                    </div>
                </div>
            </Transition>
        </nav>

        <!-- Main Content -->
        <main class="pb-[env(safe-area-inset-bottom)]">
            <slot />
        </main>

        <!-- Footer -->
        <footer class="bg-white dark:bg-gray-900 border-t border-gray-200 dark:border-gray-800 mt-12">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
                <div class="grid grid-cols-1 md:grid-cols-4 gap-8">
                    <div>
                        <h3 class="font-semibold text-gray-900 dark:text-white mb-3">Omniscient</h3>
                        <p class="text-sm text-gray-600 dark:text-gray-400">Discover the best local businesses in
                            Cameroon.</p>
                    </div>
                    <div>
                        <h4 class="font-semibold text-gray-900 dark:text-white mb-3">Quick Links</h4>
                        <ul class="space-y-2 text-sm">
                            <li>
                                <Link href="/directory"
                                    class="text-gray-600 dark:text-gray-400 hover:text-primary-600 dark:hover:text-primary-400 transition-colors">
                                    Directory</Link>
                            </li>
                            <li>
                                <Link href="/explore"
                                    class="text-gray-600 dark:text-gray-400 hover:text-primary-600 dark:hover:text-primary-400 transition-colors">
                                    Explore</Link>
                            </li>
                            <li>
                                <Link href="/pricing"
                                    class="text-gray-600 dark:text-gray-400 hover:text-primary-600 dark:hover:text-primary-400 transition-colors">
                                    Pricing
                                </Link>
                            </li>
                            <li>
                                <Link href="/contact"
                                    class="text-gray-600 dark:text-gray-400 hover:text-primary-600 dark:hover:text-primary-400 transition-colors">
                                    Contact</Link>
                            </li>
                        </ul>
                    </div>
                    <div>
                        <h4 class="font-semibold text-gray-900 dark:text-white mb-3">For Businesses</h4>
                        <ul class="space-y-2 text-sm">
                            <li>
                                <Link href="/register"
                                    class="text-gray-600 dark:text-gray-400 hover:text-primary-600 dark:hover:text-primary-400 transition-colors">
                                    List Your Business</Link>
                            </li>
                            <li>
                                <Link href="/pricing"
                                    class="text-gray-600 dark:text-gray-400 hover:text-primary-600 dark:hover:text-primary-400 transition-colors">
                                    Pricing</Link>
                            </li>
                            <li>
                                <Link href="/owner/dashboard"
                                    class="text-gray-600 dark:text-gray-400 hover:text-primary-600 dark:hover:text-primary-400 transition-colors">
                                    Owner Login</Link>
                            </li>
                        </ul>
                    </div>
                    <div>
                        <h4 class="font-semibold text-gray-900 dark:text-white mb-3">Connect</h4>
                        <ul class="space-y-2 text-sm">
                            <li><a href="#"
                                    class="text-gray-600 dark:text-gray-400 hover:text-primary-600 dark:hover:text-primary-400 transition-colors">Twitter</a>
                            </li>
                            <li><a href="#"
                                    class="text-gray-600 dark:text-gray-400 hover:text-primary-600 dark:hover:text-primary-400 transition-colors">Facebook</a>
                            </li>
                            <li><a href="#"
                                    class="text-gray-600 dark:text-gray-400 hover:text-primary-600 dark:hover:text-primary-400 transition-colors">Instagram</a>
                            </li>
                        </ul>
                    </div>
                </div>
                <div class="border-t border-gray-100 dark:border-gray-800 mt-8 pt-6">
                    <p class="text-center text-sm text-gray-500 dark:text-gray-400">
                        &copy; {{ new Date().getFullYear() }} Omniscient Directory. All rights reserved.
                    </p>
                </div>
            </div>
        </footer>
        <OfflineBanner />
        <UpgradeModal />
        <!-- <ConfirmModal />  -->
        <ToastContainer />
    </div>
</template>

<script setup>
    import { ref, watch, onMounted, onUnmounted } from 'vue';
    import { Link, usePage } from '@inertiajs/vue3';
    import ToastContainer from '@/Components/Toast/ToastContainer.vue';
    import UpgradeModal from '@/Components/UpgradeModal.vue';
    import ConfirmModal from '@/Components/ConfirmModal.vue';
    import DarkModeToggle from '@/Components/DarkModeToggle.vue';
    import OfflineBanner from '@/Components/Common/OfflineBanner.vue';

    const isMobileMenuOpen = ref(false);
    const navRef = ref(null);
    const page = usePage();

    const toggleMobileMenu = () => {
        isMobileMenuOpen.value = !isMobileMenuOpen.value;
    };

    // ✅ Auto-close the mobile menu when the URL changes
    watch(
        () => page.url,
        () => {
            isMobileMenuOpen.value = false;
        }
    );

    // ✅ Lock page scroll while the menu is open.
    //    The bulletproof approach: freeze BOTH html and body, then
    //    restore scroll position afterward. Works across all layouts
    //    regardless of which element is the actual scroll container.
    let savedScrollY = 0;

    watch(isMobileMenuOpen, (open) => {
        if (open) {
            savedScrollY = window.scrollY;

            // Freeze html
            document.documentElement.style.overflow = 'hidden';
            document.documentElement.style.height = '100%';

            // Freeze body
            document.body.style.overflow = 'hidden';
            document.body.style.height = '100%';
            document.body.style.position = 'fixed';
            document.body.style.top = `-${savedScrollY}px`;
            document.body.style.left = '0';
            document.body.style.right = '0';
            document.body.style.width = '100%';
        } else {
            // Restore everything
            document.documentElement.style.overflow = '';
            document.documentElement.style.height = '';
            document.body.style.overflow = '';
            document.body.style.height = '';
            document.body.style.position = '';
            document.body.style.top = '';
            document.body.style.left = '';
            document.body.style.right = '';
            document.body.style.width = '';

            // Restore scroll position
            window.scrollTo(0, savedScrollY);
        }
    });

    // ✅ Close when clicking outside the nav
    const handleClickOutside = (e) => {
        if (!isMobileMenuOpen.value) return;
        if (navRef.value && !navRef.value.contains(e.target)) {
            isMobileMenuOpen.value = false;
        }
    };

    onMounted(() => {
        document.addEventListener('click', handleClickOutside);
    });

    onUnmounted(() => {
        document.removeEventListener('click', handleClickOutside);
        document.documentElement.style.overflow = '';
        document.documentElement.style.height = '';
        document.body.style.overflow = '';
        document.body.style.height = '';
        document.body.style.position = '';
        document.body.style.top = '';
        document.body.style.left = '';
        document.body.style.right = '';
        document.body.style.width = '';
    });
</script>