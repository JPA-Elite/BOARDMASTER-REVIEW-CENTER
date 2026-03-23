<?php

use App\Http\Controllers\Api\PaymongoCheckoutController;
use App\Http\Controllers\Api\MobileAuthController;
use App\Http\Controllers\Api\MobileDashboardController;
use App\Http\Controllers\Api\MobilePlanController;
use App\Http\Controllers\Api\MobilePracticeController;
use App\Http\Controllers\Api\MobileReferralController;
use App\Http\Controllers\Api\MobileSubscriptionController;
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

    Route::middleware('auth:sanctum')->group(function () {
        Route::get('/me', [MobileAuthController::class, 'me']);
        Route::post('/profile', [MobileAuthController::class, 'updateProfile']);
        Route::post('/plans/select', [MobilePlanController::class, 'select']);
        Route::get('/dashboard/metrics', [MobileDashboardController::class, 'metrics']);
        Route::get('/referrals', [MobileReferralController::class, 'index']);
        Route::post('/referrals/apply', [MobileReferralController::class, 'apply']);
        Route::post('/referrals/redeem', [MobileReferralController::class, 'redeem']);
        Route::get('/subscriptions/history', [MobileSubscriptionController::class, 'history']);
        Route::get('/subjects', [MobilePracticeController::class, 'subjects']);
        Route::post('/quiz/generate', [MobilePracticeController::class, 'generate']);
        Route::post('/quiz/submit', [MobilePracticeController::class, 'submit']);
        Route::get('/quiz/attempts', [MobilePracticeController::class, 'attempts']);
        Route::get('/quiz/attempts/{quizAttempt}', [MobilePracticeController::class, 'attemptDetails']);
        Route::post('/quiz/attempts/delete', [MobilePracticeController::class, 'deleteAttempts']);
        Route::post('/quiz/attempts/clear', [MobilePracticeController::class, 'clearAttempts']);
    });
});
