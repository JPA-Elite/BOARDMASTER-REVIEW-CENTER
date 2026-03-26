<?php

namespace Database\Seeders;

use App\Models\Plan;
use App\Models\Question;
use App\Models\QuizAttempt;
use App\Models\Referral;
use App\Models\User;
use App\Models\UserSubscription;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->createAdminUser();
        $this->call(PlanCatalogSeeder::class);

        $users = $this->createDemoUsers();
        $this->assignSubscriptions($users);
        $this->seedReferral($users);
        $this->seedQuizAttempt($users);

        $this->createDefaultTestUser();
    }

    private function createAdminUser(): User
    {
        /** @var User $admin */
        $admin = User::query()->firstOrCreate(
            ['email' => 'admin@example.com'],
            [
                'name' => 'Admin User',
                'password' => Hash::make('password'),
                'role' => 'admin',
            ]
        );

        if ($admin->role !== 'admin') {
            $admin->role = 'admin';
            $admin->save();
        }

        return $admin;
    }

    /**
     * @return Collection<int, User>
     */
    private function createDemoUsers(): Collection
    {
        $users = collect();

        for ($index = 1; $index <= 5; $index++) {
            /** @var User $user */
            $user = User::query()->firstOrCreate(
                ['email' => "user{$index}@example.com"],
                [
                    'name' => "Demo User {$index}",
                    'password' => Hash::make('password'),
                    'role' => 'user',
                ]
            );
            $users->push($user);
        }

        return $users;
    }

    private function assignSubscriptions(Collection $users): void
    {
        $freeTrialPlan = Plan::query()
            ->where('plan_group', 'free_trial')
            ->orderBy('sort_order')
            ->first();
        $planAAnnual = Plan::query()
            ->where('plan_group', 'plan_a')
            ->where('billing_cycle', Plan::BILLING_ANNUAL)
            ->first();
        $planBAnnual = Plan::query()
            ->where('plan_group', 'plan_b')
            ->where('billing_cycle', Plan::BILLING_ANNUAL)
            ->first();

        if (! $freeTrialPlan) {
            return;
        }

        $fallbackPaidPlan = $planBAnnual ?? $planAAnnual;

        foreach ($users as $index => $user) {
            $targetPlan = match ($index % 3) {
                1 => $planAAnnual ?? $fallbackPaidPlan ?? $freeTrialPlan,
                2 => $planBAnnual ?? $fallbackPaidPlan ?? $freeTrialPlan,
                default => $freeTrialPlan,
            };

            if (! $targetPlan instanceof Plan) {
                continue;
            }

            UserSubscription::query()
                ->where('user_id', $user->id)
                ->where('status', 'active')
                ->update([
                    'status' => 'inactive',
                    'end_date' => now()->toDateString(),
                ]);

            $startDate = now()->subDays(3 + $index)->startOfDay();
            $duration = max(1, $targetPlan->durationDays());
            $endDate = $startDate->copy()->addDays($duration)->toDateString();

            UserSubscription::query()->updateOrCreate(
                [
                    'user_id' => $user->id,
                    'plan_id' => $targetPlan->id,
                    'status' => 'active',
                ],
                [
                    'billing_cycle' => $targetPlan->billing_cycle,
                    'start_date' => $startDate->toDateString(),
                    'end_date' => $endDate,
                ]
            );
        }
    }

    private function seedReferral(Collection $users): void
    {
        $inviter = $users->get(1);
        $invited = $users->first();

        if (! $inviter || ! $invited) {
            return;
        }

        $invited->update([
            'referred_by' => $inviter->id,
        ]);

        Referral::query()->firstOrCreate([
            'inviter_id' => $inviter->id,
            'invited_user_id' => $invited->id,
        ]);
    }

    private function seedQuizAttempt(Collection $users): void
    {
        $attemptUser = $users->last();
        if (! $attemptUser instanceof User) {
            return;
        }

        $subscription = UserSubscription::query()
            ->where('user_id', $attemptUser->id)
            ->where('status', 'active')
            ->latest('id')
            ->first();
        if (! $subscription) {
            return;
        }

        $plan = Plan::query()->find($subscription->plan_id);
        if (! $plan) {
            return;
        }

        $subjectAccess = $plan->subjectAccesses()->orderBy('subject_id')->first();
        if (! $subjectAccess) {
            return;
        }

        $questions = Question::query()
            ->where('subject_id', $subjectAccess->subject_id)
            ->with('choices')
            ->inRandomOrder()
            ->limit(10)
            ->get();

        if ($questions->isEmpty()) {
            return;
        }

        QuizAttempt::query()
            ->where('user_id', $attemptUser->id)
            ->where('subject_id', $subjectAccess->subject_id)
            ->delete();

        /** @var QuizAttempt $attempt */
        $attempt = QuizAttempt::query()->create([
            'user_id' => $attemptUser->id,
            'subject_id' => $subjectAccess->subject_id,
            'total_questions' => $questions->count(),
            'score' => 0,
        ]);

        $score = 0;
        foreach ($questions as $index => $question) {
            $choice = $index % 2 === 0
                ? $question->choices->firstWhere('is_correct', true)
                : $question->choices->first();

            if (! $choice) {
                continue;
            }

            $isCorrect = (bool) $choice->is_correct;
            if ($isCorrect) {
                $score++;
            }

            $attempt->answers()->create([
                'question_id' => $question->id,
                'selected_choice_id' => $choice->id,
                'is_correct' => $isCorrect,
            ]);
        }

        $attempt->score = $score;
        $attempt->save();
    }

    private function createDefaultTestUser(): void
    {
        User::query()->firstOrCreate(
            ['email' => 'test@example.com'],
            [
                'name' => 'Test User',
                'password' => Hash::make('password'),
                'role' => 'user',
            ]
        );
    }
}

