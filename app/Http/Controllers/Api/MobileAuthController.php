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

    public function loginWithGoogle(Request $request): JsonResponse
    {
        return $this->authService->loginWithGoogle($request);
    }

    public function loginWithApple(Request $request): JsonResponse
    {
        return $this->authService->loginWithApple($request);
    }

    public function updateProfile(Request $request): JsonResponse
    {
        return $this->authService->updateProfile($request);
    }

    public function forgotPassword(Request $request): JsonResponse
    {
        return $this->authService->forgotPassword($request);
    }

    public function resendVerification(Request $request): JsonResponse
    {
        return $this->authService->resendVerification($request);
    }

    public function checkEmail(Request $request): JsonResponse
    {
        return $this->authService->checkEmail($request);
    }

    public function verifyEmail(Request $request, int $id, string $hash)
    {
        return $this->authService->verifyEmail($request, $id, $hash);
    }

    public function me(Request $request): JsonResponse
    {
        return $this->authService->me($request);
    }

    public function logout(Request $request): JsonResponse
    {
        return $this->authService->logout($request);
    }

    public function markDemoSeen(Request $request): JsonResponse
    {
        return $this->authService->markDemoSeen($request);
    }

    public function destroy(Request $request): JsonResponse
    {
        return $this->authService->destroy($request);
    }
}
