<!-- resources/js/Pages/Owner/Listings/Create.vue -->
<template>
    <AuthenticatedLayout>
        <Head title="Create Listing" />

        <div class="max-w-3xl mx-auto px-4 sm:px-6 lg:px-8 py-8 space-y-6">
            <div>
                <h1 class="text-2xl font-bold text-gray-900 dark:text-white tracking-tight">Create a Listing</h1>
                <p class="text-sm text-gray-500 dark:text-gray-400 mt-0.5">
                    A Listing is the discoverable entity. Organization and Location are optional.
                </p>
            </div>

            <form @submit.prevent="submit" class="bg-white dark:bg-gray-800 rounded-2xl border border-gray-100 dark:border-gray-700 p-6 space-y-5">
                <div>
                    <label class="block text-xs font-bold uppercase tracking-widest text-gray-500 mb-2">Listing type</label>
                    <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
                        <button v-for="t in types" :key="t.value" type="button" @click="form.type = t.value"
                            class="px-4 py-3 rounded-xl border text-sm font-semibold transition-all"
                            :class="form.type === t.value
                                ? 'border-primary-500 bg-primary-50 dark:bg-primary-900/20 text-primary-700 dark:text-primary-300'
                                : 'border-gray-200 dark:border-gray-700 text-gray-600 dark:text-gray-300 hover:border-primary-300'">
                            {{ t.label }}
                        </button>
                    </div>
                    <p v-if="form.errors.type" class="text-xs text-rose-600 mt-1">{{ form.errors.type }}</p>
                </div>

                <div>
                    <label class="block text-xs font-bold uppercase tracking-widest text-gray-500 mb-2">Name</label>
                    <input v-model="form.name" type="text" required
                        class="w-full rounded-xl border-gray-300 dark:border-gray-600 dark:bg-gray-900 text-sm" />
                    <p v-if="form.errors.name" class="text-xs text-rose-600 mt-1">{{ form.errors.name }}</p>
                </div>

                <div>
                    <label class="block text-xs font-bold uppercase tracking-widest text-gray-500 mb-2">Description</label>
                    <textarea v-model="form.description" rows="4"
                        class="w-full rounded-xl border-gray-300 dark:border-gray-600 dark:bg-gray-900 text-sm"></textarea>
                    <p v-if="form.errors.description" class="text-xs text-rose-600 mt-1">{{ form.errors.description }}</p>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-5">
                    <div>
                        <label class="block text-xs font-bold uppercase tracking-widest text-gray-500 mb-2">
                            Organization <span class="normal-case font-normal text-gray-400">(optional)</span>
                        </label>
                        <select v-model="form.business_id"
                            class="w-full rounded-xl border-gray-300 dark:border-gray-600 dark:bg-gray-900 text-sm">
                            <option :value="null">None</option>
                            <option v-for="b in businesses" :key="b.id" :value="b.id">{{ b.name }}</option>
                        </select>
                        <p v-if="form.errors.business_id" class="text-xs text-rose-600 mt-1">{{ form.errors.business_id }}</p>
                    </div>

                    <div>
                        <label class="block text-xs font-bold uppercase tracking-widest text-gray-500 mb-2">
                            Location <span class="normal-case font-normal text-gray-400">(optional)</span>
                        </label>
                        <select v-model="form.location_id"
                            class="w-full rounded-xl border-gray-300 dark:border-gray-600 dark:bg-gray-900 text-sm">
                            <option :value="null">None</option>
                            <option v-for="l in locations" :key="l.id" :value="l.id">{{ l.name }}</option>
                        </select>
                        <p v-if="form.errors.location_id" class="text-xs text-rose-600 mt-1">{{ form.errors.location_id }}</p>

                        <!-- PHASE 22A — a Business-less Professional previously saw an
                             empty and inert selector here. Locations are account-owned, so
                             this now offers a real create path. Business is never required. -->
                        <p v-if="!locations.length" class="mt-2 text-xs text-gray-500 dark:text-gray-400">
                            No locations yet.
                            <a :href="'/owner/locations/create'"
                                class="font-semibold text-primary-600 hover:text-primary-700">Add a physical location</a>
                            for this Listing.
                        </p>
                    </div>
                </div>

                <label class="flex items-center gap-2 text-sm text-gray-700 dark:text-gray-300">
                    <input type="checkbox" v-model="form.publish" class="rounded border-gray-300 dark:border-gray-600" />
                    Publish immediately (otherwise it is saved as a draft)
                </label>

                <div class="flex items-center justify-end gap-3 pt-2">
                    <a href="/owner/listings" class="text-sm font-semibold text-gray-500 hover:text-gray-700">Cancel</a>
                    <button type="submit" :disabled="form.processing"
                        class="px-5 py-2.5 bg-gradient-to-r from-primary-600 to-primary-700 text-white rounded-xl font-semibold text-sm hover:from-primary-700 hover:to-primary-800 disabled:opacity-50">
                        Create Listing
                    </button>
                </div>
            </form>
        </div>
    </AuthenticatedLayout>
</template>

<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, useForm } from '@inertiajs/vue3';

defineProps({
    types: { type: Array, default: () => [] },
    businesses: { type: Array, default: () => [] },
    locations: { type: Array, default: () => [] },
});

const form = useForm({
    type: 'business',
    name: '',
    description: '',
    business_id: null,
    location_id: null,
    publish: false,
});

const submit = () => form.post('/owner/listings');
</script>
