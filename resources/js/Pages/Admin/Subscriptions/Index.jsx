import Pagination from '@/Components/Admin/Pagination';
import ConfirmationModal from '@/Components/ConfirmationModal';
import AdminLayout from '@/Layouts/AdminLayout';
import { Head, Link, router } from '@inertiajs/react';
import { ChevronDown, Pencil, Trash2 } from 'lucide-react';
import { useState } from 'react';

export default function Index({ subscriptions, filters = {} }) {
    const [confirmingId, setConfirmingId] = useState(null);
    const [search, setSearch] = useState(filters.search || '');
    const [perPage, setPerPage] = useState(
        filters.per_page ? String(filters.per_page) : '15',
    );

    const submitSearch = (event) => {
        event.preventDefault();
        router.get(
            route('admin.subscriptions.index'),
            { search, per_page: perPage },
            { preserveState: true, replace: true },
        );
    };

    const openConfirm = (id) => setConfirmingId(id);
    const closeConfirm = () => setConfirmingId(null);

    const confirmDelete = () => {
        if (!confirmingId) {
            return;
        }

        router.delete(route('admin.subscriptions.destroy', confirmingId), {
            onFinish: closeConfirm,
        });
    };

    const applyPerPage = (value) => {
        setPerPage(value);
        router.get(
            route('admin.subscriptions.index'),
            { search, per_page: value },
            { preserveState: true, replace: true },
        );
    };

    return (
        <AdminLayout title="Subscriptions">
            <Head title="Admin Subscriptions" />

            <div className="rounded-2xl border border-slate-200 bg-white p-5">
                <div className="mb-4 flex flex-wrap items-center justify-between gap-3">
                    <div className="flex flex-wrap items-center gap-3">
                        <div className="group relative">
                            <select
                                aria-label="Per page"
                                value={perPage}
                                onChange={(event) =>
                                    applyPerPage(event.target.value)
                                }
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
                        <form onSubmit={submitSearch} className="flex gap-2">
                            <input
                                value={search}
                                onChange={(event) =>
                                    setSearch(event.target.value)
                                }
                                className="rounded-md border border-slate-300 px-3 py-2 text-sm"
                                placeholder="Search subscriptions..."
                            />
                            <button
                                type="submit"
                                className="rounded-md bg-slate-900 px-4 py-2 text-sm text-white"
                            >
                                Search
                            </button>
                        </form>
                    </div>
                    <Link
                        href={route('admin.subscriptions.create')}
                        className="rounded-md bg-sky-700 px-4 py-2 text-sm text-white hover:bg-sky-800"
                    >
                        New Subscription
                    </Link>
                </div>

                <div className="overflow-x-auto">
                    <table className="min-w-full text-left text-sm">
                        <thead className="text-slate-500">
                            <tr>
                                <th className="px-3 py-2">User</th>
                                <th className="px-3 py-2">Plan</th>
                                <th className="px-3 py-2">Billing</th>
                                <th className="px-3 py-2">Start</th>
                                <th className="px-3 py-2">End</th>
                                <th className="px-3 py-2">Status</th>
                                <th className="px-3 py-2">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            {subscriptions.data.map((subscription) => (
                                <tr
                                    key={subscription.id}
                                    className="border-t border-slate-200"
                                >
                                    <td className="px-3 py-2">
                                        <p>{subscription.user}</p>
                                        <p className="text-xs text-slate-500">
                                            {subscription.user_email}
                                        </p>
                                    </td>
                                    <td className="px-3 py-2">
                                        {subscription.plan}
                                    </td>
                                    <td className="px-3 py-2">
                                        {subscription.billing_label || '-'}
                                    </td>
                                    <td className="px-3 py-2">
                                        {subscription.start_date}
                                    </td>
                                    <td className="px-3 py-2">
                                        {subscription.end_date || '-'}
                                    </td>
                                    <td className="px-3 py-2 uppercase">
                                        {subscription.status}
                                    </td>
                                    <td className="px-3 py-2">
                                        <div className="flex gap-2">
                                            <Link
                                                href={route(
                                                    'admin.subscriptions.edit',
                                                    subscription.id,
                                                )}
                                                className="inline-flex h-8 w-8 items-center justify-center rounded-md text-sky-700 hover:bg-sky-50 hover:text-sky-900"
                                                title="Edit"
                                                aria-label="Edit"
                                            >
                                                <Pencil className="size-4" />
                                            </Link>
                                            <button
                                                type="button"
                                                onClick={() =>
                                                    openConfirm(subscription.id)
                                                }
                                                className="inline-flex h-8 w-8 items-center justify-center rounded-md text-red-700 hover:bg-red-50 hover:text-red-900"
                                                title="Delete"
                                                aria-label="Delete"
                                            >
                                                <Trash2 className="size-4" />
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                            ))}
                            {subscriptions.data.length === 0 && (
                                <tr>
                                    <td
                                        colSpan={7}
                                        className="px-3 py-4 text-center text-slate-500"
                                    >
                                        No subscriptions yet.
                                    </td>
                                </tr>
                            )}
                        </tbody>
                    </table>
                </div>

                <div className="mt-4">
                    <Pagination links={subscriptions.links} />
                </div>
            </div>

            <ConfirmationModal
                show={confirmingId !== null}
                title="Delete Subscription"
                message="Are you sure you want to delete this subscription? This action cannot be undone."
                onCancel={closeConfirm}
                onConfirm={confirmDelete}
            />
        </AdminLayout>
    );
}
