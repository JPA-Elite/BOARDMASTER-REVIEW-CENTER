<?php

namespace App\Repositories\Contracts;

use DateTimeInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

interface UserRepositoryInterface extends RepositoryInterface
{
    public function paginateWithFilters(?string $search, int $perPage = 15): LengthAwarePaginator;

    public function listReferrers(?int $excludeId = null): Collection;

    public function listAdmins(): Collection;

    public function countBetween(DateTimeInterface $start, DateTimeInterface $end): int;

    public function countAll(): int;
}
