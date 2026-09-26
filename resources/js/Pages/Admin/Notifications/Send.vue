<template>
    <AuthenticatedLayout>

        <Head title="Send Notification" />

        <!-- COMPACT HEADER -->
        <PageHeader color="violet" :breadcrumb="[
            { label: 'Admin', href: '/admin/dashboard' },
            { label: 'Notifications', href: '/admin/notifications' },
            { label: 'Send' }
        ]">
            <template #icon>
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                    d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8" />
            </template>
            <template #title>Send a notification</template>
            <template #subtitle>Deliver a message to users via in-app and push notifications</template>
            <template #actions>
                <a href="/admin/notifications/scheduled"
                    class="inline-flex items-center gap-2 px-4 py-2 border border-gray-300 dark:border-gray-600 text-gray-700 dark:text-gray-300 rounded-xl hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors text-sm font-semibold">
                    View scheduled
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                    </svg>
                </a>
            </template>
        </PageHeader>

        <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 py-8 space-y-6">

            <!-- STATS -->
            <div class="grid grid-cols-2 sm:grid-cols-3 gap-4">
                <div
                    class="bg-white dark:bg-gray-800 rounded-2xl border border-gray-100 dark:border-gray-700 p-4 hover:shadow-md transition-shadow">
                    <p class="text-[10px] font-bold text-gray-400 dark:text-gray-500 uppercase tracking-widest">All
                        Users</p>
                    <p class="text-2xl font-bold text-primary-600 tracking-tight mt-1">{{ userCount }}</p>
                </div>
                <div
                    class="bg-white dark:bg-gray-800 rounded-2xl border border-gray-100 dark:border-gray-700 p-4 hover:shadow-md transition-shadow">
                    <p class="text-[10px] font-bold text-gray-400 dark:text-gray-500 uppercase tracking-widest">Admins
                    </p>
                    <p class="text-2xl font-bold text-blue-600 tracking-tight mt-1">{{ adminCount }}</p>
                </div>
                <div
                    class="bg-white dark:bg-gray-800 rounded-2xl border border-gray-100 dark:border-gray-700 p-4 hover:shadow-md transition-shadow">
                    <p class="text-[10px] font-bold text-gray-400 dark:text-gray-500 uppercase tracking-widest">Owners
                    </p>
                    <p class="text-2xl font-bold text-emerald-600 tracking-tight mt-1">{{ ownerCount }}</p>
                </div>
            </div>

            <!-- FORM CARD -->
            <div
                class="bg-white dark:bg-gray-800 rounded-2xl border border-gray-100 dark:border-gray-700 overflow-hidden">
                <div
                    class="px-6 py-4 border-b border-gray-100 dark:border-gray-700 bg-gradient-to-br from-gray-50 to-white dark:from-gray-800 dark:to-gray-800/50 flex items-center gap-3">
                    <div
                        class="w-9 h-9 rounded-xl bg-primary-50 dark:bg-primary-900/30 flex items-center justify-center">
                        <svg class="w-4 h-4 text-primary-600 dark:text-primary-400" fill="none" stroke="currentColor"
                            viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8" />
                        </svg>
                    </div>
                    <div>
                        <h3 class="text-sm font-bold text-gray-900 dark:text-white tracking-tight">Notification content
                        </h3>
                        <p class="text-xs text-gray-400">Compose your message</p>
                    </div>
                </div>

                <div class="p-6">
                    <form @submit.prevent="submit" class="space-y-6">

                        <!-- Title -->
                        <div>
                            <label for="title"
                                class="block text-[10px] font-bold text-gray-500 dark:text-gray-400 uppercase tracking-widest mb-2">
                                Title <span class="text-red-500">*</span>
                            </label>
                            <input id="title" v-model="form.title" type="text" maxlength="255" required
                                class="w-full px-4 py-2.5 border border-gray-200 dark:border-gray-600 rounded-xl bg-white dark:bg-gray-900 text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-primary-500 focus:border-transparent transition-colors text-sm"
                                placeholder="e.g., Important System Update" />
                            <div class="flex items-center justify-between mt-1.5">
                                <p v-if="form.errors.title" class="text-xs text-red-500 font-medium">{{
                                    form.errors.title }}</p>
                                <span class="text-xs text-gray-400 ml-auto font-medium">{{ form.title.length
                                }}/255</span>
                            </div>
                        </div>

                        <!-- Message -->
                        <div>
                            <label for="message"
                                class="block text-[10px] font-bold text-gray-500 dark:text-gray-400 uppercase tracking-widest mb-2">
                                Message <span class="text-red-500">*</span>
                            </label>
                            <textarea id="message" v-model="form.message" rows="5" maxlength="5000" required
                                class="w-full px-4 py-2.5 border border-gray-200 dark:border-gray-600 rounded-xl bg-white dark:bg-gray-900 text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-primary-500 focus:border-transparent transition-colors resize-none text-sm"
                                placeholder="Enter your notification message…"></textarea>
                            <div class="flex items-center justify-between mt-1.5">
                                <p v-if="form.errors.message" class="text-xs text-red-500 font-medium">{{
                                    form.errors.message }}
                                </p>
                                <span class="text-xs text-gray-400 ml-auto font-medium">{{ form.message.length
                                }}/5000</span>
                            </div>
                        </div>

                        <!-- Action URL -->
                        <div>
                            <label for="action_url"
                                class="block text-[10px] font-bold text-gray-500 dark:text-gray-400 uppercase tracking-widest mb-2">
                                Action URL <span class="text-gray-400 normal-case text-[10px]">(optional)</span>
                            </label>
                            <input id="action_url" v-model="form.action_url" type="url"
                                class="w-full px-4 py-2.5 border border-gray-200 dark:border-gray-600 rounded-xl bg-white dark:bg-gray-900 text-gray-900 dark:text-white placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-primary-500 focus:border-transparent transition-colors text-sm"
                                placeholder="https://example.com/page" />
                            <p class="text-xs text-gray-400 mt-1.5">
                                Users will be redirected here when clicking the notification
                            </p>
                            <p v-if="form.errors.action_url" class="text-xs text-red-500 font-medium mt-1">{{
                                form.errors.action_url }}</p>
                        </div>

                        <!-- Recipients -->
                        <div class="border-t border-gray-100 dark:border-gray-700 pt-6">
                            <label
                                class="block text-[10px] font-bold text-gray-500 dark:text-gray-400 uppercase tracking-widest mb-3">
                                Recipients <span class="text-red-500">*</span>
                            </label>
                            <div class="space-y-3">
                                <label
                                    class="flex items-start gap-3 p-4 rounded-xl border-2 transition-all cursor-pointer"
                                    :class="form.recipients.includes('all')
                                        ? 'border-primary-500 bg-primary-50/50 dark:bg-primary-900/20'
                                        : 'border-gray-200 dark:border-gray-700 hover:border-gray-300 dark:hover:border-gray-600'">
                                    <input v-model="form.recipients" type="checkbox" value="all"
                                        class="mt-0.5 rounded border-gray-300 text-primary-600 focus:ring-primary-500" />
                                    <div class="flex-1">
                                        <div class="flex items-center gap-2">
                                            <span class="text-sm font-bold text-gray-900 dark:text-white">All
                                                Users</span>
                                            <span class="text-xs text-gray-500 font-medium">({{ userCount }})</span>
                                        </div>
                                        <p class="text-xs text-gray-500 dark:text-gray-400 mt-0.5">Send to every user in
                                            the
                                            system</p>
                                    </div>
                                </label>

                                <label
                                    class="flex items-start gap-3 p-4 rounded-xl border-2 transition-all cursor-pointer"
                                    :class="form.recipients.includes('admins')
                                        ? 'border-primary-500 bg-primary-50/50 dark:bg-primary-900/20'
                                        : 'border-gray-200 dark:border-gray-700 hover:border-gray-300 dark:hover:border-gray-600'">
                                    <input v-model="form.recipients" type="checkbox" value="admins"
                                        class="mt-0.5 rounded border-gray-300 text-primary-600 focus:ring-primary-500" />
                                    <div class="flex-1">
                                        <div class="flex items-center gap-2">
                                            <span class="text-sm font-bold text-gray-900 dark:text-white">Admins &amp;
                                                Super
                                                Admins</span>
                                            <span class="text-xs text-gray-500 font-medium">({{ adminCount }})</span>
                                        </div>
                                        <p class="text-xs text-gray-500 dark:text-gray-400 mt-0.5">Only send to
                                            administrative
                                            users</p>
                                    </div>
                                </label>

                                <label
                                    class="flex items-start gap-3 p-4 rounded-xl border-2 transition-all cursor-pointer"
                                    :class="form.recipients.includes('owners')
                                        ? 'border-primary-500 bg-primary-50/50 dark:bg-primary-900/20'
                                        : 'border-gray-200 dark:border-gray-700 hover:border-gray-300 dark:hover:border-gray-600'">
                                    <input v-model="form.recipients" type="checkbox" value="owners"
                                        class="mt-0.5 rounded border-gray-300 text-primary-600 focus:ring-primary-500" />
                                    <div class="flex-1">
                                        <div class="flex items-center gap-2">
                                            <span class="text-sm font-bold text-gray-900 dark:text-white">Business
                                                Owners</span>
                                            <span class="text-xs text-gray-500 font-medium">({{ ownerCount }})</span>
                                        </div>
                                        <p class="text-xs text-gray-500 dark:text-gray-400 mt-0.5">Only send to business
                                            owners
                                        </p>
                                    </div>
                                </label>
                            </div>
                            <p v-if="form.errors.recipients" class="text-xs text-red-500 font-medium mt-2">{{
                                form.errors.recipients }}</p>
                            <p class="text-xs text-gray-500 mt-3 font-medium">
                                Selected: {{ form.recipients.length }} recipient group{{ form.recipients.length === 1 ?
                                    '' : 's'
                                }}
                            </p>
                        </div>

                        <!-- Scheduling -->
                        <div class="border-t border-gray-100 dark:border-gray-700 pt-6">
                            <h3
                                class="text-[10px] font-bold text-gray-500 dark:text-gray-400 uppercase tracking-widest mb-3 flex items-center gap-2">
                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                                </svg>
                                Scheduling
                            </h3>

                            <div class="space-y-3">
                                <label
                                    class="flex items-start gap-3 p-4 rounded-xl border-2 transition-all cursor-pointer"
                                    :class="sendMode === 'now'
                                        ? 'border-primary-500 bg-primary-50/50 dark:bg-primary-900/20'
                                        : 'border-gray-200 dark:border-gray-700 hover:border-gray-300 dark:hover:border-gray-600'">
                                    <input type="radio" v-model="sendMode" value="now"
                                        class="mt-0.5 text-primary-600 focus:ring-primary-500" />
                                    <div class="flex-1">
                                        <span class="text-sm font-bold text-gray-900 dark:text-white">Send
                                            Immediately</span>
                                        <p class="text-xs text-gray-500 dark:text-gray-400 mt-0.5">Deliver to recipients
                                            right
                                            now</p>
                                    </div>
                                </label>

                                <label
                                    class="flex items-start gap-3 p-4 rounded-xl border-2 transition-all cursor-pointer"
                                    :class="sendMode === 'schedule'
                                        ? 'border-primary-500 bg-primary-50/50 dark:bg-primary-900/20'
                                        : 'border-gray-200 dark:border-gray-700 hover:border-gray-300 dark:hover:border-gray-600'">
                                    <input type="radio" v-model="sendMode" value="schedule"
                                        class="mt-0.5 text-primary-600 focus:ring-primary-500" />
                                    <div class="flex-1">
                                        <span class="text-sm font-bold text-gray-900 dark:text-white">Schedule for
                                            Later</span>
                                        <p class="text-xs text-gray-500 dark:text-gray-400 mt-0.5">Pick a future date
                                            and time
                                        </p>
                                    </div>
                                </label>
                            </div>

                            <!-- Date/time picker (when scheduled) -->
                            <div v-if="sendMode === 'schedule'"
                                class="mt-3 pl-6 border-l-2 border-primary-200 dark:border-primary-800">
                                <label
                                    class="block text-[10px] font-bold text-gray-500 dark:text-gray-400 uppercase tracking-widest mb-2">
                                    Send Date &amp; Time
                                </label>
                                <input type="datetime-local" v-model="form.scheduled_at" :min="minDateTime"
                                    class="w-full px-4 py-2.5 border border-gray-200 dark:border-gray-600 rounded-xl bg-white dark:bg-gray-900 text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-primary-500 focus:border-transparent transition-colors text-sm" />
                                <p class="text-xs text-gray-400 mt-1.5">
                                    Notification will be sent automatically at this time
                                </p>
                                <p v-if="form.errors.scheduled_at" class="text-xs text-red-500 font-medium mt-1">{{
                                    form.errors.scheduled_at }}</p>

                                <!-- Quick presets -->
                                <div class="mt-3 flex flex-wrap gap-2">
                                    <button type="button" @click="setSchedulePreset(1, 'hours')"
                                        class="px-3 py-1.5 text-xs font-medium bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-300 rounded-lg hover:bg-gray-200 dark:hover:bg-gray-600 transition-colors">
                                        In 1 hour
                                    </button>
                                    <button type="button" @click="setSchedulePreset(3, 'hours')"
                                        class="px-3 py-1.5 text-xs font-medium bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-300 rounded-lg hover:bg-gray-200 dark:hover:bg-gray-600 transition-colors">
                                        In 3 hours
                                    </button>
                                    <button type="button" @click="setSchedulePreset(1, 'days')"
                                        class="px-3 py-1.5 text-xs font-medium bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-300 rounded-lg hover:bg-gray-200 dark:hover:bg-gray-600 transition-colors">
                                        Tomorrow
                                    </button>
                                    <button type="button" @click="setSchedulePreset(7, 'days')"
                                        class="px-3 py-1.5 text-xs font-medium bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-300 rounded-lg hover:bg-gray-200 dark:hover:bg-gray-600 transition-colors">
                                        In 1 week
                                    </button>
                                </div>
                            </div>
                        </div>

                        <!-- Delivery Options -->
                        <div class="border-t border-gray-100 dark:border-gray-700 pt-6">
                            <h3
                                class="text-[10px] font-bold text-gray-500 dark:text-gray-400 uppercase tracking-widest mb-3">
                                Delivery Options
                            </h3>

                            <div class="space-y-3">
                                <label
                                    class="flex items-start gap-3 p-4 rounded-xl border-2 transition-all cursor-pointer"
                                    :class="form.save_database
                                        ? 'border-primary-500 bg-primary-50/50 dark:bg-primary-900/20'
                                        : 'border-gray-200 dark:border-gray-700 hover:border-gray-300 dark:hover:border-gray-600'">
                                    <input v-model="form.save_database" type="checkbox"
                                        class="mt-0.5 rounded border-gray-300 text-primary-600 focus:ring-primary-500" />
                                    <div class="flex-1">
                                        <span class="text-sm font-bold text-gray-900 dark:text-white">Save to
                                            database</span>
                                        <p class="text-xs text-gray-500 dark:text-gray-400 mt-0.5">Users will see this
                                            in their
                                            notification list</p>
                                    </div>
                                </label>

                                <label
                                    class="flex items-start gap-3 p-4 rounded-xl border-2 transition-all cursor-pointer"
                                    :class="form.send_push
                                        ? 'border-primary-500 bg-primary-50/50 dark:bg-primary-900/20'
                                        : 'border-gray-200 dark:border-gray-700 hover:border-gray-300 dark:hover:border-gray-600'">
                                    <input v-model="form.send_push" type="checkbox"
                                        class="mt-0.5 rounded border-gray-300 text-primary-600 focus:ring-primary-500" />
                                    <div class="flex-1">
                                        <span class="text-sm font-bold text-gray-900 dark:text-white">Send push
                                            notifications</span>
                                        <p class="text-xs text-gray-500 dark:text-gray-400 mt-0.5">Real-time browser
                                            notifications</p>
                                    </div>
                                </label>
                            </div>
                        </div>

                        <!-- Submit -->
                        <div
                            class="border-t border-gray-100 dark:border-gray-700 pt-6 flex flex-col-reverse sm:flex-row items-stretch sm:items-center justify-between gap-3">
                            <button type="button" @click="preview"
                                class="px-5 py-2.5 text-sm font-semibold text-gray-700 dark:text-gray-300 bg-gray-100 dark:bg-gray-700 rounded-xl hover:bg-gray-200 dark:hover:bg-gray-600 transition-colors">
                                Preview
                            </button>
                            <button type="submit" :disabled="form.processing || form.recipients.length === 0"
                                class="inline-flex items-center justify-center gap-2 px-6 py-2.5 bg-gradient-to-r from-primary-600 to-primary-700 text-white rounded-xl hover:from-primary-700 hover:to-primary-800 transition-all shadow-lg shadow-primary-500/25 hover:-translate-y-0.5 font-semibold text-sm disabled:opacity-50 disabled:cursor-not-allowed disabled:hover:translate-y-0">
                                <span v-if="form.processing" class="inline-flex items-center gap-2">
                                    <svg class="animate-spin h-4 w-4" fill="none" viewBox="0 0 24 24">
                                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor"
                                            stroke-width="4"></circle>
                                        <path class="opacity-75" fill="currentColor"
                                            d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z">
                                        </path>
                                    </svg>
                                    Sending…
                                </span>
                                <span v-else>Send Notification</span>
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <!-- PREVIEW MODAL (inline, hand-rolled) -->
        <div v-if="showPreview"
            class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/60 backdrop-blur-sm"
            @click.self="showPreview = false">
            <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-2xl max-w-md w-full overflow-hidden">
                <div class="flex items-center gap-3 px-6 py-4 border-b border-gray-100 dark:border-gray-700">
                    <div
                        class="w-10 h-10 rounded-xl bg-gradient-to-br from-primary-500 to-primary-700 flex items-center justify-center shadow-md">
                        <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                        </svg>
                    </div>
                    <div class="flex-1">
                        <h3 class="text-base font-bold text-gray-900 dark:text-white tracking-tight">Preview
                            Notification</h3>
                        <p class="text-xs text-gray-400 mt-0.5">How recipients will see it</p>
                    </div>
                    <button @click="showPreview = false"
                        class="p-1.5 rounded-lg text-gray-400 hover:text-gray-600 dark:hover:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700 transition-colors">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>

                <div class="p-6">
                    <div
                        class="flex items-start gap-3 p-4 bg-blue-50 dark:bg-blue-900/20 rounded-2xl border border-blue-100 dark:border-blue-900/40">
                        <span class="text-2xl flex-shrink-0">📢</span>
                        <div class="min-w-0">
                            <h4 class="font-bold text-gray-900 dark:text-white tracking-tight truncate">
                                {{ form.title || 'Notification Title' }}
                            </h4>
                            <p class="text-sm text-gray-600 dark:text-gray-400 mt-1 leading-relaxed break-words">
                                {{ form.message || 'Notification message preview…' }}
                            </p>
                            <p v-if="form.action_url"
                                class="text-xs text-primary-600 dark:text-primary-400 mt-2 truncate font-medium">
                                Action: {{ form.action_url }}
                            </p>
                            <p class="text-xs text-gray-400 mt-2">From: Admin • Just now</p>
                        </div>
                    </div>
                </div>

                <div class="px-6 py-4 bg-gray-50 dark:bg-gray-900/50 border-t border-gray-100 dark:border-gray-700">
                    <button @click="showPreview = false"
                        class="w-full px-4 py-2.5 text-sm font-semibold text-gray-700 dark:text-gray-300 bg-white dark:bg-gray-800 border border-gray-300 dark:border-gray-600 rounded-xl hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors">
                        Close Preview
                    </button>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>


<script setup>
    import { ref, computed } from 'vue';
    import { Head, useForm } from '@inertiajs/vue3';
    import { useToast } from '@/composables/useToast';
    import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
    import PageHeader from '@/Components/PageHeader.vue';

    const props = defineProps({
        userCount: Number,
        adminCount: Number,
        ownerCount: Number,
        unreadCount: Number,
    });

    const { error } = useToast();

    const form = useForm({
        title: '',
        message: '',
        action_url: '',
        recipients: [],
        save_database: true,
        send_push: true,
        scheduled_at: '',
    });

    const showPreview = ref(false);
    const sendMode = ref('now');

    const minDateTime = computed(() => {
        const now = new Date();
        now.setMinutes(now.getMinutes() + 5);
        const pad = (n) => n.toString().padStart(2, '0');
        return `${now.getFullYear()}-${pad(now.getMonth() + 1)}-${pad(now.getDate())}T${pad(now.getHours())}:${pad(now.getMinutes())}`;
    });

    const setSchedulePreset = (amount, unit) => {
        const date = new Date();
        if (unit === 'hours') date.setHours(date.getHours() + amount);
        if (unit === 'days') date.setDate(date.getDate() + amount);
        const pad = (n) => n.toString().padStart(2, '0');
        form.scheduled_at = `${date.getFullYear()}-${pad(date.getMonth() + 1)}-${pad(date.getDate())}T${pad(date.getHours())}:${pad(date.getMinutes())}`;
    };

    const submit = () => {
        if (form.recipients.length === 0) {
            error('Error', 'Please select at least one recipient group');
            return;
        }

        if (sendMode.value === 'now') {
            form.scheduled_at = null;
        } else {
            if (!form.scheduled_at) {
                error('Error', 'Please pick a date and time to schedule');
                return;
            }

            const localDate = new Date(form.scheduled_at);
            const utcString = localDate.toISOString().slice(0, 19).replace('T', ' ');
            form.scheduled_at = utcString;
        }

        form.post('/admin/notifications/send', {
            preserveScroll: true,
            onSuccess: () => {
                // ✅ No success toast — the controller flashes
                //    'Notification sent to N users!' or
                //    'Notification scheduled for <date>!'
                form.reset();
                sendMode.value = 'now';
            },
            onError: (errors) => {
                // Client-side fallback. Inline errors render via form.errors.
                error('Error', Object.values(errors)[0] || 'Failed to send notification.');
            },
        });
    };

    const preview = () => {
        if (!form.title && !form.message) {
            error('Preview', 'Please enter a title and message to preview');
            return;
        }
        showPreview.value = true;
    };
</script>