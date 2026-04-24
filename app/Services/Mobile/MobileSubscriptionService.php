<?php

namespace App\Services\Mobile;

use App\Models\PaymentTransaction;
use App\Models\User;
use App\Models\UserSubscription;
use App\Repositories\Contracts\UserSubscriptionRepositoryInterface;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

class MobileSubscriptionService
{
    public function __construct(
        private readonly UserSubscriptionRepositoryInterface $subscriptions,
    ) {
    }

    public function history(Request $request): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();

        $subscriptions = $this->subscriptions->query()
            ->with('plan:id,name,sub_plan_label,group_label,price,billing_cycle,duration_days')
            ->where('user_id', $user->id)
            ->whereHas('plan', function ($query) {
                $query->where('price', '>', 0)
                    ->whereRaw("LOWER(name) NOT LIKE '%free%'");
            })
            ->orderByDesc('start_date')
            ->orderByDesc('id')
            ->paginate(10);

        $paymentMeta = $this->resolvePaymentMetaMap($user->id, $subscriptions->getCollection());
        $paymentMetaBuckets = $paymentMeta['entries_by_key'] ?? [];

        return response()->json([
            'data' => $subscriptions->getCollection()->map(function (UserSubscription $subscription) use (&$paymentMetaBuckets) {
                return $this->toHistoryRow(
                    $subscription,
                    $paymentMetaBuckets,
                );
            })->values(),
            'pagination' => [
                'current_page' => $subscriptions->currentPage(),
                'last_page' => $subscriptions->lastPage(),
                'next_page_url' => $subscriptions->nextPageUrl(),
                'has_more' => $subscriptions->hasMorePages(),
            ],
        ]);
    }

    /**
     * @param  array<string, array<int, array{payment_method: string|null, provider_payment_id: string|null}>>  $paymentMetaBuckets
     * @return array{
     *   id: int,
     *   plan_id: int|null,
     *   plan_name: string|null,
     *   plan_group_label: string|null,
     *   sub_plan_label: string|null,
     *   price: float,
     *   billing_cycle: string|null,
     *   duration_days: int,
     *   start_date: string|null,
     *   end_date: string|null,
     *   status: string|null,
     *   payment_method: string|null,
     *   provider_payment_id: string|null
     * }
     */
    private function toHistoryRow(UserSubscription $subscription, array &$paymentMetaBuckets): array
    {
        $startDate = $subscription->start_date?->toDateString();
        $key = $subscription->plan_id.'|'.$startDate;
        $paymentMeta = $this->takeNextPaymentMeta($paymentMetaBuckets, $key);

        return [
            'id' => $subscription->id,
            'plan_id' => $subscription->plan_id,
            'plan_name' => $subscription->plan?->name,
            'plan_group_label' => $subscription->plan?->group_label,
            'sub_plan_label' => $subscription->plan?->sub_plan_label,
            'price' => (float) ($subscription->plan?->price ?? 0),
            'billing_cycle' => $subscription->billing_cycle,
            'duration_days' => (int) ($subscription->plan?->duration_days ?? 0),
            'start_date' => $startDate,
            'end_date' => $subscription->end_date?->toDateString(),
            'status' => $subscription->status,
            'payment_method' => $paymentMeta['payment_method'] ?? null,
            'provider_payment_id' => $paymentMeta['provider_payment_id'] ?? null,
        ];
    }

    /**
     * @param  \Illuminate\Support\Collection<int, UserSubscription>  $collection
     * @return array{
     *   entries_by_key: array<string, array<int, array{payment_method: string|null, provider_payment_id: string|null}>>
     * }
     */
    private function resolvePaymentMetaMap(int $userId, $collection): array
    {
        $planIds = $collection
            ->pluck('plan_id')
            ->filter()
            ->unique()
            ->values()
            ->all();

        if ($planIds === []) {
            return [
                'entries_by_key' => [],
            ];
        }

        /** @var Carbon|null $minStart */
        $minStart = null;
        /** @var Carbon|null $maxStart */
        $maxStart = null;

        foreach ($collection as $subscription) {
            $start = $subscription->start_date;
            if (! $start instanceof Carbon) {
                continue;
            }

            $minStart = $minStart === null || $start->lt($minStart) ? $start : $minStart;
            $maxStart = $maxStart === null || $start->gt($maxStart) ? $start : $maxStart;
        }

        if ($minStart === null || $maxStart === null) {
            return [
                'entries_by_key' => [],
            ];
        }

        $transactions = PaymentTransaction::query()
            ->whereIn('provider', ['paymongo', 'in_app_purchase'])
            ->where('user_id', $userId)
            ->whereIn('plan_id', $planIds)
            ->where('status', 'processed')
            ->whereNotNull('processed_at')
            ->whereBetween('processed_at', [
                $minStart->copy()->startOfDay(),
                $maxStart->copy()->endOfDay(),
            ])
            ->orderByDesc('processed_at')
            ->get(['provider', 'plan_id', 'processed_at', 'raw_payload', 'provider_payment_id']);

        $entriesByKey = [];
        foreach ($transactions as $transaction) {
            $dateKey = $transaction->processed_at?->toDateString();
            if (! $dateKey) {
                continue;
            }

            $planId = $transaction->plan_id;
            if (! $planId) {
                continue;
            }

            $method = $this->extractMethodLabel($transaction->provider, $transaction->raw_payload);
            $key = $planId.'|'.$dateKey;
            $providerPaymentId = is_string($transaction->provider_payment_id) && $transaction->provider_payment_id !== ''
                ? $transaction->provider_payment_id
                : null;

            if ($method === null && $providerPaymentId === null) {
                continue;
            }

            $entriesByKey[$key] ??= [];
            $entriesByKey[$key][] = [
                'payment_method' => $method,
                'provider_payment_id' => $providerPaymentId,
            ];
        }

        return [
            'entries_by_key' => $entriesByKey,
        ];
    }

    /**
     * @param  array<string, array<int, array{payment_method: string|null, provider_payment_id: string|null}>>  $paymentMetaBuckets
     * @return array{payment_method: string|null, provider_payment_id: string|null}
     */
    private function takeNextPaymentMeta(array &$paymentMetaBuckets, string $key): array
    {
        if (! isset($paymentMetaBuckets[$key]) || ! is_array($paymentMetaBuckets[$key])) {
            return [
                'payment_method' => null,
                'provider_payment_id' => null,
            ];
        }

        $entry = array_shift($paymentMetaBuckets[$key]);
        if (! is_array($entry)) {
            return [
                'payment_method' => null,
                'provider_payment_id' => null,
            ];
        }

        return [
            'payment_method' => $entry['payment_method'] ?? null,
            'provider_payment_id' => $entry['provider_payment_id'] ?? null,
        ];
    }

    /**
     * @param  array<string, mixed>|null  $payload
     */
    private function extractMethodLabel(?string $provider, ?array $payload): ?string
    {
        if ($provider === 'in_app_purchase') {
            return $this->extractInAppMethodLabel($payload);
        }

        if (! is_array($payload) || $payload === []) {
            return null;
        }

        $found = $this->findFirstSupportedMethod($payload);
        if (! is_string($found) || $found === '') {
            return null;
        }

        return match (strtolower($found)) {
            'gcash' => 'GCash',
            'card' => 'Card',
            'grab_pay' => 'GrabPay',
            'paymaya' => 'PayMaya',
            'qrph' => 'QRPh',
            default => strtoupper($found),
        };
    }

    /**
     * @param  array<string, mixed>|null  $payload
     */
    private function extractInAppMethodLabel(?array $payload): string
    {
        $platform = is_array($payload)
            ? strtolower(trim((string) ($payload['platform'] ?? '')))
            : '';

        return match ($platform) {
            'android' => 'Google Play',
            'ios' => 'Apple App Store',
            default => 'In-App Purchase',
        };
    }

    /**
     * Best-effort scan of PayMongo payload for known payment method identifiers.
     *
     * @param  mixed  $value
     */
    private function findFirstSupportedMethod($value): ?string
    {
        $supported = ['card', 'gcash', 'grab_pay', 'paymaya', 'qrph'];

        if (is_string($value)) {
            $normalized = strtolower(trim($value));
            return in_array($normalized, $supported, true) ? $normalized : null;
        }

        if (is_array($value)) {
            foreach ($value as $item) {
                $found = $this->findFirstSupportedMethod($item);
                if ($found !== null) {
                    return $found;
                }
            }
        }

        return null;
    }
}
