<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\Admin\DashboardService;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class AdminDashboardController extends Controller
{
    public function __construct(private readonly DashboardService $dashboard)
    {
    }

    public function __invoke(Request $request): Response
    {
        $year = $request->query('year');
        $selectedYear = is_numeric($year) ? (int) $year : null;
        $activityPage = max(1, (int) $request->query('page', 1));

        return Inertia::render(
            'Admin/Dashboard',
            $this->dashboard->getDashboardData($selectedYear, $activityPage, 10),
        );
    }
}
