<!-- resources/js/Pages/Admin/Revenue.vue -->
<template>
    <AuthenticatedLayout>
        <!-- COMPACT HEADER -->
<PageHeader
    color="emerald"
    :breadcrumb="[
        { label: 'Admin', href: '/admin/dashboard' },
        { label: 'Revenue' }
    ]"
>
    <template #icon>
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
              d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" />
    </template>
    <template #title>Revenue analytics</template>
    <template #subtitle>Track revenue, growth, and subscription health</template>
</PageHeader>

        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8 space-y-6">

            <!-- KPI CARDS -->
            <div class="grid grid-cols-2 lg:grid-cols-4 gap-4">
                <div class="relative overflow-hidden bg-gradient-to-br from-blue-500 to-blue-600 rounded-2xl p-5 text-white shadow-lg shadow-blue-500/20">
                    <div class="absolute inset-0 opacity-[0.08]"
                         style="background-image: radial-gradient(circle, white 1px, transparent 1px); background-size: 18px 18px;"></div>
                    <div class="relative">
                        <div class="flex items-center justify-between mb-3">
                            <div class="w-10 h-10 rounded-xl bg-white/20 flex items-center justify-center">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                </svg>
                            </div>
                            <span class="text-[10px] font-bold bg-white/20 px-2 py-0.5 rounded-full uppercase tracking-wider">MRR</span>
                        </div>
                        <p class="text-2xl sm:text-3xl font-bold tracking-tight truncate">{{ formatPrice(kpis.mrr) }}</p>
                        <p class="text-xs text-white/70 mt-1">Monthly recurring revenue</p>
                    </div>
                </div>

                <div class="relative overflow-hidden bg-gradient-to-br from-purple-500 to-purple-600 rounded-2xl p-5 text-white shadow-lg shadow-purple-500/20">
                    <div class="absolute inset-0 opacity-[0.08]"
                         style="background-image: radial-gradient(circle, white 1px, transparent 1px); background-size: 18px 18px;"></div>
                    <div class="relative">
                        <div class="flex items-center justify-between mb-3">
                            <div class="w-10 h-10 rounded-xl bg-white/20 flex items-center justify-center">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6" />
                                </svg>
                            </div>
                            <span class="text-[10px] font-bold bg-white/20 px-2 py-0.5 rounded-full uppercase tracking-wider">ARR</span>
                        </div>
                        <p class="text-2xl sm:text-3xl font-bold tracking-tight truncate">{{ formatPrice(kpis.arr) }}</p>
                        <p class="text-xs text-white/70 mt-1">Annual recurring revenue</p>
                    </div>
                </div>

                <div class="bg-white dark:bg-gray-800 rounded-2xl border border-gray-100 dark:border-gray-700 p-5">
                    <div class="flex items-center justify-between mb-3">
                        <div class="w-10 h-10 rounded-xl bg-amber-50 dark:bg-amber-900/30 flex items-center justify-center">
                            <svg class="w-5 h-5 text-amber-600 dark:text-amber-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                            </svg>
                        </div>
                        <span class="text-[10px] font-bold text-amber-600 dark:text-amber-400 bg-amber-50 dark:bg-amber-900/30 px-2 py-0.5 rounded-full uppercase tracking-wider">ARPU</span>
                    </div>
                    <p class="text-2xl sm:text-3xl font-bold text-gray-900 dark:text-white tracking-tight truncate">{{ formatPrice(kpis.arpu) }}</p>
                    <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">Avg. per subscriber ({{ kpis.active_count }})</p>
                </div>

                <div class="bg-white dark:bg-gray-800 rounded-2xl border border-gray-100 dark:border-gray-700 p-5">
                    <div class="flex items-center justify-between mb-3">
                        <div class="w-10 h-10 rounded-xl bg-emerald-50 dark:bg-emerald-900/30 flex items-center justify-center">
                            <svg class="w-5 h-5 text-emerald-600 dark:text-emerald-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z" />
                            </svg>
                        </div>
                        <span class="text-[10px] font-bold text-emerald-600 dark:text-emerald-400 bg-emerald-50 dark:bg-emerald-900/30 px-2 py-0.5 rounded-full uppercase tracking-wider">LTV</span>
                    </div>
                    <p class="text-2xl sm:text-3xl font-bold text-gray-900 dark:text-white tracking-tight truncate">{{ formatPrice(kpis.ltv) }}</p>
                    <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">Customer lifetime value</p>
                </div>
            </div>

            <!-- GROWTH METRICS -->
            <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                <div class="bg-white dark:bg-gray-800 rounded-2xl border border-gray-100 dark:border-gray-700 p-5">
                    <div class="flex items-center justify-between mb-2">
                        <p class="text-[10px] font-bold text-gray-500 dark:text-gray-400 uppercase tracking-widest">Revenue growth</p>
                        <span class="text-xs font-bold px-2 py-0.5 rounded-full"
                              :class="growth.growth.revenue >= 0
                                  ? 'bg-emerald-100 text-emerald-700 dark:bg-emerald-900/30 dark:text-emerald-400'
                                  : 'bg-red-100 text-red-700 dark:bg-red-900/30 dark:text-red-400'">
                            {{ growth.growth.revenue >= 0 ? '↑' : '↓' }} {{ Math.abs(growth.growth.revenue) }}%
                        </span>
                    </div>
                    <p class="text-2xl font-bold text-gray-900 dark:text-white tracking-tight">{{ formatPrice(growth.this_month.revenue) }}</p>
                    <p class="text-xs text-gray-400 dark:text-gray-500 mt-1">vs {{ formatPrice(growth.last_month.revenue) }} last month</p>
                </div>

                <div class="bg-white dark:bg-gray-800 rounded-2xl border border-gray-100 dark:border-gray-700 p-5">
                    <div class="flex items-center justify-between mb-2">
                        <p class="text-[10px] font-bold text-gray-500 dark:text-gray-400 uppercase tracking-widest">New subscribers</p>
                        <span class="text-xs font-bold px-2 py-0.5 rounded-full"
                              :class="growth.growth.new_subs >= 0
                                  ? 'bg-emerald-100 text-emerald-700 dark:bg-emerald-900/30 dark:text-emerald-400'
                                  : 'bg-red-100 text-red-700 dark:bg-red-900/30 dark:text-red-400'">
                            {{ growth.growth.new_subs >= 0 ? '↑' : '↓' }} {{ Math.abs(growth.growth.new_subs) }}%
                        </span>
                    </div>
                    <p class="text-2xl font-bold text-gray-900 dark:text-white tracking-tight">{{ growth.this_month.new }}</p>
                    <p class="text-xs text-gray-400 dark:text-gray-500 mt-1">vs {{ growth.last_month.new }} last month</p>
                </div>

                <div class="bg-white dark:bg-gray-800 rounded-2xl border border-gray-100 dark:border-gray-700 p-5">
                    <div class="flex items-center justify-between mb-2">
                        <p class="text-[10px] font-bold text-gray-500 dark:text-gray-400 uppercase tracking-widest">Churn rate</p>
                        <span class="text-xs font-bold px-2 py-0.5 rounded-full"
                              :class="growth.growth.churn_rate < 5
                                  ? 'bg-emerald-100 text-emerald-700 dark:bg-emerald-900/30 dark:text-emerald-400'
                                  : 'bg-amber-100 text-amber-700 dark:bg-amber-900/30 dark:text-amber-400'">
                            {{ growth.growth.churn_rate < 5 ? 'Healthy' : 'Watch' }}
                        </span>
                    </div>
                    <p class="text-2xl font-bold text-gray-900 dark:text-white tracking-tight">{{ growth.growth.churn_rate }}%</p>
                    <p class="text-xs text-gray-400 dark:text-gray-500 mt-1">{{ growth.this_month.churned }} cancelled this month</p>
                </div>
            </div>

            <!-- REVENUE CHART -->
            <div class="bg-white dark:bg-gray-800 rounded-2xl border border-gray-100 dark:border-gray-700 overflow-hidden">
                <div class="px-6 py-4 border-b border-gray-100 dark:border-gray-700 bg-gradient-to-br from-gray-50 to-white dark:from-gray-800 dark:to-gray-800/50">
                    <h3 class="text-sm font-bold text-gray-900 dark:text-white tracking-tight">Revenue trend</h3>
                    <p class="text-xs text-gray-400 dark:text-gray-500">Last 12 months</p>
                </div>
                <div class="p-6 h-80">
                    <LineChart :data="revenueChartData" :options="lineOptions" />
                </div>
            </div>

            <!-- SUBSCRIBERS + PLAN DISTRIBUTION -->
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                <div class="lg:col-span-2 bg-white dark:bg-gray-800 rounded-2xl border border-gray-100 dark:border-gray-700 overflow-hidden">
                    <div class="px-6 py-4 border-b border-gray-100 dark:border-gray-700 bg-gradient-to-br from-gray-50 to-white dark:from-gray-800 dark:to-gray-800/50">
                        <h3 class="text-sm font-bold text-gray-900 dark:text-white tracking-tight">Subscriber movement</h3>
                        <p class="text-xs text-gray-400 dark:text-gray-500">New vs cancelled per month</p>
                    </div>
                    <div class="p-6 h-72">
                        <BarChart :data="subsChartData" :options="barOptions" />
                    </div>
                </div>

                <div class="bg-white dark:bg-gray-800 rounded-2xl border border-gray-100 dark:border-gray-700 overflow-hidden">
                    <div class="px-6 py-4 border-b border-gray-100 dark:border-gray-700 bg-gradient-to-br from-gray-50 to-white dark:from-gray-800 dark:to-gray-800/50">
                        <h3 class="text-sm font-bold text-gray-900 dark:text-white tracking-tight">Plan distribution</h3>
                        <p class="text-xs text-gray-400 dark:text-gray-500">Active subscribers by plan</p>
                    </div>
                    <div class="p-6 h-72 flex items-center justify-center">
                        <DoughnutChart v-if="planDistData.labels.length > 0" :data="planDistData" />
                        <p v-else class="text-sm text-gray-400 dark:text-gray-500">No active subscriptions</p>
                    </div>
                </div>
            </div>

            <!-- MRR BY PLAN + TOP PLANS -->
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                <div class="bg-white dark:bg-gray-800 rounded-2xl border border-gray-100 dark:border-gray-700 overflow-hidden">
                    <div class="px-6 py-4 border-b border-gray-100 dark:border-gray-700 bg-gradient-to-br from-gray-50 to-white dark:from-gray-800 dark:to-gray-800/50">
                        <h3 class="text-sm font-bold text-gray-900 dark:text-white tracking-tight">MRR by plan</h3>
                        <p class="text-xs text-gray-400 dark:text-gray-500">Monthly revenue per plan</p>
                    </div>
                    <div class="p-6 h-72 flex items-center justify-center">
                        <DoughnutChart v-if="revenueByPlanData.labels.length > 0" :data="revenueByPlanData" />
                        <p v-else class="text-sm text-gray-400 dark:text-gray-500">No data</p>
                    </div>
                </div>

                <div class="lg:col-span-2 bg-white dark:bg-gray-800 rounded-2xl border border-gray-100 dark:border-gray-700 overflow-hidden">
                    <div class="px-6 py-4 border-b border-gray-100 dark:border-gray-700 bg-gradient-to-br from-gray-50 to-white dark:from-gray-800 dark:to-gray-800/50 flex items-center justify-between">
                        <div>
                            <h3 class="text-sm font-bold text-gray-900 dark:text-white tracking-tight">Top performing plans</h3>
                            <p class="text-xs text-gray-400 dark:text-gray-500">By monthly recurring revenue</p>
                        </div>
                        <a href="/admin/plans" class="text-xs font-bold text-primary-600 dark:text-primary-400 hover:text-primary-800 dark:hover:text-primary-300 transition-colors">
                            Manage →
                        </a>
                    </div>
                    <div class="p-6 space-y-2">
                        <div v-for="plan in top_plans" :key="plan.id"
                             class="flex items-center justify-between p-4 bg-gray-50 dark:bg-gray-900/50 rounded-xl hover:bg-gray-100 dark:hover:bg-gray-900 transition-colors">
                            <div class="flex items-center gap-3 min-w-0">
                                <div class="w-10 h-10 rounded-2xl bg-gradient-to-br from-primary-500 to-purple-600 flex items-center justify-center text-white font-bold text-sm flex-shrink-0 shadow-md">
                                    {{ plan.name.charAt(0) }}
                                </div>
                                <div class="min-w-0">
                                    <div class="flex items-center gap-1.5">
                                        <span class="text-sm font-bold text-gray-900 dark:text-white truncate tracking-tight">{{ plan.name }}</span>
                                        <span v-if="plan.is_featured" class="text-xs">⭐</span>
                                    </div>
                                    <p class="text-xs text-gray-500 dark:text-gray-400">{{ plan.active_count }} subscribers</p>
                                </div>
                            </div>
                            <div class="text-right flex-shrink-0 ml-3">
                                <p class="text-sm font-bold text-gray-900 dark:text-white">{{ formatPrice(plan.mrr) }}</p>
                                <p class="text-xs text-gray-400 dark:text-gray-500">per month</p>
                            </div>
                        </div>
                        <div v-if="!top_plans.length" class="text-center py-8">
                            <p class="text-sm text-gray-400 dark:text-gray-500">No active plans yet</p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- TOP BUSINESSES + RECENT ACTIVITY -->
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                <div class="bg-white dark:bg-gray-800 rounded-2xl border border-gray-100 dark:border-gray-700 overflow-hidden">
                    <div class="px-6 py-4 border-b border-gray-100 dark:border-gray-700 bg-gradient-to-br from-gray-50 to-white dark:from-gray-800 dark:to-gray-800/50 flex items-center justify-between">
                        <div>
                            <h3 class="text-sm font-bold text-gray-900 dark:text-white tracking-tight">Top businesses</h3>
                            <p class="text-xs text-gray-400 dark:text-gray-500">By lifetime value</p>
                        </div>
                        <a href="/admin/businesses" class="text-xs font-bold text-primary-600 dark:text-primary-400 hover:text-primary-800 dark:hover:text-primary-300 transition-colors">
                            View all →
                        </a>
                    </div>
                    <div class="p-6 space-y-2">
                        <a v-for="biz in top_businesses" :key="biz.id"
                           :href="`/admin/businesses/${biz.id}`"
                           class="flex items-center justify-between p-3 rounded-xl hover:bg-gray-50 dark:hover:bg-gray-700/30 transition-colors group">
                            <div class="flex items-center gap-3 min-w-0">
                                <div class="w-9 h-9 rounded-2xl bg-gradient-to-br from-amber-400 to-orange-500 flex items-center justify-center text-white text-xs font-bold flex-shrink-0 shadow-md">
                                    {{ getInitials(biz.name) }}
                                </div>
                                <div class="min-w-0">
                                    <p class="text-sm font-bold text-gray-900 dark:text-white group-hover:text-primary-600 dark:group-hover:text-primary-400 transition-colors truncate tracking-tight">
                                        {{ biz.name }}
                                    </p>
                                    <p class="text-xs text-gray-400 dark:text-gray-500 truncate">
                                        {{ biz.owner_name }} · {{ biz.subscriptions_count }} subs
                                    </p>
                                </div>
                            </div>
                            <p class="text-sm font-bold text-emerald-600 dark:text-emerald-400 flex-shrink-0 ml-3">
                                {{ formatPrice(biz.total_paid) }}
                            </p>
                        </a>
                        <div v-if="!top_businesses.length" class="text-center py-6">
                            <p class="text-sm text-gray-400 dark:text-gray-500">No data yet</p>
                        </div>
                    </div>
                </div>

                <div class="bg-white dark:bg-gray-800 rounded-2xl border border-gray-100 dark:border-gray-700 overflow-hidden">
                    <div class="px-6 py-4 border-b border-gray-100 dark:border-gray-700 bg-gradient-to-br from-gray-50 to-white dark:from-gray-800 dark:to-gray-800/50">
                        <h3 class="text-sm font-bold text-gray-900 dark:text-white tracking-tight">Recent activity</h3>
                        <p class="text-xs text-gray-400 dark:text-gray-500">Latest subscription changes</p>
                    </div>
                    <div class="p-6 space-y-2">
                        <div v-for="(event, i) in recent_events" :key="i"
                             class="flex items-center gap-3 p-3 rounded-xl hover:bg-gray-50 dark:hover:bg-gray-700/30 transition-colors">
                            <span class="text-xl flex-shrink-0">{{ event.icon }}</span>
                            <div class="flex-1 min-w-0">
                                <p class="text-sm font-bold text-gray-900 dark:text-white truncate tracking-tight">{{ event.business }}</p>
                                <p class="text-xs text-gray-400 dark:text-gray-500">{{ event.label }} · {{ event.plan }}</p>
                            </div>
                            <div class="text-right flex-shrink-0 ml-3">
                                <p v-if="event.amount > 0" class="text-xs font-bold text-emerald-600 dark:text-emerald-400">
                                    {{ formatPrice(event.amount) }}
                                </p>
                                <p class="text-xs text-gray-400 dark:text-gray-500">{{ timeAgo(event.date) }}</p>
                            </div>
                        </div>
                        <div v-if="!recent_events.length" class="text-center py-6">
                            <p class="text-sm text-gray-400 dark:text-gray-500">No recent activity</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>

<script setup>
import { computed } from 'vue';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
// import Breadcrumb from '@/Components/Breadcrumb.vue';
import LineChart from '@/Components/Charts/LineChart.vue';
import BarChart from '@/Components/Charts/BarChart.vue';
import DoughnutChart from '@/Components/Charts/DoughnutChart.vue';
import PageHeader from '@/Components/PageHeader.vue';

const props = defineProps({
    kpis: { type: Object, required: true },
    charts: { type: Object, required: true },
    growth: { type: Object, required: true },
    top_plans: { type: Array, default: () => [] },
    top_businesses: { type: Array, default: () => [] },
    recent_events: { type: Array, default: () => [] },
});

const formatPrice = (price) => {
    if (!price && price !== 0) return '0 XAF';
    return new Intl.NumberFormat('en-US', {
        style: 'currency',
        currency: 'XAF',
        minimumFractionDigits: 0,
        maximumFractionDigits: 0,
    }).format(price);
};

const getInitials = (name) => {
    if (!name) return '?';
    return name.split(' ').map(n => n[0]).join('').toUpperCase().slice(0, 2);
};

const timeAgo = (date) => {
    if (!date) return '';
    const diff = Math.floor((Date.now() - new Date(date)) / 1000);
    if (diff < 60) return 'Just now';
    if (diff < 3600) return Math.floor(diff / 60) + 'm ago';
    if (diff < 86400) return Math.floor(diff / 3600) + 'h ago';
    if (diff < 604800) return Math.floor(diff / 86400) + 'd ago';
    return new Date(date).toLocaleDateString('en-US', { month: 'short', day: 'numeric' });
};

const revenueChartData = computed(() => ({
    labels: props.charts.revenue.labels,
    datasets: [{
        label: 'Revenue (XAF)',
        data: props.charts.revenue.data,
        borderColor: '#0ea5e9',
        backgroundColor: 'rgba(14, 165, 233, 0.1)',
        fill: true,
        tension: 0.4,
        borderWidth: 2,
        pointRadius: 4,
        pointHoverRadius: 6,
        pointBackgroundColor: '#0ea5e9',
        pointBorderColor: '#ffffff',
        pointBorderWidth: 2,
    }],
}));

const subsChartData = computed(() => ({
    labels: props.charts.new_subs.labels,
    datasets: [
        {
            label: 'New',
            data: props.charts.new_subs.new,
            backgroundColor: '#10b981',
            borderRadius: 6,
        },
        {
            label: 'Cancelled',
            data: props.charts.new_subs.cancelled,
            backgroundColor: '#ef4444',
            borderRadius: 6,
        },
    ],
}));

const planDistData = computed(() => ({
    labels: props.charts.plan_distribution.labels,
    datasets: [{
        data: props.charts.plan_distribution.data,
        backgroundColor: props.charts.plan_distribution.colors,
        borderWidth: 2,
        borderColor: '#ffffff',
    }],
}));

const revenueByPlanData = computed(() => ({
    labels: props.charts.revenue_by_plan.labels,
    datasets: [{
        data: props.charts.revenue_by_plan.data,
        backgroundColor: props.charts.revenue_by_plan.colors,
        borderWidth: 2,
        borderColor: '#ffffff',
    }],
}));

const lineOptions = {
    plugins: {
        legend: { display: false },
        tooltip: {
            callbacks: {
                label: (ctx) => `Revenue: ${formatPrice(ctx.raw)}`,
            },
        },
    },
    scales: {
        y: {
            beginAtZero: true,
            ticks: {
                callback: (value) => formatPrice(value),
            },
        },
    },
};

const barOptions = {
    plugins: {
        legend: {
            position: 'bottom',
            labels: { usePointStyle: true, padding: 15, boxWidth: 8 },
        },
    },
    scales: {
        y: { beginAtZero: true, ticks: { stepSize: 1 } },
    },
};
</script>