<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\Admin\AnalyticsService;
use Inertia\Inertia;
use Inertia\Response;

class AdminAnalyticsController extends Controller
{
    public function __construct(private readonly AnalyticsService $analytics)
    {
    }

    public function __invoke(): Response
    {
        return Inertia::render('Admin/Analytics', $this->analytics->getAnalyticsData());
    }
}
