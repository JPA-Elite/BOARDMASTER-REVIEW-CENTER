<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Plan;
use App\Services\Admin\PlanService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class PlanController extends Controller
{
    public function __construct(private readonly PlanService $plans)
    {
    }

    public function index(): Response
    {
        $plans = $this->plans->paginate();

        return Inertia::render('Admin/Plans/Index', [
            'plans' => $plans,
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('Admin/Plans/Form', [
            'mode' => 'create',
            'plan' => null,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255', 'unique:plans,name'],
            'price' => ['required', 'numeric', 'min:0'],
            'description' => ['nullable', 'string'],
            'features' => ['nullable', 'array'],
            'features.*' => ['required', 'string', 'max:255'],
        ]);

        $this->plans->create($validated);

        return redirect()
            ->route('admin.plans.index')
            ->with('success', 'Plan created successfully.');
    }

    public function edit(Plan $plan): Response
    {
        $plan->load('features:id,plan_id,feature_name');

        return Inertia::render('Admin/Plans/Form', [
            'mode' => 'edit',
            'plan' => [
                'id' => $plan->id,
                'name' => $plan->name,
                'price' => (float) $plan->price,
                'description' => $plan->description,
                'features' => $plan->features->pluck('feature_name')->values(),
            ],
        ]);
    }

    public function update(Request $request, Plan $plan): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255', Rule::unique('plans', 'name')->ignore($plan->id)],
            'price' => ['required', 'numeric', 'min:0'],
            'description' => ['nullable', 'string'],
            'features' => ['nullable', 'array'],
            'features.*' => ['required', 'string', 'max:255'],
        ]);

        $this->plans->update($plan, $validated);

        return redirect()
            ->route('admin.plans.index')
            ->with('success', 'Plan updated successfully.');
    }

    public function destroy(Plan $plan): RedirectResponse
    {
        $this->plans->delete($plan);

        return redirect()
            ->route('admin.plans.index')
            ->with('success', 'Plan deleted successfully.');
    }
}
