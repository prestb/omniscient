<!-- resources/js/Pages/Admin/Invitations/Create.vue -->
<template>
    <AuthenticatedLayout>

        <Head title="Send Invitation" />

        <!-- COMPACT HEADER -->
        <PageHeader color="amber" :breadcrumb="[
            { label: 'Admin', href: '/admin/dashboard' },
            { label: 'Invitations', href: '/admin/invitations' },
            { label: 'Send' }
        ]">
            <template #icon>
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                    d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />
            </template>
            <template #title>Send Invitation</template>
            <template #subtitle>Invite someone to join as a business owner</template>
            <template #actions>
                <a href="/admin/invitations"
                    class="inline-flex items-center gap-2 px-4 py-2 border border-gray-300 dark:border-gray-600 text-gray-700 dark:text-gray-300 rounded-xl hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors text-sm font-semibold">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M10 19l-7-7m0 0l7-7m-7 7h18" />
                    </svg>
                    Back
                </a>
            </template>
        </PageHeader>


        <div class="max-w-3xl mx-auto px-4 sm:px-6 lg:px-8 py-8 space-y-6">

            <!-- INFO CARD -->
            <div
                class="bg-gradient-to-r from-blue-50 to-blue-100/50 dark:from-blue-900/20 dark:to-blue-900/10 border border-blue-200 dark:border-blue-900/40 rounded-2xl p-4 flex items-start gap-3">
                <div
                    class="w-9 h-9 rounded-xl bg-blue-100 dark:bg-blue-900/40 flex items-center justify-center flex-shrink-0">
                    <svg class="w-4 h-4 text-blue-600 dark:text-blue-400" fill="none" stroke="currentColor"
                        viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                </div>
                <div>
                    <p class="text-sm font-bold text-blue-800 dark:text-blue-300">Invitation information</p>
                    <p class="text-xs text-blue-600 dark:text-blue-400 mt-0.5 leading-relaxed">
                        The recipient will receive an email with a link to register as a business owner. They will have
                        7 days
                        to accept the invitation (default).
                    </p>
                </div>
            </div>

            <!-- FORM CARD -->
            <div
                class="bg-white dark:bg-gray-800 rounded-2xl border border-gray-100 dark:border-gray-700 overflow-hidden">
                <div
                    class="px-6 py-4 border-b border-gray-100 dark:border-gray-700 bg-gradient-to-br from-gray-50 to-white dark:from-gray-800 dark:to-gray-800/50 flex items-center gap-3">
                    <div
                        class="w-9 h-9 rounded-xl bg-primary-50 dark:bg-primary-900/30 flex items-center justify-center">
                        <svg class="w-4 h-4 text-primary-600 dark:text-primary-400" fill="none" stroke="currentColor"
                            viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />
                        </svg>
                    </div>
                    <div>
                        <h3 class="text-sm font-bold text-gray-900 dark:text-white tracking-tight">Invitation details
                        </h3>
                        <p class="text-xs text-gray-400">Fill in the recipient's information</p>
                    </div>
                </div>

                <div class="p-6">
                    <form @submit.prevent="submit" class="space-y-5">

                        <!-- Full Name -->
                        <div>
                            <label for="name"
                                class="block text-[10px] font-bold text-gray-500 dark:text-gray-400 uppercase tracking-widest mb-2">
                                Full Name <span class="text-red-500">*</span>
                            </label>
                            <div class="relative">
                                <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none">
                                    <svg class="h-4 w-4 text-gray-400" fill="none" stroke="currentColor"
                                        viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                                    </svg>
                                </div>
                                <input id="name" type="text" v-model="form.name" required placeholder="John Doe"
                                    class="w-full pl-10 pr-4 py-2.5 border border-gray-200 dark:border-gray-600 rounded-xl bg-white dark:bg-gray-900 text-gray-900 dark:text-white placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-primary-500 focus:border-transparent transition-colors text-sm" />
                            </div>
                            <p v-if="form.errors.name" class="text-xs text-red-500 font-medium mt-1">{{ form.errors.name
                                }}</p>

                        </div>

                        <!-- Email -->
                        <div>
                            <label for="email"
                                class="block text-[10px] font-bold text-gray-500 dark:text-gray-400 uppercase tracking-widest mb-2">
                                Email Address <span class="text-red-500">*</span>
                            </label>
                            <div class="relative">
                                <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none">
                                    <svg class="h-4 w-4 text-gray-400" fill="none" stroke="currentColor"
                                        viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />
                                    </svg>
                                </div>
                                <input id="email" type="email" v-model="form.email" required
                                    placeholder="john@example.com"
                                    class="w-full pl-10 pr-4 py-2.5 border border-gray-200 dark:border-gray-600 rounded-xl bg-white dark:bg-gray-900 text-gray-900 dark:text-white placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-primary-500 focus:border-transparent transition-colors text-sm" />
                            </div>
                            <p v-if="form.errors.email" class="text-xs text-red-500 font-medium mt-1">{{
                                form.errors.email }}
                            </p>

                        </div>

                        <!-- Phone -->
                        <div>
                            <label for="phone"
                                class="block text-[10px] font-bold text-gray-500 dark:text-gray-400 uppercase tracking-widest mb-2">
                                Phone Number
                            </label>
                            <div class="relative">
                                <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none">
                                    <svg class="h-4 w-4 text-gray-400" fill="none" stroke="currentColor"
                                        viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z" />
                                    </svg>
                                </div>
                                <input id="phone" type="text" v-model="form.phone" placeholder="+237 699 123 456"
                                    class="w-full pl-10 pr-4 py-2.5 border border-gray-200 dark:border-gray-600 rounded-xl bg-white dark:bg-gray-900 text-gray-900 dark:text-white placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-primary-500 focus:border-transparent transition-colors text-sm" />
                            </div>
                            <p v-if="form.errors.phone" class="text-xs text-red-500 font-medium mt-1">{{
                                form.errors.phone }}
                            </p>

                        </div>

                        <!-- Business Name -->
                        <div>
                            <label for="business_name"
                                class="block text-[10px] font-bold text-gray-500 dark:text-gray-400 uppercase tracking-widest mb-2">
                                Business Name <span class="text-gray-400 normal-case text-[10px]">(optional)</span>
                            </label>
                            <div class="relative">
                                <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none">
                                    <svg class="h-4 w-4 text-gray-400" fill="none" stroke="currentColor"
                                        viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" />
                                    </svg>
                                </div>
                                <input id="business_name" type="text" v-model="form.business_name"
                                    placeholder="ABC Pharmacy"
                                    class="w-full pl-10 pr-4 py-2.5 border border-gray-200 dark:border-gray-600 rounded-xl bg-white dark:bg-gray-900 text-gray-900 dark:text-white placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-primary-500 focus:border-transparent transition-colors text-sm" />
                            </div>
                            <p class="text-xs text-gray-400 mt-1.5">Helps us identify the business during review</p>
                            <p v-if="form.errors.business_name" class="text-xs text-red-500 font-medium mt-1">{{
                                form.errors.business_name }}</p>

                        </div>

                        <!-- Notes -->
                        <div>
                            <label for="notes"
                                class="block text-[10px] font-bold text-gray-500 dark:text-gray-400 uppercase tracking-widest mb-2">
                                Notes <span class="text-gray-400 normal-case text-[10px]">(optional)</span>
                            </label>
                            <div class="relative">
                                <div class="absolute top-3 left-3.5 flex items-start pointer-events-none">
                                    <svg class="h-4 w-4 text-gray-400" fill="none" stroke="currentColor"
                                        viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M4 6h16M4 12h16M4 18h7" />
                                    </svg>
                                </div>
                                <textarea id="notes" v-model="form.notes" rows="3"
                                    placeholder="Any additional notes or instructions…"
                                    class="w-full pl-10 pr-4 py-2.5 border border-gray-200 dark:border-gray-600 rounded-xl bg-white dark:bg-gray-900 text-gray-900 dark:text-white placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-primary-500 focus:border-transparent transition-colors resize-none text-sm"></textarea>
                            </div>
                            <p v-if="form.errors.notes" class="text-xs text-red-500 font-medium mt-1">{{
                                form.errors.notes }}
                            </p>

                        </div>

                        <!-- Expiration -->
                        <div>
                            <label for="expires_in_days"
                                class="block text-[10px] font-bold text-gray-500 dark:text-gray-400 uppercase tracking-widest mb-2">
                                Expires In <span class="text-red-500">*</span>
                            </label>
                            <div class="relative">
                                <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none">
                                    <svg class="h-4 w-4 text-gray-400" fill="none" stroke="currentColor"
                                        viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                                    </svg>
                                </div>
                                <select id="expires_in_days" v-model="form.expires_in_days"
                                    class="w-full pl-10 pr-10 py-2.5 border border-gray-200 dark:border-gray-600 rounded-xl bg-white dark:bg-gray-900 text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-primary-500 focus:border-transparent transition-colors appearance-none text-sm">
                                    <option value="1">1 Day</option>
                                    <option value="3">3 Days</option>
                                    <option value="7">7 Days</option>
                                    <option value="14">14 Days</option>
                                    <option value="30">30 Days</option>
                                </select>
                                <div class="absolute inset-y-0 right-0 pr-3.5 flex items-center pointer-events-none">
                                    <svg class="h-4 w-4 text-gray-400" fill="none" stroke="currentColor"
                                        viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M19 9l-7 7-7-7" />
                                    </svg>
                                </div>
                            </div>
                            <p v-if="form.errors.expires_in_days" class="text-xs text-red-500 font-medium mt-1">{{
                                form.errors.expires_in_days }}</p>

                        </div>

                        <!-- Send Email Toggle -->
                        <div class="border-t border-gray-100 dark:border-gray-700 pt-5">
                            <label for="send_email"
                                class="flex items-start gap-3 p-4 rounded-xl border-2 transition-all cursor-pointer"
                                :class="form.send_email
                                    ? 'border-primary-500 bg-primary-50/50 dark:bg-primary-900/20'
                                    : 'border-gray-200 dark:border-gray-700 hover:border-gray-300 dark:hover:border-gray-600'">
                                <input type="checkbox" v-model="form.send_email" id="send_email"
                                    class="mt-0.5 rounded border-gray-300 text-primary-600 focus:ring-primary-500" />
                                <div class="flex-1">
                                    <span class="text-sm font-bold text-gray-900 dark:text-white">Send invitation email
                                        immediately</span>
                                    <p class="text-xs text-gray-500 dark:text-gray-400 mt-0.5">If disabled, the
                                        invitation is
                                        created but no email is sent</p>
                                </div>
                            </label>
                        </div>

                        <!-- Actions -->
                        <div
                            class="flex flex-col-reverse sm:flex-row justify-end gap-3 pt-6 border-t border-gray-100 dark:border-gray-700">
                            <a href="/admin/invitations"
                                class="inline-flex items-center justify-center px-6 py-2.5 border border-gray-300 dark:border-gray-600 text-gray-700 dark:text-gray-300 rounded-xl hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors font-semibold text-sm">
                                Cancel
                            </a>
                            <button type="submit" :disabled="form.processing"
                                class="inline-flex items-center justify-center gap-2 px-6 py-2.5 bg-gradient-to-r from-primary-600 to-primary-700 text-white rounded-xl hover:from-primary-700 hover:to-primary-800 transition-all shadow-lg shadow-primary-500/25 hover:-translate-y-0.5 font-semibold text-sm disabled:opacity-50 disabled:cursor-not-allowed disabled:hover:translate-y-0">
                                <span v-if="form.processing" class="inline-flex items-center gap-2">
                                    <svg class="animate-spin h-4 w-4" fill="none" viewBox="0 0 24 24">
                                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor"
                                            stroke-width="4"></circle>
                                        <path class="opacity-75" fill="currentColor"
                                            d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z">
                                        </path>
                                    </svg>
                                    Sending…
                                </span>
                                <span v-else class="inline-flex items-center gap-2">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />
                                    </svg>
                                    Send Invitation
                                </span>
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>

<script setup>
    import { ref, reactive } from 'vue';
    import { Head } from '@inertiajs/vue3';
    import { useForm } from '@inertiajs/vue3';
    import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
    import PageHeader from '@/Components/PageHeader.vue';

    const props = defineProps({
        errors: Object,
    });

    const form = useForm({
        name: '',
        email: '',
        phone: '',
        business_name: '',
        notes: '',
        expires_in_days: 7,
        send_email: true,
    });

    const submit = () => {
        form.post('/admin/invitations', {
            preserveScroll: true,
            // ✅ No onSuccess toast — the controller flashes
            //    'Invitation created and sent successfully!' or
            //    'Invitation created. Email not sent.'
            //    and redirects to /admin/invitations where the layout
            //    shows the toast once.
            //
            // ✅ No router.visit() — the server redirect already lands
            //    the user on /admin/invitations.
            //
            // Errors are handled automatically — useForm populates
            // form.errors and the template already renders them.
        });
    };
</script>