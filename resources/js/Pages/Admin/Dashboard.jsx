import AdminLayout from '@/Layouts/AdminLayout';
import { Head, Link } from '@inertiajs/react';

const statConfig = [
    { key: 'users', label: 'Users' },
    { key: 'admins', label: 'Admins' },
    { key: 'plans', label: 'Plans' },
    { key: 'subjects', label: 'Subjects' },
    { key: 'questions', label: 'Questions' },
    { key: 'quizAttempts', label: 'Quiz Attempts' },
];

export default function Dashboard({ stats, recentAttempts }) {
    return (
        <AdminLayout title="Dashboard">
            <Head title="Admin Dashboard" />

            <div className="grid gap-4 md:grid-cols-3">
                {statConfig.map((item) => (
                    <div
                        key={item.key}
                        className="rounded-xl bg-white px-5 py-4 shadow-sm"
                    >
                        <p className="text-sm text-slate-500">{item.label}</p>
                        <p className="mt-2 text-3xl font-semibold text-slate-900">
                            {stats[item.key]}
                        </p>
                    </div>
                ))}
            </div>

            <div className="rounded-xl bg-white p-5 shadow-sm">
                <div className="mb-4 flex items-center justify-between">
                    <h3 className="text-lg font-semibold text-slate-900">
                        Recent Quiz Attempts
                    </h3>
                    <Link
                        href={route('admin.quiz-attempts.index')}
                        className="text-sm text-sky-700 hover:text-sky-900"
                    >
                        View all
                    </Link>
                </div>

                {recentAttempts.length === 0 ? (
                    <p className="text-sm text-slate-500">
                        No quiz attempts recorded yet.
                    </p>
                ) : (
                    <div className="overflow-x-auto">
                        <table className="min-w-full text-left text-sm">
                            <thead className="text-slate-500">
                                <tr>
                                    <th className="px-3 py-2">User</th>
                                    <th className="px-3 py-2">Subject</th>
                                    <th className="px-3 py-2">Score</th>
                                    <th className="px-3 py-2">Date</th>
                                </tr>
                            </thead>
                            <tbody>
                                {recentAttempts.map((attempt) => (
                                    <tr
                                        key={attempt.id}
                                        className="border-t border-slate-200"
                                    >
                                        <td className="px-3 py-2">
                                            {attempt.user}
                                        </td>
                                        <td className="px-3 py-2">
                                            {attempt.subject}
                                        </td>
                                        <td className="px-3 py-2">
                                            {attempt.score} /{' '}
                                            {attempt.total_questions}
                                        </td>
                                        <td className="px-3 py-2">
                                            {attempt.created_at}
                                        </td>
                                    </tr>
                                ))}
                            </tbody>
                        </table>
                    </div>
                )}
            </div>
        </AdminLayout>
    );
}
