import '../css/app.css';
import './bootstrap';

import { createInertiaApp, router } from '@inertiajs/react';
import { resolvePageComponent } from 'laravel-vite-plugin/inertia-helpers';
import { createRoot } from 'react-dom/client';
import { ToastContainer, toast } from 'react-toastify';
import 'react-toastify/dist/ReactToastify.css';

const lastFlash = { success: null, error: null };
let routerBound = false;
const inertiaImportReloadKey = 'inertia.import_reload_at';
const inertiaImportReloadWindowMs = 12_000;

const showFlash = (page) => {
    if (!page || page.component === 'Admin/Settings') {
        return;
    }

    const flash = page.props?.flash;
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

const isDynamicImportFetchError = (error) => {
    const message = (error?.message ?? '').toString();
    return (
        message.includes('Failed to fetch dynamically imported module') ||
        message.includes('Importing a module script failed') ||
        message.includes('Failed to load module script')
    );
};

const reloadOnceForImportError = () => {
    if (typeof window === 'undefined') {
        return false;
    }

    try {
        const now = Date.now();
        const last = Number(
            window.sessionStorage.getItem(inertiaImportReloadKey) ?? 0,
        );

        if (last > 0 && now - last < inertiaImportReloadWindowMs) {
            return false;
        }

        window.sessionStorage.setItem(inertiaImportReloadKey, String(now));
        window.location.reload();
        return true;
    } catch (_) {
        window.location.reload();
        return true;
    }
};

createInertiaApp({
    title: (title) => title,
    resolve: (name) =>
        resolvePageComponent(
            `./Pages/${name}.jsx`,
            import.meta.glob('./Pages/**/*.jsx'),
        ).catch((error) => {
            if (isDynamicImportFetchError(error) && reloadOnceForImportError()) {
                return new Promise(() => {});
            }
            throw error;
        }),
    setup({ el, App, props }) {
        if (!routerBound) {
            routerBound = true;
            router.on('success', (event) => {
                showFlash(event.detail.page);
            });
            router.on('invalid', (event) => {
                const response = event.detail?.response;
                const status = response?.status;
                const responseUrl =
                    response?.request?.responseURL ||
                    response?.config?.url ||
                    null;

                const shouldRedirectToLogin =
                    status === 401 ||
                    status === 403 ||
                    status === 419 ||
                    (typeof responseUrl === 'string' &&
                        responseUrl.includes('/login'));

                if (!shouldRedirectToLogin) {
                    return;
                }

                event.preventDefault();

                toast.error(
                    status === 419
                        ? 'Session expired. Refreshing...'
                        : 'Session expired. Please log in again.',
                );

                window.setTimeout(() => {
                    const loginUrl =
                        typeof responseUrl === 'string' &&
                        responseUrl.includes('/login')
                            ? responseUrl
                            : '/login';
                    window.location.href = loginUrl;
                }, 250);
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

        showFlash(props?.initialPage);
    },
    progress: {
        color: '#4B5563',
    },
});
