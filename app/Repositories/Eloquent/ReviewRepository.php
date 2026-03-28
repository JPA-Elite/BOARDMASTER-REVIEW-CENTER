<?php

namespace App\Repositories\Eloquent;

use App\Models\Review;
use App\Repositories\Contracts\ReviewRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

class ReviewRepository extends AbstractRepository implements ReviewRepositoryInterface
{
    public function __construct(Review $model)
    {
        parent::__construct($model);
    }

    public function paginate(int $perPage = 10): LengthAwarePaginator
    {
        return $this->query()
            ->with('user:id,name,school')
            ->orderBy('sort_order')
            ->orderBy('id')
            ->paginate($perPage);
    }

    public function paginateUserReviews(int $perPage = 10): LengthAwarePaginator
    {
        return $this->query()
            ->with('user:id,name,school')
            ->whereNotNull('user_id')
            ->orderBy('sort_order')
            ->orderBy('id')
            ->paginate($perPage);
    }

    public function listActive(?int $limit = null): Collection
    {
        $query = $this->query()
            ->with('user:id,name,school,avatar_path')
            ->where('is_active', true)
            ->whereNotNull('user_id')
            ->orderBy('sort_order')
            ->orderBy('id');

        if ($limit !== null) {
            $query->limit($limit);
        }

        return $query->get(['id', 'user_id', 'quote', 'rating'])->map(
            fn (Review $review) => [
                'id' => $review->id,
                'quote' => $review->quote,
                'reviewer_name' => $review->user?->name ?? 'Reviewer',
                'reviewer_school' => $review->user?->school,
                'avatar_url' => $review->user?->avatar_url,
                'rating' => $review->rating ?? 5,
            ],
        );
    }
}
