// resources/js/ssr.js
//
// PHASE 15E — SSR ENTRY (build artifact only).
//
// This file exists so the public rendering layer is SSR-READY. It is NOT wired
// into production: no Node process is started, no supervisor is configured, and
// the Hostinger Premium deployment continues to serve the client bundle exactly
// as before.
//
// It mirrors resources/js/app.js deliberately — same page resolver, same title
// callback, same global component registration — so that activating SSR later is
// a deployment change, not an architectural one.
//
// Nothing here may touch window/document/navigator at module scope.

import { createInertiaApp } from '@inertiajs/vue3';
import createServer from '@inertiajs/vue3/server';
import { renderToString } from 'vue/server-renderer';
import { createSSRApp, h } from 'vue';
import { resolvePageComponent } from 'laravel-vite-plugin/inertia-helpers';
import { ZiggyVue } from '../../vendor/tightenco/ziggy';

import ToastContainer from '@/Components/Toast/ToastContainer.vue';

const appName = 'Omniscient';

createServer((page) =>
    createInertiaApp({
        page,
        render: renderToString,
        title: (title) => `${appName} - ${title}`,
        resolve: (name) =>
            resolvePageComponent(
                `./Pages/${name}.vue`,
                import.meta.glob('./Pages/**/*.vue')
            ),
        setup({ App, props, plugin }) {
            // `window` does not exist during SSR. Ziggy receives its location
            // from the page props instead, and is normalised to a URL object
            // because the client bundle gets one from window.location.
            const ziggy = props?.initialPage?.props?.ziggy ?? props?.ziggy ?? {};

            if (ziggy && typeof ziggy.location === 'string') {
                ziggy.location = new URL(ziggy.location);
            }

            const app = createSSRApp({ render: () => h(App, props) })
                .use(plugin)
                .use(ZiggyVue, ziggy);

            // Matches app.js so a page using <ToastContainer> resolves in SSR.
            app.component('ToastContainer', ToastContainer);

            return app;
        },
    })
);
