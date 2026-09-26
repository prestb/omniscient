<template>
    <div class="fixed inset-0 bg-black/50 backdrop-blur-sm z-50 flex items-center justify-center p-4"
        @click.self="$emit('close')">
        <div
            class="bg-white rounded-2xl shadow-2xl max-w-lg w-full max-h-[90vh] overflow-y-auto transform transition-all duration-300 scale-100">
            <!-- Header -->
            <div
                class="sticky top-0 bg-white z-10 px-6 py-4 border-b border-gray-100 flex justify-between items-center rounded-t-2xl">
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 rounded-xl bg-primary-100 flex items-center justify-center">
                        <svg class="w-5 h-5 text-primary-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M3 7v10a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-6l-2-2H5a2 2 0 00-2 2z" />
                        </svg>
                    </div>
                    <div>
                        <h3 class="text-lg font-semibold text-gray-900">
                            {{ category ? 'Edit Category' : 'Create Category' }}
                        </h3>
                        <p class="text-xs text-gray-500">
                            {{ category ? 'Update category details' : 'Add a new category to the system' }}
                        </p>
                    </div>
                </div>
                <button @click="$emit('close')"
                    class="w-8 h-8 rounded-lg hover:bg-gray-100 flex items-center justify-center text-gray-400 hover:text-gray-600 transition-colors">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>

            <!-- Form -->
            <form @submit.prevent="handleSubmit" class="p-6 space-y-5">
                <!-- Parent Category -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">
                        Parent Category
                    </label>
                    <div class="relative">
                        <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                            <svg class="h-5 w-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M3 7v10a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-6l-2-2H5a2 2 0 00-2 2z" />
                            </svg>
                        </div>
                        <select v-model="form.parent_id"
                            class="w-full pl-10 pr-4 py-2.5 border border-gray-300 rounded-xl focus:outline-none focus:ring-2 focus:ring-primary-500 focus:border-transparent transition-colors appearance-none">
                            <option value="">Root Category</option>
                            <option v-for="cat in rootCategories" :key="cat.id" :value="cat.id">
                                {{ cat.name }}
                            </option>
                        </select>
                        <div class="absolute inset-y-0 right-0 pr-3 flex items-center pointer-events-none">
                            <svg class="h-5 w-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M19 9l-7 7-7-7" />
                            </svg>
                        </div>
                    </div>
                </div>

                <!-- Name -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">
                        Name *
                    </label>
                    <div class="relative">
                        <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                            <svg class="h-5 w-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M7 20l4-16m2 16l4-16M6 9h14M4 15h14" />
                            </svg>
                        </div>
                        <input type="text" v-model="form.name" required
                            class="w-full pl-10 pr-4 py-2.5 border border-gray-300 rounded-xl focus:outline-none focus:ring-2 focus:ring-primary-500 focus:border-transparent transition-colors"
                            placeholder="Enter category name" />
                    </div>
                    <p v-if="errors.name" class="text-red-500 text-sm mt-1">{{ errors.name }}</p>
                </div>

                <!-- Icon -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">
                        Icon
                    </label>
                    <CategoryIconPicker v-model="form.icon" />
                    <p class="text-xs text-gray-400 mt-1">
                        Click to open the icon picker and choose a category icon
                    </p>
                </div>

                <!-- Description -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">
                        Description
                    </label>
                    <div class="relative">
                        <div class="absolute top-3 left-3 flex items-start pointer-events-none">
                            <svg class="h-5 w-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M4 6h16M4 12h16M4 18h7" />
                            </svg>
                        </div>
                        <textarea v-model="form.description" rows="3"
                            class="w-full pl-10 pr-4 py-2.5 border border-gray-300 rounded-xl focus:outline-none focus:ring-2 focus:ring-primary-500 focus:border-transparent transition-colors resize-none"
                            placeholder="Brief description of the category"></textarea>
                    </div>
                </div>

                <!-- Sort Order & Status -->
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">
                            Sort Order
                        </label>
                        <div class="relative">
                            <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                <svg class="h-5 w-5 text-gray-400" fill="none" stroke="currentColor"
                                    viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M7 16V4m0 0L3 8m4-4l4 4m6 0v12m0 0l4-4m-4 4l-4-4" />
                                </svg>
                            </div>
                            <input type="number" v-model="form.sort_order" min="0"
                                class="w-full pl-10 pr-4 py-2.5 border border-gray-300 rounded-xl focus:outline-none focus:ring-2 focus:ring-primary-500 focus:border-transparent transition-colors" />
                        </div>
                    </div>
                    <div class="flex items-center justify-end">
                        <label class="flex items-center gap-2 text-sm text-gray-700 cursor-pointer">
                            <input type="checkbox" v-model="form.is_active"
                                class="w-4 h-4 text-primary-600 focus:ring-primary-500 border-gray-300 rounded transition-colors" />
                            <span class="flex items-center gap-1">
                                <span class="w-2 h-2 rounded-full"
                                    :class="form.is_active ? 'bg-green-500' : 'bg-gray-400'"></span>
                                {{ form.is_active ? 'Active' : 'Inactive' }}
                            </span>
                        </label>
                    </div>
                </div>

                <!-- Buttons -->
                <div class="flex justify-end gap-3 pt-4 border-t border-gray-100">
                    <button type="button" @click="$emit('close')"
                        class="px-6 py-2.5 border border-gray-300 text-gray-700 rounded-xl hover:bg-gray-50 transition-colors font-medium text-sm">
                        Cancel
                    </button>
                    <button type="submit"
                        class="px-6 py-2.5 bg-gradient-to-r from-primary-600 to-primary-700 text-white rounded-xl hover:from-primary-700 hover:to-primary-800 transition-all duration-200 shadow-lg shadow-primary-100 hover:shadow-primary-200 font-medium text-sm disabled:opacity-50 disabled:cursor-not-allowed flex items-center gap-2"
                        :disabled="processing">
                        <svg v-if="!processing" class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                        </svg>
                        <svg v-else class="animate-spin h-4 w-4" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4">
                            </circle>
                            <path class="opacity-75" fill="currentColor"
                                d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z">
                            </path>
                        </svg>
                        {{ category ? 'Update Category' : 'Create Category' }}
                    </button>
                </div>
            </form>
        </div>
    </div>
</template>

<script setup>
    import { ref, watch } from 'vue';
    import CategoryIconPicker from '@/Components/Admin/CategoryIconPicker.vue';
    const props = defineProps({
        category: {
            type: Object,
            default: null,
        },
        rootCategories: {
            type: Array,
            default: () => [],
        },
    });

    const emit = defineEmits(['close', 'save']);

    const processing = ref(false);
    const errors = ref({});

    const form = ref({
        parent_id: null,
        name: '',
        icon: '',
        description: '',
        is_active: true,
        sort_order: 0,
    });


    const resetForm = () => {
        form.value = {
            parent_id: null,
            name: '',
            icon: '',
            description: '',
            is_active: true,
            sort_order: 0,
        };
    };

    // Watch for category changes
    watch(() => props.category, (newCategory) => {
        if (newCategory) {
            form.value = {
                parent_id: newCategory.parent_id || null,
                name: newCategory.name || '',
                icon: newCategory.icon || '',
                description: newCategory.description || '',
                is_active: newCategory.is_active ?? true,
                sort_order: newCategory.sort_order || 0,
            };
        } else {
            resetForm();
        }
    }, { immediate: true });



    const handleSubmit = () => {
        processing.value = true;
        errors.value = {};

        // Basic validation
        if (!form.value.name.trim()) {
            errors.value = { name: 'Category name is required' };
            processing.value = false;
            return;
        }

        emit('save', form.value, {
            onFinish: () => {
                processing.value = false;
            },
            onError: (err) => {
                errors.value = err || {};
                processing.value = false;
            },
        });
    };
</script>