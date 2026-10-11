<!-- resources/js/Pages/Admin/Locations/Index.vue -->
<!--
  PHASE 27 — PHYSICAL LOCATION INSPECTION (READ-ONLY).

  These are the `locations` rows: the physical places a Listing sits at. They are
  NOT the geographic reference data managed under Countries / Regions / Cities /
  Areas. A Location is ACCOUNT-owned; a Business is optional context and is never
  presented as the owner.
-->
<template>
    <AuthenticatedLayout>
        <PageHeader color="teal" :breadcrumb="[
            { label: 'Admin', href: '/admin/dashboard' },
            { label: 'Locations' }
        ]">
            <template #icon>
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                    d="M17.657 16.657L13.414 20.9a2 2 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z" />
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                    d="M15 11a3 3 0 11-6 0 3 3 0 016 0z" />
            </template>
            <template #title>Physical Locations</template>
            <template #subtitle>The places Listings sit at. Geographic reference data lives under Countries, Regions, Cities and Areas.</template>
        </PageHeader>

        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8 space-y-6">

            <!-- Stats -->
            <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                <div class="bg-white dark:bg-gray-800 rounded-2xl border border-gray-100 dark:border-gray-700 p-5">
                    <p class="text-3xl font-bold text-gray-900 dark:text-white tracking-tight">{{ stats?.total || 0 }}</p>
                    <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">Total locations</p>
                </div>
                <div class="bg-white dark:bg-gray-800 rounded-2xl border border-gray-100 dark:border-gray-700 p-5">
                    <p class="text-3xl font-bold text-gray-900 dark:text-white tracking-tight">{{ stats?.with_listings || 0 }}</p>
                    <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">Used by at least one Listing</p>
                </div>
                <div class="bg-white dark:bg-gray-800 rounded-2xl border border-gray-100 dark:border-gray-700 p-5">
                    <p class="text-3xl font-bold text-gray-900 dark:text-white tracking-tight">{{ stats?.without_listings || 0 }}</p>
                    <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">Not attached to any Listing</p>
                </div>
            </div>

            <!-- Filters -->
            <form @submit.prevent="applyFilters"
                class="bg-white dark:bg-gray-800 rounded-2xl border border-gray-100 dark:border-gray-700 p-5 grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-5 gap-3">
                <input v-model="form.search" type="text" aria-label="Search physical locations" placeholder="Search name, address or owner…"
                    class="rounded-xl border-gray-200 dark:border-gray-600 dark:bg-gray-900 dark:text-gray-100 text-sm" />

                <select v-model="form.country_id"
                    class="rounded-xl border-gray-200 dark:border-gray-600 dark:bg-gray-900 dark:text-gray-100 text-sm">
                    <option value="">All countries</option>
                    <option v-for="c in countries" :key="c.id" :value="c.id">{{ c.name }}</option>
                </select>

                <select v-model="form.city_id"
                    class="rounded-xl border-gray-200 dark:border-gray-600 dark:bg-gray-900 dark:text-gray-100 text-sm">
                    <option value="">All cities</option>
                    <option v-for="c in cities" :key="c.id" :value="c.id">{{ c.name }}</option>
                </select>

                <select v-model="form.has_listings"
                    class="rounded-xl border-gray-200 dark:border-gray-600 dark:bg-gray-900 dark:text-gray-100 text-sm">
                    <option value="">Any Listing state</option>
                    <option value="yes">Has Listings</option>
                    <option value="no">No Listings</option>
                </select>

                <button type="submit"
                    class="inline-flex items-center justify-center gap-2 px-4 py-2 bg-gradient-to-r from-primary-600 to-primary-700 text-white rounded-xl font-semibold hover:from-primary-700 hover:to-primary-800 transition-all text-sm">
                    Apply filters
                </button>
            </form>

            <!-- Table -->
            <div class="bg-white dark:bg-gray-800 rounded-2xl border border-gray-100 dark:border-gray-700 overflow-hidden">
                <div v-if="!locations?.data?.length" class="p-10 text-center">
                    <p class="text-sm font-semibold text-gray-900 dark:text-white">No locations match these filters</p>
                    <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">Locations appear here once owners add physical places to their Listings.</p>
                </div>

                <div v-else class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-100 dark:divide-gray-700">
                        <thead class="bg-gray-50 dark:bg-gray-900/40">
                            <tr>
                                <th class="px-6 py-3 text-left text-[11px] font-bold uppercase tracking-wider text-gray-500 dark:text-gray-400">Location</th>
                                <th class="px-6 py-3 text-left text-[11px] font-bold uppercase tracking-wider text-gray-500 dark:text-gray-400">Owner (account)</th>
                                <th class="px-6 py-3 text-left text-[11px] font-bold uppercase tracking-wider text-gray-500 dark:text-gray-400">Organization (context)</th>
                                <th class="px-6 py-3 text-left text-[11px] font-bold uppercase tracking-wider text-gray-500 dark:text-gray-400">Geography</th>
                                <th class="px-6 py-3 text-left text-[11px] font-bold uppercase tracking-wider text-gray-500 dark:text-gray-400">Listings</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                            <tr v-for="location in locations.data" :key="location.id" class="hover:bg-gray-50 dark:hover:bg-gray-700/30">
                                <td class="px-6 py-4">
                                    <p class="text-sm font-semibold text-gray-900 dark:text-white truncate">{{ location.name || '—' }}</p>
                                    <p class="text-xs text-gray-500 dark:text-gray-400 truncate">{{ location.address || 'No address' }}</p>
                                    <p v-if="location.hidden" class="text-[10px] font-bold uppercase tracking-wide text-amber-600 dark:text-amber-400 mt-0.5">Hidden</p>
                                    <p v-if="location.coordinates" class="text-[10px] text-gray-400 mt-0.5">
                                        {{ location.coordinates.latitude }}, {{ location.coordinates.longitude }}
                                    </p>
                                </td>
                                <td class="px-6 py-4">
                                    <template v-if="location.owner">
                                        <p class="text-sm text-gray-900 dark:text-white truncate">{{ location.owner.name }}</p>
                                        <p class="text-xs text-gray-500 dark:text-gray-400 truncate">{{ location.owner.email }}</p>
                                    </template>
                                    <span v-else class="text-xs text-gray-400">—</span>
                                </td>
                                <td class="px-6 py-4">
                                    <a v-if="location.business" :href="`/business/${location.business.slug}`"
                                        class="text-sm font-semibold text-primary-600 dark:text-primary-400 hover:underline truncate">
                                        {{ location.business.name }}
                                    </a>
                                    <span v-else class="text-xs text-gray-400">Independent</span>
                                </td>
                                <td class="px-6 py-4">
                                    <p class="text-sm text-gray-700 dark:text-gray-300 truncate">
                                        {{ [location.city, location.region, location.country].filter(Boolean).join(', ') || '—' }}
                                    </p>
                                </td>
                                <td class="px-6 py-4">
                                    <span class="text-sm font-bold text-gray-900 dark:text-white">{{ location.listings_count }}</span>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <div v-if="locations?.links" class="px-6 py-4 border-t border-gray-100 dark:border-gray-700">
                    <Pagination :links="locations.links" :from="locations.from || 0" :to="locations.to || 0"
                        :total="locations.total || 0" />
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>

<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import PageHeader from '@/Components/PageHeader.vue';
import Pagination from '@/Components/Pagination.vue';
import { router } from '@inertiajs/vue3';
import { reactive } from 'vue';

const props = defineProps({
    locations: { type: Object, default: () => ({}) },
    filters: { type: Object, default: () => ({}) },
    stats: { type: Object, default: () => ({}) },
    cities: { type: Array, default: () => [] },
    countries: { type: Array, default: () => [] },
});

const form = reactive({
    search: props.filters?.search || '',
    city_id: props.filters?.city_id || '',
    country_id: props.filters?.country_id || '',
    has_listings: props.filters?.has_listings || '',
});

const applyFilters = () => {
    router.get('/admin/locations', { ...form }, { preserveState: true, replace: true });
};
</script>
