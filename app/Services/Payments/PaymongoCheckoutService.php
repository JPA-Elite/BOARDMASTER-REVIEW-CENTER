<?php

namespace App\Services\Payments;

use App\Models\Plan;
use App\Repositories\Contracts\PlanRepositoryInterface;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Throwable;

class PaymongoCheckoutService
{
    public function __construct(
        private readonly PlanRepositoryInterface $plans,
        private readonly PaymongoGatewayService $paymongo,
    ) {
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'plan_id' => ['required', 'integer', 'exists:plans,id'],
            'billing_cycle' => ['nullable', 'string', Rule::in(Plan::billingCycles())],
            'reference' => ['nullable', 'string', 'max:100'],
            'payment_method_types' => ['nullable', 'array', 'min:1'],
            'payment_method_types.*' => [
                'required',
                'string',
                Rule::in($this->paymongo->supportedPaymentMethods()),
            ],
        ]);

        /** @var Plan|null $plan */
        $plan = $this->plans->find((int) $validated['plan_id']);
        if (! $plan) {
            return response()->json([
                'message' => 'Selected plan was not found.',
            ], 404);
        }
        if ((float) $plan->price <= 0) {
            return response()->json([
                'message' => 'Selected plan is free. Use the plan selection endpoint instead.',
            ], 422);
        }

        $amountInCentavos = (int) round(((float) $plan->price) * 100);
        $user = $request->user();
        $userId = $user?->id;

        if (! $userId) {
            return response()->json([
                'message' => 'Unauthorized.',
            ], 401);
        }

        $billingCycle = $validated['billing_cycle'] ?? $plan->billing_cycle;

        try {
            $checkout = $this->paymongo->createCheckoutSession(
                amountInCentavos: $amountInCentavos,
                itemName: $plan->name,
                description: $plan->name.' Subscription',
                paymentMethodTypes: $validated['payment_method_types'] ?? $this->paymongo->defaultPaymentMethods(),
                metadata: [
                    'user_id' => $userId,
                    'plan_id' => $plan->id,
                    'plan_name' => $plan->name,
                    'billing_cycle' => $billingCycle,
                    'reference' => $validated['reference'] ?? null,
                ],
            );
        } catch (Throwable $exception) {
            report($exception);

            return response()->json([
                'message' => 'Unable to create PayMongo checkout.',
                'error' => $exception->getMessage(),
            ], 422);
        }

        return response()->json([
            'checkout_id' => $checkout['id'],
            'checkout_url' => $checkout['checkout_url'],
            'status' => $checkout['status'],
            'billing_cycle' => $billingCycle,
        ]);
    }
}
