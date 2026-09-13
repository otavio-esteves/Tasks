<?php

namespace App\Application\Tasks\Data;

use App\Models\Task;
use Illuminate\Support\Collection;

final readonly class TaskReportResult
{
    /**
     * @param  Collection<int, Task>  $tasks
     * @param  array{total:int,pending:int,in_progress:int,completed:int,urgent:int,overdue:int}  $summary
     */
    public function __construct(
        public Collection $tasks,
        public array $summary,
    ) {}
}
