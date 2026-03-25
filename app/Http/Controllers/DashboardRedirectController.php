<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;

class DashboardRedirectController extends Controller
{
    public function __invoke(): RedirectResponse
    {
        $user = auth()->user();

        if ($user?->role === 'admin') {
            return redirect()->route('admin.dashboard');
        }

        return redirect('/');
    }
}
