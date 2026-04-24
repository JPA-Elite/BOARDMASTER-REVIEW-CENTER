<?php

namespace App\Services\Admin;

use App\Repositories\Contracts\PlanRepositoryInterface;
use App\Repositories\Contracts\QuestionRepositoryInterface;
use App\Repositories\Contracts\ReferralOfferRepositoryInterface;
use App\Repositories\Contracts\ReferralRepositoryInterface;
use App\Repositories\Contracts\SubjectRepositoryInterface;
use App\Repositories\Contracts\UserRepositoryInterface;
use App\Repositories\Contracts\UserSubscriptionRepositoryInterface;
use App\Models\UserActivity;
use Carbon\Carbon;

class DashboardService
{
    public function __construct(
        private readonly UserRepositoryInterface $users,
        private readonly QuestionRepositoryInterface $questions,
        private readonly SubjectRepositoryInterface $subjects,
        private readonly PlanRepositoryInterface $plans,
        private readonly UserSubscriptionRepositoryInterface $subscriptions,
        private readonly ReferralRepositoryInterface $referrals,
        private readonly ReferralOfferRepositoryInterface $referralOffers,
    ) {
    }

    public function getDashboardData(?int $signupYear = null, int $activityPage = 1, int $perPage = 10): array
    {
        $now = now();
        $resolvedYear = $signupYear ?? (int) $now->year;
        $minCreated = $this->users->query()->min('created_at');
        $maxCreated = $this->users->query()->max('created_at');
        $minYear = $minCreated ? Carbon::parse($minCreated)->year : $resolvedYear;
        $maxYear = $maxCreated ? Carbon::parse($maxCreated)->year : $resolvedYear;
        if ($resolvedYear < $minYear || $resolvedYear > $maxYear) {
            $resolvedYear = $maxYear;
        }
        $availableYears = range($maxYear, $minYear);
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
                'title' => 'Total Questions',
                'value' => $this->questions->countAll(),
                'trend' => $this->percentageChange($pagesThisMonth, $pagesLastMonth),
            ],
            [
                'title' => 'Total Subjects',
                'value' => $this->subjects->query()->count(),
                'trend' => null,
            ],
            [
                'title' => 'Total Plans',
                'value' => $this->plans->query()->count(),
                'trend' => null,
            ],
            [
                'title' => 'Active Subscriptions',
                'value' => $this->subscriptions->countActive(),
                'trend' => null,
            ],
            [
                'title' => 'Total Subscriptions',
                'value' => $this->subscriptions->query()->count(),
                'trend' => null,
            ],
            [
                'title' => 'Total Referrals',
                'value' => $this->referrals->query()->count(),
                'trend' => null,
            ],
            [
                'title' => 'Referral Offers',
                'value' => $this->referralOffers->query()->count(),
                'trend' => null,
            ],
        ];

        $userSignups = $this->monthsOfYear($resolvedYear)
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

        $recentActivities = UserActivity::query()
            ->with('user')
            ->orderByDesc('created_at')
            ->orderByDesc('id')
            ->paginate($perPage, ['*'], 'activity_page', max(1, $activityPage))
            ->withQueryString()
            ->through(fn (UserActivity $act) => [
                'id' => 'activity-'.$act->id,
                'type' => 'auth',
                'user' => $act->user?->name ?? 'Deleted User',
                'action' => $act->type,
                'platform' => $act->platform,
                'ip' => $act->ip,
                'user_agent' => $act->user_agent,
                'created_at' => $act->created_at?->toDateTimeString(),
            ]);

        return [
            'dateLabel' => $now->format('l, F j, Y'),
            'kpis' => $kpis,
            'userSignups' => $userSignups,
            'signupYears' => $availableYears,
            'signupYear' => $resolvedYear,
            'recentActivities' => $recentActivities,
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

    private function monthsOfYear(int $year)
    {
        return collect(range(1, 12))
            ->map(fn (int $month) => Carbon::create($year, $month, 1)->startOfMonth());
    }
}
