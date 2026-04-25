<?php

namespace App\Providers;

use App\Repositories\Contracts\PlanRepositoryInterface;
use App\Repositories\Contracts\QuestionRepositoryInterface;
use App\Repositories\Contracts\QuizAttemptRepositoryInterface;
use App\Repositories\Contracts\ReferralRepositoryInterface;
use App\Repositories\Contracts\ReferralOfferRepositoryInterface;
use App\Repositories\Contracts\SubjectRepositoryInterface;
use App\Repositories\Contracts\UserRepositoryInterface;
use App\Repositories\Contracts\UserSubscriptionRepositoryInterface;
use App\Repositories\Contracts\PaymentTransactionRepositoryInterface;
use App\Repositories\Contracts\FaqRepositoryInterface;
use App\Repositories\Eloquent\FaqRepository;
use App\Repositories\Contracts\ReviewRepositoryInterface;
use App\Repositories\Eloquent\ReviewRepository;
use App\Repositories\Eloquent\PlanRepository;
use App\Repositories\Eloquent\QuestionRepository;
use App\Repositories\Eloquent\QuizAttemptRepository;
use App\Repositories\Eloquent\ReferralRepository;
use App\Repositories\Eloquent\ReferralOfferRepository;
use App\Repositories\Eloquent\SubjectRepository;
use App\Repositories\Eloquent\UserRepository;
use App\Repositories\Eloquent\UserSubscriptionRepository;
use App\Repositories\Eloquent\PaymentTransactionRepository;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Vite;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->bind(UserRepositoryInterface::class, UserRepository::class);
        $this->app->bind(ReferralRepositoryInterface::class, ReferralRepository::class);
        $this->app->bind(ReferralOfferRepositoryInterface::class, ReferralOfferRepository::class);
        $this->app->bind(PlanRepositoryInterface::class, PlanRepository::class);
        $this->app->bind(SubjectRepositoryInterface::class, SubjectRepository::class);
        $this->app->bind(QuestionRepositoryInterface::class, QuestionRepository::class);
        $this->app->bind(UserSubscriptionRepositoryInterface::class, UserSubscriptionRepository::class);
        $this->app->bind(QuizAttemptRepositoryInterface::class, QuizAttemptRepository::class);
        $this->app->bind(FaqRepositoryInterface::class, FaqRepository::class);
        $this->app->bind(ReviewRepositoryInterface::class, ReviewRepository::class);
        $this->app->bind(PaymentTransactionRepositoryInterface::class, PaymentTransactionRepository::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Schema::defaultStringLength(191);
        Vite::prefetch(concurrency: 3);

        RateLimiter::for('public-pages', function (Request $request) {
            return Limit::perMinute(120)->by($request->ip());
        });

        RateLimiter::for('login', function (Request $request) {
            $email = strtolower($request->string('email'));
            return Limit::perMinute(60)->by($email);
        });

        RateLimiter::for('login-ip', function (Request $request) {
            return Limit::perMinute(20)->by($request->ip());
        });

        RateLimiter::for('mobile-auth', function (Request $request) {
            return Limit::perMinute(20)->by($request->ip());
        });

        RateLimiter::for('mobile-public', function (Request $request) {
            return Limit::perMinute(60)->by($request->ip());
        });

        RateLimiter::for('mobile-api', function (Request $request) {
            $key = $request->user()?->id ?? $request->ip();
            return Limit::perMinute(120)->by($key);
        });
    }
}
