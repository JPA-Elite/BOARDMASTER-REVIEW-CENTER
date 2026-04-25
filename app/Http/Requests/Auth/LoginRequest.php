<?php

namespace App\Http\Requests\Auth;

use Illuminate\Auth\Events\Lockout;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class LoginRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'email' => ['required', 'string', 'email'],
            'password' => ['required', 'string'],
        ];
    }

    /**
     * Attempt to authenticate the request's credentials.
     *
     * @throws ValidationException
     */
    public function authenticate(): void
    {
        $this->ensureIsNotRateLimited();

        if (! Auth::attempt($this->only('email', 'password'), $this->boolean('remember'))) {
            // Hit both email-based and IP-based rate limiters on failed attempt
            RateLimiter::hit($this->throttleKey(), 60); // 1 minute decay
            RateLimiter::hit($this->ipThrottleKey(), 60); // 1 minute decay

            throw ValidationException::withMessages([
                'email' => trans('auth.failed'),
            ]);
        }

        // Clear rate limits on successful login
        RateLimiter::clear($this->throttleKey());
        RateLimiter::clear($this->ipThrottleKey());
    }

    /**
     * Ensure the login request is not rate limited.
     *
     * @throws ValidationException
     */
    public function ensureIsNotRateLimited(): void
    {
        $email = strtolower($this->string('email'));
        $loginKey = 'login|'.$email;
        $ipKey = 'login-ip|'.$this->ip();

        // Check both email-based and IP-based rate limits (5 attempts max)
        $tooManyEmailAttempts = RateLimiter::tooManyAttempts($loginKey, 5);
        $tooManyIpAttempts = RateLimiter::tooManyAttempts($ipKey, 5);

        if (!$tooManyEmailAttempts && !$tooManyIpAttempts) {
            return;
        }

        event(new Lockout($this));

        // Calculate remaining seconds until unlock (minimum of both limits)
        $emailSeconds = $tooManyEmailAttempts ? RateLimiter::availableIn($loginKey) : 0;
        $ipSeconds = $tooManyIpAttempts ? RateLimiter::availableIn($ipKey) : 0;
        $seconds = max($emailSeconds, $ipSeconds);

        throw ValidationException::withMessages([
            'email' => trans('auth.throttle', [
                'seconds' => $seconds,
                'minutes' => ceil($seconds / 60),
            ]),
        ]);
    }

    /**
     * Get the rate limiting throttle key for the request (per email).
     */
    public function throttleKey(): string
    {
        return 'login|'.Str::transliterate(Str::lower($this->string('email')));
    }

    /**
     * Get the IP-based rate limiting throttle key for the request.
     */
    public function ipThrottleKey(): string
    {
        return 'login-ip|'.$this->ip();
    }
}