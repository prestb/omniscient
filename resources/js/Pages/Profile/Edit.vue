<!-- resources/js/Pages/Profile/Edit.vue -->
<template>
    <AuthenticatedLayout>
        <template #header>
            <Breadcrumb :items="[
                { label: 'Dashboard', href: '/owner/dashboard' },
                { label: 'Profile' }
            ]" />
        </template>

        <!-- HERO STRIP -->
        <div class="relative overflow-hidden bg-gradient-to-br from-primary-700 via-primary-600 to-primary-800 text-white">
            <div class="absolute inset-0 opacity-[0.06]"
                 style="background-image: radial-gradient(circle, white 1px, transparent 1px); background-size: 22px 22px;"></div>
            <div class="absolute -top-16 -right-16 w-72 h-72 bg-primary-400/30 rounded-full blur-3xl"></div>

            <div class="relative max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 py-10">
                <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-6">
                    <div>
                        <span class="inline-flex items-center gap-2 px-3 py-1 rounded-full bg-white/10 border border-white/20 text-xs font-medium tracking-wide uppercase text-primary-100 mb-3 backdrop-blur-sm">
                            <span class="w-1.5 h-1.5 rounded-full bg-emerald-400 animate-pulse"></span>
                            Profile
                        </span>
                        <h1 class="text-3xl md:text-4xl font-bold tracking-tight leading-tight">
                            Profile settings
                        </h1>
                        <p class="text-primary-100 mt-2">
                            Manage your account settings and preferences
                        </p>
                    </div>

                    <div class="flex items-center gap-2">
                        <span class="inline-flex items-center gap-1.5 px-3 py-1.5 text-xs font-bold uppercase tracking-wide rounded-full border backdrop-blur-sm"
                              :class="auth.user?.email_verified_at
                                  ? 'bg-emerald-500/20 border-emerald-400/40 text-emerald-100'
                                  : 'bg-amber-500/20 border-amber-400/40 text-amber-100'">
                            <span class="w-1.5 h-1.5 rounded-full animate-pulse"
                                  :class="auth.user?.email_verified_at ? 'bg-emerald-300' : 'bg-amber-300'"></span>
                            {{ auth.user?.email_verified_at ? 'Verified' : 'Unverified' }}
                        </span>
                    </div>
                </div>
            </div>
        </div>

        <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 py-8 space-y-6">

            <!-- USER SUMMARY -->
            <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6">
                <div class="flex flex-col sm:flex-row items-start sm:items-center gap-5">
                    <div class="w-16 h-16 rounded-2xl bg-gradient-to-br from-primary-500 to-primary-700 flex items-center justify-center text-white text-2xl font-bold flex-shrink-0 shadow-lg">
                        {{ getInitials(auth.user?.name) }}
                    </div>
                    <div class="flex-1 min-w-0">
                        <h3 class="text-xl font-bold text-gray-900 tracking-tight truncate">{{ auth.user?.name }}</h3>
                        <p class="text-sm text-gray-500 flex items-center gap-1.5 mt-1">
                            <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 12a4 4 0 10-8 0 4 4 0 008 0zm0 0v1.5a2.5 2.5 0 005 0V12a9 9 0 10-9 9m4.5-1.206a8.959 8.959 0 01-4.5 1.207" />
                            </svg>
                            <span class="truncate">{{ auth.user?.email }}</span>
                        </p>
                        <div class="flex items-center gap-3 mt-2 flex-wrap">
                            <span class="inline-flex items-center gap-1 text-[10px] font-bold uppercase tracking-wide text-gray-600 bg-gray-100 px-2 py-0.5 rounded-full">
                                <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z" />
                                </svg>
                                {{ auth.user?.role?.replace('_', ' ') || 'User' }}
                            </span>
                            <span class="text-xs text-gray-400">ID #{{ auth.user?.id }}</span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- PROFILE INFORMATION -->
            <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
                <div class="px-6 py-4 border-b border-gray-100 bg-gradient-to-br from-gray-50 to-white flex items-center gap-3">
                    <div class="w-9 h-9 rounded-xl bg-primary-50 flex items-center justify-center">
                        <svg class="w-4 h-4 text-primary-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                        </svg>
                    </div>
                    <div>
                        <h3 class="text-sm font-bold text-gray-900 tracking-tight">Profile information</h3>
                        <p class="text-xs text-gray-400">Update your personal details</p>
                    </div>
                </div>
                <div class="p-6">
                    <UpdateProfileInformationForm
                        :user="auth.user"
                        :must-verify-email="mustVerifyEmail"
                        :verified="auth.user?.email_verified_at !== null"
                    />
                </div>
            </div>

            <!-- UPDATE PASSWORD -->
            <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
                <div class="px-6 py-4 border-b border-gray-100 bg-gradient-to-br from-gray-50 to-white flex items-center gap-3">
                    <div class="w-9 h-9 rounded-xl bg-amber-50 flex items-center justify-center">
                        <svg class="w-4 h-4 text-amber-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" />
                        </svg>
                    </div>
                    <div>
                        <h3 class="text-sm font-bold text-gray-900 tracking-tight">Update password</h3>
                        <p class="text-xs text-gray-400">Change your password regularly to stay secure</p>
                    </div>
                </div>
                <div class="p-6">
                    <UpdatePasswordForm />
                </div>
            </div>

            <!-- DELETE ACCOUNT -->
            <div class="bg-gradient-to-br from-red-50 to-red-50/50 border border-red-200 rounded-2xl overflow-hidden">
                <div class="px-6 py-4 border-b border-red-200 bg-red-50/50 flex items-center gap-3">
                    <div class="w-9 h-9 rounded-xl bg-red-100 flex items-center justify-center">
                        <svg class="w-4 h-4 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                        </svg>
                    </div>
                    <div>
                        <h3 class="text-sm font-bold text-red-900 tracking-tight">Delete account</h3>
                        <p class="text-xs text-red-500">Permanently delete your account and all associated data</p>
                    </div>
                </div>
                <div class="p-6">
                    <DeleteUserForm />
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>

<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import Breadcrumb from '@/Components/Breadcrumb.vue';
import UpdateProfileInformationForm from './Partials/UpdateProfileInformationForm.vue';
import UpdatePasswordForm from './Partials/UpdatePasswordForm.vue';
import DeleteUserForm from './Partials/DeleteUserForm.vue';

const props = defineProps({
    auth: {
        type: Object,
        required: true,
    },
    mustVerifyEmail: {
        type: Boolean,
        default: false,
    },
});

const getInitials = (name) => {
    if (!name) return '?';
    return name.split(' ').map(n => n[0]).join('').toUpperCase().slice(0, 2);
};
</script>