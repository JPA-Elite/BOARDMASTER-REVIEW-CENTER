<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\Mobile\MobileSubscriptionService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class MobileSubscriptionController extends Controller
{
    public function __construct(private readonly MobileSubscriptionService $subscriptionService)
    {
    }

    public function history(Request $request): JsonResponse
    {
        return $this->subscriptionService->history($request);
    }
}
