<!-- resources/js/Pages/Auth/RegisterInvitation.vue -->
<template>
    <GuestLayout>
        <div class="max-w-md mx-auto">

            <!-- HEADER -->
            <div class="text-center mb-8">
                <div class="inline-flex items-center justify-center w-14 h-14 rounded-2xl bg-gradient-to-br from-primary-500 to-primary-700 shadow-lg shadow-primary-500/25 mb-4">
                    <svg class="w-7 h-7 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18 9v3m0 0v3m0-3h3m-3 0h-3m-2-5a4 4 0 11-8 0 4 4 0 018 0zM3 20a6 6 0 0112 0v1H3v-1z" />
                    </svg>
                </div>
                <h2 class="text-2xl font-bold text-gray-900 dark:text-white tracking-tight">Accept Invitation</h2>
                <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">
                    You've been invited to join Omniscient as a business owner.
                </p>
            </div>

            <!-- INVITATION DETAILS -->
            <div class="bg-white dark:bg-gray-800 rounded-2xl border border-gray-100 dark:border-gray-700 shadow-sm overflow-hidden mb-6">
                <div class="px-5 py-3 border-b border-gray-100 dark:border-gray-700 bg-gradient-to-br from-gray-50 to-white dark:from-gray-800 dark:to-gray-800/50 flex items-center gap-2">
                    <div class="w-7 h-7 rounded-lg bg-primary-50 dark:bg-primary-900/30 flex items-center justify-center">
                        <svg class="w-3.5 h-3.5 text-primary-600 dark:text-primary-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />
                        </svg>
                    </div>
                    <h3 class="text-xs font-bold text-gray-700 dark:text-gray-300 uppercase tracking-widest">Invitation Details</h3>
                </div>

                <div class="p-5 space-y-3">
                    <div class="flex items-start justify-between gap-3">
                        <span class="text-xs font-bold text-gray-400 dark:text-gray-500 uppercase tracking-widest">Invited by</span>
                        <span class="text-sm font-semibold text-gray-900 dark:text-white text-right">
                            {{ invitation.invited_by?.name || 'Admin' }}
                        </span>
                    </div>
                    <div class="flex items-start justify-between gap-3">
                        <span class="text-xs font-bold text-gray-400 dark:text-gray-500 uppercase tracking-widest">Business</span>
                        <span class="text-sm font-semibold text-gray-900 dark:text-white text-right">
                            {{ invitation.business_name || 'Not specified' }}
                        </span>
                    </div>
                    <div class="flex items-start justify-between gap-3">
                        <span class="text-xs font-bold text-gray-400 dark:text-gray-500 uppercase tracking-widest">Expires</span>
                        <span class="text-sm font-semibold text-gray-900 dark:text-white text-right">
                            {{ new Date(invitation.expires_at).toLocaleDateString() }}
                        </span>
                    </div>
                </div>
            </div>

            <!-- FORM CARD -->
            <div class="bg-white dark:bg-gray-800 rounded-2xl border border-gray-100 dark:border-gray-700 shadow-sm overflow-hidden">
                <div class="px-5 py-3 border-b border-gray-100 dark:border-gray-700 bg-gradient-to-br from-gray-50 to-white dark:from-gray-800 dark:to-gray-800/50 flex items-center gap-2">
                    <div class="w-7 h-7 rounded-lg bg-primary-50 dark:bg-primary-900/30 flex items-center justify-center">
                        <svg class="w-3.5 h-3.5 text-primary-600 dark:text-primary-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                        </svg>
                    </div>
                    <h3 class="text-xs font-bold text-gray-700 dark:text-gray-300 uppercase tracking-widest">Create Your Account</h3>
                </div>

                <form @submit.prevent="submit" class="p-5 space-y-4">

                    <!-- Full Name -->
                    <div>
                        <label for="name" class="block text-xs font-bold text-gray-500 dark:text-gray-400 uppercase tracking-widest mb-1.5">Full Name <span class="text-red-500">*</span></label>
                        <input id="name" type="text" v-model="form.name"
                               class="w-full px-4 py-2.5 border border-gray-200 dark:border-gray-600 rounded-xl bg-white dark:bg-gray-900 text-gray-900 dark:text-white placeholder-gray-400 dark:placeholder-gray-500 focus:outline-none focus:ring-2 focus:ring-primary-500 focus:border-transparent transition-colors text-sm"
                               required />
                        <p v-if="form.errors.name" class="text-xs text-red-500 dark:text-red-400 font-medium mt-1">{{ form.errors.name }}</p>
                    </div>

                    <!-- Email -->
                    <div>
                        <label for="email" class="block text-xs font-bold text-gray-500 dark:text-gray-400 uppercase tracking-widest mb-1.5">Email Address <span class="text-red-500">*</span></label>
                        <input id="email" type="email" v-model="form.email"
                               class="w-full px-4 py-2.5 border border-gray-200 dark:border-gray-600 rounded-xl bg-white dark:bg-gray-900 text-gray-900 dark:text-white placeholder-gray-400 dark:placeholder-gray-500 focus:outline-none focus:ring-2 focus:ring-primary-500 focus:border-transparent transition-colors text-sm"
                               required />
                        <p v-if="form.errors.email" class="text-xs text-red-500 dark:text-red-400 font-medium mt-1">{{ form.errors.email }}</p>
                    </div>

                    <!-- Phone -->
                    <div>
                        <label for="phone" class="block text-xs font-bold text-gray-500 dark:text-gray-400 uppercase tracking-widest mb-1.5">Phone Number</label>
                        <input id="phone" type="text" v-model="form.phone"
                               class="w-full px-4 py-2.5 border border-gray-200 dark:border-gray-600 rounded-xl bg-white dark:bg-gray-900 text-gray-900 dark:text-white placeholder-gray-400 dark:placeholder-gray-500 focus:outline-none focus:ring-2 focus:ring-primary-500 focus:border-transparent transition-colors text-sm" />
                        <p v-if="form.errors.phone" class="text-xs text-red-500 dark:text-red-400 font-medium mt-1">{{ form.errors.phone }}</p>
                    </div>

                    <!-- Password -->
                    <div>
                        <label for="password" class="block text-xs font-bold text-gray-500 dark:text-gray-400 uppercase tracking-widest mb-1.5">Password <span class="text-red-500">*</span></label>
                        <input id="password" type="password" v-model="form.password"
                               class="w-full px-4 py-2.5 border border-gray-200 dark:border-gray-600 rounded-xl bg-white dark:bg-gray-900 text-gray-900 dark:text-white placeholder-gray-400 dark:placeholder-gray-500 focus:outline-none focus:ring-2 focus:ring-primary-500 focus:border-transparent transition-colors text-sm"
                               required />
                        <p v-if="form.errors.password" class="text-xs text-red-500 dark:text-red-400 font-medium mt-1">{{ form.errors.password }}</p>
                    </div>

                    <!-- Confirm Password -->
                    <div>
                        <label for="password_confirmation" class="block text-xs font-bold text-gray-500 dark:text-gray-400 uppercase tracking-widest mb-1.5">Confirm Password <span class="text-red-500">*</span></label>
                        <input id="password_confirmation" type="password" v-model="form.password_confirmation"
                               class="w-full px-4 py-2.5 border border-gray-200 dark:border-gray-600 rounded-xl bg-white dark:bg-gray-900 text-gray-900 dark:text-white placeholder-gray-400 dark:placeholder-gray-500 focus:outline-none focus:ring-2 focus:ring-primary-500 focus:border-transparent transition-colors text-sm"
                               required />
                        <p v-if="form.errors.password_confirmation" class="text-xs text-red-500 dark:text-red-400 font-medium mt-1">{{ form.errors.password_confirmation }}</p>
                    </div>

                    <!-- Terms -->
                    <div class="pt-2">
                        <label class="flex items-start gap-2.5 cursor-pointer">
                            <input type="checkbox" v-model="form.terms" id="terms" required
                                   class="mt-0.5 rounded border-gray-300 dark:border-gray-600 text-primary-600 focus:ring-primary-500" />
                            <span class="text-sm text-gray-600 dark:text-gray-400 leading-relaxed">
                                I agree to the
                                <a href="#" class="text-primary-600 dark:text-primary-400 hover:text-primary-800 dark:hover:text-primary-300 font-medium">Terms of Service</a>
                                and
                                <a href="#" class="text-primary-600 dark:text-primary-400 hover:text-primary-800 dark:hover:text-primary-300 font-medium">Privacy Policy</a>
                            </span>
                        </label>
                        <p v-if="form.errors.terms" class="text-xs text-red-500 dark:text-red-400 font-medium mt-1">{{ form.errors.terms }}</p>
                    </div>

                    <!-- Submit -->
                    <div class="pt-2">
                        <button type="submit"
                                class="w-full inline-flex items-center justify-center gap-2 px-6 py-3 bg-gradient-to-r from-primary-600 to-primary-700 text-white rounded-xl font-semibold hover:from-primary-700 hover:to-primary-800 transition-all shadow-lg shadow-primary-500/25 hover:shadow-primary-500/40 hover:-translate-y-0.5 disabled:opacity-50 disabled:cursor-not-allowed disabled:hover:translate-y-0 text-sm"
                                :disabled="form.processing">
                            {{ form.processing ? 'Creating Account…' : 'Create Account' }}
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </GuestLayout>
</template>

<script setup>
import { useForm } from '@inertiajs/vue3';
import GuestLayout from '@/Layouts/GuestLayout.vue';

const props = defineProps({
    invitation: Object,
    token: String,
});

// ✅ useForm — gives form.errors + form.processing automatically
const form = useForm({
    name: props.invitation?.name || '',
    email: props.invitation?.email || '',
    phone: props.invitation?.phone || '',
    password: '',
    password_confirmation: '',
    terms: false,
    token: props.token,
});

const submit = () => {
    form.post('/invitations/register', {
        preserveScroll: true,
        // ✅ No onSuccess — the controller redirects to /owner/dashboard
        // ✅ No onFinish — form.processing handles the spinner state
    });
};
</script>