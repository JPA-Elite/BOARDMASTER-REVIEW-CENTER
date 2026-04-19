<?php

namespace App\Repositories\Contracts;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

interface FaqRepositoryInterface extends RepositoryInterface
{
    public function paginate(int $perPage = 10, ?string $search = null): LengthAwarePaginator;

    public function listActive(): Collection;

    public function paginateActive(int $perPage = 6): LengthAwarePaginator;
}
