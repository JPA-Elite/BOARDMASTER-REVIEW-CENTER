<?php

namespace App\Repositories\Eloquent;

use App\Models\Plan;
use App\Repositories\Contracts\PlanRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class PlanRepository extends AbstractRepository implements PlanRepositoryInterface
{
    public function __construct(Plan $model)
    {
        parent::__construct($model);
    }

    public function paginateWithFeatures(int $perPage = 10): LengthAwarePaginator
    {
        return $this->query()
            ->with('features:id,plan_id,feature_name')
            ->latest()
            ->paginate($perPage);
    }
}
