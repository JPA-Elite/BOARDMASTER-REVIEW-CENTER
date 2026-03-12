import { Link, usePage } from '@inertiajs/react';
import { useMemo, useState } from 'react';

function Icon({ children, className = 'size-4' }) {
    return (
        <svg
            viewBox="0 0 24 24"
            fill="none"
            stroke="currentColor"
            strokeWidth="2"
            strokeLinecap="round"
            strokeLinejoin="round"
            className={className}
        >
            {children}
        </svg>
    );
}

function DashboardIcon() {
    return (
        <Icon>
            <rect x="3" y="3" width="7" height="7" rx="1" />
            <rect x="14" y="3" width="7" height="7" rx="1" />
            <rect x="3" y="14" width="7" height="7" rx="1" />
            <rect x="14" y="14" width="7" height="7" rx="1" />
        </Icon>
    );
}

function AnalyticsIcon() {
    return (
        <Icon>
            <path d="M3 3v18h18" />
            <path d="M8 14v-3" />
            <path d="M12 14V8" />
            <path d="M16 14v-6" />
            <path d="M20 14V5" />
        </Icon>
    );
}

function UsersIcon() {
    return (
        <Icon>
            <path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2" />
            <circle cx="9" cy="7" r="4" />
            <path d="M23 21v-2a4 4 0 0 0-3-3.87" />
            <path d="M16 3.13a4 4 0 0 1 0 7.75" />
        </Icon>
    );
}

function FolderIcon() {
    return (
        <Icon>
            <path d="M3 7a2 2 0 0 1 2-2h4l2 2h8a2 2 0 0 1 2 2v8a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z" />
        </Icon>
    );
}

function BellIcon() {
    return (
        <Icon>
            <path d="M10.27 21a2 2 0 0 0 3.46 0" />
            <path d="M18 8a6 6 0 1 0-12 0c0 4.5-1.41 5.96-2.73 7.33A1 1 0 0 0 4 17h16a1 1 0 0 0 .74-1.67C19.41 13.96 18 12.5 18 8z" />
        </Icon>
    );
}

function DatabaseIcon() {
    return (
        <Icon>
            <ellipse cx="12" cy="5" rx="8" ry="3" />
            <path d="M4 5v6c0 1.66 3.58 3 8 3s8-1.34 8-3V5" />
            <path d="M4 11v6c0 1.66 3.58 3 8 3s8-1.34 8-3v-6" />
        </Icon>
    );
}

function SettingsIcon() {
    return (
        <Icon>
            <circle cx="12" cy="12" r="3" />
            <path d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 0 1-2.83 2.83l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V21a2 2 0 0 1-4 0v-.09a1.65 1.65 0 0 0-1-1.51 1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 0 1-2.83-2.83l.06-.06a1.65 1.65 0 0 0 .33-1.82 1.65 1.65 0 0 0-1.51-1H3a2 2 0 0 1 0-4h.09a1.65 1.65 0 0 0 1.51-1 1.65 1.65 0 0 0-.33-1.82L4.21 7.2a2 2 0 0 1 2.83-2.83l.06.06a1.65 1.65 0 0 0 1.82.33h.01a1.65 1.65 0 0 0 1-1.51V3a2 2 0 0 1 4 0v.09a1.65 1.65 0 0 0 1 1.51h.01a1.65 1.65 0 0 0 1.82-.33l.06-.06a2 2 0 0 1 2.83 2.83l-.06.06a1.65 1.65 0 0 0-.33 1.82v.01a1.65 1.65 0 0 0 1.51 1H21a2 2 0 0 1 0 4h-.09a1.65 1.65 0 0 0-1.51 1z" />
        </Icon>
    );
}

function BookIcon() {
    return (
        <Icon>
            <path d="M4 19.5A2.5 2.5 0 0 1 6.5 17H20" />
            <path d="M6.5 2H20v20H6.5A2.5 2.5 0 0 1 4 19.5v-15A2.5 2.5 0 0 1 6.5 2z" />
        </Icon>
    );
}

const navSections = [
    {
        label: 'Overview',
        items: [
            { label: 'Dashboard', routeName: 'admin.dashboard', icon: DashboardIcon },
            { label: 'Analytics', routeName: 'admin.analytics', icon: AnalyticsIcon },
        ],
    },
    {
        label: 'Management',
        items: [
            { label: 'Users', routeName: 'admin.users.index', icon: UsersIcon },
            { label: 'Referrals', routeName: 'admin.referrals.index', icon: UsersIcon },
            { label: 'Plans', routeName: 'admin.plans.index', icon: FolderIcon },
            { label: 'Subscriptions', routeName: 'admin.subscriptions.index', icon: FolderIcon },
            { label: 'Subjects', routeName: 'admin.subjects.index', icon: FolderIcon },
            { label: 'Questions', routeName: 'admin.questions.index', icon: FolderIcon },
        ],
    },
    {
        label: 'System',
        items: [
            { label: 'Admin Settings', routeName: 'admin.settings', icon: SettingsIcon },
        ],
    },
];

function isActiveRoute(routeName) {
    if (!routeName) {
        return false;
    }

    return (
        route().current(routeName) ||
        route().current(`${routeName.replace('.index', '')}.*`)
    );
}

export default function AdminLayout({ title, children }) {
    const { auth, flash } = usePage().props;
    const notifications = usePage().props.admin?.notifications ?? [];
    const notificationCount = usePage().props.admin?.notificationCount ?? 0;
    const [mobileOpen, setMobileOpen] = useState(false);
    const [notificationOpen, setNotificationOpen] = useState(false);

    const initials = useMemo(() => {
        const name = auth.user?.name || 'Admin User';
        return name
            .split(' ')
            .map((part) => part[0])
            .join('')
            .slice(0, 2)
            .toUpperCase();
    }, [auth.user?.name]);

    return (
        <div className="min-h-screen bg-[#f4f5f7] text-slate-900">
            <div className="flex min-h-screen">
                <aside
                    className={`fixed inset-y-0 left-0 z-40 w-[290px] border-r border-slate-200 bg-[#f7f7f8] transition-transform ${
                        mobileOpen ? 'translate-x-0' : '-translate-x-full'
                    } lg:translate-x-0`}
                >
                    <div className="flex h-full min-h-0 flex-col overflow-hidden">
                        <div className="border-b border-slate-200 px-6 py-6">
                            <Link href={route('admin.dashboard')} className="flex items-center gap-3">
                                <span className="inline-flex size-12 items-center justify-center rounded-xl border border-slate-200 bg-white">
                                    <img
                                        src="/images/boardmaster.png"
                                        alt="Board Master Review Center"
                                        className="size-10 object-contain"
                                    />
                                </span>
                                <span className="min-w-0">
                                    <span className="block text-sm font-semibold uppercase tracking-wide text-slate-900">
                                        BOARDMASTER REVIEW CENTER
                                    </span>
                                    <span className="block text-xs text-slate-500">
                                        Admin Dashboard
                                    </span>
                                </span>
                            </Link>
                        </div>

                        <nav className="min-h-0 flex-1 space-y-6 overflow-y-auto px-4 py-5">
                            {navSections.map((section) => (
                                <div key={section.label}>
                                    <p className="px-3 text-sm font-semibold text-[#4b5563]">
                                        {section.label}
                                    </p>
                                    <div className="mt-2 space-y-1">
                                        {section.items.map((item) => {
                                            const ItemIcon = item.icon;
                                            const active = isActiveRoute(item.routeName);
                                            const href = item.routeName
                                                ? route(item.routeName)
                                                : item.href;

                                            return (
                                                <Link
                                                    key={item.routeName + item.label}
                                                    href={href}
                                                    className={`flex items-center gap-3 rounded-md px-3 py-2.5 text-sm transition ${
                                                        active
                                                            ? 'bg-slate-100 text-slate-950'
                                                            : 'text-slate-700 hover:bg-slate-100 hover:text-slate-950'
                                                    }`}
                                                >
                                                    <span className="text-slate-500">
                                                        <ItemIcon />
                                                    </span>
                                                    <span>{item.label}</span>
                                                </Link>
                                            );
                                        })}
                                    </div>
                                </div>
                            ))}
                        </nav>

                        <div className="mt-auto shrink-0 border-t border-slate-200 p-4">
                            <div className="flex items-center gap-3 rounded-xl border border-slate-200 bg-white px-3 py-2">
                                <span className="inline-flex size-10 items-center justify-center overflow-hidden rounded-lg bg-slate-100 text-sm font-semibold text-slate-700">
                                    {auth.user?.avatar_url ? (
                                        <img
                                            src={auth.user.avatar_url}
                                            alt={auth.user?.name ?? 'Admin'}
                                            className="h-full w-full object-cover"
                                        />
                                    ) : (
                                        initials
                                    )}
                                </span>
                                <div className="min-w-0 flex-1">
                                    <p className="truncate text-sm font-semibold text-slate-900">
                                        {auth.user?.name}
                                    </p>
                                    <p className="truncate text-xs text-slate-500">
                                        {auth.user?.email}
                                    </p>
                                </div>
                                <Link
                                    href={route('logout')}
                                    method="post"
                                    as="button"
                                    className="rounded-md px-2 py-1 text-xs font-medium text-slate-500 hover:bg-slate-100"
                                >
                                    Log out
                                </Link>
                            </div>
                        </div>
                    </div>
                </aside>

                <div className="flex min-w-0 flex-1 flex-col lg:pl-[290px]">
                    <header className="sticky top-0 z-30 border-b border-slate-200 bg-[#f8f8f9]">
                        <div className="flex h-16 items-center gap-4 px-4 md:px-6">
                            <button
                                type="button"
                                onClick={() => setMobileOpen((prev) => !prev)}
                                className="inline-flex size-9 items-center justify-center rounded-md border border-slate-200 bg-white text-slate-700 lg:hidden"
                            >
                                <span className="sr-only">Toggle sidebar</span>
                                <svg
                                    viewBox="0 0 24 24"
                                    fill="none"
                                    stroke="currentColor"
                                    strokeWidth="2"
                                    strokeLinecap="round"
                                    strokeLinejoin="round"
                                    className="size-5"
                                >
                                    <path d="M4 6h16" />
                                    <path d="M4 12h16" />
                                    <path d="M4 18h16" />
                                </svg>
                            </button>

                            <p className="text-sm font-medium text-slate-900">{title}</p>

                            <div className="ml-auto flex items-center gap-2">
                                <div className="relative">
                                    <button
                                        type="button"
                                        onClick={() =>
                                            setNotificationOpen(
                                                (prev) => !prev,
                                            )
                                        }
                                        className="relative inline-flex size-9 items-center justify-center rounded-md text-slate-500 hover:bg-slate-100"
                                    >
                                        <BellIcon />
                                        {notificationCount > 0 && (
                                            <span className="absolute -right-1 -top-1 inline-flex size-4 items-center justify-center rounded-full bg-rose-500 text-[10px] font-semibold text-white">
                                                {notificationCount > 9
                                                    ? '9+'
                                                    : notificationCount}
                                            </span>
                                        )}
                                    </button>

                                    {notificationOpen && (
                                        <div className="absolute right-0 mt-2 w-80 overflow-hidden rounded-xl border border-slate-200 bg-white shadow-lg">
                                            <div className="flex items-center justify-between border-b border-slate-100 px-4 py-3">
                                                <p className="text-sm font-semibold text-slate-900">
                                                    Notifications
                                                </p>
                                                <button
                                                    type="button"
                                                    onClick={() =>
                                                        setNotificationOpen(
                                                            false,
                                                        )
                                                    }
                                                    className="text-xs text-slate-400 hover:text-slate-600"
                                                >
                                                    Close
                                                </button>
                                            </div>
                                            <div className="max-h-72 overflow-y-auto">
                                                {notifications.length === 0 && (
                                                    <p className="px-4 py-6 text-center text-sm text-slate-500">
                                                        No notifications yet.
                                                    </p>
                                                )}
                                                {notifications.map(
                                                    (notification) => (
                                                        <div
                                                            key={
                                                                notification.id
                                                            }
                                                            className="border-b border-slate-100 px-4 py-3 last:border-b-0"
                                                        >
                                                            <p className="text-sm font-medium text-slate-900">
                                                                {
                                                                    notification.title
                                                                }
                                                            </p>
                                                            <p className="mt-1 text-xs text-slate-500">
                                                                {
                                                                    notification.message
                                                                }
                                                            </p>
                                                            <p className="mt-2 text-[11px] text-slate-400">
                                                                {
                                                                    notification.time
                                                                }
                                                            </p>
                                                        </div>
                                                    ),
                                                )}
                                            </div>
                                        </div>
                                    )}
                                </div>
                                <Link
                                    href={route('admin.settings')}
                                    className="inline-flex size-9 items-center justify-center rounded-md text-slate-500 hover:bg-slate-100"
                                >
                                    <SettingsIcon />
                                </Link>
                            </div>
                        </div>
                    </header>

                    <main className="flex-1 px-4 py-5 md:px-6">
                        <div className="space-y-4">
                            {flash?.success && (
                                <div className="rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-700">
                                    {flash.success}
                                </div>
                            )}

                            {flash?.error && (
                                <div className="rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700">
                                    {flash.error}
                                </div>
                            )}

                            {children}
                        </div>
                    </main>
                </div>
            </div>

            {mobileOpen && (
                <button
                    type="button"
                    onClick={() => setMobileOpen(false)}
                    className="fixed inset-0 z-30 bg-black/30 lg:hidden"
                >
                    <span className="sr-only">Close sidebar</span>
                </button>
            )}
        </div>
    );
}
