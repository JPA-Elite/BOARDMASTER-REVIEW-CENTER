import Pagination from '@/Components/Admin/Pagination';
import AdminLayout from '@/Layouts/AdminLayout';
import { Head, Link, router } from '@inertiajs/react';

export default function Index({ attempts }) {
    const destroy = (id) => {
        if (!window.confirm('Delete this quiz attempt?')) {
            return;
        }

        router.delete(route('admin.quiz-attempts.destroy', id));
    };

    return (
        <AdminLayout title="Quiz Attempts">
            <Head title="Admin Quiz Attempts" />

            <div className="rounded-2xl border border-slate-200 bg-white p-5">
                <div className="overflow-x-auto">
                    <table className="min-w-full text-left text-sm">
                        <thead className="text-slate-500">
                            <tr>
                                <th className="px-3 py-2">User</th>
                                <th className="px-3 py-2">Subject</th>
                                <th className="px-3 py-2">Score</th>
                                <th className="px-3 py-2">Answers</th>
                                <th className="px-3 py-2">Date</th>
                                <th className="px-3 py-2">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            {attempts.data.map((attempt) => (
                                <tr
                                    key={attempt.id}
                                    className="border-t border-slate-200"
                                >
                                    <td className="px-3 py-2">
                                        <p>{attempt.user}</p>
                                        <p className="text-xs text-slate-500">
                                            {attempt.user_email}
                                        </p>
                                    </td>
                                    <td className="px-3 py-2">
                                        {attempt.subject}
                                    </td>
                                    <td className="px-3 py-2">
                                        {attempt.score} / {attempt.total_questions}
                                    </td>
                                    <td className="px-3 py-2">
                                        {attempt.answers_count}
                                    </td>
                                    <td className="px-3 py-2">
                                        {attempt.created_at}
                                    </td>
                                    <td className="px-3 py-2">
                                        <div className="flex gap-2">
                                            <Link
                                                href={route(
                                                    'admin.quiz-attempts.show',
                                                    attempt.id,
                                                )}
                                                className="text-sky-700 hover:text-sky-900"
                                            >
                                                View
                                            </Link>
                                            <button
                                                type="button"
                                                onClick={() => destroy(attempt.id)}
                                                className="text-red-700 hover:text-red-900"
                                            >
                                                Delete
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                            ))}
                            {attempts.data.length === 0 && (
                                <tr>
                                    <td
                                        colSpan={6}
                                        className="px-3 py-4 text-center text-slate-500"
                                    >
                                        No quiz attempts yet.
                                    </td>
                                </tr>
                            )}
                        </tbody>
                    </table>
                </div>

                <div className="mt-4">
                    <Pagination links={attempts.links} />
                </div>
            </div>
        </AdminLayout>
    );
}

