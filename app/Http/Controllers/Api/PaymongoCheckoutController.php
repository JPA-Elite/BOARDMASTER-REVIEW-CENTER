<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Plan;
use App\Services\Payments\PaymongoGatewayService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Throwable;

class PaymongoCheckoutController extends Controller
{
    public function store(Request $request, PaymongoGatewayService $paymongo): JsonResponse
    {
        $validated = $request->validate([
            'plan_id' => ['required', 'integer', 'exists:plans,id'],
            'reference' => ['nullable', 'string', 'max:100'],
            'payment_method_types' => ['nullable', 'array', 'min:1'],
            'payment_method_types.*' => [
                'required',
                'string',
                Rule::in($paymongo->supportedPaymentMethods()),
            ],
        ]);

        $plan = Plan::query()->findOrFail($validated['plan_id']);
        $amountInCentavos = (int) round(((float) $plan->price) * 100);
        $userId = $request->user()?->id;

        try {
            $checkout = $paymongo->createCheckoutSession(
                amountInCentavos: $amountInCentavos,
                itemName: $plan->name,
                description: $plan->name.' Subscription',
                paymentMethodTypes: $validated['payment_method_types'] ?? $paymongo->defaultPaymentMethods(),
                metadata: [
                    'user_id' => $userId,
                    'plan_id' => $plan->id,
                    'plan_name' => $plan->name,
                    'billing_cycle' => $plan->billing_cycle,
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
        ]);
    }
}
