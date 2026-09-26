<!-- resources/js/Pages/Profile/Partials/UpdateProfileInformationForm.vue -->
<template>
    <form @submit.prevent="submit" class="space-y-6">

        <!-- VERIFIED BANNER -->
        <div v-if="mustVerifyEmail && !verified"
            class="bg-gradient-to-br from-amber-50 to-orange-50 border border-amber-200 rounded-xl p-4 flex items-start gap-3">
            <div class="w-9 h-9 rounded-xl bg-amber-100 flex items-center justify-center flex-shrink-0">
                <svg class="w-4 h-4 text-amber-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />
                </svg>
            </div>
            <div class="flex-1">
                <p class="text-sm font-bold text-amber-900">Your email is unverified</p>
                <p class="text-xs text-amber-700 mt-0.5">
                    Please verify your email address by clicking the link we sent you.
                    <Link v-if="status === 'verification-link-sent'" class="text-amber-900 font-bold underline ml-1"
                        href="/email/verification-notification" method="post" as="button">
                        Resend verification email
                    </Link>
                </p>
                <p v-if="status === 'verification-link-sent'" class="text-xs text-emerald-700 font-semibold mt-1">
                    ✓ A new verification link has been sent.
                </p>
            </div>
        </div>

        <!-- NAME -->
        <div>
            <label class="block text-[10px] font-bold text-gray-500 uppercase tracking-widest mb-2">
                Full name *
            </label>
            <div class="relative">
                <div class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none">
                    <svg class="h-4 w-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                    </svg>
                </div>
                <input type="text" v-model="form.name"
                    class="w-full pl-11 pr-4 py-2.5 border border-gray-200 rounded-xl focus:outline-none focus:ring-2 focus:ring-primary-500 focus:border-transparent transition-colors text-sm"
                    placeholder="John Doe" required />
            </div>
            <p v-if="form.errors.name" class="text-red-500 text-xs font-medium mt-1.5 flex items-center gap-1.5">
                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
                {{ form.errors.name }}
            </p>
        </div>

        <!-- EMAIL -->
        <div>
            <label class="block text-[10px] font-bold text-gray-500 uppercase tracking-widest mb-2">
                Email address *
            </label>
            <div class="relative">
                <div class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none">
                    <svg class="h-4 w-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M16 12a4 4 0 10-8 0 4 4 0 008 0zm0 0v1.5a2.5 2.5 0 005 0V12a9 9 0 10-9 9m4.5-1.206a8.959 8.959 0 01-4.5 1.207" />
                    </svg>
                </div>
                <input type="email" v-model="form.email"
                    class="w-full pl-11 pr-4 py-2.5 border border-gray-200 rounded-xl focus:outline-none focus:ring-2 focus:ring-primary-500 focus:border-transparent transition-colors text-sm"
                    placeholder="john@example.com" required />
            </div>
            <p v-if="form.errors.email" class="text-red-500 text-xs font-medium mt-1.5 flex items-center gap-1.5">
                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
                {{ form.errors.email }}
            </p>
        </div>

        <!-- ACTIONS -->
        <div class="flex items-center justify-between gap-3 pt-6 border-t border-gray-100">
            <p class="text-xs text-gray-400">
                Your name and email are visible to other users.
            </p>
            <button type="submit"
                class="inline-flex items-center justify-center gap-2 px-6 py-2.5 bg-gradient-to-r from-primary-600 to-primary-700 text-white rounded-xl hover:from-primary-700 hover:to-primary-800 transition-all shadow-lg shadow-primary-500/25 hover:shadow-primary-500/40 hover:-translate-y-0.5 font-semibold text-sm disabled:opacity-50 disabled:cursor-not-allowed disabled:hover:translate-y-0"
                :disabled="form.processing">
                <span v-if="form.processing" class="flex items-center gap-2">
                    <svg class="animate-spin h-4 w-4" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4">
                        </circle>
                        <path class="opacity-75" fill="currentColor"
                            d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z">
                        </path>
                    </svg>
                    Saving…
                </span>
                <span v-else class="flex items-center gap-2">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7" />
                    </svg>
                    Save Changes
                </span>
            </button>
        </div>
    </form>
</template>
<script setup>
    import { useForm, Link } from '@inertiajs/vue3';
    import { useToast } from '@/composables/useToast';

    const props = defineProps({
        user: Object,
        mustVerifyEmail: Boolean,
        verified: Boolean,
        status: String,
    });

    const { error } = useToast();

    // ✅ useForm — gives form.errors + form.processing automatically
    const form = useForm({
        name: props.user?.name || '',
        email: props.user?.email || '',
    });

    const submit = () => {
        form.put('/profile', {
            preserveScroll: true,
            onSuccess: () => {
                // ✅ No success toast — the controller flashes
                //    'Profile updated successfully.'
            },
            onError: (errors) => {
                // Client-side fallback. Inline errors render via form.errors.
                error('Update Failed ❌', Object.values(errors)[0] || 'Failed to update profile. Please try again.', { duration: 4000 });
            },
        });
    };
</script>