<!-- resources/js/Components/Owner/ContactModal.vue -->
<template>
    <Teleport to="body">
        <Transition enter-active-class="transition duration-200 ease-out" enter-from-class="opacity-0"
            enter-to-class="opacity-100" leave-active-class="transition duration-150 ease-in"
            leave-from-class="opacity-100" leave-to-class="opacity-0">
            <div v-if="show" class="fixed inset-0 z-[100] bg-black/50 backdrop-blur-sm"
                :class="isMobile ? 'flex items-end' : 'overflow-y-auto'" @click.self="close">

                <div :class="isMobile ? 'w-full' : 'flex items-center justify-center min-h-screen px-4 py-8'">
                    <Transition :enter-active-class="isMobile
                        ? 'transition-transform duration-300 ease-out'
                        : 'transition duration-300 ease-out'" :enter-from-class="isMobile
                            ? 'translate-y-full'
                            : 'opacity-0 scale-95 translate-y-4'" :enter-to-class="isMobile
                                ? 'translate-y-0'
                                : 'opacity-100 scale-100 translate-y-0'" :leave-active-class="isMobile
                                        ? 'transition-transform duration-200 ease-in'
                                        : 'transition duration-150 ease-in'" :leave-from-class="isMobile
                                        ? 'translate-y-0'
                                        : 'opacity-100 scale-100 translate-y-0'" :leave-to-class="isMobile
                                        ? 'translate-y-full'
                                        : 'opacity-0 scale-95 translate-y-4'">
                        <div :class="isMobile
                            ? 'relative bg-white dark:bg-gray-800 w-full rounded-t-3xl shadow-2xl flex flex-col max-h-[90vh]'
                            : 'relative bg-white dark:bg-gray-800 rounded-2xl shadow-2xl max-w-md w-full'">

                            <div v-if="isMobile" class="pt-3 pb-1 flex justify-center flex-shrink-0">
                                <div class="w-10 h-1 bg-gray-300 dark:bg-gray-600 rounded-full"></div>
                            </div>

                            <!-- Header -->
                            <div class="px-5 sm:px-6 py-4 border-b border-gray-100 dark:border-gray-700 flex justify-between items-center flex-shrink-0"
                                :class="!isMobile ? 'rounded-t-2xl' : ''">
                                <div class="flex items-center gap-3">
                                    <div
                                        class="w-10 h-10 rounded-xl bg-primary-100 dark:bg-primary-900/40 flex items-center justify-center flex-shrink-0">
                                        <svg class="w-5 h-5 text-primary-600 dark:text-primary-400" fill="none" stroke="currentColor"
                                            viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />
                                        </svg>
                                    </div>
                                    <div class="min-w-0">
                                        <h3 class="text-base sm:text-lg font-semibold text-gray-900 dark:text-white truncate">
                                            {{ isEditing ? 'Edit Contact' : 'Add Contact' }}
                                        </h3>
                                        <p class="text-xs text-gray-500 dark:text-gray-400 truncate">
                                            {{ isEditing ? 'Update contact information' : 'Add a new contact method' }}
                                        </p>
                                    </div>
                                </div>
                                <button @click="close"
                                    class="w-8 h-8 rounded-lg hover:bg-gray-100 dark:hover:bg-gray-700 flex items-center justify-center text-gray-400 dark:text-gray-500 hover:text-gray-600 dark:hover:text-gray-300 transition-colors flex-shrink-0">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M6 18L18 6M6 6l12 12" />
                                    </svg>
                                </button>
                            </div>

                            <!-- Form -->
                            <form @submit.prevent="submit" class="p-5 sm:p-6 space-y-4 sm:space-y-5"
                                :class="isMobile ? 'flex-1 overflow-y-auto' : ''">

                                <!-- Contact Type -->
                                <div>
                                    <label class="block text-xs sm:text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                                        Contact Type *
                                    </label>
                                    <div class="relative">
                                        <div
                                            class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                            <svg class="h-5 w-5 text-gray-400 dark:text-gray-500" fill="none" stroke="currentColor"
                                                viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                    d="M7 8h10M7 12h4m1 8l-4-4H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-3l-4 4z" />
                                            </svg>
                                        </div>
                                        <select v-model="form.type"
                                            class="w-full pl-10 pr-4 py-2.5 border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-900 text-gray-900 dark:text-white rounded-xl focus:outline-none focus:ring-2 focus:ring-primary-500 focus:border-transparent transition-colors appearance-none text-sm">
                                            <option v-for="type in contactTypes" :key="type.value" :value="type.value">
                                                {{ type.label }}
                                            </option>
                                        </select>
                                        <div
                                            class="absolute inset-y-0 right-0 pr-3 flex items-center pointer-events-none">
                                            <svg class="h-5 w-5 text-gray-400 dark:text-gray-500" fill="none" stroke="currentColor"
                                                viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                    d="M19 9l-7 7-7-7" />
                                            </svg>
                                        </div>
                                    </div>
                                </div>

                                <!-- Contact Value -->
                                <div>
                                    <label class="block text-xs sm:text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                                        Value *
                                    </label>
                                    <div class="relative">
                                        <div
                                            class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none text-gray-400 dark:text-gray-500">
                                            <ContactIcon :type="form.type" size="sm" />
                                        </div>
                                        <input type="text" v-model="form.value" required
                                            :placeholder="getTypePlaceholder(form.type)"
                                            class="w-full pl-10 pr-4 py-2.5 border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-900 text-gray-900 dark:text-white placeholder-gray-400 dark:placeholder-gray-500 rounded-xl focus:outline-none focus:ring-2 focus:ring-primary-500 focus:border-transparent transition-colors text-sm" />
                                    </div>
                                    <p class="text-[10px] sm:text-xs text-gray-400 dark:text-gray-500 mt-1">{{ getTypeHelp(form.type) }}
                                    </p>
                                </div>

                                <!-- Primary Contact Toggle -->
                                <div class="flex items-center gap-3 p-3 bg-gray-50 dark:bg-gray-900/50 rounded-xl border border-gray-100 dark:border-gray-700">
                                    <div class="relative">
                                        <input type="checkbox" v-model="form.is_primary" id="is_primary_contact"
                                            class="peer sr-only" />
                                        <label for="is_primary_contact"
                                            class="block w-10 h-6 bg-gray-300 dark:bg-gray-600 rounded-full peer-checked:bg-primary-600 transition-colors cursor-pointer">
                                            <span
                                                class="block w-5 h-5 bg-white rounded-full shadow-md transform transition-transform duration-200 peer-checked:translate-x-4 mt-0.5 ml-0.5"></span>
                                        </label>
                                    </div>
                                    <label for="is_primary_contact"
                                        class="text-xs sm:text-sm text-gray-700 dark:text-gray-300 cursor-pointer flex items-center gap-2">
                                        <svg class="w-4 h-4 text-gray-400 dark:text-gray-500" fill="none" stroke="currentColor"
                                            viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M5 13l4 4L19 7" />
                                        </svg>
                                        Set as primary contact
                                    </label>
                                    <span v-if="form.is_primary"
                                        class="ml-auto text-xs bg-primary-100 dark:bg-primary-900/40 text-primary-700 dark:text-primary-400 px-2 py-0.5 rounded-full">
                                        Primary
                                    </span>
                                </div>

                                <!-- Buttons -->
                                <div class="flex justify-end gap-3 pt-4 border-t border-gray-100 dark:border-gray-700"
                                    :class="isMobile ? 'sticky bottom-0 bg-white dark:bg-gray-800 -mx-5 px-5 -mb-5 pb-[calc(1rem+env(safe-area-inset-bottom))] pt-3 mt-2' : ''">
                                    <button type="button" @click="close"
                                        class="px-5 sm:px-6 py-2.5 border border-gray-300 dark:border-gray-600 text-gray-700 dark:text-gray-300 rounded-xl hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors font-medium text-sm">
                                        Cancel
                                    </button>
                                    <button type="submit"
                                        class="px-5 sm:px-6 py-2.5 bg-gradient-to-r from-primary-600 to-primary-700 text-white rounded-xl hover:from-primary-700 hover:to-primary-800 transition-all duration-200 shadow-lg shadow-primary-100 dark:shadow-none hover:shadow-primary-200 font-medium text-sm disabled:opacity-50 disabled:cursor-not-allowed flex items-center gap-2"
                                        :disabled="processing">
                                        <svg v-if="!processing" class="w-4 h-4" fill="none" stroke="currentColor"
                                            viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M5 13l4 4L19 7" />
                                        </svg>
                                        <svg v-else class="animate-spin h-4 w-4" fill="none" viewBox="0 0 24 24">
                                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor"
                                                stroke-width="4"></circle>
                                            <path class="opacity-75" fill="currentColor"
                                                d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z">
                                            </path>
                                        </svg>
                                        {{ isEditing ? 'Update Contact' : 'Add Contact' }}
                                    </button>
                                </div>
                            </form>
                        </div>
                    </Transition>
                </div>
            </div>
        </Transition>
    </Teleport>
</template>

<script setup>
    import { ref, reactive, watch, onMounted, onUnmounted } from 'vue';
    import ContactIcon from '@/Components/ContactIcon.vue';

    const props = defineProps({
        show: {
            type: Boolean,
            default: false
        },
        contact: {
            type: Object,
            default: null
        }
    });

    const emit = defineEmits(['close', 'save']);

    const processing = ref(false);
    const isEditing = ref(false);

    const isMobile = ref(false);
    let mediaQuery = null;

    const updateIsMobile = (e) => {
        isMobile.value = e.matches;
    };

    onMounted(() => {
        mediaQuery = window.matchMedia('(max-width: 639px)');
        isMobile.value = mediaQuery.matches;
        mediaQuery.addEventListener('change', updateIsMobile);
    });

    onUnmounted(() => {
        if (mediaQuery) {
            mediaQuery.removeEventListener('change', updateIsMobile);
        }
    });

    const contactTypes = [
        { value: 'phone', label: 'Phone' },
        { value: 'whatsapp', label: 'WhatsApp' },
        { value: 'facebook', label: 'Facebook' },
        { value: 'instagram', label: 'Instagram' },
        { value: 'tiktok', label: 'TikTok' },
        { value: 'twitter', label: 'Twitter' },
        { value: 'youtube', label: 'YouTube' },
        { value: 'linkedin', label: 'LinkedIn' },
        { value: 'other', label: 'Other' },
    ];

    const form = reactive({
        type: 'phone',
        value: '',
        is_primary: false,
    });


    const getTypePlaceholder = (type) => {
        const placeholders = {
            'phone': '+237 699 123 456',
            'whatsapp': '+237 699 123 456',
            'facebook': 'your_username',
            'instagram': '@your_username',
            'tiktok': '@your_username',
            'twitter': '@your_username',
            'youtube': '@your_channel',
            'linkedin': 'your_company',
            'other': 'Enter value...',
        };
        return placeholders[type] || 'Enter value...';
    };

    const getTypeHelp = (type) => {
        const help = {
            'phone': 'Include country code (e.g., +237)',
            'whatsapp': 'Include country code with no spaces',
            'facebook': 'Your Facebook page username',
            'instagram': 'Your Instagram username (without @)',
            'tiktok': 'Your TikTok username (without @)',
            'twitter': 'Your Twitter username (without @)',
            'youtube': 'Your YouTube channel name',
            'linkedin': 'Your LinkedIn company page name',
            'other': 'Custom contact value',
        };
        return help[type] || 'Enter the contact value';
    };

    watch(() => props.show, (newVal) => {
        if (newVal) {
            if (props.contact) {
                isEditing.value = true;
                form.type = props.contact.type || 'phone';
                form.value = props.contact.value || '';
                form.is_primary = props.contact.is_primary || false;
            } else {
                isEditing.value = false;
                form.type = 'phone';
                form.value = '';
                form.is_primary = false;
            }
            processing.value = false;
        }
    }, { immediate: true });

    const close = () => {
        emit('close');
    };

    const submit = () => {
        processing.value = true;
        emit('save', { ...form }, {
            onFinish: () => {
                processing.value = false;
            },
            onError: () => {
                processing.value = false;
            }
        });
    };
</script>