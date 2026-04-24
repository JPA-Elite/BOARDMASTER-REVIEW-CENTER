import Pagination from '@/Components/Admin/Pagination';
import ConfirmationModal from '@/Components/ConfirmationModal';
import Modal from '@/Components/Modal';
import AdminLayout from '@/Layouts/AdminLayout';
import { Head, Link, router } from '@inertiajs/react';
import { ChevronDown, Pencil, Plus, Trash2, Eye, X } from 'lucide-react';
import { useMemo, useState, useEffect } from 'react';
import { toast } from 'react-toastify';

export default function Index({ plans, filters = {} }) {
    const [confirmingId, setConfirmingId] = useState(null);
    const [expandedGroups, setExpandedGroups] = useState({});
    const [perPage, setPerPage] = useState(
        filters.per_page ? String(filters.per_page) : '10',
    );
    const [editingGroup, setEditingGroup] = useState(null);
    const [editingLabel, setEditingLabel] = useState('');
    const [editingLabels, setEditingLabels] = useState({});
    const [showMobileModal, setShowMobileModal] = useState(false);

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

    const startEditingGroup = (key, label) => {
        setEditingGroup(key);
        setShowMobileModal(true);
        // Use a preserved label for this group if present, otherwise use provided label
        setEditingLabel((prev) => {
            const preserved = editingLabels[key];
            return preserved !== undefined && String(preserved).trim() !== ''
                ? preserved
                : label || '';
        });
    };

    const [isMobile, setIsMobile] = useState(false);

    useEffect(() => {
        if (typeof window === 'undefined') {
            return;
        }

        const mq = window.matchMedia('(max-width: 767px)');
        const update = (e) => setIsMobile(Boolean(e.matches));
        update(mq);
        if (mq.addEventListener) {
            mq.addEventListener('change', update);
        } else {
            mq.addListener(update);
        }

        return () => {
            if (mq.removeEventListener) {
                mq.removeEventListener('change', update);
            } else {
                mq.removeListener(update);
            }
        };
    }, []);

    const cancelEditingGroup = () => {
        // Hide modal immediately (animation will run). Preserve editingGroup
        // until animation completes so modal content (sub-plan count) doesn't
        // show as 0 during the leave transition.
        setShowMobileModal(false);
        // Clear editingGroup after the modal leave animation completes (200ms).
        setTimeout(() => {
            setEditingGroup(null);
        }, 250);
    };

    const saveGroupLabel = (groupKey) => {
        const target = String(editingLabel || '').trim();
        if (target === '') {
            // simple client-side guard
            // eslint-disable-next-line no-alert
            toast.error('Group display label cannot be empty.');
            return;
        }

        // Start modal hide animation before sending request so UI doesn't
        // briefly show 0 during the leave transition.
        setShowMobileModal(false);

        router.patch(
            route('admin.plans.rename-group'),
            { plan_group: groupKey, group_label: target },
            {
                preserveState: true,
                onFinish: () => {
                    // Wait for modal leave animation then clear editing state.
                    setTimeout(() => {
                        setEditingGroup(null);
                        setEditingLabel('');
                        setEditingLabels((prev) => {
                            const copy = { ...prev };
                            delete copy[groupKey];
                            return copy;
                        });
                    }, 250);
                },
                onError: (errs) => {
                    try {
                        const msgs = Object.values(errs)
                            .flat()
                            .filter(Boolean);
                        if (msgs.length > 0) {
                            // eslint-disable-next-line no-alert
                            toast.error(msgs.join('\n'));
                        }
                    } catch (e) {
                        // eslint-disable-next-line no-alert
                        toast.error('Failed to rename group');
                    }
                },
            },
        );
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
                                    <div className="min-w-0">
                                        <div className="flex items-center gap-2">
                                            {editingGroup === group.key ? (
                                                isMobile ? (
                                                    // On mobile we keep the header compact and show a modal instead
                                                    <>
                                                            <h2
                                                            className="!text-[12px] md:!text-base font-semibold text-slate-900"
                                                            style={isMobile ? { fontSize: '12px' } : undefined}
                                                            >
                                                        {group.label}
                                                            </h2>
                                                    </>
                                                ) : (
                                                    <>
                                                        <input
                                                            autoFocus
                                                            value={editingLabel}
                                                            onChange={(e) => {
                                                                const v = e.target.value;
                                                                setEditingLabel(v);
                                                                setEditingLabels((prev) => ({
                                                                    ...prev,
                                                                    [group.key]: v,
                                                                }));
                                                            }}
                                                            className="w-full md:w-96 max-w-full rounded-md border border-slate-300 bg-white px-3 py-2 text-base"
                                                        />
                                                        <button
                                                            type="button"
                                                            onClick={() => saveGroupLabel(group.key)}
                                                            className="rounded-md bg-sky-700 px-3 py-1 text-xs text-white hover:bg-sky-800"
                                                        >
                                                            Save
                                                        </button>
                                                        <button
                                                            type="button"
                                                            onClick={cancelEditingGroup}
                                                            className="rounded-md border border-slate-300 px-3 py-1 text-xs text-slate-700 hover:bg-slate-100"
                                                        >
                                                            Cancel
                                                        </button>
                                                    </>
                                                )
                                            ) : (
                                                <>
                                                    <h2
                                                        className="!text-[12px] md:!text-base font-semibold text-slate-900"
                                                        style={isMobile ? { fontSize: '12px' } : undefined}
                                                    >
                                                            {group.label}
                                                        </h2>
                                                    {group.key === 'free_trial' ? (
                                                        <button
                                                            type="button"
                                                            className="inline-flex h-8 w-8 items-center justify-center rounded-md text-slate-300"
                                                            title="Default Free Trial cannot be renamed"
                                                            disabled
                                                        >
                                                            <Pencil className="size-4" />
                                                        </button>
                                                    ) : (
                                                        <button
                                                            type="button"
                                                            onClick={() => startEditingGroup(group.key, group.label)}
                                                            className="inline-flex h-8 w-8 items-center justify-center rounded-md text-slate-500 hover:bg-slate-100"
                                                            title="Rename group"
                                                        >
                                                            <Pencil className="size-4" />
                                                        </button>
                                                    )}
                                                </>
                                            )}

                                        </div>
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
                                            className="inline-flex items-center gap-2 rounded-md border border-sky-300 px-3 py-1 text-xs font-semibold text-sky-700 hover:bg-sky-50"
                                        >
                                            <Plus className="size-4 md:hidden" />
                                            <span className="hidden md:inline">Add Sub Plan</span>
                                        </Link>
                                        <button
                                            type="button"
                                            onClick={() => toggleGroup(group.key)}
                                            className="inline-flex items-center gap-2 rounded-md border border-slate-300 px-3 py-1 text-xs font-semibold text-slate-700 hover:bg-slate-100"
                                        >
                                            <span className="hidden md:inline">{(expandedGroups[group.key] ?? false) ? 'Hide' : 'Show'}</span>
                                            <Eye className="size-4 md:hidden" />
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

            {/* Mobile rename modal */}
            <Modal show={showMobileModal && isMobile} onClose={cancelEditingGroup} maxWidth="md">
                <div className="p-6 relative">
                    <button
                        type="button"
                        onClick={cancelEditingGroup}
                        aria-label="Close"
                        className="absolute right-3 top-3 inline-flex items-center justify-center rounded-md p-1 text-slate-400 hover:bg-slate-100 hover:text-slate-700"
                    >
                        <X className="size-4" />
                    </button>
                    <h2 className="text-lg font-semibold text-slate-900">Rename Group</h2>
                    <p className="mt-1 text-sm text-slate-500">
                        {groupedPlans.find((g) => g.key === editingGroup)?.items.length ?? 0} sub plan(s)
                    </p>

                    <div className="mt-4">
                        <input
                            autoFocus
                            value={editingLabel}
                            onChange={(e) => {
                                const v = e.target.value;
                                setEditingLabel(v);
                                setEditingLabels((prev) => ({
                                    ...prev,
                                    [editingGroup]: v,
                                }));
                            }}
                            className="w-full rounded-md border border-slate-300 bg-white px-3 py-2 text-base"
                        />
                    </div>

                    <div className="mt-6 flex justify-end gap-3">
                        <button
                            type="button"
                            onClick={cancelEditingGroup}
                            className="rounded-md border border-slate-300 px-4 py-2 text-sm text-slate-700 hover:bg-slate-100"
                        >
                            Cancel
                        </button>
                        <button
                            type="button"
                            onClick={() => saveGroupLabel(editingGroup)}
                            className="rounded-md bg-sky-700 px-4 py-2 text-sm text-white hover:bg-sky-800"
                        >
                            Save
                        </button>
                    </div>
                </div>
            </Modal>

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
