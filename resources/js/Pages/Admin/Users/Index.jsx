import Pagination from '@/Components/Admin/Pagination';
import ConfirmationModal from '@/Components/ConfirmationModal';
import Modal from '@/Components/Modal';
import AdminLayout from '@/Layouts/AdminLayout';
import { Head, Link, router } from '@inertiajs/react';
import { ChevronDown, Pencil, Trash2 } from 'lucide-react';
import { useState } from 'react';

export default function Index({ users, filters }) {
    const [search, setSearch] = useState(filters.search || '');
    const [perPage, setPerPage] = useState(
        filters.per_page ? String(filters.per_page) : '15',
    );
    const [confirmingId, setConfirmingId] = useState(null);
    const [previewUser, setPreviewUser] = useState(null);

    const submitSearch = (event) => {
        event.preventDefault();
        router.get(
            route('admin.users.index'),
            { search, per_page: perPage },
            { preserveState: true, replace: true },
        );
    };

    const applyPerPage = (value) => {
        setPerPage(value);
        router.get(
            route('admin.users.index'),
            { search, per_page: value },
            { preserveState: true, replace: true },
        );
    };

    const openConfirm = (id) => setConfirmingId(id);
    const closeConfirm = () => setConfirmingId(null);
    const closePreview = () => setPreviewUser(null);

    const confirmDelete = () => {
        if (!confirmingId) {
            return;
        }

        router.delete(route('admin.users.destroy', confirmingId), {
            onFinish: closeConfirm,
        });
    };

    return (
        <AdminLayout title="Users">
            <Head title="Admin Users" />

            <div className="rounded-2xl border border-slate-200 bg-white p-5">
                <div className="mb-4 flex flex-wrap items-center justify-between gap-3">
                    <label className="order-1 flex items-center gap-2 text-sm text-slate-600">
                        <div className="group relative">
                            <select
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
                    </label>

                    <form
                        onSubmit={submitSearch}
                        className="order-3 flex w-full gap-2 sm:order-2 sm:w-auto"
                    >
                        <input
                            value={search}
                            onChange={(event) => setSearch(event.target.value)}
                            className="w-full flex-1 rounded-md border border-slate-300 px-3 py-2 text-sm sm:w-64 sm:flex-none"
                            placeholder="Search users..."
                        />
                        <button
                            type="submit"
                            className="shrink-0 rounded-md bg-slate-900 px-4 py-2 text-sm text-white"
                        >
                            Search
                        </button>
                    </form>

                    <Link
                        href={route('admin.users.create')}
                        className="order-2 ml-auto rounded-md bg-sky-700 px-4 py-2 text-sm text-white hover:bg-sky-800 sm:order-3"
                    >
                        New User
                    </Link>
                </div>

                <div className="overflow-x-auto">
                    <table className="min-w-full text-left text-sm">
                        <thead className="text-slate-500">
                            <tr>
                                <th className="px-3 py-2">Name</th>
                                <th className="px-3 py-2">Email</th>
                                <th className="px-3 py-2">Role</th>
                                <th className="px-3 py-2">Referral Code</th>
                                <th className="px-3 py-2">Referred By</th>
                                <th className="px-3 py-2">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            {users.data.map((user) => (
                                <tr
                                    key={user.id}
                                    className="border-t border-slate-200"
                                >
                                    <td className="px-3 py-2">
                                        <div className="flex items-center gap-3">
                                            <button
                                                type="button"
                                                onClick={() => setPreviewUser(user)}
                                                className="flex h-9 w-9 items-center justify-center overflow-hidden rounded-full border border-slate-200 bg-slate-100 text-xs font-semibold text-slate-600"
                                                title="Preview photo"
                                            >
                                                {user.avatar_url ? (
                                                    <img
                                                        src={user.avatar_url}
                                                        alt={user.name}
                                                        className="h-full w-full object-cover"
                                                    />
                                                ) : (
                                                    user.name
                                                        ?.split(' ')
                                                        .map((part) => part[0])
                                                        .join('')
                                                        .slice(0, 2)
                                                        .toUpperCase()
                                                )}
                                            </button>
                                            <span>{user.name}</span>
                                        </div>
                                    </td>
                                    <td className="px-3 py-2">{user.email}</td>
                                    <td className="px-3 py-2 uppercase">
                                        {user.role}
                                    </td>
                                    <td className="px-3 py-2">
                                        {user.referral_code || '-'}
                                    </td>
                                    <td className="px-3 py-2">
                                        {user.referred_by || '-'}
                                    </td>
                                    <td className="px-3 py-2">
                                        <div className="flex gap-2">
                                            <Link
                                                href={route(
                                                    'admin.users.edit',
                                                    user.id,
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
                                                    openConfirm(user.id)
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
                            {users.data.length === 0 && (
                                <tr>
                                    <td
                                        colSpan={6}
                                        className="px-3 py-4 text-center text-slate-500"
                                    >
                                        No users found.
                                    </td>
                                </tr>
                            )}
                        </tbody>
                    </table>
                </div>

                <div className="mt-4">
                    <Pagination links={users.links} />
                </div>
            </div>

            <ConfirmationModal
                show={confirmingId !== null}
                title="Delete User"
                message="Are you sure you want to delete this user? This action cannot be undone."
                onCancel={closeConfirm}
                onConfirm={confirmDelete}
            />

            <Modal show={previewUser !== null} onClose={closePreview} maxWidth="lg">
                <div className="p-6">
                    {previewUser ? (
                        <>
                            <div className="flex items-start justify-between">
                                <div>
                                    <h2 className="text-lg font-semibold text-slate-900">
                                        {previewUser?.name}
                                    </h2>
                                    <p className="text-sm text-slate-500">
                                        {previewUser?.email}
                                    </p>
                                </div>
                                <button
                                    type="button"
                                    onClick={closePreview}
                                    className="rounded-md border border-slate-200 px-3 py-1 text-sm text-slate-600"
                                >
                                    Close
                                </button>
                            </div>
                            <div className="mt-4 flex items-center justify-center">
                                {previewUser?.avatar_url ? (
                                    <img
                                        src={previewUser.avatar_url}
                                        alt={previewUser.name}
                                        className="max-h-[360px] w-full rounded-xl object-cover"
                                    />
                                ) : (
                                    <div className="flex h-48 w-full items-center justify-center rounded-xl border border-dashed border-slate-300 bg-slate-50 text-sm text-slate-500">
                                        No profile photo uploaded.
                                    </div>
                                )}
                            </div>
                        </>
                    ) : (
                        <div className="h-72" />
                    )}
                </div>
            </Modal>
        </AdminLayout>
    );
}

