import Pagination from '@/Components/Admin/Pagination';
import AdminLayout from '@/Layouts/AdminLayout';
import { Head, Link, router } from '@inertiajs/react';

export default function Index({ plans }) {
    const destroy = (id) => {
        if (!window.confirm('Delete this plan?')) {
            return;
        }

        router.delete(route('admin.plans.destroy', id));
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
                                        <div className="flex gap-2">
                                            <Link
                                                href={route('admin.plans.edit', plan.id)}
                                                className="text-sky-700 hover:text-sky-900"
                                            >
                                                Edit
                                            </Link>
                                            <button
                                                type="button"
                                                onClick={() => destroy(plan.id)}
                                                className="text-red-700 hover:text-red-900"
                                            >
                                                Delete
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                            ))}
                            {plans.data.length === 0 && (
                                <tr>
                                    <td
                                        colSpan={4}
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
        </AdminLayout>
    );
}

