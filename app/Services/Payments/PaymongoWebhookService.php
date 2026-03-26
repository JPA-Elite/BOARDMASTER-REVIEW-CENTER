<?php

namespace App\Services\Payments;

use App\Models\PaymentTransaction;
use App\Models\Plan;
use App\Models\User;
use App\Models\UserSubscription;
use App\Repositories\Contracts\PaymentTransactionRepositoryInterface;
use App\Repositories\Contracts\PlanRepositoryInterface;
use App\Repositories\Contracts\UserRepositoryInterface;
use App\Repositories\Contracts\UserSubscriptionRepositoryInterface;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Throwable;

class PaymongoWebhookService
{
    public function __construct(
        private readonly PaymentTransactionRepositoryInterface $transactions,
        private readonly UserRepositoryInterface $users,
        private readonly PlanRepositoryInterface $plans,
        private readonly UserSubscriptionRepositoryInterface $subscriptions,
        private readonly PaymongoGatewayService $paymongo,
    ) {
    }

    public function handle(Request $request): JsonResponse
    {
        $rawPayload = (string) $request->getContent();

        if ($rawPayload === '') {
            return response()->json(['message' => 'Webhook payload is empty.'], 400);
        }

        $payload = json_decode($rawPayload, true);

        if (! is_array($payload)) {
            return response()->json(['message' => 'Webhook payload is invalid JSON.'], 400);
        }

        $liveMode = data_get($payload, 'data.attributes.livemode');
        $resolvedLiveMode = is_bool($liveMode) ? $liveMode : null;
        $signatureHeader = (string) $request->header('Paymongo-Signature', '');

        if (! $this->paymongo->verifyWebhookSignature($signatureHeader, $rawPayload, $resolvedLiveMode)) {
            return response()->json(['message' => 'Invalid webhook signature.'], 400);
        }

        $eventId = (string) data_get($payload, 'data.id', '');
        $eventType = (string) data_get($payload, 'data.attributes.type', '');

        if ($eventId === '' || $eventType === '') {
            return response()->json(['message' => 'Webhook event id or type is missing.'], 400);
        }

        $details = $this->extractDetails($eventType, $payload);
        $mode = $resolvedLiveMode === true ? 'live' : 'test';

        /** @var PaymentTransaction $transaction */
        $transaction = $this->transactions->query()->firstOrNew([
            'provider' => 'paymongo',
            'provider_event_id' => $eventId,
        ]);

        if ($transaction->exists && $transaction->processed_at !== null) {
            return response()->json(['status' => 'ok', 'message' => 'Already processed.']);
        }

        $transaction->fill([
            'provider_checkout_session_id' => $details['checkout_session_id'],
            'provider_payment_id' => $details['payment_id'],
            'mode' => $mode,
            'event_type' => $eventType,
            'status' => 'received',
            'amount' => $details['amount'],
            'currency' => $details['currency'],
            'reference' => $details['reference'],
            'user_id' => $details['user_id'],
            'plan_id' => $details['plan_id'],
            'raw_payload' => $payload,
        ]);
        $transaction->save();

        if (! in_array($eventType, ['checkout_session.payment.paid', 'payment.paid'], true)) {
            $transaction->status = 'ignored';
            $transaction->notes = 'Event type is not used for subscription activation.';
            $transaction->processed_at = now();
            $transaction->save();

            return response()->json(['status' => 'ignored']);
        }

        try {
            $activationMessage = $this->activateSubscription(
                $details['user_id'],
                $details['plan_id'],
                $details['billing_cycle'],
            );
        } catch (Throwable $exception) {
            report($exception);

            $transaction->status = 'failed';
            $transaction->notes = $exception->getMessage();
            $transaction->save();

            return response()->json(['message' => 'Failed to process webhook.'], 500);
        }

        $transaction->status = $this->resolveProcessingStatus($activationMessage);
        $transaction->notes = $activationMessage;
        $transaction->processed_at = now();
        $transaction->save();

        return response()->json(['status' => 'ok']);
    }

    /**
     * @param  array<string, mixed>  $payload
     * @return array{
     *   amount: float|null,
     *   currency: string|null,
     *   checkout_session_id: string|null,
     *   payment_id: string|null,
     *   reference: string|null,
     *   user_id: int|null,
     *   plan_id: int|null,
     *   billing_cycle: string|null
     * }
     */
    private function extractDetails(string $eventType, array $payload): array
    {
        $resource = data_get($payload, 'data.attributes.data');
        $resourceAttributes = is_array($resource) ? ($resource['attributes'] ?? []) : [];
        $resourceAttributes = is_array($resourceAttributes) ? $resourceAttributes : [];
        $lineItems = $resourceAttributes['line_items'] ?? [];
        $lineItems = is_array($lineItems) ? $lineItems : [];
        $firstLineItem = is_array($lineItems[0] ?? null) ? $lineItems[0] : [];
        $firstLineItemMetadata = $firstLineItem['metadata'] ?? [];
        $resourceMetadata = $resourceAttributes['metadata'] ?? [];

        $metadata = array_merge(
            is_array($resourceMetadata) ? $resourceMetadata : [],
            is_array($firstLineItemMetadata) ? $firstLineItemMetadata : [],
        );

        $amount = $this->extractAmount($resourceAttributes, $lineItems);
        $currency = $this->extractCurrency($resourceAttributes, $lineItems);

        $checkoutSessionId = null;
        $paymentId = null;

        if ($eventType === 'checkout_session.payment.paid') {
            $checkoutSessionId = is_string($resource['id'] ?? null) ? $resource['id'] : null;
            $paymentId = data_get($resource, 'relationships.payments.data.0.id');
        }

        if ($eventType === 'payment.paid') {
            $paymentId = is_string($resource['id'] ?? null) ? $resource['id'] : null;
        }

        $paymentId = is_string($paymentId) ? $paymentId : null;
        $reference = $this->extractString($metadata, ['reference', 'order_id', 'invoice_id']);

        return [
            'amount' => $amount,
            'currency' => $currency,
            'checkout_session_id' => $checkoutSessionId,
            'payment_id' => $paymentId,
            'reference' => $reference,
            'user_id' => $this->extractInt($metadata, ['user_id', 'customer_id']),
            'plan_id' => $this->extractInt($metadata, ['plan_id']),
            'billing_cycle' => $this->extractString($metadata, ['billing_cycle']),
        ];
    }

    /**
     * @param  array<string, mixed>  $attributes
     * @param  array<int, mixed>  $lineItems
     */
    private function extractAmount(array $attributes, array $lineItems): ?float
    {
        if (isset($attributes['amount']) && is_numeric($attributes['amount'])) {
            return round(((float) $attributes['amount']) / 100, 2);
        }

        $lineItemCentavos = 0;

        foreach ($lineItems as $item) {
            if (! is_array($item) || ! is_numeric($item['amount'] ?? null)) {
                continue;
            }

            $quantity = is_numeric($item['quantity'] ?? null) ? (int) $item['quantity'] : 1;
            $lineItemCentavos += ((int) $item['amount']) * max($quantity, 1);
        }

        if ($lineItemCentavos <= 0) {
            return null;
        }

        return round($lineItemCentavos / 100, 2);
    }

    /**
     * @param  array<string, mixed>  $attributes
     * @param  array<int, mixed>  $lineItems
     */
    private function extractCurrency(array $attributes, array $lineItems): ?string
    {
        if (is_string($attributes['currency'] ?? null) && $attributes['currency'] !== '') {
            return strtoupper($attributes['currency']);
        }

        foreach ($lineItems as $item) {
            if (is_array($item) && is_string($item['currency'] ?? null) && $item['currency'] !== '') {
                return strtoupper($item['currency']);
            }
        }

        return null;
    }

    /**
     * @param  array<string, mixed>  $metadata
     * @param  array<int, string>  $keys
     */
    private function extractInt(array $metadata, array $keys): ?int
    {
        foreach ($keys as $key) {
            if (! isset($metadata[$key]) || ! is_numeric($metadata[$key])) {
                continue;
            }

            return (int) $metadata[$key];
        }

        return null;
    }

    /**
     * @param  array<string, mixed>  $metadata
     * @param  array<int, string>  $keys
     */
    private function extractString(array $metadata, array $keys): ?string
    {
        foreach ($keys as $key) {
            if (! isset($metadata[$key]) || ! is_string($metadata[$key])) {
                continue;
            }

            $value = trim($metadata[$key]);

            if ($value !== '') {
                return $value;
            }
        }

        return null;
    }

    private function activateSubscription(?int $userId, ?int $planId, ?string $billingCycle): string
    {
        if (! $userId || ! $planId) {
            return 'Missing user_id or plan_id metadata.';
        }

        /** @var User|null $user */
        $user = $this->users->find($userId);
        /** @var Plan|null $plan */
        $plan = $this->plans->find($planId);

        if (! $user || ! $plan) {
            return 'User or plan from metadata does not exist.';
        }

        $alreadyActive = $this->subscriptions->query()
            ->where('user_id', $userId)
            ->where('plan_id', $planId)
            ->where('status', 'active')
            ->exists();

        if ($alreadyActive) {
            return 'Subscription already active.';
        }

        $resolvedBillingCycle = $this->resolveBillingCycle($plan, $billingCycle);

        DB::transaction(function () use ($userId, $planId, $plan, $resolvedBillingCycle): void {
            $today = Carbon::today()->toDateString();
            $endDate = $this->calculateEndDate(Carbon::today(), $plan->durationDays());

            $this->subscriptions->query()
                ->where('user_id', $userId)
                ->where('status', 'active')
                ->update([
                    'status' => 'expired',
                    'end_date' => $today,
                ]);

            $this->subscriptions->create([
                'user_id' => $userId,
                'plan_id' => $planId,
                'billing_cycle' => $resolvedBillingCycle,
                'start_date' => $today,
                'end_date' => $endDate,
                'status' => 'active',
            ]);
        });

        return 'activated';
    }

    private function resolveProcessingStatus(string $activationMessage): string
    {
        if ($activationMessage === 'activated') {
            return 'processed';
        }

        if ($activationMessage === 'Subscription already active.') {
            return 'processed';
        }

        if (
            str_contains($activationMessage, 'Missing user_id') ||
            str_contains($activationMessage, 'does not exist')
        ) {
            return 'paid_unlinked';
        }

        return 'paid_unlinked';
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

    private function calculateEndDate(Carbon $startDate, string $billingCycle): string
    {
        return $startDate->copy()->addDays(max(1, Plan::durationDaysForCycle($billingCycle)))->toDateString();
    }
}
