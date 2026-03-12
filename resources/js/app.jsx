import '../css/app.css';
import './bootstrap';

import { createInertiaApp, router } from '@inertiajs/react';
import { resolvePageComponent } from 'laravel-vite-plugin/inertia-helpers';
import { createRoot } from 'react-dom/client';
import { ToastContainer, toast } from 'react-toastify';
import 'react-toastify/dist/ReactToastify.css';

const lastFlash = { success: null, error: null };
let routerBound = false;

const showFlash = (flash) => {
    if (!flash) {
        return;
    }

    if (flash.success && flash.success !== lastFlash.success) {
        toast.success(flash.success);
        lastFlash.success = flash.success;
    }

    if (flash.error && flash.error !== lastFlash.error) {
        toast.error(flash.error);
        lastFlash.error = flash.error;
    }
};

createInertiaApp({
    title: (title) => title,
    resolve: (name) =>
        resolvePageComponent(
            `./Pages/${name}.jsx`,
            import.meta.glob('./Pages/**/*.jsx'),
        ),
    setup({ el, App, props }) {
        if (!routerBound) {
            routerBound = true;
            router.on('success', (event) => {
                showFlash(event.detail.page?.props?.flash);
            });
        }

        const root = createRoot(el);

        root.render(
            <>
                <ToastContainer
                    position="top-right"
                    autoClose={3000}
                    hideProgressBar={false}
                    newestOnTop
                    closeOnClick
                    pauseOnFocusLoss
                    draggable
                    pauseOnHover
                    theme="light"
                />
                <App {...props} />
            </>,
        );

        showFlash(props?.initialPage?.props?.flash);
    },
    progress: {
        color: '#4B5563',
    },
});
