<?php

namespace Tests\Unit;

use App\Application\Categories\Contracts\CategoryRepository;
use App\Application\System\Contracts\SystemSettingRepository;
use App\Application\Tasks\Contracts\TaskRepository;
use App\Application\Teams\Contracts\TeamRepository;
use App\Application\Users\Contracts\UserRepository;
use App\Infrastructure\Persistence\Eloquent\EloquentCategoryRepository;
use App\Infrastructure\Persistence\Eloquent\EloquentSystemSettingRepository;
use App\Infrastructure\Persistence\Eloquent\EloquentTaskRepository;
use App\Infrastructure\Persistence\Eloquent\EloquentTeamRepository;
use App\Infrastructure\Persistence\Eloquent\EloquentUserRepository;
use Tests\TestCase;

class ContainerBindingTest extends TestCase
{
    public function test_repositories_are_bound_to_correct_implementations(): void
    {
        $this->assertInstanceOf(
            EloquentCategoryRepository::class,
            app(CategoryRepository::class)
        );

        $this->assertInstanceOf(
            EloquentTeamRepository::class,
            app(TeamRepository::class)
        );

        $this->assertInstanceOf(
            EloquentTaskRepository::class,
            app(TaskRepository::class)
        );

        $this->assertInstanceOf(
            EloquentUserRepository::class,
            app(UserRepository::class)
        );

        $this->assertInstanceOf(
            EloquentSystemSettingRepository::class,
            app(SystemSettingRepository::class)
        );
    }
}
