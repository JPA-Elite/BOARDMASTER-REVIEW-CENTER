<?php

namespace App\Http\Controllers\Webhooks;

use App\Http\Controllers\Controller;
use App\Services\Payments\PaymongoWebhookService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PaymongoWebhookController extends Controller
{
    public function __construct(private readonly PaymongoWebhookService $webhookService)
    {
    }

    public function __invoke(Request $request): JsonResponse
    {
        return $this->webhookService->handle($request);
    }
}
