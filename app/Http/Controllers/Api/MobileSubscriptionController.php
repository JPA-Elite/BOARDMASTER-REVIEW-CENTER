<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\UserSubscription;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class MobileSubscriptionController extends Controller
{
    public function history(Request $request): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();

        $subscriptions = UserSubscription::query()
            ->with('plan:id,name,price,billing_cycle')
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
                    'price' => (float) ($subscription->plan?->price ?? 0),
                    'billing_cycle' => $subscription->billing_cycle,
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
