<!-- resources/js/Components/CategoryMultiSelect.vue -->
<template>
    <div ref="rootRef" class="relative">
        <!-- Trigger button -->
        <button
            type="button"
            @click="toggle"
            class="w-full min-h-[46px] px-3 py-2 border border-gray-200 dark:border-gray-600 rounded-xl bg-white dark:bg-gray-900 hover:border-primary-400 focus:outline-none focus:ring-2 focus:ring-primary-500 focus:border-transparent transition-all text-left flex items-center gap-2 flex-wrap"
        >
            <!-- Selected chips -->
            <template v-if="selectedCategories.length > 0">
                <span
                    v-for="cat in selectedCategories.slice(0, 3)"
                    :key="cat.id"
                    class="inline-flex items-center gap-1.5 px-2.5 py-1 bg-primary-50 dark:bg-primary-900/30 border border-primary-200 dark:border-primary-800 text-primary-700 dark:text-primary-400 text-xs font-semibold rounded-lg"
                >
                    <CategoryIcon :icon="cat.icon" size="xs" class="flex-shrink-0" />
                    {{ cat.name }}
                    <button
                        type="button"
                        @click.stop="removeCategory(cat.id)"
                        class="hover:text-primary-900 dark:hover:text-primary-200 transition-colors"
                    >
                        <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                </span>
                <span v-if="selectedCategories.length > 3" class="text-xs font-bold text-primary-600 dark:text-primary-400">
                    +{{ selectedCategories.length - 3 }} more
                </span>
            </template>

            <!-- Placeholder -->
            <span v-else class="text-sm text-gray-400 dark:text-gray-500">Select categories...</span>

            <!-- Chevron -->
            <svg
                class="w-4 h-4 text-gray-400 dark:text-gray-500 ml-auto flex-shrink-0 transition-transform"
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
                class="absolute z-20 mt-2 w-full bg-white dark:bg-gray-800 rounded-2xl border border-gray-100 dark:border-gray-700 shadow-xl shadow-gray-200/50 dark:shadow-black/40 overflow-hidden"
            >
                <!-- Search -->
                <div class="p-2.5 border-b border-gray-100 dark:border-gray-700">
                    <div class="relative">
                        <svg class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-gray-400 dark:text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                        </svg>
                        <input
                            ref="searchRef"
                            v-model="search"
                            type="text"
                            placeholder="Search categories..."
                            class="w-full pl-9 pr-3 py-2 bg-gray-50 dark:bg-gray-900/50 border border-transparent rounded-lg text-sm text-gray-900 dark:text-white placeholder-gray-400 dark:placeholder-gray-500 focus:outline-none focus:ring-2 focus:ring-primary-500 focus:bg-white dark:focus:bg-gray-900 transition-all"
                        />
                    </div>
                </div>

                <!-- List -->
                <div class="max-h-72 overflow-y-auto p-1.5">
                    <button
                        v-for="cat in filteredCategories"
                        :key="cat.id"
                        type="button"
                        @click="toggleCategory(cat.id)"
                        class="w-full flex items-center gap-3 px-3 py-2.5 rounded-lg hover:bg-gray-50 dark:hover:bg-gray-700/50 transition-colors text-left"
                        :class="isSelected(cat.id) ? 'bg-primary-50/50 dark:bg-primary-900/20' : ''"
                    >
                        <!-- Checkbox -->
                        <div
                            class="w-5 h-5 rounded-md border-2 flex items-center justify-center flex-shrink-0 transition-all"
                            :class="isSelected(cat.id)
                                ? 'bg-primary-600 border-primary-600'
                                : 'border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-900'"
                        >
                            <svg
                                v-if="isSelected(cat.id)"
                                class="w-3 h-3 text-white"
                                fill="none" stroke="currentColor" viewBox="0 0 24 24"
                            >
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7" />
                            </svg>
                        </div>

                        <!-- Icon + name -->
                        <div class="w-8 h-8 rounded-lg bg-gray-50 dark:bg-gray-700 flex items-center justify-center flex-shrink-0 text-gray-600 dark:text-gray-300">
                            <CategoryIcon :icon="cat.icon" size="sm" />
                        </div>
                        <span class="text-sm font-medium text-gray-900 dark:text-white flex-1 min-w-0 truncate">
                            {{ cat.name }}
                        </span>
                    </button>

                    <!-- No results -->
                    <div v-if="filteredCategories.length === 0" class="px-3 py-8 text-center text-sm text-gray-400 dark:text-gray-500">
                        No categories match "{{ search }}"
                    </div>
                </div>

                <!-- Footer -->
                <div class="px-3 py-2.5 border-t border-gray-100 dark:border-gray-700 bg-gray-50 dark:bg-gray-900/50 flex items-center justify-between">
                    <span class="text-xs font-medium text-gray-500 dark:text-gray-400">
                        {{ modelValue.length }} selected
                    </span>
                    <button
                        type="button"
                        @click="isOpen = false"
                        class="px-3 py-1.5 bg-primary-600 text-white text-xs font-bold rounded-lg hover:bg-primary-700 transition-colors"
                    >
                        Done
                    </button>
                </div>
            </div>
        </Transition>
    </div>
</template>

<script setup>
import { ref, computed, onMounted, onUnmounted, nextTick, watch } from 'vue';
import CategoryIcon from '@/Components/CategoryIcon.vue';

const props = defineProps({
    modelValue: {
        type: Array,
        default: () => [],
    },
    categories: {
        type: Array,
        default: () => [],
    },
});

const emit = defineEmits(['update:modelValue']);

const rootRef = ref(null);
const searchRef = ref(null);
const isOpen = ref(false);
const search = ref('');

const selectedCategories = computed(() => {
    const ids = new Set(props.modelValue);
    return props.categories.filter((c) => ids.has(c.id));
});

const filteredCategories = computed(() => {
    if (!search.value.trim()) return props.categories;
    const term = search.value.toLowerCase();
    return props.categories.filter((c) => c.name.toLowerCase().includes(term));
});

const isSelected = (id) => props.modelValue.includes(id);

const toggle = () => {
    isOpen.value = !isOpen.value;
    if (isOpen.value) {
        search.value = '';
        nextTick(() => searchRef.value?.focus());
    }
};

const toggleCategory = (id) => {
    const next = isSelected(id)
        ? props.modelValue.filter((x) => x !== id)
        : [...props.modelValue, id];
    emit('update:modelValue', next);
};

const removeCategory = (id) => {
    emit('update:modelValue', props.modelValue.filter((x) => x !== id));
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