<?php

namespace App\Application\Tasks\Queries;

use App\Application\Tasks\Contracts\TaskRepository;
use App\Application\Tasks\Data\TaskListResult;

class ListTasks
{
    public function __construct(
        private readonly TaskRepository $tasks,
    ) {}

    /**
     * @param  array{category_id?:int|null,status?:string|null,urgent?:bool|null,quick_filter?:string|null}  $filters
     */
    public function handle(int $teamId, string $search = '', array $filters = [], int $perPage = 15): TaskListResult
    {
        return $this->tasks->listForTeam($teamId, $search, $filters, $perPage);
    }
}
