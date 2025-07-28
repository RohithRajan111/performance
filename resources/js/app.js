import './bootstrap';

// This imports your main stylesheet, which now contains the orgchart CSS.
import '../css/app.css';

import { createApp, h } from 'vue';
import { createInertiaApp } from '@inertiajs/vue3';
import { resolvePageComponent } from 'laravel-vite-plugin/inertia-helpers';
import { ZiggyVue } from 'ziggy-js';

const appName = import.meta.env.VITE_APP_NAME || 'Laravel';

createInertiaApp({
    title: (title) => `${title} - ${appName}`,
    resolve: (name) => {
        // Implement lazy loading for pages
        const pages = import.meta.glob('./Pages/**/*.vue', { eager: false });
        return pages[`./Pages/${name}.vue`]?.();
    },
    setup({ el, App, props, plugin }) {
        const app = createApp({ render: () => h(App, props) })
            .use(plugin)
            .use(ZiggyVue);

        // Global error handler for better debugging
        app.config.errorHandler = (error, instance, info) => {
            console.error('Vue error:', error, info);
        };

        // Performance monitoring in development
        if (import.meta.env.DEV) {
            app.config.performance = true;
        }

        return app.mount(el);
    },
    progress: {
        delay: 250,
        color: '#4F46E5',
        includeCSS: true,
        showSpinner: true,
    },
});