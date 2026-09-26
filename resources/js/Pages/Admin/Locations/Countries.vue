<!-- resources/js/Pages/Admin/Locations/Countries.vue -->
<template>
    <AuthenticatedLayout>
        <!-- COMPACT HEADER -->
        <PageHeader color="emerald" :breadcrumb="[
            { label: 'Admin', href: '/admin/dashboard' },
            { label: 'Locations', href: '/admin/locations/countries' },
            { label: 'Countries' }
        ]">
            <template #icon>
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                    d="M3.055 11H5a2 2 0 012 2v1a2 2 0 002 2 2 2 0 012 2v2.945M8 3.935V5.5A2.5 2.5 0 0010.5 8h.5a2 2 0 012 2 2 2 0 104 0 2 2 0 012-2h1.064M15 20.488V18a2 2 0 012-2h3.064M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
            </template>
            <template #title>Countries</template>
            <template #subtitle>Manage countries available on the platform</template>
            <template #actions>
                <button @click="openCreateModal"
                    class="inline-flex items-center gap-2 px-4 py-2 bg-gradient-to-r from-primary-600 to-primary-700 text-white rounded-xl font-semibold hover:from-primary-700 hover:to-primary-800 transition-all shadow-lg shadow-primary-500/25 text-sm">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                    </svg>
                    Add Country
                </button>
            </template>
        </PageHeader>

        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">

            <!-- TABLE -->
            <div
                class="bg-white dark:bg-gray-800 rounded-2xl border border-gray-100 dark:border-gray-700 overflow-hidden">
                <div v-if="countries.data && countries.data.length > 0" class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                        <thead class="bg-gray-50 dark:bg-gray-900/50">
                            <tr>
                                <th
                                    class="px-6 py-4 text-left text-[10px] font-bold text-gray-500 dark:text-gray-400 uppercase tracking-widest">
                                    Name</th>
                                <th
                                    class="px-6 py-4 text-left text-[10px] font-bold text-gray-500 dark:text-gray-400 uppercase tracking-widest">
                                    Code</th>
                                <th
                                    class="px-6 py-4 text-left text-[10px] font-bold text-gray-500 dark:text-gray-400 uppercase tracking-widest">
                                    Regions</th>
                                <th
                                    class="px-6 py-4 text-left text-[10px] font-bold text-gray-500 dark:text-gray-400 uppercase tracking-widest">
                                    Status</th>
                                <th
                                    class="px-6 py-4 text-right text-[10px] font-bold text-gray-500 dark:text-gray-400 uppercase tracking-widest">
                                    Actions</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                            <tr v-for="country in countries.data" :key="country.id"
                                class="hover:bg-gray-50/50 dark:hover:bg-gray-700/30 transition-colors">
                                <td class="px-6 py-4">
                                    <p class="text-sm font-bold text-gray-900 dark:text-white tracking-tight">{{
                                        country.name }}
                                    </p>
                                </td>
                                <td class="px-6 py-4">
                                    <span
                                        class="inline-flex items-center px-2.5 py-1 text-xs font-mono font-bold bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-300 rounded-lg">
                                        {{ country.code }}
                                    </span>
                                </td>
                                <td class="px-6 py-4">
                                    <span class="text-sm font-semibold text-gray-700 dark:text-gray-300">{{
                                        country.regions_count }}</span>
                                </td>
                                <td class="px-6 py-4">
                                    <span :class="country.is_active ? badgeActive : badgeInactive">
                                        {{ country.is_active ? 'Active' : 'Inactive' }}
                                    </span>
                                </td>
                                <td class="px-6 py-4">
                                    <div class="flex items-center justify-end gap-1.5">
                                        <button @click="openEditModal(country)"
                                            class="inline-flex items-center gap-1.5 px-3 py-1.5 bg-blue-50 dark:bg-blue-900/30 text-blue-700 dark:text-blue-400 text-xs font-bold uppercase tracking-wide rounded-lg hover:bg-blue-100 dark:hover:bg-blue-900/50 transition-colors">
                                            Edit
                                        </button>
                                        <button @click="toggleCountry(country)"
                                            class="inline-flex items-center gap-1.5 px-3 py-1.5 bg-amber-50 dark:bg-amber-900/30 text-amber-700 dark:text-amber-400 text-xs font-bold uppercase tracking-wide rounded-lg hover:bg-amber-100 dark:hover:bg-amber-900/50 transition-colors">
                                            Toggle
                                        </button>
                                        <button @click="deleteCountry(country)"
                                            class="inline-flex items-center gap-1.5 px-3 py-1.5 bg-red-50 dark:bg-red-900/30 text-red-700 dark:text-red-400 text-xs font-bold uppercase tracking-wide rounded-lg hover:bg-red-100 dark:hover:bg-red-900/50 transition-colors">
                                            Delete
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <!-- Empty state -->
                <div v-else class="p-16 text-center">
                    <div
                        class="w-20 h-20 mx-auto mb-4 rounded-2xl bg-gradient-to-br from-gray-100 to-gray-50 dark:from-gray-700 dark:to-gray-800 flex items-center justify-center shadow-sm">
                        <svg class="w-10 h-10 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M3.055 11H5a2 2 0 012 2v1a2 2 0 002 2 2 2 0 012 2v2.945M8 3.935V5.5A2.5 2.5 0 0010.5 8h.5a2 2 0 012 2 2 2 0 104 0 2 2 0 012-2h1.064M15 20.488V18a2 2 0 012-2h3.064M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                    </div>
                    <p class="text-gray-600 dark:text-gray-300 font-bold tracking-tight">No countries found</p>
                    <p class="text-sm text-gray-400 mt-1">Add your first country to get started</p>
                </div>

                <!-- Pagination -->
                <div v-if="countries.links && countries.links.length > 3"
                    class="px-6 py-4 border-t border-gray-100 dark:border-gray-700">
                    <Pagination :links="countries.links" />
                </div>
            </div>
        </div>

        <!-- MODAL -->
        <Modal :show="showModal" @close="closeModal">
            <div class="p-6">
                <!-- Header -->
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
                            {{ editingCountry ? 'Edit Country' : 'Add Country' }}
                        </h3>
                        <p class="text-xs text-gray-400 mt-0.5">
                            {{ editingCountry ? 'Update country details' : 'Add a new country to the platform' }}
                        </p>
                    </div>
                </div>

                <form @submit.prevent="saveCountry" class="space-y-5">
                    <!-- Name -->
                    <div>
                        <label
                            class="block text-[10px] font-bold text-gray-500 dark:text-gray-400 uppercase tracking-widest mb-2">
                            Name <span class="text-red-500">*</span>
                        </label>
                        <input type="text" v-model="form.name" required
                            class="w-full px-4 py-2.5 border border-gray-200 dark:border-gray-600 rounded-xl bg-white dark:bg-gray-900 text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-primary-500 focus:border-transparent transition-colors text-sm"
                            placeholder="e.g., Cameroon" />
                        <p v-if="form.errors.name" class="text-red-500 text-xs font-medium mt-1.5">{{ form.errors.name
                            }}</p>

                    </div>

                    <!-- Code -->
                    <div>
                        <label
                            class="block text-[10px] font-bold text-gray-500 dark:text-gray-400 uppercase tracking-widest mb-2">
                            Code <span class="text-red-500">*</span>
                        </label>
                        <input type="text" v-model="form.code" required maxlength="3"
                            class="w-full px-4 py-2.5 border border-gray-200 dark:border-gray-600 rounded-xl bg-white dark:bg-gray-900 text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-primary-500 focus:border-transparent transition-colors text-sm uppercase font-mono"
                            placeholder="e.g., CM" />
                        <p v-if="form.errors.code" class="text-red-500 text-xs font-medium mt-1.5">{{ form.errors.code
                            }}</p>

                    </div>

                    <!-- Active -->
                    <label class="flex items-center gap-3 p-3 bg-gray-50 dark:bg-gray-900/50 rounded-xl cursor-pointer">
                        <input type="checkbox" v-model="form.is_active"
                            class="rounded border-gray-300 text-primary-600 focus:ring-primary-500" />
                        <div>
                            <span class="text-sm font-semibold text-gray-900 dark:text-white">Active</span>
                            <p class="text-xs text-gray-400 mt-0.5">Only active countries appear in dropdowns</p>
                        </div>
                    </label>

                    <!-- Actions -->
                    <div
                        class="flex flex-col-reverse sm:flex-row justify-end gap-2 pt-5 border-t border-gray-100 dark:border-gray-700">
                        <button type="button" @click="closeModal"
                            class="px-5 py-2.5 border border-gray-300 dark:border-gray-600 text-gray-700 dark:text-gray-300 rounded-xl hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors font-semibold text-sm">
                            Cancel
                        </button>
                        <button type="submit" :disabled="form.processing"
                            class="inline-flex items-center justify-center gap-2 px-6 py-2.5 bg-gradient-to-r from-primary-600 to-primary-700 text-white rounded-xl hover:from-primary-700 hover:to-primary-800 transition-all shadow-lg shadow-primary-500/25 font-semibold text-sm disabled:opacity-50 disabled:cursor-not-allowed">
                            <span v-if="form.processing" class="flex items-center gap-2">
                                <svg class="animate-spin h-4 w-4" fill="none" viewBox="0 0 24 24">
                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor"
                                        stroke-width="4">
                                    </circle>
                                    <path class="opacity-75" fill="currentColor"
                                        d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z">
                                    </path>
                                </svg>
                                Saving…
                            </span>
                            <span v-else>
                                {{ editingCountry ? 'Update' : 'Create' }}
                            </span>
                        </button>
                    </div>
                </form>
            </div>
        </Modal>
    </AuthenticatedLayout>
</template>


<script setup>
    import { ref } from 'vue';
    import { router, useForm } from '@inertiajs/vue3';
    import { useConfirm } from '@/composables/useConfirm';
    import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
    import Pagination from '@/Components/Pagination.vue';
    import Modal from '@/Components/Modal.vue';
    import PageHeader from '@/Components/PageHeader.vue';

    const props = defineProps({
        countries: Object,
    });

    const { confirm: confirmDialog } = useConfirm();

    const showModal = ref(false);
    const editingCountry = ref(null);

    const badgeActive = 'inline-flex items-center px-2.5 py-1 text-[10px] font-bold uppercase tracking-wide rounded-full bg-emerald-100 text-emerald-700 dark:bg-emerald-900/30 dark:text-emerald-400 border border-emerald-200 dark:border-emerald-800';
    const badgeInactive = 'inline-flex items-center px-2.5 py-1 text-[10px] font-bold uppercase tracking-wide rounded-full bg-gray-100 text-gray-600 dark:bg-gray-700 dark:text-gray-300 border border-gray-200 dark:border-gray-600';

    // ✅ useForm gives us form.errors (inline validation) and form.processing
    const form = useForm({
        name: '',
        code: '',
        is_active: true,
    });

    const openCreateModal = () => {
        editingCountry.value = null;
        form.reset();
        form.clearErrors();
        form.is_active = true;
        showModal.value = true;
    };

    const openEditModal = (country) => {
        editingCountry.value = country;
        form.name = country.name;
        form.code = country.code;
        form.is_active = country.is_active;
        form.clearErrors();
        showModal.value = true;
    };

    const closeModal = () => {
        showModal.value = false;
        editingCountry.value = null;
        form.reset();
        form.clearErrors();
    };

    const saveCountry = () => {
        if (editingCountry.value) {
            form.put(`/admin/locations/countries/${editingCountry.value.id}`, {
                preserveScroll: true,
                onSuccess: () => {
                    // ✅ No success toast — the controller flashes
                    //    'Country updated successfully.'
                    closeModal();
                },
                // ⚠️ No closeModal() on error — form.errors shows inline
                //    and the modal stays open for correction.
            });
        } else {
            form.post('/admin/locations/countries', {
                preserveScroll: true,
                onSuccess: () => {
                    // ✅ No success toast — the controller flashes
                    //    'Country created successfully.'
                    closeModal();
                },
            });
        }
    };

    const toggleCountry = async (country) => {
        const action = country.is_active ? 'Deactivate' : 'Activate';

        const confirmed = await confirmDialog({
            title: `${action} country?`,
            message: country.is_active
                ? `${country.name} will be hidden from location dropdowns across the platform.`
                : `${country.name} will become available in location dropdowns.`,
            confirmText: action,
            cancelText: 'Cancel',
            variant: country.is_active ? 'warning' : 'primary',
        });

        if (!confirmed) return;

        router.post(`/admin/locations/countries/${country.id}/toggle`, {}, {
            preserveScroll: true,
            // ✅ No success toast — the controller flashes
        });
    };

    const deleteCountry = async (country) => {
        // Warn about regions first — the server will block the delete anyway
        const regionWarning = country.regions_count > 0
            ? ` This country has ${country.regions_count} region${country.regions_count === 1 ? '' : 's'} under it — you'll need to delete those first.`
            : '';

        const confirmed = await confirmDialog({
            title: 'Delete country?',
            message: `Delete "${country.name}"?${regionWarning} This action cannot be undone.`,
            confirmText: 'Delete',
            cancelText: 'Cancel',
            variant: 'danger',
        });

        if (!confirmed) return;

        router.delete(`/admin/locations/countries/${country.id}`, {
            preserveScroll: true,
            // ✅ No success toast — the controller flashes
            //    'Country deleted successfully.' or the "Cannot delete ..." error.
        });
    };
</script>