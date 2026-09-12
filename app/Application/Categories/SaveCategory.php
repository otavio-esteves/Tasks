<?php

namespace App\Application\Categories;

use App\Application\Categories\Contracts\CategoryRepository;
use App\Application\Categories\Data\CategoryMutationData;
use App\Domain\Categories\Exceptions\CategoryHasActiveTasks;
use App\Domain\Categories\Exceptions\CategorySlugAlreadyExists;
use App\Models\Category;
use Illuminate\Support\Str;

class SaveCategory
{
    public function __construct(
        private readonly CategoryRepository $categories,
        private readonly GetCategory $getCategory,
    ) {}

    public function handle(?int $categoryId, CategoryMutationData $data): Category
    {
        $category = $categoryId === null ? null : $this->getCategory->handle($categoryId);
        $slug = Str::slug($data->name);

        if ($category !== null
            && $category->team_id !== $data->teamId
            && $this->categories->hasActiveTasks($category)) {
            throw CategoryHasActiveTasks::preventsMoving();
        }

        if ($this->categories->slugExistsForTeam($data->teamId, $slug, $category?->id)) {
            throw new CategorySlugAlreadyExists;
        }

        return $this->categories->save($category, $data, $slug);
    }
}
