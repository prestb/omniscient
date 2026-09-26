import { ref } from 'vue';

// ============== MODULE-LEVEL STATE ==============
// This state is SHARED across all components that import useConfirm
const isOpen = ref(false);
const options = ref({
    title: 'Are you sure?',
    message: '',
    confirmText: 'Confirm',
    cancelText: 'Cancel',
    variant: 'primary',
    requireTyping: false,
    typingText: '',
});

// Store the resolve function so ConfirmModal can call it
let currentResolve = null;

// ============== DEFAULT OPTIONS ==============
const defaultOptions = {
    title: 'Are you sure?',
    message: '',
    confirmText: 'Confirm',
    cancelText: 'Cancel',
    variant: 'primary',
    requireTyping: false,
    typingText: '',
};

// ============== MAIN COMPOSABLE ==============
export function useConfirm() {
    /**
     * Show confirmation dialog
     * @returns {Promise<boolean>} - true if confirmed, false if cancelled
     */
    const confirm = (config = {}) => {
        return new Promise((resolve) => {
            // Merge config with defaults
            options.value = { ...defaultOptions, ...config };

            // Store resolve — but wrap it so we can track state
            currentResolve = resolve;

            // Open modal
            isOpen.value = true;
        });
    };

    /**
     * Called by ConfirmModal when user confirms
     */
    const handleConfirm = () => {
        isOpen.value = false;
        const resolver = currentResolve;
        currentResolve = null;
        if (resolver) {
            resolver(true);
        }
    };

    /**
     * Called by ConfirmModal when user cancels
     */
    const handleCancel = () => {
        isOpen.value = false;
        const resolver = currentResolve;
        currentResolve = null;
        if (resolver) {
            resolver(false);
        }
    };

    // Shortcuts
    const confirmDanger = (config = {}) => confirm({ ...config, variant: 'danger' });
    const confirmDelete = (config = {}) => confirm({
        variant: 'danger',
        title: 'Delete Confirmation',
        confirmText: 'Delete',
        ...config,
    });
    const confirmWarning = (config = {}) => confirm({ ...config, variant: 'warning' });
    const confirmSuccess = (config = {}) => confirm({ ...config, variant: 'success' });

    return {
        isOpen,
        options,
        confirm,
        confirmDanger,
        confirmDelete,
        confirmWarning,
        confirmSuccess,
        handleConfirm,
        handleCancel,
    };
}

// ============== DIRECT EXPORTS FOR MODAL ==============
// These can be imported directly in the modal component to avoid any
// composable re-instantiation issues
export const confirmState = {
    isOpen,
    options,
};

export const confirmActions = {
    confirm: (config = {}) => {
        return new Promise((resolve) => {
            console.log('[useConfirm] New confirm called with:', config);
            options.value = { ...defaultOptions, ...config };
            currentResolve = resolve;
            isOpen.value = true;
        });
    },
    handleConfirm: () => {
        console.log('[useConfirm] handleConfirm called. Resolver exists?', !!currentResolve);
        isOpen.value = false;
        const resolver = currentResolve;
        currentResolve = null;
        if (resolver) resolver(true);
    },
    handleCancel: () => {
        isOpen.value = false;
        const resolver = currentResolve;
        currentResolve = null;
        if (resolver) resolver(false);
    },
};