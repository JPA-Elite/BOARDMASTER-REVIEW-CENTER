<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\Mobile\MobilePlanService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class MobilePlanController extends Controller
{
    public function __construct(private readonly MobilePlanService $planService)
    {
    }

    public function index(): JsonResponse
    {
        return $this->planService->index();
    }

    public function select(Request $request): JsonResponse
    {
        return $this->planService->select($request);
    }
}
