<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\Payments\PaymongoCheckoutService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PaymongoCheckoutController extends Controller
{
    public function __construct(private readonly PaymongoCheckoutService $checkoutService)
    {
    }

    public function store(Request $request): JsonResponse
    {
        return $this->checkoutService->store($request);
    }
}
