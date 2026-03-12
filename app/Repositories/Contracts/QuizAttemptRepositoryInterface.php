<?php

namespace App\Repositories\Contracts;

use App\Models\QuizAttempt;
use DateTimeInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

interface QuizAttemptRepositoryInterface extends RepositoryInterface
{
    public function paginateWithRelations(int $perPage = 15): LengthAwarePaginator;

    public function getRecent(int $limit = 6): Collection;

    public function countSince(DateTimeInterface $since): int;

    public function loadForShow(QuizAttempt $attempt): QuizAttempt;
}
