// resources/js/composables/useInfiniteScroll.js
import { ref, onMounted, onUnmounted } from 'vue';

export function useInfiniteScroll(loadMore, options = {}) {
    const {
        threshold = 0.1,
        rootMargin = '0px 0px 200px 0px',
        enabled = true,
    } = options;

    const loader = ref(null);
    const loading = ref(false);
    const hasMore = ref(true);
    const observer = ref(null);

    const setupObserver = () => {
        if (!enabled || !hasMore.value) return;

        observer.value = new IntersectionObserver(
            (entries) => {
                if (entries[0].isIntersecting && !loading.value && hasMore.value) {
                    loadMoreData();
                }
            },
            {
                threshold,
                rootMargin,
            }
        );

        if (loader.value) {
            observer.value.observe(loader.value);
        }
    };

    const loadMoreData = async () => {
        if (loading.value || !hasMore.value) return;

        loading.value = true;
        try {
            const result = await loadMore();
            hasMore.value = result.hasMore ?? true;
        } catch (error) {
            console.error('Error loading more:', error);
        } finally {
            loading.value = false;
        }
    };

    const reset = () => {
        hasMore.value = true;
        loading.value = false;
        if (observer.value) {
            observer.value.disconnect();
        }
        setupObserver();
    };

    onMounted(() => {
        setupObserver();
    });

    onUnmounted(() => {
        if (observer.value) {
            observer.value.disconnect();
        }
    });

    return {
        loader,
        loading,
        hasMore,
        reset,
    };
}