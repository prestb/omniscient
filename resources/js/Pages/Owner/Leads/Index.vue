<!-- resources/js/Pages/Owner/Leads/Index.vue -->
<template>
    <AuthenticatedLayout>
        <!-- COMPACT HEADER -->
        <PageHeader color="cyan" :breadcrumb="[
            { label: 'Dashboard', href: '/owner/dashboard' },
            { label: 'Leads' }
        ]">
            <template #icon>
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                    d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4" />
            </template>
            <template #title>Leads inbox</template>
            <template #subtitle>{{ contextTitle }}</template>
            <template #actions>
                <span
                    class="inline-flex items-center gap-2 px-3 py-2 bg-gray-50 dark:bg-gray-700 border border-gray-200 dark:border-gray-600 rounded-xl text-sm font-semibold text-gray-700 dark:text-gray-300">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />
                    </svg>
                    {{ stats.total }} total
                </span>
                <span v-if="stats.new > 0"
                    class="inline-flex items-center gap-2 px-3 py-2 bg-blue-50 dark:bg-blue-900/30 border border-blue-200 dark:border-blue-800 rounded-xl text-sm font-semibold text-blue-700 dark:text-blue-400">
                    <span class="w-1.5 h-1.5 rounded-full bg-blue-500 animate-pulse"></span>
                    {{ stats.new }} new
                </span>
            </template>
        </PageHeader>

        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8 space-y-6">

            <!-- STATS -->
            <div class="grid grid-cols-2 sm:grid-cols-4 gap-4">
                <div
                    class="bg-white dark:bg-gray-800 rounded-2xl border border-gray-100 dark:border-gray-700 p-4 hover:shadow-md transition-shadow">
                    <p class="text-[10px] uppercase tracking-widest text-gray-400 font-bold">Total</p>
                    <p class="text-2xl font-bold text-gray-900 dark:text-white tracking-tight mt-1">{{ stats.total }}
                    </p>
                </div>
                <div
                    class="bg-white dark:bg-gray-800 rounded-2xl border border-gray-100 dark:border-gray-700 p-4 hover:shadow-md transition-shadow">
                    <p class="text-[10px] uppercase tracking-widest text-gray-400 font-bold">New</p>
                    <p class="text-2xl font-bold text-blue-600 tracking-tight mt-1">{{ stats.new }}</p>
                </div>
                <div
                    class="bg-white dark:bg-gray-800 rounded-2xl border border-gray-100 dark:border-gray-700 p-4 hover:shadow-md transition-shadow">
                    <p class="text-[10px] uppercase tracking-widest text-gray-400 font-bold">Replied</p>
                    <p class="text-2xl font-bold text-emerald-600 tracking-tight mt-1">{{ stats.replied }}</p>
                </div>
                <div
                    class="bg-white dark:bg-gray-800 rounded-2xl border border-gray-100 dark:border-gray-700 p-4 hover:shadow-md transition-shadow">
                    <p class="text-[10px] uppercase tracking-widest text-gray-400 font-bold">This week</p>
                    <p class="text-2xl font-bold text-purple-600 tracking-tight mt-1">{{ stats.this_week }}</p>
                </div>
            </div>

            <!-- FILTERS -->
            <div class="bg-white dark:bg-gray-800 rounded-2xl border border-gray-100 dark:border-gray-700 p-5">
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 items-end">
                    <div>
                        <label
                            class="block text-[10px] uppercase tracking-widest text-gray-400 font-bold mb-2">Status</label>
                        <select v-model="filters.status" @change="applyFilters"
                            class="w-full px-4 py-2.5 border border-gray-200 dark:border-gray-600 rounded-xl bg-white dark:bg-gray-900 text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-primary-500 focus:border-transparent transition-colors text-sm appearance-none">
                            <option value="">All active</option>
                            <option value="new">New</option>
                            <option value="read">Read</option>
                            <option value="replied">Replied</option>
                            <option value="archived">Archived</option>
                        </select>
                    </div>
                    <div class="lg:col-span-2">
                        <label
                            class="block text-[10px] uppercase tracking-widest text-gray-400 font-bold mb-2">Search</label>
                        <input type="text" v-model="filters.search" @keyup.enter="applyFilters"
                            placeholder="Search leads…"
                            class="w-full px-4 py-2.5 border border-gray-200 dark:border-gray-600 rounded-xl bg-white dark:bg-gray-900 text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-primary-500 focus:border-transparent transition-colors text-sm" />
                    </div>
                    <div class="flex gap-2">
                        <button @click="applyFilters"
                            class="flex-1 px-5 py-2.5 bg-gradient-to-r from-primary-600 to-primary-700 text-white rounded-xl hover:from-primary-700 hover:to-primary-800 transition-all shadow-lg shadow-primary-500/25 hover:shadow-primary-500/40 font-semibold text-sm">
                            Apply
                        </button>
                        <button @click="resetFilters"
                            class="px-5 py-2.5 border border-gray-300 dark:border-gray-600 text-gray-700 dark:text-gray-300 rounded-xl hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors font-semibold text-sm">
                            Reset
                        </button>
                    </div>
                </div>
            </div>

            <!-- LEADS LIST -->
            <div
                class="bg-white dark:bg-gray-800 rounded-2xl border border-gray-100 dark:border-gray-700 overflow-hidden">
                <div
                    class="px-6 py-4 border-b border-gray-100 dark:border-gray-700 bg-gradient-to-br from-gray-50 to-white dark:from-gray-800 dark:to-gray-800/50 flex items-center justify-between">
                    <div class="flex items-center gap-3">
                        <div class="w-9 h-9 rounded-xl bg-blue-50 dark:bg-blue-900/30 flex items-center justify-center">
                            <svg class="w-4 h-4 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />
                            </svg>
                        </div>
                        <div>
                            <h3 class="text-sm font-bold text-gray-900 dark:text-white tracking-tight">Inbox</h3>
                            <p class="text-xs text-gray-400">{{ leads.total || 0 }} lead{{ (leads.total || 0) !== 1 ?
                                's' : ''
                                }}</p>
                        </div>
                    </div>
                </div>

                <div v-if="leads.data && leads.data.length > 0" class="divide-y divide-gray-100 dark:divide-gray-700">
                    <a v-for="lead in leads.data" :key="lead.id" :href="`${basePath}/${lead.id}`"
                        class="block p-5 hover:bg-gray-50 dark:hover:bg-gray-700/50 transition-colors group"
                        :class="lead.status === 'new' ? 'bg-blue-50/40 dark:bg-blue-900/10' : ''">
                        <div class="flex items-start gap-4">
                            <!-- Avatar -->
                            <div
                                class="w-12 h-12 rounded-2xl bg-gradient-to-br from-primary-500 to-primary-700 flex items-center justify-center text-white font-bold text-sm flex-shrink-0 shadow-md">
                                {{ getInitials(lead.name) }}
                            </div>

                            <!-- Content -->
                            <div class="flex-1 min-w-0">
                                <div class="flex items-start justify-between gap-3">
                                    <div class="min-w-0">
                                        <div class="flex items-center gap-2 flex-wrap">
                                            <span class="font-bold text-gray-900 dark:text-white tracking-tight">{{
                                                lead.name
                                                }}</span>
                                            <span :class="statusClass(lead.status)"
                                                class="inline-flex items-center px-2 py-0.5 text-[10px] font-bold uppercase tracking-wide rounded-full">
                                                {{ lead.status }}
                                            </span>
                                            <!-- PHASE 12B — which Listing generated this inquiry.
                                                 Leads are Listing-attributed; Business is optional context. -->
                                            <span v-if="lead.listing"
                                                class="inline-flex items-center gap-1 px-2 py-0.5 text-[10px] font-semibold rounded-full bg-gray-100 text-gray-700 dark:bg-gray-700 dark:text-gray-200">
                                                {{ lead.listing.name }}
                                            </span>
                                        </div>
                                        <div
                                            class="flex flex-wrap items-center gap-3 mt-1.5 text-xs text-gray-500 dark:text-gray-400">
                                            <span v-if="lead.email" class="flex items-center gap-1">
                                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor"
                                                    viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round"
                                                        stroke-width="2"
                                                        d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />
                                                </svg>
                                                {{ lead.email }}
                                            </span>
                                            <span v-if="lead.phone" class="flex items-center gap-1">
                                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor"
                                                    viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round"
                                                        stroke-width="2"
                                                        d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z" />
                                                </svg>
                                                {{ lead.phone }}
                                            </span>
                                        </div>
                                    </div>
                                    <span class="text-xs text-gray-400 flex-shrink-0 whitespace-nowrap">{{
                                        formatTime(lead.created_at) }}</span>
                                </div>

                                <p v-if="lead.subject" class="font-semibold text-gray-900 dark:text-white text-sm mt-3">
                                    {{ lead.subject }}
                                </p>
                                <p class="text-sm text-gray-600 dark:text-gray-400 mt-1 line-clamp-2 leading-relaxed">
                                    {{ lead.message }}
                                </p>
                            </div>

                            <!-- Arrow -->
                            <div class="flex-shrink-0 self-center">
                                <svg class="w-5 h-5 text-gray-300 dark:text-gray-600 group-hover:text-primary-500 transition-colors"
                                    fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M9 5l7 7-7 7" />
                                </svg>
                            </div>
                        </div>
                    </a>
                </div>

                <!-- Empty state -->
                <div v-else class="p-12 text-center">
                    <div
                        class="w-20 h-20 bg-gradient-to-br from-blue-100 to-blue-200 dark:from-blue-900/40 dark:to-blue-900/20 rounded-2xl flex items-center justify-center mx-auto mb-5 shadow-sm">
                        <svg class="w-10 h-10 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />
                        </svg>
                    </div>
                    <h3 class="text-xl font-bold text-gray-900 dark:text-white tracking-tight mb-2">No leads yet</h3>
                    <p class="text-sm text-gray-500 dark:text-gray-400 max-w-md mx-auto">
                        When someone contacts one of your Listings, their messages will appear here.
                    </p>
                </div>

                <!-- Pagination -->
                <div v-if="leads.data && leads.data.length > 0"
                    class="px-6 py-4 border-t border-gray-100 dark:border-gray-700">
                    <Pagination :links="leads.links" />
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>

<script setup>
    import { reactive, computed } from 'vue';
    import { router } from '@inertiajs/vue3';
    import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
    // import Breadcrumb from '@/Components/Breadcrumb.vue';
    import Pagination from '@/Components/Pagination.vue';
    import PageHeader from '@/Components/PageHeader.vue';

    const props = defineProps({
        business: Object,
        /*
         * PHASE 12B — when owner inquiry access is Listing-scoped, `listing` is
         * set and `business` is null for a Listing with no organization. The
         * page then titles and links against the Listing.
         */
        listing: Object,
        leads: Object,
        stats: Object,
        filters: Object,
    });

    const contextTitle = computed(() => props.listing?.name || props.business?.name || 'Inquiries');

    const basePath = computed(() =>
        props.listing
            ? `/owner/listings/${props.listing.id}/leads`
            : `/owner/businesses/${props.business.id}/leads`
    );

    const filters = reactive({
        status: props.filters?.status || '',
        search: props.filters?.search || '',
    });

    const getInitials = (name) => {
        if (!name) return '?';
        return name.split(' ').map(n => n[0]).join('').toUpperCase().slice(0, 2);
    };

    const formatTime = (date) => {
        if (!date) return '';
        const now = new Date();
        const d = new Date(date);
        const diff = Math.floor((now - d) / 1000);
        if (diff < 60) return 'Just now';
        if (diff < 3600) return Math.floor(diff / 60) + 'm ago';
        if (diff < 86400) return Math.floor(diff / 3600) + 'h ago';
        if (diff < 172800) return 'Yesterday';
        return d.toLocaleDateString('en-US', { month: 'short', day: 'numeric' });
    };

    const statusClass = (status) => {
        const classes = {
            new: 'bg-blue-100 text-blue-700 dark:bg-blue-900/30 dark:text-blue-400',
            read: 'bg-gray-100 text-gray-700 dark:bg-gray-700 dark:text-gray-300',
            replied: 'bg-emerald-100 text-emerald-700 dark:bg-emerald-900/30 dark:text-emerald-400',
            archived: 'bg-amber-100 text-amber-700 dark:bg-amber-900/30 dark:text-amber-400',
        };
        return classes[status] || classes.read;
    };

    const applyFilters = () => {
        router.get(basePath.value, filters, {
            preserveState: true,
            preserveScroll: true,
        });
    };

    const resetFilters = () => {
        filters.status = '';
        filters.search = '';
        applyFilters();
    };
</script>