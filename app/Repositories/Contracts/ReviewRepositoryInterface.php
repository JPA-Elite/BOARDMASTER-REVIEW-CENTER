<?php

namespace App\Repositories\Contracts;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

interface ReviewRepositoryInterface extends RepositoryInterface
{
    public function paginate(?string $search = null, int $perPage = 10): LengthAwarePaginator;

    public function paginateUserReviews(int $perPage = 10): LengthAwarePaginator;

    public function listActive(?int $limit = null): Collection;
}
