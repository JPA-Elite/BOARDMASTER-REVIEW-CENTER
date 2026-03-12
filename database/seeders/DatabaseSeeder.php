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
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $admin = User::factory()->admin()->create([
            'name' => 'Admin User',
            'email' => 'admin@example.com',
            'password' => Hash::make('password'),
        ]);

        $users = User::factory(5)->create();

        $starterPlan = Plan::query()->create([
            'name' => 'Starter',
            'price' => 199,
            'description' => 'Good for new reviewers.',
        ]);

        $starterPlan->features()->createMany([
            ['feature_name' => 'Access to practice quizzes'],
            ['feature_name' => 'Progress reports'],
        ]);

        $proPlan = Plan::query()->create([
            'name' => 'Pro',
            'price' => 499,
            'description' => 'Full access for serious learners.',
        ]);

        $proPlan->features()->createMany([
            ['feature_name' => 'Unlimited quiz attempts'],
            ['feature_name' => 'Detailed answer explanations'],
            ['feature_name' => 'Priority support'],
        ]);

        foreach ($users as $index => $user) {
            UserSubscription::query()->create([
                'user_id' => $user->id,
                'plan_id' => $index % 2 === 0 ? $starterPlan->id : $proPlan->id,
                'start_date' => now()->subDays(5 + $index)->toDateString(),
                'end_date' => now()->addMonths(1)->toDateString(),
                'status' => 'active',
            ]);
        }

        $referredUser = $users->first();
        $inviter = $users->skip(1)->first();

        if ($referredUser && $inviter) {
            $referredUser->update(['referred_by' => $inviter->id]);

            Referral::query()->create([
                'inviter_id' => $inviter->id,
                'invited_user_id' => $referredUser->id,
            ]);
        }

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

        $attemptUser = $users->last();

        if ($attemptUser) {
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

        User::query()->firstOrCreate([
            'email' => 'test@example.com',
        ], [
            'name' => 'Test User',
            'password' => Hash::make('password'),
            'role' => 'user',
        ]);
    }
}
