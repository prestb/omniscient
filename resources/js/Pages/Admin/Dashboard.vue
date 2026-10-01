<!-- resources/js/Pages/Admin/Dashboard.vue -->
<template>
    <AuthenticatedLayout>
        <!-- COMPACT HEADER -->
        <PageHeader color="indigo" :breadcrumb="[{ label: 'Admin', href: '/admin/dashboard' }, { label: 'Dashboard' }]">
            <template #icon>
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                    d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6" />
            </template>
            <template #title>Admin dashboard</template>
            <template #subtitle>Overview of your platform's performance</template>
            <template #actions>
                <button @click="refreshData"
                    class="inline-flex items-center gap-2 px-4 py-2 border border-gray-300 dark:border-gray-600 text-gray-700 dark:text-gray-300 rounded-xl hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors text-sm font-semibold">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                    </svg>
                    Refresh
                </button>
            </template>
        </PageHeader>

        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8 space-y-6">

            <!-- NEEDS ATTENTION STRIP -->
            <div v-if="attentionItems.length > 0" class="bg-white dark:bg-gray-800 rounded-2xl border border-gray-100 dark:border-gray-700 p-4">
                <div class="flex items-center gap-3 mb-3">
                    <div class="w-8 h-8 rounded-lg bg-rose-50 dark:bg-rose-900/30 flex items-center justify-center">
                        <svg class="w-4 h-4 text-rose-600 dark:text-rose-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9" />
                        </svg>
                    </div>
                    <div>
                        <h2 class="text-sm font-bold text-gray-900 dark:text-white tracking-tight">Needs attention</h2>
                        <p class="text-xs text-gray-400 dark:text-gray-500">{{ attentionItems.length }} item{{ attentionItems.length !== 1
                            ? 's' :
                            '' }} require{{ attentionItems.length === 1 ? 's' : '' }} your review</p>
                    </div>
                </div>
                <div class="flex flex-wrap gap-2">
                    <a v-for="item in attentionItems" :key="item.key" :href="item.href"
                        class="inline-flex items-center gap-2 px-3.5 py-2 rounded-xl border text-sm font-semibold transition-all hover:-translate-y-0.5 hover:shadow-sm"
                        :class="item.classes">
                        <span class="text-base">{{ item.icon }}</span>
                        <span>{{ item.label }}</span>
                        <span
                            class="inline-flex items-center justify-center min-w-[22px] h-[22px] px-1.5 rounded-full text-[11px] font-bold"
                            :class="item.countClasses">
                            {{ item.count }}
                        </span>
                    </a>
                </div>
            </div>

            <!-- KPI CARDS -->
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
                <!-- Total businesses -->
                <a href="/admin/businesses"
                    class="bg-white dark:bg-gray-800 rounded-2xl border border-gray-100 dark:border-gray-700 p-5 hover:shadow-md hover:-translate-y-0.5 hover:border-indigo-200 dark:hover:border-indigo-700 transition-all group">
                    <div class="flex items-center justify-between mb-3">
                        <div class="w-11 h-11 rounded-2xl bg-blue-50 dark:bg-blue-900/30 flex items-center justify-center">
                            <svg class="w-5 h-5 text-blue-600 dark:text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" />
                            </svg>
                        </div>
                        <span v-if="(stats?.businesses_this_month || 0) > 0"
                            class="inline-flex items-center gap-1 px-2 py-0.5 text-[10px] font-bold uppercase tracking-wide rounded-full bg-emerald-100 dark:bg-emerald-900/30 text-emerald-700 dark:text-emerald-400">
                            <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M12 4v16m8-8H4" />
                            </svg>
                            +{{ stats.businesses_this_month }} this month
                        </span>
                        <span v-else
                            class="inline-flex items-center px-2 py-0.5 text-[10px] font-bold uppercase tracking-wide rounded-full bg-gray-100 dark:bg-gray-700 text-gray-500 dark:text-gray-400">
                            No new
                        </span>
                    </div>
                    <p class="text-3xl font-bold text-gray-900 dark:text-white tracking-tight">{{ stats?.total_businesses || 0 }}</p>
                    <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">Total businesses</p>
                </a>

                <!-- Total users -->
                <a href="/admin/users"
                    class="bg-white dark:bg-gray-800 rounded-2xl border border-gray-100 dark:border-gray-700 p-5 hover:shadow-md hover:-translate-y-0.5 hover:border-indigo-200 dark:hover:border-indigo-700 transition-all group">
                    <div class="flex items-center justify-between mb-3">
                        <div class="w-11 h-11 rounded-2xl bg-purple-50 dark:bg-purple-900/30 flex items-center justify-center">
                            <svg class="w-5 h-5 text-purple-600 dark:text-purple-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z" />
                            </svg>
                        </div>
                        <span v-if="stats?.user_growth !== null && stats?.user_growth !== undefined"
                            class="inline-flex items-center gap-1 px-2 py-0.5 text-[10px] font-bold uppercase tracking-wide rounded-full"
                            :class="stats.user_growth >= 0 ? 'bg-emerald-100 dark:bg-emerald-900/30 text-emerald-700 dark:text-emerald-400' : 'bg-red-100 dark:bg-red-900/30 text-red-700 dark:text-red-400'">
                            {{ stats.user_growth >= 0 ? '↑' : '↓' }} {{ Math.abs(stats.user_growth) }}%
                        </span>
                        <span v-else-if="(stats?.user_growth_delta || 0) !== 0"
                            class="inline-flex items-center gap-1 px-2 py-0.5 text-[10px] font-bold uppercase tracking-wide rounded-full"
                            :class="stats.user_growth_delta > 0 ? 'bg-emerald-100 dark:bg-emerald-900/30 text-emerald-700 dark:text-emerald-400' : 'bg-red-100 dark:bg-red-900/30 text-red-700 dark:text-red-400'">
                            {{ stats.user_growth_delta > 0 ? '+' : '' }}{{ stats.user_growth_delta }} new
                        </span>
                        <span v-else
                            class="inline-flex items-center px-2 py-0.5 text-[10px] font-bold uppercase tracking-wide rounded-full bg-gray-100 dark:bg-gray-700 text-gray-500 dark:text-gray-400">
                            No change
                        </span>
                    </div>
                    <p class="text-3xl font-bold text-gray-900 dark:text-white tracking-tight">{{ stats?.total_users || 0 }}</p>
                    <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">Total users</p>
                </a>

                <!-- Active subscriptions -->
                <a href="/admin/subscriptions"
                    class="bg-white dark:bg-gray-800 rounded-2xl border border-gray-100 dark:border-gray-700 p-5 hover:shadow-md hover:-translate-y-0.5 hover:border-indigo-200 dark:hover:border-indigo-700 transition-all group">
                    <div class="flex items-center justify-between mb-3">
                        <div class="w-11 h-11 rounded-2xl bg-emerald-50 dark:bg-emerald-900/30 flex items-center justify-center">
                            <svg class="w-5 h-5 text-emerald-600 dark:text-emerald-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                        </div>
                        <span
                            class="inline-flex items-center gap-1 px-2 py-0.5 text-[10px] font-bold uppercase tracking-wide rounded-full bg-emerald-100 dark:bg-emerald-900/30 text-emerald-700 dark:text-emerald-400">
                            {{ stats?.subscription_rate || 0 }}%
                        </span>
                    </div>
                    <p class="text-3xl font-bold text-gray-900 dark:text-white tracking-tight">{{ stats?.active_subscriptions || 0 }}
                    </p>
                    <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">Active subscriptions</p>
                </a>

                <!-- Revenue -->
                <a href="/admin/revenue"
                    class="bg-white dark:bg-gray-800 rounded-2xl border border-gray-100 dark:border-gray-700 p-5 hover:shadow-md hover:-translate-y-0.5 hover:border-indigo-200 dark:hover:border-indigo-700 transition-all group">
                    <div class="flex items-center justify-between mb-3">
                        <div class="w-11 h-11 rounded-2xl bg-amber-50 dark:bg-amber-900/30 flex items-center justify-center">
                            <svg class="w-5 h-5 text-amber-600 dark:text-amber-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                        </div>
                        <span v-if="stats?.revenue_change !== null && stats?.revenue_change !== undefined"
                            class="inline-flex items-center gap-1 px-2 py-0.5 text-[10px] font-bold uppercase tracking-wide rounded-full"
                            :class="stats.revenue_change >= 0 ? 'bg-emerald-100 dark:bg-emerald-900/30 text-emerald-700 dark:text-emerald-400' : 'bg-red-100 dark:bg-red-900/30 text-red-700 dark:text-red-400'">
                            {{ stats.revenue_change >= 0 ? '↑' : '↓' }} {{ Math.abs(stats.revenue_change) }}%
                        </span>
                        <span v-else-if="(stats?.revenue_change_delta || 0) !== 0"
                            class="inline-flex items-center gap-1 px-2 py-0.5 text-[10px] font-bold uppercase tracking-wide rounded-full"
                            :class="stats.revenue_change_delta > 0 ? 'bg-emerald-100 dark:bg-emerald-900/30 text-emerald-700 dark:text-emerald-400' : 'bg-red-100 dark:bg-red-900/30 text-red-700 dark:text-red-400'">
                            {{ stats.revenue_change_delta > 0 ? '+' : '' }}{{ formatPrice(stats.revenue_change_delta) }}
                        </span>
                        <span v-else
                            class="inline-flex items-center px-2 py-0.5 text-[10px] font-bold uppercase tracking-wide rounded-full bg-gray-100 dark:bg-gray-700 text-gray-500 dark:text-gray-400">
                            No change
                        </span>
                    </div>
                    <p class="text-2xl font-bold text-gray-900 dark:text-white tracking-tight truncate">{{
                        formatPrice(stats?.this_month_revenue
                        || 0) }}</p>
                    <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">Revenue this month</p>
                </a>
            </div>

            <!-- CHARTS ROW -->
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                <!-- Revenue chart -->
                <div class="bg-white dark:bg-gray-800 rounded-2xl border border-gray-100 dark:border-gray-700 overflow-hidden">
                    <div
                        class="px-6 py-4 border-b border-gray-100 dark:border-gray-700 bg-gradient-to-br from-gray-50 to-white dark:from-gray-800 dark:to-gray-800/50 flex items-center justify-between">
                        <div>
                            <h3 class="text-sm font-bold text-gray-900 dark:text-white tracking-tight">Revenue overview</h3>
                            <p class="text-xs text-gray-400 dark:text-gray-500">Monthly revenue trend</p>
                        </div>
                        <a href="/admin/revenue"
                            class="text-xs text-primary-600 dark:text-primary-400 hover:text-primary-800 dark:hover:text-primary-300 font-semibold transition-colors">
                            View all →
                        </a>
                    </div>
                    <div class="p-6 h-72">
                        <LineChart :data="charts?.revenue || []" />
                    </div>
                </div>

                <!-- Subscription distribution -->
                <div class="bg-white dark:bg-gray-800 rounded-2xl border border-gray-100 dark:border-gray-700 overflow-hidden">
                    <div
                        class="px-6 py-4 border-b border-gray-100 dark:border-gray-700 bg-gradient-to-br from-gray-50 to-white dark:from-gray-800 dark:to-gray-800/50 flex items-center justify-between">
                        <div>
                            <h3 class="text-sm font-bold text-gray-900 dark:text-white tracking-tight">Subscription distribution</h3>
                            <p class="text-xs text-gray-400 dark:text-gray-500">Plans breakdown</p>
                        </div>
                        <a href="/admin/subscriptions"
                            class="text-xs text-primary-600 dark:text-primary-400 hover:text-primary-800 dark:hover:text-primary-300 font-semibold transition-colors">
                            View all →
                        </a>
                    </div>
                    <div class="p-6 h-72 flex items-center justify-center">
                        <DoughnutChart :data="charts?.subscription_status || []" />
                    </div>
                </div>
            </div>

            <!-- TOP CATEGORIES -->
            <div v-if="topCategories && topCategories.length > 0"
                class="bg-white dark:bg-gray-800 rounded-2xl border border-gray-100 dark:border-gray-700 overflow-hidden">
                <div
                    class="px-6 py-4 border-b border-gray-100 dark:border-gray-700 bg-gradient-to-br from-gray-50 to-white dark:from-gray-800 dark:to-gray-800/50 flex items-center justify-between">
                    <div class="flex items-center gap-3">
                        <div class="w-9 h-9 rounded-xl bg-indigo-50 dark:bg-indigo-900/30 flex items-center justify-center">
                            <svg class="w-4 h-4 text-indigo-600 dark:text-indigo-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z" />
                            </svg>
                        </div>
                        <div>
                            <h3 class="text-sm font-bold text-gray-900 dark:text-white tracking-tight">Top categories</h3>
                            <p class="text-xs text-gray-400 dark:text-gray-500">Most populated categories across the platform</p>
                        </div>
                    </div>
                    <a href="/admin/categories"
                        class="text-xs text-primary-600 dark:text-primary-400 hover:text-primary-800 dark:hover:text-primary-300 font-semibold transition-colors">
                        View all →
                    </a>
                </div>
                <div class="p-6 space-y-4">
                    <div v-for="(cat, i) in topCategories" :key="cat.id">
                        <div class="flex items-center justify-between mb-1.5">
                            <div class="flex items-center gap-2 min-w-0">
                                <span class="text-sm text-gray-400 dark:text-gray-500 font-bold w-5 flex-shrink-0">#{{ i + 1 }}</span>
                                <span class="text-base flex-shrink-0">{{ cat.icon || '📁' }}</span>
                                <span class="text-sm font-semibold text-gray-900 dark:text-white truncate">{{ cat.name }}</span>
                            </div>
                            <span class="text-xs text-gray-500 dark:text-gray-400 font-medium flex-shrink-0 ml-2">
                                <span class="text-gray-900 dark:text-white font-bold">{{ cat.listings_count }}</span>
                                listing{{ cat.listings_count !== 1 ? 's' : '' }}
                            </span>
                        </div>
                        <div class="w-full bg-gray-100 dark:bg-gray-700 rounded-full h-2 overflow-hidden">
                            <div class="h-full rounded-full bg-gradient-to-r from-indigo-400 to-indigo-600 transition-all"
                                :style="{ width: getCategoryPercent(cat.listings_count) + '%' }"></div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- RECENT ACTIVITY ROW -->
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                <!-- Recent businesses -->
                <div class="bg-white dark:bg-gray-800 rounded-2xl border border-gray-100 dark:border-gray-700 overflow-hidden">
                    <div
                        class="px-6 py-4 border-b border-gray-100 dark:border-gray-700 bg-gradient-to-br from-gray-50 to-white dark:from-gray-800 dark:to-gray-800/50 flex items-center justify-between">
                        <div class="flex items-center gap-3">
                            <div class="w-9 h-9 rounded-xl bg-blue-50 dark:bg-blue-900/30 flex items-center justify-center">
                                <svg class="w-4 h-4 text-blue-600 dark:text-blue-400" fill="none" stroke="currentColor"
                                    viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" />
                                </svg>
                            </div>
                            <div>
                                <h3 class="text-sm font-bold text-gray-900 dark:text-white tracking-tight">Recent businesses</h3>
                                <p class="text-xs text-gray-400 dark:text-gray-500">{{ recentBusinesses?.length || 0 }} new</p>
                            </div>
                        </div>
                        <a href="/admin/businesses"
                            class="text-xs text-primary-600 dark:text-primary-400 hover:text-primary-800 dark:hover:text-primary-300 font-semibold transition-colors">
                            View all →
                        </a>
                    </div>
                    <div v-if="recentBusinesses && recentBusinesses.length > 0" class="divide-y divide-gray-100 dark:divide-gray-700">
                        <div v-for="business in recentBusinesses.slice(0, 5)" :key="business.id"
                            class="px-6 py-3.5 hover:bg-gray-50/50 dark:hover:bg-gray-700/30 transition-colors flex items-center justify-between gap-3">
                            <div class="flex items-center gap-3 min-w-0">
                                <div
                                    class="w-9 h-9 rounded-2xl bg-gradient-to-br from-primary-500 to-primary-700 flex items-center justify-center text-white text-xs font-bold flex-shrink-0 shadow-md">
                                    {{ getInitials(business.name) }}
                                </div>
                                <div class="min-w-0">
                                    <p class="text-sm font-semibold text-gray-900 dark:text-white truncate">{{ business.name }}</p>
                                    <p class="text-xs text-gray-400 dark:text-gray-500">{{ formatDate(business.created_at) }}</p>
                                </div>
                            </div>
                            <span :class="statusClass(business.status)" class="flex-shrink-0">
                                {{ statusLabel(business.status) }}
                            </span>
                        </div>
                    </div>
                    <div v-else class="px-6 py-12 text-center">
                        <p class="text-sm text-gray-500 dark:text-gray-400">No recent businesses</p>
                    </div>
                </div>

                <!-- Recent transactions -->
                <div class="bg-white dark:bg-gray-800 rounded-2xl border border-gray-100 dark:border-gray-700 overflow-hidden">
                    <div
                        class="px-6 py-4 border-b border-gray-100 dark:border-gray-700 bg-gradient-to-br from-gray-50 to-white dark:from-gray-800 dark:to-gray-800/50 flex items-center justify-between">
                        <div class="flex items-center gap-3">
                            <div class="w-9 h-9 rounded-xl bg-emerald-50 dark:bg-emerald-900/30 flex items-center justify-center">
                                <svg class="w-4 h-4 text-emerald-600 dark:text-emerald-400" fill="none" stroke="currentColor"
                                    viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M3 10h18M3 14h18m-9-4v8m-7 0h14a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v8a2 2 0 002 2z" />
                                </svg>
                            </div>
                            <div>
                                <h3 class="text-sm font-bold text-gray-900 dark:text-white tracking-tight">Recent transactions</h3>
                                <p class="text-xs text-gray-400 dark:text-gray-500">{{ recentPayments?.length || 0 }} payments</p>
                            </div>
                        </div>
                        <a href="/admin/payments"
                            class="text-xs text-primary-600 dark:text-primary-400 hover:text-primary-800 dark:hover:text-primary-300 font-semibold transition-colors">
                            View all →
                        </a>
                    </div>
                    <div v-if="recentPayments && recentPayments.length > 0" class="divide-y divide-gray-100 dark:divide-gray-700">
                        <div v-for="transaction in recentPayments.slice(0, 5)" :key="transaction.id"
                            class="px-6 py-3.5 hover:bg-gray-50/50 dark:hover:bg-gray-700/30 transition-colors flex items-center justify-between gap-3">
                            <div class="min-w-0">
                                <p class="text-sm font-semibold text-gray-900 dark:text-white truncate">{{ transaction.user?.name ||
                                    'N/A' }}
                                </p>
                                <p class="text-xs text-gray-400 dark:text-gray-500 flex items-center gap-1.5 flex-wrap">
                                    <span class="font-semibold text-primary-600 dark:text-primary-400">{{ transaction.plan?.name || 'N/A'
                                        }}</span>
                                    <span class="text-gray-300 dark:text-gray-600">·</span>
                                    <span>{{ transaction.duration_months || 0 }} months</span>
                                    <span class="text-gray-300 dark:text-gray-600">·</span>
                                    <span>{{ formatDate(transaction.confirmed_at) }}</span>
                                </p>
                            </div>
                            <div class="text-right flex-shrink-0">
                                <p class="text-sm font-bold text-emerald-600 dark:text-emerald-400">{{ formatPrice(transaction.amount) }}</p>
                                <span
                                    class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-bold uppercase tracking-wide bg-emerald-100 dark:bg-emerald-900/30 text-emerald-700 dark:text-emerald-400">
                                    Successful
                                </span>
                            </div>
                        </div>
                    </div>
                    <div v-else class="px-6 py-12 text-center">
                        <p class="text-sm text-gray-500 dark:text-gray-400">No recent transactions</p>
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>

<script setup>
    import { computed } from 'vue';
    import { router } from '@inertiajs/vue3';
    import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
    import PageHeader from '@/Components/PageHeader.vue';
    import LineChart from '@/Components/Charts/LineChart.vue';
    import DoughnutChart from '@/Components/Charts/DoughnutChart.vue';
    import { useStatusBadge } from '@/composables/useStatusBadge';

    const props = defineProps({
        stats: Object,
        charts: Object,
        recentBusinesses: Array,
        topPlans: Array,
        recentPayments: Array,
        topCategories: { type: Array, default: () => [] },
    });

    const getInitials = (name) => {
        if (!name) return '?';
        return name
            .split(' ')
            .map((n) => n[0])
            .join('')
            .toUpperCase()
            .slice(0, 2);
    };

    const { business: statusClass, label: statusLabel } = useStatusBadge();

    const formatPrice = (price) => {
        if (!price) return '0 XAF';
        return new Intl.NumberFormat('en-US', {
            style: 'currency',
            currency: 'XAF',
            minimumFractionDigits: 0,
        }).format(price);
    };

    const formatDate = (date) => {
        if (!date) return 'N/A';
        return new Date(date).toLocaleDateString('en-US', {
            year: 'numeric',
            month: 'short',
            day: 'numeric',
        });
    };

    const refreshData = () => {
        // ✅ Inertia reload — re-fetches page props without a full browser refresh.
        //    Preserves scroll position and current dark-mode/sidebar state.
        //    (Previously: window.location.reload(), which caused a hard refresh.)
        router.reload({ preserveScroll: true });
    };

    // ============== NEEDS ATTENTION ==============
    const attentionItems = computed(() => {
        const items = [];

        if (props.stats?.pending_businesses > 0) {
            items.push({
                key: 'pending_businesses',
                icon: '⏳',
                label: 'Businesses pending',
                count: props.stats.pending_businesses,
                href: '/admin/businesses?status=submitted',
                classes: 'bg-amber-50 dark:bg-amber-900/20 text-amber-800 dark:text-amber-300 border-amber-200 dark:border-amber-800 hover:border-amber-300 dark:hover:border-amber-700',
                countClasses: 'bg-amber-500 text-white',
            });
        }

        if (props.stats?.reviews_pending > 0) {
            items.push({
                key: 'reviews_pending',
                icon: '⭐',
                label: 'Reviews pending',
                count: props.stats.reviews_pending,
                href: '/admin/reviews?status=pending',
                classes: 'bg-amber-50 dark:bg-amber-900/20 text-amber-800 dark:text-amber-300 border-amber-200 dark:border-amber-800 hover:border-amber-300 dark:hover:border-amber-700',
                countClasses: 'bg-amber-500 text-white',
            });
        }

        if (props.stats?.failed_payments > 0) {
            items.push({
                key: 'failed_payments',
                icon: '💳',
                label: 'Failed payments',
                count: props.stats.failed_payments,
                href: '/admin/payments?status=failed',
                classes: 'bg-red-50 dark:bg-red-900/20 text-red-800 dark:text-red-300 border-red-200 dark:border-red-800 hover:border-red-300 dark:hover:border-red-700',
                countClasses: 'bg-red-500 text-white',
            });
        }

        if (props.stats?.expiring_subscriptions > 0) {
            items.push({
                key: 'expiring_subscriptions',
                icon: '⏰',
                label: 'Expiring soon',
                count: props.stats.expiring_subscriptions,
                href: '/admin/subscriptions?filter=expiring',
                classes: 'bg-orange-50 dark:bg-orange-900/20 text-orange-800 dark:text-orange-300 border-orange-200 dark:border-orange-800 hover:border-orange-300 dark:hover:border-orange-700',
                countClasses: 'bg-orange-500 text-white',
            });
        }

        return items;
    });

    // ============== TOP CATEGORIES ==============
    const getCategoryPercent = (count) => {
        if (!props.topCategories || props.topCategories.length === 0) return 0;
        const max = Math.max(...props.topCategories.map((c) => c.listings_count || 0));
        if (max === 0) return 0;
        return Math.round((count / max) * 100);
    };
</script>