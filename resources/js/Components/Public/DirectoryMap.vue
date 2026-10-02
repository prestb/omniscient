<!-- resources/js/Components/Public/DirectoryMap.vue -->
<template>
    <div class="relative">
        <!-- Map container -->
        <div ref="mapEl"
             class="w-full rounded-2xl overflow-hidden border border-gray-200 dark:border-gray-700 shadow-sm"
             style="height: calc(100vh - 280px); min-height: 480px;"></div>

        <!-- Top-left overlay: count + near me -->
        <div class="absolute top-4 left-4 z-[400] flex flex-col sm:flex-row items-stretch sm:items-center gap-2">
            <div class="px-3 py-2 bg-white/95 dark:bg-gray-800/95 backdrop-blur-sm rounded-xl shadow-md border border-gray-100 dark:border-gray-700">
                <p class="text-xs font-bold text-gray-900 dark:text-white">
                    {{ visibleCount }} of {{ totalCount }} {{ totalCount === 1 ? 'business' : 'businesses' }}
                </p>
                <p class="text-[10px] text-gray-500 dark:text-gray-400">
                    on the map
                </p>
            </div>

            <button type="button"
                    @click="locateMe"
                    :disabled="locating"
                    class="inline-flex items-center gap-2 px-3 py-2 bg-white/95 dark:bg-gray-800/95 backdrop-blur-sm rounded-xl shadow-md border border-gray-100 dark:border-gray-700 text-xs font-semibold text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors disabled:opacity-60">
                <svg v-if="locating" class="w-4 h-4 animate-spin" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                          d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                </svg>
                <svg v-else class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                          d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z" />
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                          d="M15 11a3 3 0 11-6 0 3 3 0 016 0z" />
                </svg>
                {{ locating ? 'Locating…' : 'Near me' }}
            </button>
        </div>

        <!-- Top-right overlay: distance filter -->
        <div class="absolute top-4 right-4 z-[400]">
            <div class="bg-white/95 dark:bg-gray-800/95 backdrop-blur-sm rounded-xl shadow-md border border-gray-100 dark:border-gray-700 p-2">
                <p class="text-[10px] font-bold text-gray-500 dark:text-gray-400 uppercase tracking-widest mb-1.5 px-1">
                    Distance
                </p>
                <div class="flex flex-wrap gap-1 max-w-[200px] sm:max-w-none">
                    <button v-for="opt in distanceOptions" :key="opt.value"
                            type="button"
                            @click="setDistance(opt.value)"
                            :disabled="opt.value !== null && !hasLocation"
                            class="px-2.5 py-1 rounded-lg text-[11px] font-bold transition-colors"
                            :class="[
                                distance === opt.value
                                    ? 'bg-primary-600 text-white shadow-sm'
                                    : (opt.value !== null && !hasLocation
                                        ? 'bg-gray-100 dark:bg-gray-700 text-gray-400 dark:text-gray-500 cursor-not-allowed'
                                        : 'bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-300 hover:bg-gray-200 dark:hover:bg-gray-600')
                            ]">
                        {{ opt.label }}
                    </button>
                </div>
                <p v-if="!hasLocation && locationError === 'denied'"
                   class="text-[10px] text-amber-600 dark:text-amber-400 mt-1.5 px-1 max-w-[200px]">
                    Enable location for distance filters
                </p>
                <p v-else-if="!hasLocation && locationError"
                   class="text-[10px] text-gray-500 dark:text-gray-400 mt-1.5 px-1 max-w-[200px]">
                    Location unavailable
                </p>
            </div>
        </div>

        <!-- Empty overlay -->
        <div v-if="businessCount === 0"
             class="absolute inset-0 z-[400] flex items-center justify-center bg-black/40 backdrop-blur-sm rounded-2xl">
            <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-2xl border border-gray-100 dark:border-gray-700 p-8 max-w-sm text-center mx-4">
                <div class="w-14 h-14 rounded-full bg-gray-100 dark:bg-gray-700 flex items-center justify-center mx-auto mb-3">
                    <svg class="w-7 h-7 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                              d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z" />
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                              d="M15 11a3 3 0 11-6 0 3 3 0 016 0z" />
                    </svg>
                </div>
                <h3 class="text-base font-bold text-gray-900 dark:text-white tracking-tight mb-1">
                    No listings on the map
                </h3>
                <p class="text-sm text-gray-500 dark:text-gray-400 leading-relaxed">
                    None of the matching businesses have a location set. Try adjusting your filters, or switch
                    back to the list view.
                </p>
            </div>
        </div>
    </div>
</template>

<script setup>
import { ref, computed, watch, onMounted, onBeforeUnmount, nextTick } from 'vue';
import { router } from '@inertiajs/vue3';
import { useGeolocation } from '@/composables/useGeolocation';
import L from 'leaflet';
import 'leaflet/dist/leaflet.css';
import 'leaflet.markercluster';
import 'leaflet.markercluster/dist/MarkerCluster.css';
import 'leaflet.markercluster/dist/MarkerCluster.Default.css';

const props = defineProps({
    businesses: {
        type: Array,
        default: () => [],
    },
});

const mapEl = ref(null);
const businessCount = ref(0);
const clusterGroup = null;
let map = null;
let cluster = null;
let userMarker = null;

// ✅ Geolocation
const { coords, loading: locating, error: locationError, locate } = useGeolocation();
const distance = ref(null); // km, or null = "Any"

const distanceOptions = [
    { value: 1, label: '1 km' },
    { value: 5, label: '5 km' },
    { value: 10, label: '10 km' },
    { value: 25, label: '25 km' },
    { value: 50, label: '50 km' },
    { value: null, label: 'Any' },
];

const hasLocation = computed(() => coords.value !== null);

// ============== FILTERED BUSINESSES ==============
const visibleBusinesses = computed(() => {
    if (!distance.value || !coords.value) return props.businesses;
    const { lat: userLat, lng: userLng } = coords.value;
    return props.businesses.filter((b) => {
        const lat = Number(b.latitude);
        const lng = Number(b.longitude);
        if (isNaN(lat) || isNaN(lng)) return false;
        return haversineKm(userLat, userLng, lat, lng) <= distance.value;
    });
});

const totalCount = computed(() => props.businesses.length);
const visibleCount = computed(() => visibleBusinesses.value.length);

// ============== HAVERSINE ==============
const haversineKm = (lat1, lng1, lat2, lng2) => {
    const R = 6371;
    const toRad = (deg) => (deg * Math.PI) / 180;
    const dLat = toRad(lat2 - lat1);
    const dLng = toRad(lng2 - lng1);
    const a =
        Math.sin(dLat / 2) ** 2 +
        Math.cos(toRad(lat1)) * Math.cos(toRad(lat2)) * Math.sin(dLng / 2) ** 2;
    return 2 * R * Math.asin(Math.sqrt(a));
};

// ============== MAP INIT ==============
const DEFAULT_CENTER = [4.6, 12.5];
const DEFAULT_ZOOM = 6;

const initMap = () => {
    if (!mapEl.value || map) return;

    map = L.map(mapEl.value, {
        center: DEFAULT_CENTER,
        zoom: DEFAULT_ZOOM,
        zoomControl: true,
        attributionControl: true,
        tap: true,
        scrollWheelZoom: true,
    });

    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        maxZoom: 19,
        attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a>',
    }).addTo(map);

    cluster = L.markerClusterGroup({
        showCoverageOnHover: false,
        maxClusterRadius: 50,
        spiderfyOnMaxZoom: true,
        disableClusteringAtZoom: 15,
        iconCreateFunction: (c) => {
            const count = c.getChildCount();
            let size = 'small';
            if (count >= 100) size = 'large';
            else if (count >= 10) size = 'medium';

            const dim = { small: 36, medium: 42, large: 48 }[size];
            const fontSize = { small: '13px', medium: '14px', large: '15px' }[size];

            return L.divIcon({
                html: `<div class="omniscient-cluster-inner" style="
                    width: ${dim}px;
                    height: ${dim}px;
                    border-radius: 50%;
                    background: linear-gradient(135deg, #0284c7 0%, #7c3aed 100%);
                    color: white;
                    display: flex;
                    align-items: center;
                    justify-content: center;
                    font-weight: 700;
                    font-size: ${fontSize};
                    border: 3px solid rgba(255,255,255,0.9);
                    box-shadow: 0 4px 12px rgba(2,132,199,0.35);
                ">${count}</div>`,
                className: 'omniscient-cluster',
                iconSize: L.point(dim, dim),
            });
        },
    });

    map.addLayer(cluster);

    renderMarkers();
};

// ============== MARKERS ==============
const renderMarkers = () => {
    if (!map || !cluster) return;

    cluster.clearLayers();
    businessCount.value = visibleBusinesses.value.length;

    const bounds = [];

    visibleBusinesses.value.forEach((business) => {
        const lat = Number(business.latitude);
        const lng = Number(business.longitude);
        if (isNaN(lat) || isNaN(lng)) return;

        const marker = L.marker([lat, lng], {
            icon: buildPinIcon(business),
        });

        marker.bindPopup(buildPopupHtml(business), {
            closeButton: true,
            autoPanPadding: [40, 40],
            maxWidth: 280,
        });

        cluster.addLayer(marker);
        bounds.push([lat, lng]);
    });

    if (bounds.length > 0) {
        try {
            map.fitBounds(bounds, { padding: [40, 40], maxZoom: 13 });
        } catch (e) {
            // Ignore fitBounds errors on degenerate bounds
        }
    }
};

// ============== PIN ICON ==============
const buildPinIcon = (business) => {
    const isFeatured = business.is_featured;
    const bg = isFeatured
        ? 'linear-gradient(135deg, #f59e0b 0%, #d97706 100%)'
        : 'linear-gradient(135deg, #0284c7 0%, #7c3aed 100%)';

    return L.divIcon({
        className: 'omniscient-map-pin',
        html: `
            <div style="position: relative;">
                <div class="omniscient-pin-inner" style="
                    width: 30px;
                    height: 30px;
                    border-radius: 50% 50% 50% 0;
                    background: ${bg};
                    transform: rotate(-45deg);
                    border: 3px solid white;
                    box-shadow: 0 4px 12px rgba(0,0,0,0.25);
                    display: flex;
                    align-items: center;
                    justify-content: center;
                "></div>
            </div>
        `,
        iconSize: [30, 30],
        iconAnchor: [15, 30],
        popupAnchor: [0, -30],
    });
};

// ============== POPUP HTML ==============
const escapeHtml = (str) => {
    if (str === null || str === undefined) return '';
    return String(str)
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;')
        .replace(/'/g, '&#039;');
};

const buildPopupHtml = (business) => {
    const name = escapeHtml(business.name);
    const category = escapeHtml(business.category);
    const address = escapeHtml(business.address || '');
    // PHASE 16F — the map renders LISTINGS, so the popup must point at the
    // canonical Listing URL. `/business/{listing.slug}` cannot resolve.
    const url = `/listing/${encodeURIComponent(business.slug)}`;

    const cover = business.cover_image_url
        ? `<img src="${escapeHtml(business.cover_image_url)}" alt="${name}"
                style="width: 100%; height: 100%; object-fit: cover;" />`
        : `<div style="
                width: 100%;
                height: 100%;
                background: linear-gradient(135deg, #0284c7 0%, #7c3aed 100%);
                color: white;
                display: flex;
                align-items: center;
                justify-content: center;
                font-weight: 700;
                font-size: 18px;
            ">${escapeHtml(initials(business.name))}</div>`;

    const featured = business.is_featured
        ? `<span class="omniscient-popup-badge-featured" style="
                display: inline-flex;
                align-items: center;
                gap: 3px;
                padding: 2px 6px;
                background: linear-gradient(135deg, #fbbf24 0%, #f59e0b 100%);
                color: #78350f;
                font-size: 9px;
                font-weight: 700;
                border-radius: 9999px;
                text-transform: uppercase;
                letter-spacing: 0.03em;
            ">⭐ Featured</span>`
        : '';

    // PHASE 14 - no "verified" badge on map results.
    //
    // `is_verified` was the owner's PAID `verified_badge` plan feature, so the badge
    // claimed a Listing was verified when nothing had been verified. The field is gone
    // from the payload; this emits nothing rather than turning a paid capability into a
    // trust claim.
    const verified = '';

    const rating = business.rating > 0
        ? `<div class="omniscient-popup-rating" style="display: flex; align-items: center; gap: 4px; font-size: 12px; margin-top: 4px;">
                <span style="color: #fbbf24;">★</span>
                <strong>${Number(business.rating).toFixed(1)}</strong>
                <span class="omniscient-popup-muted">· ${business.reviews_count || 0} review${business.reviews_count === 1 ? '' : 's'}</span>
            </div>`
        : `<div class="omniscient-popup-muted" style="font-size: 11px; margin-top: 4px;">No reviews yet</div>`;

    const addressLine = address
        ? `<div class="omniscient-popup-muted" style="display: flex; align-items: flex-start; gap: 4px; font-size: 11px; margin-top: 6px;">
                <svg width="12" height="12" fill="none" stroke="currentColor" viewBox="0 0 24 24" style="flex-shrink: 0; margin-top: 1px;">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                          d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z" />
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                          d="M15 11a3 3 0 11-6 0 3 3 0 016 0z" />
                </svg>
                <span style="display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical; overflow: hidden;">
                    ${address}
                </span>
            </div>`
        : '';

    return `
        <div class="omniscient-popup" style="font-family: inherit; padding: 0; min-width: 240px;">
            <div style="
                width: 100%;
                height: 120px;
                overflow: hidden;
                border-radius: 8px 8px 0 0;
                margin: -13px -19px 0 -19px;
            ">${cover}</div>
            <div style="padding: 12px 0 0 0;">
                <div style="display: flex; align-items: flex-start; justify-content: space-between; gap: 6px; margin-bottom: 4px;">
                    <div class="omniscient-popup-title" style="font-weight: 700; font-size: 14px; line-height: 1.3;">
                        ${name}
                    </div>
                </div>
                <div style="display: flex; flex-wrap: wrap; gap: 4px;">
                    ${featured}
                    ${verified}
                </div>
                <div class="omniscient-popup-muted" style="font-size: 11px; margin-top: 4px;">
                    ${category}
                </div>
                ${rating}
                ${addressLine}
                <a href="${url}" class="omniscient-popup-cta"
                   style="
                       display: inline-flex;
                       align-items: center;
                       gap: 4px;
                       margin-top: 10px;
                       padding: 6px 12px;
                       background: linear-gradient(135deg, #0284c7 0%, #7c3aed 100%);
                       color: white;
                       font-size: 12px;
                       font-weight: 600;
                       border-radius: 8px;
                       text-decoration: none;
                   ">
                    View profile
                    <svg width="12" height="12" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                    </svg>
                </a>
            </div>
        </div>
    `;
};

const initials = (name) => {
    if (!name) return '?';
    return name
        .split(' ')
        .map((n) => n[0])
        .join('')
        .toUpperCase()
        .slice(0, 2);
};

// ============== NEAR ME ==============
const locateMe = async () => {
    if (!navigator.geolocation) return;

    try {
        const c = await locate();

        // Drop / move the "you are here" marker
        if (userMarker) {
            userMarker.setLatLng([c.lat, c.lng]);
        } else {
            userMarker = L.marker([c.lat, c.lng], {
                icon: L.divIcon({
                    className: 'omniscient-user-pin',
                    html: `
                        <div style="position: relative;">
                            <div style="
                                width: 20px;
                                height: 20px;
                                border-radius: 50%;
                                background: #3b82f6;
                                border: 4px solid white;
                                box-shadow: 0 0 0 4px rgba(59,130,246,0.25), 0 4px 12px rgba(0,0,0,0.2);
                            "></div>
                            <div style="
                                position: absolute;
                                inset: -8px;
                                border-radius: 50%;
                                background: rgba(59,130,246,0.2);
                                animation: pulse-location 2s ease-out infinite;
                            "></div>
                        </div>
                        <style>
                            @keyframes pulse-location {
                                0% { transform: scale(0.8); opacity: 0.7; }
                                100% { transform: scale(1.8); opacity: 0; }
                            }
                        </style>
                    `,
                    iconSize: [20, 20],
                    iconAnchor: [10, 10],
                }),
                interactive: false,
            }).addTo(map);
        }

        map.setView([c.lat, c.lng], 13);
    } catch (e) {
        // Silent — the error state is already tracked by the composable
    }
};

// ============== DISTANCE FILTER ==============
const setDistance = async (value) => {
    // If picking a real distance and we don't have location yet, request it
    if (value !== null && !coords.value) {
        try {
            await locate();
        } catch (e) {
            // Permission denied — don't change filter, error is shown
            return;
        }
    }
    distance.value = value;
};

// ============== WATCH: RE-RENDER ON FILTER / DATA CHANGE ==============
watch(visibleBusinesses, () => {
    renderMarkers();
});

// ============== LIFECYCLE ==============
onMounted(async () => {
    await nextTick();
    initMap();
});

onBeforeUnmount(() => {
    if (map) {
        map.remove();
        map = null;
        cluster = null;
        userMarker = null;
    }
});
</script>

<style>
/* Global — Leaflet injects DOM outside Vue's scope */
.omniscient-map-pin,
.omniscient-cluster,
.omniscient-user-pin {
    background: transparent !important;
    border: none !important;
}

.leaflet-container {
    font-family: inherit;
}

/* Leaflet popups — restyle to match app */
.leaflet-popup-content-wrapper {
    border-radius: 12px;
    padding: 0;
    box-shadow: 0 10px 30px rgba(0, 0, 0, 0.15);
    overflow: hidden;
}

.leaflet-popup-content {
    margin: 13px 19px;
    padding: 0;
    width: auto !important;
    min-width: 240px;
    font-family: inherit;
}

.leaflet-popup-tip {
    box-shadow: 0 3px 10px rgba(0, 0, 0, 0.1);
}

.leaflet-popup-close-button {
    top: 8px !important;
    right: 8px !important;
    width: 24px !important;
    height: 24px !important;
    font-size: 18px !important;
    color: white !important;
    text-shadow: 0 1px 3px rgba(0,0,0,0.5) !important;
    z-index: 10;
}

/* ============================================================
   Dark mode overrides for Leaflet popups + markers.

   Leaflet renders outside Vue, so we can't use Tailwind classes
   directly. The `.dark` class lives on <html>, so we can target
   it from here. Popups get a dark body, dark tip, and light text.

   The POPUP CONTAINER stays white in both themes to match how
   map popups behave on Google Maps / Apple Maps — the "floating
   card" convention. But the inner content colors ARE themed.
   ============================================================ */

/* Popup body: swap to dark on toggle */
.dark .leaflet-popup-content-wrapper {
    background: #1f2937; /* gray-800 */
    color: #f9fafb;     /* gray-50 */
    border: 1px solid #374151; /* gray-700 */
}
.dark .leaflet-popup-content {
    color: #f9fafb;
}
.dark .leaflet-popup-tip {
    background: #1f2937;
    border: 1px solid #374151;
}

/* Popup title — near-black in light, near-white in dark */
.omniscient-popup-title {
    color: #111827;
}
.dark .omniscient-popup-title {
    color: #f9fafb;
}

/* Muted text — gray-500 light, gray-400 dark */
.omniscient-popup-muted {
    color: #6b7280;
}
.dark .omniscient-popup-muted {
    color: #9ca3af;
}

/* Verified badge — light blue in both, but adjust dark slightly */
.dark .omniscient-popup-badge-verified {
    background: rgba(59, 130, 246, 0.2);
    color: #93c5fd;
}

/* Featured badge — amber stays vibrant in both, keep as-is */

/* Close button — always white-on-image over the cover photo */
.dark .leaflet-popup-close-button {
    color: white !important;
    text-shadow: 0 1px 3px rgba(0,0,0,0.6) !important;
}

/* Cluster border — swap to a subtle dark ring in dark mode */
.dark .omniscient-cluster-inner {
    border-color: rgba(255,255,255,0.6) !important;
    box-shadow: 0 4px 12px rgba(0,0,0,0.4) !important;
}

/* Pin border — same treatment */
.dark .omniscient-pin-inner {
    border-color: rgba(255,255,255,0.8) !important;
}
</style>
