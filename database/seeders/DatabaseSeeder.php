<?php

namespace Database\Seeders;

use App\Models\Plan;
use App\Models\Question;
use App\Models\QuizAttempt;
use App\Models\Referral;
use App\Models\Subject;
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
        $admin = $this->createAdminUser();
        $users = User::factory(5)->create();

        [$math, $questions] = $this->seedQuestions($admin);
        [$freePlan, $starterPlan, $proPlan] = $this->createPlans();
        $this->seedPlanSubjectAccesses($freePlan, $starterPlan, $proPlan);

        $this->seedSubscriptions($users, $freePlan, $proPlan);
        $this->seedReferral($users);
        $this->seedQuizAttempt($users, $math, $questions);

        $this->createDefaultTestUser();
    }

    private function createAdminUser(): User
    {
        return User::factory()->admin()->create([
            'name' => 'Admin User',
            'email' => 'admin@example.com',
            'password' => Hash::make('password'),
        ]);
    }

    /**
     * @return array{0: Plan, 1: Plan, 2: Plan}
     */
    private function createPlans(): array
    {
        $freePlan = Plan::query()->create([
            'name' => 'Free Plan',
            'price' => 0,
            'billing_cycle' => Plan::BILLING_MONTHLY,
            'description' => 'Default starter access.',
            'is_default' => true,
        ]);

        $freePlan->features()->createMany([
            ['feature_name' => 'Starter access'],
            ['feature_name' => 'Limited subject availability'],
        ]);

        $starterPlan = Plan::query()->create([
            'name' => 'Starter',
            'price' => 199,
            'billing_cycle' => Plan::BILLING_MONTHLY,
            'description' => 'Good for new reviewers.',
            'is_default' => false,
        ]);

        $starterPlan->features()->createMany([
            ['feature_name' => 'Access to practice quizzes'],
            ['feature_name' => 'Progress reports'],
        ]);

        $proPlan = Plan::query()->create([
            'name' => 'Pro',
            'price' => 499,
            'billing_cycle' => Plan::BILLING_YEARLY,
            'description' => 'Full access for serious learners.',
            'is_default' => false,
        ]);

        $proPlan->features()->createMany([
            ['feature_name' => 'Unlimited quiz attempts'],
            ['feature_name' => 'Detailed answer explanations'],
            ['feature_name' => 'Priority support'],
        ]);

        return [$freePlan, $starterPlan, $proPlan];
    }

    private function seedPlanSubjectAccesses(Plan $freePlan, Plan $starterPlan, Plan $proPlan): void
    {
        $subjectIds = Subject::query()
            ->orderBy('id')
            ->pluck('id');

        foreach ($subjectIds->take(1) as $subjectId) {
            $freePlan->subjectAccesses()->create([
                'subject_id' => $subjectId,
                'question_limit' => 30,
            ]);
        }

        foreach ($subjectIds as $subjectId) {
            $starterPlan->subjectAccesses()->create([
                'subject_id' => $subjectId,
                'question_limit' => 60,
            ]);
            $proPlan->subjectAccesses()->create([
                'subject_id' => $subjectId,
                'question_limit' => 100,
            ]);
        }
    }

    private function seedSubscriptions(Collection $users, Plan $freePlan, Plan $proPlan): void
    {
        foreach ($users as $index => $user) {
            $assignedPlan = $index % 2 === 0 ? $freePlan : $proPlan;
            $billingCycle = $assignedPlan->billing_cycle;
            $startDate = now()->subDays(5 + $index);
            $endDate = $billingCycle === Plan::BILLING_YEARLY
                ? $startDate->copy()->addYear()->toDateString()
                : $startDate->copy()->addMonth()->toDateString();

            UserSubscription::query()->create([
                'user_id' => $user->id,
                'plan_id' => $assignedPlan->id,
                'billing_cycle' => $billingCycle,
                'start_date' => $startDate->toDateString(),
                'end_date' => $endDate,
                'status' => 'active',
            ]);
        }
    }

    private function seedReferral(Collection $users): void
    {
        $referredUser = $users->first();
        $inviter = $users->skip(1)->first();

        if (! $referredUser || ! $inviter) {
            return;
        }

        $referredUser->update(['referred_by' => $inviter->id]);

        Referral::query()->create([
            'inviter_id' => $inviter->id,
            'invited_user_id' => $referredUser->id,
        ]);
    }

    /**
     * @return array{0: Subject, 1: Collection<int, Question>}
     */
    private function seedQuestions(User $admin): array
    {
        $math = Subject::query()->create(['name' => 'Mathematics']);
        $science = Subject::query()->create(['name' => 'Science']);

        $questions = collect([
            [
                'subject_id' => $math->id,
                'question' => 'What is the value of 9 x 8?',
                'choices' => [
                    ['choice_label' => 'A', 'choice_text' => '72', 'is_correct' => true],
                    ['choice_label' => 'B', 'choice_text' => '63', 'is_correct' => false],
                    ['choice_label' => 'C', 'choice_text' => '81', 'is_correct' => false],
                    ['choice_label' => 'D', 'choice_text' => '64', 'is_correct' => false],
                ],
            ],
            [
                'subject_id' => $science->id,
                'question' => 'What gas do plants absorb from the atmosphere?',
                'choices' => [
                    ['choice_label' => 'A', 'choice_text' => 'Oxygen', 'is_correct' => false],
                    ['choice_label' => 'B', 'choice_text' => 'Carbon Dioxide', 'is_correct' => true],
                    ['choice_label' => 'C', 'choice_text' => 'Nitrogen', 'is_correct' => false],
                    ['choice_label' => 'D', 'choice_text' => 'Helium', 'is_correct' => false],
                ],
            ],
        ])->map(function (array $payload) use ($admin) {
            $question = Question::query()->create([
                'subject_id' => $payload['subject_id'],
                'question' => $payload['question'],
                'created_by' => $admin->id,
            ]);

            $question->choices()->createMany(
                collect($payload['choices'])->map(fn (array $choice) => [
                    ...$choice,
                    'explanation' => null,
                ])->all()
            );

            return $question->load('choices');
        });

        return [$math, $questions];
    }

    private function seedQuizAttempt(Collection $users, Subject $math, Collection $questions): void
    {
        $attemptUser = $users->last();

        if (! $attemptUser) {
            return;
        }

        $attempt = QuizAttempt::query()->create([
            'user_id' => $attemptUser->id,
            'subject_id' => $math->id,
            'total_questions' => $questions->count(),
            'score' => 1,
        ]);

        $questions->each(function (Question $question, int $index) use ($attempt): void {
            $selectedChoice = $index === 0
                ? $question->choices->firstWhere('is_correct', true)
                : $question->choices->first();

            $attempt->answers()->create([
                'question_id' => $question->id,
                'selected_choice_id' => $selectedChoice?->id,
                'is_correct' => (bool) $selectedChoice?->is_correct,
            ]);
        });
    }

    private function createDefaultTestUser(): void
    {
        User::query()->firstOrCreate([
            'email' => 'test@example.com',
        ], [
            'name' => 'Test User',
            'password' => Hash::make('password'),
            'role' => 'user',
        ]);
    }
}
