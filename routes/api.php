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
    Route::get('/me', [MobileAuthController::class, 'me'])
        ->middleware('auth:sanctum');
    Route::post('/profile', [MobileAuthController::class, 'updateProfile'])
        ->middleware('auth:sanctum');
    Route::post('/plans/select', [MobilePlanController::class, 'select'])
        ->middleware('auth:sanctum');
    Route::get('/dashboard/metrics', [MobileDashboardController::class, 'metrics'])
        ->middleware('auth:sanctum');
    Route::get('/referrals', [MobileReferralController::class, 'index'])
        ->middleware('auth:sanctum');
    Route::post('/referrals/apply', [MobileReferralController::class, 'apply'])
        ->middleware('auth:sanctum');
    Route::post('/referrals/redeem', [MobileReferralController::class, 'redeem'])
        ->middleware('auth:sanctum');
    Route::get('/subscriptions/history', [MobileSubscriptionController::class, 'history'])
        ->middleware('auth:sanctum');
    Route::get('/subjects', [MobilePracticeController::class, 'subjects'])
        ->middleware('auth:sanctum');
    Route::post('/quiz/generate', [MobilePracticeController::class, 'generate'])
        ->middleware('auth:sanctum');
    Route::post('/quiz/submit', [MobilePracticeController::class, 'submit'])
        ->middleware('auth:sanctum');
    Route::get('/quiz/attempts', [MobilePracticeController::class, 'attempts'])
        ->middleware('auth:sanctum');
    Route::get('/quiz/attempts/{quizAttempt}', [MobilePracticeController::class, 'attemptDetails'])
        ->middleware('auth:sanctum');
});
