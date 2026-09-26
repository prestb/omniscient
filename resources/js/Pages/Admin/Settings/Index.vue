<!-- resources/js/Pages/Admin/Settings/Index.vue -->
<template>
    <AuthenticatedLayout>
        <!-- COMPACT HEADER -->
        <PageHeader color="gray" :breadcrumb="[
            { label: 'Admin', href: '/admin/dashboard' },
            { label: 'Settings' }
        ]">
            <template #icon>
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                    d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z" />
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                    d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
            </template>
            <template #title>Subscription settings</template>
            <template #subtitle>Configure expiration, grace period, and check frequency</template>
            <template #actions>
                <span
                    class="inline-flex items-center gap-2 px-3 py-2 bg-gray-50 dark:bg-gray-700 border border-gray-200 dark:border-gray-600 rounded-xl text-sm font-semibold text-gray-700 dark:text-gray-300">
                    <span class="w-1.5 h-1.5 rounded-full bg-emerald-400 animate-pulse"></span>
                    All settings active
                </span>
            </template>
        </PageHeader>

        <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 py-8 space-y-6">

            <!-- INFO CARDS -->
            <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                <div class="bg-white dark:bg-gray-800 rounded-2xl border border-gray-100 dark:border-gray-700 p-4 hover:shadow-md transition-shadow">
                    <div class="flex items-center gap-3">
                        <div class="w-10 h-10 rounded-xl bg-blue-50 dark:bg-blue-900/30 flex items-center justify-center flex-shrink-0">
                            <svg class="w-5 h-5 text-blue-600 dark:text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                        </div>
                        <div>
                            <p class="text-[10px] font-bold text-gray-400 dark:text-gray-500 uppercase tracking-widest">Grace period</p>
                            <p class="text-lg font-bold text-gray-900 dark:text-white tracking-tight mt-0.5">{{ form.grace_period_days
                                }} days
                            </p>
                        </div>
                    </div>
                </div>

                <div class="bg-white dark:bg-gray-800 rounded-2xl border border-gray-100 dark:border-gray-700 p-4 hover:shadow-md transition-shadow">
                    <div class="flex items-center gap-3">
                        <div class="w-10 h-10 rounded-xl bg-amber-50 dark:bg-amber-900/30 flex items-center justify-center flex-shrink-0">
                            <svg class="w-5 h-5 text-amber-600 dark:text-amber-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                        </div>
                        <div>
                            <p class="text-[10px] font-bold text-gray-400 dark:text-gray-500 uppercase tracking-widest">Expiring soon</p>
                            <p class="text-lg font-bold text-gray-900 dark:text-white tracking-tight mt-0.5">{{
                                form.expiring_soon_threshold }}
                                days</p>
                        </div>
                    </div>
                </div>

                <div class="bg-white dark:bg-gray-800 rounded-2xl border border-gray-100 dark:border-gray-700 p-4 hover:shadow-md transition-shadow">
                    <div class="flex items-center gap-3">
                        <div class="w-10 h-10 rounded-xl bg-purple-50 dark:bg-purple-900/30 flex items-center justify-center flex-shrink-0">
                            <svg class="w-5 h-5 text-purple-600 dark:text-purple-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                            </svg>
                        </div>
                        <div>
                            <p class="text-[10px] font-bold text-gray-400 dark:text-gray-500 uppercase tracking-widest">Check frequency</p>
                            <p class="text-lg font-bold text-gray-900 dark:text-white tracking-tight mt-0.5">{{ form.check_frequency }}
                                min</p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- FORM -->
            <div class="bg-white dark:bg-gray-800 rounded-2xl border border-gray-100 dark:border-gray-700 overflow-hidden">
                <div
                    class="px-6 py-4 border-b border-gray-100 dark:border-gray-700 bg-gradient-to-br from-gray-50 to-white dark:from-gray-800 dark:to-gray-800/50 flex items-center gap-3">
                    <div class="w-9 h-9 rounded-xl bg-primary-50 dark:bg-primary-900/30 flex items-center justify-center">
                        <svg class="w-4 h-4 text-primary-600 dark:text-primary-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z" />
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                        </svg>
                    </div>
                    <div>
                        <h3 class="text-sm font-bold text-gray-900 dark:text-white tracking-tight">Subscription configuration</h3>
                        <p class="text-xs text-gray-400 dark:text-gray-500">All fields are required</p>
                    </div>
                </div>

                <div class="p-6">
                    <form @submit.prevent="submit" class="space-y-6">

                        <!-- Grace Period -->
                        <div>
                            <label class="block text-[10px] font-bold text-gray-500 dark:text-gray-400 uppercase tracking-widest mb-1.5">
                                Grace period (days)
                            </label>
                            <p class="text-xs text-gray-500 dark:text-gray-400 mb-2">Number of days after expiration before subscription is
                                suspended</p>
                            <div class="flex items-center gap-3">
                                <input type="number" v-model="form.grace_period_days" min="1" max="30" required
                                    class="w-full px-4 py-2.5 border border-gray-200 dark:border-gray-600 rounded-xl bg-white dark:bg-gray-900 text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-primary-500 focus:border-transparent transition-colors text-sm" />
                                <span class="text-sm text-gray-500 dark:text-gray-400 font-medium min-w-[50px]">days</span>
                            </div>
                            <div class="mt-3 flex items-center gap-3">
                                <div class="flex-1 h-1.5 bg-gray-100 dark:bg-gray-700 rounded-full overflow-hidden">
                                    <div class="h-full bg-gradient-to-r from-blue-400 to-blue-500 rounded-full transition-all duration-500"
                                        :style="{ width: (form.grace_period_days / 30 * 100) + '%' }"></div>
                                </div>
                                <span class="text-xs text-gray-400 dark:text-gray-500 font-medium min-w-[40px]">{{ form.grace_period_days
                                    }}/30</span>
                            </div>
                            <p v-if="form.errors.grace_period_days" class="text-red-500 dark:text-red-400 text-xs font-medium mt-2">{{
                                form.errors.grace_period_days }}</p>
                            <div class="mt-3 flex flex-wrap items-center gap-3 text-xs text-gray-400 dark:text-gray-500">
                                <span class="flex items-center gap-1.5">
                                    <span class="w-2 h-2 rounded-full bg-red-500"></span> Suspension
                                </span>
                                <span class="flex items-center gap-1.5">
                                    <span class="w-2 h-2 rounded-full bg-amber-500"></span> Grace period
                                </span>
                                <span class="flex items-center gap-1.5">
                                    <span class="w-2 h-2 rounded-full bg-emerald-500"></span> Active
                                </span>
                            </div>
                        </div>

                        <!-- Expiring Soon Threshold -->
                        <div>
                            <label class="block text-[10px] font-bold text-gray-500 dark:text-gray-400 uppercase tracking-widest mb-1.5">
                                Expiring soon threshold (days)
                            </label>
                            <p class="text-xs text-gray-500 dark:text-gray-400 mb-2">Days before expiration when status changes to
                                "Expiring Soon"
                            </p>
                            <div class="flex items-center gap-3">
                                <input type="number" v-model="form.expiring_soon_threshold" min="7" max="90" required
                                    class="w-full px-4 py-2.5 border border-gray-200 dark:border-gray-600 rounded-xl bg-white dark:bg-gray-900 text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-primary-500 focus:border-transparent transition-colors text-sm" />
                                <span class="text-sm text-gray-500 dark:text-gray-400 font-medium min-w-[50px]">days</span>
                            </div>
                            <div class="mt-3 flex items-center gap-3">
                                <div class="flex-1 h-1.5 bg-gray-100 dark:bg-gray-700 rounded-full overflow-hidden">
                                    <div class="h-full bg-gradient-to-r from-amber-400 to-amber-500 rounded-full transition-all duration-500"
                                        :style="{ width: (form.expiring_soon_threshold / 90 * 100) + '%' }"></div>
                                </div>
                                <span class="text-xs text-gray-400 dark:text-gray-500 font-medium min-w-[40px]">{{
                                    form.expiring_soon_threshold
                                    }}/90</span>
                            </div>
                            <p v-if="form.errors.expiring_soon_threshold" class="text-red-500 dark:text-red-400 text-xs font-medium mt-2">
                                {{
                                    form.errors.expiring_soon_threshold }}</p>
                        </div>

                        <!-- Check Frequency -->
                        <div>
                            <label class="block text-[10px] font-bold text-gray-500 dark:text-gray-400 uppercase tracking-widest mb-1.5">
                                Check frequency (minutes)
                            </label>
                            <p class="text-xs text-gray-500 dark:text-gray-400 mb-2">How often the system checks for expiring subscriptions
                            </p>
                            <div class="flex items-center gap-3">
                                <input type="number" v-model="form.check_frequency" min="15" max="1440" required
                                    class="w-full px-4 py-2.5 border border-gray-200 dark:border-gray-600 rounded-xl bg-white dark:bg-gray-900 text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-primary-500 focus:border-transparent transition-colors text-sm" />
                                <span class="text-sm text-gray-500 dark:text-gray-400 font-medium min-w-[50px]">min</span>
                            </div>
                            <div class="mt-3 flex items-center gap-3">
                                <div class="flex-1 h-1.5 bg-gray-100 dark:bg-gray-700 rounded-full overflow-hidden">
                                    <div class="h-full bg-gradient-to-r from-purple-400 to-purple-500 rounded-full transition-all duration-500"
                                        :style="{ width: (form.check_frequency / 1440 * 100) + '%' }"></div>
                                </div>
                                <span class="text-xs text-gray-400 dark:text-gray-500 font-medium min-w-[40px]">{{ form.check_frequency
                                    }}/1440</span>
                            </div>
                            <p v-if="form.errors.check_frequency" class="text-red-500 dark:text-red-400 text-xs font-medium mt-2">{{
                                form.errors.check_frequency }}</p>
                            <div class="mt-2 flex flex-wrap items-center gap-2 text-xs text-gray-400 dark:text-gray-500">
                                <span>Recommended: 60 minutes</span>
                                <span>·</span>
                                <span>Min: 15 min</span>
                                <span>·</span>
                                <span>Max: 24 hours</span>
                            </div>
                        </div>

                        <!-- Submit -->
                        <div class="flex justify-end pt-6 border-t border-gray-100 dark:border-gray-700">
                            <button type="submit" :disabled="form.processing"
                                class="inline-flex items-center justify-center gap-2 px-8 py-2.5 bg-gradient-to-r from-primary-600 to-primary-700 text-white rounded-xl hover:from-primary-700 hover:to-primary-800 transition-all shadow-lg shadow-primary-500/25 hover:-translate-y-0.5 font-semibold text-sm disabled:opacity-50 disabled:cursor-not-allowed disabled:hover:translate-y-0">
                                <span v-if="form.processing" class="flex items-center gap-2">
                                    <svg class="animate-spin h-4 w-4" fill="none" viewBox="0 0 24 24">
                                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor"
                                            stroke-width="4"></circle>
                                        <path class="opacity-75" fill="currentColor"
                                            d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z">
                                        </path>
                                    </svg>
                                    Saving…
                                </span>
                                <span v-else class="flex items-center gap-2">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5"
                                            d="M5 13l4 4L19 7" />
                                    </svg>
                                    Save settings
                                </span>
                            </button>
                        </div>
                    </form>
                </div>
            </div>

            <!-- HELP -->
            <div class="bg-gradient-to-br from-blue-50 to-indigo-50 dark:from-blue-900/20 dark:to-indigo-900/10 border border-blue-200 dark:border-blue-800 rounded-2xl p-5">
                <div class="flex items-start gap-4">
                    <div class="w-11 h-11 rounded-xl bg-blue-100 dark:bg-blue-900/40 flex items-center justify-center flex-shrink-0">
                        <svg class="w-5 h-5 text-blue-600 dark:text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                    </div>
                    <div>
                        <p class="text-sm font-bold text-blue-900 dark:text-blue-300 tracking-tight">Understanding subscription settings
                        </p>
                        <ul class="text-xs text-blue-700 dark:text-blue-400 mt-2 space-y-1.5 leading-relaxed">
                            <li><strong>Grace period:</strong> Allows businesses to renew before suspension</li>
                            <li><strong>Expiring soon threshold:</strong> Triggers notification to business owners</li>
                            <li><strong>Check frequency:</strong> How often the system runs expiration checks</li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>


<script setup>
    import { useForm } from '@inertiajs/vue3';
    import { useToast } from '@/composables/useToast';
    import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
    import PageHeader from '@/Components/PageHeader.vue';

    const props = defineProps({
        settings: Object,
    });

    const { error } = useToast();

    // ✅ useForm — gives form.errors + form.processing automatically
    const form = useForm({
        grace_period_days: props.settings.grace_period_days || 7,
        expiring_soon_threshold: props.settings.expiring_soon_threshold || 30,
        check_frequency: props.settings.check_frequency || 60,
    });

    const submit = () => {
        form.put('/admin/settings', {
            preserveScroll: true,
            // ✅ No success toast — the controller flashes
            //    'Settings updated successfully.'
            onError: (errors) => {
                // Client-side fallback. Inline errors render via form.errors.
                const firstError = Object.values(errors)[0];
                error('Save Failed ❌', firstError || 'Failed to save settings. Please try again.', { duration: 5000 });
            },
        });
    };
</script>