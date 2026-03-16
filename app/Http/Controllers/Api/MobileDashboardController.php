<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\QuizAttempt;
use App\Models\Referral;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class MobileDashboardController extends Controller
{
    public function metrics(Request $request): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();

        $referralCount = Referral::query()
            ->where('inviter_id', $user->id)
            ->count();

        $lastAttempt = QuizAttempt::query()
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
