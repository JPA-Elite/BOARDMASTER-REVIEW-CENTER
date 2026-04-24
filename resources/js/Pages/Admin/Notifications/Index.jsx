import Pagination from '@/Components/Admin/Pagination';
import ConfirmationModal from '@/Components/ConfirmationModal';
import AdminLayout from '@/Layouts/AdminLayout';
import { Head, router } from '@inertiajs/react';
import { ChevronDown, Trash2 } from 'lucide-react';
import { useState } from 'react';
import { toast } from 'react-toastify';
import NotificationAvatar from '@/Components/Admin/NotificationAvatar';

export default function Index({ notifications, filters = {} }) {
    const [confirmClear, setConfirmClear] = useState(false);
    const [perPage, setPerPage] = useState(
        filters.per_page ? String(filters.per_page) : '25',
    );

    const applyPerPage = (value) => {
        setPerPage(value);
        router.get(
            route('admin.notifications.index'),
            { per_page: value },
            { preserveState: true, replace: true },
        );
    };

    return (
        <AdminLayout title="Notifications">
            <Head title="Admin Notifications" />

            <div className="rounded-2xl border border-slate-200 bg-white p-5">
                <div className="mb-4 flex flex-wrap items-center justify-between gap-3">
                    <div className="group relative">
                        <select
                            aria-label="Per page"
                            value={perPage}
                            onChange={(event) => applyPerPage(event.target.value)}
                            className="no-arrow w-[66px] rounded-md border border-slate-300 bg-white px-2 py-2 pr-8 text-sm"
                        >
                            <option value="10">10</option>
                            <option value="15">15</option>
                            <option value="25">25</option>
                            <option value="50">50</option>
                            <option value="100">100</option>
                        </select>
                        <ChevronDown className="pointer-events-none absolute right-2 top-1/2 size-4 -translate-y-1/2 text-slate-500 transition-transform group-focus-within:rotate-180" />
                    </div>
                    <button
                        type="button"
                        onClick={() => setConfirmClear(true)}
                        disabled={notifications.data.length === 0}
                        className="rounded-md border border-slate-200 px-3 py-2 text-xs font-semibold uppercase tracking-wide text-slate-600 hover:bg-slate-50 disabled:cursor-not-allowed disabled:opacity-50"
                    >
                        Empty all
                    </button>
                </div>

                <div className="space-y-3">
                    {notifications.data.map((notification) => (
                        <div
                            key={notification.id}
                            className="rounded-xl border border-slate-200 p-4 transition-colors hover:bg-slate-50"
                        >
                            <div className="flex items-start justify-between gap-4">
                                <div className="min-w-0 flex-1">
                                    <div className="flex items-start gap-3">
                                        <NotificationAvatar
                                            label={
                                                notification.user?.name ||
                                                notification.title ||
                                                notification.message
                                            }
                                            size={48}
                                            imageUrl={
                                                notification.user?.avatar_url ||
                                                notification.user?.avatarUrl
                                            }
                                        />

                                        <div className="min-w-0 flex-1">
                                            <div className="flex items-start justify-between gap-3">
                                                <p className="text-sm font-semibold text-slate-900 truncate">
                                                    {notification.title}
                                                </p>
                                                <button
                                                    type="button"
                                                    onClick={() =>
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
                                                        )
                                                    }
                                                    className="inline-flex size-9 items-center justify-center rounded-md text-red-600 hover:bg-red-50 hover:text-red-700"
                                                    title="Delete"
                                                    aria-label="Delete notification"
                                                >
                                                    <Trash2 className="size-4" />
                                                </button>
                                            </div>
                                            <p className="mt-1 text-sm text-slate-600">
                                                {notification.message}
                                            </p>
                                            {notification.type?.startsWith(
                                                'subscription',
                                            ) &&
                                                (notification.payment
                                                    ?.payment_id ||
                                                    notification.payment
                                                        ?.method) && (
                                                    <p className="mt-2 text-xs text-slate-500">
                                                        {notification.payment
                                                            ?.method && (
                                                            <span>
                                                                Method:{' '}
                                                                <span className="font-mono">
                                                                    {
                                                                        notification
                                                                            .payment
                                                                            .method
                                                                    }
                                                                </span>
                                                            </span>
                                                        )}
                                                        {notification.payment
                                                            ?.method &&
                                                            notification
                                                                .payment
                                                                ?.payment_id && (
                                                                <span className="mx-2 text-slate-300">
                                                                    |
                                                                </span>
                                                            )}
                                                        {notification.payment
                                                            ?.payment_id && (
                                                            <span>
                                                                Payment ID:{' '}
                                                                <span className="font-mono">
                                                                    {
                                                                        notification
                                                                            .payment
                                                                            .payment_id
                                                                    }
                                                                </span>
                                                            </span>
                                                        )}
                                                    </p>
                                                )}
                                            <p className="mt-2 text-xs text-slate-400">
                                                {notification.time}
                                            </p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    ))}

                    {notifications.data.length === 0 && (
                        <div className="rounded-xl border border-slate-200 p-6 text-center text-sm text-slate-500">
                            No notifications yet.
                        </div>
                    )}
                </div>

                <div className="mt-4">
                    <Pagination links={notifications.links} />
                </div>
            </div>

            <ConfirmationModal
                show={confirmClear}
                title="Clear Notifications"
                message="Are you sure you want to clear all notifications? This action cannot be undone."
                onCancel={() => setConfirmClear(false)}
                onConfirm={() =>
                    router.delete(route('admin.notifications.clear'), {
                        onFinish: () => setConfirmClear(false),
                    })
                }
            />
        </AdminLayout>
    );
}
