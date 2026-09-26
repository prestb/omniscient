<!-- resources/js/Components/Admin/RejectBusinessModal.vue -->
<template>
    <Teleport to="body">
        <Transition enter-active-class="transition duration-200 ease-out" enter-from-class="opacity-0"
            enter-to-class="opacity-100" leave-active-class="transition duration-150 ease-in"
            leave-from-class="opacity-100" leave-to-class="opacity-0">
            <div v-if="isOpen"
                class="fixed inset-0 z-[100] flex items-center justify-center p-4 bg-black/60 backdrop-blur-sm"
                @click.self="close">
                <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-2xl max-w-md w-full overflow-hidden">
                    <!-- Header -->
                    <div class="p-6 text-white bg-gradient-to-r from-rose-500 to-red-600">
                        <div class="flex items-center gap-3">
                            <div class="w-12 h-12 rounded-xl bg-white/20 flex items-center justify-center">
                                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M6 18L18 6M6 6l12 12" />
                                </svg>
                            </div>
                            <div>
                                <h3 class="text-lg font-bold">Reject Business</h3>
                                <p class="text-sm text-white/80">The owner will be notified</p>
                            </div>
                        </div>
                    </div>

                    <!-- Content -->
                    <div class="p-6 space-y-4">
                        <p class="text-sm text-gray-700 dark:text-gray-300">
                            Reject
                            <strong class="text-gray-900 dark:text-white">{{ business?.name }}</strong>?
                            The owner will receive an email notification with your reason (if provided).
                        </p>

                        <!-- Reason input -->
                        <div>
                            <label
                                class="block text-xs font-bold text-gray-500 dark:text-gray-400 uppercase tracking-widest mb-2">
                                Reason <span class="text-gray-400 normal-case text-[10px] font-normal">(optional, max
                                    500 chars)</span>
                            </label>
                            <textarea v-model="reason" rows="4" maxlength="500"
                                placeholder="e.g., Missing business license, unclear service description…"
                                class="w-full px-4 py-3 border border-gray-200 dark:border-gray-600 rounded-xl bg-white dark:bg-gray-900 text-gray-900 dark:text-white placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-rose-500 focus:border-transparent transition-colors resize-none text-sm"></textarea>
                            <div class="flex justify-between mt-1.5">
                                <p class="text-[11px] text-gray-400">Provide actionable feedback for the owner</p>
                                <span class="text-[11px] text-gray-400">{{ reason.length }} / 500</span>
                            </div>
                        </div>
                    </div>

                    <!-- Actions -->
                    <div class="p-4 bg-gray-50 dark:bg-gray-900/50 flex gap-3">
                        <button type="button" @click="close" :disabled="processing"
                            class="flex-1 px-4 py-2.5 border border-gray-300 dark:border-gray-600 text-gray-700 dark:text-gray-300 rounded-xl hover:bg-gray-100 dark:hover:bg-gray-700 font-medium disabled:opacity-50 transition-colors">
                            Cancel
                        </button>
                        <button type="button" @click="confirm" :disabled="processing"
                            class="flex-1 px-4 py-2.5 rounded-xl font-semibold text-white transition-all disabled:opacity-50 bg-gradient-to-r from-rose-500 to-red-600 hover:from-rose-600 hover:to-red-700">
                            {{ processing ? 'Rejecting…' : 'Reject Business' }}
                        </button>
                    </div>
                </div>
            </div>
        </Transition>
    </Teleport>
</template>

<script setup>
    import { ref, watch } from 'vue';

    const props = defineProps({
        isOpen: { type: Boolean, default: false },
        business: { type: Object, required: true },
    });

    const emit = defineEmits(['close', 'confirm']);

    const reason = ref('');
    const processing = ref(false);

    // Reset state every time the modal opens
    watch(() => props.isOpen, (open) => {
        if (open) {
            reason.value = '';
            processing.value = false;
        }
    });

    const close = () => {
        if (processing.value) return;
        emit('close');
    };

    const confirm = () => {
        if (processing.value) return;
        processing.value = true;
        emit('confirm', { reason: reason.value.trim() || null });
    };
</script>