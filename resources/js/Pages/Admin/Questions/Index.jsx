import Pagination from '@/Components/Admin/Pagination';
import ConfirmationModal from '@/Components/ConfirmationModal';
import AdminLayout from '@/Layouts/AdminLayout';
import SkeletonBox from '@/Components/Admin/SkeletonBox';
import { Head, Link, router } from '@inertiajs/react';
import { ChevronDown, Pencil, Plus, Trash2, Upload } from 'lucide-react';
import { useState, useEffect } from 'react';

export default function Index({ questions, subjects = [], filters = {} }) {
    const [confirmingId, setConfirmingId] = useState(null);
    const [search, setSearch] = useState(filters.search ?? '');
    const [subjectId, setSubjectId] = useState(
        filters.subject_id ? String(filters.subject_id) : '',
    );
    const [perPage, setPerPage] = useState(
        filters.per_page ? String(filters.per_page) : '15',
    );
    const [isNavigating, setIsNavigating] = useState(false);

    useEffect(() => {
        if (typeof window === 'undefined' || !window.Inertia) {
            return;
        }

        const handleStart = () => setIsNavigating(true);
        const handleFinish = () => setIsNavigating(false);

        window.Inertia.on('start', handleStart);
        window.Inertia.on('finish', handleFinish);

        return () => {
            window.Inertia.off('start', handleStart);
            window.Inertia.off('finish', handleFinish);
        };
    }, []);

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
            per_page: perPage,
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
            { per_page: perPage },
            {
                preserveState: true,
                replace: true,
                preserveScroll: true,
            },
        );
    };

    const applyPerPage = (value) => {
        setPerPage(value);
        applyFilters({ per_page: value });
    };

    return (
        <AdminLayout title="Questions">
            <Head title="Admin Questions" />

            <div className="rounded-2xl border border-slate-200 bg-white p-5">
                <div className="mb-4 flex flex-wrap items-center justify-between gap-3">
                    <form
                        onSubmit={submitSearch}
                        className="flex w-full flex-wrap items-center gap-2"
                    >
                        <div className="order-1 group relative">
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
                        <div className="order-2 ml-auto flex gap-2 sm:order-6">
                            <Link
                                href={route('admin.questions.import-form')}
                                aria-label="Import Questions"
                                className="inline-flex items-center justify-center rounded-md border border-slate-300 px-4 py-2 text-sm text-slate-700 hover:bg-slate-100"
                            >
                                <Upload className="size-4 sm:hidden" />
                                <span className="hidden sm:inline">
                                    Import Questions
                                </span>
                            </Link>
                            <Link
                                href={route('admin.questions.create')}
                                aria-label="New Question"
                                className="inline-flex items-center justify-center rounded-md bg-sky-700 px-4 py-2 text-sm text-white hover:bg-sky-800"
                            >
                                <Plus className="size-4 sm:hidden" />
                                <span className="hidden sm:inline">
                                    New Question
                                </span>
                            </Link>
                        </div>
                        <input
                            type="text"
                            value={search}
                            onChange={(event) => setSearch(event.target.value)}
                            placeholder="Search question or subject"
                            className="order-3 w-full rounded-md border border-slate-300 px-3 py-2 text-sm sm:order-2 sm:w-64"
                        />
                        <select
                            value={subjectId}
                            onChange={(event) => {
                                setSubjectId(event.target.value);
                                applyFilters({ subject_id: event.target.value });
                            }}
                            className="order-4 w-full rounded-md border border-slate-300 px-3 py-2 text-sm sm:order-3 sm:w-56"
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
                            className="order-5 rounded-md bg-slate-900 px-4 py-2 text-sm text-white sm:order-4"
                        >
                            Search
                        </button>
                        <button
                            type="button"
                            onClick={resetFilters}
                            className="order-6 rounded-md border border-slate-300 px-3 py-2 text-sm text-slate-700 hover:bg-slate-100 sm:order-5"
                        >
                            Clear
                        </button>
                    </form>
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
                            {isNavigating
                                ? Array.from({ length: Number(perPage) || 15 }).map((_, i) => (
                                    <tr key={i} className="border-t border-slate-200">
                                        <td className="px-3 py-2">
                                            <SkeletonBox width="w-24" height="h-4" />
                                        </td>
                                        <td className="px-3 py-2">
                                            <SkeletonBox width="w-48" height="h-4" />
                                        </td>
                                        <td className="px-3 py-2">
                                            <SkeletonBox width="w-24" height="h-4" />
                                        </td>
                                        <td className="px-3 py-2">
                                            <SkeletonBox width="w-12" height="h-5" rounded="rounded-full" />
                                        </td>
                                        <td className="px-3 py-2">
                                            <div className="flex gap-2">
                                                <SkeletonBox width="w-8" height="w-8" rounded="rounded-md" />
                                                <SkeletonBox width="w-8" height="w-8" rounded="rounded-md" />
                                            </div>
                                        </td>
                                    </tr>
                                ))
                                : questions.data.map((question) => (
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
                            {!isNavigating && questions.data.length === 0 && (
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

