<?php

namespace App\Services\Admin;

use App\Models\Review;
use App\Repositories\Contracts\ReviewRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class ReviewService
{
    public function __construct(private readonly ReviewRepositoryInterface $reviews)
    {
    }

    public function paginate(?string $search = null, int $perPage = 10): LengthAwarePaginator
    {
        $reviews = $this->reviews->paginate($search, $perPage);

        $reviews->through(fn (Review $review) => [
            'id' => $review->id,
            'reviewer_name' => $review->user?->name ?? 'Reviewer',
            'reviewer_school' => $review->user?->school,
            'quote' => $review->quote,
            'rating' => $review->rating ?? 5,
            'is_active' => $review->is_active,
            'sort_order' => $review->sort_order,
            'created_at' => $review->created_at?->format('M d, Y'),
        ]);

        return $reviews;
    }

    public function delete(Review $review): void
    {
        $this->reviews->delete($review);
    }

    public function setActive(Review $review, bool $isActive): void
    {
        $this->reviews->update($review, ['is_active' => $isActive]);
    }
}
