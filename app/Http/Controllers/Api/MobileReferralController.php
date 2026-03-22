<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Referral;
use App\Models\ReferralOffer;
use App\Models\User;
use App\Services\ReferralRewardsService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class MobileReferralController extends Controller
{
    public function index(Request $request, ReferralRewardsService $rewards): JsonResponse
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

        $earnedPoints = $rewards->earnedPoints($user);
        $spentPoints = $rewards->spentPoints($user);
        $availablePoints = max(0, $earnedPoints - $spentPoints);
        $pointsPerReferral = $rewards->pointsPerReferral();
        $offers = $rewards->availableOffers();
        $categories = $offers
            ->pluck('category')
            ->filter()
            ->unique()
            ->values()
            ->all();
        $brands = $offers
            ->pluck('brand')
            ->filter()
            ->unique()
            ->values()
            ->all();
        $activeRewards = $rewards->activeRedemptions($user);

        return response()->json([
            'data' => [
                'referral_code' => $user->referral_code,
                'referred_by' => $user->referrer?->only(['id', 'name', 'email']),
                'join_count' => $referrals->total(),
                'points' => [
                    'earned' => $earnedPoints,
                    'spent' => $spentPoints,
                    'available' => $availablePoints,
                    'per_referral' => $pointsPerReferral,
                ],
                'offers' => $offers->map(fn (ReferralOffer $offer) => [
                    'id' => $offer->id,
                    'title' => $offer->title,
                    'description' => $offer->description,
                    'points_cost' => $offer->points_cost,
                    'subject' => $offer->subject?->name,
                    'subject_id' => $offer->subject_id,
                    'question_limit' => $offer->question_limit,
                    'access_duration_days' => $offer->access_duration_days,
                    'category' => $offer->category,
                    'brand' => $offer->brand,
                    'image_url' => $offer->image_url,
                    'is_featured' => $offer->is_featured,
                ])->values(),
                'categories' => $categories,
                'brands' => $brands,
                'active_rewards' => $activeRewards->map(fn ($reward) => [
                    'id' => $reward->id,
                    'offer_id' => $reward->referral_offer_id,
                    'subject_id' => $reward->subject_id,
                    'question_limit' => $reward->question_limit,
                    'expires_at' => $reward->expires_at?->toDateTimeString(),
                ])->values(),
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

    public function redeem(Request $request, ReferralRewardsService $rewards): JsonResponse
    {
        $validated = $request->validate([
            'offer_id' => ['required', 'integer', 'exists:referral_offers,id'],
        ]);

        /** @var User $user */
        $user = $request->user();
        $offer = ReferralOffer::query()->findOrFail($validated['offer_id']);

        try {
            $redemption = $rewards->redeem($user, $offer);
        } catch (\RuntimeException $exception) {
            return response()->json([
                'message' => $exception->getMessage(),
            ], 422);
        }

        return response()->json([
            'message' => 'Offer redeemed successfully.',
            'data' => [
                'id' => $redemption->id,
                'offer_id' => $redemption->referral_offer_id,
                'subject_id' => $redemption->subject_id,
                'question_limit' => $redemption->question_limit,
                'expires_at' => $redemption->expires_at?->toDateTimeString(),
            ],
            'points' => [
                'earned' => $rewards->earnedPoints($user),
                'spent' => $rewards->spentPoints($user),
                'available' => $rewards->availablePoints($user),
            ],
        ]);
    }
}
