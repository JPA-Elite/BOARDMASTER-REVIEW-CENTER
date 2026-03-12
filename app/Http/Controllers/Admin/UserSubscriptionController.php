<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Plan;
use App\Models\User;
use App\Models\UserSubscription;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class UserSubscriptionController extends Controller
{
    public function index(): Response
    {
        $subscriptions = UserSubscription::query()
            ->with(['user:id,name,email', 'plan:id,name'])
            ->latest()
            ->paginate(15)
            ->through(fn (UserSubscription $subscription) => [
                'id' => $subscription->id,
                'user' => $subscription->user?->name,
                'user_email' => $subscription->user?->email,
                'plan' => $subscription->plan?->name,
                'start_date' => $subscription->start_date?->format('Y-m-d'),
                'end_date' => $subscription->end_date?->format('Y-m-d'),
                'status' => $subscription->status,
                'created_at' => $subscription->created_at?->format('M d, Y'),
            ]);

        return Inertia::render('Admin/Subscriptions/Index', [
            'subscriptions' => $subscriptions,
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('Admin/Subscriptions/Form', [
            'mode' => 'create',
            'subscription' => null,
            'users' => $this->usersList(),
            'plans' => $this->plansList(),
            'statuses' => ['active', 'expired'],
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'user_id' => ['required', 'integer', 'exists:users,id'],
            'plan_id' => ['required', 'integer', 'exists:plans,id'],
            'start_date' => ['required', 'date'],
            'end_date' => ['nullable', 'date', 'after_or_equal:start_date'],
            'status' => ['required', Rule::in(['active', 'expired'])],
        ]);

        UserSubscription::create($validated);

        return redirect()
            ->route('admin.subscriptions.index')
            ->with('success', 'Subscription created successfully.');
    }

    public function edit(UserSubscription $subscription): Response
    {
        return Inertia::render('Admin/Subscriptions/Form', [
            'mode' => 'edit',
            'users' => $this->usersList(),
            'plans' => $this->plansList(),
            'statuses' => ['active', 'expired'],
            'subscription' => [
                'id' => $subscription->id,
                'user_id' => $subscription->user_id,
                'plan_id' => $subscription->plan_id,
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
            'start_date' => ['required', 'date'],
            'end_date' => ['nullable', 'date', 'after_or_equal:start_date'],
            'status' => ['required', Rule::in(['active', 'expired'])],
        ]);

        $subscription->update($validated);

        return redirect()
            ->route('admin.subscriptions.index')
            ->with('success', 'Subscription updated successfully.');
    }

    public function destroy(UserSubscription $subscription): RedirectResponse
    {
        $subscription->delete();

        return redirect()
            ->route('admin.subscriptions.index')
            ->with('success', 'Subscription deleted successfully.');
    }

    private function usersList()
    {
        return User::query()
            ->select('id', 'name', 'email')
            ->orderBy('name')
            ->get();
    }

    private function plansList()
    {
        return Plan::query()
            ->select('id', 'name')
            ->orderBy('name')
            ->get();
    }
}
