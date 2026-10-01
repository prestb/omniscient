<!-- resources/js/Pages/Owner/Hours/Index.vue -->
<template>
    <AuthenticatedLayout>
        <!-- COMPACT HEADER -->
        <PageHeader color="sky" :breadcrumb="[
            { label: 'Dashboard', href: '/owner/dashboard' },
            { label: business.name, href: `/owner/businesses/${business.id}/edit` },
            { label: 'Locations', href: `/owner/businesses/${business.id}/locations` },
            { label: location.name || 'Location', href: `/owner/businesses/${business.id}/locations/${location.id}/edit` },
            { label: 'Hours' }
        ]">
            <template #icon>
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                    d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
            </template>
            <template #title>Operating hours</template>
            <template #subtitle>{{ business.name }} · {{ location.name || 'Location' }}</template>
            <template #actions>
                <span class="inline-flex items-center gap-2 px-3 py-2 rounded-xl text-sm font-semibold border"
                    :class="isOpen
                        ? 'bg-emerald-50 dark:bg-emerald-900/30 text-emerald-700 dark:text-emerald-400 border-emerald-200 dark:border-emerald-800'
                        : 'bg-red-50 dark:bg-red-900/30 text-red-700 dark:text-red-400 border-red-200 dark:border-red-800'">
                    <span class="w-2 h-2 rounded-full animate-pulse"
                        :class="isOpen ? 'bg-emerald-500' : 'bg-red-500'"></span>
                    {{ isOpen ? 'Open Now' : 'Closed' }}
                </span>
            </template>
        </PageHeader>

        <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 py-8 space-y-6">

            <div
                class="bg-white dark:bg-gray-800 rounded-2xl shadow-sm border border-gray-100 dark:border-gray-700 overflow-hidden hover:shadow-md transition-shadow">
                <div
                    class="px-6 py-4 border-b border-gray-100 dark:border-gray-700 bg-gradient-to-r from-gray-50 to-white dark:from-gray-800 dark:to-gray-800/50 flex justify-between items-center">
                    <div class="flex items-center gap-2">
                        <svg class="w-5 h-5 text-gray-400 dark:text-gray-500" fill="none" stroke="currentColor"
                            viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                        <h3 class="text-sm font-semibold text-gray-700 dark:text-gray-300">Weekly Schedule</h3>
                    </div>
                    <button type="button" @click="setAllDays"
                        class="inline-flex items-center gap-1.5 text-sm text-primary-600 dark:text-primary-400 hover:text-primary-800 dark:hover:text-primary-300 transition-colors font-medium">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                        </svg>
                        Apply to all days
                    </button>
                </div>

                <div class="p-6">
                    <form @submit.prevent="saveHours" class="space-y-3">
                        <div v-for="day in days" :key="day.value"
                            class="flex flex-col lg:flex-row lg:items-center gap-3 p-4 rounded-xl hover:bg-gray-50 dark:hover:bg-gray-700/30 transition-colors border border-gray-100 dark:border-gray-700">
                            <!-- Day Label -->
                            <div class="w-28 flex-shrink-0 flex items-center gap-1.5">
                                <span class="text-sm font-semibold text-gray-700 dark:text-gray-300">{{ day.label
                                    }}</span>
                                <span class="text-xs text-gray-400 dark:text-gray-500">{{ getDayShort(day.value)
                                    }}</span>
                                <!-- ✅ Edited indicator -->
                                <span v-if="isDayDirty(day.value)"
                                    class="w-1.5 h-1.5 rounded-full bg-amber-500 flex-shrink-0"
                                    title="Unsaved changes"></span>
                            </div>

                            <!-- Controls -->
                            <div class="flex flex-wrap items-center gap-3 flex-1">
                                <!-- Closed Toggle -->
                                <label
                                    class="flex items-center gap-1.5 text-sm text-gray-600 dark:text-gray-300 cursor-pointer hover:text-gray-800 dark:hover:text-gray-100 transition-colors">
                                    <input type="checkbox" v-model="getHourData(day.value).is_closed"
                                        @change="toggleClosed(day.value)"
                                        class="w-4 h-4 text-primary-600 focus:ring-primary-500 border-gray-300 dark:border-gray-600 rounded transition-colors" />
                                    <span class="flex items-center gap-1">
                                        <svg class="w-4 h-4"
                                            :class="getHourData(day.value).is_closed ? 'text-gray-400 dark:text-gray-500' : 'text-gray-300 dark:text-gray-600'"
                                            fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M6 18L18 6M6 6l12 12" />
                                        </svg>
                                        Closed
                                    </span>
                                </label>

                                <!-- 24/7 Toggle -->
                                <label
                                    class="flex items-center gap-1.5 text-sm text-gray-600 dark:text-gray-300 cursor-pointer hover:text-gray-800 dark:hover:text-gray-100 transition-colors">
                                    <input type="checkbox" v-model="getHourData(day.value).is_24h"
                                        @change="toggle24h(day.value)"
                                        class="w-4 h-4 text-primary-600 focus:ring-primary-500 border-gray-300 dark:border-gray-600 rounded transition-colors" />
                                    <span class="flex items-center gap-1">
                                        <svg class="w-4 h-4"
                                            :class="getHourData(day.value).is_24h ? 'text-primary-600 dark:text-primary-400' : 'text-gray-300 dark:text-gray-600'"
                                            fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                                        </svg>
                                        24/7
                                    </span>
                                </label>

                                <!-- Time Inputs -->
                                <div class="flex items-center gap-2"
                                    v-if="!getHourData(day.value).is_closed && !getHourData(day.value).is_24h">
                                    <div class="relative">
                                        <div
                                            class="absolute inset-y-0 left-0 pl-2 flex items-center pointer-events-none">
                                            <svg class="w-3.5 h-3.5 text-gray-400 dark:text-gray-500" fill="none"
                                                stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                    d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                                            </svg>
                                        </div>
                                        <input type="time" v-model="getHourData(day.value).opens_at"
                                            class="w-28 pl-7 pr-2 py-1.5 rounded-lg border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-900 text-gray-900 dark:text-white shadow-sm focus:border-primary-500 focus:ring-primary-500 text-sm" />
                                    </div>
                                    <span class="text-gray-400 dark:text-gray-500 text-sm font-medium">to</span>
                                    <div class="relative">
                                        <div
                                            class="absolute inset-y-0 left-0 pl-2 flex items-center pointer-events-none">
                                            <svg class="w-3.5 h-3.5 text-gray-400 dark:text-gray-500" fill="none"
                                                stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                    d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                                            </svg>
                                        </div>
                                        <input type="time" v-model="getHourData(day.value).closes_at"
                                            class="w-28 pl-7 pr-2 py-1.5 rounded-lg border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-900 text-gray-900 dark:text-white shadow-sm focus:border-primary-500 focus:ring-primary-500 text-sm" />
                                    </div>
                                </div>

                                <!-- Status Badge -->
                                <span v-else class="text-sm font-medium"
                                    :class="getHourData(day.value).is_closed ? 'text-gray-400 dark:text-gray-500' : 'text-primary-600 dark:text-primary-400'">
                                    {{ getHourData(day.value).is_closed ? '— Closed' : '🔄 24 Hours' }}
                                </span>
                            </div>
                        </div>

                        <!-- Buttons -->
                        <div
                            class="flex flex-col sm:flex-row justify-end gap-3 pt-6 border-t border-gray-200 dark:border-gray-700">
                            <a :href="`/owner/businesses/${business.id}/locations`"
                                class="inline-flex items-center justify-center px-6 py-2.5 border border-gray-300 dark:border-gray-600 text-gray-700 dark:text-gray-300 rounded-xl hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors font-medium text-sm">
                                Cancel
                            </a>
                            <button type="submit"
                                class="inline-flex items-center justify-center px-6 py-2.5 bg-gradient-to-r from-primary-600 to-primary-700 text-white rounded-xl hover:from-primary-700 hover:to-primary-800 transition-all duration-200 shadow-lg shadow-primary-100 dark:shadow-none hover:shadow-primary-200 font-medium text-sm disabled:opacity-50 disabled:cursor-not-allowed"
                                :disabled="processing">
                                <span v-if="processing" class="flex items-center gap-2">
                                    <svg class="animate-spin h-4 w-4" fill="none" viewBox="0 0 24 24">
                                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor"
                                            stroke-width="4"></circle>
                                        <path class="opacity-75" fill="currentColor"
                                            d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z">
                                        </path>
                                    </svg>
                                    Saving...
                                </span>
                                <span v-else>
                                    <svg class="w-4 h-4 inline mr-2" fill="none" stroke="currentColor"
                                        viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M5 13l4 4L19 7" />
                                    </svg>
                                    Save Hours
                                </span>
                            </button>
                        </div>
                    </form>
                </div>
            </div>

            <!-- ==================== DATE OVERRIDES ==================== -->
            <div
                class="bg-white dark:bg-gray-800 rounded-2xl shadow-sm border border-gray-100 dark:border-gray-700 overflow-hidden">
                <div
                    class="px-6 py-4 border-b border-gray-100 dark:border-gray-700 bg-gradient-to-r from-gray-50 to-white dark:from-gray-800 dark:to-gray-800/50 flex items-center gap-3">
                    <div class="w-9 h-9 rounded-xl bg-amber-50 dark:bg-amber-900/30 flex items-center justify-center">
                        <svg class="w-4 h-4 text-amber-600 dark:text-amber-400" fill="none" stroke="currentColor"
                            viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                        </svg>
                    </div>
                    <div class="flex-1 min-w-0">
                        <h3 class="text-sm font-bold text-gray-900 dark:text-white tracking-tight">Date overrides</h3>
                        <p class="text-xs text-gray-400 dark:text-gray-500">Mark specific days as closed (holidays,
                            events)</p>
                    </div>
                </div>

                <div class="p-6 space-y-4">
                    <!-- Add override form -->
                    <div class="space-y-3">
                        <!-- Mode radio -->
                        <div class="flex flex-wrap items-center gap-2">
                            <label
                                class="inline-flex items-center gap-2 px-3 py-2 rounded-xl border cursor-pointer transition-colors text-sm font-semibold"
                                :class="overrideForm.mode === 'closed'
                                    ? 'bg-red-50 dark:bg-red-900/30 border-red-300 dark:border-red-700 text-red-700 dark:text-red-400'
                                    : 'bg-white dark:bg-gray-900 border-gray-200 dark:border-gray-600 text-gray-600 dark:text-gray-400 hover:bg-gray-50 dark:hover:bg-gray-800'">
                                <input type="radio" value="closed" v-model="overrideForm.mode" class="sr-only" />
                                <span class="w-3.5 h-3.5 rounded-full border-2 flex items-center justify-center"
                                    :class="overrideForm.mode === 'closed' ? 'border-red-600 dark:border-red-400' : 'border-gray-300 dark:border-gray-600'">
                                    <span v-if="overrideForm.mode === 'closed'"
                                        class="w-1.5 h-1.5 rounded-full bg-red-600 dark:bg-red-400"></span>
                                </span>
                                Closed all day
                            </label>
                            <label
                                class="inline-flex items-center gap-2 px-3 py-2 rounded-xl border cursor-pointer transition-colors text-sm font-semibold"
                                :class="overrideForm.mode === 'special'
                                    ? 'bg-amber-50 dark:bg-amber-900/30 border-amber-300 dark:border-amber-700 text-amber-700 dark:text-amber-400'
                                    : 'bg-white dark:bg-gray-900 border-gray-200 dark:border-gray-600 text-gray-600 dark:text-gray-400 hover:bg-gray-50 dark:hover:bg-gray-800'">
                                <input type="radio" value="special" v-model="overrideForm.mode" class="sr-only" />
                                <span class="w-3.5 h-3.5 rounded-full border-2 flex items-center justify-center"
                                    :class="overrideForm.mode === 'special' ? 'border-amber-600 dark:border-amber-400' : 'border-gray-300 dark:border-gray-600'">
                                    <span v-if="overrideForm.mode === 'special'"
                                        class="w-1.5 h-1.5 rounded-full bg-amber-600 dark:bg-amber-400"></span>
                                </span>
                                Special hours
                            </label>
                        </div>

                        <div class="grid grid-cols-1 sm:grid-cols-12 gap-3">
                            <div :class="overrideForm.mode === 'special' ? 'sm:col-span-3' : 'sm:col-span-5'">
                                <label
                                    class="block text-[10px] font-bold text-gray-500 dark:text-gray-400 uppercase tracking-widest mb-1.5">
                                    Date
                                </label>
                                <input type="date" v-model="overrideForm.date" :min="minDate"
                                    class="w-full px-4 py-2.5 border border-gray-200 dark:border-gray-600 bg-white dark:bg-gray-900 text-gray-900 dark:text-white rounded-xl focus:outline-none focus:ring-2 focus:ring-primary-500 focus:border-transparent transition-colors text-sm" />
                            </div>

                            <template v-if="overrideForm.mode === 'special'">
                                <div class="sm:col-span-2">
                                    <label
                                        class="block text-[10px] font-bold text-gray-500 dark:text-gray-400 uppercase tracking-widest mb-1.5">
                                        Opens
                                    </label>
                                    <input type="time" v-model="overrideForm.opens_at"
                                        class="w-full px-3 py-2.5 border border-gray-200 dark:border-gray-600 bg-white dark:bg-gray-900 text-gray-900 dark:text-white rounded-xl focus:outline-none focus:ring-2 focus:ring-primary-500 focus:border-transparent transition-colors text-sm" />
                                </div>
                                <div class="sm:col-span-2">
                                    <label
                                        class="block text-[10px] font-bold text-gray-500 dark:text-gray-400 uppercase tracking-widest mb-1.5">
                                        Closes
                                    </label>
                                    <input type="time" v-model="overrideForm.closes_at"
                                        class="w-full px-3 py-2.5 border border-gray-200 dark:border-gray-600 bg-white dark:bg-gray-900 text-gray-900 dark:text-white rounded-xl focus:outline-none focus:ring-2 focus:ring-primary-500 focus:border-transparent transition-colors text-sm" />
                                </div>
                            </template>

                            <div :class="overrideForm.mode === 'special' ? 'sm:col-span-3' : 'sm:col-span-5'">
                                <label
                                    class="block text-[10px] font-bold text-gray-500 dark:text-gray-400 uppercase tracking-widest mb-1.5">
                                    Note <span
                                        class="text-gray-400 dark:text-gray-500 normal-case text-[10px]">(optional)</span>
                                </label>
                                <input type="text" v-model="overrideForm.note" maxlength="100"
                                    placeholder="e.g., Christmas Eve"
                                    class="w-full px-4 py-2.5 border border-gray-200 dark:border-gray-600 bg-white dark:bg-gray-900 text-gray-900 dark:text-white placeholder-gray-400 dark:placeholder-gray-500 rounded-xl focus:outline-none focus:ring-2 focus:ring-primary-500 focus:border-transparent transition-colors text-sm" />
                            </div>

                            <div class="sm:col-span-2 flex items-end">
                                <button type="button" @click="addOverride"
                                    :disabled="overrideProcessing || !overrideForm.date"
                                    class="w-full inline-flex items-center justify-center gap-2 px-4 py-2.5 bg-gradient-to-r from-amber-500 to-orange-500 text-white rounded-xl font-semibold text-sm hover:from-amber-600 hover:to-orange-600 transition-all shadow-lg shadow-amber-500/25 disabled:opacity-50 disabled:cursor-not-allowed">
                                    <svg v-if="overrideProcessing" class="w-4 h-4 animate-spin" fill="none"
                                        stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                                    </svg>
                                    <svg v-else class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M12 4v16m8-8H4" />
                                    </svg>
                                    Add
                                </button>
                            </div>
                        </div>
                    </div>

                    <!-- Existing overrides -->
                    <div v-if="hasOverrides" class="pt-3 border-t border-gray-100 dark:border-gray-700">
                        <p
                            class="text-[10px] font-bold text-gray-500 dark:text-gray-400 uppercase tracking-widest mb-3">
                            Upcoming ({{ overrides.length }})
                        </p>
                        <div class="space-y-2">
                            <div v-for="override in overrides" :key="override.id"
                                class="flex items-center gap-3 p-3 bg-gray-50 dark:bg-gray-900/50 rounded-xl border border-gray-100 dark:border-gray-700 hover:bg-gray-100/70 dark:hover:bg-gray-900/70 transition-colors">
                                <div
                                    class="w-9 h-9 rounded-lg bg-amber-100 dark:bg-amber-900/30 flex items-center justify-center flex-shrink-0">
                                    <svg class="w-4 h-4 text-amber-600 dark:text-amber-400" fill="none"
                                        stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                                    </svg>
                                </div>
                                <div class="flex-1 min-w-0">
                                    <div class="flex items-center gap-2 flex-wrap">
                                        <p class="text-sm font-bold text-gray-900 dark:text-white">{{
                                            override.formatted_date }}
                                        </p>
                                        <span v-if="override.is_special_hours"
                                            class="text-[10px] font-bold uppercase tracking-wide px-2 py-0.5 rounded-full bg-amber-100 dark:bg-amber-900/30 text-amber-700 dark:text-amber-400">
                                            {{ override.formatted_hours }}
                                        </span>
                                        <span v-else
                                            class="text-[10px] font-bold uppercase tracking-wide px-2 py-0.5 rounded-full bg-red-100 dark:bg-red-900/30 text-red-700 dark:text-red-400">
                                            Closed
                                        </span>
                                    </div>
                                    <p v-if="override.note"
                                        class="text-xs text-gray-500 dark:text-gray-400 truncate mt-0.5">
                                        {{ override.note }}
                                    </p>
                                </div>
                                <button type="button" @click="removeOverride(override)"
                                    class="w-8 h-8 flex items-center justify-center text-red-500 dark:text-red-400 hover:text-red-700 dark:hover:text-red-300 hover:bg-red-50 dark:hover:bg-red-900/30 rounded-lg transition-colors flex-shrink-0"
                                    title="Remove override">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                    </svg>
                                </button>
                            </div>
                        </div>
                    </div>

                    <div v-else class="pt-3 border-t border-gray-100 dark:border-gray-700 text-center py-4">
                        <p class="text-xs text-gray-400 dark:text-gray-500">
                            No upcoming overrides. Add one above to mark a specific day as closed.
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>

<script setup>
    import { ref, reactive, onMounted, computed } from 'vue';
    import { router } from '@inertiajs/vue3';
    import { useToast } from '@/composables/useToast';
    import axios from 'axios';
    import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
    import PageHeader from '@/Components/PageHeader.vue';
    import { useConfirm } from '@/composables/useConfirm';

    const props = defineProps({
        business: {
            type: Object,
            required: true
        },
                location: {
            type: Object,
            required: true
        },
        hours: {
            type: Array,
            default: () => []
        },
        days: {
            type: Array,
            required: true
        },
        overrides: {
            type: Array,
            default: () => []
        }
    });


    const { success, error } = useToast();
    const { confirm: confirmDialog } = useConfirm();
    const processing = ref(false);
    const isOpen = ref(false);

    // ============== OVERRIDES ==============
    const overrideForm = reactive({
        date: '',
        mode: 'closed',   // 'closed' | 'special'
        opens_at: '',
        closes_at: '',
        note: '',
    });
    const overrideProcessing = ref(false);

    const hasOverrides = computed(() => props.overrides && props.overrides.length > 0);

    const minDate = computed(() => {
        const t = new Date();
        const pad = (n) => n.toString().padStart(2, '0');
        return `${t.getFullYear()}-${pad(t.getMonth() + 1)}-${pad(t.getDate())}`;
    });

    const addOverride = () => {
        if (!overrideForm.date) {
            error('Date required', 'Please pick a date.', { duration: 3000 });
            return;
        }

        if (overrideForm.mode === 'special') {
            if (!overrideForm.opens_at || !overrideForm.closes_at) {
                error('Times required', 'Please set both opening and closing times.', { duration: 3000 });
                return;
            }
        }

        overrideProcessing.value = true;

        router.post(
            `/owner/businesses/${props.business.id}/locations/${props.location.id}/hours/overrides`,
            {
                date: overrideForm.date,
                mode: overrideForm.mode,
                opens_at: overrideForm.mode === 'special' ? overrideForm.opens_at : null,
                closes_at: overrideForm.mode === 'special' ? overrideForm.closes_at : null,
                note: overrideForm.note || null,
            },
            {
                preserveScroll: true,
                onSuccess: () => {
                    const msg = overrideForm.mode === 'special'
                        ? 'Special hours added for this date.'
                        : 'This date will show as closed.';
                    success('Override Added 📅', msg, { duration: 3000 });
                    overrideForm.date = '';
                    overrideForm.mode = 'closed';
                    overrideForm.opens_at = '';
                    overrideForm.closes_at = '';
                    overrideForm.note = '';
                },
                onError: () => {
                    error('Failed ❌', 'Could not add override. Please try again.', { duration: 4000 });
                },
                onFinish: () => {
                    overrideProcessing.value = false;
                },
            }
        );
    };

    const removeOverride = async (override) => {
        const confirmed = await confirmDialog({
            title: 'Remove this override?',
            message: `"${override.formatted_date}"${override.note ? ` (${override.note})` : ''} will be removed and normal weekly hours will apply on that date.`,
            confirmText: 'Remove',
            cancelText: 'Cancel',
            variant: 'warning',
        });

        if (!confirmed) return;

        router.delete(
            `/owner/businesses/${props.business.id}/locations/${props.location.id}/hours/overrides/${override.id}`,
            {
                preserveScroll: true,
                onSuccess: () => {
                    success('Override Removed', 'Normal hours apply again.', { duration: 3000 });
                },
                onError: () => {
                    error('Failed ❌', 'Could not remove override.', { duration: 4000 });
                },
            }
        );
    };

    // Initialize hour data with proper defaults
    const hourData = reactive({});

    // ✅ Snapshot of the initial state, used for dirty tracking
    const originalHourData = reactive({});

    const snapshotHourData = () => {
        Object.keys(hourData).forEach((key) => {
            originalHourData[key] = { ...hourData[key] };
        });
    };

    // Initialize days with default values
    const initializeHourData = () => {
        props.days.forEach(day => {
            const existing = props.hours.find(h => h.day_of_week === day.value);
            const initial = {
                day_of_week: day.value,
                opens_at: existing?.opens_at || '',
                closes_at: existing?.closes_at || '',
                is_closed: existing?.is_closed || false,
                is_24h: existing?.is_24h || false,
                sort_order: existing?.sort_order || 0,
            };
            hourData[day.value] = { ...initial };
            originalHourData[day.value] = { ...initial };
        });
    };

    // ✅ Per-day dirty check — compares current vs snapshot
    const isDayDirty = (dayValue) => {
        const current = hourData[dayValue];
        const original = originalHourData[dayValue];
        if (!current || !original) return false;
        return (
            (current.opens_at || '') !== (original.opens_at || '') ||
            (current.closes_at || '') !== (original.closes_at || '') ||
            !!current.is_closed !== !!original.is_closed ||
            !!current.is_24h !== !!original.is_24h
        );
    };

    const dirtyDays = computed(() =>
        props.days
            .map((d) => d.value)
            .filter((v) => isDayDirty(v))
    );

    const hasUnsavedChanges = computed(() => dirtyDays.value.length > 0);

    // Get hour data with fallback
    const getHourData = (dayValue) => {
        if (!hourData[dayValue]) {
            const existing = props.hours.find(h => h.day_of_week === dayValue);
            hourData[dayValue] = {
                day_of_week: dayValue,
                opens_at: existing?.opens_at || '',
                closes_at: existing?.closes_at || '',
                is_closed: existing?.is_closed || false,
                is_24h: existing?.is_24h || false,
                sort_order: existing?.sort_order || 0,
            };
        }
        return hourData[dayValue];
    };

    const getDayShort = (day) => {
        const shorts = { 0: 'Sun', 1: 'Mon', 2: 'Tue', 3: 'Wed', 4: 'Thu', 5: 'Fri', 6: 'Sat' };
        return shorts[day] || '';
    };

    // Apply current settings to all days
    const setAllDays = () => {
        const currentDay = 1; // Monday
        const currentData = getHourData(currentDay);

        if (confirm('Apply Monday\'s settings to all days of the week?')) {
            props.days.forEach(day => {
                if (day.value !== currentDay) {
                    const data = getHourData(day.value);
                    data.is_closed = currentData.is_closed;
                    data.is_24h = currentData.is_24h;
                    data.opens_at = currentData.opens_at;
                    data.closes_at = currentData.closes_at;
                }
            });
            success('Settings Applied! 📋', 'Monday\'s settings have been applied to all days.', { duration: 3000 });
        }
    };

    onMounted(() => {
        initializeHourData();
        checkOpenStatus();
    });

    const checkOpenStatus = async () => {
        try {
            const url = `/owner/businesses/${props.business.id}/locations/${props.location.id}/hours/status`;
            const response = await axios.get(url);
            isOpen.value = response.data.is_open;
        } catch (err) {
            console.error('Error checking status:', err);
        }
    };

    const toggleClosed = (day) => {
        const data = getHourData(day);
        if (data.is_closed) {
            data.is_24h = false;
            data.opens_at = '';
            data.closes_at = '';
        }
    };

    const toggle24h = (day) => {
        const data = getHourData(day);
        if (data.is_24h) {
            data.is_closed = false;
            data.opens_at = '';
            data.closes_at = '';
        }
    };

    const saveHours = () => {
        // ✅ Nothing to save? No request at all.
        if (!hasUnsavedChanges.value) {
            success('No changes to save', 'You haven\'t modified any days.', { duration: 2500 });
            return;
        }

        processing.value = true;

        // ✅ Send ONLY the days that actually changed
        const hoursToSave = dirtyDays.value.map((v) => ({
            day_of_week: hourData[v].day_of_week,
            opens_at: hourData[v].opens_at || null,
            closes_at: hourData[v].closes_at || null,
            is_closed: hourData[v].is_closed,
            is_24h: hourData[v].is_24h,
            sort_order: hourData[v].sort_order || 0,
        }));

        const url = `/owner/businesses/${props.business.id}/locations/${props.location.id}/hours/batch`;

        router.post(url, { hours: hoursToSave }, {
            preserveScroll: true,
            // ✅ No success toast here — the controller flashes
            //    'Hours saved successfully.' and AuthenticatedLayout
            //    shows it once. One request, one toast.
            onSuccess: () => {
                // Re-snapshot so the dirty indicators clear
                snapshotHourData();
                checkOpenStatus();
            },
            onError: () => {
                error('Save Failed ❌', 'Failed to save hours. Please try again.', { duration: 4000 });
            },
            onFinish: () => {
                processing.value = false;
            },
        });
    };
</script>

<style scoped>
    input[type="time"]::-webkit-calendar-picker-indicator {
        opacity: 0.5;
        padding: 4px;
    }

    input[type="time"]::-webkit-calendar-picker-indicator:hover {
        opacity: 1;
    }
</style>