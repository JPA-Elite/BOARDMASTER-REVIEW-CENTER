import Pagination from '@/Components/Admin/Pagination';
import ConfirmationModal from '@/Components/ConfirmationModal';
import AdminLayout from '@/Layouts/AdminLayout';
import { Head, Link, router } from '@inertiajs/react';
import { Pencil, Trash2 } from 'lucide-react';
import { useState } from 'react';

export default function Index({ faqs }) {
    const [confirmingId, setConfirmingId] = useState(null);

    const openConfirm = (id) => setConfirmingId(id);
    const closeConfirm = () => setConfirmingId(null);

    const confirmDelete = () => {
        if (!confirmingId) {
            return;
        }

        router.delete(route('admin.faqs.destroy', confirmingId), {
            onFinish: closeConfirm,
        });
    };

    return (
        <AdminLayout title="FAQs">
            <Head title="Admin FAQs" />

            <div className="rounded-2xl border border-slate-200 bg-white p-5">
                <div className="mb-4 flex justify-end">
                    <Link
                        href={route('admin.faqs.create')}
                        className="rounded-md bg-sky-700 px-4 py-2 text-sm text-white hover:bg-sky-800"
                    >
                        New FAQ
                    </Link>
                </div>

                <div className="overflow-x-auto">
                    <table className="min-w-full text-left text-sm">
                        <thead className="text-slate-500">
                            <tr>
                                <th className="px-3 py-2">Question</th>
                                <th className="px-3 py-2">Status</th>
                                <th className="px-3 py-2">Order</th>
                                <th className="px-3 py-2">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            {faqs.data.map((faq) => (
                                <tr key={faq.id} className="border-t border-slate-200">
                                    <td className="px-3 py-2">
                                        <p className="font-medium text-slate-900">
                                            {faq.question}
                                        </p>
                                        <p className="mt-1 text-xs text-slate-500">
                                            {faq.answer}
                                        </p>
                                    </td>
                                    <td className="px-3 py-2">
                                        <span
                                            className={`rounded-full px-2 py-1 text-xs font-semibold ${
                                                faq.is_active
                                                    ? 'bg-emerald-100 text-emerald-700'
                                                    : 'bg-slate-100 text-slate-500'
                                            }`}
                                        >
                                            {faq.is_active ? 'Active' : 'Hidden'}
                                        </span>
                                    </td>
                                    <td className="px-3 py-2">{faq.sort_order}</td>
                                    <td className="px-3 py-2">
                                        <div className="flex gap-2">
                                            <Link
                                                href={route('admin.faqs.edit', faq.id)}
                                                className="inline-flex h-8 w-8 items-center justify-center rounded-md text-sky-700 hover:bg-sky-50 hover:text-sky-900"
                                                title="Edit"
                                                aria-label="Edit"
                                            >
                                                <Pencil className="size-4" />
                                            </Link>
                                            <button
                                                type="button"
                                                onClick={() => openConfirm(faq.id)}
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
                            {faqs.data.length === 0 && (
                                <tr>
                                    <td
                                        colSpan={4}
                                        className="px-3 py-4 text-center text-slate-500"
                                    >
                                        No FAQs available.
                                    </td>
                                </tr>
                            )}
                        </tbody>
                    </table>
                </div>

                <div className="mt-4">
                    <Pagination links={faqs.links} />
                </div>
            </div>

            <ConfirmationModal
                show={confirmingId !== null}
                title="Delete FAQ"
                message="Are you sure you want to delete this FAQ? This action cannot be undone."
                onCancel={closeConfirm}
                onConfirm={confirmDelete}
            />
        </AdminLayout>
    );
}
