<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Plan;
use App\Models\Question;
use App\Models\QuizAttempt;
use App\Models\Subject;
use App\Models\User;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class AdminDashboardController extends Controller
{
    public function __invoke(Request $request): Response
    {
        $stats = [
            'users' => User::query()->count(),
            'admins' => User::query()->where('role', 'admin')->count(),
            'plans' => Plan::query()->count(),
            'subjects' => Subject::query()->count(),
            'questions' => Question::query()->count(),
            'quizAttempts' => QuizAttempt::query()->count(),
        ];

        $recentAttempts = QuizAttempt::query()
            ->with(['user:id,name', 'subject:id,name'])
            ->latest()
            ->limit(8)
            ->get()
            ->map(fn (QuizAttempt $attempt) => [
                'id' => $attempt->id,
                'user' => $attempt->user?->name ?? 'Deleted User',
                'subject' => $attempt->subject?->name ?? 'Deleted Subject',
                'score' => $attempt->score,
                'total_questions' => $attempt->total_questions,
                'created_at' => $attempt->created_at?->format('M d, Y h:i A'),
            ]);

        return Inertia::render('Admin/Dashboard', [
            'stats' => $stats,
            'recentAttempts' => $recentAttempts,
        ]);
    }
}
