<!-- resources/js/Pages/Admin/Plans/Edit.vue -->
<template>
    <AuthenticatedLayout>
        <!-- COMPACT HEADER -->
        <PageHeader color="pink" :breadcrumb="[
            { label: 'Admin', href: '/admin/dashboard' },
            { label: 'Plans', href: '/admin/plans' },
            { label: plan.name }
        ]">
            <template #icon>
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                    d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
            </template>
            <template #title>Edit {{ plan.name }}</template>
            <template #subtitle>{{ plan.subscriptions_count || 0 }} subscriptions</template>
            <template #actions>
                <button @click="duplicate"
                    class="inline-flex items-center gap-2 px-4 py-2 border border-gray-300 dark:border-gray-600 text-gray-700 dark:text-gray-300 rounded-xl hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors text-sm font-semibold">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z" />
                    </svg>
                    Duplicate
                </button>
                <a href="/admin/plans"
                    class="inline-flex items-center gap-2 px-4 py-2 border border-gray-300 dark:border-gray-600 text-gray-700 dark:text-gray-300 rounded-xl hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors text-sm font-semibold">
                    Back
                </a>
            </template>
        </PageHeader>

        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-6">
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                <!-- FORM (2/3) -->
                <div class="lg:col-span-2 space-y-6">
                    <!-- Basic Info -->
                    <div
                        class="bg-white dark:bg-gray-800 rounded-2xl shadow-sm border border-gray-100 dark:border-gray-700 overflow-hidden">
                        <div
                            class="px-6 py-4 border-b border-gray-100 dark:border-gray-700 bg-gradient-to-r from-gray-50 to-white dark:from-gray-900/50 dark:to-gray-800">
                            <h2 class="text-sm font-semibold text-gray-900 dark:text-white flex items-center gap-2">
                                <span class="text-lg">📋</span>
                                Basic Information
                            </h2>
                        </div>
                        <div class="p-6 space-y-5">
                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                                        Plan Name <span class="text-red-500">*</span>
                                    </label>
                                    <input v-model="form.name" type="text" placeholder="e.g., Starter"
                                        class="w-full px-4 py-2.5 border border-gray-300 dark:border-gray-600 rounded-xl bg-white dark:bg-gray-900 text-gray-900 dark:text-white focus:ring-2 focus:ring-primary-500 focus:border-transparent"
                                        required />
                                    <p v-if="form.errors.name" class="text-red-500 text-xs mt-1">{{ form.errors.name }}
                                    </p>
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                                        Tier <span class="text-red-500">*</span>
                                    </label>
                                    <select v-model="form.tier"
                                        class="w-full px-4 py-2.5 border border-gray-300 dark:border-gray-600 rounded-xl bg-white dark:bg-gray-900 text-gray-900 dark:text-white focus:ring-2 focus:ring-primary-500">
                                        <option value="free">Free</option>
                                        <option value="starter">Starter</option>
                                        <option value="growth">Growth</option>
                                        <option value="premium">Premium</option>
                                    </select>
                                </div>
                            </div>

                            <div>
                                <label
                                    class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Slug</label>
                                <input v-model="form.slug" type="text" placeholder="auto-generated-from-name"
                                    class="w-full px-4 py-2.5 border border-gray-300 dark:border-gray-600 rounded-xl bg-white dark:bg-gray-900 text-gray-900 dark:text-white focus:ring-2 focus:ring-primary-500 font-mono text-sm" />
                                <p class="text-xs text-gray-400 mt-1">Leave empty to auto-generate from name</p>
                            </div>

                            <div>
                                <label
                                    class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Tagline</label>
                                <input v-model="form.tagline" type="text" maxlength="255"
                                    placeholder="e.g., Start getting more customers"
                                    class="w-full px-4 py-2.5 border border-gray-300 dark:border-gray-600 rounded-xl bg-white dark:bg-gray-900 text-gray-900 dark:text-white focus:ring-2 focus:ring-primary-500" />
                            </div>

                            <div>
                                <label
                                    class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Description</label>
                                <textarea v-model="form.description" rows="2" maxlength="1000"
                                    placeholder="Brief description shown on pricing page"
                                    class="w-full px-4 py-2.5 border border-gray-300 dark:border-gray-600 rounded-xl bg-white dark:bg-gray-900 text-gray-900 dark:text-white focus:ring-2 focus:ring-primary-500 resize-none"></textarea>
                            </div>
                        </div>
                    </div>

                    <!-- Pricing -->
                    <div
                        class="bg-white dark:bg-gray-800 rounded-2xl shadow-sm border border-gray-100 dark:border-gray-700 overflow-hidden">
                        <div
                            class="px-6 py-4 border-b border-gray-100 dark:border-gray-700 bg-gradient-to-r from-gray-50 to-white dark:from-gray-900/50 dark:to-gray-800">
                            <h2 class="text-sm font-semibold text-gray-900 dark:text-white flex items-center gap-2">
                                <span class="text-lg">💰</span>
                                Pricing
                            </h2>
                        </div>
                        <div class="p-6 space-y-5">
                            <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                                        Monthly Price <span class="text-red-500">*</span>
                                    </label>
                                    <input v-model.number="form.price_monthly" type="number" min="0" step="100"
                                        class="w-full px-4 py-2.5 border border-gray-300 dark:border-gray-600 rounded-xl bg-white dark:bg-gray-900 text-gray-900 dark:text-white focus:ring-2 focus:ring-primary-500" />
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                                        Yearly Price <span class="text-red-500">*</span>
                                    </label>
                                    <input v-model.number="form.price_yearly" type="number" min="0" step="100"
                                        class="w-full px-4 py-2.5 border border-gray-300 dark:border-gray-600 rounded-xl bg-white dark:bg-gray-900 text-gray-900 dark:text-white focus:ring-2 focus:ring-primary-500" />
                                </div>
                                <div>
                                    <label
                                        class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Currency</label>
                                    <select v-model="form.currency"
                                        class="w-full px-4 py-2.5 border border-gray-300 dark:border-gray-600 rounded-xl bg-white dark:bg-gray-900 text-gray-900 dark:text-white focus:ring-2 focus:ring-primary-500">
                                        <option value="XAF">XAF</option>
                                        <option value="USD">USD</option>
                                        <option value="EUR">EUR</option>
                                    </select>
                                </div>
                            </div>

                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                                        Yearly Discount %
                                    </label>
                                    <input v-model.number="form.yearly_discount_percentage" type="number" min="0"
                                        max="100" step="0.01"
                                        class="w-full px-4 py-2.5 border border-gray-300 dark:border-gray-600 rounded-xl bg-white dark:bg-gray-900 text-gray-900 dark:text-white focus:ring-2 focus:ring-primary-500" />
                                    <p class="text-xs text-gray-400 mt-1">Auto-calculated if left empty</p>
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Trial
                                        Days</label>
                                    <input v-model.number="form.trial_days" type="number" min="0" max="365"
                                        class="w-full px-4 py-2.5 border border-gray-300 dark:border-gray-600 rounded-xl bg-white dark:bg-gray-900 text-gray-900 dark:text-white focus:ring-2 focus:ring-primary-500" />
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Limits -->
                    <div
                        class="bg-white dark:bg-gray-800 rounded-2xl shadow-sm border border-gray-100 dark:border-gray-700 overflow-hidden">
                        <div
                            class="px-6 py-4 border-b border-gray-100 dark:border-gray-700 bg-gradient-to-r from-gray-50 to-white dark:from-gray-900/50 dark:to-gray-800">
                            <h2 class="text-sm font-semibold text-gray-900 dark:text-white flex items-center gap-2">
                                <span class="text-lg">📊</span>
                                Resource Limits
                            </h2>
                            <p class="text-xs text-gray-500 dark:text-gray-400 mt-0.5">Set to -1 for unlimited</p>
                        </div>
                        <div class="p-6">
                            <div class="grid grid-cols-2 sm:grid-cols-3 gap-4">
                                <div v-for="limit in limitFields" :key="limit.key">
                                    <label
                                        class="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1 capitalize">
                                        {{ limit.label }}
                                    </label>
                                    <input v-model.number="form[limit.key]" type="number" min="-1"
                                        class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-900 text-gray-900 dark:text-white text-sm focus:ring-2 focus:ring-primary-500" />
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Features -->
                    <div
                        class="bg-white dark:bg-gray-800 rounded-2xl shadow-sm border border-gray-100 dark:border-gray-700 overflow-hidden">
                        <div
                            class="px-6 py-4 border-b border-gray-100 dark:border-gray-700 bg-gradient-to-r from-gray-50 to-white dark:from-gray-900/50 dark:to-gray-800">
                            <h2 class="text-sm font-semibold text-gray-900 dark:text-white flex items-center gap-2">
                                <span class="text-lg">⚡</span>
                                Feature Flags
                            </h2>
                            <p class="text-xs text-gray-500 dark:text-gray-400 mt-0.5">Toggle which features this plan
                                unlocks
                            </p>
                        </div>
                        <div class="p-6">
                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                                <FeatureToggle v-for="(def, key) in featureDefinitions" :key="key"
                                    :value="form.features[key] || false" :label="def.label"
                                    :description="def.description" :icon="def.icon" :min-tier="def.min_tier"
                                    @update:value="(val) => form.features[key] = val" />
                            </div>

                            <!-- Analytics Level -->
                            <div class="mt-5 pt-5 border-t border-gray-200 dark:border-gray-700">
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                    Analytics Level
                                </label>
                                <div class="grid grid-cols-2 sm:grid-cols-3 gap-2">
                                    <button v-for="(label, key) in analyticsLevels" :key="key" type="button"
                                        @click="form.features.analytics = key"
                                        class="px-3 py-2 text-xs font-medium rounded-lg border-2 transition-all"
                                        :class="form.features.analytics === key
                                            ? 'border-primary-500 bg-primary-50 dark:bg-primary-900/20 text-primary-700 dark:text-primary-400'
                                            : 'border-gray-200 dark:border-gray-700 text-gray-600 dark:text-gray-400 hover:border-gray-300'">
                                        {{ label }}
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Marketing -->
                    <div
                        class="bg-white dark:bg-gray-800 rounded-2xl shadow-sm border border-gray-100 dark:border-gray-700 overflow-hidden">
                        <div
                            class="px-6 py-4 border-b border-gray-100 dark:border-gray-700 bg-gradient-to-r from-gray-50 to-white dark:from-gray-900/50 dark:to-gray-800">
                            <h2 class="text-sm font-semibold text-gray-900 dark:text-white flex items-center gap-2">
                                <span class="text-lg">🎨</span>
                                Marketing & Display
                            </h2>
                        </div>
                        <div class="p-6 space-y-5">
                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Badge
                                        Text</label>
                                    <input v-model="form.badge_text" type="text" maxlength="50"
                                        placeholder="MOST POPULAR"
                                        class="w-full px-4 py-2.5 border border-gray-300 dark:border-gray-600 rounded-xl bg-white dark:bg-gray-900 text-gray-900 dark:text-white focus:ring-2 focus:ring-primary-500" />
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Badge
                                        Color</label>
                                    <select v-model="form.badge_color"
                                        class="w-full px-4 py-2.5 border border-gray-300 dark:border-gray-600 rounded-xl bg-white dark:bg-gray-900 text-gray-900 dark:text-white focus:ring-2 focus:ring-primary-500">
                                        <option value="">None</option>
                                        <option value="primary">Primary (Blue)</option>
                                        <option value="purple">Purple</option>
                                        <option value="gold">Gold</option>
                                        <option value="green">Green</option>
                                        <option value="red">Red</option>
                                    </select>
                                </div>
                            </div>

                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                    Feature List (shown on pricing page)
                                </label>
                                <div class="space-y-2">
                                    <div v-for="(_, i) in form.feature_list" :key="i" class="flex items-center gap-2">
                                        <input v-model="form.feature_list[i]" type="text"
                                            placeholder="Feature description"
                                            class="flex-1 px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-900 text-gray-900 dark:text-white text-sm focus:ring-2 focus:ring-primary-500" />
                                        <button type="button" @click="form.feature_list.splice(i, 1)"
                                            class="p-2 text-red-500 hover:bg-red-50 dark:hover:bg-red-900/20 rounded-lg transition-colors">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                    d="M6 18L18 6M6 6l12 12" />
                                            </svg>
                                        </button>
                                    </div>
                                    <button type="button" @click="form.feature_list.push('')"
                                        class="w-full py-2 border-2 border-dashed border-gray-300 dark:border-gray-600 rounded-lg text-sm text-gray-500 hover:border-primary-400 hover:text-primary-600 transition-colors flex items-center justify-center gap-1">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M12 4v16m8-8H4" />
                                        </svg>
                                        Add Feature
                                    </button>
                                </div>
                            </div>

                            <div class="flex flex-wrap gap-4 pt-3">
                                <label class="flex items-center gap-2 cursor-pointer">
                                    <input type="checkbox" v-model="form.is_active" class="rounded" />
                                    <span class="text-sm text-gray-700 dark:text-gray-300">Active</span>
                                </label>
                                <label class="flex items-center gap-2 cursor-pointer">
                                    <input type="checkbox" v-model="form.is_featured" class="rounded" />
                                    <span class="text-sm text-gray-700 dark:text-gray-300">Featured</span>
                                </label>
                                <label class="flex items-center gap-2 cursor-pointer">
                                    <input type="checkbox" v-model="form.is_popular" class="rounded" />
                                    <span class="text-sm text-gray-700 dark:text-gray-300">Mark as Popular</span>
                                </label>
                                <div class="flex items-center gap-2">
                                    <label class="text-sm text-gray-700 dark:text-gray-300">Sort:</label>
                                    <input v-model.number="form.sort_order" type="number" min="0"
                                        class="w-20 px-2 py-1 border border-gray-300 dark:border-gray-600 rounded bg-white dark:bg-gray-900 text-sm" />
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Actions -->
                    <div class="flex flex-col sm:flex-row justify-end gap-3">
                        <a href="/admin/plans"
                            class="px-6 py-3 text-center border border-gray-300 dark:border-gray-600 text-gray-700 dark:text-gray-300 rounded-xl hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors font-medium text-sm">
                            Cancel
                        </a>
                        <button type="button" @click="submit" :disabled="form.processing"
                            class="px-8 py-3 bg-gradient-to-r from-primary-600 to-primary-700 text-white rounded-xl font-semibold hover:from-primary-700 hover:to-primary-800 transition-all shadow-lg shadow-primary-500/30 disabled:opacity-50 inline-flex items-center justify-center gap-2">
                            <svg v-if="form.processing" class="animate-spin w-4 h-4" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor"
                                    stroke-width="4">
                                </circle>
                                <path class="opacity-75" fill="currentColor"
                                    d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z">
                                </path>
                            </svg>
                            {{ form.processing ? 'Updating...' : 'Edit Plan' }}
                        </button>
                    </div>
                </div>

                <!-- PREVIEW (1/3) -->
                <div class="lg:col-span-1">
                    <div class="sticky top-6">
                        <div class="flex items-center gap-2 mb-4">
                            <h3 class="text-sm font-semibold text-gray-900 dark:text-white">Preview</h3>
                        </div>
                        <PlanPreviewCard :plan="form" />
                    </div>
                </div>
            </div>

            <!-- Danger Zone -->
            <div v-if="plan.subscriptions_count === 0"
                class="mt-6 bg-gradient-to-r from-red-50 to-white dark:from-red-900/20 dark:to-gray-800 border-2 border-red-200 dark:border-red-800 rounded-2xl p-6">
                <div class="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4">
                    <div>
                        <h4 class="text-sm font-bold text-red-900 dark:text-red-300">Danger Zone</h4>
                        <p class="text-sm text-red-700 dark:text-red-400 mt-0.5">
                            Permanently delete this plan. This cannot be undone.
                        </p>
                    </div>
                    <button @click="deletePlan"
                        class="px-5 py-2.5 bg-red-600 text-white rounded-xl font-semibold hover:bg-red-700 transition-colors text-sm flex-shrink-0">
                        Delete Plan
                    </button>
                </div>
            </div>
            <div v-else
                class="mt-6 bg-amber-50 dark:bg-amber-900/20 border border-amber-200 dark:border-amber-800 rounded-xl p-4 flex items-start gap-3">
                <svg class="w-5 h-5 text-amber-600 flex-shrink-0 mt-0.5" fill="none" stroke="currentColor"
                    viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                </svg>
                <div>
                    <p class="text-sm font-medium text-amber-800 dark:text-amber-400">
                        This plan has {{ plan.subscriptions_count }} active subscriptions
                    </p>
                    <p class="text-xs text-amber-600 dark:text-amber-500 mt-0.5">
                        Deactivate it instead of deleting to preserve subscription history.
                    </p>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>


<script setup>
    import { useForm } from '@inertiajs/vue3';
    import { router } from '@inertiajs/vue3';
    import { useToast } from '@/composables/useToast';
    import { useConfirm } from '@/composables/useConfirm';
    import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
    import FeatureToggle from '@/Components/Admin/FeatureToggle.vue';
    import PlanPreviewCard from '@/Components/Admin/PlanPreviewCard.vue';
    import PageHeader from '@/Components/PageHeader.vue';

    const props = defineProps({
        plan: { type: Object, required: true },
        featureDefinitions: { type: Object, required: true },
        analyticsLevels: { type: Object, required: true },
    });

    const { error } = useToast();
    const { confirm: confirmDialog } = useConfirm();

    const limitFields = [
        { key: 'max_businesses', label: 'Businesses' },
        { key: 'max_branches', label: 'Branches' },
        { key: 'max_services', label: 'Services' },
        { key: 'max_images', label: 'Images' },
        { key: 'max_coupons', label: 'Coupons' },
        { key: 'max_staff', label: 'Staff' },
    ];

    // ✅ Ensure features has all keys
    const features = {};
    Object.keys(props.featureDefinitions).forEach(key => {
        features[key] = props.plan.features?.[key] ?? false;
    });
    features.analytics = props.plan.features?.analytics || 'basic';

    // ✅ useForm — gives form.errors + form.form.processing automatically
    const form = useForm({
        name: props.plan.name,
        slug: props.plan.slug || '',
        tier: props.plan.tier || 'free',
        description: props.plan.description || '',
        tagline: props.plan.tagline || '',
        badge_text: props.plan.badge_text || '',
        badge_color: props.plan.badge_color || '',
        price_monthly: props.plan.price_monthly || 0,
        price_yearly: props.plan.price_yearly || 0,
        yearly_discount_percentage: props.plan.yearly_discount_percentage || 0,
        currency: props.plan.currency || 'XAF',
        trial_days: props.plan.trial_days || 0,
        max_businesses: props.plan.max_businesses ?? 1,
        max_branches: props.plan.max_branches ?? 1,
        max_services: props.plan.max_services ?? 3,
        max_images: props.plan.max_images ?? 3,
        max_coupons: props.plan.max_coupons ?? 0,
        max_staff: props.plan.max_staff ?? 0,
        features,
        feature_list: [...(props.plan.feature_list || [])],
        is_active: props.plan.is_active ?? true,
        is_featured: props.plan.is_featured ?? false,
        is_popular: props.plan.is_popular ?? false,
        sort_order: props.plan.sort_order ?? 0,
    });

    const submit = () => {
        form.put(`/admin/plans/${props.plan.id}`, {
            preserveScroll: true,
            // ✅ No success toast — the controller flashes
            //    'Plan updated successfully.' and redirects to /admin/plans
            //    where the toast fires on the index.
            onError: (errors) => {
                // Client-side fallback. Inline errors render via form.errors.
                const firstError = Object.values(errors)[0];
                error('Update Failed ❌', firstError || 'Please check the form.', { duration: 5000 });
            },
        });
    };

    const duplicate = async () => {
        const confirmed = await confirmDialog({
            title: 'Duplicate plan?',
            message: `A copy of "${props.plan.name}" will be created with "(Copy)" appended to its name. The new plan will be inactive by default.`,
            confirmText: 'Duplicate',
            cancelText: 'Cancel',
            variant: 'primary',
        });

        if (!confirmed) return;

        router.post(`/admin/plans/${props.plan.id}/duplicate`, {}, {
            preserveScroll: true,
            // ✅ No success toast — the controller flashes
            //    'Plan duplicated. Review and activate when ready.'
            //    and redirects to the new plan's Edit page.
        });
    };

    const deletePlan = async () => {
        const confirmed = await confirmDialog({
            title: 'Delete plan?',
            message: `"${props.plan.name}" will be permanently deleted. Note: plans with existing subscriptions cannot be deleted — deactivate them instead.`,
            confirmText: 'Delete',
            cancelText: 'Cancel',
            variant: 'danger',
        });

        if (!confirmed) return;

        router.delete(`/admin/plans/${props.plan.id}`, {
            preserveScroll: true,
            // ✅ No success toast — the controller flashes
            //    'Plan deleted successfully.' and redirects to /admin/plans.
        });
    };
</script>