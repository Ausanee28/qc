import '../css/app.css';
import './bootstrap';

import { createInertiaApp } from '@inertiajs/vue3';
import { createApp, h } from 'vue';
import { ZiggyVue } from '../../vendor/tightenco/ziggy';

const appName = import.meta.env.VITE_APP_NAME || 'Laravel';
const pages = import.meta.glob('./Pages/**/*.vue');

const warmupPages = [
    'Dashboard',
    'ReceiveJob/Create',
    'ExecuteTest/Create',
    'Report/Index',
    'Performance/Index',
    'Certificates/Index',
];

const warmupNavigationPages = () => {
    const load = () => {
        warmupPages.forEach((name) => {
            const page = pages[`./Pages/${name}.vue`];
            if (page) {
                page();
            }
        });
    };

    if (typeof window.requestIdleCallback === 'function') {
        window.requestIdleCallback(load, { timeout: 1500 });
        return;
    }

    window.setTimeout(load, 300);
};

createInertiaApp({
    title: (title) => `${title} - ${appName}`,
    resolve: (name) => {
        const page = pages[`./Pages/${name}.vue`];

        if (!page) {
            throw new Error(`Page not found: ${name}`);
        }

        return page().then((module) => module.default ?? module);
    },
    setup({ el, App, props, plugin }) {
        const app = createApp({ render: () => h(App, props) })
            .use(plugin)
            .use(ZiggyVue)
            .mount(el);

        warmupNavigationPages();

        return app;
    },
    progress: {
        color: '#4B5563',
    },
});
