import InputError from '@/Components/InputError';
import InputLabel from '@/Components/InputLabel';
import AdminLayout from '@/Layouts/AdminLayout';
import { Head, useForm } from '@inertiajs/react';
import { ArrowLeft } from 'lucide-react';

export default function Import({ subjects = [] }) {
    const {
        data,
        setData,
        post,
        processing,
        errors,
        reset,
    } = useForm({
        subject_id: '',
        import_file: null,
    });

    const submit = (event) => {
        event.preventDefault();

        post(route('admin.questions.import'), {
            forceFormData: true,
            onSuccess: () => {
                reset('import_file');
            },
        });
    };

    return (
        <AdminLayout title="Import Questions">
            <Head title="Import Questions" />

            <div className="rounded-2xl border border-slate-200 bg-white p-5">
                <div className="mb-4">
                    <a
                        href={route('admin.questions.index')}
                        className="inline-flex items-center gap-2 text-sm font-semibold text-slate-600 hover:text-slate-900"
                    >
                        <ArrowLeft className="size-4" />
                        Back to Questions
                    </a>
                </div>
                <div className="rounded-2xl border border-slate-200 bg-gradient-to-br from-slate-50 via-white to-sky-50 p-5">
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
                        <form onSubmit={submit} className="space-y-4">
                            <div>
                                <InputLabel htmlFor="import_subject_id" value="Import to Subject" />
                                <select
                                    id="import_subject_id"
                                    value={data.subject_id}
                                    onChange={(event) =>
                                        setData('subject_id', event.target.value)
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
                                <InputError message={errors.subject_id} className="mt-2" />
                            </div>

                            <div>
                                <InputLabel htmlFor="import_file" value="Upload CSV/XLSX File" />
                                <input
                                    id="import_file"
                                    type="file"
                                    accept=".csv,.xlsx,text/csv,application/vnd.openxmlformats-officedocument.spreadsheetml.sheet"
                                    disabled={!data.subject_id}
                                    onChange={(event) =>
                                        setData(
                                            'import_file',
                                            event.target.files?.[0] || null,
                                        )
                                    }
                                    className="mt-1 block w-full rounded-md border border-dashed border-slate-300 bg-white px-3 py-2 text-sm file:mr-4 file:rounded-md file:border-0 file:bg-slate-900 file:px-3 file:py-1 file:text-xs file:font-semibold file:text-white disabled:cursor-not-allowed disabled:bg-slate-100"
                                />
                                <p className="mt-2 text-xs text-slate-500">
                                    Select a subject first, then upload the CSV template.
                                </p>
                                <InputError message={errors.import_file} className="mt-2" />
                            </div>

                            <div className="flex flex-wrap items-center gap-3">
                                <button
                                    type="submit"
                                    disabled={
                                        processing ||
                                        !data.subject_id ||
                                        !data.import_file
                                    }
                                    className="rounded-md bg-slate-900 px-4 py-2 text-sm text-white disabled:opacity-50"
                                >
                                    {processing ? 'Importing...' : 'Import Questions'}
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
            </div>
        </AdminLayout>
    );
}
