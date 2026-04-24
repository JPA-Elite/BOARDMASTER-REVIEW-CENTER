<?php

use App\Http\Controllers\DashboardRedirectController;
use App\Http\Controllers\MarketingController;
use App\Http\Controllers\PaymentsController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\Admin\AdminDashboardController;
use App\Http\Controllers\Admin\AdminPaymentGatewayController;
use App\Http\Controllers\Admin\AdminNotificationController;
use App\Http\Controllers\Admin\AdminSettingsController;
use App\Http\Controllers\Admin\FaqController;
use App\Http\Controllers\Admin\PlanController;
use App\Http\Controllers\Admin\QuestionController;
use App\Http\Controllers\Admin\QuizAttemptController;
use App\Http\Controllers\Admin\ReferralController;
use App\Http\Controllers\Admin\ReferralOfferController;
use App\Http\Controllers\Admin\ReviewController;
use App\Http\Controllers\Admin\SubjectController;
use App\Http\Controllers\Admin\UserController;
use App\Http\Controllers\Admin\UserSubscriptionController;
use Illuminate\Support\Facades\Route;

Route::middleware('throttle:public-pages')->group(function () {
    Route::get('/', [MarketingController::class, 'home']);
    Route::get('/partnership', [MarketingController::class, 'partnership']);
    Route::get('/terms-of-use', [MarketingController::class, 'terms']);
    Route::get('/privacy-policy', [MarketingController::class, 'privacy']);
    Route::get('/contact', [MarketingController::class, 'contact']);
    Route::get('/vision-mission', [MarketingController::class, 'visionMission']);
});

Route::get('/payments/success', [PaymentsController::class, 'success'])
    ->name('payments.success');
Route::get('/payments/cancel', [PaymentsController::class, 'cancel'])
    ->name('payments.cancel');

Route::get('/dashboard', DashboardRedirectController::class)
    ->middleware(['auth', 'verified'])
    ->name('dashboard');

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::post('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

Route::middleware(['auth', 'verified', 'admin'])
    ->prefix('admin')
    ->as('admin.')
    ->group(function () {
        Route::get('/dashboard', AdminDashboardController::class)->name('dashboard');
        Route::get('/settings', [AdminSettingsController::class, 'index'])->name('settings');
        Route::get('/payment-gateway', [AdminPaymentGatewayController::class, 'index'])->name('payment-gateway');
        Route::post('/payment-gateway/test-connection', [AdminPaymentGatewayController::class, 'testConnection'])
            ->name('payment-gateway.test');
        Route::post('/payment-gateway/checkout', [AdminPaymentGatewayController::class, 'createCheckout'])
            ->name('payment-gateway.checkout');
        Route::delete('/payment-gateway/webhooks', [AdminPaymentGatewayController::class, 'clearWebhooks'])
            ->name('payment-gateway.webhooks.clear');
        Route::delete('/payment-gateway/webhooks/{transaction}', [AdminPaymentGatewayController::class, 'deleteWebhook'])
            ->name('payment-gateway.webhooks.delete');
        Route::get('/notifications', [AdminNotificationController::class, 'index'])
            ->name('notifications.index');
        Route::delete('/notifications', [AdminNotificationController::class, 'clear'])
            ->name('notifications.clear');
        Route::delete('/notifications/{notification}', [AdminNotificationController::class, 'destroy'])
            ->name('notifications.destroy');
        Route::post('/notifications/mark-read', [AdminNotificationController::class, 'markRead'])
            ->name('notifications.mark-read');
        Route::resource('users', UserController::class)->except('show');
        Route::resource('referrals', ReferralController::class)->except('show');
        Route::patch('/referral-offers/settings', [ReferralOfferController::class, 'updateSettings'])
            ->name('referral-offers.settings');
        Route::resource('referral-offers', ReferralOfferController::class)->except('show');
        Route::patch('/plans/rename-group', [PlanController::class, 'renameGroup'])
            ->name('plans.rename-group');
        Route::resource('plans', PlanController::class)->except('show');
        Route::resource('subscriptions', UserSubscriptionController::class)->except('show');
        Route::resource('subjects', SubjectController::class)->except('show');
        Route::get('/questions/import', [QuestionController::class, 'importForm'])
            ->name('questions.import-form');
        Route::get('/questions/import-template', [QuestionController::class, 'template'])
            ->name('questions.template');
        Route::post('/questions/import', [QuestionController::class, 'import'])
            ->name('questions.import');
        Route::resource('questions', QuestionController::class)->except('show');
        Route::resource('faqs', FaqController::class)->except('show');
        Route::patch('/reviews/{review}/toggle', [ReviewController::class, 'toggle'])
            ->name('reviews.toggle');
        Route::resource('reviews', ReviewController::class)->only(['index', 'destroy']);
        Route::resource('quiz-attempts', QuizAttemptController::class)->only(['index', 'show', 'destroy']);
    });

require __DIR__.'/auth.php';
