<!-- resources/js/Pages/Profile/Partials/DeleteUserForm.vue -->
<template>
    <div class="space-y-4">

        <!-- WARNING -->
        <div
            class="bg-gradient-to-br from-red-50 to-red-100/50 border border-red-200 rounded-2xl p-5 flex items-start gap-4">
            <div class="w-11 h-11 rounded-xl bg-red-100 flex items-center justify-center flex-shrink-0">
                <svg class="w-5 h-5 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                </svg>
            </div>
            <div>
                <p class="text-sm font-bold text-red-900 tracking-tight">This action is permanent</p>
                <p class="text-sm text-red-700 mt-1 leading-relaxed">
                    Once your account is deleted, all of its resources and data will be permanently deleted.
                    Please be certain before proceeding.
                </p>
            </div>
        </div>

        <!-- TRIGGER -->
        <button @click="confirmUserDeletion"
            class="inline-flex items-center gap-2 px-6 py-3 bg-gradient-to-r from-red-500 to-red-600 text-white rounded-xl font-semibold hover:from-red-600 hover:to-red-700 transition-all shadow-lg shadow-red-500/25 hover:shadow-red-500/40 hover:-translate-y-0.5 text-sm">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                    d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
            </svg>
            Delete Account
        </button>

        <!-- CONFIRMATION MODAL -->
        <Transition enter-active-class="transition duration-200 ease-out" enter-from-class="opacity-0"
            enter-to-class="opacity-100" leave-active-class="transition duration-150 ease-in"
            leave-from-class="opacity-100" leave-to-class="opacity-0">
            <div v-if="confirmingUserDeletion" class="fixed inset-0 z-50 overflow-y-auto">
                <!-- Backdrop -->
                <div class="fixed inset-0 bg-gray-900/60 backdrop-blur-sm transition-opacity" @click="closeModal"></div>

                <div class="flex min-h-full items-center justify-center p-4">
                    <Transition enter-active-class="transition duration-200 ease-out"
                        enter-from-class="opacity-0 translate-y-4 scale-95"
                        enter-to-class="opacity-100 translate-y-0 scale-100"
                        leave-active-class="transition duration-150 ease-in"
                        leave-from-class="opacity-100 translate-y-0 scale-100"
                        leave-to-class="opacity-0 translate-y-4 scale-95">
                        <div
                            class="relative w-full max-w-lg overflow-hidden rounded-2xl bg-white dark:bg-gray-800 shadow-2xl">

                            <!-- Header -->
                            <div class="px-6 pt-6 pb-4 flex items-start gap-4">
                                <div
                                    class="w-11 h-11 rounded-2xl bg-red-100 flex items-center justify-center flex-shrink-0">
                                    <svg class="w-5 h-5 text-red-600" fill="none" stroke="currentColor"
                                        viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                                    </svg>
                                </div>
                                <div class="flex-1 min-w-0">
                                    <h3 class="text-lg font-bold text-gray-900 dark:text-white tracking-tight">
                                        Delete account?
                                    </h3>
                                    <p class="text-sm text-gray-500 dark:text-gray-400 mt-1 leading-relaxed">
                                        This cannot be undone. Enter your password to confirm.
                                    </p>
                                </div>
                            </div>

                            <!-- Password -->
                            <div class="px-6 pb-4">
                                <label
                                    class="block text-[10px] font-bold text-gray-500 dark:text-gray-400 uppercase tracking-widest mb-2">
                                    Password
                                </label>
                                <div class="relative">
                                    <div class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none">
                                        <svg class="h-4 w-4 text-gray-400" fill="none" stroke="currentColor"
                                            viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" />
                                        </svg>
                                    </div>
                                    <input type="password" v-model="form.password"
                                        class="w-full pl-11 pr-4 py-2.5 border border-gray-200 dark:border-gray-600 rounded-xl bg-white dark:bg-gray-900 text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-red-500 focus:border-transparent transition-colors text-sm"
                                        placeholder="Enter your password to confirm" @keyup.enter="deleteUser" />
                                </div>
                                <p v-if="form.errors.password"
                                    class="text-red-500 text-xs font-medium mt-1.5 flex items-center gap-1.5">
                                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                    </svg>
                                    {{ form.errors.password }}
                                </p>
                            </div>

                            <!-- Actions -->
                            <div
                                class="px-6 py-4 bg-gray-50 dark:bg-gray-900/50 flex flex-col-reverse sm:flex-row sm:justify-end gap-2">
                                <button @click="closeModal" type="button" :disabled="form.processing"
                                    class="w-full sm:w-auto px-5 py-2.5 border border-gray-300 dark:border-gray-600 text-gray-700 dark:text-gray-300 rounded-xl hover:bg-white dark:hover:bg-gray-700 transition-colors font-semibold text-sm disabled:opacity-50">
                                    Cancel
                                </button>
                                <button @click="deleteUser" type="button" :disabled="form.processing"
                                    class="w-full sm:w-auto inline-flex items-center justify-center gap-2 px-5 py-2.5 bg-gradient-to-r from-red-500 to-red-600 text-white rounded-xl hover:from-red-600 hover:to-red-700 transition-all shadow-lg shadow-red-500/25 hover:shadow-red-500/40 font-semibold text-sm disabled:opacity-50 disabled:cursor-not-allowed">
                                    <span v-if="form.processing" class="flex items-center gap-2">
                                        <svg class="animate-spin h-4 w-4" fill="none" viewBox="0 0 24 24">
                                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor"
                                                stroke-width="4"></circle>
                                            <path class="opacity-75" fill="currentColor"
                                                d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z">
                                            </path>
                                        </svg>
                                        Deleting…
                                    </span>
                                    <span v-else class="flex items-center gap-2">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                        </svg>
                                        Delete Account
                                    </span>
                                </button>
                            </div>
                        </div>
                    </Transition>
                </div>
            </div>
        </Transition>
    </div>
</template>

<script setup>
    import { ref } from 'vue';
    import { useForm } from '@inertiajs/vue3';

    const confirmingUserDeletion = ref(false);
    const form = useForm({
        password: '',
    });

    const confirmUserDeletion = () => {
        form.password = '';
        confirmingUserDeletion.value = true;
    };

    const closeModal = () => {
        confirmingUserDeletion.value = false;
        form.reset();
        form.clearErrors();
    };

    const deleteUser = () => {
        form.delete('/profile', {
            preserveScroll: true,
            onSuccess: () => {
                closeModal();
            },
            onError: () => {
                form.reset('password');
            },
            onFinish: () => {
                form.reset('password');
            },
        });
    };
</script>