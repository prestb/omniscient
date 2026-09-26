<!-- resources/js/Components/Owner/ServiceModal.vue -->
<template>
    <Teleport to="body">
        <Transition enter-active-class="transition duration-200 ease-out" enter-from-class="opacity-0"
            enter-to-class="opacity-100" leave-active-class="transition duration-150 ease-in"
            leave-from-class="opacity-100" leave-to-class="opacity-0">
            <div v-if="show" class="fixed inset-0 z-[100] bg-black/50 backdrop-blur-sm"
                :class="isMobile ? 'flex items-end' : 'overflow-y-auto'" @click.self="close">

                <div :class="isMobile ? 'w-full' : 'flex items-center justify-center min-h-screen px-4 py-8 pointer-events-none'"
                    :style="isMobile ? '' : 'pointer-events: auto;'">
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
                            : 'relative bg-white dark:bg-gray-800 rounded-2xl shadow-2xl max-w-md w-full pointer-events-auto'">

                            <!-- Mobile drag handle -->
                            <div v-if="isMobile" class="pt-3 pb-1 flex justify-center flex-shrink-0">
                                <div class="w-10 h-1 bg-gray-300 dark:bg-gray-600 rounded-full"></div>
                            </div>

                            <!-- Header -->
                            <div class="px-5 sm:px-6 py-4 border-b border-gray-100 dark:border-gray-700 flex justify-between items-center flex-shrink-0"
                                :class="!isMobile ? 'rounded-t-2xl' : ''">
                                <div class="flex items-center gap-3">
                                    <div
                                        class="w-10 h-10 rounded-xl bg-blue-100 dark:bg-blue-900/40 flex items-center justify-center flex-shrink-0">
                                        <svg class="w-5 h-5 text-blue-600 dark:text-blue-400" fill="none" stroke="currentColor"
                                            viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10" />
                                        </svg>
                                    </div>
                                    <div class="min-w-0">
                                        <h3 class="text-base sm:text-lg font-semibold text-gray-900 dark:text-white truncate">
                                            {{ isEditing ? 'Edit Service' : 'Add Service' }}
                                        </h3>
                                        <p class="text-xs text-gray-500 dark:text-gray-400 truncate">
                                            {{ isEditing ? 'Update service details' : 'Add a new service or product' }}
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

                            <!-- Form (scrollable on mobile) -->
                            <form @submit.prevent="submit" class="p-5 sm:p-6 space-y-4 sm:space-y-5"
                                :class="isMobile ? 'flex-1 overflow-y-auto' : ''">

                                <!-- Service Name -->
                                <div>
                                    <label class="block text-xs sm:text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                                        Service Name *
                                    </label>
                                    <div class="relative">
                                        <div
                                            class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                            <svg class="h-5 w-5 text-gray-400 dark:text-gray-500" fill="none" stroke="currentColor"
                                                viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                    d="M13 10V3L4 14h7v7l9-11h-7z" />
                                            </svg>
                                        </div>
                                        <input type="text" v-model="form.name" required maxlength="50"
                                            class="w-full pl-10 pr-4 py-2.5 border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-900 text-gray-900 dark:text-white placeholder-gray-400 dark:placeholder-gray-500 rounded-xl focus:outline-none focus:ring-2 focus:ring-primary-500 focus:border-transparent transition-colors text-sm"
                                            placeholder="Enter service name" />
                                    </div>
                                    <div class="flex justify-between mt-1">
                                        <p v-if="errors.name" class="text-red-500 dark:text-red-400 text-xs">{{ errors.name }}</p>
                                        <span v-else class="text-[10px] sm:text-xs text-gray-400 dark:text-gray-500"></span>
                                        <span :class="{
                                            'text-[10px] sm:text-xs text-gray-400 dark:text-gray-500': (form.name?.length || 0) < 40,
                                            'text-[10px] sm:text-xs text-amber-600 dark:text-amber-400': (form.name?.length || 0) >= 40 && (form.name?.length || 0) < 50,
                                            'text-[10px] sm:text-xs text-red-600 dark:text-red-400 font-semibold': (form.name?.length || 0) >= 50,
                                        }">
                                            {{ form.name?.length || 0 }} / 50
                                        </span>
                                    </div>
                                </div>

                                <!-- Description -->
                                <div>
                                    <label class="block text-xs sm:text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                                        Description
                                    </label>
                                    <div class="relative">
                                        <div class="absolute top-3 left-3 flex items-start pointer-events-none">
                                            <svg class="h-5 w-5 text-gray-400 dark:text-gray-500" fill="none" stroke="currentColor"
                                                viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                    d="M4 6h16M4 12h16M4 18h7" />
                                            </svg>
                                        </div>
                                        <textarea v-model="form.description" rows="3" maxlength="75"
                                            class="w-full pl-10 pr-4 py-2.5 border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-900 text-gray-900 dark:text-white placeholder-gray-400 dark:placeholder-gray-500 rounded-xl focus:outline-none focus:ring-2 focus:ring-primary-500 focus:border-transparent transition-colors resize-none text-sm"
                                            placeholder="Brief description of this service"></textarea>
                                    </div>
                                    <div class="flex justify-between mt-1">
                                        <p class="text-[10px] sm:text-xs text-gray-400 dark:text-gray-500">Describe what this service includes</p>
                                        <span :class="{
                                            'text-[10px] sm:text-xs text-gray-400 dark:text-gray-500': (form.description?.length || 0) < 60,
                                            'text-[10px] sm:text-xs text-amber-600 dark:text-amber-400': (form.description?.length || 0) >= 60 && (form.description?.length || 0) < 75,
                                            'text-[10px] sm:text-xs text-red-600 dark:text-red-400 font-semibold': (form.description?.length || 0) >= 75,
                                        }">
                                            {{ form.description?.length || 0 }} / 75
                                        </span>
                                    </div>
                                    <p v-if="errors.description" class="text-red-500 dark:text-red-400 text-xs mt-1">{{ errors.description
                                        }}</p>
                                </div>

                                <!-- Price (Optional) -->
                                <div>
                                    <label class="block text-xs sm:text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                                        Price Range (Optional)
                                    </label>
                                    <div class="grid grid-cols-2 gap-3">
                                        <div class="relative">
                                            <div
                                                class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                                <span class="text-gray-400 dark:text-gray-500 font-medium text-xs sm:text-sm">FCFA</span>
                                            </div>
                                            <input type="number" v-model="form.price_min"
                                                class="w-full pl-12 sm:pl-14 pr-4 py-2.5 border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-900 text-gray-900 dark:text-white placeholder-gray-400 dark:placeholder-gray-500 rounded-xl focus:outline-none focus:ring-2 focus:ring-primary-500 focus:border-transparent transition-colors text-sm"
                                                placeholder="Min" />
                                        </div>
                                        <div class="relative">
                                            <div
                                                class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                                <span class="text-gray-400 dark:text-gray-500 font-medium text-xs sm:text-sm">FCFA</span>
                                            </div>
                                            <input type="number" v-model="form.price_max"
                                                class="w-full pl-12 sm:pl-14 pr-4 py-2.5 border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-900 text-gray-900 dark:text-white placeholder-gray-400 dark:placeholder-gray-500 rounded-xl focus:outline-none focus:ring-2 focus:ring-primary-500 focus:border-transparent transition-colors text-sm"
                                                placeholder="Max" />
                                        </div>
                                    </div>
                                    <p class="text-[10px] sm:text-xs text-gray-400 dark:text-gray-500 mt-1">Leave blank if price varies or
                                        not applicable</p>
                                </div>

                                <!-- Duration (Optional) -->
                                <div>
                                    <label class="block text-xs sm:text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                                        Duration (Optional)
                                    </label>
                                    <div class="relative">
                                        <div
                                            class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                            <svg class="h-5 w-5 text-gray-400 dark:text-gray-500" fill="none" stroke="currentColor"
                                                viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                    d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                                            </svg>
                                        </div>
                                        <input type="text" v-model="form.duration"
                                            class="w-full pl-10 pr-4 py-2.5 border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-900 text-gray-900 dark:text-white placeholder-gray-400 dark:placeholder-gray-500 rounded-xl focus:outline-none focus:ring-2 focus:ring-primary-500 focus:border-transparent transition-colors text-sm"
                                            placeholder="e.g., 30 min, 1 hour, 2 days" />
                                    </div>
                                </div>

                                <!-- Buttons (sticky on mobile) -->
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
                                        {{ isEditing ? 'Update Service' : 'Add Service' }}
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

    const props = defineProps({
        show: {
            type: Boolean,
            default: false
        },
        service: {
            type: Object,
            default: null
        }
    });

    const emit = defineEmits(['close', 'save']);

    const processing = ref(false);
    const isEditing = ref(false);
    const errors = ref({});

    // ✅ Reactive mobile detection
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

    const form = reactive({
        name: '',
        description: '',
        price_min: null,
        price_max: null,
        duration: '',
    });

    watch(() => props.show, (newVal) => {
        if (newVal) {
            if (props.service) {
                isEditing.value = true;
                form.name = props.service.name || '';
                form.description = props.service.description || '';
                form.price_min = props.service.price_min || null;
                form.price_max = props.service.price_max || null;
                form.duration = props.service.duration || '';
            } else {
                isEditing.value = false;
                form.name = '';
                form.description = '';
                form.price_min = null;
                form.price_max = null;
                form.duration = '';
            }
            errors.value = {};
            processing.value = false;
        }
    }, { immediate: true });

    const close = () => {
        emit('close');
    };

    const submit = () => {
        if (!form.name.trim()) {
            errors.value = { name: 'Service name is required' };
            return;
        }

        processing.value = true;
        errors.value = {};

        emit('save', { ...form }, {
            onFinish: () => {
                processing.value = false;
            },
            onError: (err) => {
                errors.value = err || {};
                processing.value = false;
            }
        });
    };
</script>