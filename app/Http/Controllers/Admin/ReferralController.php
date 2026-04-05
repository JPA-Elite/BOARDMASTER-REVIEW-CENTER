<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Referral;
use App\Services\Admin\ReferralService;
use App\Services\Admin\UserService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class ReferralController extends Controller
{
    public function __construct(
        private readonly ReferralService $referrals,
        private readonly UserService $users,
    ) {
    }

    public function index(Request $request): Response
    {
        $perPage = (int) $request->integer('per_page', 15);
        $perPage = in_array($perPage, [10, 15, 25, 50, 100], true) ? $perPage : 15;
        $referrals = $this->referrals->paginate($perPage);

        return Inertia::render('Admin/Referrals/Index', [
            'referrals' => $referrals,
            'filters' => [
                'per_page' => $perPage,
            ],
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('Admin/Referrals/Form', [
            'mode' => 'create',
            'referral' => null,
            'users' => $this->users->listReferrers(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'inviter_id' => ['required', 'integer', 'exists:users,id'],
            'invited_user_id' => ['required', 'integer', 'exists:users,id', 'different:inviter_id', 'unique:referrals,invited_user_id'],
        ]);

        $this->referrals->create($validated);

        return redirect()
            ->route('admin.referrals.index')
            ->with('success', 'Referral created successfully.');
    }

    public function edit(Referral $referral): Response
    {
        return Inertia::render('Admin/Referrals/Form', [
            'mode' => 'edit',
            'users' => $this->users->listReferrers(),
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

        $this->referrals->update($referral, $validated);

        return redirect()
            ->route('admin.referrals.index')
            ->with('success', 'Referral updated successfully.');
    }

    public function destroy(Referral $referral): RedirectResponse
    {
        $this->referrals->delete($referral);

        return redirect()
            ->route('admin.referrals.index')
            ->with('success', 'Referral deleted successfully.');
    }
}
