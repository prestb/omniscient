<template>
    <PublicLayout>
        <Head title="Pricing - Simple, Transparent Plans" />

        <!-- ============== HERO ============== -->
        <section class="relative overflow-hidden bg-gradient-to-br from-primary-700 via-primary-600 to-primary-800 dark:from-gray-900 dark:via-gray-800 dark:to-gray-900 text-white pt-16 pb-16 sm:pt-20 sm:pb-20">
            <div class="absolute inset-0 opacity-[0.06]"
                 style="background-image: radial-gradient(circle, white 1px, transparent 1px); background-size: 22px 22px;"></div>
            <div class="absolute -top-40 -right-40 w-96 h-96 bg-white/10 rounded-full blur-3xl"></div>
            <div class="absolute -bottom-40 -left-40 w-96 h-96 bg-purple-400/20 rounded-full blur-3xl"></div>

            <div class="relative max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="text-center max-w-3xl mx-auto">
                    <span class="inline-flex items-center gap-2 px-3 py-1 rounded-full bg-white/10 border border-white/20 text-xs font-medium tracking-wide uppercase text-primary-100 mb-6 backdrop-blur-sm">
                        <span class="w-1.5 h-1.5 rounded-full bg-emerald-400 animate-pulse"></span>
                        Simple, transparent pricing
                    </span>

                    <h1 class="text-4xl sm:text-5xl lg:text-6xl font-bold tracking-tight leading-tight mb-4">
                        Choose the plan that
                        <span class="bg-gradient-to-r from-amber-300 to-yellow-200 bg-clip-text text-transparent"> grows with you</span>
                    </h1>

                    <p class="text-base sm:text-lg text-primary-100 mb-8 max-w-2xl mx-auto">
                        Start free and scale as you grow. No hidden fees, no surprises. Cancel anytime.
                    </p>

                    <!-- Billing toggle -->
                    <div class="inline-flex items-center gap-1 p-1.5 bg-white/10 backdrop-blur-sm rounded-full border border-white/20 shadow-sm">
                        <button @click="billingPeriod = 'monthly'"
                                class="px-5 sm:px-6 py-2 rounded-full text-sm font-semibold transition-all duration-300"
                                :class="billingPeriod === 'monthly'
                                    ? 'bg-white text-primary-700 shadow-lg'
                                    : 'text-white/80 hover:text-white'">
                            Monthly
                        </button>
                        <button @click="billingPeriod = 'yearly'"
                                class="px-5 sm:px-6 py-2 rounded-full text-sm font-semibold transition-all duration-300 flex items-center gap-1.5"
                                :class="billingPeriod === 'yearly'
                                    ? 'bg-white text-primary-700 shadow-lg'
                                    : 'text-white/80 hover:text-white'">
                            Yearly
                            <span class="px-1.5 py-0.5 text-[10px] font-bold bg-emerald-500 text-white rounded-full">
                                2 MO FREE
                            </span>
                        </button>
                    </div>
                </div>
            </div>
        </section>

        <!-- ============== PRICING CARDS ============== -->
        <section class="py-12 sm:py-16 bg-gray-50 dark:bg-gray-900">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-4 gap-5 lg:gap-6">
                    <div v-for="plan in plans" :key="plan.id"
                         class="relative group"
                         :class="{ 'xl:-mt-4': plan.is_popular || plan.is_featured }">

                        <!-- Badge -->
                        <div v-if="plan.badge_text" class="absolute -top-3 left-1/2 -translate-x-1/2 z-10">
                            <div class="inline-flex items-center gap-1.5 px-3 py-1 text-[10px] sm:text-xs font-bold uppercase tracking-wide rounded-full shadow-lg"
                                 :class="badgeClass(plan.badge_color)">
                                <svg class="w-3 h-3" fill="currentColor" viewBox="0 0 20 20">
                                    <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.519 4.674a1 1 0 00.95.69h4.915c.969 0 1.371 1.24.588 1.81l-3.976 2.888a1 1 0 00-.363 1.118l1.518 4.674c.3.922-.755 1.688-1.538 1.118l-3.976-2.888a1 1 0 00-1.176 0l-3.976 2.888c-.783.57-1.838-.197-1.538-1.118l1.518-4.674a1 1 0 00-.363-1.118l-3.976-2.888c-.784-.57-.38-1.81.588-1.81h4.914a1 1 0 00.951-.69l1.519-4.674z" />
                                </svg>
                                {{ plan.badge_text }}
                            </div>
                        </div>

                        <!-- Card -->
                        <div class="relative h-full bg-white dark:bg-gray-800 rounded-3xl border-2 transition-all duration-300 overflow-hidden"
                             :class="[
                                 plan.is_popular || plan.is_featured
                                     ? 'border-primary-500 dark:border-primary-500 shadow-2xl shadow-primary-500/20 xl:scale-105'
                                     : 'border-gray-200 dark:border-gray-700 hover:border-primary-300 dark:hover:border-primary-700 hover:shadow-xl'
                             ]">

                            <div v-if="plan.is_popular || plan.is_featured"
                                 class="absolute inset-0 bg-gradient-to-br from-primary-50/50 to-purple-50/50 dark:from-primary-950/20 dark:to-purple-950/20 pointer-events-none"></div>

                            <div class="relative p-6 sm:p-7 flex flex-col h-full">

                                <!-- Header -->
                                <div class="mb-5">
                                    <h3 class="text-xl sm:text-2xl font-bold text-gray-900 dark:text-white tracking-tight mb-1.5">
                                        {{ plan.name }}
                                    </h3>
                                    <p class="text-sm text-gray-500 dark:text-gray-400 min-h-[40px] leading-relaxed">
                                        {{ plan.tagline || plan.description || 'Perfect for getting started' }}
                                    </p>
                                </div>

                                <!-- Price -->
                                <div class="mb-5">
                                    <div class="flex items-baseline gap-1.5 flex-wrap">
                                        <span class="text-4xl sm:text-5xl font-bold text-gray-900 dark:text-white tracking-tight">
                                            {{ plan.is_free ? 'Free' : formatPrice(getPrice(plan)) }}
                                        </span>
                                        <span v-if="!plan.is_free" class="text-sm text-gray-500 dark:text-gray-400 font-medium">
                                            {{ plan.currency }}/{{ billingPeriod === 'monthly' ? 'mo' : 'yr' }}
                                        </span>
                                    </div>

                                    <div v-if="!plan.is_free && billingPeriod === 'yearly'" class="mt-2 flex items-center gap-2 flex-wrap">
                                        <span class="text-xs text-gray-400 line-through">
                                            {{ formatPrice(plan.price_monthly * 12) }} {{ plan.currency }}
                                        </span>
                                        <span class="text-xs font-bold text-emerald-600 dark:text-emerald-400 bg-emerald-50 dark:bg-emerald-900/30 px-2 py-0.5 rounded-full uppercase tracking-wide">
                                            Save {{ Math.round(plan.yearly_discount_percentage) }}%
                                        </span>
                                    </div>

                                    <div v-if="plan.trial_days > 0" class="mt-2">
                                        <span class="text-xs font-semibold text-primary-600 dark:text-primary-400">
                                            🎁 {{ plan.trial_days }}-day free trial
                                        </span>
                                    </div>
                                </div>

                                <!-- CTA -->
                                <a :href="getCtaUrl(plan)"
                                   class="btn-shine group/btn relative w-full inline-flex items-center justify-center gap-2 px-5 py-3.5 rounded-xl font-semibold transition-all duration-300 mb-5 text-sm"
                                   :class="[
                                       plan.is_popular || plan.is_featured
                                           ? 'bg-gradient-to-r from-primary-600 to-primary-700 text-white hover:from-primary-700 hover:to-primary-800 shadow-lg shadow-primary-500/25 hover:shadow-primary-500/40 hover:-translate-y-0.5'
                                           : 'bg-gray-900 dark:bg-white text-white dark:text-gray-900 hover:bg-gray-800 dark:hover:bg-gray-100'
                                   ]">
                                    {{ getCtaText(plan) }}
                                    <svg class="w-4 h-4 transition-transform group-hover/btn:translate-x-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7l5 5m0 0l-5 5m5-5H6" />
                                    </svg>
                                </a>

                                <!-- Divider -->
                                <div class="relative mb-5">
                                    <div class="absolute inset-0 flex items-center">
                                        <div class="w-full border-t border-gray-200 dark:border-gray-700"></div>
                                    </div>
                                    <div class="relative flex justify-center">
                                        <span class="px-3 bg-white dark:bg-gray-800 text-[10px] font-bold uppercase tracking-widest text-gray-400 dark:text-gray-500">
                                            What's included
                                        </span>
                                    </div>
                                </div>

                                <!-- Features -->
                                <ul class="space-y-2.5 flex-1">
                                    <li v-for="(feature, i) in plan.feature_list" :key="i" class="flex items-start gap-2.5">
                                        <div class="flex-shrink-0 w-5 h-5 rounded-full flex items-center justify-center mt-0.5"
                                             :class="plan.is_popular || plan.is_featured
                                                 ? 'bg-primary-100 dark:bg-primary-900/50'
                                                 : 'bg-emerald-100 dark:bg-emerald-900/40'">
                                            <svg class="w-3 h-3"
                                                 :class="plan.is_popular || plan.is_featured
                                                     ? 'text-primary-600 dark:text-primary-400'
                                                     : 'text-emerald-600 dark:text-emerald-400'"
                                                 fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7" />
                                            </svg>
                                        </div>
                                        <span class="text-sm text-gray-600 dark:text-gray-300 leading-relaxed">
                                            {{ feature }}
                                        </span>
                                    </li>
                                </ul>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Trust line -->
                <div class="mt-10 text-center">
                    <p class="text-sm text-gray-500 dark:text-gray-400 inline-flex items-center gap-2 font-medium">
                        <svg class="w-4 h-4 text-emerald-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z" />
                        </svg>
                        30-day money-back guarantee • Cancel anytime • No hidden fees
                    </p>
                </div>
            </div>
        </section>

        <!-- ============== FAQ ============== -->
        <section class="py-16 sm:py-20 bg-white dark:bg-gray-900">
            <div class="max-w-3xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="text-center mb-12">
                    <span class="text-[10px] font-bold uppercase tracking-widest text-primary-600 dark:text-primary-400 mb-3 block">FAQ</span>
                    <h2 class="text-3xl sm:text-4xl font-bold text-gray-900 dark:text-white tracking-tight mb-3">
                        Frequently asked questions
                    </h2>
                    <p class="text-gray-600 dark:text-gray-400">
                        Everything you need to know about our plans
                    </p>
                </div>

                <div class="space-y-3">
                    <div v-for="(faq, index) in faqs" :key="index"
                         class="bg-gray-50 dark:bg-gray-800/50 rounded-2xl border border-gray-200 dark:border-gray-700 overflow-hidden transition-all">
                        <button @click="toggleFaq(index)"
                                class="w-full flex items-center justify-between gap-4 px-5 sm:px-6 py-4 sm:py-5 text-left hover:bg-gray-100 dark:hover:bg-gray-700/30 transition-colors">
                            <span class="text-sm sm:text-base font-bold text-gray-900 dark:text-white tracking-tight">
                                {{ faq.question }}
                            </span>
                            <div class="flex-shrink-0 w-8 h-8 rounded-full bg-white dark:bg-gray-700 flex items-center justify-center transition-transform duration-200"
                                 :class="{ 'rotate-180': openFaq === index }">
                                <svg class="w-4 h-4 text-gray-600 dark:text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                                </svg>
                            </div>
                        </button>
                        <Transition
                            enter-active-class="transition duration-200 ease-out"
                            enter-from-class="opacity-0 -translate-y-2"
                            enter-to-class="opacity-100 translate-y-0"
                            leave-active-class="transition duration-150 ease-in"
                            leave-from-class="opacity-100 translate-y-0"
                            leave-to-class="opacity-0 -translate-y-2">
                            <div v-show="openFaq === index" class="px-5 sm:px-6 pb-5 text-sm text-gray-600 dark:text-gray-400 leading-relaxed">
                                {{ faq.answer }}
                            </div>
                        </Transition>
                    </div>
                </div>
            </div>
        </section>

        <!-- ============== CTA ============== -->
        <section class="relative py-16 sm:py-20 overflow-hidden bg-gradient-to-br from-primary-600 via-primary-700 to-purple-700">
            <div class="absolute inset-0 opacity-[0.06]"
                 style="background-image: radial-gradient(circle, white 1px, transparent 1px); background-size: 22px 22px;"></div>
            <div class="absolute -top-40 -right-40 w-96 h-96 bg-white/10 rounded-full blur-3xl"></div>
            <div class="absolute -bottom-40 -left-40 w-96 h-96 bg-white/10 rounded-full blur-3xl"></div>

            <div class="relative max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 text-center">
                <h2 class="text-3xl sm:text-4xl lg:text-5xl font-bold tracking-tight text-white mb-4">
                    Ready to grow your business?
                </h2>
                <p class="text-base sm:text-lg text-white/80 mb-8 max-w-2xl mx-auto">
                    Join thousands of businesses already thriving on Omniscient. Start free today — no credit card required.
                </p>
                <div class="flex flex-col sm:flex-row items-center justify-center gap-4">
                    <a href="/register"
                       class="group inline-flex items-center gap-2 px-8 py-4 bg-white text-primary-700 rounded-xl font-bold shadow-2xl hover:bg-gray-50 transition-all hover:-translate-y-0.5">
                        Get Started Free
                        <svg class="w-5 h-5 transition-transform group-hover:translate-x-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7l5 5m0 0l-5 5m5-5H6" />
                        </svg>
                    </a>
                    <a href="/contact"
                       class="inline-flex items-center gap-2 px-8 py-4 bg-white/10 backdrop-blur-sm text-white rounded-xl font-bold border border-white/20 hover:bg-white/20 transition-all">
                        Talk to Sales
                    </a>
                </div>
            </div>
        </section>
    </PublicLayout>
</template>

<script setup>
import { ref } from 'vue';
import { Head } from '@inertiajs/vue3';
import PublicLayout from '@/Layouts/PublicLayout.vue';

const props = defineProps({
    plans: {
        type: Array,
        required: true,
        default: () => [],
    },
});

const billingPeriod = ref('monthly');
const openFaq = ref(null);

const formatPrice = (price) => {
    if (!price) return '0';
    return new Intl.NumberFormat('en-US', {
        minimumFractionDigits: 0,
        maximumFractionDigits: 0,
    }).format(price);
};

const getPrice = (plan) => {
    return billingPeriod.value === 'monthly' ? plan.price_monthly : plan.price_yearly;
};

const getCtaText = (plan) => {
    if (plan.is_free) return 'Start Free';
    if (plan.trial_days > 0) return `Start ${plan.trial_days}-day Trial`;
    return 'Get Started';
};

const getCtaUrl = (plan) => {
    return `/register?plan=${plan.slug}&billing=${billingPeriod.value}`;
};

const badgeClass = (color) => {
    const classes = {
        primary: 'bg-gradient-to-r from-primary-600 to-purple-600 text-white shadow-primary-500/30',
        purple: 'bg-gradient-to-r from-purple-600 to-pink-600 text-white shadow-purple-500/30',
        gold: 'bg-gradient-to-r from-amber-500 to-orange-500 text-white shadow-amber-500/30',
        green: 'bg-gradient-to-r from-green-500 to-emerald-600 text-white shadow-green-500/30',
    };
    return classes[color] || classes.primary;
};

const toggleFaq = (index) => {
    openFaq.value = openFaq.value === index ? null : index;
};

const faqs = [
    {
        question: 'Can I change my plan later?',
        answer: 'Absolutely! You can upgrade or downgrade your plan at any time. When you upgrade, we prorate the difference. When you downgrade, the change takes effect at your next billing cycle.',
    },
    {
        question: 'What payment methods do you accept?',
        answer: 'We accept MTN Mobile Money, Orange Money, and major credit/debit cards through our secure payment partner Fapshi. All payments are processed securely.',
    },
    {
        question: 'Is there a free trial?',
        answer: 'Yes! All paid plans come with a 14-day free trial. No credit card required to start. You can explore all features and decide if it\'s right for you.',
    },
    {
        question: 'What happens if I cancel?',
        answer: 'You can cancel at any time. Your subscription remains active until the end of your current billing period, then it won\'t renew. You keep all your data.',
    },
    {
        question: 'Do you offer refunds?',
        answer: 'We offer a 30-day money-back guarantee on all yearly plans. If you\'re not satisfied, contact our support team within 30 days for a full refund.',
    },
    {
        question: 'Can I have multiple businesses?',
        answer: 'Yes! Depending on your plan, you can manage multiple businesses, each with their own branches, services, photos, and reviews. Premium plan offers unlimited businesses.',
    },
];
</script>

<style scoped>
.btn-shine {
    position: relative;
    overflow: hidden;
}
.btn-shine::before {
    content: '';
    position: absolute;
    top: 0;
    left: -100%;
    width: 100%;
    height: 100%;
    background: linear-gradient(90deg, transparent, rgba(255,255,255,0.3), transparent);
    transition: left 0.6s;
}
.btn-shine:hover::before {
    left: 100%;
}
</style>