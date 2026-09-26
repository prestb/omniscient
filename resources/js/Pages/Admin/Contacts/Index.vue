<!-- resources/js/Pages/Admin/Contacts/Index.vue -->
<template>
    <AuthenticatedLayout>
        <!-- COMPACT HEADER -->
        <PageHeader color="blue" :breadcrumb="[
            { label: 'Admin', href: '/admin/dashboard' },
            { label: 'Messages' }
        ]">
            <template #icon>
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                    d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />
            </template>
            <template #title>Contact messages</template>
            <template #subtitle>Manage customer inquiries</template>
            <template #actions>
                <a href="/admin/contacts/export"
                    class="inline-flex items-center gap-2 px-4 py-2 bg-gradient-to-r from-primary-600 to-primary-700 text-white rounded-xl font-semibold hover:from-primary-700 hover:to-primary-800 transition-all shadow-lg shadow-primary-500/25 text-sm">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4" />
                    </svg>
                    Export
                </a>
            </template>
        </PageHeader>

        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8 space-y-6">

            <!-- STATS -->
            <div class="grid grid-cols-2 sm:grid-cols-4 gap-4">
                <div class="bg-white dark:bg-gray-800 rounded-2xl border border-gray-100 dark:border-gray-700 p-4 hover:shadow-md transition-shadow">
                    <p class="text-[10px] font-bold text-gray-400 dark:text-gray-500 uppercase tracking-widest">Total</p>
                    <p class="text-2xl font-bold text-gray-900 dark:text-white tracking-tight mt-1">{{ stats.total }}</p>
                </div>
                <div class="bg-white dark:bg-gray-800 rounded-2xl border border-gray-100 dark:border-gray-700 p-4 hover:shadow-md transition-shadow">
                    <p class="text-[10px] font-bold text-gray-400 dark:text-gray-500 uppercase tracking-widest">Unread</p>
                    <p class="text-2xl font-bold text-amber-600 dark:text-amber-400 tracking-tight mt-1">{{ stats.unread }}</p>
                </div>
                <div class="bg-white dark:bg-gray-800 rounded-2xl border border-gray-100 dark:border-gray-700 p-4 hover:shadow-md transition-shadow">
                    <p class="text-[10px] font-bold text-gray-400 dark:text-gray-500 uppercase tracking-widest">Read</p>
                    <p class="text-2xl font-bold text-blue-600 dark:text-blue-400 tracking-tight mt-1">{{ stats.read }}</p>
                </div>
                <div class="bg-white dark:bg-gray-800 rounded-2xl border border-gray-100 dark:border-gray-700 p-4 hover:shadow-md transition-shadow">
                    <p class="text-[10px] font-bold text-gray-400 dark:text-gray-500 uppercase tracking-widest">Replied</p>
                    <p class="text-2xl font-bold text-emerald-600 dark:text-emerald-400 tracking-tight mt-1">{{ stats.replied }}</p>
                </div>
            </div>

            <!-- FILTERS -->
            <div class="bg-white dark:bg-gray-800 rounded-2xl border border-gray-100 dark:border-gray-700 p-5">
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
                    <div>
                        <label
                            class="block text-[10px] font-bold text-gray-400 dark:text-gray-500 uppercase tracking-widest mb-2">Search</label>
                        <div class="relative">
                            <div class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none">
                                <svg class="h-4 w-4 text-gray-400 dark:text-gray-500" fill="none" stroke="currentColor"
                                    viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                                </svg>
                            </div>
                            <input type="text" v-model="filters.search" @input="applyFilters"
                                placeholder="Search messages…"
                                class="w-full pl-11 pr-4 py-2.5 border border-gray-200 dark:border-gray-600 rounded-xl bg-white dark:bg-gray-900 text-gray-900 dark:text-white placeholder-gray-400 dark:placeholder-gray-500 focus:outline-none focus:ring-2 focus:ring-primary-500 focus:border-transparent transition-colors text-sm" />
                        </div>
                    </div>
                    <div>
                        <label
                            class="block text-[10px] font-bold text-gray-400 dark:text-gray-500 uppercase tracking-widest mb-2">Status</label>
                        <select v-model="filters.status" @change="applyFilters"
                            class="w-full px-4 py-2.5 border border-gray-200 dark:border-gray-600 rounded-xl bg-white dark:bg-gray-900 text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-primary-500 focus:border-transparent transition-colors text-sm appearance-none">
                            <option value="">All status</option>
                            <option value="unread">Unread</option>
                            <option value="read">Read</option>
                            <option value="replied">Replied</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-[10px] font-bold text-gray-400 dark:text-gray-500 uppercase tracking-widest mb-2">Date
                            from</label>
                        <input type="date" v-model="filters.date_from" @change="applyFilters"
                            class="w-full px-4 py-2.5 border border-gray-200 dark:border-gray-600 rounded-xl bg-white dark:bg-gray-900 text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-primary-500 focus:border-transparent transition-colors text-sm" />
                    </div>
                    <div>
                        <label class="block text-[10px] font-bold text-gray-400 dark:text-gray-500 uppercase tracking-widest mb-2">Date
                            to</label>
                        <input type="date" v-model="filters.date_to" @change="applyFilters"
                            class="w-full px-4 py-2.5 border border-gray-200 dark:border-gray-600 rounded-xl bg-white dark:bg-gray-900 text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-primary-500 focus:border-transparent transition-colors text-sm" />
                    </div>
                </div>

                <div class="mt-4 pt-4 border-t border-gray-100 dark:border-gray-700 flex items-center justify-between flex-wrap gap-3">
                    <button @click="resetFilters"
                        class="text-sm text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-200 font-semibold transition-colors">
                        Reset filters
                    </button>

                    <div v-if="selectedIds.length > 0" class="flex items-center gap-2">
                        <span class="text-xs font-bold uppercase tracking-wide text-gray-500 dark:text-gray-400">
                            {{ selectedIds.length }} selected
                        </span>
                        <button @click="bulkMarkRead"
                            class="inline-flex items-center gap-1.5 px-3 py-1.5 bg-blue-50 dark:bg-blue-900/30 text-blue-700 dark:text-blue-400 text-xs font-bold uppercase tracking-wide rounded-lg hover:bg-blue-100 dark:hover:bg-blue-900/50 transition-colors">
                            Mark as read
                        </button>
                        <button @click="bulkDelete"
                            class="inline-flex items-center gap-1.5 px-3 py-1.5 bg-red-50 dark:bg-red-900/30 text-red-700 dark:text-red-400 text-xs font-bold uppercase tracking-wide rounded-lg hover:bg-red-100 dark:hover:bg-red-900/50 transition-colors">
                            Delete
                        </button>
                    </div>
                </div>
            </div>

            <!-- TABLE -->
            <div class="bg-white dark:bg-gray-800 rounded-2xl border border-gray-100 dark:border-gray-700 overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                        <thead class="bg-gray-50 dark:bg-gray-900/50">
                            <tr>
                                <th class="w-12 px-6 py-4 text-left">
                                    <input type="checkbox" @click="selectAll" :checked="allSelected"
                                        class="rounded border-gray-300 dark:border-gray-600 text-primary-600 focus:ring-primary-500" />
                                </th>
                                <th
                                    class="px-6 py-4 text-left text-[10px] font-bold text-gray-500 dark:text-gray-400 uppercase tracking-widest">
                                    Status</th>
                                <th
                                    class="px-6 py-4 text-left text-[10px] font-bold text-gray-500 dark:text-gray-400 uppercase tracking-widest">
                                    Name</th>
                                <th
                                    class="px-6 py-4 text-left text-[10px] font-bold text-gray-500 dark:text-gray-400 uppercase tracking-widest">
                                    Email</th>
                                <th
                                    class="px-6 py-4 text-left text-[10px] font-bold text-gray-500 dark:text-gray-400 uppercase tracking-widest">
                                    Subject</th>
                                <th
                                    class="px-6 py-4 text-left text-[10px] font-bold text-gray-500 dark:text-gray-400 uppercase tracking-widest">
                                    Date</th>
                                <th
                                    class="px-6 py-4 text-right text-[10px] font-bold text-gray-500 dark:text-gray-400 uppercase tracking-widest">
                                    Actions</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                            <tr v-for="contact in contacts.data" :key="contact.id" class="transition-colors" :class="contact.status === 'unread'
                                ? 'bg-amber-50/40 dark:bg-amber-900/10 hover:bg-amber-50/60 dark:hover:bg-amber-900/20'
                                : 'hover:bg-gray-50/50 dark:hover:bg-gray-700/30'">
                                <td class="px-6 py-4">
                                    <input type="checkbox" v-model="selectedIds" :value="contact.id"
                                        class="rounded border-gray-300 dark:border-gray-600 text-primary-600 focus:ring-primary-500" />
                                </td>
                                <td class="px-6 py-4">
                                    <span :class="getStatusClass(contact.status)">
                                        {{ contact.status }}
                                    </span>
                                </td>
                                <td class="px-6 py-4">
                                    <div class="flex items-center gap-3 min-w-0">
                                        <div
                                            class="w-9 h-9 rounded-2xl bg-gradient-to-br from-primary-500 to-primary-700 flex items-center justify-center text-white text-xs font-bold flex-shrink-0 shadow-md">
                                            {{ getInitials(contact.name) }}
                                        </div>
                                        <span class="text-sm font-bold text-gray-900 dark:text-white truncate tracking-tight">{{
                                            contact.name
                                            }}</span>
                                    </div>
                                </td>
                                <td class="px-6 py-4">
                                    <a :href="`mailto:${contact.email}`"
                                        class="text-sm text-primary-600 dark:text-primary-400 hover:text-primary-800 dark:hover:text-primary-300 transition-colors truncate block max-w-xs">
                                        {{ contact.email }}
                                    </a>
                                </td>
                                <td class="px-6 py-4">
                                    <span class="text-sm text-gray-900 dark:text-white truncate block max-w-xs">{{ contact.subject
                                        }}</span>
                                </td>
                                <td class="px-6 py-4 text-sm text-gray-500 dark:text-gray-400 whitespace-nowrap">
                                    {{ formatDate(contact.created_at) }}
                                </td>
                                <td class="px-6 py-4">
                                    <div class="flex items-center justify-end gap-1.5">
                                        <Link :href="`/admin/contacts/${contact.id}`"
                                            class="inline-flex items-center gap-1.5 px-3 py-1.5 bg-blue-50 dark:bg-blue-900/30 text-blue-700 dark:text-blue-400 text-xs font-bold uppercase tracking-wide rounded-lg hover:bg-blue-100 dark:hover:bg-blue-900/50 transition-colors">
                                            View
                                        </Link>
                                        <button @click="deleteContact(contact)"
                                            class="inline-flex items-center gap-1.5 px-3 py-1.5 bg-red-50 dark:bg-red-900/30 text-red-700 dark:text-red-400 text-xs font-bold uppercase tracking-wide rounded-lg hover:bg-red-100 dark:hover:bg-red-900/50 transition-colors">
                                            Delete
                                        </button>
                                    </div>
                                </td>
                            </tr>
                            <tr v-if="!contacts.data || contacts.data.length === 0">
                                <td colspan="7" class="px-6 py-16 text-center">
                                    <div
                                        class="w-20 h-20 mx-auto mb-4 rounded-2xl bg-gradient-to-br from-gray-100 to-gray-50 dark:from-gray-700 dark:to-gray-800 flex items-center justify-center">
                                        <svg class="w-10 h-10 text-gray-400" fill="none" stroke="currentColor"
                                            viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />
                                        </svg>
                                    </div>
                                    <p class="text-gray-600 dark:text-gray-300 font-bold tracking-tight">No contact messages</p>
                                    <p class="text-sm text-gray-400 dark:text-gray-500 mt-1">Try adjusting your filters</p>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <div v-if="contacts.links && contacts.links.length > 3" class="px-6 py-4 border-t border-gray-100 dark:border-gray-700">
                    <Pagination :links="contacts.links" />
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>

<script setup>
    import { ref, reactive, computed } from 'vue';
    import { router, Link } from '@inertiajs/vue3';
    import { useToast } from '@/composables/useToast';
    import { useConfirm } from '@/composables/useConfirm';
    import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
    import Pagination from '@/Components/Pagination.vue';
    import PageHeader from '@/Components/PageHeader.vue';

    const props = defineProps({
        contacts: Object,
        stats: Object,
        filters: Object,
    });

    const { error } = useToast();
    const { confirm: confirmDialog } = useConfirm();

    const selectedIds = ref([]);

    const filters = reactive({
        search: props.filters?.search || '',
        status: props.filters?.status || '',
        date_from: props.filters?.date_from || '',
        date_to: props.filters?.date_to || '',
    });

    const allSelected = computed(() => {
        return props.contacts?.data?.length > 0
            && selectedIds.value.length === props.contacts.data.length;
    });

    const applyFilters = () => {
        router.get('/admin/contacts', filters, {
            preserveState: true,
            preserveScroll: true,
        });
    };

    const resetFilters = () => {
        filters.search = '';
        filters.status = '';
        filters.date_from = '';
        filters.date_to = '';
        applyFilters();
    };

    const selectAll = () => {
        if (selectedIds.value.length === props.contacts.data.length) {
            selectedIds.value = [];
        } else {
            selectedIds.value = props.contacts.data.map(c => c.id);
        }
    };

    const bulkDelete = async () => {
        if (selectedIds.value.length === 0) return;

        const confirmed = await confirmDialog({
            title: `Delete ${selectedIds.value.length} message${selectedIds.value.length === 1 ? '' : 's'}?`,
            message: 'Selected messages will be permanently removed. This action cannot be undone.',
            confirmText: 'Delete',
            cancelText: 'Cancel',
            variant: 'danger',
        });

        if (!confirmed) return;

        router.post('/admin/contacts/bulk-delete', { ids: selectedIds.value }, {
            preserveScroll: true,
            onSuccess: () => {
                selectedIds.value = [];
            },
            onError: () => {
                error('Delete Failed ❌', 'Failed to delete selected messages.', { duration: 4000 });
            },
        });
    };

    const bulkMarkRead = async () => {
        if (selectedIds.value.length === 0) return;

        const confirmed = await confirmDialog({
            title: `Mark ${selectedIds.value.length} message${selectedIds.value.length === 1 ? '' : 's'} as read?`,
            message: 'Selected messages will be marked as read.',
            confirmText: 'Mark as Read',
            cancelText: 'Cancel',
            variant: 'primary',
        });

        if (!confirmed) return;

        router.post('/admin/contacts/bulk-mark-read', { ids: selectedIds.value }, {
            preserveScroll: true,
            onSuccess: () => {
                selectedIds.value = [];
            },
            onError: () => {
                error('Action Failed ❌', 'Failed to mark messages as read.', { duration: 4000 });
            },
        });
    };

    const deleteContact = async (contact) => {
        const confirmed = await confirmDialog({
            title: 'Delete message?',
            message: `Message from "${contact.name}" will be permanently removed. This action cannot be undone.`,
            confirmText: 'Delete',
            cancelText: 'Cancel',
            variant: 'danger',
        });

        if (!confirmed) return;

        router.delete(`/admin/contacts/${contact.id}`, {
            preserveScroll: true,
            onError: () => {
                error('Delete Failed ❌', 'Failed to delete message.', { duration: 4000 });
            },
        });
    };

    const getStatusClass = (status) => {
        const classes = {
            unread: 'inline-flex items-center px-2.5 py-1 text-[10px] font-bold uppercase tracking-wide rounded-full bg-amber-100 dark:bg-amber-900/30 text-amber-700 dark:text-amber-400 border border-amber-200 dark:border-amber-800',
            read: 'inline-flex items-center px-2.5 py-1 text-[10px] font-bold uppercase tracking-wide rounded-full bg-blue-100 dark:bg-blue-900/30 text-blue-700 dark:text-blue-400 border border-blue-200 dark:border-blue-800',
            replied: 'inline-flex items-center px-2.5 py-1 text-[10px] font-bold uppercase tracking-wide rounded-full bg-emerald-100 dark:bg-emerald-900/30 text-emerald-700 dark:text-emerald-400 border border-emerald-200 dark:border-emerald-800',
        };
        return classes[status] || classes.unread;
    };

    const getInitials = (name) => {
        if (!name) return '?';
        return name.split(' ').map(n => n[0]).join('').toUpperCase().slice(0, 2);
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
</script>