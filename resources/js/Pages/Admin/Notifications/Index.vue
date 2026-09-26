<!-- resources/js/Pages/Admin/Notifications/Index.vue -->
<template>
    <AuthenticatedLayout>

        <!-- COMPACT HEADER -->
        <PageHeader color="blue" :breadcrumb="[
            { label: 'Admin', href: '/admin/dashboard' },
            { label: 'Notifications' }
        ]">
            <template #icon>
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                    d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9" />
            </template>
            <template #title>Your notifications</template>
            <template #subtitle>Stay updated with platform activity</template>
            <template #actions>
                <button v-if="unreadCount > 0" @click="markAllAsRead"
                    class="inline-flex items-center gap-2 px-4 py-2 bg-gradient-to-r from-primary-600 to-primary-700 text-white rounded-xl font-semibold hover:from-primary-700 hover:to-primary-800 transition-all shadow-lg shadow-primary-500/25 text-sm">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7" />
                    </svg>
                    Mark all as read
                </button>
            </template>
        </PageHeader>

        <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 py-8 space-y-6">

            <!-- STATS -->
            <div class="grid grid-cols-2 gap-4">
                <div class="bg-white dark:bg-gray-800 rounded-2xl border border-gray-100 dark:border-gray-700 p-4 hover:shadow-md transition-shadow">
                    <p class="text-[10px] font-bold text-gray-400 dark:text-gray-500 uppercase tracking-widest">Total</p>
                    <p class="text-2xl font-bold text-gray-900 dark:text-white tracking-tight mt-1">{{ notifications.total || 0 }}</p>
                </div>
                <div class="bg-white dark:bg-gray-800 rounded-2xl border border-gray-100 dark:border-gray-700 p-4 hover:shadow-md transition-shadow">
                    <p class="text-[10px] font-bold text-gray-400 dark:text-gray-500 uppercase tracking-widest">Unread</p>
                    <p class="text-2xl font-bold text-blue-600 dark:text-blue-400 tracking-tight mt-1">{{ unreadCount || 0 }}</p>
                </div>
            </div>

            <!-- LIST -->
            <div class="bg-white dark:bg-gray-800 rounded-2xl border border-gray-100 dark:border-gray-700 overflow-hidden">
                <div v-if="notifications.data && notifications.data.length > 0" class="divide-y divide-gray-100 dark:divide-gray-700">
                    <div v-for="notification in notifications.data" :key="notification.id" class="p-5 transition-colors"
                        :class="notification.read_at
                            ? 'bg-white dark:bg-gray-800 hover:bg-gray-50/50 dark:hover:bg-gray-700/30'
                            : 'bg-gradient-to-r from-blue-50/60 to-white dark:from-blue-900/20 dark:to-gray-800 hover:from-blue-50/80 dark:hover:from-blue-900/30'">
                        <div class="flex items-start gap-4">

                            <!-- Icon -->
                            <div class="flex-shrink-0">
                                <div class="w-12 h-12 rounded-2xl flex items-center justify-center text-2xl shadow-sm"
                                    :class="notification.read_at
                                        ? 'bg-gray-100 dark:bg-gray-700'
                                        : 'bg-blue-100 dark:bg-blue-900/40'">
                                    {{ getNotificationIcon(notification.type) }}
                                </div>
                            </div>

                            <!-- Content -->
                            <div class="flex-1 min-w-0">
                                <div class="flex items-start justify-between flex-wrap gap-2">
                                    <div class="flex items-center gap-2 flex-wrap min-w-0">
                                        <p class="text-sm font-bold text-gray-900 dark:text-white tracking-tight">
                                            {{ notification.data.title || 'Notification' }}
                                        </p>
                                        <span v-if="!notification.read_at"
                                            class="inline-flex items-center gap-1 px-2 py-0.5 text-[10px] font-bold uppercase tracking-wide bg-blue-100 dark:bg-blue-900/40 text-blue-700 dark:text-blue-400 rounded-full">
                                            <span class="w-1.5 h-1.5 bg-blue-500 rounded-full animate-pulse"></span>
                                            New
                                        </span>
                                    </div>
                                    <span class="text-xs text-gray-400 dark:text-gray-500 flex items-center gap-1 flex-shrink-0">
                                        <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                                        </svg>
                                        {{ formatDate(notification.created_at) }}
                                    </span>
                                </div>

                                <p class="text-sm text-gray-600 dark:text-gray-400 mt-1.5 leading-relaxed">
                                    {{ notification.data.message }}
                                </p>

                                <!-- Actions -->
                                <div class="flex items-center flex-wrap gap-4 mt-3 pt-3 border-t border-gray-100/70 dark:border-gray-700/70">
                                    <a v-if="notification.data.action_url" :href="notification.data.action_url"
                                        class="inline-flex items-center gap-1.5 text-xs font-bold uppercase tracking-wide text-primary-600 dark:text-primary-400 hover:text-primary-800 dark:hover:text-primary-300 transition-colors group">
                                        View details
                                        <svg class="w-3.5 h-3.5 transform group-hover:translate-x-0.5 transition-transform"
                                            fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M9 5l7 7-7 7" />
                                        </svg>
                                    </a>
                                    <button v-if="!notification.read_at" @click="markAsRead(notification)"
                                        class="inline-flex items-center gap-1.5 text-xs font-bold uppercase tracking-wide text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-200 transition-colors">
                                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5"
                                                d="M5 13l4 4L19 7" />
                                        </svg>
                                        Mark as read
                                    </button>
                                    <button @click="deleteNotification(notification)"
                                        class="inline-flex items-center gap-1.5 text-xs font-bold uppercase tracking-wide text-red-500 dark:text-red-400 hover:text-red-700 dark:hover:text-red-300 transition-colors ml-auto">
                                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                        </svg>
                                        Delete
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Empty state -->
                <div v-else class="p-16 text-center">
                    <div class="relative inline-block mb-5">
                        <div
                            class="w-20 h-20 rounded-2xl bg-gradient-to-br from-gray-100 to-gray-50 dark:from-gray-700 dark:to-gray-800 flex items-center justify-center shadow-sm mx-auto">
                            <svg class="w-10 h-10 text-gray-400 dark:text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9" />
                            </svg>
                        </div>
                        <div
                            class="absolute -top-1 -right-1 w-7 h-7 rounded-2xl bg-emerald-100 dark:bg-emerald-900/40 flex items-center justify-center shadow-md">
                            <svg class="w-4 h-4 text-emerald-600 dark:text-emerald-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5"
                                    d="M5 13l4 4L19 7" />
                            </svg>
                        </div>
                    </div>
                    <h3 class="text-xl font-bold text-gray-900 dark:text-white tracking-tight mb-2">All caught up! 🎉</h3>
                    <p class="text-sm text-gray-500 dark:text-gray-400">You have no notifications at the moment.</p>
                </div>

                <!-- Pagination -->
                <div v-if="notifications.data && notifications.data.length > 0"
                    class="px-6 py-4 border-t border-gray-100 dark:border-gray-700">
                    <Pagination :links="notifications.links" />
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>


<script setup>
    import { router } from '@inertiajs/vue3';
    import { useToast } from '@/composables/useToast';
    import { useConfirm } from '@/composables/useConfirm';
    import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
    import Pagination from '@/Components/Pagination.vue';
    import PageHeader from '@/Components/PageHeader.vue';

    const props = defineProps({
        notifications: Object,
        unreadCount: Number,
    });

    const { error } = useToast();
    const { confirm: confirmDialog } = useConfirm();

    const formatDate = (date) => {
        if (!date) return 'N/A';
        return new Date(date).toLocaleString('en-US', {
            year: 'numeric',
            month: 'short',
            day: 'numeric',
            hour: '2-digit',
            minute: '2-digit'
        });
    };

    const getNotificationIcon = (type) => {
        const icons = {
            'invitation': '📧',
            'account_approved': '✅',
            'subscription_active': '💳',
            'subscription_expiring': '⚠️',
            'subscription_expired': '⛔',
            'subscription_grace_period': '⏳',
            'subscription_suspended': '🚫',
            'business_approved': '🎉',
            'admin_notification': '📢',
            'default': '📢',
        };
        return icons[type] || icons.default;
    };

    const markAsRead = (notification) => {
        router.post(`/admin/notifications/${notification.id}/mark-as-read`, {}, {
            preserveScroll: true,
            // ✅ No success toast — the controller flashes
            //    'Notification marked as read.' The redirect also
            //    refreshes the page props (unread count, list order).
            onError: () => {
                error('Action Failed ❌', 'Failed to mark notification as read.', { duration: 3000 });
            },
        });
    };

    const markAllAsRead = async () => {
        const confirmed = await confirmDialog({
            title: 'Mark all as read?',
            message: 'All unread notifications will be marked as read.',
            confirmText: 'Mark All as Read',
            cancelText: 'Cancel',
            variant: 'primary',
        });

        if (!confirmed) return;

        router.post('/admin/notifications/mark-all-as-read', {}, {
            preserveScroll: true,
            // ✅ No success toast — the controller flashes
            onError: () => {
                error('Action Failed ❌', 'Failed to mark all notifications as read.', { duration: 3000 });
            },
        });
    };

    const deleteNotification = async (notification) => {
        const confirmed = await confirmDialog({
            title: 'Delete notification?',
            message: 'This notification will be permanently removed.',
            confirmText: 'Delete',
            cancelText: 'Cancel',
            variant: 'danger',
        });

        if (!confirmed) return;

        router.delete(`/admin/notifications/${notification.id}`, {
            preserveScroll: true,
            // ✅ No success toast — the controller flashes
            onError: () => {
                error('Delete Failed ❌', 'Failed to delete notification.', { duration: 3000 });
            },
        });
    };
</script>