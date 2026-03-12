<?php

namespace App\Repositories\Eloquent;

use App\Models\UserSubscription;
use App\Repositories\Contracts\UserSubscriptionRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class UserSubscriptionRepository extends AbstractRepository implements UserSubscriptionRepositoryInterface
{
    public function __construct(UserSubscription $model)
    {
        parent::__construct($model);
    }

    public function paginateWithRelations(int $perPage = 15): LengthAwarePaginator
    {
        return $this->query()
            ->with(['user:id,name,email', 'plan:id,name'])
            ->latest()
            ->paginate($perPage);
    }

    public function countActive(): int
    {
        return $this->query()
            ->where('status', 'active')
            ->count();
    }
}
