<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\Admin\UserService;
use App\Services\FileUploadService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class UserController extends Controller
{
    public function __construct(
        private readonly UserService $users,
        private readonly FileUploadService $uploads,
    ) {
    }

    public function index(Request $request): Response
    {
        $search = trim((string) $request->string('search'));
        $perPage = (int) $request->integer('per_page', 15);
        $perPage = in_array($perPage, [10, 15, 25, 50, 100], true) ? $perPage : 15;
        $users = $this->users->paginate($search, $perPage);

        return Inertia::render('Admin/Users/Index', [
            'filters' => [
                'search' => $search,
                'per_page' => $perPage,
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
        $maxFileKb = $this->uploads->maxFileKilobytes();
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', 'string', 'confirmed', 'min:8'],
            'role' => ['required', Rule::in(['admin', 'user'])],
            'referral_code' => ['nullable', 'string', 'max:32', 'unique:users,referral_code'],
            'referred_by' => ['nullable', 'integer', 'exists:users,id'],
            'avatar' => ['nullable', 'image', "max:$maxFileKb"],
        ]);

        if ($request->hasFile('avatar')) {
            $validated['avatar_path'] = $this->uploads->store(
                $request->file('avatar'),
                'avatars'
            );
        }

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
                'avatar_url' => $user->avatar_url,
                'referral_code' => $user->referral_code,
                'referred_by' => $user->referred_by,
            ],
        ]);
    }

    public function update(Request $request, User $user): RedirectResponse
    {
        $maxFileKb = $this->uploads->maxFileKilobytes();
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', Rule::unique('users', 'email')->ignore($user->id)],
            'password' => ['nullable', 'string', 'confirmed', 'min:8'],
            'role' => ['required', Rule::in(['admin', 'user'])],
            'referral_code' => ['nullable', 'string', 'max:32', Rule::unique('users', 'referral_code')->ignore($user->id)],
            'referred_by' => ['nullable', 'integer', 'exists:users,id', Rule::notIn([$user->id])],
            'avatar' => ['nullable', 'image', "max:$maxFileKb"],
        ]);

        if ($request->hasFile('avatar')) {
            $previousAvatar = $user->avatar_path;
            $validated['avatar_path'] = $this->uploads->store(
                $request->file('avatar'),
                'avatars'
            );
            $this->uploads->delete($previousAvatar);
        }

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
