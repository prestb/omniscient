<!-- resources/js/Components/Admin/CategoryIconPicker.vue -->
<template>
    <div ref="rootRef" class="relative">
        <!-- Trigger button -->
        <button
            type="button"
            @click="isOpen = !isOpen"
            class="w-full min-h-[46px] px-3 py-2 border border-gray-300 rounded-xl bg-white hover:border-primary-400 focus:outline-none focus:ring-2 focus:ring-primary-500 focus:border-transparent transition-all text-left flex items-center gap-3"
        >
            <!-- Current icon preview -->
            <div class="w-9 h-9 rounded-lg bg-gradient-to-br from-purple-50 to-purple-100 flex items-center justify-center text-purple-600 flex-shrink-0">
                <CategoryIcon :icon="modelValue" size="md" />
            </div>

            <!-- Current label / placeholder -->
            <div class="flex-1 min-w-0">
                <p class="text-sm font-medium text-gray-900 truncate">
                    {{ currentLabel }}
                </p>
                <p v-if="modelValue" class="text-[10px] text-gray-400 font-mono">
                    {{ modelValue }}
                </p>
            </div>

            <!-- Chevron -->
            <svg
                class="w-4 h-4 text-gray-400 flex-shrink-0 transition-transform"
                :class="isOpen ? 'rotate-180' : ''"
                fill="none" stroke="currentColor" viewBox="0 0 24 24"
            >
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
            </svg>
        </button>

        <!-- Dropdown panel -->
        <Transition
            enter-active-class="transition duration-150 ease-out"
            enter-from-class="opacity-0 -translate-y-1"
            enter-to-class="opacity-100 translate-y-0"
            leave-active-class="transition duration-100 ease-in"
            leave-from-class="opacity-100 translate-y-0"
            leave-to-class="opacity-0 -translate-y-1"
        >
            <div
                v-if="isOpen"
                class="absolute z-30 mt-2 w-full bg-white rounded-2xl border border-gray-100 shadow-xl shadow-gray-200/50 overflow-hidden"
            >
                <!-- Search -->
                <div class="p-2.5 border-b border-gray-100">
                    <div class="relative">
                        <svg class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                        </svg>
                        <input
                            ref="searchRef"
                            v-model="search"
                            type="text"
                            placeholder="Search icons..."
                            class="w-full pl-9 pr-3 py-2 bg-gray-50 border border-transparent rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-primary-500 focus:bg-white transition-all"
                        />
                    </div>
                </div>

                <!-- Icon grid -->
                <div class="max-h-80 overflow-y-auto p-3">
                    <div v-if="filteredIcons.length > 0" class="grid grid-cols-3 sm:grid-cols-4 gap-2">
                        <button
                            v-for="icon in filteredIcons"
                            :key="icon.emoji"
                            type="button"
                            @click="select(icon.emoji)"
                            class="group relative flex flex-col items-center gap-1.5 p-2.5 rounded-xl hover:bg-purple-50 transition-colors"
                            :class="modelValue === icon.emoji
                                ? 'bg-purple-100 ring-2 ring-purple-400'
                                : 'bg-gray-50'"
                        >
                            <!-- Icon tile -->
                            <div class="w-10 h-10 rounded-lg bg-white flex items-center justify-center shadow-sm group-hover:shadow-md transition-shadow text-purple-600">
                                <CategoryIcon :icon="icon.emoji" size="md" />
                            </div>
                            <!-- Label -->
                            <span class="text-[10px] font-semibold text-gray-600 text-center leading-tight line-clamp-2">
                                {{ icon.label }}
                            </span>
                            <!-- Checkmark if selected -->
                            <div v-if="modelValue === icon.emoji"
                                 class="absolute top-1 right-1 w-4 h-4 rounded-full bg-purple-500 flex items-center justify-center">
                                <svg class="w-2.5 h-2.5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7" />
                                </svg>
                            </div>
                        </button>
                    </div>

                    <!-- No results -->
                    <div v-else class="py-8 text-center">
                        <p class="text-sm text-gray-400">No icons match "{{ search }}"</p>
                    </div>
                </div>

                <!-- Footer -->
                <div class="px-3 py-2.5 border-t border-gray-100 bg-gray-50 flex items-center justify-between">
                    <span class="text-[10px] font-medium text-gray-400">
                        {{ filteredIcons.length }} icon{{ filteredIcons.length === 1 ? '' : 's' }}
                    </span>
                    <button
                        type="button"
                        @click="isOpen = false"
                        class="px-3 py-1.5 bg-purple-600 text-white text-xs font-bold rounded-lg hover:bg-purple-700 transition-colors"
                    >
                        Done
                    </button>
                </div>
            </div>
        </Transition>
    </div>
</template>

<script setup>
import { ref, computed, onMounted, onUnmounted, nextTick } from 'vue';
import CategoryIcon from '@/Components/CategoryIcon.vue';

const props = defineProps({
    modelValue: {
        type: String,
        default: '',
    },
});

const emit = defineEmits(['update:modelValue']);

const rootRef = ref(null);
const searchRef = ref(null);
const isOpen = ref(false);
const search = ref('');

/**
 * Catalog of supported icons.
 * Each entry: emoji (stored in DB), label (displayed in picker), aliases (for search).
 */
const catalog = [
    // Health
    { emoji: '🏥', label: 'Hospital',      aliases: 'health medical clinic' },
    { emoji: '💊', label: 'Pharmacy',      aliases: 'medical medicine drugs' },
    { emoji: '🔬', label: 'Laboratory',    aliases: 'science research lab' },
    { emoji: '🦷', label: 'Dental',        aliases: 'dentist tooth clinic' },
    { emoji: '👓', label: 'Optical',       aliases: 'glasses vision eye' },

    // Food
    { emoji: '🍽️', label: 'Dining',        aliases: 'food restaurant eat' },
    { emoji: '🍴', label: 'Restaurant',    aliases: 'food dining eat' },
    { emoji: '🍞', label: 'Bakery',        aliases: 'bread pastry' },
    { emoji: '☕', label: 'Café',          aliases: 'coffee tea' },
    { emoji: '🍔', label: 'Fast Food',     aliases: 'burger' },
    { emoji: '🍱', label: 'Catering',      aliases: 'food box bento' },

    // Shopping
    { emoji: '🛍️', label: 'Shopping',      aliases: 'retail bags' },
    { emoji: '🛒', label: 'Supermarket',   aliases: 'grocery cart' },
    { emoji: '📦', label: 'Provision',     aliases: 'box store' },
    { emoji: '👗', label: 'Clothing',      aliases: 'dress fashion' },
    { emoji: '📱', label: 'Electronics',   aliases: 'phone mobile' },
    { emoji: '🪑', label: 'Furniture',     aliases: 'chair home' },
    { emoji: '🔧', label: 'Hardware',      aliases: 'wrench tool repair' },

    // Education
    { emoji: '📚', label: 'Education',     aliases: 'books learning' },
    { emoji: '🎓', label: 'University',    aliases: 'graduation college' },
    { emoji: '🏫', label: 'School',        aliases: 'college education' },
    { emoji: '📖', label: 'Primary School', aliases: 'book learn' },
    { emoji: '📐', label: 'Secondary',     aliases: 'ruler math' },
    { emoji: '🔨', label: 'Vocational',    aliases: 'hammer training' },
    { emoji: '✏️', label: 'Tutoring',      aliases: 'pencil teach' },

    // Hospitality
    { emoji: '🏨', label: 'Hotel',         aliases: 'hospitality travel' },
    { emoji: '🏠', label: 'Guest House',   aliases: 'home real estate' },
    { emoji: '🌴', label: 'Resort',        aliases: 'palm vacation' },
    { emoji: '✈️', label: 'Travel',        aliases: 'airplane flight' },

    // Financial
    { emoji: '💰', label: 'Finance',       aliases: 'money' },
    { emoji: '🏦', label: 'Bank',          aliases: 'money finance' },
    { emoji: '💳', label: 'Credit',        aliases: 'card microfinance' },
    { emoji: '🛡️', label: 'Insurance',     aliases: 'shield protection' },
    { emoji: '💸', label: 'Transfer',      aliases: 'money send' },

    // Professional
    { emoji: '💼', label: 'Business',      aliases: 'briefcase professional' },
    { emoji: '⚖️', label: 'Legal',         aliases: 'law scales justice' },
    { emoji: '📊', label: 'Accounting',    aliases: 'chart finance' },
    { emoji: '🤝', label: 'Consulting',    aliases: 'handshake deal' },

    // Automotive
    { emoji: '🚗', label: 'Automotive',    aliases: 'car auto' },
    { emoji: '🚘', label: 'Car Dealership', aliases: 'car auto' },
    { emoji: '⛽', label: 'Fuel',          aliases: 'gas station petrol' },
    { emoji: '🧼', label: 'Car Wash',      aliases: 'soap clean' },

    // Beauty
    { emoji: '💇', label: 'Beauty',        aliases: 'hair salon' },
    { emoji: '✂️', label: 'Barbershop',    aliases: 'scissors cut' },
    { emoji: '🧖', label: 'Spa',           aliases: 'steam relax' },
    { emoji: '💪', label: 'Fitness',       aliases: 'gym muscle' },

    // Technology
    { emoji: '💻', label: 'Technology',    aliases: 'laptop computer' },
    { emoji: '🖥️', label: 'Desktop',       aliases: 'computer monitor' },
    { emoji: '🌐', label: 'Internet',      aliases: 'web globe' },
    { emoji: '🖨️', label: 'Printing',      aliases: 'printer' },
    { emoji: '⌨️', label: 'Software',      aliases: 'keyboard dev' },

    // Community
    { emoji: '⛪', label: 'Church',        aliases: 'religious' },
    { emoji: '🕌', label: 'Mosque',        aliases: 'religious' },
    { emoji: '🤲', label: 'NGO',           aliases: 'charity hands' },
    { emoji: '🏘️', label: 'Community',     aliases: 'neighborhood' },

    // Government
    { emoji: '🏛️', label: 'Government',    aliases: 'public official' },
    { emoji: '📋', label: 'Public Service', aliases: 'clipboard' },
    { emoji: '🕊️', label: 'Embassy',       aliases: 'peace dove' },

    // Entertainment
    { emoji: '🎭', label: 'Entertainment', aliases: 'theater masks' },
    { emoji: '🎪', label: 'Event',         aliases: 'circus tent' },
    { emoji: '🎬', label: 'Cinema',        aliases: 'film movie' },
    { emoji: '💃', label: 'Nightclub',     aliases: 'dance party' },
];

/**
 * Extra emojis we know how to render but don't have a natural label for.
 * (Kept in the catalog table for future use — CategoryIcon supports them.)
 */

const filteredIcons = computed(() => {
    const term = search.value.trim().toLowerCase();
    if (!term) return catalog;

    return catalog.filter((icon) => {
        const haystack = `${icon.label} ${icon.aliases} ${icon.emoji}`.toLowerCase();
        return haystack.includes(term);
    });
});

const currentLabel = computed(() => {
    if (!props.modelValue) return 'Choose an icon';
    const found = catalog.find((c) => c.emoji === props.modelValue);
    return found ? found.label : 'Custom icon';
});

const select = (emoji) => {
    emit('update:modelValue', emoji);
    isOpen.value = false;
    search.value = '';
};

const handleClickOutside = (e) => {
    if (rootRef.value && !rootRef.value.contains(e.target)) {
        isOpen.value = false;
    }
};

const handleEscape = (e) => {
    if (e.key === 'Escape' && isOpen.value) {
        isOpen.value = false;
    }
};

onMounted(() => {
    document.addEventListener('mousedown', handleClickOutside);
    document.addEventListener('keydown', handleEscape);
});

onUnmounted(() => {
    document.removeEventListener('mousedown', handleClickOutside);
    document.removeEventListener('keydown', handleEscape);
});
</script>