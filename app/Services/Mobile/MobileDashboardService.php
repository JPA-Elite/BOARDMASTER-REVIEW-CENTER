<?php

namespace App\Services\Mobile;

use App\Models\QuizAttempt;
use App\Models\User;
use App\Repositories\Contracts\QuizAttemptRepositoryInterface;
use App\Repositories\Contracts\ReferralRepositoryInterface;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class MobileDashboardService
{
    public function __construct(
        private readonly ReferralRepositoryInterface $referrals,
        private readonly QuizAttemptRepositoryInterface $quizAttempts,
    ) {
    }

    public function metrics(Request $request): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();

        $referralCount = $this->referrals->query()
            ->where('inviter_id', $user->id)
            ->count();

        /** @var QuizAttempt|null $lastAttempt */
        $lastAttempt = $this->quizAttempts->query()
            ->with('subject:id,name')
            ->where('user_id', $user->id)
            ->orderByDesc('id')
            ->first();

        return response()->json([
            'data' => [
                'referral_join_count' => $referralCount,
                'last_attempt' => $lastAttempt ? [
                    'score' => $lastAttempt->score,
                    'total_questions' => $lastAttempt->total_questions,
                    'subject' => $lastAttempt->subject?->name,
                    'completed_at' => $lastAttempt->created_at?->toDateTimeString(),
                ] : null,
            ],
        ]);
    }
}
