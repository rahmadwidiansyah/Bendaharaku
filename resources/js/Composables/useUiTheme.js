/**
 * useUiTheme — UI theme (Default / Material Design 3) composable.
 *
 * Single source of truth for the `.ui-m3` DOM class is the database
 * preference (`users.ui_theme`), exposed to the client in two ways:
 *   1. `<meta name="ui-theme">` in app.blade.php (+ a synchronous head
 *      script that applies `.ui-m3` BEFORE first paint — no flash).
 *   2. `auth.user.ui_theme` Inertia shared prop (HandleInertiaRequests).
 *
 * This module only READS that preference and mirrors it to the DOM.
 * It never persists anything itself — saving goes through the Appearance
 * PATCH endpoint, followed by a full reload (see Appearance.vue).
 *
 * Pattern follows useTheme.js / useBalanceVisibility.js (module singleton).
 */

import { ref } from 'vue';

const UI_THEMES = ['default', 'm3'];

const uiTheme = ref('default');
let initialized = false;

function normalize(value) {
    return UI_THEMES.includes(value) ? value : 'default';
}

function readFromMeta() {
    const meta = document.querySelector('meta[name="ui-theme"]');
    return normalize(meta?.getAttribute('content'));
}

export function applyUiTheme(theme) {
    const html = document.documentElement;
    if (normalize(theme) === 'm3') {
        html.classList.add('ui-m3');
    } else {
        html.classList.remove('ui-m3');
    }
}

/**
 * Initialize once, before app mount. Idempotent — safe to call from
 * both app.js (pre-mount) and layouts (onMounted verification).
 * Never removes a class it did not manage; only touches `.ui-m3`.
 */
export function initUiTheme() {
    uiTheme.value = readFromMeta();
    applyUiTheme(uiTheme.value);
    initialized = true;
}

/**
 * Sync from fresh Inertia props (e.g. after navigation). Keeps the DOM
 * class consistent without a reload when the server value is available.
 */
export function syncUiThemeFromProps(props) {
    const value = normalize(props?.auth?.user?.ui_theme);
    if (value !== uiTheme.value) {
        uiTheme.value = value;
        applyUiTheme(value);
    }
}

export function isInitialized() {
    return initialized;
}

export function useUiTheme() {
    return {
        uiTheme,
        initUiTheme,
        syncUiThemeFromProps,
        applyUiTheme,
    };
}
