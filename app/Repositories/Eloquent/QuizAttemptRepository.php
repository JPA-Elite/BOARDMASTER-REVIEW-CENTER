<?php

namespace App\Repositories\Eloquent;

use App\Models\QuizAttempt;
use App\Repositories\Contracts\QuizAttemptRepositoryInterface;
use DateTimeInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

class QuizAttemptRepository extends AbstractRepository implements QuizAttemptRepositoryInterface
{
    public function __construct(QuizAttempt $model)
    {
        parent::__construct($model);
    }

    public function paginateWithRelations(int $perPage = 15): LengthAwarePaginator
    {
        return $this->query()
            ->with(['user:id,name,email', 'subject:id,name'])
            ->withCount('answers')
            ->latest()
            ->paginate($perPage);
    }

    public function getRecent(int $limit = 6): Collection
    {
        return $this->query()
            ->with(['user:id,name', 'subject:id,name'])
            ->latest()
            ->limit($limit)
            ->get();
    }

    public function countSince(DateTimeInterface $since): int
    {
        return $this->query()
            ->where('created_at', '>=', $since)
            ->count();
    }

    public function loadForShow(QuizAttempt $attempt): QuizAttempt
    {
        return $attempt->load([
            'user:id,name,email',
            'subject:id,name',
            'answers.question:id,question',
            'answers.selectedChoice:id,choice_label,choice_text',
        ]);
    }
}
