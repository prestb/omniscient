<!-- resources/js/Pages/Owner/Locations/Index.vue -->
<template>
    <AuthenticatedLayout>
        <!-- COMPACT HEADER -->
        <PageHeader color="sky" :breadcrumb="[
            { label: 'Dashboard', href: '/owner/dashboard' },
            { label: 'Businesses', href: '/owner/businesses' },
            { label: business.name, href: `/owner/businesses/${business.id}/edit` },
            { label: 'Locations' }
        ]">
            <template #icon>
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                    d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z" />
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                    d="M15 11a3 3 0 11-6 0 3 3 0 016 0z" />
            </template>
            <template #title>Locations</template>
            <template #subtitle>{{ business.name }}</template>
            <template #actions>
                <a :href="`/owner/businesses/${business.id}/locations/create`"
                    class="inline-flex items-center gap-2 px-4 py-2 bg-gradient-to-r from-primary-600 to-primary-700 text-white rounded-xl font-semibold hover:from-primary-700 hover:to-primary-800 transition-all shadow-lg shadow-primary-500/25 text-sm">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                    </svg>
                    Add Location
                </a>
            </template>
        </PageHeader>

        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8 space-y-6">

            <!-- STATS BAR -->
            <div class="grid grid-cols-3 gap-3 sm:gap-4">
                <div
                    class="bg-white dark:bg-gray-800 rounded-2xl border border-gray-100 dark:border-gray-700 p-4 hover:shadow-md transition-shadow">
                    <p class="text-[10px] uppercase tracking-widest text-gray-400 font-bold">Total</p>
                    <p class="text-2xl font-bold text-gray-900 dark:text-white tracking-tight mt-1">{{ locations?.length
                        || 0 }}
                    </p>
                </div>
                <div
                    class="bg-white dark:bg-gray-800 rounded-2xl border border-gray-100 dark:border-gray-700 p-4 hover:shadow-md transition-shadow">
                    <p class="text-[10px] uppercase tracking-widest text-gray-400 font-bold">Primary</p>
                    <p class="text-2xl font-bold text-blue-600 tracking-tight mt-1">{{ getPrimaryLocationCount() }}</p>
                </div>
                <div
                    class="bg-white dark:bg-gray-800 rounded-2xl border border-gray-100 dark:border-gray-700 p-4 hover:shadow-md transition-shadow">
                    <p class="text-[10px] uppercase tracking-widest text-gray-400 font-bold">Active</p>
                    <p class="text-2xl font-bold text-emerald-600 tracking-tight mt-1">{{ getActiveLocationCount() }}</p>
                </div>
            </div>

            <!-- LOCATION CARDS -->
            <div v-if="locations && locations.length > 0" class="grid grid-cols-1 md:grid-cols-2 gap-5">
                <!-- ⬇️ SWIPE: wrap each card in SwipeableListItem -->
                <SwipeableListItem v-for="location in locations" :key="location.id" :item="location" label="location"
                    @delete="deleteLocation">
                    <div class="group bg-white dark:bg-gray-800 rounded-2xl border hover:shadow-xl hover:-translate-y-1 transition-all duration-300 overflow-hidden p-6"
                        :class="location.hidden_at ? 'border-amber-200 dark:border-amber-800 opacity-75' : 'border-gray-100 dark:border-gray-700'">

                        <!-- Header -->
                        <div class="flex items-start justify-between gap-3 mb-4">
                            <div class="flex items-center gap-3 min-w-0">
                                <div
                                    class="w-12 h-12 rounded-2xl bg-gradient-to-br from-sky-500 to-blue-600 flex items-center justify-center flex-shrink-0 shadow-md">
                                    <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor"
                                        viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z" />
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M15 11a3 3 0 11-6 0 3 3 0 016 0z" />
                                    </svg>
                                </div>
                                <div class="min-w-0">
                                    <h3
                                        class="text-base font-bold text-gray-900 dark:text-white truncate tracking-tight">
                                        {{ location.name || "Unnamed Location" }}
                                    </h3>
                                    <div class="flex items-center gap-1.5 mt-0.5 flex-wrap">
                                        <span v-if="location.is_primary"
                                            class="inline-flex items-center gap-1 px-2 py-0.5 text-[10px] font-bold uppercase tracking-wide bg-blue-100 dark:bg-blue-900/30 text-blue-700 dark:text-blue-400 rounded-full">
                                            <svg class="w-2.5 h-2.5" fill="currentColor" viewBox="0 0 20 20">
                                                <path fill-rule="evenodd"
                                                    d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z"
                                                    clip-rule="evenodd" />
                                            </svg>
                                            Primary
                                        </span>
                                        <span v-if="location.hidden_at"
                                            class="inline-flex items-center gap-1 px-2 py-0.5 text-[10px] font-bold uppercase tracking-wide bg-amber-100 dark:bg-amber-900/30 text-amber-800 dark:text-amber-400 rounded-full">
                                            Hidden
                                        </span>
                                        <span :class="statusClass(location.status)">
                                            {{ location.status_label || location.status }}
                                        </span>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Address -->
                        <div class="space-y-1.5 text-sm text-gray-600 dark:text-gray-400 mb-4">
                            <p class="flex items-start gap-2">
                                <svg class="w-4 h-4 text-gray-400 flex-shrink-0 mt-0.5" fill="none"
                                    stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z" />
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M15 11a3 3 0 11-6 0 3 3 0 016 0z" />
                                </svg>
                                <span class="line-clamp-2">{{ location.address || "No address provided" }}</span>
                            </p>
                        </div>

                        <!-- Contact chips -->
                        <div v-if="location.phone || location.whatsapp || location.email" class="flex flex-wrap gap-2 mb-5">
                            <span v-if="location.phone"
                                class="inline-flex items-center gap-1 px-2.5 py-1 bg-gray-50 dark:bg-gray-700 text-gray-700 dark:text-gray-300 text-[11px] font-medium rounded-lg">
                                <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z" />
                                </svg>
                                {{ location.phone }}
                            </span>
                            <span v-if="location.whatsapp"
                                class="inline-flex items-center gap-1 px-2.5 py-1 bg-emerald-50 dark:bg-emerald-900/30 text-emerald-700 dark:text-emerald-400 text-[11px] font-medium rounded-lg">
                                <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z" />
                                </svg>
                                WhatsApp
                            </span>
                            <span v-if="location.email"
                                class="inline-flex items-center gap-1 px-2.5 py-1 bg-gray-50 dark:bg-gray-700 text-gray-700 dark:text-gray-300 text-[11px] font-medium rounded-lg truncate max-w-[180px]">
                                <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M16 12a4 4 0 10-8 0 4 4 0 008 0zm0 0v1.5a2.5 2.5 0 005 0V12a9 9 0 10-9 9m4.5-1.206a8.959 8.959 0 01-4.5 1.207" />
                                </svg>
                                <span class="truncate">{{ location.email }}</span>
                            </span>
                        </div>

                        <!-- Actions -->
                        <div class="flex items-center gap-2 pt-4 border-t border-gray-100 dark:border-gray-700">
                            <span v-if="location.hidden_at"
                                class="flex-1 inline-flex items-center justify-center gap-1.5 px-3 py-2 bg-gray-100 dark:bg-gray-700 text-gray-400 text-xs font-semibold rounded-lg cursor-not-allowed"
                                title="Delete this location or upgrade to edit">
                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" />
                                </svg>
                                Locked
                            </span>
                            <a v-else :href="`/owner/businesses/${business.id}/locations/${location.id}/edit`"
                                class="flex-1 inline-flex items-center justify-center gap-1.5 px-3 py-2 bg-primary-50 dark:bg-primary-900/20 hover:bg-primary-100 dark:hover:bg-primary-900/30 text-primary-700 dark:text-primary-400 text-xs font-semibold rounded-lg transition-colors">
                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                                </svg>
                                Edit
                            </a>
                            <a :href="`/owner/businesses/${business.id}/locations/${location.id}/hours`"
                                class="flex-1 inline-flex items-center justify-center gap-1.5 px-3 py-2 bg-blue-50 dark:bg-blue-900/20 hover:bg-blue-100 dark:hover:bg-blue-900/30 text-blue-700 dark:text-blue-400 text-xs font-semibold rounded-lg transition-colors">
                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                                </svg>
                                Hours
                            </a>
                            <button @click="deleteLocation(location)"
                                class="w-9 h-9 flex items-center justify-center text-red-500 hover:text-red-700 hover:bg-red-50 dark:hover:bg-red-900/20 rounded-lg transition-colors"
                                title="Delete location">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                </svg>
                            </button>
                        </div>
                    </div>
                </SwipeableListItem>
            </div>

            <!-- EMPTY STATE -->
            <div v-else
                class="bg-white dark:bg-gray-800 rounded-2xl shadow-sm border border-gray-100 dark:border-gray-700 p-12 text-center">
                <div
                    class="w-20 h-20 bg-gradient-to-br from-sky-100 to-blue-100 dark:from-sky-900/30 dark:to-blue-900/30 rounded-2xl flex items-center justify-center mx-auto mb-5 shadow-sm">
                    <svg class="w-10 h-10 text-sky-600 dark:text-sky-400" fill="none" stroke="currentColor"
                        viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z" />
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M15 11a3 3 0 11-6 0 3 3 0 016 0z" />
                    </svg>
                </div>
                <h3 class="text-xl font-bold text-gray-900 dark:text-white tracking-tight mb-2">No locations yet</h3>
                <p class="text-gray-500 dark:text-gray-400 max-w-md mx-auto mb-6 text-sm">
                    Add your first location to help customers find your business.
                </p>
                <a :href="`/owner/businesses/${business.id}/locations/create`"
                    class="inline-flex items-center gap-2 px-6 py-3 bg-gradient-to-r from-primary-600 to-primary-700 text-white rounded-xl hover:from-primary-700 hover:to-primary-800 transition-all duration-200 shadow-lg shadow-primary-500/25 hover:shadow-primary-500/40 hover:-translate-y-0.5 font-semibold text-sm">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                    </svg>
                    Add Your First Location
                </a>
            </div>
        </div>
    </AuthenticatedLayout>
</template>

<script setup>
    import { router } from "@inertiajs/vue3";
    import { useToast } from "@/composables/useToast";
    import AuthenticatedLayout from "@/Layouts/AuthenticatedLayout.vue";
    import PageHeader from "@/Components/PageHeader.vue";
    import SwipeableListItem from "@/Components/Common/SwipeableListItem.vue";
    import { useStatusBadge } from "@/composables/useStatusBadge";
    import { useConfirm } from "@/composables/useConfirm";

    const props = defineProps({
        business: Object,
        locations: Array,
    });

    const {  error } = useToast();
    const { location: statusClass } = useStatusBadge();
    const { confirm: confirmDialog } = useConfirm();

    const getPrimaryLocationCount = () => {
        if (!props.locations) return 0;
        return props.locations.filter((b) => b.is_primary).length;
    };

    const getActiveLocationCount = () => {
        if (!props.locations) return 0;
        return props.locations.filter((b) => b.status === "active").length;
    };

    const deleteLocation = async (location) => {
        const confirmed = await confirmDialog({
            title: 'Delete location?',
            message: `"${location.name || "Unnamed"}" will be permanently removed. This action cannot be undone.`,
            confirmText: 'Delete Location',
            cancelText: 'Cancel',
            variant: 'danger',
        });

        if (!confirmed) return;

        router.delete(
            `/owner/businesses/${props.business.id}/locations/${location.id}`,
            {},
            {
                preserveScroll: true,
                // ✅ No success toast here — the controller flashes
                //    'Location deleted successfully.' and AuthenticatedLayout
                //    shows it. Prevents the double toast.
                onError: () => {
                    // Client-side-only toast: request failed before the
                    // server could flash anything back.
                    error("Delete Failed ❌", "Failed to delete location. Please try again.", { duration: 4000 });
                },
            }
        );
    };
</script>