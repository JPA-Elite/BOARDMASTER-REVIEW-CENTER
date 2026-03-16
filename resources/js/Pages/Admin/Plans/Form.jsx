import InputError from '@/Components/InputError';
import InputLabel from '@/Components/InputLabel';
import TextInput from '@/Components/TextInput';
import AdminLayout from '@/Layouts/AdminLayout';
import { Head, Link, useForm } from '@inertiajs/react';
import { useEffect, useMemo } from 'react';

export default function Form({
    mode,
    plan,
    billingCycles = ['monthly', 'yearly'],
    subjects = [],
}) {
    const isDefaultPlan = mode === 'edit' && Boolean(plan?.is_default);
    const initialSubjectAccesses =
        plan?.subject_accesses?.map((item) => {
            const parsedLimit = Number(item.question_limit);
            return {
                subject_id: Number(item.subject_id),
                question_limit: Number.isFinite(parsedLimit) ? parsedLimit : 0,
            };
        }) || [];

    const { data, setData, post, put, processing, errors } = useForm({
        name: plan?.name || '',
        price: plan?.price ?? '',
        billing_cycle: plan?.billing_cycle || 'monthly',
        description: plan?.description || '',
        features:
            plan?.features?.length > 0
                ? plan.features
                : ['', ''],
        subject_accesses: initialSubjectAccesses,
    });

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
            const defaultLimit = Number(data.price) <= 0 ? 30 : 100;
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
        const defaultLimit = Number(data.price) <= 0 ? 30 : 100;
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
                            Default Free Plan name is fixed.
                        </p>
                    )}
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
                            Default Free Plan always has `PHP 0` pricing.
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
                                {cycle === 'yearly' ? 'Per Year' : 'Per Month'}
                            </option>
                        ))}
                    </select>
                    <InputError message={errors.billing_cycle} className="mt-2" />
                    {isDefaultPlan && (
                        <p className="mt-2 text-xs text-slate-500">
                            Default Free Plan billing is fixed to monthly.
                        </p>
                    )}
                </div>

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
                        {mode === 'edit' ? 'Update Plan' : 'Create Plan'}
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
