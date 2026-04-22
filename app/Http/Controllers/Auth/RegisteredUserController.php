<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use App\Models\UserActivity;
use Illuminate\Validation\Rules\Password as PasswordRule;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

class RegisteredUserController extends Controller
{
    /**
     * Display the registration view.
     */
    public function create(): Response
    {
        return Inertia::render('Auth/Register');
    }

    /**
     * Handle an incoming registration request.
     *
     * @throws ValidationException
     */
    public function store(Request $request): RedirectResponse
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|lowercase|email|max:255|unique:'.User::class,
            'password' => ['required', 'confirmed', PasswordRule::min(8)->mixedCase()->numbers()->symbols()],
        ]);

        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
        ]);

        event(new Registered($user));

        Auth::login($user);

        try {
            $platform = $this->detectPlatform($request);
            UserActivity::create([
                'user_id' => $user->id,
                'type' => 'login',
                'platform' => $platform,
                'ip' => $request->ip(),
                'user_agent' => $request->userAgent(),
            ]);
        } catch (\Throwable $e) {
        }

        return redirect(route('dashboard', absolute: false));
    }

    private function detectPlatform(Request $request): string
    {
        $explicit = $request->header('X-Platform') ?? $request->input('platform');
        if (is_string($explicit) && trim($explicit) !== '') {
            return strtolower(trim($explicit));
        }

        if ($request->is('api/*') || $request->wantsJson()) {
            return 'mobile';
        }

        $ua = strtolower((string) $request->userAgent());
        if (preg_match('/mobile|android|iphone|ipad|ipod|windows phone|opera mini|blackberry/', $ua)) {
            return 'mobile';
        }

        return 'web';
    }
}
