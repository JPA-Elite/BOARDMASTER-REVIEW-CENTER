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

    public function paginateWithCounts(int $perPage = 15, ?string $search = null): LengthAwarePaginator
    {
        $term = trim((string) $search);

        return $this->query()
            ->withCount(['questions', 'quizAttempts'])
            ->when($term, function ($query) use ($term): void {
                $query->where('name', 'like', "%{$term}%");
            })
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
