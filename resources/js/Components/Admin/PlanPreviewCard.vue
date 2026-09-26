<template>
    <div class="relative bg-white dark:bg-gray-800 rounded-3xl border-2 overflow-hidden"
         :class="plan.is_popular || plan.is_featured 
            ? 'border-primary-500 shadow-2xl shadow-primary-500/20' 
            : 'border-gray-200 dark:border-gray-700'">
        
        <!-- Badge -->
        <div v-if="plan.badge_text" class="absolute -top-3 left-1/2 -translate-x-1/2 z-10">
            <div class="inline-flex items-center gap-1.5 px-3 py-1 text-[10px] font-bold rounded-full shadow-lg"
                 :class="badgeClass">
                <svg class="w-3 h-3" fill="currentColor" viewBox="0 0 20 20">
                    <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z" />
                </svg>
                {{ plan.badge_text }}
            </div>
        </div>

        <!-- Content -->
        <div class="p-6">
            <h3 class="text-xl font-bold text-gray-900 dark:text-white mb-1">
                {{ plan.name || 'Plan Name' }}
            </h3>
            <p class="text-sm text-gray-500 dark:text-gray-400 min-h-[40px]">
                {{ plan.tagline || plan.description || 'Short tagline here' }}
            </p>

            <!-- Price -->
            <div class="my-5">
                <div class="flex items-baseline gap-1.5">
                    <span class="text-4xl font-black text-gray-900 dark:text-white">
                        {{ plan.price_monthly > 0 ? formatPrice(plan.price_monthly) : 'Free' }}
                    </span>
                    <span v-if="plan.price_monthly > 0" class="text-sm text-gray-500">
                        {{ plan.currency || 'XAF' }}/mo
                    </span>
                </div>
                <div v-if="plan.price_yearly > 0" class="mt-1 flex items-center gap-2">
                    <span class="text-xs text-gray-400">
                        or {{ formatPrice(plan.price_yearly) }} {{ plan.currency }}/year
                    </span>
                    <span v-if="plan.yearly_discount_percentage > 0" 
                          class="text-[10px] font-bold bg-green-100 text-green-700 px-1.5 py-0.5 rounded">
                        SAVE {{ Math.round(plan.yearly_discount_percentage) }}%
                    </span>
                </div>
            </div>

            <!-- Features -->
            <div class="border-t border-gray-200 dark:border-gray-700 pt-4 space-y-2">
                <div v-for="(feature, i) in (plan.feature_list || []).slice(0, 5)" :key="i" 
                     class="flex items-start gap-2 text-sm text-gray-600 dark:text-gray-400">
                    <svg class="w-4 h-4 text-green-500 flex-shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7" />
                    </svg>
                    <span>{{ feature }}</span>
                </div>
                <p v-if="!plan.feature_list || plan.feature_list.length === 0" 
                   class="text-xs text-gray-400 italic">
                    No features listed yet
                </p>
            </div>
        </div>
    </div>
</template>

<script setup>
import { computed } from 'vue';

const props = defineProps({
    plan: { type: Object, required: true },
});

const formatPrice = (price) => {
    if (!price && price !== 0) return '0';
    return new Intl.NumberFormat('en-US', {
        minimumFractionDigits: 0,
        maximumFractionDigits: 0,
    }).format(price);
};

const badgeClass = computed(() => {
    const classes = {
        primary: 'bg-gradient-to-r from-primary-600 to-purple-600 text-white',
        purple: 'bg-gradient-to-r from-purple-600 to-pink-600 text-white',
        gold: 'bg-gradient-to-r from-amber-500 to-orange-500 text-white',
        green: 'bg-gradient-to-r from-green-500 to-emerald-600 text-white',
        red: 'bg-gradient-to-r from-red-500 to-red-600 text-white',
    };
    return classes[props.plan.badge_color] || classes.primary;
});
</script>