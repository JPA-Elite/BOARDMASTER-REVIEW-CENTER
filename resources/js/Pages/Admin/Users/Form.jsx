import InputError from '@/Components/InputError';
import InputLabel from '@/Components/InputLabel';
import TextInput from '@/Components/TextInput';
import AdminLayout from '@/Layouts/AdminLayout';
import { Head, Link, useForm } from '@inertiajs/react';

export default function Form({ mode, user, roles, referrers }) {
    const { data, setData, post, put, processing, errors } = useForm({
        name: user?.name || '',
        email: user?.email || '',
        password: '',
        password_confirmation: '',
        role: user?.role || roles[0],
        referral_code: user?.referral_code || '',
        referred_by: user?.referred_by || '',
    });

    const submit = (event) => {
        event.preventDefault();

        if (mode === 'edit') {
            put(route('admin.users.update', user.id));
            return;
        }

        post(route('admin.users.store'));
    };

    return (
        <AdminLayout title={mode === 'edit' ? 'Edit User' : 'Create User'}>
            <Head title={mode === 'edit' ? 'Edit User' : 'Create User'} />

            <form onSubmit={submit} className="space-y-4 rounded-xl bg-white p-6 shadow-sm">
                <div>
                    <InputLabel htmlFor="name" value="Name" />
                    <TextInput
                        id="name"
                        value={data.name}
                        onChange={(event) => setData('name', event.target.value)}
                        className="mt-1 block w-full"
                    />
                    <InputError message={errors.name} className="mt-2" />
                </div>

                <div>
                    <InputLabel htmlFor="email" value="Email" />
                    <TextInput
                        id="email"
                        type="email"
                        value={data.email}
                        onChange={(event) => setData('email', event.target.value)}
                        className="mt-1 block w-full"
                    />
                    <InputError message={errors.email} className="mt-2" />
                </div>

                <div className="grid gap-4 md:grid-cols-2">
                    <div>
                        <InputLabel htmlFor="password" value={mode === 'edit' ? 'Password (optional)' : 'Password'} />
                        <TextInput
                            id="password"
                            type="password"
                            value={data.password}
                            onChange={(event) => setData('password', event.target.value)}
                            className="mt-1 block w-full"
                        />
                        <InputError message={errors.password} className="mt-2" />
                    </div>

                    <div>
                        <InputLabel htmlFor="password_confirmation" value="Confirm Password" />
                        <TextInput
                            id="password_confirmation"
                            type="password"
                            value={data.password_confirmation}
                            onChange={(event) =>
                                setData('password_confirmation', event.target.value)
                            }
                            className="mt-1 block w-full"
                        />
                    </div>
                </div>

                <div className="grid gap-4 md:grid-cols-2">
                    <div>
                        <InputLabel htmlFor="role" value="Role" />
                        <select
                            id="role"
                            value={data.role}
                            onChange={(event) => setData('role', event.target.value)}
                            className="mt-1 block w-full rounded-md border-slate-300"
                        >
                            {roles.map((role) => (
                                <option key={role} value={role}>
                                    {role.toUpperCase()}
                                </option>
                            ))}
                        </select>
                        <InputError message={errors.role} className="mt-2" />
                    </div>

                    <div>
                        <InputLabel htmlFor="referral_code" value="Referral Code (optional)" />
                        <TextInput
                            id="referral_code"
                            value={data.referral_code}
                            onChange={(event) =>
                                setData('referral_code', event.target.value.toUpperCase())
                            }
                            className="mt-1 block w-full"
                        />
                        <InputError message={errors.referral_code} className="mt-2" />
                    </div>
                </div>

                <div>
                    <InputLabel htmlFor="referred_by" value="Referred By (optional)" />
                    <select
                        id="referred_by"
                        value={data.referred_by}
                        onChange={(event) => setData('referred_by', event.target.value)}
                        className="mt-1 block w-full rounded-md border-slate-300"
                    >
                        <option value="">None</option>
                        {referrers.map((referrer) => (
                            <option key={referrer.id} value={referrer.id}>
                                {referrer.name} ({referrer.email})
                            </option>
                        ))}
                    </select>
                    <InputError message={errors.referred_by} className="mt-2" />
                </div>

                <div className="flex gap-3">
                    <button
                        type="submit"
                        disabled={processing}
                        className="rounded-md bg-slate-900 px-4 py-2 text-sm text-white disabled:opacity-50"
                    >
                        {mode === 'edit' ? 'Update User' : 'Create User'}
                    </button>
                    <Link
                        href={route('admin.users.index')}
                        className="rounded-md border border-slate-300 px-4 py-2 text-sm text-slate-700"
                    >
                        Cancel
                    </Link>
                </div>
            </form>
        </AdminLayout>
    );
}
