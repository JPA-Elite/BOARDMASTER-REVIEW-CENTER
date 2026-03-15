<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Plan;
use App\Models\Subject;
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
        $this->plans->ensureDefaultFreePlan();
        $plans = $this->plans->paginate();

        return Inertia::render('Admin/Plans/Index', [
            'plans' => $plans,
        ]);
    }

    public function create(): Response
    {
        $this->plans->ensureDefaultFreePlan();

        return Inertia::render('Admin/Plans/Form', [
            'mode' => 'create',
            'plan' => null,
            'billingCycles' => Plan::billingCycles(),
            'subjects' => $this->subjectsForForm(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255', 'unique:plans,name'],
            'price' => ['required', 'numeric', 'min:0'],
            'billing_cycle' => ['required', 'string', Rule::in(Plan::billingCycles())],
            'description' => ['nullable', 'string'],
            'features' => ['nullable', 'array'],
            'features.*' => ['nullable', 'string', 'max:255'],
            'subject_accesses' => ['required', 'array', 'min:1'],
            'subject_accesses.*.subject_id' => ['required', 'integer', 'distinct', 'exists:subjects,id'],
            'subject_accesses.*.question_limit' => ['required', 'integer', 'min:0', 'max:1000'],
        ]);

        $this->plans->create($validated);

        return redirect()
            ->route('admin.plans.index')
            ->with('success', 'Plan created successfully.');
    }

    public function edit(Plan $plan): Response
    {
        $this->plans->ensureDefaultFreePlan();

        $plan->load([
            'features:id,plan_id,feature_name',
            'subjectAccesses:id,plan_id,subject_id,question_limit',
        ]);

        return Inertia::render('Admin/Plans/Form', [
            'mode' => 'edit',
            'billingCycles' => Plan::billingCycles(),
            'subjects' => $this->subjectsForForm(),
            'plan' => [
                'id' => $plan->id,
                'name' => $plan->name,
                'price' => (float) $plan->price,
                'billing_cycle' => $plan->billing_cycle,
                'description' => $plan->description,
                'is_default' => (bool) $plan->is_default,
                'features' => $plan->features->pluck('feature_name')->values(),
                'subject_accesses' => $plan->subjectAccesses->map(fn ($access) => [
                    'subject_id' => $access->subject_id,
                    'question_limit' => (int) $access->question_limit,
                ])->values(),
            ],
        ]);
    }

    public function update(Request $request, Plan $plan): RedirectResponse
    {
        if ((bool) $plan->is_default) {
            $request->merge([
                'name' => 'Free Plan',
                'price' => 0,
                'billing_cycle' => Plan::BILLING_MONTHLY,
            ]);
        }

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255', Rule::unique('plans', 'name')->ignore($plan->id)],
            'price' => ['required', 'numeric', 'min:0'],
            'billing_cycle' => ['required', 'string', Rule::in(Plan::billingCycles())],
            'description' => ['nullable', 'string'],
            'features' => ['nullable', 'array'],
            'features.*' => ['nullable', 'string', 'max:255'],
            'subject_accesses' => ['required', 'array', 'min:1'],
            'subject_accesses.*.subject_id' => ['required', 'integer', 'distinct', 'exists:subjects,id'],
            'subject_accesses.*.question_limit' => ['required', 'integer', 'min:0', 'max:1000'],
        ]);

        $this->plans->update($plan, $validated);

        return redirect()
            ->route('admin.plans.index')
            ->with('success', 'Plan updated successfully.');
    }

    public function destroy(Plan $plan): RedirectResponse
    {
        if ((bool) $plan->is_default) {
            return redirect()
                ->route('admin.plans.index')
                ->with('error', 'Default Free Plan cannot be deleted.');
        }

        $this->plans->delete($plan);

        return redirect()
            ->route('admin.plans.index')
            ->with('success', 'Plan deleted successfully.');
    }

    private function subjectsForForm(): array
    {
        return Subject::query()
            ->withCount('questions')
            ->orderBy('name')
            ->get()
            ->map(fn (Subject $subject) => [
                'id' => $subject->id,
                'name' => $subject->name,
                'questions_count' => $subject->questions_count,
            ])
            ->values()
            ->all();
    }
}
