<!-- resources/js/Pages/Admin/Reviews/Index.vue -->
<template>
    <AuthenticatedLayout>
        <!-- COMPACT HEADER -->
        <PageHeader color="amber" :breadcrumb="[
            { label: 'Admin', href: '/admin/dashboard' },
            { label: 'Reviews' }
        ]">
            <template #icon>
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                    d="M11.049 2.927c.3-.921 1.603-.921 1.902 0l1.519 4.674a1 1 0 00.95.69h4.915c.969 0 1.371 1.24.588 1.81l-3.976 2.888a1 1 0 00-.363 1.118l1.518 4.674c.3.922-.755 1.688-1.538 1.118l-3.976-2.888a1 1 0 00-1.176 0l-3.976 2.888c-.783.57-1.838-.197-1.538-1.118l1.518-4.674a1 1 0 00-.363-1.118l-3.976-2.888c-.784-.57-.38-1.81.588-1.81h4.914a1 1 0 00.951-.69l1.519-4.674z" />
            </template>
            <template #title>Reviews management</template>
            <template #subtitle>Moderate customer reviews across the platform</template>
            <template #actions>
                <!-- Export dropdown -->
                <div class="relative" ref="exportDropdown">
                    <button @click="toggleExportDropdown"
                        class="inline-flex items-center gap-2 px-4 py-2 border border-gray-300 dark:border-gray-600 text-gray-700 dark:text-gray-300 rounded-xl hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors text-sm font-semibold">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4" />
                        </svg>
                        Export
                        <svg class="w-4 h-4 transition-transform" :class="{ 'rotate-180': showExportDropdown }"
                            fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                        </svg>
                    </button>

                    <!-- Dropdown -->
                    <div v-if="showExportDropdown"
                        class="absolute right-0 mt-2 w-60 bg-white dark:bg-gray-800 rounded-2xl shadow-2xl border border-gray-100 dark:border-gray-700 z-50 py-1 overflow-hidden">
                        <div
                            class="px-4 py-2 bg-gray-50 dark:bg-gray-900/50 text-[10px] font-bold text-gray-500 dark:text-gray-400 uppercase tracking-widest border-b border-gray-100 dark:border-gray-700">
                            Export all
                        </div>
                        <button @click="exportReviews('pdf', false)"
                            class="w-full text-left px-4 py-2.5 text-sm text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700/50 transition-colors flex items-center gap-3 border-b border-gray-50 dark:border-gray-700/50">
                            <svg class="w-4 h-4 text-red-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                            </svg>
                            <span>Export all as PDF</span>
                        </button>
                        <button @click="exportReviews('csv', false)"
                            class="w-full text-left px-4 py-2.5 text-sm text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700/50 transition-colors flex items-center gap-3 border-b border-gray-50 dark:border-gray-700/50">
                            <svg class="w-4 h-4 text-emerald-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M9 17V7m0 10a2 2 0 01-2 2H5a2 2 0 01-2-2V7a2 2 0 012-2h2a2 2 0 012 2m0 10a2 2 0 002 2h2a2 2 0 002-2M9 7a2 2 0 012-2h2a2 2 0 012 2m0 10V7m0 10a2 2 0 002 2h2a2 2 0 002-2V7a2 2 0 00-2-2h-2a2 2 0 00-2 2" />
                            </svg>
                            <span>Export all as CSV</span>
                        </button>
                        <button @click="exportReviews('json', false)"
                            class="w-full text-left px-4 py-2.5 text-sm text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700/50 transition-colors flex items-center gap-3 border-b border-gray-50 dark:border-gray-700/50">
                            <svg class="w-4 h-4 text-blue-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M4 7v10c0 2.21 3.582 4 8 4s8-1.79 8-4V7M4 7c0 2.21 3.582 4 8 4s8-1.79 8-4M4 7c0-2.21 3.582-4 8-4s8 1.79 8 4m0 5c0 2.21-3.582 4-8 4s-8-1.79-8-4" />
                            </svg>
                            <span>Export all as JSON</span>
                        </button>

                        <div
                            class="px-4 py-2 bg-gray-50 dark:bg-gray-900/50 text-[10px] font-bold text-gray-500 dark:text-gray-400 uppercase tracking-widest border-b border-gray-100 dark:border-gray-700">
                            Export filtered
                        </div>
                        <button @click="exportReviews('pdf', true)"
                            class="w-full text-left px-4 py-2.5 text-sm text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700/50 transition-colors flex items-center gap-3 border-b border-gray-50 dark:border-gray-700/50">
                            PDF (current filters)
                        </button>
                        <button @click="exportReviews('csv', true)"
                            class="w-full text-left px-4 py-2.5 text-sm text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700/50 transition-colors flex items-center gap-3 border-b border-gray-50 dark:border-gray-700/50">
                            CSV (current filters)
                        </button>
                        <button @click="exportReviews('json', true)"
                            class="w-full text-left px-4 py-2.5 text-sm text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700/50 transition-colors flex items-center gap-3">
                            JSON (current filters)
                        </button>
                    </div>
                </div>

                <!-- Refresh -->
                <button @click="refreshReviews"
                    class="inline-flex items-center gap-2 px-4 py-2 bg-gradient-to-r from-primary-600 to-primary-700 text-white rounded-xl font-semibold hover:from-primary-700 hover:to-primary-800 transition-all shadow-lg shadow-primary-500/25 text-sm">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                    </svg>
                    Refresh
                </button>
            </template>
        </PageHeader>

        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8 space-y-6">

            <!-- STATS -->
            <div class="grid grid-cols-2 sm:grid-cols-4 gap-4">
                <div class="bg-white dark:bg-gray-800 rounded-2xl border border-gray-100 dark:border-gray-700 p-4 hover:shadow-md transition-shadow">
                    <p class="text-[10px] font-bold text-gray-400 dark:text-gray-500 uppercase tracking-widest">Total</p>
                    <p class="text-2xl font-bold text-gray-900 dark:text-white tracking-tight mt-1">{{ getStatusCount('total') }}</p>
                </div>
                <div class="bg-white dark:bg-gray-800 rounded-2xl border border-gray-100 dark:border-gray-700 p-4 hover:shadow-md transition-shadow">
                    <p class="text-[10px] font-bold text-gray-400 dark:text-gray-500 uppercase tracking-widest">Approved</p>
                    <p class="text-2xl font-bold text-emerald-600 dark:text-emerald-400 tracking-tight mt-1">{{ getStatusCount('approved') }}
                    </p>
                </div>
                <div class="bg-white dark:bg-gray-800 rounded-2xl border border-gray-100 dark:border-gray-700 p-4 hover:shadow-md transition-shadow">
                    <p class="text-[10px] font-bold text-gray-400 dark:text-gray-500 uppercase tracking-widest">Pending</p>
                    <p class="text-2xl font-bold text-amber-600 dark:text-amber-400 tracking-tight mt-1">{{ getStatusCount('pending') }}</p>
                </div>
                <div class="bg-white dark:bg-gray-800 rounded-2xl border border-gray-100 dark:border-gray-700 p-4 hover:shadow-md transition-shadow">
                    <p class="text-[10px] font-bold text-gray-400 dark:text-gray-500 uppercase tracking-widest">Rejected</p>
                    <p class="text-2xl font-bold text-red-600 dark:text-red-400 tracking-tight mt-1">{{ getStatusCount('rejected') }}</p>
                </div>
            </div>

            <!-- FILTERS -->
            <div class="bg-white dark:bg-gray-800 rounded-2xl border border-gray-100 dark:border-gray-700 p-5">
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
                    <div>
                        <label
                            class="block text-[10px] font-bold text-gray-400 dark:text-gray-500 uppercase tracking-widest mb-2">Search</label>
                        <div class="relative">
                            <div class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none">
                                <svg class="h-4 w-4 text-gray-400 dark:text-gray-500" fill="none" stroke="currentColor"
                                    viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                                </svg>
                            </div>
                            <input type="text" v-model="filters.search" @input="applyFilters"
                                placeholder="Search reviews…"
                                class="w-full pl-11 pr-4 py-2.5 border border-gray-200 dark:border-gray-600 rounded-xl bg-white dark:bg-gray-900 text-gray-900 dark:text-white placeholder-gray-400 dark:placeholder-gray-500 focus:outline-none focus:ring-2 focus:ring-primary-500 focus:border-transparent transition-colors text-sm" />
                        </div>
                    </div>
                    <div>
                        <label
                            class="block text-[10px] font-bold text-gray-400 dark:text-gray-500 uppercase tracking-widest mb-2">Status</label>
                        <select v-model="filters.status" @change="applyFilters"
                            class="w-full px-4 py-2.5 border border-gray-200 dark:border-gray-600 rounded-xl bg-white dark:bg-gray-900 text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-primary-500 focus:border-transparent transition-colors text-sm appearance-none">
                            <option value="">All status</option>
                            <option value="pending">Pending</option>
                            <option value="approved">Approved</option>
                            <option value="rejected">Rejected</option>
                        </select>
                    </div>
                    <div>
                        <label
                            class="block text-[10px] font-bold text-gray-400 dark:text-gray-500 uppercase tracking-widest mb-2">Rating</label>
                        <select v-model="filters.rating" @change="applyFilters"
                            class="w-full px-4 py-2.5 border border-gray-200 dark:border-gray-600 rounded-xl bg-white dark:bg-gray-900 text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-primary-500 focus:border-transparent transition-colors text-sm appearance-none">
                            <option value="">All ratings</option>
                            <option value="5">5 stars</option>
                            <option value="4">4 stars</option>
                            <option value="3">3 stars</option>
                            <option value="2">2 stars</option>
                            <option value="1">1 star</option>
                        </select>
                    </div>
                    <div>
                        <label
                            class="block text-[10px] font-bold text-gray-400 dark:text-gray-500 uppercase tracking-widest mb-2">Date</label>
                        <input type="date" v-model="filters.date" @change="applyFilters"
                            class="w-full px-4 py-2.5 border border-gray-200 dark:border-gray-600 rounded-xl bg-white dark:bg-gray-900 text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-primary-500 focus:border-transparent transition-colors text-sm" />
                    </div>
                </div>
            </div>

            <!-- TABLE -->
            <div class="bg-white dark:bg-gray-800 rounded-2xl border border-gray-100 dark:border-gray-700 overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                        <thead class="bg-gray-50 dark:bg-gray-900/50">
                            <tr>
                                <th
                                    class="px-6 py-4 text-left text-[10px] font-bold text-gray-500 dark:text-gray-400 uppercase tracking-widest">
                                    Reviewer</th>
                                <th
                                    class="px-6 py-4 text-left text-[10px] font-bold text-gray-500 dark:text-gray-400 uppercase tracking-widest">
                                    Business</th>
                                <th
                                    class="px-6 py-4 text-left text-[10px] font-bold text-gray-500 dark:text-gray-400 uppercase tracking-widest">
                                    Rating</th>
                                <th
                                    class="px-6 py-4 text-left text-[10px] font-bold text-gray-500 dark:text-gray-400 uppercase tracking-widest">
                                    Review</th>
                                <th
                                    class="px-6 py-4 text-left text-[10px] font-bold text-gray-500 dark:text-gray-400 uppercase tracking-widest">
                                    Status</th>
                                <th
                                    class="px-6 py-4 text-left text-[10px] font-bold text-gray-500 dark:text-gray-400 uppercase tracking-widest">
                                    Date</th>
                                <th
                                    class="px-6 py-4 text-right text-[10px] font-bold text-gray-500 dark:text-gray-400 uppercase tracking-widest">
                                    Actions</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                            <tr v-for="review in reviews.data" :key="review.id"
                                class="transition-colors"
                                :class="review.status === 'pending'
                                    ? 'bg-amber-50/30 dark:bg-amber-900/10 hover:bg-amber-50/60 dark:hover:bg-amber-900/20'
                                    : 'hover:bg-gray-50/50 dark:hover:bg-gray-700/30'">
                                <td class="px-6 py-4">
                                    <div class="flex items-center gap-3 min-w-0">
                                        <div
                                            class="w-10 h-10 rounded-2xl bg-gradient-to-br from-primary-500 to-primary-700 flex items-center justify-center text-white text-xs font-bold flex-shrink-0 shadow-md">
                                            {{ getInitials(review.user?.name || 'Anonymous') }}
                                        </div>
                                        <div class="min-w-0">
                                            <p class="text-sm font-bold text-gray-900 dark:text-white truncate tracking-tight">
                                                {{ review.user?.name || 'Anonymous' }}
                                            </p>
                                            <p class="text-xs text-gray-500 dark:text-gray-400 truncate">{{ review.user?.email || '' }}</p>
                                        </div>
                                    </div>
                                </td>
                                <td class="px-6 py-4">
                                    <p class="text-sm font-semibold text-gray-900 dark:text-white truncate">{{ review.business?.name ||
                                        'Unknown' }}</p>
                                    <p class="text-xs text-gray-400 dark:text-gray-500 truncate">{{ review.business?.category?.name || ''
                                    }}</p>
                                </td>
                                <td class="px-6 py-4">
                                    <div class="flex items-center gap-1">
                                        <span class="text-amber-400">★</span>
                                        <span class="text-sm font-bold text-gray-900 dark:text-white">{{ review.rating }}</span>
                                        <span class="text-xs text-gray-400 dark:text-gray-500">/5</span>
                                    </div>
                                </td>
                                <td class="px-6 py-4 max-w-xs">
                                    <p class="text-sm text-gray-600 dark:text-gray-300 truncate">{{ review.content || 'No review content'
                                    }}</p>
                                    <p v-if="review.reply"
                                        class="text-xs text-blue-600 dark:text-blue-400 mt-1 flex items-center gap-1 truncate">
                                        <svg class="w-3 h-3 flex-shrink-0" fill="none" stroke="currentColor"
                                            viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M3 10h10a8 8 0 018 8v2M3 10l6 6m-6-6l6-6" />
                                        </svg>
                                        {{ review.reply }}
                                    </p>
                                </td>
                                <td class="px-6 py-4">
                                    <span :class="getStatusClass(review.status)">
                                        {{ review.status.charAt(0).toUpperCase() + review.status.slice(1) }}
                                    </span>
                                </td>
                                <td class="px-6 py-4 text-sm text-gray-500 dark:text-gray-400 whitespace-nowrap">
                                    {{ formatDate(review.created_at) }}
                                </td>
                                <td class="px-6 py-4">
                                    <div class="flex flex-wrap gap-1.5 justify-end">
                                        <a :href="`/admin/reviews/${review.id}`"
                                            class="inline-flex items-center gap-1.5 px-3 py-1.5 bg-blue-50 dark:bg-blue-900/30 text-blue-700 dark:text-blue-400 text-xs font-bold uppercase tracking-wide rounded-lg hover:bg-blue-100 dark:hover:bg-blue-900/50 transition-colors">
                                            View
                                        </a>
                                        <button v-if="review.status === 'pending'" @click="approveReview(review)"
                                            class="inline-flex items-center gap-1.5 px-3 py-1.5 bg-emerald-50 dark:bg-emerald-900/30 text-emerald-700 dark:text-emerald-400 text-xs font-bold uppercase tracking-wide rounded-lg hover:bg-emerald-100 dark:hover:bg-emerald-900/50 transition-colors">
                                            Approve
                                        </button>
                                        <button v-if="review.status === 'pending'" @click="rejectReview(review)"
                                            class="inline-flex items-center gap-1.5 px-3 py-1.5 bg-amber-50 dark:bg-amber-900/30 text-amber-700 dark:text-amber-400 text-xs font-bold uppercase tracking-wide rounded-lg hover:bg-amber-100 dark:hover:bg-amber-900/50 transition-colors">
                                            Reject
                                        </button>
                                        <button @click="deleteReview(review)"
                                            class="inline-flex items-center gap-1.5 px-3 py-1.5 bg-red-50 dark:bg-red-900/30 text-red-700 dark:text-red-400 text-xs font-bold uppercase tracking-wide rounded-lg hover:bg-red-100 dark:hover:bg-red-900/50 transition-colors">
                                            Delete
                                        </button>
                                    </div>
                                </td>
                            </tr>
                            <tr v-if="!reviews.data || reviews.data.length === 0">
                                <td colspan="7" class="px-6 py-16 text-center">
                                    <div
                                        class="w-20 h-20 mx-auto mb-4 rounded-2xl bg-gradient-to-br from-amber-100 to-amber-50 dark:from-amber-900/30 dark:to-amber-900/10 flex items-center justify-center">
                                        <svg class="w-10 h-10 text-amber-500 dark:text-amber-400" fill="currentColor" viewBox="0 0 20 20">
                                            <path
                                                d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z" />
                                        </svg>
                                    </div>
                                    <p class="text-gray-600 dark:text-gray-300 font-bold tracking-tight">No reviews found</p>
                                    <p class="text-sm text-gray-400 dark:text-gray-500 mt-1">Try adjusting your filters</p>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <!-- Pagination -->
                <div v-if="reviews.links && reviews.links.length > 3" class="px-6 py-4 border-t border-gray-100 dark:border-gray-700">
                    <Pagination :links="reviews.links" />
                </div>
            </div>
        </div>

    </AuthenticatedLayout>
</template>


<script setup>
    import { ref, reactive, computed, onMounted, onUnmounted } from 'vue';
    import { router, usePage } from '@inertiajs/vue3';
    import { useToast } from '@/composables/useToast';
    import { useConfirm } from '@/composables/useConfirm';
    import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
    import Pagination from '@/Components/Pagination.vue';
    import PageHeader from '@/Components/PageHeader.vue';

    const page = usePage();
    const { error } = useToast();
    const { confirm: confirmDialog } = useConfirm();

    const showExportDropdown = ref(false);
    const exportDropdown = ref(null);

    // ✅ Read directly from page props — reactive, no manual sync needed.
    //    Combined with preserveState: false on router.get() below, the
    //    component re-renders with fresh data on every filter change.
    const reviews = computed(() => page.props.reviews || { data: [] });

    const filters = reactive({
        search: page.props.filters?.search || '',
        status: page.props.filters?.status || '',
        rating: page.props.filters?.rating || '',
        date: page.props.filters?.date || '',
    });

    const getStatusCount = (status) => {
        if (!reviews.value?.data) return 0;
        if (status === 'total') return reviews.value.data.length;
        return reviews.value.data.filter(r => r.status === status).length;
    };

    const toggleExportDropdown = () => {
        showExportDropdown.value = !showExportDropdown.value;
    };

    const handleClickOutside = (event) => {
        if (exportDropdown.value && !exportDropdown.value.contains(event.target)) {
            showExportDropdown.value = false;
        }
    };

    const exportReviews = async (format = 'csv', useFilters = false) => {
        try {
            let url;

            if (format === 'pdf') {
                url = useFilters
                    ? `/admin/reviews/export/pdf?${new URLSearchParams(filters).toString()}`
                    : '/admin/reviews/export/pdf';
            } else if (format === 'csv') {
                url = useFilters
                    ? `/admin/reviews/export/csv?${new URLSearchParams(filters).toString()}`
                    : '/admin/reviews/export/csv';
            } else if (format === 'json') {
                url = useFilters
                    ? `/admin/reviews/export/json?${new URLSearchParams(filters).toString()}`
                    : '/admin/reviews/export/json';
            } else {
                url = useFilters
                    ? `/admin/reviews/export?${new URLSearchParams(filters).toString()}`
                    : '/admin/reviews/export';
            }

            if (format === 'pdf') {
                window.open(url, '_blank');
                showExportDropdown.value = false;
                return;
            }

            const response = await fetch(url, {
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                }
            });

            if (!response.ok) {
                const errorData = await response.json();
                throw new Error(errorData.error || 'Export failed');
            }

            const blob = await response.blob();
            const link = document.createElement('a');
            link.href = URL.createObjectURL(blob);

            const contentDisposition = response.headers.get('Content-Disposition');
            let filename = `reviews_export_${Date.now()}.${format}`;

            if (contentDisposition) {
                const matches = /filename="([^"]+)"/.exec(contentDisposition);
                if (matches && matches[1]) {
                    filename = matches[1];
                }
            }

            link.download = filename;
            document.body.appendChild(link);
            link.click();
            document.body.removeChild(link);
            URL.revokeObjectURL(link.href);

        } catch (err) {
            console.error('Export error:', err);
            error('Export Failed ❌', 'Failed to export reviews: ' + err.message, { duration: 5000 });
        }

        showExportDropdown.value = false;
    };

    const applyFilters = () => {
        router.get('/admin/reviews', filters, {
            preserveScroll: true,
            // ✅ No preserveState — component re-renders with fresh props.
            //    Removed the manual onSuccess sync since usePage() is reactive.
        });
    };

    const approveReview = async (review) => {
        const confirmed = await confirmDialog({
            title: 'Approve review?',
            message: 'This review will become publicly visible on the business profile.',
            confirmText: 'Approve',
            cancelText: 'Cancel',
            variant: 'primary',
        });

        if (!confirmed) return;

        router.post(`/admin/reviews/${review.id}/approve`, {}, {
            preserveScroll: true,
            // ✅ No success toast — the controller flashes
            //    'Review approved successfully.'
            onError: () => {
                error('Approval Failed ❌', 'Failed to approve the review. Please try again.', { duration: 4000 });
            },
        });
    };

    const rejectReview = async (review) => {
        const confirmed = await confirmDialog({
            title: 'Reject review?',
            message: 'This review will be hidden from the public. The reviewer will be notified.',
            confirmText: 'Reject',
            cancelText: 'Cancel',
            variant: 'danger',
        });

        if (!confirmed) return;

        router.post(`/admin/reviews/${review.id}/reject`, {}, {
            preserveScroll: true,
            // ✅ No success toast — the controller flashes
            //    'Review rejected successfully.'
            onError: () => {
                error('Rejection Failed ❌', 'Failed to reject the review. Please try again.', { duration: 4000 });
            },
        });
    };

    const deleteReview = async (review) => {
        const confirmed = await confirmDialog({
            title: 'Delete review?',
            message: 'This review will be permanently deleted. This action cannot be undone.',
            confirmText: 'Delete',
            cancelText: 'Cancel',
            variant: 'danger',
        });

        if (!confirmed) return;

        router.delete(`/admin/reviews/${review.id}`, {
            preserveScroll: true,
            // ✅ No success toast — the controller flashes
            //    'Review deleted successfully.'
            onError: () => {
                error('Delete Failed ❌', 'Failed to delete the review. Please try again.', { duration: 4000 });
            },
        });
    };

    const getInitials = (name) => {
        if (!name) return 'U';
        return name.split(' ').map(n => n[0]).join('').toUpperCase().slice(0, 2);
    };

    const getStatusClass = (status) => {
        const classes = {
            pending: 'inline-flex items-center px-2.5 py-1 text-[10px] font-bold uppercase tracking-wide rounded-full bg-amber-100 text-amber-700 border border-amber-200 dark:bg-amber-900/30 dark:text-amber-400 dark:border-amber-800',
            approved: 'inline-flex items-center px-2.5 py-1 text-[10px] font-bold uppercase tracking-wide rounded-full bg-emerald-100 text-emerald-700 border border-emerald-200 dark:bg-emerald-900/30 dark:text-emerald-400 dark:border-emerald-800',
            rejected: 'inline-flex items-center px-2.5 py-1 text-[10px] font-bold uppercase tracking-wide rounded-full bg-red-100 text-red-700 border border-red-200 dark:bg-red-900/30 dark:text-red-400 dark:border-red-800',
        };
        return classes[status] || classes.pending;
    };

    const formatDate = (date) => {
        if (!date) return '';
        return new Date(date).toLocaleDateString('en-US', {
            year: 'numeric',
            month: 'short',
            day: 'numeric',
        });
    };

    onMounted(() => {
        document.addEventListener('click', handleClickOutside);
    });

    onUnmounted(() => {
        document.removeEventListener('click', handleClickOutside);
    });
</script>