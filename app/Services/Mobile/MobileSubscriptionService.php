<?php

namespace App\Services\Mobile;

use App\Models\User;
use App\Models\UserSubscription;
use App\Repositories\Contracts\UserSubscriptionRepositoryInterface;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class MobileSubscriptionService
{
    public function __construct(
        private readonly UserSubscriptionRepositoryInterface $subscriptions,
    ) {
    }

    public function history(Request $request): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();

        $subscriptions = $this->subscriptions->query()
            ->with('plan:id,name,sub_plan_label,group_label,price,billing_cycle,duration_days')
            ->where('user_id', $user->id)
            ->whereHas('plan', function ($query) {
                $query->where('price', '>', 0)
                    ->whereRaw("LOWER(name) NOT LIKE '%free%'");
            })
            ->orderByDesc('start_date')
            ->orderByDesc('id')
            ->paginate(10);

        return response()->json([
            'data' => $subscriptions->getCollection()->map(
                fn (UserSubscription $subscription) => [
                    'id' => $subscription->id,
                    'plan_id' => $subscription->plan_id,
                    'plan_name' => $subscription->plan?->name,
                    'plan_group_label' => $subscription->plan?->group_label,
                    'sub_plan_label' => $subscription->plan?->sub_plan_label,
                    'price' => (float) ($subscription->plan?->price ?? 0),
                    'billing_cycle' => $subscription->billing_cycle,
                    'duration_days' => (int) ($subscription->plan?->duration_days ?? 0),
                    'start_date' => $subscription->start_date?->toDateString(),
                    'end_date' => $subscription->end_date?->toDateString(),
                    'status' => $subscription->status,
                ],
            )->values(),
            'pagination' => [
                'current_page' => $subscriptions->currentPage(),
                'last_page' => $subscriptions->lastPage(),
                'next_page_url' => $subscriptions->nextPageUrl(),
                'has_more' => $subscriptions->hasMorePages(),
            ],
        ]);
    }
}
