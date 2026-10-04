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

                <!-- PHASE 21B-G-R2 - the empty state explains what this surface is
                     for and what each media type does, then points at the upload
                     controls directly above. It does not restate their constraints. -->
                <div v-if="!images.length"
                    class="rounded-2xl border border-dashed border-gray-200 dark:border-gray-700 p-8 text-center">
                    <div class="w-14 h-14 bg-gray-100 dark:bg-gray-700 rounded-2xl flex items-center justify-center mx-auto mb-4">
                        <svg class="w-7 h-7 text-gray-400 dark:text-gray-500" fill="none" stroke="currentColor"
                            viewBox="0 0 24 24" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z" />
                        </svg>
                    </div>

                    <h3 class="text-base font-bold text-gray-900 dark:text-white tracking-tight mb-2">
                        No media on this Listing yet
                    </h3>
                    <p class="text-sm text-gray-500 dark:text-gray-400 max-w-md mx-auto">
                        Images help visitors understand what this Listing is before they get in touch.
                    </p>

                    <dl class="mt-5 text-sm text-left max-w-md mx-auto space-y-2">
                        <div class="flex gap-3">
                            <dt class="font-semibold text-gray-900 dark:text-white w-16 flex-shrink-0">Logo</dt>
                            <dd class="text-gray-500 dark:text-gray-400 min-w-0">A small mark shown beside the name.</dd>
                        </div>
                        <div class="flex gap-3">
                            <dt class="font-semibold text-gray-900 dark:text-white w-16 flex-shrink-0">Cover</dt>
                            <dd class="text-gray-500 dark:text-gray-400 min-w-0">The wide image at the top of the Listing.</dd>
                        </div>
                        <div class="flex gap-3">
                            <dt class="font-semibold text-gray-900 dark:text-white w-16 flex-shrink-0">Gallery</dt>
                            <dd class="text-gray-500 dark:text-gray-400 min-w-0">Photos of your work, products or premises.</dd>
                        </div>
                    </dl>

                    <p class="mt-6 text-sm text-gray-500 dark:text-gray-400">
                        Use the upload controls above to add them. You can change or remove any image later.
                    </p>
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
