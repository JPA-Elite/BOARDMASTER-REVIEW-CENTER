<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\Payments\MobileInAppPurchaseService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class MobileInAppPurchaseController extends Controller
{
    public function __construct(private readonly MobileInAppPurchaseService $inAppPurchaseService)
    {
    }

    public function complete(Request $request): JsonResponse
    {
        return $this->inAppPurchaseService->complete($request);
    }
}

