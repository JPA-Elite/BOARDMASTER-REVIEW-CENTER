import Pagination from '@/Components/Admin/Pagination';
import AdminLayout from '@/Layouts/AdminLayout';
import { Head, Link, router } from '@inertiajs/react';

export default function Index({ subjects }) {
    const destroy = (id) => {
        if (!window.confirm('Delete this subject?')) {
            return;
        }

        router.delete(route('admin.subjects.destroy', id));
    };

    return (
        <AdminLayout title="Subjects">
            <Head title="Admin Subjects" />

            <div className="rounded-2xl border border-slate-200 bg-white p-5">
                <div className="mb-4 flex justify-end">
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
                                                className="text-sky-700 hover:text-sky-900"
                                            >
                                                Edit
                                            </Link>
                                            <button
                                                type="button"
                                                onClick={() => destroy(subject.id)}
                                                className="text-red-700 hover:text-red-900"
                                            >
                                                Delete
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
        </AdminLayout>
    );
}

