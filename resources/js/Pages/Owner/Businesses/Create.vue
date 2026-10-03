<!-- resources/js/Pages/Owner/Businesses/Create.vue -->
<template>
    <AuthenticatedLayout>
        <!-- COMPACT HEADER -->
        <PageHeader color="primary" :breadcrumb="[
            { label: 'Dashboard', href: '/owner/dashboard' },
            { label: 'Businesses', href: '/owner/businesses' },
            { label: 'Create' }
        ]">
            <template #icon>
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                    d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" />
            </template>
            <template #title>Create a business</template>
            <template #subtitle>Add your organization. You can complete the details and submit for review
                afterward.</template>
        </PageHeader>

        <div class="max-w-3xl mx-auto px-4 sm:px-6 lg:px-8 py-8 space-y-6">

            <div class="bg-white dark:bg-gray-800 rounded-2xl border border-gray-100 dark:border-gray-700 shadow-sm overflow-hidden">
                <div
                    class="px-6 py-4 border-b border-gray-100 dark:border-gray-700 bg-gradient-to-br from-gray-50 to-white dark:from-gray-800 dark:to-gray-800/50 flex items-center gap-3">
                    <div class="w-9 h-9 rounded-xl bg-primary-50 dark:bg-primary-900/30 flex items-center justify-center">
                        <svg class="w-4 h-4 text-primary-600 dark:text-primary-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                        </svg>
                    </div>
                    <div>
                        <h3 class="text-sm font-bold text-gray-900 dark:text-white tracking-tight">Basic information</h3>
                        <p class="text-xs text-gray-400 dark:text-gray-500">Start with the essentials — you can edit everything later</p>
                    </div>
                </div>

                <div class="p-6">
                    <form @submit.prevent="submit" class="space-y-6">
                        <div>
                            <label class="block text-xs font-bold text-gray-500 dark:text-gray-400 uppercase tracking-widest mb-2">
                                Business name *
                            </label>
                            <input type="text" v-model="form.name" required maxlength="100"
                                class="w-full px-4 py-2.5 border border-gray-200 dark:border-gray-600 rounded-xl bg-white dark:bg-gray-900 text-gray-900 dark:text-white placeholder-gray-400 dark:placeholder-gray-500 focus:outline-none focus:ring-2 focus:ring-primary-500 focus:border-transparent transition-colors text-sm"
                                placeholder="e.g., SBKRAFT" />
                            <div class="flex justify-between mt-1.5 text-xs">
                                <p v-if="form.errors.name" class="text-red-500 dark:text-red-400">{{ form.errors.name }}</p>
                                <span v-else class="text-gray-400 dark:text-gray-500">Keep it short and memorable</span>
                                <span :class="{
                                    'text-gray-400 dark:text-gray-500': (form.name?.length || 0) < 80,
                                    'text-amber-600 dark:text-amber-400': (form.name?.length || 0) >= 80 && (form.name?.length || 0) < 100,
                                    'text-red-600 dark:text-red-400 font-semibold': (form.name?.length || 0) >= 100,
                                }">
                                    {{ form.name?.length || 0 }} / 100
                                </span>
                            </div>
                        </div>

                        <div>
                            <label class="block text-xs font-bold text-gray-500 dark:text-gray-400 uppercase tracking-widest mb-2">
                                Description
                            </label>
                            <textarea v-model="form.description" rows="4"
                                class="w-full px-4 py-2.5 border border-gray-200 dark:border-gray-600 rounded-xl bg-white dark:bg-gray-900 text-gray-900 dark:text-white placeholder-gray-400 dark:placeholder-gray-500 focus:outline-none focus:ring-2 focus:ring-primary-500 focus:border-transparent transition-colors resize-none text-sm"
                                placeholder="Tell customers what your business offers…"></textarea>
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div>
                                <label class="block text-xs font-bold text-gray-500 dark:text-gray-400 uppercase tracking-widest mb-2">
                                    Category *
                                </label>
                                <select v-model="form.category_id" required
                                    class="w-full px-4 py-2.5 border border-gray-200 dark:border-gray-600 rounded-xl bg-white dark:bg-gray-900 text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-primary-500 focus:border-transparent transition-colors appearance-none text-sm">
                                    <option value="">Select Category</option>
                                    <option v-for="category in categories" :key="category.id" :value="category.id">
                                        {{ category.name }}
                                    </option>
                                </select>
                            </div>
                            <div>
                                <label class="block text-xs font-bold text-gray-500 dark:text-gray-400 uppercase tracking-widest mb-2">
                                    Email
                                </label>
                                <input type="email" v-model="form.email"
                                    class="w-full px-4 py-2.5 border border-gray-200 dark:border-gray-600 rounded-xl bg-white dark:bg-gray-900 text-gray-900 dark:text-white placeholder-gray-400 dark:placeholder-gray-500 focus:outline-none focus:ring-2 focus:ring-primary-500 focus:border-transparent transition-colors text-sm"
                                    placeholder="business@example.com" />
                            </div>
                        </div>

                        <div class="flex flex-col sm:flex-row justify-end gap-3 pt-6 border-t border-gray-100 dark:border-gray-700">
                            <a href="/owner/businesses"
                                class="inline-flex items-center justify-center px-6 py-2.5 border border-gray-300 dark:border-gray-600 text-gray-700 dark:text-gray-300 rounded-xl hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors font-semibold text-sm">
                                Cancel
                            </a>
                            <button type="submit" :disabled="form.processing"
                                class="inline-flex items-center justify-center px-6 py-2.5 bg-gradient-to-r from-primary-600 to-primary-700 text-white rounded-xl hover:from-primary-700 hover:to-primary-800 transition-all shadow-lg shadow-primary-500/25 hover:shadow-primary-500/40 hover:-translate-y-0.5 font-semibold text-sm disabled:opacity-50 disabled:cursor-not-allowed disabled:hover:translate-y-0">
                                <span v-if="form.processing" class="flex items-center gap-2">
                                    <svg class="animate-spin h-4 w-4" fill="none" viewBox="0 0 24 24">
                                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor"
                                            stroke-width="4"></circle>
                                        <path class="opacity-75" fill="currentColor"
                                            d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z">
                                        </path>
                                    </svg>
                                    Creating...
                                </span>
                                <span v-else class="flex items-center gap-2">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M12 4v16m8-8H4" />
                                    </svg>
                                    Create Business
                                </span>
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>

<script setup>
    import { useForm } from '@inertiajs/vue3';
    import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
    import PageHeader from '@/Components/PageHeader.vue';

    const props = defineProps({
        categories: Array,
    });

    const form = useForm({
        name: '',
        description: '',
        category_id: '',
        email: '',
    });

    const submit = () => {
        form.post('/owner/businesses', {
            preserveScroll: true,
            // ✅ No page-level toast — the controller flashes
            //    "\"<name>\" has been created successfully." and
            //    AuthenticatedLayout shows it exactly once.
            //    Validation errors render inline (form.errors.name).
        });
    };
</script>