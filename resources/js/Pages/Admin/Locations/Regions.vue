<!-- resources/js/Pages/Admin/Locations/Regions.vue -->
<template>
    <AuthenticatedLayout>
        <!-- COMPACT HEADER -->
        <PageHeader color="emerald" :breadcrumb="[
            { label: 'Admin', href: '/admin/dashboard' },
            { label: 'Locations', href: '/admin/locations/countries' },
            { label: 'Regions' }
        ]">
            <template #icon>
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                    d="M3.055 11H5a2 2 0 012 2v1a2 2 0 002 2 2 2 0 012 2v2.945M8 3.935V5.5A2.5 2.5 0 0010.5 8h.5a2 2 0 012 2 2 2 0 104 0 2 2 0 012-2h1.064M15 20.488V18a2 2 0 012-2h3.064M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
            </template>
            <template #title>Regions</template>
            <template #subtitle>Manage regions within countries</template>
            <template #actions>
                <button @click="openCreateModal"
                    class="inline-flex items-center gap-2 px-4 py-2 bg-gradient-to-r from-primary-600 to-primary-700 text-white rounded-xl font-semibold hover:from-primary-700 hover:to-primary-800 transition-all shadow-lg shadow-primary-500/25 text-sm">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                    </svg>
                    Add Region
                </button>
            </template>
        </PageHeader>

        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8 space-y-6">

            <!-- FILTER -->
            <div class="bg-white dark:bg-gray-800 rounded-2xl border border-gray-100 dark:border-gray-700 p-5">
                <label
                    class="block text-[10px] font-bold text-gray-500 dark:text-gray-400 uppercase tracking-widest mb-2">
                    Filter by country
                </label>
                <select v-model="filterState.country_id" @change="applyFilter"
                    class="w-full sm:w-64 px-4 py-2.5 border border-gray-200 dark:border-gray-600 rounded-xl bg-white dark:bg-gray-900 text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-primary-500 focus:border-transparent transition-colors text-sm appearance-none">
                    <option value="">All countries</option>
                    <option v-for="country in countries" :key="country.id" :value="country.id">
                        {{ country.name }}
                    </option>
                </select>
            </div>

            <!-- TABLE -->
            <div
                class="bg-white dark:bg-gray-800 rounded-2xl border border-gray-100 dark:border-gray-700 overflow-hidden">
                <div v-if="regions.data && regions.data.length > 0" class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                        <thead class="bg-gray-50 dark:bg-gray-900/50">
                            <tr>
                                <th
                                    class="px-6 py-4 text-left text-[10px] font-bold text-gray-500 dark:text-gray-400 uppercase tracking-widest">
                                    Name</th>
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
                            <tr v-for="region in regions.data" :key="region.id"
                                class="hover:bg-gray-50/50 dark:hover:bg-gray-700/30 transition-colors">
                                <td class="px-6 py-4">
                                    <p class="text-sm font-bold text-gray-900 dark:text-white tracking-tight">{{
                                        region.name }}
                                    </p>
                                </td>
                                <td class="px-6 py-4">
                                    <span class="text-sm text-gray-700 dark:text-gray-300 font-medium">{{
                                        region.country?.name
                                        || 'N/A' }}</span>
                                </td>
                                <td class="px-6 py-4">
                                    <span :class="region.is_active ? badgeActive : badgeInactive">
                                        {{ region.is_active ? 'Active' : 'Inactive' }}
                                    </span>
                                </td>
                                <td class="px-6 py-4">
                                    <div class="flex items-center justify-end gap-1.5">
                                        <button @click="openEditModal(region)"
                                            class="inline-flex items-center gap-1.5 px-3 py-1.5 bg-blue-50 dark:bg-blue-900/30 text-blue-700 dark:text-blue-400 text-xs font-bold uppercase tracking-wide rounded-lg hover:bg-blue-100 dark:hover:bg-blue-900/50 transition-colors">
                                            Edit
                                        </button>
                                        <button @click="toggleRegion(region)"
                                            class="inline-flex items-center gap-1.5 px-3 py-1.5 bg-amber-50 dark:bg-amber-900/30 text-amber-700 dark:text-amber-400 text-xs font-bold uppercase tracking-wide rounded-lg hover:bg-amber-100 dark:hover:bg-amber-900/50 transition-colors">
                                            Toggle
                                        </button>
                                        <button @click="deleteRegion(region)"
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
                                d="M3.055 11H5a2 2 0 012 2v1a2 2 0 002 2 2 2 0 012 2v2.945M8 3.935V5.5A2.5 2.5 0 0010.5 8h.5a2 2 0 012 2 2 2 0 104 0 2 2 0 012-2h1.064M15 20.488V18a2 2 0 012-2h3.064M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                    </div>
                    <p class="text-gray-600 dark:text-gray-300 font-bold tracking-tight">No regions found</p>
                    <p class="text-sm text-gray-400 mt-1">Add a region or adjust your filters</p>
                </div>

                <div v-if="regions.links && regions.links.length > 3"
                    class="px-6 py-4 border-t border-gray-100 dark:border-gray-700">
                    <Pagination :links="regions.links" />
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
                                d="M3.055 11H5a2 2 0 012 2v1a2 2 0 002 2 2 2 0 012 2v2.945M8 3.935V5.5A2.5 2.5 0 0010.5 8h.5a2 2 0 012 2 2 2 0 104 0 2 2 0 012-2h1.064M15 20.488V18a2 2 0 012-2h3.064M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                    </div>
                    <div>
                        <h3 class="text-lg font-bold text-gray-900 dark:text-white tracking-tight">
                            {{ editingRegion ? 'Edit Region' : 'Add Region' }}
                        </h3>
                        <p class="text-xs text-gray-400 mt-0.5">
                            {{ editingRegion ? 'Update region details' : 'Add a new region to a country' }}
                        </p>
                    </div>
                </div>

                <form @submit.prevent="saveRegion" class="space-y-5">
                    <div>
                        <label
                            class="block text-[10px] font-bold text-gray-500 dark:text-gray-400 uppercase tracking-widest mb-2">
                            Country <span class="text-red-500">*</span>
                        </label>
                        <select v-model="form.country_id" required
                            class="w-full px-4 py-2.5 border border-gray-200 dark:border-gray-600 rounded-xl bg-white dark:bg-gray-900 text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-primary-500 focus:border-transparent transition-colors text-sm appearance-none">
                            <option value="">Select country</option>
                            <option v-for="country in countries" :key="country.id" :value="country.id">
                                {{ country.name }}
                            </option>
                        </select>
                        <p v-if="form.errors.country_id" class="text-red-500 text-xs font-medium mt-1.5">{{
                            form.errors.country_id }}</p>
                    </div>

                    <div>
                        <label
                            class="block text-[10px] font-bold text-gray-500 dark:text-gray-400 uppercase tracking-widest mb-2">
                            Name <span class="text-red-500">*</span>
                        </label>
                        <input type="text" v-model="form.name" required
                            class="w-full px-4 py-2.5 border border-gray-200 dark:border-gray-600 rounded-xl bg-white dark:bg-gray-900 text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-primary-500 focus:border-transparent transition-colors text-sm"
                            placeholder="e.g., Southwest" />
                        <p v-if="form.errors.name" class="text-red-500 text-xs font-medium mt-1.5">{{ form.errors.name
                            }}</p>
                    </div>

                    <label class="flex items-center gap-3 p-3 bg-gray-50 dark:bg-gray-900/50 rounded-xl cursor-pointer">
                        <input type="checkbox" v-model="form.is_active"
                            class="rounded border-gray-300 text-primary-600 focus:ring-primary-500" />
                        <div>
                            <span class="text-sm font-semibold text-gray-900 dark:text-white">Active</span>
                            <p class="text-xs text-gray-400 mt-0.5">Only active regions appear in dropdowns</p>
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
                            <span v-else>{{ editingRegion ? 'Update' : 'Create' }}</span>
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
        regions: Object,
        countries: Array,
        filters: Object,
    });

    const { confirm: confirmDialog } = useConfirm();

    const showModal = ref(false);
    const editingRegion = ref(null);

    const badgeActive = 'inline-flex items-center px-2.5 py-1 text-[10px] font-bold uppercase tracking-wide rounded-full bg-emerald-100 text-emerald-700 dark:bg-emerald-900/30 dark:text-emerald-400 border border-emerald-200 dark:border-emerald-800';
    const badgeInactive = 'inline-flex items-center px-2.5 py-1 text-[10px] font-bold uppercase tracking-wide rounded-full bg-gray-100 text-gray-600 dark:bg-gray-700 dark:text-gray-300 border border-gray-200 dark:border-gray-600';

    // ✅ Filter state — kept separate from the modal form
    const filterState = reactive({
        country_id: props.filters?.country_id || '',
    });

    // ✅ Form state via useForm — gives form.errors + form.processing
    const form = useForm({
        country_id: '',
        name: '',
        is_active: true,
    });

    const openCreateModal = () => {
        editingRegion.value = null;
        form.reset();
        form.clearErrors();
        form.is_active = true;
        showModal.value = true;
    };

    const openEditModal = (region) => {
        editingRegion.value = region;
        form.country_id = region.country_id;
        form.name = region.name;
        form.is_active = region.is_active;
        form.clearErrors();
        showModal.value = true;
    };

    const closeModal = () => {
        showModal.value = false;
        editingRegion.value = null;
        form.reset();
        form.clearErrors();
    };

    const applyFilter = () => {
        router.get('/admin/locations/regions', filterState, {
            preserveState: true,
            preserveScroll: true,
        });
    };

    const saveRegion = () => {
        if (editingRegion.value) {
            form.put(`/admin/locations/regions/${editingRegion.value.id}`, {
                preserveScroll: true,
                onSuccess: () => {
                    // ✅ No success toast — the controller flashes
                    //    'Region updated successfully.'
                    closeModal();
                },
            });
        } else {
            form.post('/admin/locations/regions', {
                preserveScroll: true,
                onSuccess: () => {
                    // ✅ No success toast — the controller flashes
                    //    'Region created successfully.'
                    closeModal();
                },
            });
        }
    };

    const toggleRegion = async (region) => {
        const action = region.is_active ? 'Deactivate' : 'Activate';

        const confirmed = await confirmDialog({
            title: `${action} region?`,
            message: region.is_active
                ? `${region.name} will be hidden from location dropdowns across the platform.`
                : `${region.name} will become available in location dropdowns.`,
            confirmText: action,
            cancelText: 'Cancel',
            variant: region.is_active ? 'warning' : 'primary',
        });

        if (!confirmed) return;

        router.post(`/admin/locations/regions/${region.id}/toggle`, {}, {
            preserveScroll: true,
            // ✅ No success toast — the controller flashes
        });
    };

    const deleteRegion = async (region) => {
        const confirmed = await confirmDialog({
            title: 'Delete region?',
            message: `Delete "${region.name}"? If it has cities under it, the delete will be blocked. This action cannot be undone.`,
            confirmText: 'Delete',
            cancelText: 'Cancel',
            variant: 'danger',
        });

        if (!confirmed) return;

        router.delete(`/admin/locations/regions/${region.id}`, {
            preserveScroll: true,
            // ✅ No success toast — the controller flashes
        });
    };
</script>