import Pagination from '@/Components/Admin/Pagination';
import ConfirmationModal from '@/Components/ConfirmationModal';
import AdminLayout from '@/Layouts/AdminLayout';
import SkeletonBox from '@/Components/Admin/SkeletonBox';
import { Head, Link, router } from '@inertiajs/react';
import { Trash2 } from 'lucide-react';
import { useState, useEffect } from 'react';

export default function Index({ attempts }) {
    const [confirmingId, setConfirmingId] = useState(null);
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

        router.delete(route('admin.quiz-attempts.destroy', confirmingId), {
            onFinish: closeConfirm,
        });
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
                            {isNavigating
                                ? Array.from({ length: 10 }).map((_, i) => (
                                    <tr key={i} className="border-t border-slate-200">
                                        <td className="px-3 py-2">
                                            <SkeletonBox width="w-24" height="h-4" className="mb-1" />
                                            <SkeletonBox width="w-32" height="h-3" />
                                        </td>
                                        <td className="px-3 py-2">
                                            <SkeletonBox width="w-20" height="h-4" />
                                        </td>
                                        <td className="px-3 py-2">
                                            <SkeletonBox width="w-12" height="h-4" />
                                        </td>
                                        <td className="px-3 py-2">
                                            <SkeletonBox width="w-8" height="h-4" />
                                        </td>
                                        <td className="px-3 py-2">
                                            <SkeletonBox width="w-24" height="h-4" />
                                        </td>
                                        <td className="px-3 py-2">
                                            <SkeletonBox width="w-8" height="w-8" rounded="rounded-md" />
                                        </td>
                                    </tr>
                                ))
                                : attempts.data.map((attempt) => (
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
                                                onClick={() =>
                                                    openConfirm(attempt.id)
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
                            {!isNavigating && attempts.data.length === 0 && (
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

            <ConfirmationModal
                show={confirmingId !== null}
                title="Delete Quiz Attempt"
                message="Are you sure you want to delete this quiz attempt? This action cannot be undone."
                onCancel={closeConfirm}
                onConfirm={confirmDelete}
            />
        </AdminLayout>
    );
}

