<!-- resources/js/Pages/Owner/Businesses/Edit.vue -->
<template>
    <AuthenticatedLayout>
        <!-- COMPACT HEADER -->
        <PageHeader color="primary" :breadcrumb="[
            { label: 'Dashboard', href: '/owner/dashboard' },
            { label: 'Businesses', href: '/owner/businesses' },
            { label: business.name }
        ]">
            <template #icon>
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                    d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" />
            </template>
            <template #title>{{ business.name }}</template>
            <template #subtitle>Manage your business listing</template>
            <template #actions>
                <BusinessStatusToggle :business="business" />

                <span v-if="business.is_featured"
                    class="inline-flex items-center gap-1 px-2.5 py-1.5 text-[10px] font-bold uppercase tracking-wide bg-gradient-to-r from-amber-400 to-yellow-500 text-amber-900 rounded-xl shadow-sm">
                    <svg class="w-3 h-3" fill="currentColor" viewBox="0 0 20 20">
                        <path
                            d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z" />
                    </svg>
                    Featured
                </span>

                <button v-if="business.status === 'draft'" @click="submitBusiness"
                    class="inline-flex items-center gap-2 px-4 py-2 bg-emerald-50 dark:bg-emerald-900/30 text-emerald-700 dark:text-emerald-400 border border-emerald-200 dark:border-emerald-800 rounded-xl font-semibold hover:bg-emerald-100 dark:hover:bg-emerald-900/50 transition-colors text-sm">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                    Submit for Review
                </button>

                <a :href="`/business/${business.slug}`" target="_blank"
                    class="inline-flex items-center gap-2 px-4 py-2 border border-gray-300 dark:border-gray-600 text-gray-700 dark:text-gray-300 rounded-xl hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors text-sm font-semibold">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                    </svg>
                    View Profile
                </a>
            </template>
        </PageHeader>

        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8 space-y-6">

            <!-- QUICK STATS -->
            <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-5 gap-4">
                <div class="bg-white dark:bg-gray-800 rounded-2xl border border-gray-100 dark:border-gray-700 p-4 hover:shadow-md transition-shadow">
                                        <p class="text-[10px] uppercase tracking-widest text-gray-400 dark:text-gray-500 font-bold">Locations</p>
                    <p class="text-2xl font-bold text-gray-900 dark:text-white tracking-tight mt-1">{{ business.locations?.length || 0 }}
                    </p>
                </div>
                <div class="bg-white dark:bg-gray-800 rounded-2xl border border-gray-100 dark:border-gray-700 p-4 hover:shadow-md transition-shadow">
                    <p class="text-[10px] uppercase tracking-widest text-gray-400 dark:text-gray-500 font-bold">Categories</p>
                    <p class="text-2xl font-bold text-gray-900 dark:text-white tracking-tight mt-1">{{ business.categories?.length || 0
                        }}</p>
                </div>
                <div class="bg-white dark:bg-gray-800 rounded-2xl border border-gray-100 dark:border-gray-700 p-4 hover:shadow-md transition-shadow">
                    <p class="text-[10px] uppercase tracking-widest text-gray-400 dark:text-gray-500 font-bold">Services</p>
                    <p class="text-2xl font-bold text-gray-900 dark:text-white tracking-tight mt-1">{{ business.services?.length || 0 }}
                    </p>
                </div>
                <div class="bg-white dark:bg-gray-800 rounded-2xl border border-gray-100 dark:border-gray-700 p-4 hover:shadow-md transition-shadow">
                    <p class="text-[10px] uppercase tracking-widest text-gray-400 dark:text-gray-500 font-bold">Images</p>
                    <p class="text-2xl font-bold text-gray-900 dark:text-white tracking-tight mt-1">{{ totalImages }}</p>
                </div>
                <div class="bg-white dark:bg-gray-800 rounded-2xl border border-gray-100 dark:border-gray-700 p-4 hover:shadow-md transition-shadow">
                    <p class="text-[10px] uppercase tracking-widest text-gray-400 dark:text-gray-500 font-bold">Status</p>
                    <div class="mt-2">
                        <VerifiedBadge :verified="business.feature_flags?.verified_badge || false" size="sm" />
                    </div>
                </div>
            </div>

            <!-- VERIFICATION BANNER -->
            <div v-if="!business.feature_flags?.verified_badge"
                class="bg-gradient-to-br from-blue-50 to-indigo-50 dark:from-blue-900/20 dark:to-indigo-900/10 border border-blue-200 dark:border-blue-800 rounded-2xl p-5 flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4">
                <div class="flex items-start gap-4">
                    <div class="w-11 h-11 rounded-xl bg-blue-100 dark:bg-blue-900/40 flex items-center justify-center flex-shrink-0">
                        <svg class="w-5 h-5 text-blue-600 dark:text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M9 12l2 2 4-4M7.835 4.697a3.42 3.42 0 001.946-.806 3.42 3.42 0 014.438 0 3.42 3.42 0 001.946.806 3.42 3.42 0 013.138 3.138 3.42 3.42 0 00.806 1.946 3.42 3.42 0 010 4.438 3.42 3.42 0 00-.806 1.946 3.42 3.42 0 01-3.138 3.138 3.42 3.42 0 00-1.946.806 3.42 3.42 0 01-4.438 0 3.42 3.42 0 00-1.946-.806 3.42 3.42 0 01-3.138-3.138 3.42 3.42 0 00-.806-1.946 3.42 3.42 0 010-4.438 3.42 3.42 0 00.806-1.946 3.42 3.42 0 013.138-3.138z" />
                        </svg>
                    </div>
                    <div>
                        <h3 class="font-bold text-gray-900 dark:text-white flex items-center gap-2">
                            Get verified
                            <span
                                class="text-[10px] bg-blue-600 text-white px-2 py-0.5 rounded-full font-bold tracking-wide uppercase">Growth+</span>
                        </h3>
                        <p class="text-sm text-gray-600 dark:text-gray-300 mt-0.5">
                            Add a blue verified badge and stand out in search results.
                        </p>
                    </div>
                </div>
                <button @click="showUpgrade('Verified Badge', 'Growth', [
                    'Blue checkmark on your business profile',
                    'Build trust with customers',
                    'Stand out in search results',
                    'Increase conversions by up to 40%',
                ])"
                    class="px-5 py-2.5 bg-gradient-to-r from-blue-600 to-indigo-600 text-white rounded-xl font-semibold hover:from-blue-700 hover:to-indigo-700 transition-all shadow-lg shadow-blue-500/25 hover:-translate-y-0.5 whitespace-nowrap text-sm">
                    Upgrade Now
                </button>
            </div>

            <div v-else
                class="bg-gradient-to-br from-emerald-50 to-green-50 dark:from-emerald-900/20 dark:to-green-900/10 border border-emerald-200 dark:border-emerald-800 rounded-2xl p-5 flex items-center gap-4">
                <div class="w-11 h-11 rounded-xl bg-emerald-100 dark:bg-emerald-900/40 flex items-center justify-center flex-shrink-0">
                    <svg class="w-5 h-5 text-emerald-600 dark:text-emerald-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7" />
                    </svg>
                </div>
                <div>
                    <h3 class="font-bold text-gray-900 dark:text-white">Your business is verified</h3>
                    <p class="text-sm text-gray-600 dark:text-gray-300 mt-0.5">
                        Your blue verified badge is displayed on your public profile.
                    </p>
                </div>
            </div>

            <!-- FEATURED LISTING CARD -->
            <div v-if="business.feature_flags?.featured_listing"
                class="bg-gradient-to-br from-amber-50 to-orange-50 dark:from-amber-900/20 dark:to-orange-900/10 border border-amber-200 dark:border-amber-800 rounded-2xl p-5">
                <div class="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4">
                    <div class="flex items-start gap-4">
                        <div class="w-11 h-11 rounded-xl bg-amber-100 dark:bg-amber-900/40 flex items-center justify-center flex-shrink-0">
                            <svg class="w-5 h-5 text-amber-600 dark:text-amber-400" fill="currentColor" viewBox="0 0 20 20">
                                <path
                                    d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z" />
                            </svg>
                        </div>
                        <div>
                            <h3 class="font-bold text-gray-900 dark:text-white flex items-center gap-2">
                                Featured listing
                                <span v-if="business.is_featured"
                                    class="text-[10px] bg-amber-500 text-white px-2 py-0.5 rounded-full font-bold tracking-wide uppercase">
                                    Active
                                </span>
                            </h3>
                            <p class="text-sm text-gray-600 dark:text-gray-300 mt-0.5">
                                {{ business.is_featured
                                    ? "Your business appears at the top of search results and on the homepage."
                                    : "Boost your visibility by featuring your business at the top of the directory." }}
                            </p>
                        </div>
                    </div>
                    <button @click="toggleFeatured" :disabled="featuredProcessing"
                        class="relative inline-flex items-center h-7 rounded-full w-14 transition-colors focus:outline-none focus:ring-2 focus:ring-amber-500 focus:ring-offset-2 flex-shrink-0 disabled:opacity-50"
                        :class="business.is_featured ? 'bg-amber-500' : 'bg-gray-300 dark:bg-gray-600'">
                        <span
                            class="inline-block h-5 w-5 transform rounded-full bg-white transition-transform shadow-md"
                            :class="business.is_featured ? 'translate-x-8' : 'translate-x-1'" />
                    </button>
                </div>
            </div>

            <div v-else
                class="bg-gradient-to-br from-amber-50 to-orange-50 dark:from-amber-900/20 dark:to-orange-900/10 border border-amber-200 dark:border-amber-800 rounded-2xl p-5 flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4">
                <div class="flex items-start gap-4">
                    <div class="w-11 h-11 rounded-xl bg-amber-100 dark:bg-amber-900/40 flex items-center justify-center flex-shrink-0">
                        <svg class="w-5 h-5 text-amber-600 dark:text-amber-400" fill="currentColor" viewBox="0 0 20 20">
                            <path
                                d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z" />
                        </svg>
                    </div>
                    <div>
                        <h3 class="font-bold text-gray-900 dark:text-white flex items-center gap-2">
                            Featured listing
                            <span
                                class="text-[10px] bg-amber-600 text-white px-2 py-0.5 rounded-full font-bold tracking-wide uppercase">Growth+</span>
                        </h3>
                        <p class="text-sm text-gray-600 dark:text-gray-300 mt-0.5">
                            Boost visibility by featuring your business at the top of the directory.
                        </p>
                    </div>
                </div>
                <button @click="showUpgrade('Featured Listing', 'Growth', [
                    'Appear at the top of search results',
                    'Get featured on the homepage',
                    '3-5x more visibility',
                    'Stand out with gold badge',
                ])"
                    class="px-5 py-2.5 bg-gradient-to-r from-amber-500 to-orange-500 text-white rounded-xl font-semibold hover:from-amber-600 hover:to-orange-600 transition-all shadow-lg shadow-amber-500/25 hover:-translate-y-0.5 whitespace-nowrap text-sm">
                    Upgrade Now
                </button>
            </div>

            <!-- =========================================================
                 DETAILS FORM
                 ========================================================= -->
            <div class="bg-white dark:bg-gray-800 rounded-2xl border border-gray-100 dark:border-gray-700 shadow-sm overflow-hidden">
                <div
                    class="px-6 py-4 border-b border-gray-100 dark:border-gray-700 bg-gradient-to-br from-gray-50 to-white dark:from-gray-800 dark:to-gray-800/50 flex items-center gap-3">
                    <div class="w-9 h-9 rounded-xl bg-primary-50 dark:bg-primary-900/30 flex items-center justify-center">
                        <svg class="w-4 h-4 text-primary-600 dark:text-primary-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                    </div>
                    <div>
                        <h2 class="text-sm font-bold text-gray-900 dark:text-white tracking-tight">Business details</h2>
                        <p class="text-xs text-gray-400 dark:text-gray-500">Update your business profile information</p>
                    </div>
                </div>

                <div class="p-6">
                    <form @submit.prevent="saveBusiness" class="space-y-6">
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div>
                                <label
                                    class="block text-xs font-bold text-gray-500 dark:text-gray-400 uppercase tracking-widest mb-2">Business
                                    name *</label>
                                <input type="text" v-model="form.name" required maxlength="100"
                                    class="w-full px-4 py-2.5 border border-gray-200 dark:border-gray-600 rounded-xl bg-white dark:bg-gray-900 text-gray-900 dark:text-white placeholder-gray-400 dark:placeholder-gray-500 focus:outline-none focus:ring-2 focus:ring-primary-500 focus:border-transparent transition-colors text-sm"
                                    placeholder="Business name" />
                                <div class="flex justify-end mt-1.5 text-xs">
                                    <span :class="{
                                        'text-gray-400 dark:text-gray-500': (form.name?.length || 0) < 80,
                                        'text-amber-600 dark:text-amber-400': (form.name?.length || 0) >= 80 && (form.name?.length || 0) < 100,
                                        'text-red-600 dark:text-red-400 font-semibold': (form.name?.length || 0) >= 100,
                                    }">
                                        {{ form.name?.length || 0 }} / 100
                                    </span>
                                </div>
                            </div>
                            <div>
                                <label
                                    class="block text-xs font-bold text-gray-500 dark:text-gray-400 uppercase tracking-widest mb-2">Email</label>
                                <input type="email" v-model="form.email"
                                    class="w-full px-4 py-2.5 border border-gray-200 dark:border-gray-600 rounded-xl bg-white dark:bg-gray-900 text-gray-900 dark:text-white placeholder-gray-400 dark:placeholder-gray-500 focus:outline-none focus:ring-2 focus:ring-primary-500 focus:border-transparent transition-colors text-sm"
                                    placeholder="business@example.com" />
                            </div>
                        </div>

                        <div>
                            <label
                                class="block text-xs font-bold text-gray-500 dark:text-gray-400 uppercase tracking-widest mb-2">Website</label>
                            <input type="url" v-model="form.website" placeholder="https://example.com"
                                class="w-full px-4 py-2.5 border border-gray-200 dark:border-gray-600 rounded-xl bg-white dark:bg-gray-900 text-gray-900 dark:text-white placeholder-gray-400 dark:placeholder-gray-500 focus:outline-none focus:ring-2 focus:ring-primary-500 focus:border-transparent transition-colors text-sm" />
                        </div>

                        <div>
                            <label
                                class="block text-xs font-bold text-gray-500 dark:text-gray-400 uppercase tracking-widest mb-2">Description</label>
                            <textarea v-model="form.description" rows="4" maxlength="255"
                                class="w-full px-4 py-2.5 border border-gray-200 dark:border-gray-600 rounded-xl bg-white dark:bg-gray-900 text-gray-900 dark:text-white placeholder-gray-400 dark:placeholder-gray-500 focus:outline-none focus:ring-2 focus:ring-primary-500 focus:border-transparent transition-colors resize-none text-sm"
                                placeholder="Tell customers about your business…"></textarea>
                            <div class="flex justify-between mt-1.5 text-xs">
                                <span class="text-gray-400 dark:text-gray-500">Describe what your business offers</span>
                                <span :class="{
                                    'text-gray-400 dark:text-gray-500': (form.description?.length || 0) < 200,
                                    'text-amber-600 dark:text-amber-400': (form.description?.length || 0) >= 200 && (form.description?.length || 0) < 255,
                                    'text-red-600 dark:text-red-400 font-semibold': (form.description?.length || 0) >= 255,
                                }">
                                    {{ form.description?.length || 0 }} / 255
                                </span>
                            </div>
                        </div>

                        <div>
                            <label
                                class="block text-xs font-bold text-gray-500 dark:text-gray-400 uppercase tracking-widest mb-2">Categories
                                *</label>
                            <CategoryMultiSelect v-model="form.categories" :categories="categories" />
                            <p class="text-xs text-gray-400 dark:text-gray-500 mt-1.5">Search and click to select one or more categories
                            </p>
                        </div>

                        <div class="flex justify-end gap-3 pt-6 border-t border-gray-100 dark:border-gray-700">
                            <a href="/owner/businesses"
                                class="inline-flex items-center justify-center px-6 py-2.5 border border-gray-300 dark:border-gray-600 text-gray-700 dark:text-gray-300 rounded-xl hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors font-semibold text-sm">
                                Cancel
                            </a>
                            <button type="submit" :disabled="processing"
                                class="inline-flex items-center justify-center px-6 py-2.5 bg-gradient-to-r from-primary-600 to-primary-700 text-white rounded-xl hover:from-primary-700 hover:to-primary-800 transition-all shadow-lg shadow-primary-500/25 hover:shadow-primary-500/40 hover:-translate-y-0.5 font-semibold text-sm disabled:opacity-50 disabled:cursor-not-allowed disabled:hover:translate-y-0">
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
                                <span v-else class="flex items-center gap-2">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M5 13l4 4L19 7" />
                                    </svg>
                                    Save Changes
                                </span>
                            </button>
                        </div>
                    </form>
                </div>
            </div>

            <!-- =========================================================
                 MANAGE SECTIONS
                 ========================================================= -->
            <div>
                <div class="flex items-center gap-3 mb-4">
                    <h2 class="text-lg font-bold text-gray-900 dark:text-white tracking-tight">Manage sections</h2>
                    <span class="text-xs text-gray-400 dark:text-gray-500">·</span>
                    <span class="text-xs text-gray-400 dark:text-gray-500">Access all your business tools</span>
                </div>

                <div class="grid grid-cols-2 lg:grid-cols-4 gap-4 sm:gap-5">
                    <!-- Locations -->
                    <a :href="`/owner/businesses/${business.id}/locations`"
                        class="group relative overflow-hidden bg-white dark:bg-gray-800 rounded-2xl border border-gray-100 dark:border-gray-700 hover:border-indigo-200 dark:hover:border-indigo-800 hover:shadow-xl hover:-translate-y-1 transition-all duration-300 p-5 sm:p-6">
                        <div
                            class="absolute -top-8 -right-8 w-24 h-24 bg-gradient-to-br from-indigo-100 to-indigo-50 dark:from-indigo-900/30 dark:to-indigo-900/10 rounded-full blur-2xl opacity-60 group-hover:opacity-100 transition-opacity">
                        </div>
                        <div class="relative">
                            <div
                                class="w-12 h-12 sm:w-14 sm:h-14 rounded-2xl bg-gradient-to-br from-indigo-500 to-indigo-600 flex items-center justify-center shadow-lg shadow-indigo-500/30 mb-4 group-hover:scale-110 transition-transform">
                                <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z" />
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M15 11a3 3 0 11-6 0 3 3 0 016 0z" />
                                </svg>
                            </div>
                                                        <h3 class="text-sm sm:text-base font-bold text-gray-900 dark:text-white tracking-tight">Locations</h3>
                            <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">
                                <span class="font-semibold text-gray-700 dark:text-gray-300">{{ business.locations?.length || 0 }}</span>
                                location{{ (business.locations?.length || 0) === 1 ? '' : 's' }}
                            </p>
                            <div
                                class="flex items-center gap-1 mt-3 text-xs font-bold uppercase tracking-wide text-indigo-600 dark:text-indigo-400 group-hover:text-indigo-700 dark:group-hover:text-indigo-300">
                                Manage
                                <svg class="w-3.5 h-3.5 transform group-hover:translate-x-1 transition-transform"
                                    fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M9 5l7 7-7 7" />
                                </svg>
                            </div>
                        </div>
                    </a>

                    <!-- Services -->
                    <a :href="`/owner/businesses/${business.id}/services`"
                        class="group relative overflow-hidden bg-white dark:bg-gray-800 rounded-2xl border border-gray-100 dark:border-gray-700 hover:border-teal-200 dark:hover:border-teal-800 hover:shadow-xl hover:-translate-y-1 transition-all duration-300 p-5 sm:p-6">
                        <div
                            class="absolute -top-8 -right-8 w-24 h-24 bg-gradient-to-br from-teal-100 to-teal-50 dark:from-teal-900/30 dark:to-teal-900/10 rounded-full blur-2xl opacity-60 group-hover:opacity-100 transition-opacity">
                        </div>
                        <div class="relative">
                            <div
                                class="w-12 h-12 sm:w-14 sm:h-14 rounded-2xl bg-gradient-to-br from-teal-500 to-teal-600 flex items-center justify-center shadow-lg shadow-teal-500/30 mb-4 group-hover:scale-110 transition-transform">
                                <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z" />
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                </svg>
                            </div>
                            <h3 class="text-sm sm:text-base font-bold text-gray-900 dark:text-white tracking-tight">Services</h3>
                            <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">
                                <span class="font-semibold text-gray-700 dark:text-gray-300">{{ business.services?.length || 0 }}</span>
                                service{{ (business.services?.length || 0) === 1 ? '' : 's' }}
                            </p>
                            <div
                                class="flex items-center gap-1 mt-3 text-xs font-bold uppercase tracking-wide text-teal-600 dark:text-teal-400 group-hover:text-teal-700 dark:group-hover:text-teal-300">
                                Manage
                                <svg class="w-3.5 h-3.5 transform group-hover:translate-x-1 transition-transform"
                                    fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M9 5l7 7-7 7" />
                                </svg>
                            </div>
                        </div>
                    </a>

                    <!-- Contacts -->
                    <a :href="`/owner/businesses/${business.id}/contacts`"
                        class="group relative overflow-hidden bg-white dark:bg-gray-800 rounded-2xl border border-gray-100 dark:border-gray-700 hover:border-blue-200 dark:hover:border-blue-800 hover:shadow-xl hover:-translate-y-1 transition-all duration-300 p-5 sm:p-6">
                        <div
                            class="absolute -top-8 -right-8 w-24 h-24 bg-gradient-to-br from-blue-100 to-blue-50 dark:from-blue-900/30 dark:to-blue-900/10 rounded-full blur-2xl opacity-60 group-hover:opacity-100 transition-opacity">
                        </div>
                        <div class="relative">
                            <div
                                class="w-12 h-12 sm:w-14 sm:h-14 rounded-2xl bg-gradient-to-br from-blue-500 to-blue-600 flex items-center justify-center shadow-lg shadow-blue-500/30 mb-4 group-hover:scale-110 transition-transform">
                                <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z" />
                                </svg>
                            </div>
                            <h3 class="text-sm sm:text-base font-bold text-gray-900 dark:text-white tracking-tight">Contacts</h3>
                            <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">
                                <span class="font-semibold text-gray-700 dark:text-gray-300">{{ business.contacts?.length || 0 }}</span>
                                contact{{ (business.contacts?.length || 0) === 1 ? '' : 's' }}
                            </p>
                            <div
                                class="flex items-center gap-1 mt-3 text-xs font-bold uppercase tracking-wide text-blue-600 dark:text-blue-400 group-hover:text-blue-700 dark:group-hover:text-blue-300">
                                Manage
                                <svg class="w-3.5 h-3.5 transform group-hover:translate-x-1 transition-transform"
                                    fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M9 5l7 7-7 7" />
                                </svg>
                            </div>
                        </div>
                    </a>

                    <!-- Images -->
                    <a :href="`/owner/businesses/${business.id}/images`"
                        class="group relative overflow-hidden bg-white dark:bg-gray-800 rounded-2xl border border-gray-100 dark:border-gray-700 hover:border-purple-200 dark:hover:border-purple-800 hover:shadow-xl hover:-translate-y-1 transition-all duration-300 p-5 sm:p-6">
                        <div
                            class="absolute -top-8 -right-8 w-24 h-24 bg-gradient-to-br from-purple-100 to-purple-50 dark:from-purple-900/30 dark:to-purple-900/10 rounded-full blur-2xl opacity-60 group-hover:opacity-100 transition-opacity">
                        </div>
                        <div class="relative">
                            <div
                                class="w-12 h-12 sm:w-14 sm:h-14 rounded-2xl bg-gradient-to-br from-purple-500 to-purple-600 flex items-center justify-center shadow-lg shadow-purple-500/30 mb-4 group-hover:scale-110 transition-transform">
                                <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z" />
                                </svg>
                            </div>
                            <h3 class="text-sm sm:text-base font-bold text-gray-900 dark:text-white tracking-tight">Images</h3>
                            <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">
                                <span class="font-semibold text-gray-700 dark:text-gray-300">{{ totalImages }}</span>
                                image{{ totalImages === 1 ? '' : 's' }}
                            </p>
                            <div
                                class="flex items-center gap-1 mt-3 text-xs font-bold uppercase tracking-wide text-purple-600 dark:text-purple-400 group-hover:text-purple-700 dark:group-hover:text-purple-300">
                                Manage
                                <svg class="w-3.5 h-3.5 transform group-hover:translate-x-1 transition-transform"
                                    fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M9 5l7 7-7 7" />
                                </svg>
                            </div>
                        </div>
                    </a>
                </div>
            </div>

            <!-- DANGER ZONE -->
            <div class="bg-gradient-to-br from-red-50 to-red-50/50 dark:from-red-900/20 dark:to-red-900/10 border border-red-200 dark:border-red-800 rounded-2xl p-6">
                <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                    <div class="flex items-start gap-4">
                        <div class="w-11 h-11 rounded-xl bg-red-100 dark:bg-red-900/40 flex items-center justify-center flex-shrink-0">
                            <svg class="w-5 h-5 text-red-600 dark:text-red-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                            </svg>
                        </div>
                        <div>
                            <h4 class="text-base font-bold text-red-900 dark:text-red-400">Delete business</h4>
                            <p class="text-sm text-red-700 dark:text-red-300 mt-1">
                                Permanently delete this business and all its data. This action cannot be undone.
                            </p>
                        </div>
                    </div>
                    <button @click="showDeleteModal = true"
                        class="inline-flex items-center gap-2 px-6 py-3 bg-gradient-to-r from-red-500 to-red-600 text-white rounded-xl hover:from-red-600 hover:to-red-700 transition-all shadow-lg shadow-red-500/25 font-semibold whitespace-nowrap flex-shrink-0 text-sm">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                        </svg>
                        Delete Business
                    </button>
                </div>
            </div>
        </div>

        <!-- DELETE MODAL -->
        <DeleteBusinessModal :is-open="showDeleteModal" :business="business" @close="showDeleteModal = false" />

        <!-- Sticky mobile actions (Details form) -->
        <StickyFormActions :dirty="formDirty" :can-save="!!form.name" :processing="processing" save-label="Save Changes"
            @save="saveBusiness" @cancel="() => router.visit('/owner/businesses')" />
    </AuthenticatedLayout>
</template>

<script setup>
    import { ref, reactive, computed } from "vue";
    import { router } from "@inertiajs/vue3";
    import { useToast } from "@/composables/useToast";
    import AuthenticatedLayout from "@/Layouts/AuthenticatedLayout.vue";
    import PageHeader from "@/Components/PageHeader.vue";
    import VerifiedBadge from "@/Components/VerifiedBadge.vue";
    import { useUpgradeModal } from "@/composables/useUpgradeModal";
    import BusinessStatusToggle from "@/Components/Owner/BusinessStatusToggle.vue";
    import DeleteBusinessModal from "@/Components/Owner/DeleteBusinessModal.vue";
    import StickyFormActions from '@/Components/Owner/StickyFormActions.vue';
    import CategoryMultiSelect from '@/Components/CategoryMultiSelect.vue';

    const showDeleteModal = ref(false);
    const featuredProcessing = ref(false);

    const { show: showUpgrade } = useUpgradeModal();

    const props = defineProps({
        business: { type: Object, required: true },
        categories: { type: Array, required: true },
    });

    const { error } = useToast();

    // ============ FEATURED TOGGLE ============
    const toggleFeatured = () => {
        if (featuredProcessing.value) return;
        featuredProcessing.value = true;

        router.post(`/owner/businesses/${props.business.id}/toggle-featured`, {}, {
            preserveScroll: true,
            // ✅ No success toast — the controller flashes
            //    'Business is now featured!' / 'Business removed from featured.'
            //    and AuthenticatedLayout shows it once.
            //
            // ✅ No router.reload() — back()->with(...) already re-renders
            //    the current page with fresh props.
            //
            // ⚠️ Also fixes a subtle message-inversion bug: the old toast
            //    read `props.business.is_featured` AFTER the toggle had
            //    already happened on the server, so the "Featured!" vs
            //    "Removed" labels were swapped.
            onError: (errors) => {
                error("Update Failed", errors.error || "Could not update featured status.", { duration: 2000 });
            },
            onFinish: () => {
                featuredProcessing.value = false;
            },
        });
    };

    // ============ TOTAL IMAGES ============
    const totalImages = computed(() => {
        let count = 0;
        if (props.business.logo) count++;
        if (props.business.cover_image) count++;
        if (props.business.gallery_images) count += props.business.gallery_images.length;
        return count;
    });

    // ============ INITIALS ============
    const getInitials = (name) => {
        if (!name) return "?";
        return name.split(" ").map((n) => n[0]).join("").toUpperCase().slice(0, 2);
    };

    // ============ BUSINESS FORM ============
    const processing = ref(false);

    const form = reactive({
        name: props.business?.name || "",
        description: props.business?.description || "",
        email: props.business?.email || "",
        website: props.business?.website || "",
        categories: props.business?.categories ? props.business.categories.map((c) => c.id) : [],
    });

    // Manual dirty tracking
    const initialForm = JSON.stringify({
        name: props.business?.name || "",
        description: props.business?.description || "",
        email: props.business?.email || "",
        website: props.business?.website || "",
        categories: props.business?.categories ? props.business.categories.map((c) => c.id) : [],
    });
    const formDirty = computed(() => JSON.stringify(form) !== initialForm);

    const saveBusiness = () => {
        processing.value = true;
        router.put(`/owner/businesses/${props.business.id}`, form, {
            preserveScroll: true,
            onFinish: () => { processing.value = false; },
            // ✅ No success toast — the controller flashes
            //    'Business details updated successfully.'
            // ✅ No router.reload() — back()->with(...) already
            //    re-renders the page with fresh props.
            onError: (errors) => {
                // Client-side-only: server rejected before any flash.
                const firstError = Object.values(errors)[0];
                error("Update Failed ❌", firstError || "Failed to update business.", { duration: 4000 });
            },
        });
    };

    // ============ SUBMIT FOR REVIEW ============
    const submitBusiness = () => {
        if (confirm(`Submit "${props.business.name}" for review?`)) {
            router.post(`/owner/businesses/${props.business.id}/submit`, {}, {
                preserveScroll: true,
                // ✅ No success toast — the controller flashes
                //    "\"<name>\" has been submitted for review. We'll notify you once it's approved."
                //    and the layout shows it once.
                //
                // ✅ No router.reload() — the controller redirects to
                //    /owner/businesses (index), which Inertia follows.
                onError: () => {
                    // Client-side-only: usually the verified-email gate
                    // or a network error. The `verified` middleware would
                    // return a back() with an error flash — but for
                    // defensive parity with older behavior we keep this
                    // client-side fallback.
                    error("Submission Failed ❌", "Failed to submit business for review.", { duration: 4000 });
                },
            });
        }
    };
</script>