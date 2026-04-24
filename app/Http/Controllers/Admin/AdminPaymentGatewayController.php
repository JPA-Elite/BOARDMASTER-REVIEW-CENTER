<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Plan;
use App\Models\PaymentTransaction;
use App\Models\User;
use App\Services\Payments\PaymongoGatewayService;
use Illuminate\Http\Client\RequestException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;
use Throwable;

class AdminPaymentGatewayController extends Controller
{
    public function index(Request $request, PaymongoGatewayService $paymongo): Response
    {
        return Inertia::render('Admin/PaymentGateway', [
            'plans' => Plan::query()
                ->select('id', 'name', 'price', 'billing_cycle')
                ->orderBy('name')
                ->get()
                ->map(fn (Plan $plan) => [
                    'id' => $plan->id,
                    'name' => $plan->name,
                    'price' => (float) $plan->price,
                    'billing_cycle' => $plan->billing_cycle,
                ]),
            'users' => User::query()
                ->select('id', 'name', 'email')
                ->orderBy('name')
                ->get()
                ->map(fn (User $user) => [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                ]),
            'paymongo' => [
                'mode' => $paymongo->mode(),
                'baseUrl' => $paymongo->baseUrl(),
                'hasActiveSecretKey' => $paymongo->hasActiveSecretKey(),
                'hasTestCredentials' => $paymongo->hasTestCredentials(),
                'hasLiveCredentials' => $paymongo->hasLiveCredentials(),
                'hasActiveWebhookSecret' => $paymongo->hasActiveWebhookSecret(),
                'hasTestWebhookSecret' => $paymongo->hasTestWebhookSecret(),
                'hasLiveWebhookSecret' => $paymongo->hasLiveWebhookSecret(),
                'webhookToleranceSeconds' => $paymongo->webhookToleranceInSeconds(),
                'webhookUrl' => route('api.webhooks.paymongo'),
                'supportedPaymentMethods' => $paymongo->supportedPaymentMethods(),
                'defaultPaymentMethods' => $paymongo->defaultPaymentMethods(),
            ],
            'paymongoWebhookLogs' => PaymentTransaction::query()
                ->where('provider', 'paymongo')
                ->latest()
                ->paginate(10)
                ->withQueryString()
                ->through(fn (PaymentTransaction $transaction) => [
                    'id' => $transaction->id,
                    'event_id' => $transaction->provider_event_id,
                    'event_type' => $transaction->event_type,
                    'status' => $transaction->status,
                    'mode' => $transaction->mode,
                    'amount' => $transaction->amount,
                    'currency' => $transaction->currency,
                    'user_id' => $transaction->user_id,
                    'plan_id' => $transaction->plan_id,
                    'reference' => $transaction->reference,
                    'notes' => $transaction->notes,
                    'created_at' => $transaction->created_at?->format('M d, Y h:i A'),
                    'processed_at' => $transaction->processed_at?->format('M d, Y h:i A'),
                    'payload' => $transaction->raw_payload,
                ]),
            'paymongoRedirectUrl' => session('paymongo_redirect_url'),
        ]);
    }

    public function testConnection(PaymongoGatewayService $paymongo): RedirectResponse
    {
        try {
            $paymongo->testConnection();
        } catch (Throwable $exception) {
            report($exception);

            return back()->with(
                'error',
                'PayMongo connection test failed: '.$this->formatPaymongoError($exception),
            );
        }

        return back()->with(
            'success',
            sprintf(
                'PayMongo connection successful using %s mode.',
                strtoupper($paymongo->mode()),
            ),
        );
    }

    public function createCheckout(
        Request $request,
        PaymongoGatewayService $paymongo,
    ): RedirectResponse {
        $validated = $request->validate([
            'plan_id' => ['required', 'integer', 'exists:plans,id'],
            'user_id' => ['nullable', 'integer', 'exists:users,id'],
            'description' => ['required', 'string', 'max:255'],
            'reference' => ['nullable', 'string', 'max:100'],
            'payment_method_types' => ['required', 'array', 'min:1'],
            'payment_method_types.*' => [
                'required',
                'string',
                Rule::in($paymongo->supportedPaymentMethods()),
            ],
        ]);

        if (empty($validated['user_id'])) {
            return back()
                ->withErrors(['user_id' => 'User ID is required when a plan is selected.'])
                ->withInput();
        }

        $plan = Plan::query()->findOrFail($validated['plan_id']);
        $amountInCentavos = (int) round(((float) $plan->price) * 100);

        try {
            $checkout = $paymongo->createCheckoutSession(
                amountInCentavos: $amountInCentavos,
                itemName: $plan?->name ?? 'Board Master Checkout',
                description: $validated['description'],
                paymentMethodTypes: $validated['payment_method_types'],
                metadata: [
                    'user_id' => $validated['user_id'] ?? null,
                    'plan_id' => $plan?->id,
                    'plan_name' => $plan?->name,
                    'billing_cycle' => $plan?->billing_cycle,
                    'reference' => $validated['reference'] ?? null,
                    'created_by' => (string) $request->user()->id,
                ],
            );
        } catch (Throwable $exception) {
            report($exception);

            return back()->with(
                'error',
                'Unable to create PayMongo checkout: '.$this->formatPaymongoError($exception),
            );
        }

        return redirect()
            ->route('admin.payment-gateway')
            ->with('success', 'Redirecting to PayMongo checkout...')
            ->with('paymongo_redirect_url', $checkout['checkout_url']);
    }

    public function deleteWebhook(PaymentTransaction $transaction): RedirectResponse
    {
        if ($transaction->provider !== 'paymongo') {
            abort(404);
        }

        $transaction->delete();

        return back()->with('success', 'Webhook log deleted.');
    }

    public function clearWebhooks(): RedirectResponse
    {
        PaymentTransaction::query()
            ->where('provider', 'paymongo')
            ->delete();

        return back()->with('success', 'All PayMongo webhook logs deleted.');
    }

    private function formatPaymongoError(Throwable $exception): string
    {
        if (! $exception instanceof RequestException || ! $exception->response) {
            return $exception->getMessage();
        }

        $payload = $exception->response->json();
        $detail = $payload['errors'][0]['detail'] ?? null;

        if (is_string($detail) && $detail !== '') {
            return $detail;
        }

        return sprintf(
            'HTTP %s: %s',
            $exception->response->status(),
            $exception->response->body(),
        );
    }
}
