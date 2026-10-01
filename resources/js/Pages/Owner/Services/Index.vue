<!-- resources/js/Pages/Owner/Services/Index.vue -->
<template>
    <AuthenticatedLayout>
        <!-- COMPACT HEADER -->
        <PageHeader
            color="teal"
            :breadcrumb="[
                { label: 'Dashboard', href: '/owner/dashboard' },
                { label: 'My Listings', href: '/owner/listings' },
                { label: listing.name, href: `/owner/listings/${listing.id}/edit` },
                { label: 'Services' }
            ]"
        >
            <template #icon>
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                      d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z" />
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                      d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
            </template>
            <template #title>Services & products</template>
            <template #subtitle>{{ listing.name }}</template>
            <template #actions>
                <button @click="openCreateModal"
                        class="inline-flex items-center gap-2 px-4 py-2 bg-gradient-to-r from-primary-600 to-primary-700 text-white rounded-xl font-semibold hover:from-primary-700 hover:to-primary-800 transition-all shadow-lg shadow-primary-500/25 text-sm">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                    </svg>
                    Add Service
                </button>
            </template>
        </PageHeader>

        <div class="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8 py-8 space-y-6">

            <!-- SERVICES LIST -->
            <div v-if="services && services.length > 0" class="bg-white dark:bg-gray-800 rounded-2xl border border-gray-100 dark:border-gray-700 shadow-sm overflow-hidden">
                <div class="px-6 py-4 border-b border-gray-100 dark:border-gray-700 bg-gradient-to-br from-gray-50 to-white dark:from-gray-800 dark:to-gray-800/50 flex items-center gap-3">
                    <div class="w-9 h-9 rounded-xl bg-teal-50 dark:bg-teal-900/30 flex items-center justify-center">
                        <svg class="w-4 h-4 text-teal-600 dark:text-teal-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z" />
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                        </svg>
                    </div>
                    <div>
                        <h3 class="text-sm font-bold text-gray-900 dark:text-white tracking-tight">All services</h3>
                        <p class="text-xs text-gray-400 dark:text-gray-500">{{ services.length }} total</p>
                    </div>
                </div>

                <div class="divide-y divide-gray-100 dark:divide-gray-700">
                    <div v-for="service in services" :key="service.id"
                         class="p-5 hover:bg-gray-50/50 dark:hover:bg-gray-700/30 transition-colors"
                         :class="service.hidden_at ? 'opacity-75' : ''">
                        <div class="flex justify-between items-start gap-3">
                            <div class="flex items-start gap-4 min-w-0">
                                <div class="w-11 h-11 rounded-2xl bg-gradient-to-br from-teal-500 to-teal-600 flex items-center justify-center flex-shrink-0 shadow-md">
                                    <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z" />
                                    </svg>
                                </div>
                                <div class="flex-1 min-w-0">
                                    <div class="flex items-center gap-2 flex-wrap">
                                        <h4 class="text-base font-bold text-gray-900 dark:text-white tracking-tight">{{ service.name }}</h4>
                                        <span v-if="service.hidden_at"
                                              class="inline-flex items-center gap-1 px-2 py-0.5 text-[10px] font-bold uppercase tracking-wide bg-amber-100 dark:bg-amber-900/30 text-amber-800 dark:text-amber-400 rounded-full">
                                            Hidden
                                        </span>
                                    </div>
                                    <p v-if="service.description" class="text-sm text-gray-500 dark:text-gray-400 mt-1 leading-relaxed">
                                        {{ service.description }}
                                    </p>
                                </div>
                            </div>
                            <div class="flex gap-2 flex-shrink-0">
                                <button v-if="!service.hidden_at"
                                        @click="openEditModal(service)"
                                        class="inline-flex items-center gap-1.5 px-3 py-1.5 bg-gray-50 dark:bg-gray-700 hover:bg-gray-100 dark:hover:bg-gray-600 text-gray-700 dark:text-gray-300 text-xs font-semibold rounded-lg transition-colors">
                                    Edit
                                </button>
                                <span v-else
                                      class="inline-flex items-center gap-1.5 px-3 py-1.5 bg-gray-100 dark:bg-gray-700 text-gray-400 dark:text-gray-500 text-xs font-semibold rounded-lg cursor-not-allowed"
                                      title="Delete this service or upgrade to edit">
                                    Locked
                                </span>
                                <button @click="deleteService(service)"
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
                <div class="w-20 h-20 bg-gradient-to-br from-teal-100 to-teal-50 dark:from-teal-900/40 dark:to-teal-900/20 rounded-2xl flex items-center justify-center mx-auto mb-5 shadow-sm">
                    <svg class="w-10 h-10 text-teal-600 dark:text-teal-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z" />
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                    </svg>
                </div>
                <h3 class="text-xl font-bold text-gray-900 dark:text-white tracking-tight mb-2">No services yet</h3>
                <p class="text-gray-500 dark:text-gray-400 max-w-md mx-auto mb-6 text-sm">
                    Add services or products this listing offers to help customers understand what you do.
                </p>
                <button @click="openCreateModal"
                        class="inline-flex items-center gap-2 px-6 py-3 bg-gradient-to-r from-primary-600 to-primary-700 text-white rounded-xl hover:from-primary-700 hover:to-primary-800 transition-all duration-200 shadow-lg shadow-primary-500/25 hover:shadow-primary-500/40 hover:-translate-y-0.5 font-semibold text-sm">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                    </svg>
                    Add Your First Service
                </button>
            </div>
        </div>

        <!-- SERVICE MODAL (bottom sheet on mobile) -->
        <ServiceModal
            :show="showModal"
            :service="editingService"
            @close="closeModal"
            @save="saveService"
        />
    </AuthenticatedLayout>
</template>

<script setup>
import { ref, reactive } from 'vue';
import { router } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import PageHeader from '@/Components/PageHeader.vue';
import ServiceModal from '@/Components/Owner/ServiceModal.vue';

const props = defineProps({
    listing: Object,
    services: Array,
});

const showModal = ref(false);
const editingService = ref(null);

const openCreateModal = () => {
    editingService.value = null;
    showModal.value = true;
};

const openEditModal = (service) => {
    editingService.value = service;
    showModal.value = true;
};

const closeModal = () => {
    showModal.value = false;
    editingService.value = null;
};

const saveService = (data, options = {}) => {
    const url = editingService.value
        ? `/owner/listings/${props.listing.id}/services/${editingService.value.id}`
        : `/owner/listings/${props.listing.id}/services`;
    const method = editingService.value ? 'put' : 'post';

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

const deleteService = (service) => {
    if (confirm(`Delete "${service.name}"?`)) {
        router.delete(`/owner/listings/${props.listing.id}/services/${service.id}`, {
            onSuccess: () => {
                router.reload();
            },
        });
    }
};
</script>