<!-- resources/js/Pages/Admin/SuperDashboard.vue -->
<template>
    <AuthenticatedLayout>
        <!-- COMPACT HEADER -->
        <PageHeader color="purple" :breadcrumb="[
            { label: 'Admin', href: '/admin/dashboard' },
            { label: 'Super Admin' }
        ]">
            <template #icon>
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                    d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z" />
            </template>
            <template #title>Super admin control</template>
            <template #subtitle>Full platform control &amp; health monitoring</template>
            <template #actions>
                <span
                    class="inline-flex items-center gap-1.5 px-3 py-2 bg-purple-50 dark:bg-purple-900/30 border border-purple-200 dark:border-purple-800 text-xs font-bold uppercase tracking-wide text-purple-700 dark:text-purple-300 rounded-xl">
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z" />
                    </svg>
                    Full access
                </span>
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

            <!-- STATS GRID -->
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
                <!-- Total users -->
                <a href="/admin/users"
                    class="bg-white dark:bg-gray-800 rounded-2xl border border-gray-100 dark:border-gray-700 p-5 hover:shadow-md hover:-translate-y-0.5 hover:border-purple-200 dark:hover:border-purple-800 transition-all group">
                    <div class="flex items-center justify-between mb-3">
                        <div class="w-11 h-11 rounded-2xl bg-blue-50 dark:bg-blue-900/30 flex items-center justify-center">
                            <svg class="w-5 h-5 text-blue-600 dark:text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                            </svg>
                        </div>
                        <span v-if="stats.user_growth !== null && stats.user_growth !== undefined"
                            class="inline-flex items-center gap-1 px-2 py-0.5 text-[10px] font-bold uppercase tracking-wide rounded-full"
                            :class="stats.user_growth >= 0 ? 'bg-emerald-100 text-emerald-700 dark:bg-emerald-900/30 dark:text-emerald-400' : 'bg-red-100 text-red-700 dark:bg-red-900/30 dark:text-red-400'">
                            {{ stats.user_growth >= 0 ? '↑' : '↓' }} {{ Math.abs(stats.user_growth) }}%
                        </span>
                        <span v-else-if="(stats.user_growth_delta || 0) !== 0"
                            class="inline-flex items-center gap-1 px-2 py-0.5 text-[10px] font-bold uppercase tracking-wide rounded-full"
                            :class="stats.user_growth_delta > 0 ? 'bg-emerald-100 text-emerald-700 dark:bg-emerald-900/30 dark:text-emerald-400' : 'bg-red-100 text-red-700 dark:bg-red-900/30 dark:text-red-400'">
                            {{ stats.user_growth_delta > 0 ? '+' : '' }}{{ stats.user_growth_delta }} new
                        </span>
                        <span v-else
                            class="inline-flex items-center px-2 py-0.5 text-[10px] font-bold uppercase tracking-wide rounded-full bg-gray-100 dark:bg-gray-700 text-gray-500 dark:text-gray-400">
                            No change
                        </span>
                    </div>
                    <p class="text-3xl font-bold text-gray-900 dark:text-white tracking-tight">{{ stats.total_users || 0 }}</p>
                    <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">Total users <span class="text-gray-400 dark:text-gray-500">· {{
                        stats.active_users || 0
                            }} active</span></p>
                </a>

                <!-- Total businesses -->
                <a href="/admin/businesses"
                    class="bg-white dark:bg-gray-800 rounded-2xl border border-gray-100 dark:border-gray-700 p-5 hover:shadow-md hover:-translate-y-0.5 hover:border-purple-200 dark:hover:border-purple-800 transition-all group">
                    <div class="flex items-center justify-between mb-3">
                        <div class="w-11 h-11 rounded-2xl bg-emerald-50 dark:bg-emerald-900/30 flex items-center justify-center">
                            <svg class="w-5 h-5 text-emerald-600 dark:text-emerald-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" />
                            </svg>
                        </div>
                        <span v-if="(stats.businesses_this_month || 0) > 0"
                            class="inline-flex items-center gap-1 px-2 py-0.5 text-[10px] font-bold uppercase tracking-wide rounded-full bg-emerald-100 dark:bg-emerald-900/30 text-emerald-700 dark:text-emerald-400">
                            +{{ stats.businesses_this_month }} this month
                        </span>
                        <span v-else
                            class="inline-flex items-center px-2 py-0.5 text-[10px] font-bold uppercase tracking-wide rounded-full bg-gray-100 dark:bg-gray-700 text-gray-500 dark:text-gray-400">
                            No new
                        </span>
                    </div>
                    <p class="text-3xl font-bold text-gray-900 dark:text-white tracking-tight">{{ stats.total_businesses || 0 }}</p>
                    <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">Businesses <span class="text-gray-400 dark:text-gray-500">· {{
                        stats.published_businesses
                        || 0 }} published</span></p>
                </a>

                <!-- Total revenue -->
                <a href="/admin/revenue"
                    class="bg-white dark:bg-gray-800 rounded-2xl border border-gray-100 dark:border-gray-700 p-5 hover:shadow-md hover:-translate-y-0.5 hover:border-purple-200 dark:hover:border-purple-800 transition-all group">
                    <div class="flex items-center justify-between mb-3">
                        <div class="w-11 h-11 rounded-2xl bg-amber-50 dark:bg-amber-900/30 flex items-center justify-center">
                            <svg class="w-5 h-5 text-amber-600 dark:text-amber-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                        </div>
                        <span v-if="stats.revenue_change !== null && stats.revenue_change !== undefined"
                            class="inline-flex items-center gap-1 px-2 py-0.5 text-[10px] font-bold uppercase tracking-wide rounded-full"
                            :class="stats.revenue_change >= 0 ? 'bg-emerald-100 text-emerald-700 dark:bg-emerald-900/30 dark:text-emerald-400' : 'bg-red-100 text-red-700 dark:bg-red-900/30 dark:text-red-400'">
                            {{ stats.revenue_change >= 0 ? '↑' : '↓' }} {{ Math.abs(stats.revenue_change) }}%
                        </span>
                        <span v-else-if="(stats.revenue_change_delta || 0) !== 0"
                            class="inline-flex items-center gap-1 px-2 py-0.5 text-[10px] font-bold uppercase tracking-wide rounded-full"
                            :class="stats.revenue_change_delta > 0 ? 'bg-emerald-100 text-emerald-700 dark:bg-emerald-900/30 dark:text-emerald-400' : 'bg-red-100 text-red-700 dark:bg-red-900/30 dark:text-red-400'">
                            {{ stats.revenue_change_delta > 0 ? '+' : '' }}{{ formatPrice(stats.revenue_change_delta) }}
                        </span>
                        <span v-else
                            class="inline-flex items-center px-2 py-0.5 text-[10px] font-bold uppercase tracking-wide rounded-full bg-gray-100 dark:bg-gray-700 text-gray-500 dark:text-gray-400">
                            No change
                        </span>
                    </div>
                    <p class="text-2xl font-bold text-gray-900 dark:text-white tracking-tight truncate">{{
                        formatPrice(stats.total_revenue) }}
                    </p>
                    <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">Total revenue <span class="text-gray-400 dark:text-gray-500">· {{
                        formatPrice(stats.this_month_revenue) }} this month</span></p>
                </a>

                <!-- Active subscriptions -->
                <a href="/admin/subscriptions"
                    class="bg-white dark:bg-gray-800 rounded-2xl border border-gray-100 dark:border-gray-700 p-5 hover:shadow-md hover:-translate-y-0.5 hover:border-purple-200 dark:hover:border-purple-800 transition-all group">
                    <div class="flex items-center justify-between mb-3">
                        <div class="w-11 h-11 rounded-2xl bg-purple-50 dark:bg-purple-900/30 flex items-center justify-center">
                            <svg class="w-5 h-5 text-purple-600 dark:text-purple-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                        </div>
                        <span v-if="(stats.expiring_subscriptions || 0) > 0"
                            class="inline-flex items-center px-2 py-0.5 text-[10px] font-bold uppercase tracking-wide rounded-full bg-amber-100 dark:bg-amber-900/30 text-amber-700 dark:text-amber-400">
                            {{ stats.expiring_subscriptions }} expiring
                        </span>
                    </div>
                    <p class="text-3xl font-bold text-gray-900 dark:text-white tracking-tight">{{ stats.active_subscriptions || 0 }}</p>
                    <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">Active subscriptions</p>
                </a>
            </div>

            <!-- PLATFORM HEALTH -->
            <div class="bg-white dark:bg-gray-800 rounded-2xl border border-gray-100 dark:border-gray-700 overflow-hidden">
                <div
                    class="px-6 py-4 border-b border-gray-100 dark:border-gray-700 bg-gradient-to-br from-gray-50 to-white dark:from-gray-800 dark:to-gray-800/50 flex items-center gap-3">
                    <div class="w-9 h-9 rounded-xl bg-emerald-50 dark:bg-emerald-900/30 flex items-center justify-center">
                        <svg class="w-4 h-4 text-emerald-600 dark:text-emerald-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z" />
                        </svg>
                    </div>
                    <div>
                        <h3 class="text-sm font-bold text-gray-900 dark:text-white tracking-tight">Platform health</h3>
                        <p class="text-xs text-gray-400 dark:text-gray-500">System status at a glance</p>
                    </div>
                </div>

                <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-4 gap-4 p-6">
                    <!-- Queue depth -->
                    <div class="rounded-xl border border-gray-100 dark:border-gray-700 p-4">
                        <div class="flex items-center justify-between mb-2">
                            <p class="text-[10px] uppercase tracking-widest text-gray-400 dark:text-gray-500 font-bold">Queue depth</p>
                            <span class="w-2 h-2 rounded-full"
                                :class="platformHealth.queue_depth === 0 ? 'bg-emerald-500' : platformHealth.queue_depth < 10 ? 'bg-amber-500' : 'bg-red-500'"></span>
                        </div>
                        <p class="text-2xl font-bold tracking-tight"
                            :class="platformHealth.queue_depth === 0 ? 'text-gray-900 dark:text-white' : platformHealth.queue_depth < 10 ? 'text-amber-600 dark:text-amber-400' : 'text-red-600 dark:text-red-400'">
                            {{ platformHealth.queue_depth || 0 }}
                        </p>
                        <p class="text-xs text-gray-400 dark:text-gray-500 mt-1">jobs pending</p>
                    </div>

                    <!-- Failed jobs -->
                    <div class="rounded-xl border border-gray-100 dark:border-gray-700 p-4">
                        <div class="flex items-center justify-between mb-2">
                            <p class="text-[10px] uppercase tracking-widest text-gray-400 dark:text-gray-500 font-bold">Failed jobs</p>
                            <span class="w-2 h-2 rounded-full"
                                :class="(platformHealth.failed_jobs || 0) === 0 ? 'bg-emerald-500' : 'bg-red-500'"></span>
                        </div>
                        <p class="text-2xl font-bold tracking-tight"
                            :class="(platformHealth.failed_jobs || 0) === 0 ? 'text-gray-900 dark:text-white' : 'text-red-600 dark:text-red-400'">
                            {{ platformHealth.failed_jobs || 0 }}
                        </p>
                        <p class="text-xs text-gray-400 dark:text-gray-500 mt-1">total failures</p>
                    </div>

                    <!-- Disk usage -->
                    <div class="rounded-xl border border-gray-100 dark:border-gray-700 p-4">
                        <div class="flex items-center justify-between mb-2">
                            <p class="text-[10px] uppercase tracking-widest text-gray-400 dark:text-gray-500 font-bold">Disk</p>
                            <span class="w-2 h-2 rounded-full"
                                :class="(platformHealth.disk_used_percent || 0) < 70 ? 'bg-emerald-500' : (platformHealth.disk_used_percent || 0) < 90 ? 'bg-amber-500' : 'bg-red-500'"></span>
                        </div>
                        <p class="text-2xl font-bold tracking-tight"
                            :class="(platformHealth.disk_used_percent || 0) < 70 ? 'text-gray-900 dark:text-white' : (platformHealth.disk_used_percent || 0) < 90 ? 'text-amber-600 dark:text-amber-400' : 'text-red-600 dark:text-red-400'">
                            {{ platformHealth.disk_used_percent || 0 }}%
                        </p>
                        <p class="text-xs text-gray-400 dark:text-gray-500 mt-1">
                            {{ platformHealth.disk_used_gb || 0 }} / {{ platformHealth.disk_total_gb || 0 }} GB
                        </p>
                    </div>

                    <!-- PHP / Laravel versions -->
                    <div class="rounded-xl border border-gray-100 dark:border-gray-700 p-4">
                        <div class="flex items-center justify-between mb-2">
                            <p class="text-[10px] uppercase tracking-widest text-gray-400 dark:text-gray-500 font-bold">Runtime</p>
                            <span class="w-2 h-2 rounded-full bg-emerald-500"></span>
                        </div>
                        <p class="text-lg font-bold tracking-tight text-gray-900 dark:text-white leading-tight">
                            PHP {{ platformHealth.php_version || '?' }}
                        </p>
                        <p class="text-xs text-gray-500 dark:text-gray-400 font-medium mt-1">
                            Laravel {{ platformHealth.laravel_version || '?' }}
                        </p>
                    </div>

                    <!-- ✅ NEW: Scheduler heartbeat -->
                    <div class="rounded-xl border border-gray-100 dark:border-gray-700 p-4">
                        <div class="flex items-center justify-between mb-2">
                            <p class="text-[10px] uppercase tracking-widest text-gray-400 dark:text-gray-500 font-bold">Scheduler</p>
                            <span class="w-2 h-2 rounded-full" :class="{
                                'bg-emerald-500': platformHealth.scheduler_status === 'healthy',
                                'bg-amber-500': platformHealth.scheduler_status === 'stale',
                                'bg-red-500': platformHealth.scheduler_status === 'down',
                            }"></span>
                        </div>
                        <p class="text-lg font-bold tracking-tight leading-tight" :class="{
                            'text-gray-900 dark:text-white': platformHealth.scheduler_status === 'healthy',
                            'text-amber-600 dark:text-amber-400': platformHealth.scheduler_status === 'stale',
                            'text-red-600 dark:text-red-400': platformHealth.scheduler_status === 'down',
                        }">
                            {{ schedulerLabel(platformHealth.scheduler_status) }}
                        </p>
                        <p class="text-xs text-gray-400 dark:text-gray-500 mt-1">
                            {{ schedulerSubLabel(platformHealth.scheduler_status, platformHealth.scheduler_seconds_ago)
                            }}
                        </p>
                    </div>

                    <!-- ✅ NEW: DB size -->
                    <div class="rounded-xl border border-gray-100 dark:border-gray-700 p-4">
                        <div class="flex items-center justify-between mb-2">
                            <p class="text-[10px] uppercase tracking-widest text-gray-400 dark:text-gray-500 font-bold">Database</p>
                            <span class="w-2 h-2 rounded-full"
                                :class="(platformHealth.db_size_percent || 0) < 50 ? 'bg-emerald-500' : (platformHealth.db_size_percent || 0) < 80 ? 'bg-amber-500' : 'bg-red-500'"></span>
                        </div>
                        <p class="text-2xl font-bold tracking-tight text-gray-900 dark:text-white">
                            {{ formatGb(platformHealth.db_size_gb) }}
                        </p>
                        <p class="text-xs text-gray-400 dark:text-gray-500 mt-1">
                            {{ platformHealth.db_size_percent || 0 }}% of disk
                        </p>
                    </div>

                    <!-- ✅ NEW: Storage + logs -->
                    <div class="rounded-xl border border-gray-100 dark:border-gray-700 p-4">
                        <div class="flex items-center justify-between mb-2">
                            <p class="text-[10px] uppercase tracking-widest text-gray-400 dark:text-gray-500 font-bold">Storage</p>
                            <span class="w-2 h-2 rounded-full bg-emerald-500"></span>
                        </div>
                        <p class="text-2xl font-bold tracking-tight text-gray-900 dark:text-white">
                            {{ formatGb(platformHealth.storage_size_gb) }}
                        </p>
                        <p class="text-xs text-gray-400 dark:text-gray-500 mt-1">
                            Logs: {{ formatGb(platformHealth.logs_size_gb) }}
                        </p>
                    </div>

                    <!-- ✅ NEW: Load average -->
                    <div class="rounded-xl border border-gray-100 dark:border-gray-700 p-4">
                        <div class="flex items-center justify-between mb-2">
                            <p class="text-[10px] uppercase tracking-widest text-gray-400 dark:text-gray-500 font-bold">Load avg</p>
                            <span class="w-2 h-2 rounded-full" :class="!platformHealth.load_available
                                ? 'bg-gray-300 dark:bg-gray-600'
                                : (platformHealth.load_1m || 0) < (platformHealth.cpu_cores || 2)
                                    ? 'bg-emerald-500'
                                    : 'bg-amber-500'"></span>
                        </div>
                        <template v-if="platformHealth.load_available">
                            <p class="text-lg font-bold tracking-tight text-gray-900 dark:text-white leading-tight">
                                {{ platformHealth.load_1m }} · {{ platformHealth.load_5m }} · {{ platformHealth.load_15m
                                }}
                            </p>
                            <p class="text-xs text-gray-400 dark:text-gray-500 mt-1">
                                1m · 5m · 15m <span v-if="platformHealth.cpu_cores">({{ platformHealth.cpu_cores }}
                                    cores)</span>
                            </p>
                        </template>
                        <template v-else>
                            <p class="text-lg font-bold tracking-tight text-gray-400 dark:text-gray-500 leading-tight">N/A</p>
                            <p class="text-xs text-gray-400 dark:text-gray-500 mt-1">Windows dev</p>
                        </template>
                    </div>
                </div>
            </div>

            <!-- ==================== ERROR MONITORING ==================== -->
            <div
                class="bg-white dark:bg-gray-800 rounded-2xl border border-gray-100 dark:border-gray-700 overflow-hidden">
                <div
                    class="px-6 py-4 border-b border-gray-100 dark:border-gray-700 bg-gradient-to-br from-gray-50 to-white dark:from-gray-800 dark:to-gray-800/50 flex items-center justify-between gap-3 flex-wrap">
                    <div class="flex items-center gap-3">
                        <div class="w-9 h-9 rounded-xl bg-red-50 dark:bg-red-900/30 flex items-center justify-center">
                            <svg class="w-4 h-4 text-red-600 dark:text-red-400" fill="none" stroke="currentColor"
                                viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                            </svg>
                        </div>
                        <div>
                            <h3 class="text-sm font-bold text-gray-900 dark:text-white tracking-tight">Error monitoring
                            </h3>
                            <p class="text-xs text-gray-400 dark:text-gray-500">Live error stats from Sentry</p>
                        </div>
                    </div>

                    <a v-if="sentryAvailable && sentry.sentry_url" :href="sentry.sentry_url" target="_blank"
                        class="inline-flex items-center gap-1.5 text-xs font-bold text-primary-600 dark:text-primary-400 hover:text-primary-800 dark:hover:text-primary-300 transition-colors">
                        View in Sentry
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14" />
                        </svg>
                    </a>
                </div>

                <!-- Not configured / unavailable -->
                <div v-if="!sentryAvailable" class="p-8 text-center">
                    <div
                        class="w-12 h-12 rounded-full bg-gray-100 dark:bg-gray-700 flex items-center justify-center mx-auto mb-3">
                        <svg class="w-6 h-6 text-gray-400 dark:text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                        </svg>
                    </div>
                    <p class="text-sm font-semibold text-gray-500 dark:text-gray-400">Sentry data unavailable</p>
                    <p class="text-xs text-gray-400 dark:text-gray-500 mt-1">
                        Check <code
                            class="font-mono bg-gray-100 dark:bg-gray-800 px-1.5 py-0.5 rounded">SENTRY_API_TOKEN</code>
                        in your environment.
                    </p>
                </div>

                <!-- Data available -->
                <div v-else class="grid grid-cols-1 sm:grid-cols-3 gap-4 p-6">
                    <!-- Errors 24h -->
                    <div class="rounded-xl border border-gray-100 dark:border-gray-700 p-4">
                        <div class="flex items-center justify-between mb-2">
                            <p class="text-[10px] uppercase tracking-widest text-gray-400 dark:text-gray-500 font-bold">Errors (24h)</p>
                            <span class="w-2 h-2 rounded-full" :class="{
                                'bg-emerald-500': sentryErrorsTone === 'emerald',
                                'bg-amber-500': sentryErrorsTone === 'amber',
                                'bg-red-500': sentryErrorsTone === 'red',
                            }"></span>
                        </div>
                        <p class="text-3xl font-bold tracking-tight" :class="{
                            'text-gray-900 dark:text-white': sentryErrorsTone === 'emerald',
                            'text-amber-600 dark:text-amber-400': sentryErrorsTone === 'amber',
                            'text-red-600 dark:text-red-400': sentryErrorsTone === 'red',
                        }">
                            {{ sentry.errors_24h }}
                        </p>
                        <p v-if="sentryTrendLabel" class="text-xs mt-1 font-semibold" :class="sentryTrendTone">
                            {{ sentryTrendLabel }}
                        </p>
                    </div>

                    <!-- Errors 7d -->
                    <div class="rounded-xl border border-gray-100 dark:border-gray-700 p-4">
                        <div class="flex items-center justify-between mb-2">
                            <p class="text-[10px] uppercase tracking-widest text-gray-400 dark:text-gray-500 font-bold">Errors (7d)</p>
                            <span class="w-2 h-2 rounded-full bg-blue-500"></span>
                        </div>
                        <p class="text-3xl font-bold text-gray-900 dark:text-white tracking-tight">
                            {{ sentry.errors_7d }}
                        </p>
                        <p class="text-xs text-gray-400 dark:text-gray-500 mt-1">last 7 days</p>
                    </div>

                    <!-- Unresolved -->
                    <div class="rounded-xl border border-gray-100 dark:border-gray-700 p-4">
                        <div class="flex items-center justify-between mb-2">
                            <p class="text-[10px] uppercase tracking-widest text-gray-400 dark:text-gray-500 font-bold">Unresolved</p>
                            <span class="w-2 h-2 rounded-full"
                                :class="(sentry.unresolved || 0) === 0 ? 'bg-emerald-500' : 'bg-amber-500'"></span>
                        </div>
                        <p class="text-3xl font-bold tracking-tight" :class="(sentry.unresolved || 0) === 0
                            ? 'text-gray-900 dark:text-white'
                            : 'text-amber-600 dark:text-amber-400'">
                            {{ sentry.unresolved }}
                        </p>
                        <p class="text-xs text-gray-400 dark:text-gray-500 mt-1">open issues</p>
                    </div>
                </div>

                <!-- Top issues (below the tiles) -->
                <div v-if="sentryAvailable && sentry.top_issues && sentry.top_issues.length > 0" class="px-6 pb-6">
                    <p class="text-[10px] uppercase tracking-widest text-gray-400 dark:text-gray-500 font-bold mb-3">Top issues (24h)</p>
                    <div class="space-y-2">
                        <a v-for="issue in sentry.top_issues" :key="issue.id" :href="issue.permalink" target="_blank"
                            class="flex items-start gap-3 p-3 bg-gray-50 dark:bg-gray-900/50 rounded-xl hover:bg-gray-100 dark:hover:bg-gray-900 transition-colors group">
                            <div class="w-7 h-7 rounded-lg flex items-center justify-center flex-shrink-0 mt-0.5"
                                :class="{
                                    'bg-red-100 dark:bg-red-900/30': issue.level === 'error' || issue.level === 'fatal',
                                    'bg-amber-100 dark:bg-amber-900/30': issue.level === 'warning',
                                    'bg-gray-100 dark:bg-gray-700': !['error', 'fatal', 'warning'].includes(issue.level),
                                }">
                                <svg class="w-3.5 h-3.5" :class="{
                                    'text-red-600 dark:text-red-400': issue.level === 'error' || issue.level === 'fatal',
                                    'text-amber-600 dark:text-amber-400': issue.level === 'warning',
                                    'text-gray-500 dark:text-gray-400': !['error', 'fatal', 'warning'].includes(issue.level),
                                }" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                                </svg>
                            </div>
                            <div class="flex-1 min-w-0">
                                <p
                                    class="text-sm font-semibold text-gray-900 dark:text-white truncate group-hover:text-primary-600 dark:group-hover:text-primary-400 transition-colors">
                                    {{ issue.title }}
                                </p>
                                <p v-if="issue.culprit"
                                    class="text-[11px] text-gray-500 dark:text-gray-400 truncate mt-0.5 font-mono">
                                    {{ issue.culprit }}
                                </p>
                            </div>
                            <div class="text-right flex-shrink-0">
                                <p class="text-sm font-bold text-gray-900 dark:text-white">{{ issue.count }}</p>
                                <p class="text-[10px] text-gray-400 dark:text-gray-500">{{ formatRelativeTime(issue.last_seen) }}</p>
                            </div>
                        </a>
                    </div>
                </div>

                <!-- Empty state when no errors -->
                <div v-else-if="sentryAvailable && (!sentry.top_issues || sentry.top_issues.length === 0)"
                    class="px-6 pb-6 text-center">
                    <div
                        class="inline-flex items-center gap-2 px-4 py-3 bg-emerald-50 dark:bg-emerald-900/20 border border-emerald-200 dark:border-emerald-800 rounded-xl">
                        <svg class="w-4 h-4 text-emerald-600 dark:text-emerald-400" fill="none" stroke="currentColor"
                            viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5"
                                d="M5 13l4 4L19 7" />
                        </svg>
                        <span class="text-sm font-semibold text-emerald-800 dark:text-emerald-300">No issues in the last
                            24
                            hours</span>
                    </div>
                </div>
            </div>

            <!-- CHARTS ROW -->
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                <div class="bg-white dark:bg-gray-800 rounded-2xl border border-gray-100 dark:border-gray-700 overflow-hidden">
                    <div
                        class="px-6 py-4 border-b border-gray-100 dark:border-gray-700 bg-gradient-to-br from-gray-50 to-white dark:from-gray-800 dark:to-gray-800/50 flex items-center justify-between">
                        <div>
                            <h3 class="text-sm font-bold text-gray-900 dark:text-white tracking-tight">Revenue trend</h3>
                            <p class="text-xs text-gray-400 dark:text-gray-500">Monthly revenue overview</p>
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

                <div class="bg-white dark:bg-gray-800 rounded-2xl border border-gray-100 dark:border-gray-700 overflow-hidden">
                    <div
                        class="px-6 py-4 border-b border-gray-100 dark:border-gray-700 bg-gradient-to-br from-gray-50 to-white dark:from-gray-800 dark:to-gray-800/50 flex items-center justify-between">
                        <div>
                            <h3 class="text-sm font-bold text-gray-900 dark:text-white tracking-tight">Business growth</h3>
                            <p class="text-xs text-gray-400 dark:text-gray-500">New businesses over time</p>
                        </div>
                        <a href="/admin/businesses"
                            class="text-xs text-primary-600 dark:text-primary-400 hover:text-primary-800 dark:hover:text-primary-300 font-semibold transition-colors">
                            View all →
                        </a>
                    </div>
                    <div class="p-6 h-72">
                        <LineChart :data="charts?.business_growth || []" />
                    </div>
                </div>
            </div>

            <!-- SUBSCRIPTION STATUS + TOP PLANS -->
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                <div class="bg-white dark:bg-gray-800 rounded-2xl border border-gray-100 dark:border-gray-700 overflow-hidden">
                    <div class="px-6 py-4 border-b border-gray-100 dark:border-gray-700 bg-gradient-to-br from-gray-50 to-white dark:from-gray-800 dark:to-gray-800/50">
                        <h3 class="text-sm font-bold text-gray-900 dark:text-white tracking-tight">Subscription status</h3>
                        <p class="text-xs text-gray-400 dark:text-gray-500">Live distribution</p>
                    </div>
                    <div class="p-6 h-72">
                        <DoughnutChart :data="charts?.subscription_status || []" />
                    </div>
                </div>

                <div class="lg:col-span-2 bg-white dark:bg-gray-800 rounded-2xl border border-gray-100 dark:border-gray-700 overflow-hidden">
                    <div
                        class="px-6 py-4 border-b border-gray-100 dark:border-gray-700 bg-gradient-to-br from-gray-50 to-white dark:from-gray-800 dark:to-gray-800/50 flex items-center justify-between">
                        <div>
                            <h3 class="text-sm font-bold text-gray-900 dark:text-white tracking-tight">Top plans</h3>
                            <p class="text-xs text-gray-400 dark:text-gray-500">Most popular subscription plans</p>
                        </div>
                        <a href="/admin/plans"
                            class="text-xs text-primary-600 dark:text-primary-400 hover:text-primary-800 dark:hover:text-primary-300 font-semibold transition-colors">
                            View all →
                        </a>
                    </div>
                    <div class="p-6 space-y-3">
                        <div v-for="plan in topPlans" :key="plan.id"
                            class="flex items-center justify-between p-4 bg-gray-50 dark:bg-gray-900/50 rounded-xl hover:bg-gray-100 dark:hover:bg-gray-900 transition-colors">
                            <div class="min-w-0">
                                <div class="flex items-center gap-2 flex-wrap">
                                    <p class="font-semibold text-gray-900 dark:text-white tracking-tight">{{ plan.name }}</p>
                                    <span v-if="plan.is_featured"
                                        class="text-[10px] font-bold uppercase tracking-wide bg-amber-100 dark:bg-amber-900/30 text-amber-700 dark:text-amber-400 px-2 py-0.5 rounded-full">
                                        ⭐ Featured
                                    </span>
                                </div>
                                <p class="text-xs text-gray-500 dark:text-gray-400 truncate">{{ plan.description || 'No description' }}</p>
                            </div>
                            <div class="text-right flex-shrink-0 ml-3">
                                <p class="text-sm font-bold text-gray-900 dark:text-white">{{ plan.subscriptions_count }} subs</p>
                                <p class="text-xs text-gray-500 dark:text-gray-400">{{ formatPrice(plan.price_yearly) }}/yr</p>
                            </div>
                        </div>
                        <div v-if="!topPlans || topPlans.length === 0" class="text-center py-8 text-gray-500 dark:text-gray-400 text-sm">
                            No plans available
                        </div>
                    </div>
                </div>
            </div>

            <!-- RECENT ACTIVITY -->
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
                            class="px-6 py-4 hover:bg-gray-50/50 dark:hover:bg-gray-700/30 transition-colors flex items-center justify-between gap-3">
                            <div class="flex items-center gap-3 min-w-0">
                                <div
                                    class="w-9 h-9 rounded-2xl bg-gradient-to-br from-primary-500 to-primary-700 flex items-center justify-center text-white text-xs font-bold flex-shrink-0 shadow-md">
                                    {{ getInitials(business.name) }}
                                </div>
                                <div class="min-w-0">
                                    <p class="text-sm font-semibold text-gray-900 dark:text-white truncate">{{ business.name }}</p>
                                    <p class="text-xs text-gray-400 dark:text-gray-500 truncate">by {{ business.owner?.name || 'Unknown' }}
                                    </p>
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
                            class="px-6 py-4 hover:bg-gray-50/50 dark:hover:bg-gray-700/30 transition-colors flex items-center justify-between gap-3">
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
                                <p class="text-xs text-gray-400 dark:text-gray-500">via {{ transaction.payment_method || 'Fapshi' }}</p>
                            </div>
                        </div>
                    </div>
                    <div v-else class="px-6 py-12 text-center">
                        <p class="text-sm text-gray-500 dark:text-gray-400">No recent transactions</p>
                    </div>
                </div>
            </div>

            <!-- ==================== DANGER ZONE ==================== -->
            <div
                class="bg-gradient-to-br from-red-50 to-red-50/50 dark:from-red-950/20 dark:to-red-950/10 border border-red-200 dark:border-red-900/50 rounded-2xl overflow-hidden">
                <div
                    class="px-6 py-4 border-b border-red-200 dark:border-red-900/40 bg-gradient-to-br from-red-100/50 to-white dark:from-red-950/40 dark:to-red-950/20 flex items-center gap-3">
                    <div class="w-9 h-9 rounded-xl bg-red-100 dark:bg-red-900/40 flex items-center justify-center">
                        <svg class="w-4 h-4 text-red-600 dark:text-red-400" fill="none" stroke="currentColor"
                            viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                        </svg>
                    </div>
                    <div>
                        <h3 class="text-sm font-bold text-red-900 dark:text-red-300 tracking-tight">Danger zone</h3>
                        <p class="text-xs text-red-700 dark:text-red-400">Platform-level actions — use with care</p>
                    </div>
                </div>

                <div class="p-6 grid grid-cols-1 lg:grid-cols-3 gap-5">

                    <!-- MAINTENANCE MODE -->
                    <div
                        class="bg-white dark:bg-gray-800 rounded-2xl border border-red-200 dark:border-red-900/40 p-5 flex flex-col">
                        <div class="flex items-center justify-between mb-3">
                            <div class="flex items-center gap-2">
                                <span class="w-2.5 h-2.5 rounded-full"
                                    :class="maintenanceActive ? 'bg-red-500 animate-pulse' : 'bg-emerald-500'"></span>
                                <p class="text-[10px] uppercase tracking-widest font-bold"
                                    :class="maintenanceActive ? 'text-red-600 dark:text-red-400' : 'text-emerald-600 dark:text-emerald-400'">
                                    {{ maintenanceActive ? 'Active' : 'Live' }}
                                </p>
                            </div>
                            <svg class="w-4 h-4 text-red-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M12 9v2m0 4h.01M5.07 19h13.86c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                            </svg>
                        </div>

                        <h4 class="text-base font-bold text-gray-900 dark:text-white tracking-tight">Maintenance mode
                        </h4>
                        <p class="text-xs text-gray-500 dark:text-gray-400 mt-1 mb-4">
                            <template v-if="maintenanceActive">
                                Public sees 503. Only allowlisted IPs can access — or use the bypass URL below.
                                <span class="block mt-1.5 text-[11px] text-gray-400 dark:text-gray-500">
                                    Started {{ maintenanceSince }}<span v-if="maintenance.by"> by {{ maintenance.by
                                    }}</span>
                                </span>
                            </template>
                            <template v-else>
                                Turn on to show a 503 page to the public while you deploy or make changes.
                            </template>
                        </p>

                        <!-- Bypass URL (only visible when active) -->
                        <div v-if="maintenanceActive && maintenance.bypass_url"
                            class="mb-4 p-3 bg-amber-50 dark:bg-amber-900/20 border border-amber-200 dark:border-amber-800 rounded-xl">
                            <p
                                class="text-[10px] uppercase tracking-widest font-bold text-amber-800 dark:text-amber-300 mb-1.5">
                                Bypass URL</p>
                            <p class="text-[11px] text-amber-700 dark:text-amber-400 mb-2 leading-relaxed">
                                Visit this URL once to keep your browser signed in during maintenance.
                            </p>
                            <div class="flex items-center gap-1.5">
                                <input type="text" :value="maintenance.bypass_url" readonly
                                    class="flex-1 px-2 py-1.5 text-[11px] font-mono bg-white dark:bg-gray-900 border border-amber-200 dark:border-amber-800 rounded-lg text-gray-700 dark:text-gray-300 truncate" />
                                <button @click="copyBypassUrl"
                                    class="inline-flex items-center gap-1 px-2.5 py-1.5 bg-amber-600 hover:bg-amber-700 text-white rounded-lg text-[11px] font-bold transition-colors flex-shrink-0">
                                    <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z" />
                                    </svg>
                                    Copy
                                </button>
                            </div>
                        </div>

                        <div class="flex-1"></div>

                        <button @click="toggleMaintenance"
                            :class="maintenanceActive
                                ? 'bg-gradient-to-r from-emerald-500 to-emerald-600 hover:from-emerald-600 hover:to-emerald-700 shadow-emerald-500/25'
                                : 'bg-gradient-to-r from-red-500 to-red-600 hover:from-red-600 hover:to-red-700 shadow-red-500/25'"
                            class="w-full inline-flex items-center justify-center gap-2 px-4 py-2.5 text-white rounded-xl font-semibold text-sm transition-all shadow-lg">
                            <svg v-if="!maintenanceActive" class="w-4 h-4" fill="none" stroke="currentColor"
                                viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" />
                            </svg>
                            <svg v-else class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M8 11V7a4 4 0 118 0m-4 8v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2z" />
                            </svg>
                            {{ maintenanceActive ? 'Turn Off Maintenance' : 'Turn On Maintenance' }}
                        </button>
                    </div>

                    <!-- CACHE -->
                    <div
                        class="bg-white dark:bg-gray-800 rounded-2xl border border-red-200 dark:border-red-900/40 p-5 flex flex-col">
                        <div class="flex items-center justify-between mb-3">
                            <p class="text-[10px] uppercase tracking-widest font-bold text-gray-500 dark:text-gray-400">
                                Cache
                            </p>
                            <svg class="w-4 h-4 text-red-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M4 7v10c0 2.21 3.582 4 8 4s8-1.79 8-4V7M4 7c0 2.21 3.582 4 8 4s8-1.79 8-4M4 7c0-2.21 3.582-4 8-4s8 1.79 8 4" />
                            </svg>
                        </div>

                        <h4 class="text-base font-bold text-gray-900 dark:text-white tracking-tight">Cache maintenance
                        </h4>
                        <p class="text-xs text-gray-500 dark:text-gray-400 mt-1 mb-4 flex-1">
                            Clear the application cache, or rebuild compiled config, routes, and views.
                        </p>

                        <div class="space-y-2">
                            <button @click="clearCache"
                                class="w-full inline-flex items-center justify-center gap-2 px-4 py-2.5 bg-gradient-to-r from-amber-500 to-orange-500 hover:from-amber-600 hover:to-orange-600 text-white rounded-xl font-semibold text-sm transition-all shadow-lg shadow-amber-500/25">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                </svg>
                                Clear Cache
                            </button>
                            <button @click="optimizeClear"
                                class="w-full inline-flex items-center justify-center gap-2 px-4 py-2.5 border border-gray-300 dark:border-gray-600 text-gray-700 dark:text-gray-300 rounded-xl font-semibold text-sm hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                                </svg>
                                Optimize Clear
                            </button>
                        </div>
                    </div>

                    <!-- QUEUE -->
                    <div
                        class="bg-white dark:bg-gray-800 rounded-2xl border border-red-200 dark:border-red-900/40 p-5 flex flex-col">
                        <div class="flex items-center justify-between mb-3">
                            <p class="text-[10px] uppercase tracking-widest font-bold text-gray-500 dark:text-gray-400">
                                Queue
                            </p>
                            <svg class="w-4 h-4 text-red-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M4 6h16M4 10h16M4 14h16M4 18h16" />
                            </svg>
                        </div>

                        <h4 class="text-base font-bold text-gray-900 dark:text-white tracking-tight">Queue workers</h4>
                        <p class="text-xs text-gray-500 dark:text-gray-400 mt-1 mb-4 flex-1">
                            Gracefully restart all queue workers. Active jobs finish, then workers reload with fresh
                            code.
                        </p>

                        <button @click="restartQueue"
                            class="w-full inline-flex items-center justify-center gap-2 px-4 py-2.5 bg-gradient-to-r from-blue-500 to-blue-600 hover:from-blue-600 hover:to-blue-700 text-white rounded-xl font-semibold text-sm transition-all shadow-lg shadow-blue-500/25">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                            </svg>
                            Restart Queue Workers
                        </button>
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
    import { useConfirm } from '@/composables/useConfirm';
    import { useToast } from '@/composables/useToast';

    const props = defineProps({
        stats: Object,
        platformHealth: { type: Object, default: () => ({}) },
        charts: Object,
        recentBusinesses: Array,
        topPlans: Array,
        recentPayments: Array,
        maintenance: {
            type: Object,
            default: () => ({ active: false, since: null, by: null, allow_ips: [], bypass_url: null }),
        },
        sentry: {
            type: Object,
            default: null,
        },
    });

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

    const getInitials = (name) => {
        if (!name) return '?';
        return name
            .split(' ')
            .map((n) => n[0])
            .join('')
            .toUpperCase()
            .slice(0, 2);
    };

    const schedulerLabel = (status) => {
        if (status === 'healthy') return 'Running';
        if (status === 'stale') return 'Delayed';
        if (status === 'down') return 'Not running';
        return 'Unknown';
    };

    const schedulerSubLabel = (status, secondsAgo) => {
        if (status === 'down' && (!secondsAgo || secondsAgo === null)) {
            return 'Never triggered';
        }
        if (typeof secondsAgo === 'number') {
            if (secondsAgo < 60) return `${secondsAgo}s ago`;
            if (secondsAgo < 3600) return `${Math.floor(secondsAgo / 60)}m ago`;
            return `${Math.floor(secondsAgo / 3600)}h ago`;
        }
        return '';
    };

    const formatGb = (gb) => {
        if (gb === null || gb === undefined) return '—';
        const n = Number(gb);
        if (n < 0.01) return '<0.01 GB';
        return `${n.toFixed(2)} GB`;
    };

    const { business: statusClass, label: statusLabel } = useStatusBadge();

    const refreshData = () => {
        // ✅ Inertia reload — preserves scroll, no full browser refresh.
        //    Matches the pattern used on Admin/Dashboard.
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

    // ============== DANGER ZONE ==============
    const { confirm: confirmDialog } = useConfirm();
    const { success, error: showError, info } = useToast();

    const maintenanceActive = computed(() => !!props.maintenance?.active);

    const maintenanceSince = computed(() => {
        if (!props.maintenance?.since) return null;
        return new Date(props.maintenance.since).toLocaleString('en-US', {
            month: 'short',
            day: 'numeric',
            year: 'numeric',
            hour: '2-digit',
            minute: '2-digit',
        });
    });

    const toggleMaintenance = async () => {
        if (maintenanceActive.value) {
            // Turn OFF — standard danger confirm
            const confirmed = await confirmDialog({
                title: 'Turn maintenance mode OFF?',
                message: 'The platform will immediately become live again for all users.',
                confirmText: 'Turn Off',
                cancelText: 'Cancel',
                variant: 'danger',
            });
            if (!confirmed) return;

            router.post('/admin/actions/maintenance', { action: 'off' }, {
                preserveScroll: true,
                onSuccess: () => {
                    // Flash handles the success toast
                },
                onError: () => {
                    showError('Failed', 'Could not turn off maintenance mode.', { duration: 4000 });
                },
            });
        } else {
            // Turn ON — typed confirmation
            const confirmed = await confirmDialog({
                title: 'Turn maintenance mode ON?',
                message: 'The public site will return a 503 page to everyone except your IP address.\n\nType MAINTENANCE to confirm.',
                confirmText: 'Turn On',
                cancelText: 'Cancel',
                variant: 'danger',
                requireTyping: true,
                typingText: 'MAINTENANCE',
            });
            if (!confirmed) return;

            router.post('/admin/actions/maintenance', { action: 'on' }, {
                preserveScroll: true,
                onSuccess: () => {
                    // Flash handles the success toast
                },
                onError: () => {
                    showError('Failed', 'Could not turn on maintenance mode.', { duration: 4000 });
                },
            });
        }
    };

    const clearCache = async () => {
        const confirmed = await confirmDialog({
            title: 'Clear application cache?',
            message: 'This clears the application cache (Redis/file/database). Safe — the cache will repopulate on demand.',
            confirmText: 'Clear Cache',
            cancelText: 'Cancel',
            variant: 'primary',
        });
        if (!confirmed) return;

        router.post('/admin/actions/cache-clear', {}, {
            preserveScroll: true,
            onError: () => {
                showError('Failed', 'Could not clear cache.', { duration: 4000 });
            },
        });
    };

    const optimizeClear = async () => {
        const confirmed = await confirmDialog({
            title: 'Clear compiled & cached files?',
            message: 'This clears config, route, view, and event caches. Safe — Laravel will rebuild them on the next request.',
            confirmText: 'Optimize Clear',
            cancelText: 'Cancel',
            variant: 'primary',
        });
        if (!confirmed) return;

        router.post('/admin/actions/optimize-clear', {}, {
            preserveScroll: true,
            onError: () => {
                showError('Failed', 'Could not run optimize:clear.', { duration: 4000 });
            },
        });
    };

    const restartQueue = async () => {
        // No confirmation — safe, graceful
        router.post('/admin/actions/queue-restart', {}, {
            preserveScroll: true,
            onError: () => {
                showError('Failed', 'Could not restart queue workers.', { duration: 4000 });
            },
        });
    };

    // ============== SENTRY ERROR HEALTH ==============
    const sentryAvailable = computed(() => !!props.sentry);

    const sentryErrorsTone = computed(() => {
        const n = props.sentry?.errors_24h ?? 0;
        if (n === 0) return 'emerald';
        if (n < 5) return 'amber';
        return 'red';
    });

    const sentryTrendLabel = computed(() => {
        const t = props.sentry?.trend;
        if (t === null || t === undefined) return null;
        if (t === 0) return 'No change';
        return `${t > 0 ? '↑' : '↓'} ${Math.abs(t)}% vs prev 24h`;
    });

    const sentryTrendTone = computed(() => {
        const t = props.sentry?.trend ?? 0;
        if (t > 0) return 'text-red-600 dark:text-red-400';
        if (t < 0) return 'text-emerald-600 dark:text-emerald-400';
        return 'text-gray-500 dark:text-gray-400';
    });

    const formatRelativeTime = (iso) => {
        if (!iso) return '';
        const date = new Date(iso);
        const now = new Date();
        const diffSec = Math.floor((now - date) / 1000);

        if (diffSec < 60) return 'just now';
        if (diffSec < 3600) return `${Math.floor(diffSec / 60)}m ago`;
        if (diffSec < 86400) return `${Math.floor(diffSec / 3600)}h ago`;
        return `${Math.floor(diffSec / 86400)}d ago`;
    };

    // ============== COPY BYPASS URL ==============
    const copyBypassUrl = async () => {
        const url = props.maintenance?.bypass_url;
        if (!url) return;

        try {
            if (navigator.clipboard && window.isSecureContext) {
                await navigator.clipboard.writeText(url);
            } else {
                // Fallback for non-secure contexts (localhost over HTTP)
                const ta = document.createElement('textarea');
                ta.value = url;
                ta.style.position = 'fixed';
                ta.style.opacity = '0';
                document.body.appendChild(ta);
                ta.select();
                document.execCommand('copy');
                document.body.removeChild(ta);
            }
            success('Copied!', 'Bypass URL copied to clipboard.', { duration: 2000 });
        } catch (e) {
            showError('Copy failed', 'Could not copy to clipboard. Please copy manually.', { duration: 3000 });
        }
    };
</script>