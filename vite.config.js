import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';
import vue from '@vitejs/plugin-vue';
import tailwindcss from '@tailwindcss/vite';
import path from 'path';


export default defineConfig({
    build: {
        chunkSizeWarningLimit: 700,
        rollupOptions: {
            output: {
                manualChunks: (id) => {
                    if (id.includes('node_modules/chart.js')) return 'vendor-chart';
                    if (id.includes('node_modules/lucide-vue-next')) return 'vendor-icons';
                    if (id.includes('node_modules/vue') || id.includes('node_modules/@vue') || id.includes('node_modules/@inertiajs/vue3') || id.includes('node_modules/vue-i18n')) return 'vendor-vue';
                    if (id.includes('node_modules/axios')) return 'vendor-axios';
                },
            },
        },
    },
    server: {
        host: '0.0.0.0',
        hmr: {
            host: '127.0.0.1',
        },
        watch: {
            usePolling: true,
        },
    },
    plugins: [
        tailwindcss(),
        laravel({
            input: ['resources/js/app.js'],
            refresh: true,
        }),
        vue({
            template: {
                transformAssetUrls: {
                    base: null,
                    includeAbsolute: false,
                },
            },
        }),
    ],
});
