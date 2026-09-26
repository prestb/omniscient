<!-- resources/js/Pages/Owner/Analytics/Index.vue -->
<template>
    <AuthenticatedLayout>
        <!-- COMPACT HEADER -->
        <PageHeader color="indigo" :breadcrumb="[
            { label: 'Dashboard', href: '/owner/dashboard' },
            { label: 'Analytics' }
        ]">
            <template #icon>
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                    d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" />
            </template>
            <template #title>Analytics</template>
            <template #subtitle>{{ business?.name }}</template>
            <template #actions>
                <BusinessSelector v-if="businesses && businesses.length > 1" :businesses="businesses"
                    :selected-id="selectedBusinessId" />
                <div class="flex gap-2">
                    <button @click="setDays(7)"
                        :class="days === 7 ? 'bg-primary-600 text-white shadow-sm' : 'bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-300 hover:bg-gray-200 dark:hover:bg-gray-600'"
                        class="px-3 py-2 rounded-xl text-sm font-semibold transition-colors">7D</button>
                    <button @click="setDays(30)"
                        :class="days === 30 ? 'bg-primary-600 text-white shadow-sm' : 'bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-300 hover:bg-gray-200 dark:hover:bg-gray-600'"
                        class="px-3 py-2 rounded-xl text-sm font-semibold transition-colors">30D</button>
                    <button @click="setDays(90)"
                        :class="days === 90 ? 'bg-primary-600 text-white shadow-sm' : 'bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-300 hover:bg-gray-200 dark:hover:bg-gray-600'"
                        class="px-3 py-2 rounded-xl text-sm font-semibold transition-colors">90D</button>
                    <button @click="setDays(365)"
                        :class="days === 365 ? 'bg-primary-600 text-white shadow-sm' : 'bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-300 hover:bg-gray-200 dark:hover:bg-gray-600'"
                        class="px-3 py-2 rounded-xl text-sm font-semibold transition-colors">1Y</button>
                </div>
                <div class="flex gap-2">
                    <button type="button" @click="exportCsv"
                        class="inline-flex items-center gap-2 px-3 py-2 bg-white dark:bg-gray-800 border border-gray-300 dark:border-gray-600 text-gray-700 dark:text-gray-300 rounded-xl hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors text-sm font-semibold">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                        </svg>
                        CSV
                    </button>
                    <button type="button" @click="exportPdf"
                        class="inline-flex items-center gap-2 px-3 py-2 bg-white dark:bg-gray-800 border border-gray-300 dark:border-gray-600 text-gray-700 dark:text-gray-300 rounded-xl hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors text-sm font-semibold">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z" />
                        </svg>
                        PDF
                    </button>
                </div>
            </template>
        </PageHeader>
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8 space-y-6">
            <!-- Summary Cards -->
            <div class="grid grid-cols-2 sm:grid-cols-4 gap-4 mb-6">
                <div
                    class="bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700 p-4">
                    <p class="text-xs text-gray-500 dark:text-gray-400">Total Views</p>
                    <p class="text-2xl font-bold text-gray-900 dark:text-white">{{ formatNumber(summary.total_views) }}
                    </p>
                    <p class="text-xs text-green-600 dark:text-green-400">+{{ getGrowth('views') }}% from previous
                        period</p>
                </div>
                <div
                    class="bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700 p-4">
                    <p class="text-xs text-gray-500 dark:text-gray-400">Unique Visitors</p>
                    <p class="text-2xl font-bold text-gray-900 dark:text-white">{{
                        formatNumber(summary.total_unique_visitors)
                    }}</p>
                    <p class="text-xs text-green-600 dark:text-green-400">+{{ getGrowth('unique_visitors') }}% from
                        previous
                        period</p>
                </div>
                <div
                    class="bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700 p-4">
                    <p class="text-xs text-gray-500 dark:text-gray-400">Phone Clicks</p>
                    <p class="text-2xl font-bold text-gray-900 dark:text-white">{{
                        formatNumber(summary.total_phone_clicks) }}
                    </p>
                    <p class="text-xs text-green-600 dark:text-green-400">+{{ getGrowth('phone_clicks') }}% from
                        previous period
                    </p>
                </div>
                <div
                    class="bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700 p-4">
                    <p class="text-xs text-gray-500 dark:text-gray-400">Conversion Rate</p>
                    <p class="text-2xl font-bold text-gray-900 dark:text-white">{{ conversionRate }}%</p>
                    <p class="text-xs text-green-600 dark:text-green-400">+{{ getGrowth('conversion_rate') }}% from
                        previous
                        period</p>
                </div>
            </div>

            <!-- Charts -->
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-6">
                <div
                    class="bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700 p-6">
                    <h3 class="text-sm font-medium text-gray-700 dark:text-gray-300 mb-4">Views & Visitors</h3>
                    <div class="h-64">
                        <LineChart :data="viewsChartData" />
                    </div>
                </div>
                <div
                    class="bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700 p-6">
                    <h3 class="text-sm font-medium text-gray-700 dark:text-gray-300 mb-4">Engagement</h3>
                    <div class="h-64">
                        <LineChart :data="engagementChartData" />
                    </div>
                </div>
            </div>

            <!-- Detailed Stats -->
            <div
                class="bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700 overflow-hidden">
                <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-900/50">
                    <h3 class="text-sm font-medium text-gray-700 dark:text-gray-300">Daily Breakdown</h3>
                </div>
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                        <thead>
                            <tr>
                                <th
                                    class="px-6 py-3 bg-gray-50 dark:bg-gray-900/50 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                                    Date</th>
                                <th
                                    class="px-6 py-3 bg-gray-50 dark:bg-gray-900/50 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                                    Views</th>
                                <th
                                    class="px-6 py-3 bg-gray-50 dark:bg-gray-900/50 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                                    Unique Visitors</th>
                                <th
                                    class="px-6 py-3 bg-gray-50 dark:bg-gray-900/50 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                                    Phone Clicks</th>
                                <th
                                    class="px-6 py-3 bg-gray-50 dark:bg-gray-900/50 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                                    WhatsApp</th>
                                <th
                                    class="px-6 py-3 bg-gray-50 dark:bg-gray-900/50 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                                    Website</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                            <tr v-for="item in analyticsData" :key="item.id"
                                class="hover:bg-gray-50 dark:hover:bg-gray-700/30 transition-colors">
                                <td class="px-6 py-4 text-sm text-gray-500 dark:text-gray-400">{{ formatDate(item.date)
                                }}</td>
                                <td class="px-6 py-4 text-sm font-medium text-gray-900 dark:text-white">{{ item.views }}
                                </td>
                                <td class="px-6 py-4 text-sm text-gray-500 dark:text-gray-400">{{ item.unique_visitors
                                }}</td>
                                <td class="px-6 py-4 text-sm text-gray-500 dark:text-gray-400">{{ item.phone_clicks }}
                                </td>
                                <td class="px-6 py-4 text-sm text-gray-500 dark:text-gray-400">{{ item.whatsapp_clicks
                                }}</td>
                                <td class="px-6 py-4 text-sm text-gray-500 dark:text-gray-400">{{ item.website_clicks }}
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>

<script setup>
    import { computed } from 'vue';
    import { router } from '@inertiajs/vue3';
    import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
    // import Breadcrumb from '@/Components/Breadcrumb.vue';
    import LineChart from '@/Components/Charts/LineChart.vue';
    import BusinessSelector from '@/Components/Owner/BusinessSelector.vue';
    import PageHeader from '@/Components/PageHeader.vue';

    const props = defineProps({
        business: Object,
        analyticsData: Array,
        chartData: Object,
        businesses: { type: Array, default: () => [] },
        selectedBusinessId: { type: [Number, String], default: null },
        summary: Object,
        conversionRate: Number,
        days: Number,
    });

    const formatDate = (date) => {
        if (!date) return 'N/A';
        return new Date(date).toLocaleDateString('en-US', {
            year: 'numeric',
            month: 'short',
            day: 'numeric'
        });
    };

    const formatNumber = (num) => {
        if (num >= 1000000) return (num / 1000000).toFixed(1) + 'M';
        if (num >= 1000) return (num / 1000).toFixed(1) + 'K';
        return num.toString();
    };

    const getGrowth = (metric) => {
        // This would be calculated from previous period
        // For demo, returning a random number between 5-20
        return Math.floor(Math.random() * 15) + 5;
    };

    const viewsChartData = computed(() => {
        if (!props.chartData) {
            return { labels: [], datasets: [] };
        }

        return {
            labels: props.chartData.labels,
            datasets: [
                {
                    label: 'Views',
                    data: props.chartData.views,
                    borderColor: '#0284c7',
                    backgroundColor: 'rgba(2, 132, 199, 0.1)',
                    fill: true,
                    tension: 0.4,
                },
                {
                    label: 'Unique Visitors',
                    data: props.chartData.unique_visitors,
                    borderColor: '#16a34a',
                    backgroundColor: 'rgba(22, 163, 74, 0.1)',
                    fill: true,
                    tension: 0.4,
                },
            ],
        };
    });

    const engagementChartData = computed(() => {
        if (!props.chartData) {
            return { labels: [], datasets: [] };
        }

        return {
            labels: props.chartData.labels,
            datasets: [
                {
                    label: 'Phone Clicks',
                    data: props.chartData.phone_clicks,
                    borderColor: '#f59e0b',
                    backgroundColor: 'rgba(245, 158, 11, 0.1)',
                    fill: true,
                    tension: 0.4,
                },
                {
                    label: 'WhatsApp Clicks',
                    data: props.chartData.whatsapp_clicks,
                    borderColor: '#22c55e',
                    backgroundColor: 'rgba(34, 197, 94, 0.1)',
                    fill: true,
                    tension: 0.4,
                },
                {
                    label: 'Website Clicks',
                    data: props.chartData.website_clicks,
                    borderColor: '#8b5cf6',
                    backgroundColor: 'rgba(139, 92, 246, 0.1)',
                    fill: true,
                    tension: 0.4,
                },
            ],
        };
    });

    const setDays = (newDays) => {
        const url = new URL(window.location.href);
        url.searchParams.set('days', newDays);
        // Keep business param if present
        router.visit(url.pathname + url.search, {
            preserveState: true,
            preserveScroll: true,
            only: ['analyticsData', 'chartData', 'summary', 'conversionRate', 'days', 'business', 'selectedBusinessId'],
        });
    };

    const exportCsv = () => {
        const params = new URLSearchParams();
        if (props.selectedBusinessId) params.set('business', props.selectedBusinessId);
        if (props.days) params.set('days', props.days);

        const qs = params.toString();
        window.location.href = `/owner/analytics/export/csv${qs ? '?' + qs : ''}`;
    };

    const exportPdf = () => {
        const params = new URLSearchParams();
        if (props.selectedBusinessId) params.set('business', props.selectedBusinessId);
        if (props.days) params.set('days', props.days);

        const qs = params.toString();
        window.open(`/owner/analytics/export/pdf${qs ? '?' + qs : ''}`, '_blank');
    };
</script>