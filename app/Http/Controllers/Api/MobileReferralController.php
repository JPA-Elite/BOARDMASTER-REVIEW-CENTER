<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\Mobile\MobileReferralService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class MobileReferralController extends Controller
{
    public function __construct(private readonly MobileReferralService $referralService)
    {
    }

    public function index(Request $request): JsonResponse
    {
        return $this->referralService->index($request);
    }

    public function apply(Request $request): JsonResponse
    {
        return $this->referralService->apply($request);
    }

    public function redeem(Request $request): JsonResponse
    {
        return $this->referralService->redeem($request);
    }
}
