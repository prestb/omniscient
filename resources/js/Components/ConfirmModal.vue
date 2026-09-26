<script setup>
import { computed, ref, watch, h } from 'vue';
import { confirmState, confirmActions } from '@/composables/useConfirm';

// ✅ Use direct module exports — avoids instance issues
const { isOpen, options } = confirmState;
const { handleConfirm, handleCancel } = confirmActions;

const typedText = ref('');

// Reset typed text when modal opens
watch(isOpen, (open) => {
    if (open) typedText.value = '';
});

// ============== VARIANTS ==============
const variants = {
    primary: {
        headerBg: 'bg-gradient-to-r from-primary-500 to-primary-600',
        iconBg: 'bg-white/20 text-white',
        title: 'text-white',
        message: 'text-white/90',
        button: 'bg-gradient-to-r from-primary-600 to-primary-700 hover:from-primary-700 hover:to-primary-800 shadow-primary-500/30',
    },
    danger: {
        headerBg: 'bg-gradient-to-r from-red-500 to-red-600',
        iconBg: 'bg-white/20 text-white',
        title: 'text-white',
        message: 'text-white/90',
        button: 'bg-gradient-to-r from-red-500 to-red-600 hover:from-red-600 hover:to-red-700 shadow-red-500/30',
    },
    warning: {
        headerBg: 'bg-gradient-to-r from-amber-500 to-orange-500',
        iconBg: 'bg-white/20 text-white',
        title: 'text-white',
        message: 'text-white/90',
        button: 'bg-gradient-to-r from-amber-500 to-orange-500 hover:from-amber-600 hover:to-orange-600 shadow-amber-500/30',
    },
    success: {
        headerBg: 'bg-gradient-to-r from-green-500 to-emerald-600',
        iconBg: 'bg-white/20 text-white',
        title: 'text-white',
        message: 'text-white/90',
        button: 'bg-gradient-to-r from-green-500 to-emerald-600 hover:from-green-600 hover:to-emerald-700 shadow-green-500/30',
    },
};

const currentVariant = computed(() => variants[options.value.variant] || variants.primary);
const headerClass = computed(() => currentVariant.value.headerBg);
const iconContainerClass = computed(() => currentVariant.value.iconBg);
const titleClass = computed(() => currentVariant.value.title);
const messageClass = computed(() => currentVariant.value.message);
const buttonClass = computed(() => currentVariant.value.button);

// ============== ICONS ==============
const icons = {
    primary: () => h('svg', { class: 'w-6 h-6', fill: 'none', stroke: 'currentColor', viewBox: '0 0 24 24' }, [
        h('path', { 'stroke-linecap': 'round', 'stroke-linejoin': 'round', 'stroke-width': '2', d: 'M8.228 9c.549-1.165 2.03-2 3.772-2 2.21 0 4 1.343 4 3 0 1.4-1.278 2.575-3.006 2.907-.542.104-.994.54-.994 1.093m0 3h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z' }),
    ]),
    danger: () => h('svg', { class: 'w-6 h-6', fill: 'none', stroke: 'currentColor', viewBox: '0 0 24 24' }, [
        h('path', { 'stroke-linecap': 'round', 'stroke-linejoin': 'round', 'stroke-width': '2', d: 'M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16' }),
    ]),
    warning: () => h('svg', { class: 'w-6 h-6', fill: 'none', stroke: 'currentColor', viewBox: '0 0 24 24' }, [
        h('path', { 'stroke-linecap': 'round', 'stroke-linejoin': 'round', 'stroke-width': '2', d: 'M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z' }),
    ]),
    success: () => h('svg', { class: 'w-6 h-6', fill: 'none', stroke: 'currentColor', viewBox: '0 0 24 24' }, [
        h('path', { 'stroke-linecap': 'round', 'stroke-linejoin': 'round', 'stroke-width': '2', d: 'M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z' }),
    ]),
};

const iconComponent = computed(() => icons[options.value.variant] || icons.primary);

// ============== CONFIRMATION LOGIC ==============
const canConfirm = computed(() => {
    if (!options.value.requireTyping) return true;
    return typedText.value.trim() === options.value.typingText;
});

const onConfirm = () => {
    if (canConfirm.value) {
        handleConfirm(); // ✅ Use module-level handler
    }
};

const onCancel = () => {
    handleCancel(); // ✅ Use module-level handler
};
</script>

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
                class="fixed inset-0 z-[200] flex items-center justify-center p-4 bg-black/60 backdrop-blur-sm"
                @click.self="onCancel"
            >
                <Transition
                    enter-active-class="transition duration-300 ease-out"
                    enter-from-class="opacity-0 scale-95 translate-y-4"
                    enter-to-class="opacity-100 scale-100 translate-y-0"
                    appear
                >
                    <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-2xl max-w-md w-full overflow-hidden">
                        <!-- Header -->
                        <div class="p-6 flex items-start gap-4" :class="headerClass">
                            <div class="w-12 h-12 rounded-2xl flex items-center justify-center flex-shrink-0 shadow-lg" :class="iconContainerClass">
                                <component :is="iconComponent" />
                            </div>
                            <div class="flex-1 min-w-0">
                                <h3 class="text-lg font-bold" :class="titleClass">
                                    {{ options.title }}
                                </h3>
                                <p v-if="options.message" class="text-sm mt-1 leading-relaxed whitespace-pre-line" :class="messageClass">
                                    {{ options.message }}
                                </p>
                            </div>
                        </div>

                        <!-- Typing Confirmation -->
                        <div v-if="options.requireTyping" class="px-6 pb-2">
                            <div class="p-4 bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800 rounded-xl">
                                <label class="block text-xs font-medium text-red-800 dark:text-red-300 mb-2">
                                    Type <strong class="font-mono px-1.5 py-0.5 bg-red-100 dark:bg-red-900/50 rounded">{{ options.typingText }}</strong> to confirm
                                </label>
                                <input
                                    v-model="typedText"
                                    type="text"
                                    :placeholder="options.typingText"
                                    class="w-full px-3 py-2 border border-red-300 dark:border-red-700 rounded-lg bg-white dark:bg-gray-900 text-gray-900 dark:text-white focus:ring-2 focus:ring-red-500 focus:border-transparent font-mono text-sm"
                                    autocomplete="off"
                                    autocorrect="off"
                                    spellcheck="false"
                                    @paste.prevent
                                />
                            </div>
                        </div>

                        <!-- Actions -->
                        <div class="p-6 bg-gray-50 dark:bg-gray-900/50 flex flex-col sm:flex-row gap-3">
                            <button
                                @click="onCancel"
                                class="flex-1 px-5 py-3 border border-gray-300 dark:border-gray-600 text-gray-700 dark:text-gray-300 rounded-xl hover:bg-gray-100 dark:hover:bg-gray-700 font-medium transition-colors"
                            >
                                {{ options.cancelText }}
                            </button>
                            <button
                                @click="onConfirm"
                                :disabled="!canConfirm"
                                class="flex-1 px-5 py-3 rounded-xl font-semibold text-white transition-all shadow-lg disabled:opacity-50 disabled:cursor-not-allowed"
                                :class="buttonClass"
                            >
                                {{ options.confirmText }}
                            </button>
                        </div>
                    </div>
                </Transition>
            </div>
        </Transition>
    </Teleport>
</template>