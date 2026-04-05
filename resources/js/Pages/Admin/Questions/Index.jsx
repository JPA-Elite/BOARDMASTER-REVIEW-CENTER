import Pagination from '@/Components/Admin/Pagination';
import ConfirmationModal from '@/Components/ConfirmationModal';
import InputError from '@/Components/InputError';
import InputLabel from '@/Components/InputLabel';
import AdminLayout from '@/Layouts/AdminLayout';
import { Head, Link, router, useForm } from '@inertiajs/react';
import { Pencil, Trash2 } from 'lucide-react';
import { useState } from 'react';

export default function Index({ questions, subjects = [], filters = {} }) {
    const [confirmingId, setConfirmingId] = useState(null);
    const [search, setSearch] = useState(filters.search ?? '');
    const [subjectId, setSubjectId] = useState(
        filters.subject_id ? String(filters.subject_id) : '',
    );
    const {
        data: importData,
        setData: setImportData,
        post: postImport,
        processing: importing,
        errors: importErrors,
        reset: resetImport,
    } = useForm({
        subject_id: '',
        import_file: null,
    });

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

    const submitImport = (event) => {
        event.preventDefault();

        postImport(route('admin.questions.import'), {
            forceFormData: true,
            onSuccess: () => {
                resetImport('import_file');
            },
        });
    };

    return (
        <AdminLayout title="Questions">
            <Head title="Admin Questions" />

            <div className="rounded-2xl border border-slate-200 bg-white p-5">
                <div className="mb-6 rounded-2xl border border-slate-200 bg-gradient-to-br from-slate-50 via-white to-sky-50 p-5">
                    <div className="flex flex-wrap items-center justify-between gap-3">
                        <div>
                            <p className="text-sm font-semibold text-slate-900">
                                Import Studio
                            </p>
                            <p className="text-xs text-slate-500">
                                Bring in bulk questions with a clean CSV layout and auto validation.
                            </p>
                        </div>
                        <div className="flex items-center gap-2">
                            <a
                                href={route('admin.questions.template')}
                                className="rounded-full border border-slate-200 bg-white px-3 py-1 text-xs font-semibold uppercase tracking-wide text-slate-600 hover:border-slate-300 hover:text-slate-900"
                            >
                                CSV Template
                            </a>
                            <a
                                href={route('admin.questions.template', {
                                    format: 'xlsx',
                                })}
                                className="rounded-full border border-slate-200 bg-white px-3 py-1 text-xs font-semibold uppercase tracking-wide text-slate-600 hover:border-slate-300 hover:text-slate-900"
                            >
                                XLSX Template
                            </a>
                            <span className="rounded-full border border-slate-200 bg-white px-3 py-1 text-xs font-semibold uppercase tracking-wide text-slate-500">
                                CSV / XLSX
                            </span>
                        </div>
                    </div>

                    <div className="mt-4 grid gap-6 lg:grid-cols-[1.2fr_1fr]">
                        <form onSubmit={submitImport} className="space-y-4">
                            <div>
                                <InputLabel htmlFor="import_subject_id" value="Import to Subject" />
                                <select
                                    id="import_subject_id"
                                    value={importData.subject_id}
                                    onChange={(event) =>
                                        setImportData('subject_id', event.target.value)
                                    }
                                    className="mt-1 block w-full rounded-md border-slate-300"
                                >
                                    <option value="">Select subject</option>
                                    {subjects.map((subject) => (
                                        <option key={subject.id} value={subject.id}>
                                            {subject.name}
                                        </option>
                                    ))}
                                </select>
                                <InputError message={importErrors.subject_id} className="mt-2" />
                            </div>

                            <div>
                                <InputLabel htmlFor="import_file" value="Upload CSV/XLSX File" />
                                <input
                                    id="import_file"
                                    type="file"
                                    accept=".csv,.xlsx,text/csv,application/vnd.openxmlformats-officedocument.spreadsheetml.sheet"
                                    disabled={!importData.subject_id}
                                    onChange={(event) =>
                                        setImportData(
                                            'import_file',
                                            event.target.files?.[0] || null,
                                        )
                                    }
                                    className="mt-1 block w-full rounded-md border border-dashed border-slate-300 bg-white px-3 py-2 text-sm file:mr-4 file:rounded-md file:border-0 file:bg-slate-900 file:px-3 file:py-1 file:text-xs file:font-semibold file:text-white disabled:cursor-not-allowed disabled:bg-slate-100"
                                />
                                <p className="mt-2 text-xs text-slate-500">
                                    Select a subject first, then upload the CSV template.
                                </p>
                                <InputError message={importErrors.import_file} className="mt-2" />
                            </div>

                            <div className="flex flex-wrap items-center gap-3">
                                <button
                                    type="submit"
                                    disabled={
                                        importing ||
                                        !importData.subject_id ||
                                        !importData.import_file
                                    }
                                    className="rounded-md bg-slate-900 px-4 py-2 text-sm text-white disabled:opacity-50"
                                >
                                    {importing ? 'Importing...' : 'Import Questions'}
                                </button>
                                <span className="text-xs text-slate-500">
                                    The importer uses your admin account as the author.
                                </span>
                            </div>
                        </form>

                        <div className="space-y-3 rounded-xl border border-slate-200 bg-white/70 p-4 text-sm text-slate-600">
                            <p className="text-xs font-semibold uppercase tracking-wide text-slate-500">
                                CSV Blueprint
                            </p>
                            <div className="rounded-lg border border-slate-200 bg-slate-900 text-slate-100">
                                <pre className="overflow-x-auto p-3 text-[11px] leading-5">
{`question,choice_a,choice_b,choice_c,choice_d,choice_e,choice_f,correct_label,explanation
"Which vitamin is fat-soluble?","Vitamin C","Vitamin A","Vitamin B1","Vitamin B6","","","B","Fat-soluble vitamins include A, D, E, K."
`}
                                </pre>
                            </div>
                            <p className="text-xs text-slate-500">
                                Required columns: question, choice_a, choice_b, correct_label.
                            </p>
                            <div className="text-xs text-slate-500">
                                Rules:
                            </div>
                            <ul className="space-y-1 text-xs text-slate-500">
                                <li>At least two choices are required per question.</li>
                                <li>Correct label must match one of the provided choices (A-F).</li>
                                <li>Explanation is optional and saved on the correct choice.</li>
                            </ul>
                        </div>
                    </div>
                </div>

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

