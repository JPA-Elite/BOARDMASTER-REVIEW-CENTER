import AdminLayout from '@/Layouts/AdminLayout';
import { Head } from '@inertiajs/react';
import {
    Bar,
    BarChart,
    CartesianGrid,
    ResponsiveContainer,
    Tooltip,
    XAxis,
    YAxis,
} from 'recharts';

export default function Analytics({ signups, content }) {
    return (
        <AdminLayout title="Analytics">
            <Head title="Analytics" />

            <div className="space-y-6">
                <div>
                    <h1 className="text-3xl font-semibold text-slate-950">
                        Analytics
                    </h1>
                    <p className="text-sm text-slate-500">
                        Track platform activity with simple bar charts.
                    </p>
                </div>

                <div className="grid gap-4 lg:grid-cols-2">
                    <div className="rounded-2xl border border-slate-200 bg-white p-5">
                        <h2 className="text-lg font-semibold text-slate-900">
                            User Signups
                        </h2>
                        <p className="text-sm text-slate-500">
                            New user registrations by month
                        </p>
                        <div className="mt-4 h-[260px] rounded-xl border border-slate-100 bg-slate-50/40 p-3">
                            {signups.length === 0 ? (
                                <div className="flex h-full items-center justify-center text-sm text-slate-500">
                                    No signup data available.
                                </div>
                            ) : (
                                <ResponsiveContainer width="100%" height="100%">
                                    <BarChart
                                        data={signups}
                                        margin={{ top: 10, right: 12, left: 0, bottom: 0 }}
                                    >
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
                                            cursor={{ fill: '#e2e8f0' }}
                                            contentStyle={{
                                                borderRadius: '12px',
                                                borderColor: '#e2e8f0',
                                            }}
                                        />
                                        <Bar
                                            dataKey="count"
                                            fill="#6366f1"
                                            radius={[6, 6, 0, 0]}
                                        />
                                    </BarChart>
                                </ResponsiveContainer>
                            )}
                        </div>
                    </div>

                    <div className="rounded-2xl border border-slate-200 bg-white p-5">
                        <h2 className="text-lg font-semibold text-slate-900">
                            Content Production
                        </h2>
                        <p className="text-sm text-slate-500">
                            Questions created by month
                        </p>
                        <div className="mt-4 h-[260px] rounded-xl border border-slate-100 bg-slate-50/40 p-3">
                            {content.length === 0 ? (
                                <div className="flex h-full items-center justify-center text-sm text-slate-500">
                                    No content data available.
                                </div>
                            ) : (
                                <ResponsiveContainer width="100%" height="100%">
                                    <BarChart
                                        data={content}
                                        margin={{ top: 10, right: 12, left: 0, bottom: 0 }}
                                    >
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
                                            cursor={{ fill: '#e2e8f0' }}
                                            contentStyle={{
                                                borderRadius: '12px',
                                                borderColor: '#e2e8f0',
                                            }}
                                        />
                                        <Bar
                                            dataKey="total"
                                            fill="#6366f1"
                                            radius={[6, 6, 0, 0]}
                                        />
                                    </BarChart>
                                </ResponsiveContainer>
                            )}
                        </div>
                    </div>
                </div>
            </div>
        </AdminLayout>
    );
}
