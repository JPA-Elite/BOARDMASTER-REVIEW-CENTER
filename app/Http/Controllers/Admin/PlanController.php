<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Plan;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class PlanController extends Controller
{
    public function index(): Response
    {
        $plans = Plan::query()
            ->with('features:id,plan_id,feature_name')
            ->latest()
            ->paginate(10)
            ->through(fn (Plan $plan) => [
                'id' => $plan->id,
                'name' => $plan->name,
                'price' => (float) $plan->price,
                'description' => $plan->description,
                'feature_count' => $plan->features->count(),
                'features' => $plan->features->pluck('feature_name')->values(),
                'created_at' => $plan->created_at?->format('M d, Y'),
            ]);

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

        $plan = Plan::create([
            'name' => $validated['name'],
            'price' => $validated['price'],
            'description' => $validated['description'] ?? null,
        ]);

        $this->syncFeatures($plan, $validated['features'] ?? []);

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

        $plan->update([
            'name' => $validated['name'],
            'price' => $validated['price'],
            'description' => $validated['description'] ?? null,
        ]);

        $this->syncFeatures($plan, $validated['features'] ?? []);

        return redirect()
            ->route('admin.plans.index')
            ->with('success', 'Plan updated successfully.');
    }

    public function destroy(Plan $plan): RedirectResponse
    {
        $plan->delete();

        return redirect()
            ->route('admin.plans.index')
            ->with('success', 'Plan deleted successfully.');
    }

    private function syncFeatures(Plan $plan, array $features): void
    {
        $cleanFeatures = collect($features)
            ->filter(fn ($feature) => is_string($feature) && trim($feature) !== '')
            ->map(fn ($feature) => ['feature_name' => trim($feature)])
            ->values()
            ->all();

        $plan->features()->delete();
        $plan->features()->createMany($cleanFeatures);
    }
}
