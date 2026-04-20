<?php

namespace App\Services\Mobile;

use App\Models\Plan;
use App\Models\Question;
use App\Models\QuestionChoice;
use App\Models\QuizAttempt;
use App\Models\Subject;
use App\Models\User;
use App\Models\UserSubscription;
use App\Repositories\Contracts\PlanRepositoryInterface;
use App\Repositories\Contracts\QuestionRepositoryInterface;
use App\Repositories\Contracts\QuizAttemptRepositoryInterface;
use App\Repositories\Contracts\SubjectRepositoryInterface;
use App\Repositories\Contracts\UserSubscriptionRepositoryInterface;
use App\Services\ReferralRewardsService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

class MobilePracticeService
{
    public function __construct(
        private readonly PlanRepositoryInterface $plans,
        private readonly SubjectRepositoryInterface $subjects,
        private readonly QuestionRepositoryInterface $questions,
        private readonly QuizAttemptRepositoryInterface $quizAttempts,
        private readonly UserSubscriptionRepositoryInterface $subscriptions,
        private readonly ReferralRewardsService $rewards,
    ) {
    }

    public function subjects(Request $request): JsonResponse
    {
        /** @var User|null $user */
        $user = $request->user();
        if (! $user) {
            return response()->json([
                'message' => 'Unauthorized.',
            ], 401);
        }

        $subscription = $this->resolveOrCreateSubscription($user);
        $plan = $subscription?->plan;
        $planAccesses = collect();
        if ($plan) {
            $planAccesses = $plan->subjectAccesses()
                ->with(['subject' => fn ($subjectQuery) => $subjectQuery->withCount('questions')])
                ->orderBy('subject_id')
                ->get()
                ->filter(fn ($access) => $access->subject instanceof Subject)
                ->keyBy('subject_id');
        }

        $rewardLimits = $this->rewards->rewardSubjectLimits($user);

        $subjects = $this->subjects->query()
            ->withCount('questions')
            ->orderBy('id')
            ->get()
            ->values()
            ->map(function (Subject $subject, int $index) use ($planAccesses, $rewardLimits): array {
                $totalQuestions = (int) ($subject->questions_count ?? 0);
                $planAccess = $planAccesses->get($subject->id);
                $planLimit = $planAccess
                    ? $this->normalizeLimit((int) $planAccess->question_limit, $totalQuestions)
                    : null;
                $rewardLimit = array_key_exists($subject->id, $rewardLimits)
                    ? $this->normalizeLimit((int) $rewardLimits[$subject->id], $totalQuestions)
                    : null;

                $hasAccess = $planLimit !== null || $rewardLimit !== null;
                $limits = collect([$planLimit, $rewardLimit])->filter();
                $effectiveLimit = $limits->isNotEmpty()
                    ? (int) $limits->max()
                    : 0;

                if ($hasAccess && $effectiveLimit <= 0) {
                    $effectiveLimit = $totalQuestions;
                }

                return [
                    'id' => $subject->id,
                    'code' => $this->subjectCode($subject->name),
                    'title' => $subject->name,
                    'group_key' => $this->subjectGroupKey($subject->name),
                    'group_label' => $this->subjectGroupLabel($subject->name),
                    'total_questions' => $totalQuestions,
                    'question_limit' => $hasAccess ? $effectiveLimit : 0,
                    'is_accessible' => $hasAccess,
                    'color_hex' => $this->subjectColor($index),
                ];
            });

        return response()->json([
            'data' => $subjects->all(),
            'meta' => [
                'plan_id' => $plan?->id,
                'plan_name' => $plan?->name,
                'tier' => $plan ? $this->resolveTier($plan) : 'free',
                'plan_group' => $plan?->groupKey(),
                'group_label' => $plan?->groupTitle(),
                'sub_plan_label' => $plan?->subPlanTitle(),
            ],
        ]);
    }

    public function generate(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'subject_id' => ['required', 'integer', 'exists:subjects,id'],
            'total_questions' => ['required', 'integer', 'min:1', 'max:1000'],
        ]);

        /** @var User|null $user */
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
                'message' => $this->inactiveSubscriptionMessage(),
            ], 422);
        }

        $access = $plan->subjectAccesses()
            ->where('subject_id', $validated['subject_id'])
            ->first();
        $rewardLimits = $this->rewards->rewardSubjectLimits($user);
        $rewardLimit = $rewardLimits[$validated['subject_id']] ?? null;

        if (! $access && $rewardLimit === null) {
            return response()->json([
                'message' => 'This subject is not accessible in your current plan.',
            ], 403);
        }

        $requestedCount = (int) $validated['total_questions'];
        $availableCount = (int) $this->questions->query()
            ->where('subject_id', $validated['subject_id'])
            ->count();
        $planLimit = $access
            ? $this->normalizeLimit((int) $access->question_limit, $availableCount)
            : null;
        $rewardLimitNormalized = $rewardLimit !== null
            ? $this->normalizeLimit((int) $rewardLimit, $availableCount)
            : null;

        $limits = collect([$planLimit, $rewardLimitNormalized])->filter();
        $maxAllowed = $limits->isNotEmpty()
            ? (int) $limits->max()
            : $availableCount;

        $effectiveCount = min($requestedCount, $maxAllowed, $availableCount);

        $questions = $this->questions->query()
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

        $displaySeed = random_int(1, PHP_INT_MAX);
        $payload = $questions
            ->map(fn (Question $question): ?array => $this->questionPayload($question, $displaySeed))
            ->filter()
            ->values();

        return response()->json([
            'data' => $payload->all(),
            'meta' => [
                'served_questions' => $payload->count(),
                'requested_questions' => $requestedCount,
                'max_allowed_by_plan' => $maxAllowed,
                'available_questions' => $availableCount,
                'display_seed' => $displaySeed,
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
            'display_seed' => ['nullable', 'integer', 'min:1'],
        ]);

        /** @var User|null $user */
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
                'message' => $this->inactiveSubscriptionMessage(),
            ], 422);
        }

        $access = $plan->subjectAccesses()
            ->where('subject_id', $validated['subject_id'])
            ->first();
        $rewardLimits = $this->rewards->rewardSubjectLimits($user);
        $rewardLimit = $rewardLimits[$validated['subject_id']] ?? null;

        if (! $access && $rewardLimit === null) {
            return response()->json([
                'message' => 'This subject is not accessible in your current plan.',
            ], 403);
        }

        $answers = collect($validated['answers']);
        $planLimit = $access
            ? $this->normalizeLimit((int) $access->question_limit, $answers->count())
            : null;
        $rewardLimitNormalized = $rewardLimit !== null
            ? $this->normalizeLimit((int) $rewardLimit, $answers->count())
            : null;
        $limits = collect([$planLimit, $rewardLimitNormalized])->filter();
        $maxAllowed = $limits->isNotEmpty()
            ? (int) $limits->max()
            : $answers->count();

        if ($answers->count() > $maxAllowed) {
            return response()->json([
                'message' => 'Selected question count exceeds your plan limit.',
            ], 422);
        }

        $questionIds = $answers->pluck('question_id')->unique()->values();
        $questions = $this->questions->query()
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
        $displaySeed = (int) ($validated['display_seed'] ?? 0);

        /** @var array<int, array<string, QuestionChoice>> $displayChoiceLookups */
        $displayChoiceLookups = [];
        if ($displaySeed > 0) {
            foreach ($questions as $question) {
                $displayChoiceLookups[(int) $question->id] = $this->choiceLookupByDisplayLabel($question, $displaySeed);
            }
        }

        foreach ($answers as $answer) {
            $questionId = (int) $answer['question_id'];
            $question = $questions->get($questionId);
            if (! $question) {
                continue;
            }

            $selectedLabel = strtoupper(trim((string) ($answer['selected_choice'] ?? '')));
            $selectedChoice = null;
            if ($selectedLabel !== '') {
                $selectedChoice = $displayChoiceLookups[$questionId][$selectedLabel] ?? null;

                if (! ($selectedChoice instanceof QuestionChoice)) {
                    // Backward compatibility for clients still submitting the original DB choice labels.
                    $selectedChoice = $question->choices->firstWhere('choice_label', $selectedLabel);
                }
            }
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

        if ($displaySeed <= 0) {
            $displaySeed = random_int(1, PHP_INT_MAX);
        }

        /** @var QuizAttempt $attempt */
        $attempt = $this->quizAttempts->create([
            'user_id' => $user->id,
            'subject_id' => $validated['subject_id'],
            'total_questions' => $answers->count(),
            'score' => $score,
            'display_seed' => $displaySeed,
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
                'display_seed' => $displaySeed,
            ],
        ]);
    }

    public function attempts(Request $request): JsonResponse
    {
        /** @var User|null $user */
        $user = $request->user();
        if (! $user) {
            return response()->json([
                'message' => 'Unauthorized.',
            ], 401);
        }

        $attempts = $this->quizAttempts->query()
            ->where('user_id', $user->id)
            ->with('subject:id,name')
            ->orderByDesc('created_at')
            ->paginate(5);

        return response()->json([
            'data' => collect($attempts->items())->map(fn (QuizAttempt $attempt) => [
                'id' => $attempt->id,
                'subject_id' => $attempt->subject_id,
                'subject' => $attempt->subject?->name,
                'subject_code' => $attempt->subject
                    ? $this->subjectCode($attempt->subject->name)
                    : 'SUBJ',
                'score' => $attempt->score,
                'total_questions' => $attempt->total_questions,
                'created_at' => $attempt->created_at?->toDateTimeString(),
            ])->values(),
            'pagination' => [
                'current_page' => $attempts->currentPage(),
                'last_page' => $attempts->lastPage(),
                'next_page_url' => $attempts->nextPageUrl(),
                'has_more' => $attempts->hasMorePages(),
            ],
        ]);
    }

    public function attemptDetails(Request $request, QuizAttempt $quizAttempt): JsonResponse
    {
        /** @var User|null $user */
        $user = $request->user();
        if (! $user) {
            return response()->json([
                'message' => 'Unauthorized.',
            ], 401);
        }

        if ((int) $quizAttempt->user_id !== (int) $user->id) {
            return response()->json([
                'message' => 'Attempt not found.',
            ], 404);
        }

        $quizAttempt->load([
            'subject:id,name',
            'answers.question.choices' => fn ($query) => $query->orderBy('choice_label'),
            'answers.selectedChoice',
        ]);

        $questions = [];
        $answers = [];
        $displaySeed = (int) ($quizAttempt->display_seed ?? 0);
        if ($displaySeed <= 0) {
            $displaySeed = (int) $quizAttempt->id;
        }

        foreach ($quizAttempt->answers as $answer) {
            $question = $answer->question;
            if (! $question) {
                continue;
            }

            $questionPayload = $this->questionPayload($question, $displaySeed);
            if (! $questionPayload) {
                continue;
            }

            $questions[] = $questionPayload;

            $selectedLabel = null;
            $selectedChoiceId = (int) ($answer->selected_choice_id ?? 0);
            if ($selectedChoiceId > 0) {
                $selectedLabel = $this->displayLabelLookupByChoiceId($question, $displaySeed)[$selectedChoiceId] ?? null;
            }

            if (! $selectedLabel) {
                $legacyLabel = $answer->selectedChoice?->choice_label;
                $selectedLabel = $legacyLabel
                    ? strtoupper(trim((string) $legacyLabel))
                    : null;
            }

            $answers[] = [
                'question_id' => $question->id,
                'selected_choice' => $selectedLabel,
            ];
        }

        return response()->json([
            'data' => [
                'id' => $quizAttempt->id,
                'subject' => [
                    'id' => $quizAttempt->subject_id,
                    'code' => $quizAttempt->subject
                        ? $this->subjectCode($quizAttempt->subject->name)
                        : 'SUBJ',
                    'title' => $quizAttempt->subject?->name ?? 'Unknown Subject',
                ],
                'score' => $quizAttempt->score,
                'total_questions' => $quizAttempt->total_questions,
                'created_at' => $quizAttempt->created_at?->toDateTimeString(),
                'display_seed' => $displaySeed,
                'questions' => $questions,
                'answers' => $answers,
            ],
        ]);
    }

    public function deleteAttempts(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'attempt_ids' => ['required', 'array', 'min:1'],
            'attempt_ids.*' => ['integer'],
        ]);

        /** @var User|null $user */
        $user = $request->user();
        if (! $user) {
            return response()->json([
                'message' => 'Unauthorized.',
            ], 401);
        }

        $ids = collect($validated['attempt_ids'])->unique()->values();
        $deleted = $this->quizAttempts->query()
            ->where('user_id', $user->id)
            ->whereIn('id', $ids)
            ->delete();

        return response()->json([
            'message' => 'Attempts deleted successfully.',
            'deleted' => $deleted,
        ]);
    }

    public function clearAttempts(Request $request): JsonResponse
    {
        /** @var User|null $user */
        $user = $request->user();
        if (! $user) {
            return response()->json([
                'message' => 'Unauthorized.',
            ], 401);
        }

        $deleted = $this->quizAttempts->query()
            ->where('user_id', $user->id)
            ->delete();

        return response()->json([
            'message' => 'All attempts cleared successfully.',
            'deleted' => $deleted,
        ]);
    }

    private function resolveOrCreateSubscription(User $user): ?UserSubscription
    {
        $today = now()->toDateString();
        $this->subscriptions->query()
            ->where('user_id', $user->id)
            ->where('status', 'active')
            ->whereNotNull('end_date')
            ->whereDate('end_date', '<', $today)
            ->update([
                'status' => 'expired',
                'end_date' => $today,
            ]);

        $active = $this->subscriptions->query()
            ->with('plan')
            ->where('user_id', $user->id)
            ->where('status', 'active')
            ->where(function ($query) use ($today) {
                $query->whereNull('end_date')
                    ->orWhereDate('end_date', '>=', $today);
            })
            ->orderByDesc('start_date')
            ->orderByDesc('id')
            ->first();

        if ($active instanceof UserSubscription) {
            return $active;
        }

        $defaultPlan = $this->plans->query()
            ->orderByDesc('is_default')
            ->orderBy('sort_order')
            ->orderBy('price')
            ->orderBy('id')
            ->first();

        if (! ($defaultPlan instanceof Plan)) {
            return null;
        }

        if ($defaultPlan->isFreeTrial() && $this->hasConsumedFreeTrial($user)) {
            return null;
        }

        $billingCycle = $this->normalizeBillingCycle($defaultPlan->billing_cycle);
        $endDate = $this->calculateEndDate(now(), $billingCycle);

        /** @var UserSubscription $subscription */
        $subscription = $this->subscriptions->create([
            'user_id' => $user->id,
            'plan_id' => $defaultPlan->id,
            'billing_cycle' => $billingCycle,
            'start_date' => now()->toDateString(),
            'end_date' => $endDate,
            'status' => 'active',
        ]);

        return $subscription->load('plan');
    }

    private function resolveTier(?Plan $plan): string
    {
        if (! $plan) {
            return 'free';
        }

        if ($plan->isFreeTrial()) {
            return 'free';
        }

        return 'premium';
    }

    private function normalizeBillingCycle(?string $billingCycle): string
    {
        return in_array($billingCycle, Plan::billingCycles(), true)
            ? (string) $billingCycle
            : Plan::BILLING_MONTHLY;
    }

    private function calculateEndDate(Carbon|string $startDate, string $billingCycle): string
    {
        return Plan::calculateEndDateForCycle($startDate, $billingCycle)->toDateString();
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

    private function normalizeLimit(int $limit, int $totalQuestions): int
    {
        if ($totalQuestions <= 0) {
            return 0;
        }

        if ($limit <= 0) {
            return $totalQuestions;
        }

        return min($limit, $totalQuestions);
    }

    private function questionPayload(Question $question, int $displaySeed): ?array
    {
        $choices = [];
        $rationales = [];
        $correctKey = null;

        foreach ($this->orderedChoiceModels($question, $displaySeed) as $index => $choice) {
            $displayLabel = $this->choiceDisplayLabel($index);

            $choices[$displayLabel] = (string) $choice->choice_text;
            $rationales[$displayLabel] = $choice->explanation
                ? (string) $choice->explanation
                : ($choice->is_correct
                    ? 'This option is correct.'
                    : 'This option is not a correct answer.');

            if ($choice->is_correct && ! $correctKey) {
                $correctKey = $displayLabel;
            }
        }

        if (empty($choices)) {
            return null;
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
            'display_seed' => $displaySeed,
        ];
    }

    /**
     * @return array<string, QuestionChoice>
     */
    private function choiceLookupByDisplayLabel(Question $question, int $displaySeed): array
    {
        $lookup = [];
        foreach ($this->orderedChoiceModels($question, $displaySeed) as $index => $choice) {
            $lookup[$this->choiceDisplayLabel($index)] = $choice;
        }

        return $lookup;
    }

    /**
     * @return array<int, string>
     */
    private function displayLabelLookupByChoiceId(Question $question, int $displaySeed): array
    {
        $lookup = [];
        foreach ($this->orderedChoiceModels($question, $displaySeed) as $index => $choice) {
            $choiceId = (int) ($choice->id ?? 0);
            if ($choiceId <= 0) {
                continue;
            }

            $lookup[$choiceId] = $this->choiceDisplayLabel($index);
        }

        return $lookup;
    }

    private function choiceDisplayLabel(int $index): string
    {
        $alphabet = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ';
        if ($index < 26) {
            return $alphabet[$index];
        }

        $label = '';
        $value = $index + 1;
        while ($value > 0) {
            $value--;
            $label = $alphabet[$value % 26] . $label;
            $value = intdiv($value, 26);
        }

        return $label;
    }

    /**
     * @return array<int, QuestionChoice>
     */
    private function orderedChoiceModels(Question $question, int $displaySeed): array
    {
        $original = $question->choices->values()->all();
        $shuffled = $question->choices
            ->sortBy(function (QuestionChoice $choice) use ($question, $displaySeed): string {
                $choiceLabel = strtoupper(trim((string) $choice->choice_label));
                $choiceId = (int) ($choice->id ?? 0);
                $questionId = (int) ($question->id ?? 0);

                return hash('sha256', "{$displaySeed}:{$questionId}:{$choiceId}:{$choiceLabel}");
            })
            ->values()
            ->all();

        // Enforce visible shuffling: if hashed ordering matches the original
        // DB label order (A,B,C,D...), rotate deterministically by 1..N-1.
        if (count($shuffled) > 1 && $this->sameChoiceOrder($original, $shuffled)) {
            $questionId = (int) ($question->id ?? 0);
            $rotationSeed = hash('sha256', "{$displaySeed}:{$questionId}:rotate");
            $rotation = (hexdec(substr($rotationSeed, 0, 8)) % (count($shuffled) - 1)) + 1;
            $shuffled = array_merge(
                array_slice($shuffled, $rotation),
                array_slice($shuffled, 0, $rotation),
            );
        }

        return $shuffled;
    }

    /**
     * @param  array<int, QuestionChoice>  $a
     * @param  array<int, QuestionChoice>  $b
     */
    private function sameChoiceOrder(array $a, array $b): bool
    {
        if (count($a) !== count($b)) {
            return false;
        }

        foreach ($a as $index => $choice) {
            $leftId = (int) ($choice->id ?? 0);
            $rightId = (int) (($b[$index]->id ?? 0));
            if ($leftId !== $rightId) {
                return false;
            }
        }

        return true;
    }

    private function subjectGroupKey(string $name): string
    {
        $normalized = strtolower(trim($name));
        if (str_starts_with($normalized, 'nursing practice')) {
            return 'mock_board_exam';
        }

        return 'nursing_concepts';
    }

    private function subjectGroupLabel(string $name): string
    {
        return $this->subjectGroupKey($name) === 'mock_board_exam'
            ? 'Mock Board Exam'
            : 'All Nursing Concepts';
    }

    private function inactiveSubscriptionMessage(): string
    {
        return 'No active subscription found. Your free trial may have ended. Please choose a paid plan.';
    }

    private function hasConsumedFreeTrial(User $user): bool
    {
        return $this->subscriptions->query()
            ->where('user_id', $user->id)
            ->whereHas('plan', function ($query) {
                $query->where('plan_group', 'free_trial')
                    ->orWhere('billing_cycle', Plan::BILLING_TRIAL)
                    ->orWhere(function ($legacyQuery) {
                        $legacyQuery->where('is_default', true)
                            ->where('price', '<=', 0);
                    })
                    ->orWhere(function ($legacyQuery) {
                        $legacyQuery->where('price', '<=', 0)
                            ->where(function ($nameQuery) {
                                $nameQuery->whereRaw('LOWER(name) LIKE ?', ['%trial%'])
                                    ->orWhereRaw('LOWER(name) LIKE ?', ['%free%']);
                            });
                    });
            })
            ->exists();
    }
}
