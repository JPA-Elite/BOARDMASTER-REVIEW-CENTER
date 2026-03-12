<?php

namespace App\Http\Middleware;

use Illuminate\Http\Request;
use Inertia\Middleware;
use App\Models\QuizAttempt;

class HandleInertiaRequests extends Middleware
{
    /**
     * The root template that is loaded on the first page visit.
     *
     * @var string
     */
    protected $rootView = 'app';

    /**
     * Determine the current asset version.
     */
    public function version(Request $request): ?string
    {
        return parent::version($request);
    }

    /**
     * Define the props that are shared by default.
     *
     * @return array<string, mixed>
     */
    public function share(Request $request): array
    {
        $admin = null;
        $user = $request->user();

        if ($user && $user->role === 'admin') {
            $recentAttempts = QuizAttempt::query()
                ->with(['user:id,name', 'subject:id,name'])
                ->latest()
                ->limit(50)
                ->get()
                ->map(fn (QuizAttempt $attempt) => [
                    'id' => $attempt->id,
                    'title' => $attempt->subject?->name ?? 'Quiz Attempt',
                    'message' => ($attempt->user?->name ?? 'User')
                        .' scored '
                        .$attempt->score
                        .'/'
                        .$attempt->total_questions,
                    'time' => $attempt->created_at?->format('M d, Y h:i A'),
                ]);

            $admin = [
                'notificationCount' => $recentAttempts->count(),
                'notifications' => $recentAttempts,
            ];
        }

        return [
            ...parent::share($request),
            'auth' => [
                'user' => $user,
            ],
            'admin' => $admin,
            'flash' => [
                'success' => fn () => $request->session()->get('success'),
                'error' => fn () => $request->session()->get('error'),
            ],
        ];
    }
}
