<?php

namespace App\Repositories\Eloquent;

use App\Models\Question;
use App\Repositories\Contracts\QuestionRepositoryInterface;
use DateTimeInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class QuestionRepository extends AbstractRepository implements QuestionRepositoryInterface
{
    public function __construct(Question $model)
    {
        parent::__construct($model);
    }

    public function paginateWithRelations(int $perPage = 15): LengthAwarePaginator
    {
        return $this->query()
            ->with(['subject:id,name', 'creator:id,name'])
            ->withCount('choices')
            ->latest()
            ->paginate($perPage);
    }

    public function countBetween(DateTimeInterface $start, DateTimeInterface $end): int
    {
        return $this->query()
            ->whereBetween('created_at', [$start, $end])
            ->count();
    }

    public function countAll(): int
    {
        return $this->query()->count();
    }
}
