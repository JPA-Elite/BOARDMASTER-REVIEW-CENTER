<?php

namespace App\Services\Mobile;

use App\Models\Plan;
use App\Models\User;
use App\Models\UserSubscription;
use App\Repositories\Contracts\PlanRepositoryInterface;
use App\Repositories\Contracts\UserRepositoryInterface;
use App\Repositories\Contracts\UserSubscriptionRepositoryInterface;
use App\Services\FileUploadService;
use App\Services\UserDeletionService;
use Illuminate\Auth\Events\Verified;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Validator;
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
        private readonly UserDeletionService $userDeletion,
    ) {
    }

    public function register(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => $this->nameValidationRules(),
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', 'confirmed', PasswordRule::min(8)->mixedCase()->numbers()->symbols()],
            'school' => ['required', 'string', 'max:255'],
            'phone_number' => $this->phoneValidationRules(),
            'birthdate' => ['nullable', 'date'],
            'gender' => ['nullable', 'string', Rule::in(['male', 'female', 'other'])],
        ]);

        /** @var User $user */
        $user = $this->users->create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'password' => Hash::make($validated['password']),
            'school' => $validated['school'],
            'phone_number' => $this->normalizePhilippinePhone((string) $validated['phone_number']),
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

        /** @var User|null $originUser */
        $originUser = $this->users->query()
            ->whereRaw('LOWER(email) = ?', [strtolower(trim((string) $validated['email']))])
            ->first();
        if ($originUser instanceof User && $this->isSocialOriginUser($originUser)) {
            return response()->json([
                'message' => 'Invalid credentials. Please try again.',
            ], 409);
        }

        if (! Auth::attempt([
            'email' => $validated['email'],
            'password' => $validated['password'],
        ])) {
            return response()->json([
                'message' => 'Invalid credentials. Please try again.',
                'errors' => [
                    'email' => ['Invalid credentials. Please try again.'],
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
            $profileValidation = Validator::make($request->all(), [
                'name' => $this->nameValidationRules(),
                'school' => ['required', 'string', 'max:255'],
                'phone_number' => $this->phoneValidationRules(),
            ]);
            if ($profileValidation->fails()) {
                return response()->json([
                    'message' => 'Complete your profile first (full name, phone number, school) to continue with Google sign-in.',
                    'errors' => $profileValidation->errors(),
                ], 428);
            }
            $profile = $profileValidation->validated();

            $newData = [
                'name' => $profile['name'],
                'email' => $email,
                'password' => Hash::make(Str::random(64)),
                'school' => $profile['school'],
                'phone_number' => $this->normalizePhilippinePhone((string) $profile['phone_number']),
                'avatar_path' => $avatarUrl !== '' ? $avatarUrl : null,
            ];
            if ($canStoreGoogleId) {
                $newData['google_id'] = $googleId;
            }

            /** @var User $createdUser */
            $createdUser = $this->users->create($newData);
            $user = $createdUser;
            if ($user->email_verified_at === null) {
                $user->email_verified_at = now();
                $user->save();
            }
        } else {
            $updates = [];
            if ($canStoreGoogleId && $user->google_id === null) {
                return response()->json([
                    'message' => 'This email is already registered with manual signup. Please sign in using email and password.',
                ], 409);
            }
            if ($canStoreGoogleId && $user->google_id !== null && (string) $user->google_id !== $googleId) {
                return response()->json([
                    'message' => 'This email is already linked to a different Google account.',
                ], 409);
            }
            if ($user->name === '' && $name !== '') {
                $updates['name'] = $name;
            }
            if (($user->avatar_path ?? '') === '' && $avatarUrl !== '') {
                $updates['avatar_path'] = $avatarUrl;
            }
            if ($updates !== []) {
                $user->fill($updates);
                if ($user->email_verified_at === null) {
                    $user->email_verified_at = now();
                }
                $user->save();
            } elseif ($user->email_verified_at === null) {
                $user->email_verified_at = now();
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

    public function loginWithApple(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'id_token' => ['required', 'string'],
            'apple_user_id' => ['nullable', 'string', 'max:255'],
            'name' => ['nullable', 'string', 'max:255'],
            'email' => ['nullable', 'string', 'email', 'max:255'],
            'avatar_url' => ['nullable', 'string', 'max:2048'],
        ]);

        $audiences = $this->appleAudiences();
        if ($audiences === []) {
            return response()->json([
                'message' => 'Apple login is not configured on server yet. Set APPLE_AUTH_AUDIENCES in backend .env.',
            ], 500);
        }

        $verification = $this->verifyAppleIdentityToken($validated['id_token'], $audiences);
        if (! $verification['ok']) {
            return response()->json([
                'message' => $verification['message'] ?? 'Apple token verification failed. Please try again.',
            ], 422);
        }

        /** @var array<string, mixed> $claims */
        $claims = $verification['payload'];
        $appleId = trim((string) ($claims['sub'] ?? $validated['apple_user_id'] ?? ''));
        if ($appleId === '') {
            return response()->json([
                'message' => 'Apple account identifier is missing.',
            ], 422);
        }

        $email = strtolower(trim((string) ($claims['email'] ?? $validated['email'] ?? '')));
        $emailVerified = filter_var(
            $claims['email_verified'] ?? ($email === '' ? true : false),
            FILTER_VALIDATE_BOOLEAN,
            FILTER_NULL_ON_FAILURE,
        ) ?? false;
        if ($email !== '' && ! $emailVerified) {
            return response()->json([
                'message' => 'Your Apple email is not verified.',
            ], 422);
        }

        $canStoreAppleId = Schema::hasColumn('users', 'apple_id');
        /** @var User|null $user */
        $user = null;
        if ($canStoreAppleId) {
            $user = $this->users->query()
                ->where('apple_id', $appleId)
                ->first();
        }

        if (! $user instanceof User && $email !== '') {
            $user = $this->users->query()
                ->whereRaw('LOWER(email) = ?', [$email])
                ->first();
        }

        $fallbackName = trim((string) ($validated['name'] ?? ''));
        $name = $fallbackName !== '' ? $fallbackName : ($email !== '' ? $this->nameFromEmail($email) : '');
        $avatarUrl = trim((string) ($validated['avatar_url'] ?? ''));

        if (! $user instanceof User) {
            if ($email === '') {
                return response()->json([
                    'message' => 'Apple account email is missing. Revoke this app from Apple ID settings and sign in again.',
                ], 422);
            }

            $profileValidation = Validator::make($request->all(), [
                'name' => $this->nameValidationRules(),
                'school' => ['required', 'string', 'max:255'],
                'phone_number' => $this->phoneValidationRules(),
            ]);
            if ($profileValidation->fails()) {
                return response()->json([
                    'message' => 'Complete your profile first (full name, phone number, school) to continue with Apple sign-in.',
                    'errors' => $profileValidation->errors(),
                ], 428);
            }
            $profile = $profileValidation->validated();

            $newData = [
                'name' => $profile['name'],
                'email' => $email,
                'password' => Hash::make(Str::random(64)),
                'school' => $profile['school'],
                'phone_number' => $this->normalizePhilippinePhone((string) $profile['phone_number']),
                'avatar_path' => $avatarUrl !== '' ? $avatarUrl : null,
            ];
            if ($canStoreAppleId) {
                $newData['apple_id'] = $appleId;
            }

            /** @var User $createdUser */
            $createdUser = $this->users->create($newData);
            $user = $createdUser;
            if ($user->email_verified_at === null) {
                $user->email_verified_at = now();
                $user->save();
            }
        } else {
            $updates = [];
            if ($canStoreAppleId && $user->apple_id === null) {
                return response()->json([
                    'message' => 'This email is already registered with another sign-in method. Please use your existing login option.',
                ], 409);
            }
            if ($canStoreAppleId && $user->apple_id !== null && (string) $user->apple_id !== $appleId) {
                return response()->json([
                    'message' => 'This email is already linked to a different Apple account.',
                ], 409);
            }
            if ($user->name === '' && $name !== '') {
                $updates['name'] = $name;
            }
            if (($user->avatar_path ?? '') === '' && $avatarUrl !== '') {
                $updates['avatar_path'] = $avatarUrl;
            }
            if ($updates !== []) {
                $user->fill($updates);
                if ($user->email_verified_at === null) {
                    $user->email_verified_at = now();
                }
                $user->save();
            } elseif ($user->email_verified_at === null) {
                $user->email_verified_at = now();
                $user->save();
            }
        }

        $subscription = $this->resolveOrCreateSubscription($user);
        $plan = $subscription?->plan;

        $user->tokens()->delete();
        $token = $user->createToken('mobile-app')->plainTextToken;

        return response()->json([
            'message' => 'Apple login successful.',
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
            return response()->json([
                'message' => 'Email cannot be changed after account creation.',
                'errors' => [
                    'email' => ['Email cannot be changed after account creation.'],
                ],
            ], 422);
        }

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

        $email = strtolower(trim((string) $validated['email']));
        if ($email !== '') {
            /** @var User|null $user */
            $user = $this->users->query()
                ->whereRaw('LOWER(email) = ?', [$email])
                ->first();
            if ($user instanceof User && $this->isSocialOriginUser($user)) {
                return response()->json([
                    'message' => 'Cannot proceed with password reset. This email is linked to social sign-in.',
                    'errors' => [
                        'email' => [
                            'Cannot proceed with password reset. This email is linked to social sign-in.',
                        ],
                    ],
                ], 422);
            }
        }

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

    public function markDemoSeen(Request $request): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();
        if ($user->mobile_demo_seen_at === null) {
            $user->mobile_demo_seen_at = now();
            $user->save();
        }
        $subscription = $this->resolveOrCreateSubscription($user);
        $plan = $subscription?->plan;

        return response()->json([
            'message' => 'Demo status updated.',
            'user' => $this->mobileUserPayload($user, $plan, $subscription),
        ]);
    }

    public function destroy(Request $request): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();

        $validated = $request->validate([
            'password' => ['nullable', 'string'],
        ]);

        $password = trim((string) ($validated['password'] ?? ''));
        if (! $this->isSocialOriginUser($user)) {
            if ($password === '') {
                return response()->json([
                    'message' => 'Password is required to delete your account.',
                    'errors' => [
                        'password' => ['Password is required to delete your account.'],
                    ],
                ], 422);
            }

            if (! Hash::check($password, (string) $user->password)) {
                return response()->json([
                    'message' => 'Incorrect password. Please try again.',
                    'errors' => [
                        'password' => ['Incorrect password. Please try again.'],
                    ],
                ], 422);
            }
        }

        $this->userDeletion->delete($user);

        return response()->json([
            'message' => 'Your account has been deleted successfully.',
        ]);
    }

    private function verifyAppleIdentityToken(string $idToken, array $audiences): array
    {
        $parts = explode('.', trim($idToken));
        if (count($parts) !== 3) {
            return [
                'ok' => false,
                'message' => 'Apple token format is invalid.',
                'payload' => null,
            ];
        }

        [$encodedHeader, $encodedPayload, $encodedSignature] = $parts;
        $header = $this->decodeJwtSegmentAsArray($encodedHeader);
        $payload = $this->decodeJwtSegmentAsArray($encodedPayload);
        if (! is_array($header) || ! is_array($payload)) {
            return [
                'ok' => false,
                'message' => 'Apple token payload is invalid.',
                'payload' => null,
            ];
        }

        $algorithm = trim((string) ($header['alg'] ?? ''));
        $keyId = trim((string) ($header['kid'] ?? ''));
        if ($algorithm !== 'RS256' || $keyId === '') {
            return [
                'ok' => false,
                'message' => 'Apple token header is invalid.',
                'payload' => null,
            ];
        }

        $keys = $this->applePublicKeys();
        $jwk = null;
        foreach ($keys as $candidate) {
            if (is_array($candidate) && trim((string) ($candidate['kid'] ?? '')) === $keyId) {
                $jwk = $candidate;
                break;
            }
        }
        if (! is_array($jwk)) {
            return [
                'ok' => false,
                'message' => 'Unable to match Apple signing key.',
                'payload' => null,
            ];
        }

        $publicKey = $this->jwkToPem($jwk);
        if ($publicKey === null) {
            return [
                'ok' => false,
                'message' => 'Apple signing key format is invalid.',
                'payload' => null,
            ];
        }

        $signature = $this->base64UrlDecode($encodedSignature);
        if ($signature === null) {
            return [
                'ok' => false,
                'message' => 'Apple token signature is invalid.',
                'payload' => null,
            ];
        }

        $verified = openssl_verify(
            $encodedHeader.'.'.$encodedPayload,
            $signature,
            $publicKey,
            OPENSSL_ALGO_SHA256,
        );
        if ($verified !== 1) {
            return [
                'ok' => false,
                'message' => 'Apple token signature verification failed.',
                'payload' => null,
            ];
        }

        $issuer = trim((string) ($payload['iss'] ?? ''));
        if ($issuer !== 'https://appleid.apple.com') {
            return [
                'ok' => false,
                'message' => 'Apple token issuer is invalid.',
                'payload' => null,
            ];
        }

        $audience = trim((string) ($payload['aud'] ?? ''));
        if ($audience === '' || ! in_array($audience, $audiences, true)) {
            $expected = implode(', ', $audiences);
            $received = $audience !== '' ? $audience : '(empty)';
            return [
                'ok' => false,
                'message' => "Apple token audience mismatch. Expected one of: $expected | Received aud: $received",
                'payload' => null,
            ];
        }

        $expiresAt = (int) ($payload['exp'] ?? 0);
        if ($expiresAt > 0 && $expiresAt < now()->timestamp) {
            return [
                'ok' => false,
                'message' => 'Apple token is already expired.',
                'payload' => null,
            ];
        }

        return [
            'ok' => true,
            'message' => null,
            'payload' => $payload,
        ];
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

    /**
     * @return list<string>
     */
    private function appleAudiences(): array
    {
        $raw = trim((string) config('services.apple.auth_audiences', ''));
        $parts = preg_split('/[\s,;]+/', $raw) ?: [];
        $audiences = [];
        foreach ($parts as $part) {
            $value = trim((string) $part);
            if ($value !== '') {
                $audiences[] = $value;
            }
        }

        if ($audiences === []) {
            $single = trim((string) config('services.apple.client_id', ''));
            if ($single !== '') {
                $audiences[] = $single;
            }
        }

        return array_values(array_unique($audiences));
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function applePublicKeys(): array
    {
        return Cache::remember('mobile_apple_signin_keys', now()->addHours(6), function (): array {
            $response = Http::timeout(8)->get('https://appleid.apple.com/auth/keys');
            if (! $response->successful()) {
                return [];
            }

            $body = $response->json();
            if (! is_array($body) || ! isset($body['keys']) || ! is_array($body['keys'])) {
                return [];
            }

            $keys = [];
            foreach ($body['keys'] as $item) {
                if (is_array($item)) {
                    $keys[] = $item;
                }
            }

            return $keys;
        });
    }

    /**
     * @return array<string, mixed>|null
     */
    private function decodeJwtSegmentAsArray(string $segment): ?array
    {
        $json = $this->base64UrlDecode($segment);
        if ($json === null) {
            return null;
        }

        $decoded = json_decode($json, true);
        return is_array($decoded) ? $decoded : null;
    }

    private function base64UrlDecode(string $input): ?string
    {
        $normalized = strtr($input, '-_', '+/');
        $padding = strlen($normalized) % 4;
        if ($padding !== 0) {
            $normalized .= str_repeat('=', 4 - $padding);
        }
        $decoded = base64_decode($normalized, true);

        return $decoded === false ? null : $decoded;
    }

    /**
     * @param  array<string, mixed>  $jwk
     */
    private function jwkToPem(array $jwk): ?string
    {
        if (($jwk['kty'] ?? '') !== 'RSA') {
            return null;
        }

        $modulus = $this->base64UrlDecode((string) ($jwk['n'] ?? ''));
        $exponent = $this->base64UrlDecode((string) ($jwk['e'] ?? ''));
        if ($modulus === null || $exponent === null || $modulus === '' || $exponent === '') {
            return null;
        }

        $modulusInteger = $this->asn1Integer($modulus);
        $exponentInteger = $this->asn1Integer($exponent);
        $rsaPublicKey = $this->asn1Sequence($modulusInteger.$exponentInteger);

        $algorithmIdentifier = hex2bin('300d06092a864886f70d0101010500');
        if ($algorithmIdentifier === false) {
            return null;
        }
        $bitString = "\x03".$this->asn1Length(strlen($rsaPublicKey) + 1)."\x00".$rsaPublicKey;
        $subjectPublicKeyInfo = $this->asn1Sequence($algorithmIdentifier.$bitString);

        $pem = chunk_split(base64_encode($subjectPublicKeyInfo), 64, "\n");

        return "-----BEGIN PUBLIC KEY-----\n".$pem."-----END PUBLIC KEY-----\n";
    }

    private function asn1Sequence(string $value): string
    {
        return "\x30".$this->asn1Length(strlen($value)).$value;
    }

    private function asn1Integer(string $value): string
    {
        if ($value === '') {
            $value = "\x00";
        }
        if ((ord($value[0]) & 0x80) !== 0) {
            $value = "\x00".$value;
        }

        return "\x02".$this->asn1Length(strlen($value)).$value;
    }

    private function asn1Length(int $length): string
    {
        if ($length < 128) {
            return chr($length);
        }

        $temp = '';
        while ($length > 0) {
            $temp = chr($length & 0xFF).$temp;
            $length >>= 8;
        }

        return chr(0x80 | strlen($temp)).$temp;
    }

    private function nameFromEmail(string $email): string
    {
        $base = trim((string) explode('@', trim($email))[0]);
        if ($base === '') {
            return 'BoardMaster User';
        }

        return ucfirst($base);
    }

    /**
     * @return array<int, string>
     */
    private function nameValidationRules(): array
    {
        return [
            'required',
            'string',
            'max:255',
            'regex:/^\s*\S+(?:\s+\S+)+\s*$/',
        ];
    }

    /**
     * @return array<int, string>
     */
    private function phoneValidationRules(): array
    {
        return [
            'required',
            'string',
            'max:25',
            'regex:/^(?:09\d{9}|\+639\d{9}|639\d{9})$/',
        ];
    }

    private function normalizePhilippinePhone(string $value): string
    {
        $digits = preg_replace('/\D+/', '', $value) ?? '';
        if (str_starts_with($digits, '63') && strlen($digits) === 12) {
            return '0'.substr($digits, 2);
        }
        if (str_starts_with($digits, '09') && strlen($digits) === 11) {
            return $digits;
        }

        return trim($value);
    }

    private function isGoogleOriginUser(User $user): bool
    {
        if (! Schema::hasColumn('users', 'google_id')) {
            return false;
        }

        return trim((string) ($user->google_id ?? '')) !== '';
    }

    private function isAppleOriginUser(User $user): bool
    {
        if (! Schema::hasColumn('users', 'apple_id')) {
            return false;
        }

        return trim((string) ($user->apple_id ?? '')) !== '';
    }

    private function isSocialOriginUser(User $user): bool
    {
        return $this->isGoogleOriginUser($user) || $this->isAppleOriginUser($user);
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
            'mobile_demo_seen' => $user->mobile_demo_seen_at !== null,
            'mobile_demo_seen_at' => $user->mobile_demo_seen_at?->toDateTimeString(),
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
