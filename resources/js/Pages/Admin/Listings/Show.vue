<!-- resources/js/Pages/Admin/Listings/Show.vue -->
<!--
  PHASE 19C — ADMIN LISTING DETAIL (read-only).

  Answers, for an administrator:
    Who owns this Listing?        What type is it?
    Does it belong to a Business? Does it have a Location?
    Is it published?              What public Listing does it represent?

  The public URL is surfaced deliberately: it closes the loop

      ADMIN -> LISTING -> PUBLIC DISCOVERY -> /listing/{slug}

  A Listing with no Business renders as "Independent" — an intentional state
  under the architecture, never "missing data".
-->
<template>
    <AuthenticatedLayout>
        <Head :title="listing.name" />

        <div class="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8 py-6">
            <Link href="/admin/listings"
                class="inline-flex min-h-11 items-center text-body-sm font-semibold text-primary-600 hover:text-primary-700 dark:text-primary-400 focus:outline-none focus-visible:ring-2 focus-visible:ring-primary-500 rounded-control">
                ← All listings
            </Link>

            <!-- Identity -->
            <div class="mt-4 bg-surface dark:bg-gray-800 rounded-card border border-hairline dark:border-hairline-dark p-6">
                <div class="flex flex-wrap items-start justify-between gap-4">
                    <div class="min-w-0">
                        <h1 class="text-heading-lg text-ink dark:text-white truncate">{{ listing.name }}</h1>
                        <p class="text-body-sm text-ink-muted dark:text-gray-400 mt-0.5">{{ listing.slug }}</p>
                    </div>
                    <div class="flex items-center gap-2">
                        <Badge variant="neutral">{{ listing.listing_type }}</Badge>
                        <Badge :variant="statusVariant(listing.status)">{{ listing.status }}</Badge>
                    </div>
                </div>

                <!-- The canonical public destination. -->
                <a :href="listing.public_url" target="_blank" rel="noopener noreferrer"
                    class="mt-4 inline-flex min-h-11 items-center gap-2 rounded-control bg-primary-600 px-4 text-body font-semibold text-white transition-colors duration-fast hover:bg-primary-700 focus:outline-none focus-visible:ring-2 focus-visible:ring-primary-500 focus-visible:ring-offset-2">
                    View public listing
                    <span class="font-normal opacity-80">{{ listing.public_url }}</span>
                </a>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mt-4">
                <!-- Owner -->
                <section class="bg-surface dark:bg-gray-800 rounded-card border border-hairline dark:border-hairline-dark p-6">
                    <h2 class="text-label uppercase tracking-widest text-ink-muted dark:text-gray-400 mb-3">Owner</h2>
                    <p v-if="owner" class="text-body text-ink dark:text-white">{{ owner.name }}</p>
                    <p v-if="owner" class="text-body-sm text-ink-muted dark:text-gray-400">{{ owner.email }}</p>
                    <p v-else class="text-body text-ink-muted dark:text-gray-400">No owner recorded.</p>
                </section>

                <!-- Organization: optional, and its absence is not an error. -->
                <section class="bg-surface dark:bg-gray-800 rounded-card border border-hairline dark:border-hairline-dark p-6">
                    <h2 class="text-label uppercase tracking-widest text-ink-muted dark:text-gray-400 mb-3">Organization</h2>
                    <template v-if="business">
                        <p class="text-body text-ink dark:text-white">{{ business.name }}</p>
                        <a :href="business.public_url"
                            class="text-body-sm font-semibold text-primary-600 hover:text-primary-700 dark:text-primary-400">
                            {{ business.public_url }}
                        </a>
                    </template>
                    <template v-else>
                        <p class="text-body font-semibold text-ink dark:text-white">Independent</p>
                        <p class="text-body-sm text-ink-muted dark:text-gray-400 mt-0.5">
                            This Listing is not grouped under a Business. A Listing may exist on its own.
                        </p>
                    </template>
                </section>

                <!-- Location: optional. -->
                <section class="bg-surface dark:bg-gray-800 rounded-card border border-hairline dark:border-hairline-dark p-6">
                    <h2 class="text-label uppercase tracking-widest text-ink-muted dark:text-gray-400 mb-3">Location</h2>
                    <template v-if="location">
                        <p v-if="location.address" class="text-body text-ink dark:text-white">{{ location.address }}</p>
                        <p class="text-body-sm text-ink-muted dark:text-gray-400">
                            {{ [location.city, location.region, location.country].filter(Boolean).join(', ') || '—' }}
                        </p>
                    </template>
                    <p v-else class="text-body text-ink-muted dark:text-gray-400">
                        No physical location. Not every Listing has one.
                    </p>
                </section>

                <!-- Categories -->
                <section class="bg-surface dark:bg-gray-800 rounded-card border border-hairline dark:border-hairline-dark p-6">
                    <h2 class="text-label uppercase tracking-widest text-ink-muted dark:text-gray-400 mb-3">Categories</h2>
                    <div v-if="categories.length" class="flex flex-wrap gap-2">
                        <Badge v-for="c in categories" :key="c.id" variant="neutral">{{ c.name }}</Badge>
                    </div>
                    <p v-else class="text-body text-ink-muted dark:text-gray-400">None assigned.</p>
                </section>
            </div>

            <!-- Dates -->
            <section class="bg-surface dark:bg-gray-800 rounded-card border border-hairline dark:border-hairline-dark p-6 mt-4">
                <h2 class="text-label uppercase tracking-widest text-ink-muted dark:text-gray-400 mb-3">Record</h2>
                <dl class="grid grid-cols-1 sm:grid-cols-2 gap-3 text-body-sm">
                    <div>
                        <dt class="text-ink-muted dark:text-gray-400">Created</dt>
                        <dd class="text-ink dark:text-white">{{ listing.created_at || '—' }}</dd>
                    </div>
                    <div>
                        <dt class="text-ink-muted dark:text-gray-400">Published</dt>
                        <dd class="text-ink dark:text-white">{{ listing.published_at || 'Not published' }}</dd>
                    </div>
                </dl>
                <p v-if="listing.description" class="text-body text-ink-muted dark:text-gray-300 mt-4">{{ listing.description }}</p>
            </section>
        </div>
    </AuthenticatedLayout>
</template>

<script setup>
import { Head, Link } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import Badge from '@/Components/Public/ui/Badge.vue';

const props = defineProps({
    listing: { type: Object, required: true },
    owner: { type: Object, default: null },
    // Nullable by architecture: a Listing need not belong to a Business.
    business: { type: Object, default: null },
    location: { type: Object, default: null },
    categories: { type: Array, default: () => [] },
});

const statusVariant = (status) => {
    if (status === 'published') return 'success';
    if (status === 'rejected' || status === 'suspended') return 'danger';
    if (status === 'submitted') return 'warning';
    return 'neutral';
};
</script>
