<?php

namespace App\Services\Admin;

use App\Models\UserSubscription;
use App\Repositories\Contracts\PlanRepositoryInterface;
use App\Repositories\Contracts\UserRepositoryInterface;
use App\Repositories\Contracts\UserSubscriptionRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

class SubscriptionService
{
    public function __construct(
        private readonly UserSubscriptionRepositoryInterface $subscriptions,
        private readonly UserRepositoryInterface $users,
        private readonly PlanRepositoryInterface $plans,
    ) {
    }

    public function paginate(): LengthAwarePaginator
    {
        $subscriptions = $this->subscriptions->paginateWithRelations();

        $subscriptions->through(fn (UserSubscription $subscription) => [
            'id' => $subscription->id,
            'user' => $subscription->user?->name,
            'user_email' => $subscription->user?->email,
            'plan' => $subscription->plan?->name,
            'start_date' => $subscription->start_date?->format('Y-m-d'),
            'end_date' => $subscription->end_date?->format('Y-m-d'),
            'status' => $subscription->status,
            'created_at' => $subscription->created_at?->format('M d, Y'),
        ]);

        return $subscriptions;
    }

    public function listUsers(): Collection
    {
        return $this->users->listReferrers();
    }

    public function listPlans(): Collection
    {
        return $this->plans->query()
            ->select('id', 'name')
            ->orderBy('name')
            ->get();
    }

    public function create(array $data): UserSubscription
    {
        return $this->subscriptions->create($data);
    }

    public function update(UserSubscription $subscription, array $data): UserSubscription
    {
        return $this->subscriptions->update($subscription, $data);
    }

    public function delete(UserSubscription $subscription): void
    {
        $this->subscriptions->delete($subscription);
    }
}
