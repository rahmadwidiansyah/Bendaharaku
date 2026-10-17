import './bootstrap';
import '../css/app.css';
import '../css/m3-tokens.css';
import '../css/m3-schemes.css';

import { createApp, h } from 'vue';
import { createInertiaApp } from '@inertiajs/vue3';
import { ZiggyVue } from 'ziggy-js';
import i18n from '@/i18n/index.js';
import { initAccentColor } from '@/Composables/useAccentColor.js';
import { initCategoryIconColored } from '@/Composables/useIcon.js';
import { initUiTheme } from '@/Composables/useUiTheme.js';
import { initM3ColorScheme } from '@/Composables/useM3ColorScheme.js';

// Terapkan accent color & icon preference sebelum app mount
initAccentColor();
initCategoryIconColored();
// Sinkronkan kelas .ui-m3 dari <meta name="ui-theme"> sebelum mount
// (kelas sudah dipasang head script Blade; ini verifikasi + state ref).
initUiTheme();
// Sinkronkan atribut data-m3-scheme dari <meta name="m3-color-scheme">
// sebelum mount (atribut sudah dipasang head script Blade).
initM3ColorScheme();

const appName = import.meta.env.VITE_APP_NAME || 'Laravel';

createInertiaApp({
    title: (title) => `${title} - ${appName}`,
    resolve: (name) => {
        // Multi-UI: halaman M3 di ./Material/Pages dipetakan sebagai
        // `Material/<Name>` (mis. Material/Dashboard). Tidak ada benturan
        // nama karena namespace direktori berbeda dari ./Pages/.
        const pages = import.meta.glob(['./Pages/**/*.vue', './Material/Pages/**/*.vue'], { eager: true });
        return pages[`./Pages/${name}.vue`] ?? pages[`./Material/Pages/${name.replace(/^Material\//, '')}.vue`];
    },
    setup({ el, App, props, plugin }) {
        createApp({ render: () => h(App, props) })
            .use(plugin)
            .use(ZiggyVue)
            .use(i18n)
            .mount(el);
    },
    progress: {
        color: '#4B5563',
    },
});
