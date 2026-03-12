import Pagination from '@/Components/Admin/Pagination';
import AdminLayout from '@/Layouts/AdminLayout';
import { Head, Link, router } from '@inertiajs/react';

export default function Index({ subscriptions }) {
    const destroy = (id) => {
        if (!window.confirm('Delete this subscription?')) {
            return;
        }

        router.delete(route('admin.subscriptions.destroy', id));
    };

    return (
        <AdminLayout title="Subscriptions">
            <Head title="Admin Subscriptions" />

            <div className="rounded-xl bg-white p-5 shadow-sm">
                <div className="mb-4 flex justify-end">
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
                                                className="text-sky-700 hover:text-sky-900"
                                            >
                                                Edit
                                            </Link>
                                            <button
                                                type="button"
                                                onClick={() =>
                                                    destroy(subscription.id)
                                                }
                                                className="text-red-700 hover:text-red-900"
                                            >
                                                Delete
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                            ))}
                            {subscriptions.data.length === 0 && (
                                <tr>
                                    <td
                                        colSpan={6}
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
        </AdminLayout>
    );
}
