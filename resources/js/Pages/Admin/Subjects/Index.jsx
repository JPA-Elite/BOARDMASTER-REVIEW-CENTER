import Pagination from '@/Components/Admin/Pagination';
import ConfirmationModal from '@/Components/ConfirmationModal';
import AdminLayout from '@/Layouts/AdminLayout';
import { Head, Link, router } from '@inertiajs/react';
import { ChevronDown, Pencil, Trash2 } from 'lucide-react';
import { useState } from 'react';

export default function Index({ subjects, filters = {} }) {
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

        router.delete(route('admin.subjects.destroy', confirmingId), {
            onFinish: closeConfirm,
        });
    };

    const applyPerPage = (value) => {
        setPerPage(value);
        router.get(
            route('admin.subjects.index'),
            { per_page: value },
            { preserveState: true, replace: true },
        );
    };

    return (
        <AdminLayout title="Subjects">
            <Head title="Admin Subjects" />

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
                        href={route('admin.subjects.create')}
                        className="rounded-md bg-sky-700 px-4 py-2 text-sm text-white hover:bg-sky-800"
                    >
                        New Subject
                    </Link>
                </div>

                <div className="overflow-x-auto">
                    <table className="min-w-full text-left text-sm">
                        <thead className="text-slate-500">
                            <tr>
                                <th className="px-3 py-2">Name</th>
                                <th className="px-3 py-2">Questions</th>
                                <th className="px-3 py-2">Quiz Attempts</th>
                                <th className="px-3 py-2">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            {subjects.data.map((subject) => (
                                <tr
                                    key={subject.id}
                                    className="border-t border-slate-200"
                                >
                                    <td className="px-3 py-2">{subject.name}</td>
                                    <td className="px-3 py-2">
                                        {subject.questions_count}
                                    </td>
                                    <td className="px-3 py-2">
                                        {subject.quiz_attempts_count}
                                    </td>
                                    <td className="px-3 py-2">
                                        <div className="flex gap-2">
                                            <Link
                                                href={route(
                                                    'admin.subjects.edit',
                                                    subject.id,
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
                                                    openConfirm(subject.id)
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
                            {subjects.data.length === 0 && (
                                <tr>
                                    <td
                                        colSpan={4}
                                        className="px-3 py-4 text-center text-slate-500"
                                    >
                                        No subjects found.
                                    </td>
                                </tr>
                            )}
                        </tbody>
                    </table>
                </div>

                <div className="mt-4">
                    <Pagination links={subjects.links} />
                </div>
            </div>

            <ConfirmationModal
                show={confirmingId !== null}
                title="Delete Subject"
                message="Are you sure you want to delete this subject? This action cannot be undone."
                onCancel={closeConfirm}
                onConfirm={confirmDelete}
            />
        </AdminLayout>
    );
}

