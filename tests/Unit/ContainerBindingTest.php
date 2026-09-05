<?php

namespace Tests\Unit;

use App\Application\Categories\Contracts\CategoryRepository;
use App\Application\Tasks\Contracts\TaskRepository;
use App\Application\Teams\Contracts\TeamRepository;
use App\Infrastructure\Persistence\Eloquent\EloquentCategoryRepository;
use App\Infrastructure\Persistence\Eloquent\EloquentTaskRepository;
use App\Infrastructure\Persistence\Eloquent\EloquentTeamRepository;
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
    }
}
