<?php

namespace App\Repositories\Contracts;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

interface SubjectRepositoryInterface extends RepositoryInterface
{
    public function paginateWithCounts(int $perPage = 15, ?string $search = null): LengthAwarePaginator;

    public function listAll(): Collection;
}
