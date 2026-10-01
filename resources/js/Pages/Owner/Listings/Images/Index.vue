<!-- resources/js/Pages/Owner/Listings/Images/Index.vue -->
<!--
    PHASE 11 / WAVE 1D-3 — LISTING MEDIA (LISTING-OWNED).

    Managed against ONE explicitly chosen Listing. Nothing here writes
    organization branding (businesses.logo / businesses.cover_image); that is a
    separate Business-owned concept managed on the organization branding screen.
-->
<template>
    <AuthenticatedLayout>
        <Head :title="`Images · ${listing.name}`" />

        <div class="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8 py-8 space-y-6">
            <div>
                <p class="text-xs font-bold uppercase tracking-widest text-gray-400">Listing media</p>
                <h1 class="text-2xl font-bold text-gray-900 dark:text-white tracking-tight">{{ listing.name }}</h1>
                <p class="text-xs text-gray-400 font-mono mt-0.5">/listing/{{ listing.slug }}</p>
            </div>

            <div v-if="page.props.flash?.success"
                class="rounded-xl bg-emerald-50 dark:bg-emerald-900/20 border border-emerald-200 dark:border-emerald-800 px-4 py-3 text-sm text-emerald-800 dark:text-emerald-200">
                {{ page.props.flash.success }}
            </div>
            <div v-if="page.props.flash?.error"
                class="rounded-xl bg-rose-50 dark:bg-rose-900/20 border border-rose-200 dark:border-rose-800 px-4 py-3 text-sm text-rose-800 dark:text-rose-200">
                {{ page.props.flash.error }}
            </div>

            <!-- UPLOAD -->
            <section class="bg-white dark:bg-gray-800 rounded-2xl border border-gray-100 dark:border-gray-700 p-6 space-y-5">
                <h2 class="text-sm font-bold uppercase tracking-widest text-gray-500">Add media to this Listing</h2>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-5">
                    <div>
                        <label class="block text-xs font-bold uppercase tracking-widest text-gray-500 mb-2">Listing logo</label>
                        <input type="file" accept="image/*" @change="uploadSingle('logo', $event)"
                            class="block text-sm text-gray-600 dark:text-gray-300" />
                    </div>
                    <div>
                        <label class="block text-xs font-bold uppercase tracking-widest text-gray-500 mb-2">Listing cover</label>
                        <input type="file" accept="image/*" @change="uploadSingle('cover', $event)"
                            class="block text-sm text-gray-600 dark:text-gray-300" />
                    </div>
                </div>

                <div>
                    <label class="block text-xs font-bold uppercase tracking-widest text-gray-500 mb-2">Gallery images</label>
                    <input type="file" accept="image/*" multiple @change="uploadGallery($event)"
                        class="block text-sm text-gray-600 dark:text-gray-300" />
                </div>

                <p class="text-xs text-gray-400">JPEG, PNG, GIF or WebP · max 5MB each · min 100×100</p>
            </section>

            <!-- EXISTING MEDIA -->
            <section class="bg-white dark:bg-gray-800 rounded-2xl border border-gray-100 dark:border-gray-700 p-6">
                <h2 class="text-sm font-bold uppercase tracking-widest text-gray-500 mb-4">
                    Media ({{ images.length }})
                </h2>

                <div v-if="!images.length" class="text-sm text-gray-500 dark:text-gray-400">
                    No media on this Listing yet.
                </div>

                <div v-else class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-4 gap-4">
                    <div v-for="image in images" :key="image.id"
                        class="rounded-2xl border border-gray-100 dark:border-gray-700 overflow-hidden">
                        <div class="h-28 bg-gray-50 dark:bg-gray-900">
                            <img v-if="image.url" :src="image.url" :alt="image.caption || listing.name"
                                class="w-full h-full object-cover" />
                        </div>
                        <div class="p-3 space-y-1">
                            <span class="inline-flex px-2 py-0.5 rounded-full text-[10px] font-bold uppercase tracking-widest bg-gray-100 dark:bg-gray-700 text-gray-600 dark:text-gray-300">
                                {{ image.type }}
                            </span>
                            <button type="button" @click="removeImage(image)"
                                class="block text-xs font-semibold text-rose-600 hover:text-rose-700">Delete</button>
                        </div>
                    </div>
                </div>
            </section>

            <a :href="`/owner/listings/${listing.id}/edit`"
                class="inline-block text-sm font-semibold text-primary-600 hover:text-primary-700">
                ← Back to the Listing
            </a>
        </div>
    </AuthenticatedLayout>
</template>

<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, router, usePage } from '@inertiajs/vue3';

const props = defineProps({
    listing: { type: Object, required: true },
    images: { type: Array, default: () => [] },
});

const page = usePage();

const uploadSingle = (type, event) => {
    const file = event.target.files?.[0];
    if (!file) return;

    const formData = new FormData();
    formData.append('image', file);
    formData.append('type', type);

    router.post(`/owner/listings/${props.listing.id}/images`, formData, {
        forceFormData: true,
        onFinish: () => { event.target.value = ''; },
    });
};

const uploadGallery = (event) => {
    const files = Array.from(event.target.files ?? []);
    if (!files.length) return;

    const formData = new FormData();
    files.forEach((file) => formData.append('images[]', file));
    formData.append('type', 'gallery');

    router.post(`/owner/listings/${props.listing.id}/images`, formData, {
        forceFormData: true,
        onFinish: () => { event.target.value = ''; },
    });
};

const removeImage = (image) => {
    if (confirm('Delete this image?')) {
        router.delete(`/owner/listings/${props.listing.id}/images/${image.id}`);
    }
};
</script>
