<?php

use App\Http\Controllers\Api\PaymongoCheckoutController;
use App\Http\Controllers\Api\MobileAuthController;
use App\Http\Controllers\Api\MobilePlanController;
use App\Http\Controllers\Webhooks\PaymongoWebhookController;
use Illuminate\Support\Facades\Route;

Route::post('/webhooks/paymongo', PaymongoWebhookController::class)
    ->name('api.webhooks.paymongo');

Route::post('/paymongo/checkout', [PaymongoCheckoutController::class, 'store'])
    ->middleware('auth:sanctum')
    ->name('api.paymongo.checkout');

Route::prefix('mobile')->group(function () {
    Route::post('/register', [MobileAuthController::class, 'register']);
    Route::post('/login', [MobileAuthController::class, 'login']);
    Route::get('/plans', [MobilePlanController::class, 'index']);
    Route::post('/plans/select', [MobilePlanController::class, 'select'])
        ->middleware('auth:sanctum');
});
