/**
 * useTheme — Theme switching composable for Bendaharaku.
 *
 * Manages dark/light/system theme preference.
 * Applies `.light` class on <html> for light mode.
 * Dark mode is the default (no class needed — matches @theme in app.css).
 *
 * Usage:
 *   import { initTheme } from '@/Composables/useTheme.js';
 *   // In layout's onMounted:
 *   initTheme(userThemePreference);  // 'dark' | 'light' | 'system'
 */

import { ref, watch } from 'vue';

const current = ref('system'); // 'dark' | 'light' | 'system'
let mediaQuery = null;
let mediaHandler = null;

/**
 * Apply the resolved theme to the DOM.
 * - 'light' → adds `.light` to <html>
 * - 'dark'  → removes `.light` from <html>
 */
function applyTheme(resolved) {
    const html = document.documentElement;
    if (resolved === 'light') {
        html.classList.add('light');
    } else {
        html.classList.remove('light');
    }
}

/**
 * Resolve the actual theme from the preference.
 * 'system' checks prefers-color-scheme media query.
 */
function resolveTheme(preference) {
    if (preference === 'system') {
        return window.matchMedia('(prefers-color-scheme: light)').matches
            ? 'light'
            : 'dark';
    }
    return preference;
}

/**
 * Initialize the theme system.
 * Call this once in your root layout's onMounted.
 *
 * @param {string} preference - 'dark' | 'light' | 'system'
 */
export function initTheme(preference = 'system') {
    current.value = preference;
    applyTheme(resolveTheme(preference));

    // Clean up previous listener if any
    if (mediaQuery && mediaHandler) {
        mediaQuery.removeEventListener('change', mediaHandler);
    }

    // Listen for system theme changes (relevant when preference is 'system')
    mediaQuery = window.matchMedia('(prefers-color-scheme: light)');
    mediaHandler = () => {
        if (current.value === 'system') {
            applyTheme(resolveTheme('system'));
        }
    };
    mediaQuery.addEventListener('change', mediaHandler);
}

/**
 * Change the theme preference at runtime.
 * Immediately applies the new theme to the DOM.
 *
 * @param {string} preference - 'dark' | 'light' | 'system'
 */
export function setTheme(preference) {
    current.value = preference;
    applyTheme(resolveTheme(preference));
}

/**
 * Get the current theme preference (reactive ref).
 */
export function useTheme() {
    return {
        theme: current,
        setTheme,
        initTheme,
    };
}