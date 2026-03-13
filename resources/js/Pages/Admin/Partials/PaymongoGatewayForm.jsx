import InputError from '@/Components/InputError';
import InputLabel from '@/Components/InputLabel';
import TextInput from '@/Components/TextInput';
import { useForm } from '@inertiajs/react';

const paymentMethodLabels = {
    card: 'Card',
    gcash: 'GCash',
    grab_pay: 'GrabPay',
    paymaya: 'PayMaya',
    qrph: 'QRPH',
};

export default function PaymongoGatewayForm({ paymongo, plans, users }) {
    const supportedMethods = paymongo?.supportedPaymentMethods ?? [
        'card',
        'gcash',
        'grab_pay',
        'paymaya',
        'qrph',
    ];

    const defaultMethods =
        paymongo?.defaultPaymentMethods?.length > 0
            ? paymongo.defaultPaymentMethods
            : supportedMethods.slice(0, 2);

    const connectionForm = useForm({});
    const checkoutForm = useForm({
        plan_id: '',
        user_id: '',
        amount: '',
        description: '',
        reference: '',
        payment_method_types: defaultMethods,
    });

    const selectPlan = (planId) => {
        checkoutForm.setData('plan_id', planId);

        if (!planId) {
            checkoutForm.setData('amount', '');
            checkoutForm.setData('description', '');
            return;
        }

        const selectedPlan = plans.find(
            (plan) => String(plan.id) === String(planId),
        );

        if (!selectedPlan) {
            return;
        }

        checkoutForm.setData('amount', Number(selectedPlan.price).toFixed(2));
        checkoutForm.setData('description', `${selectedPlan.name} Subscription`);
    };

    const togglePaymentMethod = (method) => {
        const currentMethods = checkoutForm.data.payment_method_types;

        if (currentMethods.includes(method)) {
            checkoutForm.setData(
                'payment_method_types',
                currentMethods.filter((item) => item !== method),
            );

            return;
        }

        checkoutForm.setData('payment_method_types', [...currentMethods, method]);
    };

    const submitCheckout = (event) => {
        event.preventDefault();

        checkoutForm.post(route('admin.payment-gateway.checkout'), {
            preserveScroll: true,
        });
    };

    const testConnection = (event) => {
        event.preventDefault();

        connectionForm.post(route('admin.payment-gateway.test'), {
            preserveScroll: true,
        });
    };

    return (
        <section className="space-y-4">
            <header className="space-y-2">
                <div className="flex flex-wrap items-center gap-2 text-sm">
                    <span className="rounded-full bg-slate-100 px-3 py-1 font-semibold text-slate-700">
                        Mode: {String(paymongo?.mode ?? 'test').toUpperCase()}
                    </span>
                    <span
                        className={`rounded-full px-3 py-1 font-semibold ${
                            paymongo?.hasActiveSecretKey
                                ? 'bg-emerald-100 text-emerald-700'
                                : 'bg-amber-100 text-amber-700'
                        }`}
                    >
                        {paymongo?.hasActiveSecretKey
                            ? 'Active key configured'
                            : 'Active key missing'}
                    </span>
                </div>
                <p className="text-sm text-slate-500">
                    Add credentials in <code>.env</code> using{' '}
                    <code>PAYMONGO_TEST_*</code> and <code>PAYMONGO_LIVE_*</code>,
                    then switch with <code>PAYMONGO_MODE=test|live</code>.
                </p>
            </header>

            <div className="rounded-xl border border-slate-200 bg-slate-50/60 p-4">
                <div className="grid gap-4 md:grid-cols-[1fr_auto] md:items-start">
                    <div className="space-y-2 text-sm">
                        <p className="font-medium text-slate-900">Credential Status</p>
                        <p className="text-slate-600">
                            Test keys: {paymongo?.hasTestCredentials ? 'Ready' : 'Missing'}
                        </p>
                        <p className="text-slate-600">
                            Live keys: {paymongo?.hasLiveCredentials ? 'Ready' : 'Missing'}
                        </p>
                        <p className="text-slate-600">
                            Test webhook secret:{' '}
                            {paymongo?.hasTestWebhookSecret ? 'Ready' : 'Missing'}
                        </p>
                        <p className="text-slate-600">
                            Live webhook secret:{' '}
                            {paymongo?.hasLiveWebhookSecret ? 'Ready' : 'Missing'}
                        </p>
                        <p className="text-slate-600">
                            Webhook URL: <span className="font-mono">{paymongo?.webhookUrl}</span>
                        </p>
                        <p className="text-slate-600">
                            Recommended event: <code>checkout_session.payment.paid</code>
                        </p>
                        <p className="text-slate-600">
                            Signature tolerance: {paymongo?.webhookToleranceSeconds ?? 300}s
                        </p>
                    </div>
                    <button
                        type="button"
                        onClick={testConnection}
                        disabled={connectionForm.processing}
                        className="rounded-md border border-slate-300 bg-white px-4 py-2 text-sm font-medium text-slate-700 hover:bg-slate-100 disabled:opacity-50"
                    >
                        {connectionForm.processing
                            ? 'Testing...'
                            : 'Test PayMongo Connection'}
                    </button>
                </div>
            </div>

            <form
                onSubmit={submitCheckout}
                className="space-y-4 rounded-xl border border-slate-200 bg-white p-4"
            >
                <div className="grid gap-4 md:grid-cols-2">
                    <div>
                        <InputLabel htmlFor="plan_id" value="Plan" />
                        <select
                            id="plan_id"
                            value={checkoutForm.data.plan_id}
                            onChange={(event) => selectPlan(event.target.value)}
                            className="mt-1 block w-full rounded-md border-slate-300"
                            required
                        >
                            <option value="">Select plan</option>
                            {plans.map((plan) => (
                                <option key={plan.id} value={plan.id}>
                                    {plan.name} (PHP {Number(plan.price).toFixed(2)})
                                </option>
                            ))}
                        </select>
                        <InputError
                            message={checkoutForm.errors.plan_id}
                            className="mt-2"
                        />
                    </div>

                    <div>
                        <InputLabel htmlFor="amount" value="Amount (PHP)" />
                        <TextInput
                            id="amount"
                            type="number"
                            min="0.01"
                            step="0.01"
                            value={checkoutForm.data.amount}
                            readOnly
                            className="mt-1 block w-full"
                            placeholder="Select a plan"
                        />
                    </div>
                </div>

                <div>
                    <InputLabel
                        htmlFor="user_id"
                        value="User (required)"
                    />
                    <select
                        id="user_id"
                        value={checkoutForm.data.user_id}
                        onChange={(event) =>
                            checkoutForm.setData('user_id', event.target.value)
                        }
                        className="mt-1 block w-full rounded-md border-slate-300"
                        required
                    >
                        <option value="">Select user</option>
                        {(users || []).map((user) => (
                            <option key={user.id} value={user.id}>
                                {user.name} ({user.email})
                            </option>
                        ))}
                    </select>
                    <InputError
                        message={checkoutForm.errors.user_id}
                        className="mt-2"
                    />
                </div>

                <div>
                    <InputLabel htmlFor="description" value="Description" />
                    <TextInput
                        id="description"
                        value={checkoutForm.data.description}
                        onChange={(event) =>
                            checkoutForm.setData('description', event.target.value)
                        }
                        className="mt-1 block w-full"
                        required
                    />
                    <InputError
                        message={checkoutForm.errors.description}
                        className="mt-2"
                    />
                </div>

                <div>
                    <InputLabel htmlFor="reference" value="Reference (optional)" />
                    <TextInput
                        id="reference"
                        value={checkoutForm.data.reference}
                        onChange={(event) =>
                            checkoutForm.setData('reference', event.target.value)
                        }
                        className="mt-1 block w-full"
                        placeholder="INV-1001"
                    />
                    <InputError
                        message={checkoutForm.errors.reference}
                        className="mt-2"
                    />
                </div>

                <div>
                    <InputLabel value="Payment Methods" />
                    <div className="mt-2 flex flex-wrap gap-3">
                        {supportedMethods.map((method) => (
                            <label
                                key={method}
                                className="inline-flex items-center gap-2 rounded-md border border-slate-300 bg-white px-3 py-2 text-sm text-slate-700"
                            >
                                <input
                                    type="checkbox"
                                    checked={checkoutForm.data.payment_method_types.includes(
                                        method,
                                    )}
                                    onChange={() => togglePaymentMethod(method)}
                                    className="rounded border-slate-300 text-slate-900 focus:ring-slate-500"
                                />
                                {paymentMethodLabels[method] ?? method}
                            </label>
                        ))}
                    </div>
                    <InputError
                        message={
                            checkoutForm.errors.payment_method_types ||
                            checkoutForm.errors['payment_method_types.0']
                        }
                        className="mt-2"
                    />
                </div>

                <div className="flex items-center gap-3">
                    <button
                        type="submit"
                        disabled={checkoutForm.processing || !paymongo?.hasActiveSecretKey}
                        className="rounded-md bg-slate-900 px-4 py-2 text-sm font-semibold text-white hover:bg-slate-800 disabled:opacity-50"
                    >
                        {checkoutForm.processing
                            ? 'Creating checkout...'
                            : 'Create Hosted Checkout'}
                    </button>
                    {!paymongo?.hasActiveSecretKey && (
                        <p className="text-xs text-amber-700">
                            Add active mode secret key in <code>.env</code> first.
                        </p>
                    )}
                </div>
                <p className="text-xs text-slate-500">
                    Select a plan and user so the webhook can activate the subscription automatically.
                </p>
            </form>

        </section>
    );
}
