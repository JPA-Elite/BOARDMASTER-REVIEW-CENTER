<?php

namespace App\Repositories\Contracts;

use DateTimeInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

interface QuestionRepositoryInterface extends RepositoryInterface
{
    public function paginateWithRelations(int $perPage = 15): LengthAwarePaginator;

    public function countBetween(DateTimeInterface $start, DateTimeInterface $end): int;

    public function countAll(): int;
}
