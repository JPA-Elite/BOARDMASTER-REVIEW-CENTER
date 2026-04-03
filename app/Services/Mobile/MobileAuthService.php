<?php

namespace App\Services\Mobile;

use App\Models\Plan;
use App\Models\User;
use App\Models\UserSubscription;
use App\Repositories\Contracts\PlanRepositoryInterface;
use App\Repositories\Contracts\UserRepositoryInterface;
use App\Repositories\Contracts\UserSubscriptionRepositoryInterface;
use App\Services\FileUploadService;
use Illuminate\Auth\Events\Verified;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password as PasswordRule;

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
            'password' => ['required', 'confirmed', PasswordRule::min(8)->mixedCase()->numbers()->symbols()],
            'school' => ['nullable', 'string', 'max:255'],
            'phone_number' => ['nullable', 'string', 'max:25'],
            'birthdate' => ['nullable', 'date'],
            'gender' => ['nullable', 'string', Rule::in(['male', 'female', 'other'])],
        ]);

        /** @var User $user */
        $user = $this->users->create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'password' => Hash::make($validated['password']),
            'school' => $validated['school'] ?? null,
            'phone_number' => $validated['phone_number'] ?? null,
            'birthdate' => $validated['birthdate'] ?? null,
            'gender' => $validated['gender'] ?? null,
        ]);

        $user->sendEmailVerificationNotification();
        $subscription = $this->resolveOrCreateSubscription($user);
        $plan = $subscription?->plan;

        return response()->json([
            'message' => 'Registered successfully. Please verify your email before logging in.',
            'requires_verification' => true,
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

        if ($user instanceof MustVerifyEmail && ! $user->hasVerifiedEmail()) {
            $user->sendEmailVerificationNotification();

            return response()->json([
                'message' => 'Email is not verified yet. We sent a verification link.',
                'requires_verification' => true,
            ], 403);
        }

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

    public function loginWithGoogle(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'id_token' => ['required', 'string'],
            'name' => ['nullable', 'string', 'max:255'],
            'email' => ['nullable', 'string', 'email', 'max:255'],
            'avatar_url' => ['nullable', 'string', 'max:2048'],
        ]);

        $audiences = $this->googleAudiences();
        if ($audiences === []) {
            return response()->json([
                'message' => 'Google login is not configured on server yet. Set GOOGLE_AUTH_AUDIENCES in backend .env.',
            ], 500);
        }

        $verification = $this->verifyGoogleIdToken($validated['id_token'], $audiences);
        if (! $verification['ok']) {
            return response()->json([
                'message' => $verification['message'] ?? 'Google token verification failed. Please try again.',
            ], 422);
        }
        $claims = $verification['payload'];

        $email = strtolower(trim((string) ($claims['email'] ?? '')));
        if ($email === '') {
            return response()->json([
                'message' => 'Google account email is missing.',
            ], 422);
        }

        $emailVerified = filter_var(
            $claims['email_verified'] ?? false,
            FILTER_VALIDATE_BOOLEAN,
            FILTER_NULL_ON_FAILURE,
        ) ?? false;
        if (! $emailVerified) {
            return response()->json([
                'message' => 'Your Google email is not verified.',
            ], 422);
        }

        $googleId = trim((string) ($claims['sub'] ?? ''));
        if ($googleId === '') {
            return response()->json([
                'message' => 'Google account identifier is missing.',
            ], 422);
        }

        /** @var User|null $user */
        $user = $this->users->query()
            ->whereRaw('LOWER(email) = ?', [$email])
            ->first();

        $googleName = trim((string) ($claims['name'] ?? ''));
        $fallbackName = trim((string) ($validated['name'] ?? ''));
        $name = $googleName !== ''
            ? $googleName
            : ($fallbackName !== '' ? $fallbackName : $this->nameFromEmail($email));

        $googleAvatar = trim((string) ($claims['picture'] ?? ''));
        $fallbackAvatar = trim((string) ($validated['avatar_url'] ?? ''));
        $avatarUrl = $googleAvatar !== '' ? $googleAvatar : $fallbackAvatar;
        $canStoreGoogleId = Schema::hasColumn('users', 'google_id');

        if (! $user instanceof User) {
            $newData = [
                'name' => $name,
                'email' => $email,
                'password' => Hash::make(Str::random(64)),
                'email_verified_at' => now(),
                'avatar_path' => $avatarUrl !== '' ? $avatarUrl : null,
            ];
            if ($canStoreGoogleId) {
                $newData['google_id'] = $googleId;
            }

            /** @var User $createdUser */
            $createdUser = $this->users->create($newData);
            $user = $createdUser;
        } else {
            $updates = [];
            if ($canStoreGoogleId && $user->google_id !== null && (string) $user->google_id !== $googleId) {
                return response()->json([
                    'message' => 'This email is already linked to a different Google account.',
                ], 409);
            }
            if ($canStoreGoogleId && $user->google_id === null) {
                $updates['google_id'] = $googleId;
            }
            if ($user->name === '' && $name !== '') {
                $updates['name'] = $name;
            }
            if ($user->email_verified_at === null) {
                $updates['email_verified_at'] = now();
            }
            if (($user->avatar_path ?? '') === '' && $avatarUrl !== '') {
                $updates['avatar_path'] = $avatarUrl;
            }
            if ($updates !== []) {
                $user->fill($updates);
                $user->save();
            }
        }

        $subscription = $this->resolveOrCreateSubscription($user);
        $plan = $subscription?->plan;

        $user->tokens()->delete();
        $token = $user->createToken('mobile-app')->plainTextToken;

        return response()->json([
            'message' => 'Google login successful.',
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

        $emailChanged = array_key_exists('email', $validated)
            && strcasecmp((string) $validated['email'], (string) $user->email) !== 0;

        if ($emailChanged) {
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
        if ($emailChanged) {
            $user->sendEmailVerificationNotification();
        }

        $subscription = $this->resolveOrCreateSubscription($user);
        $plan = $subscription?->plan;

        return response()->json([
            'message' => $emailChanged
                ? 'Profile updated. Please verify your new email address.'
                : 'Profile updated.',
            'user' => $this->mobileUserPayload($user, $plan, $subscription),
        ]);
    }

    public function forgotPassword(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'email' => ['required', 'string', 'email'],
        ]);

        $status = Password::broker()->sendResetLink([
            'email' => $validated['email'],
        ]);

        if ($status !== Password::RESET_LINK_SENT) {
            return response()->json([
                'message' => __($status),
            ], 422);
        }

        return response()->json([
            'message' => __($status),
        ]);
    }

    public function resendVerification(Request $request): JsonResponse
    {
        /** @var User|null $authUser */
        $authUser = $request->user();
        $user = $authUser;

        if (! $user) {
            $validated = $request->validate([
                'email' => ['required', 'string', 'email', 'exists:users,email'],
            ]);

            /** @var User|null $resolved */
            $resolved = $this->users->query()
                ->where('email', $validated['email'])
                ->first();
            $user = $resolved;
        }

        if (! $user instanceof User) {
            return response()->json([
                'message' => 'User not found.',
            ], 404);
        }

        if ($user instanceof MustVerifyEmail && $user->hasVerifiedEmail()) {
            return response()->json([
                'message' => 'Email is already verified.',
            ]);
        }

        $user->sendEmailVerificationNotification();

        return response()->json([
            'message' => 'A verification link has been sent to your email.',
        ]);
    }

    public function checkEmail(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'email' => ['required', 'string', 'email'],
            'ignore_email' => ['nullable', 'string', 'email'],
        ]);

        $email = strtolower(trim((string) $validated['email']));
        $ignoreEmail = strtolower(trim((string) ($validated['ignore_email'] ?? '')));
        if ($ignoreEmail !== '' && $email === $ignoreEmail) {
            return response()->json([
                'available' => true,
                'message' => 'Email can be used.',
            ]);
        }

        $exists = $this->users->query()
            ->whereRaw('LOWER(email) = ?', [$email])
            ->exists();

        return response()->json([
            'available' => ! $exists,
            'message' => $exists
                ? 'This email address is already registered.'
                : 'Email can be used.',
        ]);
    }

    public function verifyEmail(Request $request, int $id, string $hash)
    {
        /** @var User|null $user */
        $user = $this->users->query()->find($id);
        if (! $user) {
            return response()->view('emails.mobile.verify-status', [
                'status' => 'error',
                'title' => 'Verification Failed',
                'message' => 'This verification link is no longer valid.',
            ], 404);
        }

        if (! hash_equals($hash, sha1($user->getEmailForVerification()))) {
            return response()->view('emails.mobile.verify-status', [
                'status' => 'error',
                'title' => 'Verification Failed',
                'message' => 'Invalid verification link.',
            ], 403);
        }

        if ($user instanceof MustVerifyEmail && ! $user->hasVerifiedEmail()) {
            if ($user->markEmailAsVerified()) {
                event(new Verified($user));
            }
        }

        return response()->view('emails.mobile.verify-status', [
            'status' => 'success',
            'title' => 'Email Verified',
            'message' => 'Your email has been verified. You can now login in the mobile app.',
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

    private function verifyGoogleIdToken(string $idToken, array $audiences): array
    {
        try {
            $response = Http::timeout(8)->get('https://oauth2.googleapis.com/tokeninfo', [
                'id_token' => $idToken,
            ]);
        } catch (\Throwable) {
            return [
                'ok' => false,
                'message' => 'Unable to contact Google token verification service. Please check internet connection.',
                'payload' => null,
            ];
        }

        if (! $response->successful()) {
            $body = $response->json();
            $error = is_array($body) ? trim((string) ($body['error_description'] ?? $body['error'] ?? '')) : '';
            return [
                'ok' => false,
                'message' => $error !== ''
                    ? "Google token verification failed: $error"
                    : 'Google token verification failed. Please try again.',
                'payload' => null,
            ];
        }

        $payload = $response->json();
        if (! is_array($payload)) {
            return [
                'ok' => false,
                'message' => 'Google token verification returned invalid response format.',
                'payload' => null,
            ];
        }

        $issuer = strtolower(trim((string) ($payload['iss'] ?? '')));
        if (! in_array($issuer, ['accounts.google.com', 'https://accounts.google.com'], true)) {
            return [
                'ok' => false,
                'message' => 'Google token issuer is invalid.',
                'payload' => null,
            ];
        }

        $audience = trim((string) ($payload['aud'] ?? ''));
        $authorizedParty = trim((string) ($payload['azp'] ?? ''));
        $audienceMatched =
            ($audience !== '' && in_array($audience, $audiences, true))
            || ($authorizedParty !== '' && in_array($authorizedParty, $audiences, true));
        if (! $audienceMatched) {
            $expected = implode(', ', $audiences);
            $received = $audience !== '' ? $audience : '(empty)';
            $azpText = $authorizedParty !== '' ? " | azp: $authorizedParty" : '';
            return [
                'ok' => false,
                'message' => "Google token audience mismatch. Expected one of: $expected | Received aud: $received$azpText",
                'payload' => null,
            ];
        }

        $expiresAt = (int) ($payload['exp'] ?? 0);
        if ($expiresAt > 0 && $expiresAt < now()->timestamp) {
            return [
                'ok' => false,
                'message' => 'Google token is already expired.',
                'payload' => null,
            ];
        }

        return [
            'ok' => true,
            'message' => null,
            'payload' => $payload,
        ];
    }

    /**
     * @return list<string>
     */
    private function googleAudiences(): array
    {
        $raw = trim((string) config('services.google.auth_audiences', ''));
        $parts = preg_split('/[\s,;]+/', $raw) ?: [];
        $audiences = [];
        foreach ($parts as $part) {
            $value = trim((string) $part);
            if ($value !== '') {
                $audiences[] = $value;
            }
        }

        if ($audiences === []) {
            $single = trim((string) config('services.google.client_id', ''));
            if ($single !== '') {
                $audiences[] = $single;
            }
        }

        return array_values(array_unique($audiences));
    }

    private function nameFromEmail(string $email): string
    {
        $base = trim((string) explode('@', trim($email))[0]);
        if ($base === '') {
            return 'BoardMaster User';
        }

        return ucfirst($base);
    }

    private function resolveOrCreateSubscription(User $user): ?UserSubscription
    {
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

        $active = $this->subscriptions->query()
            ->with('plan.features')
            ->where('user_id', $user->id)
            ->where('status', 'active')
            ->where(function ($query) use ($today) {
                $query->whereNull('end_date')
                    ->orWhereDate('end_date', '>=', $today);
            })
            ->orderByDesc('start_date')
            ->orderByDesc('id')
            ->first();

        if ($active instanceof UserSubscription) {
            return $active;
        }

        $defaultPlan = $this->plans->query()
            ->orderByDesc('is_default')
            ->orderBy('sort_order')
            ->orderBy('price')
            ->orderBy('id')
            ->first();

        if (! ($defaultPlan instanceof Plan)) {
            return null;
        }

        if ($defaultPlan->isFreeTrial() && $this->hasConsumedFreeTrial($user)) {
            return null;
        }

        $billingCycle = $this->normalizeBillingCycle($defaultPlan->billing_cycle);
        $endDate = $this->calculateEndDate(now(), $defaultPlan->durationDays());

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

        if ($plan->isFreeTrial()) {
            return 'free';
        }

        return 'premium';
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

    private function mobileUserPayload(
        User $user,
        ?Plan $plan,
        ?UserSubscription $subscription = null,
    ): array {
        return [
            'id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
            'email_verified' => $user->hasVerifiedEmail(),
            'email_verified_at' => $user->email_verified_at?->toDateTimeString(),
            'school' => $user->school,
            'birthdate' => $user->birthdate?->toDateString(),
            'gender' => $user->gender,
            'place' => $user->place,
            'phone_number' => $user->phone_number,
            'avatar_url' => $user->avatar_url,
            'referral_code' => $user->referral_code,
            'referred_by' => $user->referred_by,
            'trial_consumed' => $this->hasConsumedFreeTrial($user),
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
                'plan_group' => $plan->groupKey(),
                'group_label' => $plan->groupTitle(),
                'sub_plan_label' => $plan->subPlanTitle(),
                'duration_days' => $plan->durationDays(),
                'billing_cycle' => $subscription
                    ? $subscription->billing_cycle
                    : $this->normalizeBillingCycle($plan->billing_cycle),
            ] : null,
        ];
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
}
