<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
<<<<<<< HEAD
use App\Models\Plan;
use App\Models\User;
use App\Models\UserSubscription;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Validation\Rule;
use Laravel\Sanctum\PersonalAccessToken;
=======
use App\Services\Mobile\MobilePlanService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
>>>>>>> 28865895a7c0afdec9935e1db7832274b9a2f54c

class MobilePlanController extends Controller
{
    public function __construct(private readonly MobilePlanService $planService)
    {
    }

    public function index(): JsonResponse
    {
        return $this->planService->index();
    }

    public function select(Request $request): JsonResponse
    {
<<<<<<< HEAD
        $validated = $request->validate([
            'plan_id' => ['required', 'integer', 'exists:plans,id'],
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

        $tier = $this->resolveTier($plan);
        if ($tier !== 'free' && (float) $plan->price > 0) {
            return response()->json([
                'message' => 'Paid plans require checkout payment first.',
            ], 422);
        }

        $user = $request->user();
        if (! $user) {
            $token = $request->bearerToken();
            if ($token) {
                $tokenModel = PersonalAccessToken::findToken($token);
                $tokenUser = $tokenModel?->tokenable;
                if ($tokenUser instanceof User) {
                    $user = $tokenUser;
                }
            }
        }

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
        if ((bool) $plan->is_default
            || (float) $plan->price <= 0
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
=======
        return $this->planService->select($request);
>>>>>>> 28865895a7c0afdec9935e1db7832274b9a2f54c
    }
}
