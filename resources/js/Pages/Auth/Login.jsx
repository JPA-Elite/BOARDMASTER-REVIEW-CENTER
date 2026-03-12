import Checkbox from '@/Components/Checkbox';
import InputError from '@/Components/InputError';
import { Head, Link, useForm } from '@inertiajs/react';

export default function Login({ status, canResetPassword }) {
    const { data, setData, post, processing, errors, reset } = useForm({
        email: '',
        password: '',
        remember: false,
    });

    const submit = (event) => {
        event.preventDefault();

        post(route('login'), {
            onFinish: () => reset('password'),
        });
    };

    return (
        <div className="bg-white flex min-h-screen items-center justify-center p-4">
            <Head title="Log in" />

            <div className="w-full max-w-md rounded-2xl border border-slate-200 bg-white py-6 shadow-sm">
                <div className="space-y-1 px-6 text-center">
                    <div className="mb-4 flex justify-center">
                        <span className="inline-flex size-14 items-center justify-center rounded-xl border border-slate-200 bg-white">
                            <img
                                src="/images/boardmaster.png"
                                alt="Board Master Review Center"
                                className="size-12 object-contain"
                            />
                        </span>
                    </div>
                    <h1 className="text-2xl font-bold text-slate-900">
                        Welcome back
                    </h1>
                    <p className="text-sm text-slate-500">
                        Enter your credentials to access the dashboard
                    </p>
                </div>

                <div className="mt-5 px-6">
                    <div className="mb-4 rounded-lg border border-slate-200 bg-white p-3 text-sm">
                        <p className="font-medium text-slate-800">Demo Access</p>
                        <p className="mt-1 text-slate-600">
                            You can use seeded credentials:
                            <span className="font-mono font-semibold">
                                {' '}
                                admin@example.com
                            </span>
                            {' / '}
                            <span className="font-mono font-semibold">
                                password
                            </span>
                            .
                        </p>
                    </div>

                    {status && (
                        <div className="mb-4 rounded-lg border border-emerald-200 bg-emerald-50 px-3 py-2 text-sm text-emerald-700">
                            {status}
                        </div>
                    )}

                    <form method="POST" onSubmit={submit} className="space-y-4">
                        <div className="space-y-1.5">
                            <label
                                htmlFor="email"
                                className="text-sm font-medium text-slate-800"
                            >
                                Email
                            </label>
                            <input
                                id="email"
                                type="email"
                                name="email"
                                value={data.email}
                                autoComplete="username"
                                autoFocus
                                onChange={(event) =>
                                    setData('email', event.target.value)
                                }
                                className="block h-10 w-full rounded-md border border-slate-300 px-3 text-sm text-slate-900 outline-none ring-slate-200 transition focus:border-slate-500 focus:ring-2"
                                placeholder="Enter your email"
                            />
                            <InputError message={errors.email} className="mt-2" />
                        </div>

                        <div className="space-y-1.5">
                            <label
                                htmlFor="password"
                                className="text-sm font-medium text-slate-800"
                            >
                                Password
                            </label>
                            <input
                                id="password"
                                type="password"
                                name="password"
                                value={data.password}
                                autoComplete="current-password"
                                onChange={(event) =>
                                    setData('password', event.target.value)
                                }
                                className="block h-10 w-full rounded-md border border-slate-300 px-3 text-sm text-slate-900 outline-none ring-slate-200 transition focus:border-slate-500 focus:ring-2"
                                placeholder="Enter your password"
                            />
                            <InputError
                                message={errors.password}
                                className="mt-2"
                            />
                        </div>

                        <div className="flex items-center justify-between">
                            <label className="inline-flex items-center gap-2 text-sm text-slate-600">
                                <Checkbox
                                    name="remember"
                                    checked={data.remember}
                                    onChange={(event) =>
                                        setData('remember', event.target.checked)
                                    }
                                />
                                Remember me
                            </label>

                            {canResetPassword && (
                                <Link
                                    href={route('password.request')}
                                    className="text-sm text-slate-500 underline-offset-4 hover:underline"
                                >
                                    Forgot your password?
                                </Link>
                            )}
                        </div>

                        <button
                            type="submit"
                            disabled={processing}
                            className="inline-flex h-10 w-full items-center justify-center rounded-md bg-slate-900 px-4 text-sm font-medium text-white transition hover:bg-slate-800 disabled:cursor-not-allowed disabled:opacity-50"
                        >
                            Sign in
                        </button>
                    </form>

                    <div className="mt-4 text-center text-sm text-slate-500">
                        Don't have an account?{' '}
                        <Link
                            href={route('register')}
                            className="font-medium text-slate-900 underline-offset-4 hover:underline"
                        >
                            Register
                        </Link>
                    </div>
                </div>
            </div>
        </div>
    );
}
