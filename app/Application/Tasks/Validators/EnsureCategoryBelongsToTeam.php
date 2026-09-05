<?php

namespace App\Application\Tasks\Validators;

use App\Application\Categories\Contracts\CategoryRepository;
use App\Domain\Tasks\Exceptions\InvalidTaskCategory;

class EnsureCategoryBelongsToTeam
{
    public function __construct(
        private readonly CategoryRepository $categories,
    ) {}

    public function handle(int $teamId, int $categoryId): void
    {
        if (! $this->categories->belongsToTeam($categoryId, $teamId)) {
            throw new InvalidTaskCategory;
        }
    }
}
