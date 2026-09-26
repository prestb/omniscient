<!-- resources/js/Pages/Owner/Notifications/Preferences.vue -->
<template>
    <AuthenticatedLayout>
        <PageHeader color="indigo" :breadcrumb="[
            { label: 'Dashboard', href: '/owner/dashboard' },
            { label: 'Notifications', href: '/owner/notifications' },
            { label: 'Preferences' }
        ]">
            <template #icon>
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                    d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z" />
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                    d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
            </template>
            <template #title>Notification preferences</template>
            <template #subtitle>Choose how you want to receive notifications</template>
            <template #actions>
                <a href="/owner/notifications"
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

            <!-- Info Card -->
            <div
                class="bg-gradient-to-r from-blue-50 to-blue-100/50 dark:from-blue-950/30 dark:to-blue-950/20 border border-blue-200 dark:border-blue-900 rounded-2xl p-4 flex items-start gap-3">
                <div
                    class="w-8 h-8 rounded-full bg-blue-100 dark:bg-blue-900/40 flex items-center justify-center flex-shrink-0">
                    <svg class="w-4 h-4 text-blue-600 dark:text-blue-400" fill="none" stroke="currentColor"
                        viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                </div>
                <div>
                    <p class="text-sm font-bold text-blue-800 dark:text-blue-300">Notification channels</p>
                    <p class="text-xs text-blue-600 dark:text-blue-400 mt-0.5">
                        Choose which channels you want to receive notifications on for each type.
                        SMS is currently not available — that toggle is disabled.
                    </p>
                </div>
            </div>

            <form @submit.prevent="submit" class="space-y-6">

                <!-- Grouped notification types -->
                <div v-for="group in groups" :key="group.label"
                    class="bg-white dark:bg-gray-800 rounded-2xl shadow-sm border border-gray-100 dark:border-gray-700 overflow-hidden">
                    <div
                        class="px-6 py-4 border-b border-gray-100 dark:border-gray-700 bg-gradient-to-r from-gray-50 to-white dark:from-gray-800 dark:to-gray-800/50 flex items-center gap-2">
                        <h3 class="text-sm font-bold text-gray-900 dark:text-white tracking-tight">
                            {{ group.label }}
                        </h3>
                        <span class="ml-auto text-[10px] font-bold uppercase tracking-widest text-gray-400">
                            {{ group.types.length }} {{ group.types.length === 1 ? 'type' : 'types' }}
                        </span>
                    </div>

                    <div class="p-6">
                        <div v-for="type in group.types" :key="type.key"
                            class="border-b border-gray-100 dark:border-gray-700 pb-5 last:border-0 last:pb-0 mb-5 last:mb-0">
                            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3">
                                <div class="min-w-0 flex-1">
                                    <div class="flex items-center gap-2">
                                        <span class="text-lg flex-shrink-0">{{ type.icon }}</span>
                                        <p class="text-sm font-bold text-gray-900 dark:text-white truncate">{{
                                            type.label }}</p>
                                    </div>
                                    <p class="text-xs text-gray-500 dark:text-gray-400 mt-0.5 leading-relaxed">
                                        {{ type.description }}
                                    </p>
                                </div>

                                <div class="flex items-center gap-4 flex-shrink-0">
                                    <!-- Email Toggle -->
                                    <label
                                        class="flex items-center gap-1.5 text-sm text-gray-600 dark:text-gray-400 cursor-pointer hover:text-gray-800 dark:hover:text-gray-200 transition-colors">
                                        <div class="relative">
                                            <input type="checkbox" :checked="getPreference(type.key, 'email')"
                                                @change="updatePreference(type.key, 'email', $event.target.checked)"
                                                class="peer sr-only" />
                                            <div
                                                class="block w-9 h-5 bg-gray-300 dark:bg-gray-600 rounded-full peer-checked:bg-primary-600 transition-colors">
                                            </div>
                                            <div
                                                class="dot absolute left-0.5 top-0.5 w-4 h-4 bg-white rounded-full transition-transform peer-checked:translate-x-4">
                                            </div>
                                        </div>
                                        <span class="text-xs font-medium">Email</span>
                                    </label>

                                    <!-- In-App Toggle -->
                                    <label
                                        class="flex items-center gap-1.5 text-sm text-gray-600 dark:text-gray-400 cursor-pointer hover:text-gray-800 dark:hover:text-gray-200 transition-colors">
                                        <div class="relative">
                                            <input type="checkbox" :checked="getPreference(type.key, 'in_app')"
                                                @change="updatePreference(type.key, 'in_app', $event.target.checked)"
                                                class="peer sr-only" />
                                            <div
                                                class="block w-9 h-5 bg-gray-300 dark:bg-gray-600 rounded-full peer-checked:bg-primary-600 transition-colors">
                                            </div>
                                            <div
                                                class="dot absolute left-0.5 top-0.5 w-4 h-4 bg-white rounded-full transition-transform peer-checked:translate-x-4">
                                            </div>
                                        </div>
                                        <span class="text-xs font-medium">In-App</span>
                                    </label>

                                    <!-- SMS Toggle (disabled) -->
                                    <label
                                        class="flex items-center gap-1.5 text-sm text-gray-400 dark:text-gray-600 cursor-not-allowed opacity-50"
                                        title="SMS notifications are not yet available">
                                        <div class="relative">
                                            <input type="checkbox" disabled class="peer sr-only" />
                                            <div class="block w-9 h-5 bg-gray-200 dark:bg-gray-700 rounded-full"></div>
                                            <div class="dot absolute left-0.5 top-0.5 w-4 h-4 bg-white rounded-full">
                                            </div>
                                        </div>
                                        <span class="text-xs font-medium">SMS</span>
                                    </label>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Save bar -->
                <div class="flex flex-col sm:flex-row justify-end gap-3 sticky bottom-4 z-10">
                    <div
                        class="flex flex-col sm:flex-row justify-end gap-3 p-3 bg-white/95 dark:bg-gray-800/95 backdrop-blur-sm rounded-2xl border border-gray-200 dark:border-gray-700 shadow-lg">
                        <a href="/owner/notifications"
                            class="inline-flex items-center justify-center px-6 py-2.5 border border-gray-300 dark:border-gray-600 text-gray-700 dark:text-gray-300 rounded-xl hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors font-semibold text-sm">
                            Cancel
                        </a>
                        <button type="submit" :disabled="processing"
                            class="inline-flex items-center justify-center px-6 py-2.5 bg-gradient-to-r from-primary-600 to-primary-700 text-white rounded-xl hover:from-primary-700 hover:to-primary-800 transition-all shadow-lg shadow-primary-500/25 font-semibold text-sm disabled:opacity-50 disabled:cursor-not-allowed">
                            <span v-if="processing" class="flex items-center gap-2">
                                <svg class="animate-spin h-4 w-4" fill="none" viewBox="0 0 24 24">
                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor"
                                        stroke-width="4">
                                    </circle>
                                    <path class="opacity-75" fill="currentColor"
                                        d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z">
                                    </path>
                                </svg>
                                Saving...
                            </span>
                            <span v-else class="flex items-center gap-2">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M5 13l4 4L19 7" />
                                </svg>
                                Save Preferences
                            </span>
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </AuthenticatedLayout>
</template>

<script setup>
    import { ref, reactive, onMounted } from 'vue';
    import { router } from '@inertiajs/vue3';
    import { useToast } from '@/composables/useToast';
    import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
    import PageHeader from '@/Components/PageHeader.vue';

    const props = defineProps({
        groups: {
            type: Array,
            default: () => [],
        },
    });

    const { error } = useToast();
    const processing = ref(false);

    // Local copy of preferences keyed by type → { email, in_app, sms }
    const localPrefs = reactive({});

    const initLocalPrefs = () => {
        props.groups.forEach((group) => {
            group.types.forEach((type) => {
                localPrefs[type.key] = {
                    email: type.preferences?.email ?? true,
                    in_app: type.preferences?.in_app ?? true,
                    sms: type.preferences?.sms ?? false,
                };
            });
        });
    };

    const getPreference = (key, field) => {
        if (!localPrefs[key]) {
            localPrefs[key] = { email: true, in_app: true, sms: false };
        }
        return !!localPrefs[key][field];
    };

    const updatePreference = (key, field, value) => {
        if (!localPrefs[key]) {
            localPrefs[key] = { email: true, in_app: true, sms: false };
        }
        localPrefs[key][field] = value;
    };

    const submit = () => {
        processing.value = true;

        const data = Object.keys(localPrefs).map((key) => ({
            type: key,
            email: !!localPrefs[key].email,
            in_app: !!localPrefs[key].in_app,
            sms: !!localPrefs[key].sms,
        }));

        router.post('/owner/notifications/preferences', { preferences: data }, {
            preserveScroll: true,
            onFinish: () => {
                processing.value = false;
            },
            // ✅ No success toast — the controller flashes
            //    'Notification preferences updated.' and redirects
            //    to /owner/notifications, where AuthenticatedLayout
            //    shows the toast once.
            onError: () => {
                // Client-side fallback for validation / network errors.
                error('Save Failed ❌', 'Failed to save preferences. Please try again.', { duration: 4000 });
            },
        });
    };

    onMounted(() => {
        initLocalPrefs();
    });
</script>

<style scoped>
    .peer:checked~.dot {
        transform: translateX(100%);
    }
</style>