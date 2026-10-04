<!-- resources/js/Pages/Public/ListingProfile.vue -->
<template>
    <PublicLayout>
        <!-- PHASE 15B — canonical + social metadata for the canonical entity.
             Skipped entirely when props.seo is absent (older payloads). -->
        <Head v-if="seo">
            <title>{{ seo.title }}</title>
            <meta v-if="seo.description" name="description" :content="seo.description" />
            <link rel="canonical" :href="seo.canonical" />
            <meta property="og:title" :content="seo.title" />
            <meta v-if="seo.description" property="og:description" :content="seo.description" />
            <meta property="og:type" :content="seo.type || 'website'" />
            <meta property="og:url" :content="seo.canonical" />
            <meta v-if="seo.image" property="og:image" :content="seo.image" />
        </Head>

        <!-- ==================== COVER ==================== -->
        <div class="relative h-64 md:h-96 bg-gray-900 overflow-hidden">
            <OptimizedImage v-if="listing.cover_image" :path="listing.cover_image" size="large" :alt="listing.name"
                img-class="w-full h-full object-cover"
                fallback-class="w-full h-full bg-gradient-to-br from-primary-700 via-primary-600 to-primary-800" />
            <div v-else class="w-full h-full bg-gradient-to-br from-primary-700 via-primary-600 to-primary-800"></div>
            <div class="absolute inset-0 bg-gradient-to-t from-black/80 via-black/30 to-black/10"></div>

            <div class="absolute bottom-0 left-0 right-0">
                <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 pb-6">
                    <div class="flex items-end gap-4">
                        <!-- Listing logo is LISTING media, not organization branding.
                             It is supporting identity, never a replacement for the name. -->
                        <div v-if="listing.logo"
                            class="hidden sm:flex h-16 w-16 shrink-0 items-center justify-center overflow-hidden rounded-card border border-white/20 bg-white/95 backdrop-blur">
                            <OptimizedImage :path="listing.logo" :name="listing.name" size="thumb"
                                img-class="h-full w-full object-contain"
                                fallback-class="h-full w-full bg-white" />
                        </div>

                        <div class="min-w-0">
                            <!-- PHASE 16E — the raw enum string ('business') was being
                                 printed straight into the hero. The Listing's TYPE is now
                                 an accessible, human label that renders nothing for an
                                 unknown/future enum value. -->
                            <ListingTypeBadge :type="listing.listing_type" size="sm" class="mb-1.5" />

                            <h1 class="text-heading-xl sm:text-display text-white">{{ listing.name }}</h1>
                        </div>
                    </div>

                    <div class="mt-3 flex flex-wrap items-center gap-x-4 gap-y-2 text-body text-white/85">
                        <span v-if="listing.categories?.length" class="min-w-0">
                            {{ listing.categories.map(c => c.name).join(' · ') }}
                        </span>

                        <!-- Location is OPTIONAL. A locationless Professional is a valid
                             Listing and must render cleanly with no empty placeholder. -->
                        <span v-if="listing.location?.city" class="inline-flex items-center gap-1">
                            <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M17.657 16.657L13.414 20.9a2 2 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z M15 11a3 3 0 11-6 0 3 3 0 016 0z" />
                            </svg>
                            {{ listing.location.city }}
                        </span>

                        <!-- PHASE 14/16C ownership: this is the OWNING BUSINESS's
                             aggregate, shown on the Listing and always attributed. -->
                        <!-- PHASE 16E correction — existing Listing-scoped favorite
                             capability (POST /favorites/{listing}/toggle). A guest is
                             sent to the existing auth flow by the server on 401/403;
                             no Business favorite semantics are involved. -->
                        <button
                            type="button"
                            class="inline-flex min-h-11 items-center gap-1.5 rounded-control px-3 text-body font-semibold transition-colors duration-fast focus:outline-none focus-visible:ring-2 focus-visible:ring-white"
                            :class="favorited ? 'text-white' : 'text-white/80 hover:text-white'"
                            :aria-pressed="String(favorited)"
                            :aria-label="favorited ? 'Remove from saved' : 'Save this listing'"
                            :disabled="savingFavorite"
                            @click="toggleFavorite"
                        >
                            <svg class="h-4 w-4" :fill="favorited ? 'currentColor' : 'none'" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z" />
                            </svg>
                            {{ favorited ? 'Saved' : 'Save' }}
                        </button>

                        <RatingSummary v-if="listing.business_id"
                            :rating="listing.average_rating" :review-count="listing.reviews_count"
                            mode="compact" attribution />
                    </div>
                </div>
            </div>
        </div>

        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8 grid grid-cols-1 lg:grid-cols-3 gap-8">
            <!-- ==================== MAIN ==================== -->
            <div class="lg:col-span-2 space-y-6">
                <section v-if="listing.description" class="bg-surface dark:bg-gray-800 rounded-card border border-hairline dark:border-hairline-dark p-6">
                    <h2 class="text-label uppercase tracking-widest text-ink-muted dark:text-gray-400 mb-3">About</h2>
                    <p class="text-sm text-gray-700 dark:text-gray-300 whitespace-pre-line">{{ listing.description }}</p>
                </section>

                <section v-if="listing.categories?.length" class="bg-surface dark:bg-gray-800 rounded-card border border-hairline dark:border-hairline-dark p-6">
                    <h2 class="text-label uppercase tracking-widest text-ink-muted dark:text-gray-400 mb-3">Categories</h2>
                    <div class="flex flex-wrap gap-2">
                        <span v-for="c in listing.categories" :key="c.id"
                            class="inline-flex px-3 py-1 rounded-full text-xs font-semibold bg-primary-50 dark:bg-primary-900/20 text-primary-700 dark:text-primary-300">
                            {{ c.name }}
                        </span>
                    </div>
                </section>

                <section v-if="listing.locations?.[0]?.hours?.length" class="bg-surface dark:bg-gray-800 rounded-card border border-hairline dark:border-hairline-dark p-6">
                    <h2 class="text-label uppercase tracking-widest text-ink-muted dark:text-gray-400 mb-3">Opening hours</h2>
                    <ul class="text-sm text-gray-700 dark:text-gray-300 space-y-1">
                        <li v-for="h in listing.locations[0].hours" :key="h.day" class="flex justify-between gap-4">
                            <span class="capitalize">Day {{ h.day }}</span>
                            <span v-if="h.is_closed" class="text-gray-400">Closed</span>
                            <span v-else>{{ h.opens_at }} – {{ h.closes_at }}</span>
                        </li>
                    </ul>
                </section>

                <!-- PHASE 11 — no Listing review section. Reviews belong to the
                     owning BUSINESS; this page shows only the Business's aggregate
                     rating/count in the header. Displaying an individual review
                     list here would present a Business-owned resource as a
                     Listing-owned feature. -->

            </div>

            <!-- ==================== SIDEBAR ==================== -->
            <aside class="space-y-6">
                <!-- PHASE 12 — LISTING-ATTRIBUTED CONNECTION.
                     The inquiry is bound to THIS Listing via route model binding.
                     Business context (if any) is derived server-side. A Listing
                     with no Business works identically. -->
                <section id="inquiry"
                    class="bg-surface dark:bg-gray-800 rounded-card border border-hairline dark:border-hairline-dark p-6">
                    <h2 class="text-label uppercase tracking-widest text-ink-muted dark:text-gray-400 mb-3">Send an inquiry</h2>
                    <LeadCaptureForm :listing="listing" />
                </section>
                <!-- PHASE 17 — LISTING-OWNED SERVICES.
                     Services belong to the Listing (Wave 1D-3); they are not
                     copied from a Business and never fanned out across sibling
                     Listings. An empty collection renders nothing. -->
                <section v-if="(listing.services || []).length"
                    class="bg-surface dark:bg-gray-800 rounded-card border border-hairline dark:border-hairline-dark p-6">
                    <h2 class="text-label uppercase tracking-widest text-ink-muted dark:text-gray-400 mb-3">Services</h2>
                    <ul class="flex flex-wrap gap-2">
                        <li v-for="service in listing.services" :key="service.id">
                            <Badge variant="neutral">{{ service.name }}</Badge>
                        </li>
                    </ul>
                </section>

                <!-- PHASE 17 — LISTING-OWNED MEDIA.
                     `listing_images`, never `businesses.logo`/`cover_image`: the
                     two concepts are deliberately separate and are not synced. -->
                <section v-if="(listing.gallery || []).length"
                    class="bg-surface dark:bg-gray-800 rounded-card border border-hairline dark:border-hairline-dark p-6">
                    <h2 class="text-label uppercase tracking-widest text-ink-muted dark:text-gray-400 mb-3">Photos</h2>
                    <ul class="grid grid-cols-2 sm:grid-cols-3 gap-3">
                        <li v-for="img in listing.gallery" :key="img.id">
                            <OptimizedImage :path="img.path" :alt="img.caption || listing.name" size="thumb"
                                img-class="w-full h-28 sm:h-32 object-cover rounded-control"
                                fallback-class="w-full h-28 sm:h-32 rounded-control bg-gray-100 dark:bg-gray-700" />
                        </li>
                    </ul>
                </section>

                <!-- PHASE 17 — LISTING-OWNED CONTACTS.
                     Replaces the dead `listing.location.phone` path: the location
                     payload has never carried a phone, so that tel: link could
                     never render. Values come from `listing_contacts`; blank
                     values are dropped server-side and absent types render no
                     control at all. -->
                <section v-if="(listing.contacts || []).length"
                    class="bg-surface dark:bg-gray-800 rounded-card border border-hairline dark:border-hairline-dark p-6">
                    <h2 class="text-label uppercase tracking-widest text-ink-muted dark:text-gray-400 mb-3">Contact</h2>
                    <ul class="space-y-2">
                        <li v-for="contact in listing.contacts" :key="contact.id">
                            <a v-if="contactHref(contact)" :href="contactHref(contact)"
                                @click="trackContact(contact)"
                                target="_blank" rel="noopener noreferrer"
                                class="inline-flex min-h-11 items-center gap-2 text-body font-semibold text-primary-600 hover:text-primary-700 dark:text-primary-400 focus:outline-none focus-visible:ring-2 focus-visible:ring-primary-500 rounded-control">
                                {{ contactLabel(contact.type) }}
                                <span class="font-normal text-ink-muted dark:text-gray-400">{{ contact.value }}</span>
                            </a>
                            <span v-else class="text-body text-ink-muted dark:text-gray-400">
                                {{ contactLabel(contact.type) }}: {{ contact.value }}
                            </span>
                        </li>
                    </ul>
                </section>
                <!-- Organization context: the Listing stays canonical -->
                <!-- PHASE 21B-F - omitted entirely when the organization page is not
                     publicly reachable. Omission over dead navigation: the Listing
                     stays canonical and fully public either way. -->
                <section v-if="listing.business_id && listing.business_publicly_reachable"
                    class="bg-surface dark:bg-gray-800 rounded-card border border-hairline dark:border-hairline-dark p-6">
                    <h2 class="text-label uppercase tracking-widest text-ink-muted dark:text-gray-400 mb-3">Organization</h2>
                    <a :href="`/business/${listing.business_slug}`"
                        class="text-sm font-semibold text-primary-600 hover:text-primary-700">
                        {{ listing.business_name }}
                    </a>
                    <p class="text-xs text-gray-400 mt-1">This Listing is one presence of that organization.</p>
                </section>

                <!-- Physical place (optional) -->
                <section v-if="listing.location" class="bg-surface dark:bg-gray-800 rounded-card border border-hairline dark:border-hairline-dark p-6">
                    <h2 class="text-label uppercase tracking-widest text-ink-muted dark:text-gray-400 mb-3">Where</h2>
                    <p class="text-sm text-gray-700 dark:text-gray-300">
                        <span v-if="listing.location.address">{{ listing.location.address }}<br /></span>
                        <span v-if="listing.location.city">{{ listing.location.city }}</span>
                    </p>
                </section>
            </aside>
        </div>
    </PublicLayout>
</template>

<script setup>
import PublicLayout from '@/Layouts/PublicLayout.vue';
import OptimizedImage from '@/Components/Public/OptimizedImage.vue';
import LeadCaptureForm from '@/Components/Public/LeadCaptureForm.vue';
// PHASE 16E — the Listing page now consumes the design system rather than
// hand-rolling a type pill, a rating span and raw surface classes.
import ListingTypeBadge from '@/Components/Public/ui/ListingTypeBadge.vue';
import Badge from '@/Components/Public/ui/Badge.vue';
// PHASE 17 — Listing-owned contact links.
import { contactHref, contactLabel, clickTypeFor } from '@/urls';
import RatingSummary from '@/Components/Public/ui/RatingSummary.vue';
import { Head } from '@inertiajs/vue3';
import axios from 'axios';
import { onMounted, ref } from 'vue';

const props = defineProps({
    listing: { type: Object, required: true },
    // PHASE 16E/17 - viewer's own favorite state for THIS Listing.
    // Listing-scoped: favorites are Listing-owned (Wave 1D-5A).
    is_favorited: { type: Boolean, default: false },
    // PHASE 15B - canonical metadata passed by the controller.
    seo: { type: Object, default: null },
});

/**
 * PHASE 11 / WAVE 1D-3 — CANONICAL LISTING TRACKING (PATH A).
 *
 * Analytics are Listing-owned, so every event carries the EXPLICIT Listing id
 * from this page's own props — never a Business id, and never a Listing
 * resolved through a Business.
 */
/**
 * PHASE 18B - a real Listing-owned contact action. Records against
 * the exact Listing via the existing endpoint. trackClick remains
 * the primitive for other public actions.
 */
const trackContact = (contact) => {
    const type = clickTypeFor(contact.type);
    if (type) trackClick(type);
};

const trackClick = (type) => {
    axios.post(`/analytics/listing/${props.listing.id}/track-click/${type}`).catch(() => {});
};

onMounted(() => {
    axios.post(`/analytics/listing/${props.listing.id}/track-view`).catch(() => {});

    /**
     * PHASE 16E/17 - the existing Listing-scoped favorite capability
     * (POST /favorites/{listing}/toggle). Optimistic, reverting on
     * failure, including a guest hitting the auth guard.
     */
    const favorited = ref(props.is_favorited);
    const savingFavorite = ref(false);

    const toggleFavorite = async () => {
        if (savingFavorite.value) return;
        savingFavorite.value = true;
        const previous = favorited.value;
        favorited.value = !previous;

        try {
            const { data } = await axios.post(`/favorites/${props.listing.id}/toggle`);
            favorited.value = data.is_favorited;
        } catch (e) {
            favorited.value = previous;
        } finally {
            savingFavorite.value = false;
        }
    };
});
</script>
