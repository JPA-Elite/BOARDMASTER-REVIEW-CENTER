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

    public function paginate(): LengthAwarePaginator
    {
        $this->ensureDefaultFreePlan();

        $plans = $this->plans->paginateWithFeatures();

        $plans->through(fn (Plan $plan) => [
            'id' => $plan->id,
            'name' => $plan->name,
            'price' => (float) $plan->price,
            'billing_cycle' => $plan->billing_cycle,
            'billing_label' => $plan->billingLabel(),
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
            if ((float) $defaultPlan->price !== 0.0
                || $defaultPlan->billing_cycle !== Plan::BILLING_MONTHLY
                || $defaultPlan->name !== 'Free Plan') {
                $defaultPlan->forceFill([
                    'name' => 'Free Plan',
                    'price' => 0,
                    'billing_cycle' => Plan::BILLING_MONTHLY,
                ])->save();
            }

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
            ->orderBy('id')
            ->first();

        if ($existingFree) {
            Plan::query()->where('is_default', true)->update(['is_default' => false]);
            $existingFree->forceFill([
                'name' => 'Free Plan',
                'price' => 0,
                'billing_cycle' => Plan::BILLING_MONTHLY,
                'is_default' => true,
            ])->save();
            return $existingFree;
        }

        $defaultPlan = Plan::query()->create([
            'name' => 'Free Plan',
            'price' => 0,
            'billing_cycle' => Plan::BILLING_MONTHLY,
            'description' => 'Default free access plan.',
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
        $plan = $this->plans->create([
            'name' => $data['name'],
            'price' => $data['price'],
            'billing_cycle' => $data['billing_cycle'],
            'description' => $data['description'] ?? null,
            'is_default' => false,
        ]);

        $this->syncFeatures($plan, $data['features'] ?? []);
        $this->syncSubjectAccesses($plan, $data['subject_accesses'] ?? []);

        return $plan;
    }

    public function update(Plan $plan, array $data): Plan
    {
        $isDefault = (bool) $plan->is_default;
        $plan = $this->plans->update($plan, [
            'name' => $isDefault ? 'Free Plan' : $data['name'],
            'price' => $isDefault ? 0 : $data['price'],
            'billing_cycle' => $isDefault ? Plan::BILLING_MONTHLY : $data['billing_cycle'],
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
                'plan' => 'Default Free Plan cannot be deleted.',
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
}
