<!-- resources/js/Pages/Owner/Leads/Show.vue -->
<template>
    <AuthenticatedLayout>
        <!-- COMPACT HEADER -->
        <PageHeader color="cyan" :breadcrumb="[
            { label: 'Dashboard', href: '/owner/dashboard' },
            { label: 'Leads', href: `${basePath}` },
            { label: lead.name }
        ]">
            <template #icon>
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                    d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
            </template>
            <template #title>{{ lead.name }}</template>
            <template #subtitle>Received {{ formatDate(lead.created_at) }}</template>
            <template #actions>
                <span :class="statusClass(lead.status)"
                    class="inline-flex items-center px-3 py-1.5 text-xs font-bold uppercase tracking-wide rounded-full border border-gray-200 dark:border-gray-600">
                    {{ lead.status }}
                </span>
                <a :href="`${basePath}`"
                    class="inline-flex items-center gap-2 px-4 py-2 border border-gray-300 dark:border-gray-600 text-gray-700 dark:text-gray-300 rounded-xl hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors text-sm font-semibold">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M10 19l-7-7m0 0l7-7m-7 7h18" />
                    </svg>
                    Back
                </a>
            </template>
        </PageHeader>

        <div class="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8 py-8 space-y-6">

            <!-- CONTACT INFORMATION -->
            <div
                class="bg-white dark:bg-gray-800 rounded-2xl border border-gray-100 dark:border-gray-700 overflow-hidden">
                <div
                    class="px-6 py-4 border-b border-gray-100 dark:border-gray-700 bg-gradient-to-br from-gray-50 to-white dark:from-gray-800 dark:to-gray-800/50 flex items-center gap-3">
                    <div class="w-9 h-9 rounded-xl bg-blue-50 dark:bg-blue-900/30 flex items-center justify-center">
                        <svg class="w-4 h-4 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                        </svg>
                    </div>
                    <div>
                        <h3 class="text-sm font-bold text-gray-900 dark:text-white tracking-tight">Contact information
                        </h3>
                        <p class="text-xs text-gray-400">How to reach this lead</p>
                    </div>
                </div>

                <div class="p-6 grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <a v-if="lead.email" :href="`mailto:${lead.email}`"
                        class="flex items-center gap-3 p-4 bg-gray-50 dark:bg-gray-900/50 rounded-xl hover:bg-blue-50 dark:hover:bg-blue-900/20 transition-colors group border border-gray-100 dark:border-gray-700">
                        <div
                            class="w-10 h-10 bg-blue-100 dark:bg-blue-900/40 rounded-xl flex items-center justify-center flex-shrink-0">
                            <svg class="w-4 h-4 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />
                            </svg>
                        </div>
                        <div class="min-w-0">
                            <p class="text-[10px] uppercase tracking-widest text-gray-400 font-bold">Email</p>
                            <p class="text-sm font-semibold text-gray-900 dark:text-white truncate">{{ lead.email }}</p>
                        </div>
                    </a>

                    <a v-if="lead.phone" :href="`tel:${lead.phone}`"
                        class="flex items-center gap-3 p-4 bg-gray-50 dark:bg-gray-900/50 rounded-xl hover:bg-emerald-50 dark:hover:bg-emerald-900/20 transition-colors group border border-gray-100 dark:border-gray-700">
                        <div
                            class="w-10 h-10 bg-emerald-100 dark:bg-emerald-900/40 rounded-xl flex items-center justify-center flex-shrink-0">
                            <svg class="w-4 h-4 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z" />
                            </svg>
                        </div>
                        <div class="min-w-0">
                            <p class="text-[10px] uppercase tracking-widest text-gray-400 font-bold">Phone</p>
                            <p class="text-sm font-semibold text-gray-900 dark:text-white truncate">{{ lead.phone }}</p>
                        </div>
                    </a>
                </div>
            </div>

            <!-- MESSAGE -->
            <div
                class="bg-white dark:bg-gray-800 rounded-2xl border border-gray-100 dark:border-gray-700 overflow-hidden">
                <div
                    class="px-6 py-4 border-b border-gray-100 dark:border-gray-700 bg-gradient-to-br from-gray-50 to-white dark:from-gray-800 dark:to-gray-800/50 flex items-center gap-3">
                    <div class="w-9 h-9 rounded-xl bg-purple-50 dark:bg-purple-900/30 flex items-center justify-center">
                        <svg class="w-4 h-4 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z" />
                        </svg>
                    </div>
                    <div>
                        <h3 class="text-sm font-bold text-gray-900 dark:text-white tracking-tight">
                            {{ lead.subject || 'Message' }}
                        </h3>
                        <p class="text-xs text-gray-400">Customer inquiry</p>
                    </div>
                </div>
                <div class="p-6">
                    <div
                        class="p-5 bg-gray-50 dark:bg-gray-900/50 rounded-xl border border-gray-100 dark:border-gray-700">
                        <p class="text-gray-700 dark:text-gray-300 whitespace-pre-wrap leading-relaxed text-sm">{{
                            lead.message
                        }}</p>
                    </div>
                </div>
            </div>

            <!-- OWNER NOTES -->
            <div
                class="bg-white dark:bg-gray-800 rounded-2xl border border-gray-100 dark:border-gray-700 overflow-hidden">
                <div
                    class="px-6 py-4 border-b border-gray-100 dark:border-gray-700 bg-gradient-to-br from-gray-50 to-white dark:from-gray-800 dark:to-gray-800/50 flex items-center gap-3">
                    <div class="w-9 h-9 rounded-xl bg-amber-50 dark:bg-amber-900/30 flex items-center justify-center">
                        <svg class="w-4 h-4 text-amber-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                        </svg>
                    </div>
                    <div>
                        <h3 class="text-sm font-bold text-gray-900 dark:text-white tracking-tight">Your notes</h3>
                        <p class="text-xs text-gray-400">Private — only visible to you</p>
                    </div>
                </div>
                <div class="p-6">
                    <textarea v-model="notesForm.owner_notes" rows="3" placeholder="Add private notes about this lead…"
                        class="w-full px-4 py-3 border border-gray-200 dark:border-gray-600 rounded-xl bg-white dark:bg-gray-900 text-gray-900 dark:text-white placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-primary-500 focus:border-transparent transition-colors resize-none text-sm"></textarea>
                    <button @click="saveNotes" :disabled="notesProcessing"
                        class="mt-3 px-5 py-2.5 bg-gray-900 dark:bg-white text-white dark:text-gray-900 rounded-xl text-sm font-semibold hover:bg-gray-800 dark:hover:bg-gray-100 transition-colors disabled:opacity-50 disabled:cursor-not-allowed">
                        {{ notesProcessing ? 'Saving…' : 'Save notes' }}
                    </button>
                </div>
            </div>

            <!-- ACTIONS -->
            <div
                class="bg-white dark:bg-gray-800 rounded-2xl border border-gray-100 dark:border-gray-700 overflow-hidden">
                <div
                    class="px-6 py-4 border-b border-gray-100 dark:border-gray-700 bg-gradient-to-br from-gray-50 to-white dark:from-gray-800 dark:to-gray-800/50 flex items-center gap-3">
                    <div class="w-9 h-9 rounded-xl bg-gray-100 dark:bg-gray-700 flex items-center justify-center">
                        <svg class="w-4 h-4 text-gray-600 dark:text-gray-300" fill="none" stroke="currentColor"
                            viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M13 10V3L4 14h7v7l9-11h-7z" />
                        </svg>
                    </div>
                    <div>
                        <h3 class="text-sm font-bold text-gray-900 dark:text-white tracking-tight">Actions</h3>
                        <p class="text-xs text-gray-400">Update lead status or remove</p>
                    </div>
                </div>
                <div class="p-6 flex flex-wrap gap-3">
                    <button v-if="lead.status !== 'replied'" @click="updateStatus('replied')"
                        class="inline-flex items-center gap-2 px-5 py-2.5 bg-gradient-to-r from-emerald-500 to-emerald-600 text-white rounded-xl hover:from-emerald-600 hover:to-emerald-700 transition-all shadow-lg shadow-emerald-500/25 hover:shadow-emerald-500/40 hover:-translate-y-0.5 font-semibold text-sm">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5"
                                d="M5 13l4 4L19 7" />
                        </svg>
                        Mark as replied
                    </button>
                    <button v-if="lead.status !== 'archived'" @click="updateStatus('archived')"
                        class="inline-flex items-center gap-2 px-5 py-2.5 bg-gray-700 dark:bg-gray-600 text-white rounded-xl hover:bg-gray-800 dark:hover:bg-gray-500 transition-all shadow-lg shadow-gray-500/25 hover:-translate-y-0.5 font-semibold text-sm">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M5 8h14M5 8a2 2 0 110-4h14a2 2 0 110 4M5 8v10a2 2 0 002 2h10a2 2 0 002-2V8m-9 4h4" />
                        </svg>
                        Archive
                    </button>
                    <button @click="deleteLead"
                        class="inline-flex items-center gap-2 px-5 py-2.5 bg-red-50 dark:bg-red-900/20 text-red-600 dark:text-red-400 rounded-xl hover:bg-red-100 dark:hover:bg-red-900/30 transition-colors font-semibold text-sm">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                        </svg>
                        Delete lead
                    </button>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>

<script setup>
    import { ref, computed } from 'vue';
    import { router, useForm } from '@inertiajs/vue3';
    import { useToast } from '@/composables/useToast';
    import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
    import PageHeader from '@/Components/PageHeader.vue';
    import { useConfirm } from '@/composables/useConfirm';

    const { error } = useToast();
    const { confirm: confirmDialog } = useConfirm();

    const props = defineProps({
        business: Object,
        listing: Object,
        lead: Object,
    });

    /*
     * PHASE 12B — owner inquiry access is Listing-scoped. `business` is null for
     * a Listing with no organization, so links resolve against the Listing.
     */
    const basePath = computed(() =>
        props.listing
            ? `/owner/listings/${props.listing.id}/leads`
            : `/owner/businesses/${props.business.id}/leads`
    );

    const notesForm = useForm({
        owner_notes: props.lead.owner_notes || '',
    });

    const notesProcessing = ref(false);

    const getInitials = (name) => {
        if (!name) return '?';
        return name.split(' ').map(n => n[0]).join('').toUpperCase().slice(0, 2);
    };

    const formatDate = (date) => {
        if (!date) return '';
        return new Date(date).toLocaleDateString('en-US', {
            year: 'numeric',
            month: 'long',
            day: 'numeric',
            hour: '2-digit',
            minute: '2-digit',
        });
    };

    const statusClass = (status) => {
        const classes = {
            new: 'bg-blue-100 dark:bg-blue-900/30 text-blue-700 dark:text-blue-400',
            read: 'bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-300',
            replied: 'bg-emerald-100 dark:bg-emerald-900/30 text-emerald-700 dark:text-emerald-400',
            archived: 'bg-amber-100 dark:bg-amber-900/30 text-amber-700 dark:text-amber-400',
        };
        return classes[status] || classes.read;
    };

    const updateStatus = (status) => {
        router.put(`${basePath}/${props.lead.id}/status`, { status }, {
            preserveScroll: true,
        });
    };

    const saveNotes = () => {
        notesProcessing.value = true;
        notesForm.put(`${basePath}/${props.lead.id}/notes`, {
            preserveScroll: true,
            onFinish: () => {
                notesProcessing.value = false;
            },
        });
    };

    const deleteLead = async () => {
        const confirmed = await confirmDialog({
            title: 'Delete this lead?',
            message: `"${props.lead.name}"'s inquiry will be permanently removed. This action cannot be undone.`,
            confirmText: 'Delete Lead',
            cancelText: 'Cancel',
            variant: 'danger',
        });

        if (!confirmed) return;

        router.delete(`${basePath}/${props.lead.id}`, {
            preserveScroll: true,
            // ✅ No success toast — the controller flashes 'Lead deleted.'
            //    and AuthenticatedLayout shows it once. The controller
            //    also redirects to the route owner.businesses.leads.index
            //    (owner/businesses/{business}/leads), so the user lands on
            //    the index (not a ghost page).
            onError: () => {
                // Client-side fallback for network / auth errors.
                error('Delete Failed ❌', 'Failed to delete lead. Please try again.', { duration: 4000 });
            },
        });
    };
</script>