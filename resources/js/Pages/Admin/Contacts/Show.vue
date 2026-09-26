<!-- resources/js/Pages/Admin/Contacts/Show.vue -->
<template>
    <AuthenticatedLayout>
        <template #header>
            <Breadcrumb :items="[
                { label: 'Admin', href: '/admin/dashboard' },
                { label: 'Messages', href: '/admin/contacts' },
                { label: contact.name }
            ]" />
        </template>

        <!-- HERO STRIP -->
        <div
            class="relative overflow-hidden bg-gradient-to-br from-primary-700 via-primary-600 to-primary-800 dark:from-gray-900 dark:via-gray-800 dark:to-gray-900 text-white">
            <div class="absolute inset-0 opacity-[0.06]"
                style="background-image: radial-gradient(circle, white 1px, transparent 1px); background-size: 22px 22px;">
            </div>
            <div class="absolute -top-16 -right-16 w-72 h-72 bg-primary-400/30 rounded-full blur-3xl"></div>

            <div class="relative max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 py-10">
                <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-6">
                    <div class="flex items-start gap-4 min-w-0">
                        <div
                            class="w-16 h-16 rounded-2xl bg-white/10 backdrop-blur-sm border border-white/20 flex items-center justify-center text-white text-2xl font-bold flex-shrink-0 shadow-lg">
                            {{ getInitials(contact.name) }}
                        </div>
                        <div class="min-w-0">
                            <h1 class="text-2xl md:text-3xl font-bold tracking-tight truncate">
                                {{ contact.name }}
                            </h1>
                            <p class="text-primary-100 dark:text-gray-300 mt-1 text-sm truncate">
                                {{ contact.email }}
                            </p>
                            <p class="text-primary-100 dark:text-gray-400 mt-0.5 text-xs">
                                {{ formatDate(contact.created_at) }}
                            </p>
                        </div>
                    </div>

                    <div class="flex items-center gap-2 flex-wrap">
                        <span :class="getStatusClass(contact.status) + ' backdrop-blur-sm'">
                            {{ contact.status }}
                        </span>
                        <Link href="/admin/contacts"
                            class="inline-flex items-center gap-2 px-4 py-2 border border-white/30 text-white rounded-xl hover:bg-white/10 transition-all text-sm font-medium">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M10 19l-7-7m0 0l7-7m-7 7h18" />
                            </svg>
                            Back
                        </Link>
                    </div>
                </div>
            </div>
        </div>

        <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
            <div class="bg-white dark:bg-gray-800 rounded-2xl border border-gray-100 dark:border-gray-700 overflow-hidden">
                <div
                    class="px-6 py-4 border-b border-gray-100 dark:border-gray-700 bg-gradient-to-br from-gray-50 to-white dark:from-gray-800 dark:to-gray-800/50 flex items-center gap-3">
                    <div class="w-9 h-9 rounded-xl bg-primary-50 dark:bg-primary-900/30 flex items-center justify-center">
                        <svg class="w-4 h-4 text-primary-600 dark:text-primary-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />
                        </svg>
                    </div>
                    <div>
                        <h3 class="text-sm font-bold text-gray-900 dark:text-white tracking-tight">Message content</h3>
                        <p class="text-xs text-gray-400 dark:text-gray-500">Submitted via the contact form</p>
                    </div>
                </div>

                <div class="p-6 space-y-5">
                    <!-- Subject -->
                    <div>
                        <p class="text-[10px] font-bold text-gray-400 dark:text-gray-500 uppercase tracking-widest">Subject</p>
                        <p class="text-lg font-bold text-gray-900 dark:text-white tracking-tight mt-1">{{ contact.subject }}</p>
                    </div>

                    <!-- Phone -->
                    <div v-if="contact.phone">
                        <p class="text-[10px] font-bold text-gray-400 dark:text-gray-500 uppercase tracking-widest">Phone</p>
                        <p class="text-sm text-gray-900 dark:text-white mt-1">{{ contact.phone }}</p>
                    </div>

                    <!-- Email -->
                    <div>
                        <p class="text-[10px] font-bold text-gray-400 dark:text-gray-500 uppercase tracking-widest">Email</p>
                        <a :href="`mailto:${contact.email}`"
                            class="text-sm text-primary-600 dark:text-primary-400 hover:text-primary-800 dark:hover:text-primary-300 transition-colors mt-1 inline-block font-medium">
                            {{ contact.email }}
                        </a>
                    </div>

                    <!-- Message -->
                    <div>
                        <p class="text-[10px] font-bold text-gray-400 dark:text-gray-500 uppercase tracking-widest">Message</p>
                        <div class="mt-2 p-5 bg-gray-50 dark:bg-gray-900/50 rounded-xl border border-gray-100 dark:border-gray-700">
                            <p class="text-sm text-gray-700 dark:text-gray-300 whitespace-pre-wrap leading-relaxed">{{ contact.message }}
                            </p>
                        </div>
                    </div>
                </div>

                <!-- Actions -->
                <div
                    class="px-6 py-5 border-t border-gray-100 dark:border-gray-700 bg-gradient-to-br from-gray-50 to-white dark:from-gray-800 dark:to-gray-800/50 flex flex-wrap gap-3">
                    <a :href="`mailto:${contact.email}?subject=Re: ${contact.subject}`" target="_blank"
                        class="inline-flex items-center gap-2 px-5 py-2.5 bg-gradient-to-r from-primary-600 to-primary-700 text-white rounded-xl hover:from-primary-700 hover:to-primary-800 transition-all shadow-lg shadow-primary-500/25 hover:-translate-y-0.5 font-semibold text-sm">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />
                        </svg>
                        Reply via email
                    </a>
                    <button v-if="contact.status !== 'replied'" @click="markReplied"
                        class="inline-flex items-center gap-2 px-5 py-2.5 bg-gradient-to-r from-emerald-500 to-emerald-600 text-white rounded-xl hover:from-emerald-600 hover:to-emerald-700 transition-all shadow-lg shadow-emerald-500/25 hover:-translate-y-0.5 font-semibold text-sm">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5"
                                d="M5 13l4 4L19 7" />
                        </svg>
                        Mark as replied
                    </button>
                    <button @click="deleteContact"
                        class="inline-flex items-center gap-2 px-5 py-2.5 bg-gradient-to-r from-red-500 to-red-600 text-white rounded-xl hover:from-red-600 hover:to-red-700 transition-all shadow-lg shadow-red-500/25 hover:-translate-y-0.5 font-semibold text-sm">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                        </svg>
                        Delete message
                    </button>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>

<script setup>
    import { router, Link } from '@inertiajs/vue3';
    import { useConfirm } from '@/composables/useConfirm';
    import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
    import Breadcrumb from '@/Components/Breadcrumb.vue';

    const props = defineProps({
        contact: Object,
    });

    const { confirm: confirmDialog } = useConfirm();

    const getInitials = (name) => {
        if (!name) return '?';
        return name.split(' ').map(n => n[0]).join('').toUpperCase().slice(0, 2);
    };

    const getStatusClass = (status) => {
        const classes = {
            unread: 'inline-flex items-center px-3 py-1 text-[10px] font-bold uppercase tracking-wide rounded-full bg-amber-100 dark:bg-amber-900/30 text-amber-700 dark:text-amber-400 border border-amber-200 dark:border-amber-800',
            read: 'inline-flex items-center px-3 py-1 text-[10px] font-bold uppercase tracking-wide rounded-full bg-blue-100 dark:bg-blue-900/30 text-blue-700 dark:text-blue-400 border border-blue-200 dark:border-blue-800',
            replied: 'inline-flex items-center px-3 py-1 text-[10px] font-bold uppercase tracking-wide rounded-full bg-emerald-100 dark:bg-emerald-900/30 text-emerald-700 dark:text-emerald-400 border border-emerald-200 dark:border-emerald-800',
        };
        return classes[status] || classes.unread;
    };

    const formatDate = (date) => {
        if (!date) return '';
        return new Date(date).toLocaleDateString('en-US', {
            year: 'numeric',
            month: 'short',
            day: 'numeric',
            hour: '2-digit',
            minute: '2-digit',
        });
    };

    const markReplied = async () => {
        const confirmed = await confirmDialog({
            title: 'Mark as replied?',
            message: 'This message will be marked as replied. Use this after you\'ve responded to the sender.',
            confirmText: 'Mark as Replied',
            cancelText: 'Cancel',
            variant: 'primary',
        });

        if (!confirmed) return;

        router.post(`/admin/contacts/${props.contact.id}/mark-replied`, {}, {
            preserveScroll: true,
        });
    };

    const deleteContact = async () => {
        const confirmed = await confirmDialog({
            title: 'Delete message?',
            message: `Message from "${props.contact.name}" will be permanently removed. This action cannot be undone.`,
            confirmText: 'Delete',
            cancelText: 'Cancel',
            variant: 'danger',
        });

        if (!confirmed) return;

        router.delete(`/admin/contacts/${props.contact.id}`, {
            preserveScroll: true,
        });
    };
</script>