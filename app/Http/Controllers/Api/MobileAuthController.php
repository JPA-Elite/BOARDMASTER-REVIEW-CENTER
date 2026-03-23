<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\Mobile\MobileAuthService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class MobileAuthController extends Controller
{
    public function __construct(private readonly MobileAuthService $authService)
    {
    }

    public function register(Request $request): JsonResponse
    {
        return $this->authService->register($request);
    }

    public function login(Request $request): JsonResponse
    {
        return $this->authService->login($request);
    }

    public function updateProfile(Request $request): JsonResponse
    {
        return $this->authService->updateProfile($request);
    }

    public function me(Request $request): JsonResponse
    {
        return $this->authService->me($request);
    }
}
