<!-- resources/js/Pages/Owner/Coupons/Edit.vue -->
<template>
    <AuthenticatedLayout>
        <!-- COMPACT HEADER -->
        <PageHeader color="purple" :breadcrumb="[
            { label: 'Dashboard', href: '/owner/dashboard' },
            { label: 'Coupons', href: '/owner/coupons' },
            { label: 'Edit' }
        ]">
            <template #icon>
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                    d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
            </template>
            <template #title>Edit {{ coupon.title }}</template>
            <template #subtitle>Update your coupon details</template>
            <template #actions>
                <span :class="statusClassHero(status)">
                    {{ status.replace('_', ' ') }}
                </span>
                <a href="/owner/coupons"
                    class="inline-flex items-center gap-2 px-4 py-2 border border-gray-300 dark:border-gray-600 text-gray-700 dark:text-gray-300 rounded-xl hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors text-sm font-semibold">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M10 19l-7-7m0 0l7-7m-7 7h18" />
                    </svg>
                    Back
                </a>
            </template>
        </PageHeader>

        <div class="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
            <form @submit.prevent="submit" class="space-y-6">

                <!-- BASIC INFORMATION -->
                <div
                    class="bg-white dark:bg-gray-800 rounded-2xl border border-gray-100 dark:border-gray-700 overflow-hidden">
                    <div
                        class="px-6 py-4 border-b border-gray-100 dark:border-gray-700 bg-gradient-to-br from-gray-50 to-white dark:from-gray-800 dark:to-gray-800/50 flex items-center gap-3">
                        <div
                            class="w-9 h-9 rounded-xl bg-primary-50 dark:bg-primary-900/30 flex items-center justify-center">
                            <svg class="w-4 h-4 text-primary-600 dark:text-primary-400" fill="none"
                                stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                        </div>
                        <div>
                            <h2 class="text-sm font-bold text-gray-900 dark:text-white tracking-tight">Basic information
                            </h2>
                            <p class="text-xs text-gray-400">Tell customers about your offer</p>
                        </div>
                    </div>

                    <div class="p-6 space-y-5">
                        <div>
                            <label
                                class="block text-xs font-bold text-gray-500 dark:text-gray-400 uppercase tracking-widest mb-2">
                                Business
                            </label>
                            <div
                                class="px-4 py-2.5 bg-gray-50 dark:bg-gray-900/50 border border-gray-200 dark:border-gray-700 rounded-xl text-sm text-gray-700 dark:text-gray-300 font-medium">
                                {{ coupon.business?.name || 'N/A' }}
                            </div>
                        </div>

                        <div>
                            <label
                                class="block text-xs font-bold text-gray-500 dark:text-gray-400 uppercase tracking-widest mb-2">
                                Offer title <span class="text-red-500">*</span>
                            </label>
                            <input v-model="form.title" type="text" maxlength="100"
                                class="w-full px-4 py-2.5 border border-gray-200 dark:border-gray-600 rounded-xl bg-white dark:bg-gray-900 text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-primary-500 focus:border-transparent transition-colors text-sm"
                                required />
                            <div class="flex items-center justify-between mt-1.5">
                                <p v-if="errors.title" class="text-xs text-red-500 font-medium">{{ errors.title }}</p>
                                <p class="text-xs text-gray-400 ml-auto">{{ form.title.length }}/100</p>
                            </div>
                        </div>

                        <div>
                            <label
                                class="block text-xs font-bold text-gray-500 dark:text-gray-400 uppercase tracking-widest mb-2">
                                Description <span class="text-gray-400 text-[10px] normal-case">(optional)</span>
                            </label>
                            <textarea v-model="form.description" rows="3" maxlength="500"
                                class="w-full px-4 py-2.5 border border-gray-200 dark:border-gray-600 rounded-xl bg-white dark:bg-gray-900 text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-primary-500 focus:border-transparent transition-colors resize-none text-sm"></textarea>
                            <div class="flex items-center justify-between mt-1.5">
                                <p v-if="errors.description" class="text-xs text-red-500 font-medium">{{
                                    errors.description }}
                                </p>
                                <p class="text-xs text-gray-400 ml-auto">{{ form.description.length }}/500</p>
                            </div>
                        </div>

                        <div>
                            <label
                                class="block text-xs font-bold text-gray-500 dark:text-gray-400 uppercase tracking-widest mb-2">
                                Coupon code <span class="text-gray-400 text-[10px] normal-case">(optional)</span>
                            </label>
                            <div class="flex gap-2">
                                <input v-model="form.code" type="text" maxlength="20"
                                    class="flex-1 px-4 py-2.5 border border-gray-200 dark:border-gray-600 rounded-xl bg-white dark:bg-gray-900 text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-primary-500 focus:border-transparent transition-colors uppercase font-mono text-sm"
                                    @input="form.code = form.code.toUpperCase().replace(/[^A-Z0-9]/g, '')" />
                                <button type="button" @click="generateCode"
                                    class="px-4 py-2.5 bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-200 rounded-xl hover:bg-gray-200 dark:hover:bg-gray-600 transition-colors text-xs font-bold uppercase tracking-wide whitespace-nowrap">
                                    Regenerate
                                </button>
                            </div>
                            <p v-if="errors.code" class="mt-1.5 text-xs text-red-500 font-medium">{{ errors.code }}</p>
                        </div>
                    </div>
                </div>

                <!-- DISCOUNT DETAILS -->
                <div
                    class="bg-white dark:bg-gray-800 rounded-2xl border border-gray-100 dark:border-gray-700 overflow-hidden">
                    <div
                        class="px-6 py-4 border-b border-gray-100 dark:border-gray-700 bg-gradient-to-br from-gray-50 to-white dark:from-gray-800 dark:to-gray-800/50 flex items-center gap-3">
                        <div
                            class="w-9 h-9 rounded-xl bg-emerald-50 dark:bg-emerald-900/30 flex items-center justify-center">
                            <svg class="w-4 h-4 text-emerald-600 dark:text-emerald-400" fill="none"
                                stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                        </div>
                        <div>
                            <h2 class="text-sm font-bold text-gray-900 dark:text-white tracking-tight">Discount details
                            </h2>
                            <p class="text-xs text-gray-400">Set the value and terms</p>
                        </div>
                    </div>

                    <div class="p-6 space-y-5">
                        <div>
                            <label
                                class="block text-xs font-bold text-gray-500 dark:text-gray-400 uppercase tracking-widest mb-3">
                                Discount type <span class="text-red-500">*</span>
                            </label>
                            <div class="grid grid-cols-2 gap-3">
                                <button type="button" @click="form.discount_type = 'percentage'"
                                    class="relative p-4 rounded-xl border-2 transition-all text-left"
                                    :class="form.discount_type === 'percentage'
                                        ? 'border-primary-500 bg-primary-50 dark:bg-primary-900/20'
                                        : 'border-gray-200 dark:border-gray-700 hover:border-gray-300 dark:hover:border-gray-600'">
                                    <div class="flex items-center justify-between mb-2">
                                        <span class="text-2xl">📊</span>
                                        <div class="w-5 h-5 rounded-full border-2 flex items-center justify-center"
                                            :class="form.discount_type === 'percentage' ? 'border-primary-500 bg-primary-500' : 'border-gray-300 dark:border-gray-600'">
                                            <svg v-if="form.discount_type === 'percentage'" class="w-3 h-3 text-white"
                                                fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3"
                                                    d="M5 13l4 4L19 7" />
                                            </svg>
                                        </div>
                                    </div>
                                    <p class="font-bold text-gray-900 dark:text-white text-sm tracking-tight">Percentage
                                    </p>
                                    <p class="text-xs text-gray-500 dark:text-gray-400 mt-0.5">e.g., 20% off</p>
                                </button>

                                <button type="button" @click="form.discount_type = 'fixed'"
                                    class="relative p-4 rounded-xl border-2 transition-all text-left"
                                    :class="form.discount_type === 'fixed'
                                        ? 'border-primary-500 bg-primary-50 dark:bg-primary-900/20'
                                        : 'border-gray-200 dark:border-gray-700 hover:border-gray-300 dark:hover:border-gray-600'">
                                    <div class="flex items-center justify-between mb-2">
                                        <span class="text-2xl">💰</span>
                                        <div class="w-5 h-5 rounded-full border-2 flex items-center justify-center"
                                            :class="form.discount_type === 'fixed' ? 'border-primary-500 bg-primary-500' : 'border-gray-300 dark:border-gray-600'">
                                            <svg v-if="form.discount_type === 'fixed'" class="w-3 h-3 text-white"
                                                fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3"
                                                    d="M5 13l4 4L19 7" />
                                            </svg>
                                        </div>
                                    </div>
                                    <p class="font-bold text-gray-900 dark:text-white text-sm tracking-tight">Fixed
                                        amount</p>
                                    <p class="text-xs text-gray-500 dark:text-gray-400 mt-0.5">e.g., 2,000 XAF off</p>
                                </button>
                            </div>
                        </div>

                        <div>
                            <label
                                class="block text-xs font-bold text-gray-500 dark:text-gray-400 uppercase tracking-widest mb-2">
                                Discount value <span class="text-red-500">*</span>
                            </label>
                            <div class="relative">
                                <input v-model.number="form.discount_value" type="number" min="0"
                                    :max="form.discount_type === 'percentage' ? 100 : undefined" step="0.01"
                                    class="w-full px-4 py-2.5 pr-16 border border-gray-200 dark:border-gray-600 rounded-xl bg-white dark:bg-gray-900 text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-primary-500 focus:border-transparent transition-colors text-sm"
                                    required />
                                <div
                                    class="absolute right-4 top-1/2 -translate-y-1/2 text-xs font-bold uppercase tracking-wide text-gray-500 dark:text-gray-400">
                                    {{ form.discount_type === 'percentage' ? '%' : 'XAF' }}
                                </div>
                            </div>
                        </div>

                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <div>
                                <label
                                    class="block text-xs font-bold text-gray-500 dark:text-gray-400 uppercase tracking-widest mb-2">
                                    Min. purchase <span class="text-gray-400 text-[10px] normal-case">(optional)</span>
                                </label>
                                <div class="relative">
                                    <input v-model.number="form.min_purchase" type="number" min="0" step="0.01"
                                        placeholder="0"
                                        class="w-full px-4 py-2.5 pr-16 border border-gray-200 dark:border-gray-600 rounded-xl bg-white dark:bg-gray-900 text-gray-900 dark:text-white placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-primary-500 focus:border-transparent transition-colors text-sm" />
                                    <div
                                        class="absolute right-4 top-1/2 -translate-y-1/2 text-xs font-bold uppercase tracking-wide text-gray-400">
                                        XAF
                                    </div>
                                </div>
                            </div>

                            <div v-if="form.discount_type === 'percentage'">
                                <label
                                    class="block text-xs font-bold text-gray-500 dark:text-gray-400 uppercase tracking-widest mb-2">
                                    Max discount <span class="text-gray-400 text-[10px] normal-case">(optional)</span>
                                </label>
                                <div class="relative">
                                    <input v-model.number="form.max_discount" type="number" min="0" step="0.01"
                                        placeholder="0"
                                        class="w-full px-4 py-2.5 pr-16 border border-gray-200 dark:border-gray-600 rounded-xl bg-white dark:bg-gray-900 text-gray-900 dark:text-white placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-primary-500 focus:border-transparent transition-colors text-sm" />
                                    <div
                                        class="absolute right-4 top-1/2 -translate-y-1/2 text-xs font-bold uppercase tracking-wide text-gray-400">
                                        XAF
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- USAGE LIMITS -->
                <div
                    class="bg-white dark:bg-gray-800 rounded-2xl border border-gray-100 dark:border-gray-700 overflow-hidden">
                    <div
                        class="px-6 py-4 border-b border-gray-100 dark:border-gray-700 bg-gradient-to-br from-gray-50 to-white dark:from-gray-800 dark:to-gray-800/50 flex items-center gap-3">
                        <div class="w-9 h-9 rounded-xl bg-blue-50 dark:bg-blue-900/30 flex items-center justify-center">
                            <svg class="w-4 h-4 text-blue-600 dark:text-blue-400" fill="none" stroke="currentColor"
                                viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M9 12l2 2 4-4M7.835 4.697a3.42 3.42 0 001.946-.806 3.42 3.42 0 014.438 0 3.42 3.42 0 001.946.806 3.42 3.42 0 013.138 3.138 3.42 3.42 0 00.806 1.946 3.42 3.42 0 010 4.438 3.42 3.42 0 00-.806 1.946 3.42 3.42 0 01-3.138 3.138 3.42 3.42 0 00-1.946.806 3.42 3.42 0 01-4.438 0 3.42 3.42 0 00-1.946-.806 3.42 3.42 0 01-3.138-3.138 3.42 3.42 0 00-.806-1.946 3.42 3.42 0 010-4.438 3.42 3.42 0 00.806-1.946 3.42 3.42 0 013.138-3.138z" />
                            </svg>
                        </div>
                        <div>
                            <h2 class="text-sm font-bold text-gray-900 dark:text-white tracking-tight">Usage limits</h2>
                            <p class="text-xs text-gray-400">Control how often the coupon can be used</p>
                        </div>
                    </div>

                    <div class="p-6 space-y-5">
                        <div class="grid grid-cols-2 gap-4 p-4 bg-gray-50 dark:bg-gray-900/50 rounded-xl">
                            <div>
                                <p class="text-[10px] uppercase tracking-widest text-gray-400 font-bold">Used</p>
                                <p class="text-2xl font-bold text-gray-900 dark:text-white tracking-tight mt-0.5">{{
                                    coupon.usage_count }}</p>
                            </div>
                            <div>
                                <p class="text-[10px] uppercase tracking-widest text-gray-400 font-bold">Remaining</p>
                                <p class="text-2xl font-bold text-gray-900 dark:text-white tracking-tight mt-0.5">
                                    {{ coupon.usage_limit ? Math.max(0, coupon.usage_limit - coupon.usage_count) : '∞'
                                    }}
                                </p>
                            </div>
                        </div>

                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <div>
                                <label
                                    class="block text-xs font-bold text-gray-500 dark:text-gray-400 uppercase tracking-widest mb-2">
                                    Total uses <span class="text-gray-400 text-[10px] normal-case">(optional)</span>
                                </label>
                                <input v-model.number="form.usage_limit" type="number" min="1" placeholder="Unlimited"
                                    class="w-full px-4 py-2.5 border border-gray-200 dark:border-gray-600 rounded-xl bg-white dark:bg-gray-900 text-gray-900 dark:text-white placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-primary-500 focus:border-transparent transition-colors text-sm" />
                            </div>

                            <div>
                                <label
                                    class="block text-xs font-bold text-gray-500 dark:text-gray-400 uppercase tracking-widest mb-2">
                                    Per user limit
                                </label>
                                <input v-model.number="form.per_user_limit" type="number" min="1"
                                    class="w-full px-4 py-2.5 border border-gray-200 dark:border-gray-600 rounded-xl bg-white dark:bg-gray-900 text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-primary-500 focus:border-transparent transition-colors text-sm"
                                    required />
                            </div>
                        </div>
                    </div>
                </div>

                <!-- VALIDITY PERIOD -->
                <div
                    class="bg-white dark:bg-gray-800 rounded-2xl border border-gray-100 dark:border-gray-700 overflow-hidden">
                    <div
                        class="px-6 py-4 border-b border-gray-100 dark:border-gray-700 bg-gradient-to-br from-gray-50 to-white dark:from-gray-800 dark:to-gray-800/50 flex items-center gap-3">
                        <div
                            class="w-9 h-9 rounded-xl bg-purple-50 dark:bg-purple-900/30 flex items-center justify-center">
                            <svg class="w-4 h-4 text-purple-600 dark:text-purple-400" fill="none" stroke="currentColor"
                                viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                            </svg>
                        </div>
                        <div>
                            <h2 class="text-sm font-bold text-gray-900 dark:text-white tracking-tight">Validity period
                            </h2>
                            <p class="text-xs text-gray-400">When the coupon is active</p>
                        </div>
                    </div>

                    <div class="p-6 space-y-5">
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <div>
                                <label
                                    class="block text-xs font-bold text-gray-500 dark:text-gray-400 uppercase tracking-widest mb-2">
                                    Start date <span class="text-gray-400 text-[10px] normal-case">(optional)</span>
                                </label>
                                <input v-model="form.starts_at" type="datetime-local"
                                    class="w-full px-4 py-2.5 border border-gray-200 dark:border-gray-600 rounded-xl bg-white dark:bg-gray-900 text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-primary-500 focus:border-transparent transition-colors text-sm" />
                            </div>

                            <div>
                                <label
                                    class="block text-xs font-bold text-gray-500 dark:text-gray-400 uppercase tracking-widest mb-2">
                                    Expiry date <span class="text-gray-400 text-[10px] normal-case">(optional)</span>
                                </label>
                                <input v-model="form.expires_at" type="datetime-local"
                                    class="w-full px-4 py-2.5 border border-gray-200 dark:border-gray-600 rounded-xl bg-white dark:bg-gray-900 text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-primary-500 focus:border-transparent transition-colors text-sm" />
                            </div>
                        </div>

                        <!-- Active toggle -->
                        <div class="flex items-center justify-between p-4 bg-gray-50 dark:bg-gray-900/50 rounded-xl">
                            <div>
                                <p class="text-sm font-bold text-gray-900 dark:text-white">Active</p>
                                <p class="text-xs text-gray-500 dark:text-gray-400">Enable or disable this coupon</p>
                            </div>
                            <button type="button" @click="form.is_active = !form.is_active"
                                class="relative inline-flex items-center h-7 rounded-full w-12 transition-colors focus:outline-none focus:ring-2 focus:ring-primary-500 focus:ring-offset-2"
                                :class="form.is_active ? 'bg-primary-600' : 'bg-gray-300 dark:bg-gray-600'">
                                <span
                                    class="inline-block h-5 w-5 transform rounded-full bg-white transition-transform shadow-sm"
                                    :class="form.is_active ? 'translate-x-6' : 'translate-x-1'" />
                            </button>
                        </div>
                    </div>
                </div>

                <!-- DANGER ZONE -->
                <div
                    class="bg-gradient-to-br from-red-50 to-red-50/50 dark:from-red-950/30 dark:to-red-950/20 border border-red-200 dark:border-red-800 rounded-2xl p-5">
                    <div class="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4">
                        <div class="flex items-start gap-4">
                            <div
                                class="w-11 h-11 rounded-xl bg-red-100 dark:bg-red-900/30 flex items-center justify-center flex-shrink-0">
                                <svg class="w-5 h-5 text-red-600 dark:text-red-400" fill="none" stroke="currentColor"
                                    viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                                </svg>
                            </div>
                            <div>
                                <h3 class="text-sm font-bold text-red-900 dark:text-red-300 tracking-tight">Delete
                                    coupon</h3>
                                <p class="text-xs text-red-700 dark:text-red-400 mt-0.5">
                                    Once deleted, this coupon cannot be recovered.
                                </p>
                            </div>
                        </div>
                        <button type="button" @click="deleteCoupon"
                            class="inline-flex items-center gap-2 px-5 py-2.5 bg-gradient-to-r from-red-500 to-red-600 text-white rounded-xl font-semibold hover:from-red-600 hover:to-red-700 transition-all shadow-lg shadow-red-500/25 hover:-translate-y-0.5 text-sm whitespace-nowrap flex-shrink-0">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                            </svg>
                            Delete Coupon
                        </button>
                    </div>
                </div>

                <!-- ACTIONS -->
                <div class="flex flex-col-reverse sm:flex-row items-center justify-end gap-3">
                    <a href="/owner/coupons"
                        class="w-full sm:w-auto px-6 py-3 text-center text-sm font-semibold text-gray-700 dark:text-gray-300 bg-white dark:bg-gray-800 border border-gray-300 dark:border-gray-600 rounded-xl hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors">
                        Cancel
                    </a>
                    <button type="submit" :disabled="submitting || !form.title || !form.discount_value"
                        class="w-full sm:w-auto inline-flex items-center justify-center gap-2 px-8 py-3 bg-gradient-to-r from-primary-600 to-primary-700 text-white rounded-xl font-semibold hover:from-primary-700 hover:to-primary-800 transition-all shadow-lg shadow-primary-500/25 hover:shadow-primary-500/40 hover:-translate-y-0.5 disabled:opacity-50 disabled:cursor-not-allowed disabled:hover:translate-y-0">
                        <span v-if="submitting" class="flex items-center gap-2">
                            <svg class="animate-spin h-4 w-4" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor"
                                    stroke-width="4">
                                </circle>
                                <path class="opacity-75" fill="currentColor"
                                    d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z">
                                </path>
                            </svg>
                            Saving…
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

        <!-- Sticky mobile actions -->
        <StickyFormActions :dirty="form.isDirty" :can-save="!!form.title && !!form.discount_value"
            :processing="submitting" save-label="Save Changes" @save="submit"
            @cancel="() => router.visit('/owner/coupons')" />

    </AuthenticatedLayout>
</template>

<script setup>
    import { ref, computed } from 'vue';
    import { useForm, usePage, router } from '@inertiajs/vue3';
    import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
    import PageHeader from '@/Components/PageHeader.vue';
    import StickyFormActions from '@/Components/Owner/StickyFormActions.vue';
    import { useConfirm } from '@/composables/useConfirm';
    const props = defineProps({
        coupon: {
            type: Object,
            required: true,
        },
    });

    const page = usePage();
    const errors = computed(() => page.props.errors || {});
    const { confirm: confirmDialog } = useConfirm();


    const formatDateTime = (date) => {
        if (!date) return '';
        const d = new Date(date);
        const pad = (n) => n.toString().padStart(2, '0');
        return `${d.getFullYear()}-${pad(d.getMonth() + 1)}-${pad(d.getDate())}T${pad(d.getHours())}:${pad(d.getMinutes())}`;
    };

    const form = useForm({
        title: props.coupon.title || '',
        description: props.coupon.description || '',
        code: props.coupon.code || '',
        discount_type: props.coupon.discount_type || 'percentage',
        discount_value: props.coupon.discount_value || null,
        min_purchase: props.coupon.min_purchase || null,
        max_discount: props.coupon.max_discount || null,
        usage_limit: props.coupon.usage_limit || null,
        per_user_limit: props.coupon.per_user_limit || 1,
        starts_at: formatDateTime(props.coupon.starts_at),
        expires_at: formatDateTime(props.coupon.expires_at),
        is_active: props.coupon.is_active ?? true,
    });

    const submitting = ref(false);

    const status = computed(() => {
        if (props.coupon.status) return props.coupon.status;
        if (!props.coupon.is_active) return 'inactive';
        if (props.coupon.expires_at && new Date(props.coupon.expires_at) < new Date()) return 'expired';
        if (props.coupon.starts_at && new Date(props.coupon.starts_at) > new Date()) return 'scheduled';
        if (props.coupon.usage_limit && props.coupon.usage_count >= props.coupon.usage_limit) return 'used_up';
        return 'active';
    });

    // Blur-friendly status classes for the hero strip
    const statusClassHero = (s) => {
        const classes = {
            active: 'bg-emerald-500/20 text-emerald-100 border-emerald-400/40',
            expired: 'bg-red-500/20 text-red-100 border-red-400/40',
            scheduled: 'bg-blue-500/20 text-blue-100 border-blue-400/40',
            used_up: 'bg-orange-500/20 text-orange-100 border-orange-400/40',
            inactive: 'bg-gray-500/20 text-gray-100 border-gray-400/40',
        };
        return classes[s] || classes.inactive;
    };

    const generateCode = () => {
        const chars = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';
        let code = '';
        for (let i = 0; i < 8; i++) {
            code += chars.charAt(Math.floor(Math.random() * chars.length));
        }
        form.code = code;
    };

    const submit = () => {
        submitting.value = true;

        form.put(`/owner/coupons/${props.coupon.id}`, {
            onSuccess: () => {
                // Laravel will handle redirect
            },
            onError: () => {
                submitting.value = false;
            },
            onFinish: () => {
                submitting.value = false;
            },
        });
    };

    const deleteCoupon = async () => {
        const confirmed = await confirmDialog({
            title: 'Delete coupon?',
            message: `"${props.coupon.title || 'This coupon'}" will be permanently removed. This action cannot be undone.`,
            confirmText: 'Delete Coupon',
            cancelText: 'Cancel',
            variant: 'danger',
        });

        if (!confirmed) return;

        router.delete(`/owner/coupons/${props.coupon.id}`);
    };
</script>