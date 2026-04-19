import Pagination from '@/Components/Admin/Pagination';
import ConfirmationModal from '@/Components/ConfirmationModal';
import AdminLayout from '@/Layouts/AdminLayout';
import { Head, Link, router } from '@inertiajs/react';
import { ChevronDown, Pencil, Plus, Trash2 } from 'lucide-react';
import { useMemo, useState } from 'react';

export default function Index({ plans, filters = {} }) {
    const [confirmingId, setConfirmingId] = useState(null);
    const [expandedGroups, setExpandedGroups] = useState({});
    const [perPage, setPerPage] = useState(
        filters.per_page ? String(filters.per_page) : '10',
    );

    const groupedPlans = useMemo(() => {
        const source = Array.isArray(plans?.data) ? plans.data : [];
        const groups = source.reduce((accumulator, plan) => {
            const key = plan.plan_group || plan.group_label || 'others';
            if (!accumulator[key]) {
                accumulator[key] = {
                    key,
                    label: plan.group_label || 'Plans',
                    items: [],
                };
            }
            accumulator[key].items.push(plan);
            return accumulator;
        }, {});

        return Object.values(groups)
            .map((group) => ({
                ...group,
                items: [...group.items].sort(
                    (a, b) => (a.sort_order ?? 0) - (b.sort_order ?? 0),
                ),
            }))
            .sort((a, b) => {
                const aKey = String(a.key || '').toLowerCase();
                const bKey = String(b.key || '').toLowerCase();
                if (aKey === 'free_trial' && bKey !== 'free_trial') {
                    return -1;
                }
                if (bKey === 'free_trial' && aKey !== 'free_trial') {
                    return 1;
                }
                return aKey.localeCompare(bKey);
            });
    }, [plans]);

    const openConfirm = (id) => setConfirmingId(id);
    const closeConfirm = () => setConfirmingId(null);
    const toggleGroup = (key) => {
        setExpandedGroups((prev) => ({
            ...prev,
            [key]: !(prev?.[key] ?? false),
        }));
    };

    const confirmDelete = () => {
        if (!confirmingId) {
            return;
        }

        router.delete(route('admin.plans.destroy', confirmingId), {
            onFinish: closeConfirm,
        });
    };

    const applyPerPage = (value) => {
        setPerPage(value);
        router.get(
            route('admin.plans.index'),
            { per_page: value },
            { preserveState: true, replace: true },
        );
    };

    return (
        <AdminLayout title="Plans">
            <Head title="Admin Plans" />

            <div className="rounded-2xl border border-slate-200 bg-white p-5">
                <div className="mb-4 flex flex-wrap items-center justify-between gap-3">
                    <div className="group relative">
                        <select
                            aria-label="Per page"
                            value={perPage}
                            onChange={(event) => applyPerPage(event.target.value)}
                            className="no-arrow w-[66px] rounded-md border border-slate-300 bg-white px-2 py-2 pr-8 text-sm"
                        >
                            <option value="10">10</option>
                            <option value="15">15</option>
                            <option value="25">25</option>
                            <option value="50">50</option>
                            <option value="100">100</option>
                        </select>
                        <ChevronDown className="pointer-events-none absolute right-2 top-1/2 size-4 -translate-y-1/2 text-slate-500 transition-transform group-focus-within:rotate-180" />
                    </div>
                    <Link
                        href={route('admin.plans.create')}
                        aria-label="Create New Plan"
                        className="rounded-md bg-sky-700 px-4 py-2 text-sm text-white hover:bg-sky-800"
                    >
                        <Plus className="size-4 sm:hidden" />
                        <span className="hidden sm:inline">Create New Plan</span>
                    </Link>
                </div>

                <div className="space-y-6">
                    {groupedPlans.map((group) => (
                        <section
                            key={group.key}
                            className="rounded-xl border border-slate-200"
                        >
                            <div className="border-b border-slate-200 bg-slate-50 px-4 py-3">
                                <div className="flex items-center justify-between gap-4">
                                    <div>
                                        <h2 className="text-base font-semibold text-slate-900">
                                            {group.label}
                                        </h2>
                                        <p className="text-xs text-slate-500">
                                            {group.items.length} sub plan
                                            {group.items.length === 1 ? '' : 's'}
                                        </p>
                                    </div>
                                    <div className="flex items-center gap-2">
                                        <Link
                                            href={route('admin.plans.create', {
                                                plan_group: group.key,
                                                group_label: group.label,
                                            })}
                                            className="rounded-md border border-sky-300 px-3 py-1 text-xs font-semibold text-sky-700 hover:bg-sky-50"
                                        >
                                            Add Sub Plan
                                        </Link>
                                        <button
                                            type="button"
                                            onClick={() => toggleGroup(group.key)}
                                            className="rounded-md border border-slate-300 px-3 py-1 text-xs font-semibold text-slate-700 hover:bg-slate-100"
                                        >
                                            {(expandedGroups[group.key] ?? false)
                                                ? 'Hide'
                                                : 'Show'}
                                        </button>
                                    </div>
                                </div>
                            </div>

                            {(expandedGroups[group.key] ?? false) && (
                                <div className="overflow-x-auto">
                                <table className="min-w-full text-left text-sm">
                                    <thead className="text-slate-500">
                                        <tr>
                                            <th className="px-3 py-2">Sub Plan</th>
                                            <th className="px-3 py-2">Price</th>
                                            <th className="px-3 py-2">Billing</th>
                                            <th className="px-3 py-2">Duration</th>
                                            <th className="px-3 py-2">Features</th>
                                            <th className="px-3 py-2">Subject Access</th>
                                            <th className="px-3 py-2">Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        {group.items.map((plan) => (
                                            <tr
                                                key={plan.id}
                                                className="border-t border-slate-200"
                                            >
                                                <td className="px-3 py-2">
                                                    <div className="flex items-center gap-2">
                                                        <p className="font-medium">
                                                            {plan.sub_plan_label ||
                                                                plan.name}
                                                        </p>
                                                        {plan.is_default && (
                                                            <span className="rounded-full bg-emerald-100 px-2 py-0.5 text-[10px] font-semibold uppercase tracking-wide text-emerald-700">
                                                                Default Free Trial
                                                            </span>
                                                        )}
                                                    </div>
                                                    <p className="text-xs text-slate-500">
                                                        {plan.description ||
                                                            'No description'}
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
                                                    {plan.duration_days} days
                                                </td>
                                                <td className="px-3 py-2">
                                                    {plan.features.length === 0 ? (
                                                        <span className="text-slate-500">
                                                            No features
                                                        </span>
                                                    ) : (
                                                        <ul className="space-y-1">
                                                            {plan.features.map(
                                                                (feature) => (
                                                                    <li
                                                                        key={
                                                                            feature
                                                                        }
                                                                    >
                                                                        - {feature}
                                                                    </li>
                                                                ),
                                                            )}
                                                        </ul>
                                                    )}
                                                </td>
                                                <td className="px-3 py-2">
                                                    {plan.subject_accesses.length ===
                                                    0 ? (
                                                        <span className="text-slate-500">
                                                            No subjects selected
                                                        </span>
                                                    ) : (
                                                        <ul className="space-y-1">
                                                            {plan.subject_accesses.map(
                                                                (item) => (
                                                                    <li
                                                                        key={`${plan.id}-${item.subject_id}`}
                                                                    >
                                                                        -{' '}
                                                                        {
                                                                            item.subject_name
                                                                        }{' '}
                                                                        (
                                                                        {item.question_limit <=
                                                                        0
                                                                            ? 'No limit'
                                                                            : `${item.question_limit} max`}
                                                                        )
                                                                    </li>
                                                                ),
                                                            )}
                                                        </ul>
                                                    )}
                                                </td>
                                                <td className="px-3 py-2">
                                                    <div className="flex items-center gap-2">
                                                        <Link
                                                            href={route(
                                                                'admin.subscriptions.create',
                                                                {
                                                                    plan_id:
                                                                        plan.id,
                                                                },
                                                            )}
                                                            className="rounded-md border border-emerald-300 px-2 py-1 text-xs font-semibold text-emerald-700 hover:bg-emerald-50"
                                                            title="Choose plan for subscription"
                                                        >
                                                            Choose Plan
                                                        </Link>
                                                        <Link
                                                            href={route(
                                                                'admin.plans.edit',
                                                                plan.id,
                                                            )}
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
                                                            className={`inline-flex h-8 w-8 items-center justify-center rounded-md ${
                                                                plan.is_default
                                                                    ? 'cursor-not-allowed text-slate-400'
                                                                    : 'text-red-700 hover:bg-red-50 hover:text-red-900'
                                                            }`}
                                                            title="Delete"
                                                            aria-label="Delete"
                                                            disabled={plan.is_default}
                                                        >
                                                            <Trash2 className="size-4" />
                                                        </button>
                                                    </div>
                                                    {plan.is_default && (
                                                        <p className="mt-1 text-[11px] font-semibold text-slate-500">
                                                            Default free trial cannot
                                                            be deleted.
                                                        </p>
                                                    )}
                                                </td>
                                            </tr>
                                        ))}
                                    </tbody>
                                </table>
                                </div>
                            )}
                        </section>
                    ))}

                    {groupedPlans.length === 0 && (
                        <p className="rounded-lg border border-slate-200 p-4 text-center text-slate-500">
                            No plans available.
                        </p>
                    )}
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
