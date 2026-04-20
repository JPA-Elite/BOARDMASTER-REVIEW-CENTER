<?php

namespace App\Services\Payments;

use App\Models\PaymentTransaction;
use App\Models\Plan;
use App\Models\User;
use App\Models\UserSubscription;
use App\Repositories\Contracts\PlanRepositoryInterface;
use App\Repositories\Contracts\UserSubscriptionRepositoryInterface;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class MobileInAppPurchaseService
{
    public function __construct(
        private readonly PlanRepositoryInterface $plans,
        private readonly UserSubscriptionRepositoryInterface $subscriptions,
        private readonly MobilePaymentConfigService $payments,
    ) {
    }

    public function complete(Request $request): JsonResponse
    {
        if (! $this->payments->isInAppPurchaseEnabled()) {
            return response()->json([
                'message' => 'In-app purchase is disabled for this environment.',
            ], 422);
        }

        $validated = $request->validate([
            'plan_id' => ['required', 'integer', 'exists:plans,id'],
            'billing_cycle' => ['nullable', 'string', Rule::in(Plan::billingCycles())],
            'platform' => ['required', 'string', Rule::in(['android', 'ios'])],
            'product_id' => ['required', 'string', 'max:191'],
            'purchase_id' => ['nullable', 'string', 'max:191'],
            'verification_data' => ['required', 'string', 'min:5'],
            'verification_source' => ['nullable', 'string', 'max:100'],
            'transaction_date' => ['nullable', 'integer'],
        ]);

        /** @var User|null $user */
        $user = $request->user();
        if (! $user) {
            return response()->json([
                'message' => 'Unauthorized.',
            ], 401);
        }

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

        $platform = strtolower(trim((string) $validated['platform']));
        $expectedProductId = $this->payments->resolveInAppProductId($plan, $platform);
        if (! is_string($expectedProductId) || trim($expectedProductId) === '') {
            return response()->json([
                'message' => 'No in-app product is configured for this plan and platform.',
            ], 422);
        }

        $providedProductId = trim((string) $validated['product_id']);
        if ($providedProductId !== $expectedProductId) {
            return response()->json([
                'message' => 'Submitted in-app product does not match server configuration.',
            ], 422);
        }

        $purchaseId = $this->normalizeNullableString($validated['purchase_id'] ?? null);
        $existing = $purchaseId === null
            ? null
            : PaymentTransaction::query()
                ->where('provider', MobilePaymentConfigService::PROVIDER_IN_APP_PURCHASE)
                ->where('provider_payment_id', $purchaseId)
                ->first();

        if ($existing instanceof PaymentTransaction) {
            if ((int) $existing->user_id !== (int) $user->id) {
                return response()->json([
                    'message' => 'This purchase token is already linked to another account.',
                ], 422);
            }

            if ((int) $existing->plan_id !== (int) $plan->id) {
                return response()->json([
                    'message' => 'This purchase token is already linked to another plan.',
                ], 422);
            }

            if ($existing->status === 'processed') {
                $activeSubscription = $this->subscriptions->query()
                    ->where('user_id', $user->id)
                    ->where('plan_id', $plan->id)
                    ->where('status', 'active')
                    ->orderByDesc('id')
                    ->first();

                if ($activeSubscription instanceof UserSubscription) {
                    return response()->json([
                        'message' => 'Purchase already processed.',
                        'data' => $this->planSelectionPayload(
                            $activeSubscription,
                            $plan,
                            $this->resolveBillingCycle($plan, $validated['billing_cycle'] ?? null),
                        ),
                    ]);
                }
            }
        }

        $billingCycle = $this->resolveBillingCycle($plan, $validated['billing_cycle'] ?? null);
        $today = Carbon::today()->toDateString();
        $endDate = Plan::calculateEndDateForCycle(Carbon::today(), $billingCycle)->toDateString();

        $transactionPayload = [
            'platform' => $platform,
            'product_id' => $providedProductId,
            'purchase_id' => $purchaseId,
            'verification_data' => trim((string) $validated['verification_data']),
            'verification_source' => $this->normalizeNullableString($validated['verification_source'] ?? null),
            'transaction_date' => $validated['transaction_date'] ?? null,
        ];
        $providerEventId = $this->resolveProviderEventId(
            platform: $platform,
            productId: $providedProductId,
            purchaseId: $purchaseId,
            userId: (int) $user->id,
        );

        /** @var UserSubscription $subscription */
        $subscription = DB::transaction(function () use (
            $user,
            $plan,
            $billingCycle,
            $today,
            $endDate,
            $purchaseId,
            $providedProductId,
            $transactionPayload,
            $providerEventId,
        ): UserSubscription {
            $this->subscriptions->query()
                ->where('user_id', $user->id)
                ->where('status', 'active')
                ->update([
                    'status' => 'inactive',
                    'end_date' => $today,
                ]);

            /** @var UserSubscription $created */
            $created = $this->subscriptions->create([
                'user_id' => $user->id,
                'plan_id' => $plan->id,
                'billing_cycle' => $billingCycle,
                'start_date' => $today,
                'end_date' => $endDate,
                'status' => 'active',
            ]);

            $existing = $purchaseId === null
                ? null
                : PaymentTransaction::query()
                    ->where('provider', MobilePaymentConfigService::PROVIDER_IN_APP_PURCHASE)
                    ->where('provider_payment_id', $purchaseId)
                    ->first();

            if ($existing instanceof PaymentTransaction) {
                $existing->fill([
                    'provider_event_id' => $providerEventId,
                    'provider_checkout_session_id' => $providedProductId,
                    'mode' => app()->environment('production') ? 'live' : 'test',
                    'event_type' => $transactionPayload['platform'].'.in_app_purchase.completed',
                    'status' => 'processed',
                    'user_id' => $user->id,
                    'plan_id' => $plan->id,
                    'amount' => (float) $plan->price,
                    'currency' => 'PHP',
                    'reference' => $providedProductId,
                    'notes' => 'In-app purchase validated by mobile flow.',
                    'raw_payload' => $transactionPayload,
                    'processed_at' => now(),
                ]);
                $existing->save();
            } else {
                PaymentTransaction::query()->create([
                    'provider' => MobilePaymentConfigService::PROVIDER_IN_APP_PURCHASE,
                    'provider_event_id' => $providerEventId,
                    'provider_checkout_session_id' => $providedProductId,
                    'provider_payment_id' => $purchaseId,
                    'mode' => app()->environment('production') ? 'live' : 'test',
                    'event_type' => $transactionPayload['platform'].'.in_app_purchase.completed',
                    'status' => 'processed',
                    'user_id' => $user->id,
                    'plan_id' => $plan->id,
                    'amount' => (float) $plan->price,
                    'currency' => 'PHP',
                    'reference' => $providedProductId,
                    'notes' => 'In-app purchase validated by mobile flow.',
                    'raw_payload' => $transactionPayload,
                    'processed_at' => now(),
                ]);
            }

            return $created;
        });

        return response()->json([
            'message' => 'Plan updated successfully.',
            'data' => $this->planSelectionPayload($subscription, $plan, $billingCycle),
        ]);
    }

    private function resolveBillingCycle(Plan $plan, ?string $billingCycle): string
    {
        if (is_string($billingCycle) && in_array($billingCycle, Plan::billingCycles(), true)) {
            return $billingCycle;
        }

        return in_array($plan->billing_cycle, Plan::billingCycles(), true)
            ? $plan->billing_cycle
            : Plan::BILLING_MONTHLY;
    }

    /**
     * @return array{
     *  subscription_id: int,
     *  plan_id: int,
     *  tier: string,
     *  plan_name: string,
     *  plan_group: string,
     *  sub_plan_label: string,
     *  billing_cycle: string,
     *  duration_days: int,
     *  end_date: string|null
     * }
     */
    private function planSelectionPayload(UserSubscription $subscription, Plan $plan, string $billingCycle): array
    {
        return [
            'subscription_id' => (int) $subscription->id,
            'plan_id' => (int) $plan->id,
            'tier' => 'premium',
            'plan_name' => (string) $plan->name,
            'plan_group' => $plan->groupKey(),
            'sub_plan_label' => $plan->subPlanTitle(),
            'billing_cycle' => $billingCycle,
            'duration_days' => $plan->durationDays(),
            'end_date' => $subscription->end_date?->toDateString(),
        ];
    }

    private function normalizeNullableString(?string $value): ?string
    {
        if (! is_string($value)) {
            return null;
        }

        $trimmed = trim($value);

        return $trimmed === '' ? null : $trimmed;
    }

    private function resolveProviderEventId(
        string $platform,
        string $productId,
        ?string $purchaseId,
        int $userId,
    ): string {
        $normalizedPurchaseId = trim((string) $purchaseId);
        if ($normalizedPurchaseId !== '') {
            return 'iap:'.$platform.':'.$normalizedPurchaseId;
        }

        $seed = implode('|', [
            $platform,
            $productId,
            $userId,
            now()->format('YmdHisv'),
            bin2hex(random_bytes(6)),
        ]);

        return 'iap:'.$platform.':'.sha1($seed);
    }
}
