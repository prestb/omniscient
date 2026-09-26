<!-- resources/js/Pages/Owner/Contacts/Index.vue -->
<template>
    <AuthenticatedLayout>
        <!-- COMPACT HEADER -->
        <PageHeader color="blue" :breadcrumb="[
            { label: 'Dashboard', href: '/owner/dashboard' },
            { label: 'Businesses', href: '/owner/businesses' },
            { label: business.name, href: `/owner/businesses/${business.id}/edit` },
            { label: 'Contacts' }
        ]">
            <template #icon>
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                    d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z" />
            </template>
            <template #title>Contacts</template>
            <template #subtitle>{{ business.name }}</template>
            <template #actions>
                <button @click="openCreateModal"
                    class="inline-flex items-center gap-2 px-4 py-2 bg-gradient-to-r from-primary-600 to-primary-700 text-white rounded-xl font-semibold hover:from-primary-700 hover:to-primary-800 transition-all shadow-lg shadow-primary-500/25 text-sm">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                    </svg>
                    Add Contact
                </button>
            </template>
        </PageHeader>

        <div class="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8 py-8 space-y-6">

            <!-- CONTACTS LIST -->
            <div v-if="contacts && contacts.length > 0"
                class="bg-white dark:bg-gray-800 rounded-2xl border border-gray-100 dark:border-gray-700 shadow-sm overflow-hidden">
                <div
                    class="px-6 py-4 border-b border-gray-100 dark:border-gray-700 bg-gradient-to-br from-gray-50 to-white dark:from-gray-800 dark:to-gray-800/50 flex items-center gap-3">
                    <div class="w-9 h-9 rounded-xl bg-blue-50 dark:bg-blue-900/30 flex items-center justify-center">
                        <svg class="w-4 h-4 text-blue-600 dark:text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z" />
                        </svg>
                    </div>
                    <div>
                        <h3 class="text-sm font-bold text-gray-900 dark:text-white tracking-tight">All contacts</h3>
                        <p class="text-xs text-gray-400 dark:text-gray-500">{{ contacts.length }} total</p>
                    </div>
                </div>

                <div class="divide-y divide-gray-100 dark:divide-gray-700">
                    <div v-for="contact in contacts" :key="contact.id"
                        class="p-5 hover:bg-gray-50/50 dark:hover:bg-gray-700/30 transition-colors">
                        <div class="flex justify-between items-center gap-3 flex-wrap">
                            <div class="flex items-center gap-4 min-w-0">
                                <div
                                    class="w-11 h-11 rounded-2xl bg-gradient-to-br from-blue-500 to-blue-600 flex items-center justify-center text-white flex-shrink-0 shadow-md">
                                    <ContactIcon :type="contact.type" size="md" />
                                </div>
                                <div class="min-w-0">
                                    <div class="flex items-center gap-2 flex-wrap">
                                        <p class="text-sm font-bold text-gray-900 dark:text-white tracking-tight">
                                            {{ getLabel(contact.type) }}
                                        </p>
                                        <span v-if="contact.is_primary"
                                            class="inline-flex items-center gap-1 px-2 py-0.5 text-[10px] font-bold uppercase tracking-wide bg-blue-100 dark:bg-blue-900/30 text-blue-700 dark:text-blue-400 rounded-full">
                                            <svg class="w-2.5 h-2.5" fill="currentColor" viewBox="0 0 20 20">
                                                <path fill-rule="evenodd"
                                                    d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z"
                                                    clip-rule="evenodd" />
                                            </svg>
                                            Primary
                                        </span>
                                    </div>
                                    <p class="text-sm text-gray-500 dark:text-gray-400 mt-0.5 truncate">{{ contact.value }}</p>
                                </div>
                            </div>
                            <div class="flex gap-2 flex-shrink-0">
                                <button @click="openEditModal(contact)"
                                    class="inline-flex items-center gap-1.5 px-3 py-1.5 bg-gray-50 dark:bg-gray-700 hover:bg-gray-100 dark:hover:bg-gray-600 text-gray-700 dark:text-gray-300 text-xs font-semibold rounded-lg transition-colors">
                                    Edit
                                </button>
                                <button @click="deleteContact(contact)"
                                    class="inline-flex items-center gap-1.5 px-3 py-1.5 bg-red-50 dark:bg-red-900/30 hover:bg-red-100 dark:hover:bg-red-900/50 text-red-600 dark:text-red-400 text-xs font-semibold rounded-lg transition-colors">
                                    Delete
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- EMPTY STATE -->
            <div v-else class="bg-white dark:bg-gray-800 rounded-2xl shadow-sm border border-gray-100 dark:border-gray-700 p-12 text-center">
                <div
                    class="w-20 h-20 bg-gradient-to-br from-blue-100 to-blue-50 dark:from-blue-900/40 dark:to-blue-900/20 rounded-2xl flex items-center justify-center mx-auto mb-5 shadow-sm">
                    <svg class="w-10 h-10 text-blue-600 dark:text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z" />
                    </svg>
                </div>
                <h3 class="text-xl font-bold text-gray-900 dark:text-white tracking-tight mb-2">No contacts yet</h3>
                <p class="text-gray-500 dark:text-gray-400 max-w-md mx-auto mb-6 text-sm">
                    Add phone, WhatsApp, social media, or other contact methods so customers can reach you.
                </p>
                <button @click="openCreateModal"
                    class="inline-flex items-center gap-2 px-6 py-3 bg-gradient-to-r from-primary-600 to-primary-700 text-white rounded-xl hover:from-primary-700 hover:to-primary-800 transition-all duration-200 shadow-lg shadow-primary-500/25 hover:shadow-primary-500/40 hover:-translate-y-0.5 font-semibold text-sm">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                    </svg>
                    Add Your First Contact
                </button>
            </div>
        </div>

        <!-- CONTACT MODAL (bottom sheet on mobile) -->
        <ContactModal :show="showModal" :contact="editingContact" @close="closeModal" @save="saveContact" />
    </AuthenticatedLayout>
</template>

<script setup>
    import { ref } from 'vue';
    import { router } from '@inertiajs/vue3';
    import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
    import PageHeader from '@/Components/PageHeader.vue';
    import ContactModal from '@/Components/Owner/ContactModal.vue';
    import ContactIcon from '@/Components/ContactIcon.vue';

    const props = defineProps({
        business: Object,
        contacts: Array,
    });

    const showModal = ref(false);
    const editingContact = ref(null);


    const getLabel = (type) => {
        const labels = {
            'phone': 'Phone', 'whatsapp': 'WhatsApp', 'facebook': 'Facebook', 'instagram': 'Instagram',
            'tiktok': 'TikTok', 'twitter': 'Twitter', 'youtube': 'YouTube', 'linkedin': 'LinkedIn', 'other': 'Other',
        };
        return labels[type] || 'Other';
    };

    const openCreateModal = () => {
        editingContact.value = null;
        showModal.value = true;
    };

    const openEditModal = (contact) => {
        editingContact.value = contact;
        showModal.value = true;
    };

    const closeModal = () => {
        showModal.value = false;
        editingContact.value = null;
    };

    const saveContact = (data, options = {}) => {
        const url = editingContact.value
            ? `/owner/businesses/${props.business.id}/contacts/${editingContact.value.id}`
            : `/owner/businesses/${props.business.id}/contacts`;
        const method = editingContact.value ? 'put' : 'post';

        router[method](url, data, {
            onFinish: () => {
                if (options.onFinish) options.onFinish();
            },
            onSuccess: () => {
                router.reload();
            },
            onError: (errors) => {
                if (options.onError) options.onError(errors);
            },
        });
    };

    const deleteContact = (contact) => {
        if (confirm(`Delete this contact?`)) {
            router.delete(`/owner/businesses/${props.business.id}/contacts/${contact.id}`, {
                onSuccess: () => {
                    router.reload();
                },
            });
        }
    };
</script>