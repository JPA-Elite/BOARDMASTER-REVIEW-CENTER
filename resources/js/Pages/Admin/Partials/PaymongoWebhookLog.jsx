import Pagination from '@/Components/Admin/Pagination';
import ConfirmationModal from '@/Components/ConfirmationModal';
import { router } from '@inertiajs/react';
import { useMemo, useState } from 'react';

function formatJson(payload) {
    if (!payload) {
        return '';
    }

    try {
        return JSON.stringify(payload, null, 2);
    } catch (error) {
        return String(payload);
    }
}

function StatusBadge({ status }) {
    const normalized = status || 'unknown';
    const styles = {
        processed: 'bg-emerald-100 text-emerald-700',
        received: 'bg-sky-100 text-sky-700',
        ignored: 'bg-slate-100 text-slate-600',
        failed: 'bg-rose-100 text-rose-700',
        paid_unlinked: 'bg-amber-100 text-amber-700',
        unknown: 'bg-slate-100 text-slate-600',
    };

    return (
        <span className={`rounded-full px-2 py-0.5 text-xs font-semibold ${styles[normalized] || styles.unknown}`}>
            {normalized.replace('_', ' ')}
        </span>
    );
}

export default function PaymongoWebhookLog({ logs }) {
    const [expandedId, setExpandedId] = useState(null);
    const [confirmingId, setConfirmingId] = useState(null);
    const [confirmingClear, setConfirmingClear] = useState(false);

    const entries = useMemo(() => logs?.data ?? logs ?? [], [logs]);
    const links = useMemo(() => logs?.links ?? [], [logs]);

    if (entries.length === 0) {
        return (
            <div className="rounded-xl border border-slate-200 bg-white p-4">
                <p className="text-sm text-slate-500">No webhook events received yet.</p>
            </div>
        );
    }

    const deleteOne = () => {
        if (!confirmingId) {
            return;
        }

        router.delete(route('admin.payment-gateway.webhooks.delete', confirmingId), {
            preserveScroll: true,
            onFinish: () => setConfirmingId(null),
        });
    };

    const deleteAll = () => {
        router.delete(route('admin.payment-gateway.webhooks.clear'), {
            preserveScroll: true,
            onFinish: () => setConfirmingClear(false),
        });
    };

    return (
        <div className="space-y-3">
            <div className="flex flex-wrap items-center justify-between gap-2">
                <p className="text-xs text-slate-500">
                    Showing {entries.length} webhook events.
                </p>
                <button
                    type="button"
                    onClick={() => setConfirmingClear(true)}
                    className="rounded-md border border-rose-200 bg-rose-50 px-3 py-1.5 text-xs font-semibold text-rose-700 hover:bg-rose-100"
                >
                    Delete All
                </button>
            </div>
            {entries.map((entry) => {
                const isExpanded = expandedId === entry.id;

                return (
                    <div key={entry.id} className="rounded-xl border border-slate-200 bg-white p-4">
                        <div className="flex flex-wrap items-center justify-between gap-3">
                            <div className="space-y-1">
                                <p className="text-sm font-semibold text-slate-900">
                                    {entry.event_type || 'Unknown event'}{' '}
                                    <span className="text-xs text-slate-400">({entry.mode})</span>
                                </p>
                                <p className="text-xs text-slate-500">
                                    Event ID: <span className="font-mono">{entry.event_id}</span>
                                </p>
                                {entry.reference && (
                                    <p className="text-xs text-slate-500">
                                        Reference: <span className="font-mono">{entry.reference}</span>
                                    </p>
                                )}
                            </div>
                            <div className="flex items-center gap-3 text-xs text-slate-500">
                                <div className="space-y-1 text-right">
                                    <p>{entry.created_at || 'Pending'}</p>
                                    <p>Processed: {entry.processed_at || 'Pending'}</p>
                                </div>
                                <StatusBadge status={entry.status} />
                                <button
                                    type="button"
                                    onClick={() => setConfirmingId(entry.id)}
                                    className="rounded-md border border-rose-200 bg-rose-50 px-2 py-1 text-[11px] font-semibold text-rose-700 hover:bg-rose-100"
                                >
                                    Delete
                                </button>
                            </div>
                        </div>

                        <div className="mt-3 grid gap-2 text-xs text-slate-600 md:grid-cols-2">
                            <p>
                                Amount: {entry.amount ?? '--'} {entry.currency || ''}
                            </p>
                            <p>
                                User ID: {entry.user_id ?? '--'} | Plan ID: {entry.plan_id ?? '--'}
                            </p>
                            {entry.notes && (
                                <p className="md:col-span-2">
                                    Notes: {entry.notes}
                                </p>
                            )}
                        </div>

                        <button
                            type="button"
                            onClick={() => setExpandedId(isExpanded ? null : entry.id)}
                            className="mt-3 text-xs font-semibold text-slate-600 hover:text-slate-900"
                        >
                            {isExpanded ? 'Hide payload' : 'View payload'}
                        </button>

                        {isExpanded && (
                            <pre className="mt-3 max-h-72 overflow-auto rounded-lg border border-slate-200 bg-slate-50 p-3 text-xs text-slate-700">
{formatJson(entry.payload)}
                            </pre>
                        )}
                    </div>
                );
            })}
            <Pagination links={links} />

            <ConfirmationModal
                show={Boolean(confirmingId)}
                title="Delete webhook log"
                message="Are you sure you want to delete this webhook log? This action cannot be undone."
                onCancel={() => setConfirmingId(null)}
                onConfirm={deleteOne}
            />
            <ConfirmationModal
                show={confirmingClear}
                title="Delete all webhook logs"
                message="Are you sure you want to delete all PayMongo webhook logs? This action cannot be undone."
                confirmText="Delete All"
                onCancel={() => setConfirmingClear(false)}
                onConfirm={deleteAll}
            />
        </div>
    );
}
