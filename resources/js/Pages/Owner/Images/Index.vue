<!-- resources/js/Pages/Owner/Images/Index.vue -->
<!--
    PHASE 11 / WAVE 1D-3 — ORGANIZATION BRANDING (BUSINESS-OWNED).

    This screen manages ONLY the organization's own branding:
    businesses.logo / businesses.cover_image.

    It is deliberately Business-scoped: a Business may have zero Listings and
    still hold branding. It never writes listing_images and never touches a
    Listing. Listing presentation media is managed per Listing at
    /owner/listings/{listing}/images.
-->
<template>
    <AuthenticatedLayout>
        <Head title="Organization branding" />

        <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 py-8 space-y-6">
            <div>
                <h1 class="text-2xl font-bold text-gray-900 dark:text-white tracking-tight">Organization branding</h1>
                <p class="text-sm text-gray-500 dark:text-gray-400 mt-0.5">
                    Branding for <span class="font-semibold">{{ business.name }}</span> as an organization.
                    This is independent of the media on its Listings.
                </p>
            </div>

            <div v-if="page.props.flash?.success"
                class="rounded-xl bg-emerald-50 dark:bg-emerald-900/20 border border-emerald-200 dark:border-emerald-800 px-4 py-3 text-sm text-emerald-800 dark:text-emerald-200">
                {{ page.props.flash.success }}
            </div>
            <div v-if="page.props.flash?.error"
                class="rounded-xl bg-rose-50 dark:bg-rose-900/20 border border-rose-200 dark:border-rose-800 px-4 py-3 text-sm text-rose-800 dark:text-rose-200">
                {{ page.props.flash.error }}
            </div>

            <!-- LOGO -->
            <section class="bg-white dark:bg-gray-800 rounded-2xl border border-gray-100 dark:border-gray-700 p-6">
                <h2 class="text-sm font-bold uppercase tracking-widest text-gray-500 mb-4">Organization logo</h2>
                <div class="flex items-start gap-6">
                    <div
                        class="w-32 h-32 rounded-2xl border border-gray-200 dark:border-gray-700 overflow-hidden bg-gray-50 dark:bg-gray-900 shrink-0">
                        <img v-if="logo_url" :src="logo_url" alt="Organization logo"
                            class="w-full h-full object-contain" />
                        <div v-else class="w-full h-full flex items-center justify-center text-xs text-gray-400">No logo
                        </div>
                    </div>
                    <div class="space-y-3">
                        <input type="file" accept="image/*" @change="upload('logo', $event)"
                            class="block text-sm text-gray-600 dark:text-gray-300" />
                        <p class="text-xs text-gray-400">JPEG, PNG, GIF or WebP · max 5MB · min 100×100</p>
                        <button v-if="logo" type="button" @click="remove('logo')"
                            class="text-sm font-semibold text-rose-600 hover:text-rose-700">Remove logo</button>
                    </div>
                </div>
            </section>

            <!-- COVER -->
            <section class="bg-white dark:bg-gray-800 rounded-2xl border border-gray-100 dark:border-gray-700 p-6">
                <h2 class="text-sm font-bold uppercase tracking-widest text-gray-500 mb-4">Organization cover</h2>
                <div class="space-y-4">
                    <div
                        class="h-40 rounded-2xl border border-gray-200 dark:border-gray-700 overflow-hidden bg-gray-50 dark:bg-gray-900">
                        <img v-if="cover_image_url" :src="cover_image_url" alt="Organization cover"
                            class="w-full h-full object-cover" />
                        <div v-else class="w-full h-full flex items-center justify-center text-xs text-gray-400">No
                            cover image</div>
                    </div>
                    <div class="space-y-3">
                        <input type="file" accept="image/*" @change="upload('cover', $event)"
                            class="block text-sm text-gray-600 dark:text-gray-300" />
                        <p class="text-xs text-gray-400">JPEG, PNG, GIF or WebP · max 5MB · min 100×100</p>
                        <button v-if="cover" type="button" @click="remove('cover')"
                            class="text-sm font-semibold text-rose-600 hover:text-rose-700">Remove cover</button>
                    </div>
                </div>
            </section>

            <p class="text-xs text-gray-400">
                Looking for gallery or per-Listing images?
                <a href="/owner/listings" class="font-semibold text-primary-600 hover:text-primary-700">Open My
                    Listings</a>
                and choose the Listing.
            </p>
        </div>
    </AuthenticatedLayout>
</template>

<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, router, usePage } from '@inertiajs/vue3';

const props = defineProps({
    business: Object,
    logo: { type: String, default: null },
    cover: { type: String, default: null },
    logo_url: { type: String, default: null },
    cover_image_url: { type: String, default: null },
});

const page = usePage();

const upload = (type, event) => {
    const file = event.target.files?.[0];
    if (!file) return;

    const formData = new FormData();
    formData.append('image', file);
    formData.append('type', type);

    router.post(`/owner/businesses/${props.business.id}/images`, formData, {
        forceFormData: true,
        onFinish: () => { event.target.value = ''; },
    });
};

const remove = (type) => {
    if (confirm(`Remove the organization ${type === 'logo' ? 'logo' : 'cover'}?`)) {
        router.delete(`/owner/businesses/${props.business.id}/images/${type}`);
    }
};
</script>
