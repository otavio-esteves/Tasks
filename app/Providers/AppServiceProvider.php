<?php

namespace App\Providers;

use App\Application\Categories\Contracts\CategoryRepository;
use App\Application\Tasks\Contracts\TaskRepository;
use App\Application\Teams\Contracts\TeamRepository;
use App\Infrastructure\Persistence\Eloquent\EloquentCategoryRepository;
use App\Infrastructure\Persistence\Eloquent\EloquentTaskRepository;
use App\Infrastructure\Persistence\Eloquent\EloquentTeamRepository;
use App\Models\Category;
use App\Models\Task;
use App\Models\Team;
use App\Policies\CategoryPolicy;
use App\Policies\TaskPolicy;
use App\Policies\TeamPolicy;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->bind(CategoryRepository::class, EloquentCategoryRepository::class);
        $this->app->bind(TeamRepository::class, EloquentTeamRepository::class);
        $this->app->bind(TaskRepository::class, EloquentTaskRepository::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Gate::policy(Category::class, CategoryPolicy::class);
        Gate::policy(Team::class, TeamPolicy::class);
        Gate::policy(Task::class, TaskPolicy::class);
    }
}
