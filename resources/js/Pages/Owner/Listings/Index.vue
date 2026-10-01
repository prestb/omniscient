<!-- resources/js/Pages/Owner/Listings/Index.vue -->
<template>
    <AuthenticatedLayout>
        <Head title="My Listings" />

        <div class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8 py-8 space-y-6">
            <div class="flex flex-wrap items-center justify-between gap-4">
                <div>
                    <h1 class="text-2xl font-bold text-gray-900 dark:text-white tracking-tight">My Listings</h1>
                    <p class="text-sm text-gray-500 dark:text-gray-400 mt-0.5">
                        A Listing is the discoverable entity. It has its own public page at
                        <span class="font-mono">/listing/{slug}</span>.
                    </p>
                </div>
                <a href="/owner/listings/create"
                    class="inline-flex items-center gap-2 px-4 py-2 bg-gradient-to-r from-primary-600 to-primary-700 text-white rounded-xl font-semibold hover:from-primary-700 hover:to-primary-800 transition-all shadow-lg shadow-primary-500/25 text-sm">
                    Create Listing
                </a>
            </div>

            <div v-if="page.props.flash?.success"
                class="rounded-xl bg-emerald-50 dark:bg-emerald-900/20 border border-emerald-200 dark:border-emerald-800 px-4 py-3 text-sm text-emerald-800 dark:text-emerald-200">
                {{ page.props.flash.success }}
            </div>

            <div v-if="!listings.data || listings.data.length === 0"
                class="text-center py-14 bg-white dark:bg-gray-800 rounded-2xl border border-gray-100 dark:border-gray-700">
                <h3 class="text-lg font-semibold text-gray-900 dark:text-white">No listings yet</h3>
                <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">
                    Create your first Listing to become discoverable.
                </p>
            </div>

            <div v-else class="bg-white dark:bg-gray-800 rounded-2xl border border-gray-100 dark:border-gray-700 overflow-hidden">
                <table class="min-w-full divide-y divide-gray-100 dark:divide-gray-700">
                    <thead class="bg-gray-50 dark:bg-gray-900/40">
                        <tr>
                            <th class="px-4 py-3 text-left text-xs font-bold uppercase tracking-widest text-gray-500">Listing</th>
                            <th class="px-4 py-3 text-left text-xs font-bold uppercase tracking-widest text-gray-500">Type</th>
                            <th class="px-4 py-3 text-left text-xs font-bold uppercase tracking-widest text-gray-500">Status</th>
                            <th class="px-4 py-3 text-left text-xs font-bold uppercase tracking-widest text-gray-500">Organization</th>
                            <th class="px-4 py-3 text-right text-xs font-bold uppercase tracking-widest text-gray-500">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                        <tr v-for="listing in listings.data" :key="listing.id">
                            <td class="px-4 py-3">
                                <div class="text-sm font-semibold text-gray-900 dark:text-white">{{ listing.name }}</div>
                                <div class="text-xs text-gray-400 font-mono truncate">/listing/{{ listing.slug }}</div>
                            </td>
                            <td class="px-4 py-3 text-sm text-gray-600 dark:text-gray-300 capitalize">{{ listing.type }}</td>
                            <td class="px-4 py-3">
                                <span class="inline-flex px-2 py-0.5 rounded-full text-[11px] font-semibold"
                                    :class="listing.status === 'published'
                                        ? 'bg-emerald-100 text-emerald-700 dark:bg-emerald-900/30 dark:text-emerald-300'
                                        : 'bg-gray-100 text-gray-600 dark:bg-gray-700 dark:text-gray-300'">
                                    {{ listing.status }}
                                </span>
                            </td>
                            <td class="px-4 py-3 text-sm text-gray-600 dark:text-gray-300">
                                {{ listing.business?.name || '—' }}
                            </td>
                            <td class="px-4 py-3 text-right space-x-2 whitespace-nowrap">
                                <a :href="`/owner/listings/${listing.id}/edit`"
                                    class="text-sm font-semibold text-primary-600 hover:text-primary-700">Edit</a>

                                <!-- PHASE 11 / WAVE 1D-3 — services are Listing-owned, so
                                     they are managed against an explicit Listing. -->
                                <a :href="`/owner/listings/${listing.id}/services`"
                                    class="text-sm font-semibold text-teal-600 hover:text-teal-700">Services</a>

                                <a :href="`/owner/listings/${listing.id}/contacts`"
                                    class="text-sm font-semibold text-blue-600 hover:text-blue-700">Contacts</a>

                                <a :href="`/owner/listings/${listing.id}/images`"
                                    class="text-sm font-semibold text-purple-600 hover:text-purple-700">Images</a>

                                <a v-if="listing.status === 'published'" :href="`/listing/${listing.slug}`" target="_blank"
                                    class="text-sm font-semibold text-gray-500 hover:text-gray-700">View</a>

                                <form v-if="listing.status !== 'published'" :action="`/owner/listings/${listing.id}/publish`"
                                    method="post" class="inline">
                                    <input type="hidden" name="_token" :value="csrf" />
                                    <button type="submit" class="text-sm font-semibold text-emerald-600 hover:text-emerald-700">Publish</button>
                                </form>
                                <form v-else :action="`/owner/listings/${listing.id}/unpublish`" method="post" class="inline">
                                    <input type="hidden" name="_token" :value="csrf" />
                                    <button type="submit" class="text-sm font-semibold text-amber-600 hover:text-amber-700">Unpublish</button>
                                </form>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </AuthenticatedLayout>
</template>

<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, usePage } from '@inertiajs/vue3';

const props = defineProps({
    listings: { type: Object, required: true },
});

const page = usePage();
const csrf = page.props.csrf_token ?? document.querySelector('meta[name="csrf-token"]')?.content ?? '';
</script>
