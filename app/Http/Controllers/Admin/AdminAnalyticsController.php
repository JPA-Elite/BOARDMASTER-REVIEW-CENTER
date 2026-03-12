<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Question;
use App\Models\User;
use Carbon\Carbon;
use Inertia\Inertia;
use Inertia\Response;

class AdminAnalyticsController extends Controller
{
    public function __invoke(): Response
    {
        $signups = $this->lastMonths(6)
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

        $content = $this->lastMonths(6)
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

        return Inertia::render('Admin/Analytics', [
            'signups' => $signups,
            'content' => $content,
        ]);
    }

    private function lastMonths(int $count)
    {
        return collect(range($count - 1, 0))
            ->map(fn (int $offset) => now()->copy()->subMonthsNoOverflow($offset)->startOfMonth());
    }
}
