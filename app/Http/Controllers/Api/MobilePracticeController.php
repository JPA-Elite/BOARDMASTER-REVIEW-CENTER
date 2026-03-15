<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Plan;
use App\Models\Question;
use App\Models\QuizAttempt;
use App\Models\Subject;
use App\Models\User;
use App\Models\UserSubscription;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

class MobilePracticeController extends Controller
{
    public function subjects(Request $request): JsonResponse
    {
        $user = $request->user();
        if (! $user) {
            return response()->json([
                'message' => 'Unauthorized.',
            ], 401);
        }

        $subscription = $this->resolveOrCreateSubscription($user);
        $plan = $subscription?->plan;
        if (! $plan) {
            return response()->json([
                'message' => 'No plan is assigned to your account yet.',
            ], 422);
        }

        $plan->load([
            'subjectAccesses' => fn ($query) => $query
                ->with(['subject' => fn ($subjectQuery) => $subjectQuery->withCount('questions')])
                ->orderBy('subject_id'),
        ]);

        $subjects = $plan->subjectAccesses
            ->filter(fn ($access) => $access->subject instanceof Subject)
            ->values()
            ->map(function ($access, int $index): array {
                $subject = $access->subject;
                $totalQuestions = (int) ($subject->questions_count ?? 0);
                $limitValue = (int) $access->question_limit;
                if ($limitValue <= 0) {
                    $limitValue = $totalQuestions;
                } else {
                    $limitValue = min($limitValue, $totalQuestions);
                }

                return [
                    'id' => $subject->id,
                    'code' => $this->subjectCode($subject->name),
                    'title' => $subject->name,
                    'total_questions' => $totalQuestions,
                    'question_limit' => $limitValue,
                    'color_hex' => $this->subjectColor($index),
                ];
            });

        return response()->json([
            'data' => $subjects->all(),
            'meta' => [
                'plan_id' => $plan->id,
                'plan_name' => $plan->name,
                'tier' => $this->resolveTier($plan),
            ],
        ]);
    }

    public function generate(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'subject_id' => ['required', 'integer', 'exists:subjects,id'],
            'total_questions' => ['required', 'integer', 'min:1', 'max:1000'],
        ]);

        $user = $request->user();
        if (! $user) {
            return response()->json([
                'message' => 'Unauthorized.',
            ], 401);
        }

        $subscription = $this->resolveOrCreateSubscription($user);
        $plan = $subscription?->plan;
        if (! $plan) {
            return response()->json([
                'message' => 'No plan is assigned to your account yet.',
            ], 422);
        }

        $access = $plan->subjectAccesses()
            ->where('subject_id', $validated['subject_id'])
            ->first();

        if (! $access) {
            return response()->json([
                'message' => 'This subject is not accessible in your current plan.',
            ], 403);
        }

        $requestedCount = (int) $validated['total_questions'];
        $availableCount = (int) Question::query()
            ->where('subject_id', $validated['subject_id'])
            ->count();
        $rawLimit = (int) $access->question_limit;
        $maxAllowedByPlan = $rawLimit <= 0 ? $availableCount : $rawLimit;
        $effectiveCount = min($requestedCount, $maxAllowedByPlan, $availableCount);

        $questions = Question::query()
            ->where('subject_id', $validated['subject_id'])
            ->with(['choices' => fn ($query) => $query->orderBy('choice_label')])
            ->inRandomOrder()
            ->limit($effectiveCount)
            ->get();

        if ($questions->isEmpty()) {
            return response()->json([
                'message' => 'No questions available for this subject.',
            ], 422);
        }

        $payload = $questions->map(function (Question $question): array {
            $choices = [];
            $rationales = [];
            $correctKey = null;

            foreach ($question->choices as $choice) {
                $label = strtoupper(trim((string) $choice->choice_label));
                if ($label === '') {
                    continue;
                }

                $choices[$label] = (string) $choice->choice_text;
                $rationales[$label] = $choice->explanation
                    ? (string) $choice->explanation
                    : ($choice->is_correct
                        ? 'This option is correct.'
                        : 'This option does not satisfy this question.');

                if ($choice->is_correct && ! $correctKey) {
                    $correctKey = $label;
                }
            }

            if (! $correctKey) {
                $correctKey = array_key_first($choices) ?: 'A';
            }

            return [
                'id' => $question->id,
                'subject_id' => (string) $question->subject_id,
                'question' => (string) $question->question,
                'choices' => $choices,
                'correct_key' => $correctKey,
                'rationales' => $rationales,
            ];
        })->values();

        return response()->json([
            'data' => $payload->all(),
            'meta' => [
                'served_questions' => $payload->count(),
                'requested_questions' => $requestedCount,
                'max_allowed_by_plan' => $maxAllowedByPlan,
                'available_questions' => $availableCount,
            ],
        ]);
    }

    public function submit(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'subject_id' => ['required', 'integer', 'exists:subjects,id'],
            'answers' => ['required', 'array', 'min:1'],
            'answers.*.question_id' => ['required', 'integer', 'distinct', 'exists:questions,id'],
            'answers.*.selected_choice' => ['nullable', 'string', 'max:5'],
        ]);

        $user = $request->user();
        if (! $user) {
            return response()->json([
                'message' => 'Unauthorized.',
            ], 401);
        }

        $subscription = $this->resolveOrCreateSubscription($user);
        $plan = $subscription?->plan;
        if (! $plan) {
            return response()->json([
                'message' => 'No plan is assigned to your account yet.',
            ], 422);
        }

        $access = $plan->subjectAccesses()
            ->where('subject_id', $validated['subject_id'])
            ->first();

        if (! $access) {
            return response()->json([
                'message' => 'This subject is not accessible in your current plan.',
            ], 403);
        }

        $answers = collect($validated['answers']);
        $rawLimit = (int) $access->question_limit;
        $maxAllowedByPlan = $rawLimit <= 0 ? $answers->count() : $rawLimit;

        if ($rawLimit > 0 && $answers->count() > $maxAllowedByPlan) {
            return response()->json([
                'message' => 'Selected question count exceeds your plan limit.',
            ], 422);
        }

        $questionIds = $answers->pluck('question_id')->unique()->values();
        $questions = Question::query()
            ->whereIn('id', $questionIds)
            ->with(['choices' => fn ($query) => $query->orderBy('choice_label')])
            ->get()
            ->keyBy('id');

        if ($questions->count() !== $questionIds->count()) {
            return response()->json([
                'message' => 'Some selected questions are invalid.',
            ], 422);
        }

        foreach ($questions as $question) {
            if ((int) $question->subject_id !== (int) $validated['subject_id']) {
                return response()->json([
                    'message' => 'Question subject mismatch detected.',
                ], 422);
            }
        }

        $score = 0;
        $answerPayloads = [];

        foreach ($answers as $answer) {
            $questionId = (int) $answer['question_id'];
            $question = $questions->get($questionId);
            if (! $question) {
                continue;
            }

            $selectedLabel = strtoupper(trim((string) ($answer['selected_choice'] ?? '')));
            $selectedChoice = $selectedLabel === ''
                ? null
                : $question->choices->firstWhere('choice_label', $selectedLabel);
            $isCorrect = (bool) ($selectedChoice?->is_correct ?? false);

            if ($isCorrect) {
                $score += 1;
            }

            $answerPayloads[] = [
                'question_id' => $questionId,
                'selected_choice_id' => $selectedChoice?->id,
                'is_correct' => $isCorrect,
            ];
        }

        $attempt = QuizAttempt::query()->create([
            'user_id' => $user->id,
            'subject_id' => $validated['subject_id'],
            'total_questions' => $answers->count(),
            'score' => $score,
        ]);

        if (! empty($answerPayloads)) {
            $attempt->answers()->createMany($answerPayloads);
        }

        return response()->json([
            'message' => 'Quiz submitted successfully.',
            'data' => [
                'attempt_id' => $attempt->id,
                'score' => $score,
                'total_questions' => $answers->count(),
            ],
        ]);
    }

    private function resolveOrCreateSubscription(User $user): ?UserSubscription
    {
        $active = $user->subscriptions()
            ->with('plan')
            ->where('status', 'active')
            ->orderByDesc('start_date')
            ->orderByDesc('id')
            ->first();

        if ($active) {
            return $active;
        }

        $defaultPlan = Plan::query()
            ->orderByDesc('is_default')
            ->orderBy('price')
            ->orderBy('id')
            ->first();

        if (! $defaultPlan) {
            return null;
        }

        $billingCycle = $this->normalizeBillingCycle($defaultPlan->billing_cycle);
        $tier = $this->resolveTier($defaultPlan);
        $endDate = $tier === 'free'
            ? null
            : $this->calculateEndDate(now(), $billingCycle);

        return UserSubscription::query()
            ->create([
                'user_id' => $user->id,
                'plan_id' => $defaultPlan->id,
                'billing_cycle' => $billingCycle,
                'start_date' => now()->toDateString(),
                'end_date' => $endDate,
                'status' => 'active',
            ])
            ->load('plan');
    }

    private function resolveTier(?Plan $plan): string
    {
        if (! $plan) {
            return 'free';
        }

        if ((bool) $plan->is_default
            || (float) $plan->price <= 0
            || str_contains(strtolower($plan->name), 'free')) {
            return 'free';
        }

        return 'premium';
    }

    private function normalizeBillingCycle(?string $billingCycle): string
    {
        return $billingCycle === Plan::BILLING_YEARLY
            ? Plan::BILLING_YEARLY
            : Plan::BILLING_MONTHLY;
    }

    private function calculateEndDate(Carbon|string $startDate, string $billingCycle): string
    {
        $start = $startDate instanceof Carbon
            ? $startDate->copy()
            : Carbon::parse($startDate);

        return $billingCycle === Plan::BILLING_YEARLY
            ? $start->addYear()->toDateString()
            : $start->addMonth()->toDateString();
    }

    private function subjectCode(string $name): string
    {
        $clean = trim($name);
        if ($clean === '') {
            return 'SUBJ';
        }

        $parts = preg_split('/\s+/', $clean) ?: [];
        if (count($parts) === 1) {
            $single = preg_replace('/[^A-Za-z0-9]/', '', $parts[0]) ?: 'SUBJ';
            return strtoupper(substr($single, 0, 4));
        }

        $letters = '';
        foreach ($parts as $part) {
            $part = trim($part);
            if ($part === '') {
                continue;
            }
            $letters .= $part[0];
        }

        $letters = preg_replace('/[^A-Za-z0-9]/', '', $letters) ?: 'SUBJ';
        return strtoupper(substr($letters, 0, 4));
    }

    private function subjectColor(int $index): string
    {
        $colors = [
            '#9F76C0',
            '#70A764',
            '#F45A64',
            '#2CA6AA',
            '#F29C33',
            '#EF4DA8',
            '#4B8DDF',
        ];

        return $colors[$index % count($colors)];
    }
}
