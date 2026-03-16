<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Plan;
use App\Models\User;
use App\Models\UserSubscription;
use App\Services\FileUploadService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

class MobileAuthController extends Controller
{
    public function register(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', 'confirmed', Password::defaults()],
        ]);

        $user = User::query()->create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'password' => Hash::make($validated['password']),
        ]);

        $subscription = $this->resolveOrCreateSubscription($user);
        $plan = $subscription?->plan;

        $token = $user->createToken('mobile-app')->plainTextToken;

        return response()->json([
            'message' => 'Registered successfully.',
            'token' => $token,
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'plan_tier' => $this->resolveTier($plan),
                'plan' => $plan ? [
                    'id' => $plan->id,
                    'name' => $plan->name,
                    'tier' => $this->resolveTier($plan),
                    'billing_cycle' => $this->normalizeBillingCycle($plan->billing_cycle),
                ] : null,
            ],
        ], 201);
    }

    public function login(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'email' => ['required', 'string', 'email'],
            'password' => ['required', 'string'],
        ]);

        if (! Auth::attempt([
            'email' => $validated['email'],
            'password' => $validated['password'],
        ])) {
            return response()->json([
                'message' => 'Invalid credentials.',
                'errors' => [
                    'email' => ['Invalid credentials.'],
                ],
            ], 422);
        }

        $user = User::query()->where('email', $validated['email'])->firstOrFail();
        $subscription = $this->resolveOrCreateSubscription($user);
        $plan = $subscription?->plan;

        $user->tokens()->delete();
        $token = $user->createToken('mobile-app')->plainTextToken;

        return response()->json([
            'message' => 'Login successful.',
            'token' => $token,
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'plan_tier' => $this->resolveTier($plan),
                'plan' => $plan ? [
                    'id' => $plan->id,
                    'name' => $plan->name,
                    'tier' => $this->resolveTier($plan),
                    'billing_cycle' => $this->normalizeBillingCycle($plan->billing_cycle),
                ] : null,
            ],
        ]);
    }

    private function resolveOrCreateSubscription(User $user): ?UserSubscription
    {
        $active = $user->subscriptions()
            ->with('plan.features')
            ->where('status', 'active')
            ->orderByDesc('start_date')
            ->orderByDesc('id')
            ->first();

        if ($active) {
            return $active;
        }

        $defaultPlan = Plan::query()
            ->orderByDesc('is_default')
            ->orderBy('price')
            ->orderBy('id')
            ->first();

        if (! $defaultPlan) {
            return null;
        }

        $billingCycle = $this->normalizeBillingCycle($defaultPlan->billing_cycle);
        $tier = $this->resolveTier($defaultPlan);
        $endDate = $tier === 'free'
            ? null
            : $this->calculateEndDate(now(), $billingCycle);

        return UserSubscription::query()
            ->create([
                'user_id' => $user->id,
                'plan_id' => $defaultPlan->id,
                'billing_cycle' => $billingCycle,
                'start_date' => now()->toDateString(),
                'end_date' => $endDate,
                'status' => 'active',
            ])
            ->load('plan.features');
    }

    private function resolveTier(?Plan $plan): string
    {
        if (! $plan) {
            return 'free';
        }

        $normalized = strtolower($plan->name);
        if ((bool) $plan->is_default
            || (float) $plan->price <= 0
            || str_contains($normalized, 'free')) {
            return 'free';
        }

        return 'premium';
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

    private function mobileUserPayload(
        User $user,
        ?Plan $plan,
        ?UserSubscription $subscription = null,
    ): array
    {
        return [
            'id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
            'school' => $user->school,
            'birthdate' => $user->birthdate?->toDateString(),
            'gender' => $user->gender,
            'place' => $user->place,
            'phone_number' => $user->phone_number,
            'avatar_url' => $user->avatar_url,
            'referral_code' => $user->referral_code,
            'referred_by' => $user->referred_by,
            'plan_tier' => $this->resolveTier($plan),
            'subscription' => $subscription ? [
                'id' => $subscription->id,
                'plan_id' => $subscription->plan_id,
                'billing_cycle' => $subscription->billing_cycle,
                'start_date' => $subscription->start_date?->toDateString(),
                'end_date' => $subscription->end_date?->toDateString(),
                'status' => $subscription->status,
            ] : null,
            'end_date' => $subscription?->end_date?->toDateString(),
            'plan' => $plan ? [
                'id' => $plan->id,
                'name' => $plan->name,
                'tier' => $this->resolveTier($plan),
                'billing_cycle' => $subscription
                    ? $subscription->billing_cycle
                    : $this->normalizeBillingCycle($plan->billing_cycle),
            ] : null,
        ];
    }
}
