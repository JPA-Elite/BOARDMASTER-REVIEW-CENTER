import InputError from '@/Components/InputError';
import InputLabel from '@/Components/InputLabel';
import AdminLayout from '@/Layouts/AdminLayout';
import { Head, Link, useForm } from '@inertiajs/react';

export default function Form({ mode, referral, users }) {
    const { data, setData, post, put, processing, errors } = useForm({
        inviter_id: referral?.inviter_id || '',
        invited_user_id: referral?.invited_user_id || '',
    });

    const submit = (event) => {
        event.preventDefault();

        if (mode === 'edit') {
            put(route('admin.referrals.update', referral.id));
            return;
        }

        post(route('admin.referrals.store'));
    };

    return (
        <AdminLayout
            title={mode === 'edit' ? 'Edit Referral' : 'Create Referral'}
        >
            <Head
                title={mode === 'edit' ? 'Edit Referral' : 'Create Referral'}
            />

            <form onSubmit={submit} className="space-y-4 rounded-xl bg-white p-6 shadow-sm">
                <div>
                    <InputLabel htmlFor="inviter_id" value="Inviter" />
                    <select
                        id="inviter_id"
                        value={data.inviter_id}
                        onChange={(event) =>
                            setData('inviter_id', event.target.value)
                        }
                        className="mt-1 block w-full rounded-md border-slate-300"
                    >
                        <option value="">Select inviter</option>
                        {users.map((user) => (
                            <option key={user.id} value={user.id}>
                                {user.name} ({user.email})
                            </option>
                        ))}
                    </select>
                    <InputError message={errors.inviter_id} className="mt-2" />
                </div>

                <div>
                    <InputLabel htmlFor="invited_user_id" value="Invited User" />
                    <select
                        id="invited_user_id"
                        value={data.invited_user_id}
                        onChange={(event) =>
                            setData('invited_user_id', event.target.value)
                        }
                        className="mt-1 block w-full rounded-md border-slate-300"
                    >
                        <option value="">Select invited user</option>
                        {users.map((user) => (
                            <option key={user.id} value={user.id}>
                                {user.name} ({user.email})
                            </option>
                        ))}
                    </select>
                    <InputError
                        message={errors.invited_user_id}
                        className="mt-2"
                    />
                </div>

                <div className="flex gap-3">
                    <button
                        type="submit"
                        disabled={processing}
                        className="rounded-md bg-slate-900 px-4 py-2 text-sm text-white disabled:opacity-50"
                    >
                        {mode === 'edit' ? 'Update Referral' : 'Create Referral'}
                    </button>
                    <Link
                        href={route('admin.referrals.index')}
                        className="rounded-md border border-slate-300 px-4 py-2 text-sm text-slate-700"
                    >
                        Cancel
                    </Link>
                </div>
            </form>
        </AdminLayout>
    );
}
