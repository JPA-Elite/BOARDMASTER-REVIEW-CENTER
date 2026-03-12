<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Referral;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class UserController extends Controller
{
    public function index(Request $request): Response
    {
        $search = trim((string) $request->string('search'));

        $users = User::query()
            ->with('referrer:id,name,email')
            ->when($search, function ($query, string $term): void {
                $query->where(function ($nested) use ($term): void {
                    $nested->where('name', 'like', "%{$term}%")
                        ->orWhere('email', 'like', "%{$term}%")
                        ->orWhere('referral_code', 'like', "%{$term}%");
                });
            })
            ->latest()
            ->paginate(15)
            ->withQueryString()
            ->through(fn (User $user) => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'role' => $user->role,
                'referral_code' => $user->referral_code,
                'referred_by' => $user->referrer?->name,
                'created_at' => $user->created_at?->format('M d, Y'),
            ]);

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
            'referrers' => User::query()
                ->select('id', 'name', 'email')
                ->orderBy('name')
                ->get(),
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

        $user = User::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'password' => Hash::make($validated['password']),
            'role' => $validated['role'],
            'referral_code' => $validated['referral_code'] ?? null,
            'referred_by' => $validated['referred_by'] ?? null,
        ]);

        $this->syncReferral($user, $validated['referred_by'] ?? null);

        return redirect()
            ->route('admin.users.index')
            ->with('success', 'User created successfully.');
    }

    public function edit(User $user): Response
    {
        return Inertia::render('Admin/Users/Form', [
            'mode' => 'edit',
            'roles' => ['admin', 'user'],
            'referrers' => User::query()
                ->whereKeyNot($user->id)
                ->select('id', 'name', 'email')
                ->orderBy('name')
                ->get(),
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

        $payload = [
            'name' => $validated['name'],
            'email' => $validated['email'],
            'role' => $validated['role'],
            'referral_code' => $validated['referral_code'] ?? null,
            'referred_by' => $validated['referred_by'] ?? null,
        ];

        if (! empty($validated['password'])) {
            $payload['password'] = Hash::make($validated['password']);
        }

        $user->update($payload);
        $this->syncReferral($user, $validated['referred_by'] ?? null);

        return redirect()
            ->route('admin.users.index')
            ->with('success', 'User updated successfully.');
    }

    public function destroy(Request $request, User $user): RedirectResponse
    {
        if ($request->user()?->id === $user->id) {
            return back()->with('error', 'You cannot delete your own account from the admin panel.');
        }

        $user->delete();

        return redirect()
            ->route('admin.users.index')
            ->with('success', 'User deleted successfully.');
    }

    private function syncReferral(User $user, ?int $inviterId): void
    {
        if (! $inviterId) {
            Referral::query()->where('invited_user_id', $user->id)->delete();

            return;
        }

        Referral::query()->updateOrCreate(
            ['invited_user_id' => $user->id],
            ['inviter_id' => $inviterId]
        );
    }
}
