<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\Mobile\MobileDashboardService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class MobileDashboardController extends Controller
{
    public function __construct(private readonly MobileDashboardService $dashboardService)
    {
    }

    public function metrics(Request $request): JsonResponse
    {
        return $this->dashboardService->metrics($request);
    }
}
