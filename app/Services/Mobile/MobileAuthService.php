<?php

namespace App\Services\Mobile;

use App\Models\Plan;
use App\Models\User;
use App\Models\UserSubscription;
use App\Repositories\Contracts\PlanRepositoryInterface;
use App\Repositories\Contracts\UserRepositoryInterface;
use App\Repositories\Contracts\UserSubscriptionRepositoryInterface;
use App\Services\FileUploadService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

class MobileAuthService
{
    public function __construct(
        private readonly UserRepositoryInterface $users,
        private readonly PlanRepositoryInterface $plans,
        private readonly UserSubscriptionRepositoryInterface $subscriptions,
        private readonly FileUploadService $uploads,
    ) {
    }

    public function register(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', 'confirmed', Password::defaults()],
            'birthdate' => ['nullable', 'date'],
            'gender' => ['nullable', 'string', Rule::in(['male', 'female', 'other'])],
        ]);

        /** @var User $user */
        $user = $this->users->create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'password' => Hash::make($validated['password']),
            'birthdate' => $validated['birthdate'] ?? null,
            'gender' => $validated['gender'] ?? null,
        ]);

        $subscription = $this->resolveOrCreateSubscription($user);
        $plan = $subscription?->plan;

        $token = $user->createToken('mobile-app')->plainTextToken;

        return response()->json([
            'message' => 'Registered successfully.',
            'token' => $token,
            'user' => $this->mobileUserPayload($user, $plan, $subscription),
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

        /** @var User $user */
        $user = $this->users->query()
            ->where('email', $validated['email'])
            ->firstOrFail();
        $subscription = $this->resolveOrCreateSubscription($user);
        $plan = $subscription?->plan;

        $user->tokens()->delete();
        $token = $user->createToken('mobile-app')->plainTextToken;

        return response()->json([
            'message' => 'Login successful.',
            'token' => $token,
            'user' => $this->mobileUserPayload($user, $plan, $subscription),
        ]);
    }

    public function updateProfile(Request $request): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();
        $maxFileKb = $this->uploads->maxFileKilobytes();

        $validated = $request->validate([
            'name' => ['sometimes', 'required', 'string', 'max:255'],
            'email' => [
                'sometimes',
                'required',
                'string',
                'email',
                'max:255',
                Rule::unique(User::class)->ignore($user->id),
            ],
            'school' => ['nullable', 'string', 'max:255'],
            'birthdate' => ['nullable', 'date'],
            'gender' => ['nullable', 'string', Rule::in(['male', 'female', 'other'])],
            'place' => ['nullable', 'string', 'max:255'],
            'phone_number' => ['nullable', 'string', 'max:25'],
            'avatar' => ['nullable', 'image', "max:$maxFileKb"],
        ]);

        if (array_key_exists('email', $validated)
            && $validated['email'] !== $user->email) {
            $user->email_verified_at = null;
        }

        if ($request->hasFile('avatar')) {
            $previousAvatar = $user->avatar_path;
            $validated['avatar_path'] = $this->uploads->store(
                $request->file('avatar'),
                'avatars'
            );
            $this->uploads->delete($previousAvatar);
        }

        $user->fill($validated);
        $user->save();

        $subscription = $this->resolveOrCreateSubscription($user);
        $plan = $subscription?->plan;

        return response()->json([
            'message' => 'Profile updated.',
            'user' => $this->mobileUserPayload($user, $plan, $subscription),
        ]);
    }

    public function me(Request $request): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();
        $subscription = $this->resolveOrCreateSubscription($user);
        $plan = $subscription?->plan;

        return response()->json([
            'user' => $this->mobileUserPayload($user, $plan, $subscription),
        ]);
    }

    private function resolveOrCreateSubscription(User $user): ?UserSubscription
    {
        $active = $this->subscriptions->query()
            ->with('plan.features')
            ->where('user_id', $user->id)
            ->where('status', 'active')
            ->orderByDesc('start_date')
            ->orderByDesc('id')
            ->first();

        if ($active instanceof UserSubscription) {
            return $active;
        }

        $defaultPlan = $this->plans->query()
            ->orderByDesc('is_default')
            ->orderBy('price')
            ->orderBy('id')
            ->first();

        if (! ($defaultPlan instanceof Plan)) {
            return null;
        }

        $billingCycle = $this->normalizeBillingCycle($defaultPlan->billing_cycle);
        $tier = $this->resolveTier($defaultPlan);
        $endDate = $tier === 'free'
            ? null
            : $this->calculateEndDate(now(), $billingCycle);

        /** @var UserSubscription $subscription */
        $subscription = $this->subscriptions->create([
            'user_id' => $user->id,
            'plan_id' => $defaultPlan->id,
            'billing_cycle' => $billingCycle,
            'start_date' => now()->toDateString(),
            'end_date' => $endDate,
            'status' => 'active',
        ]);

        return $subscription->load('plan.features');
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
    ): array {
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
