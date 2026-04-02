import { computed, ref } from 'vue';

export const THEME_STORAGE_KEY = 'qc-theme-preference';
export const DEFAULT_THEME = 'dark';
const VALID_THEMES = new Set(['dark', 'light']);

const currentTheme = ref(DEFAULT_THEME);

const normalizeTheme = (theme) => (VALID_THEMES.has(theme) ? theme : DEFAULT_THEME);

export const readStoredTheme = () => {
    if (typeof window === 'undefined') {
        return DEFAULT_THEME;
    }

    return normalizeTheme(window.localStorage.getItem(THEME_STORAGE_KEY) || DEFAULT_THEME);
};

export const applyThemePreference = (theme) => {
    const normalizedTheme = normalizeTheme(theme);

    currentTheme.value = normalizedTheme;

    if (typeof document !== 'undefined') {
        document.documentElement.dataset.theme = normalizedTheme;
        document.documentElement.style.colorScheme = normalizedTheme;
    }

    if (typeof window !== 'undefined') {
        window.localStorage.setItem(THEME_STORAGE_KEY, normalizedTheme);
    }

    return normalizedTheme;
};

export const initializeThemePreference = () => applyThemePreference(readStoredTheme());

export const useTheme = () => ({
    currentTheme,
    isLightTheme: computed(() => currentTheme.value === 'light'),
    setTheme: (theme) => applyThemePreference(theme),
    toggleTheme: () => applyThemePreference(currentTheme.value === 'light' ? 'dark' : 'light'),
    syncThemeFromDocument: () => {
        if (typeof document === 'undefined') {
            return currentTheme.value;
        }

        return applyThemePreference(document.documentElement.dataset.theme || readStoredTheme());
    },
});
