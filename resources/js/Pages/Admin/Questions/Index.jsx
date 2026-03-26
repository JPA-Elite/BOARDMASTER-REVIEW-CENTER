import Pagination from '@/Components/Admin/Pagination';
import ConfirmationModal from '@/Components/ConfirmationModal';
import AdminLayout from '@/Layouts/AdminLayout';
import { Head, Link, router } from '@inertiajs/react';
import { Pencil, Trash2 } from 'lucide-react';
import { useState } from 'react';

export default function Index({ questions, subjects = [], filters = {} }) {
    const [confirmingId, setConfirmingId] = useState(null);
    const [search, setSearch] = useState(filters.search ?? '');
    const [subjectId, setSubjectId] = useState(
        filters.subject_id ? String(filters.subject_id) : '',
    );

    const openConfirm = (id) => setConfirmingId(id);
    const closeConfirm = () => setConfirmingId(null);

    const confirmDelete = () => {
        if (!confirmingId) {
            return;
        }

        router.delete(route('admin.questions.destroy', confirmingId), {
            onFinish: closeConfirm,
        });
    };

    const applyFilters = (next = {}) => {
        const payload = {
            search,
            subject_id: subjectId || null,
            ...next,
        };

        router.get(route('admin.questions.index'), payload, {
            preserveState: true,
            replace: true,
            preserveScroll: true,
        });
    };

    const submitSearch = (event) => {
        event.preventDefault();
        applyFilters();
    };

    const resetFilters = () => {
        setSearch('');
        setSubjectId('');
        router.get(
            route('admin.questions.index'),
            {},
            {
                preserveState: true,
                replace: true,
                preserveScroll: true,
            },
        );
    };

    return (
        <AdminLayout title="Questions">
            <Head title="Admin Questions" />

            <div className="rounded-2xl border border-slate-200 bg-white p-5">
                <div className="mb-4 flex flex-wrap items-center justify-between gap-3">
                    <form
                        onSubmit={submitSearch}
                        className="flex flex-wrap items-center gap-2"
                    >
                        <input
                            type="text"
                            value={search}
                            onChange={(event) => setSearch(event.target.value)}
                            placeholder="Search question or subject"
                            className="w-full rounded-md border border-slate-300 px-3 py-2 text-sm sm:w-64"
                        />
                        <select
                            value={subjectId}
                            onChange={(event) => {
                                setSubjectId(event.target.value);
                                applyFilters({ subject_id: event.target.value });
                            }}
                            className="w-full rounded-md border border-slate-300 px-3 py-2 text-sm sm:w-56"
                        >
                            <option value="">All subjects</option>
                            {subjects.map((subject) => (
                                <option key={subject.id} value={subject.id}>
                                    {subject.name}
                                </option>
                            ))}
                        </select>
                        <button
                            type="submit"
                            className="rounded-md bg-slate-900 px-4 py-2 text-sm text-white"
                        >
                            Search
                        </button>
                        <button
                            type="button"
                            onClick={resetFilters}
                            className="rounded-md border border-slate-300 px-3 py-2 text-sm text-slate-700 hover:bg-slate-100"
                        >
                            Clear
                        </button>
                    </form>
                    <Link
                        href={route('admin.questions.create')}
                        className="rounded-md bg-sky-700 px-4 py-2 text-sm text-white hover:bg-sky-800"
                    >
                        New Question
                    </Link>
                </div>

                <div className="overflow-x-auto">
                    <table className="min-w-full text-left text-sm">
                        <thead className="text-slate-500">
                            <tr>
                                <th className="px-3 py-2">Subject</th>
                                <th className="px-3 py-2">Question</th>
                                <th className="px-3 py-2">Created By</th>
                                <th className="px-3 py-2">Choices</th>
                                <th className="px-3 py-2">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            {questions.data.map((question) => (
                                <tr
                                    key={question.id}
                                    className="border-t border-slate-200"
                                >
                                    <td className="px-3 py-2">
                                        {question.subject}
                                    </td>
                                    <td className="px-3 py-2">
                                        {question.question}
                                    </td>
                                    <td className="px-3 py-2">
                                        {question.created_by || '-'}
                                    </td>
                                    <td className="px-3 py-2">
                                        {question.choices_count}
                                    </td>
                                    <td className="px-3 py-2">
                                        <div className="flex gap-2">
                                            <Link
                                                href={route(
                                                    'admin.questions.edit',
                                                    question.id,
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
                                                    openConfirm(question.id)
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
                            {questions.data.length === 0 && (
                                <tr>
                                    <td
                                        colSpan={5}
                                        className="px-3 py-4 text-center text-slate-500"
                                    >
                                        No questions found.
                                    </td>
                                </tr>
                            )}
                        </tbody>
                    </table>
                </div>

                <div className="mt-4">
                    <Pagination links={questions.links} />
                </div>
            </div>

            <ConfirmationModal
                show={confirmingId !== null}
                title="Delete Question"
                message="Are you sure you want to delete this question? This action cannot be undone."
                onCancel={closeConfirm}
                onConfirm={confirmDelete}
            />
        </AdminLayout>
    );
}

