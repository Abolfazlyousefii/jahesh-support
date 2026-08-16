<?php

namespace App\Providers;

use App\Models\Task;
use App\Models\User;
use App\Policies\TaskPolicy;
use App\Support\DatePresenter;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->singleton(DatePresenter::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Gate::policy(Task::class, TaskPolicy::class);

        Gate::before(function (User $user): ?bool {
            return $user->hasRole('super-admin') ? true : null;
        });
    }
}
