<?php

namespace App\Application\Teams\Queries;

use App\Application\Teams\Contracts\TeamRepository;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class ListTeams
{
    public function __construct(
        private readonly TeamRepository $teams,
    ) {}

    public function handle(string $search = '', int $perPage = 10): LengthAwarePaginator
    {
        return $this->teams->paginate($search, $perPage);
    }
}
