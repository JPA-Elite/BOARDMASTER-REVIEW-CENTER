<?php

namespace App\Services\Admin;

use App\Repositories\Contracts\QuestionRepositoryInterface;
use App\Repositories\Contracts\UserRepositoryInterface;
use Carbon\Carbon;

class AnalyticsService
{
    public function __construct(
        private readonly UserRepositoryInterface $users,
        private readonly QuestionRepositoryInterface $questions,
    ) {
    }

    public function getAnalyticsData(): array
    {
        $signups = $this->lastMonths(6)
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

        $content = $this->lastMonths(6)
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

        return [
            'signups' => $signups,
            'content' => $content,
        ];
    }

    private function lastMonths(int $count)
    {
        return collect(range($count - 1, 0))
            ->map(fn (int $offset) => now()->copy()->subMonthsNoOverflow($offset)->startOfMonth());
    }
}
