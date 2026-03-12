import AdminLayout from '@/Layouts/AdminLayout';
import { Head, Link } from '@inertiajs/react';

export default function Show({ attempt }) {
    return (
        <AdminLayout title={`Quiz Attempt #${attempt.id}`}>
            <Head title={`Quiz Attempt #${attempt.id}`} />

            <div className="space-y-4 rounded-xl bg-white p-5 shadow-sm">
                <div className="grid gap-4 md:grid-cols-2">
                    <div>
                        <p className="text-sm text-slate-500">User</p>
                        <p className="font-medium text-slate-900">
                            {attempt.user}
                        </p>
                        <p className="text-sm text-slate-500">
                            {attempt.user_email}
                        </p>
                    </div>
                    <div>
                        <p className="text-sm text-slate-500">Subject</p>
                        <p className="font-medium text-slate-900">
                            {attempt.subject}
                        </p>
                    </div>
                    <div>
                        <p className="text-sm text-slate-500">Score</p>
                        <p className="font-medium text-slate-900">
                            {attempt.score} / {attempt.total_questions}
                        </p>
                    </div>
                    <div>
                        <p className="text-sm text-slate-500">Date</p>
                        <p className="font-medium text-slate-900">
                            {attempt.created_at}
                        </p>
                    </div>
                </div>

                <div className="overflow-x-auto">
                    <table className="min-w-full text-left text-sm">
                        <thead className="text-slate-500">
                            <tr>
                                <th className="px-3 py-2">Question</th>
                                <th className="px-3 py-2">Selected Choice</th>
                                <th className="px-3 py-2">Result</th>
                            </tr>
                        </thead>
                        <tbody>
                            {attempt.answers.map((answer) => (
                                <tr
                                    key={answer.id}
                                    className="border-t border-slate-200"
                                >
                                    <td className="px-3 py-2">
                                        {answer.question}
                                    </td>
                                    <td className="px-3 py-2">
                                        {answer.selected_choice || '-'}
                                    </td>
                                    <td className="px-3 py-2">
                                        <span
                                            className={
                                                answer.is_correct
                                                    ? 'text-emerald-700'
                                                    : 'text-red-700'
                                            }
                                        >
                                            {answer.is_correct
                                                ? 'Correct'
                                                : 'Incorrect'}
                                        </span>
                                    </td>
                                </tr>
                            ))}
                            {attempt.answers.length === 0 && (
                                <tr>
                                    <td
                                        colSpan={3}
                                        className="px-3 py-4 text-center text-slate-500"
                                    >
                                        No answers found for this attempt.
                                    </td>
                                </tr>
                            )}
                        </tbody>
                    </table>
                </div>

                <div>
                    <Link
                        href={route('admin.quiz-attempts.index')}
                        className="rounded-md border border-slate-300 px-4 py-2 text-sm text-slate-700"
                    >
                        Back to Attempts
                    </Link>
                </div>
            </div>
        </AdminLayout>
    );
}
