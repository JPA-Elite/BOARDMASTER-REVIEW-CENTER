<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;
use Inertia\Response;
use App\Models\UserActivity;

class AuthenticatedSessionController extends Controller
{
    /**
     * Display the login view.
     */
    public function create(): Response
    {
        return Inertia::render('Auth/Login', [
            'canResetPassword' => Route::has('password.request'),
            'status' => session('status'),
        ]);
    }

    /**
     * Handle an incoming authentication request.
     */
    public function store(LoginRequest $request): RedirectResponse
    {
        $request->authenticate();

        $request->session()->regenerate();

        // record web login activity
        try {
            $user = $request->user();
            if ($user) {
                $platform = $this->detectPlatform($request);
                UserActivity::create([
                    'user_id' => $user->id,
                    'type' => 'login',
                    'platform' => $platform,
                    'ip' => $request->ip(),
                    'user_agent' => $request->userAgent(),
                ]);
            }
        } catch (\Throwable $e) {
        }

        return redirect()->intended(route('dashboard', absolute: false));
    }

    /**
     * Destroy an authenticated session.
     */
    public function destroy(Request $request): RedirectResponse
    {
        // record logout activity before logging out
        try {
            $user = $request->user();
            if ($user) {
                $platform = $this->detectPlatform($request);
                UserActivity::create([
                    'user_id' => $user->id,
                    'type' => 'logout',
                    'platform' => $platform,
                    'ip' => $request->ip(),
                    'user_agent' => $request->userAgent(),
                ]);
            }
        } catch (\Throwable $e) {
        }

        Auth::guard('web')->logout();

        $request->session()->invalidate();

        $request->session()->regenerateToken();

        return redirect()->route('login');
    }

    private function detectPlatform(Request $request): string
    {
        // prefer explicit header or input field
        $explicit = $request->header('X-Platform') ?? $request->input('platform');
        if (is_string($explicit) && trim($explicit) !== '') {
            return strtolower(trim($explicit));
        }

        // API routes or JSON requests are considered mobile by default
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
