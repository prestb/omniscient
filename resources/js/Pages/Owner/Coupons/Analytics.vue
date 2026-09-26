<!-- resources/js/Pages/Owner/Coupons/Analytics.vue -->
<template>
     <AuthenticatedLayout>
          <!-- COMPACT HEADER -->
          <PageHeader
               color="purple"
               :breadcrumb="[
                    { label: 'Dashboard', href: '/owner/dashboard' },
                    { label: 'Coupons', href: '/owner/coupons' },
                    { label: coupon.title },
               ]"
          >
               <template #icon>
                    <path
                         stroke-linecap="round"
                         stroke-linejoin="round"
                         stroke-width="2"
                         d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"
                    />
               </template>
               <template #title>{{ coupon.title }}</template>
               <template #subtitle>Coupon performance</template>
               <template #actions>
                    <span
                         v-if="coupon.code"
                         class="inline-flex items-center gap-1.5 px-2.5 py-2 bg-gray-50 dark:bg-gray-700 border border-gray-200 dark:border-gray-600 text-xs font-mono font-bold text-gray-700 dark:text-gray-300 rounded-xl"
                    >
                         <svg
                              class="w-3 h-3"
                              fill="none"
                              stroke="currentColor"
                              viewBox="0 0 24 24"
                         >
                              <path
                                   stroke-linecap="round"
                                   stroke-linejoin="round"
                                   stroke-width="2"
                                   d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z"
                              />
                         </svg>
                         {{ coupon.code }}
                    </span>
                    <a
                         :href="`/owner/coupons/${coupon.id}/edit`"
                         class="inline-flex items-center gap-2 px-4 py-2 bg-gradient-to-r from-primary-600 to-primary-700 text-white rounded-xl font-semibold hover:from-primary-700 hover:to-primary-800 transition-all shadow-lg shadow-primary-500/25 text-sm"
                    >
                         <svg
                              class="w-4 h-4"
                              fill="none"
                              stroke="currentColor"
                              viewBox="0 0 24 24"
                         >
                              <path
                                   stroke-linecap="round"
                                   stroke-linejoin="round"
                                   stroke-width="2"
                                   d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"
                              />
                         </svg>
                         Edit Coupon
                    </a>
               </template>
          </PageHeader>

          <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8 space-y-6">
               <!-- ==================== KPI CARDS ==================== -->
               <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
                    <!-- Total redemptions -->
                    <div
                         class="bg-white dark:bg-gray-800 rounded-2xl border border-gray-100 dark:border-gray-700 p-5 hover:shadow-md transition-shadow"
                    >
                         <div class="flex items-center justify-between mb-3">
                              <div
                                   class="w-11 h-11 rounded-2xl bg-primary-50 dark:bg-primary-900/30 flex items-center justify-center"
                              >
                                   <svg
                                        class="w-5 h-5 text-primary-600 dark:text-primary-400"
                                        fill="none"
                                        stroke="currentColor"
                                        viewBox="0 0 24 24"
                                   >
                                        <path
                                             stroke-linecap="round"
                                             stroke-linejoin="round"
                                             stroke-width="2"
                                             d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"
                                        />
                                   </svg>
                              </div>
                              <span
                                   class="text-[10px] font-bold uppercase tracking-wide text-emerald-700 dark:text-emerald-400 bg-emerald-50 dark:bg-emerald-900/30 px-2 py-1 rounded-full"
                              >
                                   Total
                              </span>
                         </div>
                         <p
                              class="text-3xl font-bold text-gray-900 dark:text-white tracking-tight"
                         >
                              {{ stats.total_redemptions }}
                         </p>
                         <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">
                              Redemptions
                         </p>
                    </div>

                    <!-- Unique users -->
                    <div
                         class="bg-white dark:bg-gray-800 rounded-2xl border border-gray-100 dark:border-gray-700 p-5 hover:shadow-md transition-shadow"
                    >
                         <div class="flex items-center justify-between mb-3">
                              <div
                                   class="w-11 h-11 rounded-2xl bg-purple-50 dark:bg-purple-900/30 flex items-center justify-center"
                              >
                                   <svg
                                        class="w-5 h-5 text-purple-600 dark:text-purple-400"
                                        fill="none"
                                        stroke="currentColor"
                                        viewBox="0 0 24 24"
                                   >
                                        <path
                                             stroke-linecap="round"
                                             stroke-linejoin="round"
                                             stroke-width="2"
                                             d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"
                                        />
                                   </svg>
                              </div>
                              <span
                                   class="text-[10px] font-bold uppercase tracking-wide text-purple-700 dark:text-purple-400 bg-purple-50 dark:bg-purple-900/30 px-2 py-1 rounded-full"
                              >
                                   Unique
                              </span>
                         </div>
                         <p
                              class="text-3xl font-bold text-gray-900 dark:text-white tracking-tight"
                         >
                              {{ stats.unique_users }}
                         </p>
                         <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">
                              Unique customers
                         </p>
                    </div>

                    <!-- Total discount given -->
                    <div
                         class="bg-white dark:bg-gray-800 rounded-2xl border border-gray-100 dark:border-gray-700 p-5 hover:shadow-md transition-shadow"
                    >
                         <div class="flex items-center justify-between mb-3">
                              <div
                                   class="w-11 h-11 rounded-2xl bg-emerald-50 dark:bg-emerald-900/30 flex items-center justify-center"
                              >
                                   <svg
                                        class="w-5 h-5 text-emerald-600 dark:text-emerald-400"
                                        fill="none"
                                        stroke="currentColor"
                                        viewBox="0 0 24 24"
                                   >
                                        <path
                                             stroke-linecap="round"
                                             stroke-linejoin="round"
                                             stroke-width="2"
                                             d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"
                                        />
                                   </svg>
                              </div>
                              <span
                                   class="text-[10px] font-bold uppercase tracking-wide text-emerald-700 dark:text-emerald-400 bg-emerald-50 dark:bg-emerald-900/30 px-2 py-1 rounded-full"
                              >
                                   Saved
                              </span>
                         </div>
                         <p
                              class="text-2xl font-bold text-gray-900 dark:text-white tracking-tight truncate"
                         >
                              {{ formatPrice(stats.total_discount_given) }}
                         </p>
                         <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">
                              XAF discount given
                         </p>
                    </div>

                    <!-- Usage rate -->
                    <div
                         class="bg-white dark:bg-gray-800 rounded-2xl border border-gray-100 dark:border-gray-700 p-5 hover:shadow-md transition-shadow"
                    >
                         <div class="flex items-center justify-between mb-3">
                              <div
                                   class="w-11 h-11 rounded-2xl bg-amber-50 dark:bg-amber-900/30 flex items-center justify-center"
                              >
                                   <svg
                                        class="w-5 h-5 text-amber-600 dark:text-amber-400"
                                        fill="none"
                                        stroke="currentColor"
                                        viewBox="0 0 24 24"
                                   >
                                        <path
                                             stroke-linecap="round"
                                             stroke-linejoin="round"
                                             stroke-width="2"
                                             d="M13 10V3L4 14h7v7l9-11h-7z"
                                        />
                                   </svg>
                              </div>
                              <span
                                   class="text-[10px] font-bold uppercase tracking-wide text-amber-700 dark:text-amber-400 bg-amber-50 dark:bg-amber-900/30 px-2 py-1 rounded-full"
                              >
                                   {{ usagePercent }}%
                              </span>
                         </div>
                         <p
                              class="text-2xl font-bold text-gray-900 dark:text-white tracking-tight"
                         >
                              {{ coupon.usage_count }}
                              <span class="text-gray-400 font-medium"
                                   >/ {{ coupon.usage_limit || "∞" }}</span
                              >
                         </p>
                         <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">
                              Usage
                         </p>
                    </div>
               </div>

               <!-- ==================== TIME-BASED STATS ==================== -->
               <div
                    class="bg-white dark:bg-gray-800 rounded-2xl border border-gray-100 dark:border-gray-700 overflow-hidden"
               >
                    <div
                         class="px-6 py-4 border-b border-gray-100 dark:border-gray-700 bg-gradient-to-br from-gray-50 to-white dark:from-gray-800 dark:to-gray-800/50 flex items-center gap-3"
                    >
                         <div
                              class="w-9 h-9 rounded-xl bg-blue-50 dark:bg-blue-900/30 flex items-center justify-center"
                         >
                              <svg
                                   class="w-4 h-4 text-blue-600 dark:text-blue-400"
                                   fill="none"
                                   stroke="currentColor"
                                   viewBox="0 0 24 24"
                              >
                                   <path
                                        stroke-linecap="round"
                                        stroke-linejoin="round"
                                        stroke-width="2"
                                        d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"
                                   />
                              </svg>
                         </div>
                         <div>
                              <h2
                                   class="text-sm font-bold text-gray-900 dark:text-white tracking-tight"
                              >
                                   Time-based performance
                              </h2>
                              <p class="text-xs text-gray-400">
                                   How your coupon is performing over time
                              </p>
                         </div>
                    </div>

                    <div
                         class="grid grid-cols-1 sm:grid-cols-3 divide-y sm:divide-y-0 sm:divide-x divide-gray-100 dark:divide-gray-700"
                    >
                         <div class="p-6 text-center">
                              <p
                                   class="text-[10px] uppercase tracking-widest text-gray-400 font-bold mb-2"
                              >
                                   Today
                              </p>
                              <p
                                   class="text-3xl font-bold text-gray-900 dark:text-white tracking-tight"
                              >
                                   {{ stats.redemptions_today }}
                              </p>
                              <p class="text-xs text-gray-400 mt-1">redemptions</p>
                         </div>
                         <div class="p-6 text-center">
                              <p
                                   class="text-[10px] uppercase tracking-widest text-gray-400 font-bold mb-2"
                              >
                                   This week
                              </p>
                              <p
                                   class="text-3xl font-bold text-gray-900 dark:text-white tracking-tight"
                              >
                                   {{ stats.redemptions_this_week }}
                              </p>
                              <p class="text-xs text-gray-400 mt-1">redemptions</p>
                         </div>
                         <div class="p-6 text-center">
                              <p
                                   class="text-[10px] uppercase tracking-widest text-gray-400 font-bold mb-2"
                              >
                                   This month
                              </p>
                              <p
                                   class="text-3xl font-bold text-gray-900 dark:text-white tracking-tight"
                              >
                                   {{ stats.redemptions_this_month }}
                              </p>
                              <p class="text-xs text-gray-400 mt-1">redemptions</p>
                         </div>
                    </div>
               </div>

               <!-- ==================== REDEMPTION HISTORY ==================== -->
               <div
                    class="bg-white dark:bg-gray-800 rounded-2xl border border-gray-100 dark:border-gray-700 overflow-hidden"
               >
                    <div
                         class="px-6 py-4 border-b border-gray-100 dark:border-gray-700 bg-gradient-to-br from-gray-50 to-white dark:from-gray-800 dark:to-gray-800/50 flex items-center justify-between"
                    >
                         <div class="flex items-center gap-3">
                              <div
                                   class="w-9 h-9 rounded-xl bg-primary-50 dark:bg-primary-900/30 flex items-center justify-center"
                              >
                                   <svg
                                        class="w-4 h-4 text-primary-600 dark:text-primary-400"
                                        fill="none"
                                        stroke="currentColor"
                                        viewBox="0 0 24 24"
                                   >
                                        <path
                                             stroke-linecap="round"
                                             stroke-linejoin="round"
                                             stroke-width="2"
                                             d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"
                                        />
                                   </svg>
                              </div>
                              <div>
                                   <h2
                                        class="text-sm font-bold text-gray-900 dark:text-white tracking-tight"
                                   >
                                        Redemption history
                                   </h2>
                                   <p class="text-xs text-gray-400">
                                        Who used your coupon
                                   </p>
                              </div>
                         </div>
                         <span
                              class="text-[10px] font-bold uppercase tracking-wide text-gray-500 dark:text-gray-400 bg-gray-100 dark:bg-gray-700 px-2.5 py-1 rounded-full"
                         >
                              {{ redemptions.total }} total
                         </span>
                    </div>

                    <!-- Empty state -->
                    <div v-if="redemptions.data.length === 0" class="p-12 text-center">
                         <div
                              class="w-16 h-16 bg-gradient-to-br from-gray-100 to-gray-50 dark:from-gray-700 dark:to-gray-800 rounded-2xl flex items-center justify-center mx-auto mb-4"
                         >
                              <svg
                                   class="w-8 h-8 text-gray-400"
                                   fill="none"
                                   stroke="currentColor"
                                   viewBox="0 0 24 24"
                              >
                                   <path
                                        stroke-linecap="round"
                                        stroke-linejoin="round"
                                        stroke-width="2"
                                        d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"
                                   />
                              </svg>
                         </div>
                         <p class="text-sm font-bold text-gray-900 dark:text-white mb-1">
                              No redemptions yet
                         </p>
                         <p class="text-xs text-gray-500 dark:text-gray-400">
                              Your coupon hasn't been used yet. Share it with your
                              customers!
                         </p>
                    </div>

                    <!-- Desktop table -->
                    <div v-else class="hidden md:block overflow-x-auto">
                         <table class="w-full">
                              <thead
                                   class="bg-gray-50 dark:bg-gray-900/50 border-b border-gray-100 dark:border-gray-700"
                              >
                                   <tr>
                                        <th
                                             class="px-6 py-3 text-left text-[10px] font-bold text-gray-500 dark:text-gray-400 uppercase tracking-widest"
                                        >
                                             Customer
                                        </th>
                                        <th
                                             class="px-6 py-3 text-left text-[10px] font-bold text-gray-500 dark:text-gray-400 uppercase tracking-widest"
                                        >
                                             Discount
                                        </th>
                                        <th
                                             class="px-6 py-3 text-left text-[10px] font-bold text-gray-500 dark:text-gray-400 uppercase tracking-widest"
                                        >
                                             Original
                                        </th>
                                        <th
                                             class="px-6 py-3 text-left text-[10px] font-bold text-gray-500 dark:text-gray-400 uppercase tracking-widest"
                                        >
                                             Final
                                        </th>
                                        <th
                                             class="px-6 py-3 text-left text-[10px] font-bold text-gray-500 dark:text-gray-400 uppercase tracking-widest"
                                        >
                                             Date
                                        </th>
                                   </tr>
                              </thead>
                              <tbody
                                   class="divide-y divide-gray-100 dark:divide-gray-700"
                              >
                                   <tr
                                        v-for="redemption in redemptions.data"
                                        :key="redemption.id"
                                        class="hover:bg-gray-50/50 dark:hover:bg-gray-700/30 transition-colors"
                                   >
                                        <td class="px-6 py-4">
                                             <div class="flex items-center gap-3">
                                                  <div
                                                       class="w-10 h-10 rounded-2xl bg-gradient-to-br from-primary-500 to-purple-600 flex items-center justify-center text-white text-xs font-bold shadow-md flex-shrink-0"
                                                  >
                                                       {{
                                                            getInitials(
                                                                 redemption.user?.name ||
                                                                      "Guest"
                                                            )
                                                       }}
                                                  </div>
                                                  <div class="min-w-0">
                                                       <p
                                                            class="text-sm font-semibold text-gray-900 dark:text-white truncate"
                                                       >
                                                            {{
                                                                 redemption.user?.name ||
                                                                 "Guest User"
                                                            }}
                                                       </p>
                                                       <p
                                                            class="text-xs text-gray-500 dark:text-gray-400 truncate"
                                                       >
                                                            {{
                                                                 redemption.user?.email ||
                                                                 "Anonymous"
                                                            }}
                                                       </p>
                                                  </div>
                                             </div>
                                        </td>
                                        <td class="px-6 py-4">
                                             <span
                                                  class="text-sm font-bold text-emerald-600 dark:text-emerald-400"
                                             >
                                                  −{{
                                                       formatPrice(
                                                            redemption.discount_amount
                                                       )
                                                  }}
                                                  XAF
                                             </span>
                                        </td>
                                        <td
                                             class="px-6 py-4 text-sm text-gray-600 dark:text-gray-400"
                                        >
                                             {{
                                                  redemption.original_amount
                                                       ? formatPrice(
                                                              redemption.original_amount
                                                         ) + " XAF"
                                                       : "—"
                                             }}
                                        </td>
                                        <td
                                             class="px-6 py-4 text-sm font-bold text-gray-900 dark:text-white"
                                        >
                                             {{
                                                  redemption.final_amount
                                                       ? formatPrice(
                                                              redemption.final_amount
                                                         ) + " XAF"
                                                       : "—"
                                             }}
                                        </td>
                                        <td
                                             class="px-6 py-4 text-sm text-gray-600 dark:text-gray-400"
                                        >
                                             {{ formatDate(redemption.created_at) }}
                                        </td>
                                   </tr>
                              </tbody>
                         </table>
                    </div>

                    <!-- Mobile list -->
                    <div
                         v-if="redemptions.data.length > 0"
                         class="md:hidden divide-y divide-gray-100 dark:divide-gray-700"
                    >
                         <div
                              v-for="redemption in redemptions.data"
                              :key="redemption.id"
                              class="p-4 hover:bg-gray-50/50 dark:hover:bg-gray-700/30 transition-colors"
                         >
                              <div class="flex items-start gap-3">
                                   <div
                                        class="w-10 h-10 rounded-2xl bg-gradient-to-br from-primary-500 to-purple-600 flex items-center justify-center text-white text-xs font-bold flex-shrink-0 shadow-md"
                                   >
                                        {{
                                             getInitials(redemption.user?.name || "Guest")
                                        }}
                                   </div>
                                   <div class="flex-1 min-w-0">
                                        <div
                                             class="flex items-start justify-between gap-2"
                                        >
                                             <div class="min-w-0">
                                                  <p
                                                       class="text-sm font-semibold text-gray-900 dark:text-white truncate"
                                                  >
                                                       {{
                                                            redemption.user?.name ||
                                                            "Guest User"
                                                       }}
                                                  </p>
                                                  <p
                                                       class="text-xs text-gray-500 dark:text-gray-400 truncate"
                                                  >
                                                       {{
                                                            redemption.user?.email ||
                                                            "Anonymous"
                                                       }}
                                                  </p>
                                             </div>
                                             <span
                                                  class="text-xs text-gray-400 dark:text-gray-500 flex-shrink-0 whitespace-nowrap"
                                             >
                                                  {{ formatDate(redemption.created_at) }}
                                             </span>
                                        </div>
                                        <div class="flex items-center gap-3 mt-2">
                                             <span
                                                  class="text-sm font-bold text-emerald-600 dark:text-emerald-400"
                                             >
                                                  −{{
                                                       formatPrice(
                                                            redemption.discount_amount
                                                       )
                                                  }}
                                                  XAF
                                             </span>
                                             <span
                                                  v-if="redemption.original_amount"
                                                  class="text-xs text-gray-500 dark:text-gray-400"
                                             >
                                                  {{
                                                       formatPrice(
                                                            redemption.original_amount
                                                       )
                                                  }}
                                                  →
                                                  {{
                                                       formatPrice(
                                                            redemption.final_amount
                                                       )
                                                  }}
                                             </span>
                                        </div>
                                   </div>
                              </div>
                         </div>
                    </div>

                    <!-- Pagination -->
                    <div
                         v-if="redemptions.links && redemptions.links.length > 3"
                         class="px-6 py-4 border-t border-gray-100 dark:border-gray-700 flex flex-wrap items-center justify-center gap-1"
                    >
                         <template
                              v-for="(link, index) in redemptions.links"
                              :key="index"
                         >
                              <span
                                   v-if="!link.url"
                                   class="px-3 py-2 text-sm text-gray-400 dark:text-gray-600 cursor-not-allowed"
                                   v-html="link.label"
                              ></span>
                              <a
                                   v-else
                                   :href="link.url"
                                   class="px-3 py-2 text-sm rounded-lg transition-colors font-medium"
                                   :class="
                                        link.active
                                             ? 'bg-primary-600 text-white font-semibold shadow-sm'
                                             : 'text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700'
                                   "
                                   v-html="link.label"
                              ></a>
                         </template>
                    </div>
               </div>

               <!-- ==================== COUPON SUMMARY ==================== -->
               <div
                    class="relative overflow-hidden bg-gradient-to-br from-primary-600 via-primary-700 to-purple-700 rounded-2xl shadow-lg p-6 text-white"
               >
                    <div
                         class="absolute inset-0 opacity-[0.06]"
                         style="
                              background-image: radial-gradient(
                                   circle,
                                   white 1px,
                                   transparent 1px
                              );
                              background-size: 22px 22px;
                         "
                    ></div>

                    <div class="relative flex items-center gap-3 mb-5">
                         <div
                              class="w-9 h-9 rounded-xl bg-white/10 backdrop-blur-sm flex items-center justify-center"
                         >
                              <svg
                                   class="w-4 h-4"
                                   fill="none"
                                   stroke="currentColor"
                                   viewBox="0 0 24 24"
                              >
                                   <path
                                        stroke-linecap="round"
                                        stroke-linejoin="round"
                                        stroke-width="2"
                                        d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z"
                                   />
                              </svg>
                         </div>
                         <h3 class="font-bold tracking-tight">Coupon summary</h3>
                    </div>

                    <div class="relative grid grid-cols-2 sm:grid-cols-4 gap-4">
                         <div>
                              <p
                                   class="text-[10px] uppercase tracking-widest text-white/60 font-bold mb-1"
                              >
                                   Type
                              </p>
                              <p class="font-bold capitalize">
                                   {{
                                        coupon.discount_type === "percentage"
                                             ? "Percentage"
                                             : "Fixed"
                                   }}
                              </p>
                         </div>
                         <div>
                              <p
                                   class="text-[10px] uppercase tracking-widest text-white/60 font-bold mb-1"
                              >
                                   Code
                              </p>
                              <p class="font-mono font-bold">
                                   {{ coupon.code || "No code" }}
                              </p>
                         </div>
                         <div>
                              <p
                                   class="text-[10px] uppercase tracking-widest text-white/60 font-bold mb-1"
                              >
                                   Status
                              </p>
                              <p class="font-bold capitalize">Active</p>
                         </div>
                         <div>
                              <p
                                   class="text-[10px] uppercase tracking-widest text-white/60 font-bold mb-1"
                              >
                                   Created
                              </p>
                              <p class="font-bold text-sm">
                                   {{ formatDate(coupon.created_at) }}
                              </p>
                         </div>
                    </div>
               </div>
          </div>
     </AuthenticatedLayout>
</template>

<script setup>
import AuthenticatedLayout from "@/Layouts/AuthenticatedLayout.vue";
// import Breadcrumb from '@/Components/Breadcrumb.vue';
import { computed } from "vue";
import PageHeader from "@/Components/PageHeader.vue";

const props = defineProps({
     coupon: {
          type: Object,
          required: true,
     },
     stats: {
          type: Object,
          required: true,
     },
     redemptions: {
          type: Object,
          default: () => ({ data: [], links: [], total: 0 }),
     },
});

const usagePercent = computed(() => {
     if (!props.coupon.usage_limit) return 0;
     return Math.round((props.coupon.usage_count / props.coupon.usage_limit) * 100);
});

const formatPrice = (price) => {
     if (!price && price !== 0) return "0";
     return new Intl.NumberFormat("en-US", {
          minimumFractionDigits: 0,
          maximumFractionDigits: 0,
     }).format(price);
};

const formatDate = (date) => {
     if (!date) return "—";
     return new Date(date).toLocaleDateString("en-US", {
          month: "short",
          day: "numeric",
          year: "numeric",
     });
};

const getInitials = (name) => {
     if (!name) return "?";
     return name
          .split(" ")
          .map((n) => n[0])
          .join("")
          .toUpperCase()
          .slice(0, 2);
};
</script>
