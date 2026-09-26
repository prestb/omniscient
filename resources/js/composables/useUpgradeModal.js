import { ref } from 'vue';

const isOpen = ref(false);
const modalData = ref({
    feature: null,
    featureLabel: 'This feature',
    requiredPlan: 'Starter',
    benefits: [],
});

export function useUpgradeModal() {
    const show = (options = {}) => {
        modalData.value = {
            feature: options.feature || null,
            featureLabel: options.featureLabel || 'This feature',
            requiredPlan: options.requiredPlan || 'Starter',
            benefits: options.benefits || [],
        };
        isOpen.value = true;
    };

    const hide = () => {
        isOpen.value = false;
    };

    return {
        isOpen,
        modalData,
        show,
        hide,
    };
}