<?php

use App\Http\Controllers\Api\PaymongoCheckoutController;
use App\Http\Controllers\Api\MobileInAppPurchaseController;
use App\Http\Controllers\Api\MobileAuthController;
use App\Http\Controllers\Api\MobileDashboardController;
use App\Http\Controllers\Api\MobilePlanController;
use App\Http\Controllers\Api\MobilePracticeController;
use App\Http\Controllers\Api\MobileReviewController;
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
    Route::middleware('throttle:mobile-auth')->group(function () {
        Route::post('/register', [MobileAuthController::class, 'register']);
        Route::post('/login', [MobileAuthController::class, 'login']);
        Route::post('/login/google', [MobileAuthController::class, 'loginWithGoogle']);
        Route::post('/login/apple', [MobileAuthController::class, 'loginWithApple']);
        Route::post('/password/forgot', [MobileAuthController::class, 'forgotPassword']);
        Route::post('/email/check', [MobileAuthController::class, 'checkEmail']);
    });
    Route::post('/email/verification-notification', [MobileAuthController::class, 'resendVerification'])
        ->middleware('throttle:6,1');
    Route::get('/email/verify/{id}/{hash}', [MobileAuthController::class, 'verifyEmail'])
        ->middleware(['signed', 'throttle:6,1'])
        ->name('api.mobile.verification.verify');
    Route::get('/plans', [MobilePlanController::class, 'index'])
        ->middleware('throttle:mobile-public');

    Route::middleware(['auth:sanctum', 'throttle:mobile-api'])->group(function () {
        Route::get('/me', [MobileAuthController::class, 'me']);
        Route::post('/demo/seen', [MobileAuthController::class, 'markDemoSeen']);
        Route::post('/profile', [MobileAuthController::class, 'updateProfile']);
        Route::delete('/account', [MobileAuthController::class, 'destroy']);
        Route::post('/plans/select', [MobilePlanController::class, 'select']);
        Route::post('/in-app-purchases/complete', [MobileInAppPurchaseController::class, 'complete']);
        Route::get('/dashboard/metrics', [MobileDashboardController::class, 'metrics']);
        Route::get('/referrals', [MobileReferralController::class, 'index']);
        Route::post('/referrals/apply', [MobileReferralController::class, 'apply']);
        Route::post('/referrals/redeem', [MobileReferralController::class, 'redeem']);
        Route::get('/subscriptions/history', [MobileSubscriptionController::class, 'history']);
        Route::get('/subjects', [MobilePracticeController::class, 'subjects']);
        Route::post('/reviews', [MobileReviewController::class, 'store']);
        Route::post('/quiz/generate', [MobilePracticeController::class, 'generate']);
        Route::post('/quiz/submit', [MobilePracticeController::class, 'submit']);
        Route::get('/quiz/attempts', [MobilePracticeController::class, 'attempts']);
        Route::get('/quiz/attempts/{quizAttempt}', [MobilePracticeController::class, 'attemptDetails']);
        Route::post('/quiz/attempts/delete', [MobilePracticeController::class, 'deleteAttempts']);
        Route::post('/quiz/attempts/clear', [MobilePracticeController::class, 'clearAttempts']);
    });
});
