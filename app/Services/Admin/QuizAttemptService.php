<?php

namespace App\Services\Admin;

use App\Models\QuizAttempt;
use App\Repositories\Contracts\QuizAttemptRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class QuizAttemptService
{
    public function __construct(private readonly QuizAttemptRepositoryInterface $attempts)
    {
    }

    public function paginate(): LengthAwarePaginator
    {
        $attempts = $this->attempts->paginateWithRelations();

        $attempts->through(fn (QuizAttempt $attempt) => [
            'id' => $attempt->id,
            'user' => $attempt->user?->name,
            'user_email' => $attempt->user?->email,
            'subject' => $attempt->subject?->name,
            'score' => $attempt->score,
            'total_questions' => $attempt->total_questions,
            'answers_count' => $attempt->answers_count,
            'created_at' => $attempt->created_at?->format('M d, Y h:i A'),
        ]);

        return $attempts;
    }

    public function getDetails(QuizAttempt $attempt): array
    {
        $attempt = $this->attempts->loadForShow($attempt);

        return [
            'id' => $attempt->id,
            'user' => $attempt->user?->name,
            'user_email' => $attempt->user?->email,
            'subject' => $attempt->subject?->name,
            'score' => $attempt->score,
            'total_questions' => $attempt->total_questions,
            'created_at' => $attempt->created_at?->format('M d, Y h:i A'),
            'answers' => $attempt->answers->map(fn ($answer) => [
                'id' => $answer->id,
                'question' => $answer->question?->question,
                'selected_choice' => $answer->selectedChoice
                    ? "{$answer->selectedChoice->choice_label}. {$answer->selectedChoice->choice_text}"
                    : null,
                'is_correct' => (bool) $answer->is_correct,
            ])->values(),
        ];
    }

    public function delete(QuizAttempt $attempt): void
    {
        $this->attempts->delete($attempt);
    }
}
