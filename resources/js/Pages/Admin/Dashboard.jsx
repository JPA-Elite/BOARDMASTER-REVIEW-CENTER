import AdminLayout from '@/Layouts/AdminLayout';
import { Head, router } from '@inertiajs/react';
import {
    Area,
    AreaChart,
    Bar,
    BarChart,
    CartesianGrid,
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

export default function Dashboard({
    dateLabel,
    kpis,
    userSignups,
    signupYears,
    signupYear,
    recentAttempts,
}) {
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
                                <p className="text-sm text-slate-500">
                                    New user registrations in {signupYear}
                                </p>
                            </div>
                            <div className="flex items-center gap-2 text-sm text-slate-600">
                                <span className="text-xs uppercase tracking-wide text-slate-400">
                                    Year
                                </span>
                                <select
                                    className="rounded-lg border border-slate-200 bg-white px-3 py-1.5 text-sm font-medium text-slate-700 shadow-sm focus:border-indigo-400 focus:outline-none focus:ring-2 focus:ring-indigo-100"
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
                                    <AreaChart
                                        data={userSignups}
                                        margin={{ top: 10, right: 12, left: 0, bottom: 0 }}
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
                                            cursor={{ stroke: '#cbd5f5' }}
                                            contentStyle={{
                                                borderRadius: '12px',
                                                borderColor: '#e2e8f0',
                                            }}
                                        />
                                        <Area
                                            type="monotone"
                                            dataKey="count"
                                            stroke="#6366f1"
                                            strokeWidth={2}
                                            fill="url(#signupFill)"
                                        />
                                    </AreaChart>
                                </ResponsiveContainer>
                            )}
                        </div>
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

