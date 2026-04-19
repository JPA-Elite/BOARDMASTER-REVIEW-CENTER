<?php

namespace App\Repositories\Contracts;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;

interface ReferralRepositoryInterface extends RepositoryInterface
{
    public function paginateWithRelations(int $perPage = 15, ?string $search = null): LengthAwarePaginator;

    public function syncForInvited(int $invitedUserId, ?int $inviterId): void;
}
