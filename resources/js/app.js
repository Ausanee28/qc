import '../css/app.css';

import { config, createInertiaApp, router } from '@inertiajs/vue3';
import { createApp, h } from 'vue';
import { ZiggyVue } from '../../vendor/tightenco/ziggy';

const appName = import.meta.env.VITE_APP_NAME || 'Laravel';
const pages = import.meta.glob('./Pages/**/*.vue');
let activeNavigationVisits = 0;

config.set({
    prefetch: {
        cacheFor: '45s',
        hoverDelay: 45,
    },
    visitOptions: (href, options) => {
        const method = (options.method ?? 'get').toLowerCase();

        return {
            ...options,
            viewTransition: typeof options.viewTransition === 'undefined' && method === 'get'
                ? true
                : options.viewTransition,
        };
    },
});

const isInteractiveNavigationVisit = (visit) => (
    (visit.method ?? 'get').toLowerCase() === 'get'
    && !visit.prefetch
    && !visit.deferredProps
);

const updateNavigationBusyState = () => {
    if (typeof document === 'undefined') {
        return;
    }

    document.documentElement.dataset.navBusy = activeNavigationVisits > 0 ? 'true' : 'false';
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

        updateNavigationBusyState();

        router.on('start', (event) => {
            if (!isInteractiveNavigationVisit(event.detail.visit)) {
                return;
            }

            activeNavigationVisits += 1;
            updateNavigationBusyState();
        });

        router.on('finish', (event) => {
            if (!isInteractiveNavigationVisit(event.detail.visit)) {
                return;
            }

            activeNavigationVisits = Math.max(0, activeNavigationVisits - 1);
            updateNavigationBusyState();
        });

        return app;
    },
    progress: {
        delay: 80,
        color: '#f97316',
        showSpinner: false,
    },
});
