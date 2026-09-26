<!-- resources/js/Components/Public/InstallPrompt.vue -->
<template>
    <div v-if="showInstallPrompt" class="fixed bottom-0 left-0 right-0 bg-white dark:bg-gray-800 shadow-2xl border-t-4 border-primary-500 p-4 z-50 animate-slide-up">
        <div class="max-w-7xl mx-auto">
            <div class="flex items-center justify-between gap-4">
                <div class="flex items-center gap-3 flex-1">
                    <div class="w-12 h-12 rounded-xl shadow-md flex items-center justify-center bg-primary-100 dark:bg-primary-900/40 text-3xl">
                        🏪
                    </div>
                    <div>
                        <p class="font-semibold text-gray-900 dark:text-white text-sm sm:text-base">
                            Install Omniscient
                        </p>
                        <p class="text-xs sm:text-sm text-gray-500 dark:text-gray-400">
                            Get the full app experience with offline access
                        </p>
                    </div>
                </div>
                
                <div class="flex gap-2 flex-shrink-0">
                    <button 
                        @click="dismiss" 
                        class="px-3 py-2 text-sm text-gray-600 dark:text-gray-300 hover:text-gray-900 dark:hover:text-white font-medium rounded-lg hover:bg-gray-100 dark:hover:bg-gray-700 transition"
                    >
                        Later
                    </button>
                    <button 
                        @click="installApp" 
                        class="px-4 py-2 text-sm bg-primary-500 hover:bg-primary-600 text-white font-medium rounded-lg transition shadow-md hover:shadow-lg"
                    >
                        Install
                    </button>
                </div>
            </div>
        </div>
    </div>
</template>

<script setup>
import { ref, onMounted, onBeforeUnmount } from 'vue';

const showInstallPrompt = ref(false);
const showFloatingButton = ref(false);
let deferredPrompt = null;
let isAppInstalled = false;

const checkIfInstalled = () => {
    if (window.matchMedia('(display-mode: standalone)').matches) {
        isAppInstalled = true;
        showInstallPrompt.value = false;
        showFloatingButton.value = false;
        return true;
    }
    return false;
};

const handleInstallPrompt = (e) => {
    e.preventDefault();
    deferredPrompt = e;
    
    if (isAppInstalled) return;
    
    showInstallPrompt.value = true;
    showFloatingButton.value = true;
};

const installApp = async () => {
    if (!deferredPrompt) {
        try {
            const result = await navigator?.share?.();
            if (result) {
                showInstallPrompt.value = false;
                showFloatingButton.value = false;
            }
        } catch (err) {
            console.log('Installation not supported');
        }
        return;
    }
    
    try {
        deferredPrompt.prompt();
        const result = await deferredPrompt.userChoice;
        
        if (result.outcome === 'accepted') {
            console.log('User accepted the install prompt');
            showInstallPrompt.value = false;
            showFloatingButton.value = false;
            isAppInstalled = true;
        } else {
            console.log('User dismissed the install prompt');
            showInstallPrompt.value = false;
            setTimeout(() => {
                showFloatingButton.value = true;
            }, 5000);
        }
        
        deferredPrompt = null;
    } catch (error) {
        console.error('Installation error:', error);
        showInstallPrompt.value = false;
    }
};

const dismiss = () => {
    showInstallPrompt.value = false;
    setTimeout(() => {
        if (!isAppInstalled) {
            showFloatingButton.value = true;
        }
    }, 10000);
};

const handleAppInstalled = () => {
    isAppInstalled = true;
    showInstallPrompt.value = false;
    showFloatingButton.value = false;
    console.log('App was installed successfully');
};

const isPwaSupported = () => {
    return 'serviceWorker' in navigator && 'PushManager' in window;
};

onMounted(() => {
    if (checkIfInstalled()) return;
    
    if (!isPwaSupported()) {
        console.log('PWA not supported in this browser');
        return;
    }
    
    window.addEventListener('beforeinstallprompt', handleInstallPrompt);
    window.addEventListener('appinstalled', handleAppInstalled);
    
    const mediaQuery = window.matchMedia('(display-mode: standalone)');
    mediaQuery.addEventListener('change', (e) => {
        if (e.matches) {
            isAppInstalled = true;
            showInstallPrompt.value = false;
            showFloatingButton.value = false;
        }
    });
});

onBeforeUnmount(() => {
    window.removeEventListener('beforeinstallprompt', handleInstallPrompt);
    window.removeEventListener('appinstalled', handleAppInstalled);
});
</script>

<style scoped>
@keyframes slide-up {
    from {
        transform: translateY(100%);
        opacity: 0;
    }
    to {
        transform: translateY(0);
        opacity: 1;
    }
}

.animate-slide-up {
    animation: slide-up 0.3s ease-out;
}

@media (max-width: 480px) {
    .fixed.bottom-0 {
        padding: 12px 16px;
    }
}
</style>