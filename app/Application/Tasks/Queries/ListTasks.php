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
     * @param  array{category_id?:int|null,assignee_id?:int|null,status?:string|null,urgent?:bool|null,quick_filter?:'pending'|'urgent'|'in_progress'|'completed'|'overdue'|null,indicator_start_date?:string|null,indicator_end_date?:string|null,indicator_grouping?:'daily'|'weekly'|'monthly'|'yearly'|null}  $filters
     */
    public function handle(int $teamId, string $search = '', array $filters = [], int $perPage = 15): TaskListResult
    {
        return $this->tasks->listForTeam($teamId, $search, $filters, $perPage);
    }
}
