<?php

namespace App\Providers;

use App\Repositories\Contracts\PlanRepositoryInterface;
use App\Repositories\Contracts\QuestionRepositoryInterface;
use App\Repositories\Contracts\QuizAttemptRepositoryInterface;
use App\Repositories\Contracts\ReferralRepositoryInterface;
use App\Repositories\Contracts\SubjectRepositoryInterface;
use App\Repositories\Contracts\UserRepositoryInterface;
use App\Repositories\Contracts\UserSubscriptionRepositoryInterface;
use App\Repositories\Eloquent\PlanRepository;
use App\Repositories\Eloquent\QuestionRepository;
use App\Repositories\Eloquent\QuizAttemptRepository;
use App\Repositories\Eloquent\ReferralRepository;
use App\Repositories\Eloquent\SubjectRepository;
use App\Repositories\Eloquent\UserRepository;
use App\Repositories\Eloquent\UserSubscriptionRepository;
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
        $this->app->bind(PlanRepositoryInterface::class, PlanRepository::class);
        $this->app->bind(SubjectRepositoryInterface::class, SubjectRepository::class);
        $this->app->bind(QuestionRepositoryInterface::class, QuestionRepository::class);
        $this->app->bind(UserSubscriptionRepositoryInterface::class, UserSubscriptionRepository::class);
        $this->app->bind(QuizAttemptRepositoryInterface::class, QuizAttemptRepository::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Vite::prefetch(concurrency: 3);
    }
}
