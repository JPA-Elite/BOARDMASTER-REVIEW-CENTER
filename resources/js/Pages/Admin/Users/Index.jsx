import Pagination from '@/Components/Admin/Pagination';
import AdminLayout from '@/Layouts/AdminLayout';
import { Head, Link, router } from '@inertiajs/react';
import { useState } from 'react';

export default function Index({ users, filters }) {
    const [search, setSearch] = useState(filters.search || '');

    const submitSearch = (event) => {
        event.preventDefault();
        router.get(
            route('admin.users.index'),
            { search },
            { preserveState: true, replace: true },
        );
    };

    const destroy = (id) => {
        if (!window.confirm('Delete this user?')) {
            return;
        }

        router.delete(route('admin.users.destroy', id));
    };

    return (
        <AdminLayout title="Users">
            <Head title="Admin Users" />

            <div className="rounded-xl bg-white p-5 shadow-sm">
                <div className="mb-4 flex flex-wrap items-center justify-between gap-3">
                    <form onSubmit={submitSearch} className="flex gap-2">
                        <input
                            value={search}
                            onChange={(event) => setSearch(event.target.value)}
                            className="rounded-md border border-slate-300 px-3 py-2 text-sm"
                            placeholder="Search users..."
                        />
                        <button
                            type="submit"
                            className="rounded-md bg-slate-900 px-4 py-2 text-sm text-white"
                        >
                            Search
                        </button>
                    </form>

                    <Link
                        href={route('admin.users.create')}
                        className="rounded-md bg-sky-700 px-4 py-2 text-sm text-white hover:bg-sky-800"
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
                                    <td className="px-3 py-2">{user.name}</td>
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
                                                className="text-sky-700 hover:text-sky-900"
                                            >
                                                Edit
                                            </Link>
                                            <button
                                                type="button"
                                                onClick={() => destroy(user.id)}
                                                className="text-red-700 hover:text-red-900"
                                            >
                                                Delete
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
        </AdminLayout>
    );
}
