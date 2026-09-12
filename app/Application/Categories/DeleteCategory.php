<?php

namespace App\Application\Categories;

use App\Application\Categories\Contracts\CategoryRepository;
use App\Domain\Categories\Exceptions\CategoryHasActiveTasks;

class DeleteCategory
{
    public function __construct(
        private readonly GetCategory $getCategory,
        private readonly CategoryRepository $categories,
    ) {}

    public function handle(int $categoryId): void
    {
        $category = $this->getCategory->handle($categoryId);

        if ($this->categories->hasActiveTasks($category)) {
            throw CategoryHasActiveTasks::preventsDeletion();
        }

        $this->categories->delete($category);
    }
}
