// resources/js/composables/useDarkMode.js
import { ref, watch, onMounted } from 'vue';

const isDark = ref(false);

// Load preference from localStorage
const loadPreference = () => {
    const stored = localStorage.getItem('dark_mode');
    if (stored !== null) {
        isDark.value = stored === 'true';
    } else {
        // Check system preference
        isDark.value = window.matchMedia('(prefers-color-scheme: dark)').matches;
    }
    applyTheme();
};

// Apply theme to document
const applyTheme = () => {
    if (isDark.value) {
        document.documentElement.classList.add('dark');
    } else {
        document.documentElement.classList.remove('dark');
    }
    localStorage.setItem('dark_mode', isDark.value.toString());
};

// Toggle dark mode
const toggleDarkMode = () => {
    isDark.value = !isDark.value;
    applyTheme();
};

// Watch for changes
watch(isDark, () => {
    applyTheme();
});

// Setup on mount
export function useDarkMode() {
    onMounted(() => {
        loadPreference();
        // Watch system preference changes
        const mediaQuery = window.matchMedia('(prefers-color-scheme: dark)');
        const handler = (e) => {
            if (localStorage.getItem('dark_mode') === null) {
                isDark.value = e.matches;
                applyTheme();
            }
        };
        mediaQuery.addEventListener('change', handler);
    });

    return {
        isDark,
        toggleDarkMode,
    };
}