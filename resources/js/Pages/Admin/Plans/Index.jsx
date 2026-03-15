import Pagination from '@/Components/Admin/Pagination';
import ConfirmationModal from '@/Components/ConfirmationModal';
import AdminLayout from '@/Layouts/AdminLayout';
import { Head, Link, router } from '@inertiajs/react';
import { Pencil, Trash2 } from 'lucide-react';
import { useState } from 'react';

export default function Index({ plans }) {
    const [confirmingId, setConfirmingId] = useState(null);

    const openConfirm = (id) => setConfirmingId(id);
    const closeConfirm = () => setConfirmingId(null);

    const confirmDelete = () => {
        if (!confirmingId) {
            return;
        }

        router.delete(route('admin.plans.destroy', confirmingId), {
            onFinish: closeConfirm,
        });
    };

    return (
        <AdminLayout title="Plans">
            <Head title="Admin Plans" />

            <div className="rounded-2xl border border-slate-200 bg-white p-5">
                <div className="mb-4 flex justify-end">
                    <Link
                        href={route('admin.plans.create')}
                        className="rounded-md bg-sky-700 px-4 py-2 text-sm text-white hover:bg-sky-800"
                    >
                        New Plan
                    </Link>
                </div>

                <div className="overflow-x-auto">
                    <table className="min-w-full text-left text-sm">
                        <thead className="text-slate-500">
                            <tr>
                                <th className="px-3 py-2">Name</th>
                                <th className="px-3 py-2">Price</th>
                                <th className="px-3 py-2">Billing</th>
                                <th className="px-3 py-2">Features</th>
                                <th className="px-3 py-2">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            {plans.data.map((plan) => (
                                <tr key={plan.id} className="border-t border-slate-200">
                                    <td className="px-3 py-2">
                                        <p className="font-medium">{plan.name}</p>
                                        <p className="text-xs text-slate-500">
                                            {plan.description || 'No description'}
                                        </p>
                                    </td>
                                    <td className="px-3 py-2">
                                        PHP {Number(plan.price).toFixed(2)}
                                    </td>
                                    <td className="px-3 py-2">
                                        <span className="rounded-full bg-slate-100 px-2 py-1 text-xs font-semibold uppercase tracking-wide text-slate-700">
                                            {plan.billing_label}
                                        </span>
                                    </td>
                                    <td className="px-3 py-2">
                                        {plan.features.length === 0 ? (
                                            <span className="text-slate-500">No features</span>
                                        ) : (
                                            <ul className="space-y-1">
                                                {plan.features.map((feature) => (
                                                    <li key={feature}>- {feature}</li>
                                                ))}
                                            </ul>
                                        )}
                                    </td>
                                    <td className="px-3 py-2">
                                        <div className="flex items-center gap-2">
                                            <Link
                                                href={route('admin.subscriptions.create', {
                                                    plan_id: plan.id,
                                                })}
                                                className="rounded-md border border-emerald-300 px-2 py-1 text-xs font-semibold text-emerald-700 hover:bg-emerald-50"
                                                title="Choose plan for subscription"
                                            >
                                                Choose Plan
                                            </Link>
                                            <Link
                                                href={route('admin.plans.edit', plan.id)}
                                                className="inline-flex h-8 w-8 items-center justify-center rounded-md text-sky-700 hover:bg-sky-50 hover:text-sky-900"
                                                title="Edit"
                                                aria-label="Edit"
                                            >
                                                <Pencil className="size-4" />
                                            </Link>
                                            <button
                                                type="button"
                                                onClick={() =>
                                                    openConfirm(plan.id)
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
                            {plans.data.length === 0 && (
                                <tr>
                                    <td
                                        colSpan={5}
                                        className="px-3 py-4 text-center text-slate-500"
                                    >
                                        No plans available.
                                    </td>
                                </tr>
                            )}
                        </tbody>
                    </table>
                </div>

                <div className="mt-4">
                    <Pagination links={plans.links} />
                </div>
            </div>

            <ConfirmationModal
                show={confirmingId !== null}
                title="Delete Plan"
                message="Are you sure you want to delete this plan? This action cannot be undone."
                onCancel={closeConfirm}
                onConfirm={confirmDelete}
            />
        </AdminLayout>
    );
}
