import Pagination from '@/Components/Admin/Pagination';
import ConfirmationModal from '@/Components/ConfirmationModal';
import AdminLayout from '@/Layouts/AdminLayout';
import { Head, router } from '@inertiajs/react';
import { Trash2 } from 'lucide-react';
import { useState } from 'react';

export default function Index({ reviews }) {
    const [confirmingId, setConfirmingId] = useState(null);

    const openConfirm = (id) => setConfirmingId(id);
    const closeConfirm = () => setConfirmingId(null);

    const confirmDelete = () => {
        if (!confirmingId) {
            return;
        }

        router.delete(route('admin.reviews.destroy', confirmingId), {
            onFinish: closeConfirm,
        });
    };

    return (
        <AdminLayout title="Reviews">
            <Head title="Admin Reviews" />

            <div className="rounded-2xl border border-slate-200 bg-white p-5">
                <div className="overflow-x-auto">
                    <table className="min-w-full text-left text-sm">
                        <thead className="text-slate-500">
                            <tr>
                                <th className="px-3 py-2">Reviewer</th>
                                <th className="px-3 py-2">School</th>
                                <th className="px-3 py-2">Quote</th>
                                <th className="px-3 py-2">Status</th>
                                <th className="px-3 py-2">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            {reviews.data.map((review) => (
                                <tr key={review.id} className="border-t border-slate-200">
                                    <td className="px-3 py-2">
                                        <p className="font-medium text-slate-900">
                                            {review.reviewer_name}
                                        </p>
                                        <p className="text-xs text-slate-500">
                                            Added {review.created_at}
                                        </p>
                                    </td>
                                    <td className="px-3 py-2">
                                        {review.reviewer_school || '—'}
                                    </td>
                                    <td className="px-3 py-2 text-slate-600">
                                        "{review.quote}"
                                    </td>
                                    <td className="px-3 py-2">
                                        <span
                                            className={`rounded-full px-2 py-1 text-xs font-semibold ${
                                                review.is_active
                                                    ? 'bg-emerald-100 text-emerald-700'
                                                    : 'bg-slate-100 text-slate-500'
                                            }`}
                                        >
                                            {review.is_active ? 'Active' : 'Hidden'}
                                        </span>
                                    </td>
                                    <td className="px-3 py-2">
                                        <button
                                            type="button"
                                            onClick={() => openConfirm(review.id)}
                                            className="inline-flex h-8 w-8 items-center justify-center rounded-md text-red-700 hover:bg-red-50 hover:text-red-900"
                                            title="Delete"
                                            aria-label="Delete"
                                        >
                                            <Trash2 className="size-4" />
                                        </button>
                                    </td>
                                </tr>
                            ))}
                            {reviews.data.length === 0 && (
                                <tr>
                                    <td
                                        colSpan={5}
                                        className="px-3 py-4 text-center text-slate-500"
                                    >
                                        No reviews available.
                                    </td>
                                </tr>
                            )}
                        </tbody>
                    </table>
                </div>

                <div className="mt-4">
                    <Pagination links={reviews.links} />
                </div>
            </div>

            <ConfirmationModal
                show={confirmingId !== null}
                title="Delete Review"
                message="Are you sure you want to delete this review? This action cannot be undone."
                onCancel={closeConfirm}
                onConfirm={confirmDelete}
            />
        </AdminLayout>
    );
}
