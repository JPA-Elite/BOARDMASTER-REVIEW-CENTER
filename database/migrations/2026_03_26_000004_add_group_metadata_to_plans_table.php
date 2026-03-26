<?php

use App\Models\Plan;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('plans', function (Blueprint $table) {
            $table->string('plan_group', 50)->nullable()->after('name');
            $table->string('group_label')->nullable()->after('plan_group');
            $table->string('sub_plan_label')->nullable()->after('group_label');
            $table->unsignedSmallInteger('duration_days')->nullable()->after('billing_cycle');
            $table->unsignedInteger('sort_order')->default(0)->after('duration_days');
        });

        $plans = DB::table('plans')
            ->select('id', 'name', 'price', 'billing_cycle', 'is_default')
            ->orderBy('id')
            ->get();

        $position = 0;
        foreach ($plans as $plan) {
            $name = strtolower(trim((string) $plan->name));
            $isFree = (bool) ($plan->is_default ?? false)
                || (float) ($plan->price ?? 0) <= 0
                || str_contains($name, 'free')
                || str_contains($name, 'trial');

            if ($isFree) {
                DB::table('plans')->where('id', $plan->id)->update([
                    'plan_group' => 'free_trial',
                    'group_label' => 'Free Trial',
                    'sub_plan_label' => '3 days FREE',
                    'billing_cycle' => Plan::BILLING_TRIAL,
                    'duration_days' => 3,
                    'sort_order' => 100 + $position,
                    'updated_at' => now(),
                ]);
                $position++;
                continue;
            }

            $isPlanB = str_contains($name, 'plan b')
                || str_contains($name, 'mock board')
                || str_contains($name, 'full access');

            $group = $isPlanB ? 'plan_b' : 'plan_a';
            $groupLabel = $isPlanB
                ? 'Plan B: Mock Board Exam + All Nursing Concepts'
                : 'Plan A: All Nursing Concepts';
            $billingCycle = $plan->billing_cycle ?: Plan::BILLING_MONTHLY;
            $durationDays = match ($billingCycle) {
                Plan::BILLING_ANNUAL, Plan::BILLING_YEARLY => 365,
                Plan::BILLING_SEMI_ANNUAL => 180,
                Plan::BILLING_QUARTERLY => 90,
                Plan::BILLING_TRIAL => 3,
                default => 30,
            };
            $subPlanLabel = match ($billingCycle) {
                Plan::BILLING_ANNUAL, Plan::BILLING_YEARLY => '12 months',
                Plan::BILLING_SEMI_ANNUAL => '6 months',
                Plan::BILLING_QUARTERLY => '3 months',
                Plan::BILLING_TRIAL => '3 days FREE',
                default => '1 month',
            };

            DB::table('plans')->where('id', $plan->id)->update([
                'plan_group' => $group,
                'group_label' => $groupLabel,
                'sub_plan_label' => $subPlanLabel,
                'duration_days' => $durationDays,
                'sort_order' => ($isPlanB ? 300 : 200) + $position,
                'updated_at' => now(),
            ]);
            $position++;
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('plans', function (Blueprint $table) {
            $table->dropColumn([
                'plan_group',
                'group_label',
                'sub_plan_label',
                'duration_days',
                'sort_order',
            ]);
        });
    }
};

