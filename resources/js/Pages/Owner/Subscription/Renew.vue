<!-- resources/js/Pages/Owner/Subscription/Renew.vue -->
<template>
    <AuthenticatedLayout>
        <!-- COMPACT HEADER -->
        <PageHeader color="primary" :breadcrumb="[
            { label: 'Dashboard', href: '/owner/dashboard' },
            { label: 'Subscription', href: '/owner/subscription' },
            { label: 'Renew' }
        ]">
            <template #icon>
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                    d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z" />
            </template>
            <template #title>{{ hasSubscription ? 'Manage your plan' : 'Get started with a plan' }}</template>
            <template #subtitle>{{ business?.name }}</template>
            <template #actions>
                <span
                    v-if="currentSubscription"
                    class="inline-flex items-center gap-2 px-3 py-2 bg-primary-50 dark:bg-primary-900/30 border border-primary-200 dark:border-primary-800 rounded-xl text-sm font-semibold text-primary-700 dark:text-primary-400">
                    <span class="text-xs font-bold uppercase tracking-wide text-primary-600/70 dark:text-primary-400/70">Current:</span>
                    {{ currentSubscription.plan?.name }}
                    <span v-if="currentSubscription.days_remaining !== null" class="text-xs text-primary-600/70 dark:text-primary-400/70">
                        · {{ currentSubscription.days_remaining }}d
                    </span>
                </span>
            </template>
        </PageHeader>

        <div class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8 py-8 pb-32 lg:pb-8">
            <!-- No-subscription banner -->
            <div v-if="!hasSubscription" class="bg-blue-50 dark:bg-blue-900/20 border border-blue-200 dark:border-blue-800 rounded-2xl p-5 mb-6 flex items-start gap-3">
                <span class="text-2xl">🚀</span>
                <div>
                    <h3 class="text-sm font-semibold text-blue-800 dark:text-blue-300">First time subscribing</h3>
                    <p class="text-sm text-blue-700 dark:text-blue-400 mt-0.5">Pick a plan below to get started. You can change plans anytime.</p>
                </div>
            </div>

            <!-- Pending subscription banner -->
            <div v-else-if="currentSubscription && currentSubscription.status === 'pending'"
                 class="bg-yellow-50 dark:bg-yellow-900/20 border border-yellow-200 dark:border-yellow-800 rounded-2xl p-5 mb-6 flex items-start gap-3">
                <span class="text-2xl">⏳</span>
                <div>
                    <h3 class="text-sm font-semibold text-yellow-800 dark:text-yellow-300">Pending subscription</h3>
                    <p class="text-sm text-yellow-700 dark:text-yellow-400 mt-0.5">
                        You have a pending subscription. Complete payment to activate it.
                    </p>
                    <p v-if="currentSubscription.failure_reason" class="text-xs text-red-600 dark:text-red-400 mt-1">
                        ⚠️ {{ currentSubscription.failure_reason }}
                    </p>
                </div>
            </div>

            <!-- Two-column grid: plan picker + sticky summary -->
            <div class="grid grid-cols-1 lg:grid-cols-[1fr_360px] gap-8">

                <!-- ============ LEFT: Action type + plan picker ============ -->
                <div class="space-y-6">

                    <!-- Action type toggle -->
                    <div class="bg-white dark:bg-gray-800 rounded-2xl border border-gray-100 dark:border-gray-700 shadow-sm p-2">
                        <div class="grid grid-cols-2 gap-2">
                            <template v-if="!hasSubscription">
                                <button type="button"
                                        @click="actionType = 'new'"
                                        class="px-4 py-3 rounded-xl border-2 transition-all duration-200 text-center"
                                        :class="actionType === 'new'
                                            ? 'border-primary-500 bg-primary-50 dark:bg-primary-900/30 shadow-sm'
                                            : 'border-transparent hover:bg-gray-50 dark:hover:bg-gray-700/50'">
                                    <div class="flex items-center justify-center gap-2">
                                        <span class="text-lg">🚀</span>
                                        <span class="text-sm font-semibold text-gray-900 dark:text-white">Get Subscription</span>
                                    </div>
                                </button>
                            </template>

                            <template v-else>
                                <button type="button"
                                        @click="actionType = 'renew'"
                                        class="px-4 py-3 rounded-xl border-2 transition-all duration-200 text-center"
                                        :class="actionType === 'renew'
                                            ? 'border-primary-500 bg-primary-50 dark:bg-primary-900/30 shadow-sm'
                                            : 'border-transparent hover:bg-gray-50 dark:hover:bg-gray-700/50'">
                                    <div class="flex items-center justify-center gap-2">
                                        <span class="text-lg">🔄</span>
                                        <span class="text-sm font-semibold text-gray-900 dark:text-white">Renew</span>
                                    </div>
                                </button>
                                <button type="button"
                                        @click="actionType = 'upgrade'"
                                        class="px-4 py-3 rounded-xl border-2 transition-all duration-200 text-center"
                                        :class="actionType === 'upgrade'
                                            ? 'border-primary-500 bg-primary-50 dark:bg-primary-900/30 shadow-sm'
                                            : 'border-transparent hover:bg-gray-50 dark:hover:bg-gray-700/50'">
                                    <div class="flex items-center justify-center gap-2">
                                        <span class="text-lg">🔀</span>
                                        <span class="text-sm font-semibold text-gray-900 dark:text-white">Change plan</span>
                                    </div>
                                </button>
                            </template>
                        </div>
                    </div>

                    <!-- Plan picker heading -->
                    <div class="flex items-center justify-between">
                        <h2 class="text-lg font-bold text-gray-900 dark:text-white tracking-tight">
                            {{ actionType === 'new' ? 'Choose your plan' : actionType === 'renew' ? 'Renew your plan' : 'Choose your new plan' }}
                        </h2>
                        <span class="text-xs text-gray-400 dark:text-gray-500">{{ filteredPlans.length }} plan{{ filteredPlans.length !== 1 ? 's' : '' }}</span>
                    </div>

                    <!-- Plan cards -->
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                        <div v-for="plan in filteredPlans"
                             :key="plan.id"
                             @click="setBillingType(plan.id, billingType)"
                             class="group relative bg-white dark:bg-gray-800 rounded-2xl border-2 p-6 transition-all duration-300 cursor-pointer hover:-translate-y-1"
                             :class="selectedPlanId === plan.id
                                ? 'border-primary-500 shadow-xl shadow-primary-500/10'
                                : 'border-gray-100 dark:border-gray-700 hover:border-primary-200 dark:hover:border-primary-800 hover:shadow-lg'"
                                @click.stop>

                            <!-- Featured badge -->
                            <div v-if="plan.is_featured"
                                 class="absolute -top-3 left-1/2 -translate-x-1/2 px-3 py-1 bg-gradient-to-r from-amber-400 to-yellow-500 text-yellow-900 text-[10px] font-bold rounded-full shadow-lg shadow-amber-200 dark:shadow-amber-900/40 tracking-wide uppercase">
                                ⭐ Most popular
                            </div>

                            <!-- Current plan ribbon -->
                            <div v-if="isCurrentPlan(plan) && actionType !== 'new'"
                                 class="absolute -top-3 right-4 px-3 py-1 bg-gray-900 dark:bg-white text-white dark:text-gray-900 text-[10px] font-bold rounded-full tracking-wide uppercase">
                                Current
                            </div>

                            <!-- Header -->
                            <div class="mb-5">
                                <h3 class="text-xl font-bold text-gray-900 dark:text-white tracking-tight">{{ plan.name }}</h3>
                                <p v-if="plan.description" class="text-sm text-gray-500 dark:text-gray-400 mt-1 leading-relaxed line-clamp-2">
                                    {{ plan.description }}
                                </p>
                            </div>

                            <!-- Price line -->
                            <div class="mb-5">
                                <template v-if="Number(plan.price_monthly) === 0">
                                    <p class="text-3xl font-bold text-gray-900 dark:text-white">Free</p>
                                    <p class="text-xs text-gray-400 dark:text-gray-500 mt-0.5">Forever — no card required</p>
                                </template>
                                <template v-else>
                                    <p class="text-3xl font-bold text-gray-900 dark:text-white">
                                        {{ formatPrice(plan.price_monthly) }}
                                        <span class="text-sm font-medium text-gray-400 dark:text-gray-500">/mo</span>
                                    </p>
                                    <p class="text-xs text-gray-400 dark:text-gray-500 mt-0.5">
                                        or {{ formatPrice(plan.price_yearly) }} yearly
                                    </p>
                                </template>
                            </div>

                            <!-- Billing toggle -->
                            <div class="mb-4 p-1 bg-gray-100 dark:bg-gray-700 rounded-xl flex">
                                <button type="button"
                                        @click.stop="setBillingType(plan.id, 'monthly')"
                                        class="flex-1 px-3 py-2 rounded-lg text-xs font-semibold transition-all duration-200"
                                        :class="selectedPlanId === plan.id && billingType === 'monthly'
                                            ? 'bg-white dark:bg-gray-800 shadow-sm text-primary-700 dark:text-primary-400'
                                            : 'text-gray-600 dark:text-gray-400 hover:text-gray-800 dark:hover:text-gray-200'">
                                    Monthly
                                </button>
                                <button type="button"
                                        @click.stop="setBillingType(plan.id, 'yearly')"
                                        class="flex-1 px-3 py-2 rounded-lg text-xs font-semibold transition-all duration-200"
                                        :class="selectedPlanId === plan.id && billingType === 'yearly'
                                            ? 'bg-white dark:bg-gray-800 shadow-sm text-primary-700 dark:text-primary-400'
                                            : 'text-gray-600 dark:text-gray-400 hover:text-gray-800 dark:hover:text-gray-200'">
                                    Yearly
                                    <span class="block text-[9px] text-emerald-600 dark:text-emerald-400 font-medium">Save 15%</span>
                                </button>
                            </div>

                            <!-- Duration dropdown -->
                            <Transition
                                enter-active-class="transition duration-200 ease-out"
                                enter-from-class="opacity-0 -translate-y-2"
                                enter-to-class="opacity-100 translate-y-0"
                                leave-active-class="transition duration-150 ease-in"
                                leave-from-class="opacity-100 translate-y-0"
                                leave-to-class="opacity-0 -translate-y-2">
                                <div v-if="selectedPlanId === plan.id" class="mb-4" @click.stop @mousedown.stop>
                                <label class="block text-xs font-medium text-gray-500 dark:text-gray-400 mb-1.5">
                                    Duration
                                </label>
                                <select v-model="selectedDuration"
                                        @change="updatePrice(plan)"
                                        @click.stop
                                        @mousedown.stop
                                        class="w-full px-3 py-2.5 border border-gray-200 dark:border-gray-600 rounded-xl bg-white dark:bg-gray-900 text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-primary-500 focus:border-transparent transition-all text-sm font-medium">
                                    <option value="">Choose duration…</option>
                                    <option v-for="value in availableDurations" :key="value" :value="value">
                                        {{ value }} {{ billingType === 'monthly' ? (value > 1 ? 'months' : 'month') : (value > 1 ? 'years' : 'year') }}
                                        {{ getRecommendedBadge(value) ? `· ${getRecommendedBadge(value)}` : '' }}
                                    </option>
                                </select>
                            </div>
                            </Transition>

                            <!-- Features -->
                            <ul class="space-y-2 mb-6 text-sm">
                                <li class="flex items-center gap-2 text-gray-600 dark:text-gray-400">
                                    <svg class="w-4 h-4 text-emerald-500 dark:text-emerald-400 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7" />
                                    </svg>
                                    <span>
                                        <span class="font-semibold text-gray-900 dark:text-white">{{ formatLimit(plan.max_locations) }}</span>
                                        {{ isUnlimited(plan.max_locations) ? 'unlimited locations' : (Number(plan.max_locations) === 1 ? 'location' : 'locations') }}
                                    </span>
                                </li>
                                <li class="flex items-center gap-2 text-gray-600 dark:text-gray-400">
                                    <svg class="w-4 h-4 text-emerald-500 dark:text-emerald-400 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7" />
                                    </svg>
                                    <span>
                                        <span class="font-semibold text-gray-900 dark:text-white">{{ formatLimit(plan.max_images) }}</span>
                                        {{ isUnlimited(plan.max_images) ? 'unlimited images' : (Number(plan.max_images) === 1 ? 'image' : 'images') }}
                                    </span>
                                </li>
                            </ul>

                            <!-- Card CTA -->
                            <button type="button"
                                    @click.stop="confirmSelection(plan)"
                                    :disabled="isPlanButtonDisabled(plan)"
                                    class="w-full py-2.5 rounded-xl font-semibold transition-all duration-200 text-sm"
                                    :class="[
                                        isPlanButtonDisabled(plan)
                                            ? 'bg-gray-100 dark:bg-gray-700 text-gray-400 dark:text-gray-500 cursor-not-allowed'
                                            : (selectedPlanId === plan.id && selectedDuration && billingType
                                                ? 'bg-gradient-to-r from-primary-600 to-primary-700 text-white shadow-lg shadow-primary-500/25 hover:shadow-primary-500/40 hover:-translate-y-0.5'
                                                : 'bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-300 hover:bg-gray-200 dark:hover:bg-gray-600')
                                    ]">
                                {{ getPlanButtonLabel(plan) }}
                            </button>
                        </div>
                    </div>

                    <!-- ✅ MOBILE ORDER SUMMARY -->
                    <div v-if="selectedPlan && selectedDuration" class="lg:hidden">
                        <div class="bg-white dark:bg-gray-800 rounded-2xl border border-gray-100 dark:border-gray-700 shadow-sm overflow-hidden">
                            <div class="px-5 py-3 border-b border-gray-100 dark:border-gray-700 bg-gradient-to-br from-gray-50 to-white dark:from-gray-800 dark:to-gray-800/50">
                                <p class="text-[10px] uppercase tracking-widest text-gray-400 dark:text-gray-500 font-bold">Order summary</p>
                            </div>
                            <div class="p-5 space-y-3">
                                <div class="flex items-center justify-between text-sm">
                                    <span class="text-gray-500 dark:text-gray-400">Plan</span>
                                    <span class="font-semibold text-gray-900 dark:text-white">{{ selectedPlan.name }}</span>
                                </div>
                                <div class="flex items-center justify-between text-sm">
                                    <span class="text-gray-500 dark:text-gray-400">Duration</span>
                                    <span class="font-semibold text-gray-900 dark:text-white">
                                        {{ selectedDuration }} {{ billingType === 'monthly' ? (selectedDuration > 1 ? 'months' : 'month') : (selectedDuration > 1 ? 'years' : 'year') }}
                                    </span>
                                </div>
                                <div class="pt-3 mt-3 border-t border-gray-100 dark:border-gray-700 space-y-2">
                                    <div class="flex items-center justify-between text-sm">
                                        <span class="text-gray-500 dark:text-gray-400">List price</span>
                                        <span class="text-gray-900 dark:text-white">{{ formatPrice(totalPrice) }}</span>
                                    </div>
                                    <div v-if="creditApplied > 0" class="flex items-center justify-between text-sm">
                                        <span class="text-emerald-600 dark:text-emerald-400 flex items-center gap-1">
                                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 12H4" />
                                            </svg>
                                            Credit applied
                                        </span>
                                        <span class="text-emerald-600 dark:text-emerald-400 font-medium">− {{ formatPrice(creditApplied) }}</span>
                                    </div>
                                    <div class="pt-3 mt-3 border-t border-gray-100 dark:border-gray-700 flex items-end justify-between">
                                        <span class="text-sm font-semibold text-gray-700 dark:text-gray-300">You pay</span>
                                        <span class="text-2xl font-bold text-gray-900 dark:text-white tracking-tight">
                                            {{ formatPrice(amountDue) }}
                                        </span>
                                    </div>
                                    <div v-if="leftoverCredit > 0" class="pt-3 mt-3 border-t border-dashed border-emerald-200 dark:border-emerald-800">
                                        <div class="flex items-center gap-2 text-xs text-emerald-700 dark:text-emerald-400">
                                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                                            </svg>
                                            <span>
                                                <strong>{{ formatPrice(leftoverCredit) }}</strong> saved as credit for next time
                                            </span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- ✅ MOBILE CREDIT EXPLANATION -->
                    <div v-if="actionType === 'upgrade' && totalAvailableCredit > 0 && selectedPlan" class="lg:hidden">
                        <div class="bg-gradient-to-br from-emerald-50 to-green-50 dark:from-emerald-900/20 dark:to-green-900/10 border border-emerald-200 dark:border-emerald-800 rounded-2xl p-5">
                            <div class="flex items-start gap-3">
                                <div class="w-9 h-9 rounded-xl bg-emerald-100 dark:bg-emerald-900/40 flex items-center justify-center flex-shrink-0">
                                    <svg class="w-4 h-4 text-emerald-600 dark:text-emerald-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                    </svg>
                                </div>
                                <div class="text-xs text-emerald-800 dark:text-emerald-300 leading-relaxed">
                                    <p class="font-bold mb-1">Credit available</p>
                                    <p v-if="upgradeCredit">
                                        {{ upgradeCredit.days_remaining }} unused days on {{ upgradeCredit.old_plan_name }} —
                                        worth <strong>{{ formatPrice(upgradeCredit.credit_amount) }}</strong>.
                                    </p>
                                    <p v-if="carriedCredit > 0" class="mt-1">
                                        Plus <strong>{{ formatPrice(carriedCredit) }}</strong> saved from a previous change.
                                    </p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Info strip -->
                    <div class="bg-white dark:bg-gray-800 rounded-2xl border border-gray-100 dark:border-gray-700 p-5 flex items-start gap-3">
                        <div class="w-9 h-9 rounded-xl bg-blue-50 dark:bg-blue-900/30 flex items-center justify-center flex-shrink-0">
                            <svg class="w-4 h-4 text-blue-600 dark:text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                        </div>
                        <div class="text-sm text-gray-600 dark:text-gray-400">
                            <p class="font-semibold text-gray-900 dark:text-white">
                                {{ actionType === 'new' ? 'Ready to subscribe' : actionType === 'renew' ? 'Renewing your plan' : 'Changing your plan' }}
                            </p>
                            <p class="mt-0.5">
                                Payments are processed securely via Fapshi (MTN MoMo / Orange Money).
                            </p>
                        </div>
                    </div>
                </div>

                <!-- ============ RIGHT: Sticky summary ============ -->
                <div class="hidden lg:block">
                    <div class="sticky top-24 space-y-4">

                        <!-- Summary card -->
                        <div class="bg-white dark:bg-gray-800 rounded-2xl border border-gray-100 dark:border-gray-700 shadow-sm overflow-hidden">
                            <div class="px-5 py-4 border-b border-gray-100 dark:border-gray-700 bg-gradient-to-br from-gray-50 to-white dark:from-gray-800 dark:to-gray-800/50">
                                <p class="text-[10px] uppercase tracking-widest text-gray-400 dark:text-gray-500 font-bold">Order summary</p>
                            </div>

                            <div class="p-5 space-y-3">
                                <!-- Selection state -->
                                <div v-if="!selectedPlanId" class="text-sm text-gray-400 dark:text-gray-500 italic">
                                    Select a plan to see your total
                                </div>

                                <template v-else>
                                    <!-- New plan row -->
                                    <div class="flex items-center justify-between text-sm">
                                        <span class="text-gray-500 dark:text-gray-400">Plan</span>
                                        <span class="font-semibold text-gray-900 dark:text-white">
                                            {{ selectedPlan?.name || '—' }}
                                        </span>
                                    </div>

                                    <!-- Billing row -->
                                    <div v-if="selectedDuration" class="flex items-center justify-between text-sm">
                                        <span class="text-gray-500 dark:text-gray-400">Duration</span>
                                        <span class="font-semibold text-gray-900 dark:text-white">
                                            {{ selectedDuration }} {{ billingType === 'monthly' ? (selectedDuration > 1 ? 'months' : 'month') : (selectedDuration > 1 ? 'years' : 'year') }}
                                        </span>
                                    </div>

                                    <!-- Divider -->
                                    <div v-if="selectedDuration" class="pt-3 mt-3 border-t border-gray-100 dark:border-gray-700 space-y-2">

                                        <!-- List price -->
                                        <div class="flex items-center justify-between text-sm">
                                            <span class="text-gray-500 dark:text-gray-400">List price</span>
                                            <span class="text-gray-900 dark:text-white">{{ formatPrice(totalPrice) }}</span>
                                        </div>

                                        <!-- Proration credit -->
                                        <div v-if="creditApplied > 0" class="flex items-center justify-between text-sm">
                                            <span class="text-emerald-600 dark:text-emerald-400 flex items-center gap-1">
                                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 12H4" />
                                                </svg>
                                                Credit applied
                                            </span>
                                            <span class="text-emerald-600 dark:text-emerald-400 font-medium">− {{ formatPrice(creditApplied) }}</span>
                                        </div>

                                        <!-- Total -->
                                        <div class="pt-3 mt-3 border-t border-gray-100 dark:border-gray-700">
                                            <div class="flex items-end justify-between">
                                                <span class="text-sm font-semibold text-gray-700 dark:text-gray-300">You pay</span>
                                                <span class="text-2xl font-bold text-gray-900 dark:text-white tracking-tight">
                                                    {{ formatPrice(amountDue) }}
                                                </span>
                                            </div>
                                        </div>

                                        <!-- Leftover credit -->
                                        <div v-if="leftoverCredit > 0" class="pt-3 mt-3 border-t border-dashed border-emerald-200 dark:border-emerald-800">
                                            <div class="flex items-center gap-2 text-xs text-emerald-700 dark:text-emerald-400">
                                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                                                </svg>
                                                <span>
                                                    <strong>{{ formatPrice(leftoverCredit) }}</strong> saved as credit for next time
                                                </span>
                                            </div>
                                        </div>
                                    </div>
                                </template>
                            </div>

                            <!-- Inline CTA -->
                            <div v-if="selectedPlan && selectedDuration && !isPlanButtonDisabled(selectedPlan)"
                                 class="px-5 pb-5">
                                <button type="button"
                                        @click="confirmSelection(selectedPlan)"
                                        class="w-full py-3 rounded-xl font-semibold text-sm transition-all duration-200 bg-gradient-to-r from-primary-600 to-primary-700 text-white shadow-lg shadow-primary-500/25 hover:shadow-primary-500/40 hover:-translate-y-0.5">
                                    {{ getPlanButtonLabel(selectedPlan) }}
                                </button>
                                <p class="text-[11px] text-center text-gray-400 dark:text-gray-500 mt-3">
                                    You'll be redirected to Fapshi to complete payment
                                </p>
                            </div>
                        </div>

                        <!-- Credit explanation -->
                        <div v-if="actionType === 'upgrade' && totalAvailableCredit > 0"
                             class="bg-gradient-to-br from-emerald-50 to-green-50 dark:from-emerald-900/20 dark:to-green-900/10 border border-emerald-200 dark:border-emerald-800 rounded-2xl p-5">
                            <div class="flex items-start gap-3">
                                <div class="w-9 h-9 rounded-xl bg-emerald-100 dark:bg-emerald-900/40 flex items-center justify-center flex-shrink-0">
                                    <svg class="w-4 h-4 text-emerald-600 dark:text-emerald-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                    </svg>
                                </div>
                                <div class="text-xs text-emerald-800 dark:text-emerald-300 leading-relaxed">
                                    <p class="font-bold mb-1">Credit available</p>
                                    <p v-if="upgradeCredit">
                                        {{ upgradeCredit.days_remaining }} unused days on {{ upgradeCredit.old_plan_name }} —
                                        worth <strong>{{ formatPrice(upgradeCredit.credit_amount) }}</strong>.
                                    </p>
                                    <p v-if="carriedCredit > 0" class="mt-1">
                                        Plus <strong>{{ formatPrice(carriedCredit) }}</strong> saved from a previous change.
                                    </p>
                                </div>
                            </div>
                        </div>

                        <!-- Trust signal -->
                        <div class="flex items-center justify-center gap-2 text-[11px] text-gray-400 dark:text-gray-500">
                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" />
                            </svg>
                            Secure payment · Cancel anytime
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- ==================== MOBILE STICKY CTA BAR ==================== -->
        <Transition
            enter-active-class="transition duration-200 ease-out"
            enter-from-class="opacity-0 translate-y-4"
            enter-to-class="opacity-100 translate-y-0"
            leave-active-class="transition duration-150 ease-in"
            leave-from-class="opacity-100 translate-y-0"
            leave-to-class="opacity-0 translate-y-4">
            <div v-if="selectedPlan && selectedDuration && !isPlanButtonDisabled(selectedPlan)"
                 class="lg:hidden fixed bottom-0 left-0 right-0 z-30 bg-white dark:bg-gray-800 border-t border-gray-200 dark:border-gray-700 shadow-2xl shadow-gray-900/10">
                <div class="px-4 py-3 flex items-center gap-3">
                    <div class="flex-1 min-w-0">
                        <p class="text-[10px] uppercase tracking-wider text-gray-400 dark:text-gray-500 font-semibold">You pay</p>
                        <p class="text-lg font-bold text-gray-900 dark:text-white truncate">
                            {{ formatPrice(amountDue) }}
                            <span v-if="creditApplied > 0" class="text-xs font-medium text-emerald-600 dark:text-emerald-400 ml-1">
                                · {{ formatPrice(creditApplied) }} credit
                            </span>
                        </p>
                    </div>
                    <button type="button"
                            @click="confirmSelection(selectedPlan)"
                            class="flex-shrink-0 px-5 py-3 rounded-xl font-semibold text-sm bg-gradient-to-r from-primary-600 to-primary-700 text-white shadow-lg shadow-primary-500/30">
                        {{ getPlanButtonLabel(selectedPlan) }}
                    </button>
                </div>
            </div>
        </Transition>
    </AuthenticatedLayout>
</template>

<script setup>
import { ref, computed, watch } from 'vue';
import { router } from '@inertiajs/vue3';
import { useToast } from '@/composables/useToast';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import PageHeader from '@/Components/PageHeader.vue';

const props = defineProps({
    business: Object,
    currentSubscription: Object,
    plans: Array,
    hasSubscription: Boolean,
    subscriptionStatus: String,
    upgradeCredit: Object,
    carriedCredit: { type: Number, default: 0 },
});

const { success, error } = useToast();

// State
const actionType = ref(props.hasSubscription ? 'renew' : 'new');
const selectedPlanId = ref(null);
const billingType = ref('monthly');
const selectedDuration = ref(null);
const totalPrice = ref(0);
const originalPrice = ref(0);
const savingsPercentage = ref(0);

// ✅ Duration options based on billing type
const availableDurations = computed(() => {
    if (billingType.value === 'monthly') {
        return [1, 2, 3, 4, 5, 6, 7, 8, 9, 10, 11];
    } else {
        return [1, 2, 3, 4, 5, 6, 7, 8, 9, 10];
    }
});

// Filter plans based on action type
const filteredPlans = computed(() => {
    if (actionType.value === 'renew' && props.currentSubscription) {
        return props.plans.filter(p => p.id === props.currentSubscription.plan_id);
    }
    return props.plans;
});

// ✅ Currently selected plan object (for summary sidebar)
const selectedPlan = computed(() => {
    if (!selectedPlanId.value) return null;
    return props.plans.find(p => p.id === selectedPlanId.value) || null;
});

// ✅ Is the selected plan cheaper than the current plan?
const isDowngrade = computed(() => {
    if (actionType.value !== 'upgrade' || !props.currentSubscription?.plan) return false;
    const selected = props.plans.find(p => p.id === selectedPlanId.value);
    if (!selected) return false;
    const currentMonthly = Number(props.currentSubscription.plan.price_monthly || 0);
    const selectedMonthly = Number(selected.price_monthly || 0);
    return selectedMonthly < currentMonthly;
});

// ✅ Total available credit (proration + carried)
const totalAvailableCredit = computed(() => {
    const proration = Number(props.upgradeCredit?.credit_amount || 0);
    const carried = Number(props.carriedCredit || 0);
    return proration + carried;
});

// ✅ Effective credit applied to this purchase
const effectiveCreditApplied = computed(() => {
    if (actionType.value !== 'upgrade') return 0;
    return Math.max(0, Math.min(totalAvailableCredit.value, totalPrice.value));
});

// ✅ Leftover credit that will be banked after this purchase
const leftoverCredit = computed(() => {
    return Math.max(0, totalAvailableCredit.value - totalPrice.value);
});

// Alias for template convenience
const creditApplied = effectiveCreditApplied;

const amountDue = computed(() => {
    return Math.max(100, Math.round(totalPrice.value - effectiveCreditApplied.value));
});

// ✅ Direction helpers per plan
const isCurrentPlan = (plan) => {
    return props.currentSubscription?.plan_id === plan.id;
};

const getPlanDirection = (plan) => {
    if (actionType.value === 'new') return 'new';
    if (!props.currentSubscription?.plan) return 'upgrade';

    // ✅ Current plan — always show as "current" regardless of actionType
    if (props.currentSubscription.plan_id === plan.id) {
        return 'current';
    }

    const currentMonthly = Number(props.currentSubscription.plan.price_monthly || 0);
    const planMonthly = Number(plan.price_monthly || 0);

    if (planMonthly > currentMonthly) return 'upgrade';
    if (planMonthly < currentMonthly) return 'downgrade';
    return 'same';
};

const getPlanButtonLabel = (plan) => {
    const dir = getPlanDirection(plan);
    if (dir === 'current') return '✓ Current plan';
    if (dir === 'downgrade') return '⬇️ Downgrade now';
    if (dir === 'upgrade') return '⬆️ Upgrade now';
    if (dir === 'same') return '🔄 Renew plan';
    return '🚀 Get subscription';
};

const isPlanButtonDisabled = (plan) => {
    const dir = getPlanDirection(plan);
    if (dir === 'current') return true;
    return false;
};

// Watch for billing type changes to reset duration
watch(billingType, () => {
    selectedDuration.value = null;
    totalPrice.value = 0;
    originalPrice.value = 0;
    savingsPercentage.value = 0;
});

// ✅ Update price when duration changes
const updatePrice = (plan) => {
    if (!selectedDuration.value || !billingType.value) return;

    const duration = selectedDuration.value;

    if (billingType.value === 'monthly') {
        totalPrice.value = Number(plan.price_monthly) * duration;
        originalPrice.value = totalPrice.value;
        savingsPercentage.value = 0;
    } else {
        const discount = plan.yearly_discount_percentage || 15;
        const basePrice = Number(plan.price_yearly) * duration;
        totalPrice.value = basePrice * (1 - (discount / 100));
        originalPrice.value = basePrice;
        savingsPercentage.value = discount;
    }
};

// ✅ Get recommended badge for duration
const getRecommendedBadge = (duration) => {
    if (billingType.value === 'monthly' && duration === 6) return 'Popular';
    if (billingType.value === 'yearly' && duration >= 3) return 'Best value';
    return null;
};

const setBillingType = (planId, type) => {
    selectedPlanId.value = planId;
    billingType.value = type;
    selectedDuration.value = null;
    totalPrice.value = 0;
    originalPrice.value = 0;
    savingsPercentage.value = 0;
};

const formatDate = (date) => {
    if (!date) return 'N/A';
    return new Date(date).toLocaleDateString('en-US', {
        year: 'numeric',
        month: 'short',
        day: 'numeric'
    });
};

const formatPrice = (price) => {
    if (price === null || price === undefined || price === '') return 'N/A';
    const num = Number(price);
    if (isNaN(num)) return 'N/A';
    if (num === 0) return 'Free';
    return new Intl.NumberFormat('en-US', {
        style: 'currency',
        currency: 'XAF',
        minimumFractionDigits: 0,
    }).format(num);
};


// ✅ Handles -1, 999, and 0 correctly for plan limits
const formatLimit = (value) => {
    const n = Number(value);
    if (isNaN(n)) return '—';
    if (n === 999 || n === -1) return '∞';
    if (n === 0) return '0';
    return n;
};

const isUnlimited = (value) => {
    const n = Number(value);
    return n === 999 || n === -1;
};


const confirmSelection = (plan) => {
    if (isPlanButtonDisabled(plan)) return;

    if (!selectedDuration.value || !billingType.value) {
        error('Selection Required ⚠️', 'Please select a duration for your subscription.', { duration: 4000 });
        return;
    }

    // ✅ Wire contract unchanged — same payload as before
    router.post('/owner/subscription/select-plan', {
        plan_id: plan.id,
        action_type: actionType.value,
        billing_type: billingType.value,
        duration: selectedDuration.value,
        total_price: totalPrice.value,
    }, {
        onError: (errors) => {
            const firstError = Object.values(errors)[0];
            error(
                'Action Failed ❌',
                firstError || 'Failed to process. Please try again.',
                { duration: 4000 }
            );
        }
    });
};

</script>