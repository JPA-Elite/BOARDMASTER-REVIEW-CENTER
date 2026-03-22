<?php

use App\Http\Controllers\ProfileController;
use App\Http\Controllers\Admin\AdminDashboardController;
use App\Http\Controllers\Admin\AdminAnalyticsController;
use App\Http\Controllers\Admin\AdminPaymentGatewayController;
use App\Http\Controllers\Admin\AdminNotificationController;
use App\Http\Controllers\Admin\AdminSettingsController;
use App\Http\Controllers\Admin\FaqController;
use App\Http\Controllers\Admin\PlanController;
use App\Http\Controllers\Admin\QuestionController;
use App\Http\Controllers\Admin\QuizAttemptController;
use App\Http\Controllers\Admin\ReferralController;
use App\Http\Controllers\Admin\ReviewController;
use App\Http\Controllers\Admin\SubjectController;
use App\Http\Controllers\Admin\UserController;
use App\Http\Controllers\Admin\UserSubscriptionController;
use App\Repositories\Contracts\FaqRepositoryInterface;
use App\Repositories\Contracts\ReviewRepositoryInterface;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::get('/', function (FaqRepositoryInterface $faqsRepository) {
    $faqs = $faqsRepository->listActive();

    if ($faqs->isEmpty()) {
        $faqs = collect([
            [
                'question' => 'Does Board Masters focus on Nursing only?',
                'answer' =>
                    'Nursing is a primary focus, but we also support other major Philippine licensure tracks.',
            ],
            [
                'question' => 'Can review centers request demo access?',
                'answer' =>
                    'Yes. Contact us by email so we can discuss a pilot setup based on your student batch size.',
            ],
            [
                'question' => 'Where should we report question issues?',
                'answer' =>
                    'Send the item details and screenshot to boardmastersreviewcenter@gmail.com for content review.',
            ],
        ]);
    }

    $reviewsRepository = app(ReviewRepositoryInterface::class);
    $reviews = $reviewsRepository->listActive(3);

    if ($reviews->isEmpty()) {
        $reviews = collect([
            [
                'reviewer_name' => 'Patrick Bat-Og',
                'reviewer_school' => 'Asia Pacific College',
                'quote' =>
                    'Board Masters is the biggest game changer. It stopped me from spending too much time on topics I already mastered.',
            ],
            [
                'reviewer_name' => 'Sophie Dizon',
                'reviewer_school' => 'Wesleyan University',
                'quote' =>
                    'The step-by-step explanations made the lessons stick. It finally clicked for me.',
            ],
            [
                'reviewer_name' => 'Matt Singson',
                'reviewer_school' => 'Ateneo de Naga',
                'quote' =>
                    'Everything is organized in one app. I can practice on the bus and still track my progress.',
            ],
        ]);
    }

    return Inertia::render('Welcome', [
        'faqs' => $faqs->shuffle()->take(3)->values()->all(),
        'reviews' => $reviews->shuffle()->take(3)->values()->all(),
    ]);
});

Route::get('/partnership', function () {
    return Inertia::render('Partnership');
});

Route::get('/terms-of-use', function () {
    return Inertia::render('TermsOfUse');
});

Route::get('/privacy-policy', function () {
    return Inertia::render('PrivacyPolicy');
});

Route::get('/contact', function (FaqRepositoryInterface $faqsRepository) {
    $faqs = $faqsRepository->paginateActive(6);

    if ($faqs->total() === 0) {
        $faqs = [
            [
                'question' => 'Does Board Masters focus on Nursing only?',
                'answer' =>
                    'Nursing is a primary focus, but we also support other major Philippine licensure tracks.',
            ],
            [
                'question' => 'Can review centers request demo access?',
                'answer' =>
                    'Yes. Contact us by email so we can discuss a pilot setup based on your student batch size.',
            ],
            [
                'question' => 'Where should we report question issues?',
                'answer' =>
                    'Send the item details and screenshot to boardmastersreviewcenter@gmail.com for content review.',
            ],
        ];
    }

    return Inertia::render('Contact', [
        'faqs' => $faqs,
    ]);
});

Route::get('/vision-mission', function () {
    return Inertia::render('VisionMission');
});

Route::get('/payments/success', function () {
    return Inertia::render('Payments/Success');
})->name('payments.success');

Route::get('/payments/cancel', function () {
    return Inertia::render('Payments/Cancel');
})->name('payments.cancel');


Route::get('/dashboard', function () {
    $user = auth()->user();

    if ($user?->role === 'admin') {
        return redirect()->route('admin.dashboard');
    }

    return redirect('/');
})->middleware(['auth', 'verified'])->name('dashboard');

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::post('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

Route::middleware(['auth', 'verified', 'admin'])
    ->prefix('admin')
    ->as('admin.')
    ->group(function () {
        Route::get('/', AdminDashboardController::class)->name('dashboard');
        Route::get('/analytics', AdminAnalyticsController::class)->name('analytics');
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
        Route::post('/notifications/mark-read', [AdminNotificationController::class, 'markRead'])
            ->name('notifications.mark-read');
        Route::resource('users', UserController::class)->except('show');
        Route::resource('referrals', ReferralController::class)->except('show');
        Route::resource('plans', PlanController::class)->except('show');
        Route::resource('subscriptions', UserSubscriptionController::class)->except('show');
        Route::resource('subjects', SubjectController::class)->except('show');
        Route::resource('questions', QuestionController::class)->except('show');
        Route::resource('faqs', FaqController::class)->except('show');
        Route::resource('reviews', ReviewController::class)->only(['index', 'destroy']);
        Route::resource('quiz-attempts', QuizAttemptController::class)->only(['index', 'show', 'destroy']);
    });

require __DIR__.'/auth.php';
