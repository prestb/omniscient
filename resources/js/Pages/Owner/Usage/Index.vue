<!-- resources/js/Pages/Owner/Usage/Index.vue -->
<template>
    <AuthenticatedLayout>
        <!-- COMPACT HEADER -->
        <PageHeader
            color="emerald"
            :breadcrumb="[
                { label: 'Dashboard', href: '/owner/dashboard' },
                { label: 'Usage' },
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
            <template #title>Plan usage</template>
            <template #subtitle>Track your resource usage and plan limits</template>
            <template #actions>
                <span
                    class="inline-flex items-center gap-2 px-3 py-2 bg-gray-50 dark:bg-gray-700 border border-gray-200 dark:border-gray-600 rounded-xl text-sm font-semibold text-gray-700 dark:text-gray-300"
                >
                    <svg
                        class="w-4 h-4 text-emerald-600 dark:text-emerald-400"
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
                    {{ plan.name }}
                    <span class="text-xs text-gray-400 dark:text-gray-500">·</span>
                    <span class="text-xs capitalize">{{ plan.tier }} tier</span>
                </span>
                <a
                    v-if="plan.tier !== 'premium'"
                    href="/owner/subscription/renew"
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
                            d="M13 10V3L4 14h7v7l9-11h-7z"
                        />
                    </svg>
                    Upgrade
                </a>
            </template>
        </PageHeader>

        <div class="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8 py-8 space-y-6">
            <!-- PLAN OVERFLOW BANNER -->
            <div
                v-if="planOverflow && planOverflow.isOver"
                class="rounded-2xl p-5 border flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4"
                :class="
                    planOverflow.graceActive
                        ? 'bg-gradient-to-br from-orange-50 to-amber-50 dark:from-orange-900/20 dark:to-amber-900/10 border-orange-200 dark:border-orange-800'
                        : 'bg-gradient-to-br from-red-50 to-rose-50 dark:from-red-900/20 dark:to-rose-900/10 border-red-200 dark:border-red-800'
                "
            >
                <div class="flex items-start gap-4">
                    <div
                        class="w-11 h-11 rounded-xl flex items-center justify-center flex-shrink-0"
                        :class="planOverflow.graceActive ? 'bg-orange-100 dark:bg-orange-900/40' : 'bg-red-100 dark:bg-red-900/40'"
                    >
                        <svg
                            class="w-5 h-5"
                            :class="
                                planOverflow.graceActive
                                    ? 'text-orange-600 dark:text-orange-400'
                                    : 'text-red-600 dark:text-red-400'
                            "
                            fill="none"
                            stroke="currentColor"
                            viewBox="0 0 24 24"
                        >
                            <path
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                stroke-width="2"
                                d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"
                            />
                        </svg>
                    </div>
                    <div class="min-w-0">
                        <p
                            class="text-sm font-bold"
                            :class="
                                planOverflow.graceActive
                                    ? 'text-orange-800 dark:text-orange-300'
                                    : 'text-red-800 dark:text-red-300'
                            "
                        >
                            {{
                                planOverflow.graceActive
                                    ? "Plan downgrade — action needed"
                                    : "Over your plan limit"
                            }}
                        </p>
                        <p
                            class="text-sm mt-0.5"
                            :class="
                                planOverflow.graceActive
                                    ? 'text-orange-700 dark:text-orange-400'
                                    : 'text-red-700 dark:text-red-400'
                            "
                        >
                            <template v-if="planOverflow.graceActive">
                                You're over your plan limit for
                                {{ overLimitCount }} resource{{
                                    overLimitCount === 1 ? "" : "s"
                                }}. <strong>{{ planOverflow.totalOver }}</strong> item{{
                                    planOverflow.totalOver === 1 ? "" : "s"
                                }}
                                will be hidden on
                                <strong>{{ planOverflow.graceEndsAt }}</strong
                                >.
                            </template>
                            <template v-else>
                                <strong>{{ planOverflow.totalOver }}</strong> item{{
                                    planOverflow.totalOver === 1 ? "" : "s"
                                }}
                                {{ planOverflow.totalOver === 1 ? "is" : "are" }} hidden
                                from the public. Delete them from their respective pages,
                                or upgrade to restore.
                            </template>
                        </p>
                    </div>
                </div>
                <a
                    href="/owner/subscription/renew"
                    class="flex-shrink-0 inline-flex items-center gap-2 px-5 py-2.5 rounded-xl font-semibold transition-all shadow-lg hover:-translate-y-0.5 text-sm text-white"
                    :class="
                        planOverflow.graceActive
                            ? 'bg-gradient-to-r from-orange-500 to-amber-500 hover:from-orange-600 hover:to-amber-600 shadow-orange-500/25'
                            : 'bg-gradient-to-r from-red-500 to-rose-500 hover:from-red-600 hover:to-rose-600 shadow-red-500/25'
                    "
                >
                    Upgrade plan
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
                            d="M9 5l7 7-7 7"
                        />
                    </svg>
                </a>
            </div>

            <!-- USAGE CARDS -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                <div
                    v-for="(item, key) in usage"
                    :key="key"
                    class="bg-white dark:bg-gray-800 rounded-2xl border border-gray-100 dark:border-gray-700 p-5 hover:shadow-md transition-shadow"
                >
                    <!-- Header -->
                    <div class="flex items-center justify-between mb-4">
                        <div class="flex items-center gap-3">
                            <div
                                class="w-11 h-11 rounded-2xl bg-primary-50 dark:bg-primary-900/30 flex items-center justify-center text-xl flex-shrink-0"
                            >
                                {{ item.icon }}
                            </div>
                            <div class="min-w-0">
                                <h3
                                    class="font-bold text-gray-900 dark:text-white tracking-tight truncate"
                                >
                                    {{ item.label }}
                                </h3>
                                <p
                                    class="text-xs text-gray-500 dark:text-gray-400 mt-0.5"
                                >
                                    <span
                                        class="font-semibold text-gray-700 dark:text-gray-300"
                                        >{{ item.current }}</span
                                    >
                                    of
                                    <span
                                        class="font-semibold text-gray-700 dark:text-gray-300"
                                        >{{ item.limit === -1 ? "∞" : item.limit }}</span
                                    >
                                    used
                                </p>
                            </div>
                        </div>
                        <div
                            class="text-2xl font-bold tracking-tight"
                            :class="statusColor(item).text"
                        >
                            {{ item.limit === -1 ? "∞" : getPercent(item) + "%" }}
                        </div>
                    </div>

                    <!-- Progress -->
                    <div
                        class="w-full bg-gray-100 dark:bg-gray-700 rounded-full h-2.5 overflow-hidden mb-3"
                    >
                        <div
                            class="h-full rounded-full transition-all duration-500"
                            :class="statusColor(item).bar"
                            :style="{
                                width:
                                    item.limit === -1 ? '100%' : getPercent(item) + '%',
                            }"
                        ></div>
                    </div>

                    <!-- Hidden badge (if this resource is over limit) -->
                    <div v-if="overflowFor(key) && overflowFor(key).over > 0"
                         class="mt-3 pt-3 border-t border-gray-100 dark:border-gray-700 flex items-center justify-between gap-2">
                        <div class="inline-flex items-center gap-2 px-2.5 py-1 rounded-lg text-[11px] font-bold uppercase tracking-wide"
                             :class="planOverflow.graceActive
                                 ? 'bg-orange-100 dark:bg-orange-900/30 text-orange-700 dark:text-orange-400'
                                 : 'bg-red-100 dark:bg-red-900/30 text-red-700 dark:text-red-400'">
                            <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                      d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l3.59 3.59m0 0A9.953 9.953 0 0112 5c4.478 0 8.268 2.943 9.543 7a10.025 10.025 0 01-4.132 5.411m0 0L21 21" />
                            </svg>
                            {{ overflowFor(key).over }} hidden
                        </div>
                        <span class="text-[10px] font-medium"
                              :class="planOverflow.graceActive ? 'text-orange-600 dark:text-orange-400' : 'text-red-600 dark:text-red-400'">
                            {{ planOverflow.graceActive ? 'Hides on ' + planOverflow.graceEndsAt : 'Delete to reduce' }}
                        </span>
                    </div>

                    <!-- Status row -->
                    <div class="flex items-center justify-between"
                         :class="overflowFor(key) && overflowFor(key).over > 0 ? 'mt-2' : ''">
                        <p class="text-xs font-bold uppercase tracking-wide" :class="statusColor(item).text">
                            {{ getStatusText(item) }}
                        </p>
                        <a v-if="item.limit !== -1 && item.current >= item.limit"
                           href="/owner/subscription/renew"
                           class="text-xs font-bold text-primary-600 dark:text-primary-400 hover:text-primary-800 dark:hover:text-primary-300 transition-colors">
                            Upgrade →
                        </a>
                    </div>
                </div>
            </div>

            <!-- PLAN FEATURES -->
            <div
                class="bg-white dark:bg-gray-800 rounded-2xl border border-gray-100 dark:border-gray-700 overflow-hidden"
            >
                <div
                    class="px-6 py-4 border-b border-gray-100 dark:border-gray-700 bg-gradient-to-br from-gray-50 to-white dark:from-gray-800 dark:to-gray-800/50 flex items-center gap-3"
                >
                    <div
                        class="w-9 h-9 rounded-xl bg-purple-50 dark:bg-purple-900/30 flex items-center justify-center"
                    >
                        <svg
                            class="w-4 h-4 text-purple-600 dark:text-purple-400"
                            fill="none"
                            stroke="currentColor"
                            viewBox="0 0 24 24"
                        >
                            <path
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                stroke-width="2"
                                d="M5 3v4M3 5h4M6 17v4m-2-2h4m5-16l2.286 6.857L21 12l-5.714 2.143L13 21l-2.286-6.857L5 12l5.714-2.143L13 3z"
                            />
                        </svg>
                    </div>
                    <div>
                        <h3
                            class="text-sm font-bold text-gray-900 dark:text-white tracking-tight"
                        >
                            Your plan features
                        </h3>
                        <p class="text-xs text-gray-400 dark:text-gray-500">
                            What's included in your {{ plan.name }} plan
                        </p>
                    </div>
                </div>

                <div class="p-6">
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                        <div
                            v-for="(enabled, feature) in plan.features"
                            :key="feature"
                            class="flex items-center gap-3 p-3 rounded-xl transition-colors"
                            :class="
                                enabled
                                    ? 'bg-emerald-50 dark:bg-emerald-900/20 border border-emerald-100 dark:border-emerald-900/40'
                                    : 'bg-gray-50 dark:bg-gray-900/50 border border-gray-100 dark:border-gray-800'
                            "
                        >
                            <div
                                class="flex-shrink-0 w-6 h-6 rounded-full flex items-center justify-center"
                                :class="
                                    enabled
                                        ? 'bg-emerald-500'
                                        : 'bg-gray-300 dark:bg-gray-600'
                                "
                            >
                                <svg
                                    v-if="enabled"
                                    class="w-3.5 h-3.5 text-white"
                                    fill="none"
                                    stroke="currentColor"
                                    viewBox="0 0 24 24"
                                >
                                    <path
                                        stroke-linecap="round"
                                        stroke-linejoin="round"
                                        stroke-width="3"
                                        d="M5 13l4 4L19 7"
                                    />
                                </svg>
                                <svg
                                    v-else
                                    class="w-3 h-3 text-white"
                                    fill="none"
                                    stroke="currentColor"
                                    viewBox="0 0 24 24"
                                >
                                    <path
                                        stroke-linecap="round"
                                        stroke-linejoin="round"
                                        stroke-width="3"
                                        d="M6 18L18 6M6 6l12 12"
                                    />
                                </svg>
                            </div>
                            <span
                                class="text-sm font-semibold capitalize"
                                :class="
                                    enabled
                                        ? 'text-emerald-900 dark:text-emerald-300'
                                        : 'text-gray-500 dark:text-gray-400'
                                "
                            >
                                {{ formatFeature(feature) }}
                            </span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>

<script setup>
import { computed } from "vue";
import AuthenticatedLayout from "@/Layouts/AuthenticatedLayout.vue";
// import Breadcrumb from '@/Components/Breadcrumb.vue';
import PageHeader from "@/Components/PageHeader.vue";
const props = defineProps({
    plan: Object,
    usage: Object,
    planOverflow: { type: Object, default: null },
});

const formatPrice = (price) => {
    return new Intl.NumberFormat("en-US", {
        minimumFractionDigits: 0,
        maximumFractionDigits: 0,
    }).format(price);
};

// ✅ Count how many resource types are over limit
const overLimitCount = computed(() => {
    if (!props.planOverflow?.items) return 0;
    return Object.values(props.planOverflow.items).filter((i) => i.over > 0).length;
});

// ✅ Find overflow info for a specific resource key
const overflowFor = (key) => {
    return props.planOverflow?.items?.[key] || null;
};

const formatFeature = (feature) => {
    return feature.replace(/_/g, " ");
};

// ✅ Distinguish between "0/0 - not available on plan" vs "0/1 - available"
const isUnavailable = (item) => {
    return item.limit === 0;
};

const isUnlimited = (item) => {
    return item.limit === -1;
};

const getPercent = (item) => {
    if (item.limit === -1) return 0;
    if (item.limit === 0) return 0;   // ✅ 0/0 shows 0%, not 100%
    return Math.min(100, Math.round((item.current / item.limit) * 100));
};

const statusColor = (item) => {
    if (item.limit === -1) return { text: 'text-primary-600 dark:text-primary-400', bar: 'bg-gradient-to-r from-primary-400 to-primary-500' };
    if (item.limit === 0) return { text: 'text-gray-500 dark:text-gray-400', bar: 'bg-gray-300 dark:bg-gray-600' };   // ✅ not available
    const percent = getPercent(item);
    if (percent >= 100) return { text: 'text-red-600 dark:text-red-400', bar: 'bg-gradient-to-r from-red-500 to-red-600' };
    if (percent >= 80) return { text: 'text-amber-600 dark:text-amber-400', bar: 'bg-gradient-to-r from-amber-400 to-amber-500' };
    return { text: 'text-emerald-600 dark:text-emerald-400', bar: 'bg-gradient-to-r from-emerald-400 to-emerald-500' };
};

const getStatusText = (item) => {
    if (item.limit === -1) return 'Unlimited';
    if (item.limit === 0) return 'Not on your plan';   // ✅ clearer than "Limit reached"
    if (item.current >= item.limit) return 'Limit reached';
    if (item.current >= item.limit * 0.8) return 'Almost full';
    return 'Available';
};
</script>