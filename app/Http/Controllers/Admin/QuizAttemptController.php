<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\QuizAttempt;
use App\Services\Admin\QuizAttemptService;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class QuizAttemptController extends Controller
{
    public function __construct(private readonly QuizAttemptService $attempts)
    {
    }

    public function index(): Response
    {
        $attempts = $this->attempts->paginate();

        return Inertia::render('Admin/QuizAttempts/Index', [
            'attempts' => $attempts,
        ]);
    }

    public function show(QuizAttempt $quizAttempt): Response
    {
        return Inertia::render('Admin/QuizAttempts/Show', [
            'attempt' => $this->attempts->getDetails($quizAttempt),
        ]);
    }

    public function destroy(QuizAttempt $quizAttempt): RedirectResponse
    {
        $this->attempts->delete($quizAttempt);

        return redirect()
            ->route('admin.quiz-attempts.index')
            ->with('success', 'Quiz attempt deleted successfully.');
    }
}
