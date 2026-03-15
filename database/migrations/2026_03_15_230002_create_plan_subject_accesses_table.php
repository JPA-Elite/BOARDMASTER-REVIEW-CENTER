<?php

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
        Schema::create('plan_subject_accesses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('plan_id')->constrained('plans')->cascadeOnDelete();
            $table->foreignId('subject_id')->constrained('subjects')->cascadeOnDelete();
            $table->unsignedInteger('question_limit')->default(30);
            $table->timestamps();

            $table->unique(['plan_id', 'subject_id']);
        });

        $subjectIds = DB::table('subjects')
            ->orderBy('id')
            ->pluck('id');

        if ($subjectIds->isEmpty()) {
            return;
        }

        $plans = DB::table('plans')
            ->select('id', 'name', 'price', 'is_default')
            ->orderBy('id')
            ->get();

        $now = now();

        foreach ($plans as $plan) {
            $isFree = (bool) ($plan->is_default ?? false)
                || (float) $plan->price <= 0
                || str_contains(strtolower((string) $plan->name), 'free');

            $allowedSubjectIds = $isFree
                ? $subjectIds->take(3)->values()
                : $subjectIds;

            $questionLimit = $isFree ? 30 : 100;

            foreach ($allowedSubjectIds as $subjectId) {
                DB::table('plan_subject_accesses')->insert([
                    'plan_id' => $plan->id,
                    'subject_id' => $subjectId,
                    'question_limit' => $questionLimit,
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('plan_subject_accesses');
    }
};
