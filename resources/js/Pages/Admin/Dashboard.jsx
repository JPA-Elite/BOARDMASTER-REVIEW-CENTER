import AdminLayout from '@/Layouts/AdminLayout';
import { Head } from '@inertiajs/react';

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

function linePoints(data, width, height, maxValue) {
    if (!data.length || maxValue <= 0) {
        return '';
    }

    return data
        .map((point, index) => {
            const x = (index / Math.max(data.length - 1, 1)) * width;
            const y = height - (point.count / maxValue) * height;
            return `${x},${y}`;
        })
        .join(' ');
}

export default function Dashboard({
    dateLabel,
    kpis,
    userSignups,
    contentProduction,
    recentAttempts,
}) {
    const chartWidth = 620;
    const chartHeight = 220;
    const maxSignups = Math.max(...userSignups.map((item) => item.count), 1);
    const signupsLine = linePoints(userSignups, chartWidth, chartHeight, maxSignups);
    const signupsArea = signupsLine
        ? `${signupsLine} ${chartWidth},${chartHeight} 0,${chartHeight}`
        : '';

    const maxContent = Math.max(
        ...contentProduction.map((item) => item.published + item.draft),
        1,
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

                <div className="grid gap-4 md:grid-cols-2 xl:grid-cols-4">
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
                    <div className="rounded-2xl border border-slate-200 bg-white p-5 xl:col-span-4">
                        <h2 className="text-xl font-semibold text-slate-900">
                            User Signups
                        </h2>
                        <p className="text-sm text-slate-500">
                            New user registrations over time
                        </p>

                        <div className="mt-4 h-[300px] rounded-xl border border-slate-100 bg-slate-50/40 p-3">
                            {signupsLine ? (
                                <svg
                                    viewBox={`0 0 ${chartWidth} ${chartHeight}`}
                                    className="h-full w-full"
                                    preserveAspectRatio="none"
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
                                    <polyline
                                        fill="url(#signupFill)"
                                        stroke="none"
                                        points={signupsArea}
                                    />
                                    <polyline
                                        fill="none"
                                        stroke="#6366f1"
                                        strokeWidth="3"
                                        points={signupsLine}
                                    />
                                    {userSignups.map((point, index) => {
                                        const x =
                                            (index /
                                                Math.max(
                                                    userSignups.length - 1,
                                                    1,
                                                )) *
                                            chartWidth;
                                        const y =
                                            chartHeight -
                                            (point.count / maxSignups) *
                                                chartHeight;

                                        return (
                                            <circle
                                                key={point.month + index}
                                                cx={x}
                                                cy={y}
                                                r="4.5"
                                                fill="#6366f1"
                                                stroke="#ffffff"
                                                strokeWidth="2"
                                            />
                                        );
                                    })}
                                </svg>
                            ) : (
                                <div className="flex h-full items-center justify-center text-sm text-slate-500">
                                    No signup data available.
                                </div>
                            )}
                        </div>
                    </div>

                    <div className="rounded-2xl border border-slate-200 bg-white p-5 xl:col-span-3">
                        <h2 className="text-xl font-semibold text-slate-900">
                            Content Production
                        </h2>
                        <p className="text-sm text-slate-500">
                            Pages created by month and status
                        </p>

                        <div className="mt-4 flex h-[300px] items-end gap-4 rounded-xl border border-slate-100 bg-slate-50/40 px-4 pb-4 pt-8">
                            {contentProduction.map((item) => {
                                const publishedHeight =
                                    ((item.published || 0) / maxContent) * 210;
                                const draftHeight =
                                    ((item.draft || 0) / maxContent) * 210;

                                return (
                                    <div
                                        key={item.month}
                                        className="flex flex-1 flex-col items-center gap-2"
                                    >
                                        <div className="flex w-full flex-col justify-end">
                                            <div
                                                className="w-full rounded-t-md border border-slate-700/80 bg-cyan-500"
                                                style={{
                                                    height: `${Math.max(
                                                        draftHeight,
                                                        8,
                                                    )}px`,
                                                }}
                                            />
                                            <div
                                                className="w-full border border-slate-700/80 border-t-0 bg-emerald-500"
                                                style={{
                                                    height: `${Math.max(
                                                        publishedHeight,
                                                        8,
                                                    )}px`,
                                                }}
                                            />
                                        </div>
                                        <span className="text-xs font-medium text-slate-500">
                                            {item.month}
                                        </span>
                                    </div>
                                );
                            })}
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

