<!-- resources/js/Components/Public/ListingCard.vue -->
<template>
    <Link :href="`/listing/${listing.slug}`" :class="['block group h-full', compact ? 'business-card-compact' : '']">
        <div
            class="bg-white dark:bg-gray-800 rounded-2xl shadow-sm border border-gray-100 dark:border-gray-700 hover:shadow-xl transition-all duration-300 overflow-hidden h-full hover:-translate-y-1">

            <!-- ============== IMAGE ============== -->
            <div class="relative w-full aspect-[16/10] sm:aspect-[4/3] bg-gray-100 dark:bg-gray-700 overflow-hidden">
                <!-- Cover Image -->
                <template v-if="listing.cover_image">
                    <OptimizedImage :path="listing.cover_image" :name="listing.name" size="thumb" :alt="listing.name"
                        img-class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-500"
                        fallback-class="w-full h-full flex items-center justify-center bg-gradient-to-br from-primary-500 to-primary-600" />
                </template>
                <!-- Logo (only when NO cover image exists) -->
                <div v-else-if="listing.logo"
                    class="w-full h-full flex items-center justify-center bg-gradient-to-br from-gray-50 to-gray-100 dark:from-gray-700 dark:to-gray-800 p-3 sm:p-6">
                    <OptimizedImage :path="listing.logo" :name="listing.name" size="thumb" :alt="listing.name"
                        img-class="w-14 h-14 sm:w-28 sm:h-28 object-contain group-hover:scale-110 transition-transform duration-300"
                        fallback-class="w-14 h-14 sm:w-28 sm:h-28 flex items-center justify-center text-2xl font-bold text-primary-600" />
                </div>
                <!-- Fallback: Gradient with initials -->
                <div v-else
                    class="w-full h-full flex items-center justify-center bg-gradient-to-br from-primary-500 to-primary-600">
                    <span class="text-2xl sm:text-5xl font-bold text-white opacity-90">
                        {{ getInitials(listing.name) }}
                    </span>
                </div>

                <!-- Featured Badge (top-left) -->
                <div v-if="listing.is_featured"
                    class="absolute top-2 left-2 sm:top-2.5 sm:left-2.5 px-1.5 py-0.5 sm:px-2 sm:py-1 bg-gradient-to-r from-yellow-400 to-yellow-500 text-yellow-900 text-[9px] sm:text-[10px] font-bold rounded-full flex items-center gap-1 shadow-lg shadow-yellow-200/50 dark:shadow-yellow-900/30">
                    <svg class="w-2.5 h-2.5" fill="currentColor" viewBox="0 0 20 20">
                        <path
                            d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z" />
                    </svg>
                    Featured
                </div>

                <!-- Favorite Heart (top-right) -->
                <button @click.prevent="toggleFavorite" :disabled="processing" :class="[
                    'absolute top-2 right-2 w-6 h-6 sm:w-7 sm:h-7 rounded-full flex items-center justify-center transition-all backdrop-blur-sm shadow-md',
                    isFavorited
                        ? 'bg-white/90 dark:bg-gray-800/90 text-red-500'
                        : 'bg-black/30 dark:bg-black/50 text-white hover:bg-black/50 dark:hover:bg-black/70',
                    processing ? 'opacity-50 cursor-wait' : ''
                ]" :title="isFavorited ? 'Remove from favorites' : 'Add to favorites'">
                    <svg class="w-3.5 h-3.5" :fill="isFavorited ? 'currentColor' : 'none'" stroke="currentColor"
                        stroke-width="2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round"
                            d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z" />
                    </svg>
                </button>

                <!-- Open Now / Special hours / Closed today Pill (bottom-left) -->
                <div v-if="hasSpecialHoursToday"
                    class="absolute bottom-2 left-2 sm:bottom-2.5 sm:left-2.5 px-1.5 py-0.5 sm:px-2 sm:py-1 bg-amber-500 text-white text-[9px] sm:text-[10px] font-bold rounded-full shadow-lg shadow-amber-500/30 flex items-center gap-1 sm:gap-1.5"
                    :title="overrideNote || 'Special hours today'">
                    <span class="w-1 h-1 sm:w-1.5 sm:h-1.5 bg-white rounded-full"></span>
                    <span class="truncate max-w-[120px] sm:max-w-none">Special hours{{ specialHoursText ? ' · ' +
                        specialHoursText : ''
                        }}</span>
                </div>
                <div v-else-if="hasOverrideToday"
                    class="absolute bottom-2 left-2 sm:bottom-2.5 sm:left-2.5 px-1.5 py-0.5 sm:px-2 sm:py-1 bg-red-500 text-white text-[9px] sm:text-[10px] font-bold rounded-full shadow-lg shadow-red-500/30 flex items-center gap-1 sm:gap-1.5"
                    :title="overrideNote || 'Closed today'">
                    <span class="w-1 h-1 sm:w-1.5 sm:h-1.5 bg-white rounded-full"></span>
                    Closed today
                </div>
                <div v-else-if="hasOpenLocation"
                    class="absolute bottom-2 left-2 sm:bottom-2.5 sm:left-2.5 px-1.5 py-0.5 sm:px-2 sm:py-1 bg-green-500 text-white text-[9px] sm:text-[10px] font-bold rounded-full shadow-lg shadow-green-500/30 flex items-center gap-1 sm:gap-1.5">
                    <span class="w-1 h-1 sm:w-1.5 sm:h-1.5 bg-white rounded-full animate-pulse"></span>
                    {{ getOpenStatusLabel() }}
                </div>
            </div>

            <!-- ============== CONTENT ============== -->
            <div class="p-2.5 sm:p-4">
                <!-- PHASE 16C — Listing TYPE is now visible to visitors.
                     Phase 16A found ListingType existed in the backend but was
                     invisible in the UI. It renders nothing for an unknown or
                     future enum value, so the layout never depends on it. -->
                <ListingTypeBadge :type="listing.type" class="mb-1" />

                <!-- Listing Name -->
                <h3
                    class="text-heading-sm sm:text-heading-md text-ink dark:text-white group-hover:text-primary-600 dark:group-hover:text-primary-400 transition-colors duration-fast line-clamp-1 leading-snug">
                    {{ listing.name }}
                </h3>

                <!-- Description (1 line) -->
                <p class="text-[11px] sm:text-sm text-gray-500 dark:text-gray-400 line-clamp-1 mt-0.5 leading-snug">
                    {{ listing.description || 'No description available' }}
                </p>

                <!-- Rating + Reviews + Category -->
                <div class="flex items-center gap-1.5 mt-1.5 text-[11px] sm:text-sm text-gray-600 dark:text-gray-400 min-w-0">
                    <svg class="w-3.5 h-3.5 text-yellow-400 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                        <path
                            d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z" />
                    </svg>
                    <span class="font-semibold text-gray-900 dark:text-white">{{ formatRating(listing.average_rating)
                        }}</span>
                    <span class="text-gray-400 dark:text-gray-500">·</span>
                    <span class="text-gray-500 dark:text-gray-400 truncate min-w-0">{{ listing.reviews_count ||
                        listing.total_reviews || 0
                        }}
                        review{{ (listing.reviews_count || listing.total_reviews) === 1 ? '' : 's' }}</span>
                    <span v-if="getPrimaryCategory() && getPrimaryCategory() !== 'Uncategorized'"
                        class="text-gray-400 dark:text-gray-500">·</span>
                    <span v-if="getPrimaryCategory() && getPrimaryCategory() !== 'Uncategorized'"
                        class="text-gray-500 dark:text-gray-400 truncate min-w-0">{{ getPrimaryCategory() }}</span>
                </div>
            </div>
        </div>
    </Link>
</template>

<script setup>
    import { ref, computed } from 'vue';
    import { Link, router, usePage } from '@inertiajs/vue3';
    import { useToast } from '@/composables/useToast';
    import OptimizedImage from '@/Components/Public/OptimizedImage.vue';
    import axios from 'axios';

    const props = defineProps({
        listing: {
            type: Object,
            required: true
        },
        compact: {
            type: Boolean,
            default: false,
        },
    });

    const page = usePage();
    const { success, error } = useToast();

    // ✅ Initialize from server data
    const isFavorited = ref(props.listing.is_favorited || false);
    const processing = ref(false);

        const isLoggedIn = computed(() => !!page.props.auth?.user);

    // ✅ Unified location list — accepts either `locations` or the
    //    legacy `branches` payload key, which three resources still emit.
    const locations = computed(() => props.listing.locations || props.listing.branches || []);

    // ============== Open Status ==============

    const hasOpenLocation = computed(() => {
        if (!locations.value || locations.value.length === 0) {
            return props.listing.is_open_now || false;
        }
        return locations.value.some(b => b.is_open_now);
    });


    // ✅ Override today — business-wide (any location with an override today)
    const hasOverrideToday = computed(() => {
        if (!locations.value || locations.value.length === 0) {
            return false;
        }
        return locations.value.some(b => b.has_override_today);
    });

    // First override note we find, for the tooltip
    const overrideNote = computed(() => {
        const location = locations.value.find(b => b.has_override_today);
        return location?.override_note || null;
    });

    // ✅ Special hours override today — business-wide (any location)
    const hasSpecialHoursToday = computed(() => {
        if (!locations.value || locations.value.length === 0) {
            return false;
        }
        return locations.value.some(b => b.is_special_hours);
    });

    // ✅ "10 AM–2 PM" from the first location with special hours today
    const specialHoursText = computed(() => {
        const location = locations.value.find(b => b.is_special_hours);
        if (!location?.override_opens_at || !location?.override_closes_at) return null;

        try {
            const open = new Date('2000-01-01T' + location.override_opens_at).toLocaleTimeString('en-US', {
                hour: 'numeric', minute: '2-digit', hour12: true
            }).replace(':00', '');
            const close = new Date('2000-01-01T' + location.override_closes_at).toLocaleTimeString('en-US', {
                hour: 'numeric', minute: '2-digit', hour12: true
            }).replace(':00', '');
            return `${open}–${close}`;
        } catch (e) {
            return null;
        }
    });

    const locationStatusCounts = computed(() => {
        if (!locations.value || locations.value.length === 0) {
            return { open: 0, closed: 0 };
        }
        const open = locations.value.filter(b => b.is_open_now).length;
        const closed = locations.value.filter(b => !b.is_open_now).length;
        return { open, closed };
    });

    const getOpenStatusLabel = () => {
        const { open, closed } = locationStatusCounts.value;
        const total = open + closed;

        if (open === 0) return 'Closed';
        if (closed === 0) return 'Open';
        if (open === total) return 'Open';
        return `${open} open`;
    };

    // ============== Format Helpers ==============

    const formatRating = (value) => {
        if (!value && value !== 0) return '0.0';
        const num = Number(value);
        if (isNaN(num)) return '0.0';
        return num.toFixed(1);
    };

    // ============== Image Helpers ==============

    const getLogoUrl = () => {
        if (props.listing.logo_url) {
            return props.listing.logo_url;
        }
        if (props.listing.logo && typeof props.listing.logo === 'object' && props.listing.logo.path) {
            return '/storage/' + props.listing.logo.path;
        }
        if (props.listing.logo && typeof props.listing.logo === 'string') {
            if (props.listing.logo.startsWith('http://') || props.listing.logo.startsWith('https://')) {
                return props.listing.logo;
            }
            return '/storage/' + props.listing.logo;
        }
        return null;
    };

    const getCoverImageUrl = () => {
        if (props.listing.cover_image_url) {
            return props.listing.cover_image_url;
        }
        if (props.listing.cover_image && typeof props.listing.cover_image === 'object' && props.listing.cover_image.path) {
            return '/storage/' + props.listing.cover_image.path;
        }
        if (props.listing.cover_image && typeof props.listing.cover_image === 'string') {
            if (props.listing.cover_image.startsWith('http://') || props.listing.cover_image.startsWith('https://')) {
                return props.listing.cover_image;
            }
            return '/storage/' + props.listing.cover_image;
        }
        return null;
    };

    // ============== Helpers ==============

    const getInitials = (name) => {
        if (!name) return '?';
        return name
            .split(' ')
            .map(n => n[0])
            .join('')
            .toUpperCase()
            .slice(0, 2);
    };

    const getPrimaryCategory = () => {
        if (!props.listing.categories || props.listing.categories.length === 0) {
            return 'Uncategorized';
        }
        if (props.listing.categories[0]?.pivot) {
            const primary = props.listing.categories.find(c => c.pivot?.is_primary);
            return primary?.name || props.listing.categories[0]?.name || 'Uncategorized';
        }
        return props.listing.categories[0]?.name || 'Uncategorized';
    };

    // ============== Favorites ==============

    const toggleFavorite = async () => {
        if (processing.value) return;

        if (!isLoggedIn.value) {
            router.visit('/login?redirect=' + encodeURIComponent(window.location.pathname));
            return;
        }

        processing.value = true;

        const previousState = isFavorited.value;
        isFavorited.value = !previousState;

        try {
            const response = await axios.post(`/favorites/${props.listing.id}/toggle`, {}, {
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content,
                },
            });

            if (response.data.success) {
                isFavorited.value = response.data.is_favorited;

                if (response.data.is_favorited) {
                    success('Added to favorites ❤️', props.listing.name);
                } else {
                    success('Removed from favorites', props.listing.name);
                }
            }
        } catch (err) {
            isFavorited.value = previousState;
            console.error('Favorite toggle failed:', err);
            error('Error', err.response?.data?.message || 'Failed to update favorite.');
        } finally {
            processing.value = false;
        }
    };

    // ============== Image Error Handler ==============

    const handleImageError = (event) => {
        const target = event.target;
        if (target) {
            target.style.display = 'none';
            const parent = target.parentElement;
            if (parent) {
                const fallback = document.createElement('div');
                fallback.className = 'w-full h-full flex items-center justify-center bg-gradient-to-br from-primary-500 to-primary-600';
                fallback.innerHTML = `<span class="text-4xl sm:text-5xl font-bold text-white opacity-90">${getInitials(props.listing.name)}</span>`;
                parent.appendChild(fallback);
            }
        }
    };
</script>

<style scoped>
    .line-clamp-1 {
        display: -webkit-box;
        -webkit-line-clamp: 1;
        -webkit-box-orient: vertical;
        overflow: hidden;
    }

    /* scrollbar-hide is now defined globally in app.css */

    /* ✅ Compact variant — only when inside a directory grid */
    /* Scoped via :deep() to pierce the wrapper class set by Directory.vue */
    :deep(.directory-grid .business-card-compact) .line-clamp-1 {
        /* no-op marker for future compact overrides */
    }
</style>