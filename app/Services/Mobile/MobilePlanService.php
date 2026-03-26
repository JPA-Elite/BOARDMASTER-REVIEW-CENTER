<?php

namespace App\Services\Mobile;

use App\Models\Plan;
use App\Models\User;
use App\Models\UserSubscription;
use App\Repositories\Contracts\PlanRepositoryInterface;
use App\Repositories\Contracts\UserSubscriptionRepositoryInterface;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Validation\Rule;

class MobilePlanService
{
    public function __construct(
        private readonly PlanRepositoryInterface $plans,
        private readonly UserSubscriptionRepositoryInterface $subscriptions,
    ) {
    }

    public function index(): JsonResponse
    {
        $plans = $this->plans->query()
            ->with(['features', 'subjectAccesses'])
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get()
            ->map(fn (Plan $plan): array => $this->planPayload($plan))
            ->values();

        $groups = $plans
            ->groupBy('plan_group')
            ->map(function ($items, $groupKey): array {
                $first = $items->first();

                return [
                    'group_key' => $groupKey,
                    'group_label' => $first['group_label'] ?? 'Plans',
                    'plans' => $items
                        ->sortBy('sort_order')
                        ->values()
                        ->all(),
                ];
            })
            ->values();

        return response()->json([
            'data' => $plans,
            'groups' => $groups,
        ]);
    }

    public function select(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'plan_id' => ['nullable', 'integer', 'exists:plans,id'],
            'tier' => ['nullable', 'string', 'in:free,premium,subscription'],
            'billing_cycle' => ['nullable', 'string', Rule::in(Plan::billingCycles())],
        ]);

        $plan = null;
        if (! empty($validated['plan_id'])) {
            $plan = $this->plans->find((int) $validated['plan_id']);
        }

        if (! $plan && ! empty($validated['tier'])) {
            $plan = $validated['tier'] === 'free'
                ? $this->plans->query()
                    ->orderByDesc('is_default')
                    ->orderBy('sort_order')
                    ->orderBy('price')
                    ->orderBy('id')
                    ->first()
                : $this->plans->query()
                    ->where('price', '>', 0)
                    ->orderBy('sort_order')
                    ->orderBy('price')
                    ->orderBy('id')
                    ->first();
        }

        if (! ($plan instanceof Plan)) {
            return response()->json([
                'message' => 'No matching plan found.',
            ], 422);
        }

        $tier = $this->resolveTier($plan);
        if ($tier !== 'free' && (float) $plan->price > 0) {
            return response()->json([
                'message' => 'Paid plans require checkout payment first.',
            ], 422);
        }

        /** @var User|null $user */
        $user = $request->user();
        if (! $user) {
            return response()->json([
                'message' => 'Unauthorized.',
            ], 401);
        }

        $today = now()->toDateString();
        $this->subscriptions->query()
            ->where('user_id', $user->id)
            ->where('status', 'active')
            ->whereNotNull('end_date')
            ->whereDate('end_date', '<', $today)
            ->update([
                'status' => 'expired',
                'end_date' => $today,
            ]);

        $activeSubscription = $this->subscriptions->query()
            ->with('plan')
            ->where('user_id', $user->id)
            ->where('status', 'active')
            ->where(function ($query) use ($today) {
                $query->whereNull('end_date')
                    ->orWhereDate('end_date', '>=', $today);
            })
            ->orderByDesc('start_date')
            ->orderByDesc('id')
            ->first();

        if ($plan->isFreeTrial()) {
            if ($activeSubscription instanceof UserSubscription
                && (int) $activeSubscription->plan_id === (int) $plan->id) {
                $durationDays = $plan->durationDays();

                return response()->json([
                    'message' => 'Free trial is already active.',
                    'data' => [
                        'subscription_id' => $activeSubscription->id,
                        'plan_id' => $plan->id,
                        'tier' => $tier,
                        'plan_name' => $plan->name,
                        'plan_group' => $plan->groupKey(),
                        'sub_plan_label' => $plan->subPlanTitle(),
                        'billing_cycle' => $activeSubscription->billing_cycle,
                        'duration_days' => $durationDays,
                        'end_date' => $activeSubscription->end_date?->toDateString(),
                    ],
                ]);
            }

            $resumeCandidate = $this->findResumableFreeTrial($user, $plan);
            if ($resumeCandidate !== null) {
                $resumeEndDate = $resumeCandidate['end_date'];
                $billingCycle = $resumeCandidate['billing_cycle'];

                $this->deactivateActiveSubscriptions($user, now()->toDateString());

                /** @var UserSubscription $resumeSubscription */
                $resumeSubscription = $resumeCandidate['subscription'];
                $resumeSubscription->update([
                    'status' => 'active',
                    'billing_cycle' => $billingCycle,
                    'end_date' => $resumeEndDate,
                ]);

                return response()->json([
                    'message' => 'Free trial resumed.',
                    'data' => [
                        'subscription_id' => $resumeSubscription->id,
                        'plan_id' => $plan->id,
                        'tier' => $tier,
                        'plan_name' => $plan->name,
                        'plan_group' => $plan->groupKey(),
                        'sub_plan_label' => $plan->subPlanTitle(),
                        'billing_cycle' => $billingCycle,
                        'duration_days' => $plan->durationDays(),
                        'end_date' => $resumeEndDate,
                    ],
                ]);
            }

            if ($this->hasConsumedFreeTrial($user)) {
                return response()->json([
                    'message' => 'Free trial can only be activated once. Please choose a paid plan.',
                ], 422);
            }
        }

        $this->deactivateActiveSubscriptions($user, now()->toDateString());

        $billingCycle = $validated['billing_cycle'] ?? $this->normalizeBillingCycle($plan->billing_cycle);
        $durationDays = $plan->durationDays();
        $endDate = $this->calculateEndDate(now(), $durationDays);

        /** @var UserSubscription $subscription */
        $subscription = $this->subscriptions->create([
            'user_id' => $user->id,
            'plan_id' => $plan->id,
            'billing_cycle' => $billingCycle,
            'start_date' => now()->toDateString(),
            'end_date' => $endDate,
            'status' => 'active',
        ]);

        return response()->json([
            'message' => 'Plan updated successfully.',
            'data' => [
                'subscription_id' => $subscription->id,
                'plan_id' => $plan->id,
                'tier' => $tier,
                'plan_name' => $plan->name,
                'plan_group' => $plan->groupKey(),
                'sub_plan_label' => $plan->subPlanTitle(),
                'billing_cycle' => $billingCycle,
                'duration_days' => $durationDays,
                'end_date' => $endDate,
            ],
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function planPayload(Plan $plan): array
    {
        $tier = $this->resolveTier($plan);
        $billingCycle = $this->normalizeBillingCycle($plan->billing_cycle);
        $durationDays = $plan->durationDays();

        return [
            'id' => $plan->id,
            'name' => $plan->name,
            'title' => $plan->groupTitle(),
            'tier' => $tier,
            'price' => (float) $plan->price,
            'price_label' => $tier === 'free'
                ? 'PHP 0'
                : 'PHP '.$this->formatPrice((float) $plan->price),
            'billing_cycle' => $billingCycle,
            'billing_label' => $plan->billingLabel(),
            'duration_days' => $durationDays,
            'description' => $plan->description,
            'features' => $plan->features->pluck('feature_name')->values()->all(),
            'is_default' => (bool) $plan->is_default,
            'subject_access_count' => $plan->subjectAccesses->count(),
            'plan_group' => $plan->groupKey(),
            'group_label' => $plan->groupTitle(),
            'sub_plan_label' => $plan->subPlanTitle(),
            'sort_order' => (int) ($plan->sort_order ?? 0),
        ];
    }

    private function resolveTier(Plan $plan): string
    {
        return $plan->isFreeTrial() ? 'free' : 'premium';
    }

    private function formatPrice(float $price): string
    {
        if (fmod($price, 1.0) === 0.0) {
            return number_format($price, 0);
        }

        return number_format($price, 2);
    }

    private function normalizeBillingCycle(?string $billingCycle): string
    {
        return in_array($billingCycle, Plan::billingCycles(), true)
            ? (string) $billingCycle
            : Plan::BILLING_MONTHLY;
    }

    private function calculateEndDate(Carbon|string $startDate, int $durationDays): string
    {
        $start = $startDate instanceof Carbon
            ? $startDate->copy()
            : Carbon::parse($startDate);

        return $start->addDays(max(1, $durationDays))->toDateString();
    }

    private function deactivateActiveSubscriptions(User $user, string $endDate): void
    {
        $activeSubscriptions = $this->subscriptions->query()
            ->where('user_id', $user->id)
            ->where('status', 'active')
            ->get();

        foreach ($activeSubscriptions as $subscription) {
            $subscription->update([
                'status' => 'inactive',
                'end_date' => $endDate,
            ]);
        }
    }

    private function hasConsumedFreeTrial(User $user): bool
    {
        return $this->subscriptions->query()
            ->where('user_id', $user->id)
            ->whereHas('plan', function ($query) {
                $query->where('plan_group', 'free_trial')
                    ->orWhere('billing_cycle', Plan::BILLING_TRIAL)
                    ->orWhere(function ($legacyQuery) {
                        $legacyQuery->where('is_default', true)
                            ->where('price', '<=', 0);
                    })
                    ->orWhere(function ($legacyQuery) {
                        $legacyQuery->where('price', '<=', 0)
                            ->where(function ($nameQuery) {
                                $nameQuery->whereRaw('LOWER(name) LIKE ?', ['%trial%'])
                                    ->orWhereRaw('LOWER(name) LIKE ?', ['%free%']);
                            });
                    });
            })
            ->exists();
    }

    /**
     * @return array{subscription: UserSubscription, end_date: string, billing_cycle: string}|null
     */
    private function findResumableFreeTrial(User $user, Plan $plan): ?array
    {
        $subscription = $this->subscriptions->query()
            ->where('user_id', $user->id)
            ->whereHas('plan', function ($query) {
                $query->where('plan_group', 'free_trial')
                    ->orWhere('billing_cycle', Plan::BILLING_TRIAL)
                    ->orWhere(function ($legacyQuery) {
                        $legacyQuery->where('is_default', true)
                            ->where('price', '<=', 0);
                    })
                    ->orWhere(function ($legacyQuery) {
                        $legacyQuery->where('price', '<=', 0)
                            ->where(function ($nameQuery) {
                                $nameQuery->whereRaw('LOWER(name) LIKE ?', ['%trial%'])
                                    ->orWhereRaw('LOWER(name) LIKE ?', ['%free%']);
                            });
                    });
            })
            ->orderByDesc('start_date')
            ->orderByDesc('id')
            ->first();

        if (! $subscription instanceof UserSubscription) {
            return null;
        }

        if ((int) $subscription->plan_id !== (int) $plan->id) {
            return null;
        }

        if (! $subscription->start_date) {
            return null;
        }

        $startDate = Carbon::parse($subscription->start_date);
        $durationDays = max(1, $plan->durationDays());
        $expectedEnd = $startDate->copy()->addDays($durationDays);

        if ($subscription->end_date instanceof Carbon
            && $subscription->end_date->greaterThan($expectedEnd)) {
            $expectedEnd = $subscription->end_date->copy();
        }

        if (Carbon::today()->greaterThan($expectedEnd)) {
            return null;
        }

        $billingCycle = $subscription->billing_cycle ?: $this->normalizeBillingCycle($plan->billing_cycle);

        return [
            'subscription' => $subscription,
            'end_date' => $expectedEnd->toDateString(),
            'billing_cycle' => $billingCycle,
        ];
    }
}
