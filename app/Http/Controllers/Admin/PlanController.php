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

    public function index(Request $request): Response
    {
        $this->plans->ensureDefaultFreePlan();
        $perPage = (int) $request->integer('per_page', 10);
        $perPage = in_array($perPage, [10, 15, 25, 50, 100], true) ? $perPage : 10;
        $plans = $this->plans->paginate($perPage);

        return Inertia::render('Admin/Plans/Index', [
            'plans' => $plans,
            'filters' => [
                'per_page' => $perPage,
            ],
        ]);
    }

    public function create(Request $request): Response
    {
        $this->plans->ensureDefaultFreePlan();

        return Inertia::render('Admin/Plans/Form', [
            'mode' => 'create',
            'plan' => null,
            'billingCycles' => Plan::billingCycles(),
            'planGroups' => $this->planGroups(),
            'subjects' => $this->subjectsForForm(),
            'initialPlanGroup' => (string) $request->get('plan_group', ''),
            'initialGroupLabel' => (string) $request->get('group_label', ''),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['nullable', 'string', 'max:255', 'required_without:sub_plans', 'unique:plans,name'],
            'plan_group' => ['required', 'string', 'max:50'],
            'group_label' => ['nullable', 'string', 'max:255'],
            'sub_plan_label' => ['nullable', 'string', 'max:255', 'required_without:sub_plans'],
            'price' => ['nullable', 'numeric', 'min:0', 'required_without:sub_plans'],
            'billing_cycle' => ['nullable', 'string', Rule::in(Plan::billingCycles()), 'required_without:sub_plans'],
            'duration_days' => ['nullable', 'integer', 'min:1', 'max:3650'],
            'sort_order' => ['nullable', 'integer', 'min:0', 'max:100000'],
            'description' => ['nullable', 'string'],
            'features' => ['nullable', 'array'],
            'features.*' => ['nullable', 'string', 'max:255'],
            'subject_accesses' => ['required', 'array', 'min:1'],
            'subject_accesses.*.subject_id' => ['required', 'integer', 'distinct', 'exists:subjects,id'],
            'subject_accesses.*.question_limit' => ['required', 'integer', 'min:0', 'max:1000'],
            'sub_plans' => ['nullable', 'array'],
            'sub_plans.*.name' => ['required_with:sub_plans', 'string', 'max:255', 'distinct', 'unique:plans,name'],
            'sub_plans.*.sub_plan_label' => ['nullable', 'string', 'max:255'],
            'sub_plans.*.price' => ['required_with:sub_plans', 'numeric', 'min:0'],
            'sub_plans.*.billing_cycle' => ['required_with:sub_plans', 'string', Rule::in(Plan::billingCycles())],
            'sub_plans.*.duration_days' => ['nullable', 'integer', 'min:1', 'max:3650'],
            'sub_plans.*.sort_order' => ['nullable', 'integer', 'min:0', 'max:100000'],
        ]);

        $createdPlans = ! empty($validated['sub_plans'] ?? [])
            ? $this->plans->createMany($validated)
            : [$this->plans->create($validated)];
        $createdCount = count($createdPlans);
        $message = $createdCount > 1
            ? $createdCount.' sub plans created successfully.'
            : 'Plan created successfully.';

        return redirect()
            ->route('admin.plans.index')
            ->with('success', $message);
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
            'planGroups' => $this->planGroups(),
            'subjects' => $this->subjectsForForm(),
            'plan' => [
                'id' => $plan->id,
                'name' => $plan->name,
                'plan_group' => $plan->plan_group,
                'group_label' => $plan->group_label,
                'sub_plan_label' => $plan->sub_plan_label,
                'price' => (float) $plan->price,
                'billing_cycle' => $plan->billing_cycle,
                'duration_days' => $plan->duration_days,
                'sort_order' => (int) ($plan->sort_order ?? 0),
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
                'name' => 'Free Trial',
                'plan_group' => 'free_trial',
                'group_label' => 'A. Free Trial (Limited Access)',
                'sub_plan_label' => '3 days FREE',
                'price' => 0,
                'billing_cycle' => Plan::BILLING_TRIAL,
                'duration_days' => 3,
            ]);
        }

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255', Rule::unique('plans', 'name')->ignore($plan->id)],
            'plan_group' => ['required', 'string', 'max:50'],
            'group_label' => ['nullable', 'string', 'max:255'],
            'sub_plan_label' => ['nullable', 'string', 'max:255'],
            'price' => ['required', 'numeric', 'min:0'],
            'billing_cycle' => ['required', 'string', Rule::in(Plan::billingCycles())],
            'duration_days' => ['nullable', 'integer', 'min:1', 'max:3650'],
            'sort_order' => ['nullable', 'integer', 'min:0', 'max:100000'],
            'description' => ['nullable', 'string'],
            'features' => ['nullable', 'array'],
            'features.*' => ['nullable', 'string', 'max:255'],
            'subject_accesses' => ['required', 'array', 'min:1'],
            'subject_accesses.*.subject_id' => ['required', 'integer', 'distinct', 'exists:subjects,id'],
            'subject_accesses.*.question_limit' => ['required', 'integer', 'min:0', 'max:1000'],
            'sub_plans' => ['nullable', 'array'],
            'sub_plans.*.name' => ['required_with:sub_plans', 'string', 'max:255', 'distinct', 'unique:plans,name'],
            'sub_plans.*.sub_plan_label' => ['nullable', 'string', 'max:255'],
            'sub_plans.*.price' => ['required_with:sub_plans', 'numeric', 'min:0'],
            'sub_plans.*.billing_cycle' => ['required_with:sub_plans', 'string', Rule::in(Plan::billingCycles())],
            'sub_plans.*.duration_days' => ['nullable', 'integer', 'min:1', 'max:3650'],
            'sub_plans.*.sort_order' => ['nullable', 'integer', 'min:0', 'max:100000'],
        ]);

        $this->plans->update($plan, $validated);
        $createdCount = 0;
        if (! (bool) $plan->is_default && ! empty($validated['sub_plans'] ?? [])) {
            $created = $this->plans->createMany([
                'plan_group' => $validated['plan_group'],
                'group_label' => $validated['group_label'] ?? null,
                'description' => $validated['description'] ?? null,
                'features' => $validated['features'] ?? [],
                'subject_accesses' => $validated['subject_accesses'] ?? [],
                'sub_plans' => $validated['sub_plans'],
            ]);
            $createdCount = count($created);
        }
        $message = $createdCount > 0
            ? 'Plan updated and '.$createdCount.' new sub plan(s) created.'
            : 'Plan updated successfully.';

        return redirect()
            ->route('admin.plans.index')
            ->with('success', $message);
    }

    public function destroy(Plan $plan): RedirectResponse
    {
        if ((bool) $plan->is_default) {
            return redirect()
                ->route('admin.plans.index')
                ->with('error', 'Default Free Trial cannot be deleted.');
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

    /**
     * @return array<int, array{value: string, label: string}>
     */
    private function planGroups(): array
    {
        $rows = Plan::query()
            ->select(['plan_group', 'group_label'])
            ->whereNotNull('plan_group')
            ->where('plan_group', '!=', '')
            ->orderBy('plan_group')
            ->orderBy('group_label')
            ->get()
            ->unique('plan_group')
            ->values()
            ->map(function (Plan $plan): array {
                $group = trim((string) $plan->plan_group);
                $label = trim((string) $plan->group_label);

                return [
                    'value' => $group,
                    'label' => $label !== '' ? $label : $this->groupLabelFromKey($group),
                ];
            })
            ->all();

        $required = [
            'free_trial' => 'Free Trial',
            'plan_a' => 'Plan A',
            'plan_b' => 'Plan B',
        ];

        $existingKeys = collect($rows)->pluck('value')->all();
        foreach ($required as $key => $label) {
            if (! in_array($key, $existingKeys, true)) {
                $rows[] = ['value' => $key, 'label' => $label];
            }
        }

        usort($rows, function (array $a, array $b): int {
            $order = [
                'free_trial' => 0,
                'plan_a' => 1,
                'plan_b' => 2,
            ];

            $aKey = (string) ($a['value'] ?? '');
            $bKey = (string) ($b['value'] ?? '');
            $aOrder = $order[$aKey] ?? 99;
            $bOrder = $order[$bKey] ?? 99;
            if ($aOrder !== $bOrder) {
                return $aOrder <=> $bOrder;
            }

            return strcasecmp($aKey, $bKey);
        });

        return array_values($rows);
    }

    private function groupLabelFromKey(string $groupKey): string
    {
        $normalized = trim(strtolower($groupKey));
        if ($normalized === '') {
            return 'Plans';
        }

        if ($normalized === 'free_trial') {
            return 'Free Trial';
        }

        return ucwords(str_replace('_', ' ', $normalized));
    }
}
