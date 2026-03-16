<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Referral;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class MobileReferralController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();

        if (! $user->referral_code) {
            $user->referral_code = User::generateReferralCode();
            $user->save();
        }

        $referrals = Referral::query()
            ->with('invitedUser:id,name,email')
            ->where('inviter_id', $user->id)
            ->latest()
            ->paginate(10);

        return response()->json([
            'data' => [
                'referral_code' => $user->referral_code,
                'referred_by' => $user->referrer?->only(['id', 'name', 'email']),
                'join_count' => $referrals->total(),
                'referrals' => collect($referrals->items())->map(fn (Referral $referral) => [
                    'id' => $referral->id,
                    'invited_user' => $referral->invitedUser
                        ? [
                            'id' => $referral->invitedUser->id,
                            'name' => $referral->invitedUser->name,
                            'email' => $referral->invitedUser->email,
                        ]
                        : null,
                    'created_at' => $referral->created_at?->toDateTimeString(),
                ])->values(),
            ],
            'pagination' => [
                'current_page' => $referrals->currentPage(),
                'last_page' => $referrals->lastPage(),
                'next_page_url' => $referrals->nextPageUrl(),
                'has_more' => $referrals->hasMorePages(),
            ],
        ]);
    }

    public function apply(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'referral_code' => ['required', 'string', 'max:32'],
        ]);

        /** @var User $user */
        $user = $request->user();

        if ($user->referred_by) {
            return response()->json([
                'message' => 'You already used a referral code.',
            ], 422);
        }

        $inviter = User::query()
            ->where('referral_code', $validated['referral_code'])
            ->first();

        if (! $inviter) {
            return response()->json([
                'message' => 'Referral code not found.',
            ], 422);
        }

        if ($inviter->id === $user->id) {
            return response()->json([
                'message' => 'You cannot use your own referral code.',
            ], 422);
        }

        $alreadyReferred = Referral::query()
            ->where('invited_user_id', $user->id)
            ->exists();

        if ($alreadyReferred) {
            return response()->json([
                'message' => 'You already have a referral record.',
            ], 422);
        }

        Referral::query()->create([
            'inviter_id' => $inviter->id,
            'invited_user_id' => $user->id,
        ]);

        $user->referred_by = $inviter->id;
        $user->save();

        return response()->json([
            'message' => 'Referral applied successfully.',
        ]);
    }
}
