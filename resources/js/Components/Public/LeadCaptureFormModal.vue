<!-- resources/js/Components/Public/LeadCaptureFormModal.vue -->
<template>
    <Teleport to="body">
        <Transition enter-active-class="transition duration-200 ease-out" enter-from-class="opacity-0"
            enter-to-class="opacity-100" leave-active-class="transition duration-150 ease-in"
            leave-from-class="opacity-100" leave-to-class="opacity-0">
            <div v-if="show"
                class="fixed inset-0 z-[150] flex items-end sm:items-center justify-center bg-black/70 backdrop-blur-sm sm:p-4"
                @click.self="close">
                <Transition enter-active-class="transition duration-300 ease-out"
                    enter-from-class="opacity-0 translate-y-full sm:translate-y-4 sm:scale-95"
                    enter-to-class="opacity-100 translate-y-0 sm:scale-100"
                    leave-active-class="transition duration-200 ease-in"
                    leave-from-class="opacity-100 translate-y-0 sm:scale-100"
                    leave-to-class="opacity-0 translate-y-full sm:translate-y-4 sm:scale-95" appear>
                    <div
                        class="bg-white dark:bg-gray-800 rounded-t-3xl sm:rounded-3xl shadow-2xl w-full sm:max-w-lg max-h-[92vh] sm:max-h-[90vh] flex flex-col overflow-hidden">

                        <!-- Header -->
                        <div
                            class="px-5 py-4 border-b border-gray-100 dark:border-gray-700 bg-gradient-to-br from-primary-50 to-purple-50 dark:from-primary-950/20 dark:to-purple-950/20 flex items-center justify-between gap-3 flex-shrink-0">
                            <div class="flex items-center gap-3 min-w-0">
                                <div
                                    class="w-10 h-10 rounded-xl bg-gradient-to-br from-primary-500 to-purple-600 flex items-center justify-center shadow-lg shadow-primary-500/25 flex-shrink-0">
                                    <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor"
                                        viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />
                                    </svg>
                                </div>
                                <div class="min-w-0">
                                    <h3
                                        class="text-base font-bold text-gray-900 dark:text-white tracking-tight truncate">
                                        Send a message
                                    </h3>
                                    <p class="text-xs text-gray-500 dark:text-gray-400 truncate">
                                        {{ businessName || "We'll get back to you soon" }}
                                    </p>
                                </div>
                            </div>
                            <button @click="close"
                                aria-label="Close contact form"
                                class="w-8 h-8 flex items-center justify-center rounded-lg text-gray-400 hover:text-gray-700 dark:hover:text-gray-200 hover:bg-gray-100 dark:hover:bg-gray-700 transition-colors flex-shrink-0">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M6 18L18 6M6 6l12 12" />
                                </svg>
                            </button>
                        </div>

                        <!-- Body -->
                        <div class="p-5 overflow-y-auto flex-1">
                            <LeadCaptureForm :business="business" @submitted="onLeadSubmitted" />
                        </div>
                    </div>
                </Transition>
            </div>
        </Transition>
    </Teleport>
</template>

<script setup>
    import { onUnmounted } from 'vue';
    import LeadCaptureForm from './LeadCaptureForm.vue';
    import { useToast } from '@/composables/useToast';

    const props = defineProps({
        show: {
            type: Boolean,
            default: false,
        },
        business: {
            type: Object,
            required: true,
        },
        businessName: {
            type: String,
            default: '',
        },
    });

    const emit = defineEmits(['update:show', 'submitted']);

    const { success } = useToast();

    let closeTimer = null;

    const close = () => {
        if (closeTimer) {
            clearTimeout(closeTimer);
            closeTimer = null;
        }
        emit('update:show', false);
    };

    const onLeadSubmitted = () => {
        // Notify the parent
        emit('submitted');

        // Toast for persistent confirmation
        success(
            'Message sent! 📩',
            "The business will get back to you soon.",
            { duration: 3000 }
        );

        // Auto-close after the success banner has had time to be read
        closeTimer = setTimeout(() => {
            emit('update:show', false);
            closeTimer = null;
        }, 0);
    };

    onUnmounted(() => {
        if (closeTimer) {
            clearTimeout(closeTimer);
            closeTimer = null;
        }
    });
</script>