import '../css/app.css';

import { config, createInertiaApp, router } from '@inertiajs/vue3';
import { createApp, h } from 'vue';
import { ZiggyVue } from '../../vendor/tightenco/ziggy';

const appName = import.meta.env.VITE_APP_NAME || 'Laravel';
const pages = import.meta.glob('./Pages/**/*.vue');
let activeNavigationVisits = 0;
let navBusyTimer = null;

config.set({
    prefetch: {
        cacheFor: '2m',
        hoverDelay: 0,
    },
    visitOptions: (href, options) => {
        return {
            ...options,
            viewTransition: typeof options.viewTransition === 'undefined'
                ? false
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

    if (activeNavigationVisits > 0) {
        if (navBusyTimer !== null) {
            return;
        }

        navBusyTimer = window.setTimeout(() => {
            navBusyTimer = null;

            if (activeNavigationVisits > 0) {
                document.documentElement.dataset.navBusy = 'true';
            }
        }, 140);

        return;
    }

    if (navBusyTimer !== null) {
        window.clearTimeout(navBusyTimer);
        navBusyTimer = null;
    }

    document.documentElement.dataset.navBusy = 'false';
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
