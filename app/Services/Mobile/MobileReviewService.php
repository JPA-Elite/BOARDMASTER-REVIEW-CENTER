<?php

namespace App\Services\Mobile;

use App\Models\User;
use App\Repositories\Contracts\ReviewRepositoryInterface;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class MobileReviewService
{
    public function __construct(private readonly ReviewRepositoryInterface $reviews)
    {
    }

    public function store(Request $request): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();

        $validated = $request->validate([
            'rating' => ['required', 'integer', 'min:1', 'max:5'],
            'comment' => ['required', 'string', 'min:10', 'max:500'],
        ]);

        $comment = trim($validated['comment']);
        if (mb_strlen($comment) < 10) {
            return response()->json([
                'message' => 'Please share at least 10 characters.',
            ], 422);
        }

        $review = $this->reviews->create([
            'user_id' => $user->id,
            'quote' => $comment,
            'rating' => $validated['rating'],
            'is_active' => false,
            'sort_order' => 0,
        ]);

        return response()->json([
            'data' => [
                'id' => $review->id,
                'status' => 'pending',
            ],
            'message' => 'Thanks for your feedback! It will be visible once approved.',
        ], 201);
    }
}
