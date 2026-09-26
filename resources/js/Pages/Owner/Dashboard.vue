<!-- resources/js/Pages/Owner/Dashboard.vue -->
<template>
     <AuthenticatedLayout>
          <!-- COMPACT HEADER -->
          <PageHeader color="primary" :breadcrumb="[{ label: 'Dashboard' }]">
               <template #icon>
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                         d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6" />
               </template>
               <template #title>Welcome back, {{ user?.name || "User" }}</template>
               <template #subtitle>
                    <span class="inline-flex items-center gap-1.5">
                         <span class="w-1.5 h-1.5 rounded-full" :class="user?.status === 'active'
                              ? 'bg-emerald-500 animate-pulse'
                              : 'bg-amber-500'
                              "></span>
                         {{
                              user?.status === "active"
                                   ? "Account active"
                                   : "Account pending approval"
                         }}
                    </span>
               </template>
               <template #actions>
                    <BusinessSelector v-if="businesses && businesses.length > 1" :businesses="businesses"
                         :selected-id="selectedBusinessId" />
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

          <PullToRefresh ref="ptrRef" @refresh="refreshData">
               <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8 space-y-6">
                    <!-- CONSOLIDATED STATUS BANNER (priority-ordered) -->
                    <div v-if="topBanner"
                         class="rounded-2xl p-5 border flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4"
                         :class="topBanner.classes">
                         <div class="flex items-start gap-4">
                              <div class="w-11 h-11 rounded-xl flex items-center justify-center flex-shrink-0"
                                   :class="topBanner.iconBg">
                                   <span v-html="topBanner.icon" class="w-5 h-5" :class="topBanner.iconColor"></span>
                              </div>
                              <div>
                                   <p class="text-sm font-bold" :class="topBanner.titleColor">
                                        {{ topBanner.title }}
                                   </p>
                                   <p class="text-sm mt-0.5" :class="topBanner.bodyColor">
                                        {{ topBanner.body }}
                                   </p>
                              </div>
                         </div>
                         <a :href="topBanner.actionHref"
                              class="flex-shrink-0 inline-flex items-center gap-2 px-5 py-2.5 rounded-xl font-semibold transition-all shadow-lg hover:-translate-y-0.5 text-sm"
                              :class="topBanner.actionClasses">
                              {{ topBanner.actionLabel }}
                              <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                   <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M9 5l7 7-7 7" />
                              </svg>
                         </a>
                    </div>

                    <!-- PLAN OVERFLOW BANNER (downgrade grace / hidden items) -->
                    <div v-if="planOverflow && planOverflow.isOver"
                         class="rounded-2xl p-5 border flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4"
                         :class="planOverflow.graceActive
                              ? 'bg-gradient-to-br from-orange-50 to-amber-50 dark:from-orange-900/20 dark:to-amber-900/10 border-orange-200 dark:border-orange-800'
                              : 'bg-gradient-to-br from-red-50 to-rose-50 dark:from-red-900/20 dark:to-rose-900/10 border-red-200 dark:border-red-800'
                              ">
                         <div class="flex items-start gap-4">
                              <div class="w-11 h-11 rounded-xl flex items-center justify-center flex-shrink-0" :class="planOverflow.graceActive
                                   ? 'bg-orange-100 dark:bg-orange-900/40'
                                   : 'bg-red-100 dark:bg-red-900/40'
                                   ">
                                   <svg class="w-5 h-5" :class="planOverflow.graceActive
                                        ? 'text-orange-600 dark:text-orange-400'
                                        : 'text-red-600 dark:text-red-400'
                                        " fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                             d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                                   </svg>
                              </div>
                              <div class="min-w-0">
                                   <p class="text-sm font-bold" :class="planOverflow.graceActive
                                        ? 'text-orange-800 dark:text-orange-300'
                                        : 'text-red-800 dark:text-red-300'
                                        ">
                                        {{
                                             planOverflow.graceActive
                                                  ? "Plan downgrade — action needed"
                                                  : "Over your plan limit"
                                        }}
                                   </p>
                                   <p class="text-sm mt-0.5" :class="planOverflow.graceActive
                                        ? 'text-orange-700 dark:text-orange-400'
                                        : 'text-red-700 dark:text-red-400'
                                        ">
                                        <template v-if="planOverflow.graceActive">
                                             You're over your plan limit for
                                             {{ overLimitCount }} resource{{
                                                  overLimitCount === 1 ? "" : "s"
                                             }}.
                                             <strong>{{ planOverflow.totalOver }}</strong>
                                             item{{ planOverflow.totalOver === 1 ? "" : "s" }}
                                             will be hidden on
                                             <strong>{{
                                                  formatDate(planOverflow.graceEndsAt)
                                             }}</strong>.
                                        </template>
                                        <template v-else>
                                             <strong>{{ planOverflow.totalOver }}</strong>
                                             item{{ planOverflow.totalOver === 1 ? "" : "s" }}
                                             {{
                                                  planOverflow.totalOver === 1 ? "is" : "are"
                                             }}
                                             hidden from the public. Delete them or upgrade to
                                             restore.
                                        </template>
                                   </p>
                              </div>
                         </div>
                         <div class="flex items-center gap-2 flex-wrap flex-shrink-0">
                              <a href="/owner/usage"
                                   class="inline-flex items-center gap-2 px-4 py-2 border rounded-xl text-sm font-semibold transition-colors"
                                   :class="planOverflow.graceActive
                                        ? 'border-orange-300 dark:border-orange-700 text-orange-700 dark:text-orange-400 hover:bg-orange-100 dark:hover:bg-orange-900/30'
                                        : 'border-red-300 dark:border-red-700 text-red-700 dark:text-red-400 hover:bg-red-100 dark:hover:bg-red-900/30'
                                        ">
                                   View details
                              </a>
                              <a href="/owner/subscription/renew"
                                   class="inline-flex items-center gap-2 px-5 py-2.5 rounded-xl font-semibold transition-all shadow-lg hover:-translate-y-0.5 text-sm text-white"
                                   :class="planOverflow.graceActive
                                        ? 'bg-gradient-to-r from-orange-500 to-amber-500 hover:from-orange-600 hover:to-amber-600 shadow-orange-500/25'
                                        : 'bg-gradient-to-r from-red-500 to-rose-500 hover:from-red-600 hover:to-rose-600 shadow-red-500/25'
                                        ">
                                   Upgrade plan
                                   <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                             d="M9 5l7 7-7 7" />
                                   </svg>
                              </a>
                         </div>
                    </div>

                    <!-- NEEDS ATTENTION STRIP -->
                    <div v-if="attentionItems.length > 0" class="bg-white dark:bg-gray-800 rounded-2xl border border-gray-100 dark:border-gray-700 p-4">
                         <div class="flex items-center gap-3 mb-3">
                              <div class="w-8 h-8 rounded-lg bg-rose-50 dark:bg-rose-900/30 flex items-center justify-center">
                                   <svg class="w-4 h-4 text-rose-600 dark:text-rose-400" fill="none" stroke="currentColor"
                                        viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                             d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9" />
                                   </svg>
                              </div>
                              <div>
                                   <h2 class="text-sm font-bold text-gray-900 dark:text-white tracking-tight">
                                        Needs attention
                                   </h2>
                                   <p class="text-xs text-gray-400 dark:text-gray-500">
                                        {{ attentionItems.length }} item{{
                                             attentionItems.length !== 1 ? "s" : ""
                                        }}
                                        require{{
                                             attentionItems.length === 1 ? "s" : ""
                                        }}
                                        your review
                                   </p>
                              </div>
                         </div>
                         <div class="flex flex-wrap gap-2">
                              <a v-for="item in attentionItems" :key="item.key" :href="item.href"
                                   class="inline-flex items-center gap-2 px-3.5 py-2 rounded-xl border text-sm font-semibold transition-all hover:-translate-y-0.5 hover:shadow-sm"
                                   :class="item.classes">
                                   <span class="text-base">{{ item.icon }}</span>
                                   <span>{{ item.label }}</span>
                                   <span class="inline-flex items-center justify-center min-w-[22px] h-[22px] px-1.5 rounded-full text-[11px] font-bold"
                                        :class="item.countClasses">
                                        {{ item.count }}
                                   </span>
                              </a>
                         </div>
                    </div>

                    <!-- ✅ PROFILE COMPLETENESS -->
                    <div v-if="hasBusiness && completeness && completeness.score < 100"
                         class="bg-white dark:bg-gray-800 rounded-2xl border border-gray-100 dark:border-gray-700 overflow-hidden">
                         <div class="p-5 sm:p-6">

                              <!-- Header -->
                              <div class="flex items-start justify-between gap-3 mb-4 flex-wrap">
                                   <div class="flex items-center gap-3 min-w-0">
                                        <div class="w-10 h-10 rounded-xl flex items-center justify-center flex-shrink-0"
                                             :class="{
                                                  'bg-emerald-50 dark:bg-emerald-900/30': completeness.tier.color === 'emerald',
                                                  'bg-blue-50 dark:bg-blue-900/30': completeness.tier.color === 'blue',
                                                  'bg-amber-50 dark:bg-amber-900/30': completeness.tier.color === 'amber',
                                                  'bg-red-50 dark:bg-red-900/30': completeness.tier.color === 'red',
                                                  'bg-gray-100 dark:bg-gray-700': completeness.tier.color === 'gray',
                                             }">
                                             <svg class="w-5 h-5" :class="{
                                                  'text-emerald-600 dark:text-emerald-400': completeness.tier.color === 'emerald',
                                                  'text-blue-600 dark:text-blue-400': completeness.tier.color === 'blue',
                                                  'text-amber-600 dark:text-amber-400': completeness.tier.color === 'amber',
                                                  'text-red-600 dark:text-red-400': completeness.tier.color === 'red',
                                                  'text-gray-600 dark:text-gray-400': completeness.tier.color === 'gray',
                                             }" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                       d="M9 12l2 2 4-4M7.835 4.697a3.42 3.42 0 001.946-.806 3.42 3.42 0 014.438 0 3.42 3.42 0 001.946.806 3.42 3.42 0 013.138 3.138 3.42 3.42 0 00.806 1.946 3.42 3.42 0 010 4.438 3.42 3.42 0 00-.806 1.946 3.42 3.42 0 01-3.138 3.138 3.42 3.42 0 00-1.946.806 3.42 3.42 0 01-4.438 0 3.42 3.42 0 00-1.946-.806 3.42 3.42 0 01-3.138-3.138 3.42 3.42 0 00-.806-1.946 3.42 3.42 0 010-4.438 3.42 3.42 0 00.806-1.946 3.42 3.42 0 013.138-3.138z" />
                                             </svg>
                                        </div>
                                        <div class="min-w-0">
                                             <h3 class="text-sm font-bold text-gray-900 dark:text-white tracking-tight">
                                                  Profile completeness
                                             </h3>
                                             <p class="text-xs text-gray-500 dark:text-gray-400 truncate">
                                                  {{ completeness.tier.message }}
                                             </p>
                                        </div>
                                   </div>

                                   <div class="text-right flex-shrink-0">
                                        <p class="text-3xl font-bold tracking-tight" :class="{
                                             'text-emerald-600 dark:text-emerald-400': completeness.tier.color === 'emerald',
                                             'text-blue-600 dark:text-blue-400': completeness.tier.color === 'blue',
                                             'text-amber-600 dark:text-amber-400': completeness.tier.color === 'amber',
                                             'text-red-600 dark:text-red-400': completeness.tier.color === 'red',
                                             'text-gray-600 dark:text-gray-400': completeness.tier.color === 'gray',
                                        }">
                                             {{ completeness.score }}%
                                        </p>
                                   </div>
                              </div>

                              <!-- Progress bar -->
                              <div class="w-full h-2.5 bg-gray-100 dark:bg-gray-700 rounded-full overflow-hidden mb-5">
                                   <div class="h-full rounded-full transition-all duration-500" :class="{
                                        'bg-gradient-to-r from-emerald-400 to-emerald-500': completeness.tier.color === 'emerald',
                                        'bg-gradient-to-r from-blue-400 to-blue-500': completeness.tier.color === 'blue',
                                        'bg-gradient-to-r from-amber-400 to-amber-500': completeness.tier.color === 'amber',
                                        'bg-gradient-to-r from-red-400 to-red-500': completeness.tier.color === 'red',
                                        'bg-gradient-to-r from-gray-400 to-gray-500': completeness.tier.color === 'gray',
                                   }" :style="{ width: Math.max(4, completeness.score) + '%' }"></div>
                              </div>

                              <!-- Missing items (top 3 by weight) -->
                              <div v-if="completeness.missing.length > 0" class="space-y-2 mb-4">
                                   <div v-for="item in completeness.missing.slice(0, 3)" :key="item.key"
                                        class="flex items-center gap-3 p-2.5 rounded-xl bg-gray-50 dark:bg-gray-900/50 border border-gray-100 dark:border-gray-700">
                                        <div
                                             class="w-6 h-6 rounded-full bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-600 flex items-center justify-center flex-shrink-0">
                                             <svg class="w-3 h-3 text-gray-400 dark:text-gray-500" fill="none" stroke="currentColor"
                                                  viewBox="0 0 24 24">
                                                  <path stroke-linecap="round" stroke-linejoin="round"
                                                       stroke-width="2.5" d="M12 4v16m8-8H4" />
                                             </svg>
                                        </div>
                                        <div class="flex-1 min-w-0">
                                             <p class="text-xs font-bold text-gray-900 dark:text-white truncate">{{
                                                  item.label }}
                                             </p>
                                             <p class="text-[10px] text-gray-500 dark:text-gray-400 truncate">{{
                                                  item.hint }}</p>
                                        </div>
                                        <span
                                             class="text-[10px] font-bold uppercase tracking-wide text-primary-600 dark:text-primary-400 flex-shrink-0">
                                             +{{ item.weight }}%
                                        </span>
                                   </div>
                                   <p v-if="completeness.missing.length > 3"
                                        class="text-[11px] text-gray-500 dark:text-gray-400 text-center pt-1">
                                        +{{ completeness.missing.length - 3 }} more to complete
                                   </p>
                              </div>

                              <!-- CTA -->
                              <a v-if="business" :href="`/owner/businesses/${business.id}/edit`"
                                   class="w-full inline-flex items-center justify-center gap-2 px-5 py-2.5 bg-gradient-to-r from-primary-600 to-primary-700 text-white rounded-xl font-semibold text-sm hover:from-primary-700 hover:to-primary-800 transition-all shadow-lg shadow-primary-500/25 hover:-translate-y-0.5">
                                   Complete your profile
                                   <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                             d="M9 5l7 7-7 7" />
                                   </svg>
                              </a>
                         </div>
                    </div>

                    <!-- KPI CARDS (with deltas + click-through) -->
                    <SkeletonLoader v-if="hasBusiness && isRefreshing" variant="kpi" :count="4" />
                    <div v-else-if="hasBusiness" class="grid grid-cols-2 sm:grid-cols-4 gap-4">
                         <a href="/owner/analytics"
                              class="bg-white dark:bg-gray-800 rounded-2xl border border-gray-100 dark:border-gray-700 p-5 hover:shadow-md hover:-translate-y-0.5 hover:border-primary-200 dark:hover:border-primary-800 transition-all group">
                              <div class="flex items-center justify-between mb-2">
                                   <p class="text-[10px] uppercase tracking-widest text-gray-400 dark:text-gray-500 font-bold">
                                        Total views
                                   </p>
                                   <svg class="w-4 h-4 text-gray-300 dark:text-gray-600 group-hover:text-primary-500 transition-colors"
                                        fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                             d="M9 5l7 7-7 7" />
                                   </svg>
                              </div>
                              <p class="text-2xl font-bold text-gray-900 dark:text-white tracking-tight">
                                   {{ formatNumber(analytics?.stats?.total_views || 0) }}
                              </p>
                              <p class="text-xs mt-1 font-semibold" :class="analytics?.comparison?.growth >= 0
                                   ? 'text-emerald-600 dark:text-emerald-400'
                                   : 'text-red-600 dark:text-red-400'
                                   ">
                                   {{ analytics?.comparison?.growth >= 0 ? "↑" : "↓" }}
                                   {{ Math.abs(analytics?.comparison?.growth || 0) }}% vs last
                                   period
                              </p>
                         </a>
                         <a href="/owner/analytics"
                              class="bg-white dark:bg-gray-800 rounded-2xl border border-gray-100 dark:border-gray-700 p-5 hover:shadow-md hover:-translate-y-0.5 hover:border-primary-200 dark:hover:border-primary-800 transition-all group">
                              <div class="flex items-center justify-between mb-2">
                                   <p class="text-[10px] uppercase tracking-widest text-gray-400 dark:text-gray-500 font-bold">
                                        Unique visitors
                                   </p>
                                   <svg class="w-4 h-4 text-gray-300 dark:text-gray-600 group-hover:text-primary-500 transition-colors"
                                        fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                             d="M9 5l7 7-7 7" />
                                   </svg>
                              </div>
                              <p class="text-2xl font-bold text-gray-900 dark:text-white tracking-tight">
                                   {{
                                        formatNumber(
                                             analytics?.stats?.total_unique_visitors || 0
                                        )
                                   }}
                              </p>
                              <p class="text-xs text-gray-400 dark:text-gray-500 mt-1">Last 30 days</p>
                         </a>
                         <a href="/owner/analytics"
                              class="bg-white dark:bg-gray-800 rounded-2xl border border-gray-100 dark:border-gray-700 p-5 hover:shadow-md hover:-translate-y-0.5 hover:border-primary-200 dark:hover:border-primary-800 transition-all group">
                              <div class="flex items-center justify-between mb-2">
                                   <p class="text-[10px] uppercase tracking-widest text-gray-400 dark:text-gray-500 font-bold">
                                        Phone clicks
                                   </p>
                                   <svg class="w-4 h-4 text-gray-300 dark:text-gray-600 group-hover:text-primary-500 transition-colors"
                                        fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                             d="M9 5l7 7-7 7" />
                                   </svg>
                              </div>
                              <p class="text-2xl font-bold text-gray-900 dark:text-white tracking-tight">
                                   {{
                                        formatNumber(analytics?.stats?.total_phone_clicks || 0)
                                   }}
                              </p>
                              <p class="text-xs text-gray-400 dark:text-gray-500 mt-1">Total calls made</p>
                         </a>
                         <a href="/owner/analytics"
                              class="bg-white dark:bg-gray-800 rounded-2xl border border-gray-100 dark:border-gray-700 p-5 hover:shadow-md hover:-translate-y-0.5 hover:border-primary-200 dark:hover:border-primary-800 transition-all group">
                              <div class="flex items-center justify-between mb-2">
                                   <p class="text-[10px] uppercase tracking-widest text-gray-400 dark:text-gray-500 font-bold">
                                        Website visits
                                   </p>
                                   <svg class="w-4 h-4 text-gray-300 dark:text-gray-600 group-hover:text-primary-500 transition-colors"
                                        fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                             d="M9 5l7 7-7 7" />
                                   </svg>
                              </div>
                              <p class="text-2xl font-bold text-gray-900 dark:text-white tracking-tight">
                                   {{
                                        formatNumber(
                                             analytics?.stats?.total_website_clicks || 0
                                        )
                                   }}
                              </p>
                              <p class="text-xs text-gray-400 dark:text-gray-500 mt-1">Total website clicks</p>
                         </a>
                    </div>

                    <!-- ============ VIEWS CHART (skeleton on refresh) ============ -->
                    <SkeletonLoader v-if="hasBusiness && isRefreshing" variant="chart" />

                    <div v-else-if="
                         hasBusiness &&
                         analytics?.has_data &&
                         analytics?.chart?.labels?.length > 0
                    " class="bg-white dark:bg-gray-800 rounded-2xl border border-gray-100 dark:border-gray-700 overflow-hidden">
                         <div
                              class="px-6 py-4 border-b border-gray-100 dark:border-gray-700 bg-gradient-to-br from-gray-50 to-white dark:from-gray-800 dark:to-gray-800/50 flex flex-col sm:flex-row justify-between items-start sm:items-center gap-3">
                              <div>
                                   <h3 class="text-sm font-bold text-gray-900 dark:text-white tracking-tight">
                                        Business performance
                                   </h3>
                                   <p class="text-xs text-gray-400 dark:text-gray-500">
                                        Views and visitor trends over time
                                   </p>
                              </div>
                              <div class="flex gap-4 text-xs">
                                   <span class="flex items-center gap-1.5">
                                        <span class="w-3 h-0.5 bg-blue-600 rounded"></span>
                                        <span class="text-gray-600 dark:text-gray-300 font-medium">Views</span>
                                   </span>
                                   <span class="flex items-center gap-1.5">
                                        <span class="w-3 h-0.5 bg-emerald-600 rounded"></span>
                                        <span class="text-gray-600 dark:text-gray-300 font-medium">Unique Visitors</span>
                                   </span>
                              </div>
                         </div>
                         <div class="p-6 h-64">
                              <LineChart :data="chartData" />
                         </div>
                    </div>

                    <div v-else-if="hasBusiness" class="bg-white dark:bg-gray-800 rounded-2xl border border-gray-100 dark:border-gray-700 p-12 text-center">
                         <div
                              class="w-16 h-16 bg-gradient-to-br from-gray-100 to-gray-50 dark:from-gray-700 dark:to-gray-800 rounded-2xl flex items-center justify-center mx-auto mb-4">
                              <svg class="w-8 h-8 text-gray-400 dark:text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                   <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" />
                              </svg>
                         </div>
                         <h3 class="text-base font-bold text-gray-900 dark:text-white tracking-tight mb-1">
                              No analytics data yet
                         </h3>
                         <p class="text-sm text-gray-500 dark:text-gray-400 max-w-md mx-auto">
                              Start getting views to see your performance here.
                         </p>
                    </div>

                    <!-- ============ CLICK BREAKDOWN + PLAN USAGE (skeleton on refresh) ============ -->
                    <div v-if="hasBusiness && isRefreshing" class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                         <SkeletonLoader variant="chart" />
                         <SkeletonLoader variant="card" :count="5" />
                    </div>

                    <div v-else-if="hasBusiness" class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                         <!-- Click breakdown -->
                         <div class="bg-white dark:bg-gray-800 rounded-2xl border border-gray-100 dark:border-gray-700 overflow-hidden">
                              <div
                                   class="px-6 py-4 border-b border-gray-100 dark:border-gray-700 bg-gradient-to-br from-gray-50 to-white dark:from-gray-800 dark:to-gray-800/50 flex items-center justify-between">
                                   <div>
                                        <h3 class="text-sm font-bold text-gray-900 dark:text-white tracking-tight">
                                             Click breakdown
                                        </h3>
                                        <p class="text-xs text-gray-400 dark:text-gray-500">
                                             How visitors engage with your business
                                        </p>
                                   </div>
                                   <span class="text-2xl font-bold text-gray-900 dark:text-white tracking-tight">{{ clickBreakdownTotal
                                   }}</span>
                              </div>

                              <div v-if="clickBreakdownTotal > 0" class="p-6 h-64">
                                   <DoughnutChart :data="clickBreakdownData" />
                              </div>
                              <div v-else class="h-64 flex flex-col items-center justify-center text-center p-6">
                                   <div
                                        class="w-16 h-16 bg-gradient-to-br from-gray-100 to-gray-50 dark:from-gray-700 dark:to-gray-800 rounded-2xl flex items-center justify-center mb-3">
                                        <svg class="w-8 h-8 text-gray-400 dark:text-gray-500" fill="none" stroke="currentColor"
                                             viewBox="0 0 24 24">
                                             <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                  d="M15 15l-2 5L9 9l11 4-5 2zm0 0l5 5M7.188 2.239l.777 2.897M5.136 7.965l-2.898-.777M13.95 4.05l-2.122 2.122m-5.657 5.656l-2.12 2.122" />
                                        </svg>
                                   </div>
                                   <p class="text-sm font-semibold text-gray-500 dark:text-gray-400">
                                        No clicks yet
                                   </p>
                                   <p class="text-xs text-gray-400 dark:text-gray-500 mt-1">
                                        Interactions will appear here
                                   </p>
                              </div>
                         </div>

                         <!-- Plan usage -->
                         <div v-if="usage && usage.businesses"
                              class="bg-white dark:bg-gray-800 rounded-2xl border border-gray-100 dark:border-gray-700 overflow-hidden">
                              <div
                                   class="px-6 py-4 border-b border-gray-100 dark:border-gray-700 bg-gradient-to-br from-gray-50 to-white dark:from-gray-800 dark:to-gray-800/50 flex items-center justify-between">
                                   <div>
                                        <h3 class="text-sm font-bold text-gray-900 dark:text-white tracking-tight">
                                             Plan usage
                                        </h3>
                                        <p class="text-xs text-gray-400 dark:text-gray-500">
                                             <span class="capitalize font-semibold text-gray-600 dark:text-gray-300">{{ usage.plan_tier
                                             }}</span>
                                             plan
                                             <span v-if="usage.plan_price > 0">
                                                  ·
                                                  {{
                                                       formatNumber(usage.plan_price)
                                                  }}
                                                  XAF/mo</span>
                                        </p>
                                   </div>
                                   <a v-if="usage.plan_tier !== 'premium'" href="/owner/subscription/renew"
                                        class="text-xs font-bold text-primary-600 dark:text-primary-400 hover:text-primary-800 dark:hover:text-primary-300 transition-colors">
                                        Upgrade →
                                   </a>
                              </div>

                              <div class="p-6 space-y-4">
                                   <div v-for="key in [
                                        'businesses',
                                        'branches',
                                        'services',
                                        'images',
                                        'coupons',
                                   ]" :key="key">
                                        <template v-if="usage && usage[key]">
                                             <div class="flex items-center justify-between text-xs mb-1.5">
                                                  <span class="capitalize font-bold text-gray-700 dark:text-gray-300 tracking-wide">
                                                       {{ key === "images" ? "Photos" : key }}
                                                  </span>
                                                  <span class="text-gray-500 dark:text-gray-400 font-medium">
                                                       <span class="text-gray-900 dark:text-white font-semibold">{{ usage[key].current
                                                       }}</span>
                                                       /
                                                       {{
                                                            usage[key].limit === -1
                                                                 ? "∞"
                                                                 : usage[key].limit
                                                       }}
                                                  </span>
                                             </div>
                                             <div class="w-full bg-gray-100 dark:bg-gray-700 rounded-full h-2 overflow-hidden">
                                                  <div class="h-full rounded-full transition-all"
                                                       :class="getUsageBarColor(usage[key])" :style="{
                                                            width:
                                                                 getUsagePercent(usage[key]) +
                                                                 '%',
                                                       }"></div>
                                             </div>
                                        </template>
                                   </div>
                              </div>
                         </div>
                    </div>

                    <!-- ============ REVIEWS + LEADS (skeleton on refresh) ============ -->
                    <div v-if="hasBusiness && isRefreshing" class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                         <SkeletonLoader variant="list" :count="3" />
                         <SkeletonLoader variant="list" :count="3" />
                    </div>

                    <div v-else-if="hasBusiness" class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                         <!-- Recent reviews -->
                         <div class="bg-white dark:bg-gray-800 rounded-2xl border border-gray-100 dark:border-gray-700 overflow-hidden">
                              <div
                                   class="px-6 py-4 border-b border-gray-100 dark:border-gray-700 bg-gradient-to-br from-amber-50 to-white dark:from-amber-900/20 dark:to-gray-800/50 flex items-center justify-between">
                                   <div class="flex items-center gap-3">
                                        <div class="w-9 h-9 rounded-xl bg-amber-50 dark:bg-amber-900/30 flex items-center justify-center">
                                             <svg class="w-4 h-4 text-amber-500 dark:text-amber-400" fill="currentColor"
                                                  viewBox="0 0 20 20">
                                                  <path
                                                       d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z" />
                                             </svg>
                                        </div>
                                        <div>
                                             <h3 class="text-sm font-bold text-gray-900 dark:text-white tracking-tight">
                                                  Recent reviews
                                             </h3>
                                             <p class="text-xs text-gray-400 dark:text-gray-500">
                                                  <span class="text-amber-500">★</span>
                                                  <span class="font-semibold text-gray-700 dark:text-gray-300">{{
                                                       reviewStats.average
                                                  }}</span>
                                                  average
                                             </p>
                                        </div>
                                   </div>
                                   <a href="/owner/reviews"
                                        class="text-xs font-bold text-primary-600 dark:text-primary-400 hover:text-primary-800 dark:hover:text-primary-300 transition-colors">
                                        View all →
                                   </a>
                              </div>

                              <div v-if="recentReviews.length > 0" class="divide-y divide-gray-100 dark:divide-gray-700">
                                   <div v-for="review in recentReviews" :key="review.id"
                                        class="px-6 py-4 hover:bg-gray-50/50 dark:hover:bg-gray-700/30 transition-colors">
                                        <div class="flex items-start gap-3">
                                             <div
                                                  class="w-9 h-9 rounded-2xl bg-gradient-to-br from-amber-400 to-amber-600 flex items-center justify-center text-white text-xs font-bold flex-shrink-0 shadow-md">
                                                  {{
                                                       review.user_name.charAt(0).toUpperCase()
                                                  }}
                                             </div>
                                             <div class="flex-1 min-w-0">
                                                  <div class="flex items-center justify-between gap-2">
                                                       <span class="text-sm font-semibold text-gray-900 dark:text-white truncate">{{
                                                            review.user_name
                                                       }}</span>
                                                       <span class="text-xs text-gray-400 dark:text-gray-500 flex-shrink-0">{{
                                                            review.created_at
                                                       }}</span>
                                                  </div>
                                                  <div class="flex items-center gap-0.5 mt-1">
                                                       <span v-for="i in 5" :key="i" class="text-xs" :class="i <= review.rating
                                                            ? 'text-amber-400'
                                                            : 'text-gray-200 dark:text-gray-700'
                                                            ">★</span>
                                                  </div>
                                                  <p class="text-xs text-gray-600 dark:text-gray-300 mt-1.5 line-clamp-2 leading-relaxed">
                                                       {{ review.content }}
                                                  </p>
                                             </div>
                                        </div>
                                   </div>
                              </div>
                              <div v-else class="px-6 py-10 text-center">
                                   <p class="text-sm font-semibold text-gray-500 dark:text-gray-400">
                                        No reviews yet
                                   </p>
                                   <p class="text-xs text-gray-400 dark:text-gray-500 mt-1">
                                        Reviews will appear here
                                   </p>
                              </div>
                         </div>

                         <!-- Recent leads -->
                         <div class="bg-white dark:bg-gray-800 rounded-2xl border border-gray-100 dark:border-gray-700 overflow-hidden">
                              <div
                                   class="px-6 py-4 border-b border-gray-100 dark:border-gray-700 bg-gradient-to-br from-blue-50 to-white dark:from-blue-900/20 dark:to-gray-800/50 flex items-center justify-between">
                                   <div class="flex items-center gap-3">
                                        <div class="w-9 h-9 rounded-xl bg-blue-50 dark:bg-blue-900/30 flex items-center justify-center">
                                             <svg class="w-4 h-4 text-blue-500 dark:text-blue-400" fill="none" stroke="currentColor"
                                                  viewBox="0 0 24 24">
                                                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                       d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />
                                             </svg>
                                        </div>
                                        <div class="flex items-center gap-2">
                                             <h3 class="text-sm font-bold text-gray-900 dark:text-white tracking-tight">
                                                  Recent leads
                                             </h3>
                                             <span v-if="hasLeadCapture && leadStats.new > 0"
                                                  class="bg-blue-600 text-white text-[10px] font-bold px-2 py-0.5 rounded-full">
                                                  {{ leadStats.new }} new
                                             </span>
                                        </div>
                                   </div>
                                   <a v-if="hasLeadCapture" href="/owner/leads"
                                        class="text-xs font-bold text-primary-600 dark:text-primary-400 hover:text-primary-800 dark:hover:text-primary-300 transition-colors">
                                        View all →
                                   </a>
                              </div>

                              <template v-if="hasLeadCapture">
                                   <div v-if="recentLeads.length > 0" class="divide-y divide-gray-100 dark:divide-gray-700">
                                        <div v-for="lead in recentLeads" :key="lead.id"
                                             class="px-6 py-4 hover:bg-gray-50/50 dark:hover:bg-gray-700/30 transition-colors">
                                             <div class="flex items-start gap-3">
                                                  <div
                                                       class="w-9 h-9 rounded-2xl bg-gradient-to-br from-blue-400 to-blue-600 flex items-center justify-center text-white text-xs font-bold flex-shrink-0 shadow-md">
                                                       {{ lead.name.charAt(0).toUpperCase() }}
                                                  </div>
                                                  <div class="flex-1 min-w-0">
                                                       <div class="flex items-center justify-between gap-2">
                                                            <span class="text-sm font-semibold text-gray-900 dark:text-white truncate">{{
                                                                 lead.name }}</span>
                                                            <span class="text-xs text-gray-400 dark:text-gray-500 flex-shrink-0">{{
                                                                 lead.created_at }}</span>
                                                       </div>
                                                       <p
                                                            class="text-xs text-gray-600 dark:text-gray-300 mt-1 line-clamp-2 leading-relaxed">
                                                            {{ lead.message }}
                                                       </p>
                                                  </div>
                                             </div>
                                        </div>
                                   </div>
                                   <div v-else class="px-6 py-10 text-center">
                                        <p class="text-sm font-semibold text-gray-500 dark:text-gray-400">
                                             No leads yet
                                        </p>
                                        <p class="text-xs text-gray-400 dark:text-gray-500 mt-1">
                                             Customer inquiries will appear here
                                        </p>
                                   </div>
                              </template>

                              <!-- Locked -->
                              <div v-else class="px-6 py-10 text-center">
                                   <div
                                        class="w-12 h-12 bg-amber-100 dark:bg-amber-900/30 rounded-2xl flex items-center justify-center mx-auto mb-3">
                                        <svg class="w-6 h-6 text-amber-600 dark:text-amber-400" fill="none" stroke="currentColor"
                                             viewBox="0 0 24 24">
                                             <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                  d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" />
                                        </svg>
                                   </div>
                                   <p class="text-sm font-bold text-gray-900 dark:text-white tracking-tight">
                                        Leads locked
                                   </p>
                                   <p class="text-xs text-gray-500 dark:text-gray-400 mt-1 mb-4 max-w-xs mx-auto">
                                        Upgrade to Growth to receive customer inquiries
                                   </p>
                                   <a href="/owner/subscription/renew"
                                        class="inline-flex items-center gap-1.5 px-4 py-2 bg-gradient-to-r from-primary-600 to-primary-700 text-white rounded-xl text-xs font-semibold hover:from-primary-700 hover:to-primary-800 transition-all shadow-lg shadow-primary-500/25">
                                        Upgrade Now
                                   </a>
                              </div>
                         </div>
                    </div>

                    <!-- ============ RECENT COUPONS (skeleton on refresh) ============ -->
                    <SkeletonLoader v-if="hasBusiness && hasCoupons && isRefreshing" variant="coupons" :count="3" />

                    <div v-else-if="hasBusiness && hasCoupons && recentCoupons.length > 0"
                         class="bg-white dark:bg-gray-800 rounded-2xl border border-gray-100 dark:border-gray-700 overflow-hidden">
                         <div
                              class="px-6 py-4 border-b border-gray-100 dark:border-gray-700 bg-gradient-to-br from-purple-50 to-white dark:from-purple-900/20 dark:to-gray-800/50 flex items-center justify-between">
                              <div class="flex items-center gap-3">
                                   <div class="w-9 h-9 rounded-xl bg-purple-50 dark:bg-purple-900/30 flex items-center justify-center">
                                        <svg class="w-4 h-4 text-purple-500 dark:text-purple-400" fill="none" stroke="currentColor"
                                             viewBox="0 0 24 24">
                                             <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                  d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z" />
                                        </svg>
                                   </div>
                                   <div>
                                        <h3 class="text-sm font-bold text-gray-900 dark:text-white tracking-tight">
                                             Active coupons
                                        </h3>
                                        <p class="text-xs text-gray-400 dark:text-gray-500">
                                             {{ couponStats.active }} active
                                        </p>
                                   </div>
                              </div>
                              <a href="/owner/coupons"
                                   class="text-xs font-bold text-primary-600 dark:text-primary-400 hover:text-primary-800 dark:hover:text-primary-300 transition-colors">
                                   Manage →
                              </a>
                         </div>
                         <div
                              class="grid grid-cols-1 sm:grid-cols-3 divide-y sm:divide-y-0 sm:divide-x divide-gray-100 dark:divide-gray-700">
                              <div v-for="coupon in recentCoupons" :key="coupon.id" class="p-5">
                                   <div class="flex items-center gap-2 mb-2">
                                        <span class="text-lg">🎟️</span>
                                        <span class="text-sm font-semibold text-gray-900 dark:text-white truncate">{{ coupon.title
                                        }}</span>
                                   </div>
                                   <div class="flex items-center gap-2 text-xs">
                                        <span
                                             class="font-mono font-bold text-purple-700 dark:text-purple-300 bg-purple-50 dark:bg-purple-900/30 px-2 py-0.5 rounded">{{
                                                  coupon.code }}</span>
                                        <span class="text-gray-500 dark:text-gray-400">Used {{ coupon.usage_count }}×</span>
                                   </div>
                              </div>
                         </div>
                    </div>

                    <!-- QUICK ACTIONS (only when business exists) -->
                    <div v-if="hasBusiness && business"
                         class="bg-white dark:bg-gray-800 rounded-2xl border border-gray-100 dark:border-gray-700 overflow-hidden">
                         <div
                              class="px-6 py-4 border-b border-gray-100 dark:border-gray-700 bg-gradient-to-br from-gray-50 to-white dark:from-gray-800 dark:to-gray-800/50 flex items-center gap-3">
                              <div class="w-9 h-9 rounded-xl bg-primary-50 dark:bg-primary-900/30 flex items-center justify-center">
                                   <svg class="w-4 h-4 text-primary-600 dark:text-primary-400" fill="none" stroke="currentColor"
                                        viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                             d="M13 10V3L4 14h7v7l9-11h-7z" />
                                   </svg>
                              </div>
                              <h3 class="text-sm font-bold text-gray-900 dark:text-white tracking-tight">
                                   Quick actions
                              </h3>
                         </div>
                         <div class="p-5 grid grid-cols-2 sm:grid-cols-4 gap-3">
                              <a :href="`/owner/businesses/${business.id}/edit`"
                                   class="flex items-center justify-center gap-2 px-4 py-3 bg-gray-50 dark:bg-gray-700 text-gray-700 dark:text-gray-300 rounded-xl hover:bg-gray-100 dark:hover:bg-gray-600 transition-colors text-xs font-semibold border border-gray-100 dark:border-gray-600 hover:border-primary-200 dark:hover:border-primary-700">
                                   <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                             d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                                   </svg>
                                   Edit Business
                              </a>
                              <a :href="`/owner/businesses/${business.id}/branches`"
                                   class="flex items-center justify-center gap-2 px-4 py-3 bg-gray-50 dark:bg-gray-700 text-gray-700 dark:text-gray-300 rounded-xl hover:bg-gray-100 dark:hover:bg-gray-600 transition-colors text-xs font-semibold border border-gray-100 dark:border-gray-600 hover:border-primary-200 dark:hover:border-primary-700">
                                   <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                             d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z" />
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                             d="M15 11a3 3 0 11-6 0 3 3 0 016 0z" />
                                   </svg>
                                   Branches
                              </a>
                              <a href="/owner/subscription"
                                   class="flex items-center justify-center gap-2 px-4 py-3 bg-gray-50 dark:bg-gray-700 text-gray-700 dark:text-gray-300 rounded-xl hover:bg-gray-100 dark:hover:bg-gray-600 transition-colors text-xs font-semibold border border-gray-100 dark:border-gray-600 hover:border-primary-200 dark:hover:border-primary-700">
                                   <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                             d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                   </svg>
                                   Subscription
                              </a>
                              <a :href="`/owner/analytics?business=${business.id}`"
                                   class="flex items-center justify-center gap-2 px-4 py-3 bg-gray-50 dark:bg-gray-700 text-gray-700 dark:text-gray-300 rounded-xl hover:bg-gray-100 dark:hover:bg-gray-600 transition-colors text-xs font-semibold border border-gray-100 dark:border-gray-600 hover:border-primary-200 dark:hover:border-primary-700">
                                   <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                             d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" />
                                   </svg>
                                   Analytics
                              </a>
                              <button v-if="business.status === 'draft'" @click="submitBusiness"
                                   class="col-span-2 sm:col-span-4 inline-flex items-center justify-center gap-2 px-4 py-3 bg-gradient-to-r from-primary-600 to-primary-700 text-white rounded-xl hover:from-primary-700 hover:to-primary-800 transition-all duration-200 shadow-lg shadow-primary-500/25 hover:-translate-y-0.5 text-xs font-semibold">
                                   <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                             d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                                   </svg>
                                   Submit for Review
                              </button>
                         </div>
                    </div>

                    <!-- NO BUSINESS -->
                    <div v-if="!hasBusiness" class="bg-white dark:bg-gray-800 rounded-2xl border border-gray-100 dark:border-gray-700 p-12 text-center">
                         <div
                              class="w-20 h-20 bg-gradient-to-br from-primary-100 to-primary-200 dark:from-primary-900/40 dark:to-primary-900/20 rounded-2xl flex items-center justify-center mx-auto mb-5 shadow-sm">
                              <svg class="w-10 h-10 text-primary-600 dark:text-primary-400" fill="none" stroke="currentColor"
                                   viewBox="0 0 24 24">
                                   <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" />
                              </svg>
                         </div>
                         <h3 class="text-xl font-bold text-gray-900 dark:text-white tracking-tight mb-2">
                              No business yet
                         </h3>
                         <p class="text-gray-500 dark:text-gray-400 max-w-md mx-auto mb-6 text-sm">
                              You haven't created a business profile yet. Start by creating
                              your first business.
                         </p>
                         <a href="/owner/businesses/create"
                              class="inline-flex items-center gap-2 px-6 py-3 bg-gradient-to-r from-primary-600 to-primary-700 text-white rounded-xl hover:from-primary-700 hover:to-primary-800 transition-all duration-200 shadow-lg shadow-primary-500/25 hover:shadow-primary-500/40 hover:-translate-y-0.5 font-semibold text-sm">
                              <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                   <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M12 4v16m8-8H4" />
                              </svg>
                              Create Your Business
                         </a>
                    </div>
               </div>
          </PullToRefresh>
     </AuthenticatedLayout>
</template>

<script setup>
     import { computed, ref } from "vue";
     import { router } from "@inertiajs/vue3";
     import { useToast } from "@/composables/useToast";
     import { useConfirm } from "@/composables/useConfirm";
     import AuthenticatedLayout from "@/Layouts/AuthenticatedLayout.vue";
     import PageHeader from "@/Components/PageHeader.vue";
     import LineChart from "@/Components/Charts/LineChart.vue";
     import DoughnutChart from "@/Components/Charts/DoughnutChart.vue";
     import BusinessSelector from "@/Components/Owner/BusinessSelector.vue";
     import PullToRefresh from "@/Components/Common/PullToRefresh.vue";
     import SkeletonLoader from "@/Components/Common/SkeletonLoader.vue";

     const props = defineProps({
          user: Object,
          business: Object,
          hasBusiness: Boolean,
          businesses: { type: Array, default: () => [] },
          selectedBusinessId: { type: [Number, String], default: null },
          branchesCount: Number,
          activeSubscription: Object,
          completeness: {
               type: Object,
               default: () => ({ score: 0, tier: { label: '', message: '', color: 'gray' }, missing: [] }),
          },
          analytics: Object,
          recentActivity: Array,
          recentReviews: { type: Array, default: () => [] },
          reviewStats: {
               type: Object,
               default: () => ({ total: 0, average: 0, pending: 0, approved: 0 }),
          },
          recentLeads: { type: Array, default: () => [] },
          leadStats: { type: Object, default: () => ({ total: 0, new: 0 }) },
          recentCoupons: { type: Array, default: () => [] },
          couponStats: {
               type: Object,
               default: () => ({ active: 0, total_redemptions: 0, total_discount: 0 }),
          },
          usage: Object,
          hasLeadCapture: Boolean,
          hasCoupons: Boolean,
          planOverflow: { type: Object, default: null },
     });

     const { success } = useToast();
     const { confirm: confirmDialog } = useConfirm();

     const formatDate = (date) => {
          if (!date) return "N/A";
          return new Date(date).toLocaleDateString("en-US", {
               year: "numeric",
               month: "short",
               day: "numeric",
          });
     };

     const formatNumber = (num) => {
          if (num >= 1000000) return (num / 1000000).toFixed(1) + "M";
          if (num >= 1000) return (num / 1000).toFixed(1) + "K";
          return num.toString();
     };

     // ============== CONSOLIDATED BANNER (priority-ordered) ==============
     const topBanner = computed(() => {
          // 1. Pending account
          if (props.user?.status === "pending") {
               return {
                    title: "Account pending approval",
                    body:
                         "Your account is pending admin approval. You'll receive an email once approved.",
                    actionLabel: "View profile",
                    actionHref: "/profile",
                    classes: "bg-gradient-to-br from-amber-50 to-orange-50 dark:from-amber-900/20 dark:to-orange-900/10 border-amber-200 dark:border-amber-800",
                    iconBg: "bg-amber-100 dark:bg-amber-900/40",
                    iconColor: "text-amber-600 dark:text-amber-400",
                    titleColor: "text-amber-800 dark:text-amber-300",
                    bodyColor: "text-amber-700 dark:text-amber-400",
                    actionClasses:
                         "bg-gradient-to-r from-amber-500 to-orange-500 text-white hover:from-amber-600 hover:to-orange-600 shadow-amber-500/25",
                    icon:
                         '<svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>',
               };
          }

          // 1b. Grace period
          if (
               props.activeSubscription?.status === "grace_period" &&
               props.activeSubscription.grace_period_ends_at
          ) {
               const graceEnd = new Date(props.activeSubscription.grace_period_ends_at);
               const now = new Date();
               const daysLeft = Math.max(
                    0,
                    Math.ceil((graceEnd - now) / (1000 * 60 * 60 * 24))
               );

               const daysText =
                    daysLeft === 0
                         ? "Suspension imminent"
                         : `${daysLeft} ${daysLeft === 1 ? "day" : "days"} until suspension`;

               return {
                    title: "Grace period — account suspension imminent",
                    body: `Your subscription expired. Your account will be suspended on ${graceEnd.toLocaleDateString(
                         "en-US",
                         { month: "short", day: "numeric", year: "numeric" }
                    )}. ${daysText}.`,
                    actionLabel: "Renew now",
                    actionHref: "/owner/subscription/renew",
                    classes: "bg-gradient-to-br from-orange-50 to-amber-50 dark:from-orange-900/20 dark:to-amber-900/10 border-orange-200 dark:border-orange-800",
                    iconBg: "bg-orange-100 dark:bg-orange-900/40",
                    iconColor: "text-orange-600 dark:text-orange-400",
                    titleColor: "text-orange-800 dark:text-orange-300",
                    bodyColor: "text-orange-700 dark:text-orange-400",
                    actionClasses:
                         "bg-gradient-to-r from-orange-500 to-amber-500 text-white hover:from-orange-600 hover:to-amber-600 shadow-orange-500/25",
                    icon:
                         '<svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>',
               };
          }

          // 2. Expired subscription
          if (props.activeSubscription && props.activeSubscription.days_remaining === 0) {
               return {
                    title: "Subscription expired",
                    body: `Your ${props.activeSubscription.plan?.name || "subscription"
                         } has expired. Renew to keep your business active.`,
                    actionLabel: "Renew now",
                    actionHref: "/owner/subscription/renew",
                    classes: "bg-gradient-to-br from-red-50 to-red-100 dark:from-red-900/20 dark:to-red-900/10 border-red-200 dark:border-red-800",
                    iconBg: "bg-red-100 dark:bg-red-900/40",
                    iconColor: "text-red-600 dark:text-red-400",
                    titleColor: "text-red-800 dark:text-red-300",
                    bodyColor: "text-red-700 dark:text-red-400",
                    actionClasses:
                         "bg-gradient-to-r from-red-500 to-red-600 text-white hover:from-red-600 hover:to-red-700 shadow-red-500/25",
                    icon:
                         '<svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>',
               };
          }

          // 3. Expiring soon (<= 7 days)
          if (
               props.activeSubscription &&
               props.activeSubscription.days_remaining !== null &&
               props.activeSubscription.days_remaining <= 7 &&
               props.activeSubscription.days_remaining > 0
          ) {
               return {
                    title: "Subscription expiring soon",
                    body: `Your ${props.activeSubscription.plan?.name || "subscription"
                         } expires in ${props.activeSubscription.days_remaining} day${props.activeSubscription.days_remaining === 1 ? "" : "s"
                         }.`,
                    actionLabel: "Renew now",
                    actionHref: "/owner/subscription/renew",
                    classes: "bg-gradient-to-br from-amber-50 to-orange-50 dark:from-amber-900/20 dark:to-orange-900/10 border-amber-200 dark:border-amber-800",
                    iconBg: "bg-amber-100 dark:bg-amber-900/40",
                    iconColor: "text-amber-600 dark:text-amber-400",
                    titleColor: "text-amber-800 dark:text-amber-300",
                    bodyColor: "text-amber-700 dark:text-amber-400",
                    actionClasses:
                         "bg-gradient-to-r from-amber-500 to-orange-500 text-white hover:from-amber-600 hover:to-orange-600 shadow-amber-500/25",
                    icon:
                         '<svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>',
               };
          }

          // 4. No subscription
          if (props.hasBusiness && !props.activeSubscription) {
               return {
                    title: "No active subscription",
                    body:
                         "Your business doesn't have an active subscription. Subscribe to unlock all features.",
                    actionLabel: "Get subscription",
                    actionHref: "/owner/subscription/renew",
                    classes: "bg-gradient-to-br from-blue-50 to-indigo-50 dark:from-blue-900/20 dark:to-indigo-900/10 border-blue-200 dark:border-blue-800",
                    iconBg: "bg-blue-100 dark:bg-blue-900/40",
                    iconColor: "text-blue-600 dark:text-blue-400",
                    titleColor: "text-blue-800 dark:text-blue-300",
                    bodyColor: "text-blue-700 dark:text-blue-400",
                    actionClasses:
                         "bg-gradient-to-r from-blue-600 to-indigo-600 text-white hover:from-blue-700 hover:to-indigo-700 shadow-blue-500/25",
                    icon:
                         '<svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>',
               };
          }

          return null;
     });

     // ============== PLAN OVERFLOW HELPERS ==============
     const overLimitCount = computed(() => {
          if (!props.planOverflow?.items) return 0;
          return Object.values(props.planOverflow.items).filter((i) => i.over > 0).length;
     });

     // ============== NEEDS ATTENTION ITEMS ==============
     const attentionItems = computed(() => {
          const items = [];

          // Draft business
          if (props.business?.status === "draft") {
               items.push({
                    key: "draft",
                    icon: "📝",
                    label: "Business is draft",
                    count: "Submit",
                    href: `/owner/businesses/${props.business.id}/edit`,
                    classes: "bg-gray-50 dark:bg-gray-700 text-gray-700 dark:text-gray-300 border-gray-200 dark:border-gray-600 hover:border-gray-300 dark:hover:border-gray-500",
                    countClasses: "bg-gray-200 dark:bg-gray-600 text-gray-700 dark:text-gray-200",
               });
          }

          // Pending reviews
          if (props.reviewStats?.pending > 0) {
               items.push({
                    key: "reviews",
                    icon: "⭐",
                    label: "Reviews pending",
                    count: props.reviewStats.pending,
                    href: "/owner/reviews?status=pending",
                    classes:
                         "bg-amber-50 dark:bg-amber-900/20 text-amber-800 dark:text-amber-300 border-amber-200 dark:border-amber-800 hover:border-amber-300 dark:hover:border-amber-700",
                    countClasses: "bg-amber-500 text-white",
               });
          }

          // New leads
          if (props.hasLeadCapture && props.leadStats?.new > 0) {
               items.push({
                    key: "leads",
                    icon: "📥",
                    label: "New leads",
                    count: props.leadStats.new,
                    href: "/owner/leads",
                    classes: "bg-blue-50 dark:bg-blue-900/20 text-blue-800 dark:text-blue-300 border-blue-200 dark:border-blue-800 hover:border-blue-300 dark:hover:border-blue-700",
                    countClasses: "bg-blue-500 text-white",
               });
          }

          // Subscription expiring
          if (
               props.activeSubscription &&
               props.activeSubscription.days_remaining !== null &&
               props.activeSubscription.days_remaining > 7 &&
               props.activeSubscription.days_remaining <= 30
          ) {
               items.push({
                    key: "subscription",
                    icon: "⏳",
                    label: "Subscription expiring",
                    count: `${props.activeSubscription.days_remaining}d`,
                    href: "/owner/subscription",
                    classes:
                         "bg-orange-50 dark:bg-orange-900/20 text-orange-800 dark:text-orange-300 border-orange-200 dark:border-orange-800 hover:border-orange-300 dark:hover:border-orange-700",
                    countClasses: "bg-orange-500 text-white",
               });
          }

          // Grace period
          if (
               props.activeSubscription?.status === "grace_period" &&
               props.activeSubscription.grace_period_ends_at
          ) {
               const graceEnd = new Date(props.activeSubscription.grace_period_ends_at);
               const daysLeft = Math.max(
                    0,
                    Math.ceil((graceEnd - new Date()) / (1000 * 60 * 60 * 24))
               );
               items.push({
                    key: "grace_period",
                    icon: "🚨",
                    label: "Grace period",
                    count: daysLeft === 0 ? "now" : `${daysLeft}d`,
                    href: "/owner/subscription",
                    classes: "bg-red-50 dark:bg-red-900/20 text-red-800 dark:text-red-300 border-red-200 dark:border-red-800 hover:border-red-300 dark:hover:border-red-700",
                    countClasses: "bg-red-500 text-white",
               });
          }

          return items;
     });

     // ============== CHART DATA ==============
     const chartData = computed(() => {
          if (!props.analytics?.chart) {
               return { labels: [], datasets: [] };
          }
          return {
               labels: props.analytics.chart.labels,
               datasets: [
                    {
                         label: "Views",
                         data: props.analytics.chart.views,
                         borderColor: "#0284c7",
                         backgroundColor: "rgba(2, 132, 199, 0.1)",
                         fill: true,
                         tension: 0.4,
                    },
                    {
                         label: "Unique Visitors",
                         data: props.analytics.chart.unique_visitors,
                         borderColor: "#16a34a",
                         backgroundColor: "rgba(22, 163, 74, 0.1)",
                         fill: true,
                         tension: 0.4,
                    },
               ],
          };
     });

     const clickBreakdownData = computed(() => {
          if (!props.analytics?.click_breakdown) {
               return { labels: [], datasets: [] };
          }
          const bd = props.analytics.click_breakdown;
          const labels = [];
          const data = [];
          const colors = [];

          if (bd.phone > 0) {
               labels.push("Phone");
               data.push(bd.phone);
               colors.push("#f59e0b");
          }
          if (bd.whatsapp > 0) {
               labels.push("WhatsApp");
               data.push(bd.whatsapp);
               colors.push("#22c55e");
          }
          if (bd.website > 0) {
               labels.push("Website");
               data.push(bd.website);
               colors.push("#8b5cf6");
          }
          if (bd.directions > 0) {
               labels.push("Directions");
               data.push(bd.directions);
               colors.push("#0ea5e9");
          }
          if (bd.social > 0) {
               labels.push("Social");
               data.push(bd.social);
               colors.push("#ec4899");
          }

          if (labels.length === 0) {
               return { labels: [], datasets: [] };
          }

          return {
               labels,
               datasets: [
                    {
                         data,
                         backgroundColor: colors,
                         borderWidth: 2,
                         borderColor: "#ffffff",
                    },
               ],
          };
     });

     const clickBreakdownTotal = computed(() => {
          if (!props.analytics?.click_breakdown) return 0;
          return Object.values(props.analytics.click_breakdown).reduce((a, b) => a + b, 0);
     });

     // ============== ACTIONS ==============
     const submitBusiness = async () => {
          if (!props.business) return;

          const confirmed = await confirmDialog({
               title: 'Submit for review?',
               message: `"${props.business.name}" will be sent to the admin team for approval. You'll be notified once it's reviewed.`,
               confirmText: 'Submit for Review',
               cancelText: 'Cancel',
               variant: 'primary',
          });

          if (!confirmed) return;

          router.post(`/owner/businesses/${props.business.id}/submit`, {}, {
               preserveScroll: true,
               // ✅ No success toast — the controller flashes a message
               //    AND redirects to /owner/businesses, where the
               //    AuthenticatedLayout shows the toast once. Adding a
               //    page-level toast here would fire AFTER the redirect
               //    (on the wrong page) AND double with the flash.
               //
               // ⚠️ Note: the empty `{}` data object is important — the
               //    previous code passed the config object as data.
          });
     };

     // Pull-to-refresh ref
     const ptrRef = ref(null);

     // Skeleton state — true while an Inertia reload is in-flight
     const isRefreshing = ref(false);

     // Wire both the header Refresh button AND the pull gesture to this same function
     const refreshData = () => {
          isRefreshing.value = true;
          router.reload({
               preserveScroll: true,
               onFinish: () => {
                    isRefreshing.value = false;
                    // Tell PullToRefresh the spinner can stop
                    ptrRef.value?.done();
               },
               // ✅ Toast only on successful reload — previous placement
               //    in onFinish fired even if the reload failed.
               onSuccess: () => {
                    success("Refreshed! 🔄", "Dashboard data has been updated.", {
                         duration: 3000,
                    });
               },
          });
     };

     // ============== USAGE HELPERS ==============
     const getUsagePercent = (item) => {
          if (!item || item.limit === -1 || !item.limit) return 0;
          return Math.min(100, Math.round((item.current / item.limit) * 100));
     };

     const getUsageBarColor = (item) => {
          if (!item) return "bg-gray-300";
          const percent = getUsagePercent(item);
          if (percent >= 100) return "bg-gradient-to-r from-red-500 to-red-600";
          if (percent >= 80) return "bg-gradient-to-r from-amber-400 to-amber-500";
          return "bg-gradient-to-r from-primary-400 to-primary-500";
     };
</script>