import InputError from '@/Components/InputError';
import InputLabel from '@/Components/InputLabel';
import AdminLayout from '@/Layouts/AdminLayout';
import { Head, Link, useForm } from '@inertiajs/react';

export default function Form({ mode, subscription, users, plans, statuses }) {
    const { data, setData, post, put, processing, errors } = useForm({
        user_id: subscription?.user_id || '',
        plan_id: subscription?.plan_id || '',
        start_date: subscription?.start_date || '',
        end_date: subscription?.end_date || '',
        status: subscription?.status || statuses[0],
    });

    const submit = (event) => {
        event.preventDefault();

        if (mode === 'edit') {
            put(route('admin.subscriptions.update', subscription.id));
            return;
        }

        post(route('admin.subscriptions.store'));
    };

    return (
        <AdminLayout
            title={mode === 'edit' ? 'Edit Subscription' : 'Create Subscription'}
        >
            <Head
                title={
                    mode === 'edit' ? 'Edit Subscription' : 'Create Subscription'
                }
            />

            <form onSubmit={submit} className="space-y-4 rounded-xl bg-white p-6 shadow-sm">
                <div>
                    <InputLabel htmlFor="user_id" value="User" />
                    <select
                        id="user_id"
                        value={data.user_id}
                        onChange={(event) => setData('user_id', event.target.value)}
                        className="mt-1 block w-full rounded-md border-slate-300"
                    >
                        <option value="">Select user</option>
                        {users.map((user) => (
                            <option key={user.id} value={user.id}>
                                {user.name} ({user.email})
                            </option>
                        ))}
                    </select>
                    <InputError message={errors.user_id} className="mt-2" />
                </div>

                <div>
                    <InputLabel htmlFor="plan_id" value="Plan" />
                    <select
                        id="plan_id"
                        value={data.plan_id}
                        onChange={(event) => setData('plan_id', event.target.value)}
                        className="mt-1 block w-full rounded-md border-slate-300"
                    >
                        <option value="">Select plan</option>
                        {plans.map((plan) => (
                            <option key={plan.id} value={plan.id}>
                                {plan.name}
                            </option>
                        ))}
                    </select>
                    <InputError message={errors.plan_id} className="mt-2" />
                </div>

                <div className="grid gap-4 md:grid-cols-2">
                    <div>
                        <InputLabel htmlFor="start_date" value="Start Date" />
                        <input
                            id="start_date"
                            type="date"
                            value={data.start_date}
                            onChange={(event) =>
                                setData('start_date', event.target.value)
                            }
                            className="mt-1 block w-full rounded-md border-slate-300"
                        />
                        <InputError message={errors.start_date} className="mt-2" />
                    </div>

                    <div>
                        <InputLabel htmlFor="end_date" value="End Date (optional)" />
                        <input
                            id="end_date"
                            type="date"
                            value={data.end_date}
                            onChange={(event) =>
                                setData('end_date', event.target.value)
                            }
                            className="mt-1 block w-full rounded-md border-slate-300"
                        />
                        <InputError message={errors.end_date} className="mt-2" />
                    </div>
                </div>

                <div>
                    <InputLabel htmlFor="status" value="Status" />
                    <select
                        id="status"
                        value={data.status}
                        onChange={(event) => setData('status', event.target.value)}
                        className="mt-1 block w-full rounded-md border-slate-300"
                    >
                        {statuses.map((status) => (
                            <option key={status} value={status}>
                                {status.toUpperCase()}
                            </option>
                        ))}
                    </select>
                    <InputError message={errors.status} className="mt-2" />
                </div>

                <div className="flex gap-3">
                    <button
                        type="submit"
                        disabled={processing}
                        className="rounded-md bg-slate-900 px-4 py-2 text-sm text-white disabled:opacity-50"
                    >
                        {mode === 'edit'
                            ? 'Update Subscription'
                            : 'Create Subscription'}
                    </button>
                    <Link
                        href={route('admin.subscriptions.index')}
                        className="rounded-md border border-slate-300 px-4 py-2 text-sm text-slate-700"
                    >
                        Cancel
                    </Link>
                </div>
            </form>
        </AdminLayout>
    );
}
