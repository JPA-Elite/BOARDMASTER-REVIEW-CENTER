import AdminLayout from '@/Layouts/AdminLayout';
import { Head } from '@inertiajs/react';

function BarChart({ data, valueKeys, colors, height = 240 }) {
    const maxValue = Math.max(
        ...data.map((item) =>
            valueKeys.reduce((sum, key) => sum + (item[key] || 0), 0),
        ),
        1,
    );

    return (
        <div className="flex h-[260px] items-end gap-4 rounded-xl border border-slate-100 bg-slate-50/40 px-4 pb-4 pt-6">
            {data.map((item) => {
                const total = valueKeys.reduce(
                    (sum, key) => sum + (item[key] || 0),
                    0,
                );
                const totalHeight = (total / maxValue) * height;

                return (
                    <div
                        key={item.month}
                        className="flex flex-1 flex-col items-center gap-2"
                    >
                        <div className="flex w-full flex-col justify-end">
                            {valueKeys.map((key, index) => {
                                const barHeight =
                                    ((item[key] || 0) / maxValue) * height;
                                const color = colors[index] || '#4f46e5';

                                return (
                                    <div
                                        key={key}
                                        className="w-full border border-slate-700/80"
                                        style={{
                                            height: `${Math.max(
                                                barHeight,
                                                6,
                                            )}px`,
                                            backgroundColor: color,
                                            borderTopLeftRadius:
                                                index === 0 ? '6px' : '0',
                                            borderTopRightRadius:
                                                index === 0 ? '6px' : '0',
                                        }}
                                    />
                                );
                            })}
                            {totalHeight <= 6 && (
                                <div className="h-2 w-full rounded bg-slate-200" />
                            )}
                        </div>
                        <span className="text-xs font-medium text-slate-500">
                            {item.month}
                        </span>
                    </div>
                );
            })}
        </div>
    );
}

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
                        <div className="mt-4">
                            <BarChart
                                data={signups}
                                valueKeys={['count']}
                                colors={['#6366f1']}
                            />
                        </div>
                    </div>

                    <div className="rounded-2xl border border-slate-200 bg-white p-5">
                        <h2 className="text-lg font-semibold text-slate-900">
                            Content Production
                        </h2>
                        <p className="text-sm text-slate-500">
                            Published vs draft pages
                        </p>
                        <div className="mt-4">
                            <BarChart
                                data={content}
                                valueKeys={['draft', 'published']}
                                colors={['#22c55e', '#06b6d4']}
                            />
                        </div>
                    </div>
                </div>
            </div>
        </AdminLayout>
    );
}
