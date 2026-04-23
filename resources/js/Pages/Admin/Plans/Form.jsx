import InputError from '@/Components/InputError';
import InputLabel from '@/Components/InputLabel';
import TextInput from '@/Components/TextInput';
import AdminLayout from '@/Layouts/AdminLayout';
import { Head, Link, useForm } from '@inertiajs/react';
import { useEffect, useMemo } from 'react';

export default function Form({
    mode,
    plan,
    billingCycles = ['trial', 'quarterly', 'semi_annual', 'annual', 'monthly', 'yearly'],
    planGroups = [],
    subjects = [],
    initialPlanGroup = '',
    initialGroupLabel = '',
}) {
    const createEmptySubPlan = () => ({
        name: '',
        sub_plan_label: '',
        price: '',
        billing_cycle: 'quarterly',
        duration_days: '',
        sort_order: 0,
    });
    const isDefaultPlan = mode === 'edit' && Boolean(plan?.is_default);
    const canManageSubPlans = mode === 'create';
    const initialSubjectAccesses =
        plan?.subject_accesses?.map((item) => {
            const parsedLimit = Number(item.question_limit);
            return {
                subject_id: Number(item.subject_id),
                question_limit: Number.isFinite(parsedLimit) ? parsedLimit : 0,
            };
        }) || [];
    const initialSubPlans =
        mode === 'create'
            ? [
                  {
                      ...createEmptySubPlan(),
                  },
              ]
            : [];
    const billingCycleLabel = (cycle) => {
        if (cycle === 'trial') {
            return 'Trial (3 days)';
        }
        if (cycle === 'quarterly') {
            return 'Quarterly (3 months)';
        }
        if (cycle === 'semi_annual') {
            return 'Semi-Annual (6 months)';
        }
        if (cycle === 'annual' || cycle === 'yearly') {
            return 'Annual (12 months)';
        }
        return 'Monthly (1 month)';
    };

    const { data, setData, post, put, processing, errors } = useForm({
        name: plan?.name || '',
        plan_group:
            plan?.plan_group ||
            (initialPlanGroup?.trim() || 'plan_a'),
        group_label:
            plan?.group_label ||
            (initialGroupLabel?.trim() || ''),
        sub_plan_label: plan?.sub_plan_label || '',
        price: plan?.price ?? '',
        billing_cycle: plan?.billing_cycle || 'monthly',
        duration_days: plan?.duration_days ?? '',
        sort_order: plan?.sort_order ?? 0,
        description: plan?.description || '',
        features:
            plan?.features?.length > 0
                ? plan.features
                : ['', ''],
        subject_accesses: initialSubjectAccesses,
        sub_plans: initialSubPlans,
    });

    const lockGroupFields =
        mode === 'create' &&
        ((initialPlanGroup && initialPlanGroup.trim() !== '') ||
            (initialGroupLabel && initialGroupLabel.trim() !== ''));

    const updateFeature = (index, value) => {
        const nextFeatures = [...data.features];
        nextFeatures[index] = value;
        setData('features', nextFeatures);
    };

    const addFeature = () => {
        setData('features', [...data.features, '']);
    };

    const removeFeature = (index) => {
        if (data.features.length <= 1) {
            return;
        }

        setData(
            'features',
            data.features.filter((_, featureIndex) => featureIndex !== index),
        );
    };

    const updateSubPlan = (index, field, value) => {
        const next = [...data.sub_plans];
        next[index] = {
            ...next[index],
            [field]: value,
        };
        setData('sub_plans', next);
    };

    const addSubPlan = () => {
        setData('sub_plans', [...data.sub_plans, createEmptySubPlan()]);
    };

    const removeSubPlan = (index) => {
        if (mode === 'create' && data.sub_plans.length <= 1) {
            return;
        }

        setData(
            'sub_plans',
            data.sub_plans.filter((_, itemIndex) => itemIndex !== index),
        );
    };

    const defaultSubjectLimit = () => {
        const currentPrice =
            mode === 'create'
                ? Number(data.sub_plans?.[0]?.price)
                : Number(data.price);
        return Number.isFinite(currentPrice) && currentPrice <= 0 ? 30 : 100;
    };

    const selectedAccessBySubjectId = useMemo(() => {
        const map = {};
        data.subject_accesses.forEach((access, index) => {
            map[String(access.subject_id)] = {
                index,
                question_limit: Number(access.question_limit) || 0,
            };
        });
        return map;
    }, [data.subject_accesses]);

    const subjectMaxById = useMemo(() => {
        return subjects.reduce((accumulator, subject) => {
            const maxValue = Math.max(1, Number(subject.questions_count || 0));
            accumulator[String(subject.id)] = maxValue;
            return accumulator;
        }, {});
    }, [subjects]);

    useEffect(() => {
        if (data.subject_accesses.length === 0) {
            return;
        }

        let changed = false;
        const next = data.subject_accesses.map((item) => {
            const subjectMax = subjectMaxById[String(item.subject_id)] || 1;
            const rawLimit = Number(item.question_limit) || 0;

            if (rawLimit <= 0) {
                return item;
            }

            const clamped = Math.min(Math.max(1, rawLimit), subjectMax);
            if (clamped === rawLimit) {
                return item;
            }

            changed = true;
            return {
                ...item,
                question_limit: clamped,
            };
        });

        if (changed) {
            setData('subject_accesses', next);
        }
    }, [data.subject_accesses, setData, subjectMaxById]);

    const toggleSubjectAccess = (subjectId, checked) => {
        const numericSubjectId = Number(subjectId);
        const next = [...data.subject_accesses];
        const existingIndex = next.findIndex(
            (item) => Number(item.subject_id) === numericSubjectId,
        );

        if (checked && existingIndex === -1) {
            const defaultLimit = defaultSubjectLimit();
            const subjectMax = subjectMaxById[String(subjectId)] || defaultLimit;
            next.push({
                subject_id: numericSubjectId,
                question_limit: Math.min(subjectMax, defaultLimit),
            });
        }

        if (!checked && existingIndex >= 0) {
            next.splice(existingIndex, 1);
        }

        setData('subject_accesses', next);
    };

    const updateSubjectLimit = (subjectId, value) => {
        const numericSubjectId = Number(subjectId);
        const subjectMax = subjectMaxById[String(subjectId)] || 1;
        const next = data.subject_accesses.map((item) => {
            if (Number(item.subject_id) !== numericSubjectId) {
                return item;
            }
            const numericValue = value === '' ? '' : Number(value);
            const clamped =
                numericValue === ''
                    ? ''
                    : Math.min(Math.max(1, numericValue), subjectMax);
            return {
                ...item,
                question_limit: clamped,
            };
        });
        setData('subject_accesses', next);
    };

    const setNoLimit = (subjectId) => {
        const numericSubjectId = Number(subjectId);
        const next = data.subject_accesses.map((item) => {
            if (Number(item.subject_id) !== numericSubjectId) {
                return item;
            }
            return {
                ...item,
                question_limit: 0,
            };
        });
        setData('subject_accesses', next);
    };

    const setLimitMode = (subjectId) => {
        const numericSubjectId = Number(subjectId);
        const subjectMax = subjectMaxById[String(subjectId)] || 1;
        const defaultLimit = defaultSubjectLimit();
        const fallback = Math.min(subjectMax, defaultLimit);
        const next = data.subject_accesses.map((item) => {
            if (Number(item.subject_id) !== numericSubjectId) {
                return item;
            }
            const currentLimit = Number(item.question_limit) || 0;
            return {
                ...item,
                question_limit: currentLimit > 0 ? currentLimit : fallback,
            };
        });
        setData('subject_accesses', next);
    };

    const subjectLimitError = (subjectId) => {
        const targetIndex = data.subject_accesses.findIndex(
            (item) => Number(item.subject_id) === Number(subjectId),
        );

        if (targetIndex < 0) {
            return null;
        }

        return errors[`subject_accesses.${targetIndex}.question_limit`];
    };

    const subPlanFieldError = (index, field) =>
        errors[`sub_plans.${index}.${field}`];

    const submit = (event) => {
        event.preventDefault();

        if (mode === 'edit') {
            put(route('admin.plans.update', plan.id));
            return;
        }

        post(route('admin.plans.store'));
    };

    return (
        <AdminLayout title={mode === 'edit' ? 'Edit Plan' : 'Create Plan'}>
            <Head title={mode === 'edit' ? 'Edit Plan' : 'Create Plan'} />

            <form onSubmit={submit} className="space-y-4 rounded-2xl border border-slate-200 bg-white p-6">
                {mode === 'edit' && (
                    <div>
                        <InputLabel htmlFor="name" value="Plan Name" />
                        <TextInput
                            id="name"
                            value={data.name}
                            onChange={(event) => setData('name', event.target.value)}
                            className="mt-1 block w-full"
                            disabled={isDefaultPlan}
                        />
                        <InputError message={errors.name} className="mt-2" />
                        {isDefaultPlan && (
                            <p className="mt-2 text-xs text-slate-500">
                                Default Free Trial name is fixed.
                            </p>
                        )}
                    </div>
                )}

                <div>
                    <InputLabel htmlFor="plan_group" value="Plan Group" />
                    <TextInput
                        id="plan_group"
                        value={data.plan_group}
                        onChange={(event) =>
                            setData(
                                'plan_group',
                                event.target.value
                                    .toLowerCase()
                                    .replace(/[^a-z0-9_]/g, '_'),
                            )
                        }
                        className="mt-1 block w-full"
                        disabled={isDefaultPlan || lockGroupFields}
                        list="plan-group-options"
                        placeholder="e.g. plan_a, plan_b, plan_c"
                    />
                    <datalist id="plan-group-options">
                        {planGroups.map((group) => (
                            <option key={group.value} value={group.value}>
                                {group.label}
                            </option>
                        ))}
                    </datalist>
                    <InputError message={errors.plan_group} className="mt-2" />
                    <p className="mt-2 text-xs text-slate-500">
                        You can add new groups anytime, like `plan_c` or `plan_d`.
                    </p>
                </div>

                <div>
                    <InputLabel htmlFor="group_label" value="Group Display Label" />
                    <TextInput
                        id="group_label"
                        value={data.group_label}
                        onChange={(event) => setData('group_label', event.target.value)}
                        className="mt-1 block w-full"
                        disabled={isDefaultPlan || lockGroupFields}
                    />
                    <InputError message={errors.group_label} className="mt-2" />
                </div>

                {canManageSubPlans && (
                    <div className="space-y-3 rounded-xl border border-slate-200 p-4">
                        <div className="flex items-center justify-between">
                            <div>
                                <InputLabel
                                    value={
                                        mode === 'create'
                                            ? 'Sub Plans in this Group'
                                            : 'Add New Sub Plans to This Group'
                                    }
                                />
                                <p className="mt-1 text-xs text-slate-500">
                                    {mode === 'create'
                                        ? 'Add one or more sub plans under the selected group.'
                                        : 'Optionally add more sub plans while editing this group.'}
                                </p>
                            </div>
                            <button
                                type="button"
                                onClick={addSubPlan}
                                className="text-sm text-sky-700 hover:text-sky-900"
                            >
                                Add Sub Plan
                            </button>
                        </div>
                        <InputError message={errors.sub_plans} className="mt-1" />
                        {data.sub_plans.map((subPlan, index) => (
                            <div
                                key={index}
                                className="rounded-lg border border-slate-200 p-3"
                            >
                                <div className="mb-3 flex items-center justify-between">
                                    <p className="text-sm font-semibold text-slate-800">
                                        Sub Plan {index + 1}
                                    </p>
                                    <button
                                        type="button"
                                        onClick={() => removeSubPlan(index)}
                                        className="rounded-md border border-red-300 px-2 py-1 text-xs text-red-700"
                                        disabled={
                                            mode === 'create' &&
                                            data.sub_plans.length <= 1
                                        }
                                    >
                                        Remove
                                    </button>
                                </div>
                                <div className="grid gap-3 md:grid-cols-2">
                                    <div>
                                        <InputLabel
                                            htmlFor={`sub-plan-name-${index}`}
                                            value="Plan Name"
                                        />
                                        <TextInput
                                            id={`sub-plan-name-${index}`}
                                            value={subPlan.name}
                                            onChange={(event) =>
                                                updateSubPlan(
                                                    index,
                                                    'name',
                                                    event.target.value,
                                                )
                                            }
                                            className="mt-1 block w-full"
                                            placeholder="Plan C - Quarterly (3 months)"
                                        />
                                        <InputError
                                            message={subPlanFieldError(index, 'name')}
                                            className="mt-2"
                                        />
                                    </div>
                                    <div>
                                        <InputLabel
                                            htmlFor={`sub-plan-label-${index}`}
                                            value="Sub Plan Label"
                                        />
                                        <TextInput
                                            id={`sub-plan-label-${index}`}
                                            value={subPlan.sub_plan_label}
                                            onChange={(event) =>
                                                updateSubPlan(
                                                    index,
                                                    'sub_plan_label',
                                                    event.target.value,
                                                )
                                            }
                                            className="mt-1 block w-full"
                                            placeholder="Quarterly (3 months)"
                                        />
                                        <InputError
                                            message={subPlanFieldError(
                                                index,
                                                'sub_plan_label',
                                            )}
                                            className="mt-2"
                                        />
                                    </div>
                                    <div>
                                        <InputLabel
                                            htmlFor={`sub-plan-price-${index}`}
                                            value="Price"
                                        />
                                        <TextInput
                                            id={`sub-plan-price-${index}`}
                                            type="number"
                                            step="0.01"
                                            min="0"
                                            value={subPlan.price}
                                            onChange={(event) =>
                                                updateSubPlan(
                                                    index,
                                                    'price',
                                                    event.target.value,
                                                )
                                            }
                                            className="mt-1 block w-full"
                                        />
                                        <InputError
                                            message={subPlanFieldError(index, 'price')}
                                            className="mt-2"
                                        />
                                    </div>
                                    <div>
                                        <InputLabel
                                            htmlFor={`sub-plan-billing-${index}`}
                                            value="Subscription Billing"
                                        />
                                        <select
                                            id={`sub-plan-billing-${index}`}
                                            value={subPlan.billing_cycle}
                                            onChange={(event) =>
                                                updateSubPlan(
                                                    index,
                                                    'billing_cycle',
                                                    event.target.value,
                                                )
                                            }
                                            className="mt-1 block w-full rounded-md border-slate-300"
                                        >
                                            {billingCycles.map((cycle) => (
                                                <option key={cycle} value={cycle}>
                                                    {billingCycleLabel(cycle)}
                                                </option>
                                            ))}
                                        </select>
                                        <InputError
                                            message={subPlanFieldError(
                                                index,
                                                'billing_cycle',
                                            )}
                                            className="mt-2"
                                        />
                                    </div>
                                    <div>
                                        <InputLabel
                                            htmlFor={`sub-plan-duration-${index}`}
                                            value="Duration (days)"
                                        />
                                        <TextInput
                                            id={`sub-plan-duration-${index}`}
                                            type="number"
                                            min="1"
                                            value={subPlan.duration_days}
                                            onChange={(event) =>
                                                updateSubPlan(
                                                    index,
                                                    'duration_days',
                                                    event.target.value,
                                                )
                                            }
                                            className="mt-1 block w-full"
                                        />
                                        <InputError
                                            message={subPlanFieldError(
                                                index,
                                                'duration_days',
                                            )}
                                            className="mt-2"
                                        />
                                    </div>
                                    <div>
                                        <InputLabel
                                            htmlFor={`sub-plan-sort-${index}`}
                                            value="Sort Order"
                                        />
                                        <TextInput
                                            id={`sub-plan-sort-${index}`}
                                            type="number"
                                            min="0"
                                            value={subPlan.sort_order}
                                            onChange={(event) =>
                                                updateSubPlan(
                                                    index,
                                                    'sort_order',
                                                    event.target.value,
                                                )
                                            }
                                            className="mt-1 block w-full"
                                        />
                                        <InputError
                                            message={subPlanFieldError(
                                                index,
                                                'sort_order',
                                            )}
                                            className="mt-2"
                                        />
                                    </div>
                                </div>
                            </div>
                        ))}
                    </div>
                )}

                {mode === 'edit' && (
                    <>
                        <div>
                            <InputLabel htmlFor="sub_plan_label" value="Sub Plan Label" />
                            <TextInput
                                id="sub_plan_label"
                                value={data.sub_plan_label}
                                onChange={(event) =>
                                    setData('sub_plan_label', event.target.value)
                                }
                                className="mt-1 block w-full"
                                disabled={isDefaultPlan}
                                placeholder="Quarterly (3 months)"
                            />
                            <InputError message={errors.sub_plan_label} className="mt-2" />
                        </div>

                        <div>
                            <InputLabel htmlFor="price" value="Price" />
                            <TextInput
                                id="price"
                                type="number"
                                step="0.01"
                                min="0"
                                value={data.price}
                                onChange={(event) => setData('price', event.target.value)}
                                className="mt-1 block w-full"
                                disabled={isDefaultPlan}
                            />
                            <InputError message={errors.price} className="mt-2" />
                            {isDefaultPlan && (
                                <p className="mt-2 text-xs text-slate-500">
                                    Default Free Trial always has `PHP 0` pricing.
                                </p>
                            )}
                        </div>

                        <div>
                            <InputLabel htmlFor="billing_cycle" value="Subscription Billing" />
                            <select
                                id="billing_cycle"
                                value={data.billing_cycle}
                                onChange={(event) => setData('billing_cycle', event.target.value)}
                                className="mt-1 block w-full rounded-md border-slate-300"
                                disabled={isDefaultPlan}
                            >
                                {billingCycles.map((cycle) => (
                                    <option key={cycle} value={cycle}>
                                        {billingCycleLabel(cycle)}
                                    </option>
                                ))}
                            </select>
                            <InputError message={errors.billing_cycle} className="mt-2" />
                            {isDefaultPlan && (
                                <p className="mt-2 text-xs text-slate-500">
                                    Default Free Trial billing is fixed to 3 days.
                                </p>
                            )}
                        </div>

                        <div>
                            <InputLabel htmlFor="duration_days" value="Duration (days)" />
                            <TextInput
                                id="duration_days"
                                type="number"
                                min="1"
                                value={data.duration_days}
                                onChange={(event) =>
                                    setData('duration_days', event.target.value)
                                }
                                className="mt-1 block w-full"
                                disabled={isDefaultPlan}
                            />
                            <InputError message={errors.duration_days} className="mt-2" />
                        </div>

                        <div>
                            <InputLabel htmlFor="sort_order" value="Sort Order" />
                            <TextInput
                                id="sort_order"
                                type="number"
                                min="0"
                                value={data.sort_order}
                                onChange={(event) =>
                                    setData('sort_order', event.target.value)
                                }
                                className="mt-1 block w-full"
                            />
                            <InputError message={errors.sort_order} className="mt-2" />
                        </div>
                    </>
                )}

                <div>
                    <InputLabel htmlFor="description" value="Description" />
                    <textarea
                        id="description"
                        rows={4}
                        value={data.description}
                        onChange={(event) =>
                            setData('description', event.target.value)
                        }
                        className="mt-1 block w-full rounded-md border-slate-300"
                    />
                    <InputError message={errors.description} className="mt-2" />
                </div>

                <div className="space-y-2">
                    <div className="flex items-center justify-between">
                        <InputLabel value="Included Features" />
                        <button
                            type="button"
                            onClick={addFeature}
                            className="text-sm text-sky-700 hover:text-sky-900"
                        >
                            Add Feature
                        </button>
                    </div>
                    {data.features.map((feature, index) => (
                        <div key={index} className="flex gap-2">
                            <TextInput
                                value={feature}
                                onChange={(event) =>
                                    updateFeature(index, event.target.value)
                                }
                                className="block w-full"
                                placeholder={`Feature ${index + 1} (e.g. Can post answers)`}
                            />
                            <button
                                type="button"
                                onClick={() => removeFeature(index)}
                                className="rounded-md border border-red-300 px-3 text-red-700"
                            >
                                Remove
                            </button>
                        </div>
                    ))}
                    <InputError message={errors.features} className="mt-2" />
                </div>

                <div className="space-y-3 rounded-xl border border-slate-200 p-4">
                    <div>
                        <InputLabel value="Accessible Subjects and Limits" />
                        <p className="mt-1 text-xs text-slate-500">
                            Select which subjects this plan can access and set max questions per set per subject.
                        </p>
                    </div>

                    <InputError message={errors.subject_accesses} className="mt-1" />

                    {subjects.length === 0 && (
                        <p className="text-sm text-slate-500">
                            No subjects found. Add subjects first.
                        </p>
                    )}

                    {subjects.map((subject) => {
                        const state = selectedAccessBySubjectId[String(subject.id)];
                        const checked = Boolean(state);
                        const rawLimit = state?.question_limit ?? 0;
                        const isUnlimited = checked && Number(rawLimit) <= 0;
                        const subjectTotal = Number(subject.questions_count || 0);
                        const subjectMax = subjectMaxById[String(subject.id)] || 1;
                        const limitValue = isUnlimited
                            ? subjectTotal
                            : Math.min(
                                  Math.max(1, Number(rawLimit) || 1),
                                  subjectMax,
                              );
                        const displayTotal =
                            subjectTotal > 0 ? subjectTotal : subjectMax;
                        const limitError = subjectLimitError(subject.id);

                        return (
                            <div
                                key={subject.id}
                                className="rounded-lg border border-slate-200 p-3"
                            >
                                <div className="flex items-center justify-between gap-3">
                                    <label className="inline-flex items-center gap-2 text-sm font-medium text-slate-800">
                                        <input
                                            type="checkbox"
                                            checked={checked}
                                            onChange={(event) =>
                                                toggleSubjectAccess(
                                                    subject.id,
                                                    event.target.checked,
                                                )
                                            }
                                            className="rounded border-slate-300 text-sky-700 focus:ring-sky-500"
                                        />
                                        <span>{subject.name}</span>
                                    </label>
                                    <span className="text-xs text-slate-500">
                                        {subject.questions_count} total questions
                                    </span>
                                </div>

                                {checked && (
                                    <div className="mt-3">
                                        <InputLabel value="Limit Mode" />
                                        <div className="mt-2 flex flex-wrap gap-4 text-sm">
                                            <label className="inline-flex items-center gap-2">
                                                <input
                                                    type="radio"
                                                    name={`limit-mode-${subject.id}`}
                                                    checked={isUnlimited}
                                                    onChange={() =>
                                                        setNoLimit(subject.id)
                                                    }
                                                    className="text-sky-700 focus:ring-sky-500"
                                                />
                                                No limit
                                            </label>
                                            <label className="inline-flex items-center gap-2">
                                                <input
                                                    type="radio"
                                                    name={`limit-mode-${subject.id}`}
                                                    checked={!isUnlimited}
                                                    onChange={() =>
                                                        setLimitMode(subject.id)
                                                    }
                                                    className="text-sky-700 focus:ring-sky-500"
                                                />
                                                Limit
                                            </label>
                                        </div>

                                        {!isUnlimited && (
                                            <div className="mt-3">
                                                <InputLabel
                                                    htmlFor={`limit-${subject.id}`}
                                                    value="Question Limit"
                                                />
                                                <input
                                                    id={`limit-${subject.id}`}
                                                    type="range"
                                                    min="1"
                                                    max={subjectMax}
                                                    value={limitValue}
                                                    onChange={(event) =>
                                                        updateSubjectLimit(
                                                            subject.id,
                                                            event.target.value,
                                                        )
                                                    }
                                                    className="mt-2 w-full"
                                                />
                                                <div className="mt-2 text-sm text-slate-600">
                                                    Limit: {limitValue} / {displayTotal}
                                                </div>
                                            </div>
                                        )}
                                        <InputError
                                            message={limitError}
                                            className="mt-2"
                                        />
                                    </div>
                                )}
                            </div>
                        );
                    })}
                </div>

                <div className="flex gap-3">
                    <button
                        type="submit"
                        disabled={processing}
                        className="rounded-md bg-slate-900 px-4 py-2 text-sm text-white disabled:opacity-50"
                    >
                        {mode === 'edit' ? 'Update Plan' : 'Create Group Plans'}
                    </button>
                    <Link
                        href={route('admin.plans.index')}
                        className="rounded-md border border-slate-300 px-4 py-2 text-sm text-slate-700"
                    >
                        Cancel
                    </Link>
                </div>
            </form>
        </AdminLayout>
    );
}
