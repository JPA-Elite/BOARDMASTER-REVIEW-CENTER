import AdminLayout from '@/Layouts/AdminLayout';
import { Head, router } from '@inertiajs/react';
import { Users } from 'lucide-react';
import { useState } from 'react';
import {
    Area,
    Bar,
    CartesianGrid,
    ComposedChart,
    Line,
    ResponsiveContainer,
    Tooltip,
    XAxis,
    YAxis,
} from 'recharts';

function TrendBadge({ value }) {
    if (value === null || value === undefined) {
        return null;
    }

    const positive = value >= 0;

    return (
        <span
            className={`inline-flex items-center rounded-full px-2 py-0.5 text-[11px] font-semibold ${
                positive
                    ? 'bg-emerald-100 text-emerald-700'
                    : 'bg-rose-100 text-rose-700'
            }`}
        >
            {positive ? '+' : ''}
            {value}%
        </span>
    );
}

function SignupTooltip({ active, payload, label }) {
    if (!active || !payload || payload.length === 0) {
        return null;
    }

    const countEntry = payload.find((entry) => entry?.dataKey === 'count');
    const countValue = countEntry?.value ?? payload[0]?.value ?? 0;

    return (
        <div className="rounded-xl border border-slate-200 bg-white px-3 py-2 shadow-sm">
            <p className="text-xs font-semibold text-slate-900">
                Month: {label}
            </p>
            <p className="mt-1 text-sm font-semibold text-indigo-600">
                Signups: {countValue}
            </p>
        </div>
    );
}

export default function Dashboard({
    dateLabel,
    kpis,
    userSignups,
    signupYears,
    signupYear,
    recentAttempts,
}) {
    const [signupChartType, setSignupChartType] = useState('line');

    const totalSignups = userSignups.reduce(
        (total, item) => total + (Number(item?.count) || 0),
        0,
    );

    return (
        <AdminLayout title="Dashboard">
            <Head title="Admin Dashboard" />

            <div className="space-y-5">
                <div className="flex items-end justify-between">
                    <div>
                        <h1 className="text-4xl font-semibold tracking-tight text-slate-950">
                            Dashboard
                        </h1>
                        <p className="mt-1 text-lg text-slate-500">
                            Welcome back. Here's what's happening today.
                        </p>
                    </div>
                    <p className="hidden text-sm font-medium text-slate-500 sm:block">
                        {dateLabel}
                    </p>
                </div>

                <div className="grid gap-4 md:grid-cols-2 xl:grid-cols-3 2xl:grid-cols-4">
                    {kpis.map((item) => (
                        <div
                            key={item.title}
                            className="rounded-2xl border border-slate-200 bg-white px-6 py-5"
                        >
                            <p className="text-lg font-semibold text-slate-900">
                                {item.title}
                            </p>
                            <p className="mt-4 text-4xl font-bold text-slate-950">
                                {item.value}
                            </p>
                            <div className="mt-3 flex items-center gap-2">
                                <TrendBadge value={item.trend} />
                                {item.trend !== null && (
                                    <span className="text-xs text-slate-400">
                                        vs last month
                                    </span>
                                )}
                            </div>
                        </div>
                    ))}
                </div>

                <div className="grid gap-4 xl:grid-cols-7">
                    <div className="rounded-2xl border border-slate-200 bg-white p-5 xl:col-span-7">
                        <div className="flex flex-wrap items-start justify-between gap-3">
                            <div>
                                <h2 className="text-xl font-semibold text-slate-900">
                                    User Signups
                                </h2>
                                <div className="mt-1 flex flex-wrap items-center gap-2 text-sm text-slate-500">
                                    <span>Total user registrations in {signupYear}</span>
                                    <span className="text-slate-500">/</span>
                                    <span className="inline-flex items-center gap-1.5 rounded-full bg-gradient-to-r from-indigo-50 to-violet-50 px-2.5 py-1 text-xs font-semibold text-indigo-700 ring-1 ring-inset ring-indigo-100">
                                        <Users className="h-3.5 w-3.5" aria-hidden="true" />
                                        Total:{' '}
                                        <span className="tabular-nums text-slate-950">
                                            {totalSignups}
                                        </span>{' '}
                                        <span className="font-medium text-slate-600">
                                            {totalSignups === 1 ? 'user' : 'users'}
                                        </span>
                                    </span>
                                </div>
                            </div>
                            <div className="flex items-center gap-2 text-sm text-slate-600">
                                <span className="text-xs uppercase tracking-wide text-slate-400">
                                    Year
                                </span>
                                <select
                                    className="rounded-lg border border-slate-200 bg-white px-3 py-1.5 pr-8 text-sm font-medium text-slate-700 shadow-sm focus:border-indigo-400 focus:outline-none focus:ring-2 focus:ring-indigo-100"
                                    value={signupYear}
                                    onChange={(event) => {
                                        const year = event.target.value;
                                        router.get(
                                            route('admin.dashboard'),
                                            { year },
                                            {
                                                preserveState: true,
                                                preserveScroll: true,
                                                replace: true,
                                            },
                                        );
                                    }}
                                >
                                    {signupYears?.map((year) => (
                                        <option key={year} value={year}>
                                            {year}
                                        </option>
                                    ))}
                                </select>
                            </div>
                        </div>

                        <div className="mt-4 h-[300px] rounded-xl border border-slate-100 bg-slate-50/40 p-3">
                            {userSignups.length === 0 ? (
                                <div className="flex h-full items-center justify-center text-sm text-slate-500">
                                    No signup data available.
                                </div>
                            ) : (
                                <ResponsiveContainer width="100%" height="100%">
                                    <ComposedChart
                                        data={userSignups}
                                        margin={{ top: 26, right: 12, left: 6, bottom: 16 }}
                                    >
                                        <defs>
                                            <linearGradient
                                                id="signupFill"
                                                x1="0"
                                                x2="0"
                                                y1="0"
                                                y2="1"
                                            >
                                                <stop
                                                    offset="0%"
                                                    stopColor="#818cf8"
                                                    stopOpacity="0.45"
                                                />
                                                <stop
                                                    offset="100%"
                                                    stopColor="#818cf8"
                                                    stopOpacity="0.05"
                                                />
                                            </linearGradient>
                                        </defs>
                                        <CartesianGrid
                                            strokeDasharray="3 3"
                                            stroke="#e2e8f0"
                                            vertical={false}
                                        />
                                        <XAxis
                                            dataKey="month"
                                            tick={{ fontSize: 12, fill: '#64748b' }}
                                            axisLine={false}
                                            tickLine={false}
                                        />
                                        <YAxis
                                            allowDecimals={false}
                                            tick={{ fontSize: 12, fill: '#94a3b8' }}
                                            axisLine={false}
                                            tickLine={false}
                                        />
                                        <Tooltip
                                            cursor={
                                                signupChartType === 'bar'
                                                    ? { fill: '#e2e8f0' }
                                                    : { stroke: '#cbd5f5' }
                                            }
                                            content={(props) => (
                                                <SignupTooltip {...props} />
                                            )}
                                        />
                                        {signupChartType === 'bar' ? (
                                            <Bar
                                                dataKey="count"
                                                fill="#6366f1"
                                                radius={[10, 10, 0, 0]}
                                                barSize={26}
                                            />
                                        ) : (
                                            <>
                                                <Area
                                                    type="monotone"
                                                    dataKey="count"
                                                    stroke="#6366f1"
                                                    strokeWidth={2}
                                                    fill="url(#signupFill)"
                                                />
                                                <Line
                                                    type="monotone"
                                                    dataKey="count"
                                                    stroke="#4f46e5"
                                                    strokeWidth={2}
                                                    dot={{
                                                        r: 3,
                                                        stroke: '#4f46e5',
                                                        strokeWidth: 2,
                                                        fill: '#ffffff',
                                                    }}
                                                    activeDot={{
                                                        r: 5,
                                                        stroke: '#4f46e5',
                                                        strokeWidth: 2,
                                                        fill: '#ffffff',
                                                    }}
                                                />
                                            </>
                                        )}
                                    </ComposedChart>
                                </ResponsiveContainer>
                            )}
                        </div>

                        {userSignups.length > 0 && (
                            <div className="mt-4 flex justify-center">
                                <div className="inline-flex rounded-xl bg-slate-100 p-1 ring-1 ring-inset ring-slate-200">
                                    <button
                                        type="button"
                                        onClick={() => setSignupChartType('line')}
                                        className={`rounded-lg px-3 py-1.5 text-xs font-semibold transition ${
                                            signupChartType === 'line'
                                                ? 'bg-white text-slate-900 shadow-sm ring-1 ring-inset ring-slate-200'
                                                : 'text-slate-500 hover:text-slate-700'
                                        }`}
                                    >
                                        Line
                                    </button>
                                    <button
                                        type="button"
                                        onClick={() => setSignupChartType('bar')}
                                        className={`rounded-lg px-3 py-1.5 text-xs font-semibold transition ${
                                            signupChartType === 'bar'
                                                ? 'bg-white text-slate-900 shadow-sm ring-1 ring-inset ring-slate-200'
                                                : 'text-slate-500 hover:text-slate-700'
                                        }`}
                                    >
                                        Bar
                                    </button>
                                </div>
                            </div>
                        )}
                    </div>
                </div>

                <div className="rounded-2xl border border-slate-200 bg-white p-5">
                    <h3 className="text-sm font-semibold uppercase tracking-wide text-slate-600">
                        Recent Activity
                    </h3>
                    <div className="mt-3 space-y-2">
                        {recentAttempts.length === 0 && (
                            <p className="text-sm text-slate-500">
                                No recent quiz attempts.
                            </p>
                        )}

                        {recentAttempts.map((attempt) => (
                            <div
                                key={attempt.id}
                                className="flex items-center justify-between rounded-lg border border-slate-100 px-3 py-2"
                            >
                                <p className="text-sm text-slate-700">
                                    {attempt.user} took {attempt.subject}
                                </p>
                                <p className="text-sm font-medium text-slate-900">
                                    {attempt.score}/{attempt.total_questions}
                                </p>
                            </div>
                        ))}
                    </div>
                </div>
            </div>
        </AdminLayout>
    );
}
