<?php

namespace Tests\Unit;

use App\Application\Categories\Contracts\CategoryRepository;
use App\Application\Secretariats\Contracts\SecretariatRepository;
use App\Application\ServiceOrders\Contracts\ServiceOrderRepository;
use App\Infrastructure\Persistence\Eloquent\EloquentCategoryRepository;
use App\Infrastructure\Persistence\Eloquent\EloquentSecretariatRepository;
use App\Infrastructure\Persistence\Eloquent\EloquentServiceOrderRepository;
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
            EloquentSecretariatRepository::class,
            app(SecretariatRepository::class)
        );

        $this->assertInstanceOf(
            EloquentServiceOrderRepository::class,
            app(ServiceOrderRepository::class)
        );
    }
}
