<!-- resources/js/Pages/Admin/Locations/Cities.vue -->
<template>
    <AuthenticatedLayout>
        <!-- COMPACT HEADER -->
        <PageHeader color="emerald" :breadcrumb="[
            { label: 'Admin', href: '/admin/dashboard' },
            { label: 'Locations', href: '/admin/locations/countries' },
            { label: 'Cities' }
        ]">
            <template #icon>
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                    d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" />
            </template>
            <template #title>Cities</template>
            <template #subtitle>Manage cities within regions</template>
            <template #actions>
                <button @click="openCreateModal"
                    class="inline-flex items-center gap-2 px-4 py-2 bg-gradient-to-r from-primary-600 to-primary-700 text-white rounded-xl font-semibold hover:from-primary-700 hover:to-primary-800 transition-all shadow-lg shadow-primary-500/25 text-sm">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                    </svg>
                    Add City
                </button>
            </template>
        </PageHeader>

        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8 space-y-6">

            <!-- FILTER -->
            <div class="bg-white dark:bg-gray-800 rounded-2xl border border-gray-100 dark:border-gray-700 p-5">
                <label
                    class="block text-[10px] font-bold text-gray-500 dark:text-gray-400 uppercase tracking-widest mb-2">
                    Filter by region
                </label>
                <select v-model="filterState.region_id" @change="applyFilter"
                    class="w-full sm:w-64 px-4 py-2.5 border border-gray-200 dark:border-gray-600 rounded-xl bg-white dark:bg-gray-900 text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-primary-500 focus:border-transparent transition-colors text-sm appearance-none">
                    <option value="">All regions</option>
                    <option v-for="region in regions" :key="region.id" :value="region.id">
                        {{ region.name }}
                    </option>
                </select>
            </div>

            <!-- TABLE -->
            <div
                class="bg-white dark:bg-gray-800 rounded-2xl border border-gray-100 dark:border-gray-700 overflow-hidden">
                <div v-if="cities.data && cities.data.length > 0" class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                        <thead class="bg-gray-50 dark:bg-gray-900/50">
                            <tr>
                                <th
                                    class="px-6 py-4 text-left text-[10px] font-bold text-gray-500 dark:text-gray-400 uppercase tracking-widest">
                                    Name</th>
                                <th
                                    class="px-6 py-4 text-left text-[10px] font-bold text-gray-500 dark:text-gray-400 uppercase tracking-widest">
                                    Region</th>
                                <th
                                    class="px-6 py-4 text-left text-[10px] font-bold text-gray-500 dark:text-gray-400 uppercase tracking-widest">
                                    Country</th>
                                <th
                                    class="px-6 py-4 text-left text-[10px] font-bold text-gray-500 dark:text-gray-400 uppercase tracking-widest">
                                    Status</th>
                                <th
                                    class="px-6 py-4 text-right text-[10px] font-bold text-gray-500 dark:text-gray-400 uppercase tracking-widest">
                                    Actions</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                            <tr v-for="city in cities.data" :key="city.id"
                                class="hover:bg-gray-50/50 dark:hover:bg-gray-700/30 transition-colors">
                                <td class="px-6 py-4">
                                    <p class="text-sm font-bold text-gray-900 dark:text-white tracking-tight">{{
                                        city.name }}
                                    </p>
                                </td>
                                <td class="px-6 py-4">
                                    <span class="text-sm text-gray-700 dark:text-gray-300 font-medium">{{
                                        city.region?.name ||
                                        'N/A' }}</span>
                                </td>
                                <td class="px-6 py-4">
                                    <span class="text-sm text-gray-700 dark:text-gray-300 font-medium">{{
                                        city.region?.country?.name || 'N/A' }}</span>
                                </td>
                                <td class="px-6 py-4">
                                    <span :class="city.is_active ? badgeActive : badgeInactive">
                                        {{ city.is_active ? 'Active' : 'Inactive' }}
                                    </span>
                                </td>
                                <td class="px-6 py-4">
                                    <div class="flex items-center justify-end gap-1.5">
                                        <button @click="openEditModal(city)"
                                            class="inline-flex items-center gap-1.5 px-3 py-1.5 bg-blue-50 dark:bg-blue-900/30 text-blue-700 dark:text-blue-400 text-xs font-bold uppercase tracking-wide rounded-lg hover:bg-blue-100 dark:hover:bg-blue-900/50 transition-colors">
                                            Edit
                                        </button>
                                        <button @click="toggleCity(city)"
                                            class="inline-flex items-center gap-1.5 px-3 py-1.5 bg-amber-50 dark:bg-amber-900/30 text-amber-700 dark:text-amber-400 text-xs font-bold uppercase tracking-wide rounded-lg hover:bg-amber-100 dark:hover:bg-amber-900/50 transition-colors">
                                            Toggle
                                        </button>
                                        <button @click="deleteCity(city)"
                                            class="inline-flex items-center gap-1.5 px-3 py-1.5 bg-red-50 dark:bg-red-900/30 text-red-700 dark:text-red-400 text-xs font-bold uppercase tracking-wide rounded-lg hover:bg-red-100 dark:hover:bg-red-900/50 transition-colors">
                                            Delete
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <div v-else class="p-16 text-center">
                    <div
                        class="w-20 h-20 mx-auto mb-4 rounded-2xl bg-gradient-to-br from-gray-100 to-gray-50 dark:from-gray-700 dark:to-gray-800 flex items-center justify-center shadow-sm">
                        <svg class="w-10 h-10 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" />
                        </svg>
                    </div>
                    <p class="text-gray-600 dark:text-gray-300 font-bold tracking-tight">No cities found</p>
                    <p class="text-sm text-gray-400 mt-1">Add a city or adjust your filters</p>
                </div>

                <div v-if="cities.links && cities.links.length > 3"
                    class="px-6 py-4 border-t border-gray-100 dark:border-gray-700">
                    <Pagination :links="cities.links" />
                </div>
            </div>
        </div>

        <!-- MODAL -->
        <Modal :show="showModal" @close="closeModal">
            <div class="p-6">
                <div class="flex items-center gap-3 mb-6">
                    <div
                        class="w-11 h-11 rounded-2xl bg-gradient-to-br from-primary-500 to-primary-700 flex items-center justify-center shadow-md">
                        <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" />
                        </svg>
                    </div>
                    <div>
                        <h3 class="text-lg font-bold text-gray-900 dark:text-white tracking-tight">
                            {{ editingCity ? 'Edit City' : 'Add City' }}
                        </h3>
                        <p class="text-xs text-gray-400 mt-0.5">
                            {{ editingCity ? 'Update city details' : 'Add a new city to a region' }}
                        </p>
                    </div>
                </div>

                <form @submit.prevent="saveCity" class="space-y-5">
                    <div>
                        <label
                            class="block text-[10px] font-bold text-gray-500 dark:text-gray-400 uppercase tracking-widest mb-2">
                            Region <span class="text-red-500">*</span>
                        </label>
                        <select v-model="form.region_id" required
                            class="w-full px-4 py-2.5 border border-gray-200 dark:border-gray-600 rounded-xl bg-white dark:bg-gray-900 text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-primary-500 focus:border-transparent transition-colors text-sm appearance-none">
                            <option value="">Select region</option>
                            <option v-for="region in regions" :key="region.id" :value="region.id">
                                {{ region.country?.name }} – {{ region.name }}
                            </option>
                        </select>
                        <p v-if="form.errors.region_id" class="text-red-500 text-xs font-medium mt-1.5">{{
                            form.errors.region_id
                            }}</p>
                    </div>

                    <div>
                        <label
                            class="block text-[10px] font-bold text-gray-500 dark:text-gray-400 uppercase tracking-widest mb-2">
                            Name <span class="text-red-500">*</span>
                        </label>
                        <input type="text" v-model="form.name" required
                            class="w-full px-4 py-2.5 border border-gray-200 dark:border-gray-600 rounded-xl bg-white dark:bg-gray-900 text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-primary-500 focus:border-transparent transition-colors text-sm"
                            placeholder="e.g., Buea" />
                        <p v-if="form.errors.name" class="text-red-500 text-xs font-medium mt-1.5">{{ form.errors.name
                            }}</p>
                    </div>

                    <label class="flex items-center gap-3 p-3 bg-gray-50 dark:bg-gray-900/50 rounded-xl cursor-pointer">
                        <input type="checkbox" v-model="form.is_active"
                            class="rounded border-gray-300 text-primary-600 focus:ring-primary-500" />
                        <div>
                            <span class="text-sm font-semibold text-gray-900 dark:text-white">Active</span>
                            <p class="text-xs text-gray-400 mt-0.5">Only active cities appear in dropdowns</p>
                        </div>
                    </label>

                    <div
                        class="flex flex-col-reverse sm:flex-row justify-end gap-2 pt-5 border-t border-gray-100 dark:border-gray-700">
                        <button type="button" @click="closeModal"
                            class="px-5 py-2.5 border border-gray-300 dark:border-gray-600 text-gray-700 dark:text-gray-300 rounded-xl hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors font-semibold text-sm">
                            Cancel
                        </button>
                        <button type="submit" :disabled="form.processing"
                            class="inline-flex items-center justify-center gap-2 px-6 py-2.5 bg-gradient-to-r from-primary-600 to-primary-700 text-white rounded-xl hover:from-primary-700 hover:to-primary-800 transition-all shadow-lg shadow-primary-500/25 font-semibold text-sm disabled:opacity-50 disabled:cursor-not-allowed">
                            <span v-if="form.processing">Saving…</span>
                            <span v-else>{{ editingCity ? 'Update' : 'Create' }}</span>
                        </button>
                    </div>
                </form>
            </div>
        </Modal>
    </AuthenticatedLayout>
</template>

<script setup>
    import { ref, reactive } from 'vue';
    import { router, useForm } from '@inertiajs/vue3';
    import { useConfirm } from '@/composables/useConfirm';
    import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
    import Pagination from '@/Components/Pagination.vue';
    import Modal from '@/Components/Modal.vue';
    import PageHeader from '@/Components/PageHeader.vue';

    const props = defineProps({
        cities: Object,
        regions: Array,
        filters: Object,
    });

    const { confirm: confirmDialog } = useConfirm();

    const showModal = ref(false);
    const editingCity = ref(null);

    const badgeActive = 'inline-flex items-center px-2.5 py-1 text-[10px] font-bold uppercase tracking-wide rounded-full bg-emerald-100 text-emerald-700 dark:bg-emerald-900/30 dark:text-emerald-400 border border-emerald-200 dark:border-emerald-800';
    const badgeInactive = 'inline-flex items-center px-2.5 py-1 text-[10px] font-bold uppercase tracking-wide rounded-full bg-gray-100 text-gray-600 dark:bg-gray-700 dark:text-gray-300 border border-gray-200 dark:border-gray-600';

    // ✅ Filter state — kept separate from the modal form
    const filterState = reactive({
        region_id: props.filters?.region_id || '',
    });

    // ✅ Form state via useForm — gives form.errors + form.processing
    const form = useForm({
        region_id: '',
        name: '',
        is_active: true,
    });

    const openCreateModal = () => {
        editingCity.value = null;
        form.reset();
        form.clearErrors();
        form.is_active = true;
        showModal.value = true;
    };

    const openEditModal = (city) => {
        editingCity.value = city;
        form.region_id = city.region_id;
        form.name = city.name;
        form.is_active = city.is_active;
        form.clearErrors();
        showModal.value = true;
    };

    const closeModal = () => {
        showModal.value = false;
        editingCity.value = null;
        form.reset();
        form.clearErrors();
    };

    const applyFilter = () => {
        router.get('/admin/locations/cities', filterState, {
            preserveState: true,
            preserveScroll: true,
        });
    };

    const saveCity = () => {
        if (editingCity.value) {
            form.put(`/admin/locations/cities/${editingCity.value.id}`, {
                preserveScroll: true,
                onSuccess: () => {
                    // ✅ No success toast — the controller flashes
                    closeModal();
                },
            });
        } else {
            form.post('/admin/locations/cities', {
                preserveScroll: true,
                onSuccess: () => {
                    // ✅ No success toast — the controller flashes
                    closeModal();
                },
            });
        }
    };

    const toggleCity = async (city) => {
        const action = city.is_active ? 'Deactivate' : 'Activate';

        const confirmed = await confirmDialog({
            title: `${action} city?`,
            message: city.is_active
                ? `${city.name} will be hidden from location dropdowns across the platform.`
                : `${city.name} will become available in location dropdowns.`,
            confirmText: action,
            cancelText: 'Cancel',
            variant: city.is_active ? 'warning' : 'primary',
        });

        if (!confirmed) return;

        router.post(`/admin/locations/cities/${city.id}/toggle`, {}, {
            preserveScroll: true,
            // ✅ No success toast — the controller flashes
        });
    };

    const deleteCity = async (city) => {
        const confirmed = await confirmDialog({
            title: 'Delete city?',
            message: `Delete "${city.name}"? If it has areas under it, the delete will be blocked. This action cannot be undone.`,
            confirmText: 'Delete',
            cancelText: 'Cancel',
            variant: 'danger',
        });

        if (!confirmed) return;

        router.delete(`/admin/locations/cities/${city.id}`, {
            preserveScroll: true,
            // ✅ No success toast — the controller flashes
        });
    };
</script>