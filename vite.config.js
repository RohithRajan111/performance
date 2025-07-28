import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';
import vue from '@vitejs/plugin-vue';

export default defineConfig({
    plugins: [
        laravel({
            input: 'resources/js/app.js',
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
    // ADD THIS SECTION to treat jQuery as an external dependency
    build: {
        rollupOptions: {
            external: ['jquery'],
        },
    },
    // This can sometimes help Vite resolve external dependencies during dev
    optimizeDeps: {
        exclude: ['jquery']
    }
});