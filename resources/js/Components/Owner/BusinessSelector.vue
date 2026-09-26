<template>
  <div v-if="businesses.length > 1" class="relative">
    <select
      :value="selectedId"
      @change="onChange"
      class="appearance-none w-full sm:w-auto pl-10 pr-10 py-2.5 bg-white dark:bg-gray-800 border border-gray-300 dark:border-gray-600 rounded-xl text-sm font-medium text-gray-900 dark:text-white focus:ring-2 focus:ring-primary-500 cursor-pointer"
    >
      <option v-for="biz in businesses" :key="biz.id" :value="biz.id">
        {{ biz.name }}{{ biz.status !== 'published' ? ` (${formatStatus(biz.status)})` : '' }}
    </option>
    </select>
    <div class="absolute left-3 top-1/2 -translate-y-1/2 pointer-events-none">
      <svg
        class="w-4 h-4 text-gray-400"
        fill="none"
        stroke="currentColor"
        viewBox="0 0 24 24"
      >
        <path
          stroke-linecap="round"
          stroke-linejoin="round"
          stroke-width="2"
          d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"
        />
      </svg>
    </div>
    <div class="absolute right-3 top-1/2 -translate-y-1/2 pointer-events-none">
      <svg
        class="w-4 h-4 text-gray-400"
        fill="none"
        stroke="currentColor"
        viewBox="0 0 24 24"
      >
        <path
          stroke-linecap="round"
          stroke-linejoin="round"
          stroke-width="2"
          d="M19 9l-7 7-7-7"
        />
      </svg>
    </div>
  </div>
</template>

<script setup>
import { router } from "@inertiajs/vue3";

const props = defineProps({
  businesses: { type: Array, required: true },
  selectedId: { type: [Number, String], required: true },
});

const onChange = (e) => {
    const newId = e.target.value;
    const url = new URL(window.location.href);
    url.searchParams.set('business', newId);
    
    // ✅ Reload the full page data (not just specific props)
    // This is safer because different pages need different props
    router.visit(url.pathname + url.search, {
        preserveState: true,
        preserveScroll: true,
        // ✅ Don't use 'only' — reload everything the page needs
    });
};

const formatStatus = (status) => {
    const labels = {
        draft: 'Draft',
        submitted: 'Pending',
        approved: 'Approved',
        published: 'Live',
        inactive: 'Hidden',
        rejected: 'Rejected',
        suspended: 'Suspended',
    };
    return labels[status] || status;
};
</script>
