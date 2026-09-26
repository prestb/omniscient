<!-- resources/js/Components/Public/RatingBadge.vue -->
<template>
    <span 
        class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-full text-xs font-semibold transition-all duration-300 hover:scale-105"
        :class="badgeClass"
    >
        <!-- Star Icon -->
        <svg 
            class="w-3.5 h-3.5" 
            :class="iconClass" 
            fill="currentColor" 
            viewBox="0 0 20 20"
        >
            <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z" />
        </svg>
        
        <!-- Rating Value -->
        <span class="font-bold">{{ ratingValue }}</span>
        
        <!-- Label -->
        <span class="opacity-80">{{ ratingLabel }}</span>
        
        <!-- Review Count (optional) -->
        <span v-if="reviewCount" class="text-[10px] opacity-60 ml-0.5">
            ({{ reviewCount }})
        </span>
    </span>
</template>

<script setup>
import { computed } from 'vue';

const props = defineProps({
    rating: {
        type: [Number, String],
        required: true
    },
    reviewCount: {
        type: [Number, String],
        default: null
    },
    size: {
        type: String,
        default: 'md', // sm, md, lg
        validator: (value) => ['sm', 'md', 'lg'].includes(value)
    },
    showLabel: {
        type: Boolean,
        default: true
    },
    showIcon: {
        type: Boolean,
        default: true
    }
});

// ============== Computed Values ==============
const ratingValue = computed(() => {
    return Number(props.rating) || 0;
});

const ratingLabel = computed(() => {
    const rating = ratingValue.value;
    if (rating >= 4.8) return 'Outstanding';
    if (rating >= 4.5) return 'Excellent';
    if (rating >= 4.0) return 'Very Good';
    if (rating >= 3.5) return 'Good';
    if (rating >= 3.0) return 'Pleasant';
    if (rating >= 2.5) return 'Average';
    if (rating >= 2.0) return 'Fair';
    if (rating >= 1.5) return 'Below Average';
    return 'Poor';
});

// ============== Badge Classes ==============
const badgeClass = computed(() => {
    const rating = ratingValue.value;
    const baseClasses = 'inline-flex items-center gap-1.5 rounded-full font-semibold transition-all duration-300 hover:scale-105';
    
    let sizeClasses;
    switch (props.size) {
        case 'sm':
            sizeClasses = 'px-2 py-1 text-[10px]';
            break;
        case 'lg':
            sizeClasses = 'px-4 py-2 text-sm';
            break;
        default: // md
            sizeClasses = 'px-3 py-1.5 text-xs';
    }
    
    let colorClasses;
    if (rating >= 4.8) {
        colorClasses = 'bg-gradient-to-r from-purple-100 to-purple-50 dark:from-purple-900/30 dark:to-purple-900/10 text-purple-800 dark:text-purple-300 border border-purple-200 dark:border-purple-800 shadow-sm shadow-purple-100 dark:shadow-none';
    } else if (rating >= 4.5) {
        colorClasses = 'bg-gradient-to-r from-green-100 to-green-50 dark:from-green-900/30 dark:to-green-900/10 text-green-800 dark:text-green-300 border border-green-200 dark:border-green-800 shadow-sm shadow-green-100 dark:shadow-none';
    } else if (rating >= 4.0) {
        colorClasses = 'bg-gradient-to-r from-emerald-100 to-emerald-50 dark:from-emerald-900/30 dark:to-emerald-900/10 text-emerald-800 dark:text-emerald-300 border border-emerald-200 dark:border-emerald-800 shadow-sm shadow-emerald-100 dark:shadow-none';
    } else if (rating >= 3.5) {
        colorClasses = 'bg-gradient-to-r from-blue-100 to-blue-50 dark:from-blue-900/30 dark:to-blue-900/10 text-blue-800 dark:text-blue-300 border border-blue-200 dark:border-blue-800 shadow-sm shadow-blue-100 dark:shadow-none';
    } else if (rating >= 3.0) {
        colorClasses = 'bg-gradient-to-r from-cyan-100 to-cyan-50 dark:from-cyan-900/30 dark:to-cyan-900/10 text-cyan-800 dark:text-cyan-300 border border-cyan-200 dark:border-cyan-800 shadow-sm shadow-cyan-100 dark:shadow-none';
    } else if (rating >= 2.5) {
        colorClasses = 'bg-gradient-to-r from-yellow-100 to-yellow-50 dark:from-yellow-900/30 dark:to-yellow-900/10 text-yellow-800 dark:text-yellow-300 border border-yellow-200 dark:border-yellow-800 shadow-sm shadow-yellow-100 dark:shadow-none';
    } else if (rating >= 2.0) {
        colorClasses = 'bg-gradient-to-r from-orange-100 to-orange-50 dark:from-orange-900/30 dark:to-orange-900/10 text-orange-800 dark:text-orange-300 border border-orange-200 dark:border-orange-800 shadow-sm shadow-orange-100 dark:shadow-none';
    } else if (rating >= 1.5) {
        colorClasses = 'bg-gradient-to-r from-amber-100 to-amber-50 dark:from-amber-900/30 dark:to-amber-900/10 text-amber-800 dark:text-amber-300 border border-amber-200 dark:border-amber-800 shadow-sm shadow-amber-100 dark:shadow-none';
    } else {
        colorClasses = 'bg-gradient-to-r from-red-100 to-red-50 dark:from-red-900/30 dark:to-red-900/10 text-red-800 dark:text-red-300 border border-red-200 dark:border-red-800 shadow-sm shadow-red-100 dark:shadow-none';
    }
    
    return `${baseClasses} ${sizeClasses} ${colorClasses}`;
});

// ============== Icon Class ==============
const iconClass = computed(() => {
    const rating = ratingValue.value;
    if (rating >= 4.8) return 'text-purple-500 dark:text-purple-400';
    if (rating >= 4.5) return 'text-green-500 dark:text-green-400';
    if (rating >= 4.0) return 'text-emerald-500 dark:text-emerald-400';
    if (rating >= 3.5) return 'text-blue-500 dark:text-blue-400';
    if (rating >= 3.0) return 'text-cyan-500 dark:text-cyan-400';
    if (rating >= 2.5) return 'text-yellow-500 dark:text-yellow-400';
    if (rating >= 2.0) return 'text-orange-500 dark:text-orange-400';
    if (rating >= 1.5) return 'text-amber-500 dark:text-amber-400';
    return 'text-red-500 dark:text-red-400';
});
</script>

<style scoped>
/* Additional hover effects */
.badge-rating {
    transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
}

.badge-rating:hover {
    transform: scale(1.05) translateY(-1px);
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
}
</style>