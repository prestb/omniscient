<!-- resources/js/Pages/Admin/Listings/Index.vue -->
<!--
  PHASE 19C — ADMIN LISTINGS (read-only).

  Listing is the canonical discoverable entity, but Admin previously had no
  Listings surface: it could only see Businesses. An administrator could not
  inspect what people actually discover, nor see a Business-less Professional
  without going through an owner.

  This is an INSPECTION surface only — no editing, no moderation workflow.

  Domain reflected here:

      Business        (optional organization)
          | optionally groups
      Listing         (canonical discoverable entity)
          | optionally has
      Location

  A Listing with no Business is "Independent", NOT broken or orphaned data.
-->
<template>
    <AuthenticatedLayout>
        <Head title="Listings" />

        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-6">
            <div class="mb-6">
                <h1 class="text-heading-lg text-ink dark:text-white">Listings</h1>
                <p class="text-body-sm text-ink-muted dark:text-gray-400 mt-1">
                    The canonical discoverable entity. A Listing may belong to an organization, or stand alone.
                </p>
            </div>

            <!-- Stats -->
            <div class="grid grid-cols-2 lg:grid-cols-4 gap-3 sm:gap-4 mb-6">
                <div v-for="s in statCards" :key="s.label"
                    class="bg-surface dark:bg-gray-800 rounded-card border border-hairline dark:border-hairline-dark p-4">
                    <p class="text-heading-md text-ink dark:text-white">{{ s.value }}</p>
                    <p class="text-caption uppercase tracking-widest text-ink-muted dark:text-gray-400 mt-0.5">{{ s.label }}</p>
                </div>
            </div>

            <!-- Filters -->
            <form @submit.prevent="apply" class="bg-surface dark:bg-gray-800 rounded-card border border-hairline dark:border-hairline-dark p-4 mb-6">
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-5 gap-3">
                    <input v-model="form.search" type="search" placeholder="Name, slug, owner or organization"
                        aria-label="Search listings"
                        class="min-h-11 rounded-control border border-hairline dark:border-hairline-dark bg-surface dark:bg-gray-900 px-3 text-body text-ink dark:text-white focus:outline-none focus-visible:ring-2 focus-visible:ring-primary-500" />

                    <select v-model="form.type" aria-label="Listing type"
                        class="min-h-11 rounded-control border border-hairline dark:border-hairline-dark bg-surface dark:bg-gray-900 px-3 text-body text-ink dark:text-white focus:outline-none focus-visible:ring-2 focus-visible:ring-primary-500">
                        <option value="">All types</option>
                        <option v-for="t in listingTypes" :key="t" :value="t">{{ t }}</option>
                    </select>

                    <select v-model="form.status" aria-label="Publication status"
                        class="min-h-11 rounded-control border border-hairline dark:border-hairline-dark bg-surface dark:bg-gray-900 px-3 text-body text-ink dark:text-white focus:outline-none focus-visible:ring-2 focus-visible:ring-primary-500">
                        <option value="">All statuses</option>
                        <option v-for="s in statuses" :key="s" :value="s">{{ s }}</option>
                    </select>

                    <select v-model="form.affiliation" aria-label="Organization"
                        class="min-h-11 rounded-control border border-hairline dark:border-hairline-dark bg-surface dark:bg-gray-900 px-3 text-body text-ink dark:text-white focus:outline-none focus-visible:ring-2 focus-visible:ring-primary-500">
                        <option value="">Grouped or independent</option>
                        <option value="business">Grouped under a Business</option>
                        <option value="independent">Independent (no Business)</option>
                    </select>

                    <select v-model="form.location" aria-label="Location"
                        class="min-h-11 rounded-control border border-hairline dark:border-hairline-dark bg-surface dark:bg-gray-900 px-3 text-body text-ink dark:text-white focus:outline-none focus-visible:ring-2 focus-visible:ring-primary-500">
                        <option value="">Any location</option>
                        <option value="with">Has a location</option>
                        <option value="without">No location</option>
                    </select>
                </div>

                <div class="mt-3 flex items-center gap-2">
                    <Button type="submit" variant="primary" size="sm">Apply</Button>
                    <Button type="button" variant="ghost" size="sm" @click="reset">Reset</Button>
                </div>
            </form>

            <!-- Results -->
            <div class="bg-surface dark:bg-gray-800 rounded-card border border-hairline dark:border-hairline-dark overflow-hidden">
                <div v-if="!listings.data.length" class="p-10 text-center">
                    <h2 class="text-heading-md text-ink dark:text-white mb-1">No listings match</h2>
                    <p class="text-body text-ink-muted dark:text-gray-400">Try clearing a filter.</p>
                </div>

                <div v-else class="overflow-x-auto">
                    <table class="w-full text-left">
                        <thead class="bg-gray-50 dark:bg-gray-900/40 border-b border-hairline dark:border-hairline-dark">
                            <tr>
                                <th v-for="h in headers" :key="h"
                                    class="px-4 py-3 text-caption font-bold uppercase tracking-widest text-ink-muted dark:text-gray-400">{{ h }}</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-for="listing in listings.data" :key="listing.id"
                                class="border-b border-hairline dark:border-hairline-dark last:border-0 hover:bg-gray-50 dark:hover:bg-gray-700/40">
                                <td class="px-4 py-3 min-w-0">
                                    <Link :href="`/admin/listings/${listing.id}`"
                                        class="text-body font-semibold text-ink dark:text-white hover:text-primary-600 dark:hover:text-primary-400 focus:outline-none focus-visible:ring-2 focus-visible:ring-primary-500 rounded-control">
                                        {{ listing.name }}
                                    </Link>
                                    <p class="text-caption text-ink-muted dark:text-gray-500 truncate">{{ listing.slug }}</p>
                                </td>
                                <td class="px-4 py-3">
                                    <Badge variant="neutral">{{ listing.listing_type || listing.type }}</Badge>
                                </td>
                                <td class="px-4 py-3 text-body text-ink-muted dark:text-gray-300">
                                    {{ listing.owner?.name || '—' }}
                                </td>
                                <td class="px-4 py-3 text-body">
                                    <!-- An ungrouped Listing is a legitimate state, not a gap. -->
                                    <span v-if="listing.business" class="text-ink dark:text-gray-200">{{ listing.business.name }}</span>
                                    <span v-else class="text-ink-muted dark:text-gray-400 italic">Independent</span>
                                </td>
                                <td class="px-4 py-3 text-body">
                                    <span v-if="listing.location" class="text-ink-muted dark:text-gray-300">
                                        {{ listing.location.city?.name || listing.location.address || 'Set' }}
                                    </span>
                                    <span v-else class="text-ink-muted dark:text-gray-500">—</span>
                                </td>
                                <td class="px-4 py-3">
                                    <Badge :variant="statusVariant(listing.status)">{{ listing.status }}</Badge>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

            <Pagination :links="listings.links" :from="listings.from || 0" :to="listings.to || 0"
                :total="listings.total || 0" class="mt-6" />
        </div>
    </AuthenticatedLayout>
</template>

<script setup>
import { computed, reactive } from 'vue';
import { Head, Link, router } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import Pagination from '@/Components/Pagination.vue';
import Badge from '@/Components/Public/ui/Badge.vue';
import Button from '@/Components/Public/ui/Button.vue';

const props = defineProps({
    listings: { type: Object, required: true },
    filters: { type: Object, default: () => ({}) },
    listingTypes: { type: Array, default: () => [] },
    statuses: { type: Array, default: () => [] },
    stats: { type: Object, default: () => ({}) },
});

const headers = ['Listing', 'Type', 'Owner', 'Organization', 'Location', 'Status'];

// Keys mirror the controller's `filters` payload exactly.
const form = reactive({
    search: props.filters.search || '',
    type: props.filters.type || '',
    status: props.filters.status || '',
    affiliation: props.filters.affiliation || '',
    location: props.filters.location || '',
});

const statCards = computed(() => [
    { label: 'Total', value: props.stats.total ?? 0 },
    { label: 'Published', value: props.stats.published ?? 0 },
    { label: 'Independent', value: props.stats.independent ?? 0 },
    { label: 'With location', value: props.stats.with_location ?? 0 },
]);

const apply = () => {
    const params = {};
    Object.entries(form).forEach(([k, v]) => {
        if (v !== '' && v !== null && v !== undefined) params[k] = v;
    });
    router.get('/admin/listings', params, { preserveState: true, preserveScroll: true });
};

const reset = () => {
    Object.keys(form).forEach((k) => (form[k] = ''));
    router.get('/admin/listings', {}, { preserveScroll: true });
};

// Publication state is never conveyed by colour alone — the badge carries text.
const statusVariant = (status) => {
    if (status === 'published') return 'success';
    if (status === 'rejected' || status === 'suspended') return 'danger';
    if (status === 'submitted') return 'warning';
    return 'neutral';
};
</script>
