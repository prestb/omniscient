<template>
    <div class="flex items-center gap-3">
        <!-- Status Badge -->
        <span 
            :class="getBadgeClass(business.status)"
            class="inline-flex items-center gap-1.5 px-3 py-1 text-xs font-bold rounded-full uppercase tracking-wider"
        >
            <span :class="getDotClass(business.status)" class="w-1.5 h-1.5 rounded-full"></span>
            {{ getStatusLabel(business.status) }}
        </span>

        <!-- Toggle Button -->
        <button
            v-if="canToggle"
            @click="handleToggle"
            :disabled="processing"
            class="relative inline-flex items-center h-6 rounded-full w-11 transition-colors focus:outline-none focus:ring-2 focus:ring-primary-500 focus:ring-offset-2 disabled:opacity-50"
            :class="isLive ? 'bg-green-500' : 'bg-gray-300 dark:bg-gray-600'"
            :title="isLive ? 'Hide from public' : 'Make live'"
        >
            <span
                class="inline-block h-4 w-4 transform rounded-full bg-white shadow transition-transform"
                :class="isLive ? 'translate-x-6' : 'translate-x-1'"
            />
        </button>
    </div>
</template>

<script setup>
import { computed, ref } from 'vue';
import { router } from '@inertiajs/vue3';
import { useConfirm } from '@/composables/useConfirm';

const props = defineProps({
    business: {
        type: Object,
        required: true,
    },
    showToggle: {
        type: Boolean,
        default: true,
    },
});

const { confirm } = useConfirm();
const processing = ref(false);

const isLive = computed(() => props.business.status === 'published');

const canToggle = computed(() => {
    if (!props.showToggle) return false;
    return ['published', 'inactive'].includes(props.business.status);
});

const handleToggle = async () => {
    if (processing.value) return;

    const currentlyLive = props.business.status === 'published';
    const businessName = props.business.name;

    const confirmed = await confirm(currentlyLive ? {
        title: 'Hide Business?',
        message: `"${businessName}" will be hidden from the public directory.`,
        confirmText: 'Hide Business',
        variant: 'warning',
    } : {
        title: 'Make Live?',
        message: `"${businessName}" will appear in the public directory immediately.`,
        confirmText: 'Make Live',
        variant: 'success',
    });

    if (!confirmed) return;

    processing.value = true;

    // ✅ Generate unique request ID to dedupe on backend
    const requestId = `${Date.now()}-${Math.random().toString(36).substr(2, 9)}`;

    router.post(`/owner/businesses/${props.business.id}/toggle-active`, {
        request_id: requestId,
    }, {
        preserveScroll: true,
        onFinish: () => {
            processing.value = false;
        },
    });
};

// ============== UI HELPERS ==============
const getStatusLabel = (status) => {
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

const getBadgeClass = (status) => {
    const classes = {
        draft: 'bg-gray-100 text-gray-700 dark:bg-gray-700 dark:text-gray-300',
        submitted: 'bg-yellow-100 text-yellow-700 dark:bg-yellow-900/30 dark:text-yellow-400',
        approved: 'bg-blue-100 text-blue-700 dark:bg-blue-900/30 dark:text-blue-400',
        published: 'bg-green-100 text-green-700 dark:bg-green-900/30 dark:text-green-400',
        inactive: 'bg-amber-100 text-amber-700 dark:bg-amber-900/30 dark:text-amber-400',
        rejected: 'bg-red-100 text-red-700 dark:bg-red-900/30 dark:text-red-400',
        suspended: 'bg-red-100 text-red-700 dark:bg-red-900/30 dark:text-red-400',
    };
    return classes[status] || classes.draft;
};

const getDotClass = (status) => {
    const classes = {
        draft: 'bg-gray-400',
        submitted: 'bg-yellow-500',
        approved: 'bg-blue-500',
        published: 'bg-green-500 animate-pulse',
        inactive: 'bg-amber-500',
        rejected: 'bg-red-500',
        suspended: 'bg-red-500',
    };
    return classes[status] || classes.draft;
};
</script>