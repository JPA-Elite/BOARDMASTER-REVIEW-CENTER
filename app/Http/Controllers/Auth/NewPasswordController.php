<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules\Password as PasswordRule;
use Illuminate\Validation\ValidationException;
use Illuminate\Contracts\View\View;

class NewPasswordController extends Controller
{
    /**
     * Display the password reset view.
     */
    public function create(Request $request): View
    {
        $email = (string) $request->query('email', '');
        $token = (string) $request->route('token');
        $user = $email !== '' ? User::query()->where('email', $email)->first() : null;
        $expired = ! ($user && $token !== '' && Password::broker()->tokenExists($user, $token));

        return view('auth.reset-password', [
            'email' => $email,
            'token' => $token,
            'expired' => $expired,
        ]);
    }

    /**
     * Handle an incoming new password request.
     *
     * @throws ValidationException
     */
    public function store(Request $request): RedirectResponse
    {
        $request->validate([
            'token' => 'required',
            'email' => 'required|email',
            'password' => ['required', 'confirmed', PasswordRule::min(8)->mixedCase()->numbers()->symbols()],
        ]);

        // Here we will attempt to reset the user's password. If it is successful we
        // will update the password on an actual user model and persist it to the
        // database. Otherwise we will parse the error and return the response.
        $status = Password::reset(
            $request->only('email', 'password', 'password_confirmation', 'token'),
            function ($user) use ($request) {
                $user->forceFill([
                    'password' => Hash::make($request->password),
                    'remember_token' => Str::random(60),
                ])->save();

                event(new PasswordReset($user));
            }
        );

        // If the password was successfully reset, we will redirect the user back to
        // the application's home authenticated view. If there is an error we can
        // redirect them back to where they came from with their error message.
        if ($status == Password::PASSWORD_RESET) {
            return redirect()->route('password.reset.success', [
                'token' => $request->input('token'),
                'email' => $request->input('email'),
            ]);
        }

        if ($status == Password::INVALID_TOKEN) {
            return redirect()
                ->route('password.reset', [
                    'token' => $request->input('token'),
                    'email' => $request->input('email'),
                ])
                ->with('error', 'This reset password link has expired or was already used.');
        }

        throw ValidationException::withMessages([
            'email' => [trans($status)],
        ]);
    }

    /**
     * Display the password reset success view.
     */
    public function success(Request $request): View
    {
        return view('auth.reset-password-success', [
            'email' => $request->query('email'),
        ]);
    }
}
