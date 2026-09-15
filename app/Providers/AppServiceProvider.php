<?php

namespace App\Providers;

use App\Application\Categories\Contracts\CategoryRepository;
use App\Application\System\Contracts\SystemSettingRepository;
use App\Application\Tasks\Contracts\TaskAttachmentRepository;
use App\Application\Tasks\Contracts\TaskRepository;
use App\Application\Teams\Contracts\TeamRepository;
use App\Application\Users\Contracts\UserRepository;
use App\Infrastructure\Persistence\Eloquent\EloquentCategoryRepository;
use App\Infrastructure\Persistence\Eloquent\EloquentSystemSettingRepository;
use App\Infrastructure\Persistence\Eloquent\EloquentTaskAttachmentRepository;
use App\Infrastructure\Persistence\Eloquent\EloquentTaskRepository;
use App\Infrastructure\Persistence\Eloquent\EloquentTeamRepository;
use App\Infrastructure\Persistence\Eloquent\EloquentUserRepository;
use App\Models\Category;
use App\Models\Task;
use App\Models\Team;
use App\Models\User;
use App\Policies\CategoryPolicy;
use App\Policies\TaskPolicy;
use App\Policies\TeamPolicy;
use App\Policies\UserPolicy;
use Illuminate\Pagination\Paginator;
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
        $this->app->bind(TaskAttachmentRepository::class, EloquentTaskAttachmentRepository::class);
        $this->app->bind(UserRepository::class, EloquentUserRepository::class);
        $this->app->bind(SystemSettingRepository::class, EloquentSystemSettingRepository::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Paginator::defaultView('vendor.pagination.tasks');

        Gate::policy(Category::class, CategoryPolicy::class);
        Gate::policy(Team::class, TeamPolicy::class);
        Gate::policy(Task::class, TaskPolicy::class);
        Gate::policy(User::class, UserPolicy::class);
    }
}
