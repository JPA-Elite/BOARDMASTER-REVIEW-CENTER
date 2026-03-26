<?php

namespace App\Services\Admin;

use App\Models\Plan;
use App\Models\UserSubscription;
use App\Repositories\Contracts\PlanRepositoryInterface;
use App\Repositories\Contracts\UserRepositoryInterface;
use App\Repositories\Contracts\UserSubscriptionRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Carbon;
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
            'billing_cycle' => $subscription->billing_cycle,
            'billing_label' => $this->billingLabel((string) $subscription->billing_cycle),
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
            ->select('id', 'name', 'sub_plan_label', 'price', 'billing_cycle', 'sort_order')
            ->orderBy('sort_order')
            ->orderBy('price')
            ->orderBy('name')
            ->get();
    }

    public function create(array $data): UserSubscription
    {
        return $this->subscriptions->create($this->preparePayload($data));
    }

    public function update(UserSubscription $subscription, array $data): UserSubscription
    {
        return $this->subscriptions->update($subscription, $this->preparePayload($data));
    }

    public function delete(UserSubscription $subscription): void
    {
        $this->subscriptions->delete($subscription);
    }

    private function preparePayload(array $data): array
    {
        $plan = $this->plans->find((int) $data['plan_id']);
        $billingCycle = $this->resolveBillingCycle($data);
        $startDate = Carbon::parse($data['start_date']);
        $endDate = $data['end_date'] ?? null;

        if (! $endDate) {
            $durationDays = $plan instanceof Plan
                ? $plan->durationDays()
                : Plan::durationDaysForCycle($billingCycle);
            $endDate = $startDate->copy()->addDays(max(1, $durationDays))->toDateString();
        }

        return [
            'user_id' => $data['user_id'],
            'plan_id' => $data['plan_id'],
            'billing_cycle' => $billingCycle,
            'start_date' => $startDate->toDateString(),
            'end_date' => $endDate,
            'status' => $data['status'],
        ];
    }

    private function resolveBillingCycle(array $data): string
    {
        $requestedCycle = $data['billing_cycle'] ?? null;
        if (is_string($requestedCycle) && in_array($requestedCycle, Plan::billingCycles(), true)) {
            return $requestedCycle;
        }

        $plan = $this->plans->find((int) $data['plan_id']);
        if ($plan instanceof Plan && in_array($plan->billing_cycle, Plan::billingCycles(), true)) {
            return $plan->billing_cycle;
        }

        return Plan::BILLING_MONTHLY;
    }

    private function billingLabel(string $billingCycle): string
    {
        return match ($billingCycle) {
            Plan::BILLING_TRIAL => '3 days',
            Plan::BILLING_QUARTERLY => '3 months',
            Plan::BILLING_SEMI_ANNUAL => '6 months',
            Plan::BILLING_ANNUAL, Plan::BILLING_YEARLY => '12 months',
            Plan::BILLING_MONTHLY => '1 month',
            default => 'Per cycle',
        };
    }
}
