<!-- resources/js/Pages/Admin/Businesses/Show.vue -->
<template>
    <AuthenticatedLayout>
        <PageHeader color="primary" :breadcrumb="[
            { label: 'Admin', href: '/admin/dashboard' },
            { label: 'Businesses', href: '/admin/businesses' },
            { label: business.name }
        ]">
            <template #icon>
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                    d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" />
            </template>
            <template #title>{{ business.name }}</template>
            <template #subtitle>
                <span v-if="business.slug" class="text-xs font-mono text-gray-500">{{ business.slug }}</span>
            </template>
            <template #actions>
                <span :class="statusClass(business.status)">
                    {{ formatStatus(business.status) }}
                </span>
            </template>
        </PageHeader>

        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8 space-y-6">

            <!-- ============ SUBMISSION CONTEXT ============ -->
            <div v-if="business.submitted_at"
                class="bg-gradient-to-br from-amber-50 to-orange-50 border border-amber-200 rounded-2xl p-5">
                <div class="flex items-start gap-4">
                    <div class="w-11 h-11 rounded-xl bg-amber-100 flex items-center justify-center flex-shrink-0">
                        <svg class="w-5 h-5 text-amber-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                    </div>
                    <div class="min-w-0 flex-1">
                        <p class="text-sm font-bold text-amber-800">
                            Submitted for review
                        </p>
                        <p class="text-sm text-amber-700 mt-0.5">
                            <strong>{{ relativeTime(business.submitted_at) }}</strong>
                            <span class="text-amber-600"> · {{ formatDate(business.submitted_at) }}</span>
                        </p>
                        <div class="flex flex-wrap gap-2 mt-2">
                            <span v-if="isNewAccount"
                                class="inline-flex items-center gap-1 px-2 py-0.5 bg-rose-100 text-rose-700 text-[10px] font-bold uppercase tracking-wide rounded-full">
                                ⚠️ New account (&lt; 7 days)
                            </span>
                            <span v-if="isFirstBusiness"
                                class="inline-flex items-center gap-1 px-2 py-0.5 bg-blue-100 text-blue-700 text-[10px] font-bold uppercase tracking-wide rounded-full">
                                First business
                            </span>
                            <span v-if="!ownerIsVerified"
                                class="inline-flex items-center gap-1 px-2 py-0.5 bg-orange-100 text-orange-700 text-[10px] font-bold uppercase tracking-wide rounded-full">
                                Email not verified
                            </span>
                        </div>
                    </div>
                </div>
            </div>

            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                <!-- ============ LEFT: Business details + Owner + Locations ============ -->
                <div class="lg:col-span-2 space-y-6">

                    <!-- BUSINESS OVERVIEW -->
                    <div
                        class="bg-white dark:bg-gray-800 rounded-2xl shadow-sm border border-gray-100 dark:border-gray-700 overflow-hidden">
                        <div
                            class="px-6 py-4 border-b border-gray-100 dark:border-gray-700 bg-gradient-to-br from-gray-50 to-white dark:from-gray-800 dark:to-gray-800/50 flex items-center gap-3">
                            <div
                                class="w-9 h-9 rounded-xl bg-primary-50 dark:bg-primary-900/30 flex items-center justify-center">
                                <svg class="w-4 h-4 text-primary-600 dark:text-primary-400" fill="none"
                                    stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" />
                                </svg>
                            </div>
                            <h3 class="text-sm font-bold text-gray-900 dark:text-white tracking-tight">Business overview
                            </h3>
                        </div>

                        <!-- Logo + Cover thumbnails -->
                        <div v-if="business.logo || business.cover_image"
                            class="px-6 pt-6 grid grid-cols-1 sm:grid-cols-3 gap-3">
                            <div v-if="business.logo"
                                class="rounded-xl overflow-hidden border border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-900/50 aspect-square">
                                <img :src="logoUrl" alt="Logo" class="w-full h-full object-contain p-3" />
                            </div>
                            <div v-if="business.cover_image"
                                class="rounded-xl overflow-hidden border border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-900/50 aspect-square sm:col-span-2">
                                <img :src="coverUrl" alt="Cover" class="w-full h-full object-cover" />
                            </div>
                        </div>

                        <div class="p-6">
                            <dl class="grid grid-cols-1 sm:grid-cols-2 gap-x-6 gap-y-4">
                                <div class="sm:col-span-2">
                                    <dt class="text-[10px] uppercase tracking-widest text-gray-400 font-bold mb-1">
                                        Description
                                    </dt>
                                    <dd
                                        class="text-sm text-gray-700 dark:text-gray-300 whitespace-pre-wrap leading-relaxed">
                                        {{ business.description || 'No description provided.' }}
                                    </dd>
                                </div>

                                <div>
                                    <dt class="text-[10px] uppercase tracking-widest text-gray-400 font-bold mb-1">Email
                                    </dt>
                                    <dd class="text-sm text-gray-900 dark:text-white truncate">
                                        <a v-if="business.email" :href="`mailto:${business.email}`"
                                            class="text-primary-600 hover:underline">
                                            {{ business.email }}
                                        </a>
                                        <span v-else class="text-gray-400 italic">Not set</span>
                                    </dd>
                                </div>

                                <div>
                                    <dt class="text-[10px] uppercase tracking-widest text-gray-400 font-bold mb-1">Phone
                                    </dt>
                                    <dd class="text-sm text-gray-900 dark:text-white truncate">
                                        <a v-if="business.phone" :href="`tel:${business.phone}`"
                                            class="text-primary-600 hover:underline">
                                            {{ business.phone }}
                                        </a>
                                        <span v-else class="text-gray-400 italic">Not set</span>
                                    </dd>
                                </div>

                                <div class="sm:col-span-2">
                                    <dt class="text-[10px] uppercase tracking-widest text-gray-400 font-bold mb-1">
                                        Website</dt>
                                    <dd class="text-sm truncate">
                                        <a v-if="business.website" :href="business.website" target="_blank"
                                            class="text-primary-600 hover:underline inline-flex items-center gap-1">
                                            {{ business.website }}
                                            <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                    d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14" />
                                            </svg>
                                        </a>
                                        <span v-else class="text-gray-400 italic">Not set</span>
                                    </dd>
                                </div>

                                <div>
                                    <dt class="text-[10px] uppercase tracking-widest text-gray-400 font-bold mb-1">
                                        Published
                                    </dt>
                                    <dd class="text-sm text-gray-900 dark:text-white">
                                        {{ business.published_at ? formatDate(business.published_at) : '—' }}
                                    </dd>
                                </div>

                                <div>
                                    <dt class="text-[10px] uppercase tracking-widest text-gray-400 font-bold mb-1">
                                        Public URL
                                    </dt>
                                    <dd class="text-sm truncate">
                                        <a v-if="business.slug" :href="`/business/${business.slug}`" target="_blank"
                                            class="text-primary-600 hover:underline">
                                            /business/{{ business.slug }}
                                        </a>
                                    </dd>
                                </div>
                            </dl>
                        </div>
                    </div>

                    <!-- OWNER PROFILE -->
                    <div v-if="business.owner"
                        class="bg-white dark:bg-gray-800 rounded-2xl shadow-sm border border-gray-100 dark:border-gray-700 overflow-hidden">
                        <div
                            class="px-6 py-4 border-b border-gray-100 dark:border-gray-700 bg-gradient-to-br from-gray-50 to-white dark:from-gray-800 dark:to-gray-800/50 flex items-center justify-between">
                            <div class="flex items-center gap-3">
                                <div
                                    class="w-9 h-9 rounded-xl bg-purple-50 dark:bg-purple-900/30 flex items-center justify-center">
                                    <svg class="w-4 h-4 text-purple-600 dark:text-purple-400" fill="none"
                                        stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                                    </svg>
                                </div>
                                <h3 class="text-sm font-bold text-gray-900 dark:text-white tracking-tight">Owner profile
                                </h3>
                            </div>
                            <a :href="ownerProfileUrl"
                                class="text-xs font-bold text-primary-600 hover:text-primary-800 transition-colors">
                                View full profile →
                            </a>
                        </div>

                        <div class="p-6">
                            <div class="flex items-start gap-4 mb-5">
                                <div
                                    class="w-14 h-14 rounded-2xl bg-gradient-to-br from-primary-500 to-primary-700 flex items-center justify-center text-white text-lg font-bold flex-shrink-0 shadow-md">
                                    {{ getInitials(business.owner.name) }}
                                </div>
                                <div class="min-w-0">
                                    <p
                                        class="text-base font-bold text-gray-900 dark:text-white truncate tracking-tight">
                                        {{ business.owner.name }}
                                    </p>
                                    <div class="flex flex-wrap items-center gap-2 mt-1">
                                        <span
                                            class="inline-flex items-center px-2 py-0.5 text-[10px] font-bold uppercase tracking-wide rounded-full"
                                            :class="roleBadgeClass">
                                            {{ business.owner.role }}
                                        </span>
                                        <span
                                            class="inline-flex items-center px-2 py-0.5 text-[10px] font-bold uppercase tracking-wide rounded-full"
                                            :class="business.owner.status === 'active' ? 'bg-emerald-100 text-emerald-700' : 'bg-amber-100 text-amber-700'">
                                            {{ business.owner.status }}
                                        </span>
                                        <span v-if="ownerIsVerified"
                                            class="inline-flex items-center gap-1 px-2 py-0.5 text-[10px] font-bold uppercase tracking-wide bg-blue-100 text-blue-700 rounded-full">
                                            ✓ Email verified
                                        </span>
                                        <span v-else
                                            class="inline-flex items-center gap-1 px-2 py-0.5 text-[10px] font-bold uppercase tracking-wide bg-orange-100 text-orange-700 rounded-full">
                                            ✗ Email not verified
                                        </span>
                                    </div>
                                </div>
                            </div>

                            <dl class="grid grid-cols-1 sm:grid-cols-2 gap-x-6 gap-y-4">
                                <div>
                                    <dt class="text-[10px] uppercase tracking-widest text-gray-400 font-bold mb-1">Email
                                    </dt>
                                    <dd class="text-sm text-gray-900 dark:text-white truncate">
                                        <a v-if="business.owner.email" :href="`mailto:${business.owner.email}`"
                                            class="text-primary-600 hover:underline">
                                            {{ business.owner.email }}
                                        </a>
                                    </dd>
                                </div>
                                <div>
                                    <dt class="text-[10px] uppercase tracking-widest text-gray-400 font-bold mb-1">Phone
                                    </dt>
                                    <dd class="text-sm text-gray-900 dark:text-white truncate">
                                        <a v-if="business.owner.phone" :href="`tel:${business.owner.phone}`"
                                            class="text-primary-600 hover:underline">
                                            {{ business.owner.phone }}
                                        </a>
                                        <span v-else class="text-gray-400 italic">Not set</span>
                                    </dd>
                                </div>
                                <div>
                                    <dt class="text-[10px] uppercase tracking-widest text-gray-400 font-bold mb-1">
                                        Account
                                        created</dt>
                                    <dd class="text-sm text-gray-900 dark:text-white">
                                        {{ formatDate(business.owner.created_at) }}
                                        <span class="text-xs text-gray-400 ml-1">{{
                                            relativeTime(business.owner.created_at)
                                            }}</span>
                                    </dd>
                                </div>
                                <div>
                                    <dt class="text-[10px] uppercase tracking-widest text-gray-400 font-bold mb-1">Total
                                        businesses</dt>
                                    <dd class="text-sm text-gray-900 dark:text-white">
                                        {{ business.owner.businesses_count || 0 }}
                                    </dd>
                                </div>
                            </dl>
                        </div>
                    </div>

                    <!-- Locations -->
                    <div
                        class="bg-white dark:bg-gray-800 rounded-2xl shadow-sm border border-gray-100 dark:border-gray-700 overflow-hidden">
                        <div
                            class="px-6 py-4 border-b border-gray-100 dark:border-gray-700 bg-gradient-to-br from-gray-50 to-white dark:from-gray-800 dark:to-gray-800/50 flex items-center justify-between">
                            <div class="flex items-center gap-3">
                                <div
                                    class="w-9 h-9 rounded-xl bg-sky-50 dark:bg-sky-900/30 flex items-center justify-center">
                                    <svg class="w-4 h-4 text-sky-600 dark:text-sky-400" fill="none"
                                        stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z" />
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M15 11a3 3 0 11-6 0 3 3 0 016 0z" />
                                    </svg>
                                </div>
                                <div>
                                    <h3 class="text-sm font-bold text-gray-900 dark:text-white tracking-tight">Locations
                                    </h3>
                                    <p class="text-xs text-gray-400">
                                        {{ business.locations?.length || 0 }}
                                        location{{ (business.locations?.length || 0) === 1 ? '' : 's' }}
                                    </p>
                                </div>
                            </div>
                        </div>

                        <div v-if="business.locations && business.locations.length > 0"
                            class="divide-y divide-gray-100 dark:divide-gray-700">
                            <div v-for="location in business.locations" :key="location.id" class="p-6">
                                <div class="flex items-start justify-between gap-3 mb-4">
                                    <div class="flex items-center gap-2 min-w-0">
                                        <h4
                                            class="text-sm font-bold text-gray-900 dark:text-white truncate tracking-tight">
                                            {{ location.name || 'Unnamed Location' }}
                                        </h4>
                                        <span v-if="location.is_primary"
                                            class="inline-flex items-center px-2 py-0.5 text-[10px] font-bold uppercase tracking-wide bg-primary-100 dark:bg-primary-900/30 text-primary-700 dark:text-primary-400 rounded-full">
                                            Primary
                                        </span>
                                    </div>
                                    <span :class="statusClass(location.status)">
                                        {{ formatStatus(location.status) }}
                                    </span>
                                </div>

                                <dl class="grid grid-cols-1 sm:grid-cols-2 gap-x-6 gap-y-4">
                                    <!-- Full address -->
                                    <div class="sm:col-span-2">
                                        <dt class="text-[10px] uppercase tracking-widest text-gray-400 font-bold mb-1">
                                            Full
                                            address</dt>
                                        <dd class="text-sm text-gray-900 dark:text-white">
                                            <template
                                                v-if="location.address || location.city?.name || location.region?.name || location.country?.name">
                                                <p v-if="location.address" class="font-medium">{{ location.address }}</p>
                                                <p class="text-gray-600 dark:text-gray-400">
                                                    {{ [location.city?.name, location.region?.name,
                                                    location.country?.name].filter(Boolean).join(', ') }}
                                                </p>
                                            </template>
                                            <span v-else class="text-gray-400 italic">No address provided</span>
                                        </dd>
                                    </div>

                                    <!-- Coordinates -->
                                    <div v-if="location.latitude && location.longitude" class="sm:col-span-2">
                                        <dt class="text-[10px] uppercase tracking-widest text-gray-400 font-bold mb-1">
                                            GPS
                                            coordinates</dt>
                                        <dd class="text-sm flex items-center gap-3 flex-wrap">
                                            <span class="font-mono text-gray-900 dark:text-white">
                                                {{ Number(location.latitude).toFixed(5) }}, {{
                                                Number(location.longitude).toFixed(5)
                                                }}
                                            </span>
                                            <a :href="`https://www.google.com/maps/dir/?api=1&destination=${location.latitude},${location.longitude}`"
                                                target="_blank" rel="noopener"
                                                class="inline-flex items-center gap-1 text-xs font-bold text-primary-600 hover:text-primary-800">
                                                Open in Maps
                                                <svg class="w-3 h-3" fill="none" stroke="currentColor"
                                                    viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round"
                                                        stroke-width="2"
                                                        d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14" />
                                                </svg>
                                            </a>
                                        </dd>
                                    </div>

                                    <!-- Location phone -->
                                    <div v-if="location.phone">
                                        <dt class="text-[10px] uppercase tracking-widest text-gray-400 font-bold mb-1">
                                                                                        Location
                                            phone</dt>
                                        <dd class="text-sm text-gray-900 dark:text-white">
                                            <a :href="`tel:${location.phone}`" class="text-primary-600 hover:underline">
                                                {{ location.phone }}
                                            </a>
                                        </dd>
                                    </div>

                                    <!-- Location WhatsApp -->
                                    <div v-if="location.whatsapp">
                                        <dt class="text-[10px] uppercase tracking-widest text-gray-400 font-bold mb-1">
                                                                                        Location
                                            WhatsApp</dt>
                                        <dd class="text-sm text-gray-900 dark:text-white">
                                            <a :href="`https://wa.me/${String(location.whatsapp).replace(/[^0-9]/g, '')}`"
                                                target="_blank" rel="noopener" class="text-emerald-600 hover:underline">
                                                {{ location.whatsapp }}
                                            </a>
                                        </dd>
                                    </div>
                                </dl>
                            </div>
                        </div>

                        <div v-else class="p-12 text-center">
                            <p class="text-sm font-semibold text-gray-500 dark:text-gray-400">No Locations yet</p>
                            <p class="text-xs text-gray-400 mt-1">The owner hasn't added any locations</p>
                        </div>
                    </div>
                </div>

                <!-- ============ RIGHT: Actions + Categories ============ -->
                <div class="lg:col-span-1 space-y-6">

                    <!-- ACTIONS -->
                    <div
                        class="bg-white dark:bg-gray-800 rounded-2xl shadow-sm border border-gray-100 dark:border-gray-700 overflow-hidden sticky top-6">
                        <div
                            class="px-6 py-4 border-b border-gray-100 dark:border-gray-700 bg-gradient-to-br from-gray-50 to-white dark:from-gray-800 dark:to-gray-800/50">
                            <h3 class="text-sm font-bold text-gray-900 dark:text-white tracking-tight">Actions</h3>
                        </div>

                        <div class="p-6 space-y-3">
                            <!-- Approve -->
                            <button v-if="business.status === 'submitted'" @click="approveBusiness"
                                class="w-full inline-flex items-center justify-center gap-2 px-4 py-3 bg-gradient-to-r from-emerald-500 to-emerald-600 text-white rounded-xl font-semibold text-sm hover:from-emerald-600 hover:to-emerald-700 transition-all shadow-lg shadow-emerald-500/25 hover:-translate-y-0.5">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5"
                                        d="M5 13l4 4L19 7" />
                                </svg>
                                Approve Business
                            </button>

                            <!-- Reject (only when submitted) -->
                            <button v-if="business.status === 'submitted'" @click="openRejectModal"
                                class="w-full inline-flex items-center justify-center gap-2 px-4 py-3 bg-gradient-to-r from-rose-500 to-red-600 text-white rounded-xl font-semibold text-sm hover:from-rose-600 hover:to-red-700 transition-all shadow-lg shadow-rose-500/25 hover:-translate-y-0.5">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5"
                                        d="M6 18L18 6M6 6l12 12" />
                                </svg>
                                Reject Business
                            </button>

                            <!-- Publish -->
                            <button v-if="business.status === 'approved'" @click="publishBusiness"
                                class="w-full inline-flex items-center justify-center gap-2 px-4 py-3 bg-gradient-to-r from-primary-600 to-primary-700 text-white rounded-xl font-semibold text-sm hover:from-primary-700 hover:to-primary-800 transition-all shadow-lg shadow-primary-500/25 hover:-translate-y-0.5">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M11 5.882V19.24a1.76 1.76 0 01-3.417.592l-2.147-6.15M18 13a3 3 0 100-6M5.436 13.683A4.001 4.001 0 017 6h1.832c4.1 0 7.625-1.234 9.168-3v14c-1.543-1.766-5.067-3-9.168-3H7a3.988 3.988 0 01-1.564-.317z" />
                                </svg>
                                Publish Business
                            </button>

                            <!-- Suspend -->
                            <button v-if="business.status === 'published'" @click="suspendBusiness"
                                class="w-full inline-flex items-center justify-center gap-2 px-4 py-3 bg-gradient-to-r from-amber-500 to-orange-500 text-white rounded-xl font-semibold text-sm hover:from-amber-600 hover:to-orange-600 transition-all shadow-lg shadow-amber-500/25 hover:-translate-y-0.5">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728A9 9 0 015.636 5.636m12.728 12.728L5.636 5.636" />
                                </svg>
                                Suspend Business
                            </button>

                            <!-- Activate -->
                            <button v-if="business.status === 'suspended'" @click="activateBusiness"
                                class="w-full inline-flex items-center justify-center gap-2 px-4 py-3 bg-gradient-to-r from-emerald-500 to-emerald-600 text-white rounded-xl font-semibold text-sm hover:from-emerald-600 hover:to-emerald-700 transition-all shadow-lg shadow-emerald-500/25 hover:-translate-y-0.5">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                                </svg>
                                Activate Business
                            </button>

                            <!-- View public profile -->
                            <a v-if="business.slug" :href="`/business/${business.slug}`" target="_blank"
                                class="w-full inline-flex items-center justify-center gap-2 px-4 py-3 border border-gray-300 dark:border-gray-600 text-gray-700 dark:text-gray-300 rounded-xl font-semibold text-sm hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14" />
                                </svg>
                                View Public Profile
                            </a>

                            <!-- Danger zone -->
                            <div class="pt-4 mt-4 border-t border-gray-100 dark:border-gray-700">
                                <p class="text-[10px] uppercase tracking-widest text-red-500 font-bold mb-2">Danger zone
                                </p>
                                <button @click="deleteBusiness"
                                    class="w-full inline-flex items-center justify-center gap-2 px-4 py-2.5 border border-red-200 dark:border-red-800 text-red-600 dark:text-red-400 rounded-xl font-semibold text-sm hover:bg-red-50 dark:hover:bg-red-900/20 transition-colors">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                    </svg>
                                    Delete (soft)
                                </button>
                                <p class="text-[10px] text-gray-400 mt-1.5 text-center">
                                    Can be restored from the deleted list
                                </p>
                            </div>
                        </div>
                    </div>

                    <!-- CATEGORIES -->
                    <div
                        class="bg-white dark:bg-gray-800 rounded-2xl shadow-sm border border-gray-100 dark:border-gray-700 overflow-hidden">
                        <div
                            class="px-6 py-4 border-b border-gray-100 dark:border-gray-700 bg-gradient-to-br from-gray-50 to-white dark:from-gray-800 dark:to-gray-800/50">
                            <h3 class="text-sm font-bold text-gray-900 dark:text-white tracking-tight">Categories</h3>
                        </div>
                        <div class="p-6">
                            <div v-if="business.categories && business.categories.length > 0"
                                class="flex flex-wrap gap-2">
                                <span v-for="category in business.categories" :key="category.id"
                                    class="inline-flex items-center gap-1.5 px-2.5 py-1 bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-300 text-xs font-medium rounded-lg">
                                    <span>{{ category.icon || '📁' }}</span>
                                    {{ category.name }}
                                </span>
                            </div>
                            <p v-else class="text-sm text-gray-400 italic">No categories assigned</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- REJECT MODAL -->
        <RejectBusinessModal :is-open="rejectModalOpen" :business="business" @close="rejectModalOpen = false"
            @confirm="handleRejectConfirm" />
    </AuthenticatedLayout>
</template>

<script setup>
    import { ref, computed } from 'vue';
    import { router } from '@inertiajs/vue3';
    import { useConfirm } from '@/composables/useConfirm';
    import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
    import PageHeader from '@/Components/PageHeader.vue';
    import RejectBusinessModal from '@/Components/Admin/RejectBusinessModal.vue';
    import { useStatusBadge } from '@/composables/useStatusBadge';

    const props = defineProps({
        business: Object,
    });

    const { business: statusClass } = useStatusBadge();
    const { confirm: confirmDialog } = useConfirm();

    // ============== HELPERS ==============
    const formatDate = (date) => {
        if (!date) return 'N/A';
        return new Date(date).toLocaleDateString('en-US', {
            year: 'numeric',
            month: 'short',
            day: 'numeric',
        });
    };

    const relativeTime = (date) => {
        if (!date) return '';
        const diff = Math.floor((Date.now() - new Date(date)) / 1000);
        if (diff < 60) return 'just now';
        if (diff < 3600) return `${Math.floor(diff / 60)} minute${Math.floor(diff / 60) === 1 ? '' : 's'} ago`;
        if (diff < 86400) return `${Math.floor(diff / 3600)} hour${Math.floor(diff / 3600) === 1 ? '' : 's'} ago`;
        if (diff < 604800) return `${Math.floor(diff / 86400)} day${Math.floor(diff / 86400) === 1 ? '' : 's'} ago`;
        return formatDate(date);
    };

    const getInitials = (name) => {
        if (!name) return '?';
        return name.split(' ').map((n) => n[0]).join('').toUpperCase().slice(0, 2);
    };

    const formatStatus = (status) => {
        if (!status) return '';
        return status.replace(/_/g, ' ').replace(/\b\w/g, (l) => l.toUpperCase());
    };

    // ============== COMPUTED ==============
    const logoUrl = computed(() => {
        if (props.business.logo) {
            // logo is a string path on the Business model
            if (typeof props.business.logo === 'string') {
                if (props.business.logo.startsWith('http')) return props.business.logo;
                return '/storage/' + props.business.logo;
            }
            if (props.business.logo.path) {
                return '/storage/' + props.business.logo.path;
            }
        }
        return null;
    });

    const coverUrl = computed(() => {
        if (props.business.cover_image) {
            if (typeof props.business.cover_image === 'string') {
                if (props.business.cover_image.startsWith('http')) return props.business.cover_image;
                return '/storage/' + props.business.cover_image;
            }
            if (props.business.cover_image.path) {
                return '/storage/' + props.business.cover_image.path;
            }
        }
        return null;
    });

    const ownerIsVerified = computed(() => !!props.business.owner?.email_verified_at);

    const isNewAccount = computed(() => {
        if (!props.business.owner?.created_at) return false;
        const days = (Date.now() - new Date(props.business.owner.created_at)) / (1000 * 60 * 60 * 24);
        return days < 7;
    });

    const isFirstBusiness = computed(() => {
        return (props.business.owner?.businesses_count || 0) <= 1;
    });

    const ownerProfileUrl = computed(() => {
        const owner = props.business.owner;
        if (!owner) return '#';
        return owner.role === 'owner'
            ? `/admin/owners/${owner.id}`
            : `/admin/users/${owner.id}`;
    });

    const roleBadgeClass = computed(() => {
        const role = props.business.owner?.role;
        const classes = {
            super_admin: 'bg-purple-100 text-purple-700',
            admin: 'bg-blue-100 text-blue-700',
            owner: 'bg-emerald-100 text-emerald-700',
            user: 'bg-gray-100 text-gray-700',
        };
        return classes[role] || classes.user;
    });

    // ============== ACTIONS ==============
    const approveBusiness = async () => {
        const confirmed = await confirmDialog({
            title: 'Approve business?',
            message: `"${props.business.name}" will be marked as approved. The owner will be notified and can then get a subscription to publish.`,
            confirmText: 'Approve',
            cancelText: 'Cancel',
            variant: 'primary',
        });

        if (!confirmed) return;

        router.post(`/admin/businesses/${props.business.id}/approve`, {}, {
            preserveScroll: true,
            // ✅ No success toast — controller flashes
        });
    };

    const publishBusiness = async () => {
        const confirmed = await confirmDialog({
            title: 'Publish business?',
            message: `"${props.business.name}" will go live immediately and be visible in the public directory.`,
            confirmText: 'Publish',
            cancelText: 'Cancel',
            variant: 'primary',
        });

        if (!confirmed) return;

        router.post(`/admin/businesses/${props.business.id}/publish`, {}, {
            preserveScroll: true,
        });
    };

    const suspendBusiness = async () => {
        const confirmed = await confirmDialog({
            title: 'Suspend business?',
            message: `"${props.business.name}" will be hidden from the public until reactivated.`,
            confirmText: 'Suspend',
            cancelText: 'Cancel',
            variant: 'warning',
        });

        if (!confirmed) return;

        router.post(`/admin/businesses/${props.business.id}/suspend`, {}, {
            preserveScroll: true,
        });
    };

    const activateBusiness = async () => {
        const confirmed = await confirmDialog({
            title: 'Activate business?',
            message: `"${props.business.name}" will be reactivated and shown as published again. The owner will be notified.`,
            confirmText: 'Activate',
            cancelText: 'Cancel',
            variant: 'primary',
        });

        if (!confirmed) return;

        router.post(`/admin/businesses/${props.business.id}/activate`, {}, {
            preserveScroll: true,
        });
    };

    const deleteBusiness = async () => {
        const confirmed = await confirmDialog({
            title: 'Delete business?',
            message: `"${props.business.name}" will be moved to the deleted list. You can restore it later from Admin → Businesses → Show deleted.`,
            confirmText: 'Delete',
            cancelText: 'Cancel',
            variant: 'danger',
        });

        if (!confirmed) return;

        router.delete(`/admin/businesses/${props.business.id}`, {
            preserveScroll: true,
        });
    };

    // ============== REJECT FLOW ==============
    const rejectModalOpen = ref(false);

    const openRejectModal = () => {
        rejectModalOpen.value = true;
    };

    const handleRejectConfirm = ({ reason }) => {
        router.post(`/admin/businesses/${props.business.id}/reject`,
            { reason },
            {
                preserveScroll: true,
                onSuccess: () => {
                    rejectModalOpen.value = false;
                },
                onError: () => {
                    rejectModalOpen.value = false;
                },
            }
        );
    };
</script>