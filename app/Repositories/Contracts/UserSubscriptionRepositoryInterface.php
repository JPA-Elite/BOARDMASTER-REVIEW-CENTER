<?php

namespace App\Repositories\Contracts;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;

interface UserSubscriptionRepositoryInterface extends RepositoryInterface
{
    public function paginateWithRelations(int $perPage = 15): LengthAwarePaginator;

    public function countActive(): int;
}
