<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\QuizAttempt;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class QuizAttemptController extends Controller
{
    public function index(): Response
    {
        $attempts = QuizAttempt::query()
            ->with(['user:id,name,email', 'subject:id,name'])
            ->withCount('answers')
            ->latest()
            ->paginate(15)
            ->through(fn (QuizAttempt $attempt) => [
                'id' => $attempt->id,
                'user' => $attempt->user?->name,
                'user_email' => $attempt->user?->email,
                'subject' => $attempt->subject?->name,
                'score' => $attempt->score,
                'total_questions' => $attempt->total_questions,
                'answers_count' => $attempt->answers_count,
                'created_at' => $attempt->created_at?->format('M d, Y h:i A'),
            ]);

        return Inertia::render('Admin/QuizAttempts/Index', [
            'attempts' => $attempts,
        ]);
    }

    public function show(QuizAttempt $quizAttempt): Response
    {
        $quizAttempt->load([
            'user:id,name,email',
            'subject:id,name',
            'answers.question:id,question',
            'answers.selectedChoice:id,choice_label,choice_text',
        ]);

        return Inertia::render('Admin/QuizAttempts/Show', [
            'attempt' => [
                'id' => $quizAttempt->id,
                'user' => $quizAttempt->user?->name,
                'user_email' => $quizAttempt->user?->email,
                'subject' => $quizAttempt->subject?->name,
                'score' => $quizAttempt->score,
                'total_questions' => $quizAttempt->total_questions,
                'created_at' => $quizAttempt->created_at?->format('M d, Y h:i A'),
                'answers' => $quizAttempt->answers->map(fn ($answer) => [
                    'id' => $answer->id,
                    'question' => $answer->question?->question,
                    'selected_choice' => $answer->selectedChoice
                        ? "{$answer->selectedChoice->choice_label}. {$answer->selectedChoice->choice_text}"
                        : null,
                    'is_correct' => (bool) $answer->is_correct,
                ])->values(),
            ],
        ]);
    }

    public function destroy(QuizAttempt $quizAttempt): RedirectResponse
    {
        $quizAttempt->delete();

        return redirect()
            ->route('admin.quiz-attempts.index')
            ->with('success', 'Quiz attempt deleted successfully.');
    }
}
