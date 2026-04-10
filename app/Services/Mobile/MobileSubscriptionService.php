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

        $methodMap = $this->resolvePaymentMethodMap($user->id, $subscriptions->getCollection());

        return response()->json([
            'data' => $subscriptions->getCollection()->map(
                fn (UserSubscription $subscription) => $this->toHistoryRow($subscription, $methodMap),
            )->values(),
            'pagination' => [
                'current_page' => $subscriptions->currentPage(),
                'last_page' => $subscriptions->lastPage(),
                'next_page_url' => $subscriptions->nextPageUrl(),
                'has_more' => $subscriptions->hasMorePages(),
            ],
        ]);
    }

    /**
     * @param  array<string, string|null>  $methodMap
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
     *   payment_method: string|null
     * }
     */
    private function toHistoryRow(UserSubscription $subscription, array $methodMap): array
    {
        $startDate = $subscription->start_date?->toDateString();
        $key = $subscription->plan_id.'|'.$startDate;

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
            'payment_method' => $methodMap[$key] ?? null,
        ];
    }

    /**
     * @param  \Illuminate\Support\Collection<int, UserSubscription>  $collection
     * @return array<string, string|null>
     */
    private function resolvePaymentMethodMap(int $userId, $collection): array
    {
        $planIds = $collection
            ->pluck('plan_id')
            ->filter()
            ->unique()
            ->values()
            ->all();

        if ($planIds === []) {
            return [];
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
            return [];
        }

        $transactions = PaymentTransaction::query()
            ->where('provider', 'paymongo')
            ->where('user_id', $userId)
            ->whereIn('plan_id', $planIds)
            ->where('status', 'processed')
            ->whereNotNull('processed_at')
            ->whereBetween('processed_at', [
                $minStart->copy()->startOfDay(),
                $maxStart->copy()->endOfDay(),
            ])
            ->orderByDesc('processed_at')
            ->get(['plan_id', 'processed_at', 'raw_payload']);

        $methodMap = [];
        foreach ($transactions as $transaction) {
            $dateKey = $transaction->processed_at?->toDateString();
            if (! $dateKey) {
                continue;
            }

            $planId = $transaction->plan_id;
            if (! $planId) {
                continue;
            }

            $method = $this->extractMethodLabel($transaction->raw_payload);
            if ($method === null) {
                continue;
            }

            $key = $planId.'|'.$dateKey;
            $methodMap[$key] ??= $method;
        }

        return $methodMap;
    }

    /**
     * @param  array<string, mixed>|null  $payload
     */
    private function extractMethodLabel(?array $payload): ?string
    {
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
