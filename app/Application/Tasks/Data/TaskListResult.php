<?php

namespace App\Application\Tasks\Data;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;

final readonly class TaskListResult
{
    /**
     * @param  array{total:int, urgent:int, overdue:int, in_progress:int, completed:int}  $summary
     * @param  list<array{label:string,value:int}>  $monthlyTasks
     */
    public function __construct(
        public LengthAwarePaginator $tasks,
        public array $summary,
        public array $monthlyTasks,
    ) {}
}
