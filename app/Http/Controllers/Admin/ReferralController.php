<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Referral;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class ReferralController extends Controller
{
    public function index(): Response
    {
        $referrals = Referral::query()
            ->with(['inviter:id,name,email', 'invitedUser:id,name,email'])
            ->latest()
            ->paginate(15)
            ->through(fn (Referral $referral) => [
                'id' => $referral->id,
                'inviter' => $referral->inviter?->name,
                'inviter_email' => $referral->inviter?->email,
                'invited_user' => $referral->invitedUser?->name,
                'invited_email' => $referral->invitedUser?->email,
                'created_at' => $referral->created_at?->format('M d, Y'),
            ]);

        return Inertia::render('Admin/Referrals/Index', [
            'referrals' => $referrals,
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('Admin/Referrals/Form', [
            'mode' => 'create',
            'referral' => null,
            'users' => $this->usersList(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'inviter_id' => ['required', 'integer', 'exists:users,id'],
            'invited_user_id' => ['required', 'integer', 'exists:users,id', 'different:inviter_id', 'unique:referrals,invited_user_id'],
        ]);

        $referral = Referral::create($validated);

        User::query()
            ->whereKey($referral->invited_user_id)
            ->update(['referred_by' => $referral->inviter_id]);

        return redirect()
            ->route('admin.referrals.index')
            ->with('success', 'Referral created successfully.');
    }

    public function edit(Referral $referral): Response
    {
        return Inertia::render('Admin/Referrals/Form', [
            'mode' => 'edit',
            'users' => $this->usersList(),
            'referral' => [
                'id' => $referral->id,
                'inviter_id' => $referral->inviter_id,
                'invited_user_id' => $referral->invited_user_id,
            ],
        ]);
    }

    public function update(Request $request, Referral $referral): RedirectResponse
    {
        $validated = $request->validate([
            'inviter_id' => ['required', 'integer', 'exists:users,id'],
            'invited_user_id' => [
                'required',
                'integer',
                'exists:users,id',
                'different:inviter_id',
                Rule::unique('referrals', 'invited_user_id')->ignore($referral->id),
            ],
        ]);

        $referral->update($validated);

        User::query()
            ->whereKey($referral->invited_user_id)
            ->update(['referred_by' => $referral->inviter_id]);

        return redirect()
            ->route('admin.referrals.index')
            ->with('success', 'Referral updated successfully.');
    }

    public function destroy(Referral $referral): RedirectResponse
    {
        User::query()
            ->whereKey($referral->invited_user_id)
            ->where('referred_by', $referral->inviter_id)
            ->update(['referred_by' => null]);

        $referral->delete();

        return redirect()
            ->route('admin.referrals.index')
            ->with('success', 'Referral deleted successfully.');
    }

    private function usersList()
    {
        return User::query()
            ->select('id', 'name', 'email')
            ->orderBy('name')
            ->get();
    }
}
