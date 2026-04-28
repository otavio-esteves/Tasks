<?php

namespace App\Application\Categories\Queries;

use App\Application\Categories\Contracts\CategoryRepository;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class ListCategories
{
    public function __construct(
        private readonly CategoryRepository $categories,
    ) {}

    public function handle(string $search = '', int $perPage = 10): LengthAwarePaginator
    {
        return $this->categories->paginate($search, $perPage);
    }
}
