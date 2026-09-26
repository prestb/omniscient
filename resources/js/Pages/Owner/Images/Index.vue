<!-- resources/js/Pages/Owner/Images/Index.vue -->
<template>
    <AuthenticatedLayout>
        <!-- COMPACT HEADER -->
        <PageHeader color="purple" :breadcrumb="[
            { label: 'Dashboard', href: '/owner/dashboard' },
            { label: 'Businesses', href: '/owner/businesses' },
            { label: business.name, href: `/owner/businesses/${business.id}/edit` },
            { label: 'Images' }
        ]">
            <template #icon>
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                    d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z" />
            </template>
            <template #title>Manage images</template>
            <template #subtitle>{{ business.name }}</template>
        </PageHeader>

        <div class="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8 py-8 space-y-6">

            <!-- UPLOAD CARD -->
            <div class="bg-white dark:bg-gray-800 rounded-2xl border border-gray-100 dark:border-gray-700 shadow-sm overflow-hidden">
                <div
                    class="px-6 py-4 border-b border-gray-100 dark:border-gray-700 bg-gradient-to-br from-gray-50 to-white dark:from-gray-800 dark:to-gray-800/50 flex items-center gap-3">
                    <div class="w-9 h-9 rounded-xl bg-purple-50 dark:bg-purple-900/30 flex items-center justify-center">
                        <svg class="w-4 h-4 text-purple-600 dark:text-purple-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                        </svg>
                    </div>
                    <div>
                        <h3 class="text-sm font-bold text-gray-900 dark:text-white tracking-tight">Upload new image</h3>
                        <p class="text-xs text-gray-400 dark:text-gray-500">Add a logo, cover, or gallery photo</p>
                    </div>
                </div>

                <div class="p-6">
                    <form @submit.prevent="uploadImage" enctype="multipart/form-data" class="space-y-5">

                        <!-- Type first — so the guidance below can adapt -->
                        <div>
                            <label class="block text-xs font-bold text-gray-500 dark:text-gray-400 uppercase tracking-widest mb-2">
                                What are you uploading? *
                            </label>
                            <div class="grid grid-cols-3 gap-2">
                                <button v-for="opt in uploadTypeOptions" :key="opt.value" type="button"
                                    @click="uploadForm.type = opt.value"
                                    class="flex flex-col items-center gap-1.5 p-3 rounded-xl border-2 transition-all"
                                    :class="uploadForm.type === opt.value
                                        ? 'border-primary-500 bg-primary-50 dark:bg-primary-900/30 text-primary-700 dark:text-primary-400'
                                        : 'border-gray-200 dark:border-gray-600 hover:border-gray-300 dark:hover:border-gray-500 text-gray-600 dark:text-gray-400'">
                                    <span class="text-lg">{{ opt.icon }}</span>
                                    <span class="text-xs font-bold">{{ opt.label }}</span>
                                </button>
                            </div>
                        </div>

                        <!-- Dimension guidance + live preview -->
                        <div class="grid grid-cols-1 md:grid-cols-5 gap-4 items-start">
                            <!-- Guidance column -->
                            <div class="md:col-span-3 space-y-3">
                                <div class="p-4 bg-blue-50 dark:bg-blue-900/20 border border-blue-200 dark:border-blue-800 rounded-xl">
                                    <div class="flex items-start gap-3">
                                        <svg class="w-5 h-5 text-blue-600 dark:text-blue-400 flex-shrink-0 mt-0.5" fill="none"
                                            stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                        </svg>
                                        <div class="text-sm">
                                            <p class="font-bold text-blue-900 dark:text-blue-300">
                                                Recommended: {{ currentTypeGuidance.recommended }}
                                            </p>
                                            <p class="text-blue-700 dark:text-blue-400 text-xs mt-0.5 leading-relaxed">
                                                {{ currentTypeGuidance.description }}
                                            </p>
                                        </div>
                                    </div>
                                </div>

                                <!-- File picker -->
                                <div>
                                    <div class="relative">
                                        <input type="file" ref="fileInput" @change="handleFileSelect" accept="image/*"
                                            class="hidden" required />
                                        <button type="button" @click="fileInput?.click()"
                                            class="w-full px-4 py-4 border-2 border-dashed border-gray-300 dark:border-gray-600 rounded-xl hover:border-primary-400 dark:hover:border-primary-600 hover:bg-primary-50/30 dark:hover:bg-primary-900/10 transition-all flex flex-col items-center justify-center gap-1.5 text-gray-500 dark:text-gray-400 hover:text-primary-600 dark:hover:text-primary-400">
                                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                    d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12" />
                                            </svg>
                                            <span class="text-xs font-semibold truncate max-w-full px-2">
                                                {{ selectedFileName || 'Click to select an image' }}
                                            </span>
                                            <span class="text-[10px] text-gray-400 dark:text-gray-500">PNG, JPG, WEBP · Max 5MB</span>
                                        </button>
                                    </div>
                                </div>
                            </div>

                            <!-- Live preview column -->
                            <div class="md:col-span-2">
                                <label class="block text-xs font-bold text-gray-500 dark:text-gray-400 uppercase tracking-widest mb-2">
                                    Preview
                                </label>
                                <div class="rounded-xl border border-gray-200 dark:border-gray-600 bg-gray-50 dark:bg-gray-900/50 overflow-hidden flex items-center justify-center"
                                    :style="{ aspectRatio: currentTypeGuidance.ratio }">
                                    <img v-if="previewUrl" :src="previewUrl" alt="Preview"
                                        class="w-full h-full object-cover" />
                                    <div v-else class="text-center p-3">
                                        <svg class="w-6 h-6 text-gray-300 dark:text-gray-600 mx-auto mb-1" fill="none"
                                            stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z" />
                                        </svg>
                                        <p class="text-[10px] text-gray-400 dark:text-gray-500">Select a file to preview</p>
                                    </div>
                                </div>
                                <p class="text-[10px] text-gray-400 dark:text-gray-500 mt-1.5 text-center">
                                    {{ currentTypeGuidance.ratioLabel }}
                                </p>
                            </div>
                        </div>

                        <!-- Caption -->
                        <div>
                            <label class="block text-xs font-bold text-gray-500 dark:text-gray-400 uppercase tracking-widest mb-2">
                                Caption <span class="text-gray-400 dark:text-gray-500 text-[10px] normal-case">(optional)</span>
                            </label>
                            <input type="text" v-model="uploadForm.caption" maxlength="100"
                                class="w-full px-4 py-2.5 border border-gray-200 dark:border-gray-600 bg-white dark:bg-gray-900 text-gray-900 dark:text-white placeholder-gray-400 dark:placeholder-gray-500 rounded-xl focus:outline-none focus:ring-2 focus:ring-primary-500 focus:border-transparent transition-colors text-sm"
                                placeholder="Short description" />
                            <div class="flex justify-end mt-1.5 text-xs">
                                <span :class="{
                                    'text-gray-400 dark:text-gray-500': (uploadForm.caption?.length || 0) < 80,
                                    'text-amber-600 dark:text-amber-400': (uploadForm.caption?.length || 0) >= 80 && (uploadForm.caption?.length || 0) < 100,
                                    'text-red-600 dark:text-red-400 font-semibold': (uploadForm.caption?.length || 0) >= 100,
                                }">
                                    {{ uploadForm.caption?.length || 0 }} / 100
                                </span>
                            </div>
                        </div>

                        <!-- Submit -->
                        <div class="flex justify-end pt-2">
                            <button type="submit" :disabled="uploading || !uploadForm.file"
                                class="inline-flex items-center justify-center gap-2 px-6 py-2.5 bg-gradient-to-r from-primary-600 to-primary-700 text-white rounded-xl hover:from-primary-700 hover:to-primary-800 transition-all shadow-lg shadow-primary-500/25 hover:shadow-primary-500/40 hover:-translate-y-0.5 font-semibold text-sm disabled:opacity-50 disabled:cursor-not-allowed disabled:hover:translate-y-0">
                                <span v-if="uploading" class="flex items-center gap-2">
                                    <svg class="animate-spin h-4 w-4" fill="none" viewBox="0 0 24 24">
                                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor"
                                            stroke-width="4"></circle>
                                        <path class="opacity-75" fill="currentColor"
                                            d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z">
                                        </path>
                                    </svg>
                                    Uploading…
                                </span>
                                <span v-else class="flex items-center gap-2">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12" />
                                    </svg>
                                    Upload Image
                                </span>
                            </button>
                        </div>
                    </form>
                </div>
            </div>

            <!-- IMAGES GRID -->
            <div v-if="images && images.length > 0"
                class="bg-white dark:bg-gray-800 rounded-2xl border border-gray-100 dark:border-gray-700 shadow-sm overflow-hidden">
                <div
                    class="px-6 py-4 border-b border-gray-100 dark:border-gray-700 bg-gradient-to-br from-gray-50 to-white dark:from-gray-800 dark:to-gray-800/50 flex items-center gap-3">
                    <div class="w-9 h-9 rounded-xl bg-purple-50 dark:bg-purple-900/30 flex items-center justify-center">
                        <svg class="w-4 h-4 text-purple-600 dark:text-purple-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z" />
                        </svg>
                    </div>
                    <div>
                        <h3 class="text-sm font-bold text-gray-900 dark:text-white tracking-tight">Your images</h3>
                        <p class="text-xs text-gray-400 dark:text-gray-500">{{ images.length }} total</p>
                    </div>
                </div>

                <div class="p-6">
                    <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 gap-4">
                        <div v-for="image in images" :key="image.id" class="relative group">
                            <div class="aspect-square rounded-xl overflow-hidden border"
                                :class="image.hidden_at ? 'border-amber-300 dark:border-amber-700 opacity-60' : 'border-gray-200 dark:border-gray-600'">
                                <img :src="'/storage/' + image.path" :alt="image.caption || 'Business image'"
                                    class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-300" />
                            </div>

                            <!-- Hidden badge -->
                            <div v-if="image.hidden_at"
                                class="absolute top-2 left-2 px-2 py-0.5 text-[10px] font-bold uppercase tracking-wide bg-amber-500 text-white rounded-full shadow">
                                Hidden
                            </div>

                            <!-- Type badge -->
                            <div
                                class="absolute bottom-2 left-2 px-2 py-0.5 text-[10px] font-bold uppercase tracking-wide bg-black/60 backdrop-blur-sm text-white rounded-full">
                                {{ image.type }}
                            </div>

                            <!-- Hover overlay -->
                            <div
                                class="absolute inset-0 bg-black/60 opacity-0 group-hover:opacity-100 transition-opacity flex items-center justify-center gap-2 rounded-xl">
                                <button @click="deleteImage(image)"
                                    class="px-2.5 py-1.5 text-[11px] font-semibold bg-red-600 hover:bg-red-700 text-white rounded-lg transition-colors">
                                    Delete
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- EMPTY STATE -->
            <div v-else class="bg-white dark:bg-gray-800 rounded-2xl shadow-sm border border-gray-100 dark:border-gray-700 p-12 text-center">
                <div
                    class="w-20 h-20 bg-gradient-to-br from-purple-100 to-purple-50 dark:from-purple-900/40 dark:to-purple-900/20 rounded-2xl flex items-center justify-center mx-auto mb-5 shadow-sm">
                    <svg class="w-10 h-10 text-purple-600 dark:text-purple-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z" />
                    </svg>
                </div>
                <h3 class="text-xl font-bold text-gray-900 dark:text-white tracking-tight mb-2">No images yet</h3>
                <p class="text-gray-500 dark:text-gray-400 max-w-md mx-auto mb-6 text-sm">
                    Upload a logo, cover image, and gallery photos to make your business stand out.
                </p>
                <button @click="fileInput?.click()"
                    class="inline-flex items-center gap-2 px-6 py-3 bg-gradient-to-r from-primary-600 to-primary-700 text-white rounded-xl hover:from-primary-700 hover:to-primary-800 transition-all duration-200 shadow-lg shadow-primary-500/25 hover:shadow-primary-500/40 hover:-translate-y-0.5 font-semibold text-sm">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12" />
                    </svg>
                    Upload Your First Image
                </button>
            </div>
        </div>
    </AuthenticatedLayout>
</template>

<script setup>
    import { ref, reactive, computed, watch, onBeforeUnmount } from 'vue';
    import { router } from '@inertiajs/vue3';
    import { useToast } from '@/composables/useToast';
    import { useConfirm } from '@/composables/useConfirm';
    import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
    import PageHeader from '@/Components/PageHeader.vue';

    const props = defineProps({
        business: { type: Object, required: true },
        images: { type: Array, default: () => [] },
    });

    const { error, warning } = useToast();
    const { confirm: confirmDialog } = useConfirm();
    const fileInput = ref(null);
    const uploading = ref(false);
    const previewUrl = ref(null);

    const uploadForm = reactive({
        type: 'gallery',
        caption: '',
        file: null,
    });

    const selectedFileName = computed(() => uploadForm.file?.name || '');

    // ============== TYPE OPTIONS + GUIDANCE ==============
    const uploadTypeOptions = [
        { value: 'gallery', label: 'Gallery', icon: '🖼️' },
        { value: 'logo', label: 'Logo', icon: '🎨' },
        { value: 'cover', label: 'Cover', icon: '🌄' },
    ];

    const typeGuidance = {
        logo: {
            recommended: '512 × 512 px',
            ratio: '1 / 1',
            ratioLabel: 'Square (1:1)',
            description: 'Logos are shown as a square. Use a square image with a transparent or solid background for best results.',
        },
        cover: {
            recommended: '1600 × 600 px',
            ratio: '16 / 6',
            ratioLabel: 'Wide banner (~2.67:1)',
            description: 'Covers appear as a wide banner at the top of your profile. Edges may be cropped on smaller screens.',
        },
        gallery: {
            recommended: '1200 × 900 px',
            ratio: '4 / 3',
            ratioLabel: 'Landscape (4:3)',
            description: 'Gallery photos look best in landscape orientation. Landscape format fills the grid evenly.',
        },
    };

    const currentTypeGuidance = computed(() => typeGuidance[uploadForm.type] || typeGuidance.gallery);

    // ============== PREVIEW URL LIFECYCLE ==============
    // Revoke the blob URL whenever it changes or the component unmounts
    watch(previewUrl, (url, prev) => {
        if (prev) URL.revokeObjectURL(prev);
        void url;
    });

    onBeforeUnmount(() => {
        if (previewUrl.value) URL.revokeObjectURL(previewUrl.value);
    });

    const handleFileSelect = (event) => {
        const file = event.target.files[0];
        if (!file) return;

        if (file.size > 5 * 1024 * 1024) {
            error('File Too Large 📁', 'Image size must be less than 5MB.', { duration: 4000 });
            if (fileInput.value) fileInput.value.value = '';
            uploadForm.file = null;
            if (previewUrl.value) {
                URL.revokeObjectURL(previewUrl.value);
                previewUrl.value = null;
            }
            return;
        }

        uploadForm.file = file;
        if (previewUrl.value) URL.revokeObjectURL(previewUrl.value);
        previewUrl.value = URL.createObjectURL(file);
    };

    const uploadImage = async () => {
        if (!uploadForm.file) {
            warning('No File Selected 📁', 'Please select an image to upload.', { duration: 3000 });
            return;
        }

        uploading.value = true;

        const formData = new FormData();
        formData.append('image', uploadForm.file);
        formData.append('type', uploadForm.type);
        formData.append('caption', uploadForm.caption || '');

        router.post(`/owner/businesses/${props.business.id}/images`, formData, {
            forceFormData: true,
            onSuccess: () => {
                // ✅ No success toast — the controller flashes
                //    '<Type> uploaded successfully.'
                uploadForm.file = null;
                uploadForm.caption = '';
                if (previewUrl.value) {
                    URL.revokeObjectURL(previewUrl.value);
                    previewUrl.value = null;
                }
            },
            onError: (errors) => {
                // Client-side fallback: server rejected before any flash.
                const firstError = Object.values(errors)[0];
                error('Upload Failed ❌', firstError || 'Failed to upload image.', { duration: 5000 });
            },
            onFinish: () => {
                uploading.value = false;
                if (fileInput.value) fileInput.value.value = '';
            },
        });
    };

    const deleteImage = async (image) => {
        const confirmed = await confirmDialog({
            title: 'Delete this image?',
            message: 'This image will be permanently removed. This action cannot be undone.',
            confirmText: 'Delete',
            cancelText: 'Cancel',
            variant: 'danger',
        });

        if (!confirmed) return;

        router.delete(`/owner/businesses/${props.business.id}/images/${image.id}`, {
            preserveScroll: true,
            // ✅ No success toast — the controller flashes
            //    'Image deleted successfully.'
            onError: () => {
                error('Delete Failed ❌', 'Failed to delete image.', { duration: 4000 });
            },
        });
    };
</script>