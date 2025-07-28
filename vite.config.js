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
    build: {
        rollupOptions: {
            external: ['jquery'],
            output: {
                // Manual chunk splitting for better caching
                manualChunks: {
                    // Core vendor chunk
                    vendor: ['vue', '@inertiajs/vue3'],
                    // Chart libraries chunk
                    charts: ['chart.js', 'vue-chartjs', '@fullcalendar/core', '@fullcalendar/vue3'],
                    // Tree/org chart libraries chunk
                    orgchart: ['@balkangraph/orgchart.js', '@he-tree/vue', 'vue3-tree-org'],
                    // Utility libraries chunk
                    utils: ['lodash-es', 'date-fns', 'axios'],
                    // UI components chunk
                    ui: ['@heroicons/vue', '@popperjs/core', '@vuepic/vue-datepicker']
                },
                // Optimize chunk file names
                chunkFileNames: (chunkInfo) => {
                    const facadeModuleId = chunkInfo.facadeModuleId
                        ? chunkInfo.facadeModuleId.split('/').pop().replace('.vue', '')
                        : 'chunk';
                    return `assets/${facadeModuleId}-[hash].js`;
                },
            },
        },
        // Enable build optimizations
        minify: 'terser',
        terserOptions: {
            compress: {
                drop_console: true,
                drop_debugger: true,
            },
        },
        // Optimize chunk size warnings
        chunkSizeWarningLimit: 1000,
    },
    optimizeDeps: {
        exclude: ['jquery'],
        include: [
            'vue',
            '@inertiajs/vue3',
            'lodash-es',
            'date-fns'
        ]
    },
    // Performance optimizations
    esbuild: {
        drop: ['console', 'debugger'],
    },
});