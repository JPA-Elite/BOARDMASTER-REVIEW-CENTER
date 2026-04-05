import Pagination from '@/Components/Admin/Pagination';
import ConfirmationModal from '@/Components/ConfirmationModal';
import AdminLayout from '@/Layouts/AdminLayout';
import { Head, Link, router } from '@inertiajs/react';
import { ChevronDown, Pencil, Trash2 } from 'lucide-react';
import { useState } from 'react';

export default function Index({ referrals, filters = {} }) {
    const [confirmingId, setConfirmingId] = useState(null);
    const [perPage, setPerPage] = useState(
        filters.per_page ? String(filters.per_page) : '15',
    );

    const openConfirm = (id) => setConfirmingId(id);
    const closeConfirm = () => setConfirmingId(null);

    const confirmDelete = () => {
        if (!confirmingId) {
            return;
        }

        router.delete(route('admin.referrals.destroy', confirmingId), {
            onFinish: closeConfirm,
        });
    };

    const applyPerPage = (value) => {
        setPerPage(value);
        router.get(
            route('admin.referrals.index'),
            { per_page: value },
            { preserveState: true, replace: true },
        );
    };

    return (
        <AdminLayout title="Referrals">
            <Head title="Admin Referrals" />

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
                                                className="inline-flex h-8 w-8 items-center justify-center rounded-md text-sky-700 hover:bg-sky-50 hover:text-sky-900"
                                                title="Edit"
                                                aria-label="Edit"
                                            >
                                                <Pencil className="size-4" />
                                            </Link>
                                            <button
                                                type="button"
                                                onClick={() =>
                                                    openConfirm(referral.id)
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

            <ConfirmationModal
                show={confirmingId !== null}
                title="Delete Referral"
                message="Are you sure you want to delete this referral? This action cannot be undone."
                onCancel={closeConfirm}
                onConfirm={confirmDelete}
            />
        </AdminLayout>
    );
}

