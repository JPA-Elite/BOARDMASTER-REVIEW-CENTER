<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\Mobile\MobileReviewService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class MobileReviewController extends Controller
{
    public function __construct(private readonly MobileReviewService $reviews)
    {
    }

    public function store(Request $request): JsonResponse
    {
        return $this->reviews->store($request);
    }
}
