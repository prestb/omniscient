<template>
    <Teleport to="body">
        <Transition
            enter-active-class="transition duration-200 ease-out"
            enter-from-class="opacity-0"
            enter-to-class="opacity-100"
            leave-active-class="transition duration-150 ease-in"
            leave-from-class="opacity-100"
            leave-to-class="opacity-0"
        >
            <div
                v-if="isOpen"
                class="fixed inset-0 z-[100] flex items-center justify-center p-4 bg-black/60 backdrop-blur-sm"
                @click.self="close"
            >
                <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-2xl max-w-md w-full overflow-hidden">
                    <!-- Header -->
                    <div class="p-6 text-white" :class="headerClass">
                        <div class="flex items-center gap-3">
                            <div class="w-12 h-12 rounded-xl bg-white/20 flex items-center justify-center">
                                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" :d="iconPath" />
                                </svg>
                            </div>
                            <div>
                                <h3 class="text-lg font-bold">{{ title }}</h3>
                                <p class="text-sm text-white/80">{{ subtitle }}</p>
                            </div>
                        </div>
                    </div>

                    <!-- Content -->
                    <div class="p-6">
                        <p class="text-sm text-gray-700 dark:text-gray-300">{{ message }}</p>

                        <!-- Confirmation input for force delete -->
                        <div v-if="requireTyping" class="mt-4">
                            <label class="block text-xs font-medium text-gray-500 dark:text-gray-400 mb-1">
                                Type <strong>{{ business.name }}</strong> to confirm
                            </label>
                            <input
                                v-model="confirmText"
                                type="text"
                                class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-900 text-gray-900 dark:text-white focus:ring-2 focus:ring-red-500 font-mono text-sm"
                                @paste.prevent
                            />
                        </div>
                    </div>

                    <!-- Actions -->
                    <div class="p-4 bg-gray-50 dark:bg-gray-900/50 flex gap-3">
                        <button
                            @click="close"
                            :disabled="processing"
                            class="flex-1 px-4 py-2.5 border border-gray-300 dark:border-gray-600 text-gray-700 dark:text-gray-300 rounded-xl hover:bg-gray-100 dark:hover:bg-gray-700 font-medium disabled:opacity-50"
                        >
                            Cancel
                        </button>
                        <button
                            @click="confirm"
                            :disabled="!canConfirm || processing"
                            class="flex-1 px-4 py-2.5 rounded-xl font-semibold text-white transition-all disabled:opacity-50"
                            :class="buttonClass"
                        >
                            {{ processing ? 'Processing...' : confirmText2 }}
                        </button>
                    </div>
                </div>
            </div>
        </Transition>
    </Teleport>
</template>

<script setup>
import { ref, computed, watch } from 'vue';

const props = defineProps({
    isOpen: { type: Boolean, default: false },
    action: { type: String, required: true }, // 'restore' or 'force-delete'
    business: { type: Object, required: true },
});

const emit = defineEmits(['close', 'confirm']);

const confirmText = ref('');
const processing = ref(false);

watch(() => props.isOpen, (open) => {
    if (open) {
        confirmText.value = '';
        processing.value = false;
    }
});

const isRestore = computed(() => props.action === 'restore');
const requireTyping = computed(() => !isRestore.value);

const title = computed(() => isRestore.value ? 'Restore Business' : 'Permanently Delete');
const subtitle = computed(() => isRestore.value ? 'Undo soft delete' : 'Cannot be undone');
const message = computed(() => isRestore.value
    ? `Restore "${props.business.name}"? All related branches, services, images, and reviews will be recovered.`
    : `Permanently delete "${props.business.name}"? This will destroy all data including image files. This action cannot be undone.`
);

const iconPath = computed(() => isRestore.value
    ? 'M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15'
    : 'M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16'
);

const headerClass = computed(() => isRestore.value
    ? 'bg-gradient-to-r from-green-500 to-emerald-600'
    : 'bg-gradient-to-r from-red-500 to-red-600'
);

const buttonClass = computed(() => isRestore.value
    ? 'bg-gradient-to-r from-green-500 to-emerald-600 hover:from-green-600 hover:to-emerald-700'
    : 'bg-gradient-to-r from-red-500 to-red-600 hover:from-red-600 hover:to-red-700'
);

const confirmText2 = computed(() => isRestore.value ? 'Restore' : 'Delete Forever');

const canConfirm = computed(() => !requireTyping.value || confirmText.value.trim() === props.business.name);

const close = () => {
    if (processing.value) return;
    emit('close');
};

const confirm = () => {
    if (!canConfirm.value || processing.value) return;
    processing.value = true;
    emit('confirm');
};
</script>