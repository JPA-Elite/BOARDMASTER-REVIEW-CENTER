<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Plan;
use App\Models\UserSubscription;
use App\Services\Admin\SubscriptionService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class UserSubscriptionController extends Controller
{
    public function __construct(private readonly SubscriptionService $subscriptions)
    {
    }

    public function index(): Response
    {
        $subscriptions = $this->subscriptions->paginate();

        return Inertia::render('Admin/Subscriptions/Index', [
            'subscriptions' => $subscriptions,
        ]);
    }

    public function create(Request $request): Response
    {
        $selectedPlanId = $request->integer('plan_id');
        $selectedPlan = $selectedPlanId > 0
            ? Plan::query()
                ->select('id', 'billing_cycle')
                ->find($selectedPlanId)
            : null;

        return Inertia::render('Admin/Subscriptions/Form', [
            'mode' => 'create',
            'subscription' => [
                'plan_id' => $selectedPlan?->id,
                'billing_cycle' => $selectedPlan?->billing_cycle ?? Plan::BILLING_MONTHLY,
                'start_date' => now()->toDateString(),
            ],
            'users' => $this->subscriptions->listUsers(),
            'plans' => $this->subscriptions->listPlans(),
            'billingCycles' => Plan::billingCycles(),
            'statuses' => ['active', 'inactive', 'expired'],
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'user_id' => ['required', 'integer', 'exists:users,id'],
            'plan_id' => ['required', 'integer', 'exists:plans,id'],
            'billing_cycle' => ['required', 'string', Rule::in(Plan::billingCycles())],
            'start_date' => ['required', 'date'],
            'end_date' => ['nullable', 'date', 'after_or_equal:start_date'],
            'status' => ['required', Rule::in(['active', 'inactive', 'expired'])],
        ]);

        $this->subscriptions->create($validated);

        return redirect()
            ->route('admin.subscriptions.index')
            ->with('success', 'Subscription created successfully.');
    }

    public function edit(UserSubscription $subscription): Response
    {
        return Inertia::render('Admin/Subscriptions/Form', [
            'mode' => 'edit',
            'users' => $this->subscriptions->listUsers(),
            'plans' => $this->subscriptions->listPlans(),
            'billingCycles' => Plan::billingCycles(),
            'statuses' => ['active', 'inactive', 'expired'],
            'subscription' => [
                'id' => $subscription->id,
                'user_id' => $subscription->user_id,
                'plan_id' => $subscription->plan_id,
                'billing_cycle' => $subscription->billing_cycle,
                'start_date' => $subscription->start_date?->format('Y-m-d'),
                'end_date' => $subscription->end_date?->format('Y-m-d'),
                'status' => $subscription->status,
            ],
        ]);
    }

    public function update(Request $request, UserSubscription $subscription): RedirectResponse
    {
        $validated = $request->validate([
            'user_id' => ['required', 'integer', 'exists:users,id'],
            'plan_id' => ['required', 'integer', 'exists:plans,id'],
            'billing_cycle' => ['required', 'string', Rule::in(Plan::billingCycles())],
            'start_date' => ['required', 'date'],
            'end_date' => ['nullable', 'date', 'after_or_equal:start_date'],
            'status' => ['required', Rule::in(['active', 'inactive', 'expired'])],
        ]);

        $this->subscriptions->update($subscription, $validated);

        return redirect()
            ->route('admin.subscriptions.index')
            ->with('success', 'Subscription updated successfully.');
    }

    public function destroy(UserSubscription $subscription): RedirectResponse
    {
        $this->subscriptions->delete($subscription);

        return redirect()
            ->route('admin.subscriptions.index')
            ->with('success', 'Subscription deleted successfully.');
    }
}
