<!-- resources/js/Pages/Owner/Locations/Edit.vue -->
<template>
    <AuthenticatedLayout>
        <!-- COMPACT HEADER -->
        <PageHeader color="sky" :breadcrumb="[
            { label: 'Dashboard', href: '/owner/dashboard' },
            { label: business.name, href: `/owner/businesses/${business.id}/edit` },
            { label: 'Locations', href: `/owner/businesses/${business.id}/locations` },
            { label: 'Edit Location' }
        ]">
            <template #icon>
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                    d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z" />
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                    d="M15 11a3 3 0 11-6 0 3 3 0 016 0z" />
            </template>
            <template #title>{{ location.name || 'Unnamed Location' }}</template>
            <template #subtitle>{{ business.name }}</template>
            <template #actions>
                <span :class="statusClass(location.status)">
                    {{ location.status_label || location.status }}
                </span>
                <a :href="`/owner/businesses/${business.id}/locations`"
                    class="inline-flex items-center gap-2 px-4 py-2 border border-gray-300 dark:border-gray-600 text-gray-700 dark:text-gray-300 rounded-xl hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors text-sm font-semibold">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M10 19l-7-7m0 0l7-7m-7 7h18" />
                    </svg>
                    Back
                </a>
            </template>
        </PageHeader>

        <div class="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8 py-8 space-y-6">

            <!-- LOCATION SUMMARY -->
            <div
                class="bg-white dark:bg-gray-800 rounded-2xl border border-gray-100 dark:border-gray-700 shadow-sm p-6">
                <div class="flex items-center gap-4">
                    <div
                        class="w-14 h-14 rounded-2xl bg-gradient-to-br from-sky-500 to-blue-600 flex items-center justify-center text-white text-xl font-bold flex-shrink-0 shadow-md">
                        {{ getInitials(location.name || 'Location') }}
                    </div>
                    <div class="min-w-0 flex-1">
                        <h3 class="text-lg font-bold text-gray-900 dark:text-white truncate tracking-tight">
                            {{ location.name || 'Unnamed Location' }}
                        </h3>
                        <p class="text-sm text-gray-500 dark:text-gray-400 flex items-center gap-1.5 mt-0.5">
                            <svg class="w-3.5 h-3.5 text-gray-400" fill="none" stroke="currentColor"
                                viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z" />
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M15 11a3 3 0 11-6 0 3 3 0 016 0z" />
                            </svg>
                            {{ location.full_address || 'No address set' }}
                        </p>
                        <div class="flex items-center gap-3 mt-1.5 flex-wrap">
                            <span v-if="location.is_primary"
                                class="inline-flex items-center gap-1 px-2 py-0.5 text-[10px] font-bold uppercase tracking-wide bg-blue-100 dark:bg-blue-900/30 text-blue-700 dark:text-blue-400 rounded-full">
                                <svg class="w-2.5 h-2.5" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd"
                                        d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z"
                                        clip-rule="evenodd" />
                                </svg>
                                Primary
                            </span>
                            <span class="text-[11px] text-gray-400">ID #{{ location.id }}</span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- FORM -->
            <div
                class="bg-white dark:bg-gray-800 rounded-2xl border border-gray-100 dark:border-gray-700 shadow-sm overflow-hidden">
                <div
                    class="px-6 py-4 border-b border-gray-100 dark:border-gray-700 bg-gradient-to-br from-gray-50 to-white dark:from-gray-800 dark:to-gray-800/50 flex items-center gap-3">
                    <div
                        class="w-9 h-9 rounded-xl bg-primary-50 dark:bg-primary-900/30 flex items-center justify-center">
                        <svg class="w-4 h-4 text-primary-600 dark:text-primary-400" fill="none" stroke="currentColor"
                            viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                        </svg>
                    </div>
                    <div>
                        <h3 class="text-sm font-bold text-gray-900 dark:text-white tracking-tight">Location information
                        </h3>
                        <p class="text-xs text-gray-400">Fields marked * are required</p>
                    </div>
                </div>

                <div class="p-6">
                    <form @submit.prevent="submit" class="space-y-6">
                        <!-- Name -->
                        <div>
                            <label
                                class="block text-xs font-bold text-gray-500 dark:text-gray-400 uppercase tracking-widest mb-2">
                                Location name
                            </label>
                            <input type="text" v-model="form.name" maxlength="100"
                                class="w-full px-4 py-2.5 border border-gray-200 dark:border-gray-600 rounded-xl bg-white dark:bg-gray-900 text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-primary-500 focus:border-transparent transition-colors text-sm"
                                placeholder="e.g., Buea Office" />
                            <div class="flex justify-end mt-1.5 text-xs">
                                <span :class="{
                                    'text-gray-400 dark:text-gray-500': (form.name?.length || 0) < 80,
                                    'text-amber-600 dark:text-amber-400': (form.name?.length || 0) >= 80 && (form.name?.length || 0) < 100,
                                    'text-red-600 dark:text-red-400 font-semibold': (form.name?.length || 0) >= 100,
                                }">
                                    {{ form.name?.length || 0 }} / 100
                                </span>
                            </div>
                        </div>

                        <!-- Primary toggle -->
                        <div
                            class="flex items-center gap-3 p-4 bg-gray-50 dark:bg-gray-900/50 rounded-xl border border-gray-100 dark:border-gray-700">
                            <div class="relative">
                                <input type="checkbox" v-model="form.is_primary" id="is_primary" class="peer sr-only" />
                                <label for="is_primary"
                                    class="block w-10 h-6 bg-gray-300 dark:bg-gray-600 rounded-full peer-checked:bg-primary-600 transition-colors cursor-pointer">
                                    <span
                                        class="block w-5 h-5 bg-white rounded-full shadow-md transform transition-transform duration-200 peer-checked:translate-x-4 mt-0.5 ml-0.5"></span>
                                </label>
                            </div>
                            <label for="is_primary" class="text-sm text-gray-700 dark:text-gray-300 cursor-pointer">
                                Mark as primary location
                            </label>
                        </div>

                        <!-- Location group -->
                        <div>
                            <p class="text-[10px] uppercase tracking-widest text-gray-400 font-bold mb-3">Location</p>
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                <div>
                                    <label
                                        class="block text-xs font-medium text-gray-600 dark:text-gray-400 mb-1.5">Country
                                        *</label>
                                    <select v-model="form.country_id" @change="loadRegions"
                                        class="w-full px-4 py-2.5 border border-gray-200 dark:border-gray-600 rounded-xl bg-white dark:bg-gray-900 text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-primary-500 focus:border-transparent transition-colors appearance-none text-sm"
                                        required>
                                        <option value="">Select Country</option>
                                        <option v-for="country in countries" :key="country.id" :value="country.id">
                                            {{ country.name }}
                                        </option>
                                    </select>
                                </div>

                                <div>
                                    <label
                                        class="block text-xs font-medium text-gray-600 dark:text-gray-400 mb-1.5">Region
                                        *</label>
                                    <select v-model="form.region_id" @change="loadCities"
                                        class="w-full px-4 py-2.5 border border-gray-200 dark:border-gray-600 rounded-xl bg-white dark:bg-gray-900 text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-primary-500 focus:border-transparent transition-colors appearance-none text-sm"
                                        required>
                                        <option value="">Select Region</option>
                                        <option v-for="region in availableRegions" :key="region.id" :value="region.id">
                                            {{ region.name }}
                                        </option>
                                    </select>
                                </div>

                                <div>
                                    <label
                                        class="block text-xs font-medium text-gray-600 dark:text-gray-400 mb-1.5">City
                                        *</label>
                                    <select v-model="form.city_id" @change="loadAreas"
                                        class="w-full px-4 py-2.5 border border-gray-200 dark:border-gray-600 rounded-xl bg-white dark:bg-gray-900 text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-primary-500 focus:border-transparent transition-colors appearance-none text-sm"
                                        required>
                                        <option value="">Select City</option>
                                        <option v-for="city in availableCities" :key="city.id" :value="city.id">
                                            {{ city.name }}
                                        </option>
                                    </select>
                                </div>

                                <div>
                                    <label
                                        class="block text-xs font-medium text-gray-600 dark:text-gray-400 mb-1.5">Area</label>
                                    <select v-model="form.area_id"
                                        class="w-full px-4 py-2.5 border border-gray-200 dark:border-gray-600 rounded-xl bg-white dark:bg-gray-900 text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-primary-500 focus:border-transparent transition-colors appearance-none text-sm">
                                        <option value="">Select Area</option>
                                        <option v-for="area in availableAreas" :key="area.id" :value="area.id">
                                            {{ area.name }}
                                        </option>
                                    </select>
                                </div>
                            </div>
                        </div>

                        <!-- Address -->
                        <div>
                            <label class="block text-xs font-medium text-gray-600 dark:text-gray-400 mb-1.5">Street
                                address</label>
                            <input type="text" v-model="form.address" maxlength="100"
                                class="w-full px-4 py-2.5 border border-gray-200 dark:border-gray-600 rounded-xl bg-white dark:bg-gray-900 text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-primary-500 focus:border-transparent transition-colors text-sm"
                                placeholder="Street address, building name" />
                            <div class="flex justify-between mt-1.5 text-xs">
                                <span class="text-gray-400 dark:text-gray-500">Street address (use the map pin below for coordinates)</span>
                                <span :class="{
                                    'text-gray-400 dark:text-gray-500': (form.address?.length || 0) < 80,
                                    'text-amber-600 dark:text-amber-400': (form.address?.length || 0) >= 80 && (form.address?.length || 0) < 100,
                                    'text-red-600 dark:text-red-400 font-semibold': (form.address?.length || 0) >= 100,
                                }">
                                    {{ form.address?.length || 0 }} / 100
                                </span>
                            </div>
                        </div>

                        <!-- ✅ Map pin -->
                        <LocationPicker v-model:latitude="form.latitude" v-model:longitude="form.longitude"
                            :address="form.address" :city="availableCities.find(c => c.id === form.city_id)?.name"
                            :region="availableRegions.find(r => r.id === form.region_id)?.name"
                            :country="countries.find(c => c.id === form.country_id)?.name" />

                        <!-- Contact group -->
                        <div>
                            <p class="text-[10px] uppercase tracking-widest text-gray-400 font-bold mb-3">Contact</p>
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                <div>
                                    <label
                                        class="block text-xs font-medium text-gray-600 dark:text-gray-400 mb-1.5">Phone
                                        number</label>
                                    <input type="text" v-model="form.phone"
                                        class="w-full px-4 py-2.5 border border-gray-200 dark:border-gray-600 rounded-xl bg-white dark:bg-gray-900 text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-primary-500 focus:border-transparent transition-colors text-sm"
                                        placeholder="+237 699 123 456" />
                                </div>
                                <div>
                                    <label
                                        class="block text-xs font-medium text-gray-600 dark:text-gray-400 mb-1.5">WhatsApp</label>
                                    <input type="text" v-model="form.whatsapp"
                                        class="w-full px-4 py-2.5 border border-gray-200 dark:border-gray-600 rounded-xl bg-white dark:bg-gray-900 text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-primary-500 focus:border-transparent transition-colors text-sm"
                                        placeholder="+237 699 123 456" />
                                </div>
                            </div>
                        </div>

                        <!-- Status -->
                        <div>
                            <label class="block text-xs font-medium text-gray-600 dark:text-gray-400 mb-1.5">Status
                                *</label>
                            <select v-model="form.status"
                                class="w-full px-4 py-2.5 border border-gray-200 dark:border-gray-600 rounded-xl bg-white dark:bg-gray-900 text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-primary-500 focus:border-transparent transition-colors appearance-none text-sm"
                                required>
                                <option value="active">Active</option>
                                <option value="temporarily_unavailable">Temporarily Unavailable</option>
                                <option value="unlisted">Unlisted</option>
                            </select>
                        </div>

                        <!-- Actions -->
                        <div
                            class="flex flex-col sm:flex-row justify-end gap-3 pt-6 border-t border-gray-100 dark:border-gray-700">
                            <a :href="`/owner/businesses/${business.id}/locations`"
                                class="inline-flex items-center justify-center px-6 py-2.5 border border-gray-300 dark:border-gray-600 text-gray-700 dark:text-gray-300 rounded-xl hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors font-semibold text-sm">
                                Cancel
                            </a>
                            <button type="submit" :disabled="processing"
                                class="inline-flex items-center justify-center px-6 py-2.5 bg-gradient-to-r from-primary-600 to-primary-700 text-white rounded-xl hover:from-primary-700 hover:to-primary-800 transition-all shadow-lg shadow-primary-500/25 hover:shadow-primary-500/40 hover:-translate-y-0.5 font-semibold text-sm disabled:opacity-50 disabled:cursor-not-allowed disabled:hover:translate-y-0">
                                <span v-if="processing" class="flex items-center gap-2">
                                    <svg class="animate-spin h-4 w-4" fill="none" viewBox="0 0 24 24">
                                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor"
                                            stroke-width="4"></circle>
                                        <path class="opacity-75" fill="currentColor"
                                            d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z">
                                        </path>
                                    </svg>
                                    Saving...
                                </span>
                                <span v-else class="flex items-center gap-2">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M5 13l4 4L19 7" />
                                    </svg>
                                    Update Location
                                </span>
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <!-- Sticky mobile actions -->
        <StickyFormActions :dirty="isDirty" :can-save="!!form.country_id && !!form.region_id && !!form.city_id"
            :processing="processing" save-label="Update Location" @save="submit"
            @cancel="() => router.visit(`/owner/businesses/${business.id}/locations`)" />
    </AuthenticatedLayout>
</template>

<script setup>
    import { ref, reactive, watch, onMounted, onBeforeUnmount, computed, nextTick } from 'vue';
    import { router } from '@inertiajs/vue3';
    import { useToast } from '@/composables/useToast';
    import axios from 'axios';
    import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
    import PageHeader from '@/Components/PageHeader.vue';
    import StickyFormActions from '@/Components/Owner/StickyFormActions.vue';
    import LocationPicker from '@/Components/Owner/LocationPicker.vue';
    import { useStatusBadge } from '@/composables/useStatusBadge';

    const props = defineProps({
        business: Object,
        location: Object,
        countries: Array,
    });

    const { error } = useToast();
    const { location: statusClass } = useStatusBadge();
    const processing = ref(false);

    const form = reactive({
        name: props.location.name || '',
        is_primary: props.location.is_primary || false,
        country_id: props.location.country_id || '',
        region_id: props.location.region_id || '',
        city_id: props.location.city_id || '',
        area_id: props.location.area_id || '',
        address: props.location.address || '',
        latitude: props.location.latitude || null,
        longitude: props.location.longitude || null,
        phone: props.location.phone || '',
        whatsapp: props.location.whatsapp || '',
        status: props.location.status || 'active',
    });

    const initialForm = JSON.stringify({
        name: props.location.name || '',
        is_primary: props.location.is_primary || false,
        country_id: props.location.country_id || '',
        region_id: props.location.region_id || '',
        city_id: props.location.city_id || '',
        area_id: props.location.area_id || '',
        address: props.location.address || '',
        latitude: props.location.latitude || null,
        longitude: props.location.longitude || null,
        phone: props.location.phone || '',
        whatsapp: props.location.whatsapp || '',
        status: props.location.status || 'active',
    });

    const isDirty = computed(() => JSON.stringify(form) !== initialForm);

    const availableRegions = ref([]);
    const availableCities = ref([]);
    const availableAreas = ref([]);

    const getInitials = (name) => {
        if (!name) return '?';
        return name.split(' ').map(n => n[0]).join('').toUpperCase().slice(0, 2);
    };

    const loadRegions = async () => {
        if (!form.country_id) { availableRegions.value = []; return; }
        try {
            const response = await axios.get(`/api/locations/regions?country_id=${form.country_id}`);
            availableRegions.value = response.data;
        } catch (e) { console.error(e); availableRegions.value = []; }
    };

    const loadCities = async () => {
        if (!form.region_id) { availableCities.value = []; return; }
        try {
            const response = await axios.get(`/api/locations/cities?region_id=${form.region_id}`);
            availableCities.value = response.data;
        } catch (e) { console.error(e); availableCities.value = []; }
    };

    const loadAreas = async () => {
        if (!form.city_id) { availableAreas.value = []; return; }
        try {
            const response = await axios.get(`/api/locations/areas?city_id=${form.city_id}`);
            availableAreas.value = response.data;
        } catch (e) { console.error(e); availableAreas.value = []; }
    };

    watch(() => form.country_id, () => loadRegions());
    watch(() => form.region_id, () => loadCities());
    watch(() => form.city_id, () => loadAreas());

    onMounted(() => {
        if (form.country_id) loadRegions();
        if (form.region_id) loadCities();
        if (form.city_id) loadAreas();
    });

    const submit = () => {
        processing.value = true;
        router.put(`/owner/businesses/${props.business.id}/locations/${props.location.id}`, form, {
            preserveScroll: true,
            onFinish: () => { processing.value = false; },
            // ✅ No onSuccess toast — the controller flashes
            //    'Location updated successfully.' and AuthenticatedLayout
            //    shows it. Also no manual router.visit() — the server
            //    already redirects to the locations index, so Inertia
            //    follows that redirect automatically. The extra visit
            //    was causing a second page load (and a second toast).
            onError: (errors) => {
                // Client-side-only: server rejected the request
                // (validation, permission, etc.) before any flash.
                const firstError = Object.values(errors)[0];
                error('Update Failed ❌', firstError || 'Failed to update location.', { duration: 5000 });
            },
        });
    };
</script>