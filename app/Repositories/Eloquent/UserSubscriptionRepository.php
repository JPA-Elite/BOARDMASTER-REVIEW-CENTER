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

    public function paginateWithRelations(int $perPage = 15, ?string $search = null): LengthAwarePaginator
    {
        $term = trim((string) $search);

        return $this->query()
            ->with(['user:id,name,email', 'plan:id,name'])
            ->when($term, function ($query) use ($term): void {
                $query->where(function ($nested) use ($term): void {
                    $nested->where('status', 'like', "%{$term}%")
                        ->orWhere('billing_cycle', 'like', "%{$term}%")
                        ->orWhereHas('user', function ($userQuery) use ($term): void {
                            $userQuery->where('name', 'like', "%{$term}%")
                                ->orWhere('email', 'like', "%{$term}%");
                        })
                        ->orWhereHas('plan', function ($planQuery) use ($term): void {
                            $planQuery->where('name', 'like', "%{$term}%");
                        });
                });
            })
            ->latest()
            ->paginate($perPage)
            ->withQueryString();
    }

    public function countActive(): int
    {
        return $this->query()
            ->where('status', 'active')
            ->count();
    }
}
