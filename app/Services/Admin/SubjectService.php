<?php

namespace App\Services\Admin;

use App\Models\Subject;
use App\Repositories\Contracts\SubjectRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

class SubjectService
{
    public function __construct(private readonly SubjectRepositoryInterface $subjects)
    {
    }

    public function paginate(int $perPage = 15, ?string $search = null): LengthAwarePaginator
    {
        $subjects = $this->subjects->paginateWithCounts($perPage, $search);

        $subjects->through(fn (Subject $subject) => [
            'id' => $subject->id,
            'name' => $subject->name,
            'questions_count' => $subject->questions_count,
            'quiz_attempts_count' => $subject->quiz_attempts_count,
            'created_at' => $subject->created_at?->format('M d, Y'),
        ]);

        return $subjects;
    }

    public function listAll(): Collection
    {
        return $this->subjects->listAll();
    }

    public function create(array $data): Subject
    {
        return $this->subjects->create($data);
    }

    public function update(Subject $subject, array $data): Subject
    {
        return $this->subjects->update($subject, $data);
    }

    public function delete(Subject $subject): void
    {
        $this->subjects->delete($subject);
    }
}
