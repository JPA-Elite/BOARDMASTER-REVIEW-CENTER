<?php

namespace App\Services\Admin;

use App\Repositories\Contracts\QuestionRepositoryInterface;
use App\Repositories\Contracts\QuizAttemptRepositoryInterface;
use App\Repositories\Contracts\UserRepositoryInterface;
use App\Repositories\Contracts\UserSubscriptionRepositoryInterface;
use Carbon\Carbon;

class DashboardService
{
    public function __construct(
        private readonly UserRepositoryInterface $users,
        private readonly QuestionRepositoryInterface $questions,
        private readonly UserSubscriptionRepositoryInterface $subscriptions,
        private readonly QuizAttemptRepositoryInterface $attempts,
    ) {
    }

    public function getDashboardData(): array
    {
        $now = now();
        $currentMonthStart = $now->copy()->startOfMonth();
        $previousMonthStart = $now->copy()->subMonthNoOverflow()->startOfMonth();
        $previousMonthEnd = $currentMonthStart->copy()->subSecond();

        $usersThisMonth = $this->users->countBetween($currentMonthStart, $now);
        $usersLastMonth = $this->users->countBetween($previousMonthStart, $previousMonthEnd);

        $pagesThisMonth = $this->questions->countBetween($currentMonthStart, $now);
        $pagesLastMonth = $this->questions->countBetween($previousMonthStart, $previousMonthEnd);

        $kpis = [
            [
                'title' => 'Total Users',
                'value' => $this->users->countAll(),
                'trend' => $this->percentageChange($usersThisMonth, $usersLastMonth),
            ],
            [
                'title' => 'Active Sessions',
                'value' => $this->subscriptions->countActive(),
                'trend' => null,
            ],
            [
                'title' => 'Total Pages',
                'value' => $this->questions->countAll(),
                'trend' => $this->percentageChange($pagesThisMonth, $pagesLastMonth),
            ],
            [
                'title' => 'Unread Notifications',
                'value' => $this->attempts->countSince($now->copy()->subDays(7)),
                'trend' => null,
            ],
        ];

        $userSignups = $this->lastMonths(12)
            ->map(function (Carbon $month) {
                $count = $this->users->query()
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
                $total = $this->questions->query()
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

        $recentAttempts = $this->attempts->getRecent(6)
            ->map(fn ($attempt) => [
                'id' => $attempt->id,
                'user' => $attempt->user?->name ?? 'Deleted User',
                'subject' => $attempt->subject?->name ?? 'Deleted Subject',
                'score' => $attempt->score,
                'total_questions' => $attempt->total_questions,
                'created_at' => $attempt->created_at?->toDateTimeString(),
            ]);

        return [
            'dateLabel' => $now->format('l, F j, Y'),
            'kpis' => $kpis,
            'userSignups' => $userSignups,
            'contentProduction' => $contentProduction,
            'recentAttempts' => $recentAttempts,
        ];
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
