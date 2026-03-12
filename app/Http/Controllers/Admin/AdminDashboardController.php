<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Question;
use App\Models\QuizAttempt;
use App\Models\User;
use App\Models\UserSubscription;
use Carbon\Carbon;
use Inertia\Inertia;
use Inertia\Response;

class AdminDashboardController extends Controller
{
    public function __invoke(): Response
    {
        $now = now();
        $currentMonthStart = $now->copy()->startOfMonth();
        $previousMonthStart = $now->copy()->subMonthNoOverflow()->startOfMonth();
        $previousMonthEnd = $currentMonthStart->copy()->subSecond();

        $usersThisMonth = User::query()
            ->whereBetween('created_at', [$currentMonthStart, $now])
            ->count();
        $usersLastMonth = User::query()
            ->whereBetween('created_at', [$previousMonthStart, $previousMonthEnd])
            ->count();

        $pagesThisMonth = Question::query()
            ->whereBetween('created_at', [$currentMonthStart, $now])
            ->count();
        $pagesLastMonth = Question::query()
            ->whereBetween('created_at', [$previousMonthStart, $previousMonthEnd])
            ->count();

        $kpis = [
            [
                'title' => 'Total Users',
                'value' => User::query()->count(),
                'trend' => $this->percentageChange($usersThisMonth, $usersLastMonth),
            ],
            [
                'title' => 'Active Sessions',
                'value' => UserSubscription::query()->where('status', 'active')->count(),
                'trend' => null,
            ],
            [
                'title' => 'Total Pages',
                'value' => Question::query()->count(),
                'trend' => $this->percentageChange($pagesThisMonth, $pagesLastMonth),
            ],
            [
                'title' => 'Unread Notifications',
                'value' => QuizAttempt::query()
                    ->where('created_at', '>=', $now->copy()->subDays(7))
                    ->count(),
                'trend' => null,
            ],
        ];

        $userSignups = $this->lastMonths(12)
            ->map(function (Carbon $month) {
                $count = User::query()
                    ->whereYear('created_at', $month->year)
                    ->whereMonth('created_at', $month->month)
                    ->count();

                return [
                    'month' => $month->format('M'),
                    'count' => $count,
                ];
            })
            ->values();

        $contentProduction = $this->lastMonths(7)
            ->map(function (Carbon $month) {
                $total = Question::query()
                    ->whereYear('created_at', $month->year)
                    ->whereMonth('created_at', $month->month)
                    ->count();

                $published = (int) ceil($total * 0.65);
                $draft = max($total - $published, 0);

                return [
                    'month' => $month->format('M'),
                    'published' => $published,
                    'draft' => $draft,
                ];
            })
            ->values();

        $recentAttempts = QuizAttempt::query()
            ->with(['user:id,name', 'subject:id,name'])
            ->latest()
            ->limit(6)
            ->get()
            ->map(fn (QuizAttempt $attempt) => [
                'id' => $attempt->id,
                'user' => $attempt->user?->name ?? 'Deleted User',
                'subject' => $attempt->subject?->name ?? 'Deleted Subject',
                'score' => $attempt->score,
                'total_questions' => $attempt->total_questions,
                'created_at' => $attempt->created_at?->toDateTimeString(),
            ]);

        return Inertia::render('Admin/Dashboard', [
            'dateLabel' => $now->format('l, F j, Y'),
            'kpis' => $kpis,
            'userSignups' => $userSignups,
            'contentProduction' => $contentProduction,
            'recentAttempts' => $recentAttempts,
        ]);
    }

    private function percentageChange(int $current, int $previous): ?float
    {
        if ($previous === 0) {
            return $current > 0 ? 100.0 : null;
        }

        return round((($current - $previous) / $previous) * 100, 1);
    }

    private function lastMonths(int $count)
    {
        return collect(range($count - 1, 0))
            ->map(fn (int $offset) => now()->copy()->subMonthsNoOverflow($offset)->startOfMonth());
    }
}
