<template>
    <AuthenticatedLayout>

        <Head title="Send Notification" />
        <!-- COMPACT HEADER -->
        <PageHeader color="amber" :breadcrumb="[
            { label: 'Admin', href: '/admin/dashboard' },
            { label: 'Notifications', href: '/admin/notifications' },
            { label: 'Scheduled' }
        ]">
            <template #icon>
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                    d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
            </template>
            <template #title>Scheduled notifications</template>
            <template #subtitle>Manage pending, sent, and cancelled notifications</template>
            <template #actions>
                <a href="/admin/notifications/create"
                    class="inline-flex items-center gap-2 px-4 py-2 bg-gradient-to-r from-primary-600 to-primary-700 text-white rounded-xl font-semibold hover:from-primary-700 hover:to-primary-800 transition-all shadow-lg shadow-primary-500/25 text-sm">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                    </svg>
                    New notification
                </a>
            </template>
        </PageHeader>
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8 space-y-6">

            <!-- STATS -->
            <div class="grid grid-cols-2 sm:grid-cols-4 gap-4">
                <div
                    class="bg-white dark:bg-gray-800 rounded-2xl border border-gray-100 dark:border-gray-700 p-4 hover:shadow-md transition-shadow">
                    <p class="text-[10px] font-bold text-gray-400 dark:text-gray-500 uppercase tracking-widest">Pending
                    </p>
                    <p class="text-2xl font-bold text-amber-600 tracking-tight mt-1">{{ stats.pending }}</p>
                </div>
                <div
                    class="bg-white dark:bg-gray-800 rounded-2xl border border-gray-100 dark:border-gray-700 p-4 hover:shadow-md transition-shadow">
                    <p class="text-[10px] font-bold text-gray-400 dark:text-gray-500 uppercase tracking-widest">Sent</p>
                    <p class="text-2xl font-bold text-emerald-600 tracking-tight mt-1">{{ stats.sent }}</p>
                </div>
                <div
                    class="bg-white dark:bg-gray-800 rounded-2xl border border-gray-100 dark:border-gray-700 p-4 hover:shadow-md transition-shadow">
                    <p class="text-[10px] font-bold text-gray-400 dark:text-gray-500 uppercase tracking-widest">Failed
                    </p>
                    <p class="text-2xl font-bold text-red-600 tracking-tight mt-1">{{ stats.failed }}</p>
                </div>
                <div
                    class="bg-white dark:bg-gray-800 rounded-2xl border border-gray-100 dark:border-gray-700 p-4 hover:shadow-md transition-shadow">
                    <p class="text-[10px] font-bold text-gray-400 dark:text-gray-500 uppercase tracking-widest">
                        Cancelled</p>
                    <p class="text-2xl font-bold text-gray-500 tracking-tight mt-1">{{ stats.cancelled }}</p>
                </div>
            </div>

            <!-- LIST -->
            <div
                class="bg-white dark:bg-gray-800 rounded-2xl border border-gray-100 dark:border-gray-700 overflow-hidden">
                <div v-if="scheduled.data.length > 0" class="divide-y divide-gray-100 dark:divide-gray-700">
                    <div v-for="item in scheduled.data" :key="item.id"
                        class="p-5 hover:bg-gray-50/50 dark:hover:bg-gray-700/30 transition-colors">
                        <div class="flex flex-col sm:flex-row sm:items-start justify-between gap-4">

                            <div class="flex-1 min-w-0">
                                <!-- Header -->
                                <div class="flex items-center gap-2 flex-wrap mb-2">
                                    <span :class="statusClass(item.status)">
                                        {{ item.status }}
                                    </span>
                                    <h3 class="font-bold text-gray-900 dark:text-white truncate tracking-tight">{{
                                        item.title }}
                                    </h3>
                                </div>

                                <p class="text-sm text-gray-600 dark:text-gray-400 line-clamp-2 leading-relaxed">{{
                                    item.message
                                    }}</p>

                                <!-- Meta -->
                                <div
                                    class="flex flex-wrap items-center gap-x-4 gap-y-2 mt-3 text-xs text-gray-500 dark:text-gray-400 font-medium">
                                    <span class="flex items-center gap-1.5">
                                        <svg class="w-3.5 h-3.5 text-gray-400" fill="none" stroke="currentColor"
                                            viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                                        </svg>
                                        {{ formatDateTime(item.scheduled_at) }}
                                    </span>

                                    <!-- Countdown -->
                                    <span v-if="item.status === 'pending' && countdowns[item.id]"
                                        class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full font-bold uppercase tracking-wide text-[10px]"
                                        :class="countdowns[item.id].class">
                                        <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                                        </svg>
                                        {{ countdowns[item.id].text }}
                                    </span>

                                    <span class="flex items-center gap-1.5">
                                        <svg class="w-3.5 h-3.5 text-gray-400" fill="none" stroke="currentColor"
                                            viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z" />
                                        </svg>
                                        {{ item.recipient_count }} recipients
                                    </span>
                                    <span v-if="item.send_push" class="flex items-center gap-1.5">
                                        <svg class="w-3.5 h-3.5 text-gray-400" fill="none" stroke="currentColor"
                                            viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9" />
                                        </svg>
                                        Push
                                    </span>
                                    <span v-if="item.save_database" class="flex items-center gap-1.5">
                                        <svg class="w-3.5 h-3.5 text-gray-400" fill="none" stroke="currentColor"
                                            viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M4 7v10c0 2.21 3.582 4 8 4s8-1.79 8-4V7M4 7c0 2.21 3.582 4 8 4s8-1.79 8-4M4 7c0-2.21 3.582-4 8-4s8 1.79 8 4" />
                                        </svg>
                                        DB
                                    </span>
                                    <span class="flex items-center gap-1.5">
                                        <svg class="w-3.5 h-3.5 text-gray-400" fill="none" stroke="currentColor"
                                            viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                                        </svg>
                                        {{ item.creator?.name || 'System' }}
                                    </span>
                                </div>

                                <!-- Sent info -->
                                <div v-if="item.status === 'sent'"
                                    class="mt-3 inline-flex items-center gap-2 px-3 py-1.5 rounded-xl bg-emerald-50 dark:bg-emerald-900/20 border border-emerald-100 dark:border-emerald-900/40 text-xs text-emerald-700 dark:text-emerald-400 font-medium">
                                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5"
                                            d="M5 13l4 4L19 7" />
                                    </svg>
                                    Sent {{ item.sent_at ? 'on ' + formatDateTime(item.sent_at) : '' }} ·
                                    {{ item.sent_count }} in-app, {{ item.push_sent_count }} push
                                </div>

                                <!-- Failure -->
                                <div v-if="item.status === 'failed' && item.failure_reason"
                                    class="mt-3 inline-flex items-start gap-2 px-3 py-1.5 rounded-xl bg-red-50 dark:bg-red-900/20 border border-red-100 dark:border-red-900/40 text-xs text-red-700 dark:text-red-400 font-medium">
                                    <svg class="w-3.5 h-3.5 flex-shrink-0 mt-0.5" fill="none" stroke="currentColor"
                                        viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                                    </svg>
                                    Failed: {{ item.failure_reason }}
                                </div>
                            </div>

                            <!-- Actions -->
                            <div class="flex flex-wrap gap-1.5 flex-shrink-0">
                                <template v-if="item.status === 'pending'">
                                    <button @click="sendNow(item)"
                                        class="inline-flex items-center gap-1.5 px-3 py-1.5 bg-emerald-50 dark:bg-emerald-900/30 text-emerald-700 dark:text-emerald-400 text-xs font-bold uppercase tracking-wide rounded-lg hover:bg-emerald-100 dark:hover:bg-emerald-900/50 transition-colors">
                                        Send now
                                    </button>
                                    <button @click="openReschedule(item)"
                                        class="inline-flex items-center gap-1.5 px-3 py-1.5 bg-blue-50 dark:bg-blue-900/30 text-blue-700 dark:text-blue-400 text-xs font-bold uppercase tracking-wide rounded-lg hover:bg-blue-100 dark:hover:bg-blue-900/50 transition-colors">
                                        Reschedule
                                    </button>
                                    <button @click="cancel(item)"
                                        class="inline-flex items-center gap-1.5 px-3 py-1.5 bg-amber-50 dark:bg-amber-900/30 text-amber-700 dark:text-amber-400 text-xs font-bold uppercase tracking-wide rounded-lg hover:bg-amber-100 dark:hover:bg-amber-900/50 transition-colors">
                                        Cancel
                                    </button>
                                </template>

                                <button @click="deleteItem(item)"
                                    class="inline-flex items-center gap-1.5 px-3 py-1.5 bg-red-50 dark:bg-red-900/30 text-red-700 dark:text-red-400 text-xs font-bold uppercase tracking-wide rounded-lg hover:bg-red-100 dark:hover:bg-red-900/50 transition-colors">
                                    Delete
                                </button>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Empty -->
                <div v-else class="p-16 text-center">
                    <div
                        class="w-20 h-20 rounded-2xl bg-gradient-to-br from-gray-100 to-gray-50 dark:from-gray-700 dark:to-gray-800 flex items-center justify-center mx-auto mb-4 shadow-sm">
                        <svg class="w-10 h-10 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                    </div>
                    <h3 class="text-xl font-bold text-gray-900 dark:text-white tracking-tight mb-2">No scheduled
                        notifications
                    </h3>
                    <p class="text-sm text-gray-500 dark:text-gray-400 mb-6">
                        Schedule a notification to see it here
                    </p>
                    <a href="/admin/notifications/create"
                        class="inline-flex items-center gap-2 px-6 py-3 bg-gradient-to-r from-primary-600 to-primary-700 text-white rounded-xl hover:from-primary-700 hover:to-primary-800 transition-all shadow-lg shadow-primary-500/25 hover:-translate-y-0.5 font-semibold text-sm">
                        Schedule notification
                    </a>
                </div>

                <!-- Pagination -->
                <div v-if="scheduled.links && scheduled.links.length > 3"
                    class="px-6 py-4 border-t border-gray-100 dark:border-gray-700">
                    <Pagination :links="scheduled.links" />
                </div>
            </div>
        </div>

        <!-- RESCHEDULE MODAL (inline, hand-rolled — kept from original) -->
        <div v-if="showRescheduleModal"
            class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/60 backdrop-blur-sm"
            @click.self="showRescheduleModal = false">
            <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-2xl max-w-md w-full p-6">
                <div class="flex items-center gap-3 mb-6">
                    <div
                        class="w-11 h-11 rounded-2xl bg-gradient-to-br from-primary-500 to-primary-700 flex items-center justify-center shadow-md">
                        <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                        </svg>
                    </div>
                    <div>
                        <h3 class="text-lg font-bold text-gray-900 dark:text-white tracking-tight">Reschedule
                            notification</h3>
                        <p class="text-xs text-gray-400 mt-0.5">Pick a new date and time</p>
                    </div>
                </div>

                <label
                    class="block text-[10px] font-bold text-gray-500 dark:text-gray-400 uppercase tracking-widest mb-2">
                    New send time
                </label>
                <input type="datetime-local" v-model="rescheduleForm.scheduled_at" :min="minDateTime"
                    class="w-full px-4 py-2.5 border border-gray-200 dark:border-gray-600 rounded-xl bg-white dark:bg-gray-900 text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-primary-500 focus:border-transparent transition-colors text-sm mb-5" />

                <div class="flex flex-col-reverse sm:flex-row justify-end gap-2">
                    <button @click="showRescheduleModal = false"
                        class="px-5 py-2.5 border border-gray-300 dark:border-gray-600 text-gray-700 dark:text-gray-300 rounded-xl hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors font-semibold text-sm">
                        Cancel
                    </button>
                    <button @click="submitReschedule" :disabled="rescheduleProcessing"
                        class="px-6 py-2.5 bg-gradient-to-r from-primary-600 to-primary-700 text-white rounded-xl hover:from-primary-700 hover:to-primary-800 transition-all shadow-lg shadow-primary-500/25 font-semibold text-sm disabled:opacity-50 disabled:cursor-not-allowed">
                        {{ rescheduleProcessing ? 'Saving…' : 'Reschedule' }}
                    </button>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>


<script setup>
    import { ref, computed, onMounted, onUnmounted } from 'vue';
    import { router } from '@inertiajs/vue3';
    import { useToast } from '@/composables/useToast';
    import { useConfirm } from '@/composables/useConfirm';
    import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
    import Pagination from '@/Components/Pagination.vue';
    import { useStatusBadge } from '@/composables/useStatusBadge';
    import PageHeader from '@/Components/PageHeader.vue';

    const props = defineProps({
        scheduled: Object,
        stats: Object,
    });

    const { error: showError } = useToast();
    const { confirm: confirmDialog } = useConfirm();
    const { notification: statusClass } = useStatusBadge();

    const showRescheduleModal = ref(false);
    const rescheduleTarget = ref(null);
    const rescheduleProcessing = ref(false);

    const rescheduleForm = ref({
        scheduled_at: '',
    });

    // Reactive clock — updates every second
    const currentTime = ref(new Date());
    let ticker;

    onMounted(() => {
        ticker = setInterval(() => {
            currentTime.value = new Date();
        }, 1000);
    });

    onUnmounted(() => {
        if (ticker) clearInterval(ticker);
    });

    // Computed countdown for all pending items
    const countdowns = computed(() => {
        if (!props.scheduled?.data) return {};

        const now = currentTime.value;
        const result = {};

        props.scheduled.data.forEach(item => {
            if (item.status === 'pending') {
                result[item.id] = computeCountdown(item.scheduled_at, now);
            }
        });

        return result;
    });

    const computeCountdown = (scheduledAt, now) => {
        const scheduled = new Date(scheduledAt);
        const diffMs = scheduled - now;

        if (diffMs <= 0) {
            return {
                text: 'Sending soon...',
                class: 'bg-red-100 text-red-700 dark:bg-red-900/30 dark:text-red-400 animate-pulse',
            };
        }

        const diffSec = Math.floor(diffMs / 1000);
        const diffMin = Math.floor(diffSec / 60);
        const diffHour = Math.floor(diffMin / 60);
        const diffDay = Math.floor(diffHour / 24);

        let text, badgeClass;

        if (diffSec < 60) {
            text = `in ${diffSec}s`;
            badgeClass = 'bg-red-100 text-red-700 dark:bg-red-900/30 dark:text-red-400 animate-pulse';
        } else if (diffMin < 5) {
            text = `in ${diffMin}m ${diffSec % 60}s`;
            badgeClass = 'bg-orange-100 text-orange-700 dark:bg-orange-900/30 dark:text-orange-400';
        } else if (diffMin < 60) {
            text = `in ${diffMin} min`;
            badgeClass = 'bg-amber-100 text-amber-700 dark:bg-amber-900/30 dark:text-amber-400';
        } else if (diffHour < 24) {
            const remMin = diffMin % 60;
            text = remMin > 0 ? `in ${diffHour}h ${remMin}m` : `in ${diffHour}h`;
            badgeClass = 'bg-blue-100 text-blue-700 dark:bg-blue-900/30 dark:text-blue-400';
        } else {
            const remHour = diffHour % 24;
            text = remHour > 0 ? `in ${diffDay}d ${remHour}h` : `in ${diffDay}d`;
            badgeClass = 'bg-gray-100 text-gray-600 dark:bg-gray-700 dark:text-gray-300';
        }

        return { text, class: badgeClass };
    };

    // ============== Format Helpers ==============
    const minDateTime = computed(() => {
        const now = new Date();
        now.setMinutes(now.getMinutes() + 5);
        const pad = (n) => n.toString().padStart(2, '0');
        return `${now.getFullYear()}-${pad(now.getMonth() + 1)}-${pad(now.getDate())}T${pad(now.getHours())}:${pad(now.getMinutes())}`;
    });

    const formatDateTime = (date) => {
        if (!date) return 'N/A';
        return new Date(date).toLocaleString('en-US', {
            month: 'short', day: 'numeric', year: 'numeric',
            hour: '2-digit', minute: '2-digit',
        });
    };

    // ============== Actions ==============
    const cancel = async (item) => {
        const confirmed = await confirmDialog({
            title: 'Cancel scheduled notification?',
            message: `"${item.title}" will not be sent. You can still reschedule it later if you change your mind.`,
            confirmText: 'Cancel Notification',
            cancelText: 'Keep',
            variant: 'warning',
        });

        if (!confirmed) return;

        router.post(`/admin/notifications/scheduled/${item.id}/cancel`, {}, {
            preserveScroll: true,
            // ✅ No success toast — the controller flashes
            //    'Scheduled notification cancelled.'
            onError: () => {
                showError('Action Failed ❌', 'Failed to cancel notification. Please try again.', { duration: 4000 });
            },
        });
    };

    const deleteItem = async (item) => {
        const confirmed = await confirmDialog({
            title: 'Delete scheduled notification?',
            message: `"${item.title}" will be permanently deleted. This action cannot be undone.`,
            confirmText: 'Delete',
            cancelText: 'Cancel',
            variant: 'danger',
        });

        if (!confirmed) return;

        router.delete(`/admin/notifications/scheduled/${item.id}`, {
            preserveScroll: true,
            // ✅ No success toast — the controller flashes
            onError: () => {
                showError('Delete Failed ❌', 'Failed to delete notification. Please try again.', { duration: 4000 });
            },
        });
    };

    const sendNow = async (item) => {
        const confirmed = await confirmDialog({
            title: 'Send notification now?',
            message: `"${item.title}" will be sent immediately to ${item.recipient_count} recipients. This cannot be undone.`,
            confirmText: 'Send Now',
            cancelText: 'Cancel',
            variant: 'primary',
        });

        if (!confirmed) return;

        router.post(`/admin/notifications/scheduled/${item.id}/send-now`, {}, {
            preserveScroll: true,
            // ✅ No success toast — the controller flashes
            onError: () => {
                showError('Send Failed ❌', 'Failed to send notification. Please try again.', { duration: 4000 });
            },
        });
    };

    const openReschedule = (item) => {
        rescheduleTarget.value = item;
        const d = new Date(item.scheduled_at);
        const pad = (n) => n.toString().padStart(2, '0');
        rescheduleForm.value.scheduled_at = `${d.getFullYear()}-${pad(d.getMonth() + 1)}-${pad(d.getDate())}T${pad(d.getHours())}:${pad(d.getMinutes())}`;
        showRescheduleModal.value = true;
    };

    const submitReschedule = () => {
        if (!rescheduleForm.value.scheduled_at) {
            showError('Error', 'Please pick a date and time.');
            return;
        }

        // Convert local to UTC before sending
        const localDate = new Date(rescheduleForm.value.scheduled_at);
        const utcString = localDate.toISOString().slice(0, 19).replace('T', ' ');

        rescheduleProcessing.value = true;
        router.post(`/admin/notifications/scheduled/${rescheduleTarget.value.id}/reschedule`, {
            scheduled_at: utcString,
        }, {
            preserveScroll: true,
            onSuccess: () => {
                // ✅ No success toast — the controller flashes
                //    'Notification rescheduled.'
                showRescheduleModal.value = false;
            },
            onError: () => {
                showError('Reschedule Failed ❌', 'Failed to reschedule notification. Please try again.', { duration: 4000 });
            },
            onFinish: () => {
                rescheduleProcessing.value = false;
            },
        });
    };
</script>