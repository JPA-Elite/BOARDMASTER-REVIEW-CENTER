import { useState, useEffect } from 'react';
import Checkbox from '@/Components/Checkbox';
import InputError from '@/Components/InputError';
import { Head, Link, useForm } from '@inertiajs/react';
import { toast } from 'react-toastify';

// Helper to get initial cooldown from localStorage
function getInitialCooldown() {
    if (typeof window === 'undefined') return 0;
    const stored = localStorage.getItem('login_cooldown');
    if (stored) {
        const remaining = parseInt(stored, 10);
        return remaining > 0 && !isNaN(remaining) ? remaining : 0;
    }
    return 0;
}

export default function Login({ status, canResetPassword }) {
    const { data, setData, post, processing, errors, reset, setError, clearErrors } = useForm({
        email: '',
        password: '',
        remember: false,
    });

    const [cooldownSeconds, setCooldownSeconds] = useState(getInitialCooldown);
    const [attempts, setAttempts] = useState(0);
    const [throttleError, setThrottleError] = useState(
        getInitialCooldown() > 0 ? 'Too many login attempts. Please try again.' : ''
    );

    // Check for stored cooldown in localStorage
    useEffect(() => {
        const stored = localStorage.getItem('login_cooldown');
        const storedAttempts = localStorage.getItem('login_attempts');
        if (stored) {
            const remaining = parseInt(stored, 10);
            if (remaining > 0 && !isNaN(remaining)) {
                setCooldownSeconds(remaining);
                setThrottleError('Too many login attempts. Please try again.');
            } else {
                localStorage.removeItem('login_cooldown');
            }
        }
        if (storedAttempts) {
            setAttempts(parseInt(storedAttempts, 10) || 0);
        }
    }, []);

    // Countdown timer
    useEffect(() => {
        if (cooldownSeconds <= 0) {
            if (localStorage.getItem('login_cooldown')) {
                localStorage.removeItem('login_cooldown');
                localStorage.removeItem('login_attempts');
                setAttempts(0);
                setThrottleError('');
            }
            return;
        }

        const timer = setInterval(() => {
            setCooldownSeconds((prev) => {
                const newValue = prev - 1;
                if (newValue <= 0) {
                    localStorage.removeItem('login_cooldown');
                    localStorage.removeItem('login_attempts');
                    setAttempts(0);
                    setThrottleError('');
                    return 0;
                }
                localStorage.setItem('login_cooldown', newValue.toString());
                return newValue;
            });
        }, 1000);

        return () => clearInterval(timer);
    }, [cooldownSeconds]);

    const submit = (event) => {
        event.preventDefault();

        if (cooldownSeconds > 0) {
            return;
        }

        // Increment attempts locally for UI feedback
        const newAttempts = attempts + 1;
        setAttempts(newAttempts);
        localStorage.setItem('login_attempts', newAttempts.toString());

        post(route('login'), {
            onSuccess: () => {
                toast.success('Logged in successfully.');
                reset('password');
                // Clear attempts on success
                localStorage.removeItem('login_attempts');
                localStorage.removeItem('login_cooldown');
                setAttempts(0);
                setThrottleError('');
            },
            onError: (err) => {
                // Check if throttled error returned from server
                const errorMsg = err.email || err.password || '';
                if (errorMsg.includes('Too many login attempts') || errorMsg.includes('throttle')) {
                    // Extract seconds from error message or default to 60
                    const match = errorMsg.match(/(\d+)\s*(second|seconds|minute)/i);
                    let seconds = 60;
                    if (match) {
                        if (match[2].includes('minute')) {
                            seconds = parseInt(match[1], 10) * 60;
                        } else {
                            seconds = parseInt(match[1], 10);
                        }
                    }
                    setCooldownSeconds(seconds);
                    setThrottleError('Too many login attempts. Please try again.');
                    localStorage.setItem('login_cooldown', seconds.toString());
                    // Reset attempts after cooldown starts
                    localStorage.setItem('login_attempts', '0');
                    setAttempts(0);

                    // Clear throttle errors so they don't show in InputError fields
                    clearErrors(['email', 'password']);
                    return;
                }

                // Regular auth errors stay
            },
            onFinish: () => reset('password'),
        });
    };

    const formatTime = (seconds) => {
        const mins = Math.floor(seconds / 60);
        const secs = seconds % 60;
        if (mins > 0) {
            return `${mins}:${secs.toString().padStart(2, '0')}`;
        }
        return `${secs} seconds`;
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
                                alt="BoardMasters Review Center"
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
                    {status && (
                        <div className="mb-4 rounded-lg border border-emerald-200 bg-emerald-50 px-3 py-2 text-sm text-emerald-700">
                            {status}
                        </div>
                    )}

                    {/* Attempts warning */}
                    {attempts >= 3 && attempts < 5 && cooldownSeconds === 0 && (
                        <div className="mb-4 rounded-lg border border-amber-200 bg-amber-50 px-3 py-2 text-sm text-amber-700">
                            Warning: {5 - attempts} attempts remaining before lockout.
                        </div>
                    )}

                    {/* Throttle error */}
                    {throttleError && cooldownSeconds > 0 && (
                        <div className="mb-4 rounded-lg border border-red-200 bg-red-50 px-3 py-2 text-sm text-red-700">
                            {throttleError}
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
                                disabled={cooldownSeconds > 0}
                                className="block h-10 w-full rounded-md border border-slate-300 px-3 text-sm text-slate-900 outline-none ring-slate-200 transition focus:border-slate-500 focus:ring-2 disabled:bg-slate-100 disabled:text-slate-500"
                                placeholder="Enter your email"
                            />
                            <InputError
                                message={
                                    throttleError && cooldownSeconds > 0
                                        ? undefined
                                        : errors.email
                                }
                                className="mt-2"
                            />
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
                                disabled={cooldownSeconds > 0}
                                className="block h-10 w-full rounded-md border border-slate-300 px-3 text-sm text-slate-900 outline-none ring-slate-200 transition focus:border-slate-500 focus:ring-2 disabled:bg-slate-100 disabled:text-slate-500"
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
                                    disabled={cooldownSeconds > 0}
                                />
                                Remember me
                            </label>

                        </div>

                        <button
                            type="submit"
                            disabled={processing || cooldownSeconds > 0}
                            className="inline-flex h-10 w-full items-center justify-center rounded-md bg-slate-900 px-4 text-sm font-medium text-white transition hover:bg-slate-800 disabled:cursor-not-allowed disabled:opacity-50"
                        >
                            {cooldownSeconds > 0
                                ? `Try again in ${formatTime(cooldownSeconds)}`
                                : processing
                                    ? 'Signing in...'
                                    : 'Sign in'
                            }
                        </button>
                    </form>

                </div>
            </div>
        </div>
    );
}