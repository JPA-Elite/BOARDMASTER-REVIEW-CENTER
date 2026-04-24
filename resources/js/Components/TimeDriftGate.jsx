import { useMemo } from 'react';

function formatLocal(isoOrMs) {
    const date =
        typeof isoOrMs === 'number' ? new Date(isoOrMs) : new Date(isoOrMs);
    if (Number.isNaN(date.getTime())) {
        return null;
    }
    return date.toLocaleString();
}

function shouldEnforce(componentName, adminHost) {
    const component = String(componentName || '');
    if (component.startsWith('Admin/')) {
        return true;
    }

    const host = String(window?.location?.host || '').toLowerCase();
    const normalizedAdminHost = String(adminHost || '').toLowerCase();
    const isAdminHost =
        normalizedAdminHost !== '' && host === normalizedAdminHost;

    // Covers the admin login page when served on the admin host.
    if (isAdminHost && component.startsWith('Auth/')) {
        return true;
    }

    return false;
}

export default function TimeDriftGate({ page, children }) {
    const guard = page?.props?.clientTimeGuard ?? {};
    const component = page?.component ?? '';
    const enforce = shouldEnforce(component, guard.adminHost);

    const state = useMemo(() => {
        if (!enforce) {
            return { blocked: false };
        }

        const allowedSeconds = Number(guard.allowedDriftSeconds ?? 600);
        const allowedMs = Number.isFinite(allowedSeconds)
            ? Math.max(0, allowedSeconds) * 1000
            : 600_000;

        const serverUtcNow = guard.serverUtcNow ?? null;
        const serverMs = typeof serverUtcNow === 'string'
            ? Date.parse(serverUtcNow)
            : NaN;
        if (!Number.isFinite(serverMs)) {
            return { blocked: false };
        }

        const clientMs = Date.now();
        const driftMs = Math.abs(clientMs - serverMs);
        const blocked = driftMs > allowedMs;

        return {
            blocked,
            driftMs,
            clientMs,
            serverMs,
        };
    }, [enforce, guard.adminHost, guard.allowedDriftSeconds, guard.serverUtcNow]);

    if (!state.blocked) {
        return children;
    }

    const driftMinutes = Math.round((state.driftMs ?? 0) / 60000);
    const deviceTime = formatLocal(state.clientMs);
    const serverTime = formatLocal(state.serverMs);

    const csrfHeaders = () => {
        const metaToken =
            document
                ?.querySelector('meta[name="csrf-token"]')
                ?.getAttribute('content') ?? '';

        if (metaToken) {
            return { 'X-CSRF-TOKEN': metaToken };
        }

        const cookie = String(document?.cookie ?? '');
        const xsrfPair = cookie
            .split('; ')
            .find((part) => part.startsWith('XSRF-TOKEN='));
        if (!xsrfPair) {
            return {};
        }

        const raw = xsrfPair.slice('XSRF-TOKEN='.length);
        const decoded = decodeURIComponent(raw);
        return decoded ? { 'X-XSRF-TOKEN': decoded } : {};
    };

    const logout = async () => {
        try {
            await fetch('/logout', {
                method: 'POST',
                credentials: 'same-origin',
                headers: {
                    Accept: 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                    ...csrfHeaders(),
                },
            });
        } catch (_) {
            // Ignore and fall through to redirect.
        } finally {
            window.location.href = '/admin';
        }
    };

    return (
        <div className="fixed inset-0 z-[9999] flex items-center justify-center bg-slate-950/60 p-4 backdrop-blur">
            <div className="w-full max-w-lg rounded-2xl border border-slate-200 bg-white p-6 shadow-xl">
                <h1 className="text-lg font-semibold text-slate-900">
                    Device date/time is incorrect
                </h1>
                <p className="mt-2 text-sm text-slate-600">
                    Admin pages are blocked because your device clock differs from
                    the server by about <span className="font-semibold">{driftMinutes} minute(s)</span>.
                    Enable automatic date &amp; time, then reload.
                </p>

                <div className="mt-4 rounded-xl border border-slate-200 bg-slate-50 p-4 text-sm text-slate-700">
                    <p>
                        Server time:{' '}
                        <span className="font-mono">{serverTime ?? '--'}</span>
                    </p>
                    <p className="mt-1">
                        Device time:{' '}
                        <span className="font-mono">{deviceTime ?? '--'}</span>
                    </p>
                </div>

                <div className="mt-5 flex flex-wrap gap-2">
                    <button
                        type="button"
                        onClick={() => window.location.reload()}
                        className="rounded-md bg-slate-900 px-4 py-2 text-sm font-semibold text-white hover:bg-slate-800"
                    >
                        Reload
                    </button>
                    <button
                        type="button"
                        onClick={logout}
                        className="rounded-md border border-slate-200 bg-white px-4 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-50"
                    >
                        Log out
                    </button>
                </div>
            </div>
        </div>
    );
}
