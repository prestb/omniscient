<!-- resources/js/Pages/Admin/Categories/Index.vue -->
<template>
    <AuthenticatedLayout>
        <!-- COMPACT HEADER -->
        <PageHeader color="purple" :breadcrumb="[
            { label: 'Admin', href: '/admin/dashboard' },
            { label: 'Categories' }
        ]">
            <template #icon>
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                    d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z" />
            </template>
            <template #title>Categories</template>
            <template #subtitle>Manage the discovery taxonomy that classifies listings</template>
            <template #actions>
                <button @click="showCreateModal = true"
                    class="inline-flex items-center gap-2 px-4 py-2 bg-gradient-to-r from-primary-600 to-primary-700 text-white rounded-xl font-semibold hover:from-primary-700 hover:to-primary-800 transition-all shadow-lg shadow-primary-500/25 text-sm">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                    </svg>
                    Add Category
                </button>
            </template>
        </PageHeader>

        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8 space-y-6">

            <!-- STATS -->
            <div class="grid grid-cols-2 sm:grid-cols-4 gap-4">
                <div
                    class="bg-white dark:bg-gray-800 rounded-2xl border border-gray-100 dark:border-gray-700 p-4 hover:shadow-md transition-shadow">
                    <p class="text-[10px] font-bold text-gray-400 dark:text-gray-500 uppercase tracking-widest">Total
                    </p>
                    <p class="text-2xl font-bold text-gray-900 dark:text-white tracking-tight mt-1">{{ categories.total
                        || 0 }}
                    </p>
                </div>
                <div
                    class="bg-white dark:bg-gray-800 rounded-2xl border border-gray-100 dark:border-gray-700 p-4 hover:shadow-md transition-shadow">
                    <p class="text-[10px] font-bold text-gray-400 dark:text-gray-500 uppercase tracking-widest">Active
                    </p>
                    <p class="text-2xl font-bold text-emerald-600 tracking-tight mt-1">{{ getStatusCount(true) }}</p>
                </div>
                <div
                    class="bg-white dark:bg-gray-800 rounded-2xl border border-gray-100 dark:border-gray-700 p-4 hover:shadow-md transition-shadow">
                    <p class="text-[10px] font-bold text-gray-400 dark:text-gray-500 uppercase tracking-widest">Inactive
                    </p>
                    <p class="text-2xl font-bold text-gray-500 tracking-tight mt-1">{{ getStatusCount(false) }}</p>
                </div>
                <div
                    class="bg-white dark:bg-gray-800 rounded-2xl border border-gray-100 dark:border-gray-700 p-4 hover:shadow-md transition-shadow">
                    <p class="text-[10px] font-bold text-gray-400 dark:text-gray-500 uppercase tracking-widest">Root</p>
                    <p class="text-2xl font-bold text-purple-600 tracking-tight mt-1">{{ rootCategories?.length || 0 }}
                    </p>
                </div>
            </div>

            <!-- FILTERS -->
            <div class="bg-white dark:bg-gray-800 rounded-2xl border border-gray-100 dark:border-gray-700 p-5">
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 items-end">
                    <div class="lg:col-span-2">
                        <label
                            class="block text-[10px] font-bold text-gray-500 dark:text-gray-400 uppercase tracking-widest mb-2">Search</label>
                        <div class="relative">
                            <div class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none">
                                <svg class="h-4 w-4 text-gray-400" fill="none" stroke="currentColor"
                                    viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                                </svg>
                            </div>
                            <input type="text" v-model="filters.search" placeholder="Search categories…"
                                class="w-full pl-11 pr-4 py-2.5 border border-gray-200 dark:border-gray-600 rounded-xl bg-white dark:bg-gray-900 text-gray-900 dark:text-white placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-primary-500 focus:border-transparent transition-colors text-sm"
                                @input="applyFilters" />
                        </div>
                    </div>
                    <div>
                        <label
                            class="block text-[10px] font-bold text-gray-500 dark:text-gray-400 uppercase tracking-widest mb-2">Parent
                            category</label>
                        <select v-model="filters.parent_id"
                            class="w-full px-4 py-2.5 border border-gray-200 dark:border-gray-600 rounded-xl bg-white dark:bg-gray-900 text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-primary-500 focus:border-transparent transition-colors text-sm appearance-none"
                            @change="applyFilters">
                            <option value="">All categories</option>
                            <option v-for="cat in rootCategories" :key="cat.id" :value="cat.id">
                                {{ cat.name }}
                            </option>
                        </select>
                    </div>
                    <div>
                        <button @click="resetFilters"
                            class="w-full px-5 py-2.5 border border-gray-300 dark:border-gray-600 text-gray-700 dark:text-gray-300 rounded-xl hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors font-semibold text-sm">
                            Reset
                        </button>
                    </div>
                </div>
            </div>

            <!-- TABLE -->
            <div
                class="bg-white dark:bg-gray-800 rounded-2xl border border-gray-100 dark:border-gray-700 overflow-hidden">
                <div v-if="categories.data && categories.data.length > 0" class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                        <thead class="bg-gray-50 dark:bg-gray-900/50">
                            <tr>
                                <th
                                    class="px-6 py-4 text-left text-[10px] font-bold text-gray-500 dark:text-gray-400 uppercase tracking-widest">
                                    Category</th>
                                <th
                                    class="px-6 py-4 text-left text-[10px] font-bold text-gray-500 dark:text-gray-400 uppercase tracking-widest">
                                    Parent</th>
                                <th
                                    class="px-6 py-4 text-left text-[10px] font-bold text-gray-500 dark:text-gray-400 uppercase tracking-widest">
                                    Status</th>
                                <th
                                    class="px-6 py-4 text-right text-[10px] font-bold text-gray-500 dark:text-gray-400 uppercase tracking-widest">
                                    Actions</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                            <tr v-for="category in categories.data" :key="category.id"
                                class="hover:bg-gray-50/50 dark:hover:bg-gray-700/30 transition-colors">
                                <td class="px-6 py-4">
                                    <div class="flex items-center gap-3 min-w-0">
                                        <div
                                            class="w-11 h-11 rounded-2xl bg-gradient-to-br from-purple-50 to-purple-100 dark:from-purple-900/30 dark:to-purple-900/20 flex items-center justify-center flex-shrink-0 shadow-sm text-purple-600 dark:text-purple-400">
                                            <CategoryIcon :icon="category.icon" size="lg" />
                                        </div>
                                        <div class="min-w-0">
                                            <p
                                                class="text-sm font-bold text-gray-900 dark:text-white truncate tracking-tight">
                                                {{ category.name }}</p>
                                            <p class="text-xs text-gray-400 font-mono truncate">{{ category.slug }}</p>
                                            <p v-if="category.description"
                                                class="text-xs text-gray-500 dark:text-gray-400 truncate mt-0.5">
                                                {{ category.description }}
                                            </p>
                                        </div>
                                    </div>
                                </td>
                                <td class="px-6 py-4">
                                    <span
                                        class="inline-flex items-center px-2.5 py-1 text-[10px] font-bold uppercase tracking-wide rounded-full"
                                        :class="category.parent
                                            ? 'bg-blue-50 dark:bg-blue-900/30 text-blue-700 dark:text-blue-400 border border-blue-200 dark:border-blue-800'
                                            : 'bg-purple-50 dark:bg-purple-900/30 text-purple-700 dark:text-purple-400 border border-purple-200 dark:border-purple-800'">
                                        {{ category.parent ? category.parent.name : 'Root' }}
                                    </span>
                                </td>
                                <td class="px-6 py-4">
                                    <span :class="category.is_active ? badgeActive : badgeInactive">
                                        {{ category.is_active ? 'Active' : 'Inactive' }}
                                    </span>
                                </td>
                                <td class="px-6 py-4">
                                    <div class="flex items-center justify-end gap-1.5">
                                        <button @click="editCategory(category)"
                                            class="inline-flex items-center gap-1.5 px-3 py-1.5 bg-blue-50 dark:bg-blue-900/30 text-blue-700 dark:text-blue-400 text-xs font-bold uppercase tracking-wide rounded-lg hover:bg-blue-100 dark:hover:bg-blue-900/50 transition-colors">
                                            Edit
                                        </button>
                                        <button @click="toggleActive(category)"
                                            class="inline-flex items-center gap-1.5 px-3 py-1.5 bg-amber-50 dark:bg-amber-900/30 text-amber-700 dark:text-amber-400 text-xs font-bold uppercase tracking-wide rounded-lg hover:bg-amber-100 dark:hover:bg-amber-900/50 transition-colors">
                                            {{ category.is_active ? 'Deactivate' : 'Activate' }}
                                        </button>
                                        <button @click="deleteCategory(category)"
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
                                d="M3 7v10a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-6l-2-2H5a2 2 0 00-2 2z" />
                        </svg>
                    </div>
                    <p class="text-gray-600 dark:text-gray-300 font-bold tracking-tight">No categories found</p>
                    <p class="text-sm text-gray-400 mt-1">Click "Add Category" to create your first category</p>
                </div>

                <div v-if="categories.links && categories.links.length > 3"
                    class="px-6 py-4 border-t border-gray-100 dark:border-gray-700">
                    <Pagination :links="categories.links" />
                </div>
            </div>
        </div>

        <!-- CREATE/EDIT MODAL -->
        <CategoryModal v-if="showCreateModal || showEditModal" :category="editingCategory"
            :root-categories="rootCategories" @close="closeModal" @save="saveCategory" />
    </AuthenticatedLayout>
</template>

<script setup>
    import { ref, reactive } from 'vue';
    import { router } from '@inertiajs/vue3';
    import { useToast } from '@/composables/useToast';
    import { useConfirm } from '@/composables/useConfirm';
    import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
    import CategoryModal from '@/Components/Admin/CategoryModal.vue';
    import CategoryIcon from '@/Components/CategoryIcon.vue';
    import Pagination from '@/Components/Pagination.vue';
    import PageHeader from '@/Components/PageHeader.vue';

    const props = defineProps({
        categories: Object,
        rootCategories: Array,
        filters: Object,
    });

    const { error } = useToast();
    const { confirm: confirmDialog } = useConfirm();

    const filters = reactive({
        search: props.filters?.search || '',
        parent_id: props.filters?.parent_id || '',
    });

    const showCreateModal = ref(false);
    const showEditModal = ref(false);
    const editingCategory = ref(null);

    const badgeActive = 'inline-flex items-center px-2.5 py-1 text-[10px] font-bold uppercase tracking-wide rounded-full bg-emerald-100 text-emerald-700 dark:bg-emerald-900/30 dark:text-emerald-400 border border-emerald-200 dark:border-emerald-800';
    const badgeInactive = 'inline-flex items-center px-2.5 py-1 text-[10px] font-bold uppercase tracking-wide rounded-full bg-gray-100 text-gray-600 dark:bg-gray-700 dark:text-gray-300 border border-gray-200 dark:border-gray-600';

    const getStatusCount = (isActive) => {
        if (!props.categories?.data) return 0;
        return props.categories.data.filter(c => c.is_active === isActive).length;
    };

    const applyFilters = () => {
        router.get('/admin/categories', filters, {
            preserveState: true,
            preserveScroll: true,
        });
    };

    const resetFilters = () => {
        filters.search = '';
        filters.parent_id = '';
        applyFilters();
    };

    const editCategory = (category) => {
        editingCategory.value = category;
        showEditModal.value = true;
    };

    const closeModal = () => {
        showCreateModal.value = false;
        showEditModal.value = false;
        editingCategory.value = null;
    };

    const saveCategory = (data) => {
        if (editingCategory.value) {
            router.put(`/admin/categories/${editingCategory.value.id}`, data, {
                preserveScroll: true,
                onSuccess: () => {
                    // ✅ No success toast — the controller flashes
                    //    'Category updated successfully.'
                    closeModal();
                },
                onError: () => {
                    // Client-side fallback for validation / network errors.
                    // Note: circular-parent errors come back as validation
                    // errors on the `parent_id` field, so the modal shows them.
                    error('Update Failed ❌', 'Failed to update category. Please try again.', { duration: 4000 });
                },
            });
        } else {
            router.post('/admin/categories', data, {
                preserveScroll: true,
                onSuccess: () => {
                    // ✅ No success toast — the controller flashes
                    //    'Category created successfully.'
                    closeModal();
                },
                onError: () => {
                    error('Creation Failed ❌', 'Failed to create category. Please try again.', { duration: 4000 });
                },
            });
        }
    };

    const toggleActive = async (category) => {
        const action = category.is_active ? 'Deactivate' : 'Activate';

        const confirmed = await confirmDialog({
            title: `${action} category?`,
            message: category.is_active
                ? `"${category.name}" will be hidden from customers. It won't appear in directory filters.`
                : `"${category.name}" will become visible and usable across the platform.`,
            confirmText: action,
            cancelText: 'Cancel',
            variant: category.is_active ? 'warning' : 'primary',
        });

        if (!confirmed) return;

        router.post(`/admin/categories/${category.id}/toggle`, {}, {
            preserveScroll: true,
            // ✅ No success toast — the controller flashes
            //    'Category status updated successfully.'
            onError: () => {
                error('Action Failed ❌', `Failed to ${category.is_active ? 'deactivate' : 'activate'} category.`, { duration: 4000 });
            },
        });
    };

    const deleteCategory = async (category) => {
        const confirmed = await confirmDialog({
            title: 'Delete category?',
            message: `Delete "${category.name}"? Categories with sub-categories cannot be deleted — you'll need to move or delete its children first.`,
            confirmText: 'Delete',
            cancelText: 'Cancel',
            variant: 'danger',
        });

        if (!confirmed) return;

        router.delete(`/admin/categories/${category.id}`, {
            preserveScroll: true,
            // ✅ No success toast — the controller flashes
            //    'Category deleted successfully.'
            //
            // Note: if the category has children, the controller returns
            // back()->with('error', ...) which the layout shows as a
            // toast. This onError is only for network failures.
            onError: () => {
                error('Delete Failed ❌', 'Failed to delete category. Please try again.', { duration: 4000 });
            },
        });
    };
</script>