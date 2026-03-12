<?php

namespace App\Repositories\Contracts;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;

interface PlanRepositoryInterface extends RepositoryInterface
{
    public function paginateWithFeatures(int $perPage = 10): LengthAwarePaginator;
}
