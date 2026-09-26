<!-- resources/js/Components/Public/ReviewCardCompact.vue -->
<template>
    <Link :href="businessUrl" class="block group h-full">
        <div class="bg-white dark:bg-gray-800 rounded-2xl border border-gray-100 dark:border-gray-700 hover:shadow-lg hover:-translate-y-0.5 transition-all duration-300 overflow-hidden h-full p-4 sm:p-5 flex flex-col">

            <!-- Header: Avatar + Name + Stars -->
            <div class="flex items-start gap-3 mb-3">
                <div class="w-10 h-10 rounded-2xl bg-gradient-to-br from-primary-500 to-primary-700 flex items-center justify-center text-white font-bold text-sm flex-shrink-0 shadow-md">
                    {{ getInitials(review.reviewer_name) }}
                </div>
                <div class="min-w-0 flex-1">
                    <p class="text-sm font-semibold text-gray-900 dark:text-white truncate leading-tight">
                        {{ review.reviewer_name }}
                    </p>
                    <div class="flex items-center gap-1 mt-0.5">
                        <span v-for="i in 5" :key="i" class="text-xs"
                              :class="i <= review.rating ? 'text-amber-400' : 'text-gray-200 dark:text-gray-600'">
                            ★
                        </span>
                    </div>
                </div>
            </div>

            <!-- Review text (clamped) -->
            <p class="text-xs sm:text-sm text-gray-600 dark:text-gray-400 leading-relaxed line-clamp-3 flex-1">
                {{ review.content }}
            </p>

            <!-- Footer: business name + time -->
            <div class="mt-3 pt-3 border-t border-gray-100 dark:border-gray-700 flex items-center justify-between gap-2">
                <span class="text-[10px] sm:text-xs font-semibold text-primary-600 dark:text-primary-400 truncate group-hover:text-primary-700 dark:group-hover:text-primary-300 transition-colors">
                    {{ review.business?.name || 'Business' }}
                </span>
                <span class="text-[10px] sm:text-xs text-gray-400 dark:text-gray-500 flex-shrink-0">
                    {{ review.created_at }}
                </span>
            </div>
        </div>
    </Link>
</template>

<script setup>
import { computed } from 'vue';
import { Link } from '@inertiajs/vue3';

const props = defineProps({
    review: {
        type: Object,
        required: true,
    },
});

const businessUrl = computed(() => {
    const slug = props.review.business?.slug;
    return slug ? `/business/${slug}#reviews` : '/directory';
});

const getInitials = (name) => {
    if (!name) return '?';
    return name
        .split(' ')
        .map(n => n[0])
        .join('')
        .toUpperCase()
        .slice(0, 2);
};
</script>

<style scoped>
.line-clamp-3 {
    display: -webkit-box;
    -webkit-line-clamp: 3;
    -webkit-box-orient: vertical;
    overflow: hidden;
}
</style>