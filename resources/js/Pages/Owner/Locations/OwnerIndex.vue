<!-- resources/js/Pages/Owner/Locations/OwnerIndex.vue -->
<!--
    PHASE 22A — ACCOUNT-OWNED LOCATIONS.

    This is the OWNER-SCOPED list. It is deliberately a separate page from
    `Owner/Locations/Index.vue`, which remains the BUSINESS-scoped index rendered
    by `owner/businesses/{business}/locations` and must keep its own contract
    (breadcrumbs, per-business hours links, deletion protection).

    A Location is an ACCOUNT-owned resource, so this list is scoped to
    `owner_id`, never to a Business. It is therefore fully usable by a
    Business-less Professional. A Business, when present, is optional context.
-->
<template>
    <AuthenticatedLayout>
        <Head title="Locations" />

        <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 py-8 space-y-6">
            <div class="flex flex-wrap items-start justify-between gap-4">
                <div class="min-w-0">
                    <h1 class="text-2xl font-bold text-gray-900 dark:text-white tracking-tight">Locations</h1>
                    <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">
                        Physical places you can attach to a Listing. A Location does not need an organization.
                    </p>
                </div>
                <a :href="'/owner/locations/create'"
                    class="inline-flex min-h-11 items-center rounded-xl bg-primary-600 px-4 text-sm font-semibold text-white transition-colors hover:bg-primary-700 focus:outline-none focus-visible:ring-2 focus-visible:ring-primary-500 focus-visible:ring-offset-2">
                    Add a Location
                </a>
            </div>

            <div v-if="page.props.flash?.success" role="status"
                class="rounded-xl bg-emerald-50 dark:bg-emerald-900/20 border border-emerald-200 dark:border-emerald-800 px-4 py-3 text-sm text-emerald-800 dark:text-emerald-200">
                {{ page.props.flash.success }}
            </div>
            <div v-if="page.props.flash?.error" role="alert"
                class="rounded-xl bg-rose-50 dark:bg-rose-900/20 border border-rose-200 dark:border-rose-800 px-4 py-3 text-sm text-rose-800 dark:text-rose-200">
                {{ page.props.flash.error }}
            </div>

            <!-- Empty state: what this surface is for and what to do next. -->
            <div v-if="!locations.length"
                class="bg-white dark:bg-gray-800 rounded-2xl border border-dashed border-gray-200 dark:border-gray-700 p-10 text-center">
                <h2 class="text-base font-bold text-gray-900 dark:text-white mb-2">No locations yet</h2>
                <p class="text-sm text-gray-500 dark:text-gray-400 max-w-md mx-auto">
                    Add a physical location so visitors know where a Listing can be found. This is optional —
                    a service that travels to customers does not need one.
                </p>
                <a :href="'/owner/locations/create'"
                    class="mt-5 inline-flex min-h-11 items-center rounded-xl bg-primary-600 px-5 text-sm font-semibold text-white hover:bg-primary-700">
                    Add your first Location
                </a>
            </div>

            <ul v-else class="space-y-3">
                <li v-for="location in locations" :key="location.id"
                    class="bg-white dark:bg-gray-800 rounded-2xl border border-gray-100 dark:border-gray-700 p-5 flex flex-wrap items-start justify-between gap-4">
                    <div class="min-w-0">
                        <h2 class="text-sm font-bold text-gray-900 dark:text-white truncate">
                            {{ location.name || `Location #${location.id}` }}
                        </h2>
                        <p v-if="location.full_address" class="text-xs text-gray-500 dark:text-gray-400 mt-0.5">
                            {{ location.full_address }}
                        </p>
                        <!-- Organization is CONTEXT. Its absence is normal, not a defect. -->
                        <p class="text-xs text-gray-400 mt-1">
                            {{ location.business?.name || 'Independent' }}
                        </p>
                        <!-- PHASE 22B - communicate usage. A Location still
                             attached to Listings cannot be deleted; the backend
                             enforces that, this only warns first. -->
                        <p class="text-xs mt-1"
                            :class="location.listings_count ? 'text-amber-600 dark:text-amber-400' : 'text-gray-400'">
                            <template v-if="location.listings_count">
                                Used by {{ location.listings_count }}
                                {{ location.listings_count === 1 ? 'Listing' : 'Listings' }}
                                &middot; detach before deleting
                            </template>
                            <template v-else>Not used by any Listing</template>
                        </p>
                    </div>
                    <a :href="`/owner/locations/${location.id}/edit`"
                        class="text-sm font-semibold text-primary-600 hover:text-primary-700">Edit</a>
                </li>
            </ul>
        </div>
    </AuthenticatedLayout>
</template>

<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, usePage } from '@inertiajs/vue3';

const page = usePage();

defineProps({
    /** Locations owned by the authenticated account, across all Businesses. */
    locations: { type: Array, default: () => [] },
    /** Optional organization context. Never required. */
    businesses: { type: Array, default: () => [] },
});
</script>
