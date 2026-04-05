<?php

namespace App\Services\Admin;

use App\Models\Plan;
use App\Models\Subject;
use App\Repositories\Contracts\PlanRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Validation\ValidationException;

class PlanService
{
    public function __construct(private readonly PlanRepositoryInterface $plans)
    {
    }

    public function paginate(int $perPage = 10): LengthAwarePaginator
    {
        $this->ensureDefaultFreePlan();

        $plans = $this->plans->paginateWithFeatures($perPage);

        $plans->through(fn (Plan $plan) => [
            'id' => $plan->id,
            'name' => $plan->name,
            'plan_group' => $plan->groupKey(),
            'group_label' => $plan->groupTitle(),
            'sub_plan_label' => $plan->subPlanTitle(),
            'price' => (float) $plan->price,
            'billing_cycle' => $plan->billing_cycle,
            'billing_label' => $plan->billingLabel(),
            'duration_days' => $plan->durationDays(),
            'sort_order' => (int) ($plan->sort_order ?? 0),
            'description' => $plan->description,
            'is_default' => (bool) $plan->is_default,
            'feature_count' => $plan->features->count(),
            'features' => $plan->features->pluck('feature_name')->values(),
            'subject_access_count' => $plan->subjectAccesses->count(),
            'subject_accesses' => $plan->subjectAccesses
                ->filter(fn ($access) => $access->subject)
                ->map(fn ($access) => [
                    'subject_id' => $access->subject_id,
                    'subject_name' => $access->subject->name,
                    'question_limit' => (int) $access->question_limit,
                ])
                ->values(),
            'created_at' => $plan->created_at?->format('M d, Y'),
        ]);

        return $plans;
    }

    public function ensureDefaultFreePlan(): Plan
    {
        $defaultPlan = Plan::query()->where('is_default', true)->first();

        if ($defaultPlan) {
            $defaultPlan->forceFill([
                'name' => 'Free Trial',
                'plan_group' => 'free_trial',
                'group_label' => 'A. Free Trial (Limited Access)',
                'sub_plan_label' => '3 days FREE',
                'price' => 0,
                'billing_cycle' => Plan::BILLING_TRIAL,
                'duration_days' => 3,
                'sort_order' => 100,
                'is_default' => true,
            ])->save();

            if (! $defaultPlan->subjectAccesses()->exists()) {
                $subjectIds = Subject::query()->orderBy('id')->pluck('id')->take(3);
                foreach ($subjectIds as $subjectId) {
                    $defaultPlan->subjectAccesses()->create([
                        'subject_id' => $subjectId,
                        'question_limit' => 30,
                    ]);
                }
            }

            return $defaultPlan;
        }

        $existingFree = Plan::query()
            ->where('price', '<=', 0)
            ->orWhereRaw('LOWER(name) LIKE ?', ['%free%'])
            ->orWhereRaw('LOWER(name) LIKE ?', ['%trial%'])
            ->orderBy('id')
            ->first();

        if ($existingFree) {
            Plan::query()->where('is_default', true)->update(['is_default' => false]);
            $existingFree->forceFill([
                'name' => 'Free Trial',
                'plan_group' => 'free_trial',
                'group_label' => 'A. Free Trial (Limited Access)',
                'sub_plan_label' => '3 days FREE',
                'price' => 0,
                'billing_cycle' => Plan::BILLING_TRIAL,
                'duration_days' => 3,
                'sort_order' => 100,
                'is_default' => true,
            ])->save();
            return $existingFree;
        }

        $defaultPlan = Plan::query()->create([
            'name' => 'Free Trial',
            'plan_group' => 'free_trial',
            'group_label' => 'A. Free Trial (Limited Access)',
            'sub_plan_label' => '3 days FREE',
            'price' => 0,
            'billing_cycle' => Plan::BILLING_TRIAL,
            'duration_days' => 3,
            'sort_order' => 100,
            'description' => '3 days free trial with limited access.',
            'is_default' => true,
        ]);

        $subjectIds = Subject::query()->orderBy('id')->pluck('id')->take(3);
        foreach ($subjectIds as $subjectId) {
            $defaultPlan->subjectAccesses()->create([
                'subject_id' => $subjectId,
                'question_limit' => 30,
            ]);
        }

        return $defaultPlan;
    }

    public function create(array $data): Plan
    {
        $normalized = $this->normalizeGroupFields($data);

        $plan = $this->plans->create([
            'name' => $data['name'],
            'plan_group' => $normalized['plan_group'],
            'group_label' => $normalized['group_label'],
            'sub_plan_label' => $normalized['sub_plan_label'],
            'price' => $data['price'],
            'billing_cycle' => $data['billing_cycle'],
            'duration_days' => $data['duration_days'] ?? null,
            'sort_order' => $data['sort_order'] ?? 0,
            'description' => $data['description'] ?? null,
            'is_default' => false,
        ]);

        $this->syncFeatures($plan, $data['features'] ?? []);
        $this->syncSubjectAccesses($plan, $data['subject_accesses'] ?? []);

        return $plan;
    }

    /**
     * @return array<int, Plan>
     */
    public function createMany(array $data): array
    {
        $subPlans = collect($data['sub_plans'] ?? [])
            ->filter(fn ($row) => is_array($row))
            ->values();

        if ($subPlans->isEmpty()) {
            return [$this->create($data)];
        }

        $created = [];
        foreach ($subPlans as $row) {
            $payload = [
                'name' => $row['name'] ?? '',
                'plan_group' => $data['plan_group'] ?? '',
                'group_label' => $data['group_label'] ?? null,
                'sub_plan_label' => $row['sub_plan_label'] ?? null,
                'price' => $row['price'] ?? 0,
                'billing_cycle' => $row['billing_cycle'] ?? Plan::BILLING_MONTHLY,
                'duration_days' => $row['duration_days'] ?? null,
                'sort_order' => $row['sort_order'] ?? 0,
                'description' => $data['description'] ?? null,
                'features' => $data['features'] ?? [],
                'subject_accesses' => $data['subject_accesses'] ?? [],
            ];

            $created[] = $this->create($payload);
        }

        return $created;
    }

    public function update(Plan $plan, array $data): Plan
    {
        $isDefault = (bool) $plan->is_default;
        $normalized = $this->normalizeGroupFields($data, $plan);

        $plan = $this->plans->update($plan, [
            'name' => $isDefault ? 'Free Trial' : $data['name'],
            'plan_group' => $isDefault ? 'free_trial' : $normalized['plan_group'],
            'group_label' => $isDefault
                ? 'A. Free Trial (Limited Access)'
                : $normalized['group_label'],
            'sub_plan_label' => $isDefault
                ? '3 days FREE'
                : $normalized['sub_plan_label'],
            'price' => $isDefault ? 0 : $data['price'],
            'billing_cycle' => $isDefault ? Plan::BILLING_TRIAL : $data['billing_cycle'],
            'duration_days' => $isDefault ? 3 : ($data['duration_days'] ?? null),
            'sort_order' => $data['sort_order'] ?? ($plan->sort_order ?? 0),
            'description' => $data['description'] ?? null,
            'is_default' => $isDefault,
        ]);

        $this->syncFeatures($plan, $data['features'] ?? []);
        $this->syncSubjectAccesses($plan, $data['subject_accesses'] ?? []);

        return $plan;
    }

    public function delete(Plan $plan): void
    {
        if ((bool) $plan->is_default) {
            throw ValidationException::withMessages([
                'plan' => 'Default Free Trial cannot be deleted.',
            ]);
        }

        $this->plans->delete($plan);
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

    private function syncSubjectAccesses(Plan $plan, array $subjectAccesses): void
    {
        $cleanAccesses = collect($subjectAccesses)
            ->filter(fn ($access) => is_array($access))
            ->map(function (array $access) {
                $subjectId = (int) ($access['subject_id'] ?? 0);
                $limit = (int) ($access['question_limit'] ?? 0);
                if ($limit < 0) {
                    $limit = 0;
                }
                return [
                    'subject_id' => $subjectId,
                    'question_limit' => $limit,
                ];
            })
            ->filter(fn (array $access) => $access['subject_id'] > 0 && $access['question_limit'] >= 0)
            ->unique(fn (array $access) => $access['subject_id'])
            ->values()
            ->all();

        $plan->subjectAccesses()->delete();
        $plan->subjectAccesses()->createMany($cleanAccesses);
    }

    /**
     * @return array{plan_group: string, group_label: string, sub_plan_label: string}
     */
    private function normalizeGroupFields(array $data, ?Plan $existing = null): array
    {
        $planGroup = trim((string) ($data['plan_group'] ?? $existing?->plan_group ?? ''));
        $planGroup = strtolower($planGroup);
        $planGroup = preg_replace('/[^a-z0-9_]+/', '_', $planGroup) ?? '';
        $planGroup = trim($planGroup, '_');
        if ($planGroup === '') {
            $planGroup = 'plan_a';
        }

        $groupLabel = trim((string) ($data['group_label'] ?? $existing?->group_label ?? ''));
        if ($groupLabel === '') {
            $groupLabel = match ($planGroup) {
                'free_trial' => 'A. Free Trial (Limited Access)',
                'plan_b' => 'PLAN B: MOCK BOARD EXAM + ALL NURSING CONCEPTS',
                'plan_a' => 'PLAN A: ALL NURSING CONCEPTS',
                default => strtoupper(str_replace('_', ' ', $planGroup)),
            };
        }

        $subPlanLabel = trim((string) ($data['sub_plan_label'] ?? $existing?->sub_plan_label ?? ''));
        if ($subPlanLabel === '') {
            $subPlanLabel = 'Sub Plan';
        }

        return [
            'plan_group' => $planGroup,
            'group_label' => $groupLabel,
            'sub_plan_label' => $subPlanLabel,
        ];
    }
}
