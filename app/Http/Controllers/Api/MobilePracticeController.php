<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\QuizAttempt;
use App\Services\Mobile\MobilePracticeService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class MobilePracticeController extends Controller
{
    public function __construct(private readonly MobilePracticeService $practiceService)
    {
    }

    public function subjects(Request $request): JsonResponse
    {
        return $this->practiceService->subjects($request);
    }

    public function generate(Request $request): JsonResponse
    {
        return $this->practiceService->generate($request);
    }

    public function submit(Request $request): JsonResponse
    {
        return $this->practiceService->submit($request);
    }

    public function attempts(Request $request): JsonResponse
    {
        return $this->practiceService->attempts($request);
    }

    public function attemptDetails(Request $request, QuizAttempt $quizAttempt): JsonResponse
    {
        return $this->practiceService->attemptDetails($request, $quizAttempt);
    }

    public function deleteAttempts(Request $request): JsonResponse
    {
        return $this->practiceService->deleteAttempts($request);
    }

    public function clearAttempts(Request $request): JsonResponse
    {
        return $this->practiceService->clearAttempts($request);
    }
}
