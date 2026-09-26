<!-- resources/js/Pages/Admin/Owners/Edit.vue -->
<template>
    <AuthenticatedLayout>

        <!-- COMPACT HEADER -->
        <PageHeader color="teal" :breadcrumb="[
            { label: 'Admin', href: '/admin/dashboard' },
            { label: 'Owners', href: '/admin/owners' },
            { label: owner.name, href: `/admin/owners/${owner.id}` },
            { label: 'Edit' }
        ]">
            <template #icon>
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                    d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
            </template>
            <template #title>Edit {{ owner.name }}</template>
            <template #subtitle>ID #{{ owner.id }}</template>
            <template #actions>
                <span :class="statusClass(owner.status)">
                    {{ owner.status.charAt(0).toUpperCase() + owner.status.slice(1) }}
                </span>
                <a href="/admin/owners"
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

            <!-- SUMMARY CARD -->
            <div class="bg-white dark:bg-gray-800 rounded-2xl border border-gray-100 dark:border-gray-700 p-6 shadow-sm">
                <div class="flex items-center gap-4">
                    <div
                        class="w-16 h-16 rounded-2xl bg-gradient-to-br from-primary-500 to-primary-700 flex items-center justify-center text-white text-2xl font-bold flex-shrink-0 shadow-md">
                        {{ getInitials(owner.name) }}
                    </div>
                    <div class="min-w-0 flex-1">
                        <h3 class="text-lg font-bold text-gray-900 dark:text-white truncate tracking-tight">{{ owner.name }}</h3>
                        <p class="text-sm text-gray-500 dark:text-gray-400 truncate">{{ owner.email }}</p>
                        <p v-if="owner.phone" class="text-sm text-gray-400 dark:text-gray-500 mt-0.5">{{ owner.phone }}</p>
                        <div class="flex items-center gap-3 mt-2 flex-wrap">
                            <span class="text-xs text-gray-400 dark:text-gray-500">Joined {{ formatDate(owner.created_at) }}</span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- EDIT FORM -->
            <div class="bg-white dark:bg-gray-800 rounded-2xl border border-gray-100 dark:border-gray-700 overflow-hidden">
                <div
                    class="px-6 py-4 border-b border-gray-100 dark:border-gray-700 bg-gradient-to-br from-gray-50 to-white dark:from-gray-800 dark:to-gray-800/50 flex items-center gap-3">
                    <div class="w-9 h-9 rounded-xl bg-primary-50 dark:bg-primary-900/30 flex items-center justify-center">
                        <svg class="w-4 h-4 text-primary-600 dark:text-primary-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                        </svg>
                    </div>
                    <div>
                        <h3 class="text-sm font-bold text-gray-900 dark:text-white tracking-tight">Owner information</h3>
                        <p class="text-xs text-gray-400 dark:text-gray-500">Fields marked * are required</p>
                    </div>
                </div>

                <div class="p-6">
                    <form @submit.prevent="submit" class="space-y-5">
                        <!-- Name -->
                        <div>
                            <label class="block text-[10px] font-bold text-gray-500 dark:text-gray-400 uppercase tracking-widest mb-2">
                                Full name <span class="text-red-500">*</span>
                            </label>
                            <input type="text" v-model="form.name" required
                                class="w-full px-4 py-2.5 border border-gray-200 dark:border-gray-600 rounded-xl bg-white dark:bg-gray-900 text-gray-900 dark:text-white placeholder-gray-400 dark:placeholder-gray-500 focus:outline-none focus:ring-2 focus:ring-primary-500 focus:border-transparent transition-colors text-sm"
                                placeholder="John Doe" />
                            <p v-if="form.errors.name" class="text-red-500 dark:text-red-400 text-xs font-medium mt-1.5">{{ form.errors.name }}</p>
                        </div>

                        <!-- Email -->
                        <div>
                            <label class="block text-[10px] font-bold text-gray-500 dark:text-gray-400 uppercase tracking-widest mb-2">
                                Email <span class="text-red-500">*</span>
                            </label>
                            <input type="email" v-model="form.email" required
                                class="w-full px-4 py-2.5 border border-gray-200 dark:border-gray-600 rounded-xl bg-white dark:bg-gray-900 text-gray-900 dark:text-white placeholder-gray-400 dark:placeholder-gray-500 focus:outline-none focus:ring-2 focus:ring-primary-500 focus:border-transparent transition-colors text-sm"
                                placeholder="john@example.com" />
                            <p v-if="form.errors.email" class="text-red-500 dark:text-red-400 text-xs font-medium mt-1.5">{{ form.errors.email }}
                            </p>
                        </div>

                        <!-- Phone -->
                        <div>
                            <label class="block text-[10px] font-bold text-gray-500 dark:text-gray-400 uppercase tracking-widest mb-2">
                                Phone
                            </label>
                            <input type="text" v-model="form.phone"
                                class="w-full px-4 py-2.5 border border-gray-200 dark:border-gray-600 rounded-xl bg-white dark:bg-gray-900 text-gray-900 dark:text-white placeholder-gray-400 dark:placeholder-gray-500 focus:outline-none focus:ring-2 focus:ring-primary-500 focus:border-transparent transition-colors text-sm"
                                placeholder="+237 699 123 456" />
                            <p v-if="form.errors.phone" class="text-red-500 dark:text-red-400 text-xs font-medium mt-1.5">{{ form.errors.phone }}
                            </p>
                        </div>

                        <!-- Status -->
                        <div>
                            <label class="block text-[10px] font-bold text-gray-500 dark:text-gray-400 uppercase tracking-widest mb-2">
                                Status <span class="text-red-500">*</span>
                            </label>
                            <select v-model="form.status" required
                                class="w-full px-4 py-2.5 border border-gray-200 dark:border-gray-600 rounded-xl bg-white dark:bg-gray-900 text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-primary-500 focus:border-transparent transition-colors text-sm appearance-none">
                                <option value="pending">Pending</option>
                                <option value="active">Active</option>
                                <option value="suspended">Suspended</option>
                            </select>
                            <p v-if="form.errors.status" class="text-red-500 dark:text-red-400 text-xs font-medium mt-1.5">{{ form.errors.status }}
                            </p>
                        </div>

                        <!-- Actions -->
                        <div class="flex flex-col-reverse sm:flex-row justify-end gap-3 pt-6 border-t border-gray-100 dark:border-gray-700">
                            <a href="/admin/owners"
                                class="px-6 py-2.5 border border-gray-300 dark:border-gray-600 text-gray-700 dark:text-gray-300 rounded-xl hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors font-semibold text-sm text-center">
                                Cancel
                            </a>
                            <button type="submit" :disabled="form.processing"
                                class="inline-flex items-center justify-center gap-2 px-6 py-2.5 bg-gradient-to-r from-primary-600 to-primary-700 text-white rounded-xl hover:from-primary-700 hover:to-primary-800 transition-all shadow-lg shadow-primary-500/25 hover:-translate-y-0.5 font-semibold text-sm disabled:opacity-50 disabled:cursor-not-allowed disabled:hover:translate-y-0">
                                <span v-if="form.processing" class="flex items-center gap-2">
                                    <svg class="animate-spin h-4 w-4" fill="none" viewBox="0 0 24 24">
                                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor"
                                            stroke-width="4"></circle>
                                        <path class="opacity-75" fill="currentColor"
                                            d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z">
                                        </path>
                                    </svg>
                                    Saving…
                                </span>
                                <span v-else class="flex items-center gap-2">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5"
                                            d="M5 13l4 4L19 7" />
                                    </svg>
                                    Update Owner
                                </span>
                            </button>
                        </div>
                    </form>
                </div>
            </div>

            <!-- DANGER ZONE -->
            <div class="bg-gradient-to-br from-red-50 to-red-50/50 dark:from-red-900/20 dark:to-red-900/10 border border-red-200 dark:border-red-800 rounded-2xl p-6">
                <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                    <div class="flex items-start gap-4">
                        <div class="w-11 h-11 rounded-xl bg-red-100 dark:bg-red-900/40 flex items-center justify-center flex-shrink-0">
                            <svg class="w-5 h-5 text-red-600 dark:text-red-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                            </svg>
                        </div>
                        <div>
                            <h4 class="text-sm font-bold text-red-900 dark:text-red-400 tracking-tight">Delete this owner</h4>
                            <p class="text-xs text-red-700 dark:text-red-300 mt-1">
                                This will also delete all businesses owned by this user. Cannot be undone.
                            </p>
                        </div>
                    </div>
                    <button @click="deleteOwner"
                        class="inline-flex items-center gap-2 px-6 py-2.5 bg-gradient-to-r from-red-500 to-red-600 text-white rounded-xl hover:from-red-600 hover:to-red-700 transition-all shadow-lg shadow-red-500/25 hover:-translate-y-0.5 font-semibold text-sm whitespace-nowrap flex-shrink-0">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                        </svg>
                        Delete Owner
                    </button>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>


<script setup>
    import { router, useForm } from '@inertiajs/vue3';
    import { useToast } from '@/composables/useToast';
    import { useConfirm } from '@/composables/useConfirm';
    import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
    import { useStatusBadge } from '@/composables/useStatusBadge';
    import PageHeader from '@/Components/PageHeader.vue';

    const props = defineProps({
        owner: Object,
    });

    const { error } = useToast();
    const { confirm: confirmDialog } = useConfirm();
    const { user: statusClass } = useStatusBadge();

    // ✅ useForm — populates form.errors automatically and gives form.processing
    const form = useForm({
        name: props.owner.name || '',
        email: props.owner.email || '',
        phone: props.owner.phone || '',
        status: props.owner.status || 'active',
    });

    const getInitials = (name) => {
        if (!name) return '?';
        return name.split(' ').map(n => n[0]).join('').toUpperCase().slice(0, 2);
    };

    const formatDate = (date) => {
        if (!date) return 'N/A';
        return new Date(date).toLocaleDateString('en-US', {
            year: 'numeric',
            month: 'short',
            day: 'numeric'
        });
    };

    const submit = () => {
        form.put(`/admin/owners/${props.owner.id}`, {
            preserveScroll: true,
            // ✅ No success toast — the controller flashes
            //    'Owner updated successfully.' and redirects to
            //    /admin/owners, where the toast fires on the index.
            //
            // ✅ No router.visit() — the server redirect already navigates.
            onError: (errors) => {
                // Client-side fallback. Inline errors render via form.errors.
                const firstError = Object.values(errors)[0];
                error('Update Failed ❌', firstError || 'Failed to update owner. Please check the form and try again.', { duration: 5000 });
            },
        });
    };

    const deleteOwner = async () => {
        const confirmed = await confirmDialog({
            title: 'Delete owner?',
            message: `"${props.owner.name}" will be deleted along with all their businesses. Note: owners with active subscriptions cannot be deleted.`,
            confirmText: 'Delete Owner',
            cancelText: 'Cancel',
            variant: 'danger',
        });

        if (!confirmed) return;

        router.delete(`/admin/owners/${props.owner.id}`, {
            preserveScroll: true,
            // ✅ No success toast — the controller flashes
            //    'Owner deleted successfully.' and redirects to
            //    /admin/owners.
            //
            // ✅ No router.visit() — the server redirect already navigates.
            onError: () => {
                error('Delete Failed ❌', 'Failed to delete owner. Please try again.', { duration: 4000 });
            },
        });
    };
</script>