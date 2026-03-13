import AdminLayout from '@/Layouts/AdminLayout';
import { Head, usePage } from '@inertiajs/react';
import PaymongoGatewayForm from '@/Pages/Admin/Partials/PaymongoGatewayForm';
import PaymongoWebhookLog from '@/Pages/Admin/Partials/PaymongoWebhookLog';
import { useEffect, useState } from 'react';

export default function PaymentGateway({
    paymongo,
    plans,
    users,
    paymongoWebhookLogs,
    paymongoRedirectUrl,
}) {
    const { flash } = usePage().props;
    const [redirecting, setRedirecting] = useState(false);

    useEffect(() => {
        if (!paymongoRedirectUrl) {
            return;
        }

        setRedirecting(true);

        const timeout = setTimeout(() => {
            window.location.href = paymongoRedirectUrl;
        }, 900);

        return () => clearTimeout(timeout);
    }, [paymongoRedirectUrl]);

    return (
        <AdminLayout title="Payment Gateway">
            <Head title="Payment Gateway" />

            <div className="space-y-6">
                {paymongoRedirectUrl && (
                    <div className="rounded-xl border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-700">
                        {flash?.success ?? 'Redirecting to PayMongo checkout...'}
                    </div>
                )}

                <div className="rounded-2xl border border-slate-200 bg-white p-6">
                    <h2 className="text-lg font-semibold text-slate-900">
                        Payment Gateway (PayMongo)
                    </h2>
                    <p className="mt-1 text-sm text-slate-500">
                        Configure credentials by environment and generate a hosted checkout page.
                    </p>
                    <div className="mt-5">
                        <PaymongoGatewayForm
                            paymongo={paymongo}
                            plans={plans}
                            users={users}
                        />
                    </div>
                </div>

                <div className="rounded-2xl border border-slate-200 bg-white p-6">
                    <h2 className="text-lg font-semibold text-slate-900">
                        Webhook Debug (PayMongo)
                    </h2>
                    <p className="mt-1 text-sm text-slate-500">
                        Recent PayMongo webhook deliveries and processing results.
                    </p>
                    <div className="mt-5">
                        <PaymongoWebhookLog logs={paymongoWebhookLogs} />
                    </div>
                </div>
            </div>
        </AdminLayout>
    );
}
