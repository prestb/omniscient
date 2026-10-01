<!-- resources/js/Components/Public/LeadCaptureForm.vue -->
<template>
    <form @submit.prevent="submit" class="space-y-5">

        <!-- Name -->
        <div v-if="!isLoggedIn">
            <label class="block text-[10px] font-bold text-gray-500 dark:text-gray-400 uppercase tracking-widest mb-2">
                Your name <span class="text-red-500">*</span>
            </label>
            <input v-model="form.name" type="text" required maxlength="255" placeholder="John Doe"
                class="w-full px-4 py-2.5 border border-gray-200 dark:border-gray-600 rounded-xl bg-white dark:bg-gray-900 text-gray-900 dark:text-white placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-primary-500 focus:border-transparent transition-colors text-sm" />
        </div>

        <!-- Logged-in-as strip -->
        <div v-else
            class="flex items-center gap-2.5 p-3 bg-gray-50 dark:bg-gray-900/50 rounded-xl border border-gray-100 dark:border-gray-700">
            <div
                class="w-8 h-8 rounded-full bg-gradient-to-br from-primary-500 to-purple-600 flex items-center justify-center text-white text-xs font-bold flex-shrink-0">
                {{ getInitials(currentUser?.name) }}
            </div>
            <div class="min-w-0 flex-1">
                <p class="text-xs font-bold text-gray-900 dark:text-white truncate">{{ currentUser?.name }}</p>
                <p class="text-[10px] text-gray-500 dark:text-gray-400 truncate">{{ currentUser?.email }}</p>
            </div>
        </div>

        <!-- Email + Phone -->
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
            <div v-if="!isLoggedIn">
                <label
                    class="block text-[10px] font-bold text-gray-500 dark:text-gray-400 uppercase tracking-widest mb-2">
                    Email
                </label>
                <input v-model="form.email" type="email" maxlength="255" placeholder="you@example.com"
                    class="w-full px-4 py-2.5 border border-gray-200 dark:border-gray-600 rounded-xl bg-white dark:bg-gray-900 text-gray-900 dark:text-white placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-primary-500 focus:border-transparent transition-colors text-sm" />
            </div>
            <div :class="isLoggedIn ? 'sm:col-span-2' : ''">
                <label
                    class="block text-[10px] font-bold text-gray-500 dark:text-gray-400 uppercase tracking-widest mb-2">
                    Phone
                </label>
                <input v-model="form.phone" type="tel" maxlength="50" placeholder="+237 6XX XXX XXX"
                    class="w-full px-4 py-2.5 border border-gray-200 dark:border-gray-600 rounded-xl bg-white dark:bg-gray-900 text-gray-900 dark:text-white placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-primary-500 focus:border-transparent transition-colors text-sm" />
            </div>
        </div>

        <!-- Subject -->
        <div>
            <label class="block text-[10px] font-bold text-gray-500 dark:text-gray-400 uppercase tracking-widest mb-2">
                Subject
            </label>
            <input v-model="form.subject" type="text" maxlength="255" placeholder="e.g., Request for quote"
                class="w-full px-4 py-2.5 border border-gray-200 dark:border-gray-600 rounded-xl bg-white dark:bg-gray-900 text-gray-900 dark:text-white placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-primary-500 focus:border-transparent transition-colors text-sm" />
        </div>

        <!-- Message -->
        <div>
            <label class="block text-[10px] font-bold text-gray-500 dark:text-gray-400 uppercase tracking-widest mb-2">
                Message <span class="text-red-500">*</span>
            </label>
            <textarea v-model="form.message" required maxlength="2000" rows="5" placeholder="Tell us what you need…"
                class="w-full px-4 py-2.5 border border-gray-200 dark:border-gray-600 rounded-xl bg-white dark:bg-gray-900 text-gray-900 dark:text-white placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-primary-500 focus:border-transparent transition-colors resize-none text-sm"></textarea>
            <p class="text-xs text-gray-400 dark:text-gray-500 mt-1.5 text-right font-medium">{{ form.message.length
                }}/2000</p>
        </div>

        <!-- Submit -->
        <button type="submit" :disabled="submitting"
            class="w-full inline-flex items-center justify-center gap-2 px-6 py-3 bg-gradient-to-r from-primary-600 to-primary-700 text-white rounded-xl font-semibold hover:from-primary-700 hover:to-primary-800 transition-all shadow-lg shadow-primary-500/25 hover:shadow-primary-500/40 hover:-translate-y-0.5 disabled:opacity-50 disabled:cursor-not-allowed disabled:hover:translate-y-0 text-sm">
            <span v-if="submitting" class="flex items-center gap-2">
                <svg class="animate-spin h-4 w-4" fill="none" viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                    <path class="opacity-75" fill="currentColor"
                        d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z">
                    </path>
                </svg>
                Sending…
            </span>
            <span v-else class="flex items-center gap-2">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8" />
                </svg>
                Send Message
            </span>
        </button>

        <!-- Success banner -->
        <Transition enter-active-class="transition duration-200 ease-out" enter-from-class="opacity-0 -translate-y-2"
            enter-to-class="opacity-100 translate-y-0" leave-active-class="transition duration-150 ease-in"
            leave-from-class="opacity-100 translate-y-0" leave-to-class="opacity-0 -translate-y-2">
            <div v-if="successMessage"
                class="p-4 bg-gradient-to-br from-emerald-50 to-green-50 dark:from-emerald-900/20 dark:to-green-900/20 border border-emerald-200 dark:border-emerald-800 rounded-xl flex items-start gap-3">
                <div
                    class="w-8 h-8 rounded-full bg-emerald-100 dark:bg-emerald-900/40 flex items-center justify-center flex-shrink-0">
                    <svg class="w-4 h-4 text-emerald-600 dark:text-emerald-400" fill="none" stroke="currentColor"
                        viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7" />
                    </svg>
                </div>
                <p class="text-sm text-emerald-800 dark:text-emerald-300 font-medium leading-relaxed">{{ successMessage
                    }}</p>
            </div>
        </Transition>

        <!-- Error banner -->
        <Transition enter-active-class="transition duration-200 ease-out" enter-from-class="opacity-0 -translate-y-2"
            enter-to-class="opacity-100 translate-y-0" leave-active-class="transition duration-150 ease-in"
            leave-from-class="opacity-100 translate-y-0" leave-to-class="opacity-0 -translate-y-2">
            <div v-if="errorMessage"
                class="p-4 bg-gradient-to-br from-red-50 to-red-100/50 dark:from-red-900/20 dark:to-red-900/10 border border-red-200 dark:border-red-800 rounded-xl flex items-start gap-3">
                <div
                    class="w-8 h-8 rounded-full bg-red-100 dark:bg-red-900/40 flex items-center justify-center flex-shrink-0">
                    <svg class="w-4 h-4 text-red-600 dark:text-red-400" fill="none" stroke="currentColor"
                        viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                    </svg>
                </div>
                <p class="text-sm text-red-800 dark:text-red-300 font-medium leading-relaxed">{{ errorMessage }}</p>
            </div>
        </Transition>
    </form>
</template>

<script setup>
    import { ref, reactive, computed, watch } from 'vue';
    import axios from 'axios';
    import { usePage } from '@inertiajs/vue3';

    const props = defineProps({
        /*
         * PHASE 12 — the inquiry is attributed to the LISTING the visitor is
         * viewing. Business is optional context that the server derives from the
         * Listing; it is never submitted by the client.
         */
        listing: {
            type: Object,
            required: true,
        },
    });

    const emit = defineEmits(['submitted']);

    const page = usePage();

    const currentUser = computed(() => page.props.auth?.user || null);
    const isLoggedIn = computed(() => !!currentUser.value);

    const form = reactive({
        name: '',
        email: '',
        phone: '',
        subject: '',
        message: '',
    });

    // Prefill name + email from the authenticated user
    watch(
        currentUser,
        (user) => {
            if (user) {
                form.name = user.name || '';
                form.email = user.email || '';
            }
        },
        { immediate: true }
    );

    const submitting = ref(false);
    const successMessage = ref('');
    const errorMessage = ref('');

    const getInitials = (name) => {
        if (!name) return '?';
        return name.split(' ').map((n) => n[0]).join('').toUpperCase().slice(0, 2);
    };

    const submit = async () => {
        submitting.value = true;
        successMessage.value = '';
        errorMessage.value = '';

        try {
            const response = await axios.post(`/listing/${props.listing.slug}/contact`, form);

            if (response.data.success) {
                successMessage.value = response.data.message;
                form.name = isLoggedIn.value ? (currentUser.value?.name || '') : '';
                form.email = isLoggedIn.value ? (currentUser.value?.email || '') : '';
                form.phone = '';
                form.subject = '';
                form.message = '';
                emit('submitted');
            }
        } catch (error) {
            if (error.response?.status === 422) {
                const errors = error.response.data.errors || {};
                errorMessage.value = Object.values(errors).flat()[0] || 'Please check your input.';
            } else {
                errorMessage.value = error.response?.data?.message || 'Something went wrong. Please try again.';
            }
        } finally {
            submitting.value = false;
        }
    };
</script>