<?php

namespace App\Repositories\Eloquent;

use App\Models\Subject;
use App\Repositories\Contracts\SubjectRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

class SubjectRepository extends AbstractRepository implements SubjectRepositoryInterface
{
    public function __construct(Subject $model)
    {
        parent::__construct($model);
    }

    public function paginateWithCounts(int $perPage = 15): LengthAwarePaginator
    {
        return $this->query()
            ->withCount(['questions', 'quizAttempts'])
            ->latest()
            ->paginate($perPage)
            ->withQueryString();
    }

    public function listAll(): Collection
    {
        return $this->query()
            ->select('id', 'name')
            ->orderBy('name')
            ->get();
    }
}
