<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\Admin\UserService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class UserController extends Controller
{
    public function __construct(private readonly UserService $users)
    {
    }

    public function index(Request $request): Response
    {
        $search = trim((string) $request->string('search'));
        $users = $this->users->paginate($search);

        return Inertia::render('Admin/Users/Index', [
            'filters' => [
                'search' => $search,
            ],
            'users' => $users,
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('Admin/Users/Form', [
            'mode' => 'create',
            'roles' => ['admin', 'user'],
            'referrers' => $this->users->listReferrers(),
            'user' => null,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', 'string', 'confirmed', 'min:8'],
            'role' => ['required', Rule::in(['admin', 'user'])],
            'referral_code' => ['nullable', 'string', 'max:32', 'unique:users,referral_code'],
            'referred_by' => ['nullable', 'integer', 'exists:users,id'],
        ]);

        $this->users->create($validated);

        return redirect()
            ->route('admin.users.index')
            ->with('success', 'User created successfully.');
    }

    public function edit(User $user): Response
    {
        return Inertia::render('Admin/Users/Form', [
            'mode' => 'edit',
            'roles' => ['admin', 'user'],
            'referrers' => $this->users->listReferrers($user->id),
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'role' => $user->role,
                'referral_code' => $user->referral_code,
                'referred_by' => $user->referred_by,
            ],
        ]);
    }

    public function update(Request $request, User $user): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', Rule::unique('users', 'email')->ignore($user->id)],
            'password' => ['nullable', 'string', 'confirmed', 'min:8'],
            'role' => ['required', Rule::in(['admin', 'user'])],
            'referral_code' => ['nullable', 'string', 'max:32', Rule::unique('users', 'referral_code')->ignore($user->id)],
            'referred_by' => ['nullable', 'integer', 'exists:users,id', Rule::notIn([$user->id])],
        ]);

        $this->users->update($user, $validated);

        return redirect()
            ->route('admin.users.index')
            ->with('success', 'User updated successfully.');
    }

    public function destroy(Request $request, User $user): RedirectResponse
    {
        if (! $this->users->delete($user, $request->user()?->id ?? 0)) {
            return back()->with('error', 'You cannot delete your own account from the admin panel.');
        }

        return redirect()
            ->route('admin.users.index')
            ->with('success', 'User deleted successfully.');
    }
}
