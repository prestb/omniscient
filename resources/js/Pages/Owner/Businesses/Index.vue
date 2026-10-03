<!-- resources/js/Pages/Owner/Businesses/Index.vue -->
<template>
    <AuthenticatedLayout>
        <!-- COMPACT HEADER -->
        <PageHeader color="primary" :breadcrumb="[
            { label: 'Dashboard', href: '/owner/dashboard' },
            { label: 'Businesses' }
        ]">
            <template #icon>
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                    d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" />
            </template>
            <template #title>My Businesses</template>
            <template #subtitle>Manage all your organizations in one place</template>
            <template #actions>
                <span
                    class="inline-flex items-center gap-2 px-3 py-2 bg-gray-50 dark:bg-gray-700 border border-gray-200 dark:border-gray-600 rounded-xl text-sm font-semibold text-gray-700 dark:text-gray-300">
                    <span class="w-1.5 h-1.5 rounded-full"
                        :class="subscription.status === 'active' ? 'bg-emerald-500 animate-pulse' : 'bg-red-500'"></span>
                    {{ subscription.status === 'active' ? 'Active' : 'Inactive' }}
                </span>

                <span
                    class="inline-flex items-center gap-2 px-3 py-2 bg-gray-50 dark:bg-gray-700 border border-gray-200 dark:border-gray-600 rounded-xl text-sm font-semibold text-gray-700 dark:text-gray-300">
                    <svg class="w-4 h-4 text-primary-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                    {{ subscription.plan_name }}
                    <span class="text-xs text-gray-400 dark:text-gray-500">·</span>
                    <span class="text-xs">
                        {{ subscription.current_businesses }}
                        {{ subscription.current_businesses === 1 ? 'business' : 'businesses' }}
                    </span>
                </span>

                <a v-if="canCreate" href="/owner/businesses/create"
                    class="inline-flex items-center gap-2 px-4 py-2 bg-gradient-to-r from-primary-600 to-primary-700 text-white rounded-xl font-semibold hover:from-primary-700 hover:to-primary-800 transition-all shadow-lg shadow-primary-500/25 text-sm">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                    </svg>
                    Add Business
                </a>
                <a v-else-if="!subscription.has_active_subscription" href="/owner/subscription/renew"
                    class="inline-flex items-center gap-2 px-4 py-2 bg-gradient-to-r from-red-500 to-red-600 text-white rounded-xl font-semibold hover:from-red-600 hover:to-red-700 transition-all shadow-lg shadow-red-500/25 text-sm">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                    Subscribe Now
                </a>
                <a v-else href="/owner/subscription/renew"
                    class="inline-flex items-center gap-2 px-4 py-2 bg-gradient-to-r from-amber-500 to-orange-500 text-white rounded-xl font-semibold hover:from-amber-600 hover:to-orange-600 transition-all shadow-lg shadow-amber-500/25 text-sm">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M5 10l7-7m0 0l7 7m-7-7v18" />
                    </svg>
                    Upgrade Plan
                </a>
            </template>
        </PageHeader>

        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8 space-y-6">

            <!-- ==================== WARNINGS ==================== -->
            <div v-if="!subscription.has_active_subscription && businesses.length > 0"
                class="bg-gradient-to-br from-red-50 to-red-100 rounded-2xl border border-red-200 p-5 flex items-start gap-4">
                <div class="w-11 h-11 rounded-xl bg-red-100 flex items-center justify-center flex-shrink-0">
                    <svg class="w-5 h-5 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                    </svg>
                </div>
                <div class="flex-1">
                    <p class="text-sm font-bold text-red-800">No active subscription</p>
                    <p class="text-sm text-red-700 mt-0.5">Your businesses will be hidden until you activate a
                        subscription.</p>
                </div>
                <a href="/owner/subscription/renew"
                    class="flex-shrink-0 inline-flex items-center gap-2 px-4 py-2 bg-red-600 text-white rounded-xl font-semibold hover:bg-red-700 transition-colors text-sm">
                    Subscribe
                </a>
            </div>

            <!-- ==================== USAGE BAR ==================== -->
            <div v-if="businesses.length > 0"
                class="bg-white dark:bg-gray-800 rounded-2xl shadow-sm border border-gray-100 dark:border-gray-700 p-5">
                <div class="flex items-center justify-between mb-3">
                    <div class="flex items-center gap-3">
                        <div
                            class="w-9 h-9 rounded-xl bg-primary-50 dark:bg-primary-900/30 flex items-center justify-center">
                            <svg class="w-4 h-4 text-primary-600 dark:text-primary-400" fill="none"
                                stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" />
                            </svg>
                        </div>
                        <div>
                            <p class="text-sm font-bold text-gray-900 dark:text-white">Business usage</p>
                            <p class="text-xs text-gray-400">How many businesses you've created</p>
                        </div>
                    </div>
                    <div class="text-right">
                        <p class="text-lg font-bold text-gray-900 dark:text-white tracking-tight">
                            {{ subscription.current_businesses }}
                        </p>
                        <p class="text-xs text-gray-400 font-medium">total</p>
                    </div>
                </div>
            </div>

            <!-- ==================== STATS BAR ==================== -->
            <div v-if="businesses && businesses.length > 0" class="grid grid-cols-2 sm:grid-cols-4 gap-4">
                <div
                    class="bg-white dark:bg-gray-800 rounded-2xl border border-gray-100 dark:border-gray-700 p-4 hover:shadow-md transition-shadow">
                    <p class="text-[10px] uppercase tracking-widest text-gray-400 font-bold">Total</p>
                    <p class="text-2xl font-bold text-gray-900 dark:text-white tracking-tight mt-1">{{ businesses.length
                        }}</p>
                </div>
                <div
                    class="bg-white dark:bg-gray-800 rounded-2xl border border-gray-100 dark:border-gray-700 p-4 hover:shadow-md transition-shadow">
                    <p class="text-[10px] uppercase tracking-widest text-gray-400 font-bold">Published</p>
                    <p class="text-2xl font-bold text-emerald-600 tracking-tight mt-1">
                        {{businesses.filter(b => b.status === 'published').length}}
                    </p>
                </div>
                <div
                    class="bg-white dark:bg-gray-800 rounded-2xl border border-gray-100 dark:border-gray-700 p-4 hover:shadow-md transition-shadow">
                    <p class="text-[10px] uppercase tracking-widest text-gray-400 font-bold">Pending</p>
                    <p class="text-2xl font-bold text-amber-600 tracking-tight mt-1">
                        {{businesses.filter(b => b.status === 'submitted').length}}
                    </p>
                </div>
                <div
                    class="bg-white dark:bg-gray-800 rounded-2xl border border-gray-100 dark:border-gray-700 p-4 hover:shadow-md transition-shadow">
                    <p class="text-[10px] uppercase tracking-widest text-gray-400 font-bold">Drafts</p>
                    <p class="text-2xl font-bold text-gray-500 tracking-tight mt-1">
                        {{businesses.filter(b => b.status === 'draft').length}}
                    </p>
                </div>
            </div>

            <!-- ==================== BUSINESS CARDS ==================== -->
            <div v-if="businesses && businesses.length > 0"
                class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                <!-- ⬇️ SWIPE: wrap each card in SwipeableListItem -->
                <SwipeableListItem v-for="business in businesses" :key="business.id" :item="business" label="business"
                    @delete="deleteBusiness">
                    <div class="group bg-white dark:bg-gray-800 rounded-2xl shadow-sm border hover:shadow-xl hover:-translate-y-1 transition-all duration-300 overflow-hidden"
                        :class="business.hidden_at ? 'border-amber-200 dark:border-amber-800 opacity-75' : 'border-gray-100 dark:border-gray-700'">

                        <!-- Cover with overlay -->
                        <div
                            class="relative h-44 overflow-hidden bg-gradient-to-br from-gray-100 to-gray-50 dark:from-gray-700 dark:to-gray-800">
                            <img v-if="business.cover_image" :src="'/storage/' + business.cover_image"
                                :alt="business.name"
                                class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-500" />
                            <div v-else class="w-full h-full flex items-center justify-center">
                                <span class="text-6xl opacity-20">🏢</span>
                            </div>

                            <div
                                class="absolute inset-0 bg-gradient-to-t from-black/40 via-black/0 to-black/0 opacity-0 group-hover:opacity-100 transition-opacity">
                            </div>

                            <div v-if="business.is_featured"
                                class="absolute top-3 left-3 px-2.5 py-1 bg-gradient-to-r from-amber-400 to-yellow-500 text-amber-900 text-[10px] font-bold rounded-full tracking-wide uppercase shadow-lg shadow-amber-200/50 flex items-center gap-1">
                                <svg class="w-3 h-3" fill="currentColor" viewBox="0 0 20 20">
                                    <path
                                        d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z" />
                                </svg>
                                Featured
                            </div>

                            <div class="absolute top-3 right-3">
                                <span :class="statusClass(business.status) + ' backdrop-blur-sm shadow-sm'">
                                    {{ getStatusLabel(business.status) }}
                                </span>
                            </div>

                            <div v-if="!subscription.has_active_subscription"
                                class="absolute inset-0 bg-black/60 backdrop-blur-[2px] flex items-center justify-center">
                                <span
                                    class="px-4 py-2 bg-red-500 text-white text-xs font-bold rounded-full tracking-wide uppercase shadow-lg">
                                    Subscription Required
                                </span>
                            </div>
                        </div>

                        <!-- Body -->
                        <div class="p-5 pt-7 relative">
                            <div class="absolute -top-7 left-5">
                                <div v-if="business.logo"
                                    class="w-14 h-14 rounded-2xl overflow-hidden border-4 border-white dark:border-gray-800 bg-white shadow-md">
                                    <img :src="'/storage/' + business.logo" :alt="business.name"
                                        class="w-full h-full object-cover" />
                                </div>
                                <div v-else
                                    class="w-14 h-14 rounded-2xl bg-gradient-to-br from-primary-500 to-primary-700 border-4 border-white dark:border-gray-800 shadow-md flex items-center justify-center">
                                    <span class="text-white font-bold text-lg">{{ getInitials(business.name) }}</span>
                                </div>
                            </div>

                            <div class="ml-16">
                                <h3
                                    class="text-base font-bold text-gray-900 dark:text-white truncate group-hover:text-primary-600 transition-colors tracking-tight">
                                    {{ business.name }}
                                </h3>
                                <div class="flex flex-wrap gap-1 mt-1">
                                    <span v-for="category in business.categories?.slice(0, 2)" :key="category.id"
                                        class="inline-flex items-center px-2 py-0.5 bg-primary-50 dark:bg-primary-900/30 text-primary-700 dark:text-primary-400 text-[10px] font-medium rounded-full">
                                        {{ category.name }}
                                    </span>
                                    <span v-if="business.categories?.length > 2"
                                        class="inline-flex items-center px-2 py-0.5 bg-gray-100 dark:bg-gray-700 text-gray-500 dark:text-gray-400 text-[10px] font-medium rounded-full">
                                        +{{ business.categories.length - 2 }}
                                    </span>
                                </div>
                            </div>

                            <p class="text-sm text-gray-500 dark:text-gray-400 mt-4 line-clamp-2 leading-relaxed">
                                {{ business.description || 'No description available' }}
                            </p>

                            <div
                                class="flex items-center gap-4 mt-4 pt-4 border-t border-gray-100 dark:border-gray-700 text-xs text-gray-500 dark:text-gray-400">
                                <span class="flex items-center gap-1.5">
                                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" />
                                    </svg>
                                                                        <span class="font-semibold text-gray-700 dark:text-gray-300">{{
                                        business.locations?.length || 0
                                        }}</span>
                                    location{{ (business.locations?.length || 0) !== 1 ? 's' : '' }}
                                </span>
                                <span class="flex items-center gap-1.5">
                                    <svg class="w-3.5 h-3.5 text-amber-400" fill="currentColor" viewBox="0 0 20 20">
                                        <path
                                            d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z" />
                                    </svg>
                                    <span class="font-semibold text-gray-700 dark:text-gray-300">{{
                                        business.average_rating || 0
                                        }}</span>
                                </span>
                                <span class="flex items-center gap-1.5">
                                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z" />
                                    </svg>
                                    <span class="font-semibold text-gray-700 dark:text-gray-300">{{
                                        business.total_reviews || 0
                                        }}</span>
                                </span>
                            </div>

                            <div v-if="business.hidden_at"
                                class="mt-3 inline-flex items-center gap-1.5 px-2.5 py-1 rounded-lg text-[10px] font-bold uppercase tracking-wide bg-amber-100 dark:bg-amber-900/30 text-amber-800 dark:text-amber-400 border border-amber-200 dark:border-amber-800">
                                <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l3.59 3.59m0 0A9.953 9.953 0 0112 5c4.478 0 8.268 2.943 9.543 7a10.025 10.025 0 01-4.132 5.411m0 0L21 21" />
                                </svg>
                                Hidden from public
                            </div>

                            <div
                                class="flex items-center gap-2 mt-4 pt-4 border-t border-gray-100 dark:border-gray-700">
                                <span v-if="business.hidden_at"
                                    class="flex-1 inline-flex items-center justify-center gap-1.5 px-3 py-2 bg-gray-100 dark:bg-gray-700 text-gray-400 text-xs font-semibold rounded-lg cursor-not-allowed"
                                    title="Delete this item or upgrade to edit">
                                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" />
                                    </svg>
                                    Edit locked
                                </span>
                                <a v-else :href="`/owner/businesses/${business.id}/edit`"
                                    class="flex-1 inline-flex items-center justify-center gap-1.5 px-3 py-2 bg-primary-50 dark:bg-primary-900/30 hover:bg-primary-100 dark:hover:bg-primary-900/50 text-primary-700 dark:text-primary-400 text-xs font-semibold rounded-lg transition-colors">
                                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                                    </svg>
                                    Edit
                                </a>
                                <a :href="`/owner/businesses/${business.id}/locations`"
                                    class="flex-1 inline-flex items-center justify-center gap-1.5 px-3 py-2 bg-gray-50 dark:bg-gray-700 hover:bg-gray-100 dark:hover:bg-gray-600 text-gray-700 dark:text-gray-300 text-xs font-semibold rounded-lg transition-colors">
                                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" />
                                    </svg>
                                                                        Locations
                                </a>
                                <button @click="deleteBusiness(business)"
                                    class="w-9 h-9 flex items-center justify-center text-red-500 hover:text-red-700 hover:bg-red-50 dark:hover:bg-red-900/30 rounded-lg transition-colors"
                                    title="Delete business">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                    </svg>
                                </button>
                            </div>

                            <button v-if="business.status === 'draft'" @click="submitBusiness(business)"
                                class="w-full mt-3 inline-flex items-center justify-center gap-2 px-4 py-2.5 bg-gradient-to-r from-emerald-500 to-emerald-600 text-white text-sm font-semibold rounded-xl hover:from-emerald-600 hover:to-emerald-700 transition-all duration-200 shadow-lg shadow-emerald-500/25 hover:shadow-emerald-500/40 hover:-translate-y-0.5">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                                </svg>
                                Submit for Review
                            </button>
                        </div>
                    </div>
                </SwipeableListItem>
            </div>

            <!-- ==================== EMPTY STATE ==================== -->
            <div v-else
                class="bg-white dark:bg-gray-800 rounded-2xl shadow-sm border border-gray-100 dark:border-gray-700 p-12 text-center">
                <div
                    class="w-20 h-20 bg-gradient-to-br from-primary-100 to-primary-200 rounded-2xl flex items-center justify-center mx-auto mb-5 shadow-sm">
                    <span class="text-4xl">🏢</span>
                </div>
                <h3 class="text-xl font-bold text-gray-900 dark:text-white tracking-tight mb-2">No businesses yet</h3>
                <p class="text-gray-500 dark:text-gray-400 max-w-md mx-auto mb-6">
                    You haven't created an organization yet. An organization is optional - it can group several of your Listings listing.
                </p>

                <a v-if="canCreate" href="/owner/businesses/create"
                    class="inline-flex items-center gap-2 px-6 py-3 bg-gradient-to-r from-primary-600 to-primary-700 text-white rounded-xl hover:from-primary-700 hover:to-primary-800 transition-all duration-200 shadow-lg shadow-primary-500/25 hover:shadow-primary-500/40 hover:-translate-y-0.5 font-semibold text-sm">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                    </svg>
                    Create Your First Business
                </a>
                <div v-else class="space-y-3">
                    <p class="text-amber-600 text-sm">You've reached the maximum number of businesses on your plan.</p>
                    <a v-if="subscription.has_active_subscription" href="/owner/subscription/renew"
                        class="inline-flex items-center gap-2 px-6 py-3 bg-gradient-to-r from-amber-500 to-orange-500 text-white rounded-xl hover:from-amber-600 hover:to-orange-600 transition-all shadow-lg shadow-amber-500/25 font-semibold text-sm">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M5 10l7-7m0 0l7 7m-7-7v18" />
                        </svg>
                        Upgrade Plan
                    </a>
                    <a v-else href="/owner/subscription/renew"
                        class="inline-flex items-center gap-2 px-6 py-3 bg-gradient-to-r from-red-500 to-red-600 text-white rounded-xl hover:from-red-600 hover:to-red-700 transition-all shadow-lg shadow-red-500/25 font-semibold text-sm">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                        Subscribe Now
                    </a>
                </div>
            </div>
        </div>

        <DeleteBusinessModal v-if="deleteTarget" :is-open="!!deleteTarget" :business="deleteTarget"
            @close="deleteTarget = null" />
    </AuthenticatedLayout>
</template>

<script setup>
    import { computed, ref } from 'vue';
    import { router } from '@inertiajs/vue3';
    import { useToast } from '@/composables/useToast';
    import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
    import PageHeader from '@/Components/PageHeader.vue';
    import DeleteBusinessModal from '@/Components/Owner/DeleteBusinessModal.vue';
    import SwipeableListItem from '@/Components/Common/SwipeableListItem.vue';
    import { useStatusBadge } from '@/composables/useStatusBadge';
    import { useConfirm } from '@/composables/useConfirm';

    const deleteTarget = ref(null);

    const deleteBusiness = (business) => {
        deleteTarget.value = business;
    };

    const props = defineProps({
        businesses: Array,
        canCreate: Boolean,
        subscription: Object,
    });

    const { success, error } = useToast();
    const { business: statusClass } = useStatusBadge();
    const { confirm: confirmDialog } = useConfirm();

    // PHASE 11 / WAVE 1D-4 — a Business is not a quota unit, so there is no
    // business usage percentage to compute. `max_listings` governs Listings.

    const getStatusLabel = (status) => {
        const labels = {
            'draft': 'Draft',
            'submitted': 'Pending',
            'approved': 'Approved',
            'published': 'Published',
            'rejected': 'Rejected',
            'suspended': 'Suspended',
        };
        return labels[status] || status;
    };

    const getInitials = (name) => {
        if (!name) return '?';
        return name.split(' ').map(n => n[0]).join('').toUpperCase().slice(0, 2);
    };

    // ⬇️ SWIPE + CONFIRM: swap window.confirm → useConfirm (promise-based)
    const submitBusiness = async (business) => {
        const confirmed = await confirmDialog({
            title: 'Submit for review?',
            message: `"${business.name}" will be sent to the admin team for approval. You'll be notified once it's reviewed.`,
            confirmText: 'Submit for Review',
            cancelText: 'Cancel',
            variant: 'primary',
        });

        if (!confirmed) return;

        router.post(`/owner/businesses/${business.id}/submit`, {}, {
            onSuccess: (page) => {
                // ✅ Only show success if the server actually set a success flash.
                // The `verified` middleware returns a redirect with an `error` flash
                // instead — and Inertia still reports that as `onSuccess` because
                // the final page load is a 200. So we check the flash.
                const flashSuccess = page?.props?.flash?.success;
                if (flashSuccess) {
                    success(
                        'Business Submitted! 📋',
                        flashSuccess,
                        {
                            duration: 5000,
                            actions: [
                                { label: 'Refresh', dismiss: true, onClick: () => window.location.reload() }
                            ]
                        }
                    );
                }
                // If no success flash, the AuthenticatedLayout's flash handler
                // will surface the actual server message (e.g. the "verify your
                // email" error).
            },
            onError: () => {
                error('Submission Failed ❌', `Failed to submit "${business.name}" for review. Please try again.`, { duration: 5000 });
            }
        });
    };
</script>

<style scoped>
    .line-clamp-2 {
        display: -webkit-box;
        -webkit-line-clamp: 2;
        -webkit-box-orient: vertical;
        overflow: hidden;
    }
</style>