<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Plan;
use App\Models\UserSubscription;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Validation\Rule;

class MobilePlanController extends Controller
{
    public function index(): JsonResponse
    {
        $plans = Plan::query()
            ->with('features')
            ->orderBy('price')
            ->orderBy('id')
            ->get()
            ->map(function (Plan $plan): array {
                $tier = $this->resolveTier($plan);
                $billingCycle = $this->normalizeBillingCycle($plan->billing_cycle);
                $billingLabel = $billingCycle === Plan::BILLING_YEARLY ? 'per year' : 'per month';

                return [
                    'id' => $plan->id,
                    'name' => $plan->name,
                    'title' => $tier === 'free' ? 'Free Plan' : 'Subscription',
                    'tier' => $tier,
                    'price' => (float) $plan->price,
                    'price_label' => $tier === 'free'
                        ? 'PHP 0'
                        : 'PHP '.$this->formatPrice((float) $plan->price),
                    'billing_cycle' => $billingCycle,
                    'billing_label' => $tier === 'free' ? 'Forever free' : $billingLabel,
                    'description' => $plan->description,
                    'features' => $plan->features->pluck('feature_name')->values()->all(),
                ];
            })
            ->values();

        return response()->json([
            'data' => $plans,
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
            $plan = Plan::query()->find($validated['plan_id']);
        }

        if (! $plan && ! empty($validated['tier'])) {
            $plan = $validated['tier'] === 'free'
                ? Plan::query()->orderBy('price')->orderBy('id')->first()
                : Plan::query()->where('price', '>', 0)->orderBy('price')->orderBy('id')->first();
        }

        if (! $plan) {
            return response()->json([
                'message' => 'No matching plan found.',
            ], 422);
        }

        $user = $request->user();
        if (! $user) {
            return response()->json([
                'message' => 'Unauthorized.',
            ], 401);
        }

        $user->subscriptions()
            ->where('status', 'active')
            ->update([
                'status' => 'inactive',
                'end_date' => now()->toDateString(),
            ]);

        $billingCycle = $validated['billing_cycle'] ?? $this->normalizeBillingCycle($plan->billing_cycle);
        $tier = $this->resolveTier($plan);
        $endDate = $tier === 'free'
            ? null
            : $this->calculateEndDate(now(), $billingCycle);

        $subscription = UserSubscription::query()->create([
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
                'billing_cycle' => $billingCycle,
                'end_date' => $endDate,
            ],
        ]);
    }

    private function resolveTier(Plan $plan): string
    {
        $normalized = strtolower($plan->name);
        if ((float) $plan->price <= 0
            || str_contains($normalized, 'free')) {
            return 'free';
        }

        return 'premium';
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
        return $billingCycle === Plan::BILLING_YEARLY
            ? Plan::BILLING_YEARLY
            : Plan::BILLING_MONTHLY;
    }

    private function calculateEndDate(Carbon|string $startDate, string $billingCycle): string
    {
        $start = $startDate instanceof Carbon
            ? $startDate->copy()
            : Carbon::parse($startDate);

        return $billingCycle === Plan::BILLING_YEARLY
            ? $start->addYear()->toDateString()
            : $start->addMonth()->toDateString();
    }
}
