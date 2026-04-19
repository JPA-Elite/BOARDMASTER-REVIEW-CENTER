import { Link, router, usePage } from '@inertiajs/react';
import { useEffect, useMemo, useRef, useState } from 'react';
import { createPortal } from 'react-dom';
import { toast } from 'react-toastify';

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

function ClipboardIcon() {
    return (
        <Icon>
            <rect x="9" y="2" width="6" height="4" rx="1" />
            <path d="M9 4H7a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h10a2 2 0 0 0 2-2V6a2 2 0 0 0-2-2h-2" />
        </Icon>
    );
}

function CreditCardIcon() {
    return (
        <Icon>
            <rect x="2" y="5" width="20" height="14" rx="2" />
            <path d="M2 10h20" />
            <path d="M6 15h4" />
        </Icon>
    );
}

function BookOpenIcon() {
    return (
        <Icon>
            <path d="M12 7a4 4 0 0 1 4-4h4v16h-4a4 4 0 0 0-4 4" />
            <path d="M12 7a4 4 0 0 0-4-4H4v16h4a4 4 0 0 1 4 4" />
        </Icon>
    );
}

function HelpCircleIcon() {
    return (
        <Icon>
            <circle cx="12" cy="12" r="9" />
            <path d="M9.1 9a3 3 0 0 1 5.8 1c0 2-3 2-3 4" />
            <path d="M12 17h.01" />
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

function TrashIcon() {
    return (
        <Icon className="size-4">
            <path d="M3 6h18" />
            <path d="M8 6V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2" />
            <path d="M19 6l-1 14a2 2 0 0 1-2 2H8a2 2 0 0 1-2-2L5 6" />
            <path d="M10 11v6" />
            <path d="M14 11v6" />
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

function ArrowRightIcon() {
    return (
        <Icon className="size-4">
            <path d="M5 12h14" />
            <path d="m13 6 6 6-6 6" />
        </Icon>
    );
}

const navSections = [
    {
        label: 'Overview',
        items: [
            { label: 'Dashboard', routeName: 'admin.dashboard', icon: DashboardIcon },
        ],
    },
    {
        label: 'Management',
        items: [
            { label: 'Users', routeName: 'admin.users.index', icon: UsersIcon },
            { label: 'Referrals', routeName: 'admin.referrals.index', icon: UsersIcon },
            { label: 'Referral Offers', routeName: 'admin.referral-offers.index', icon: CreditCardIcon },
            { label: 'Plans', routeName: 'admin.plans.index', icon: ClipboardIcon },
            { label: 'Subscriptions', routeName: 'admin.subscriptions.index', icon: CreditCardIcon },
            { label: 'Subjects', routeName: 'admin.subjects.index', icon: BookOpenIcon },
            { label: 'Questions', routeName: 'admin.questions.index', icon: HelpCircleIcon },
            { label: 'FAQs', routeName: 'admin.faqs.index', icon: BookIcon },
            { label: 'Reviews', routeName: 'admin.reviews.index', icon: BookOpenIcon },
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

function FloatingSidebarTooltip({ anchorRef, label, open }) {
    const [coords, setCoords] = useState(null);

    useEffect(() => {
        if (!open) {
            setCoords(null);
            return;
        }

        const el = anchorRef.current;
        if (!el) {
            return;
        }

        const update = () => {
            const rect = el.getBoundingClientRect();
            setCoords({
                top: rect.top + rect.height / 2,
                left: rect.right,
            });
        };

        const raf = window.requestAnimationFrame(update);
        window.addEventListener('scroll', update, true);
        window.addEventListener('resize', update);
        return () => {
            window.cancelAnimationFrame(raf);
            window.removeEventListener('scroll', update, true);
            window.removeEventListener('resize', update);
        };
    }, [open, anchorRef]);

    if (!open || !coords) {
        return null;
    }

    return createPortal(
        <div
            className="admin-floating-tooltip pointer-events-none fixed z-[9999]"
            style={{
                top: coords.top,
                left: coords.left,
                transform: 'translateY(-50%)',
            }}
        >
            <div className="relative">
                <div className="rounded-r-xl rounded-l-none bg-slate-900/95 px-3 py-2 text-xs font-semibold text-white shadow-xl ring-1 ring-black/10 backdrop-blur">
                    <span className="block max-w-[240px] truncate">{label}</span>
                </div>
            </div>
        </div>,
        document.body,
    );
}

function AdminSidebarNavItem({ item, href, active, collapsed }) {
    const [open, setOpen] = useState(false);
    const linkRef = useRef(null);
    const ItemIcon = item.icon;

    return (
        <Link
            ref={linkRef}
            href={href}
            aria-label={collapsed ? item.label : undefined}
            onMouseEnter={() => collapsed && setOpen(true)}
            onMouseLeave={() => setOpen(false)}
            onFocus={() => collapsed && setOpen(true)}
            onBlur={() => setOpen(false)}
            className={`group relative flex items-center rounded-md py-2.5 text-sm transition ${
                active
                    ? 'bg-slate-200 text-slate-950'
                    : 'text-slate-700 hover:bg-slate-200 hover:text-slate-950'
            } ${collapsed ? 'justify-center px-1' : 'gap-3 px-3'}`}
        >
            {collapsed ? (
                <span className="text-slate-500">
                    <ItemIcon />
                </span>
            ) : (
                <>
                    <span className="flex items-center gap-3">
                        <span className="text-slate-500">
                            <ItemIcon />
                        </span>
                        <span>{item.label}</span>
                    </span>
                    <span
                        className={`ml-auto text-slate-400 opacity-0 transition ${
                            active
                                ? 'opacity-100 text-slate-600'
                                : 'group-hover:translate-x-0.5 group-hover:opacity-100'
                        }`}
                    >
                        <ArrowRightIcon />
                    </span>
                </>
            )}

            <FloatingSidebarTooltip
                anchorRef={linkRef}
                label={item.label}
                open={collapsed && open}
            />
        </Link>
    );
}

export default function AdminLayout({ title, children }) {
    const { auth, flash } = usePage().props;
    const hideFlash = route().current('admin.settings');
    const notifications = usePage().props.admin?.notifications ?? [];
    const notificationCount = usePage().props.admin?.notificationCount ?? 0;
    const [unreadCount, setUnreadCount] = useState(notificationCount);
    const [mobileOpen, setMobileOpen] = useState(false);
    const [notificationOpen, setNotificationOpen] = useState(false);
    const [sidebarCollapsed, setSidebarCollapsed] = useState(() => {
        try {
            if (typeof window === 'undefined') {
                return false;
            }
            return window.localStorage.getItem('admin_sidebar_collapsed') === '1';
        } catch (_) {
            return false;
        }
    });
    const [desktopShell, setDesktopShell] = useState(() => {
        try {
            if (typeof window === 'undefined') {
                return true;
            }
            return window.matchMedia('(min-width: 1024px)').matches;
        } catch (_) {
            return true;
        }
    });
    const notificationButtonRef = useRef(null);
    const notificationPanelRef = useRef(null);
    const collapsedLogoutRef = useRef(null);
    const [collapsedLogoutTooltipOpen, setCollapsedLogoutTooltipOpen] =
        useState(false);

    const initials = useMemo(() => {
        const name = auth.user?.name || 'Admin User';
        return name
            .split(' ')
            .map((part) => part[0])
            .join('')
            .slice(0, 2)
            .toUpperCase();
    }, [auth.user?.name]);

    useEffect(() => {
        setUnreadCount(notificationCount);
    }, [notificationCount]);

    useEffect(() => {
        try {
            const media = window.matchMedia('(min-width: 1024px)');
            const onChange = (event) => setDesktopShell(event.matches);
            setDesktopShell(media.matches);
            media.addEventListener?.('change', onChange);
            return () => media.removeEventListener?.('change', onChange);
        } catch (_) {
            return undefined;
        }
    }, []);

    useEffect(() => {
        try {
            window.localStorage.setItem(
                'admin_sidebar_collapsed',
                sidebarCollapsed ? '1' : '0',
            );
        } catch (_) {
            // Ignore persistence failures.
        }
    }, [sidebarCollapsed]);

    const effectiveSidebarCollapsed = sidebarCollapsed && desktopShell;

    useEffect(() => {
        if (!notificationOpen) {
            return;
        }

        const handleClickOutside = (event) => {
            if (
                notificationPanelRef.current?.contains(event.target) ||
                notificationButtonRef.current?.contains(event.target)
            ) {
                return;
            }

            setNotificationOpen(false);
        };

        document.addEventListener('mousedown', handleClickOutside);

        return () => {
            document.removeEventListener('mousedown', handleClickOutside);
        };
    }, [notificationOpen]);

    useEffect(() => {
        if (!notificationOpen || unreadCount === 0) {
            return;
        }

        setUnreadCount(0);
        const markRead = async () => {
            try {
                if (window.axios?.post) {
                    await window.axios.post(
                        route('admin.notifications.mark-read'),
                    );
                } else {
                    await fetch(route('admin.notifications.mark-read'), {
                        method: 'POST',
                        headers: {
                            'X-Requested-With': 'XMLHttpRequest',
                        },
                    });
                }
            } catch (_) {
                // Ignore mark-read failures; UI already cleared the badge.
            }
        };

        markRead();
    }, [notificationOpen, unreadCount]);

    return (
        <div className="admin-shell min-h-screen bg-[#f4f5f7] text-slate-900">
            <div className="flex min-h-screen">
                <aside
                    className={`fixed inset-y-0 left-0 z-40 w-[290px] border-r border-slate-200 bg-[#f7f7f8] transition-[transform,width] duration-200 ${
                        mobileOpen ? 'translate-x-0' : '-translate-x-full'
                    } lg:translate-x-0 ${effectiveSidebarCollapsed ? 'lg:w-[72px]' : ''}`}
                >
                    <div className="flex h-full min-h-0 flex-col overflow-visible">
                        <div
                            className={`relative border-b border-slate-200 py-2 ${effectiveSidebarCollapsed ? 'px-3' : 'px-6 pr-14'}`}
                        >
                            <Link
                                href={route('admin.dashboard')}
                                className={`flex items-center ${effectiveSidebarCollapsed ? 'justify-center' : 'gap-3'}`}
                            >
                                <span className="inline-flex size-12 items-center justify-center rounded-full border border-slate-200 bg-white">
                                    <img
                                        src="/images/boardmaster.png"
                                        alt="BoardMasters Review Center"
                                        className="size-10 rounded-full object-contain"
                                    />
                                </span>
                                <span className={`min-w-0 ${effectiveSidebarCollapsed ? 'hidden' : ''}`}>
                                    <span className="block text-[11px] font-bold uppercase tracking-[0.2em] text-slate-500">
                                        BoardMasters
                                    </span>
                                    <span className="block text-lg font-semibold text-slate-900">
                                        Review Center
                                    </span>
                                </span>
                            </Link>

                            {!effectiveSidebarCollapsed && (
                                <button
                                    type="button"
                                    onClick={() => setSidebarCollapsed(true)}
                                    className="hidden lg:inline-flex absolute right-4 top-1/2 size-9 -translate-y-1/2 items-center justify-center rounded-md border border-slate-200 bg-white text-slate-700 hover:bg-slate-100"
                                    aria-pressed={effectiveSidebarCollapsed}
                                    title="Collapse sidebar"
                                >
                                    <span className="sr-only">Collapse sidebar</span>
                                    <svg
                                        viewBox="0 0 24 24"
                                        fill="none"
                                        stroke="currentColor"
                                        strokeWidth="2"
                                        strokeLinecap="round"
                                        strokeLinejoin="round"
                                        className="size-5"
                                    >
                                        <path d="M15 18l-6-6 6-6" />
                                    </svg>
                                </button>
                            )}
                        </div>

                        <nav
                            className={`min-h-0 flex-1 space-y-6 overflow-y-auto py-5 ${effectiveSidebarCollapsed ? 'px-1' : 'px-4'}`}
                        >
                            {navSections.map((section) => (
                                <div key={section.label}>
                                    {!effectiveSidebarCollapsed && (
                                        <p className="px-3 text-sm font-semibold text-[#4b5563]">
                                            {section.label}
                                        </p>
                                    )}
                                    <div className="mt-2 space-y-1">
                                        {section.items.map((item) => {
                                            const ItemIcon = item.icon;
                                            const active = isActiveRoute(item.routeName);
                                            const href = item.routeName
                                                ? route(item.routeName)
                                                : item.href;

                                            return (
                                                <AdminSidebarNavItem
                                                    key={item.routeName + item.label}
                                                    item={item}
                                                    href={href}
                                                    active={active}
                                                    collapsed={effectiveSidebarCollapsed}
                                                />
                                            );
                                        })}
                                    </div>

                                    {effectiveSidebarCollapsed && section.label === 'Management' && (
                                        <div className="mt-2">
                                            <button
                                                type="button"
                                                onClick={() => setSidebarCollapsed(false)}
                                                className="hidden w-full justify-center rounded-md border border-slate-200 bg-white px-1 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-100 lg:inline-flex"
                                                aria-pressed={sidebarCollapsed}
                                                title="Expand sidebar"
                                            >
                                                <span className="sr-only">Expand sidebar</span>
                                                <svg
                                                    viewBox="0 0 24 24"
                                                    fill="none"
                                                    stroke="currentColor"
                                                    strokeWidth="2"
                                                    strokeLinecap="round"
                                                    strokeLinejoin="round"
                                                    className="size-5"
                                                    aria-hidden="true"
                                                >
                                                    <path d="M9 18l6-6-6-6" />
                                                </svg>
                                            </button>
                                        </div>
                                    )}
                                </div>
                            ))}
                        </nav>

                        <div className={`mt-auto shrink-0 border-t border-slate-200 ${effectiveSidebarCollapsed ? 'p-3' : 'p-4'}`}>
                            {effectiveSidebarCollapsed ? (
                                <div className="flex justify-center">
                                    <Link
                                        ref={collapsedLogoutRef}
                                        href={route('logout')}
                                        method="post"
                                        as="button"
                                        onSuccess={() =>
                                            toast.success('Logged out successfully.')
                                        }
                                        onMouseEnter={() =>
                                            setCollapsedLogoutTooltipOpen(true)
                                        }
                                        onMouseLeave={() =>
                                            setCollapsedLogoutTooltipOpen(false)
                                        }
                                        onFocus={() =>
                                            setCollapsedLogoutTooltipOpen(true)
                                        }
                                        onBlur={() =>
                                            setCollapsedLogoutTooltipOpen(false)
                                        }
                                        className="inline-flex size-10 items-center justify-center rounded-xl border border-slate-200 bg-white text-slate-600 hover:bg-slate-100"
                                        aria-label="Log out"
                                    >
                                        <span className="sr-only">Log out</span>
                                        <svg
                                            viewBox="0 0 24 24"
                                            fill="none"
                                            stroke="currentColor"
                                            strokeWidth="2"
                                            strokeLinecap="round"
                                            strokeLinejoin="round"
                                            className="size-4"
                                        >
                                            <path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4" />
                                            <path d="M16 17l5-5-5-5" />
                                            <path d="M21 12H9" />
                                        </svg>
                                    </Link>
                                    <FloatingSidebarTooltip
                                        anchorRef={collapsedLogoutRef}
                                        label="Log out"
                                        open={collapsedLogoutTooltipOpen}
                                    />
                                </div>
                            ) : (
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
                                        onSuccess={() =>
                                            toast.success('Logged out successfully.')
                                        }
                                        className="rounded-md px-2 py-1 text-xs font-medium text-slate-500 hover:bg-slate-100"
                                    >
                                        Log out
                                    </Link>
                                </div>
                            )}
                        </div>
                    </div>
                </aside>

                <div
                    className={`flex min-w-0 flex-1 flex-col ${
                        effectiveSidebarCollapsed ? 'lg:pl-[72px]' : 'lg:pl-[290px]'
                    }`}
                >
                    <header
                        className={`fixed top-0 left-0 right-0 z-30 border-b border-slate-200 bg-[#f8f8f9] ${
                            effectiveSidebarCollapsed ? 'lg:left-[72px]' : 'lg:left-[290px]'
                        }`}
                    >
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
                                        ref={notificationButtonRef}
                                        className="relative inline-flex size-9 items-center justify-center rounded-md border border-slate-200 bg-white text-slate-500 hover:bg-slate-100"
                                    >
                                        <BellIcon />
                                        {unreadCount > 0 && (
                                            <span className="absolute -right-1 -top-1 inline-flex size-4 items-center justify-center rounded-full bg-rose-500 text-[10px] font-semibold text-white">
                                                {unreadCount > 9
                                                    ? '9+'
                                                    : unreadCount}
                                            </span>
                                        )}
                                    </button>

                                    {notificationOpen && (
                                        <div
                                            ref={notificationPanelRef}
                                            className="fixed right-6 top-16 z-50 w-[320px] max-h-[70vh] overflow-hidden rounded-xl border border-slate-200 bg-white shadow-lg"
                                        >
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
                                                            className="border-b border-slate-100 px-4 py-3 transition-colors hover:bg-slate-50 last:border-b-0"
                                                        >
                                                            <div className="flex items-start justify-between gap-3">
                                                                <p className="text-sm font-medium text-slate-900">
                                                                    {
                                                                        notification.title
                                                                    }
                                                                </p>
                                                                <div className="flex items-center gap-2">
                                                                    <button
                                                                        type="button"
                                                                        onClick={() => {
                                                                            router.delete(
                                                                                route(
                                                                                    'admin.notifications.destroy',
                                                                                    notification.id,
                                                                                ),
                                                                                {
                                                                                    preserveScroll: true,
                                                                                    preserveState: true,
                                                                                    onSuccess: () => {
                                                                                        toast.success(
                                                                                            'Notification deleted.',
                                                                                        );
                                                                                    },
                                                                                },
                                                                            );
                                                                        }}
                                                                        className="inline-flex size-7 items-center justify-center rounded-md text-red-600 hover:bg-red-50 hover:text-red-700"
                                                                        title="Delete"
                                                                        aria-label="Delete notification"
                                                                    >
                                                                        <TrashIcon />
                                                                    </button>
                                                                </div>
                                                            </div>
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
                                                <div className="border-t border-slate-100 px-4 py-3">
                                                    <Link
                                                        href={route(
                                                            'admin.notifications.index',
                                                        )}
                                                        onClick={() =>
                                                            setNotificationOpen(
                                                                false,
                                                            )
                                                        }
                                                        className="block w-full rounded-md border border-slate-200 px-3 py-2 text-center text-xs font-semibold uppercase tracking-wide text-slate-600 hover:bg-slate-50"
                                                    >
                                                        Show all
                                                    </Link>
                                                </div>
                                            </div>
                                        </div>
                                    )}
                                </div>
                                <Link
                                    href={route('admin.settings')}
                                    className="inline-flex size-9 items-center justify-center rounded-md border border-slate-200 bg-white text-slate-500 hover:bg-slate-100"
                                >
                                    <SettingsIcon />
                                </Link>
                            </div>
                        </div>
                    </header>

                    <main className="flex-1 px-4 pb-5 pt-20 md:px-6">
                        <div className="space-y-4">
                            {!hideFlash && flash?.success && (
                                <div className="rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-700">
                                    {flash.success}
                                </div>
                            )}

                            {!hideFlash && flash?.error && (
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
