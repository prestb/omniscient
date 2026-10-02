<!-- resources/js/Components/Public/LocationsSection.vue -->
<!--
  PHASE 19D - renamed from BranchesSection.
  The component already rendered LOCATIONS (its heading is
  "Locations & Hours" and it iterates a `locations` prop). Only the
  filename carried the retired Branch domain.
-->
<template>
    <!-- No locations: hide section entirely -->
    <div v-if="!locations || locations.length === 0" class="hidden"></div>

    <!-- Unlocked: full details -->
    <div v-else-if="hasAccess"
        class="bg-white dark:bg-gray-800 rounded-2xl shadow-sm border border-gray-100 dark:border-gray-700 overflow-hidden">

        <!-- Header -->
        <div
            class="px-6 py-5 border-b border-gray-100 dark:border-gray-700 bg-gradient-to-br from-orange-50 to-amber-50 dark:from-orange-950/20 dark:to-amber-950/20">
            <div class="flex items-center justify-between flex-wrap gap-3">
                <div class="flex items-center gap-3">
                    <div
                        class="w-10 h-10 rounded-xl bg-white dark:bg-gray-800 shadow-sm flex items-center justify-center">
                        <svg class="w-5 h-5 text-orange-600 dark:text-orange-400" fill="none" stroke="currentColor"
                            viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z" />
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M15 11a3 3 0 11-6 0 3 3 0 016 0z" />
                        </svg>
                    </div>
                    <div>
                        <h2 class="text-lg font-bold text-gray-900 dark:text-white tracking-tight">
                            Locations & Hours
                        </h2>
                        <p class="text-xs text-gray-500 dark:text-gray-400 mt-0.5">
                            {{ locations.length }} {{ locations.length === 1 ? 'location' : 'locations' }}
                        </p>
                    </div>
                </div>

                <div v-if="openCount > 0"
                    class="inline-flex items-center gap-2 px-3 py-1.5 bg-emerald-100 dark:bg-emerald-900/30 text-emerald-700 dark:text-emerald-400 rounded-full">
                    <span class="w-1.5 h-1.5 bg-emerald-500 rounded-full animate-pulse"></span>
                    <span class="text-xs font-bold uppercase tracking-wide">
                        {{ openCount }} open
                    </span>
                </div>
            </div>
        </div>

        <!-- List -->
        <div class="p-6 space-y-4">
            <div v-for="location in sortedLocations" :key="location.id"
                class="border border-gray-200 dark:border-gray-700 rounded-2xl overflow-hidden hover:border-primary-300 dark:hover:border-primary-700 transition-colors">

                <!-- location header -->
                <div class="px-4 py-3 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3" :class="location.is_open_now
                    ? 'bg-emerald-50/60 dark:bg-emerald-950/20'
                    : 'bg-gray-50 dark:bg-gray-900/50'">
                    <div class="flex items-start gap-3 min-w-0">
                        <div class="w-9 h-9 rounded-xl flex items-center justify-center flex-shrink-0" :class="location.is_open_now
                            ? 'bg-emerald-100 dark:bg-emerald-900/40'
                            : 'bg-gray-200 dark:bg-gray-700'">
                            <span class="text-base">🏪</span>
                        </div>
                        <div class="min-w-0">
                            <div class="flex items-center gap-2 flex-wrap">
                                <h3 class="font-bold text-gray-900 dark:text-white truncate tracking-tight">
                                    {{ location.name || 'Main location' }}
                                </h3>
                                <span v-if="location.is_primary"
                                    class="text-[10px] font-bold bg-primary-100 dark:bg-primary-900/40 text-primary-700 dark:text-primary-400 px-2 py-0.5 rounded-full uppercase tracking-wider">
                                    Primary
                                </span>
                            </div>
                            <p class="text-xs text-gray-500 dark:text-gray-400 mt-0.5 truncate">
                                {{ getLocationAddress(location) }}
                            </p>
                        </div>
                    </div>

                    <span
                        class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-bold uppercase tracking-wide flex-shrink-0 self-start sm:self-center"
                        :class="location.is_open_now
                            ? 'bg-emerald-100 dark:bg-emerald-900/40 text-emerald-700 dark:text-emerald-400'
                            : (location.is_special_hours
                                ? 'bg-amber-100 dark:bg-amber-900/40 text-amber-700 dark:text-amber-400'
                                : 'bg-red-100 dark:bg-red-900/40 text-red-700 dark:text-red-400')">
                        <div class="flex items-center gap-1.5 flex-wrap">
                            <!-- Special hours override -->
                            <span v-if="location.is_special_hours"
                                class="inline-flex items-center gap-1 px-2 py-0.5 text-[10px] font-bold uppercase tracking-wide bg-amber-100 dark:bg-amber-900/30 text-amber-700 dark:text-amber-400 rounded-full"
                                :title="location.override_note || 'Special hours today'">
                                <span class="w-1.5 h-1.5 bg-amber-500 rounded-full"></span>
                                Special hours · {{ formatOverrideTime(location) }}
                            </span>
                            <!-- Closed override -->
                            <span v-else-if="location.has_override_today"
                                class="inline-flex items-center gap-1 px-2 py-0.5 text-[10px] font-bold uppercase tracking-wide bg-red-100 dark:bg-red-900/30 text-red-700 dark:text-red-400 rounded-full"
                                :title="location.override_note || 'Closed today'">
                                <span class="w-1.5 h-1.5 bg-red-500 rounded-full"></span>
                                Closed today
                            </span>
                            <!-- Default -->
                            <span v-else class="text-[10px] font-bold uppercase tracking-wide"
                                :class="location.is_open_now ? 'text-emerald-600 dark:text-emerald-400' : 'text-red-500 dark:text-red-400'">
                                {{ location.is_open_now ? "Open" : "Closed" }}
                            </span>
                        </div>
                    </span>
                </div>

                <!-- location details -->
                <div class="p-4 space-y-4">
                    <!-- Contact chips -->
                    <div v-if="location.phone || location.whatsapp" class="flex flex-wrap gap-2">
                        <a v-if="location.phone" :href="`tel:${location.phone}`"
                            class="inline-flex items-center gap-2 px-3 py-1.5 bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-300 rounded-xl text-xs font-semibold hover:bg-gray-200 dark:hover:bg-gray-600 transition-colors">
                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z" />
                            </svg>
                            {{ location.phone }}
                        </a>
                        <a v-if="location.whatsapp" :href="`https://wa.me/${location.whatsapp.replace(/[^0-9]/g, '')}`"
                            target="_blank"
                            class="inline-flex items-center gap-2 px-3 py-1.5 bg-emerald-50 dark:bg-emerald-900/20 text-emerald-700 dark:text-emerald-400 rounded-xl text-xs font-semibold hover:bg-emerald-100 dark:hover:bg-emerald-900/30 transition-colors">
                            <svg class="w-3.5 h-3.5" fill="currentColor" viewBox="0 0 24 24">
                                <path
                                    d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.872.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413Z" />
                            </svg>
                            WhatsApp
                        </a>
                    </div>

                    <!-- Hours -->
                    <div v-if="location.hours && location.hours.length > 0">
                        <button @click="toggleHours(location.id)"
                            class="flex items-center justify-between w-full text-[10px] font-bold text-gray-400 dark:text-gray-500 uppercase tracking-widest mb-2 hover:text-gray-700 dark:hover:text-gray-300 transition-colors">
                            <span>Weekly hours</span>
                            <svg class="w-3.5 h-3.5 transition-transform"
                                :class="expandedLocations.includes(location.id) ? 'rotate-180' : ''" fill="none"
                                stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M19 9l-7 7-7-7" />
                            </svg>
                        </button>

                        <div v-show="expandedLocations.includes(location.id)"
                            class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-2">
                            <div v-for="hour in sortedHours(location.hours)" :key="hour.id"
                                class="flex items-center justify-between px-3 py-2 rounded-xl border transition-colors"
                                :class="isTodayWithOverride(hour.day_of_week, location)
                                    ? (location.is_special_hours
                                        ? 'border-amber-300 dark:border-amber-700 bg-amber-50/60 dark:bg-amber-950/20'
                                        : 'border-red-300 dark:border-red-700 bg-red-50/50 dark:bg-red-950/20')
                                    : (isToday(hour.day_of_week)
                                        ? 'border-primary-300 dark:border-primary-700 bg-primary-50/50 dark:bg-primary-950/20'
                                        : 'border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800')">
                                <span class="text-xs font-bold" :class="isToday(hour.day_of_week)
                                    ? 'text-primary-700 dark:text-primary-400'
                                    : 'text-gray-600 dark:text-gray-400'">
                                    {{ getDayShort(hour.day_of_week) }}
                                    <span v-if="isToday(hour.day_of_week)"
                                        class="text-[10px] ml-1 text-primary-500">Today</span>
                                </span>
                                <span class="text-xs font-semibold" :class="isTodayWithOverride(hour.day_of_week, location)
                                    ? (location.is_special_hours
                                        ? 'text-amber-700 dark:text-amber-400'
                                        : 'text-red-500 dark:text-red-400')
                                    : (hour.is_closed
                                        ? 'text-red-500 dark:text-red-400'
                                        : 'text-gray-900 dark:text-white')">
                                    {{ getEffectiveHourDisplay(hour, location) }}
                                </span>
                            </div>
                        </div>
                    </div>

                    <!-- Directions — render when EITHER a text address OR coordinates exist -->
                    <a v-if="location.address || (location.latitude && location.longitude)" :href="getDirectionsUrl(location)"
                        target="_blank" rel="noopener"
                        class="inline-flex items-center gap-2 text-sm text-primary-600 dark:text-primary-400 hover:text-primary-800 dark:hover:text-primary-300 font-semibold transition-colors">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M9 20l-5.447-2.724A1 1 0 013 16.382V5.618a1 1 0 011.447-.894L9 7m0 13l6-3m-6 3V7m6 10l4.553 2.276A1 1 0 0021 18.382V7.618a1 1 0 00-.553-.894L15 4m0 13V4m0 0L9 7" />
                        </svg>
                        Get directions
                    </a>
                </div>
            </div>
        </div>
    </div>

    <!-- Locked: preview with CTA -->
    <div v-else
        class="bg-white dark:bg-gray-800 rounded-2xl shadow-sm border border-gray-100 dark:border-gray-700 overflow-hidden">

        <div
            class="px-6 py-5 border-b border-gray-100 dark:border-gray-700 bg-gradient-to-br from-orange-50 to-amber-50 dark:from-orange-950/20 dark:to-amber-950/20">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 rounded-xl bg-white dark:bg-gray-800 shadow-sm flex items-center justify-center">
                    <svg class="w-5 h-5 text-orange-600 dark:text-orange-400" fill="none" stroke="currentColor"
                        viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z" />
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M15 11a3 3 0 11-6 0 3 3 0 016 0z" />
                    </svg>
                </div>
                <div>
                    <h2 class="text-lg font-bold text-gray-900 dark:text-white tracking-tight">
                        Locations & Hours
                    </h2>
                    <p class="text-xs text-gray-500 dark:text-gray-400 mt-0.5">
                        {{ locations.length }} {{ locations.length === 1 ? 'location' : 'locations' }}
                    </p>
                </div>
            </div>
        </div>

        <div class="p-6">
            <div class="relative">
                <!-- Blurred preview -->
                <div class="space-y-3 blur-sm select-none pointer-events-none">
                    <div v-for="location in locations.slice(0, 2)" :key="'preview-' + location.id"
                        class="border border-gray-200 dark:border-gray-700 rounded-2xl p-4">
                        <div class="flex items-center justify-between mb-2">
                            <span class="font-bold text-gray-900 dark:text-white">
                                {{ location.name || 'Main location' }}
                            </span>
                            <span
                                class="text-xs px-2 py-1 rounded-full bg-emerald-100 dark:bg-emerald-900/40 text-emerald-700 dark:text-emerald-400 font-bold">
                                Open
                            </span>
                        </div>
                        <p class="text-xs text-gray-500 dark:text-gray-400">
                            {{ getLocationAddress(location) }}
                        </p>
                        <div class="grid grid-cols-2 sm:grid-cols-3 gap-2 mt-3">
                            <div v-for="i in 6" :key="i"
                                class="px-2 py-1 rounded-lg border border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-900/50">
                                <span class="text-xs text-gray-400">Mon 9:00</span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- CTA overlay -->
                <div
                    class="absolute inset-0 bg-gradient-to-t from-white via-white/85 to-transparent dark:from-gray-800 dark:via-gray-800/85 flex items-end justify-center pb-2">
                    <div class="text-center p-6">
                        <div
                            class="w-12 h-12 mx-auto mb-3 rounded-2xl bg-gradient-to-br from-primary-500 to-purple-600 flex items-center justify-center shadow-lg shadow-primary-500/25">
                            <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" />
                            </svg>
                        </div>
                        <h3 class="text-base font-bold text-gray-900 dark:text-white tracking-tight mb-1">
                            Unlock Locations & Hours
                        </h3>
                        <p class="text-sm text-gray-500 dark:text-gray-400 mb-4 max-w-xs mx-auto leading-relaxed">
                            Upgrade to Starter to display all your locations, opening hours, and contact details.
                        </p>
                        <a :href="route('pricing')"
                            class="inline-flex items-center gap-2 px-6 py-2.5 bg-gradient-to-r from-primary-600 to-primary-700 text-white rounded-xl font-semibold hover:from-primary-700 hover:to-primary-800 transition-all shadow-lg shadow-primary-500/30 hover:-translate-y-0.5 text-sm">
                            <span
                                class="text-[10px] font-bold bg-white/20 px-2 py-0.5 rounded-full uppercase tracking-wide">Starter+</span>
                            Upgrade Now
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M13 7l5 5m0 0l-5 5m5-5H6" />
                            </svg>
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

<script setup>
    import { ref, computed } from 'vue';

    const props = defineProps({
        locations: {
            type: Array,
            default: () => [],
        },
        hasAccess: {
            type: Boolean,
            default: false,
        },
    });

    const expandedLocations = ref([]);

    if (props.hasAccess && props.locations.length > 0) {
        const primary = props.locations.find(b => b.is_primary);
        if (primary) {
            expandedLocations.value.push(primary.id);
        }
    }

    const toggleHours = (locationId) => {
        const idx = expandedLocations.value.indexOf(locationId);
        if (idx === -1) {
            expandedLocations.value.push(locationId);
        } else {
            expandedLocations.value.splice(idx, 1);
        }
    };

    const sortedLocations = computed(() => {
        return [...props.locations].sort((a, b) => {
            if (a.is_primary && !b.is_primary) return -1;
            if (!a.is_primary && b.is_primary) return 1;
            return (a.sort_order || 0) - (b.sort_order || 0);
        });
    });

    const openCount = computed(() => {
        return props.locations.filter(b => b.is_open_now).length;
    });

    const isToday = (dayOfWeek) => {
        return dayOfWeek === new Date().getDay();
    };

    const getDayShort = (dayNumber) => {
        const days = ['Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat'];
        return days[dayNumber] || '—';
    };

    const sortedHours = (hours) => {
        if (!hours) return [];
        return [...hours].sort((a, b) => a.day_of_week - b.day_of_week);
    };

    const getLocationAddress = (location) => {
        if (!location) return 'Address not set';
        const parts = [];
        if (location.address) parts.push(location.address);
        if (location.city?.name) parts.push(location.city.name);
        if (location.region?.name) parts.push(location.region.name);
        if (location.country?.name) parts.push(location.country.name);
        return parts.join(', ') || 'Address not set';
    };

    /**
     * ✅ Build a Google Maps directions URL that opens the native app on
     *    mobile (or the browser tab on desktop).
     *
     *    - Prefers coordinates when available — more precise than text address
     *    - Falls back to the address string otherwise
     *    - Uses the universal https://maps.google.com/... format that
     *      iOS and Android both auto-detect and offer to open in the app
     */
    const getDirectionsUrl = (location) => {
        if (!location) return '#';

        const lat = location.latitude;
        const lng = location.longitude;

        // Prefer coordinates (pin lands exactly where the owner placed it)
        const destination = (lat && lng)
            ? `${lat},${lng}`
            : encodeURIComponent(getLocationAddress(location));

        return `https://www.google.com/maps/dir/?api=1&destination=${destination}`;
    };

    const getHourDisplay = (hour) => {
        if (!hour) return 'Not set';
        if (hour.is_closed) return 'Closed';
        if (hour.is_24h) return '24h';
        if (!hour.opens_at || !hour.closes_at) return 'Not set';

        try {
            const open = new Date('2000-01-01T' + hour.opens_at).toLocaleTimeString('en-US', {
                hour: 'numeric', minute: '2-digit', hour12: true
            }).replace(':00', '');
            const close = new Date('2000-01-01T' + hour.closes_at).toLocaleTimeString('en-US', {
                hour: 'numeric', minute: '2-digit', hour12: true
            }).replace(':00', '');
            return `${open}–${close}`;
        } catch (e) {
            return `${hour.opens_at}–${hour.closes_at}`;
        }
    };

    /**
 * ✅ Is the current day the location's today AND does the location have an override?
 */
    const isTodayWithOverride = (dayOfWeek, location) => {
        if (!isToday(dayOfWeek)) return false;
        return !!(location.has_override_today || location.is_special_hours);
    };

    /**
     * ✅ Return the effective hour text for a day, respecting today's override.
     */
    const getEffectiveHourDisplay = (hour, location) => {
        if (isToday(hour.day_of_week) && location.is_special_hours) {
            return formatOverrideTime(location);
        }
        if (isToday(hour.day_of_week) && location.has_override_today) {
            return 'Closed';
        }
        return getHourDisplay(hour);
    };

    /**
     * ✅ "10 AM–2 PM" from location.override_opens_at / override_closes_at.
     */
    const formatOverrideTime = (location) => {
        if (!location.override_opens_at || !location.override_closes_at) return '';
        try {
            const open = new Date('2000-01-01T' + location.override_opens_at).toLocaleTimeString('en-US', {
                hour: 'numeric', minute: '2-digit', hour12: true
            }).replace(':00', '');
            const close = new Date('2000-01-01T' + location.override_closes_at).toLocaleTimeString('en-US', {
                hour: 'numeric', minute: '2-digit', hour12: true
            }).replace(':00', '');
            return `${open}–${close}`;
        } catch (e) {
            return `${location.override_opens_at}–${location.override_closes_at}`;
        }
    };
</script>