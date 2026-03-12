<?php

use App\Http\Controllers\ProfileController;
use App\Http\Controllers\Admin\AdminDashboardController;
use App\Http\Controllers\Admin\PlanController;
use App\Http\Controllers\Admin\QuestionController;
use App\Http\Controllers\Admin\QuizAttemptController;
use App\Http\Controllers\Admin\ReferralController;
use App\Http\Controllers\Admin\SubjectController;
use App\Http\Controllers\Admin\UserController;
use App\Http\Controllers\Admin\UserSubscriptionController;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::get('/', function () {
    return Inertia::render('Welcome');
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

Route::get('/contact', function () {
    return Inertia::render('Contact');
});

Route::get('/dashboard', function () {
    return Inertia::render('Dashboard');
})->middleware(['auth', 'verified'])->name('dashboard');

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

Route::middleware(['auth', 'verified', 'admin'])
    ->prefix('admin')
    ->as('admin.')
    ->group(function () {
        Route::get('/', AdminDashboardController::class)->name('dashboard');
        Route::resource('users', UserController::class)->except('show');
        Route::resource('referrals', ReferralController::class)->except('show');
        Route::resource('plans', PlanController::class)->except('show');
        Route::resource('subscriptions', UserSubscriptionController::class)->except('show');
        Route::resource('subjects', SubjectController::class)->except('show');
        Route::resource('questions', QuestionController::class)->except('show');
        Route::resource('quiz-attempts', QuizAttemptController::class)->only(['index', 'show', 'destroy']);
    });

require __DIR__.'/auth.php';
