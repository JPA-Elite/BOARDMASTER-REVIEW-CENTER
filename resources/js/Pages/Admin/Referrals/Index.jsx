import Pagination from '@/Components/Admin/Pagination';
import AdminLayout from '@/Layouts/AdminLayout';
import { Head, Link, router } from '@inertiajs/react';

export default function Index({ referrals }) {
    const destroy = (id) => {
        if (!window.confirm('Delete this referral?')) {
            return;
        }

        router.delete(route('admin.referrals.destroy', id));
    };

    return (
        <AdminLayout title="Referrals">
            <Head title="Admin Referrals" />

            <div className="rounded-2xl border border-slate-200 bg-white p-5">
                <div className="mb-4 flex justify-end">
                    <Link
                        href={route('admin.referrals.create')}
                        className="rounded-md bg-sky-700 px-4 py-2 text-sm text-white hover:bg-sky-800"
                    >
                        New Referral
                    </Link>
                </div>

                <div className="overflow-x-auto">
                    <table className="min-w-full text-left text-sm">
                        <thead className="text-slate-500">
                            <tr>
                                <th className="px-3 py-2">Inviter</th>
                                <th className="px-3 py-2">Invited User</th>
                                <th className="px-3 py-2">Created</th>
                                <th className="px-3 py-2">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            {referrals.data.map((referral) => (
                                <tr
                                    key={referral.id}
                                    className="border-t border-slate-200"
                                >
                                    <td className="px-3 py-2">
                                        <p>{referral.inviter}</p>
                                        <p className="text-xs text-slate-500">
                                            {referral.inviter_email}
                                        </p>
                                    </td>
                                    <td className="px-3 py-2">
                                        <p>{referral.invited_user}</p>
                                        <p className="text-xs text-slate-500">
                                            {referral.invited_email}
                                        </p>
                                    </td>
                                    <td className="px-3 py-2">
                                        {referral.created_at}
                                    </td>
                                    <td className="px-3 py-2">
                                        <div className="flex gap-2">
                                            <Link
                                                href={route(
                                                    'admin.referrals.edit',
                                                    referral.id,
                                                )}
                                                className="text-sky-700 hover:text-sky-900"
                                            >
                                                Edit
                                            </Link>
                                            <button
                                                type="button"
                                                onClick={() =>
                                                    destroy(referral.id)
                                                }
                                                className="text-red-700 hover:text-red-900"
                                            >
                                                Delete
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                            ))}
                            {referrals.data.length === 0 && (
                                <tr>
                                    <td
                                        colSpan={4}
                                        className="px-3 py-4 text-center text-slate-500"
                                    >
                                        No referrals yet.
                                    </td>
                                </tr>
                            )}
                        </tbody>
                    </table>
                </div>

                <div className="mt-4">
                    <Pagination links={referrals.links} />
                </div>
            </div>
        </AdminLayout>
    );
}

