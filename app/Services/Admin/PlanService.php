<?php

namespace App\Services\Admin;

use App\Models\Plan;
use App\Repositories\Contracts\PlanRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class PlanService
{
    public function __construct(private readonly PlanRepositoryInterface $plans)
    {
    }

    public function paginate(): LengthAwarePaginator
    {
        $plans = $this->plans->paginateWithFeatures();

        $plans->through(fn (Plan $plan) => [
            'id' => $plan->id,
            'name' => $plan->name,
            'price' => (float) $plan->price,
            'description' => $plan->description,
            'feature_count' => $plan->features->count(),
            'features' => $plan->features->pluck('feature_name')->values(),
            'created_at' => $plan->created_at?->format('M d, Y'),
        ]);

        return $plans;
    }

    public function create(array $data): Plan
    {
        $plan = $this->plans->create([
            'name' => $data['name'],
            'price' => $data['price'],
            'description' => $data['description'] ?? null,
        ]);

        $this->syncFeatures($plan, $data['features'] ?? []);

        return $plan;
    }

    public function update(Plan $plan, array $data): Plan
    {
        $plan = $this->plans->update($plan, [
            'name' => $data['name'],
            'price' => $data['price'],
            'description' => $data['description'] ?? null,
        ]);

        $this->syncFeatures($plan, $data['features'] ?? []);

        return $plan;
    }

    public function delete(Plan $plan): void
    {
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
}
