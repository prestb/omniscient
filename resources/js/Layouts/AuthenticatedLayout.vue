<template>
    <div class="min-h-screen bg-gray-50 dark:bg-gray-900">
        <!-- Sidebar (Owner/Admin/Super only) -->
        <Sidebar v-if="showSidebar" :user="user" :mobile-open="mobileSidebarOpen" @close="mobileSidebarOpen = false" />

        <!-- Main content wrapper -->
        <div class="flex flex-col min-h-screen transition-[padding]" :class="showSidebar ? 'lg:pl-60' : ''">
            <!-- Enhanced Navigation -->
            <Navbar :user="user" :notification-count="notificationCount"
                @toggle-sidebar="mobileSidebarOpen = !mobileSidebarOpen" />

            <!-- ✅ Email verification banner (unverified owners only, dismissible) -->
            <div v-if="emailUnverified && !verifyBannerDismissed"
                class="sticky top-0 z-40 bg-gradient-to-r from-sky-500 via-blue-500 to-sky-500 text-white shadow-md">
                <div
                    class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-2 flex items-center justify-between gap-3 flex-wrap">
                    <div class="flex items-center gap-2 text-sm font-semibold min-w-0">
                        <svg class="w-4 h-4 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />
                        </svg>
                        <span class="truncate">
                            <strong>Verify your email</strong>
                            <span class="hidden sm:inline opacity-90">— you won't be able to publish a listing until
                                it's verified.</span>
                        </span>
                    </div>
                    <div class="flex items-center gap-2 flex-shrink-0">
                        <button type="button" @click="resendVerification" :disabled="resending"
                            class="inline-flex items-center gap-1.5 px-3 py-1.5 bg-white text-sky-700 rounded-lg font-bold text-xs hover:bg-sky-50 transition-colors disabled:opacity-60">
                            <svg v-if="resending" class="w-3.5 h-3.5 animate-spin" fill="none" stroke="currentColor"
                                viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                            </svg>
                            <svg v-else class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />
                            </svg>
                            {{ resending ? 'Sending…' : 'Resend email' }}
                        </button>
                        <a href="/verify-email" class="text-xs font-bold underline underline-offset-2 hover:opacity-90">
                            Open verification page
                        </a>
                        <button type="button" @click="verifyBannerDismissed = true"
                            class="text-white/80 hover:text-white transition-colors p-1" aria-label="Dismiss">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M6 18L18 6M6 6l12 12" />
                            </svg>
                        </button>
                    </div>
                </div>
            </div>

            <!-- ✅ Impersonation banner -->
            <div v-if="impersonator"
                class="sticky top-0 z-40 bg-gradient-to-r from-amber-400 via-yellow-400 to-amber-400 text-amber-950 shadow-md">
                <div
                    class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-2 flex items-center justify-between gap-3 flex-wrap">
                    <div class="flex items-center gap-2 text-sm font-semibold min-w-0">
                        <svg class="w-4 h-4 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                        </svg>
                        <span class="truncate">
                            Impersonating <strong>{{ user?.name }}</strong>
                            <span class="hidden sm:inline opacity-80">({{ user?.email }})</span>
                            · Admin: {{ impersonator.name }}
                        </span>
                    </div>
                    <button @click="stopImpersonating"
                        class="inline-flex items-center gap-1.5 px-3 py-1.5 bg-amber-950 text-amber-50 rounded-lg font-bold text-xs hover:bg-amber-900 transition-colors flex-shrink-0">
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M10 19l-7-7m0 0l7-7m-7 7h18" />
                        </svg>
                        Return to admin
                    </button>
                </div>
            </div>

            <!-- Add padding for mobile bottom nav on tablet and below -->
            <!-- Add padding for mobile bottom nav (safe-area aware) -->
            <div class="pb-[calc(4rem+env(safe-area-inset-bottom))] md:pb-0 flex-1">
                <header v-if="$slots.header"
                    class="bg-white/80 dark:bg-gray-800/80 backdrop-blur-sm border-b border-gray-200 dark:border-gray-700">
                    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-4">
                        <slot name="header" />
                    </div>
                </header>

                <main :class="showSidebar ? 'pb-4 sm:pb-6' : 'py-4 sm:py-6'">
                    <slot />
                </main>
            </div>
        </div>

        <UpgradeModal />
        <ConfirmModal />
        <!-- Toast Container -->
        <ToastContainer />
    </div>
</template>

<script setup>
    import { ref, computed, onMounted, onUnmounted, watch } from 'vue';
    import { usePage, router } from '@inertiajs/vue3';
    import axios from 'axios';
    import Navbar from '@/Components/Navbar.vue';
    import Sidebar from '@/Components/Sidebar.vue';
    import ToastContainer from '@/Components/Toast/ToastContainer.vue';
    import UpgradeModal from '@/Components/UpgradeModal.vue';
    import { useToast } from '@/composables/useToast';
    import ConfirmModal from '@/Components/ConfirmModal.vue';
    import { useNavigation } from '@/composables/useNavigation';

    const page = usePage();
    const { success, error: showError, warning, info } = useToast();
    const { usesSidebar } = useNavigation();

    const notificationCount = ref(0);
    const mobileSidebarOpen = ref(false);
    const user = computed(() => page.props.auth?.user || null);
    const impersonator = computed(() => page.props.auth?.impersonator || null);

    // ✅ Email verification banner state
    const verifyBannerDismissed = ref(false);
    const resending = ref(false);

    const emailUnverified = computed(() => {
        const u = user.value;
        if (!u) return false;
        if (u.role !== 'owner') return false;
        return !u.email_verified_at;
    });

    const resendVerification = () => {
        if (resending.value) return;
        resending.value = true;
        router.post('/email/verification-notification', {}, {
            preserveScroll: true,
            onFinish: () => { resending.value = false; },
        });
    };

    const stopImpersonating = () => {
        router.post('/admin/impersonate/stop');
    };

    // Show sidebar only for owner/admin/super roles
    const showSidebar = computed(() => usesSidebar(user.value?.role));

    // Auto-close mobile drawer on navigation
    watch(
        () => page.url,
        () => {
            mobileSidebarOpen.value = false;
        }
    );

    // Fetch notification count
    const fetchNotificationCount = async () => {
        try {
            const role = user.value?.role;
            const url = role === 'admin' || role === 'super_admin'
                ? '/admin/notifications/unread-count'
                : '/owner/notifications/unread-count';
            // With this:
            const response = await fetch(url, {
                headers: {
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                },
                credentials: 'same-origin',
            });
            if (!response.ok) throw new Error(await response.text());
            const data = await response.json();
            notificationCount.value = data.count || 0;
        } catch (error) {
            console.error('Error fetching notification count:', error);
        }
    };

    let interval;

    // ✅ Flash message handler
    const handleFlashMessages = () => {
        const flash = page.props.flash || {};

        if (flash.success) {
            success('Success', flash.success, { duration: 4000 });
        }
        if (flash.error) {
            showError('Error', flash.error, { duration: 5000 });
        }
        if (flash.warning) {
            warning('Warning', flash.warning, { duration: 4000 });
        }
        if (flash.info || flash.message) {
            info('Info', flash.info || flash.message, { duration: 4000 });
        }
    };

    onMounted(() => {
        fetchNotificationCount();
        interval = setInterval(fetchNotificationCount, 30000);
        handleFlashMessages();
    });

    // ✅ Watch for flash changes (important for Inertia redirects)
    watch(
        () => page.props.flash,
        () => {
            handleFlashMessages();
        },
        { deep: true }
    );

    onUnmounted(() => {
        if (interval) {
            clearInterval(interval);
        }
    });
</script>