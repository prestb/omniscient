<!-- resources/js/Pages/Owner/Listings/Edit.vue -->
<template>
    <AuthenticatedLayout>
        <Head :title="`Edit ${listing.name}`" />

        <div class="max-w-3xl mx-auto px-4 sm:px-6 lg:px-8 py-8 space-y-6">
            <div class="flex flex-wrap items-center justify-between gap-3">
                <div>
                    <h1 class="text-2xl font-bold text-gray-900 dark:text-white tracking-tight">{{ listing.name }}</h1>
                    <p class="text-xs text-gray-400 font-mono mt-0.5">/listing/{{ listing.slug }}</p>
                </div>
                <div class="flex items-center gap-3">
                    <span class="inline-flex px-2.5 py-1 rounded-full text-[11px] font-semibold"
                        :class="listing.status === 'published'
                            ? 'bg-emerald-100 text-emerald-700 dark:bg-emerald-900/30 dark:text-emerald-300'
                            : 'bg-gray-100 text-gray-600 dark:bg-gray-700 dark:text-gray-300'">
                        {{ listing.status }}
                    </span>
                    <a v-if="listing.status === 'published'" :href="`/listing/${listing.slug}`" target="_blank"
                        class="text-sm font-semibold text-primary-600 hover:text-primary-700">View public page</a>
                </div>
            </div>

            <div v-if="page.props.flash?.success"
                class="rounded-xl bg-emerald-50 dark:bg-emerald-900/20 border border-emerald-200 dark:border-emerald-800 px-4 py-3 text-sm text-emerald-800 dark:text-emerald-200">
                {{ page.props.flash.success }}
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
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-5">
                    <div>
                        <label class="block text-xs font-bold uppercase tracking-widest text-gray-500 mb-2">Organization</label>
                        <select v-model="form.business_id"
                            class="w-full rounded-xl border-gray-300 dark:border-gray-600 dark:bg-gray-900 text-sm">
                            <option :value="null">None</option>
                            <option v-for="b in businesses" :key="b.id" :value="b.id">{{ b.name }}</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-xs font-bold uppercase tracking-widest text-gray-500 mb-2">Location</label>
                        <select v-model="form.location_id"
                            class="w-full rounded-xl border-gray-300 dark:border-gray-600 dark:bg-gray-900 text-sm">
                            <option :value="null">None</option>
                            <option v-for="l in locations" :key="l.id" :value="l.id">{{ l.name }}</option>
                        </select>
                    </div>
                </div>

                <div>
                    <label class="block text-xs font-bold uppercase tracking-widest text-gray-500 mb-2">
                        Categories <span class="normal-case font-normal text-gray-400">(this Listing)</span>
                    </label>
                    <!-- PHASE 11 / WAVE 1D-3 — categories are Listing-owned, so they
                         are edited against THIS explicit Listing. -->
                    <CategoryMultiSelect v-model="form.categories" :categories="categories" />
                    <p v-if="form.errors.categories" class="text-xs text-rose-600 mt-1">{{ form.errors.categories }}</p>
                </div>

                <div class="flex flex-wrap items-center justify-between gap-3 pt-2">
                    <div class="flex items-center gap-2">
                        <button v-if="listing.status !== 'published'" type="button" @click="publish"
                            class="px-4 py-2 rounded-xl bg-emerald-600 text-white text-sm font-semibold hover:bg-emerald-700">Publish</button>
                        <button v-else type="button" @click="unpublish"
                            class="px-4 py-2 rounded-xl bg-amber-500 text-white text-sm font-semibold hover:bg-amber-600">Unpublish</button>
                    </div>
                    <button type="submit" :disabled="form.processing"
                        class="px-5 py-2.5 bg-gradient-to-r from-primary-600 to-primary-700 text-white rounded-xl font-semibold text-sm hover:from-primary-700 hover:to-primary-800 disabled:opacity-50">
                        Save changes
                    </button>
                </div>
            </form>
        </div>
    </AuthenticatedLayout>
</template>

<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import CategoryMultiSelect from '@/Components/CategoryMultiSelect.vue';
import { Head, useForm, usePage } from '@inertiajs/vue3';

const props = defineProps({
    listing: { type: Object, required: true },
    types: { type: Array, default: () => [] },
    categories: { type: Array, default: () => [] },
    businesses: { type: Array, default: () => [] },
    locations: { type: Array, default: () => [] },
});

const page = usePage();

const form = useForm({
    type: props.listing.type,
    name: props.listing.name,
    description: props.listing.description ?? '',
    business_id: props.listing.business_id ?? null,
    location_id: props.listing.location_id ?? null,
    categories: (props.listing.categories ?? []).map((c) => c.id),
});

const submit = () => form.put(`/owner/listings/${props.listing.id}`);
const publish = () => form.post(`/owner/listings/${props.listing.id}/publish`);
const unpublish = () => form.post(`/owner/listings/${props.listing.id}/unpublish`);
</script>
