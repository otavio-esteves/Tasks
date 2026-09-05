<?php

namespace App\Application\Tasks\Data;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;

final readonly class TaskListResult
{
    /**
     * @param  array{total:int, urgent:int, overdue:int, in_progress:int, completed:int}  $summary
     */
    public function __construct(
        public LengthAwarePaginator $tasks,
        public array $summary,
    ) {}
}
