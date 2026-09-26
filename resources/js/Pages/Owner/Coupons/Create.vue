<!-- resources/js/Pages/Owner/Coupons/Create.vue -->
<template>
    <AuthenticatedLayout>
        <!-- COMPACT HEADER -->
        <PageHeader color="purple" :breadcrumb="[
            { label: 'Dashboard', href: '/owner/dashboard' },
            { label: 'Coupons', href: '/owner/coupons' },
            { label: 'Create' }
        ]">
            <template #icon>
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
            </template>
            <template #title>Create a coupon</template>
            <template #subtitle>Attract more customers with a special offer</template>
            <template #actions>
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
                        <!-- Business selection -->
                        <div v-if="businesses.length > 1">
                            <label
                                class="block text-xs font-bold text-gray-500 dark:text-gray-400 uppercase tracking-widest mb-2">
                                Business <span class="text-red-500">*</span>
                            </label>
                            <select v-model="form.business_id"
                                class="w-full px-4 py-2.5 border border-gray-200 dark:border-gray-600 rounded-xl bg-white dark:bg-gray-900 text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-primary-500 focus:border-transparent transition-colors appearance-none text-sm"
                                required>
                                <option value="">Select a business</option>
                                <option v-for="biz in businesses" :key="biz.id" :value="biz.id">
                                    {{ biz.name }}
                                </option>
                            </select>
                            <p v-if="errors.business_id" class="mt-1.5 text-xs text-red-500 font-medium">{{
                                errors.business_id
                                }}</p>
                        </div>
                        <input v-else type="hidden" v-model="form.business_id" />

                        <!-- Title -->
                        <div>
                            <label
                                class="block text-xs font-bold text-gray-500 dark:text-gray-400 uppercase tracking-widest mb-2">
                                Offer title <span class="text-red-500">*</span>
                            </label>
                            <input v-model="form.title" type="text" maxlength="100"
                                placeholder="e.g., 20% off all services"
                                class="w-full px-4 py-2.5 border border-gray-200 dark:border-gray-600 rounded-xl bg-white dark:bg-gray-900 text-gray-900 dark:text-white placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-primary-500 focus:border-transparent transition-colors text-sm"
                                required />
                            <div class="flex items-center justify-between mt-1.5">
                                <p v-if="errors.title" class="text-xs text-red-500 font-medium">{{ errors.title }}</p>
                                <p class="text-xs text-gray-400 ml-auto">{{ form.title.length }}/100</p>
                            </div>
                        </div>

                        <!-- Description -->
                        <div>
                            <label
                                class="block text-xs font-bold text-gray-500 dark:text-gray-400 uppercase tracking-widest mb-2">
                                Description <span class="text-gray-400 text-[10px] normal-case">(optional)</span>
                            </label>
                            <textarea v-model="form.description" rows="3" maxlength="500"
                                placeholder="Describe the terms, conditions, or details of your offer…"
                                class="w-full px-4 py-2.5 border border-gray-200 dark:border-gray-600 rounded-xl bg-white dark:bg-gray-900 text-gray-900 dark:text-white placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-primary-500 focus:border-transparent transition-colors resize-none text-sm"></textarea>
                            <div class="flex items-center justify-between mt-1.5">
                                <p v-if="errors.description" class="text-xs text-red-500 font-medium">{{
                                    errors.description }}
                                </p>
                                <p class="text-xs text-gray-400 ml-auto">{{ form.description.length }}/500</p>
                            </div>
                        </div>

                        <!-- Coupon code -->
                        <div>
                            <label
                                class="block text-xs font-bold text-gray-500 dark:text-gray-400 uppercase tracking-widest mb-2">
                                Coupon code <span class="text-gray-400 text-[10px] normal-case">(optional)</span>
                            </label>
                            <div class="flex gap-2">
                                <input v-model="form.code" type="text" maxlength="20" placeholder="e.g., SAVE20"
                                    class="flex-1 px-4 py-2.5 border border-gray-200 dark:border-gray-600 rounded-xl bg-white dark:bg-gray-900 text-gray-900 dark:text-white placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-primary-500 focus:border-transparent transition-colors uppercase font-mono text-sm"
                                    @input="form.code = form.code.toUpperCase().replace(/[^A-Z0-9]/g, '')" />
                                <button type="button" @click="generateCode"
                                    class="px-4 py-2.5 bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-200 rounded-xl hover:bg-gray-200 dark:hover:bg-gray-600 transition-colors text-xs font-bold uppercase tracking-wide whitespace-nowrap">
                                    Generate
                                </button>
                            </div>
                            <p class="mt-1.5 text-xs text-gray-400">
                                Leave empty if no code is required
                            </p>
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
                        <!-- Discount type -->
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

                        <!-- Discount value -->
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
                            <p v-if="form.discount_type === 'percentage'" class="mt-1.5 text-xs text-gray-400">
                                Enter a value between 1 and 100
                            </p>
                            <p v-if="errors.discount_value" class="mt-1.5 text-xs text-red-500 font-medium">{{
                                errors.discount_value }}</p>
                        </div>

                        <!-- Min purchase & max discount -->
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
                                <p class="mt-1.5 text-xs text-gray-400">Minimum order amount</p>
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
                                <p class="mt-1.5 text-xs text-gray-400">Cap the max discount amount</p>
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

                    <div class="p-6 grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                            <label
                                class="block text-xs font-bold text-gray-500 dark:text-gray-400 uppercase tracking-widest mb-2">
                                Total uses <span class="text-gray-400 text-[10px] normal-case">(optional)</span>
                            </label>
                            <input v-model.number="form.usage_limit" type="number" min="1" placeholder="Unlimited"
                                class="w-full px-4 py-2.5 border border-gray-200 dark:border-gray-600 rounded-xl bg-white dark:bg-gray-900 text-gray-900 dark:text-white placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-primary-500 focus:border-transparent transition-colors text-sm" />
                            <p class="mt-1.5 text-xs text-gray-400">Leave empty for unlimited uses</p>
                        </div>

                        <div>
                            <label
                                class="block text-xs font-bold text-gray-500 dark:text-gray-400 uppercase tracking-widest mb-2">
                                Per user limit
                            </label>
                            <input v-model.number="form.per_user_limit" type="number" min="1"
                                class="w-full px-4 py-2.5 border border-gray-200 dark:border-gray-600 rounded-xl bg-white dark:bg-gray-900 text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-primary-500 focus:border-transparent transition-colors text-sm"
                                required />
                            <p class="mt-1.5 text-xs text-gray-400">How many times one customer can use it</p>
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
                                <p class="mt-1.5 text-xs text-gray-400">Leave empty to start immediately</p>
                            </div>

                            <div>
                                <label
                                    class="block text-xs font-bold text-gray-500 dark:text-gray-400 uppercase tracking-widest mb-2">
                                    Expiry date <span class="text-gray-400 text-[10px] normal-case">(optional)</span>
                                </label>
                                <input v-model="form.expires_at" type="datetime-local"
                                    class="w-full px-4 py-2.5 border border-gray-200 dark:border-gray-600 rounded-xl bg-white dark:bg-gray-900 text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-primary-500 focus:border-transparent transition-colors text-sm" />
                                <p class="mt-1.5 text-xs text-gray-400">Leave empty for no expiration</p>
                            </div>
                        </div>

                        <!-- Quick duration -->
                        <div>
                            <label
                                class="block text-xs font-bold text-gray-500 dark:text-gray-400 uppercase tracking-widest mb-3">
                                Quick duration
                            </label>
                            <div class="flex flex-wrap gap-2">
                                <button v-for="duration in quickDurations" :key="duration.value" type="button"
                                    @click="applyQuickDuration(duration.value)"
                                    class="px-3.5 py-1.5 text-xs font-semibold bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-300 rounded-full hover:bg-primary-100 dark:hover:bg-primary-900/30 hover:text-primary-700 dark:hover:text-primary-300 transition-colors">
                                    {{ duration.label }}
                                </button>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- LIVE PREVIEW -->
                <div
                    class="bg-gradient-to-br from-primary-600 via-primary-700 to-purple-700 rounded-2xl shadow-lg p-6 text-white">
                    <div class="flex items-center gap-3 mb-4">
                        <div class="w-8 h-8 rounded-xl bg-white/10 backdrop-blur-sm flex items-center justify-center">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                            </svg>
                        </div>
                        <h3 class="font-bold tracking-tight">Live preview</h3>
                    </div>

                    <div class="bg-white/10 backdrop-blur-sm rounded-xl p-5 border border-white/20">
                        <div class="text-4xl font-black leading-none mb-2">
                            {{ form.discount_value ? (form.discount_type === 'percentage' ? form.discount_value + '%' :
                                formatPrice(form.discount_value) + ' XAF') : '0' }}
                            <span class="text-base font-semibold text-white/70 ml-1">OFF</span>
                        </div>
                        <h4 class="font-bold text-lg tracking-tight">
                            {{ form.title || 'Your offer title' }}
                        </h4>
                        <p class="text-white/70 text-sm mt-1.5 leading-relaxed">
                            {{ form.description || 'Your offer description will appear here' }}
                        </p>

                        <div
                            class="flex flex-wrap items-center gap-3 text-xs text-white/80 pt-4 mt-4 border-t border-white/20">
                            <span v-if="form.code"
                                class="inline-flex items-center gap-1.5 px-2.5 py-1 bg-white/20 rounded-md font-mono font-bold">
                                <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z" />
                                </svg>
                                {{ form.code }}
                            </span>
                            <span v-if="form.expires_at" class="inline-flex items-center gap-1.5">
                                <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                                </svg>
                                Ends {{ formatDate(form.expires_at) }}
                            </span>
                            <span v-if="form.min_purchase" class="inline-flex items-center gap-1.5">
                                Min: {{ formatPrice(form.min_purchase) }} XAF
                            </span>
                        </div>
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
                            Creating…
                        </span>
                        <span v-else class="flex items-center gap-2">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M5 13l4 4L19 7" />
                            </svg>
                            Create Coupon
                        </span>
                    </button>
                </div>
            </form>
        </div>

        <!-- Sticky mobile actions -->
        <StickyFormActions :dirty="form.isDirty" :can-save="!!form.title && !!form.discount_value"
            :processing="submitting" save-label="Create Coupon" @save="submit"
            @cancel="() => router.visit('/owner/coupons')" />
    </AuthenticatedLayout>
</template>

<script setup>
    import { ref, computed } from 'vue';
    import { useForm, usePage, router } from '@inertiajs/vue3';
    import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
    // import Breadcrumb from '@/Components/Breadcrumb.vue';
    import PageHeader from '@/Components/PageHeader.vue';
    import StickyFormActions from '@/Components/Owner/StickyFormActions.vue';

    const props = defineProps({
        businesses: {
            type: Array,
            required: true,
        },
    });

    const page = usePage();
    const errors = computed(() => page.props.errors || {});

    const form = useForm({
        business_id: props.businesses[0]?.id || '',
        title: '',
        description: '',
        code: '',
        discount_type: 'percentage',
        discount_value: null,
        min_purchase: null,
        max_discount: null,
        usage_limit: null,
        per_user_limit: 1,
        starts_at: '',
        expires_at: '',
    });

    const submitting = ref(false);

    const quickDurations = [
        { label: '1 week', value: 7 },
        { label: '2 weeks', value: 14 },
        { label: '1 month', value: 30 },
        { label: '3 months', value: 90 },
        { label: '6 months', value: 180 },
        { label: '1 year', value: 365 },
    ];

    const formatPrice = (price) => {
        if (!price) return '0';
        return new Intl.NumberFormat('en-US').format(price);
    };

    const formatDate = (date) => {
        if (!date) return '';
        return new Date(date).toLocaleDateString('en-US', {
            month: 'short',
            day: 'numeric',
            year: 'numeric',
        });
    };

    const generateCode = () => {
        const chars = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';
        let code = '';
        for (let i = 0; i < 8; i++) {
            code += chars.charAt(Math.floor(Math.random() * chars.length));
        }
        form.code = code;
    };

    const applyQuickDuration = (days) => {
        const now = new Date();
        const expiry = new Date();
        expiry.setDate(now.getDate() + days);

        const formatDateTime = (date) => {
            const pad = (n) => n.toString().padStart(2, '0');
            return `${date.getFullYear()}-${pad(date.getMonth() + 1)}-${pad(date.getDate())}T${pad(date.getHours())}:${pad(date.getMinutes())}`;
        };

        form.starts_at = formatDateTime(now);
        form.expires_at = formatDateTime(expiry);
    };

    const submit = () => {
        submitting.value = true;

        form.post('/owner/coupons', {
            onSuccess: () => {
                // Laravel will handle the redirect
            },
            onError: () => {
                submitting.value = false;
            },
            onFinish: () => {
                submitting.value = false;
            },
        });
    };
</script>

<style scoped>

    input[type="number"]::-webkit-outer-spin-button,
    input[type="number"]::-webkit-inner-spin-button {
        -webkit-appearance: none;
        margin: 0;
    }

    input[type="number"] {
        -moz-appearance: textfield;
    }
</style>