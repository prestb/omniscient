<!-- resources/js/Components/Public/ReviewForm.vue -->
<template>
    <div>
        <!-- Success banner -->
        <Transition enter-active-class="transition duration-200 ease-out" enter-from-class="opacity-0 -translate-y-2"
            enter-to-class="opacity-100 translate-y-0" leave-active-class="transition duration-150 ease-in"
            leave-from-class="opacity-100 translate-y-0" leave-to-class="opacity-0 -translate-y-2">
            <div v-if="successMessage"
                class="mb-4 p-3.5 bg-gradient-to-br from-emerald-50 to-green-50 dark:from-emerald-900/20 dark:to-green-900/20 border border-emerald-200 dark:border-emerald-800 rounded-xl flex items-start gap-2.5">
                <div
                    class="w-7 h-7 rounded-full bg-emerald-100 dark:bg-emerald-900/40 flex items-center justify-center flex-shrink-0">
                    <svg class="w-3.5 h-3.5 text-emerald-600 dark:text-emerald-400" fill="none" stroke="currentColor"
                        viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7" />
                    </svg>
                </div>
                <div class="flex-1 min-w-0">
                    <p class="text-xs font-semibold text-emerald-800 dark:text-emerald-300 leading-relaxed">{{
                        successMessage }}</p>
                    <p class="text-[10px] text-emerald-600 dark:text-emerald-400 mt-0.5">Visible after admin approval.
                    </p>
                </div>
                <button @click="successMessage = ''"
                    class="flex-shrink-0 text-emerald-400 hover:text-emerald-600 transition-colors">
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>
        </Transition>

        <!-- Info banner -->
        <Transition enter-active-class="transition duration-200 ease-out" enter-from-class="opacity-0 -translate-y-2"
            enter-to-class="opacity-100 translate-y-0" leave-active-class="transition duration-150 ease-in"
            leave-from-class="opacity-100 translate-y-0" leave-to-class="opacity-0 -translate-y-2">
            <div v-if="infoMessage"
                class="mb-4 p-3.5 bg-gradient-to-br from-blue-50 to-indigo-50 dark:from-blue-900/20 dark:to-indigo-900/20 border border-blue-200 dark:border-blue-800 rounded-xl flex items-start gap-2.5">
                <div
                    class="w-7 h-7 rounded-full bg-blue-100 dark:bg-blue-900/40 flex items-center justify-center flex-shrink-0">
                    <svg class="w-3.5 h-3.5 text-blue-600 dark:text-blue-400" fill="none" stroke="currentColor"
                        viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                </div>
                <div class="flex-1 min-w-0">
                    <p class="text-xs font-semibold text-blue-800 dark:text-blue-300 leading-relaxed">{{ infoMessage }}
                    </p>
                    <p class="text-[10px] text-blue-600 dark:text-blue-400 mt-0.5">Updated reviews need admin approval.
                    </p>
                </div>
                <button @click="infoMessage = ''"
                    class="flex-shrink-0 text-blue-400 hover:text-blue-600 transition-colors">
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>
        </Transition>

        <!-- Error banner -->
        <Transition enter-active-class="transition duration-200 ease-out" enter-from-class="opacity-0 -translate-y-2"
            enter-to-class="opacity-100 translate-y-0" leave-active-class="transition duration-150 ease-in"
            leave-from-class="opacity-100 translate-y-0" leave-to-class="opacity-0 -translate-y-2">
            <div v-if="errorMessage"
                class="mb-4 p-3.5 bg-gradient-to-br from-red-50 to-red-100/50 dark:from-red-900/20 dark:to-red-900/10 border border-red-200 dark:border-red-800 rounded-xl flex items-start gap-2.5">
                <div
                    class="w-7 h-7 rounded-full bg-red-100 dark:bg-red-900/40 flex items-center justify-center flex-shrink-0">
                    <svg class="w-3.5 h-3.5 text-red-600 dark:text-red-400" fill="none" stroke="currentColor"
                        viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                </div>
                <p class="text-xs font-semibold text-red-800 dark:text-red-300 flex-1 leading-relaxed">{{ errorMessage
                }}</p>
                <button @click="errorMessage = ''"
                    class="flex-shrink-0 text-red-400 hover:text-red-600 transition-colors">
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>
        </Transition>

        <form @submit.prevent="submit" class="space-y-4">

            <!-- Logged-in-as strip -->
            <div v-if="isLoggedIn"
                class="flex items-center gap-2.5 p-3 bg-gray-50 dark:bg-gray-900/50 rounded-xl border border-gray-100 dark:border-gray-700">
                <div
                    class="w-8 h-8 rounded-full bg-gradient-to-br from-primary-500 to-purple-600 flex items-center justify-center text-white text-xs font-bold flex-shrink-0">
                    {{ getInitials(currentUser?.name) }}
                </div>
                <div class="min-w-0 flex-1">
                    <p class="text-xs font-bold text-gray-900 dark:text-white truncate">
                        {{ currentUser?.name }}
                    </p>
                    <p class="text-[10px] text-gray-500 dark:text-gray-400 truncate">
                        {{ currentUser?.email }}
                    </p>
                </div>
            </div>

            <!-- Name + Email (only when anonymous) -->
            <div v-if="!isLoggedIn" class="space-y-4">
                <div>
                    <label
                        class="block text-[10px] font-bold text-gray-500 dark:text-gray-400 uppercase tracking-widest mb-2">
                        Full name <span class="text-red-500">*</span>
                    </label>
                    <div class="relative">
                        <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none">
                            <svg class="h-4 w-4 text-gray-400 dark:text-gray-500" fill="none" stroke="currentColor"
                                viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                            </svg>
                        </div>
                        <input type="text" v-model="form.name"
                            class="w-full pl-10 pr-4 py-2.5 border border-gray-200 dark:border-gray-600 rounded-xl bg-white dark:bg-gray-900 text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-primary-500 focus:border-transparent transition-colors text-sm"
                            placeholder="John Doe" required />
                    </div>
                    <p v-if="errors.name" class="text-red-500 text-xs font-medium mt-1.5">{{ errors.name }}</p>
                </div>

                <div>
                    <label
                        class="block text-[10px] font-bold text-gray-500 dark:text-gray-400 uppercase tracking-widest mb-2">
                        Email address <span class="text-red-500">*</span>
                    </label>
                    <div class="relative">
                        <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none">
                            <svg class="h-4 w-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M16 12a4 4 0 10-8 0 4 4 0 008 0zm0 0v1.5a2.5 2.5 0 005 0V12a9 9 0 10-9 9m4.5-1.206a8.959 8.959 0 01-4.5 1.207" />
                            </svg>
                        </div>
                        <input type="email" v-model="form.email"
                            class="w-full pl-10 pr-4 py-2.5 border border-gray-200 dark:border-gray-600 rounded-xl bg-white dark:bg-gray-900 text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-primary-500 focus:border-transparent transition-colors text-sm"
                            placeholder="you@example.com" required />
                    </div>
                    <p v-if="errors.email" class="text-red-500 text-xs font-medium mt-1.5">{{ errors.email }}</p>
                </div>
            </div>

            <!-- Rating -->
            <div>
                <label
                    class="block text-[10px] font-bold text-gray-500 dark:text-gray-400 uppercase tracking-widest mb-2">
                    Your rating <span class="text-red-500">*</span>
                </label>
                <div class="flex flex-wrap items-center gap-3">
                    <div class="flex gap-1">
                        <button v-for="i in 5" :key="i" type="button" @click="form.rating = i"
                            @mouseenter="hoverRating = i" @mouseleave="hoverRating = 0"
                            class="text-3xl transition-all duration-150 focus:outline-none transform" :class="[
                                i <= (hoverRating || form.rating)
                                    ? 'text-amber-400 scale-110'
                                    : 'text-gray-200 dark:text-gray-700 scale-100 hover:scale-110 hover:text-amber-200'
                            ]">
                            ★
                        </button>
                    </div>
                    <span class="text-sm font-semibold text-gray-700 dark:text-gray-300">
                        {{ ratingLabel }}
                    </span>
                </div>
                <p v-if="errors.rating" class="text-red-500 text-xs font-medium mt-1.5">{{ errors.rating }}</p>
            </div>

            <!-- Title -->
            <div>
                <label
                    class="block text-[10px] font-bold text-gray-500 dark:text-gray-400 uppercase tracking-widest mb-2">
                    Review title
                </label>
                <div class="relative">
                    <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none">
                        <svg class="h-4 w-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M7 8h10M7 12h4m1 8l-4-4H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-3l-4 4z" />
                        </svg>
                    </div>
                    <input type="text" v-model="form.title"
                        class="w-full pl-10 pr-4 py-2.5 border border-gray-200 dark:border-gray-600 rounded-xl bg-white dark:bg-gray-900 text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-primary-500 focus:border-transparent transition-colors text-sm"
                        placeholder="Summarize your experience" />
                </div>
                <p v-if="errors.title" class="text-red-500 text-xs font-medium mt-1.5">{{ errors.title }}</p>
            </div>

            <!-- Content -->
            <div>
                <label
                    class="block text-[10px] font-bold text-gray-500 dark:text-gray-400 uppercase tracking-widest mb-2">
                    Your review <span class="text-red-500">*</span>
                </label>
                <textarea v-model="form.content" rows="4" maxlength="2000"
                    class="w-full px-4 py-2.5 border border-gray-200 dark:border-gray-600 rounded-xl bg-white dark:bg-gray-900 text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-primary-500 focus:border-transparent transition-colors resize-none text-sm"
                    placeholder="Share your experience with this business…" required></textarea>
                <div class="flex justify-between mt-1.5">
                    <p v-if="errors.content" class="text-red-500 text-xs font-medium">{{ errors.content }}</p>
                    <span class="text-[11px] text-gray-400 dark:text-gray-500 ml-auto font-medium">{{ form.content ?
                        form.content.length : 0 }} / 2000</span>
                </div>
            </div>

            <!-- Photos -->
            <div>
                <label
                    class="block text-[10px] font-bold text-gray-500 dark:text-gray-400 uppercase tracking-widest mb-2">
                    Photos <span class="text-gray-400 normal-case text-[10px]">(optional · up to 3)</span>
                </label>

                <div v-if="images.length < MAX_IMAGES" class="grid grid-cols-3 sm:grid-cols-4 gap-2">
                    <div v-for="(img, index) in images" :key="index"
                        class="relative aspect-square rounded-xl overflow-hidden border border-gray-200 dark:border-gray-600 group">
                        <img :src="img.preview" class="w-full h-full object-cover" :alt="'Photo ' + (index + 1)" />
                        <button type="button" @click="removeImage(index)"
                            class="absolute top-1.5 right-1.5 w-6 h-6 rounded-full bg-red-500 hover:bg-red-600 text-white flex items-center justify-center shadow-md opacity-0 group-hover:opacity-100 transition-opacity">
                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5"
                                    d="M6 18L18 6M6 6l12 12" />
                            </svg>
                        </button>
                    </div>

                    <label
                        class="aspect-square rounded-xl border-2 border-dashed border-gray-300 dark:border-gray-600 hover:border-primary-400 dark:hover:border-primary-500 hover:bg-primary-50/30 dark:hover:bg-primary-900/10 transition-colors cursor-pointer flex flex-col items-center justify-center text-gray-400 hover:text-primary-600">
                        <svg class="w-6 h-6 mb-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                        </svg>
                        <span class="text-[10px] font-semibold">Add</span>
                        <input type="file" ref="fileInputRef" @change="handleFileSelect"
                            accept="image/jpeg,image/jpg,image/png,image/webp,image/gif" multiple class="hidden" />
                    </label>
                </div>

                <div v-else class="grid grid-cols-3 sm:grid-cols-4 gap-2">
                    <div v-for="(img, index) in images" :key="index"
                        class="relative aspect-square rounded-xl overflow-hidden border border-gray-200 dark:border-gray-600 group">
                        <img :src="img.preview" class="w-full h-full object-cover" :alt="'Photo ' + (index + 1)" />
                        <button type="button" @click="removeImage(index)"
                            class="absolute top-1.5 right-1.5 w-6 h-6 rounded-full bg-red-500 hover:bg-red-600 text-white flex items-center justify-center shadow-md opacity-0 group-hover:opacity-100 transition-opacity">
                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5"
                                    d="M6 18L18 6M6 6l12 12" />
                            </svg>
                        </button>
                    </div>
                </div>

                <p class="text-[11px] text-gray-400 dark:text-gray-500 mt-1.5">
                    JPEG, PNG, WEBP · Max 5 MB each
                </p>
                <p v-if="errors.images" class="text-red-500 text-xs font-medium mt-1">{{ errors.images }}</p>
                <p v-if="imageError" class="text-red-500 text-xs font-medium mt-1">{{ imageError }}</p>
            </div>

            <!-- Submit -->
            <button type="submit" :disabled="submitting || form.rating === 0"
                class="w-full inline-flex items-center justify-center gap-2 px-6 py-3 bg-gradient-to-r from-primary-600 to-primary-700 text-white rounded-xl hover:from-primary-700 hover:to-primary-800 transition-all shadow-lg shadow-primary-500/25 hover:shadow-primary-500/40 hover:-translate-y-0.5 disabled:opacity-50 disabled:cursor-not-allowed disabled:hover:translate-y-0 font-semibold text-sm">
                <span v-if="submitting" class="flex items-center gap-2">
                    <svg class="animate-spin h-4 w-4" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4">
                        </circle>
                        <path class="opacity-75" fill="currentColor"
                            d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z">
                        </path>
                    </svg>
                    Submitting…
                </span>
                <span v-else class="flex items-center gap-2">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7" />
                    </svg>
                    Submit Review
                </span>
            </button>
        </form>
    </div>
</template>

<script setup>
    import { ref, reactive, computed, watch } from 'vue';
    import { router, usePage } from '@inertiajs/vue3';
    import { useToast } from '@/composables/useToast';

    const MAX_IMAGES = 3;
    const MAX_SIZE_MB = 5;

    const props = defineProps({
        businessId: {
            type: Number,
            required: true
        },
        errors: {
            type: Object,
            default: () => ({})
        }
    });

    const emit = defineEmits(['review-submitted']);

    const page = usePage();
    const { success, error: toastError } = useToast();

    const submitting = ref(false);
    const successMessage = ref('');
    const infoMessage = ref('');
    const errorMessage = ref('');
    const imageError = ref('');
    const hoverRating = ref(0);
    const fileInputRef = ref(null);

    const currentUser = computed(() => page.props.auth?.user || null);
    const isLoggedIn = computed(() => !!currentUser.value);

    const form = reactive({
        name: '',
        email: '',
        rating: 0,
        title: '',
        content: '',
    });

    watch(
        currentUser,
        (user) => {
            if (user) {
                form.name = user.name || '';
                form.email = user.email || '';
            } else {
                form.name = '';
                form.email = '';
            }
        },
        { immediate: true }
    );

    const images = ref([]);

    const ratingLabel = computed(() => {
        const ratings = {
            0: 'Select a rating',
            1: '⭐ Poor',
            2: '⭐⭐ Fair',
            3: '⭐⭐⭐ Average',
            4: '⭐⭐⭐⭐ Good',
            5: '⭐⭐⭐⭐⭐ Excellent',
        };
        return ratings[form.rating] || 'Select a rating';
    });

    const getInitials = (name) => {
        if (!name) return '?';
        return name.split(' ').map((n) => n[0]).join('').toUpperCase().slice(0, 2);
    };

    watch(() => page.props.flash, (newFlash) => {
        if (newFlash?.success) {
            successMessage.value = newFlash.success;
            setTimeout(() => successMessage.value = '', 10000);
        }
        if (newFlash?.info) {
            infoMessage.value = newFlash.info;
            setTimeout(() => infoMessage.value = '', 10000);
        }
        if (newFlash?.error) {
            errorMessage.value = newFlash.error;
            setTimeout(() => errorMessage.value = '', 10000);
        }
    }, { deep: true, immediate: true });

    const handleFileSelect = (event) => {
        imageError.value = '';
        const files = Array.from(event.target.files || []);

        if (files.length === 0) return;

        const remaining = MAX_IMAGES - images.value.length;

        if (files.length > remaining) {
            imageError.value = `You can only add ${remaining} more photo${remaining === 1 ? '' : 's'}.`;
        }

        const toAdd = files.slice(0, remaining);

        for (const file of toAdd) {
            if (file.size > MAX_SIZE_MB * 1024 * 1024) {
                imageError.value = `"${file.name}" exceeds ${MAX_SIZE_MB} MB.`;
                continue;
            }

            if (!['image/jpeg', 'image/jpg', 'image/png', 'image/webp', 'image/gif'].includes(file.type)) {
                imageError.value = `"${file.name}" is not a supported image type.`;
                continue;
            }

            images.value.push({
                file,
                preview: URL.createObjectURL(file),
            });
        }

        if (fileInputRef.value) {
            fileInputRef.value.value = '';
        }
    };

    const removeImage = (index) => {
        const removed = images.value.splice(index, 1)[0];
        if (removed?.preview) {
            URL.revokeObjectURL(removed.preview);
        }
    };

    const submit = () => {
        if (form.rating === 0) {
            toastError('Rating Required ⭐', 'Please select a rating before submitting.', { duration: 3000 });
            return;
        }
        if (!form.name.trim()) {
            toastError('Name Required', 'Please enter your name.', { duration: 3000 });
            return;
        }
        if (!form.email.trim()) {
            toastError('Email Required', 'Please enter your email address.', { duration: 3000 });
            return;
        }
        if (!form.content.trim()) {
            toastError('Review Required', 'Please write your review.', { duration: 3000 });
            return;
        }

        submitting.value = true;
        errorMessage.value = '';
        successMessage.value = '';
        infoMessage.value = '';

        const data = new FormData();
        data.append('name', form.name);
        data.append('email', form.email);
        data.append('rating', form.rating);
        data.append('title', form.title || '');
        data.append('content', form.content);

        images.value.forEach((img, i) => {
            data.append(`images[${i}]`, img.file);
        });

        router.post(`/business/${props.businessId}/reviews`, data, {
            forceFormData: true,
            onFinish: () => { submitting.value = false; },
            onSuccess: () => {
                successMessage.value = 'Your review has been submitted successfully!';
                form.rating = 0;
                form.title = '';
                form.content = '';
                images.value.forEach(img => img.preview && URL.revokeObjectURL(img.preview));
                images.value = [];
                emit('review-submitted');
            },
            onError: (errors) => {
                console.error('Review submission failed:', errors);
                if (typeof errors === 'object') {
                    const firstError = Object.values(errors)[0];
                    errorMessage.value = firstError || 'Failed to submit review. Please try again.';
                } else {
                    errorMessage.value = 'Failed to submit review. Please try again.';
                }
            },
        });
    };
</script>